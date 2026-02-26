<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = Item::with(['vendor', 'category'])
            ->where('approval_status', 'approved')
            ->latest()
            ->get();

        return response()->json([
            'message' => $items->isEmpty() ? 'No items found.' : 'Items fetched successfully.',
            'data' => $items->map(function (Item $item) {
                return $this->withImageUrls($item);
            })->values(),
        ]);
    }

    public function vendorIndex(Request $request)
    {
        $vendor = $this->ensureVendor($request);

        $items = Item::with(['vendor', 'category'])
            ->where('vendor_id', $vendor->id)
            ->latest()
            ->get();

        return response()->json([
            'message' => $items->isEmpty() ? 'No vendor items found.' : 'Vendor items fetched successfully.',
            'data' => $items->map(function (Item $item) {
                return $this->withImageUrls($item);
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $vendor = $this->ensureVendor($request);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'prep_time_value' => 'required|integer|min:1',
            'prep_time_unit' => ['required', Rule::in(['minutes', 'hours'])],
            'stock' => 'required|integer|min:0',
            'max_orders_per_day' => 'nullable|integer|min:0',
            'photos' => 'required|array|size:4',
            'photos.*' => 'required|image|max:4096',
        ]);

        $validated['vendor_id'] = $vendor->id;
        $validated['approval_status'] = 'pending';
        $validated['availability_status'] = 'paused';
        $validated['max_orders_per_day'] = $validated['max_orders_per_day'] ?? $validated['stock'];
        $validated['photos'] = $this->storePhotos($request->file('photos'));

        $item = Item::create($validated);

        return response()->json([
            'message' => 'Item created successfully and awaiting approval.',
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category'])),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $vendor = $this->ensureVendor($request);

        $item = Item::where('vendor_id', $vendor->id)->findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'prep_time_value' => 'nullable|integer|min:1',
            'prep_time_unit' => ['nullable', Rule::in(['minutes', 'hours'])],
            'stock' => 'nullable|integer|min:0',
            'max_orders_per_day' => 'nullable|integer|min:0',
            'photos' => 'nullable|array|size:4',
            'photos.*' => 'required_with:photos|image|max:4096',
        ]);

        if ($request->hasFile('photos')) {
            $this->deletePhotos($item->photos ?? []);
            $validated['photos'] = $this->storePhotos($request->file('photos'));
        }

        if (array_key_exists('stock', $validated) && $validated['stock'] <= 0) {
            $validated['availability_status'] = 'paused';
        }

        $item->update($validated);

        return response()->json([
            'message' => 'Item updated successfully.',
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category'])),
        ]);
    }

    public function approve(Request $request, int $id)
    {
        $this->ensureAdmin($request);

        $item = Item::findOrFail($id);
        $item->approval_status = 'approved';
        $item->availability_status = 'paused';
        $item->save();

        return response()->json([
            'message' => 'Item approved.',
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category'])),
        ]);
    }

    public function reject(Request $request, int $id)
    {
        $this->ensureAdmin($request);

        $item = Item::findOrFail($id);
        $item->approval_status = 'rejected';
        $item->availability_status = 'paused';
        $item->save();

        return response()->json([
            'message' => 'Item rejected.',
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category'])),
        ]);
    }

    public function publish(Request $request, int $id)
    {
        $vendor = $this->ensureVendor($request);

        $item = Item::where('vendor_id', $vendor->id)->findOrFail($id);

        if ($item->approval_status !== 'approved') {
            return response()->json([
                'message' => 'Item must be approved before publishing.',
            ], 422);
        }

        if ($item->stock <= 0) {
            return response()->json([
                'message' => 'Cannot publish item with zero stock.',
            ], 422);
        }

        $item->availability_status = 'published';
        $item->save();

        return response()->json([
            'message' => 'Item published.',
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category'])),
        ]);
    }

    public function pause(Request $request, int $id)
    {
        $vendor = $this->ensureVendor($request);

        $item = Item::where('vendor_id', $vendor->id)->findOrFail($id);

        if ($item->approval_status !== 'approved') {
            return response()->json([
                'message' => 'Item must be approved before pausing.',
            ], 422);
        }

        $item->availability_status = 'paused';
        $item->save();

        return response()->json([
            'message' => 'Item paused.',
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category'])),
        ]);
    }

    private function storePhotos(array $files): array
    {
        $paths = [];
        foreach ($files as $file) {
            $paths[] = $file->store('items', 'public');
        }
        return $paths;
    }

    private function deletePhotos(array $paths): void
    {
        foreach ($paths as $path) {
            if (! empty($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function withImageUrls(Model $model): array
    {
        $data = $model->toArray();

        if (! empty($data['photos']) && is_array($data['photos'])) {
            $data['photos'] = array_map(function ($item) {
                return $this->toPublicUrl($item);
            }, $data['photos']);
        }

        return $data;
    }

    private function toPublicUrl($value)
    {
        if (empty($value)) {
            return $value;
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $path = ltrim($value, '/');
        if (str_starts_with($path, 'storage/')) {
            return rtrim(config('app.url'), '/') . '/' . $path;
        }

        return rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;
    }

    private function ensureVendor(Request $request): Vendor
    {
        $user = $request->user();

        if (! $user || ! ($user instanceof Vendor)) {
            abort(403, 'Vendor authentication required.');
        }

        return $user;
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Admin authentication required.');
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return;
        }

        if (property_exists($user, 'type') && $user->type === 'admin') {
            return;
        }

        abort(403, 'Admin authentication required.');
    }
}
