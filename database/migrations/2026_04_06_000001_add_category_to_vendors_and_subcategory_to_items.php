<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendors') && ! Schema::hasColumn('vendors', 'category_id')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->after('restaurant_name')->constrained()->nullOnDelete();
            });
        }

        if (Schema::hasTable('items') && ! Schema::hasColumn('items', 'subcategory_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->foreignId('subcategory_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('items') && Schema::hasColumn('items', 'subcategory_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subcategory_id');
            });
        }

        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'category_id')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_id');
            });
        }
    }
};
