<?php

namespace App\Providers;

use App\Services\EmailService;
use App\Services\TeamsService;
use App\Services\NotificationService;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register EmailService as singleton
        $this->app->singleton(EmailService::class, function ($app) {
            return new EmailService();
        });

        // Register TeamsService as singleton
        $this->app->singleton(TeamsService::class, function ($app) {
            return new TeamsService();
        });

        // Register NotificationService with dependencies
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService(
                $app->make(TeamsService::class),
                $app->make(EmailService::class)
            );
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