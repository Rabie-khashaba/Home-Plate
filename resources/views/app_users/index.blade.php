@extends('partial.master')
@section('title', 'App Users')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">All App Users</h5>
        <a href="{{ route('app_users.create') }}" class="btn btn-primary">Add New</a>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $key => $user)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->phone }}</td>
                    <td>{{ $user->city->name_en }}</td>
                    <td>{{ $user->area->name_en }}</td>
                    <td class="flex gap-2">
                        <a href="{{ route('app_users.show', $user->id) }}" class="btn btn-secondary">Show</a>
                        <a href="{{ route('app_users.edit', $user->id) }}" class="btn btn-info">Edit</a>
                        <a href="{{ route('app_users.toggleActive', $user->id) }}" class="btn {{ $user->is_active ? 'btn-success' : 'btn-warning' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $users->links() }}</div>
    </div>
</div>
@endsection
