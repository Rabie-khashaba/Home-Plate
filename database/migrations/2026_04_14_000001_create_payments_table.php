<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('provider', 50); // e.g. paymob
            $table->string('method', 50)->nullable(); // e.g. card

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EGP');
            $table->string('status', 30)->default('pending');

            $table->string('reference')->nullable();

            $table->string('provider_order_id')->nullable();
            $table->string('provider_transaction_id')->nullable();
            $table->text('payment_key')->nullable();
            $table->text('iframe_url')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->json('provider_payload')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['provider', 'status']);
            $table->index(['order_id', 'status']);
            $table->unique(['provider', 'provider_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
