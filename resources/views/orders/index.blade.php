@extends('partial.master')
@section('title', 'Orders')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Orders</h5>
        <a href="{{ route('orders.create') }}" class="btn btn-primary">Create Order</a>
    </div>

    <form method="GET" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
        <input type="text" name="order_number" value="{{ request('order_number') }}" class="form-input" placeholder="Search order number">
        <input type="text" name="status" value="{{ request('status') }}" class="form-input" placeholder="Search status">
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Client Phone</th>
                    <th>Order Number</th>
                    {{-- <th>Order Content</th> --}}
                    <th>Order Cost</th>
                    <th>Payment Status</th>
                   {{-- <th>Payment Method</th> --}}
                   {{-- <th>Address</th> --}}
                    <th>Order Date</th>
                    <th>Vendor</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->appUser->name ?? '-' }}</td>
                        <td>{{ $order->appUser->phone ?? '-' }}</td>
                        <td>{{ $order->order_number }}</td>
                        <!--<td>-->
                        <!--    @if($order->orderItems->count())-->
                        <!--        {{ $order->orderItems->take(2)->map(fn($i) => ($i->item_name ?? 'Item') . ' x' . $i->quantity)->implode(' | ') }}-->
                        <!--        @if($order->orderItems->count() > 2)-->
                        <!--            +{{ $order->orderItems->count() - 2 }} more-->
                        <!--        @endif-->
                        <!--    @else-->
                        <!--        --->
                        <!--    @endif-->
                        <!--</td>-->
                        <td>{{ number_format((float) $order->order_cost, 2) }}</td>
                        @php
                            $ps = $order->payment_status ?? 'unpaid';
                            $pc = match($ps) {
                                'payment_confirmed', 'paid' => '#22c55e',
                                'pending' => '#3b82f6',
                                'unpaid', 'failed' => '#ef4444',
                                'refunded' => '#f59e0b',
                                default => '#6b7280'
                            };
                        @endphp
                        <td>
                            <span style="background:{{ $pc }}20;color:{{ $pc }};border:1px solid {{ $pc }}40;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600">
                                {{ ucwords(str_replace('_', ' ', $ps)) }}
                            </span>
                        </td>
                        <!--<td>{{ $order->paymentMethodLabel() }}</td>-->
                        <!--<td>{{ \Illuminate\Support\Str::limit($order->delivery_address, 40) }}</td>-->
                        <td>{{ $order->ordered_at?->format('Y-m-d H:i') ?? $order->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $order->vendor->full_name ?? '-' }}</td>
                        <td>
                            <span class="px-3 py-1 rounded-full text-white bg-info">
                                {{ ucwords(str_replace('_', ' ', $order->status)) }}
                            </span>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">View Details</a>
                                <a href="{{ route('orders.edit', $order) }}" class="btn btn-primary">Edit</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-6">No orders found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</div>
@endsection
