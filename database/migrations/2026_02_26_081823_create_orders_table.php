<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained('deliveries')->nullOnDelete();
            $table->decimal('order_cost', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_method', ['vodafone_cash', 'instapay', 'visa']);
            $table->enum('payment_status', ['paid', 'failed', 'refunded'])->default('paid');
            $table->string('payment_reference')->nullable();
            $table->text('delivery_address');
            $table->timestamp('ordered_at')->nullable();

            $table->enum('status', [
                'pending_vendor_preparation',
                'searching_delivery',
                'delivery_assigned',
                'ready_for_pickup',
                'handover_pending_confirmation',
                'picked_up',
                'out_for_delivery',
                'delivered',
                'cancelled',
            ])->index();
            $table->string('delivery_pin', 6);
            $table->timestamp('pin_verified_at')->nullable();

            $table->timestamp('started_cooking_at')->nullable();
            $table->timestamp('delivery_requested_at')->nullable();
            $table->timestamp('delivery_accepted_at')->nullable();
            $table->timestamp('ready_for_pickup_at')->nullable();
            $table->timestamp('vendor_handover_confirmed_at')->nullable();
            $table->timestamp('delivery_pickup_confirmed_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
