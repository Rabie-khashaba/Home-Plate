@extends('partial.master')
@section('title', 'Support Ticket')

@section('content')
<div class="panel max-w-5xl mx-auto">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">{{ $ticket->subject }}</h5>
            <p class="text-sm text-gray-500 mt-1">
                From {{ $ticket->senderName() }} ({{ $ticket->senderType() }}) on {{ $ticket->created_at?->format('d M Y h:i A') }}
            </p>
        </div>
        <a href="{{ route('support.index') }}" class="btn btn-outline-primary">Back</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="panel bg-gray-50 dark:bg-[#1b2e4b]">
            <p class="text-xs text-gray-400 mb-1">Priority</p>
            <span style="background:{{ $ticket->priorityColor() }}20;color:{{ $ticket->priorityColor() }};border:1px solid {{ $ticket->priorityColor() }}40;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600">
                {{ ucfirst($ticket->priority) }}
            </span>
        </div>
        <div class="panel bg-gray-50 dark:bg-[#1b2e4b]">
            <p class="text-xs text-gray-400 mb-1">Status</p>
            <span style="background:{{ $ticket->statusColor() }}20;color:{{ $ticket->statusColor() }};border:1px solid {{ $ticket->statusColor() }}40;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600">
                {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
            </span>
        </div>
        <div class="panel bg-gray-50 dark:bg-[#1b2e4b]">
            <p class="text-xs text-gray-400 mb-1">Replied At</p>
            <p class="font-medium">{{ $ticket->replied_at?->format('d M Y h:i A') ?? 'Not replied yet' }}</p>
        </div>
    </div>

    <div class="panel bg-gray-50 dark:bg-[#1b2e4b] mb-6">
        <h6 class="font-semibold mb-2">Customer Message</h6>
        <p class="text-sm leading-6 whitespace-pre-line">{{ $ticket->message }}</p>
    </div>

    @if($ticket->admin_reply)
        <div class="panel bg-success-light mb-6">
            <h6 class="font-semibold mb-2">Admin Reply</h6>
            <p class="text-sm leading-6 whitespace-pre-line">{{ $ticket->admin_reply }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('support.reply', $ticket->id) }}">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="md:col-span-2">
                <label class="form-label">Reply</label>
                <textarea name="admin_reply" rows="6" class="form-textarea @error('admin_reply') border-danger @enderror" required>{{ old('admin_reply', $ticket->admin_reply) }}</textarea>
                @error('admin_reply')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-select @error('status') border-danger @enderror">
                    @foreach(['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $ticket->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn btn-primary">Save Reply</button>
            <a href="{{ route('support.index') }}" class="btn btn-outline-danger">Cancel</a>
        </div>
    </form>
</div>
@endsection
