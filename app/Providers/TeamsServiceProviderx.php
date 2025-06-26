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
            // Check if we should use mock service
            $useMock = config('services.teams.use_mock', false) || 
                      config('app.use_mock_teams', false) ||
                      env('USE_MOCK_TEAMS', false);
            
            // Also use mock in development if Teams is not configured
            if ($app->environment(['local', 'development', 'testing'])) {
                $teamsConfigured = !empty(config('services.teams.client_id')) &&
                                 !empty(config('services.teams.client_secret')) &&
                                 !empty(config('services.teams.tenant_id'));
                
                if (!$teamsConfigured || $useMock) {
                    \Log::info('🎭 Using Mock Teams Service', [
                        'reason' => $useMock ? 'Mock explicitly enabled' : 'Teams not configured',
                        'environment' => $app->environment()
                    ]);
                    return new MockTeamsService();
                }
            } else if ($useMock) {
                \Log::info('🎭 Using Mock Teams Service in production (explicitly enabled)');
                return new MockTeamsService();
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