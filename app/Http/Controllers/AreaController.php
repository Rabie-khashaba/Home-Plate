<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\City;
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
        $request->validate([
            'city_id' => 'required|exists:cities,id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        Area::create($request->all());

        return redirect()->route('areas.index')->with('success', 'Area created successfully.');
    }

    public function edit(Area $area)
    {
        $cities = City::all();
        return view('areas.edit', compact('area', 'cities'));
    }

    public function update(Request $request, Area $area)
    {
        $request->validate([
            'city_id' => 'required|exists:cities,id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        $area->update($request->all());

        return redirect()->route('areas.index')->with('success', 'Area updated successfully.');
    }

    public function show(Area $area)
    {
        return view('areas.show', compact('area'));
    }

    public function destroy(Area $area)
    {
        $area->delete();
        return redirect()->route('areas.index')->with('success', 'Area deleted successfully.');
    }
}
