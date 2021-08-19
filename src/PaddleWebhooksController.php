<?php

namespace Vanthao03596\PaddleWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;

class PaddleWebhooksController
{
    public function __invoke(Request $request, string $configKey = null)
    {
        $webhookConfig = new WebhookConfig([
            'name' => 'paddle',
            'signing_secret' => ($configKey) ?
                config('paddle-webhooks.signing_secret_'.$configKey) :
                config('paddle-webhooks.signing_secret'),
            'signature_validator' => PaddleSignatureValidator::class,
            'webhook_profile' => config('paddle-webhooks.profile'),
            'webhook_model' => WebhookCall::class,
            'process_webhook_job' => config('paddle-webhooks.model'),
        ]);

        (new WebhookProcessor($request, $webhookConfig))->process();

        return response()->json(['message' => 'ok']);
    }
}
