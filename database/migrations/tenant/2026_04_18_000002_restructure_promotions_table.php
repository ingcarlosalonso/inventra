<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['description', 'price', 'starts_at', 'ends_at']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
            $table->decimal('sale_price', 10, 2)->nullable()->after('code');
        });

        Schema::drop('product_promotion');

        Schema::create('promotion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->integer('quantity')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_items');

        Schema::create('product_promotion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 10, 3)->default(1);
            $table->timestamps();
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['code', 'sale_price']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->decimal('price', 12, 2)->default(0)->after('description');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
        });
    }
};
