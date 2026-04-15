<?php

namespace App\Http\Controllers;

use App\Models\AppUser;
use App\Models\Coupon;
use App\Models\Item;
use App\Models\Order;
use App\Models\Vendor;
use App\Interfaces\PaymentGatewayInterface;
use App\Services\CouponService;
use App\Services\OrderPaymentRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\ActivityLogger;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['appUser', 'vendor', 'delivery', 'orderItems']);
        $dateFilter = $request->get('date_filter');
        $from = $request->get('from');
        $to   = $request->get('to');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(fn($q) => $q->where('order_number', 'like', "%{$s}%")
                                      ->orWhereHas('appUser', fn($u) => $u->where('name', 'like', "%{$s}%")
                                                                          ->orWhere('phone', 'like', "%{$s}%")));
        }

        if ($request->filled('status')) $query->where('status', $request->get('status'));

        match($dateFilter) {
            'today'      => $query->whereDate('created_at', today()),
            'yesterday'  => $query->whereDate('created_at', today()->subDay()),
            'last_week'  => $query->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]),
            'last_month' => $query->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]),
            'custom'     => $query->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
                                  ->when($to,   fn($q) => $q->whereDate('created_at', '<=', $to)),
            default      => null,
        };

        $orders = $query->latest()->paginate(15)->withQueryString();

        $sq = fn() => Order::when($dateFilter === 'today',      fn($q) => $q->whereDate('created_at', today()))
                           ->when($dateFilter === 'yesterday',  fn($q) => $q->whereDate('created_at', today()->subDay()))
                           ->when($dateFilter === 'last_week',  fn($q) => $q->whereBetween('created_at', [now()->subWeek()->startOfDay(), now()->endOfDay()]))
                           ->when($dateFilter === 'last_month', fn($q) => $q->whereBetween('created_at', [now()->subMonth()->startOfDay(), now()->endOfDay()]))
                           ->when($dateFilter === 'custom' && $from, fn($q) => $q->whereDate('created_at', '>=', $from))
                           ->when($dateFilter === 'custom' && $to,   fn($q) => $q->whereDate('created_at', '<=', $to));

        $stats = [
            'total'     => $sq()->count(),
            'pending'   => $sq()->where('status', 'pending_vendor_preparation')->count(),
            'delivered' => $sq()->where('status', 'delivered')->count(),
            'cancelled' => $sq()->where('status', 'cancelled')->count(),
        ];

        return view('orders.index', compact('orders', 'stats'));
    }

    public function create()
    {
        $appUsers = AppUser::query()
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $vendors = Vendor::query()
            ->orderBy('restaurant_name')
            ->get(['id', 'restaurant_name', 'full_name']);

        $items = Item::query()
            ->where('approval_status', 'approved')
            ->where('availability_status', 'published')
            ->orderBy('vendor_id')
            ->orderBy('name')
            ->get(['id', 'vendor_id', 'name', 'price', 'discount']);

        return view('orders.create', compact('appUsers', 'vendors', 'items'));
    }

    public function store(Request $request, PaymentGatewayInterface $paymentGateway, OrderPaymentRecorder $paymentRecorder, CouponService $couponService)
    {
        $validated = $request->validate([
            'app_user_id' => ['required', 'exists:app_users,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:vodafone_cash,instapay,visa'],
            'delivery_address' => ['required', 'string'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $itemIds = collect($validated['items'])->pluck('item_id')->unique()->values();
        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        if ($items->count() !== $itemIds->count()) {
            return back()->withErrors(['items' => 'One or more items are invalid.'])->withInput();
        }

        $vendorIds = $items->pluck('vendor_id')->unique()->values();
        if ($vendorIds->count() !== 1 || (int) $vendorIds->first() !== (int) $validated['vendor_id']) {
            return back()->withErrors(['items' => 'All items must belong to the selected vendor.'])->withInput();
        }

        $orderLines = [];
        $orderCost = 0.0;

        foreach ($validated['items'] as $line) {
            /** @var \App\Models\Item $item */
            $item = $items->get((int) $line['item_id']);
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
            return back()->withErrors(['coupon_code' => $couponResult['message'] ?? 'Invalid coupon.'])->withInput();
        }

        $discountAmount = (float) ($couponResult['discount_amount'] ?? 0);
        $totalAmount = round($orderCost + $deliveryFee - $discountAmount, 2);
        $coupon = $couponResult['coupon'];

        $paymentMethod = (string) $validated['payment_method'];
        $appUser = AppUser::query()->findOrFail((int) $validated['app_user_id']);

        $order = DB::transaction(function () use ($validated, $orderLines, $orderCost, $deliveryFee, $totalAmount, $couponResult, $coupon) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'app_user_id' => $validated['app_user_id'],
                'vendor_id' => $validated['vendor_id'],
                'order_cost' => $orderCost,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'coupon_type' => $coupon?->type,
                'coupon_value' => $coupon?->value,
                'coupon_discount_percent' => $couponResult['discount_percent'] ?? null,
                'coupon_discount_amount' => $couponResult['discount_amount'] ?? 0,
                'coupon_redeemed_at' => ($coupon && $validated['payment_method'] !== 'visa') ? now() : null,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_method'] === 'visa' ? 'pending' : 'paid',
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
                'actor_type' => 'admin',
                'actor_id' => auth()->id(),
                'action' => 'create_order_from_dashboard',
                'note' => 'Order created from dashboard.',
            ]);

            return $order;
        });

        if ($coupon && $paymentMethod !== 'visa') {
            Coupon::query()->where('id', $coupon->id)->increment('used_count');
        }

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

            $response = $paymentGateway->sendPayment($paymentRequest);

            if (($response['success'] ?? false) && ! empty($response['url'])) {
                $order->update([
                    'payment_reference' => $response['provider_order_id'] ?? null,
                    'payment_status' => 'pending',
                ]);

                $paymentRecorder->createPendingPaymobPayment($order, $response, $paymentRequest->all());

                return redirect()->away($response['url']);
            }

            $order->update(['payment_status' => 'unpaid']);

            return redirect()
                ->route('orders.show', $order)
                ->withErrors(['payment' => 'تعذر إنشاء رابط الدفع. راجع إعدادات Paymob وحاول مرة أخرى.']);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Order created successfully.');
    }

    public function show(Order $order)
    {
        $order->load([
            'appUser',
            'vendor',
            'delivery',
            'orderItems.item',
            'statusLogs',
        ]);

        return view('orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $order->load(['orderItems.item', 'vendor']);

        $vendorItems = Item::query()
            ->where('vendor_id', $order->vendor_id)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'discount']);

        return view('orders.edit', compact('order', 'vendorItems'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:vodafone_cash,instapay,visa'],
            'delivery_address' => ['required', 'string'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $itemIds = collect($validated['items'])->pluck('item_id')->unique()->values();
        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        if ($items->count() !== $itemIds->count()) {
            return back()->withErrors(['items' => 'One or more items are invalid.'])->withInput();
        }

        $vendorIds = $items->pluck('vendor_id')->unique()->values();
        if ($vendorIds->count() !== 1 || (int) $vendorIds->first() !== (int) $order->vendor_id) {
            return back()->withErrors(['items' => 'All items must belong to the same order vendor.'])->withInput();
        }

        $orderLines = [];
        $orderCost = 0.0;

        foreach ($validated['items'] as $line) {
            /** @var \App\Models\Item $item */
            $item = $items->get((int) $line['item_id']);
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

        DB::transaction(function () use ($order, $validated, $orderLines, $orderCost, $deliveryFee, $totalAmount) {
            $order->update([
                'order_cost' => $orderCost,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'delivery_address' => $validated['delivery_address'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->orderItems()->delete();
            $order->orderItems()->createMany($orderLines);
        });
        ActivityLogger::log('updated', 'Updated order: ' . $order->order_number, $order);

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Order updated successfully.');
    }

    private function generateOrderNumber(): string
    {
        return 'HP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }
}
