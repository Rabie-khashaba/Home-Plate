<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with(['appUser', 'vendor', 'delivery', 'orderItems'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('order_number'), fn ($q) => $q->where('order_number', 'like', '%' . $request->string('order_number')->toString() . '%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', compact('orders'));
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

        return redirect()
            ->route('orders.show', $order)
            ->with('success', 'Order updated successfully.');
    }
}
