@extends('partial.master')
@section('title', 'Ratings')

@section('content')
<div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-4">
    <div class="panel text-white" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Total Ratings</p><h3 class="text-3xl font-bold">{{ number_format($stats['total']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M12 17.3 18.2 21l-1.7-7.1L22 9.2l-7.2-.6L12 2 9.2 8.6 2 9.2l5.5 4.7L5.8 21 12 17.3Z" stroke="white" stroke-width="1.5" stroke-linejoin="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#22c55e,#4ade80)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Vendor Avg</p><h3 class="text-3xl font-bold">{{ number_format($stats['vendor_avg'], 1) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M12 17.3 18.2 21l-1.7-7.1L22 9.2l-7.2-.6L12 2 9.2 8.6 2 9.2l5.5 4.7L5.8 21 12 17.3Z" stroke="white" stroke-width="1.5" stroke-linejoin="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">Delivery Avg</p><h3 class="text-3xl font-bold">{{ number_format($stats['delivery_avg'], 1) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M12 17.3 18.2 21l-1.7-7.1L22 9.2l-7.2-.6L12 2 9.2 8.6 2 9.2l5.5 4.7L5.8 21 12 17.3Z" stroke="white" stroke-width="1.5" stroke-linejoin="round"/></svg>
        </div>
    </div>
    <div class="panel text-white" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)">
        <div class="flex items-center justify-between">
            <div><p class="text-sm" style="opacity:.8">With Comment</p><h3 class="text-3xl font-bold">{{ number_format($stats['with_comment']) }}</h3></div>
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="opacity:.7"><path d="M8 10h8M8 14h5M6 20l-2 2V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
    </div>
</div>

<div class="panel mb-4">
    <form method="GET" action="{{ route('ratings.index') }}">
        <div class="flex flex-wrap gap-2 items-center justify-between">
            <div class="flex gap-2 items-center flex-wrap">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order, vendor, user, comment..." class="form-input pl-10 h-9 text-sm w-72" />
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" width="13" height="13" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="1.5"/><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </div>
                <select name="vendor_rating" class="form-select h-9 text-sm w-40">
                    <option value="">Vendor Rating</option>
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" @selected((string) request('vendor_rating') === (string) $i)>{{ $i }} Stars</option>
                    @endfor
                </select>
                <select name="delivery_rating" class="form-select h-9 text-sm w-40">
                    <option value="">Delivery Rating</option>
                    @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" @selected((string) request('delivery_rating') === (string) $i)>{{ $i }} Stars</option>
                    @endfor
                </select>
                <button type="submit" class="btn btn-primary h-9 px-4">Filter</button>
                @if(request()->hasAny(['search', 'vendor_rating', 'delivery_rating']))
                    <a href="{{ route('ratings.index') }}" class="btn btn-outline-danger h-9 px-4">Clear</a>
                @endif
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Ratings</h5>
            @if($ratings->total() > 0)
                <p class="text-sm text-gray-500 mt-1">Showing {{ $ratings->firstItem() }}-{{ $ratings->lastItem() }} of <strong>{{ $ratings->total() }}</strong> ratings</p>
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Order</th>
                    <th>User</th>
                    <th>Vendor</th>
                    <th>Vendor Rating</th>
                    <th>Delivery Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ratings as $rating)
                    <tr>
                        <td>{{ $rating->id }}</td>
                        <td>{{ $rating->order?->order_number ?? ('#' . $rating->order_id) }}</td>
                        <td>{{ $rating->appUser?->name ?? '—' }}</td>
                        <td>{{ $rating->vendor?->restaurant_name ?? '—' }}</td>
                        <td class="text-warning">{{ $rating->vendor_rating ? $rating->stars((int) $rating->vendor_rating) : '—' }}</td>
                        <td class="text-warning">{{ $rating->delivery_rating ? $rating->stars((int) $rating->delivery_rating) : '—' }}</td>
                        <td class="max-w-xs text-sm">{{ $rating->comment ?: '—' }}</td>
                        <td class="text-xs text-gray-500 whitespace-nowrap">{{ $rating->created_at?->format('d M Y H:i') }}</td>
                        <td>
                            <form action="{{ route('ratings.destroy', $rating->id) }}" method="POST" onsubmit="return confirm('Delete this rating?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-gray-400 py-12">No ratings found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $ratings->links() }}</div>
    </div>
</div>
@endsection
