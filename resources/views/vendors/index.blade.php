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
                    <th>Name</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>Area</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vendors as $key => $vendor)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $vendor->name }}</td>
                    <td>{{ $vendor->phone }}</td>
                    <td>{{ $vendor->city->name_en ?? '-' }}</td>
                    <td>{{ $vendor->area->name_en ?? '-' }}</td>
                    <td>
                        <span class="px-3 py-1 rounded-full text-white
                            {{ $vendor->status == 'approved' ? 'bg-success' : ($vendor->status == 'pending' ? 'bg-warning' : 'bg-danger') }}">
                            {{ ucfirst($vendor->status) }}
                        </span>
                    </td>
                    <td class="flex gap-2">
                        <a href="{{ route('vendors.show', $vendor->id) }}" class="btn btn-secondary">Show</a>
                        <a href="{{ route('vendors.edit', $vendor->id) }}" class="btn btn-info">Edit</a>

                        @if($vendor->status === 'pending')
                            <form action="{{ route('vendors.approve', $vendor->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-success">Accept</button>
                            </form>

                            <form action="{{ route('vendors.reject', $vendor->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-danger">Reject</button>
                            </form>
                        @endif

                        <form action="{{ route('vendors.toggleStatus', $vendor->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                class="btn {{ $vendor->is_active ? 'btn-success' : 'btn-danger' }}"
                                onclick="return confirm('هل أنت متأكد من تغيير الحالة؟')">
                                {{ $vendor->is_active ? 'Active ' : 'Disactive ' }}
                            </button>
                        </form>

                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $vendors->links() }}</div>
    </div>
</div>
@endsection
