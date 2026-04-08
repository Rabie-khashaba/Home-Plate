<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VendorAuthRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $workingTime = $this->input('working_time', []);

        if (is_array($workingTime)) {
            foreach ($workingTime as $index => $slot) {
                if (! is_array($slot)) {
                    continue;
                }

                foreach (['from', 'to'] as $key) {
                    if (! empty($slot[$key]) && is_string($slot[$key])) {
                        // Normalize am/pm casing (e.g. "Pm" => "PM") before validation.
                        $workingTime[$index][$key] = preg_replace_callback(
                            '/\b(am|pm)\b/i',
                            fn ($matches) => strtoupper($matches[1]),
                            trim($slot[$key])
                        );
                    }
                }
            }

            $this->merge(['working_time' => $workingTime]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }

    public function rules(): array
    {
        return match ($this->route()?->getActionMethod()) {
            'register' => $this->registerRules(),
            'verifyRegisterOtp' => $this->verifyOtpRules(),
            'login' => $this->loginRules(),
            'forgotPassword' => $this->forgotPasswordRules(),
            'resendOtp' => $this->resendOtpRules(),
            'verifyForgotPasswordOtp' => $this->verifyForgotPasswordOtpRules(),
            'resetPassword' => $this->resetPasswordRules(),
            default => [],
        };
    }

    private function registerRules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:6|confirmed',
            'fcm_token' => 'nullable|string|max:5000',
            'id_front' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'id_back' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'restaurant_info' => 'required|string',
            'tax_card_number' => 'nullable|string|max:255',
            'tax_card_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'commercial_register_number' => 'nullable|string|max:255',
            'commercial_register_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'main_photo' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'restaurant_name' => 'required|string|max:255',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'subcategory_ids' => 'nullable|array|min:1',
            'subcategory_ids.*' => 'exists:subcategories,id',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'delivery_address' => 'required|string',
            'location' => 'required|string',
            'kitchen_photo_1' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'kitchen_photo_2' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'kitchen_photo_3' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'working_time' => 'required|array|min:1',
            'working_time.*.day' => 'required|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday|distinct',
            'working_time.*.from' => 'required|date_format:g:i A',
            'working_time.*.to' => 'required|date_format:g:i A',
        ];
    }

    private function verifyOtpRules(): array
    {
        return [
            'phone' => 'required|string|max:30',
            'otp' => 'required|string|size:4',
        ];
    }

    private function loginRules(): array
    {
        return [
            'phone' => 'required|string|max:30',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string|max:5000',
        ];
    }

    private function forgotPasswordRules(): array
    {
        return [
            'phone' => 'required|string|max:30',
        ];
    }

    private function resendOtpRules(): array
    {
        return [
            'phone' => 'required|string|max:30',
        ];
    }

    private function resetPasswordRules(): array
    {
        return [
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:6|confirmed',
        ];
    }

    private function verifyForgotPasswordOtpRules(): array
    {
        return [
            'phone' => 'required|string|max:30',
            'otp' => 'required|string|size:4',
        ];
    }
}
