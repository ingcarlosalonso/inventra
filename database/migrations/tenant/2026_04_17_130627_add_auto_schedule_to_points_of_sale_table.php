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
        Schema::table('points_of_sale', function (Blueprint $table) {
            $table->time('auto_open_time')->nullable()->after('address');
            $table->time('auto_close_time')->nullable()->after('auto_open_time');
        });
    }

    public function down(): void
    {
        Schema::table('points_of_sale', function (Blueprint $table) {
            $table->dropColumn(['auto_open_time', 'auto_close_time']);
        });
    }
};
