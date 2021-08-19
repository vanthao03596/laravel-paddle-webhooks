<?php

namespace Vanthao03596\PaddleWebhooks\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Vanthao03596\PaddleWebhooks\PaddleWebhooksServiceProvider;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

    }

    protected function getPackageProviders($app)
    {
        return [
            PaddleWebhooksServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
        include_once __DIR__.'/../database/migrations/create_laravel-paddle-webhooks_table.php.stub';
        (new \CreatePackageTable())->up();
        */
    }
}
