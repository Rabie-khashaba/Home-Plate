<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subcategory_vendor')) {
            Schema::create('subcategory_vendor', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('subcategory_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['vendor_id', 'subcategory_id']);
            });
        }

        if (Schema::hasTable('items')) {
            $rows = DB::table('items')
                ->whereNotNull('vendor_id')
                ->whereNotNull('subcategory_id')
                ->select('vendor_id', 'subcategory_id')
                ->distinct()
                ->get();

            foreach ($rows as $row) {
                DB::table('subcategory_vendor')->updateOrInsert(
                    [
                        'vendor_id' => $row->vendor_id,
                        'subcategory_id' => $row->subcategory_id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategory_vendor');
    }
};
