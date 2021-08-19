<?php

namespace Vanthao03596\PaddleWebhooks\Tests;

use Illuminate\Support\Facades\Event;
use Vanthao03596\PaddleWebhooks\ProcessPaddleWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

class PaddleWebhookCallTest extends TestCase
{
    /** @var \Vanthao03596\PaddleWebhooks\ProcessPaddleWebhookJob */
    public $processPaddleWebhookJob;

    /** @var \Spatie\WebhookClient\Models\WebhookCall */
    public $webhookCall;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['paddle-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        $this->webhookCall = WebhookCall::create([
            'name' => 'paddle',
            'payload' => ['alert_name' => 'my_type', 'name' => 'value'],
        ]);

        $this->processPaddleWebhookJob = new ProcessPaddleWebhookJob($this->webhookCall);

    }

    /** @test */
    public function it_will_fire_off_the_configured_job()
    {
        $this->processPaddleWebhookJob->handle();

        $this->assertEquals($this->webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function it_will_not_dispatch_a_job_for_another_type()
    {
        config(['paddle-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processPaddleWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured()
    {
        config(['paddle-webhooks.jobs' => []]);

        $this->processPaddleWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_dispatch_events_even_when_no_corresponding_job_is_configured()
    {
        config(['paddle-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processPaddleWebhookJob->handle();

        $webhookCall = $this->webhookCall;

        Event::assertDispatched("paddle-webhooks::{$webhookCall->payload['alert_name']}", function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertNull(cache('dummyjob'));
    }
}
