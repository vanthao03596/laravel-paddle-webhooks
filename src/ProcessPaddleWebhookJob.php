<?php

namespace Vanthao03596\PaddleWebhooks;

use Spatie\WebhookClient\ProcessWebhookJob;
use Vanthao03596\PaddleWebhooks\Exceptions\WebhookFailed;

class ProcessPaddleWebhookJob extends ProcessWebhookJob
{
    public function handle()
    {
        $eventName = $this->webhookCall->payload['alert_name'] ?? null;

        if (!$eventName) {
            $eventName = 'fulfillment';
        }

        event("paddle-webhooks::{$eventName}", $this->webhookCall);

        $jobClass = $this->determineJobClass($eventName);

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
