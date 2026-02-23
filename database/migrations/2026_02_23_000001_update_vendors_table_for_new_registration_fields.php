<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Renames old columns to the new naming convention (may require doctrine/dbal).
            if (Schema::hasColumn('vendors', 'name') && ! Schema::hasColumn('vendors', 'full_name')) {
                $table->renameColumn('name', 'full_name');
            }

            if (Schema::hasColumn('vendors', 'logo') && ! Schema::hasColumn('vendors', 'main_photo')) {
                $table->renameColumn('logo', 'main_photo');
            }

            if (Schema::hasColumn('vendors', 'address') && ! Schema::hasColumn('vendors', 'delivery_address')) {
                $table->renameColumn('address', 'delivery_address');
            }
        });

        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'id_front')) {
                $table->string('id_front')->nullable()->after('password');
            }

            if (! Schema::hasColumn('vendors', 'id_back')) {
                $table->string('id_back')->nullable()->after('id_front');
            }

            if (! Schema::hasColumn('vendors', 'restaurant_info')) {
                $table->text('restaurant_info')->nullable()->after('id_back');
            }

            if (! Schema::hasColumn('vendors', 'restaurant_name')) {
                $table->string('restaurant_name')->nullable()->after('main_photo');
            }

            if (! Schema::hasColumn('vendors', 'kitchen_photo_1')) {
                $table->string('kitchen_photo_1')->nullable()->after('location');
            }

            if (! Schema::hasColumn('vendors', 'kitchen_photo_2')) {
                $table->string('kitchen_photo_2')->nullable()->after('kitchen_photo_1');
            }

            if (! Schema::hasColumn('vendors', 'kitchen_photo_3')) {
                $table->string('kitchen_photo_3')->nullable()->after('kitchen_photo_2');
            }

            if (! Schema::hasColumn('vendors', 'working_time')) {
                $table->string('working_time')->nullable()->after('kitchen_photo_3');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $dropColumns = [];

            foreach ([
                'id_front',
                'id_back',
                'restaurant_info',
                'restaurant_name',
                'kitchen_photo_1',
                'kitchen_photo_2',
                'kitchen_photo_3',
                'working_time',
            ] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'full_name') && ! Schema::hasColumn('vendors', 'name')) {
                $table->renameColumn('full_name', 'name');
            }

            if (Schema::hasColumn('vendors', 'main_photo') && ! Schema::hasColumn('vendors', 'logo')) {
                $table->renameColumn('main_photo', 'logo');
            }

            if (Schema::hasColumn('vendors', 'delivery_address') && ! Schema::hasColumn('vendors', 'address')) {
                $table->renameColumn('delivery_address', 'address');
            }
        });
    }
};
