@extends('partial.master')
@section('title', 'Edit Vendor')

@section('content')
@php
    $workingDays = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    $workingTime = old('working_time', $vendor->working_time);

    if (! is_array($workingTime)) {
        $workingTime = json_decode($workingTime ?? '', true) ?: [];
    }

    if (isset($workingTime['day'])) {
        $workingTime = [$workingTime];
    }

    $workingTimeMap = [];
    foreach ($workingTime as $slot) {
        if (! is_array($slot) || empty($slot['day'])) {
            continue;
        }

        $workingTimeMap[$slot['day']] = [
            'enabled' => true,
            'from' => ! empty($slot['from']) ? \Illuminate\Support\Carbon::createFromFormat('g:i A', $slot['from'])->format('H:i') : '',
            'to' => ! empty($slot['to']) ? \Illuminate\Support\Carbon::createFromFormat('g:i A', $slot['to'])->format('H:i') : '',
        ];
    }
@endphp
<div class="panel">
    <div class="flex items-center justify-between mb-5">
        <h5 class="font-semibold text-lg dark:text-white-light">Edit Vendor</h5>
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
            <label>Categories</label>
            <select name="category_ids[]" class="form-input" multiple required size="5">
                @php
                    $selectedCategoryIds = old('category_ids', $vendor->categories->pluck('id')->all());
                @endphp
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ in_array($category->id, $selectedCategoryIds) ? 'selected' : '' }}>
                        {{ $category->name_en }}
                    </option>
                @endforeach
            </select>
            <small class="text-gray-500">Hold Ctrl or Cmd to select more than one category.</small>
        </div>

        <div class="md:col-span-2">
            <div class="mb-3">
                <label class="mb-1 block text-base font-semibold">Working Schedule</label>
                <p class="text-sm text-gray-500">Enable the days the vendor works, then set opening and closing time for each one.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 rounded-xl border border-[#e0e6ed] bg-[#f8fafc] p-4 lg:grid-cols-3 dark:border-[#1b2e4b] dark:bg-[#0e1726]">
                @foreach ($workingDays as $day)
                    @php
                        $dayState = old("working_time.$day", $workingTimeMap[$day] ?? []);
                    @endphp
                    <div class="working-day-row rounded-xl border border-transparent bg-white p-4 shadow-sm transition dark:bg-[#132136]" data-day-row>
                        <div class="flex flex-col gap-3">
                            <div>
                                <label class="flex cursor-pointer items-center gap-3">
                                    <input
                                        type="checkbox"
                                        name="working_time[{{ $day }}][enabled]"
                                        value="1"
                                        class="working-day-toggle h-5 w-5 rounded border-gray-300 text-primary focus:ring-primary"
                                        {{ ! empty($dayState['enabled']) ? 'checked' : '' }}
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
                                    <input type="time" name="working_time[{{ $day }}][from]" value="{{ $dayState['from'] ?? '' }}" class="form-input working-time-input" />
                                    @error("working_time.$day.from")
                                        <small class="mt-1 block text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">To</label>
                                    <input type="time" name="working_time[{{ $day }}][to]" value="{{ $dayState['to'] ?? '' }}" class="form-input working-time-input" />
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
            <label>Tax Card Number</label>
            <input type="text" name="tax_card_number" value="{{ old('tax_card_number', $vendor->tax_card_number) }}" class="form-input" />
        </div>

        <div>
            <label>Commercial Register Number</label>
            <input type="text" name="commercial_register_number" value="{{ old('commercial_register_number', $vendor->commercial_register_number) }}" class="form-input" />
        </div>

        <div>
            <label>Tax Card Image</label>
            <input type="file" name="tax_card_image" class="form-input" />
            @if($vendor->tax_card_image)
                <img src="{{ asset('storage/app/public/'.$vendor->tax_card_image) }}" class="w-20 h-20 mt-2 rounded object-cover">
            @endif
        </div>

        <div>
            <label>Commercial Register Image</label>
            <input type="file" name="commercial_register_image" class="form-input" />
            @if($vendor->commercial_register_image)
                <img src="{{ asset('storage/app/public/'.$vendor->commercial_register_image) }}" class="w-20 h-20 mt-2 rounded object-cover">
            @endif
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
