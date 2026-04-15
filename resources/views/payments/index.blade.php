@extends('partial.master')
@section('title', 'Payments')

@section('content')
<div class="panel mb-4" x-data="{ dateFilter: '{{ request('date_filter','') }}' }">
    <form x-ref="form" method="GET" action="{{ route('payments.index') }}">
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
                <select name="provider" class="form-select h-9 text-sm w-40" onchange="this.form.submit()">
                    <option value="">All Providers</option>
                    @foreach($providers as $p)
                        <option value="{{ $p }}" {{ request('provider') === $p ? 'selected' : '' }}>{{ $p }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select h-9 text-sm w-40" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Order #, client, ref..." class="form-input pl-10 h-9 text-sm w-52" />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                <button type="submit" class="btn btn-primary h-9 text-sm px-4">Search</button>
                @if(request()->hasAny(['search','date_filter','from','to','provider','status']))
                    <a href="{{ route('payments.index') }}" class="btn btn-outline-danger h-9 text-sm px-3">✕ Clear</a>
                @endif
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Payments</h5>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Order</th>
                    <th>Client</th>
                    <th>Vendor</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Currency</th>
                    <th>Ref</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td>{{ $payment->id }}</td>
                    <td>
                        @if($payment->order)
                            <a href="{{ route('orders.show', $payment->order) }}" class="text-primary font-medium hover:underline">
                                {{ $payment->order->order_number }}
                            </a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-sm">{{ $payment->order->appUser->name ?? '—' }}</td>
                    <td class="text-sm">{{ $payment->order->vendor->full_name ?? $payment->order->vendor->restaurant_name ?? '—' }}</td>
                    <td><span class="badge badge-outline-info text-xs">{{ $payment->provider }}</span></td>
                    <td>{{ $payment->status }}</td>
                    <td>{{ number_format((float) $payment->amount, 2) }}</td>
                    <td>{{ $payment->currency }}</td>
                    <td class="text-xs text-gray-400 font-mono">{{ $payment->reference ?? $payment->provider_transaction_id ?? '—' }}</td>
                    <td class="text-xs text-gray-500 whitespace-nowrap">{{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-gray-400 py-10">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
