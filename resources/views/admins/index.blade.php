@extends('partial.master')
@section('title', 'Admins')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">All Admins</h5>
        <a href="{{ route('admins.create') }}" class="btn btn-primary">Add New</a>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admins as $key => $admin)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $admin->name }}</td>
                    <td>{{ $admin->phone }}</td>
                    <td>{{ ucfirst($admin->type) }}</td>
                    <td class="flex gap-2">
                        <a href="{{ route('admins.show', $admin->id) }}" class="btn btn-success">Show</a>
                        <a href="{{ route('admins.edit', $admin->id) }}" class="btn btn-info">Edit</a>
                        <form action="{{ route('admins.destroy', $admin->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $admins->links() }}</div>
    </div>
</div>
@endsection
