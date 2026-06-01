<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // ── sales: add discount_type and discount_value ────────────────────
        if (! Schema::hasColumn('sales', 'discount_type')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('discount_type', 20)->nullable()->after('subtotal');
                $table->decimal('discount_value', 12, 2)->default(0)->after('discount_type');
            });
        }

        // ── sale_items: rebuild with correct structure ─────────────────────
        if (Schema::hasColumn('sale_items', 'product_id')) {
            Schema::drop('sale_items');

            Schema::create('sale_items', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
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

        // ── payments: add uuid and audit columns ───────────────────────────
        if (! Schema::hasColumn('payments', 'uuid')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->uuid('uuid')->unique()->after('id');
            });

            // Populate uuid for existing rows
            DB::table('payments')->whereNull('uuid')->orWhere('uuid', '')->lazyById()->each(
                fn ($row) => DB::table('payments')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()])
            );
        }

        if (! Schema::hasColumn('payments', 'created_by')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('notes');
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value']);
        });

        Schema::drop('sale_items');

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'created_by', 'updated_by']);
        });
    }
};
