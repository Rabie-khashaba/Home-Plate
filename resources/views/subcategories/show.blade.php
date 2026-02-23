@extends('partial.master')
@section('title', 'Show Subcategory')

@section('content')
<div class="panel max-w-3xl mx-auto">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Subcategory Details</h5>
        <a href="{{ route('subcategories.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-300">Category</label>
            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $subcategory->category->name_en }} / {{ $subcategory->category->name_ar }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-300">Name (English)</label>
            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $subcategory->name_en }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-600 dark:text-gray-300">Name (Arabic)</label>
            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $subcategory->name_ar }}</p>
        </div>
    </div>

    <div class="mt-8 flex justify-end gap-3">
        <a href="{{ route('subcategories.edit', $subcategory->id) }}" class="btn btn-primary">Edit</a>
        <form action="{{ route('subcategories.destroy', $subcategory->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this subcategory?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>
@endsection
