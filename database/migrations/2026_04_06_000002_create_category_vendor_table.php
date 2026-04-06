<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_vendor')) {
            Schema::create('category_vendor', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['vendor_id', 'category_id']);
            });
        }

        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'category_id')) {
            $rows = DB::table('vendors')
                ->whereNotNull('category_id')
                ->select('id as vendor_id', 'category_id')
                ->get();

            foreach ($rows as $row) {
                DB::table('category_vendor')->updateOrInsert(
                    [
                        'vendor_id' => $row->vendor_id,
                        'category_id' => $row->category_id,
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
        Schema::dropIfExists('category_vendor');
    }
};
