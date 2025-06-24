<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        // Add other model policies here
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates if needed
        Gate::define('access-admin-panel', function (User $user) {
            return $user->hasRole(['admin', 'super-admin', 'manager']);
        });

        Gate::define('manage-system-settings', function (User $user) {
            return $user->hasRole(['admin', 'super-admin']);
        });

        Gate::define('view-system-logs', function (User $user) {
            return $user->hasRole(['admin', 'super-admin', 'system-admin']) || 
                   $user->hasPermissionTo('view-system-logs');
        });

        Gate::define('manage-notifications', function (User $user) {
            return $user->hasRole(['admin', 'super-admin', 'notification-manager']) || 
                   $user->hasPermissionTo('manage-notifications');
        });

        Gate::define('manage-api-gateway', function (User $user) {
            return $user->hasRole(['admin', 'super-admin', 'api-manager']) || 
                   $user->hasPermissionTo('manage-api-gateway');
        });
    }
}