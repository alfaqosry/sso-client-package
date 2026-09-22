<?php

namespace WebKampus\SSOClient;

use Illuminate\Support\ServiceProvider;

class SSOClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/sso.php',
            'sso'
        );

        $this->app->singleton(SSOClient::class, function () {
            return new SSOClient();
        });
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/sso.php' => config_path('sso.php'),
        ], 'sso-config');

        // Publish migration
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'sso-migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
