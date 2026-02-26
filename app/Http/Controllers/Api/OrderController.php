<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OrderPinVerificationRequest;
use App\Models\AppUser;
use App\Models\Delivery;
use App\Models\Item;
use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $appUser = $this->requireActor($request->user(), AppUser::class, 'Only app users can create orders.');
        if ($appUser instanceof JsonResponse) {
            return $appUser;
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:vodafone_cash,instapay,visa'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $itemIds = collect($validated['items'])->pluck('item_id')->unique()->values();
        $items = Item::with('vendor')
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        if ($items->count() !== $itemIds->count()) {
            return response()->json(['message' => 'One or more cart items are invalid.'], 422);
        }

        $vendorIds = $items->pluck('vendor_id')->unique()->values();
        if ($vendorIds->count() !== 1) {
            return response()->json(['message' => 'All cart items must belong to the same vendor.'], 422);
        }

        $orderLines = [];
        $orderCost = 0.0;

        foreach ($validated['items'] as $line) {
            /** @var \App\Models\Item $item */
            $item = $items->get($line['item_id']);
            $quantity = (int) $line['quantity'];
            $discountAmount = (float) ($item->discount ?? 0);
            $unitPrice = max(((float) $item->price) - $discountAmount, 0);
            $lineTotal = round($unitPrice * $quantity, 2);
            $orderCost += $lineTotal;

            $orderLines[] = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => $discountAmount,
                'line_total' => $lineTotal,
            ];
        }

        $orderCost = round($orderCost, 2);
        $deliveryFee = round((float) ($validated['delivery_fee'] ?? 0), 2);
        $totalAmount = round($orderCost + $deliveryFee, 2);

        $order = Order::create([
            'order_number' => $this->generateOrderNumber(),
            'app_user_id' => $appUser->id,
            'vendor_id' => (int) $vendorIds->first(),
            'order_cost' => $orderCost,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totalAmount,
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'paid',
            'payment_reference' => $validated['payment_reference'] ?? null,
            'delivery_address' => $validated['delivery_address'],
            'ordered_at' => now(),
            'status' => Order::STATUS_PENDING_VENDOR_PREPARATION,
            'delivery_pin' => (string) random_int(1000, 9999),
            'notes' => $validated['notes'] ?? null,
        ]);

        $order->orderItems()->createMany($orderLines);

        $order->statusLogs()->create([
            'from_status' => null,
            'to_status' => $order->status,
            'actor_type' => 'app_user',
            'actor_id' => $appUser->id,
            'action' => 'create_order',
            'note' => 'Order created by app user.',
            'meta' => [
                'payment_method' => $validated['payment_method'],
                'items_count' => count($orderLines),
            ],
        ]);

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => $order->load(['orderItems.item', 'vendor:id,restaurant_name,full_name,phone']),
            'delivery_pin' => $order->delivery_pin, // visible to app user only
        ], 201);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof AppUser) {
            $orders = Order::with(['orderItems.item', 'vendor', 'delivery'])
                ->where('app_user_id', $user->id)
                ->latest()
                ->get();

            return response()->json([
                'message' => $orders->isEmpty() ? 'No orders found.' : 'Orders fetched successfully.',
                'data' => $orders,
            ]);
        }

        if ($user instanceof Vendor) {
            $orders = Order::with(['appUser', 'delivery', 'orderItems.item'])
                ->where('vendor_id', $user->id)
                ->latest()
                ->get();

            return response()->json([
                'message' => $orders->isEmpty() ? 'No orders found.' : 'Orders fetched successfully.',
                'data' => $orders,
            ]);
        }

        if ($user instanceof Delivery) {
            $orders = Order::with(['appUser', 'vendor', 'orderItems.item'])
                ->where('delivery_id', $user->id)
                ->latest()
                ->get();

            return response()->json([
                'message' => $orders->isEmpty() ? 'No orders found.' : 'Orders fetched successfully.',
                'data' => $orders,
            ]);
        }

        return response()->json(['message' => 'Unauthorized actor type.'], 403);
    }

    public function vendorStartCooking(Request $request, $id): JsonResponse
    {
        $order = $this->findOrderOrFail($id);
        $vendor = $this->requireActor($request->user(), Vendor::class, 'Only vendors can start cooking.');
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        if ((int) $order->vendor_id !== (int) $vendor->id) {
            return response()->json(['message' => 'Order does not belong to this vendor.'], 403);
        }

        if ($order->status !== Order::STATUS_PENDING_VENDOR_PREPARATION) {
            return response()->json(['message' => 'Order is not in a startable state.'], 422);
        }

        $order->forceFill([
            'started_cooking_at' => now(),
            'delivery_requested_at' => now(),
        ])->save();

        $order->transitionTo(
            Order::STATUS_SEARCHING_DELIVERY,
            'vendor',
            $vendor->id,
            'start_cooking',
            'Vendor started cooking and dispatched order to delivery pool.'
        );

        return response()->json([
            'message' => 'Order sent to delivery pool successfully.',
            'data' => $order->fresh(['appUser', 'orderItems.item', 'delivery']),
        ]);
    }

    public function vendorReadyForPickup(Request $request, $id): JsonResponse
    {
        $order = $this->findOrderOrFail($id);
        $vendor = $this->requireActor($request->user(), Vendor::class, 'Only vendors can mark order ready.');
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        if ((int) $order->vendor_id !== (int) $vendor->id) {
            return response()->json(['message' => 'Order does not belong to this vendor.'], 403);
        }

        if ($order->status !== Order::STATUS_DELIVERY_ASSIGNED) {
            return response()->json(['message' => 'Order must be assigned to a delivery first.'], 422);
        }

        $order->forceFill(['ready_for_pickup_at' => now()])->save();
        $order->transitionTo(
            Order::STATUS_READY_FOR_PICKUP,
            'vendor',
            $vendor->id,
            'mark_ready_for_pickup',
            'Vendor marked order ready for pickup.'
        );

        return response()->json([
            'message' => 'Order is ready for pickup.',
            'data' => $order->fresh(['delivery']),
        ]);
    }

    public function vendorConfirmHandover(Request $request, $id): JsonResponse
    {
        $order = $this->findOrderOrFail($id);
        $vendor = $this->requireActor($request->user(), Vendor::class, 'Only vendors can confirm handover.');
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        if ((int) $order->vendor_id !== (int) $vendor->id) {
            return response()->json(['message' => 'Order does not belong to this vendor.'], 403);
        }

        if (! in_array($order->status, [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_HANDOVER_PENDING_CONFIRMATION], true)) {
            return response()->json(['message' => 'Order is not ready for handover confirmation.'], 422);
        }

        $order->forceFill(['vendor_handover_confirmed_at' => now()])->save();

        if ($order->delivery_pickup_confirmed_at) {
            $order->forceFill(['picked_up_at' => now()])->save();
            $order->transitionTo(
                Order::STATUS_PICKED_UP,
                'vendor',
                $vendor->id,
                'confirm_handover',
                'Vendor and delivery confirmed pickup handover.'
            );
        } else {
            $order->transitionTo(
                Order::STATUS_HANDOVER_PENDING_CONFIRMATION,
                'vendor',
                $vendor->id,
                'confirm_handover',
                'Vendor confirmed handover; waiting for delivery confirmation.'
            );
        }

        return response()->json([
            'message' => 'Vendor handover confirmation saved.',
            'data' => $order->fresh(['delivery']),
        ]);
    }

    public function deliveryAvailable(Request $request): JsonResponse
    {
        $delivery = $this->requireActor($request->user(), Delivery::class, 'Only delivery users can view available orders.');
        if ($delivery instanceof JsonResponse) {
            return $delivery;
        }

        $orders = Order::with(['appUser', 'vendor', 'orderItems.item'])
            ->where('status', Order::STATUS_SEARCHING_DELIVERY)
            ->latest()
            ->get();

        return response()->json([
            'message' => $orders->isEmpty() ? 'No available orders found.' : 'Available orders fetched successfully.',
            'data' => $orders,
        ]);
    }

    public function deliveryAccept(Request $request, $id): JsonResponse
    {
        $order = $this->findOrderOrFail($id);
        $delivery = $this->requireActor($request->user(), Delivery::class, 'Only delivery users can accept orders.');
        if ($delivery instanceof JsonResponse) {
            return $delivery;
        }

        if ($order->status !== Order::STATUS_SEARCHING_DELIVERY) {
            return response()->json(['message' => 'Order is not available for delivery acceptance.'], 422);
        }

        if ($order->delivery_id && (int) $order->delivery_id !== (int) $delivery->id) {
            return response()->json(['message' => 'Order already assigned to another delivery user.'], 409);
        }

        $order->forceFill([
            'delivery_id' => $delivery->id,
            'delivery_accepted_at' => now(),
        ])->save();

        $order->transitionTo(
            Order::STATUS_DELIVERY_ASSIGNED,
            'delivery',
            $delivery->id,
            'accept_delivery_assignment',
            'Delivery accepted order assignment.'
        );

        return response()->json([
            'message' => 'Order assigned successfully.',
            'data' => $order->fresh(['appUser', 'vendor', 'delivery', 'orderItems.item']),
        ]);
    }

    public function deliveryConfirmPickup(Request $request, $id): JsonResponse
    {
        $order = $this->findOrderOrFail($id);
        $delivery = $this->requireActor($request->user(), Delivery::class, 'Only delivery users can confirm pickup.');
        if ($delivery instanceof JsonResponse) {
            return $delivery;
        }

        if ((int) $order->delivery_id !== (int) $delivery->id) {
            return response()->json(['message' => 'Order is not assigned to this delivery user.'], 403);
        }

        if (! in_array($order->status, [Order::STATUS_READY_FOR_PICKUP, Order::STATUS_HANDOVER_PENDING_CONFIRMATION], true)) {
            return response()->json(['message' => 'Order is not ready for pickup confirmation.'], 422);
        }

        $order->forceFill(['delivery_pickup_confirmed_at' => now()])->save();

        if ($order->vendor_handover_confirmed_at) {
            $order->forceFill(['picked_up_at' => now()])->save();
            $order->transitionTo(
                Order::STATUS_PICKED_UP,
                'delivery',
                $delivery->id,
                'confirm_pickup_from_vendor',
                'Vendor and delivery confirmed pickup handover.'
            );
        } else {
            $order->transitionTo(
                Order::STATUS_HANDOVER_PENDING_CONFIRMATION,
                'delivery',
                $delivery->id,
                'confirm_pickup_from_vendor',
                'Delivery confirmed pickup; waiting for vendor confirmation.'
            );
        }

        return response()->json([
            'message' => 'Delivery pickup confirmation saved.',
            'data' => $order->fresh(['vendor']),
        ]);
    }

    public function deliveryMarkOutForDelivery(Request $request, $id): JsonResponse
    {
        $order = $this->findOrderOrFail($id);
        $delivery = $this->requireActor($request->user(), Delivery::class, 'Only delivery users can update delivery status.');
        if ($delivery instanceof JsonResponse) {
            return $delivery;
        }

        if ((int) $order->delivery_id !== (int) $delivery->id) {
            return response()->json(['message' => 'Order is not assigned to this delivery user.'], 403);
        }

        if ($order->status !== Order::STATUS_PICKED_UP) {
            return response()->json(['message' => 'Order must be picked up first.'], 422);
        }

        $order->forceFill(['out_for_delivery_at' => now()])->save();
        $order->transitionTo(
            Order::STATUS_OUT_FOR_DELIVERY,
            'delivery',
            $delivery->id,
            'mark_out_for_delivery',
            'Delivery started heading to customer.'
        );

        return response()->json([
            'message' => 'Order marked as out for delivery.',
            'data' => $order->fresh(),
        ]);
    }

    public function deliveryVerifyPinAndComplete(OrderPinVerificationRequest $request, $id): JsonResponse
    {
        try {
            $order = $this->findOrderOrFail($id);
            $delivery = $this->requireActor($request->user(), Delivery::class, 'Only delivery users can complete orders.');
            if ($delivery instanceof JsonResponse) {
                return $delivery;
            }

            if ((int) $order->delivery_id !== (int) $delivery->id) {
                return response()->json(['message' => 'Order is not assigned to this delivery user.'], 403);
            }

            if (! in_array($order->status, [Order::STATUS_PICKED_UP, Order::STATUS_OUT_FOR_DELIVERY], true)) {
                return response()->json(['message' => 'Order is not in a deliverable state.'], 422);
            }

            $validated = $request->validated();

            if ((string) $validated['pin'] !== (string) $order->delivery_pin) {
                return response()->json(['message' => 'Invalid PIN.'], 422);
            }

            $order->forceFill([
                'out_for_delivery_at' => $order->out_for_delivery_at ?? now(),
                'pin_verified_at' => now(),
                'delivered_at' => now(),
            ])->save();

            $order->transitionTo(
                Order::STATUS_DELIVERED,
                'delivery',
                $delivery->id,
                'verify_pin_and_complete_delivery',
                'Delivery verified PIN and completed order.'
            );

            return response()->json([
                'message' => 'Order delivered successfully.',
                'data' => $order->fresh(['appUser', 'vendor', 'delivery', 'orderItems.item']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'PIN verification failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to complete delivery.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        $order = $this->findOrderOrFail($id);
        $user = $request->user();

        $allowed = ($user instanceof AppUser && (int) $order->app_user_id === (int) $user->id)
            || ($user instanceof Vendor && (int) $order->vendor_id === (int) $user->id)
            || ($user instanceof Delivery && (int) $order->delivery_id === (int) $user->id)
            || ($user instanceof Delivery && $order->status === Order::STATUS_SEARCHING_DELIVERY);

        if (! $allowed) {
            return response()->json(['message' => 'You are not allowed to view this order.'], 403);
        }

        return response()->json([
            'data' => $order->load(['appUser', 'vendor', 'delivery', 'orderItems.item', 'statusLogs']),
        ]);
    }

    private function requireActor(?Model $actor, string $expectedClass, string $message): Model|JsonResponse
    {
        if (! $actor instanceof $expectedClass) {
            return response()->json(['message' => $message], 403);
        }

        return $actor;
    }

    private function generateOrderNumber(): string
    {
        return 'HP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    private function findOrderOrFail($id): Order
    {
        return Order::findOrFail($id);
    }
}
