@extends('partial.master')
@section('title', 'Vendors')

@section('content')

@php
    $activeStatus   = request('status');
    $noStatusFilter = !$activeStatus;
    $dateParams     = request()->only(['search', 'date_filter', 'from', 'to']);
@endphp

{{-- Date filter + Search --}}
<div class="panel mb-4" x-data="{ dateFilter: '{{ request('date_filter','') }}' }">
    <form x-ref="form" method="GET" action="{{ route('vendors.index') }}">
        @if($activeStatus) <input type="hidden" name="status" value="{{ $activeStatus }}"> @endif
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
            <div class="flex gap-2 items-center">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="form-input pl-10 h-9 text-sm w-48" />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                @if(request()->hasAny(['search','date_filter','status','from','to']))
                <a href="{{ route('vendors.index') }}" class="btn btn-outline-danger h-9 text-sm px-3">✕ Clear</a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <a href="{{ route('vendors.index', $dateParams) }}" class="panel text-white" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);cursor:pointer;text-decoration:none;{{ $noStatusFilter ? 'box-shadow:0 0 0 3px white,0 0 0 5px #3b82f6' : '' }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm" style="opacity:.8">Total Vendors</p>
                <h3 class="text-3xl font-bold">{{ number_format($stats['total']) }}</h3>
            </div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M9 22V12h6v10" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
    </a>
    <a href="{{ route('vendors.index', array_merge($dateParams, ['status' => 'approved'])) }}" class="panel text-white" style="background:linear-gradient(135deg,#22c55e,#4ade80);cursor:pointer;text-decoration:none;{{ $activeStatus==='approved' ? 'box-shadow:0 0 0 3px white,0 0 0 5px #22c55e' : '' }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm" style="opacity:.8">Approved</p>
                <h3 class="text-3xl font-bold">{{ number_format($stats['approved']) }}</h3>
            </div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7">
                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/>
                <path d="M8 12l3 3 5-5" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
    </a>
    <a href="{{ route('vendors.index', array_merge($dateParams, ['status' => 'pending'])) }}" class="panel text-white" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);cursor:pointer;text-decoration:none;{{ $activeStatus==='pending' ? 'box-shadow:0 0 0 3px white,0 0 0 5px #f59e0b' : '' }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm" style="opacity:.8">Pending</p>
                <h3 class="text-3xl font-bold">{{ number_format($stats['pending']) }}</h3>
            </div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7">
                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/>
                <path d="M12 6v6l4 2" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
    </a>
    <a href="{{ route('vendors.index', array_merge($dateParams, ['status' => 'rejected'])) }}" class="panel text-white" style="background:linear-gradient(135deg,#ef4444,#f87171);cursor:pointer;text-decoration:none;{{ $activeStatus==='rejected' ? 'box-shadow:0 0 0 3px white,0 0 0 5px #ef4444' : '' }}">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm" style="opacity:.8">Rejected</p>
                <h3 class="text-3xl font-bold">{{ number_format($stats['rejected']) }}</h3>
            </div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7">
                <circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/>
                <path d="M15 9l-6 6M9 9l6 6" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
    </a>
</div>

<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Vendors</h5>
        <a href="{{ route('vendors.create') }}" class="btn btn-primary gap-1">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Add New
        </a>
    </div>

    @if($vendors->total() > 0)
    <p class="text-sm text-gray-500 mb-3">
        Showing {{ $vendors->firstItem() }}–{{ $vendors->lastItem() }} of <strong>{{ $vendors->total() }}</strong> vendors
    </p>
    @endif

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Restaurant</th>
                    <th>Owner</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>Status</th>
                    <th>Active</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vendors as $vendor)
                <tr>
                    <td>{{ $vendor->id }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            @if($vendor->main_photo)
                                <img src="{{ asset('storage/app/public/'.$vendor->main_photo) }}" class="w-8 h-8 rounded object-cover" />
                            @else
                                <div class="w-8 h-8 rounded bg-primary/20 flex items-center justify-center text-xs font-bold text-primary">
                                    {{ strtoupper(substr($vendor->restaurant_name ?? 'V', 0, 1)) }}
                                </div>
                            @endif
                            <span class="font-medium">{{ $vendor->restaurant_name ?? '—' }}</span>
                        </div>
                    </td>
                    <td>{{ $vendor->full_name ?? '—' }}</td>
                    <td>{{ $vendor->phone }}</td>
                    <td>{{ $vendor->city?->name_en ?? '—' }}</td>
                    <td>
                        @php
                            $sc = match($vendor->status) { 'approved'=>'#22c55e','pending'=>'#f59e0b','rejected'=>'#ef4444',default=>'#6b7280' };
                        @endphp
                        <span style="background:{{ $sc }}20;color:{{ $sc }};border:1px solid {{ $sc }}40;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600">
                            {{ ucfirst($vendor->status) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $vendor->is_active ? 'badge-outline-success' : 'badge-outline-danger' }}">
                            {{ $vendor->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-500 whitespace-nowrap">{{ $vendor->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            <a href="{{ route('vendors.show', $vendor->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('vendors.edit', $vendor->id) }}" class="btn btn-sm btn-outline-info">Edit</a>
                            @if($vendor->status !== 'approved')
                            <form action="{{ route('vendors.approve', $vendor->id) }}" method="POST" class="inline">
                                @csrf <button type="submit" class="btn btn-sm btn-success">Approve</button>
                            </form>
                            @endif
                            @if($vendor->status !== 'rejected')
                            <form action="{{ route('vendors.reject', $vendor->id) }}" method="POST" class="inline">
                                @csrf <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                            @endif
                            @if($vendor->status === 'approved')
                            <form action="{{ route('vendors.toggleStatus', $vendor->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $vendor->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                    onclick="return confirm('Change active status?')">
                                    {{ $vendor->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-gray-400 py-12">
                        <svg class="mx-auto mb-2 text-gray-300" width="48" height="48" viewBox="0 0 24 24" fill="none">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                        No vendors found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $vendors->links() }}</div>
    </div>
</div>
@endsection
