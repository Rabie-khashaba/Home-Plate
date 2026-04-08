@extends('partial.master')
@section('title', 'Support Tickets')

@section('content')
<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <div class="panel text-white" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Total Tickets</p><h3 class="text-3xl font-bold">{{ number_format($stats['total']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Open</p><h3 class="text-3xl font-bold">{{ number_format($stats['open']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/><path d="M12 7v5l3 3" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">In Progress</p><h3 class="text-3xl font-bold">{{ number_format($stats['in_progress']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M12 6v6l4 2" stroke="white" stroke-width="1.5" stroke-linecap="round"/><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#22c55e,#4ade80)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Resolved</p><h3 class="text-3xl font-bold">{{ number_format($stats['resolved']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5"/><path d="M8 12l3 3 5-5" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
        </div>
    </div>
</div>

<div class="panel mb-4">
    <form method="GET" action="{{ route('support.index') }}">
        <div class="flex flex-wrap gap-2 items-center justify-between">
            <div class="flex gap-2 items-center flex-wrap">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search subject, message, sender..." class="form-input pl-10 h-9 text-sm w-72" />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                <select name="status" class="form-select h-9 text-sm w-40">
                    <option value="">All Statuses</option>
                    @foreach(['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="priority" class="form-select h-9 text-sm w-40">
                    <option value="">All Priorities</option>
                    @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('priority') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary h-9 px-4">Filter</button>
                @if(request()->hasAny(['search', 'status', 'priority']))
                    <a href="{{ route('support.index') }}" class="btn btn-outline-danger h-9 px-4">Clear</a>
                @endif
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Support Tickets</h5>
            @if($tickets->total() > 0)
                <p class="text-sm text-gray-500 mt-1">Showing {{ $tickets->firstItem() }}-{{ $tickets->lastItem() }} of <strong>{{ $tickets->total() }}</strong> tickets</p>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th>Sender</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Reply</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->id }}</td>
                        <td class="font-semibold">{{ $ticket->subject }}</td>
                        <td>
                            <div>{{ $ticket->senderName() }}</div>
                            <div class="text-xs text-gray-400">{{ $ticket->senderType() }}</div>
                        </td>
                        <td>
                            <span style="background:{{ $ticket->priorityColor() }}20;color:{{ $ticket->priorityColor() }};border:1px solid {{ $ticket->priorityColor() }}40;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </td>
                        <td>
                            <span style="background:{{ $ticket->statusColor() }}20;color:{{ $ticket->statusColor() }};border:1px solid {{ $ticket->statusColor() }}40;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600">
                                {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
                            </span>
                        </td>
                        <td>{{ $ticket->admin_reply ? 'Replied' : 'Pending' }}</td>
                        <td class="text-xs text-gray-500 whitespace-nowrap">{{ $ticket->created_at?->format('d M Y H:i') }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <a href="{{ route('support.show', $ticket->id) }}" class="btn btn-sm btn-outline-info">View</a>
                                <form action="{{ route('support.destroy', $ticket->id) }}" method="POST" onsubmit="return confirm('Delete this support ticket?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-gray-400 py-12">No support tickets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $tickets->links() }}</div>
    </div>
</div>
@endsection
