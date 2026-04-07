<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('item_subcategory')) {
            Schema::create('item_subcategory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('item_id')->constrained()->cascadeOnDelete();
                $table->foreignId('subcategory_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['item_id', 'subcategory_id']);
            });
        }

        if (Schema::hasTable('items')) {
            $rows = DB::table('items')
                ->whereNotNull('subcategory_id')
                ->select('id as item_id', 'subcategory_id')
                ->distinct()
                ->get();

            foreach ($rows as $row) {
                DB::table('item_subcategory')->updateOrInsert(
                    [
                        'item_id' => $row->item_id,
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
        Schema::dropIfExists('item_subcategory');
    }
};
