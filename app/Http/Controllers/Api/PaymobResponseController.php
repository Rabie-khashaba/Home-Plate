<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Order;
use App\Services\OrderPaymentRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class PaymobResponseController extends Controller
{
    public function handle(Request $request, PaymentGatewayInterface $paymentGateway, OrderPaymentRecorder $paymentRecorder): JsonResponse
    {
        $result = $paymentGateway->callBack($request);
        $orderId = $result['order_id'] ?? null;

        Storage::put(
            'paymob/api-response-' . now()->format('YmdHis') . '.json',
            json_encode(['result' => $result, 'query' => $request->query(), 'body' => $request->all()])
        );

        if (! $orderId) {
            return response()->json(['message' => 'Missing merchant order id.'], 400);
        }

        /** @var \App\Models\Order|null $order */
        $order = Order::query()->find($orderId);
        if (! $order) {
            return response()->json(['message' => 'Order not found.'], 404);
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

                return response()->json([
                    'message' => 'Amount mismatch.',
                    'order_id' => $order->id,
                ], 422);
            }
        }

        if (($result['success'] ?? false) === true) {
            $paymentRecorder->recordPaymobCallbackResult($order, $result, $amountCents !== null ? (int) $amountCents : null);

            return response()->json([
                'message' => 'Payment successful.',
                'order_id' => $order->id,
            ]);
        }

        $paymentRecorder->recordPaymobCallbackResult($order, $result, $amountCents !== null ? (int) $amountCents : null);

        return response()->json([
            'message' => 'Payment failed.',
            'order_id' => $order->id,
        ], 422);
    }
}
