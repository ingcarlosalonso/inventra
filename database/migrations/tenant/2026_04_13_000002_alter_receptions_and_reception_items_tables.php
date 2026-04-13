<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add supplier_invoice if not already present (fresh installs have it from the create migration)
        if (! Schema::hasColumn('receptions', 'supplier_invoice')) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->string('supplier_invoice')->nullable()->after('user_id');
            });
        }

        // Convert received_at from timestamp to date if it is still a timestamp type
        $columns = Schema::getColumns('receptions');
        $receivedAtColumn = collect($columns)->firstWhere('name', 'received_at');
        $isTimestamp = $receivedAtColumn && str_contains($receivedAtColumn['type_name'] ?? $receivedAtColumn['type'] ?? '', 'timestamp');

        if ($isTimestamp) {
            Schema::table('receptions', function (Blueprint $table) {
                $table->date('received_at_date')->nullable()->after('notes');
            });

            DB::statement('UPDATE receptions SET received_at_date = DATE(received_at)');

            Schema::table('receptions', function (Blueprint $table) {
                $table->dropColumn('received_at');
            });

            Schema::table('receptions', function (Blueprint $table) {
                $table->renameColumn('received_at_date', 'received_at');
            });
        }

        // Rebuild reception_items only if it still uses product_id (old structure)
        if (Schema::hasColumn('reception_items', 'product_id')) {
            Schema::drop('reception_items');

            Schema::create('reception_items', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('reception_id')->constrained('receptions')->cascadeOnDelete();
                $table->foreignId('product_presentation_id')->constrained('product_presentations')->restrictOnDelete();
                $table->decimal('quantity', 10, 3);
                $table->decimal('unit_cost', 12, 2);
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
        Schema::table('receptions', function (Blueprint $table) {
            $table->dropColumn('supplier_invoice');
            $table->timestamp('received_at_restore')->nullable();
        });

        DB::statement('UPDATE receptions SET received_at_restore = received_at');

        Schema::table('receptions', function (Blueprint $table) {
            $table->dropColumn('received_at');
        });

        Schema::table('receptions', function (Blueprint $table) {
            $table->renameColumn('received_at_restore', 'received_at');
        });

        Schema::drop('reception_items');

        Schema::create('reception_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('receptions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }
};
