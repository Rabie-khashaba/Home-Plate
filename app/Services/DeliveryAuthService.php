<?php

namespace App\Services;

use App\Models\Delivery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DeliveryAuthService
{
    private const REGISTER_PREFIX = 'delivery_auth:register:';
    private const RESET_PREFIX = 'delivery_auth:reset:';
    private const OTP_TTL_MINUTES = 10;

    public function __construct(
        private readonly WhatsappService $whatsappService
    ) {
    }

    public function startRegistration(array $data): array
    {
        if (Delivery::where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Phone already registered.'],
            ]);
        }

        $otp = $this->generateOtp();
        $cacheKey = $this->registerKey($data['phone']);

        Cache::put($cacheKey, [
            'otp_hash' => Hash::make($otp),
            'payload' => [
                'first_name' => $data['first_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'city_id' => $data['city_id'],
                'area_id' => $data['area_id'],
                'is_active' => true,
                'status' => $data['status'] ?? 'pending',
            ],
        ], now()->addMinutes(self::OTP_TTL_MINUTES));

        $this->sendOtpOrFail($data['phone'], $otp, 'register');

        return [
            'message' => 'OTP sent to WhatsApp for registration.',
            'expires_in_minutes' => self::OTP_TTL_MINUTES,
        ];
    }

    public function verifyRegistrationOtp(string $phone, string $otp): array
    {
        $cached = Cache::get($this->registerKey($phone));

        if (! $cached || ! isset($cached['otp_hash'], $cached['payload'])) {
            throw ValidationException::withMessages([
                'otp' => ['OTP expired or not found.'],
            ]);
        }

        if (! Hash::check($otp, $cached['otp_hash'])) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        if (Delivery::where('phone', $phone)->exists()) {
            Cache::forget($this->registerKey($phone));

            throw ValidationException::withMessages([
                'phone' => ['Phone already registered.'],
            ]);
        }

        $delivery = Delivery::create($cached['payload']);
        Cache::forget($this->registerKey($phone));

        $token = $delivery->createToken('delivery_token')->plainTextToken;

        return [
            'message' => 'Delivery account created successfully.',
            'delivery' => $delivery,
            'token' => $token,
        ];
    }

    public function login(string $phone, string $password): array
    {
        $delivery = Delivery::where('phone', $phone)->first();

        if (! $delivery || ! Hash::check($password, $delivery->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Invalid phone or password.'],
            ]);
        }

        if (! $delivery->is_active) {
            throw ValidationException::withMessages([
                'phone' => ['Account is not active.'],
            ]);
        }

        $token = $delivery->createToken('delivery_token')->plainTextToken;

        return [
            'message' => 'Login successful.',
            'delivery' => $delivery,
            'token' => $token,
        ];
    }

    public function sendForgotPasswordOtp(string $phone): array
    {
        $delivery = Delivery::where('phone', $phone)->first();

        if (! $delivery) {
            throw ValidationException::withMessages([
                'phone' => ['Phone is not registered.'],
            ]);
        }

        $otp = $this->generateOtp();

        Cache::put($this->resetKey($phone), [
            'otp_hash' => Hash::make($otp),
        ], now()->addMinutes(self::OTP_TTL_MINUTES));

        $this->sendOtpOrFail($phone, $otp, 'reset_password');

        return [
            'message' => 'OTP sent to WhatsApp for password reset.',
            'expires_in_minutes' => self::OTP_TTL_MINUTES,
        ];
    }

    public function resetPassword(string $phone, string $otp, string $password): array
    {
        $delivery = Delivery::where('phone', $phone)->first();

        if (! $delivery) {
            throw ValidationException::withMessages([
                'phone' => ['Phone is not registered.'],
            ]);
        }

        $cached = Cache::get($this->resetKey($phone));

        if (! $cached || ! isset($cached['otp_hash'])) {
            throw ValidationException::withMessages([
                'otp' => ['OTP expired or not found.'],
            ]);
        }

        if (! Hash::check($otp, $cached['otp_hash'])) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        $delivery->password = Hash::make($password);
        $delivery->save();

        Cache::forget($this->resetKey($phone));

        return [
            'message' => 'Password reset successfully.',
        ];
    }

    private function sendOtpOrFail(string $phone, string $otp, string $purpose): void
    {
        $sent = $this->whatsappService->sendOtp($phone, $otp, $purpose);

        if (! $sent) {
            throw ValidationException::withMessages([
                'phone' => ['Failed to send OTP via WhatsApp.'],
            ]);
        }
    }

    private function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function registerKey(string $phone): string
    {
        return self::REGISTER_PREFIX.$phone;
    }

    private function resetKey(string $phone): string
    {
        return self::RESET_PREFIX.$phone;
    }
}
