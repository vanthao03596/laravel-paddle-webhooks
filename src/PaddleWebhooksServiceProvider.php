<?php

namespace Vanthao03596\PaddleWebhooks;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PaddleWebhooksServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/paddle-webhooks.php' => config_path('paddle-webhooks.php'),
            ], 'config');
        }

        Route::macro('paddleWebhooks', function ($url) {
            return Route::post($url, '\Vanthao03596\PaddleWebhooks\PaddleWebhooksController');
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/paddle-webhooks.php', 'paddle-webhooks');
    }
}
