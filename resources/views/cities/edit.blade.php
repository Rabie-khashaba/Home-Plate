@extends('partial.master')
@section('title', 'Edit City')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Edit City</h5>
        <a href="{{ route('cities.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form class="grid grid-cols-1 md:grid-cols-2 gap-5" method="POST" action="{{ route('cities.update', $city->id) }}">
        @csrf
        @method('PUT')

        <div>
            <label for="country_id">Choose Country</label>
            <select id="country_id" name="country_id" class="form-select" required>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" {{ $city->country_id == $country->id ? 'selected' : '' }}>
                        {{ $country->name_en }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="name_en">Name (English)</label>
            <input id="name_en" name="name_en" value="{{ $city->name_en }}" type="text" class="form-input" required />
        </div>

        <div>
            <label for="name_ar">Name (Arabic)</label>
            <input id="name_ar" name="name_ar" value="{{ $city->name_ar }}" type="text" class="form-input" required />
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">
                Update
            </button>
        </div>
    </form>
</div>
@endsection
