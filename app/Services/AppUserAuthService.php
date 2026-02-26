<?php

namespace App\Services;

use App\Models\AppUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AppUserAuthService
{
    private const REGISTER_PREFIX = 'app_user_auth:register:';
    private const OTP_TTL_MINUTES = 10;

    public function __construct(
        private readonly WhatsappService $whatsappService
    ) {
    }

    public function startRegistration(array $data): array
    {
        if (AppUser::where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Phone already registered.'],
            ]);
        }

        $otp = $this->generateOtp();

        Cache::put($this->registerKey($data['phone']), [
            'otp_hash' => Hash::make($otp),
            'payload' => [
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'gender' => $data['gender'] ?? null,
                'dob' => $data['dob'] ?? null,
                'city_id' => $data['city_id'],
                'area_id' => $data['area_id'],
                'delivery_addresses' => $data['delivery_addresses'] ?? null,
                'location' => $data['location'] ?? null,
                'is_active' => true,
            ],
        ], now()->addMinutes(self::OTP_TTL_MINUTES));

        $this->sendOtpOrFail($data['phone'], $otp, 'app_user_register');

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

        if (AppUser::where('phone', $phone)->exists()) {
            Cache::forget($this->registerKey($phone));

            throw ValidationException::withMessages([
                'phone' => ['Phone already registered.'],
            ]);
        }

        $appUser = AppUser::create($cached['payload']);
        Cache::forget($this->registerKey($phone));

        $token = $appUser->createToken('app_user_token')->plainTextToken;

        return [
            'message' => 'App user account created successfully.',
            'user' => $appUser,
            'token' => $token,
        ];
    }

    public function login(string $phone, string $password): array
    {
        $appUser = AppUser::where('phone', $phone)->first();

        if (! $appUser || ! Hash::check($password, $appUser->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Invalid phone or password.'],
            ]);
        }

        if (! $appUser->is_active) {
            throw ValidationException::withMessages([
                'phone' => ['Account is not active.'],
            ]);
        }

        $token = $appUser->createToken('app_user_token')->plainTextToken;

        return [
            'message' => 'Login successful.',
            'user' => $appUser,
            'token' => $token,
        ];
    }

    public function sendForgotPasswordOtp(string $phone): array
    {
        $appUser = AppUser::where('phone', $phone)->first();

        if (! $appUser) {
            throw ValidationException::withMessages([
                'phone' => ['Phone is not registered.'],
            ]);
        }

        $otp = $this->generateOtp();

        try {
            $this->storeOtp($appUser, $otp);
            $this->sendOtpOrFail($phone, $otp, 'app_user_reset_password');
        } catch (ValidationException $e) {
            $this->clearOtp($appUser);
            $appUser->save();
            throw $e;
        }

        return [
            'message' => 'OTP sent to WhatsApp for password reset.',
            'expires_in_minutes' => self::OTP_TTL_MINUTES,
        ];
    }

    public function resendOtp(string $phone): array
    {
        $appUser = AppUser::where('phone', $phone)->first();

        if ($appUser) {
            $otp = $this->generateOtp();

            try {
                $this->storeOtp($appUser, $otp);
                $this->sendOtpOrFail($phone, $otp, 'app_user_resend_otp');
            } catch (ValidationException $e) {
                $this->clearOtp($appUser);
                $appUser->save();
                throw $e;
            }

            return [
                'message' => 'OTP sent successfully.',
                'phone' => $appUser->phone,
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

            $this->sendOtpOrFail($phone, $otp, 'app_user_register');

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
        $appUser = AppUser::where('phone', $phone)->first();

        if (! $appUser) {
            throw ValidationException::withMessages([
                'phone' => ['Phone is not registered.'],
            ]);
        }

        $this->assertValidOtp($appUser, $otp);
        $this->clearOtp($appUser);
        $appUser->save();

        return [
            'message' => 'OTP verified successfully.',
        ];
    }

    public function resetPassword(string $phone, string $password): array
    {
        $appUser = AppUser::where('phone', $phone)->first();

        if (! $appUser) {
            throw ValidationException::withMessages([
                'phone' => ['Phone is not registered.'],
            ]);
        }

        $appUser->password = Hash::make($password);
        $appUser->save();

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
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function registerKey(string $phone): string
    {
        return self::REGISTER_PREFIX.$phone;
    }

    private function storeOtp(AppUser $appUser, string $otp): void
    {
        $appUser->otp_code = $otp;
        $appUser->otp_expires_at = now()->addMinutes(self::OTP_TTL_MINUTES);
        $appUser->save();
    }

    private function clearOtp(AppUser $appUser): void
    {
        $appUser->otp_code = null;
        $appUser->otp_expires_at = null;
    }

    private function assertValidOtp(AppUser $appUser, string $otp): void
    {
        if ($appUser->otp_code !== $otp) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        if (! $appUser->otp_expires_at || now()->greaterThan($appUser->otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['OTP expired.'],
            ]);
        }
    }
}
