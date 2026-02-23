@extends('partial.master')
@section('title', 'Show Role')

@section('content')
<div class="panel max-w-3xl mx-auto">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Role Details</h5>
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <p class="form-input bg-gray-100 dark:bg-dark">{{ $role->name }}</p>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permissions</label>
            <p class="form-input bg-gray-100 dark:bg-dark">
                {{ $role->permissions->pluck('name')->join(', ') }}
            </p>
        </div>
    </div>

    <div class="mt-8 flex gap-3">
        <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-primary">Edit</a>
        <form action="{{ route('roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>
@endsection
