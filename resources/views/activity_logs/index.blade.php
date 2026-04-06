@extends('partial.master')
@section('title', 'Activity Log')

@section('content')

{{-- Stats row --}}
<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <div class="panel text-white" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Total Events</p><h3 class="text-3xl font-bold">{{ number_format($stats['total']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="white" stroke-width="1.5"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#10b981,#34d399)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Today's Events</p><h3 class="text-3xl font-bold">{{ number_format($stats['today']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><rect x="3" y="4" width="18" height="18" rx="2" stroke="white" stroke-width="1.5"/><path d="M16 2v4M8 2v4M3 10h18" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
    {{-- Top action --}}
    @php $topAction = $stats['actions']->keys()->first(); @endphp
    <div class="panel text-white" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Most Common</p><h3 class="text-lg font-bold mt-1">{{ $topAction ? ucfirst($topAction) : '—' }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M18 20V10M12 20V4M6 20v-6" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
    {{-- Top model --}}
    @php $topModel = $stats['models']->keys()->first(); @endphp
    <div class="panel text-white" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Most Active</p><h3 class="text-lg font-bold mt-1">{{ $topModel ?? '—' }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/><path d="M12 8v4l3 3" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
</div>

<div class="panel">
    {{-- Header + filters --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Activity Log</h5>
            @if($logs->total() > 0)
            <p class="text-sm text-gray-500 mt-1">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of <strong>{{ $logs->total() }}</strong> events</p>
            @endif
        </div>
        <div class="flex gap-2 items-center flex-wrap">
            <form method="POST" action="{{ route('activity_logs.clear') }}" onsubmit="return confirm('Delete all logs older than 30 days?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">🗑 Clear Old Logs</button>
            </form>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="panel mb-4 bg-gray-50 dark:bg-[#1b2e4b]" x-data="{ dateFilter: '{{ request('date_filter','') }}' }">
        <form x-ref="form" method="GET" action="{{ route('activity_logs.index') }}">
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
                    <select name="action" class="form-select h-9 text-sm w-36" onchange="this.form.submit()">
                        <option value="">All Actions</option>
                        @foreach($actionTypes as $a)
                        <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                        @endforeach
                    </select>
                    <select name="model_type" class="form-select h-9 text-sm w-36" onchange="this.form.submit()">
                        <option value="">All Sections</option>
                        @foreach($modelTypes as $m)
                        <option value="{{ $m }}" {{ request('model_type') === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description..." class="form-input pl-10 h-9 text-sm w-56" />
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </div>
                    @if(request()->hasAny(['search','action','model_type','date_filter','from','to']))
                    <a href="{{ route('activity_logs.index') }}" class="btn btn-outline-danger h-9 text-sm px-3">✕ Clear</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-4">{{ session('success') }}</div>
    @endif

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Section</th>
                    <th>Admin</th>
                    <th>IP</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php $color = $log->actionColor(); @endphp
                <tr>
                    <td class="text-gray-400 text-xs">{{ $log->id }}</td>
                    <td>
                        <span style="background:{{ $color }}20;color:{{ $color }};border:1px solid {{ $color }}40;padding:3px 12px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap">
                            {{ $log->actionIcon() }} {{ ucfirst($log->action) }}
                        </span>
                    </td>
                    <td class="text-sm max-w-sm">{{ $log->description }}</td>
                    <td>
                        @if($log->model_type)
                        <span class="badge badge-outline-secondary text-xs">{{ $log->model_type }}</span>
                        @if($log->model_id)
                        <span class="text-xs text-gray-400 ml-1">#{{ $log->model_id }}</span>
                        @endif
                        @else
                        <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="text-sm">{{ $log->user->name ?? '<span class="text-gray-400">System</span>' }}</td>
                    <td class="text-xs text-gray-400 font-mono">{{ $log->ip_address ?? '—' }}</td>
                    <td class="text-xs text-gray-500 whitespace-nowrap">
                        {{ $log->created_at->format('d M Y') }}<br>
                        <span class="text-gray-400">{{ $log->created_at->format('H:i:s') }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-gray-400 py-16">
                        <svg class="mx-auto mb-3 text-gray-300" width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke="currentColor" stroke-width="1.5"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        No activity logs yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
