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
    private const RESET_PREFIX = 'vendor_auth:reset:';
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
                'working_time' => isset($data['working_time']) ? json_encode($data['working_time']) : null,
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

        $payload = $cached['payload'];

        if (isset($payload['working_time']) && is_array($payload['working_time'])) {
            $payload['working_time'] = json_encode($payload['working_time']);
        }

        $vendor = Vendor::create($payload);
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

        Cache::put($this->resetKey($phone), [
            'otp_hash' => Hash::make($otp),
        ], now()->addMinutes(self::OTP_TTL_MINUTES));

        $this->sendOtpOrFail($phone, $otp, 'vendor_reset_password');

        return [
            'message' => 'OTP sent to WhatsApp for password reset.',
            'expires_in_minutes' => self::OTP_TTL_MINUTES,
        ];
    }

    public function resetPassword(string $phone, string $otp, string $password): array
    {
        $vendor = Vendor::where('phone', $phone)->first();

        if (! $vendor) {
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

        // Vendor model hashes via mutator.
        $vendor->password = $password;
        $vendor->save();

        Cache::forget($this->resetKey($phone));

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

    private function resetKey(string $phone): string
    {
        return self::RESET_PREFIX.$phone;
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
}