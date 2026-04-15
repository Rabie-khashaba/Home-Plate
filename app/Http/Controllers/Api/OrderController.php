<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OrderPinVerificationRequest;
use App\Models\AppUser;
use App\Models\Delivery;
use App\Models\DeviceToken;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UserNotification;
use App\Models\Vendor;
use App\Models\Coupon;
use App\Interfaces\PaymentGatewayInterface;
use App\Services\CouponService;
use App\Services\OrderPaymentRecorder;
use App\Services\FirebaseNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        private readonly FirebaseNotificationService $firebaseNotificationService
    ) {
    }

    public function store(Request $request, PaymentGatewayInterface $paymentGateway, OrderPaymentRecorder $paymentRecorder, CouponService $couponService): JsonResponse
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
            'coupon_code' => ['nullable', 'string', 'max:50'],
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

        $couponResult = $couponService->validateAndCalculate($validated['coupon_code'] ?? null, (float) $orderCost, (float) $deliveryFee);
        if (($validated['coupon_code'] ?? null) && $couponResult['coupon'] === null) {
            return response()->json(['message' => $couponResult['message'] ?? 'Invalid coupon.'], 422);
        }

        $discountAmount = (float) ($couponResult['discount_amount'] ?? 0);
        $totalAmount = round($orderCost + $deliveryFee - $discountAmount, 2);
        $paymentMethod = (string) $validated['payment_method'];
        $coupon = $couponResult['coupon'];

        $order = Order::create([
            'order_number' => $this->generateOrderNumber(),
            'app_user_id' => $appUser->id,
            'vendor_id' => (int) $vendorIds->first(),
            'order_cost' => $orderCost,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totalAmount,
            'coupon_id' => $coupon?->id,
            'coupon_code' => $coupon?->code,
            'coupon_type' => $coupon?->type,
            'coupon_value' => $coupon?->value,
            'coupon_discount_percent' => $couponResult['discount_percent'] ?? null,
            'coupon_discount_amount' => $couponResult['discount_amount'] ?? 0,
            'coupon_redeemed_at' => ($coupon && $paymentMethod !== 'visa') ? now() : null,
            'payment_method' => $validated['payment_method'],
            'payment_status' => $paymentMethod === 'visa' ? 'pending' : 'paid',
            'payment_reference' => $validated['payment_reference'] ?? null,
            'delivery_address' => $validated['delivery_address'],
            'ordered_at' => now(),
            'status' => $paymentMethod === 'visa'
                ? Order::STATUS_AWAITING_PAYMENT
                : Order::STATUS_PENDING_VENDOR_PREPARATION,
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

        $order->loadMissing('vendor');
        $this->notifyVendorAboutCreatedOrder($order, $appUser);

        if ($coupon && $paymentMethod !== 'visa') {
            Coupon::query()->where('id', $coupon->id)->increment('used_count');
        }

        $paymentUrl = null;
        if ($paymentMethod === 'visa') {
            $name = trim((string) ($appUser->name ?? ''));
            $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $firstName = $parts[0] ?? 'Customer';
            $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : ' ';

            $paymentRequest = new Request([
                'amount' => (float) $totalAmount,
                'currency' => (string) config('paymob.currency', 'EGP'),
                'delivery_needed' => false,
                'items' => [],
                'merchant_order_id' => 'ORDER-' . $order->id,
                'shipping_data' => [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone_number' => (string) ($appUser->phone ?? ''),
                    'email' => (string) ($appUser->email ?? ''),
                ],
            ]);

            $paymentResponse = $paymentGateway->sendPayment($paymentRequest);
            if (($paymentResponse['success'] ?? false) && ! empty($paymentResponse['url'])) {
                $paymentUrl = (string) $paymentResponse['url'];
                $order->update([
                    'payment_reference' => $paymentResponse['provider_order_id'] ?? null,
                    'payment_status' => 'pending',
                ]);

                $paymentRecorder->createPendingPaymobPayment($order, $paymentResponse, $paymentRequest->all());
            } else {
                $order->update(['payment_status' => 'unpaid']);
            }
        }

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => $order->load(['orderItems.item', 'vendor:id,restaurant_name,full_name,phone', 'latestPayment']),
            'delivery_pin' => $order->delivery_pin, // visible to app user only
            'payment_url' => $paymentUrl,
        ], 201);
    }

    public function myOrders(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof AppUser) {
            $query = $this->ordersQueryForActor($user);
            $this->applyOrdersFilters($query, $request);
            $this->withVendorItems($query);
            $orders = $query->latest()->get();

            return response()->json([
                'message' => $orders->isEmpty() ? 'No orders found.' : 'Orders fetched successfully.',
                'data' => $this->withVendorDetails($this->withOrderItemImageUrls($orders)),
            ]);
        }

        if ($user instanceof Vendor) {
            $query = $this->ordersQueryForActor($user);
            $this->applyOrdersFilters($query, $request);
            $this->withVendorItems($query);
            $orders = $query->latest()->get();

            return response()->json([
                'message' => $orders->isEmpty() ? 'No orders found.' : 'Orders fetched successfully.',
                'data' => $this->withVendorDetails($this->withOrderItemImageUrls($orders)),
            ]);
        }

        if ($user instanceof Delivery) {
            $query = $this->ordersQueryForActor($user);
            $this->applyOrdersFilters($query, $request);
            $this->withVendorItems($query);
            $orders = $query->latest()->get();

            return response()->json([
                'message' => $orders->isEmpty() ? 'No orders found.' : 'Orders fetched successfully.',
                'data' => $this->withVendorDetails($this->withOrderItemImageUrls($orders)),
            ]);
        }

        return response()->json(['message' => 'Unauthorized actor type.'], 403);
    }

    public function ordersByUserId(Request $request, int $appUserId): JsonResponse
    {
        $user = $request->user();

        $query = Order::query()
            ->with([
                'appUser',
                'vendor:id,full_name,restaurant_name,main_photo',
                'delivery',
                'orderItems.item',
                'latestPayment' => function ($paymentQuery) {
                    $paymentQuery->select([
                        'payments.id',
                        'payments.order_id',
                        'payments.amount',
                        'payments.currency',
                        'payments.status',
                        'payments.paid_at',
                        'payments.reference',
                        'payments.provider_transaction_id',
                        'payments.provider_order_id',
                        'payments.provider',
                        'payments.iframe_url',
                    ]);
                },
            ])
            ->where('app_user_id', $appUserId);



        if ($user instanceof Vendor) {
            $query->where('vendor_id', $user->id);
        }

        if ($user instanceof Delivery) {
            $query->where('delivery_id', $user->id);
        }

        $this->applyOrdersFilters($query, $request);

        $orders = $query->latest()->get();

        $this->withVendorDetails($this->withOrderItemImageUrls($orders));

        return response()->json([
            'message' => $orders->isEmpty() ? 'No orders found.' : 'Orders fetched successfully.',
            'data' => $this->presentOrdersWithLiteVendorAndLatestPayment($orders),
        ]);
    }

    private function presentOrdersWithLiteVendorAndLatestPayment($orders): array
    {
        return $orders
            ->map(function (Order $order) {
                $vendor = $order->vendor;
                $latestPayment = $order->latestPayment;

                $orderedAt = $order->ordered_at ?? $order->created_at;
                $paidAt = $latestPayment?->paid_at;

                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'app_user_id' => $order->app_user_id,
                    'vendor_id' => $order->vendor_id,
                    'delivery_id' => $order->delivery_id,
                    'order_cost' => $order->order_cost,
                    'delivery_fee' => $order->delivery_fee,
                    'total_amount' => $order->total_amount,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'payment_reference' => $order->payment_reference,
                    'status' => $order->status,
                    'delivery_address' => $order->delivery_address,
                    'ordered_at' => $orderedAt ? $orderedAt->toDateTimeString() : null,
                    'notes' => $order->notes,
                    'coupon_code' => $order->coupon_code,
                    'coupon_discount_percent' => $order->coupon_discount_percent,
                    'coupon_discount_amount' => $order->coupon_discount_amount,
                    'vendor' => $vendor ? [
                        'id' => $vendor->id,
                        'full_name' => $vendor->full_name,
                        'restaurant_name' => $vendor->restaurant_name,
                        'main_photo' => $vendor->main_photo,
                    ] : null,
                    'latest_payment' => $latestPayment ? [
                        'id' => $latestPayment->id,
                        'provider' => $latestPayment->provider,
                        'status' => $latestPayment->status,
                        'amount' => $latestPayment->amount,
                        'currency' => $latestPayment->currency,
                        'reference' => $latestPayment->reference,
                        'provider_order_id' => $latestPayment->provider_order_id,
                        'provider_transaction_id' => $latestPayment->provider_transaction_id,
                        'paid_at' => $paidAt ? $paidAt->toDateTimeString() : null,
                        'payment_url' => $latestPayment->iframe_url,
                    ] : null,
                    'payment_url' => ($order->payment_method === 'visa'
                        && ($order->payment_status === 'pending')
                        && $latestPayment
                        && ($latestPayment->status === 'pending')
                        && ! empty($latestPayment->iframe_url))
                        ? (string) $latestPayment->iframe_url
                        : null,
                    'order_items' => $order->orderItems
                        ? $order->orderItems->map(function (OrderItem $orderItem) {
                            $item = $orderItem->item;

                            return [
                                'id' => $orderItem->id,
                                'item_id' => $orderItem->item_id,
                                'item_name' => $orderItem->item_name,
                                'quantity' => $orderItem->quantity,
                                'unit_price' => $orderItem->unit_price,
                                'discount_amount' => $orderItem->discount_amount,
                                'line_total' => $orderItem->line_total,
                                'item' => $item ? [
                                    'id' => $item->id,
                                    'name' => $item->name,
                                    'price' => $item->price,
                                    'discount' => $item->discount,
                                    'photos' => $item->photos,
                                ] : null,
                            ];
                        })->values()->all()
                        : [],
                ];
            })
            ->values()
            ->all();
    }

    public function filter(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:50'],
            'payment_status' => ['nullable', 'string', 'max:50'],
            'tab' => ['nullable', 'string', 'max:50'], // cancelled | delivered | confirmed_payment
            'app_user_id' => ['nullable', 'integer'],
            'vendor_id' => ['nullable', 'integer'],
            'delivery_id' => ['nullable', 'integer'],
        ]);

        $query = Order::query()->with([
            'appUser',
            'vendor:id,full_name,restaurant_name,main_photo',
            'delivery',
            'orderItems.item',
            'latestPayment' => function ($paymentQuery) {
                $paymentQuery->select([
                    'payments.id',
                    'payments.order_id',
                    'payments.amount',
                    'payments.currency',
                    'payments.status',
                    'payments.paid_at',
                    'payments.reference',
                    'payments.provider_transaction_id',
                    'payments.provider_order_id',
                    'payments.provider',
                    'payments.iframe_url',
                ]);
            },
        ]);

        if ($user instanceof AppUser) {
            $query->where('app_user_id', $user->id);
        } elseif ($user instanceof Vendor) {
            $query->where('vendor_id', $user->id);
        } elseif ($user instanceof Delivery) {
            $query->where('delivery_id', $user->id);
        } else {
            return response()->json(['message' => 'Unauthorized actor type.'], 403);
        }

        $query->when($validated['app_user_id'] ?? null, fn($q) => $q->where('app_user_id', (int) $validated['app_user_id']));
        $query->when($validated['vendor_id'] ?? null, fn($q) => $q->where('vendor_id', (int) $validated['vendor_id']));
        $query->when($validated['delivery_id'] ?? null, fn($q) => $q->where('delivery_id', (int) $validated['delivery_id']));

        $this->applyOrdersFilters($query, $request);

        $orders = $query->latest()->get();

        $this->withVendorDetails($this->withOrderItemImageUrls($orders));

        return response()->json([
            'message' => $orders->isEmpty() ? 'No orders found.' : 'Orders fetched successfully.',
            'data' => $this->presentOrdersWithLiteVendorAndLatestPayment($orders),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $search = trim($validated['q']);

        $vendors = Vendor::query()
            ->withCount('ratings')
            ->withAvg('ratings', 'rating')
            ->where(function ($query) use ($search) {
                $query
                    ->where('restaurant_name', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhereHas('items', function ($itemQuery) use ($search) {
                        $itemQuery->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->get();

        return response()->json([
            'message' => $vendors->isEmpty() ? 'No matching vendors found.' : 'Search results fetched successfully.',
            'data' => $vendors->map(function (Vendor $vendor) use ($search) {
                $vendorMatchedByName = str_contains(
                    mb_strtolower(($vendor->restaurant_name ?: $vendor->full_name ?: '')),
                    mb_strtolower($search)
                );

                $items = Item::query()
                    ->where('vendor_id', $vendor->id)
                    ->latest()
                    ->get();

                return [
                    'id' => $vendor->id,
                    'name' => $vendor->restaurant_name ?: $vendor->full_name,
                    'image' => $this->toPublicUrl($vendor->main_photo),
                    'rating' => [
                        'average' => round((float) ($vendor->ratings_avg_rating ?? 0), 1),
                        'count' => (int) ($vendor->ratings_count ?? 0),
                    ],
                    'matched_by' => $vendorMatchedByName ? 'vendor' : 'item',
                    'items' => $items->map(function (Item $item) {
                        return $this->formatSearchItem($item);
                    })->values(),
                ];
            })->values(),
        ]);
    }

    public function lastOrderWithTopItem(Request $request): JsonResponse
    {
        $appUser = $this->requireActor($request->user(), AppUser::class, 'Only app users can view this data.');
        if ($appUser instanceof JsonResponse) {
            return $appUser;
        }

        $lastOrder = Order::with(['orderItems.item', 'vendor', 'delivery'])
            ->where('app_user_id', $appUser->id)
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->first();
        if ($lastOrder) {
            $lastOrder->orderItems->each(function ($orderItem) {
                if ($orderItem->item) {
                    $this->applyItemImageUrls($orderItem->item);
                }
            });
        }

        $topItemRow = OrderItem::query()
            ->select('item_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('COUNT(*) as line_count'))
            ->whereHas('order', function ($query) use ($appUser) {
                $query->where('app_user_id', $appUser->id);
            })
            ->groupBy('item_id')
            ->orderByDesc('total_quantity')
            ->orderByDesc('line_count')
            ->orderBy('item_id')
            ->first();

        $topItem = null;
        if ($topItemRow) {
            $item = Item::find($topItemRow->item_id);
            if ($item) {
                $this->applyItemImageUrls($item);
            }
            $topItem = [
                'item_id' => $topItemRow->item_id,
                'total_quantity' => (int) $topItemRow->total_quantity,
                'line_count' => (int) $topItemRow->line_count,
                'item' => $item,
            ];
        }

        return response()->json([
            'message' => $lastOrder ? 'Last order fetched successfully.' : 'No orders found for this user.',
            'data' => [
                'last_order' => $lastOrder,
                'top_item' => $topItem,
            ],
        ]);
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

        $order->loadMissing('vendor');
        $this->notifyDeliveriesAboutAvailableOrder($order, $vendor);

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

        $order->loadMissing(['vendor', 'delivery']);
        $this->notifyAssignedDeliveryOrderReady($order, $vendor);

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

        // if ($order->status !== Order::STATUS_PICKED_UP) {
        //     return response()->json(['message' => 'Order must be picked up first.'], 422);
        // }

        $order->forceFill(['out_for_delivery_at' => now()])->save();
        $order->transitionTo(
            Order::STATUS_OUT_FOR_DELIVERY,
            'delivery',
            $delivery->id,
            'mark_out_for_delivery',
            'Delivery started heading to customer.'
        );

        $order->loadMissing(['appUser', 'vendor']);
        $this->notifyAppUserOrderOutForDelivery($order, $delivery);

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
            'data' => $order->load(['appUser', 'vendor', 'delivery', 'orderItems.item', 'statusLogs', 'payments']),
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

    private function ordersQueryForActor(?Model $user)
    {
        $query = Order::with(['appUser', 'vendor', 'delivery', 'orderItems.item', 'latestPayment']);

        if ($user instanceof AppUser) {
            return $query->where('app_user_id', $user->id);
        }

        if ($user instanceof Vendor) {
            return $query->where('vendor_id', $user->id);
        }

        if ($user instanceof Delivery) {
            return $query->where('delivery_id', $user->id);
        }

        return null;
    }

    private function withOrderItemImageUrls($orders)
    {
        return $orders->each(function ($order) {
            $order->orderItems->each(function ($orderItem) {
                if ($orderItem->item) {
                    $this->applyItemImageUrls($orderItem->item);
                }
            });
        });
    }

    private function applyItemImageUrls(Item $item): Item
    {
        $item->photos = $this->toPublicUrl($item->photos);

        return $item;
    }

    private function withVendorItems($query): void
    {
        $query->with([
            'vendor.items' => function ($itemsQuery) {
                $itemsQuery
                    ->where('approval_status', 'approved')
                    ->where('availability_status', 'published')
                    ->select(['id', 'vendor_id', 'name', 'price', 'discount', 'photos']);
            },
        ]);
    }

    private function withVendorDetails($orders)
    {
        return $orders->each(function ($order) {
            if (! $order->vendor) {
                return;
            }

            $order->vendor->main_photo = $this->toPublicUrl($order->vendor->main_photo);

            if ($order->vendor->relationLoaded('items')) {
                $order->vendor->items->each(function ($item) {
                    $this->applyItemImageUrls($item);
                });
            }
        });
    }

    private function applyOrdersFilters($query, Request $request): void
    {
        $tab = $request->get('tab');

        if (! $request->filled('status') && ! $request->filled('payment_status') && is_string($tab) && $tab !== '') {
            if ($tab === 'cancelled') {
                $query->where('status', Order::STATUS_CANCELLED);
            } elseif ($tab === 'delivered') {
                $query->where('status', Order::STATUS_DELIVERED);
            } elseif ($tab === 'confirmed_payment') {
                $query->where('payment_status', 'payment_confirmed');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }
    }

    private function formatSearchItem(Item $item): array
    {
        $data = $item->toArray();
        $data['photos'] = $this->toPublicUrl($data['photos'] ?? []);

        return $data;
    }

    private function notifyVendorAboutCreatedOrder(Order $order, AppUser $appUser): void
    {
        if (! $order->vendor) {
            return;
        }

        $appUserName = $appUser->name ?: ('User #' . $appUser->id);

        $this->sendNotificationToRecipients(
            $appUser,
            Vendor::class,
            [$order->vendor_id],
            'تم إنشاء طلب جديد',
            "تم إنشاء أوردر جديد بواسطة {$appUserName}.",
            $this->orderNotificationData($order, 'order_created')
        );
    }

    private function notifyDeliveriesAboutAvailableOrder(Order $order, Vendor $vendor): void
    {
        $vendorName = $vendor->restaurant_name ?: $vendor->full_name ?: ('Vendor #' . $vendor->id);

        $this->sendNotificationToRecipients(
            $vendor,
            Delivery::class,
            [],
            'طلب جديد متاح للتوصيل',
            "تم إنشاء أوردر جديد من {$vendorName}.",
            $this->orderNotificationData($order, 'order_searching_delivery')
        );
    }

    private function notifyAssignedDeliveryOrderReady(Order $order, Vendor $vendor): void
    {
        if (! $order->delivery_id) {
            return;
        }

        $vendorName = $vendor->restaurant_name ?: $vendor->full_name ?: ('Vendor #' . $vendor->id);

        $this->sendNotificationToRecipients(
            $vendor,
            Delivery::class,
            [$order->delivery_id],
            'الطلب جاهز للاستلام',
            "الأوردر جاهز للاستلام عند {$vendorName}.",
            $this->orderNotificationData($order, 'order_ready_for_pickup')
        );
    }

    private function notifyAppUserOrderOutForDelivery(Order $order, Delivery $delivery): void
    {
        if (! $order->app_user_id) {
            return;
        }

        $deliveryName = $delivery->first_name ?: ('Delivery #' . $delivery->id);

        $this->sendNotificationToRecipients(
            $delivery,
            AppUser::class,
            [$order->app_user_id],
            'الطلب في الطريق',
            "الأوردر الخاص بك أصبح في الطريق مع {$deliveryName}.",
            $this->orderNotificationData($order, 'order_out_for_delivery')
        );
    }

    private function orderNotificationData(Order $order, string $type): array
    {
        $order->loadMissing('vendor');

        return [
            'type' => $type,
            'order_id' => (string) $order->id,
            'order_number' => (string) $order->order_number,
            'status' => (string) $order->status,
            'main_photo' => $this->resolveOrderMainPhoto($order),
        ];
    }

    private function resolveOrderMainPhoto(Order $order): ?string
    {
        $vendorPhoto = $order->vendor?->main_photo;
        if (! empty($vendorPhoto)) {
            return $this->toPublicUrl($vendorPhoto);
        }

        $firstItem = $order->relationLoaded('orderItems')
            ? $order->orderItems->first()?->item
            : $order->orderItems()->with('item')->first()?->item;

        $firstItemPhoto = $firstItem?->photos[0] ?? null;

        return $firstItemPhoto ? $this->toPublicUrl($firstItemPhoto) : null;
    }

    private function sendNotificationToRecipients(
        Model $sender,
        string $recipientType,
        array $recipientIds,
        string $title,
        string $body,
        array $data = []
    ): void {
        try {
            $tokens = DeviceToken::query()
                ->where('tokenable_type', $recipientType)
                ->when($recipientIds !== [], fn ($query) => $query->whereIn('tokenable_id', $recipientIds))
                ->get(['token', 'tokenable_id'])
                ->filter(fn ($deviceToken) => ! empty($deviceToken->token))
                ->unique('token')
                ->values();

            if ($tokens->isEmpty()) {
                return;
            }

            $result = $this->firebaseNotificationService->sendToTokens(
                $tokens->pluck('token')->all(),
                $title,
                $body,
                $data
            );

            $successfulTokens = collect($result['data']['results'] ?? [])
                ->filter(fn ($item) => (bool) ($item['status'] ?? false) && ! empty($item['token']))
                ->pluck('token')
                ->all();

            if ($successfulTokens === []) {
                return;
            }

            $successfulRecipientIds = $tokens
                ->whereIn('token', $successfulTokens)
                ->pluck('tokenable_id')
                ->unique()
                ->values();

            $tokens
                ->whereIn('tokenable_id', $successfulRecipientIds)
                ->unique('tokenable_id')
                ->each(function ($deviceToken) use ($sender, $title, $body, $data, $recipientType) {
                    UserNotification::query()->create([
                        'sender_type' => $sender::class,
                        'sender_id' => $sender->getKey(),
                        'recipient_type' => $recipientType,
                        'recipient_id' => $deviceToken->tokenable_id,
                        'target_fcm_token' => $deviceToken->token,
                        'title' => $title,
                        'body' => $body,
                        'data' => $data ?: null,
                        'is_read' => false,
                        'read_at' => null,
                        'sent_at' => now(),
                    ]);
                });
        } catch (\Throwable $throwable) {
            Log::warning('Failed to send order notification.', [
                'sender_type' => $sender::class,
                'sender_id' => $sender->getKey(),
                'recipient_type' => $recipientType,
                'recipient_ids' => $recipientIds,
                'title' => $title,
                'error' => $throwable->getMessage(),
            ]);
        }
    }

    private function toPublicUrl($value)
    {
        if (empty($value)) {
            return $value;
        }

        if (is_array($value)) {
            return array_map(function ($item) {
                return $this->toPublicUrl($item);
            }, $value);
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $path = ltrim($value, '/');
        if (str_starts_with($path, 'storage/')) {
            return rtrim(config('app.url'), '/') . '/' . $path;
        }

        return rtrim(config('app.url'), '/') . '/storage/app/public/' . $path;
    }
}
