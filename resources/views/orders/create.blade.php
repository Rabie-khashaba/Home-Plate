@extends('partial.master')
@section('title', 'Create Order')

@section('content')
@php
    $formItems = old('items', [['item_id' => '', 'quantity' => 1]]);
    $itemsByVendor = $items->groupBy('vendor_id');
@endphp

<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Create Order</h5>
            <p class="text-sm text-gray-500 mt-1">Create a new order from the dashboard.</p>
        </div>
        <a href="{{ route('orders.index') }}" class="btn btn-secondary">Back</a>
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

    <form method="POST" action="{{ route('orders.store') }}" class="grid grid-cols-1 gap-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div>
                <label>Client</label>
                <select name="app_user_id" class="form-input" required>
                    <option value="">Select Client</option>
                    @foreach ($appUsers as $appUser)
                        <option value="{{ $appUser->id }}" {{ (string) old('app_user_id') === (string) $appUser->id ? 'selected' : '' }}>
                            {{ $appUser->name }}{{ $appUser->phone ? ' - ' . $appUser->phone : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Vendor</label>
                <select name="vendor_id" id="vendor-select" class="form-input" required>
                    <option value="">Select Vendor</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ (string) old('vendor_id') === (string) $vendor->id ? 'selected' : '' }}>
                            {{ $vendor->full_name ?? "" }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Payment Method</label>
                <select name="payment_method" class="form-input" required>
                    @foreach (['vodafone_cash' => 'Vodafone Cash', 'instapay' => 'InstaPay', 'visa' => 'Visa'] as $value => $label)
                        <option value="{{ $value }}" {{ old('payment_method') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Delivery Fee</label>
                <input type="number" step="0.01" min="0" name="delivery_fee" class="form-input" value="{{ old('delivery_fee', 0) }}">
            </div>

            <div>
                <label>Payment Reference</label>
                <input type="text" name="payment_reference" class="form-input" value="{{ old('payment_reference') }}">
            </div>

            <div class="md:col-span-2 xl:col-span-1">
                <label>Notes</label>
                <input type="text" name="notes" class="form-input" value="{{ old('notes') }}" placeholder="Optional notes">
            </div>

            <div class="md:col-span-2 xl:col-span-3">
                <label>Delivery Address</label>
                <textarea name="delivery_address" rows="2" class="form-input" required>{{ old('delivery_address') }}</textarea>
            </div>
        </div>

        <div class="border rounded p-4">
            <div class="mb-3 flex items-center justify-between">
                <h6 class="font-semibold">Items</h6>
                <button type="button" id="add-item-row" class="btn btn-primary">Add Item</button>
            </div>

            <div id="items-wrapper" class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                @foreach ($formItems as $index => $line)
                    <div class="item-row grid grid-cols-1 md:grid-cols-12 gap-3 items-end border rounded p-3">
                        <div class="md:col-span-7">
                            <label>Item</label>
                            <select name="items[{{ $index }}][item_id]" class="form-input item-id-input" required data-selected="{{ $line['item_id'] ?? '' }}">
                                <option value="">Select Item</option>
                            </select>
                        </div>

                        <div class="md:col-span-4">
                            <label>Quantity</label>
                            <input type="number" min="1" name="items[{{ $index }}][quantity]" class="form-input" value="{{ $line['quantity'] ?? 1 }}" required>
                        </div>

                        <div class="md:col-span-1">
                            <button type="button" class="btn btn-danger remove-item-row w-full">X</button>
                        </div>
                    </div>
                @endforeach
            </div>

            <template id="item-row-template">
                <div class="item-row grid grid-cols-1 md:grid-cols-12 gap-3 items-end border rounded p-3">
                    <div class="md:col-span-7">
                        <label>Item</label>
                        <select class="form-input item-id-input" required data-selected="">
                            <option value="">Select Item</option>
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <label>Quantity</label>
                        <input type="number" min="1" class="form-input" value="1" required>
                    </div>

                    <div class="md:col-span-1">
                        <button type="button" class="btn btn-danger remove-item-row w-full">X</button>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Create Order</button>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('items-wrapper');
    const template = document.getElementById('item-row-template');
    const addBtn = document.getElementById('add-item-row');
    const vendorSelect = document.getElementById('vendor-select');
    const itemsByVendor = @json($itemsByVendor);

    function buildOptions(select) {
        const vendorId = vendorSelect.value;
        const selectedValue = select.dataset.selected || '';
        const vendorItems = itemsByVendor[vendorId] || [];

        select.innerHTML = '<option value="">Select Item</option>';

        vendorItems.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = `${item.name} (${Number(item.price).toFixed(2)})`;
            if (String(selectedValue) === String(item.id)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    function refreshItemOptions() {
        wrapper.querySelectorAll('.item-id-input').forEach(buildOptions);
    }

    function reindexRows() {
        const rows = wrapper.querySelectorAll('.item-row');
        rows.forEach((row, index) => {
            const select = row.querySelector('.item-id-input');
            const qty = row.querySelector('input[type="number"]');

            if (select) {
                select.name = `items[${index}][item_id]`;
            }

            if (qty) {
                qty.name = `items[${index}][quantity]`;
            }
        });
    }

    function bindRemoveButtons() {
        wrapper.querySelectorAll('.remove-item-row').forEach((btn) => {
            if (btn.dataset.bound === '1') {
                return;
            }

            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                const rows = wrapper.querySelectorAll('.item-row');
                if (rows.length <= 1) {
                    return;
                }

                btn.closest('.item-row').remove();
                reindexRows();
            });
        });
    }

    addBtn.addEventListener('click', function () {
        const clone = template.content.firstElementChild.cloneNode(true);
        wrapper.appendChild(clone);
        refreshItemOptions();
        reindexRows();
        bindRemoveButtons();
    });

    vendorSelect.addEventListener('change', function () {
        wrapper.querySelectorAll('.item-id-input').forEach((select) => {
            select.dataset.selected = '';
        });
        refreshItemOptions();
    });

    refreshItemOptions();
    reindexRows();
    bindRemoveButtons();
});
</script>
@endsection
