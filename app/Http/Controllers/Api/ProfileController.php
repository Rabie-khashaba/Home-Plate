<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Delivery;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

    public function updateAppUser(Request $request, int $id)
    {
        $appUser = AppUser::findOrFail($id);

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('app_users', 'email')->ignore($appUser->id)],
            'phone' => ['required', Rule::unique('app_users', 'phone')->ignore($appUser->id)],
            'password' => 'nullable|string|min:6',
            'gender' => 'nullable|in:male,female',
            'photo' => 'nullable|image|max:4096',
            'dob' => 'nullable|date',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'delivery_addresses' => 'nullable|string',
            'location' => 'nullable|string|max:500',
        ]);

        if ($request->hasFile('photo')) {
            if ($appUser->photo) {
                Storage::disk('public')->delete($appUser->photo);
            }
            $data['photo'] = $request->file('photo')->store('app_users', 'public');
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $appUser->update($data);

        return response()->json([
            'message' => 'App user updated successfully.',
            'data' => $this->withImageUrls($appUser->fresh(['city', 'area']), ['photo']),
        ]);
    }

    public function updateVendor(Request $request, int $id)
    {
        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'nullable|string|max:255',
            'phone' => ['nullable', Rule::unique('vendors', 'phone')->ignore($vendor->id)],
            'email' => 'nullable|email|max:255',
            'id_front' => 'nullable|image|max:4096',
            'id_back' => 'nullable|image|max:4096',
            'restaurant_info' => 'nullable|string',
            'main_photo' => 'nullable|image|max:4096',
            'restaurant_name' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'delivery_address' => 'nullable|string',
            'location' => 'nullable|string',
            'kitchen_photo_1' => 'nullable|image|max:4096',
            'kitchen_photo_2' => 'nullable|image|max:4096',
            'kitchen_photo_3' => 'nullable|image|max:4096',
            'working_time' => 'nullable|array',
        ]);

        foreach ($this->vendorImageFields() as $field) {
            if ($request->hasFile($field)) {
                if ($vendor->{$field}) {
                    Storage::disk('public')->delete($vendor->{$field});
                }
                $validated[$field] = $request->file($field)->store('vendors', 'public');
            }
        }

        $vendor->update($validated);

        return response()->json([
            'message' => 'Vendor updated successfully.',
            'data' => $this->withImageUrls($vendor->fresh(['city', 'area']), $this->vendorImageFields()),
        ]);
    }

    public function updateDelivery(Request $request, int $id)
    {
        $delivery = Delivery::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => ['required', Rule::unique('deliveries', 'phone')->ignore($delivery->id)],
            'password' => 'nullable|string|min:6',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'photo' => 'nullable|image|max:4096',
            'drivers_license' => 'nullable|image|max:4096',
            'national_id' => 'nullable|image|max:4096',
            'vehicle_photo' => 'nullable|image|max:4096',
            'vehicle_license' => 'array',
            'vehicle_license.front' => 'nullable|image|max:4096',
            'vehicle_license.back' => 'nullable|image|max:4096',
            'vehicle_type' => 'nullable|string|max:100',
        ]);

        foreach (['photo', 'drivers_license', 'national_id', 'vehicle_photo'] as $field) {
            if ($request->hasFile($field)) {
                if ($delivery->{$field}) {
                    Storage::disk('public')->delete($delivery->{$field});
                }
                $validated[$field] = $request->file($field)->store('deliveries', 'public');
            }
        }

        $vehicleLicense = $delivery->vehicle_license ?? [];
        foreach (['front', 'back'] as $key) {
            if ($request->hasFile("vehicle_license.$key")) {
                if (isset($vehicleLicense[$key])) {
                    Storage::disk('public')->delete($vehicleLicense[$key]);
                }
                $vehicleLicense[$key] = $request->file("vehicle_license.$key")->store('deliveries/licenses', 'public');
            }
        }
        $validated['vehicle_license'] = $vehicleLicense;

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $delivery->update($validated);

        return response()->json([
            'message' => 'Delivery updated successfully.',
            'data' => $this->withImageUrls($delivery->fresh(['city', 'area']), $this->deliveryImageFields()),
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

    private function vendorImageFields(): array
    {
        return [
            'id_front',
            'id_back',
            'main_photo',
            'kitchen_photo_1',
            'kitchen_photo_2',
            'kitchen_photo_3',
        ];
    }

    private function deliveryImageFields(): array
    {
        return [
            'photo',
            'drivers_license',
            'national_id',
            'vehicle_photo',
        ];
    }
}
