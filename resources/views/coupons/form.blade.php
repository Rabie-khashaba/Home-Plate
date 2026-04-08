@extends('partial.master')
@section('title', $coupon->exists ? 'Edit Coupon' : 'Create Coupon')

@section('content')
<div class="panel max-w-4xl mx-auto">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">
                {{ $coupon->exists ? 'Edit Coupon' : 'Create Coupon' }}
            </h5>
            <p class="text-sm text-gray-500 mt-1">
                Configure discount type, validity, and usage controls.
            </p>
        </div>
        <a href="{{ route('coupons.index') }}" class="btn btn-outline-primary">Back</a>
    </div>

    <form method="POST" action="{{ $coupon->exists ? route('coupons.update', $coupon->id) : route('coupons.store') }}">
        @csrf
        @if($coupon->exists)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="form-label">Code</label>
                <input type="text" name="code" value="{{ old('code', $coupon->code) }}" class="form-input @error('code') border-danger @enderror" maxlength="50" required>
                @error('code')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Type</label>
                <select name="type" class="form-select @error('type') border-danger @enderror" required>
                    <option value="">Select type</option>
                    <option value="percentage" @selected(old('type', $coupon->type) === 'percentage')>Percentage</option>
                    <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Fixed</option>
                </select>
                @error('type')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Value</label>
                <input type="number" step="0.01" min="0.01" name="value" value="{{ old('value', $coupon->value) }}" class="form-input @error('value') border-danger @enderror" required>
                @error('value')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Minimum Order Amount</label>
                <input type="number" step="0.01" min="0" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" class="form-input @error('min_order_amount') border-danger @enderror">
                @error('min_order_amount')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Maximum Discount</label>
                <input type="number" step="0.01" min="0" name="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" class="form-input @error('max_discount') border-danger @enderror">
                @error('max_discount')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Usage Limit</label>
                <input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="form-input @error('usage_limit') border-danger @enderror">
                @error('usage_limit')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Starts At</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($coupon->starts_at)->format('Y-m-d\\TH:i')) }}" class="form-input @error('starts_at') border-danger @enderror">
                @error('starts_at')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="form-label">Expires At</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', optional($coupon->expires_at)->format('Y-m-d\\TH:i')) }}" class="form-input @error('expires_at') border-danger @enderror">
                @error('expires_at')
                    <p class="mt-1 text-danger text-sm">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $coupon->exists ? $coupon->is_active : true))>
                    <span>Active</span>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn btn-primary">
                {{ $coupon->exists ? 'Update Coupon' : 'Create Coupon' }}
            </button>
            <a href="{{ route('coupons.index') }}" class="btn btn-outline-danger">Cancel</a>
        </div>
    </form>
</div>
@endsection
