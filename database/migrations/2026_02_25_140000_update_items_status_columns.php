<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('items')) {
            return;
        }

        if (Schema::hasColumn('items', 'approval_status') && Schema::hasColumn('items', 'availability_status')) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('max_orders_per_day');
            $table->enum('availability_status', ['paused', 'published'])->default('paused')->after('approval_status');
        });

        if (Schema::hasColumn('items', 'status')) {
            DB::table('items')->whereIn('status', ['pending', 'approved', 'rejected'])->update([
                'approval_status' => DB::raw('status'),
            ]);

            DB::table('items')->whereIn('status', ['paused', 'published'])->update([
                'availability_status' => DB::raw('status'),
            ]);

            Schema::table('items', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('max_orders_per_day');
        });

        DB::table('items')->whereNotNull('approval_status')->update([
            'status' => DB::raw('approval_status'),
        ]);

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['approval_status', 'availability_status']);
        });
    }
};
