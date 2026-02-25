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
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-input" value="{{ old('full_name') }}" required />
        </div>

        <div>
            <label>Phone Number</label>
            <input type="text" name="phone" class="form-input" value="{{ old('phone') }}" required />
        </div>

        <div>
            <label>Email</label>
            <input type="email" name="email" class="form-input" value="{{ old('email') }}" />
        </div>

        <div>
            <label>Password</label>
            <input type="password" name="password" class="form-input" required />
        </div>

        <div>
            <label>Restaurant Name</label>
            <input type="text" name="restaurant_name" class="form-input" value="{{ old('restaurant_name') }}" required />
        </div>

        <div>
            <label>Working Time</label>
            <input type="text" name="working_time" class="form-input" value="{{ old('working_time') }}" placeholder="10:00 AM - 12:00 AM" />
        </div>

        <div>
            <label>City</label>
            <select name="city_id" class="form-input" required>
                <option value="">Select City</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}" {{ old('city_id') == $city->id ? 'selected' : '' }}>{{ $city->name_en }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Area</label>
            <select name="area_id" class="form-input" required>
                <option value="">Select Area</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" {{ old('area_id') == $area->id ? 'selected' : '' }}>{{ $area->name_en }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label>Delivery Address</label>
            <textarea name="delivery_address" rows="3" class="form-input" required>{{ old('delivery_address') }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label>Restaurant Info</label>
            <textarea name="restaurant_info" rows="3" class="form-input">{{ old('restaurant_info') }}</textarea>
        </div>

        <div>
            <label>Location</label>
            <input type="text" name="location" class="form-input" value="{{ old('location') }}" />
        </div>

        <div>
            <label>Main Photo</label>
            <input type="file" name="main_photo" class="form-input" />
        </div>

        <div>
            <label>Upload ID Front</label>
            <input type="file" name="id_front" class="form-input" />
        </div>

        <div>
            <label>Upload ID Back</label>
            <input type="file" name="id_back" class="form-input" />
        </div>

        <div>
            <label>Kitchen Photo 1</label>
            <input type="file" name="kitchen_photo_1" class="form-input" />
        </div>

        <div>
            <label>Kitchen Photo 2</label>
            <input type="file" name="kitchen_photo_2" class="form-input" />
        </div>

        <div>
            <label>Kitchen Photo 3</label>
            <input type="file" name="kitchen_photo_3" class="form-input" />
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">Save</button>
        </div>
    </form>
</div>
@endsection
