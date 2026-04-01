@extends('partial.master')
@section('title', 'Create Item')

@section('content')
<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <h5 class="text-lg font-semibold dark:text-white-light">Create Item</h5>
        <a href="{{ route('items.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <form method="POST" action="{{ route('items.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @csrf

        <div>
            <label>Vendor</label>
            <select name="vendor_id" class="form-input" required>
                <option value="">Select Vendor</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                        {{ $vendor->full_name ?? "N/A" }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Category</label>
            <select name="category_id" class="form-input" required>
                <option value="">Select Category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name_en }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Item Name</label>
            <input type="text" name="name" class="form-input" value="{{ old('name') }}" required />
        </div>

        <div>
            <label>Price</label>
            <input type="number" step="0.01" name="price" class="form-input" value="{{ old('price') }}" required />
        </div>

        <div>
            <label>Discount</label>
            <input type="number" step="0.01" name="discount" class="form-input" value="{{ old('discount') }}" />
        </div>

        <div>
            <label>Prep Time</label>
            <div class="flex gap-2">
                <input type="number" name="prep_time_value" class="form-input" value="{{ old('prep_time_value') }}" required />
                <select name="prep_time_unit" class="form-input" required>
                    <option value="minutes" {{ old('prep_time_unit') === 'minutes' ? 'selected' : '' }}>Minutes</option>
                    <option value="hours" {{ old('prep_time_unit') === 'hours' ? 'selected' : '' }}>Hours</option>
                </select>
            </div>
        </div>

        <div>
            <label>Stock</label>
            <input type="number" name="stock" class="form-input" value="{{ old('stock') }}" required />
        </div>

        <div>
            <label>Max Orders Per Day</label>
            <input type="number" name="max_orders_per_day" class="form-input" value="{{ old('max_orders_per_day') }}" />
        </div>

        <div class="md:col-span-2">
            <label>Description</label>
            <textarea name="description" rows="3" class="form-input">{{ old('description') }}</textarea>
        </div>

        <div class="md:col-span-2 border-t pt-4 font-semibold text-gray-600">Item Photos (4)</div>

        @for($i = 1; $i <= 4; $i++)
            <div>
                <label>Photo {{ $i }}</label>
                <input type="file" name="photos[]" class="form-input" required />
            </div>
        @endfor

        <div class="md:col-span-2">
            <button type="submit" class="btn btn-primary w-full md:w-auto !mt-6">Save</button>
        </div>
    </form>
</div>
@endsection
