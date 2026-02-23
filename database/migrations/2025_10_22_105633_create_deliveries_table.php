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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('email')->nullable();
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('photo')->nullable();

            $table->foreignId('city_id')->constrained()->onDelete('cascade');
            $table->foreignId('area_id')->constrained()->onDelete('cascade');

            $table->string('drivers_license')->nullable();
            $table->string('national_id')->nullable();
            $table->string('vehicle_photo')->nullable();
            $table->string('vehicle_type')->nullable();

            // store vehicle_license as JSON array: ['front' => 'path', 'back' => 'path']
            $table->json('vehicle_license')->nullable();

            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->boolean('is_active')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
