<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_movements', function (Blueprint $table) {
            $table->foreignId('product_presentation_id')
                ->after('product_id')
                ->constrained('product_presentations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_movements', function (Blueprint $table) {
            $table->dropForeign(['product_presentation_id']);
            $table->dropColumn('product_presentation_id');
        });
    }
};
