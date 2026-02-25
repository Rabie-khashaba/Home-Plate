<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index()
    {
        $cities = City::with('country')->paginate(10);
        return view('cities.index', compact('cities'));
    }

    public function create()
    {
        $countries = Country::all();
        return view('cities.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        City::create($request->all());

        return redirect()->route('cities.index')->with('success', 'تمت الإضافة بنجاح');
    }

    public function show(City $city)
    {
        return view('cities.show', compact('city'));
    }

    public function edit(City $city)
    {
        $countries = Country::all();
        return view('cities.edit', compact('city', 'countries'));
    }

    public function update(Request $request, City $city)
    {
        $request->validate([
            'country_id' => 'required|exists:countries,id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        $city->update($request->all());

        return redirect()->route('cities.index')->with('success', 'تم التحديث بنجاح');
    }

    public function destroy(City $city)
    {
        $city->delete();
        return redirect()->route('cities.index')->with('success', 'تم الحذف بنجاح');
    }
}
