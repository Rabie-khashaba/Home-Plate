<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Delivery;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;

class ProfileController extends Controller
{
    public function appUser(int $id)
    {
        $user = AppUser::with(['city', 'area'])->findOrFail($id);

        return response()->json([
            'message' => 'App user profile fetched successfully.',
            'data' => $this->withImageUrls($user, ['photo']),
        ]);
    }

    public function vendor(int $id)
    {
        $vendor = Vendor::with(['city', 'area'])->findOrFail($id);

        return response()->json([
            'message' => 'Vendor profile fetched successfully.',
            'data' => $this->withImageUrls($vendor, [
                'id_front',
                'id_back',
                'main_photo',
                'kitchen_photo_1',
                'kitchen_photo_2',
                'kitchen_photo_3',
            ]),
        ]);
    }

    public function delivery(int $id)
    {
        $delivery = Delivery::with(['city', 'area'])->findOrFail($id);

        return response()->json([
            'message' => 'Delivery profile fetched successfully.',
            'data' => $this->withImageUrls($delivery, [
                'photo',
                'drivers_license',
                'national_id',
                'vehicle_photo',
                'vehicle_license_front',
                'vehicle_license_back',
            ]),
        ]);
    }

    private function withImageUrls(Model $model, array $imageFields): array
    {
        $data = $model->toArray();

        foreach ($imageFields as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->toPublicUrl($data[$field]);
            }
        }

        return $data;
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
}
