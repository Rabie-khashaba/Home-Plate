<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 10, 2)->nullable();
            $table->unsignedInteger('prep_time_value');
            $table->enum('prep_time_unit', ['minutes', 'hours']);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('max_orders_per_day')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('availability_status', ['paused', 'published'])->default('paused');
            $table->json('photos');
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
