@extends('partial.master')
@section('title', 'Edit Vendor')

@section('content')
<div class="panel">
    <div class="flex items-center justify-between mb-5">
        <h5 class="font-semibold text-lg dark:text-white-light">Edit Vendor</h5>
        <a href="{{ route('vendors.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form method="POST" action="{{ route('vendors.update', $vendor->id) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @csrf
        @method('PUT')

        <div>
            <label>Full Name</label>
            <input type="text" name="full_name" value="{{ old('full_name', $vendor->full_name) }}" class="form-input" required />
        </div>

        <div>
            <label>Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}" class="form-input" required />
        </div>

        <div>
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $vendor->email) }}" class="form-input" />
        </div>

        <div>
            <label>Restaurant Name</label>
            <input type="text" name="restaurant_name" value="{{ old('restaurant_name', $vendor->restaurant_name) }}" class="form-input" required />
        </div>

        <div>
            <label>Working Time</label>
            <input type="text" name="working_time" value="{{ old('working_time', $vendor->working_time) }}" class="form-input" />
        </div>

        <div>
            <label>City</label>
            <select name="city_id" class="form-input" required>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}" {{ old('city_id', $vendor->city_id) == $city->id ? 'selected' : '' }}>
                        {{ $city->name_en }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Area</label>
            <select name="area_id" class="form-input" required>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" {{ old('area_id', $vendor->area_id) == $area->id ? 'selected' : '' }}>
                        {{ $area->name_en }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label>Delivery Address</label>
            <textarea name="delivery_address" rows="3" class="form-input" required>{{ old('delivery_address', $vendor->delivery_address) }}</textarea>
        </div>

        <div class="md:col-span-2">
            <label>Restaurant Info</label>
            <textarea name="restaurant_info" rows="3" class="form-input">{{ old('restaurant_info', $vendor->restaurant_info) }}</textarea>
        </div>

        <div>
            <label>Location</label>
            <input type="text" name="location" value="{{ old('location', $vendor->location) }}" class="form-input" />
        </div>

        <div>
            <label>Main Photo</label>
            <input type="file" name="main_photo" class="form-input" />
            @if($vendor->main_photo)
                <img src="{{ asset('storage/app/public/'.$vendor->main_photo) }}" class="w-20 h-20 mt-2 rounded object-cover">
            @endif
        </div>

        <div>
            <label>ID Front</label>
            <input type="file" name="id_front" class="form-input" />
            @if($vendor->id_front)
                <img src="{{ asset('storage/app/public/'.$vendor->id_front) }}" class="w-20 h-20 mt-2 rounded object-cover">
            @endif
        </div>

        <div>
            <label>ID Back</label>
            <input type="file" name="id_back" class="form-input" />
            @if($vendor->id_back)
                <img src="{{ asset('storage/app/public/'.$vendor->id_back) }}" class="w-20 h-20 mt-2 rounded object-cover">
            @endif
        </div>

        <div>
            <label>Kitchen Photo 1</label>
            <input type="file" name="kitchen_photo_1" class="form-input" />
            @if($vendor->kitchen_photo_1)
                <img src="{{ asset('storage/app/public/'.$vendor->kitchen_photo_1) }}" class="w-20 h-20 mt-2 rounded object-cover">
            @endif
        </div>

        <div>
            <label>Kitchen Photo 2</label>
            <input type="file" name="kitchen_photo_2" class="form-input" />
            @if($vendor->kitchen_photo_2)
                <img src="{{ asset('storage/app/public/'.$vendor->kitchen_photo_2) }}" class="w-20 h-20 mt-2 rounded object-cover">
            @endif
        </div>

        <div>
            <label>Kitchen Photo 3</label>
            <input type="file" name="kitchen_photo_3" class="form-input" />
            @if($vendor->kitchen_photo_3)
                <img src="{{ asset('storage/app/public/'.$vendor->kitchen_photo_3) }}" class="w-20 h-20 mt-2 rounded object-cover">
            @endif
        </div>

        <div>
            <label>Status</label>
            <select name="status" class="form-input">
                <option value="pending" {{ old('status', $vendor->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ old('status', $vendor->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ old('status', $vendor->status) == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <label>Active</label>
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $vendor->is_active) ? 'checked' : '' }} />
        </div>

        <div class="md:col-span-2 mt-5">
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div>
@endsection
