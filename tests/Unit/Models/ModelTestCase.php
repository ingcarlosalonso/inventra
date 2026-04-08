<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class ModelTestCase extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['tenant'];

    private static bool $tenantMigrationsRun = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! self::$tenantMigrationsRun) {
            Artisan::call('migrate:fresh', [
                '--database' => 'tenant',
                '--path'     => 'database/migrations/tenant',
                '--force'    => true,
            ]);

            self::$tenantMigrationsRun = true;
        }
    }

    /**
     * @param  string[]  $expectedColumns
     */
    protected function assertHasExpectedColumns(string $table, array $expectedColumns): void
    {
        $actual = Schema::connection('tenant')->getColumnListing($table);

        sort($expectedColumns);
        sort($actual);

        $this->assertEquals(
            $expectedColumns,
            $actual,
            "Table [{$table}] does not have the expected columns."
        );
    }
}
