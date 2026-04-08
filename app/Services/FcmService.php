<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FcmService
{
    public function sendToAudience(string $audience, string $title, string $body, array $extraData = []): array
    {
        // The project does not currently store FCM device tokens, so keep the
        // notification workflow alive and report that nothing was sent.
        Log::info('FCM notification requested without token delivery implementation.', [
            'audience' => $audience,
            'title' => $title,
            'body' => $body,
            'extra_data' => $extraData,
        ]);

        return [
            'success' => false,
            'total' => 0,
            'message' => 'FCM delivery is not configured yet.',
        ];
    }
}
