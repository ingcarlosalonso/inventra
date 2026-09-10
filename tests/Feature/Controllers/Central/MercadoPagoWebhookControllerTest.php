<?php

namespace Tests\Feature\Controllers\Central;

use App\Jobs\ProcessMercadoPagoWebhookJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MercadoPagoWebhookControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mercadopago.webhook_secret' => 'test-webhook-secret']);
    }

    private function signature(string $dataId, string $requestId, string $ts): string
    {
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";

        return hash_hmac('sha256', $manifest, 'test-webhook-secret');
    }

    public function test_rejects_missing_signature(): void
    {
        Queue::fake();

        $this->postJson('/webhooks/mercadopago?data_id=123&type=order', ['user_id' => 555])
            ->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    public function test_rejects_invalid_signature(): void
    {
        Queue::fake();

        $this->withHeaders([
            'x-request-id' => 'req-1',
            'x-signature' => 'ts=1000,v1=deadbeef',
        ])->postJson('/webhooks/mercadopago?data_id=123&type=order', ['user_id' => 555])
            ->assertUnauthorized();

        Queue::assertNothingPushed();
    }

    public function test_accepts_valid_signature_and_dispatches_job(): void
    {
        Queue::fake();

        $ts = (string) time();
        $signature = $this->signature('123', 'req-1', $ts);

        $this->withHeaders([
            'x-request-id' => 'req-1',
            'x-signature' => "ts={$ts},v1={$signature}",
        ])->postJson('/webhooks/mercadopago?data_id=123&type=order', ['user_id' => 555, 'type' => 'order'])
            ->assertNoContent();

        Queue::assertPushed(ProcessMercadoPagoWebhookJob::class, fn ($job) => $job->mercadoPagoUserId === '555' && $job->mercadoPagoOrderId === '123'
        );
    }

    public function test_ignores_unsupported_topic_but_still_acknowledges(): void
    {
        Queue::fake();

        $ts = (string) time();
        $signature = $this->signature('123', 'req-1', $ts);

        $this->withHeaders([
            'x-request-id' => 'req-1',
            'x-signature' => "ts={$ts},v1={$signature}",
        ])->postJson('/webhooks/mercadopago?data_id=123&type=merchant_order', ['user_id' => 555, 'type' => 'merchant_order'])
            ->assertNoContent();

        Queue::assertNothingPushed();
    }

    public function test_ignores_payment_topic_but_still_acknowledges(): void
    {
        Queue::fake();

        $ts = (string) time();
        $signature = $this->signature('123', 'req-1', $ts);

        // "payment" notifications carry a payment id in data_id, not an order id — this
        // integration only creates/tracks Mercado Pago orders, so it must not be dispatched.
        $this->withHeaders([
            'x-request-id' => 'req-1',
            'x-signature' => "ts={$ts},v1={$signature}",
        ])->postJson('/webhooks/mercadopago?data_id=123&type=payment', ['user_id' => 555, 'type' => 'payment'])
            ->assertNoContent();

        Queue::assertNothingPushed();
    }
}
