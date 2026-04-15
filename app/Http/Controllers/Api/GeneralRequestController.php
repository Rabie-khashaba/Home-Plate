<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Subcategory;
use App\Models\Vendor;
use Illuminate\Http\Request;

class GeneralRequestController extends Controller
{
    public function categories()
    {
        $categories = Category::query()
            ->with('items')
            ->latest()
            ->get()
            ->map(function (Category $category) {
                $data = $category->toArray();
                $data['photo'] = $category->photo
                    ? asset('storage/app/public/' . ltrim($category->photo, '/'))
                    : null;
                $data['items'] = $category->items->map(function ($item) {
                    $itemData = $item->toArray();

                    if (! empty($itemData['photos']) && is_array($itemData['photos'])) {
                        $itemData['photos'] = array_map(function ($photo) {
                            return asset('storage/app/public/' . ltrim($photo, '/'));
                        }, $itemData['photos']);
                    }

                    unset($itemData['vendor'], $itemData['category'], $itemData['subcategory']);

                    return $itemData;
                })->values();

                return $data;
            });

        return response()->json([
            'message' => 'Categories fetched successfully.',
            'data' => $categories,
        ]);
    }

    public function subcategories(Request $request)
    {
        $query = Subcategory::query()->with('category')->latest();

        return response()->json([
            'message' => 'Subcategories fetched successfully.',
            'data' => $query->get(),
        ]);
    }

    public function subcategoriesByVendor(int $vendorId)
    {
        $vendor = Vendor::with(['subcategories:id,category_id,name_en,name_ar'])->findOrFail($vendorId);

        return response()->json([
            'message' => 'Subcategories fetched successfully.',
            'data' => $vendor->subcategories()
               // ->with('category')
                ->latest()
                ->get(),
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
