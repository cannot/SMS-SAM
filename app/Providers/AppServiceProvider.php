<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Services\NotificationService;
use App\Services\TeamsService;
use App\Services\EmailService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services safely
        $this->app->singleton(TeamsService::class, function ($app) {
            return new TeamsService();
        });
        
        $this->app->singleton(EmailService::class, function ($app) {
            return new EmailService();
        });
        
        $this->app->singleton(NotificationService::class, function ($app) {
            try {
                $teamsService = $app->make(TeamsService::class);
                $emailService = $app->make(EmailService::class);
                return new NotificationService($teamsService, $emailService);
            } catch (\Exception $e) {
                \Log::error('Failed to create NotificationService: ' . $e->getMessage());
                // Return with null services if creation fails
                return new NotificationService(null, null);
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination.custom-pagination');
        date_default_timezone_set('Asia/Bangkok');
    }
}
