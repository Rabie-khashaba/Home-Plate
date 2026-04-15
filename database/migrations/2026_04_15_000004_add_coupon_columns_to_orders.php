<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code', 50)->nullable();
            }

            if (! Schema::hasColumn('orders', 'coupon_type')) {
                $table->string('coupon_type', 20)->nullable(); // percentage | fixed
            }

            if (! Schema::hasColumn('orders', 'coupon_value')) {
                $table->decimal('coupon_value', 10, 2)->nullable();
            }

            if (! Schema::hasColumn('orders', 'coupon_discount_percent')) {
                $table->decimal('coupon_discount_percent', 5, 2)->nullable();
            }

            if (! Schema::hasColumn('orders', 'coupon_discount_amount')) {
                $table->decimal('coupon_discount_amount', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('orders', 'coupon_redeemed_at')) {
                $table->timestamp('coupon_redeemed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'coupon_id')) {
                $table->dropConstrainedForeignId('coupon_id');
            }

            $cols = array_values(array_filter([
                Schema::hasColumn('orders', 'coupon_code') ? 'coupon_code' : null,
                Schema::hasColumn('orders', 'coupon_type') ? 'coupon_type' : null,
                Schema::hasColumn('orders', 'coupon_value') ? 'coupon_value' : null,
                Schema::hasColumn('orders', 'coupon_discount_percent') ? 'coupon_discount_percent' : null,
                Schema::hasColumn('orders', 'coupon_discount_amount') ? 'coupon_discount_amount' : null,
                Schema::hasColumn('orders', 'coupon_redeemed_at') ? 'coupon_redeemed_at' : null,
            ]));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};

