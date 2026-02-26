@extends('partial.master')
@section('title', 'Edit Item')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Edit Item</h5>
        <a href="{{ route('items.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form method="POST" action="{{ route('items.update', $item->id) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @csrf
        @method('PUT')

        <div>
            <label>Vendor</label>
            <select name="vendor_id" class="form-input" required>
                <option value="">Select Vendor</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" {{ (old('vendor_id', $item->vendor_id) == $vendor->id) ? 'selected' : '' }}>
                        {{ $vendor->restaurant_name ?? $vendor->full_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Category</label>
            <select name="category_id" class="form-input" required>
                <option value="">Select Category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (old('category_id', $item->category_id) == $category->id) ? 'selected' : '' }}>
                        {{ $category->name_en }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Item Name</label>
            <input type="text" name="name" class="form-input" value="{{ old('name', $item->name) }}" required />
        </div>

        <div>
            <label>Price</label>
            <input type="number" step="0.01" name="price" class="form-input" value="{{ old('price', $item->price) }}" required />
        </div>

        <div>
            <label>Discount</label>
            <input type="number" step="0.01" name="discount" class="form-input" value="{{ old('discount', $item->discount) }}" />
        </div>

        <div>
            <label>Prep Time</label>
            <div class="flex gap-2">
                <input type="number" name="prep_time_value" class="form-input" value="{{ old('prep_time_value', $item->prep_time_value) }}" required />
                <select name="prep_time_unit" class="form-input" required>
                    <option value="minutes" {{ old('prep_time_unit', $item->prep_time_unit) === 'minutes' ? 'selected' : '' }}>Minutes</option>
                    <option value="hours" {{ old('prep_time_unit', $item->prep_time_unit) === 'hours' ? 'selected' : '' }}>Hours</option>
                </select>
            </div>
        </div>

        <div>
            <label>Stock</label>
            <input type="number" name="stock" class="form-input" value="{{ old('stock', $item->stock) }}" required />
        </div>

        <div>
            <label>Max Orders Per Day</label>
            <input type="number" name="max_orders_per_day" class="form-input" value="{{ old('max_orders_per_day', $item->max_orders_per_day) }}" />
        </div>

        <div>
            <label>Approval Status</label>
            <select name="approval_status" class="form-input" required>
                @foreach(['pending','approved','rejected'] as $status)
                    <option value="{{ $status }}" {{ old('approval_status', $item->approval_status) === $status ? 'selected' : '' }}>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Availability Status</label>
            <select name="availability_status" class="form-input" required>
                @foreach(['paused','published'] as $status)
                    <option value="{{ $status }}" {{ old('availability_status', $item->availability_status) === $status ? 'selected' : '' }}>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1">Availability is forced to paused unless approved.</p>
        </div>

        <div class="md:col-span-2">
            <label>Description</label>
            <textarea name="description" rows="3" class="form-input">{{ old('description', $item->description) }}</textarea>
        </div>

        <div class="md:col-span-2 border-t pt-4 font-semibold text-gray-600">Replace Photos (upload 4 to replace)</div>

        @for($i = 1; $i <= 4; $i++)
            <div>
                <label>Photo {{ $i }}</label>
                <input type="file" name="photos[]" class="form-input" />
                @if(!empty($item->photos[$i - 1]))
                    <img src="{{ asset('storage/' . ltrim($item->photos[$i - 1], '/')) }}" alt="Current Photo {{ $i }}" class="mt-2 h-14 w-24 rounded object-cover" />
                @endif
            </div>
        @endfor

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">Save</button>
        </div>
    </form>
</div>
@endsection
