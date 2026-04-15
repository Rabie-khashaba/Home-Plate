<?php

use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('payments')) {
            return;
        }

        $hasPaymentMethod = Schema::hasColumn('orders', 'payment_method');
        $hasPaymentStatus = Schema::hasColumn('orders', 'payment_status');
        $hasPaymentReference = Schema::hasColumn('orders', 'payment_reference');

        if (! $hasPaymentMethod && ! $hasPaymentStatus && ! $hasPaymentReference) {
            return;
        }

        $orderColumns = array_values(array_filter([
            'id',
            'order_number',
            Schema::hasColumn('orders', 'total_amount') ? 'total_amount' : null,
            $hasPaymentMethod ? 'payment_method' : null,
            $hasPaymentStatus ? 'payment_status' : null,
            $hasPaymentReference ? 'payment_reference' : null,
            'created_at',
        ]));

        DB::table('orders')
            ->select($orderColumns)
            ->orderBy('id')
            ->chunkById(500, function ($orders) use ($hasPaymentMethod, $hasPaymentStatus, $hasPaymentReference) {
                foreach ($orders as $order) {
                    $existing = DB::table('payments')->where('order_id', $order->id)->exists();
                    if ($existing) {
                        continue;
                    }

                    $legacyMethod = $hasPaymentMethod ? (string) ($order->payment_method ?? '') : '';
                    $legacyStatus = $hasPaymentStatus ? (string) ($order->payment_status ?? '') : '';
                    $legacyReference = $hasPaymentReference ? (string) ($order->payment_reference ?? '') : '';

                    if ($legacyMethod === '' && $legacyStatus === '' && $legacyReference === '') {
                        continue;
                    }

                    $provider = $legacyMethod === 'visa' ? 'paymob' : 'manual';
                    $method = match ($legacyMethod) {
                        'visa' => 'card',
                        default => $legacyMethod !== '' ? $legacyMethod : null,
                    };

                    $status = match ($legacyStatus) {
                        'paid' => Payment::STATUS_PAID,
                        'pending' => Payment::STATUS_PENDING,
                        'refunded' => Payment::STATUS_REFUNDED,
                        'canceled' => Payment::STATUS_CANCELED,
                        default => $legacyStatus !== '' ? $legacyStatus : Payment::STATUS_PENDING,
                    };

                    $providerTransactionId = null;
                    $reference = $legacyReference !== '' ? $legacyReference : null;
                    $meta = [
                        'migrated_from_orders' => true,
                        'legacy_order_number' => $order->order_number ?? null,
                    ];

                    // `provider_transaction_id` is unique per provider. For legacy manual methods (vodafone/instapay),
                    // refs are not guaranteed unique, so keep it in `reference` only and leave tx id null.
                    if ($provider !== 'manual' && $legacyReference !== '') {
                        $duplicateTx = DB::table('payments')
                            ->where('provider', $provider)
                            ->where('provider_transaction_id', $legacyReference)
                            ->exists();

                        if (! $duplicateTx) {
                            $providerTransactionId = $legacyReference;
                        } else {
                            $meta['provider_transaction_id_collision'] = true;
                        }
                    }

                    DB::table('payments')->insert([
                        'order_id' => $order->id,
                        'provider' => $provider,
                        'method' => $method,
                        'amount' => $order->total_amount ?? 0,
                        'currency' => 'EGP',
                        'status' => $status,
                        'reference' => $reference,
                        'provider_transaction_id' => $providerTransactionId,
                        'provider_payload' => null,
                        'meta' => json_encode($meta),
                        'paid_at' => $status === Payment::STATUS_PAID ? ($order->created_at ?? now()) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });

        Schema::table('orders', function (Blueprint $table) use ($hasPaymentMethod, $hasPaymentStatus, $hasPaymentReference) {
            $columns = array_values(array_filter([
                $hasPaymentMethod ? 'payment_method' : null,
                $hasPaymentStatus ? 'payment_status' : null,
                $hasPaymentReference ? 'payment_reference' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }
            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->nullable();
            }
            if (! Schema::hasColumn('orders', 'payment_reference')) {
                $table->string('payment_reference')->nullable();
            }
        });
    }
};
