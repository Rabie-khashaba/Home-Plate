@extends('partial.master')
@section('title', 'Order Details')

@section('content')
<div class="panel space-y-6">
    <div class="flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Order Details</h5>
        <a href="{{ route('orders.index') }}" class="btn btn-outline-primary">Back</a>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded border p-4">
            <h6 class="mb-3 font-semibold">Order</h6>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Status:</strong> {{ ucwords(str_replace('_', ' ', $order->status)) }}</p>
            <p><strong>Order Cost:</strong> {{ number_format((float) $order->order_cost, 2) }}</p>
            <p><strong>Delivery Fee:</strong> {{ number_format((float) $order->delivery_fee, 2) }}</p>
            <p><strong>Total Amount:</strong> {{ number_format((float) $order->total_amount, 2) }}</p>
            <p><strong>Payment Method:</strong> {{ $order->paymentMethodLabel() }}</p>
            <p><strong>Payment Status:</strong> {{ ucfirst($order->payment_status) }}</p>
            <p><strong>Delivery Address:</strong> {{ $order->delivery_address }}</p>
            <p><strong>Order Date:</strong> {{ $order->ordered_at?->format('Y-m-d H:i:s') ?? $order->created_at?->format('Y-m-d H:i:s') }}</p>
        </div>

        <div class="rounded border p-4">
            <h6 class="mb-3 font-semibold">Client (App User)</h6>
            <p><strong>Name:</strong> {{ $order->appUser->name ?? '-' }}</p>
            <p><strong>Phone:</strong> {{ $order->appUser->phone ?? '-' }}</p>
            <p><strong>Email:</strong> {{ $order->appUser->email ?? '-' }}</p>
        </div>

        <div class="rounded border p-4">
            <h6 class="mb-3 font-semibold">Vendor</h6>
            <p><strong>Restaurant:</strong> {{ $order->vendor->restaurant_name ?? '-' }}</p>
            <p><strong>Owner:</strong> {{ $order->vendor->full_name ?? '-' }}</p>
            <p><strong>Phone:</strong> {{ $order->vendor->phone ?? '-' }}</p>
        </div>

        <div class="rounded border p-4">
            <h6 class="mb-3 font-semibold">Delivery</h6>
            <p><strong>Name:</strong> {{ $order->delivery->first_name ?? '-' }}</p>
            <p><strong>Phone:</strong> {{ $order->delivery->phone ?? '-' }}</p>
            <p><strong>Assigned:</strong> {{ $order->delivery_accepted_at?->format('Y-m-d H:i') ?? '-' }}</p>
        </div>
    </div>

    <div class="rounded border p-4">
        <h6 class="mb-3 font-semibold">Order Content</h6>
        <div class="table-responsive">
            <table class="w-full">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Item ID</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Discount</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->orderItems as $orderItem)
                        <tr>
                            <td>{{ $orderItem->item_name }}</td>
                            <td>{{ $orderItem->item_id }}</td>
                            <td>{{ $orderItem->quantity }}</td>
                            <td>{{ number_format((float) $orderItem->unit_price, 2) }}</td>
                            <td>{{ number_format((float) $orderItem->discount_amount, 2) }}</td>
                            <td>{{ number_format((float) $orderItem->line_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">No items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded border p-4">
        <h6 class="mb-3 font-semibold">Status Timeline</h6>
        <div class="table-responsive">
            <table class="w-full">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->statusLogs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $log->from_status ? ucwords(str_replace('_', ' ', $log->from_status)) : '-' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $log->to_status)) }}</td>
                            <td>{{ $log->actor_type ?? '-' }}{{ $log->actor_id ? ' #' . $log->actor_id : '' }}</td>
                            <td>{{ $log->action ?? '-' }}</td>
                            <td>{{ $log->note ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">No status logs yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
