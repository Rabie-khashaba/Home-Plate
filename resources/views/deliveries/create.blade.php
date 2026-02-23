@extends('partial.master')
@section('title', 'Create Delivery')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Create Delivery</h5>
        <a href="{{ route('deliveries.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form method="POST" action="{{ route('deliveries.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @csrf

        <div>
            <label>First Name</label>
            <input type="text" name="first_name" class="form-input" required />
        </div>

        <div>
            <label>Email (Optional)</label>
            <input type="email" name="email" class="form-input" />
        </div>

        <div>
            <label>Phone</label>
            <input type="text" name="phone" class="form-input" required />
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

        <div>
            <label>Vehicle Type</label>
            <select name="vehicle_type" class="form-input" required>
                <option value="">Select Vehicle Type</option>
                <option value="car">Car</option>
                <option value="motorcycle">Motorcycle</option>
                <option value="bicycle">Bicycle</option>
                <option value="truck">Truck</option>
            </select>
        </div>

        <div class="md:col-span-2 border-t pt-4 font-semibold text-gray-600">Documents Upload</div>

        {{-- الصور الفردية --}}
        @foreach(['photo','drivers_license','national_id','vehicle_photo'] as $file)
            <div>
                <label>{{ ucwords(str_replace('_', ' ', $file)) }}</label>
                <input type="file" name="{{ $file }}" class="form-input" />
            </div>
        @endforeach

        {{-- رخصة المركبة (front/back) --}}
        <div>
            <label>Vehicle License (Front)</label>
            <input type="file" name="vehicle_license[front]" class="form-input" required />
        </div>
        <div>
            <label>Vehicle License (Back)</label>
            <input type="file" name="vehicle_license[back]" class="form-input" required />
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">Save</button>
        </div>
    </form>
</div>
@endsection
