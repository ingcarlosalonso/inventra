<?php

namespace Tests\Unit\Models\Module;

use App\Models\Module;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        self::createModuleTables();
    }

    public function test_it_has_expected_columns(): void
    {
        $expected = ['id', 'key', 'name', 'description', 'sort_order', 'created_at', 'updated_at'];
        $actual = Schema::getColumnListing('modules');

        sort($expected);
        sort($actual);

        $this->assertEquals($expected, $actual);
    }

    public function test_it_extends_from_eloquent_model(): void
    {
        $module = Module::factory()->create();

        $this->assertInstanceOf(Module::class, $module);
    }

    public function test_it_uses_the_mysql_connection(): void
    {
        $module = new Module;

        $this->assertEquals('mysql', $module->getConnectionName());
    }
}
