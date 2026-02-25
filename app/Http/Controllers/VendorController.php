<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\City;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
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
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30|unique:vendors,phone',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6',
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
            'working_time' => 'nullable|string|max:255',
        ]);

        foreach ($this->imageFields() as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store('vendors', 'public');
            }
        }

        $validated['status'] = 'pending';
        $validated['is_active'] = false;

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
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30|unique:vendors,phone,' . $vendor->id,
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
            'working_time' => 'nullable|string|max:255',
            'status' => 'required|in:pending,approved,rejected',
            'is_active' => 'nullable|boolean',
        ]);

        foreach ($this->imageFields() as $field) {
            if ($request->hasFile($field)) {
                if ($vendor->{$field}) {
                    Storage::disk('public')->delete($vendor->{$field});
                }

                $validated[$field] = $request->file($field)->store('vendors', 'public');
            }
        }

        $validated['is_active'] = $request->boolean('is_active');

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
}
