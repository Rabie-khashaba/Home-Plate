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
            foreach (['from', 'to'] as $key) {
                if (! empty($workingTime[$key]) && is_string($workingTime[$key])) {
                    // Normalize am/pm casing (e.g. "Pm" => "PM") before validation.
                    $workingTime[$key] = preg_replace_callback(
                        '/\b(am|pm)\b/i',
                        fn ($matches) => strtoupper($matches[1]),
                        trim($workingTime[$key])
                    );
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
            'id_front' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'id_back' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'restaurant_info' => 'required|string',
            'main_photo' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'restaurant_name' => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
            'delivery_address' => 'required|string',
            'location' => 'required|string',
            'kitchen_photo_1' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'kitchen_photo_2' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'kitchen_photo_3' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,svg,webp,avif|max:5120',
            'working_time' => 'required|array',
            'working_time.day' => 'required|in:saturday,sunday,monday,tuesday,wednesday,thursday,friday',
            'working_time.from' => 'required|date_format:g:i A',
            'working_time.to' => 'required|date_format:g:i A|after:working_time.from',
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
