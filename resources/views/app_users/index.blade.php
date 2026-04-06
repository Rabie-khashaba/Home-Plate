@extends('partial.master')
@section('title', 'App Users')

@section('content')

@php
    $dateParams     = request()->only(['search', 'date_filter', 'from', 'to']);
    $activeStatus   = request('status');
    $noStatusFilter = !$activeStatus;
@endphp

{{-- Date filter + Search (top, before stats) --}}
<div class="panel mb-4" x-data="{ dateFilter: '{{ request('date_filter','') }}' }">
    <form x-ref="form" method="GET" action="{{ route('app_users.index') }}">
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
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name, phone, email..." class="form-input pl-10 h-9 text-sm w-56" />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                @if(request()->hasAny(['search','date_filter','status','from','to']))
                <a href="{{ route('app_users.index') }}" class="btn btn-outline-danger h-9 text-sm px-3">✕ Clear</a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- Stats (clickable) --}}
<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <a href="{{ route('app_users.index', $dateParams) }}" class="panel text-white" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);cursor:pointer;text-decoration:none;{{ $noStatusFilter ? 'box-shadow:0 0 0 3px white,0 0 0 5px #3b82f6' : '' }}">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Total Users</p><h3 class="text-3xl font-bold">{{ number_format($stats['total']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="9" cy="7" r="4" stroke="white" stroke-width="1.5"/><path d="M2 21v-1a7 7 0 0 1 14 0v1" stroke="white" stroke-width="1.5" stroke-linecap="round"/><path d="M19 8v6M22 11h-6" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </a>
    <a href="{{ route('app_users.index', array_merge($dateParams, ['status' => 'active'])) }}" class="panel text-white" style="background:linear-gradient(135deg,#22c55e,#4ade80);cursor:pointer;text-decoration:none;{{ $activeStatus==='active' ? 'box-shadow:0 0 0 3px white,0 0 0 5px #22c55e' : '' }}">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Active</p><h3 class="text-3xl font-bold">{{ number_format($stats['active']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/><path d="M8 12l3 3 5-5" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </a>
    <a href="{{ route('app_users.index', array_merge($dateParams, ['status' => 'inactive'])) }}" class="panel text-white" style="background:linear-gradient(135deg,#ef4444,#f87171);cursor:pointer;text-decoration:none;{{ $activeStatus==='inactive' ? 'box-shadow:0 0 0 3px white,0 0 0 5px #ef4444' : '' }}">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Inactive</p><h3 class="text-3xl font-bold">{{ number_format($stats['inactive']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/><path d="M15 9l-6 6M9 9l6 6" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </a>
    <a href="{{ route('app_users.index', array_merge(request()->only(['search', 'status']), ['date_filter' => 'today'])) }}" class="panel text-white" style="background:linear-gradient(135deg,#a855f7,#c084fc);cursor:pointer;text-decoration:none;">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">New Today</p><h3 class="text-3xl font-bold">{{ number_format($stats['today']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><rect x="3" y="4" width="18" height="18" rx="2" stroke="white" stroke-width="1.5"/><path d="M16 2v4M8 2v4M3 10h18" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </a>
</div>

<div class="panel">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">App Users</h5>
            @if($users->total() > 0)
            <p class="text-sm text-gray-500 mt-1">Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of <strong>{{ $users->total() }}</strong> users</p>
            @endif
        </div>
        <a href="{{ route('app_users.create') }}" class="btn btn-primary gap-1">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Add New
        </a>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>City</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            @if($user->photo)
                                <img src="{{ asset('storage/app/public/'.$user->photo) }}" class="w-8 h-8 rounded-full object-cover" />
                            @else
                                <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-xs font-bold text-primary">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                            @endif
                            <span class="font-medium">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td>{{ $user->phone }}</td>
                    <td class="text-gray-500 text-sm">{{ $user->email ?? '—' }}</td>
                    <td>{{ $user->city?->name_en ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $user->is_active ? 'badge-outline-success' : 'badge-outline-danger' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-500 whitespace-nowrap">{{ $user->created_at->format('d M Y') }}</td>
                    <td>
                        <div class="flex gap-1">
                            <a href="{{ route('app_users.show', $user->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            <a href="{{ route('app_users.edit', $user->id) }}" class="btn btn-sm btn-outline-info">Edit</a>
                            <a href="{{ route('app_users.toggleActive', $user->id) }}" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-gray-400 py-10">No users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $users->links() }}</div>
    </div>
</div>
@endsection
