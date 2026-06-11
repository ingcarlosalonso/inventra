<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presentation_types', function (Blueprint $table) {
            $table->string('abbreviation', 20)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('presentation_types', function (Blueprint $table) {
            $table->dropColumn('abbreviation');
        });
    }
};
