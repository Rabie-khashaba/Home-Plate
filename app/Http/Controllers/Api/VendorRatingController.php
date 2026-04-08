<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppUser;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorRating;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorRatingController extends Controller
{
    public function store(Request $request, int $orderId): JsonResponse
    {
        $appUser = $this->requireActor($request->user(), AppUser::class, 'Only app users can rate vendors.');
        if ($appUser instanceof JsonResponse) {
            return $appUser;
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = Order::query()
            ->whereKey($orderId)
            ->where('app_user_id', $appUser->id)
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order not found for this app user.',
            ], 404);
        }

        if ($order->status !== Order::STATUS_DELIVERED) {
            return response()->json([
                'message' => 'You can rate this vendor only after a delivered order.',
            ], 422);
        }

        $vendor = Vendor::findOrFail($order->vendor_id);

        $rating = VendorRating::updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'app_user_id' => $appUser->id,
            ],
            [
                'order_id' => $order->id,
                'rating' => (int) $validated['rating'],
                'review' => $validated['review'] ?? null,
            ]
        );

        $vendor->loadCount('ratings')->loadAvg('ratings', 'rating');

        return response()->json([
            'message' => 'Vendor rating saved successfully.',
            'data' => [
                'rating' => [
                    'id' => $rating->id,
                    'value' => (int) $rating->rating,
                    'review' => $rating->review,
                    'order_id' => $rating->order_id,
                    'created_at' => optional($rating->created_at)?->toISOString(),
                    'updated_at' => optional($rating->updated_at)?->toISOString(),
                ],
                'vendor_rating_summary' => [
                    'average' => round((float) ($vendor->ratings_avg_rating ?? 0), 1),
                    'count' => (int) ($vendor->ratings_count ?? 0),
                ],
            ],
        ], $rating->wasRecentlyCreated ? 201 : 200);
    }

    private function requireActor(?Model $actor, string $expectedClass, string $message): Model|JsonResponse
    {
        if (! $actor instanceof $expectedClass) {
            return response()->json(['message' => $message], 403);
        }

        return $actor;
    }
}
