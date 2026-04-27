<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('sales', function (Blueprint $table) {
            $table->dropForeign(['daily_cash_id']);
            $table->dropColumn('daily_cash_id');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('sales', function (Blueprint $table) {
            $table->foreignId('daily_cash_id')->nullable()->constrained('daily_cashes')->nullOnDelete()->after('currency_id');
        });
    }
};
