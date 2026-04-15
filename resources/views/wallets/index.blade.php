@extends('partial.master')
@section('title', 'Wallets & Earnings')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <div class="panel text-white" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Vendors Balance</p><h3 class="text-2xl font-bold">{{ number_format((float)$stats['total_vendor_balance'], 2) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Riders Balance</p><h3 class="text-2xl font-bold">{{ number_format((float)$stats['total_delivery_balance'], 2) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="8" r="4" stroke="white" stroke-width="1.5"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#10b981,#34d399)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Vendors Total Earned</p><h3 class="text-2xl font-bold">{{ number_format((float)$stats['total_vendor_earned'], 2) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><line x1="12" y1="1" x2="12" y2="23" stroke="white" stroke-width="1.5"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Riders Total Earned</p><h3 class="text-2xl font-bold">{{ number_format((float)$stats['total_delivery_earned'], 2) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><line x1="12" y1="1" x2="12" y2="23" stroke="white" stroke-width="1.5"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
</div>

{{-- Tab switcher --}}
<div class="flex gap-2 mb-4">
    <a href="{{ route('wallets.index', ['type' => 'all']) }}" class="btn btn-sm {{ $type === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
    <a href="{{ route('wallets.index', ['type' => 'vendor']) }}" class="btn btn-sm {{ $type === 'vendor' ? 'btn-primary' : 'btn-outline-primary' }}">Vendors</a>
    <a href="{{ route('wallets.index', ['type' => 'delivery']) }}" class="btn btn-sm {{ $type === 'delivery' ? 'btn-primary' : 'btn-outline-primary' }}">Riders</a>
</div>

@if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger mb-4">{{ session('error') }}</div>@endif

{{-- Vendors --}}
@if($type !== 'delivery')
<div class="panel mb-6">
    <h5 class="text-lg font-semibold dark:text-white-light mb-4">Vendor Wallets</h5>
    <div class="table-responsive">
        <table class="w-full">
            <thead><tr><th>#</th><th>Vendor</th><th>Balance</th><th>Total Earned</th><th>Withdrawn</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($vendorWallets as $w)
                <tr>
                    <td>{{ $w->owner_id }}</td>
                    <td class="font-medium">{{ $w->owner->full_name ?? $w->owner->restaurant_name ?? 'غير متاح' }}</td>
                    <td><span class="font-bold {{ $w->balance < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float)$w->balance, 2) }}</span></td>
                    <td>{{ number_format((float)$w->total_earned, 2) }}</td>
                    <td>{{ number_format((float)$w->total_withdrawn, 2) }}</td>
                    <td><a href="{{ route('wallets.show', ['type' => 'vendor', 'id' => $w->owner_id]) }}" class="btn btn-sm btn-outline-primary">Manage</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-gray-400 py-8">No vendor wallets yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($vendorWallets, 'links')) <div class="mt-4">{{ $vendorWallets->links() }}</div> @endif
    </div>
</div>
@endif

{{-- Riders --}}
@if($type !== 'vendor')
<div class="panel">
    <h5 class="text-lg font-semibold dark:text-white-light mb-4">Rider Wallets</h5>
    <div class="table-responsive">
        <table class="w-full">
            <thead><tr><th>#</th><th>Rider</th><th>Balance</th><th>Total Earned</th><th>Withdrawn</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($deliveryWallets as $w)
                <tr>
                    <td>{{ $w->owner_id }}</td>
                    <td class="font-medium">{{ $w->owner->first_name ?? $w->owner->full_name ?? 'غير متاح' }}</td>
                    <td><span class="font-bold {{ $w->balance < 0 ? 'text-danger' : 'text-success' }}">{{ number_format((float)$w->balance, 2) }}</span></td>
                    <td>{{ number_format((float)$w->total_earned, 2) }}</td>
                    <td>{{ number_format((float)$w->total_withdrawn, 2) }}</td>
                    <td><a href="{{ route('wallets.show', ['type' => 'delivery', 'id' => $w->owner_id]) }}" class="btn btn-sm btn-outline-primary">Manage</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-gray-400 py-8">No rider wallets yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($deliveryWallets, 'links')) <div class="mt-4">{{ $deliveryWallets->links() }}</div> @endif
    </div>
</div>
@endif
@endsection

