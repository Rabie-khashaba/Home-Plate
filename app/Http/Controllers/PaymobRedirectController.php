<?php

namespace App\Http\Controllers;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Order;
use App\Services\OrderPaymentRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class PaymobRedirectController extends Controller
{
    public function response(Request $request, PaymentGatewayInterface $paymentGateway, OrderPaymentRecorder $paymentRecorder)
    {
        $result = $paymentGateway->callBack($request);
        $orderId = $result['order_id'] ?? null;

        Storage::put(
            'paymob/redirect-' . now()->format('YmdHis') . '.json',
            json_encode(['result' => $result, 'query' => $request->query(), 'body' => $request->all()])
        );

        if (! $orderId) {
            return redirect()->route('payment.failed')->withErrors([
                'payment' => 'لم يتم تحديد الطلب من رد Paymob.',
            ]);
        }

        /** @var \App\Models\Order|null $order */
        $order = Order::query()->find($orderId);
        if (! $order) {
            return redirect()->route('payment.failed')->withErrors([
                'payment' => 'الطلب غير موجود.',
            ]);
        }

        $raw = $result['raw'] ?? [];
        $amountCents = $request->input('amount_cents')
            ?? Arr::get($raw, 'amount_cents')
            ?? Arr::get($raw, 'obj.amount_cents')
            ?? null;

        if ($amountCents !== null) {
            $expected = (int) round(((float) $order->total_amount) * 100);
            if ((int) $amountCents !== $expected) {
                $result['success'] = false;
                $paymentRecorder->recordPaymobCallbackResult($order, $result, (int) $amountCents);

                return redirect()->route('payment.failed')->withErrors([
                    'payment' => 'قيمة الدفع لا تطابق قيمة الطلب.',
                ]);
            }
        }

        if (($result['success'] ?? false) === true) {
            $paymentRecorder->recordPaymobCallbackResult($order, $result, $amountCents !== null ? (int) $amountCents : null);

            return redirect()->route('payment.success');
        }

        $paymentRecorder->recordPaymobCallbackResult($order, $result, $amountCents !== null ? (int) $amountCents : null);

        return redirect()->route('payment.failed');
    }
}
