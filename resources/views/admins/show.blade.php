@extends('partial.master')
@section('title', 'Show Admin')

@section('content')
<div class="panel max-w-3xl mx-auto">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Admin Details</h5>
        <a href="{{ route('admins.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
            <p class="form-input bg-gray-100 dark:bg-dark">{{ $admin->name }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
            <p class="form-input bg-gray-100 dark:bg-dark">{{ $admin->phone }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
            <p class="form-input bg-gray-100 dark:bg-dark">{{ ucfirst($admin->type) }}</p>
        </div>
    </div>

    <div class="mt-8 flex gap-3">
        <a href="{{ route('admins.edit', $admin->id) }}" class="btn btn-primary">Edit</a>
        <form action="{{ route('admins.destroy', $admin->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>
@endsection
