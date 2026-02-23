@extends('partial.master')
@section('title', 'Edit Country')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Edit Country</h5>
        <a href="{{ route('countries.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form class="grid grid-cols-1 md:grid-cols-2 gap-5" method="POST" action="{{ route('countries.update', $country->id) }}">
        @csrf
        @method('PUT')

        <div>
            <label for="name_en">Name (English)</label>
            <input id="name_en" name="name_en" value="{{ $country->name_en }}" type="text" class="form-input" required />
        </div>

        <div>
            <label for="name_ar">Name (Arabic)</label>
            <input id="name_ar" name="name_ar" value="{{ $country->name_ar }}" type="text" class="form-input" required />
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">
                Update
            </button>
        </div>
    </form>
</div>
@endsection
