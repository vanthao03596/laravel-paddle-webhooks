<?php

return [
    /*
     * Paddle will sign each webhook using a public key to create signature . You can find the used public key at the
     * webhook configuration settings: https://vendors.paddle.com/public-key.
     */
    'signing_secret' => env('PADDLE_PUBLIC_KEY'),

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Paddle event type.
     *
     * You can find a list of Paddle webhook types here:
     * https://developer.paddle.com/webhook-reference/intro.
     */
    'jobs' => [
        // 'subscription_created' => \App\Jobs\PaddleWebhooks\HandleSubscriptionCreated::class,
        // 'payment_succeeded' => \App\Jobs\PaddleWebhooks\HandlePaymentSucceeded::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Vanthao03596\PaddleWebhooks\ProcessPaddleWebhookJob.
     */
    'model' => \Vanthao03596\PaddleWebhooks\ProcessPaddleWebhookJob::class,

    /**
     * This class determines if the webhook call should be stored and processed.
     */
    'profile' => \Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile::class,

    /*
     * When disabled, the package will not verify if the signature is valid.
     * This can be handy in local environments.
     */
    'verify_signature' => env('PADDLE_SIGNATURE_VERIFY', true),
];