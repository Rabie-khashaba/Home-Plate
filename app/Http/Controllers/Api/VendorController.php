<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::with(['city', 'area'])->latest()->get();

        $vendors = $vendors->map(function (Vendor $vendor) {
            $data = $vendor->makeHidden(['password', 'otp_code', 'otp_expires_at'])->toArray();

            foreach ($this->vendorImageFields() as $field) {
                if (array_key_exists($field, $data)) {
                    $data[$field] = $this->toPublicUrl($data[$field]);
                }
            }

            return $data;
        });

        return response()->json([
            'message' => $vendors->isEmpty() ? 'No vendors found.' : 'Vendors fetched successfully.',
            'data' => $vendors,
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
