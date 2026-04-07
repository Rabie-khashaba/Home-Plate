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
    private const WORKING_DAYS = [
        'saturday',
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ];

    public function appUser(int $id)
    {
        $user = AppUser::with(['city', 'area', 'addresses'])->findOrFail($id);

        return response()->json([
            'message' => 'App user profile fetched successfully.',
            'data' => $this->withImageUrls($user, ['photo']),
        ]);
    }

    public function vendor(int $id)
    {
        $vendor = Vendor::with(['categories', 'subcategories', 'city', 'area', 'addresses'])->findOrFail($id);

        return response()->json([
            'message' => 'Vendor profile fetched successfully.',
            'data' => $this->withImageUrls($vendor, [
                'id_front',
                'id_back',
                'tax_card_image',
                'commercial_register_image',
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

        $this->normalizeWorkingTime($request);

        $validated = $request->validate([
            'full_name' => 'nullable|string|max:255',
            'phone' => ['nullable', Rule::unique('vendors', 'phone')->ignore($vendor->id)],
            'email' => 'nullable|email|max:255',
            'id_front' => 'nullable|image|max:4096',
            'id_back' => 'nullable|image|max:4096',
            'restaurant_info' => 'nullable|string',
            'tax_card_number' => 'nullable|string|max:255',
            'tax_card_image' => 'nullable|image|max:4096',
            'commercial_register_number' => 'nullable|string|max:255',
            'commercial_register_image' => 'nullable|image|max:4096',
            'main_photo' => 'nullable|image|max:4096',
            'restaurant_name' => 'nullable|string|max:255',
            'category_ids' => 'nullable|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'subcategory_ids' => 'nullable|array|min:1',
            'subcategory_ids.*' => 'exists:subcategories,id',
            'city_id' => 'nullable|exists:cities,id',
            'area_id' => 'nullable|exists:areas,id',
            'delivery_address' => 'nullable|string',
            'location' => 'nullable|string',
            'kitchen_photo_1' => 'nullable|image|max:4096',
            'kitchen_photo_2' => 'nullable|image|max:4096',
            'kitchen_photo_3' => 'nullable|image|max:4096',
            'working_time' => 'nullable|array',
            'working_time.*.day' => ['required_with:working_time.*', Rule::in(self::WORKING_DAYS)],
            'working_time.*.from' => 'required_with:working_time.*|date_format:g:i A',
            'working_time.*.to' => 'required_with:working_time.*|date_format:g:i A',
        ]);

        foreach ($this->vendorImageFields() as $field) {
            if ($request->hasFile($field)) {
                if ($vendor->{$field}) {
                    Storage::disk('public')->delete($vendor->{$field});
                }
                $validated[$field] = $request->file($field)->store('vendors', 'public');
            }
        }

        $categoryIds = $validated['category_ids'] ?? null;
        $subcategoryIds = $validated['subcategory_ids'] ?? null;
        unset($validated['category_ids'], $validated['subcategory_ids']);

        if (is_array($categoryIds)) {
            $validated['category_id'] = $categoryIds[0] ?? null;
        }

        $vendor->update($validated);

        if (is_array($categoryIds)) {
            $vendor->categories()->sync($categoryIds);
        }

        if (is_array($subcategoryIds)) {
            $vendor->subcategories()->sync($subcategoryIds);
        }

        return response()->json([
            'message' => 'Vendor updated successfully.',
            'data' => $this->withImageUrls($vendor->fresh(['categories', 'subcategories.category', 'city', 'area']), $this->vendorImageFields()),
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

    private function normalizeWorkingTime(Request $request): void
    {
        $workingTime = $request->input('working_time');

        if (! is_array($workingTime)) {
            return;
        }

        foreach ($workingTime as $index => $slot) {
            if (! is_array($slot)) {
                continue;
            }

            foreach (['from', 'to'] as $key) {
                if (! empty($slot[$key]) && is_string($slot[$key])) {
                    $workingTime[$index][$key] = preg_replace_callback(
                        '/\b(am|pm)\b/i',
                        fn ($matches) => strtoupper($matches[1]),
                        trim($slot[$key])
                    );
                }
            }
        }

        $request->merge(['working_time' => $workingTime]);
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
            'tax_card_image',
            'commercial_register_image',
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