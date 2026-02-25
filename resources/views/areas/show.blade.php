@extends('partial.master')
@section('title', 'Show Area')

@section('content')
<div class="panel max-w-3xl mx-auto">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Area Details</h5>
        <a href="{{ route('areas.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">City</label>
            <p class="form-input bg-gray-100 dark:bg-dark">{{ $area->city->name_en ?? '-' }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name (English)</label>
            <p class="form-input bg-gray-100 dark:bg-dark">{{ $area->name_en }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name (Arabic)</label>
            <p class="form-input bg-gray-100 dark:bg-dark text-right">{{ $area->name_ar }}</p>
        </div>
    </div>

    <div class="mt-8 flex gap-3">
        <a href="{{ route('areas.edit', $area->id) }}" class="btn btn-primary">Edit</a>
        <form action="{{ route('areas.destroy', $area->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>
@endsection
