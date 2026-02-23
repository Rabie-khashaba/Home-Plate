@extends('partial.master')
@section('title', 'Show Category')

@section('content')
<div class="panel max-w-3xl mx-auto">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Category Details</h5>
        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-300">Name (English)</label>
            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $category->name_en }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-300">Name (Arabic)</label>
            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $category->name_ar }}</p>
        </div>

        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-300">Photo</label>
            @if($category->photo)
                <img src="{{ asset('storage/'.$category->photo) }}" alt="Category Photo" class="mt-3 w-40 h-40 object-cover rounded shadow" />
            @else
                <p class="mt-3 text-gray-400">No photo uploaded.</p>
            @endif
        </div>
    </div>

    <div class="mt-8 flex justify-end gap-3">
        <a href="{{ route('categories.edit', $category->id) }}" class="btn btn-primary">Edit</a>
        <form action="{{ route('categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this category?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>
@endsection
