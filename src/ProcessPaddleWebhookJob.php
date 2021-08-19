<?php

namespace Vanthao03596\PaddleWebhooks;

use Vanthao03596\PaddleWebhooks\Exceptions\WebhookFailed;
use Spatie\WebhookClient\ProcessWebhookJob;

class ProcessPaddleWebhookJob extends ProcessWebhookJob
{
    public function handle()
    {
        if (! isset($this->webhookCall->payload['alert_name']) || $this->webhookCall->payload['alert_name'] === '') {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        event("paddle-webhooks::{$this->webhookCall->payload['alert_name']}", $this->webhookCall);

        $jobClass = $this->determineJobClass($this->webhookCall->payload['alert_name']);

        if ($jobClass === '') {
            return;
        }

        if (! class_exists($jobClass)) {
            throw WebhookFailed::jobClassDoesNotExist($jobClass, $this->webhookCall);
        }

        dispatch(new $jobClass($this->webhookCall));
    }

    protected function determineJobClass(string $eventType): string
    {
        return config("paddle-webhooks.jobs.{$eventType}", '');
    }
}