<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DeliveryAuthRequest extends FormRequest
{
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
            'forgotPassword' => $this->phoneOnlyRules(),
            'resendOtp' => $this->phoneOnlyRules(),
            'verifyForgotPasswordOtp' => $this->verifyOtpRules(),
            'resetPassword' => $this->resetPasswordRules(),
            default => [],
        };
    }

    private function registerRules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:6',
            'city_id' => 'required|exists:cities,id',
            'area_id' => 'required|exists:areas,id',
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

    private function phoneOnlyRules(): array
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
}