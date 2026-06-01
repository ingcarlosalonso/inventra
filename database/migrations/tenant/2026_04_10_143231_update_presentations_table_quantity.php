<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->dropColumn(['name', 'abbreviation']);
            $table->decimal('quantity', 10, 3)->after('presentation_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->dropColumn('quantity');
            $table->string('name')->after('presentation_type_id');
            $table->string('abbreviation', 20)->after('name');
        });
    }
};
