@extends('partial.master')
@section('title', 'Create Vendor')

@section('content')
@php
    $workingDays = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
@endphp
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Create Vendor</h5>
        <a href="{{ route('vendors.index') }}" class="btn btn-secondary">Back</a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded border border-danger p-3 text-danger">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
            <label>Categories</label>
            <select name="category_ids[]" class="form-input" multiple required size="5">
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ in_array($category->id, old('category_ids', [])) ? 'selected' : '' }}>{{ $category->name_en }}</option>
                @endforeach
            </select>
            <small class="text-gray-500">Hold Ctrl or Cmd to select more than one category.</small>
        </div>

        <div>
            <label>Subcategories</label>
            <select name="subcategory_ids[]" class="form-input" multiple size="5">
                @foreach($subcategories as $subcategory)
                    <option value="{{ $subcategory->id }}" {{ in_array($subcategory->id, old('subcategory_ids', [])) ? 'selected' : '' }}>
                        {{ $subcategory->name_en }}
                    </option>
                @endforeach
            </select>
            <small class="text-gray-500">Optional. Hold Ctrl or Cmd to select more than one subcategory.</small>
        </div>

        <div class="md:col-span-2">
            <div class="mb-3">
                <label class="mb-1 block text-base font-semibold">Working Schedule</label>
                <p class="text-sm text-gray-500">Choose the active days, then set opening and closing time for each selected day.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 rounded-xl border border-[#e0e6ed] bg-[#f8fafc] p-4 lg:grid-cols-3 dark:border-[#1b2e4b] dark:bg-[#0e1726]">
                @foreach ($workingDays as $day)
                    <div class="working-day-row rounded-xl border border-transparent bg-white p-4 shadow-sm transition dark:bg-[#132136]" data-day-row>
                        <div class="flex flex-col gap-3">
                            <div>
                                <label class="flex cursor-pointer items-center gap-3">
                                    <input
                                        type="checkbox"
                                        name="working_time[{{ $day }}][enabled]"
                                        value="1"
                                        class="working-day-toggle h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary"
                                        {{ old("working_time.$day.enabled") ? 'checked' : '' }}
                                    >
                                    <div>
                                        <span class="block font-semibold text-[#3b3f5c] dark:text-white-light">{{ ucfirst($day) }}</span>
                                        <span class="text-xs text-gray-500">Available for orders</span>
                                    </div>
                                </label>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">From</label>
                                    <input type="time" name="working_time[{{ $day }}][from]" class="form-input working-time-input" value="{{ old("working_time.$day.from") }}" />
                                    @error("working_time.$day.from")
                                        <small class="mt-1 block text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">To</label>
                                    <input type="time" name="working_time[{{ $day }}][to]" class="form-input working-time-input" value="{{ old("working_time.$day.to") }}" />
                                    @error("working_time.$day.to")
                                        <small class="mt-1 block text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
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
            <label>Tax Card Number</label>
            <input type="text" name="tax_card_number" class="form-input" value="{{ old('tax_card_number') }}" />
        </div>

        <div>
            <label>Commercial Register Number</label>
            <input type="text" name="commercial_register_number" class="form-input" value="{{ old('commercial_register_number') }}" />
        </div>

        <div>
            <label>Tax Card Image</label>
            <input type="file" name="tax_card_image" class="form-input" />
        </div>

        <div>
            <label>Commercial Register Image</label>
            <input type="file" name="commercial_register_image" class="form-input" />
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-day-row]').forEach(function (row) {
        const toggle = row.querySelector('.working-day-toggle');
        const inputs = row.querySelectorAll('.working-time-input');

        function syncRowState() {
            const isEnabled = toggle.checked;

            row.classList.toggle('border-primary', isEnabled);
            row.classList.toggle('bg-primary-light', isEnabled);

            inputs.forEach(function (input) {
                input.disabled = !isEnabled;
                input.classList.toggle('opacity-50', !isEnabled);
                input.classList.toggle('cursor-not-allowed', !isEnabled);
            });
        }

        toggle.addEventListener('change', syncRowState);
        syncRowState();
    });
});
</script>
@endsection
