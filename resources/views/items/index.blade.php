@extends('partial.master')
@section('title', 'Items')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">All Items</h5>
        <a href="{{ route('items.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Price</th>
                    <th>Vendor</th>
                    <th>Created</th>
                    <th>Approval</th>
                    <th>Availability</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $key => $item)
                <tr>
                    <td>{{ $items->firstItem() + $key }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->category->name_en ?? '-' }}</td>
                    <td>{{ $item->price }}</td>
                    <td>{{ $item->vendor->restaurant_name ?? '-' }}</td>
                    <td>{{ $item->created_at?->format('Y-m-d') }}</td>
                    <td>
                        <span class="px-3 py-1 rounded-full text-white
                            {{ $item->approval_status === 'approved' ? 'bg-success' : ($item->approval_status === 'pending' ? 'bg-warning' : 'bg-danger') }}">
                            {{ ucfirst($item->approval_status) }}
                        </span>
                    </td>
                    <td>
                        <span class="px-3 py-1 rounded-full text-white
                            {{ $item->availability_status === 'published' ? 'bg-success' : 'bg-danger' }}">
                            {{ ucfirst($item->availability_status) }}
                        </span>
                    </td>
                    <td class="flex flex-wrap gap-2">
                        <a href="{{ route('items.show', $item->id) }}" class="btn btn-secondary">Show</a>
                        <a href="{{ route('items.edit', $item->id) }}" class="btn btn-info">Edit</a>

                        {{-- <form action="{{ route('items.destroy', $item->id) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this item?')">Delete</button>
                        </form> --}}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $items->links() }}</div>
    </div>
</div>
@endsection
