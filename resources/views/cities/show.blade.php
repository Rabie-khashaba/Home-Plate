@extends('partial.master')
@section('title', 'Show City')

@section('content')
<div class="panel max-w-3xl mx-auto">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">City Details</h5>
        <a href="{{ route('cities.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Country</label>
            <p class="form-input bg-gray-100 dark:bg-dark text-gray-800 dark:text-gray-100">
                {{ $city->country->name_en ?? '-' }}
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name (English)</label>
            <p class="form-input bg-gray-100 dark:bg-dark text-gray-800 dark:text-gray-100">
                {{ $city->name_en }}
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name (Arabic)</label>
            <p class="form-input bg-gray-100 dark:bg-dark text-gray-800 dark:text-gray-100 text-right">
                {{ $city->name_ar }}
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Created At</label>
            <p class="form-input bg-gray-100 dark:bg-dark text-gray-800 dark:text-gray-100">
                {{ $city->created_at->format('Y-m-d H:i') }}
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Updated At</label>
            <p class="form-input bg-gray-100 dark:bg-dark text-gray-800 dark:text-gray-100">
                {{ $city->updated_at->format('Y-m-d H:i') }}
            </p>
        </div>
    </div>

    <div class="mt-8 flex gap-3">
        <a href="{{ route('cities.edit', $city->id) }}" class="btn btn-primary">Edit</a>
        <form action="{{ route('cities.destroy', $city->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this city?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>
@endsection
