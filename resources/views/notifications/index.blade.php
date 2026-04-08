@extends('partial.master')
@section('title', 'Notifications')

@section('content')
@php
    $dateFilter = request('date_filter', '');
@endphp

<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <div class="panel text-white" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Total Notifications</p><h3 class="text-3xl font-bold">{{ number_format($stats['total']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#22c55e,#4ade80)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Sent</p><h3 class="text-3xl font-bold">{{ number_format($stats['sent']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/><path d="M8 12l3 3 5-5" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Pending</p><h3 class="text-3xl font-bold">{{ number_format($stats['pending']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/><path d="M12 7v5l3 3" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Active Recurring</p><h3 class="text-3xl font-bold">{{ number_format($stats['active']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M21 12a9 9 0 1 1-2.64-6.36" stroke="white" stroke-width="1.5" stroke-linecap="round"/><path d="M21 3v6h-6" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>
</div>

<div class="panel mb-4" x-data="{ dateFilter: '{{ $dateFilter }}' }">
    <form x-ref="form" method="GET" action="{{ route('notifications.index') }}">
        @foreach(request()->except('date_filter') as $k => $v)
            @if($v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endif
        @endforeach
        <input type="hidden" name="date_filter" :value="dateFilter" />

        <div class="flex flex-wrap gap-2 items-center justify-between">
            <div class="flex flex-wrap gap-2 items-center">
                @foreach(['' => 'All Time', 'today' => 'Today', 'yesterday' => 'Yesterday', 'last_week' => 'Last Week', 'last_month' => 'Last Month', 'custom' => 'Custom'] as $value => $label)
                    <button type="button"
                        @click="dateFilter='{{ $value }}'; @if($value !== 'custom') $nextTick(() => $refs.form.submit()) @endif"
                        :class="dateFilter==='{{ $value }}' ? 'btn-primary' : 'btn-outline-primary'"
                        class="btn btn-sm h-9 text-xs">{{ $label }}</button>
                @endforeach
                <div class="flex gap-2 items-center" x-show="dateFilter==='custom'" x-cloak>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-input h-9 text-sm w-36" />
                    <span class="text-gray-400 text-xs">to</span>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-input h-9 text-sm w-36" />
                    <button type="submit" class="btn btn-primary h-9 text-sm px-4">Go</button>
                </div>
            </div>
            <div class="flex gap-2 items-center flex-wrap">
                <select name="status" class="form-select h-9 text-sm w-36" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(['pending' => 'Pending', 'active' => 'Active', 'sent' => 'Sent', 'failed' => 'Failed'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="type" class="form-select h-9 text-sm w-44" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    @foreach(['immediate' => 'Immediate', 'scheduled' => 'Scheduled', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly_day' => 'Monthly Day', 'monthly_date' => 'Monthly Date'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or body..." class="form-input pl-10 h-9 text-sm w-60" />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                @if(request()->hasAny(['search', 'status', 'type', 'date_filter', 'from', 'to']))
                    <a href="{{ route('notifications.index') }}" class="btn btn-outline-danger h-9 px-3">Clear</a>
                @endif
                <a href="{{ route('notifications.create') }}" class="btn btn-primary h-9 px-4">Create</a>
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Notifications</h5>
            @if($notifications->total() > 0)
                <p class="text-sm text-gray-500 mt-1">Showing {{ $notifications->firstItem() }}-{{ $notifications->lastItem() }} of <strong>{{ $notifications->total() }}</strong></p>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Audience</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Scheduled</th>
                    <th>Sent At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $notification)
                    <tr>
                        <td>{{ $notification->id }}</td>
                        <td>
                            <div class="font-semibold">{{ $notification->title }}</div>
                            <div class="text-xs text-gray-400 max-w-xs truncate">{{ $notification->body }}</div>
                        </td>
                        <td>{{ $notification->targetLabel() }}</td>
                        <td>{{ $notification->typeLabel() }}</td>
                        <td>
                            <span class="badge {{ $notification->statusBadgeClass() }}">
                                {{ ucfirst($notification->status) }}
                            </span>
                        </td>
                        <td class="text-xs text-gray-500 whitespace-nowrap">{{ $notification->scheduled_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-xs text-gray-500 whitespace-nowrap">{{ $notification->sent_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @if(in_array($notification->status, ['pending', 'active', 'failed'], true))
                                    <form action="{{ route('notifications.send-now', $notification->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">Send Now</button>
                                    </form>
                                @endif
                                <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" onsubmit="return confirm('Delete this notification?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-gray-400 py-12">No notifications found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $notifications->links() }}</div>
    </div>
</div>
@endsection
