<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class GeneralRequestController extends Controller
{
    public function categories()
    {
        $categories = Category::query()->latest()->get();

        return response()->json([
            'message' => 'Categories fetched successfully.',
            'data' => $categories,
        ]);
    }

    public function subcategories(Request $request)
    {
        $query = Subcategory::query()->with('category')->latest();

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return response()->json([
            'message' => 'Subcategories fetched successfully.',
            'data' => $query->get(),
        ]);
    }

    public function countries()
    {
        $countries = Country::query()->latest()->get();

        return response()->json([
            'message' => 'Countries fetched successfully.',
            'data' => $countries,
        ]);
    }

    public function cities(Request $request)
    {
        $query = City::query()->with('country')->latest();

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        return response()->json([
            'message' => 'Cities fetched successfully.',
            'data' => $query->get(),
        ]);
    }

    public function areas(Request $request)
    {
        $query = Area::query()->with('city')->latest();

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        return response()->json([
            'message' => 'Areas fetched successfully.',
            'data' => $query->get(),
        ]);
    }
}
