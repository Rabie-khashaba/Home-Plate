<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\UserNotification;
use App\Services\FirebaseNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FirebaseNotificationController extends Controller
{
    public function __construct(
        private readonly FirebaseNotificationService $firebaseNotificationService
    ) {
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'data' => ['nullable', 'array'],
        ]);

        $sender = $this->resolveActor($request->user());
        if ($sender instanceof JsonResponse) {
            return $sender;
        }

        try {
            $result = $this->firebaseNotificationService->sendToToken(
                $validated['token'],
                $validated['title'],
                $validated['body'],
                $validated['data'] ?? []
            );

            if ($result['status']) {
                $this->storeNotification($sender, $validated['token'], $validated['title'], $validated['body'], $validated['data'] ?? []);
            }
        } catch (\Throwable $throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error while sending notification.',
                'error' => $throwable->getMessage(),
            ], 500);
        }

        return response()->json($result, $result['status'] ? 200 : 422);
    }

    public function sendBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tokens' => ['required', 'array', 'min:1', 'max:500'],
            'tokens.*' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:1000'],
            'data' => ['nullable', 'array'],
        ]);

        $sender = $this->resolveActor($request->user());
        if ($sender instanceof JsonResponse) {
            return $sender;
        }

        try {
            $result = $this->firebaseNotificationService->sendToTokens(
                $validated['tokens'],
                $validated['title'],
                $validated['body'],
                $validated['data'] ?? []
            );

            $this->storeBulkNotifications(
                $sender,
                $validated['title'],
                $validated['body'],
                $validated['data'] ?? [],
                $result['data']['results'] ?? []
            );
        } catch (\Throwable $throwable) {
            return response()->json([
                'status' => false,
                'message' => 'Unexpected error while sending notifications.',
                'error' => $throwable->getMessage(),
            ], 500);
        }

        return response()->json($result, $result['status'] ? 200 : 207);
    }

    public function updateToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'max:5000'],
            'platform' => ['sometimes', 'nullable', 'string', 'max:50'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $actor = $this->resolveActor($request->user());
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        DeviceToken::query()->updateOrCreate(
            ['token_hash' => DeviceToken::makeTokenHash($validated['fcm_token'])],
            [
                'tokenable_type' => $actor::class,
                'tokenable_id' => $actor->getKey(),
                'token' => $validated['fcm_token'],
                'platform' => $validated['platform'] ?? null,
                'device_name' => $validated['device_name'] ?? null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'FCM token updated successfully.',
            'data' => [
                'user_type' => class_basename($actor::class),
                'user_id' => $actor->getKey(),
                'has_fcm_token' => $actor->deviceTokens()->exists(),
            ],
        ]);
    }

    public function myNotifications(Request $request): JsonResponse
    {
        $actor = $this->resolveActor($request->user());
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $notifications = UserNotification::query()
            ->with('sender')
            ->where('recipient_type', $actor::class)
            ->where('recipient_id', $actor->getKey())
            ->latest('sent_at')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Notifications fetched successfully.',
            'data' => $notifications,
            'meta' => [
                'unread_count' => UserNotification::query()
                    ->where('recipient_type', $actor::class)
                    ->where('recipient_id', $actor->getKey())
                    ->where('is_read', false)
                    ->count(),
            ],
        ]);
    }

    public function markAsRead(Request $request, UserNotification $notification): JsonResponse
    {
        $actor = $this->resolveActor($request->user());
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        if ($notification->recipient_type !== $actor::class || (int) $notification->recipient_id !== (int) $actor->getKey()) {
            return response()->json([
                'status' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        if (! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read.',
            'data' => $notification->fresh(),
        ]);
    }

    private function storeNotification(Model $sender, string $token, string $title, string $body, array $data = []): void
    {
        $recipient = DeviceToken::query()
            ->with('tokenable')
            ->where('token_hash', DeviceToken::makeTokenHash($token))
            ->first()
            ?->tokenable;

        UserNotification::query()->create([
            'sender_type' => $sender::class,
            'sender_id' => $sender->getKey(),
            'recipient_type' => $recipient ? $recipient::class : null,
            'recipient_id' => $recipient?->getKey(),
            'target_fcm_token' => $token,
            'title' => $title,
            'body' => $body,
            'data' => $data ?: null,
            'is_read' => false,
            'read_at' => null,
            'sent_at' => now(),
        ]);
    }

    private function storeBulkNotifications(Model $sender, string $title, string $body, array $data, array $results): void
    {
        foreach ($results as $result) {
            if (! ($result['status'] ?? false) || empty($result['token'])) {
                continue;
            }

            $this->storeNotification($sender, (string) $result['token'], $title, $body, $data);
        }
    }

    private function resolveActor(mixed $actor): Model|JsonResponse
    {
        if (! $actor instanceof Model) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $actor;
    }
}
