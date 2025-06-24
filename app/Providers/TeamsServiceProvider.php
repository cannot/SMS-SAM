<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TeamsService;
use App\Services\MockTeamsService;

class TeamsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(TeamsService::class, function ($app) {
            // Use mock service in development if Teams is not configured
            if ($app->environment(['local', 'development', 'testing'])) {
                $teamsConfigured = !empty(config('services.teams.client_id')) &&
                                 !empty(config('services.teams.client_secret')) &&
                                 !empty(config('services.teams.tenant_id'));
                
                if (!$teamsConfigured || config('app.use_mock_teams', false)) {
                    \Log::info('🎭 Using Mock Teams Service for development');
                    return new MockTeamsService();
                }
            }
            
            \Log::info('🔗 Using Real Teams Service');
            return new TeamsService();
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