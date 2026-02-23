@extends('partial.master')
@section('title', 'Create Vendor')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Create Vendor</h5>
        <a href="{{ route('vendors.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form method="POST" action="{{ route('vendors.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @csrf

        <div>
            <label>Name</label>
            <input type="text" name="name" class="form-input" required />
        </div>

        <div>
            <label>Phone</label>
            <input type="text" name="phone" class="form-input" required />
        </div>

        <div>
            <label>Email (Optional)</label>
            <input type="email" name="email" class="form-input" />
        </div>

        <div>
            <label>Password</label>
            <input type="password" name="password" class="form-input" required />
        </div>

        <div>
            <label>City</label>
            <select name="city_id" class="form-input" required>
                <option value="">Select City</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}">{{ $city->name_en }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Area</label>
            <select name="area_id" class="form-input" required>
                <option value="">Select Area</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}">{{ $area->name_en }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label>Address</label>
            <textarea name="address" rows="3" class="form-input"></textarea>
        </div>

        <div>
            <label>Location URL</label>
            <input type="url" name="location" class="form-input" />
        </div>

        <div>
            <label>Logo (Optional)</label>
            <input type="file" name="logo" class="form-input" />
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">Save</button>
        </div>
    </form>
</div>
@endsection
