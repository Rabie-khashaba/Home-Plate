<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Category;
use App\Models\City;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Services\ActivityLogger;

class VendorController extends Controller
{
    private const WORKING_DAYS = [
        'saturday',
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ];

    public function index(Request $request)
    {
        $query = Vendor::with(['categories', 'city', 'area']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name',        'like', "%{$search}%")
                  ->orWhere('restaurant_name','like', "%{$search}%")
                  ->orWhere('phone',          'like', "%{$search}%")
                  ->orWhere('email',          'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->get('status')) $query->where('status', $status);
        if ($request->get('active') === '1')   $query->where('is_active', true);
        if ($request->get('active') === '0')   $query->where('is_active', false);

        // Date filter
        $dateFilter = $request->get('date_filter');
        match($dateFilter) {
            'today'      => $query->whereDate('created_at', today()),
            'yesterday'  => $query->whereDate('created_at', today()->subDay()),
            'last_week'  => $query->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]),
            'last_month' => $query->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]),
            'custom'     => $query->when($request->get('from'), fn($q) => $q->whereDate('created_at', '>=', $request->get('from')))
                                  ->when($request->get('to'),   fn($q) => $q->whereDate('created_at', '<=', $request->get('to'))),
            default      => null,
        };

        $vendors = $query->latest()->paginate(15)->withQueryString();

        // Stats — apply same date filter
        $from = $request->get('from');
        $to   = $request->get('to');

        $statsQuery = fn() => Vendor::when($dateFilter === 'today',      fn($q) => $q->whereDate('created_at', today()))
                                    ->when($dateFilter === 'yesterday',  fn($q) => $q->whereDate('created_at', today()->subDay()))
                                    ->when($dateFilter === 'last_week',  fn($q) => $q->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]))
                                    ->when($dateFilter === 'last_month', fn($q) => $q->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]))
                                    ->when($dateFilter === 'custom' && $from, fn($q) => $q->whereDate('created_at', '>=', $from))
                                    ->when($dateFilter === 'custom' && $to,   fn($q) => $q->whereDate('created_at', '<=', $to));

        $stats = [
            'total'    => $statsQuery()->count(),
            'approved' => $statsQuery()->where('status', 'approved')->count(),
            'pending'  => $statsQuery()->where('status', 'pending')->count(),
            'rejected' => $statsQuery()->where('status', 'rejected')->count(),
        ];

        return view('vendors.index', compact('vendors', 'stats'));
    }

    public function create()
    {
        $categories = Category::orderBy('name_en')->get();
        $cities = City::all();
        $areas = Area::all();

        return view('vendors.create', compact('categories', 'cities', 'areas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->vendorRules());
        $categoryIds = $validated['category_ids'];
        unset($validated['category_ids']);

        foreach ($this->imageFields() as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('vendors', 'public');
            }
        }

        $validated['status'] = 'pending';
        $validated['is_active'] = false;
        $validated['working_time'] = $this->normalizeWorkingTime($validated['working_time'] ?? null);

        $validated['category_id'] = $categoryIds[0] ?? null;

        $vendor = Vendor::create($validated);
        $vendor->categories()->sync($categoryIds);
        ActivityLogger::log('created', 'Created vendor: ' . ($vendor->restaurant_name ?? $vendor->full_name), $vendor);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully and awaiting approval.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['categories', 'city', 'area']);

        return view('vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor)
    {
        $categories = Category::orderBy('name_en')->get();
        $cities = City::all();
        $areas = Area::all();

        return view('vendors.edit', compact('vendor', 'categories', 'cities', 'areas'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate($this->vendorRules($vendor));
        $categoryIds = $validated['category_ids'];
        unset($validated['category_ids']);

        foreach ($this->imageFields() as $field) {
            if ($request->hasFile($field)) {
                if ($vendor->{$field}) {
                    Storage::disk('public')->delete($vendor->{$field});
                }

                $validated[$field] = $request->file($field)->store('vendors', 'public');
            }
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['working_time'] = $this->normalizeWorkingTime($validated['working_time'] ?? null);

        if ($validated['status'] === 'rejected') {
            $validated['is_active'] = false;
        }

        $validated['category_id'] = $categoryIds[0] ?? null;

        $vendor->update($validated);
        $vendor->categories()->sync($categoryIds);
        ActivityLogger::log('updated', 'Updated vendor: ' . ($vendor->restaurant_name ?? $vendor->full_name), $vendor);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        foreach ($this->imageFields() as $field) {
            if ($vendor->{$field}) {
                Storage::disk('public')->delete($vendor->{$field});
            }
        }

        ActivityLogger::log('deleted', 'Deleted vendor: ' . ($vendor->restaurant_name ?? $vendor->full_name), $vendor);
        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $vendor = Vendor::findOrFail($id);

        if ($vendor->status !== 'approved') {
            return redirect()->back()->with('error', 'Approve vendor first before changing active status.');
        }

        $vendor->is_active = ! $vendor->is_active;
        $vendor->save();
        ActivityLogger::log('updated', ($vendor->is_active ? 'Activated' : 'Deactivated') . ' vendor: ' . ($vendor->restaurant_name ?? $vendor->full_name), $vendor);

        return redirect()->back()->with('success', 'Vendor active status updated successfully.');
    }

    public function approve($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->status = 'approved';
        $vendor->is_active = true;
        $vendor->save();
        ActivityLogger::log('approved', 'Approved vendor: ' . ($vendor->restaurant_name ?? $vendor->full_name), $vendor);

        return redirect()->back()->with('success', 'Vendor approved successfully.');
    }

    public function reject($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->status = 'rejected';
        $vendor->is_active = false;
        $vendor->save();
        ActivityLogger::log('rejected', 'Rejected vendor: ' . ($vendor->restaurant_name ?? $vendor->full_name), $vendor);

        return redirect()->back()->with('error', 'Vendor rejected.');
    }

    private function vendorRules(?Vendor $vendor = null): array
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30|unique:vendors,phone' . ($vendor ? ',' . $vendor->id : ''),
            'email' => 'nullable|email|max:255',
            'id_front' => 'nullable|image|max:4096',
            'id_back' => 'nullable|image|max:4096',
            'restaurant_info' => 'nullable|string',
            'tax_card_number' => 'nullable|string|max:255',
            'tax_card_image' => 'nullable|image|max:4096',
            'commercial_register_number' => 'nullable|string|max:255',
            'commercial_register_image' => 'nullable|image|max:4096',
            'main_photo' => 'nullable|image|max:4096',
            'restaurant_name' => 'required|string|max:255',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'delivery_address' => 'required|string',
            'location' => 'nullable|string',
            'kitchen_photo_1' => 'nullable|image|max:4096',
            'kitchen_photo_2' => 'nullable|image|max:4096',
            'kitchen_photo_3' => 'nullable|image|max:4096',
            'working_time' => 'nullable|array',
        ];

        if ($vendor) {
            $rules['status'] = 'required|in:pending,approved,rejected';
            $rules['is_active'] = 'nullable|boolean';
        } else {
            $rules['password'] = 'required|string|min:6';
        }

        foreach (self::WORKING_DAYS as $day) {
            $rules["working_time.$day"] = 'nullable|array';
            $rules["working_time.$day.enabled"] = 'nullable|boolean';
            $rules["working_time.$day.from"] = "nullable|date_format:H:i|required_with:working_time.$day.to,working_time.$day.enabled";
            $rules["working_time.$day.to"] = "nullable|date_format:H:i|after:working_time.$day.from|required_with:working_time.$day.from,working_time.$day.enabled";
        }

        return $rules;
    }

    private function imageFields(): array
    {
        return [
            'id_front',
            'id_back',
            'tax_card_image',
            'commercial_register_image',
            'main_photo',
            'kitchen_photo_1',
            'kitchen_photo_2',
            'kitchen_photo_3',
        ];
    }

    private function normalizeWorkingTime(?array $workingTime): ?array
    {
        if (! is_array($workingTime)) {
            return null;
        }

        if (isset($workingTime['day'])) {
            $day = $workingTime['day'] ?? null;
            $from = $workingTime['from'] ?? null;
            $to = $workingTime['to'] ?? null;

            if (! $day || ! $from || ! $to) {
                return null;
            }

            return [
                'day' => $day,
                'from' => Carbon::createFromFormat('g:i A', $from)->format('g:i A'),
                'to' => Carbon::createFromFormat('g:i A', $to)->format('g:i A'),
            ];
        }

        $normalized = [];

        foreach (self::WORKING_DAYS as $day) {
            $dayData = $workingTime[$day] ?? null;
            if (! is_array($dayData)) {
                continue;
            }

            $isEnabled = ! empty($dayData['enabled']);
            $from = $dayData['from'] ?? null;
            $to = $dayData['to'] ?? null;

            if (! $isEnabled || ! $from || ! $to) {
                continue;
            }

            $normalized[] = [
                'day' => $day,
                'from' => Carbon::createFromFormat('H:i', $from)->format('g:i A'),
                'to' => Carbon::createFromFormat('H:i', $to)->format('g:i A'),
            ];
        }

        return $normalized ?: null;
    }
}
