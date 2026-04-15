@extends('partial.master')
@section('title', 'Transactions')

@section('content')

@php
    $dateParams = request()->only(['search', 'date_filter', 'from', 'to', 'payment_method', 'payment_status']);
@endphp

{{-- Date filter --}}
<div class="panel mb-4" x-data="{ dateFilter: '{{ request('date_filter','') }}' }">
    <form x-ref="form" method="GET" action="{{ route('transactions.index') }}">
        @foreach(request()->except('date_filter') as $k => $v)
            @if($v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endif
        @endforeach
        <input type="hidden" name="date_filter" :value="dateFilter" />
        <div class="flex flex-wrap gap-2 items-center justify-between">
            <div class="flex flex-wrap gap-2 items-center">
                @foreach(['' => 'All Time', 'today' => 'Today', 'yesterday' => 'Yesterday', 'last_week' => 'Last Week', 'last_month' => 'Last Month', 'custom' => 'Custom'] as $val => $label)
                <button type="button"
                    @click="dateFilter='{{ $val }}'; @if($val !== 'custom') $nextTick(() => $refs.form.submit()) @endif"
                    :class="dateFilter==='{{ $val }}' ? 'btn-primary' : 'btn-outline-primary'"
                    class="btn btn-sm h-9 text-xs">{{ $label }}</button>
                @endforeach
                <div class="flex gap-2 items-center" x-show="dateFilter==='custom'" x-cloak>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-input h-9 text-sm w-36" />
                    <span class="text-gray-400 text-xs">→</span>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-input h-9 text-sm w-36" />
                    <button type="submit" class="btn btn-primary h-9 text-sm px-4">Go</button>
                </div>
            </div>
            <div class="flex gap-2 items-center flex-wrap">
                <select name="payment_method" class="form-select h-9 text-sm w-40" onchange="this.form.submit()">
                    <option value="">All Methods</option>
                    @foreach($paymentMethods as $m)
                    <option value="{{ $m }}" {{ request('payment_method') === $m ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $m)) }}</option>
                    @endforeach
                </select>
                <select name="payment_status" class="form-select h-9 text-sm w-36" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="payment_confirmed" {{ request('payment_status') === 'payment_confirmed' ? 'selected' : '' }}>Payment Confirmed</option>
                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Order #, client, ref..." class="form-input pl-10 h-9 text-sm w-52" />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                @if(request()->hasAny(['search','date_filter','from','to','payment_method','payment_status']))
                <a href="{{ route('transactions.index') }}" class="btn btn-outline-danger h-9 text-sm px-3">✕ Clear</a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <div class="panel text-white" style="background:linear-gradient(135deg,#10b981,#34d399)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Total Revenue</p><h3 class="text-2xl font-bold">{{ number_format((float)$stats['total_revenue'], 2) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><line x1="12" y1="1" x2="12" y2="23" stroke="white" stroke-width="1.5"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Delivery Fees</p><h3 class="text-2xl font-bold">{{ number_format((float)$stats['total_fees'], 2) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h11a2 2 0 012 2v3m-4 12H9a2 2 0 01-2-2v-7a2 2 0 012-2h9a2 2 0 012 2v7a2 2 0 01-2 2z" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Total Orders</p><h3 class="text-3xl font-bold">{{ number_format($stats['count']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="white" stroke-width="1.5"/><path d="M3 6h18M16 10a4 4 0 01-8 0" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#22c55e,#4ade80)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Paid</p><h3 class="text-3xl font-bold">{{ number_format($stats['paid']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/><path d="M8 12l3 3 5-5" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
</div>

<div class="panel">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Transactions</h5>
            @if($transactions->total() > 0)
            <p class="text-sm text-gray-500 mt-1">Showing {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} of <strong>{{ $transactions->total() }}</strong></p>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Client</th>
                    <th>Vendor</th>
                    <th>Order Cost</th>
                    <th>Delivery Fee</th>
                    <th>Total</th>
                    <th>Method</th>
                    <th>Payment Status</th>
                    <th>Reference</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                @php
                    $ps = $tx->payment_status ?? 'unknown';
                    $pc = match($ps) { 'paid' => '#22c55e', 'payment_confirmed' => '#22c55e', 'refunded' => '#f59e0b', 'unpaid' => '#ef4444', 'pending' => '#3b82f6', default => '#6b7280' };
                @endphp
                <tr>
                    <td><a href="{{ route('orders.show', $tx) }}" class="text-primary font-medium hover:underline">{{ $tx->order_number }}</a></td>
                    <td class="text-sm">{{ $tx->appUser->name ?? '—' }}</td>
                    <td class="text-sm">{{ $tx->vendor->full_name ?? $tx->vendor->restaurant_name ?? '—' }}</td>
                    <td>{{ number_format((float)$tx->order_cost, 2) }}</td>
                    <td>{{ number_format((float)$tx->delivery_fee, 2) }}</td>
                    <td class="font-semibold">{{ number_format((float)$tx->total_amount, 2) }}</td>
                    <td><span class="badge badge-outline-info text-xs">{{ ucwords(str_replace('_',' ',$tx->payment_method ?? '—')) }}</span></td>
                    <td><span style="background:{{ $pc }}20;color:{{ $pc }};border:1px solid {{ $pc }}40;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600">{{ ucfirst($ps) }}</span></td>
                    <td class="text-xs text-gray-400 font-mono">{{ $tx->payment_reference ?? '—' }}</td>
                    <td class="text-xs text-gray-500 whitespace-nowrap">{{ ($tx->ordered_at ?? $tx->created_at)?->format('d M Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-gray-400 py-10">No transactions found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $transactions->links() }}</div>
    </div>
</div>

<div class="panel mt-6">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Wallet Transactions (Credit / Debit)</h5>
            @if($walletTransactions->total() > 0)
            <p class="text-sm text-gray-500 mt-1">Showing {{ $walletTransactions->firstItem() }}–{{ $walletTransactions->lastItem() }} of <strong>{{ $walletTransactions->total() }}</strong></p>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Owner</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Balance After</th>
                    <th>Order</th>
                    <th>Description</th>
                    <th>By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($walletTransactions as $wtx)
                @php
                    $owner = $wtx->wallet?->owner;
                    $ownerName = $owner?->full_name
                        ?? $owner?->restaurant_name
                        ?? $owner?->first_name
                        ?? $owner?->name
                        ?? '—';
                    $tc = $wtx->type === 'credit' ? '#22c55e' : '#ef4444';
                @endphp
                <tr>
                    <td>{{ $wtx->id }}</td>
                    <td class="text-sm">{{ $ownerName }}</td>
                    <td><span style="background:{{ $tc }}20;color:{{ $tc }};border:1px solid {{ $tc }}40;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600">{{ ucfirst($wtx->type) }}</span></td>
                    <td>{{ number_format((float) $wtx->amount, 2) }}</td>
                    <td>{{ number_format((float) $wtx->balance_after, 2) }}</td>
                    <td>
                        @if($wtx->order)
                            <a href="{{ route('orders.show', $wtx->order) }}" class="text-primary font-medium hover:underline">{{ $wtx->order->order_number }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-sm">{{ $wtx->description }}</td>
                    <td class="text-sm">{{ $wtx->createdBy->name ?? '—' }}</td>
                    <td class="text-xs text-gray-500 whitespace-nowrap">{{ $wtx->created_at?->format('d M Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-gray-400 py-10">No wallet transactions found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $walletTransactions->links() }}</div>
    </div>
</div>

<div class="panel mt-6">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Payments (Paymob)</h5>
            @if($payments->total() > 0)
            <p class="text-sm text-gray-500 mt-1">Showing {{ $payments->firstItem() }}–{{ $payments->lastItem() }} of <strong>{{ $payments->total() }}</strong></p>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Ref</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $pay)
                <tr>
                    <td>{{ $pay->id }}</td>
                    <td>
                        @if($pay->order)
                            <a href="{{ route('orders.show', $pay->order) }}" class="text-primary font-medium hover:underline">{{ $pay->order->order_number }}</a>
                        @else
                            —
                        @endif
                    </td>
                    <td><span class="badge badge-outline-info text-xs">{{ $pay->provider }}</span></td>
                    <td>{{ $pay->status }}</td>
                    <td>{{ number_format((float) $pay->amount, 2) }} {{ $pay->currency }}</td>
                    <td class="text-xs text-gray-400 font-mono">{{ $pay->reference ?? $pay->provider_transaction_id ?? '—' }}</td>
                    <td class="text-xs text-gray-500 whitespace-nowrap">{{ ($pay->paid_at ?? $pay->created_at)?->format('d M Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-gray-400 py-10">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
