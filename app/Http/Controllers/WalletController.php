<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Vendor;
use App\Models\Wallet;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'all'); // all | vendor | delivery

        $vendorWallets   = collect();
        $deliveryWallets = collect();

        if ($type !== 'delivery') {
            $vendorWallets = Wallet::with('owner')
                ->where('owner_type', 'App\\Models\\Vendor')
                ->orderByDesc('balance')
                ->paginate(10, ['*'], 'vendor_page');
        }

        if ($type !== 'vendor') {
            $deliveryWallets = Wallet::with('owner')
                ->where('owner_type', 'App\\Models\\Delivery')
                ->orderByDesc('balance')
                ->paginate(10, ['*'], 'delivery_page');
        }

        $stats = [
            'total_vendor_balance'   => Wallet::where('owner_type', 'App\\Models\\Vendor')->sum('balance'),
            'total_delivery_balance' => Wallet::where('owner_type', 'App\\Models\\Delivery')->sum('balance'),
            'total_vendor_earned'    => Wallet::where('owner_type', 'App\\Models\\Vendor')->sum('total_earned'),
            'total_delivery_earned'  => Wallet::where('owner_type', 'App\\Models\\Delivery')->sum('total_earned'),
        ];

        return view('wallets.index', compact('vendorWallets', 'deliveryWallets', 'stats', 'type'));
    }

    public function show(string $ownerType, int $id)
    {
        $modelClass = match($ownerType) {
            'vendor'   => \App\Models\Vendor::class,
            'delivery' => \App\Models\Delivery::class,
            default    => abort(404),
        };

        $owner  = $modelClass::findOrFail($id);
        $wallet = Wallet::forOwner($modelClass, $id);
        $wallet->load(['transactions.order', 'transactions.createdBy']);

        $transactions = $wallet->transactions()->paginate(20);

        return view('wallets.show', compact('owner', 'wallet', 'transactions', 'ownerType'));
    }

    public function adjust(Request $request, string $ownerType, int $id)
    {
        $data = $request->validate([
            'type'        => 'required|in:credit,debit',
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $modelClass = match($ownerType) {
            'vendor'   => \App\Models\Vendor::class,
            'delivery' => \App\Models\Delivery::class,
            default    => abort(404),
        };

        $owner  = $modelClass::findOrFail($id);
        $wallet = Wallet::forOwner($modelClass, $id);

        if ($data['type'] === 'credit') {
            $wallet->credit((float)$data['amount'], $data['description'], null, Auth::id());
        } else {
            if ($data['amount'] > $wallet->balance) {
                return back()->with('error', 'Insufficient wallet balance.');
            }
            $wallet->debit((float)$data['amount'], $data['description'], null, Auth::id());
        }

        $name = $ownerType === 'vendor'
            ? ($owner->restaurant_name ?? $owner->full_name)
            : ($owner->first_name ?? 'Rider #'.$id);

        ActivityLogger::log(
            $data['type'] === 'credit' ? 'credit' : 'debit',
            ucfirst($data['type']) . ' wallet of ' . $name . ': ' . $data['amount'] . ' EGP — ' . $data['description'],
            $wallet
        );

        return back()->with('success', 'Wallet adjusted successfully.');
    }
}
