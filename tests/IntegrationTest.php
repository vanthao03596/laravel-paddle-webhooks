<?php

namespace Vanthao03596\PaddleWebhooks\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Spatie\WebhookClient\Models\WebhookCall;

class IntegrationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        Route::paddleWebhooks('paddle-webhooks');
        Route::paddleWebhooks('paddle-webhooks/{configKey}');

        config(['paddle-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        cache()->clear();
    }

    private static function generatePrivateKey()
    {
        return openssl_pkey_new([
            'private_key_bits' => 512,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
    }

    /** @test */
    public function it_can_handle_a_valid_request()
    {
        $this->withoutExceptionHandling();

        $payload = [
            'alert_name' => 'my_type',
            'key' => 'value',
        ];

        $privateKey = static::generatePrivateKey();
        $keyDetails = openssl_pkey_get_details($privateKey);

        config(['paddle-webhooks.signing_secret' => $keyDetails['key']]);

        openssl_sign(serialize($payload), $signature, $privateKey, OPENSSL_ALGO_SHA1);

        $payload['p_signature'] = base64_encode($signature);

        $this
            ->postJson('paddle-webhooks', $payload)
            ->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertEquals('my_type', $webhookCall->payload['alert_name']);
        $this->assertEquals($payload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('paddle-webhooks::my_type', function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertEquals($webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function a_request_with_invalid_signature_with_verification_disabled_will_pass()
    {
        config(['paddle-webhooks.verify_signature' => false]);
        cache()->clear();

        $this->withoutExceptionHandling();

        $payload = [
            'alert_name' => 'my_type',
            'key' => 'value',
        ];

        $payload['p_signature'] = 'invalid_signature';

        $this
            ->postJson('paddle-webhooks', $payload)
            ->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertEquals('my_type', $webhookCall->payload['alert_name']);
        $this->assertEquals($payload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('paddle-webhooks::my_type', function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertEquals($webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function a_request_without_signature_with_verification_disabled()
    {
        config(['paddle-webhooks.verify_signature' => false]);
        cache()->clear();

        $this->withoutExceptionHandling();

        $payload = [
            'alert_name' => 'my_type',
            'key' => 'value',
        ];

        $this
            ->postJson('paddle-webhooks', $payload)
            ->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertEquals('my_type', $webhookCall->payload['alert_name']);
        $this->assertEquals($payload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('paddle-webhooks::my_type', function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertEquals($webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function a_request_with_an_invalid_signature_wont_be_logged()
    {
        $payload = [
            'alert_name' => 'my_type',
            'key' => 'value',
        ];

        $payload['p_signature'] = 'invalid_signature';

        $this
            ->postJson('paddle-webhooks', $payload)
            ->assertStatus(500);

        $this->assertCount(0, WebhookCall::get());

        Event::assertNotDispatched('paddle-webhooks::my_type');

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function a_request_without_alert_name_will_dispatch_fulfillment_event()
    {
        config(['paddle-webhooks.jobs' => ['fulfillment' => DummyJob::class]]);

        $payload = ['payload'];

        $privateKey = static::generatePrivateKey();
        $keyDetails = openssl_pkey_get_details($privateKey);

        config(['paddle-webhooks.signing_secret' => $keyDetails['key']]);

        openssl_sign(serialize($payload), $signature, $privateKey, OPENSSL_ALGO_SHA1);

        $payload['p_signature'] = base64_encode($signature);

        $this
            ->postJson('paddle-webhooks', $payload)
            ->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertEquals($payload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('paddle-webhooks::fulfillment', function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertEquals($webhookCall->id, cache('dummyjob')->id);
    }

    /** @test * */
    public function a_request_with_a_config_key_will_use_the_correct_signing_secret()
    {
        $payload = [
            'alert_name' => 'my_type',
            'key' => 'value',
        ];

        $privateKey = static::generatePrivateKey();
        $keyDetails = openssl_pkey_get_details($privateKey);

        config()->set('paddle-webhooks.signing_secret_somekey', $keyDetails['key']);

        openssl_sign(serialize($payload), $signature, $privateKey, OPENSSL_ALGO_SHA1);

        $payload['p_signature'] = base64_encode($signature);

        $this
            ->postJson('paddle-webhooks/somekey', $payload)
            ->assertSuccessful();
    }
}
