<?php

namespace App\Services;

use App\Models\Delivery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DeliveryAuthService
{
    private const REGISTER_PREFIX = 'delivery_auth:register:';
    private const OTP_TTL_MINUTES = 10;

    public function __construct(
        protected WhatsappService $whatsappService
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

        try {
            $this->storeOtp($delivery, $otp);
            $this->sendOtpOrFail($phone, $otp, 'reset_password');
        } catch (ValidationException $e) {
            $this->clearOtp($delivery);
            $delivery->save();
            throw $e;
        }

        return [
            'message' => 'OTP sent to WhatsApp for password reset.',
            'expires_in_minutes' => self::OTP_TTL_MINUTES,
        ];
    }

    public function resendOtp(string $phone): array
    {
        $delivery = Delivery::where('phone', $phone)->first();

        if ($delivery) {
            $otp = $this->generateOtp();

            try {
                $this->storeOtp($delivery, $otp);
                $this->sendOtpOrFail($phone, $otp, 'resend_otp');
            } catch (ValidationException $e) {
                $this->clearOtp($delivery);
                $delivery->save();
                throw $e;
            }

            return [
                'message' => 'OTP sent successfully.',
                'phone' => $delivery->phone,
            ];
        }

        $cacheKey = $this->registerKey($phone);
        $cachedData = Cache::get($cacheKey);

        if ($cachedData && isset($cachedData['payload'])) {
            $otp = $this->generateOtp();

            Cache::put($cacheKey, [
                'otp_hash' => Hash::make($otp),
                'payload' => $cachedData['payload'],
            ], now()->addMinutes(self::OTP_TTL_MINUTES));

            $this->sendOtpOrFail($phone, $otp, 'register');

            return [
                'message' => 'OTP sent successfully.',
                'phone' => $phone,
                'registration_pending' => true,
            ];
        }

        throw ValidationException::withMessages([
            'phone' => ['User not found or registration expired.'],
        ]);
    }

    public function verifyForgotPasswordOtp(string $phone, string $otp): array
    {
        $delivery = Delivery::where('phone', $phone)->first();

        if (! $delivery) {
            throw ValidationException::withMessages([
                'phone' => ['Phone is not registered.'],
            ]);
        }

        $this->assertValidOtp($delivery, $otp);
        $this->clearOtp($delivery);
        $delivery->save();

        return [
            'message' => 'OTP verified successfully.',
        ];
    }

    public function resetPassword(string $phone, string $password): array
    {
        $delivery = Delivery::where('phone', $phone)->first();

        if (! $delivery) {
            throw ValidationException::withMessages([
                'phone' => ['Phone is not registered.'],
            ]);
        }

        $delivery->password = Hash::make($password);
        $delivery->save();

        return [
            'message' => 'Password reset successfully.',
        ];
    }

    private function sendOtpOrFail(string $phone, string $otp, string $purpose): void
    {
        $message = "Your verification code is: {$otp}";
        $sent = $this->whatsappService->send($phone, $message);

        if (! is_array($sent) || ! ($sent['success'] ?? false)) {
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

    private function storeOtp(Delivery $delivery, string $otp): void
    {
        $delivery->otp_code = $otp;
        $delivery->otp_expires_at = now()->addMinutes(self::OTP_TTL_MINUTES);
        $delivery->save();
    }

    private function clearOtp(Delivery $delivery): void
    {
        $delivery->otp_code = null;
        $delivery->otp_expires_at = null;
    }

    private function assertValidOtp(Delivery $delivery, string $otp): void
    {
        if ($delivery->otp_code !== $otp) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        if (! $delivery->otp_expires_at || now()->greaterThan($delivery->otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['OTP expired.'],
            ]);
        }
    }
}
