@extends('partial.master')
@section('title', 'Edit Category')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Edit Category</h5>
        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form class="grid grid-cols-1 md:grid-cols-2 gap-5" method="POST" action="{{ route('categories.update', $category->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div>
            <label for="name_en">Name (English)</label>
            <input id="name_en" name="name_en" value="{{ $category->name_en }}" type="text" class="form-input" required />
        </div>

        <div>
            <label for="name_ar">Name (Arabic)</label>
            <input id="name_ar" name="name_ar" value="{{ $category->name_ar }}" type="text" class="form-input" required />
        </div>

        <div class="md:col-span-2">
            <label for="photo">Photo</label>
            <input id="photo" name="photo" type="file"
                class="rtl:file-ml-5 form-input p-0 file:border-0 file:bg-primary/90 file:px-4 file:py-2 file:font-semibold file:text-white ltr:file:mr-5"
            />
            @if($category->photo)
                <div class="mt-2">
                    <img src="{{ asset('storage/'.$category->photo) }}" class="w-16 h-16 rounded" />
                </div>
            @endif
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">
                Update
            </button>
        </div>
    </form>
</div>
@endsection
