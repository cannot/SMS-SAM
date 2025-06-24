<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// API Key Events
use App\Events\ApiKeyCreated;
use App\Events\ApiKeyRegenerated;
use App\Events\ApiKeyRevoked;
use App\Events\ApiKeyStatusChanged;
use App\Listeners\ApiKeyEventListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // API Key Events
        ApiKeyCreated::class => [
            ApiKeyEventListener::class . '@handleApiKeyCreated',
        ],
        ApiKeyRegenerated::class => [
            ApiKeyEventListener::class . '@handleApiKeyRegenerated',
        ],
        ApiKeyRevoked::class => [
            ApiKeyEventListener::class . '@handleApiKeyRevoked',
        ],
        ApiKeyStatusChanged::class => [
            ApiKeyEventListener::class . '@handleApiKeyStatusChanged',
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        ApiKeyEventListener::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}