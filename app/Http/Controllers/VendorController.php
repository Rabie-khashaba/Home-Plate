<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\City;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

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

    public function index()
    {
        $vendors = Vendor::with(['city', 'area'])->latest()->paginate(10);

        return view('vendors.index', compact('vendors'));
    }

    public function create()
    {
        $cities = City::all();
        $areas = Area::all();

        return view('vendors.create', compact('cities', 'areas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->vendorRules());
        
        foreach ($this->imageFields() as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('vendors', 'public');
            }
        }

        $validated['status'] = 'pending';
        $validated['is_active'] = false;
        $validated['working_time'] = $this->normalizeWorkingTime($validated['working_time'] ?? null);

        Vendor::create($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully and awaiting approval.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['city', 'area']);

        return view('vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor)
    {
        $cities = City::all();
        $areas = Area::all();

        return view('vendors.edit', compact('vendor', 'cities', 'areas'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate($this->vendorRules($vendor));

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

        $vendor->update($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        foreach ($this->imageFields() as $field) {
            if ($vendor->{$field}) {
                Storage::disk('public')->delete($vendor->{$field});
            }
        }

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

        return redirect()->back()->with('success', 'Vendor active status updated successfully.');
    }

    public function approve($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->status = 'approved';
        $vendor->is_active = true;
        $vendor->save();

        return redirect()->back()->with('success', 'Vendor approved successfully.');
    }

    public function reject($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->status = 'rejected';
        $vendor->is_active = false;
        $vendor->save();

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
            'main_photo' => 'nullable|image|max:4096',
            'restaurant_name' => 'required|string|max:255',
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
