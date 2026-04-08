<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('push_notifications')) {
            return;
        }

        Schema::create('push_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('target_audience');
            $table->string('type');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('recurrence_time')->nullable();
            $table->unsignedTinyInteger('recurrence_day_of_week')->nullable();
            $table->unsignedTinyInteger('recurrence_week_of_month')->nullable();
            $table->unsignedTinyInteger('recurrence_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->json('extra_data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notifications');
    }
};
