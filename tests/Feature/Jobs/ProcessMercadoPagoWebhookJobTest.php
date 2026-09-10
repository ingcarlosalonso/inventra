<?php

namespace Tests\Feature\Jobs;

use App\Actions\MercadoPago\ProcessMercadoPagoWebhookAction;
use App\Jobs\ProcessMercadoPagoWebhookJob;
use App\Models\Module;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Multitenancy\Jobs\NotTenantAware;
use Tests\TestCase;

class ProcessMercadoPagoWebhookJobTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = ['mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        self::createTenantsTable();
        self::createModuleTables();
    }

    public function test_job_implements_not_tenant_aware(): void
    {
        $this->assertInstanceOf(NotTenantAware::class, new ProcessMercadoPagoWebhookJob('555', 'ORDER1'));
    }

    public function test_job_has_correct_properties(): void
    {
        $job = new ProcessMercadoPagoWebhookJob('555', 'ORDER1');

        $this->assertSame('555', $job->mercadoPagoUserId);
        $this->assertSame('ORDER1', $job->mercadoPagoOrderId);
    }

    public function test_handle_does_nothing_when_no_tenant_matches(): void
    {
        $job = new ProcessMercadoPagoWebhookJob('does-not-exist', 'ORDER1');

        $job->handle();

        $this->assertTrue(true);
    }

    public function test_handle_skips_processing_when_the_module_is_not_enabled(): void
    {
        Tenant::create([
            'name' => 'Test Tenant',
            'domain' => uniqid('mp-job-').'.test',
            'database' => 'in_ventra_tenant_'.uniqid(),
            'status' => 'active',
            'mercado_pago_user_id' => 'MPUSER999',
        ]);

        Module::query()->firstOrCreate(['key' => 'mercado_pago'], ['name' => 'Mercado Pago']);

        // No TenantModule row is created for this tenant, so the module is not contracted.
        // The Action must never run — an in-flight charge shouldn't be able to register a
        // payment for a tenant whose Mercado Pago module was disabled mid-flight.
        $this->mock(ProcessMercadoPagoWebhookAction::class)->shouldNotReceive('execute');

        $job = new ProcessMercadoPagoWebhookJob('MPUSER999', 'ORDER1');

        $job->handle();

        $this->assertTrue(true);
    }
}
