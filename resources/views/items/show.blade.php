@extends('partial.master')
@section('title', 'Item Details')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Item Details</h5>
        <div class="flex items-center gap-2">
            @if($item->approval_status === 'pending')
                <form action="{{ route('items.approve', $item->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-success">Approve</button>
                </form>
                <form action="{{ route('items.reject', $item->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-danger">Reject</button>
                </form>
            @elseif($item->approval_status === 'approved')
                <form action="{{ route('items.reject', $item->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-danger">Reject</button>
                </form>
            @elseif($item->approval_status === 'rejected')
                <form action="{{ route('items.approve', $item->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-success">Approve</button>
                </form>
            @endif
            <a href="{{ route('items.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><strong>Name:</strong> {{ $item->name }}</div>
        <div><strong>Type:</strong> {{ $item->category->name_en ?? '-' }}</div>
        <div><strong>Vendor:</strong> {{ $item->vendor->restaurant_name ?? '-' }}</div>
        <div><strong>Price:</strong> {{ $item->price }}</div>
        <div><strong>Discount:</strong> {{ $item->discount ?? '-' }}</div>
        <div><strong>Prep Time:</strong> {{ $item->prep_time_value }} {{ $item->prep_time_unit }}</div>
        <div><strong>Stock:</strong> {{ $item->stock }}</div>
        <div><strong>Max Orders/Day:</strong> {{ $item->max_orders_per_day ?? '-' }}</div>
        <div><strong>Approval:</strong> {{ ucfirst($item->approval_status) }}</div>
        <div><strong>Availability:</strong> {{ ucfirst($item->availability_status) }}</div>
        <div class="md:col-span-2"><strong>Description:</strong> {{ $item->description ?? '-' }}</div>
    </div>

    <div class="mt-6">
        <h6 class="font-semibold mb-2">Photos</h6>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach(($item->photos ?? []) as $photo)
                <div class="border rounded p-2">
                    <img src="{{ asset('storage/' . ltrim($photo, '/')) }}" alt="Item Photo" class="w-full h-32 object-cover" />
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
