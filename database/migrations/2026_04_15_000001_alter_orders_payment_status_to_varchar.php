<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'payment_status')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `orders` MODIFY `payment_status` VARCHAR(30) NULL");
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE orders ALTER COLUMN payment_status TYPE VARCHAR(30)');
            return;
        }

        // sqlite / others: do nothing (schema rebuild would be required)
    }

    public function down(): void
    {
        // no-op (keep it flexible)
    }
};

