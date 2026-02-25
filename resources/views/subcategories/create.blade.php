@extends('partial.master')
@section('title', 'Create Subcategory')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Create Subcategory</h5>
        <a href="{{ route('subcategories.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form class="grid grid-cols-1 md:grid-cols-2 gap-5" method="POST" action="{{ route('subcategories.store') }}">
        @csrf
        <div>
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" class="form-input" required>
                <option value="">Select Category</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name_en }} / {{ $cat->name_ar }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="name_en">Name (English)</label>
            <input id="name_en" name="name_en" type="text" class="form-input" required />
        </div>

        <div>
            <label for="name_ar">Name (Arabic)</label>
            <input id="name_ar" name="name_ar" type="text" class="form-input" required />
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">Save</button>
        </div>
    </form>
</div>
@endsection
