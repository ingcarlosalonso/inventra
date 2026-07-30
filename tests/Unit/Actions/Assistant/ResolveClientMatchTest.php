<?php

namespace Tests\Unit\Actions\Assistant;

use App\Actions\Assistant\ResolveClientMatch;
use App\Models\Client;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResolveClientMatchTest extends TestCase
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

    public function test_finds_active_client_by_partial_name(): void
    {
        $client = Client::factory()->create(['first_name' => 'Juan', 'last_name' => 'Perez']);

        $result = (new ResolveClientMatch)->execute('Juan');

        $this->assertCount(1, $result);
        $this->assertSame($client->id, $result->first()->id);
    }

    public function test_excludes_inactive_clients(): void
    {
        Client::factory()->create(['first_name' => 'Inactivo', 'is_active' => false]);

        $result = (new ResolveClientMatch)->execute('Inactivo');

        $this->assertCount(0, $result);
    }

    public function test_returns_multiple_matches_when_ambiguous(): void
    {
        $perez = Client::factory()->create(['first_name' => 'Juan', 'last_name' => 'Perez']);
        $gomez = Client::factory()->create(['first_name' => 'Juan', 'last_name' => 'Gomez']);

        $result = (new ResolveClientMatch)->execute('Juan');

        $this->assertGreaterThanOrEqual(2, $result->count());
        $this->assertTrue($result->pluck('id')->contains($perez->id));
        $this->assertTrue($result->pluck('id')->contains($gomez->id));
    }

    public function test_returns_empty_when_no_match(): void
    {
        $result = (new ResolveClientMatch)->execute('zzz_nonexistent_zzz');

        $this->assertCount(0, $result);
    }
}
