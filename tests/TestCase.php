<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    private static bool $tenantDbMigrated = false;

    protected static function createReleaseTables(): void
    {
        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('releases')) {
            return;
        }

        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('version')->unique();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('release_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->text('title');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });
    }

    protected static function migrateTenantDb(): void
    {
        if (self::$tenantDbMigrated) {
            return;
        }

        Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        self::$tenantDbMigrated = true;
    }
}
