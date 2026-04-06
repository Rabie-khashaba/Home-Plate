<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\Vendor;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = $this->vendorsBaseQuery()->latest()->get();

        return response()->json([
            'message' => $vendors->isEmpty() ? 'No vendors found.' : 'Vendors fetched successfully.',
            'data' => $this->transformVendors($vendors),
        ]);
    }

    public function byCategory(int $categoryId)
    {
        Category::query()->findOrFail($categoryId);

        $vendors = $this->vendorsBaseQuery()
            ->whereHas('categories', fn ($query) => $query->where('categories.id', $categoryId))
            ->latest()
            ->get();

        return response()->json([
            'message' => $vendors->isEmpty()
                ? 'No vendors found for this category.'
                : 'Vendors fetched successfully.',
            'data' => $this->transformVendors($vendors),
        ]);
    }

    public function withItems()
    {
        $vendors = $this->vendorsBaseQuery()
            ->whereHas('items')
            ->latest()
            ->get();

        return response()->json([
            'message' => $vendors->isEmpty()
                ? 'No vendors found with items.'
                : 'Vendors fetched successfully.',
            'data' => $this->transformVendors($vendors),
        ]);
    }

    public function byItem(int $itemId)
    {
        $item = Item::query()->findOrFail($itemId);

        $vendor = $this->vendorsBaseQuery()
            ->whereKey($item->vendor_id)
            ->firstOrFail();

        return response()->json([
            'message' => 'Vendor fetched successfully.',
            'data' => [
                'id' => $vendor->id,
                'name' => $vendor->restaurant_name ?: $vendor->full_name,
                'main_photo' => $this->toPublicUrl($vendor->main_photo),
            ],
        ]);
    }

    public function transformCategoryVendors($vendors)
    {
        return $this->transformVendors($vendors);
    }

    private function transformVendors($vendors)
    {
        return $vendors->map(function (Vendor $vendor) {
            $data = $vendor->makeHidden(['password', 'otp_code', 'otp_expires_at'])->toArray();

            foreach ($this->vendorImageFields() as $field) {
                if (array_key_exists($field, $data)) {
                    $data[$field] = $this->toPublicUrl($data[$field]);
                }
            }

            $data['items'] = $vendor->items->map(function ($item) {
                $itemData = $item->toArray();
                if (! empty($itemData['photos']) && is_array($itemData['photos'])) {
                    $itemData['photos'] = array_map(fn ($photo) => $this->toPublicUrl($photo), $itemData['photos']);
                }

                return $itemData;
            })->values();

            $data['subcategories'] = $vendor->items
                ->pluck('subcategory')
                ->filter()
                ->unique('id')
                ->values()
                ->map(fn ($subcategory) => $subcategory->toArray());

            return $data;
        });
    }

    private function vendorsBaseQuery()
    {
        return Vendor::with([
            'categories',
            'city',
            'area',
            'items.category',
            'items.subcategory',
        ]);
    }

    private function toPublicUrl($value)
    {
        if (empty($value)) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(function ($item) {
                return $this->toPublicUrl($item);
            }, $value);
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $path = ltrim($value, '/');
        if (str_starts_with($path, 'storage/')) {
            return rtrim(config('app.url'), '/') . '/' . $path;
        }

        return rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;
    }

    private function vendorImageFields(): array
    {
        return [
            'id_front',
            'id_back',
            'tax_card_image',
            'commercial_register_image',
            'main_photo',
            'kitchen_photo_1',
            'kitchen_photo_2',
            'kitchen_photo_3',
        ];
    }
}
