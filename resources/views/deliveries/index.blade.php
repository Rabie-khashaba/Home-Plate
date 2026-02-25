@extends('partial.master')
@section('title', 'Deliveries')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">All Deliveries</h5>
        <a href="{{ route('deliveries.create') }}" class="btn btn-primary">Add New</a>
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
                    <th>Vehicle Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveries as $key => $delivery)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $delivery->first_name }}</td>
                    <td>{{ $delivery->phone }}</td>
                    <td>{{ $delivery->city->name_en ?? '-' }}</td>
                    <td>{{ $delivery->area->name_en ?? '-' }}</td>
                    <td>{{ $delivery->vehicle_type }}</td>
                    <td>
                        <span class="px-3 py-1 rounded-full text-white
                            {{ $delivery->status == 'approved' ? 'bg-success' : ($delivery->status == 'pending' ? 'bg-warning' : 'bg-danger') }}">
                            {{ ucfirst($delivery->status) }}
                        </span>
                    </td>
                    <td class="flex gap-2">
                        <a href="{{ route('deliveries.show', $delivery->id) }}" class="btn btn-secondary">Show</a>
                        <a href="{{ route('deliveries.edit', $delivery->id) }}" class="btn btn-info">Edit</a>

                        @if($delivery->status === 'pending')
                            <form action="{{ route('deliveries.approve', $delivery->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success">Accept</button>
                            </form>

                            <form action="{{ route('deliveries.reject', $delivery->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger">Reject</button>
                            </form>
                        @endif

                        <form action="{{ route('deliveries.toggleStatus', $delivery->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="btn {{ $delivery->is_active ? 'btn-success' : 'btn-danger' }}"
                                onclick="return confirm('هل أنت متأكد من تغيير الحالة؟')">
                                {{ $delivery->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $deliveries->links() }}</div>
    </div>
</div>
@endsection
