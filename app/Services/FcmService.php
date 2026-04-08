<?php

namespace App\Services;

use App\Models\AppUser;
use App\Models\Delivery;
use App\Models\DeviceToken;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;

class FcmService
{
    public function __construct(
        private readonly FirebaseNotificationService $firebaseNotificationService
    ) {
    }

    public function sendToAudience(string $audience, string $title, string $body, array $extraData = [], array $targetIds = []): array
    {
        $tokens = DeviceToken::query()
            ->when($audience === 'users', fn ($query) => $query->where('tokenable_type', AppUser::class))
            ->when($audience === 'vendors', fn ($query) => $query->where('tokenable_type', Vendor::class))
            ->when($audience === 'riders', fn ($query) => $query->where('tokenable_type', Delivery::class))
            ->when($targetIds !== [], fn ($query) => $query->whereIn('tokenable_id', $targetIds))
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            Log::info('FCM notification requested with no registered device tokens.', [
                'audience' => $audience,
                'title' => $title,
            ]);

            return [
                'success' => false,
                'total' => 0,
                'message' => 'No device tokens found for the selected audience.',
            ];
        }

        $result = $this->firebaseNotificationService->sendToTokens($tokens, $title, $body, $extraData);

        return [
            'success' => (bool) $result['status'],
            'total' => (int) ($result['data']['success_count'] ?? 0),
            'message' => $result['message'],
            'details' => $result['data'] ?? [],
        ];
    }
}
