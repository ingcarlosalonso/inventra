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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('email')->nullable()->after('domain');
            $table->string('contact_name')->nullable()->after('email');
            $table->enum('status', ['trial', 'active', 'suspended'])->default('trial')->after('contact_name');
            $table->string('plan')->nullable()->after('status');
            $table->date('expires_at')->nullable()->after('plan');
            $table->text('notes')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['email', 'contact_name', 'status', 'plan', 'expires_at', 'notes']);
        });
    }
};
