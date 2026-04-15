<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Coupon;
use App\Models\Vendor;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class OrderPaymentRecorder
{
    public function createPendingPaymobPayment(Order $order, array $gatewayResponse = [], array $gatewayRequest = []): Payment
    {
        return Payment::query()->create([
            'order_id' => $order->id,
            'provider' => 'paymob',
            'method' => 'card',
            'amount' => (float) $order->total_amount,
            'currency' => (string) config('paymob.currency', 'EGP'),
            'status' => Payment::STATUS_PENDING,
            'reference' => null,
            'provider_order_id' => $gatewayResponse['provider_order_id'] ?? null,
            'provider_transaction_id' => null,
            'iframe_url' => $gatewayResponse['url'] ?? null,
            'provider_payload' => $gatewayResponse['raw'] ?? null,
            'meta' => [
                'gateway_request' => $gatewayRequest,
            ],
        ]);
    }

    public function recordPaymobCallbackResult(Order $order, array $result, ?int $amountCents = null): Payment
    {
        return DB::transaction(function () use ($order, $result, $amountCents) {
            $providerOrderId = $result['provider_order_id'] ?? null;
            $providerTransactionId = $result['provider_transaction_id'] ?? null;

            $paymentQuery = Payment::query()
                ->where('order_id', $order->id)
                ->where('provider', 'paymob');

            $payment = null;
            if ($providerTransactionId) {
                $payment = (clone $paymentQuery)->where('provider_transaction_id', $providerTransactionId)->first();
            }
            if (! $payment && $providerOrderId) {
                $payment = (clone $paymentQuery)->where('provider_order_id', $providerOrderId)->latest()->first();
            }
            if (! $payment) {
                $payment = (clone $paymentQuery)->latest()->first();
            }

            if (! $payment) {
                $payment = Payment::query()->create([
                    'order_id' => $order->id,
                    'provider' => 'paymob',
                    'method' => 'card',
                    'amount' => (float) $order->total_amount,
                    'currency' => (string) config('paymob.currency', 'EGP'),
                    'status' => Payment::STATUS_PENDING,
                ]);
            }

            $success = (bool) ($result['success'] ?? false);

            $meta = $payment->meta ?? [];
            if ($amountCents !== null) {
                $meta['amount_cents'] = (int) $amountCents;
            }

            if ($success) {
                $payment->fill([
                    'status' => Payment::STATUS_CONFIRMED,
                    'reference' => $providerTransactionId ?: ($providerOrderId ?: $payment->reference),
                    'provider_order_id' => $providerOrderId ?: $payment->provider_order_id,
                    'provider_transaction_id' => $providerTransactionId ?: $payment->provider_transaction_id,
                    'paid_at' => $payment->paid_at ?? now(),
                    'provider_payload' => $result['raw'] ?? $payment->provider_payload,
                    'meta' => $meta,
                ])->save();

                $order->update([
                    'payment_status' => 'payment_confirmed',
                    'payment_reference' => $providerTransactionId ?: ($providerOrderId ?: $order->payment_reference),
                ]);

                if ($order->status === Order::STATUS_AWAITING_PAYMENT) {
                    $order->transitionTo(
                        Order::STATUS_PENDING_VENDOR_PREPARATION,
                        'payment',
                        null,
                        'payment_confirmed',
                        'Payment confirmed via Paymob.',
                        [
                            'provider' => 'paymob',
                            'provider_order_id' => $providerOrderId,
                            'provider_transaction_id' => $providerTransactionId,
                        ]
                    );
                }

                $this->redeemCouponIfNeeded($order, $payment);
                $this->creditVendorWalletIfNeeded($order, $payment);

                return $payment;
            }

            $payment->fill([
                'status' => Payment::STATUS_FAILED,
                'reference' => $providerTransactionId ?: ($providerOrderId ?: $payment->reference),
                'provider_order_id' => $providerOrderId ?: $payment->provider_order_id,
                'provider_transaction_id' => $providerTransactionId ?: $payment->provider_transaction_id,
                'failed_at' => $payment->failed_at ?? now(),
                'provider_payload' => $result['raw'] ?? $payment->provider_payload,
                'meta' => $meta,
            ])->save();

            $order->update(['payment_status' => 'unpaid']);

            return $payment;
        });
    }

    private function creditVendorWalletIfNeeded(Order $order, Payment $payment): void
    {
        $meta = $payment->meta ?? [];
        if (($meta['vendor_wallet_credited'] ?? false) === true) {
            return;
        }

        $amount = (float) $order->order_cost;
        if ($amount <= 0) {
            $meta['vendor_wallet_credited'] = true;
            $meta['vendor_wallet_credit_skipped'] = 'order_cost_zero';
            $payment->meta = $meta;
            $payment->save();
            return;
        }

        $wallet = Wallet::forOwner(Vendor::class, (int) $order->vendor_id);
        $tx = $wallet->credit(
            $amount,
            'Payment confirmed for order ' . ($order->order_number ?? ('#' . $order->id)),
            (int) $order->id,
            null
        );

        $meta['vendor_wallet_credited'] = true;
        $meta['vendor_wallet_transaction_id'] = $tx->id;
        $payment->meta = $meta;
        $payment->save();
    }

    private function redeemCouponIfNeeded(Order $order, Payment $payment): void
    {
        if (! $order->coupon_id || $order->coupon_redeemed_at) {
            return;
        }

        $coupon = Coupon::query()->lockForUpdate()->find($order->coupon_id);
        if (! $coupon) {
            return;
        }

        // Protect against overuse in race conditions.
        if ($coupon->usage_limit !== null && $coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit) {
            $meta = $payment->meta ?? [];
            $meta['coupon_redeem_failed'] = 'usage_limit_reached';
            $payment->meta = $meta;
            $payment->save();
            return;
        }

        $coupon->increment('used_count');
        $order->update(['coupon_redeemed_at' => now()]);
    }
}
