@extends('partial.master')
@section('title', 'Edit Delivery')

@section('content')
<div class="panel">
    <div class="flex items-center justify-between mb-5">
        <h5 class="font-semibold text-lg dark:text-white-light">Edit Delivery</h5>
        <a href="{{ route('deliveries.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form method="POST" action="{{ route('deliveries.update', $delivery->id) }}" enctype="multipart/form-data"
          class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @csrf
        @method('PUT')

        <div>
            <label>First Name</label>
            <input type="text" name="first_name" value="{{ old('first_name', $delivery->first_name) }}" class="form-input" required />
        </div>

        <div>
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $delivery->email) }}" class="form-input" />
        </div>

        <div>
            <label>Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $delivery->phone) }}" class="form-input" required />
        </div>

        <div>
            <label>City</label>
            <select name="city_id" class="form-input" required>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}" {{ $delivery->city_id == $city->id ? 'selected' : '' }}>
                        {{ $city->name_en }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Area</label>
            <select name="area_id" class="form-input" required>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" {{ $delivery->area_id == $area->id ? 'selected' : '' }}>
                        {{ $area->name_en }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Vehicle Type</label>
            <select name="vehicle_type" class="form-input" required>
                <option value="">Select Vehicle Type</option>
                <option value="car" {{ old('vehicle_type', $delivery->vehicle_type) == 'car' ? 'selected' : '' }}>Car</option>
                <option value="motorcycle" {{ old('vehicle_type', $delivery->vehicle_type) == 'motorcycle' ? 'selected' : '' }}>Motorcycle</option>
                <option value="bicycle" {{ old('vehicle_type', $delivery->vehicle_type) == 'bicycle' ? 'selected' : '' }}>Bicycle</option>
            </select>
        </div>


        <div>
            <label>Status</label>
            <select name="status" class="form-input">
                <option value="pending" {{ $delivery->status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ $delivery->status == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ $delivery->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <label>Active</label>
            <input type="checkbox" name="is_active" value="1" {{ $delivery->is_active ? 'checked' : '' }} />
        </div>

        <div class="md:col-span-2 border-t pt-4 font-semibold text-gray-600">Documents</div>

        @foreach(['photo','drivers_license','national_id','vehicle_photo'] as $file)
            <div>
                <label>{{ ucwords(str_replace('_', ' ', $file)) }}</label>
                <input type="file" name="{{ $file }}" class="form-input" />
                @if($delivery->$file)
                    <img src="{{ asset('storage/app/public/'.$delivery->$file) }}" class="w-20 h-20 mt-2 rounded-md object-cover">
                @endif
            </div>
        @endforeach



        <div class="md:col-span-2 border-t pt-4 font-semibold text-gray-600">Vehicle License</div>

        @foreach(['front','back'] as $key)
            <div>
                <label>Vehicle License {{ ucfirst($key) }}</label>
                <input type="file" name="vehicle_license[{{ $key }}]" class="form-input" />
                @if(!empty($delivery->vehicle_license[$key]))
                    <img src="{{ asset('storage/app/public/'.$delivery->vehicle_license[$key]) }}"
                        class="w-20 h-20 mt-2 rounded-md object-cover"
                        alt="Vehicle License {{ $key }}">
                @endif
            </div>
        @endforeach

        <div class="md:col-span-2 mt-5">
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div>
@endsection
