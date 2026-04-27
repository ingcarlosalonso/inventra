<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('saleable_type')->nullable()->after('product_presentation_id');
            $table->unsignedBigInteger('saleable_id')->nullable()->after('saleable_type');
        });

        DB::connection('tenant')->statement(
            "UPDATE order_items SET saleable_type = 'product_presentation', saleable_id = product_presentation_id WHERE product_presentation_id IS NOT NULL"
        );
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['saleable_type', 'saleable_id']);
        });
    }
};
