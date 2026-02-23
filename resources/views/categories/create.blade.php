@extends('partial.master')
@section('title', 'Create Category')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Create Category</h5>
        <a href="{{ route('categories.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form class="grid grid-cols-1 md:grid-cols-2 gap-5" method="POST" action="{{ route('categories.store') }}" enctype="multipart/form-data">
        @csrf
        <div>
            <label for="name_en">Name (English)</label>
            <input id="name_en" name="name_en" type="text" class="form-input" required />
        </div>

        <div>
            <label for="name_ar">Name (Arabic)</label>
            <input id="name_ar" name="name_ar" type="text" class="form-input" required />
        </div>

        <div class="md:col-span-2">
            <label for="photo">Photo</label>
            <input id="photo" name="photo" type="file"
                class="rtl:file-ml-5 form-input p-0 file:border-0 file:bg-primary/90 file:px-4 file:py-2 file:font-semibold file:text-white ltr:file:mr-5"
            />
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">
                Save
            </button>
        </div>
    </form>
</div>
@endsection
