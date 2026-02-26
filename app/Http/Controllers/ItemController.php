<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $itemsQuery = Item::with(['vendor', 'category'])->latest();

        if ($request->filled('approval_status')) {
            $itemsQuery->where('approval_status', $request->string('approval_status')->toString());
        }

        $items = $itemsQuery->paginate(10);

        return view('items.index', compact('items'));
    }

    public function create()
    {
        $vendors = Vendor::orderBy('restaurant_name')->get();
        $categories = Category::orderBy('name_en')->get();

        return view('items.create', compact('vendors', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
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

        $validated['approval_status'] = 'approved';
        $validated['availability_status'] = 'paused';
        $validated['max_orders_per_day'] = $validated['max_orders_per_day'] ?? $validated['stock'];
        $validated['photos'] = $this->storePhotos($request->file('photos'));

        Item::create($validated);

        return redirect()->route('items.index')->with('success', 'Item created and awaiting approval.');
    }

    public function show(Item $item)
    {
        $item->load(['vendor', 'category']);

        return view('items.show', compact('item'));
    }

    public function edit(Item $item)
    {
        $vendors = Vendor::orderBy('restaurant_name')->get();
        $categories = Category::orderBy('name_en')->get();

        return view('items.edit', compact('item', 'vendors', 'categories'));
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'prep_time_value' => 'required|integer|min:1',
            'prep_time_unit' => ['required', Rule::in(['minutes', 'hours'])],
            'stock' => 'required|integer|min:0',
            'max_orders_per_day' => 'nullable|integer|min:0',
            'approval_status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
            'availability_status' => ['required', Rule::in(['paused', 'published'])],
            'photos' => 'nullable|array|size:4',
            'photos.*' => 'required_with:photos|image|max:4096',
        ]);

        if ($request->hasFile('photos')) {
            $this->deletePhotos($item->photos ?? []);
            $validated['photos'] = $this->storePhotos($request->file('photos'));
        }

        if ($validated['approval_status'] !== 'approved') {
            $validated['availability_status'] = 'paused';
        }

        if (array_key_exists('stock', $validated) && $validated['stock'] <= 0) {
            $validated['availability_status'] = 'paused';
        }

        if (! isset($validated['max_orders_per_day'])) {
            $validated['max_orders_per_day'] = $item->max_orders_per_day;
        }

        $item->update($validated);

        return redirect()->route('items.index')->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        $this->deletePhotos($item->photos ?? []);
        $item->delete();

        return redirect()->route('items.index')->with('success', 'Item deleted successfully.');
    }

    public function approve($id)
    {
        $item = Item::findOrFail($id);
        $item->approval_status = 'approved';
        $item->availability_status = 'paused';
        $item->save();

        return redirect()->back()->with('success', 'Item approved successfully.');
    }

    public function reject($id)
    {
        $item = Item::findOrFail($id);
        $item->approval_status = 'rejected';
        $item->availability_status = 'paused';
        $item->save();

        return redirect()->back()->with('error', 'Item rejected.');
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
}