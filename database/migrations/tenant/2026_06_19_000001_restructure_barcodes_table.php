<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing barcode records were product-level and cannot be migrated
        // to presentation-level without manual mapping, so we clear them.
        DB::table('barcodes')->delete();

        Schema::table('barcodes', function (Blueprint $table) {
            $table->foreignId('product_presentation_id')
                ->after('id')
                ->constrained('product_presentations')
                ->cascadeOnDelete();
        });

        Schema::table('barcodes', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }

    public function down(): void
    {
        DB::table('barcodes')->delete();

        Schema::table('barcodes', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->after('id')
                ->constrained('products')
                ->cascadeOnDelete();
        });

        Schema::table('barcodes', function (Blueprint $table) {
            $table->dropForeign(['product_presentation_id']);
            $table->dropColumn('product_presentation_id');
        });
    }
};
