<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\City;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VendorController extends Controller
{
    // 🟢 عرض جميع التجار
    public function index()
    {
        $vendors = Vendor::with(['city', 'area'])->latest()->paginate(10);
        return view('vendors.index', compact('vendors'));
    }

    // 🟢 عرض صفحة الإنشاء
    public function create()
    {
        $cities = City::all();
        $areas = Area::all();
        return view('vendors.create', compact('cities', 'areas'));
    }

    // 🟢 حفظ تاجر جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|unique:vendors',
            'password' => 'required|min:6',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'logo' => 'nullable|image|max:2048',
            'address' => 'nullable|string',
            'location' => 'nullable|url',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('vendors', 'public');
        }

        $validated['status'] = 'pending'; // الحالة الافتراضية
        $validated['is_active'] = false;

        Vendor::create($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor created successfully and awaiting approval.');
    }

    // 🟢 عرض بيانات تاجر
    public function show(Vendor $vendor)
    {
        return view('vendors.show', compact('vendor'));
    }

    // 🟢 تعديل تاجر
    public function edit(Vendor $vendor)
    {
        $cities = City::all();
        $areas = Area::all();
        return view('vendors.edit', compact('vendor', 'cities', 'areas'));
    }

    // 🟢 تحديث بيانات التاجر
    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|unique:vendors,phone,' . $vendor->id,
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'logo' => 'nullable|image|max:2048',
            'address' => 'nullable|string',
            'location' => 'nullable|url',
            'status' => 'required|in:pending,approved,rejected',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('logo')) {
            if ($vendor->logo) {
                Storage::disk('public')->delete($vendor->logo);
            }
            $validated['logo'] = $request->file('logo')->store('vendors', 'public');
        }

        $vendor->update($validated);

        return redirect()->route('vendors.index')->with('success', 'Vendor updated successfully.');
    }

    // 🟢 حذف تاجر
    public function destroy(Vendor $vendor)
    {
        if ($vendor->logo) {
            Storage::disk('public')->delete($vendor->logo);
        }

        $vendor->delete();

        return redirect()->route('vendors.index')->with('success', 'Vendor deleted successfully.');
    }

    // 🟡 تبديل حالة التفعيل
    public function toggleStatus($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->is_active = !$vendor->is_active;

        // لو مفعّل، اجعل الحالة approved
        if ($vendor->is_active) {
            $vendor->status = 'approved';
        }
        $vendor->save();

        return redirect()->back()->with('success', 'Vendor status toggled successfully.');
    }

    // ✅ قبول التاجر
    public function approve($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->status = 'approved';
        $vendor->is_active = true;
        $vendor->save();

        return redirect()->back()->with('success', 'تم قبول التاجر بنجاح ✅');
    }

    // 🚫 رفض التاجر
    public function reject($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->status = 'rejected';
        $vendor->is_active = false;
        $vendor->save();

        return redirect()->back()->with('error', 'تم رفض التاجر 🚫');
    }
}
