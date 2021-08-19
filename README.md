# Handle Paddle webhooks in a Laravel application

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vanthao03596/laravel-paddle-webhooks.svg?style=flat-square)](https://packagist.org/packages/vanthao03596/laravel-paddle-webhooks)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/vanthao03596/laravel-paddle-webhooks/run-tests?label=tests)](https://github.com/vanthao03596/laravel-paddle-webhooks/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/vanthao03596/laravel-paddle-webhooks/Check%20&%20fix%20styling?label=code%20style)](https://github.com/vanthao03596/laravel-paddle-webhooks/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/vanthao03596/laravel-paddle-webhooks.svg?style=flat-square)](https://packagist.org/packages/vanthao03596/laravel-paddle-webhooks)

[Paddle](https://paddle.com/) can notify your application of events using webhooks. This package can help you handle those webhooks. Out of the box it will verify the Paddle signature of all incoming requests. All valid calls will be logged to the database. You can easily define jobs or events that should be dispatched when specific events hit your app.

This package will not handle what should be done after the webhook request has been validated and the right job or event is called. You should still code up any work (eg. regarding payments) yourself.

Before using this package we highly recommend reading [the entire documentation on webhooks over at Paddle](https://developer.paddle.com/webhook-reference/intro).

## Installation

You can install the package via composer:

```bash
composer require vanthao03596/laravel-paddle-webhooks
```

The service provider will automatically register itself.

You must publish the config file with:
```bash
php artisan vendor:publish --provider="Vanthao03596\PaddleWebhooks\PaddleWebhooksServiceProvider" --tag="config"
```

This is the contents of the config file that will be published at `config/paddle-webhooks.php`:


This is the contents of the published config file:

```php
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
```

Next, you must publish the migration with:
```bash
php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider" --tag="migrations"
```

After the migration has been published you can create the `webhook_calls` table by running the migrations:

```bash
php artisan migrate
```

Finally, take care of the routing: At [the Paddle dashboard](https://sandbox-vendors.paddle.com/alerts-webhooks) you must configure at what url Paddle webhooks should hit your app. In the routes file of your app you must pass that route to `Route::paddleWebhooks`:

```php
Route::paddleWebhooks('webhook-route-configured-at-the-paddle-dashboard');
```

Behind the scenes this will register a `POST` route to a controller provided by this package. Because Paddle has no way of getting a csrf-token, you must add that route to the `except` array of the `VerifyCsrfToken` middleware:

```php
protected $except = [
    'webhook-route-configured-at-the-paddle-dashboard',
];
```

## Usage

Paddle will send out webhooks for several event types. You can find the [full list of events types](https://developer.paddle.com/webhook-reference/intro) in the Paddle documentation.

Paddle will sign all requests hitting the webhook url of your app. This package will automatically verify if the signature is valid. If it is not, the request was probably not sent by Paddle.

Unless something goes terribly wrong, this package will always respond with a `200` to webhook requests. Sending a `200` will prevent Paddle from resending the same event over and over again. All webhook requests with a valid signature will be logged in the `webhook_calls` table. The table has a `payload` column where the entire payload of the incoming webhook is saved.

If the signature is not valid, the request will not be logged in the `webhook_calls` table but a `Vanthao03596\PaddleWebhooks\Exceptions\WebhookFailed` exception will be thrown.
If something goes wrong during the webhook request the thrown exception will be saved in the `exception` column. In that case the controller will send a `500` instead of `200`.

There are two ways this package enables you to handle webhook requests: you can opt to queue a job or listen to the events the package will fire.

### Handling webhook requests using jobs
If you want to do something when a specific event type comes in you can define a job that does the work. Here's an example of such a job:

```php
<?php

namespace App\Jobs\PaddleWebhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\WebhookClient\Models\WebhookCall;

class HandleChargeableSource implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /** @var \Spatie\WebhookClient\Models\WebhookCall */
    public $webhookCall;

    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    public function handle()
    {
        // do your work here

        // you can access the payload of the webhook call with `$this->webhookCall->payload`
    }
}
```

We highly recommend that you make this job queueable, because this will minimize the response time of the webhook requests. This allows you to handle more Paddle webhook requests and avoid timeouts.

After having created your job you must register it at the `jobs` array in the `paddle-webhooks.php` config file. The key should be the name of [the Paddle event type](https://developer.paddle.com/webhook-reference/intro) where, only [Fulfillment Webhook](https://developer.paddle.com/webhook-reference/product-fulfillment/fulfillment-webhook) will return ***fulfillment***

```php
// config/paddle-webhooks.php

'jobs' => [
    'subscription_created' => \App\Jobs\PaddleWebhooks\HandleSubscriptionCreated::class
],
```

### Handling webhook requests using events

Instead of queueing jobs to perform some work when a webhook request comes in, you can opt to listen to the events this package will fire. Whenever a valid request hits your app, the package will fire a `paddle-webhooks::<name-of-the-event>` event.

The payload of the events will be the instance of `WebhookCall` that was created for the incoming request.

Let's take a look at how you can listen for such an event. In the `EventServiceProvider` you can register listeners.

```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    'paddle-webhooks::subscription_created' => [
        App\Listeners\ChargeSource::class,
    ],
];
```

Here's an example of such a listener:

```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\WebhookClient\Models\WebhookCall;

class ChargeSource implements ShouldQueue
{
    public function handle(WebhookCall $webhookCall)
    {
        // do your work here

        // you can access the payload of the webhook call with `$webhookCall->payload`
    }
}
```

We highly recommend that you make the event listener queueable, as this will minimize the response time of the webhook requests. This allows you to handle more Paddle webhook requests and avoid timeouts.

The above example is only one way to handle events in Laravel. To learn the other options, read [the Laravel documentation on handling events](https://laravel.com/docs/5.5/events).

## Advanced usage

### Retry handling a webhook

All incoming webhook requests are written to the database. This is incredibly valuable when something goes wrong while handling a webhook call. You can easily retry processing the webhook call, after you've investigated and fixed the cause of failure, like this:

```php
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\PaddleWebhooks\ProcessPaddleWebhookJob;

dispatch(new ProcessPaddleWebhookJob(WebhookCall::find($id)));
```

### Performing custom logic

You can add some custom logic that should be executed before and/or after the scheduling of the queued job by using your own model. You can do this by specifying your own model in the `model` key of the `paddle-webhooks` config file. The class should extend `Spatie\PaddleWebhooks\ProcessPaddleWebhookJob`.

Here's an example:

```php
use Spatie\PaddleWebhooks\ProcessPaddleWebhookJob;

class MyCustomPaddleWebhookJob extends ProcessPaddleWebhookJob
{
    public function handle()
    {
        // do some custom stuff beforehand

        parent::handle();

        // do some custom stuff afterwards
    }
}
```

### Determine if a request should be processed

You may use your own logic to determine if a request should be processed or not. You can do this by specifying your own profile in the `profile` key of the `paddle-webhooks` config file. The class should implement `Spatie\WebhookClient\WebhookProfile\WebhookProfile`.

Paddle might occasionally send a webhook request ***more than once***. In this example we will make sure to only process a request if it wasn't processed before.

```php
use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class PaddleWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        return ! WebhookCall::where('payload->id', $request->get('id'))->exists();
    }
}
```

### Handling multiple signing secrets

Yu might want to the package to handle multiple endpoints and secrets. Here's how to configurate that behaviour.

If you are using the `Route::paddleWebhooks` macro, you can append the `configKey` as follows:

```php
Route::paddleWebhooks('webhook-url/{configKey}');
```

Alternatively, if you are manually defining the route, you can add `configKey` like so:

```php
Route::post('webhook-url/{configKey}', '\Vanthao03596\PaddleWebhooks\PaddleWebhooksController');
```

If this route parameter is present the verify middleware will look for the secret using a different config key, by appending the given the parameter value to the default config key. E.g. If Paddle posts to `webhook-url/my-named-secret` you'd add a new config named `signing_secret_my-named-secret`.

Example config for Connect might look like:

```php
// secret for when Paddle posts to webhook-url/account
'signing_secret_account' => 'whsec_abc',
// secret for when Paddle posts to webhook-url/connect
'signing_secret_connect' => 'whsec_123',
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [phamthao](https://github.com/vanthao03596)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
