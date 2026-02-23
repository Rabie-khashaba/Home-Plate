@extends('partial.master')
@section('title', 'Create App User')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Create App User</h5>
        <a href="{{ route('app_users.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form class="grid grid-cols-1 md:grid-cols-2 gap-5" method="POST" action="{{ route('app_users.store') }}" enctype="multipart/form-data">
        @csrf

        <div>
            <label for="name">Name</label>
            <input id="name" name="name" type="text" class="form-input" required />
        </div>

        <div>
            <label for="phone">Phone</label>
            <input id="phone" name="phone" type="text" class="form-input" required />
        </div>

        <div>
            <label for="email">Email (Optional)</label>
            <input id="email" name="email" type="email" class="form-input" />
        </div>

        <div>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" class="form-input" required />
        </div>

        <div>
            <label for="gender">Gender (Optional)</label>
            <select id="gender" name="gender" class="form-input">
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
        </div>

        <div>
            <label for="dob">Date of Birth (Optional)</label>
            <input id="dob" name="dob" type="date" class="form-input" />
        </div>

        <div>
            <label for="city_id">City</label>
            <select id="city_id" name="city_id" class="form-input" required>
                <option value="">Select City</option>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}">{{ $city->name_ar }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="area_id">Area</label>
            <select id="area_id" name="area_id" class="form-input" required>
                <option value="">Select Area</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}">{{ $area->name_ar }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label for="delivery_addresses">Delivery Addresses </label>
            <textarea id="delivery_addresses" name="delivery_addresses" class="form-input" rows="3" placeholder='Enter Address'></textarea>
        </div>

        <div>
            <label for="location">Location (URL)</label>
            <input id="location" name="location" type="url" class="form-input" />
        </div>


        <div class="md:col-span-2"> <label for="photo">Photo (Optional)</label>
            <input id="photo" name="photo" type="file" class="form-input" />
        </div>

        <div class="md:col-span-2">
            <label class="block mb-1">Status</label>
            <input type="checkbox" name="is_active" value="1" checked /> Active
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">Save</button>
        </div>
    </form>
</div>
@endsection
