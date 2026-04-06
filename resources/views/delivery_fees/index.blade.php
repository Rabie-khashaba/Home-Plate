@extends('partial.master')
@section('title', 'Delivery Fees')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <div class="panel text-white" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Total Areas</p><h3 class="text-3xl font-bold">{{ number_format($stats['total']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z" stroke="white" stroke-width="1.5"/><circle cx="12" cy="9" r="2.5" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#22c55e,#4ade80)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Active</p><h3 class="text-3xl font-bold">{{ number_format($stats['active']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/><path d="M8 12l3 3 5-5" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Avg Fee</p><h3 class="text-2xl font-bold">{{ $stats['avg_fee'] > 0 ? number_format($stats['avg_fee'], 2) : '—' }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><line x1="12" y1="1" x2="12" y2="23" stroke="white" stroke-width="1.5"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#10b981,#34d399)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Free Delivery</p><h3 class="text-3xl font-bold">{{ number_format($stats['free']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z" stroke="white" stroke-width="1.5"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
</div>

<div class="panel">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Delivery Fees by Area</h5>
            <p class="text-sm text-gray-500 mt-1">Set delivery fee, minimum order, and estimated delivery time per area.</p>
        </div>
        {{-- City filter --}}
        <form method="GET" action="{{ route('delivery_fees.index') }}">
            <select name="city_id" class="form-select h-9 text-sm w-44" onchange="this.form.submit()">
                <option value="">All Cities</option>
                @foreach($cities as $city)
                <option value="{{ $city->id }}" {{ request('city_id') == $city->id ? 'selected' : '' }}>{{ $city->name_en }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>City</th>
                    <th>Area</th>
                    <th>Delivery Fee</th>
                    <th>Min Order</th>
                    <th>Est. Time (min)</th>
                    <th>Status</th>
                    <th>Save</th>
                </tr>
            </thead>
            <tbody>
                @forelse($areas as $area)
                <tr>
                    <td class="text-sm text-gray-500">{{ $area->city->name_en ?? '—' }}</td>
                    <td class="font-medium">{{ $area->name_en }}</td>
                    <form action="{{ route('delivery_fees.update', $area) }}" method="POST">
                        @csrf
                        <td>
                            <div class="flex items-center gap-1">
                                <input type="number" name="delivery_fee" value="{{ old('delivery_fee_'.$area->id, $area->delivery_fee) }}"
                                       class="form-input h-9 text-sm w-28" step="0.01" min="0" placeholder="0.00" />
                                <span class="text-xs text-gray-400">EGP</span>
                            </div>
                        </td>
                        <td>
                            <input type="number" name="min_order_amount" value="{{ old('min_order_amount_'.$area->id, $area->min_order_amount) }}"
                                   class="form-input h-9 text-sm w-28" step="0.01" min="0" placeholder="0.00" />
                        </td>
                        <td>
                            <input type="number" name="estimated_minutes" value="{{ old('estimated_minutes_'.$area->id, $area->estimated_minutes) }}"
                                   class="form-input h-9 text-sm w-24" min="1" placeholder="30" />
                        </td>
                        <td>
                            <label class="relative h-6 w-12 cursor-pointer inline-flex">
                                <input type="hidden" name="is_active" value="0" />
                                <input type="checkbox" name="is_active" value="1"
                                       class="custom_switch absolute w-full h-full opacity-0 z-10 cursor-pointer"
                                       {{ $area->is_active ? 'checked' : '' }} />
                                <span class="outline_checkbox border-2 border-[#ebedf2] dark:border-white-dark block h-full rounded-full before:absolute before:left-1 before:bg-[#ebedf2] dark:before:bg-white-dark before:bottom-1 before:w-4 before:h-4 before:rounded-full before:transition-all before:duration-300 peer-checked:before:left-7 peer-checked:border-primary peer-checked:before:bg-primary"></span>
                            </label>
                        </td>
                        <td>
                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                        </td>
                    </form>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-gray-400 py-10">No areas found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $areas->links() }}</div>
    </div>
</div>
@endsection
