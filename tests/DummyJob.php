<?php

namespace Vanthao03596\PaddleWebhooks\Tests;

use Spatie\WebhookClient\Models\WebhookCall;

class DummyJob
{
    /** @var \Spatie\WebhookClient\Models\WebhookCall */
    public $webhookCall;

    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    public function handle()
    {
        cache()->put('dummyjob', $this->webhookCall);
    }
}