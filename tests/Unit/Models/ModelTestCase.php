<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

abstract class ModelTestCase extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['tenant'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.tenant.database' => env('DB_TENANT_DATABASE', 'in_ventra_testing')]);
        DB::purge('tenant');
        DB::connection('tenant')->beginTransaction();

        self::migrateTenantDb();
    }

    protected function tearDown(): void
    {
        DB::connection('tenant')->rollBack();
        parent::tearDown();
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
