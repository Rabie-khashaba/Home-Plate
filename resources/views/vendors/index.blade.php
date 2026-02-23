@extends('partial.master')
@section('title', 'Vendors')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">All Vendors</h5>
        <a href="{{ route('vendors.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Restaurant</th>
                    <th>Owner</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>Area</th>
                    <th>Status</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vendors as $key => $vendor)
                <tr>
                    <td>{{ $vendors->firstItem() + $key }}</td>
                    <td>{{ $vendor->restaurant_name ?? '-' }}</td>
                    <td>{{ $vendor->full_name ?? '-' }}</td>
                    <td>{{ $vendor->phone }}</td>
                    <td>{{ $vendor->city->name_en ?? '-' }}</td>
                    <td>{{ $vendor->area->name_en ?? '-' }}</td>
                    <td>
                        <span class="px-3 py-1 rounded-full text-white {{ $vendor->status == 'approved' ? 'bg-success' : ($vendor->status == 'pending' ? 'bg-warning' : 'bg-danger') }}">
                            {{ ucfirst($vendor->status) }}
                        </span>
                    </td>
                    <td>
                        <span class="px-2 py-1 rounded-full text-white {{ $vendor->is_active ? 'bg-success' : 'bg-danger' }}">
                            {{ $vendor->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="flex flex-wrap gap-2">
                        <a href="{{ route('vendors.show', $vendor->id) }}" class="btn btn-secondary">Show</a>
                        <a href="{{ route('vendors.edit', $vendor->id) }}" class="btn btn-info">Edit</a>

                        @if($vendor->status !== 'approved')
                            <form action="{{ route('vendors.approve', $vendor->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-success">Approve</button>
                            </form>
                        @endif

                        @if($vendor->status !== 'rejected')
                            <form action="{{ route('vendors.reject', $vendor->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-danger">Reject</button>
                            </form>
                        @endif

                        @if($vendor->status === 'approved')
                            <form action="{{ route('vendors.toggleStatus', $vendor->id) }}" method="POST" class="inline">
                                @csrf
                                <button
                                    type="submit"
                                    class="btn {{ $vendor->is_active ? 'btn-success' : 'btn-danger' }}"
                                    onclick="return confirm('Are you sure you want to change active status?')">
                                    {{ $vendor->is_active ? 'Set Inactive' : 'Set Active' }}
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $vendors->links() }}</div>
    </div>
</div>
@endsection
