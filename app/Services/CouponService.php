<?php

namespace App\Services;

use App\Models\Coupon;
use Illuminate\Support\Str;

class CouponService
{
    /**
     * @return array{
     *   coupon: ?Coupon,
     *   discount_amount: float,
     *   discount_percent: ?float,
     *   message: ?string
     * }
     */
    public function validateAndCalculate(?string $code, float $orderCost, float $deliveryFee = 0.0): array
    {
        $code = trim((string) $code);
        if ($code === '') {
            return [
                'coupon' => null,
                'discount_amount' => 0.0,
                'discount_percent' => null,
                'message' => null,
            ];
        }

        $normalized = Str::upper($code);
        /** @var Coupon|null $coupon */
        $coupon = Coupon::query()->where('code', $normalized)->first();
        if (! $coupon) {
            return $this->invalid('كود الكوبون غير صحيح.');
        }

        if (! $coupon->is_active) {
            return $this->invalid('الكوبون غير مفعل.');
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return $this->invalid('الكوبون لم يبدأ بعد.');
        }

        if ($coupon->isExpired()) {
            return $this->invalid('الكوبون منتهي.');
        }

        if ($coupon->isUsageLimitReached()) {
            return $this->invalid('تم استهلاك الكوبون بالكامل.');
        }

        $baseTotal = round($orderCost + $deliveryFee, 2);
        if ($coupon->min_order_amount !== null && (float) $coupon->min_order_amount > 0 && $baseTotal < (float) $coupon->min_order_amount) {
            return $this->invalid('قيمة الطلب أقل من الحد الأدنى لاستخدام الكوبون.');
        }

        $subtotal = max((float) $orderCost, 0.0);

        $discount = 0.0;
        $discountPercent = null;

        if ($coupon->type === 'percentage') {
            $discountPercent = (float) $coupon->value;
            $discount = $subtotal * ($discountPercent / 100);
        } else {
            $discount = (float) $coupon->value;
            if ($subtotal > 0) {
                $discountPercent = ($discount / $subtotal) * 100;
            }
        }

        $discount = min($discount, $subtotal);

        if ($coupon->max_discount !== null && (float) $coupon->max_discount > 0) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        $discount = round($discount, 2);
        $discountPercent = $discountPercent !== null ? round((float) $discountPercent, 2) : null;

        return [
            'coupon' => $coupon,
            'discount_amount' => $discount,
            'discount_percent' => $discountPercent,
            'message' => null,
        ];
    }

    private function invalid(string $message): array
    {
        return [
            'coupon' => null,
            'discount_amount' => 0.0,
            'discount_percent' => null,
            'message' => $message,
        ];
    }
}

