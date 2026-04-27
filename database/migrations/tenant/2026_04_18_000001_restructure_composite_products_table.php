<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('composite_products', function (Blueprint $table) {
            $table->dropColumn(['description', 'price']);
        });

        Schema::table('composite_products', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
        });

        Schema::drop('composite_product_product');

        Schema::create('composite_product_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composite_product_id')->constrained('composite_products')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->integer('quantity')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composite_product_items');

        Schema::create('composite_product_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('composite_product_id')->constrained('composite_products')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 10, 3)->default(1);
            $table->timestamps();
        });

        Schema::table('composite_products', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('composite_products', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->decimal('price', 12, 2)->default(0)->after('description');
        });
    }
};
