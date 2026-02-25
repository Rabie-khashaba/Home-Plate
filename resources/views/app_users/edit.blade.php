@extends('partial.master')
@section('title', 'Edit App User')

@section('content')
<div>

    <div class="pt-5">
        <div class="flex items-center justify-between mb-5">
            <h5 class="font-semibold text-lg dark:text-white-light">Edit App User</h5>
            <a href="{{ route('app_users.index') }}" class="btn btn-secondary">Back</a>
        </div>

        <form method="POST" action="{{ route('app_users.update', $app_user->id) }}" enctype="multipart/form-data"
              class="border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-4 bg-white dark:bg-[#0e1726]">
            @csrf
            @method('PUT')

            <h6 class="text-lg font-bold mb-5">General Information</h6>

            <div class="flex flex-col sm:flex-row">
                <div class="ltr:sm:mr-4 rtl:sm:ml-4 w-full sm:w-2/12 mb-5 text-center">
                    @if($app_user->photo)
                        <img src="{{ asset('storage/app/public/'.$app_user->photo) }}" alt="User Photo"
                             class="w-20 h-20 md:w-32 md:h-32 rounded-full object-cover mx-auto mb-3" />
                    @else
                        <img src="/assets/images/profile-placeholder.png" alt="No Image"
                             class="w-20 h-20 md:w-32 md:h-32 rounded-full object-cover mx-auto mb-3 opacity-60" />
                    @endif
                    <input type="file" name="photo" class="form-input w-full" />
                </div>

                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label>Full Name</label>
                        <input type="text" name="name" value="{{ old('name', $app_user->name) }}" class="form-input" required>
                    </div>

                    <div>
                        <label>Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $app_user->phone) }}" class="form-input" required>
                    </div>

                    <div>
                        <label>Email</label>
                        <input type="email" name="email" value="{{ old('email', $app_user->email) }}" class="form-input">
                    </div>

                    <div>
                        <label>Gender</label>
                        <select name="gender" class="form-input">
                            <option value="">Select</option>
                            <option value="male" {{ old('gender', $app_user->gender) == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $app_user->gender) == 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>

                    <div>
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="{{ old('dob', $app_user->dob ? $app_user->dob->format('Y-m-d') : '') }}" class="form-input">
                    </div>

                    <div>
                        <label>City</label>
                        <select name="city_id" class="form-input" required>
                            <option value="">Select City</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" {{ old('city_id', $app_user->city_id) == $city->id ? 'selected' : '' }}>
                                    {{ $city->name_en }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label>Area</label>
                        <select name="area_id" class="form-input" required>
                            <option value="">Select Area</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" {{ old('area_id', $app_user->area_id) == $area->id ? 'selected' : '' }}>
                                    {{ $area->name_en }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label>Delivery Addresses</label>
                        <textarea name="delivery_addresses" rows="3" class="form-input">{{ old('delivery_addresses', $app_user->delivery_addresses) }}</textarea>
                    </div>

                    <div>
                        <label>Location (URL)</label>
                        <input type="url" name="location" value="{{ old('location', $app_user->location) }}" class="form-input" required>
                    </div>

                    <div>
                        <label>Status</label><br>
                        <label class="inline-flex items-center mt-2">
                            <input type="checkbox" name="is_active" value="1" {{ $app_user->is_active ? 'checked' : '' }} />
                            <span class="ml-2">Active</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>
@endsection
