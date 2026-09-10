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
        Schema::connection('tenant')->create('mercado_pago_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->foreignId('point_of_sale_id')->constrained('points_of_sale')->restrictOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('mercado_pago_order_id')->unique();
            $table->string('external_reference');
            $table->string('status');
            $table->decimal('amount', 12, 2);
            $table->string('mercado_pago_payment_id')->nullable();
            $table->string('failure_detail')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('mercado_pago_orders');
    }
};
