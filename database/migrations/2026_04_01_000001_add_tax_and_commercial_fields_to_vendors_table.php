<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'tax_card_number')) {
                $table->string('tax_card_number')->nullable()->after('restaurant_info');
            }
            if (! Schema::hasColumn('vendors', 'tax_card_image')) {
                $table->string('tax_card_image')->nullable()->after('tax_card_number');
            }
            if (! Schema::hasColumn('vendors', 'commercial_register_number')) {
                $table->string('commercial_register_number')->nullable()->after('tax_card_image');
            }
            if (! Schema::hasColumn('vendors', 'commercial_register_image')) {
                $table->string('commercial_register_image')->nullable()->after('commercial_register_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $dropColumns = [];
            foreach ([
                'tax_card_number',
                'tax_card_image',
                'commercial_register_number',
                'commercial_register_image',
            ] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
