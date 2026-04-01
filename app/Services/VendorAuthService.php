<?php

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VendorAuthService
{
    private const REGISTER_PREFIX = 'vendor_auth:register:';
    private const OTP_TTL_MINUTES = 10;

    public function __construct(
        private readonly WhatsappService $whatsappService
    ) {
    }

    public function startRegistration(array $data): array
    {
        if (Vendor::where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Phone already registered.'],
            ]);
        }

        $data = $this->storeRegisterFiles($data);

        $otp = $this->generateOtp();

        // Keep password plain in cache because Vendor model hashes via mutator.
        Cache::put($this->registerKey($data['phone']), [
            'otp_hash' => Hash::make($otp),
            'payload' => [
                'full_name' => $data['full_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
                'password' => $data['password'],
                'id_front' => $data['id_front'] ?? null,
                'id_back' => $data['id_back'] ?? null,
                'restaurant_info' => $data['restaurant_info'] ?? null,
                'main_photo' => $data['main_photo'] ?? null,
                'restaurant_name' => $data['restaurant_name'],
                'city_id' => $data['city_id'],
                'area_id' => $data['area_id'],
                'delivery_address' => $data['delivery_address'],
                'location' => $data['location'] ?? null,
                'kitchen_photo_1' => $data['kitchen_photo_1'] ?? null,
                'kitchen_photo_2' => $data['kitchen_photo_2'] ?? null,
                'kitchen_photo_3' => $data['kitchen_photo_3'] ?? null,
                'working_time' => $data['working_time'] ?? null,
                'is_active' => false,
                'status' => 'pending',
            ],
        ], now()->addMinutes(self::OTP_TTL_MINUTES));

        $this->sendOtpOrFail($data['phone'], $otp, 'vendor_register');

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

        if (Vendor::where('phone', $phone)->exists()) {
            Cache::forget($this->registerKey($phone));

            throw ValidationException::withMessages([
                'phone' => ['Phone already registered.'],
            ]);
        }

        $vendor = Vendor::create($cached['payload']);
        Cache::forget($this->registerKey($phone));

        $token = $vendor->createToken('vendor_token')->plainTextToken;

        return [
            'message' => 'Vendor account created successfully.',
            'vendor' => $vendor,
            'token' => $token,
        ];
    }

    public function login(string $phone, string $password): array
    {
        $vendor = Vendor::where('phone', $phone)->first();

        if (! $vendor || ! Hash::check($password, $vendor->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Invalid phone or password.'],
            ]);
        }

        // if (! $vendor->is_active) {
        //     throw ValidationException::withMessages([
        //         'phone' => ['Vendor account is not active.'],
        //     ]);
        // }

        $token = $vendor->createToken('vendor_token')->plainTextToken;

        return [
            'message' => 'Login successful.',
            'vendor' => $vendor,
            'token' => $token,
        ];
    }

    public function sendForgotPasswordOtp(string $phone): array
    {
        $vendor = Vendor::where('phone', $phone)->first();

        if (! $vendor) {
            throw ValidationException::withMessages([
                'phone' => ['Phone is not registered.'],
            ]);
        }

        $otp = $this->generateOtp();

        try {
            $this->storeOtp($vendor, $otp);
            $this->sendOtpOrFail($phone, $otp, 'vendor_reset_password');
        } catch (ValidationException $e) {
            $this->clearOtp($vendor);
            $vendor->save();
            throw $e;
        }

        return [
            'message' => 'OTP sent to WhatsApp for password reset.',
            'expires_in_minutes' => self::OTP_TTL_MINUTES,
        ];
    }

    public function resendOtp(string $phone): array
    {
        $vendor = Vendor::where('phone', $phone)->first();

        if ($vendor) {
            $otp = $this->generateOtp();

            try {
                $this->storeOtp($vendor, $otp);
                $this->sendOtpOrFail($phone, $otp, 'vendor_resend_otp');
            } catch (ValidationException $e) {
                $this->clearOtp($vendor);
                $vendor->save();
                throw $e;
            }

            return [
                'message' => 'OTP sent successfully.',
                'phone' => $vendor->phone,
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

            $this->sendOtpOrFail($phone, $otp, 'vendor_register');

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

    public function verifyForgotPasswordOtp(string $phone, ?string $otp): array
    {


        $vendor = Vendor::where('phone', $phone)->first();

        if (! $vendor) {
            throw ValidationException::withMessages([
                'phone' => ['Phone is not registered.'],
            ]);
        }

        if (! $otp) {
            throw ValidationException::withMessages([
                'otp' => ['Invalid OTP.'],
            ]);
        }

        $this->assertValidOtp($vendor, $otp);
        $this->clearOtp($vendor);
        $vendor->save();

        return [
            'message' => 'OTP verified successfully.',
        ];
    }


    public function resetPassword(string $phone, string $password): array
    {
        $vendor = Vendor::where('phone', $phone)->first();

        if (! $vendor) {
            throw ValidationException::withMessages([
                'phone' => ['Phone is not registered.'],
            ]);
        }

        // Vendor model hashes via mutator.
        $vendor->password = $password;
        $vendor->save();

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

    private function storeRegisterFiles(array $data): array
    {
        foreach ($this->registerFileFields() as $field) {
            if (isset($data[$field]) && $data[$field] instanceof UploadedFile) {
                $file = $data[$field];
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = Str::slug($originalName) ?: $field;
                $extension = $file->getClientOriginalExtension();
                $fileName = $safeName . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $extension;

                $data[$field] = $file->storeAs("vendors/register/{$field}", $fileName, 'public');
            }
        }

        return $data;
    }

    private function registerFileFields(): array
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

    private function storeOtp(Vendor $vendor, string $otp): void
    {
        $vendor->otp_code = $otp;
        $vendor->otp_expires_at = now()->addMinutes(self::OTP_TTL_MINUTES);
        $vendor->save();
    }

    private function clearOtp(Vendor $vendor): void
    {
        $vendor->otp_code = null;
        $vendor->otp_expires_at = null;
    }

    private function assertValidOtp(Vendor $vendor, string $otp): void
    {
        if (! $vendor->otp_code) {
            throw ValidationException::withMessages([
                'otp' => ['OTP not found. Please request a new OTP.'],
            ]);
        }

        if ($vendor->otp_code !== $otp) {
            throw ValidationException::withMessages([
                'otp' => ['OTP value not found.'],
            ]);
        }

        if (! $vendor->otp_expires_at || now()->greaterThan($vendor->otp_expires_at)) {
            throw ValidationException::withMessages([
                'otp' => ['OTP expired.'],
            ]);
        }
    }
}
