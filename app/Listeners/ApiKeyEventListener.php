<?php

namespace App\Listeners;

use App\Events\ApiKeyCreated;
use App\Events\ApiKeyRegenerated;
use App\Events\ApiKeyRevoked;
use App\Events\ApiKeyStatusChanged;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ApiKeyEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected NotificationService $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle API key created events.
     */
    public function handleApiKeyCreated(ApiKeyCreated $event): void
    {
        Log::info('API Key created', [
            'api_key_id' => $event->apiKey->id,
            'api_key_name' => $event->apiKey->name,
            'created_by' => $event->createdBy->username,
            'assigned_to' => $event->apiKey->assignedTo?->username
        ]);

        // Send notification to assigned user if auto notifications enabled
        if ($event->apiKey->auto_notifications && $event->apiKey->assignedTo) {
            $this->notificationService->sendApiKeyCreatedNotification(
                $event->apiKey->assignedTo,
                $event->apiKey,
                $event->createdBy
            );
        }

        // Send webhook notification if configured
        if ($event->apiKey->notification_webhook) {
            $this->sendWebhookNotification($event->apiKey, 'created', [
                'api_key_id' => $event->apiKey->id,
                'api_key_name' => $event->apiKey->name,
                'created_by' => $event->createdBy->username,
                'created_at' => $event->apiKey->created_at->toISOString()
            ]);
        }
    }

    /**
     * Handle API key regenerated events.
     */
    public function handleApiKeyRegenerated(ApiKeyRegenerated $event): void
    {
        Log::warning('API Key regenerated', [
            'api_key_id' => $event->apiKey->id,
            'api_key_name' => $event->apiKey->name,
            'regenerated_by' => $event->regeneratedBy->username,
            'assigned_to' => $event->apiKey->assignedTo?->username
        ]);

        // Send notification to assigned user if auto notifications enabled
        if ($event->apiKey->auto_notifications && $event->apiKey->assignedTo) {
            $this->notificationService->sendApiKeyRegeneratedNotification(
                $event->apiKey->assignedTo,
                $event->apiKey,
                $event->regeneratedBy
            );
        }

        // Send webhook notification if configured
        if ($event->apiKey->notification_webhook) {
            $this->sendWebhookNotification($event->apiKey, 'regenerated', [
                'api_key_id' => $event->apiKey->id,
                'api_key_name' => $event->apiKey->name,
                'regenerated_by' => $event->regeneratedBy->username,
                'regenerated_at' => now()->toISOString(),
                'warning' => 'Previous API key is now invalid'
            ]);
        }
    }

    /**
     * Handle API key revoked events.
     */
    public function handleApiKeyRevoked(ApiKeyRevoked $event): void
    {
        Log::warning('API Key revoked', [
            'api_key_id' => $event->apiKey->id,
            'api_key_name' => $event->apiKey->name,
            'revoked_by' => $event->revokedBy->username,
            'assigned_to' => $event->apiKey->assignedTo?->username
        ]);

        // Send notification to assigned user if auto notifications enabled
        if ($event->apiKey->auto_notifications && $event->apiKey->assignedTo) {
            $this->notificationService->sendApiKeyRevokedNotification(
                $event->apiKey->assignedTo,
                $event->apiKey,
                $event->revokedBy
            );
        }

        // Send webhook notification if configured
        if ($event->apiKey->notification_webhook) {
            $this->sendWebhookNotification($event->apiKey, 'revoked', [
                'api_key_id' => $event->apiKey->id,
                'api_key_name' => $event->apiKey->name,
                'revoked_by' => $event->revokedBy->username,
                'revoked_at' => now()->toISOString(),
                'warning' => 'API key is now permanently disabled'
            ]);
        }
    }

    /**
     * Handle API key status changed events.
     */
    public function handleApiKeyStatusChanged(ApiKeyStatusChanged $event): void
    {
        $status = $event->newStatus ? 'activated' : 'deactivated';
        
        Log::info('API Key status changed', [
            'api_key_id' => $event->apiKey->id,
            'api_key_name' => $event->apiKey->name,
            'old_status' => $event->oldStatus ? 'active' : 'inactive',
            'new_status' => $event->newStatus ? 'active' : 'inactive',
            'changed_by' => $event->changedBy->username,
            'assigned_to' => $event->apiKey->assignedTo?->username
        ]);

        // Send notification to assigned user if auto notifications enabled
        if ($event->apiKey->auto_notifications && $event->apiKey->assignedTo) {
            $this->notificationService->sendApiKeyStatusChangedNotification(
                $event->apiKey->assignedTo,
                $event->apiKey,
                $event->newStatus,
                $event->changedBy
            );
        }

        // Send webhook notification if configured
        if ($event->apiKey->notification_webhook) {
            $this->sendWebhookNotification($event->apiKey, 'status_changed', [
                'api_key_id' => $event->apiKey->id,
                'api_key_name' => $event->apiKey->name,
                'old_status' => $event->oldStatus ? 'active' : 'inactive',
                'new_status' => $event->newStatus ? 'active' : 'inactive',
                'changed_by' => $event->changedBy->username,
                'changed_at' => now()->toISOString()
            ]);
        }
    }

    /**
     * Send webhook notification
     */
    private function sendWebhookNotification($apiKey, string $event, array $data): void
    {
        try {
            $payload = [
                'event' => "api_key.{$event}",
                'timestamp' => now()->toISOString(),
                'data' => $data
            ];

            // Use HTTP client to send webhook
            $response = \Http::timeout(10)->post($apiKey->notification_webhook, $payload);

            if ($response->successful()) {
                Log::info('Webhook notification sent successfully', [
                    'api_key_id' => $apiKey->id,
                    'event' => $event,
                    'webhook_url' => $apiKey->notification_webhook,
                    'response_code' => $response->status()
                ]);
            } else {
                Log::warning('Webhook notification failed', [
                    'api_key_id' => $apiKey->id,
                    'event' => $event,
                    'webhook_url' => $apiKey->notification_webhook,
                    'response_code' => $response->status(),
                    'response_body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Webhook notification error', [
                'api_key_id' => $apiKey->id,
                'event' => $event,
                'webhook_url' => $apiKey->notification_webhook,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events): array
    {
        return [
            ApiKeyCreated::class => 'handleApiKeyCreated',
            ApiKeyRegenerated::class => 'handleApiKeyRegenerated',
            ApiKeyRevoked::class => 'handleApiKeyRevoked',
            ApiKeyStatusChanged::class => 'handleApiKeyStatusChanged',
        ];
    }
}