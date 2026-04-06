@extends('partial.master')
@section('title', 'Reports & Analytics')

@section('content')

{{-- KPI Cards --}}
<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <div class="panel text-white" style="background:linear-gradient(135deg,#10b981,#34d399)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Total Revenue</p><h3 class="text-2xl font-bold">{{ number_format((float)$kpis['total_revenue'], 2) }}</h3></div>
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" style="opacity:.7"><line x1="12" y1="1" x2="12" y2="23" stroke="white" stroke-width="1.5"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Total Orders</p><h3 class="text-3xl font-bold">{{ number_format($kpis['total_orders']) }}</h3></div>
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="white" stroke-width="1.5"/><path d="M3 6h18" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#22c55e,#4ade80)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Delivered</p><h3 class="text-3xl font-bold">{{ number_format($kpis['delivered']) }}</h3></div>
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/><path d="M8 12l3 3 5-5" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#ef4444,#f87171)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Cancelled</p><h3 class="text-3xl font-bold">{{ number_format($kpis['cancelled']) }}</h3></div>
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/><path d="M15 9l-6 6M9 9l6 6" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <div class="panel">
        <p class="text-sm text-gray-500">Avg Order Value</p>
        <h3 class="text-2xl font-bold text-primary mt-1">{{ number_format((float)$kpis['avg_order_value'], 2) }} EGP</h3>
    </div>
    <div class="panel">
        <p class="text-sm text-gray-500">Total Users</p>
        <h3 class="text-2xl font-bold text-primary mt-1">{{ number_format($kpis['total_users']) }}</h3>
    </div>
    <div class="panel">
        <p class="text-sm text-gray-500">Active Vendors</p>
        <h3 class="text-2xl font-bold text-primary mt-1">{{ number_format($kpis['total_vendors']) }}</h3>
    </div>
    <div class="panel">
        <p class="text-sm text-gray-500">Active Riders</p>
        <h3 class="text-2xl font-bold text-primary mt-1">{{ number_format($kpis['total_riders']) }}</h3>
    </div>
</div>

{{-- Charts row --}}
<div class="grid grid-cols-1 gap-6 mb-6 xl:grid-cols-2">
    {{-- Revenue chart --}}
    <div class="panel">
        <h6 class="font-semibold mb-4 dark:text-white-light">Revenue & Orders — {{ $year }}</h6>
        <div id="revenueChart"></div>
    </div>

    {{-- Users / Vendors growth --}}
    <div class="panel">
        <h6 class="font-semibold mb-4 dark:text-white-light">New Users & Vendors — {{ $year }}</h6>
        <div id="growthChart"></div>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 mb-6 xl:grid-cols-2">
    {{-- Order status donut --}}
    <div class="panel">
        <h6 class="font-semibold mb-4 dark:text-white-light">Order Status Breakdown — {{ $year }}</h6>
        <div id="statusChart"></div>
    </div>

    {{-- Top Vendors --}}
    <div class="panel">
        <h6 class="font-semibold mb-4 dark:text-white-light">Top 10 Vendors by Orders</h6>
        <div id="vendorChart"></div>
    </div>
</div>

{{-- Top Items table --}}
<div class="panel">
    <h6 class="font-semibold mb-4 dark:text-white-light">Top 10 Most Ordered Items</h6>
    <div class="table-responsive">
        <table class="w-full">
            <thead><tr><th>#</th><th>Item</th><th>Vendor</th><th>Price</th><th>Times Ordered</th><th>Bar</th></tr></thead>
            <tbody>
                @php $maxOrders = $topItems->max('order_items_count') ?: 1; @endphp
                @forelse($topItems as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="font-medium">{{ $item->name }}</td>
                    <td class="text-sm text-gray-500">{{ $item->vendor->restaurant_name ?? '—' }}</td>
                    <td>{{ number_format((float)$item->price, 2) }}</td>
                    <td><span class="font-bold text-primary">{{ number_format($item->order_items_count) }}</span></td>
                    <td class="w-40">
                        <div class="h-2 bg-gray-100 dark:bg-[#1b2e4b] rounded-full">
                            <div class="h-full rounded-full" style="background:#3b82f6;width:{{ round(($item->order_items_count/$maxOrders)*100) }}%"></div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-gray-400 py-8">No order data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const months = @json($months);

    // Revenue + Orders
    new ApexCharts(document.getElementById('revenueChart'), {
        series: [
            { name: 'Revenue (EGP)', type: 'area', data: @json($revenueData) },
            { name: 'Orders', type: 'bar', data: @json($ordersData) }
        ],
        chart: { height: 280, toolbar: { show: false } },
        colors: ['#10b981', '#3b82f6'],
        xaxis: { categories: months },
        yaxis: [
            { title: { text: 'Revenue' }, labels: { formatter: v => v.toLocaleString() } },
            { opposite: true, title: { text: 'Orders' } }
        ],
        stroke: { curve: 'smooth', width: [2, 0] },
        fill: { type: ['gradient', 'solid'], gradient: { opacityFrom: .4, opacityTo: .05 } },
        tooltip: { shared: true },
        legend: { position: 'top' }
    }).render();

    // Growth
    new ApexCharts(document.getElementById('growthChart'), {
        series: [
            { name: 'New Users', data: @json($usersData) },
            { name: 'New Vendors', data: @json($vendorsData) }
        ],
        chart: { type: 'line', height: 280, toolbar: { show: false } },
        colors: ['#8b5cf6', '#f59e0b'],
        xaxis: { categories: months },
        stroke: { curve: 'smooth', width: 2 },
        markers: { size: 4 },
        legend: { position: 'top' }
    }).render();

    // Status donut
    @php
        $statusLabels = $statusBreakdown->keys()->map(fn($s) => ucwords(str_replace('_',' ',$s)))->values()->toArray();
        $statusValues = $statusBreakdown->values()->toArray();
    @endphp
    new ApexCharts(document.getElementById('statusChart'), {
        series: @json($statusValues),
        labels: @json($statusLabels),
        chart: { type: 'donut', height: 280 },
        colors: ['#f59e0b','#3b82f6','#8b5cf6','#22c55e','#ef4444','#10b981','#6b7280','#a855f7','#ec4899'],
        legend: { position: 'bottom' },
        plotOptions: { pie: { donut: { size: '65%' } } }
    }).render();

    // Top Vendors bar
    new ApexCharts(document.getElementById('vendorChart'), {
        series: [{ name: 'Orders', data: @json($topVendors->pluck('orders_count')) }],
        chart: { type: 'bar', height: 280, toolbar: { show: false } },
        colors: ['#3b82f6'],
        xaxis: { categories: @json($topVendors->map(fn($v) => mb_substr($v->restaurant_name ?? $v->full_name ?? 'Vendor', 0, 15))) },
        plotOptions: { bar: { borderRadius: 4, horizontal: false } },
        dataLabels: { enabled: false }
    }).render();
});
</script>
@endpush
@endsection
