<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function getAllItems()
    {
        $items = $this->publicItemsQuery()
            ->latest()
            ->get();

        return response()->json([
            'message' => $items->isEmpty() ? 'No items found.' : 'Items fetched successfully.',
            'data' => $items->map(function (Item $item) {
                return $this->withImageUrls($item);
            })->values(),
        ]);
    }

    public function getItemById(int $id)
    {
        $item = $this->publicItemsQuery()->findOrFail($id);

        return response()->json([
            'message' => 'Item fetched successfully.',
            'data' => $this->withImageUrls($item),
        ]);
    }

    public function bySubcategory(int $subcategoryId)
    {
        Subcategory::query()->findOrFail($subcategoryId);

        $items = $this->publicItemsQuery()
            ->whereHas('subcategories', fn ($query) => $query->where('subcategories.id', $subcategoryId))
            ->latest()
            ->get();

        return response()->json([
            'message' => $items->isEmpty()
                ? 'No items found for this subcategory.'
                : 'Items fetched successfully.',
            'data' => $items->map(function (Item $item) {
                return $this->withImageUrls($item);
            })->values(),
        ]);
    }

    public function byCategory(int $categoryId)
    {
        Category::query()->findOrFail($categoryId);

        $items = $this->publicItemsQuery()
            ->whereHas('subcategories', fn ($query) => $query->where('subcategories.category_id', $categoryId))
            ->latest()
            ->get();

        return response()->json([
            'message' => $items->isEmpty()
                ? 'No items found for this category.'
                : 'Items fetched successfully.',
            'data' => $items->map(function (Item $item) {
                return $this->withImageUrls($item);
            })->values(),
        ]);
    }

    public function indexApproved(Request $request)
    {

        $vendor = $this->ensureVendor($request);

        $items = Item::with(['vendor', 'category', 'subcategory', 'subcategories.category'])
            ->where('vendor_id', $vendor->id)
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

        $items = Item::with(['vendor', 'category', 'subcategory', 'subcategories.category'])
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
            'subcategory_ids' => 'required|array|min:1',
            'subcategory_ids.*' => 'required|exists:subcategories,id',
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

        $subcategories = $this->resolveVendorSubcategories($vendor, $validated['subcategory_ids']);
        $primarySubcategory = $subcategories->first();
        $validated['vendor_id'] = $vendor->id;
        $validated['subcategory_id'] = $primarySubcategory?->id;
        $validated['category_id'] = $primarySubcategory?->category_id;
        $validated['approval_status'] = 'pending';
        $validated['availability_status'] = 'paused';
        $validated['max_orders_per_day'] = $validated['max_orders_per_day'] ?? $validated['stock'];
        $validated['photos'] = $this->storePhotos($request->file('photos'));
        unset($validated['subcategory_ids']);

        $item = Item::create($validated);
        $item->subcategories()->sync($subcategories->pluck('id')->all());
        $vendor->categories()->syncWithoutDetaching(
            $subcategories->pluck('category_id')->filter()->unique()->values()->all()
        );

        return response()->json([
            'message' => 'Item created successfully and awaiting approval.',
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category', 'subcategory', 'subcategories.category'])),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $vendor = $this->ensureVendor($request);

        $item = Item::where('vendor_id', $vendor->id)->findOrFail($id);

        $validated = $request->validate([
            'subcategory_ids' => 'nullable|array|min:1',
            'subcategory_ids.*' => 'required|exists:subcategories,id',
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

        if (array_key_exists('subcategory_ids', $validated)) {
            $subcategories = $this->resolveVendorSubcategories($vendor, $validated['subcategory_ids']);
            $primarySubcategory = $subcategories->first();
            $validated['subcategory_id'] = $primarySubcategory?->id;
            $validated['category_id'] = $primarySubcategory?->category_id;
        } else {
            $subcategories = null;
        }

        if ($request->hasFile('photos')) {
            $this->deletePhotos($item->photos ?? []);
            $validated['photos'] = $this->storePhotos($request->file('photos'));
        }

        if (array_key_exists('stock', $validated) && $validated['stock'] <= 0) {
            $validated['availability_status'] = 'paused';
        }

        unset($validated['subcategory_ids']);

        $item->update($validated);

        if ($subcategories !== null) {
            $item->subcategories()->sync($subcategories->pluck('id')->all());
        }

        $vendorId = $validated['vendor_id'] ?? $item->vendor_id;
        $itemVendor = Vendor::find($vendorId);
        if ($itemVendor) {
            $itemVendor->categories()->syncWithoutDetaching(
                $item->subcategories()->pluck('category_id')->filter()->unique()->values()->all()
            );
        }

        return response()->json([
            'message' => 'Item updated successfully.',
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category', 'subcategory', 'subcategories.category'])),
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
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category', 'subcategory', 'subcategories.category'])),
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
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category', 'subcategory', 'subcategories.category'])),
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
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category', 'subcategory', 'subcategories.category'])),
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
            'data' => $this->withImageUrls($item->fresh(['vendor', 'category', 'subcategory', 'subcategories.category'])),
        ]);
    }

    private function publicItemsQuery()
    {
        return Item::with(['vendor', 'category', 'subcategory', 'subcategories.category'])
            ->where('approval_status', 'approved')
            ->where('availability_status', 'published');
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

    private function resolveVendorSubcategories(Vendor $vendor, array $subcategoryIds)
    {
        $vendor->loadMissing(['subcategories:id']);

        $resolved = Subcategory::query()
            ->whereIn('id', $subcategoryIds)
            ->whereIn('id', $vendor->subcategories->pluck('id')->all())
            ->get();

        if ($resolved->count() !== count(array_unique($subcategoryIds))) {
            abort(422, 'One or more subcategories are invalid for this vendor.');
        }

        return $resolved;
    }
}
