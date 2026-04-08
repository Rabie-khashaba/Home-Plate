@extends('partial.master')
@section('title', 'Coupons')

@section('content')
@php
    $activeStatus = request('status');
    $search = request('search');
@endphp

<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <a href="{{ route('coupons.index', array_filter(['search' => $search])) }}" class="panel text-white" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);cursor:pointer;text-decoration:none;{{ !$activeStatus ? 'box-shadow:0 0 0 3px white,0 0 0 5px #3b82f6' : '' }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm" style="opacity:.8">Total Coupons</p>
                <h3 class="text-3xl font-bold">{{ number_format($stats['total']) }}</h3>
            </div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7">
                <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6H20v4a2 2 0 1 0 0 4v4H6.5A2.5 2.5 0 0 1 4 15.5v-7Z" stroke="white" stroke-width="1.5"/>
                <path d="M9 6v12" stroke="white" stroke-width="1.5" stroke-dasharray="2 2"/>
            </svg>
        </div>
    </a>
    <a href="{{ route('coupons.index', array_filter(['search' => $search, 'status' => 'active'])) }}" class="panel text-white" style="background:linear-gradient(135deg,#22c55e,#4ade80);cursor:pointer;text-decoration:none;{{ $activeStatus === 'active' ? 'box-shadow:0 0 0 3px white,0 0 0 5px #22c55e' : '' }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm" style="opacity:.8">Active</p>
                <h3 class="text-3xl font-bold">{{ number_format($stats['active']) }}</h3>
            </div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7">
                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/>
                <path d="M8 12l2.5 2.5L16 9" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
    </a>
    <a href="{{ route('coupons.index', array_filter(['search' => $search, 'status' => 'inactive'])) }}" class="panel text-white" style="background:linear-gradient(135deg,#6b7280,#9ca3af);cursor:pointer;text-decoration:none;{{ $activeStatus === 'inactive' ? 'box-shadow:0 0 0 3px white,0 0 0 5px #6b7280' : '' }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm" style="opacity:.8">Inactive</p>
                <h3 class="text-3xl font-bold">{{ number_format($stats['inactive']) }}</h3>
            </div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7">
                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/>
                <path d="M8 8l8 8M16 8l-8 8" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
    </a>
    <a href="{{ route('coupons.index', array_filter(['search' => $search, 'status' => 'expired'])) }}" class="panel text-white" style="background:linear-gradient(135deg,#ef4444,#f87171);cursor:pointer;text-decoration:none;{{ $activeStatus === 'expired' ? 'box-shadow:0 0 0 3px white,0 0 0 5px #ef4444' : '' }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm" style="opacity:.8">Expired</p>
                <h3 class="text-3xl font-bold">{{ number_format($stats['expired']) }}</h3>
            </div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7">
                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/>
                <path d="M12 7v5l3 3" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
    </a>
</div>

<div class="panel mb-4">
    <form method="GET" action="{{ route('coupons.index') }}">
        <div class="flex flex-wrap gap-2 items-center justify-between">
            <div class="flex gap-2 items-center">
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by code..." class="form-input pl-10 h-9 text-sm w-64" />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <select name="status" class="form-select h-9 text-sm w-44">
                    <option value="">All Statuses</option>
                    <option value="active" @selected($activeStatus === 'active')>Active</option>
                    <option value="inactive" @selected($activeStatus === 'inactive')>Inactive</option>
                    <option value="expired" @selected($activeStatus === 'expired')>Expired</option>
                </select>
                <button type="submit" class="btn btn-primary h-9 px-4">Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('coupons.index') }}" class="btn btn-outline-danger h-9 px-4">Clear</a>
                @endif
            </div>
            <a href="{{ route('coupons.create') }}" class="btn btn-primary">
                Add Coupon
            </a>
        </div>
    </form>
</div>

<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Coupons</h5>
    </div>

    @if($coupons->total() > 0)
        <p class="text-sm text-gray-500 mb-3">
            Showing {{ $coupons->firstItem() }}-{{ $coupons->lastItem() }} of <strong>{{ $coupons->total() }}</strong> coupons
        </p>
    @endif

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Usage</th>
                    <th>Starts</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coupons as $coupon)
                    <tr>
                        <td>{{ $coupon->id }}</td>
                        <td>
                            <span class="font-semibold">{{ $coupon->code }}</span>
                        </td>
                        <td>{{ ucfirst($coupon->type) }}</td>
                        <td>
                            @if($coupon->type === 'percentage')
                                {{ rtrim(rtrim(number_format((float) $coupon->value, 2, '.', ''), '0'), '.') }}%
                            @else
                                {{ number_format((float) $coupon->value, 2) }}
                            @endif
                        </td>
                        <td>
                            {{ (int) $coupon->used_count }}
                            @if($coupon->usage_limit)
                                / {{ (int) $coupon->usage_limit }}
                            @else
                                / Unlimited
                            @endif
                        </td>
                        <td>{{ optional($coupon->starts_at)->format('d M Y h:i A') ?? '—' }}</td>
                        <td>{{ optional($coupon->expires_at)->format('d M Y h:i A') ?? '—' }}</td>
                        <td>
                            <span style="background:{{ $coupon->statusColor() }}20;color:{{ $coupon->statusColor() }};border:1px solid {{ $coupon->statusColor() }}40;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600">
                                {{ $coupon->statusLabel() }}
                            </span>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <a href="{{ route('coupons.edit', $coupon->id) }}" class="btn btn-sm btn-outline-info">Edit</a>
                                <form action="{{ route('coupons.toggle', $coupon->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $coupon->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                        {{ $coupon->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                <form action="{{ route('coupons.destroy', $coupon->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this coupon?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-gray-400 py-12">
                            No coupons found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $coupons->links() }}</div>
    </div>
</div>
@endsection
