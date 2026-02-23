<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\City;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DeliveryController extends Controller
{
    public function index()
    {
        $deliveries = Delivery::with(['city','area'])->latest()->paginate(12);
        return view('deliveries.index', compact('deliveries'));
    }

    public function create()
    {
        $cities = City::all();
        $areas = Area::all();
        return view('deliveries.create', compact('cities', 'areas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|unique:deliveries,phone',
            'password' => 'required|min:6',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'photo' => 'nullable|image|max:4096',
            'drivers_license' => 'nullable|image|max:4096',
            'national_id' => 'nullable|image|max:4096',
            'vehicle_photo' => 'nullable|image|max:4096',
            'vehicle_license.front' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
            'vehicle_license.back' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
            'vehicle_type' => 'required|string|max:100',
        ]);

        // files
        $paths = [];
        foreach (['photo','drivers_license','national_id','vehicle_photo'] as $file) {
            if ($request->hasFile($file)) {
                $paths[$file] = $request->file($file)->store('deliveries', 'public');
            }
        }


        $vehicleLicense = [];
        foreach (['front','back'] as $key) {
            if ($request->hasFile("vehicle_license.$key")) {
                $vehicleLicense[$key] = $request->file("vehicle_license.$key")->store('deliveries/licenses', 'public');
            }
        }

        $data = [
            'first_name' => $validated['first_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'city_id' => $validated['city_id'],
            'area_id' => $validated['area_id'],
            'vehicle_type' => $validated['vehicle_type'],
            'status' => 'pending',
            'is_active' => false,
            'vehicle_license' => $vehicleLicense,
        ];

        // merge file paths if exist
        $data = array_merge($data, $paths);



        $delivery = Delivery::create($data);

        return redirect()->route('deliveries.index')->with('success', 'Delivery created and awaiting approval.');
    }

    public function show(Delivery $delivery)
    {
        return view('deliveries.show', compact('delivery'));
    }

    public function edit(Delivery $delivery)
    {
        $cities = City::all();
        $areas = Area::all();
        return view('deliveries.edit', compact('delivery', 'cities', 'areas'));
    }

    public function update(Request $request, Delivery $delivery)
    {
        //return $request;
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['required', Rule::unique('deliveries','phone')->ignore($delivery->id)],
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'photo' => 'nullable|image|max:4096',
            'drivers_license' => 'nullable|image|max:4096',
            'national_id' => 'nullable|image|max:4096',
            'vehicle_photo' => 'nullable|image|max:4096',
            'vehicle_license' => 'array',
            'vehicle_license.front' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'vehicle_license.back' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'vehicle_type' => 'required|string|max:100',
            'status' => 'nullable|in:pending,approved,rejected',
            'is_active' => 'nullable|boolean',
        ]);

        // handle file replacements
        $files = ['photo','drivers_license','national_id','vehicle_photo'];
        foreach ($files as $file) {
            if ($request->hasFile($file)) {
                if ($delivery->$file) {
                    Storage::disk('public')->delete($delivery->$file);
                }
                $validated[$file] = $request->file($file)->store('deliveries', 'public');
            }
        }

        // ✅ رفع vehicle_license كمصفوفة front/back
       $vehicleLicense = $delivery->vehicle_license ?? [];

        foreach (['front','back'] as $key) {
            if ($request->hasFile("vehicle_license.$key")) {
                if(isset($vehicleLicense[$key])) {
                    Storage::disk('public')->delete($vehicleLicense[$key]);
                }
                $vehicleLicense[$key] = $request->file("vehicle_license.$key")->store('deliveries/licenses', 'public');
            }
        }

        $validated['vehicle_license'] = $vehicleLicense;




        // ensure is_active boolean
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = false;
        }

        $delivery->update($validated);

        return redirect()->route('deliveries.index')->with('success', 'Delivery updated.');
    }

    public function destroy(Delivery $delivery)
    {
        // delete files
        foreach (['photo','drivers_license','national_id','vehicle_photo','vehicle_license_front','vehicle_license_back'] as $file) {
            if ($delivery->$file) Storage::disk('public')->delete($delivery->$file);
        }
        $delivery->delete();
        return redirect()->route('deliveries.index')->with('success', 'Delivery deleted.');
    }

    // toggle active
    public function toggleStatus($id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->is_active = !$delivery->is_active;


        // لو مفعّل، اجعل الحالة approved
        if ($delivery->is_active) {
            $delivery->status = 'approved';
        }
        $delivery->save();

        return redirect()->back()->with('success', 'Delivery activation toggled.');
    }

    // approve
    public function approve($id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->status = 'approved';
        $delivery->is_active = true;
        $delivery->save();

        return redirect()->back()->with('success', 'Delivery approved.');
    }

    // reject
    public function reject($id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->status = 'rejected';
        $delivery->is_active = false;
        $delivery->save();

        return redirect()->back()->with('error', 'Delivery rejected.');
    }
}
