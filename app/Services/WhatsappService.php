<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function sendOtp(string $phone, string $otp, string $purpose): bool
    {
        $message = "Your verification code is {$otp}. Purpose: {$purpose}.";

        // Placeholder integration. Replace with your WhatsApp provider API call.
        Log::info('WhatsApp OTP queued', [
            'phone' => $phone,
            'purpose' => $purpose,
            'message' => $message,
        ]);

        return true;
    }
}
