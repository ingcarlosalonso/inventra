<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── quotes: add missing columns ────────────────────────────────────────
        if (! Schema::hasColumn('quotes', 'discount_type')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->string('discount_type', 20)->nullable()->after('subtotal');
                $table->decimal('discount_value', 12, 2)->default(0)->after('discount_type');
            });
        }

        if (! Schema::hasColumn('quotes', 'sale_id')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete()->after('currency_id');
            });
        }

        if (! Schema::hasColumn('quotes', 'starts_at')) {
            Schema::table('quotes', function (Blueprint $table) {
                $table->date('starts_at')->nullable()->after('notes');
            });
        }

        // ── quote_items: rebuild with correct structure ────────────────────────
        if (Schema::hasColumn('quote_items', 'product_id')) {
            Schema::drop('quote_items');

            Schema::create('quote_items', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
                $table->foreignId('product_presentation_id')->nullable()->constrained('product_presentations')->nullOnDelete();
                $table->string('description');
                $table->decimal('quantity', 10, 3);
                $table->decimal('unit_price', 12, 2);
                $table->string('discount_type', 20)->nullable();
                $table->decimal('discount_value', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('total', 12, 2);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'sale_id', 'starts_at']);
        });

        Schema::drop('quote_items');

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }
};
