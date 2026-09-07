<?php

namespace WebKampus\SSOClient;

use Illuminate\Support\ServiceProvider;

class SSOClientServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/sso.php', 'sso'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/sso.php' => config_path('sso.php'),
        ], 'sso-config');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
