<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogger;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with(['vendor', 'category']);
        $dateFilter = $request->get('date_filter');
        $from = $request->get('from');
        $to   = $request->get('to');

        if ($search = $request->get('search')) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                                      ->orWhereHas('vendor', fn($v) => $v->where('restaurant_name', 'like', "%{$search}%")));
        }

        if ($request->filled('approval_status'))     $query->where('approval_status', $request->get('approval_status'));
        if ($request->filled('availability_status')) $query->where('availability_status', $request->get('availability_status'));

        match($dateFilter) {
            'today'      => $query->whereDate('created_at', today()),
            'yesterday'  => $query->whereDate('created_at', today()->subDay()),
            'last_week'  => $query->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]),
            'last_month' => $query->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]),
            'custom'     => $query->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
                                  ->when($to,   fn($q) => $q->whereDate('created_at', '<=', $to)),
            default      => null,
        };

        $items = $query->latest()->paginate(15)->withQueryString();

        $sq = fn() => Item::when($dateFilter === 'today',      fn($q) => $q->whereDate('created_at', today()))
                          ->when($dateFilter === 'yesterday',  fn($q) => $q->whereDate('created_at', today()->subDay()))
                          ->when($dateFilter === 'last_week',  fn($q) => $q->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]))
                          ->when($dateFilter === 'last_month', fn($q) => $q->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]))
                          ->when($dateFilter === 'custom' && $from, fn($q) => $q->whereDate('created_at', '>=', $from))
                          ->when($dateFilter === 'custom' && $to,   fn($q) => $q->whereDate('created_at', '<=', $to));

        $stats = [
            'total'     => $sq()->count(),
            'approved'  => $sq()->where('approval_status', 'approved')->count(),
            'pending'   => $sq()->where('approval_status', 'pending')->count(),
            'published' => $sq()->where('availability_status', 'published')->count(),
        ];

        return view('items.index', compact('items', 'stats'));
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

        $item = Item::create($validated);
        ActivityLogger::log('created', 'Added item: ' . $item->name, $item);

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
        ActivityLogger::log('updated', 'Updated item: ' . $item->name, $item);

        return redirect()->route('items.index')->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item)
    {
        $this->deletePhotos($item->photos ?? []);
        ActivityLogger::log('deleted', 'Deleted item: ' . $item->name, $item);
        $item->delete();

        return redirect()->route('items.index')->with('success', 'Item deleted successfully.');
    }

    public function approve($id)
    {
        $item = Item::findOrFail($id);
        $item->approval_status = 'approved';
        $item->availability_status = 'paused';
        $item->save();
        ActivityLogger::log('approved', 'Approved item: ' . $item->name, $item);

        return redirect()->back()->with('success', 'Item approved successfully.');
    }

    public function reject($id)
    {
        $item = Item::findOrFail($id);
        $item->approval_status = 'rejected';
        $item->availability_status = 'paused';
        $item->save();
        ActivityLogger::log('rejected', 'Rejected item: ' . $item->name, $item);

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
