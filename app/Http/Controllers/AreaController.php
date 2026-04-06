<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\City;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::with('city')->latest()->paginate(10);
        return view('areas.index', compact('areas'));
    }

    public function create()
    {
        $cities = City::all();
        return view('areas.create', compact('cities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'city_id'           => 'required|exists:cities,id',
            'name_en'           => 'required|string|max:255',
            'name_ar'           => 'required|string|max:255',
            'delivery_fee'      => 'nullable|numeric|min:0',
            'min_order_amount'  => 'nullable|numeric|min:0',
            'estimated_minutes' => 'nullable|integer|min:1',
            'is_active'         => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $area = Area::create($data);
        ActivityLogger::log('created', 'Created area: ' . $area->name_en, $area);

        return redirect()->route('areas.index')->with('success', 'Area created successfully.');
    }

    public function edit(Area $area)
    {
        $cities = City::all();
        return view('areas.edit', compact('area', 'cities'));
    }

    public function update(Request $request, Area $area)
    {
        $data = $request->validate([
            'city_id'           => 'required|exists:cities,id',
            'name_en'           => 'required|string|max:255',
            'name_ar'           => 'required|string|max:255',
            'delivery_fee'      => 'nullable|numeric|min:0',
            'min_order_amount'  => 'nullable|numeric|min:0',
            'estimated_minutes' => 'nullable|integer|min:1',
            'is_active'         => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $area->update($data);
        ActivityLogger::log('updated', 'Updated area: ' . $area->name_en, $area);

        return redirect()->route('areas.index')->with('success', 'Area updated successfully.');
    }

    public function show(Area $area)
    {
        return view('areas.show', compact('area'));
    }

    public function destroy(Area $area)
    {
        ActivityLogger::log('deleted', 'Deleted area: ' . $area->name_en, $area);
        $area->delete();
        return redirect()->route('areas.index')->with('success', 'Area deleted successfully.');
    }

    /* ── Delivery Fees dedicated page ── */
    public function deliveryFees(Request $request)
    {
        $cityId = $request->get('city_id');
        $areas  = Area::with('city')
            ->when($cityId, fn($q) => $q->where('city_id', $cityId))
            ->orderBy('city_id')->orderBy('name_en')
            ->paginate(20)->withQueryString();

        $cities = City::orderBy('name_en')->get();

        $stats = [
            'total'    => Area::count(),
            'active'   => Area::where('is_active', true)->count(),
            'avg_fee'  => round(Area::where('delivery_fee', '>', 0)->avg('delivery_fee'), 2),
            'free'     => Area::where('delivery_fee', 0)->count(),
        ];

        return view('delivery_fees.index', compact('areas', 'cities', 'stats'));
    }

    public function updateFee(Request $request, Area $area)
    {
        $data = $request->validate([
            'delivery_fee'      => 'required|numeric|min:0',
            'min_order_amount'  => 'nullable|numeric|min:0',
            'estimated_minutes' => 'nullable|integer|min:1',
            'is_active'         => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $area->update($data);
        ActivityLogger::log('updated', 'Updated delivery fee for area: ' . $area->name_en . ' → ' . $data['delivery_fee'], $area);

        return back()->with('success', 'Delivery fee updated for ' . $area->name_en);
    }
}
