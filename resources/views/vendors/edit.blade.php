@extends('partial.master')
@section('title', 'Edit Vendor')

@section('content')
<div class="panel">
    <div class="flex items-center justify-between mb-5">
        <h5 class="font-semibold text-lg dark:text-white-light">Edit Vendor</h5>
        <a href="{{ route('vendors.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form method="POST" action="{{ route('vendors.update', $vendor->id) }}" enctype="multipart/form-data"
          class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @csrf
        @method('PUT')

        <div>
            <label>Name</label>
            <input type="text" name="name" value="{{ old('name', $vendor->name) }}" class="form-input" required />
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
            <label>City</label>
            <select name="city_id" class="form-input" required>
                @foreach($cities as $city)
                    <option value="{{ $city->id }}" {{ $vendor->city_id == $city->id ? 'selected' : '' }}>
                        {{ $city->name_en }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Area</label>
            <select name="area_id" class="form-input" required>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" {{ $vendor->area_id == $area->id ? 'selected' : '' }}>
                        {{ $area->name_en }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-2">
            <label>Address</label>
            <textarea name="address" rows="3" class="form-input">{{ $vendor->address }}</textarea>
        </div>

        <div>
            <label>Location</label>
            <input type="url" name="location" value="{{ $vendor->location }}" class="form-input" />
        </div>

        <div>
            <label>Logo</label>
            <input type="file" name="logo" class="form-input" />
            @if($vendor->logo)
                <img src="{{ asset('storage/'.$vendor->logo) }}" class="w-20 h-20 mt-2 rounded-full object-cover">
            @endif
        </div>

        <div>
            <label>Status</label>
            <select name="status" class="form-input">
                <option value="pending" {{ $vendor->status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ $vendor->status == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ $vendor->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <label>Active</label>
            <input type="checkbox" name="is_active" value="1" {{ $vendor->is_active ? 'checked' : '' }} />
        </div>

        <div class="md:col-span-2 mt-5">
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </form>
</div>
@endsection
