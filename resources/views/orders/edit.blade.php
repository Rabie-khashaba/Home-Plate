@extends('partial.master')
@section('title', 'Edit Order')

@section('content')
@php
    $formItems = old('items', $order->orderItems->map(function ($orderItem) {
        return [
            'item_id' => $orderItem->item_id,
            'quantity' => $orderItem->quantity,
        ];
    })->values()->all());

    if (empty($formItems)) {
        $formItems = [['item_id' => '', 'quantity' => 1]];
    }
@endphp

<div class="panel">
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h5 class="text-lg font-semibold dark:text-white-light">Edit Order</h5>
            <p class="text-sm text-gray-500 mt-1">Order #: {{ $order->order_number }}</p>
        </div>
        <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">Back</a>
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

    <form method="POST" action="{{ route('orders.update', $order) }}" class="grid grid-cols-1 gap-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div>
                <label>Payment Method</label>
                <select name="payment_method" class="form-input" required>
                    @foreach (['vodafone_cash' => 'Vodafone Cash', 'instapay' => 'InstaPay', 'visa' => 'Visa'] as $value => $label)
                        <option value="{{ $value }}" {{ old('payment_method', $order->payment_method) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>Delivery Fee</label>
                <input type="number" step="0.01" min="0" name="delivery_fee" class="form-input" value="{{ old('delivery_fee', $order->delivery_fee) }}">
            </div>

            <div>
                <label>Payment Reference</label>
                <input type="text" name="payment_reference" class="form-input" value="{{ old('payment_reference', $order->payment_reference) }}">
            </div>

            <div>
                <label>Notes</label>
                <input type="text" name="notes" class="form-input" value="{{ old('notes', $order->notes) }}" placeholder="Optional notes">
            </div>

            <div class="md:col-span-1 xl:col-span-2">
                <label>Delivery Address</label>
                <textarea name="delivery_address" rows="1" class="form-input" required>{{ old('delivery_address', $order->delivery_address) }}</textarea>
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
                            <select name="items[{{ $index }}][item_id]" class="form-input" required>
                                <option value="">Select Item</option>
                                @foreach ($vendorItems as $vendorItem)
                                    <option value="{{ $vendorItem->id }}" {{ (string) ($line['item_id'] ?? '') === (string) $vendorItem->id ? 'selected' : '' }}>
                                        {{ $vendorItem->name }} ({{ number_format((float) $vendorItem->price, 2) }})
                                    </option>
                                @endforeach
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
                        <select class="form-input item-id-input" required>
                            <option value="">Select Item</option>
                            @foreach ($vendorItems as $vendorItem)
                                <option value="{{ $vendorItem->id }}">{{ $vendorItem->name }} ({{ number_format((float) $vendorItem->price, 2) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <label>Quantity</label>
                        <input type="number" min="1" class="form-input item-qty-input" value="1" required>
                    </div>

                    <div class="md:col-span-1">
                        <button type="button" class="btn btn-danger remove-item-row w-full">X</button>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Update Order</button>
            <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('items-wrapper');
    const template = document.getElementById('item-row-template');
    const addBtn = document.getElementById('add-item-row');

    function reindexRows() {
        const rows = wrapper.querySelectorAll('.item-row');
        rows.forEach((row, index) => {
            const select = row.querySelector('select');
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
        reindexRows();
        bindRemoveButtons();
    });

    reindexRows();
    bindRemoveButtons();
});
</script>
@endsection
