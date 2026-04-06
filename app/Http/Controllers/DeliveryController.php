<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\City;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogger;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = Delivery::with(['city', 'area']);
        $dateFilter = $request->get('date_filter');
        $from = $request->get('from');
        $to   = $request->get('to');

        if ($search = $request->get('search')) {
            $query->where(fn($q) => $q->where('first_name', 'like', "%{$search}%")
                                      ->orWhere('phone', 'like', "%{$search}%")
                                      ->orWhere('email', 'like', "%{$search}%"));
        }

        if ($status = $request->get('status')) $query->where('status', $status);
        if ($request->get('active') === '1')   $query->where('is_active', true);
        if ($request->get('active') === '0')   $query->where('is_active', false);

        match($dateFilter) {
            'today'      => $query->whereDate('created_at', today()),
            'yesterday'  => $query->whereDate('created_at', today()->subDay()),
            'last_week'  => $query->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]),
            'last_month' => $query->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]),
            'custom'     => $query->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
                                  ->when($to,   fn($q) => $q->whereDate('created_at', '<=', $to)),
            default      => null,
        };

        $deliveries = $query->latest()->paginate(15)->withQueryString();

        $sq = fn() => Delivery::when($dateFilter === 'today',      fn($q) => $q->whereDate('created_at', today()))
                               ->when($dateFilter === 'yesterday',  fn($q) => $q->whereDate('created_at', today()->subDay()))
                               ->when($dateFilter === 'last_week',  fn($q) => $q->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]))
                               ->when($dateFilter === 'last_month', fn($q) => $q->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]))
                               ->when($dateFilter === 'custom' && $from, fn($q) => $q->whereDate('created_at', '>=', $from))
                               ->when($dateFilter === 'custom' && $to,   fn($q) => $q->whereDate('created_at', '<=', $to));

        $stats = [
            'total'    => $sq()->count(),
            'approved' => $sq()->where('status', 'approved')->count(),
            'pending'  => $sq()->where('status', 'pending')->count(),
            'rejected' => $sq()->where('status', 'rejected')->count(),
        ];

        return view('deliveries.index', compact('deliveries', 'stats'));
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
        ActivityLogger::log('created', 'Created rider: ' . ($delivery->full_name ?? $delivery->name ?? 'Rider #'.$delivery->id), $delivery);

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
        ActivityLogger::log('updated', 'Updated rider: ' . ($delivery->full_name ?? $delivery->name ?? 'Rider #'.$delivery->id), $delivery);

        return redirect()->route('deliveries.index')->with('success', 'Delivery updated.');
    }

    public function destroy(Delivery $delivery)
    {
        // delete files
        foreach (['photo','drivers_license','national_id','vehicle_photo','vehicle_license_front','vehicle_license_back'] as $file) {
            if ($delivery->$file) Storage::disk('public')->delete($delivery->$file);
        }
        ActivityLogger::log('deleted', 'Deleted rider: ' . ($delivery->full_name ?? $delivery->name ?? 'Rider #'.$delivery->id), $delivery);
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
        ActivityLogger::log('approved', 'Approved rider: ' . ($delivery->full_name ?? $delivery->name ?? 'Rider #'.$delivery->id), $delivery);

        return redirect()->back()->with('success', 'Delivery approved.');
    }

    // reject
    public function reject($id)
    {
        $delivery = Delivery::findOrFail($id);
        $delivery->status = 'rejected';
        $delivery->is_active = false;
        $delivery->save();
        ActivityLogger::log('rejected', 'Rejected rider: ' . ($delivery->full_name ?? $delivery->name ?? 'Rider #'.$delivery->id), $delivery);

        return redirect()->back()->with('error', 'Delivery rejected.');
    }
}
