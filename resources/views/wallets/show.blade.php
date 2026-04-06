@extends('partial.master')
@section('title', 'Wallet — ' . ($owner->restaurant_name ?? $owner->first_name ?? 'Owner'))

@section('content')

<div class="mb-4 flex items-center gap-3">
    <a href="{{ route('wallets.index') }}" class="btn btn-outline-secondary btn-sm">← Back</a>
    <h5 class="text-lg font-semibold dark:text-white-light">
        Wallet — {{ $ownerType === 'vendor' ? ($owner->restaurant_name ?? $owner->full_name) : ($owner->first_name ?? 'Rider #'.$owner->id) }}
    </h5>
</div>

@if(session('success'))<div class="alert alert-success mb-4">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger mb-4">{{ session('error') }}</div>@endif

<div class="grid grid-cols-1 gap-6 md:grid-cols-3 mb-6">
    {{-- Balance card --}}
    <div class="panel text-white md:col-span-1" style="background:linear-gradient(135deg,#3b82f6,#60a5fa)">
        <p class="text-sm mb-1" style="opacity:.8">Current Balance</p>
        <h2 class="text-4xl font-bold mb-4">{{ number_format((float)$wallet->balance, 2) }} <span class="text-xl">EGP</span></h2>
        <div class="flex justify-between text-sm" style="opacity:.85">
            <div><p style="opacity:.7">Total Earned</p><p class="font-semibold">{{ number_format((float)$wallet->total_earned, 2) }}</p></div>
            <div><p style="opacity:.7">Total Withdrawn</p><p class="font-semibold">{{ number_format((float)$wallet->total_withdrawn, 2) }}</p></div>
        </div>
    </div>

    {{-- Adjust form --}}
    <div class="panel md:col-span-2">
        <h6 class="text-base font-semibold mb-4 dark:text-white-light">Manual Adjustment</h6>
        @if($errors->any())
            <div class="alert alert-danger mb-3 text-sm">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('wallets.adjust', ['type' => $ownerType, 'id' => $owner->id]) }}">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Type</label>
                    <select name="type" class="form-select">
                        <option value="credit">Credit (+)</option>
                        <option value="debit">Debit (−)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Amount (EGP)</label>
                    <input type="number" name="amount" class="form-input" step="0.01" min="0.01" placeholder="0.00" required />
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium mb-1">Description</label>
                    <input type="text" name="description" class="form-input" placeholder="e.g. Order payout" required />
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4">Apply Adjustment</button>
        </form>
    </div>
</div>

{{-- Transactions history --}}
<div class="panel">
    <h6 class="text-base font-semibold mb-4 dark:text-white-light">Transaction History</h6>
    <div class="table-responsive">
        <table class="w-full">
            <thead>
                <tr><th>#</th><th>Type</th><th>Amount</th><th>Balance After</th><th>Description</th><th>Order</th><th>By</th><th>Date</th></tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                @php $isCredit = $tx->type === 'credit'; @endphp
                <tr>
                    <td class="text-xs text-gray-400">{{ $tx->id }}</td>
                    <td>
                        @if($isCredit)
                        <span style="background:#22c55e20;color:#22c55e;border:1px solid #22c55e40;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600">▲ Credit</span>
                        @else
                        <span style="background:#ef444420;color:#ef4444;border:1px solid #ef444440;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600">▼ Debit</span>
                        @endif
                    </td>
                    <td class="font-semibold {{ $isCredit ? 'text-success' : 'text-danger' }}">
                        {{ $isCredit ? '+' : '−' }}{{ number_format((float)$tx->amount, 2) }}
                    </td>
                    <td>{{ number_format((float)$tx->balance_after, 2) }}</td>
                    <td class="text-sm">{{ $tx->description }}</td>
                    <td class="text-xs text-gray-500">{{ $tx->order->order_number ?? '—' }}</td>
                    <td class="text-xs text-gray-500">{{ $tx->createdBy->name ?? 'System' }}</td>
                    <td class="text-xs text-gray-500 whitespace-nowrap">{{ $tx->created_at->format('d M Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-gray-400 py-8">No transactions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
