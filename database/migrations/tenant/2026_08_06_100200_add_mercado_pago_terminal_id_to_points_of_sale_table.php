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
        Schema::connection('tenant')->table('points_of_sale', function (Blueprint $table) {
            $table->string('mercado_pago_terminal_id')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('points_of_sale', function (Blueprint $table) {
            $table->dropColumn('mercado_pago_terminal_id');
        });
    }
};
