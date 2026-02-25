@extends('partial.master')
@section('title', 'Roles')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">All Roles</h5>
        <a href="{{ route('roles.create') }}" class="btn btn-primary">Add New Role</a>
    </div>

    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Permissions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $key => $role)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $role->name }}</td>
                    <td>{{ $role->permissions->pluck('name')->join(', ') }}</td>
                    <td class="flex gap-2">
                        <a href="{{ route('roles.show', $role->id) }}" class="btn btn-success">Show</a>
                        <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-info">Edit</a>
                        <form action="{{ route('roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
