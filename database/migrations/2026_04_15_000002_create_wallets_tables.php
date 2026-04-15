<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->string('owner_type');
                $table->unsignedBigInteger('owner_id');
                $table->decimal('balance', 10, 2)->default(0);
                $table->decimal('total_earned', 10, 2)->default(0);
                $table->decimal('total_withdrawn', 10, 2)->default(0);
                $table->timestamps();

                $table->unique(['owner_type', 'owner_id']);
                $table->index(['owner_type', 'owner_id']);
            });
        }

        if (! Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
                $table->string('type', 10); // credit | debit
                $table->decimal('amount', 10, 2);
                $table->decimal('balance_after', 10, 2);
                $table->string('description', 255);
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['wallet_id', 'type']);
                $table->index(['order_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};

