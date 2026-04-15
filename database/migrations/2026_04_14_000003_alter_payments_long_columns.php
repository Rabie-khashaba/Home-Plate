<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_key')) {
                $table->text('payment_key')->nullable()->change();
            }
            if (Schema::hasColumn('payments', 'iframe_url')) {
                $table->text('iframe_url')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_key')) {
                $table->string('payment_key')->nullable()->change();
            }
            if (Schema::hasColumn('payments', 'iframe_url')) {
                $table->string('iframe_url')->nullable()->change();
            }
        });
    }
};

