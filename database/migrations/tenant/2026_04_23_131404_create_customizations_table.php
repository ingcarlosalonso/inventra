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
        Schema::connection('tenant')->create('customizations', function (Blueprint $table) {
            $table->id();
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->default('#3B82F6');
            $table->string('secondary_color', 7)->default('#1E40AF');
            $table->string('accent_color', 7)->default('#F59E0B');
            $table->string('font_family')->default('Inter');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('customizations');
    }
};
