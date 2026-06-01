<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_presentations')) {
            Schema::create('product_presentations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('presentation_id')->constrained('presentations')->restrictOnDelete();
                $table->decimal('price', 12, 2)->default(0);
                $table->decimal('stock', 10, 3)->default(0);
                $table->decimal('min_stock', 10, 3)->default(0);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['product_id', 'presentation_id', 'deleted_at'], 'pp_product_presentation_deleted_unique');
            });
        }

        if (Schema::hasColumn('products', 'price')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'presentation_id')) {
                    $table->dropForeign(['presentation_id']);
                    $table->dropColumn('presentation_id');
                }
                $table->dropColumn(['price', 'stock', 'min_stock']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_presentations');

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('presentation_id')->nullable()->constrained('presentations')->nullOnDelete();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('stock', 10, 3)->default(0);
            $table->decimal('min_stock', 10, 3)->default(0);
        });
    }
};
