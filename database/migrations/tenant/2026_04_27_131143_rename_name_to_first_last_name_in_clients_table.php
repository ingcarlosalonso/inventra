<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('first_name')->after('uuid');
            $table->string('last_name')->after('first_name');
        });

        DB::table('clients')->update([
            'first_name' => DB::raw("SUBSTRING_INDEX(`name`, ' ', 1)"),
            'last_name' => DB::raw("TRIM(SUBSTR(`name`, LOCATE(' ', `name`)))"),
        ]);

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('name')->after('uuid');
        });

        DB::table('clients')->update([
            'name' => DB::raw("CONCAT(`first_name`, ' ', `last_name`)"),
        ]);

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
