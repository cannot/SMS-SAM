<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LdapService;

class LdapServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(LdapService::class, function ($app) {
            return new LdapService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
