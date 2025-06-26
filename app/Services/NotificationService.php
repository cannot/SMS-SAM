<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationGroup;
use App\Models\User;
use App\Models\ApiKey;
use App\Jobs\SendTeamsNotification;
use App\Jobs\SendEmailNotification;
use App\Jobs\SendWebhookNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NotificationService
{
    protected $teamsService;
    protected $emailService;

    // Valid priority values that match database constraint (including medium)
    const VALID_PRIORITIES = ['low', 'medium', 'normal', 'high', 'urgent'];
    
    // Valid status values that match database constraint
    const VALID_STATUSES = ['draft', 'queued', 'scheduled', 'processing', 'sent', 'failed', 'cancelled'];

    public function __construct($teamsService = null, $emailService = null)
    {
        // Safe constructor - avoid crashes if services not available
        try {
            $this->teamsService = $teamsService;
            $this->emailService = $emailService;
            
            Log::info('NotificationService initialized', [
                'teams_service' => $teamsService ? get_class($teamsService) : 'null',
                'email_service' => $emailService ? get_class($emailService) : 'null'
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService constructor failed', [
                'error' => $e->getMessage()
            ]);
            $this->teamsService = null;
            $this->emailService = null;
        }
    }

    // ===============================================
    // Main process notification method (for compatibility)
    // ===============================================

    /**
     * Process notification - main entry point for notification delivery
     * This method is called from NotificationController
     */
    public function processNotificationx(Notification $notification)
    {
        try {
            Log::info("Processing notification START", [
                'uuid' => $notification->uuid,
                'status' => $notification->status,
                'priority' => $notification->priority,
                'channels' => $notification->channels
            ]);

            // Validate notification status
            if (!in_array($notification->status, ['draft', 'queued', 'scheduled'])) {
                throw new \Exception("Cannot process notification with status: {$notification->status}");
            }

            // If notification is scheduled for future, don't process now
            if ($notification->scheduled_at && $notification->scheduled_at->isFuture()) {
                // Use separate transaction for status update
                DB::transaction(function() use ($notification) {
                    $notification->update(['status' => 'scheduled']);
                });
                
                Log::info("Notification scheduled for future delivery", [
                    'uuid' => $notification->uuid,
                    'scheduled_at' => $notification->scheduled_at
                ]);
                return true;
            }

            // Update status to processing in separate transaction
            DB::transaction(function() use ($notification) {
                $notification->update(['status' => 'processing']);
            });

            // Get all recipients with error handling
            $recipients = $this->getAllRecipientsSafely($notification);

            Log::info('Recipients found', [
                'notification_id' => $notification->id,
                'recipient_count' => count($recipients)
            ]);

            if (empty($recipients)) {
                // Update to failed status in separate transaction
                DB::transaction(function() use ($notification) {
                    $notification->update([
                        'status' => 'failed',
                        'failure_reason' => 'No recipients found'
                    ]);
                });
                return false;
            }

            // Create logs for each recipient and channel combination
            $totalLogs = 0;
            DB::transaction(function() use ($notification, $recipients, &$totalLogs) {
                foreach ($notification->channels as $channel) {
                    foreach ($recipients as $recipient) {
                        try {
                            $this->createNotificationLog($notification, $recipient, $channel);
                            $totalLogs++;
                        } catch (\Exception $e) {
                            Log::error('Failed to create notification log', [
                                'notification_id' => $notification->id,
                                'channel' => $channel,
                                'recipient' => $recipient,
                                'error' => $e->getMessage()
                            ]);
                            // Don't throw exception here, just log and continue
                        }
                    }
                }

                // Update notification counters
                $notification->update([
                    'total_recipients' => count($recipients),
                    'total_logs' => $totalLogs
                ]);
            });

            // Queue the notification safely (outside transaction)
            $this->queueNotificationSafely($notification);

            Log::info('Notification processed successfully END', [
                'notification_id' => $notification->id,
                'total_logs' => $totalLogs
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("CRITICAL: Failed to process notification", [
                'uuid' => $notification->uuid ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Update notification status to failed in separate transaction
            try {
                DB::transaction(function() use ($notification, $e) {
                    // Fresh instance to avoid stale data
                    $freshNotification = Notification::find($notification->id);
                    if ($freshNotification) {
                        $freshNotification->update([
                            'status' => 'failed',
                            'failure_reason' => substr($e->getMessage(), 0, 500) // Limit length
                        ]);
                    }
                });
            } catch (\Exception $updateError) {
                Log::error("Failed to update notification status after processing failure", [
                    'notification_id' => $notification->id,
                    'original_error' => $e->getMessage(),
                    'update_error' => $updateError->getMessage()
                ]);
            }

            // Don't re-throw to prevent crashes
            return false;
        }
    }

    /**
     * Get all recipients for notification with comprehensive error handling
     */
    private function getAllRecipientsSafely(Notification $notification)
    {
        try {
            return $this->getAllRecipients($notification);
        } catch (\Exception $e) {
            Log::error("Failed to get recipients", [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get all recipients for notification
     */
    private function getAllRecipientsx(Notification $notification)
    {
        $recipients = [];

        // Direct recipients
        if (!empty($notification->recipients)) {
            foreach ($notification->recipients as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[] = [
                        'email' => $email,
                        'name' => $this->extractNameFromEmail($email)
                    ];
                }
            }
        }

        // Group recipients
        if (!empty($notification->recipient_groups)) {
            try {
                $groups = NotificationGroup::whereIn('id', $notification->recipient_groups)
                                         ->with('users')
                                         ->get();

                foreach ($groups as $group) {
                    foreach ($group->users as $user) {
                        if ($user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                            $recipients[] = [
                                'email' => $user->email,
                                'name' => $user->display_name ?: $user->name ?: $this->extractNameFromEmail($user->email)
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to get group recipients", [
                    'groups' => $notification->recipient_groups,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Remove duplicates
        $uniqueRecipients = [];
        $emails = [];
        foreach ($recipients as $recipient) {
            if (!in_array($recipient['email'], $emails)) {
                $uniqueRecipients[] = $recipient;
                $emails[] = $recipient['email'];
            }
        }

        return $uniqueRecipients;
    }

    /**
     * Process notification - อัปเดตเมธอดหลัก (แก้ไข undefined variable)
     */
    public function processNotification(Notification $notification)
    {
        try {
            Log::info("Processing notification START", [
                'uuid' => $notification->uuid,
                'status' => $notification->status,
                'priority' => $notification->priority,
                'channels' => $notification->channels
            ]);

            // Validate notification status
            if (!in_array($notification->status, ['draft', 'queued', 'scheduled'])) {
                throw new \Exception("Cannot process notification with status: {$notification->status}");
            }

            // If notification is scheduled for future, don't process now
            if ($notification->scheduled_at && $notification->scheduled_at->isFuture()) {
                DB::transaction(function() use ($notification) {
                    $notification->update(['status' => 'scheduled']);
                });
                
                Log::info("Notification scheduled for future delivery", [
                    'uuid' => $notification->uuid,
                    'scheduled_at' => $notification->scheduled_at
                ]);
                return true;
            }

            // Update status to processing
            DB::transaction(function() use ($notification) {
                $notification->update(['status' => 'processing']);
            });

            // Check for webhook-only notifications BEFORE getting recipients
            $hasWebhookOnly = count($notification->channels) === 1 && in_array('webhook', $notification->channels);
            
            Log::info('Channel analysis', [
                'channels' => $notification->channels,
                'hasWebhookOnly' => $hasWebhookOnly
            ]);

            // Get recipients - webhook will return system recipient only
            $recipients = $this->getAllRecipientsSafely($notification);

            Log::info('Recipients found', [
                'notification_id' => $notification->id,
                'recipient_count' => count($recipients),
                'channels' => $notification->channels,
                'hasWebhookOnly' => $hasWebhookOnly
            ]);

            // For webhook-only notifications, we always have at least the system recipient
            if (empty($recipients) && !$hasWebhookOnly) {
                DB::transaction(function() use ($notification) {
                    $notification->update([
                        'status' => 'failed',
                        'failure_reason' => 'No recipients found'
                    ]);
                });
                return false;
            }

            // Create logs using optimized method
            $totalLogs = 0;
            DB::transaction(function() use ($notification, $recipients, &$totalLogs, $hasWebhookOnly) {
                $result = $this->createNotificationLogsOptimized($notification, $recipients);
                $totalLogs = $result['total'];

                // Update notification counters
                if ($hasWebhookOnly) {
                    // For webhook-only notifications, count as 1 recipient
                    $actualRecipientCount = 1;
                } else {
                    // For other channels, count actual recipients (excluding system webhook)
                    $actualRecipientCount = count(array_filter($recipients, function($r) {
                        return $r['email'] !== 'system@webhook';
                    }));
                }
                
                $notification->update([
                    'total_recipients' => $actualRecipientCount,
                    'total_logs' => $totalLogs
                ]);
                
                Log::info('Updated notification counters', [
                    'notification_id' => $notification->id,
                    'actualRecipientCount' => $actualRecipientCount,
                    'totalLogs' => $totalLogs,
                    'hasWebhookOnly' => $hasWebhookOnly
                ]);
            });

            // Queue the notification
            $this->queueNotificationSafely($notification);

            Log::info('Notification processed successfully END', [
                'notification_id' => $notification->id,
                'total_logs' => $totalLogs,
                'has_webhook' => in_array('webhook', $notification->channels),
                'hasWebhookOnly' => $hasWebhookOnly
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("CRITICAL: Failed to process notification", [
                'uuid' => $notification->uuid ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            try {
                DB::transaction(function() use ($notification, $e) {
                    $freshNotification = Notification::find($notification->id);
                    if ($freshNotification) {
                        $freshNotification->update([
                            'status' => 'failed',
                            'failure_reason' => substr($e->getMessage(), 0, 500)
                        ]);
                    }
                });
            } catch (\Exception $updateError) {
                Log::error("Failed to update notification status after processing failure", [
                    'notification_id' => $notification->id,
                    'original_error' => $e->getMessage(),
                    'update_error' => $updateError->getMessage()
                ]);
            }

            return false;
        }
    }
    /**
     * Get all recipients for notification with webhook channel handling (updated)
     */
    private function getAllRecipients(Notification $notification)
    {
        $recipients = [];

        // Handle webhook channel separately - send only once regardless of recipients
        if (in_array('webhook', $notification->channels)) {
            // For webhook, create a single "system" recipient
            $recipients[] = [
                'email' => 'system@webhook', // Special identifier for webhook
                'name' => 'Webhook Endpoint'
            ];
            
            Log::info("Webhook channel detected - single system recipient created", [
                'notification_id' => $notification->id,
                'webhook_url' => $notification->webhook_url
            ]);
            
            // If webhook is the only channel, return early
            if (count($notification->channels) === 1) {
                Log::info("Webhook-only notification - returning system recipient only");
                return $recipients;
            }
        }

        // Handle other channels (email, teams) - require actual recipients
        $otherChannels = array_diff($notification->channels, ['webhook']);
        if (!empty($otherChannels)) {
            Log::info("Processing non-webhook channels", [
                'channels' => $otherChannels
            ]);
            
            // Direct recipients for non-webhook channels
            if (!empty($notification->recipients)) {
                foreach ($notification->recipients as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = [
                            'email' => $email,
                            'name' => $this->extractNameFromEmail($email)
                        ];
                    }
                }
            }

            // Group recipients for non-webhook channels
            if (!empty($notification->recipient_groups)) {
                try {
                    $groups = NotificationGroup::whereIn('id', $notification->recipient_groups)
                                            ->with('users')
                                            ->get();

                    foreach ($groups as $group) {
                        foreach ($group->users as $user) {
                            if ($user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                                $recipients[] = [
                                    'email' => $user->email,
                                    'name' => $user->display_name ?: $user->name ?: $this->extractNameFromEmail($user->email)
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to get group recipients", [
                        'groups' => $notification->recipient_groups,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Remove duplicates for non-webhook recipients
        $uniqueRecipients = [];
        $emails = [];
        foreach ($recipients as $recipient) {
            if ($recipient['email'] === 'system@webhook' || !in_array($recipient['email'], $emails)) {
                $uniqueRecipients[] = $recipient;
                if ($recipient['email'] !== 'system@webhook') {
                    $emails[] = $recipient['email'];
                }
            }
        }

        Log::info("Final recipients list", [
            'total_recipients' => count($uniqueRecipients),
            'has_webhook_system' => in_array('system@webhook', array_column($uniqueRecipients, 'email')),
            'actual_emails' => array_values($emails)
        ]);

        return $uniqueRecipients;
    }

    /**
     * Create notification log entry with webhook channel handling (updated)
     */
    private function createNotificationLog(Notification $notification, array $recipient, string $channel)
    {
        // Special handling for webhook channel
        if ($channel === 'webhook') {
            // Only create one webhook log per notification
            $existingWebhookLog = NotificationLog::where('notification_id', $notification->id)
                                            ->where('channel', 'webhook')
                                            ->first();
            
            if ($existingWebhookLog) {
                Log::info("Webhook log already exists, skipping creation", [
                    'notification_id' => $notification->id,
                    'existing_log_id' => $existingWebhookLog->id
                ]);
                return $existingWebhookLog;
            }

            $webhookLog = NotificationLog::create([
                'notification_id' => $notification->id,
                'channel' => $channel,
                'recipient_email' => 'system@webhook',
                'recipient_name' => 'Webhook Endpoint',
                'status' => 'pending',
                'retry_count' => 0,
                'variables' => $notification->variables
            ]);
            
            Log::info("Created webhook log", [
                'notification_id' => $notification->id,
                'log_id' => $webhookLog->id,
                'webhook_url' => $notification->webhook_url
            ]);
            
            return $webhookLog;
        }

        // Regular handling for other channels
        return NotificationLog::create([
            'notification_id' => $notification->id,
            'channel' => $channel,
            'recipient_email' => $recipient['email'],
            'recipient_name' => $recipient['name'],
            'status' => 'pending',
            'retry_count' => 0,
            'variables' => $notification->variables
        ]);
    }

    /**
     * Process notification - แก้ไขส่วนการสร้าง logs
     */
    private function createNotificationLogsOptimized(Notification $notification, $recipients)
    {
        $totalLogs = 0;
        $createdLogs = [];

        foreach ($notification->channels as $channel) {
            if ($channel === 'webhook') {
                // Webhook: สร้าง log เพียงครั้งเดียว
                $existingLog = NotificationLog::where('notification_id', $notification->id)
                                            ->where('channel', 'webhook')
                                            ->first();
                
                if (!$existingLog) {
                    $log = $this->createNotificationLog($notification, 
                        ['email' => 'superadmin@smart-notification.local', 'name' => 'Webhook Endpoint'], 
                        $channel
                    );
                    $createdLogs[] = $log;
                    $totalLogs++;
                    
                    Log::info("Created webhook log", [
                        'notification_id' => $notification->id,
                        'log_id' => $log->id,
                        'webhook_url' => $notification->webhook_url
                    ]);
                } else {
                    Log::info("Webhook log already exists", [
                        'notification_id' => $notification->id,
                        'existing_log_id' => $existingLog->id
                    ]);
                    $totalLogs++;
                }
            } else {
                // Other channels: สร้าง log สำหรับแต่ละ recipient
                foreach ($recipients as $recipient) {
                    // Skip webhook system recipient for non-webhook channels
                    if ($recipient['email'] === 'system@webhook') {
                        continue;
                    }
                    
                    try {
                        $log = $this->createNotificationLog($notification, $recipient, $channel);
                        $createdLogs[] = $log;
                        $totalLogs++;
                    } catch (\Exception $e) {
                        Log::error('Failed to create notification log', [
                            'notification_id' => $notification->id,
                            'channel' => $channel,
                            'recipient' => $recipient,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        return ['logs' => $createdLogs, 'total' => $totalLogs];
    }

    /**
     * Extract name from email address safely
     */
    private function extractNameFromEmail($email)
    {
        try {
            $username = explode('@', $email)[0];
            return ucfirst(str_replace(['.', '_', '-'], ' ', $username));
        } catch (\Exception $e) {
            return $email; // Fallback to email if extraction fails
        }
    }

    /**
     * Create notification log entry
     */
    // private function createNotificationLog(Notification $notification, array $recipient, string $channel)
    // {
    //     return NotificationLog::create([
    //         'notification_id' => $notification->id,
    //         'channel' => $channel,
    //         'recipient_email' => $recipient['email'],
    //         'recipient_name' => $recipient['name'],
    //         'status' => 'pending',
    //         'retry_count' => 0,
    //         'variables' => $notification->variables
    //     ]);
    // }

    private function queueNotificationSafely(Notification $notification)
    {
        try {
            Log::info("Queuing notification START", [
                'notification_id' => $notification->id
            ]);
    
            $delay = $this->calculateDelay($notification->priority);
            $queueName = $this->getQueueName($notification->priority);
    
            // Get fresh logs to avoid stale data
            $logs = NotificationLog::where('notification_id', $notification->id)
                                  ->where('status', 'pending')
                                  ->get();
    
            Log::info("Found pending logs", [
                'count' => $logs->count()
            ]);
    
            if ($logs->isEmpty()) {
                Log::warning("No pending logs found for notification", [
                    'notification_id' => $notification->id
                ]);
                
                // Update status to indicate no logs to process
                DB::transaction(function() use ($notification) {
                    $freshNotification = Notification::find($notification->id);
                    if ($freshNotification) {
                        $freshNotification->update([
                            'status' => 'failed',
                            'failure_reason' => 'No pending logs found to queue'
                        ]);
                    }
                });
                return;
            }
    
            $successfullyQueued = 0;
            $failedToQueue = 0;
    
            foreach ($logs as $log) {
                try {
                    Log::info("Dispatching job for log", [
                        'log_id' => $log->id,
                        'channel' => $log->channel,
                        'recipient' => $log->recipient_email
                    ]);
    
                    switch ($log->channel) {
                        case 'email':
                            // Check if email service is available
                            if (!$this->emailService) {
                                Log::warning("Email service not available, marking as failed");
                                $this->updateLogStatus($log, 'failed', 'Email service not available');
                                $failedToQueue++;
                                break;
                            }
                            
                            SendEmailNotification::dispatch($log)
                                ->delay($delay)
                                ->onQueue($queueName);
                            $successfullyQueued++;
                            break;
    
                        case 'teams':
                            // Check if teams service is available
                            if (!$this->teamsService) {
                                Log::warning("Teams service not available, marking as failed");
                                $this->updateLogStatus($log, 'failed', 'Teams service not available');
                                $failedToQueue++;
                                break;
                            }
                            
                            SendTeamsNotification::dispatch($log)
                                ->delay($delay)
                                ->onQueue($queueName);
                            $successfullyQueued++;
                            break;
    
                        case 'webhook':
                            // Webhook doesn't need external service
                            SendWebhookNotification::dispatch($log)
                                ->delay($delay)
                                ->onQueue('webhooks');
                            $successfullyQueued++;
                            break;
    
                        default:
                            Log::warning('Unknown channel type', [
                                'channel' => $log->channel,
                                'log_id' => $log->id
                            ]);
                            $this->updateLogStatus($log, 'failed', 'Unknown channel type: ' . $log->channel);
                            $failedToQueue++;
                            break;
                    }
    
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch job for log", [
                        'log_id' => $log->id,
                        'channel' => $log->channel,
                        'error' => $e->getMessage()
                    ]);
    
                    $this->updateLogStatus($log, 'failed', 'Failed to dispatch job: ' . $e->getMessage());
                    $failedToQueue++;
                }
            }
    
            // Update notification status based on queue results
            DB::transaction(function() use ($notification, $successfullyQueued, $failedToQueue) {
                $freshNotification = Notification::find($notification->id);
                if ($freshNotification) {
                    if ($successfullyQueued > 0) {
                        $freshNotification->update(['status' => 'queued']);
                    } else {
                        $freshNotification->update([
                            'status' => 'failed',
                            'failure_reason' => 'Failed to queue any jobs'
                        ]);
                    }
                }
            });
    
            Log::info("Queuing notification END", [
                'notification_id' => $notification->id,
                'successfully_queued' => $successfullyQueued,
                'failed_to_queue' => $failedToQueue
            ]);
    
        } catch (\Exception $e) {
            Log::error("CRITICAL: Failed to queue notification", [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            // Update notification status in separate transaction
            try {
                DB::transaction(function() use ($notification, $e) {
                    $freshNotification = Notification::find($notification->id);
                    if ($freshNotification) {
                        $freshNotification->update([
                            'status' => 'failed',
                            'failure_reason' => substr('Failed to queue: ' . $e->getMessage(), 0, 500)
                        ]);
                    }
                });
            } catch (\Exception $updateError) {
                Log::error("Failed to update notification after queue failure", [
                    'notification_id' => $notification->id,
                    'original_error' => $e->getMessage(),
                    'update_error' => $updateError->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Helper method to update log status in separate transaction
     */
    private function updateLogStatus(NotificationLog $log, string $status, string $errorMessage = null)
    {
        try {
            DB::transaction(function() use ($log, $status, $errorMessage) {
                $freshLog = NotificationLog::find($log->id);
                if ($freshLog) {
                    $updateData = ['status' => $status];
                    if ($errorMessage) {
                        $updateData['error_message'] = substr($errorMessage, 0, 500);
                    }
                    $freshLog->update($updateData);
                }
            });
        } catch (\Exception $e) {
            Log::error("Failed to update log status", [
                'log_id' => $log->id,
                'intended_status' => $status,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Queue notification for delivery (public method for backward compatibility)
     */
    public function queueNotification(Notification $notification)
    {
        return $this->queueNotificationSafely($notification);
    }

    // ===============================================
    // Test notification methods
    // ===============================================

    /**
     * Send test notification to user (existing method - keeping for compatibility)
     */
    public static function sendTest(User $user, array $notification)
    {
        try {
            Log::info("Sending test notification to user: {$user->username}", [
                'channels' => $notification['channels'],
                'message' => $notification['message'] ?? 'Test message',
                'priority' => $notification['priority'] ?? 'medium'
            ]);

            $instance = app(self::class);
            
            // Validate and fix priority (keeping medium as valid)
            $priority = $instance->validatePriority($notification['priority'] ?? 'medium');
            $notification['priority'] = $priority;
            
            // Get user preferences
            $preferences = $user->preferences;
            $enabledChannels = $instance->getEnabledChannels($user, $notification['channels']);
            
            if (empty($enabledChannels)) {
                throw new \Exception('No enabled notification channels found for this user');
            }

            $results = [];
            
            // Send to each enabled channel without creating notification record
            foreach ($enabledChannels as $channel) {
                try {
                    $result = $instance->sendTestDirectly($user, $notification, $channel);
                    $results[$channel] = $result;
                    
                    Log::info("Test notification sent successfully", [
                        'user' => $user->username,
                        'channel' => $channel,
                        'status' => $result['status'],
                        'priority' => $priority
                    ]);
                    
                } catch (\Exception $e) {
                    $results[$channel] = [
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error("Test notification failed for channel: {$channel}", [
                        'user' => $user->username,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Log test notification activity
            try {
                activity()
                    ->performedOn($user)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'channels' => $enabledChannels,
                        'results' => $results,
                        'test_message' => $notification['message'] ?? 'Default test message',
                        'priority' => $priority
                    ])
                    ->log('Test notification sent');
            } catch (\Exception $e) {
                Log::warning('Failed to log test notification activity: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'channels_tested' => $enabledChannels,
                'results' => $results,
                'priority' => $priority,
                'message' => 'Test notification sent to ' . count($enabledChannels) . ' channel(s)'
            ];

        } catch (\Exception $e) {
            Log::error("Test notification failed for user: {$user->username}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('Failed to send test notification: ' . $e->getMessage());
        }
    }

    /**
     * Send test notification with enhanced error handling
     */
    public function sendTestNotification(array $testData)
    {
        try {
            Log::info("Test notification START", $testData);

            // Create a temporary notification for testing
            $notification = new Notification([
                'uuid' => Str::uuid(),
                'subject' => $testData['subject'] ?? 'Test Subject',
                'body_html' => $testData['body_html'] ?? '',
                'body_text' => $testData['body_text'] ?? 'Test message',
                'variables' => $testData['variables'] ?? [],
                'channels' => $testData['channels'] ?? ['email'],
                'priority' => $testData['priority'] ?? 'normal',
                'webhook_config' => $testData['webhook_config'] ?? null
            ]);

            // Create a temporary log for testing
            $testEmail = $testData['test_email'] ?? $testData['recipients'][0] ?? 'test@example.com';
            $log = new NotificationLog([
                'notification_id' => 0, // Temporary ID
                'channel' => $testData['channels'][0] ?? 'email',
                'recipient_email' => $testEmail,
                'recipient_name' => $this->extractNameFromEmail($testEmail),
                'status' => 'pending',
                'variables' => $testData['variables'] ?? []
            ]);

            // Set the notification relationship
            $log->setRelation('notification', $notification);

            // Send test based on channel
            $channel = $log->channel;
            Log::info("Testing channel: " . $channel);

            switch ($channel) {
                case 'email':
                    $this->sendTestEmailSafely($log);
                    break;
                case 'webhook':
                    $this->sendTestWebhookSafely($log, $testData['webhook_config'] ?? $notification->webhook_config ?? []);
                    break;
                case 'teams':
                    $this->sendTestTeamsSafely($log);
                    break;
                default:
                    throw new \Exception('Test not supported for channel: ' . $channel);
            }

            Log::info("Test notification END - SUCCESS");
            return true;

        } catch (\Exception $e) {
            Log::error('CRITICAL: Test notification failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'test_data' => $testData,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Send test email safely
     */
    private function sendTestEmailSafely(NotificationLog $log)
    {
        try {
            $notification = $log->notification;
            $variables = $this->getTemplateVariables($notification, $log);

            // Replace variables in content
            $subject = $this->replaceVariables($notification->subject, $variables);
            $bodyHtml = $this->replaceVariables($notification->body_html ?? '', $variables);
            $bodyText = $this->replaceVariables($notification->body_text ?? '', $variables);

            // Send email using Laravel Mail with timeout
            Mail::raw($bodyText ?: strip_tags($bodyHtml), function ($message) use ($log, $subject, $bodyHtml) {
                $message->to($log->recipient_email, $log->recipient_name)
                       ->subject('[TEST] ' . $subject);
                
                if ($bodyHtml) {
                    $message->html($bodyHtml);
                }
            });

            Log::info("Test email sent successfully");

        } catch (\Exception $e) {
            Log::error("Test email failed", [
                'recipient' => $log->recipient_email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send test webhook safely
     */
    private function sendTestWebhookSafely(NotificationLog $log, array $webhookConfig)
    {
        try {
            if (empty($webhookConfig['url'])) {
                throw new \Exception('Webhook URL is required for testing');
            }

            $notification = $log->notification;
            $variables = $this->getTemplateVariables($notification, $log);

            // Prepare headers
            $headers = $webhookConfig['headers'] ?? [];
            $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json';
            $headers['User-Agent'] = $headers['User-Agent'] ?? 'Smart-Notification-System/1.0 (Test)';

            // Prepare payload
            $payload = $webhookConfig['payload_template'] ?? [
                'test' => true,
                'message' => 'This is a test webhook from Smart Notification System',
                'subject' => $notification->subject,
                'recipient' => $log->recipient_email,
                'timestamp' => now()->toISOString()
            ];

            // Replace variables in payload
            $payload = $this->replaceVariablesInPayload($payload, $variables);

            Log::info("Sending test webhook", [
                'url' => $webhookConfig['url'],
                'method' => $webhookConfig['method'] ?? 'POST',
                'payload' => $payload
            ]);

            // Send webhook with reduced timeout and error handling
            $client = new \GuzzleHttp\Client([
                'timeout' => 10, // Reduced timeout for testing
                'connect_timeout' => 5
            ]);
            
            $response = $client->request(
                $webhookConfig['method'] ?? 'POST',
                $webhookConfig['url'],
                [
                    'headers' => $headers,
                    'json' => $payload,
                    'verify' => false, // For testing only
                    'http_errors' => false // Don't throw on HTTP errors
                ]
            );

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            Log::info("Webhook response received", [
                'status_code' => $statusCode,
                'response_body' => substr($responseBody, 0, 500) // Limit log size
            ]);

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new \Exception("Webhook test failed with status: {$statusCode}. Response: " . substr($responseBody, 0, 200));
            }

            Log::info("Test webhook sent successfully");

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error("Webhook connection failed", [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Webhook connection failed: ' . $e->getMessage());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error("Webhook request failed", [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Webhook request failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error("Test webhook failed", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send test teams safely
     */
    private function sendTestTeamsSafely(NotificationLog $log)
    {
        try {
            if (!$this->teamsService) {
                throw new \Exception('Teams service not available');
            }

            Log::info("Test teams sent successfully (dummy)");

        } catch (\Exception $e) {
            Log::error("Test teams failed", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send test email
     */
    private function sendTestEmail(NotificationLog $log)
    {
        return $this->sendTestEmailSafely($log);
    }

    /**
     * Send test webhook
     */
    private function sendTestWebhook(NotificationLog $log, array $webhookConfig)
    {
        return $this->sendTestWebhookSafely($log, $webhookConfig);
    }

    /**
     * Validate and normalize priority value (supports medium)
     */
    private function validatePriority($priority)
    {
        // Normalize common variations
        $normalizedPriority = strtolower(trim($priority));
        
        // Priority mapping with medium support
        $priorityMap = [
            'low' => 'low',
            'medium' => 'medium',      // Keep medium as is
            'normal' => 'normal',
            'high' => 'high',
            'urgent' => 'urgent',
            'critical' => 'urgent',    // Convert critical to urgent
            'emergency' => 'urgent'    // Convert emergency to urgent
        ];

        if (isset($priorityMap[$normalizedPriority])) {
            $validPriority = $priorityMap[$normalizedPriority];
            
            if (in_array($validPriority, self::VALID_PRIORITIES)) {
                Log::info("Priority validated", [
                    'original' => $priority,
                    'normalized' => $validPriority
                ]);
                return $validPriority;
            }
        }

        // Fallback to 'medium' if invalid
        Log::warning("Invalid priority value, using fallback", [
            'original' => $priority,
            'fallback' => 'medium'
        ]);
        
        return 'medium';
    }

    /**
     * Validate and normalize status value
     */
    private function validateStatus($status)
    {
        $normalizedStatus = strtolower(trim($status));
        
        if (in_array($normalizedStatus, self::VALID_STATUSES)) {
            return $normalizedStatus;
        }

        // Map common variations
        $statusMap = [
            'test' => 'sent',
            'pending' => 'queued',
            'completed' => 'sent',
            'delivered' => 'sent',
            'success' => 'sent',
            'error' => 'failed'
        ];

        if (isset($statusMap[$normalizedStatus])) {
            return $statusMap[$normalizedStatus];
        }

        // Fallback to 'draft'
        Log::warning("Invalid status value, using fallback", [
            'original' => $status,
            'fallback' => 'draft'
        ]);
        
        return 'draft';
    }

    /**
     * Send test directly without database record
     */
    private function sendTestDirectly(User $user, array $notification, string $channel)
    {
        switch ($channel) {
            case 'email':
                return $this->sendTestEmailDirectly($user, $notification);
                
            case 'teams':
                return $this->sendTestTeamsDirectly($user, $notification);
                
            case 'webhook':
                return $this->sendTestWebhookDirectly($user, $notification);
                
            default:
                throw new \Exception("Unsupported channel: {$channel}");
        }
    }

    /**
     * Send test email directly without notification record
     */
    private function sendTestEmailDirectly(User $user, array $notification)
    {
        try {
            $preferences = $user->preferences;
            $emailAddress = $preferences && $preferences->email_address 
                ? $preferences->email_address 
                : $user->email;

            if (!$emailAddress) {
                throw new \Exception('No email address configured for user');
            }

            $emailFormat = $preferences && $preferences->email_format 
                ? $preferences->email_format 
                : 'html';

            // Prepare test content
            $testSubject = '[TEST] ' . ($notification['title'] ?? 'Test Notification');
            $testBodyHtml = $this->generateTestHtmlBodyDirect($notification, $user);
            $testBodyText = $this->generateTestTextBodyDirect($notification, $user);

            $emailData = [
                'to' => $emailAddress,
                'subject' => $testSubject,
                'body_html' => $emailFormat === 'html' ? $testBodyHtml : null,
                'body_text' => $testBodyText,
                'variables' => [
                    'user_name' => $user->display_name ?? $user->username,
                    'test_time' => now()->format('Y-m-d H:i:s'),
                    'user_preferences' => $user->preferences ? 'Configured' : 'Default'
                ],
                'user_preferences' => [
                    'format' => $emailFormat,
                    'language' => $preferences ? $preferences->language : 'th',
                    'timezone' => $preferences ? $preferences->timezone : 'Asia/Bangkok'
                ]
            ];

            // Send immediately (not queued for test)
            $result = $this->emailService->sendDirect($emailData);

            Log::info("Email service result", [
                'success' => $result['success'],
                'error' => $result['error'] ?? null,
                'method' => $result['method'] ?? 'unknown'
            ]);

            return [
                'status' => $result['success'] ? 'sent' : 'failed',
                'recipient' => $emailAddress,
                'format' => $emailFormat,
                'priority' => $notification['priority'],
                'details' => $result
            ];

        } catch (\Exception $e) {
            Log::error("Test email failed", [
                'user' => $user->username,
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Email test failed: " . $e->getMessage());
        }
    }

    /**
     * Send test Teams message directly without notification record
     */
    private function sendTestTeamsDirectly(User $user, array $notification)
    {
        try {
            $preferences = $user->preferences;
            
            // Determine Teams delivery method
            $deliveryMethod = $preferences && $preferences->teams_channel_preference 
                ? $preferences->teams_channel_preference 
                : 'direct';

            // Prepare test content
            $testSubject = '[TEST] ' . ($notification['title'] ?? 'Test Notification');
            $testMessage = $this->generateTestTextBodyDirect($notification, $user);
            $testHtmlContent = $this->generateTestHtmlBodyDirect($notification, $user);

            $teamsData = [
                'user' => $user,
                'subject' => $testSubject,
                'message' => $testMessage,
                'html_content' => $testHtmlContent,
                'variables' => [
                    'user_name' => $user->display_name ?? $user->username,
                    'test_time' => now()->format('Y-m-d H:i:s'),
                    'user_preferences' => $user->preferences ? 'Configured' : 'Default'
                ],
                'delivery_method' => $deliveryMethod,
                'teams_user_id' => $preferences ? $preferences->teams_user_id : null,
                'priority' => $notification['priority']
            ];

            // Send immediately (not queued for test)
            $result = $this->teamsService->sendDirect($teamsData);

            return [
                'status' => $result['success'] ? 'sent' : 'failed',
                'delivery_method' => $deliveryMethod,
                'teams_user_id' => $preferences ? $preferences->teams_user_id : 'auto-detect',
                'priority' => $notification['priority'],
                'details' => $result
            ];

        } catch (\Exception $e) {
            Log::error("Test Teams message failed", [
                'user' => $user->username,
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Teams test failed: " . $e->getMessage());
        }
    }

    /**
     * Send test webhook directly
     */
    private function sendTestWebhookDirectly(User $user, array $notification)
    {
        try {
            $webhookConfig = $notification['webhook_config'] ?? [];
            
            if (empty($webhookConfig['url'])) {
                throw new \Exception('Webhook URL not configured');
            }

            // Prepare test payload
            $testPayload = [
                'test' => true,
                'user' => $user->username,
                'email' => $user->email,
                'message' => $notification['message'] ?? 'Test webhook notification',
                'priority' => $notification['priority'] ?? 'medium',
                'timestamp' => now()->toISOString()
            ];

            // Use configured payload if available
            if (!empty($webhookConfig['payload_template'])) {
                $testPayload = array_merge($testPayload, $webhookConfig['payload_template']);
            }

            // Send webhook
            $client = new \GuzzleHttp\Client(['timeout' => 30]);
            $response = $client->request(
                $webhookConfig['method'] ?? 'POST',
                $webhookConfig['url'],
                [
                    'headers' => array_merge([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'Smart-Notification-System/1.0 (Test)'
                    ], $webhookConfig['headers'] ?? []),
                    'json' => $testPayload,
                    'verify' => false
                ]
            );

            return [
                'status' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ? 'sent' : 'failed',
                'webhook_url' => $webhookConfig['url'],
                'response_code' => $response->getStatusCode(),
                'priority' => $notification['priority']
            ];

        } catch (\Exception $e) {
            Log::error("Test webhook failed", [
                'user' => $user->username,
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Webhook test failed: " . $e->getMessage());
        }
    }

    /**
     * Get template variables for notification safely
     */
    private function getTemplateVariables(Notification $notification, NotificationLog $log)
    {
        try {
            $variables = $notification->variables ?? [];

            // Add system variables
            $variables = array_merge($variables, [
                'notification_id' => $notification->uuid ?? 'test-' . time(),
                'subject' => $notification->subject ?? 'Test Subject',
                'message' => $notification->body_text ?: strip_tags($notification->body_html ?? ''),
                'user_name' => $log->recipient_name ?: $log->recipient_email,
                'user_email' => $log->recipient_email,
                'user_first_name' => $this->extractFirstName($log->recipient_name),
                'user_last_name' => $this->extractLastName($log->recipient_name),
                'recipient_name' => $log->recipient_name ?: $log->recipient_email,
                'recipient_email' => $log->recipient_email,
                'current_date' => now()->format('Y-m-d'),
                'current_time' => now()->format('H:i:s'),
                'current_datetime' => now()->format('Y-m-d H:i:s'),
                'app_name' => config('app.name', 'Smart Notification System'),
                'app_url' => config('app.url', 'http://localhost'),
                'priority' => $notification->priority ?? 'normal',
                'created_by_name' => 'Test User',
                'notification_created_at' => now()->format('Y-m-d H:i:s')
            ]);

            return $variables;

        } catch (\Exception $e) {
            Log::error("Failed to get template variables", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Replace variables in string content safely
     */
    private function replaceVariables(string $content, array $variables)
    {
        try {
            foreach ($variables as $key => $value) {
                $placeholder = '{{' . $key . '}}';
                $content = str_replace($placeholder, (string)$value, $content);
            }
            return $content;
        } catch (\Exception $e) {
            Log::error("Failed to replace variables", [
                'error' => $e->getMessage()
            ]);
            return $content;
        }
    }

    /**
     * Replace variables in payload recursively safely
     */
    private function replaceVariablesInPayload($payload, array $variables)
    {
        try {
            if (is_array($payload)) {
                $result = [];
                foreach ($payload as $key => $value) {
                    $newKey = $this->replaceVariables((string)$key, $variables);
                    $result[$newKey] = $this->replaceVariablesInPayload($value, $variables);
                }
                return $result;
            } elseif (is_string($payload)) {
                return $this->replaceVariables($payload, $variables);
            } else {
                return $payload;
            }
        } catch (\Exception $e) {
            Log::error("Failed to replace variables in payload", [
                'error' => $e->getMessage()
            ]);
            return $payload;
        }
    }

    /**
     * Extract first name from full name safely
     */
    private function extractFirstName($fullName)
    {
        try {
            if (empty($fullName)) return '';
            $parts = explode(' ', trim($fullName));
            return $parts[0] ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract last name from full name safely
     */
    private function extractLastName($fullName)
    {
        try {
            if (empty($fullName)) return '';
            $parts = explode(' ', trim($fullName));
            if (count($parts) > 1) {
                array_shift($parts);
                return implode(' ', $parts);
            }
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Calculate delay based on priority safely
     */
    public function calculateDelay($priority)
    {
        try {
            switch ($priority) {
                case 'urgent':
                    return now(); // Immediate
                case 'high':
                    return now()->addSeconds(5);
                case 'medium':
                    return now()->addSeconds(15);
                case 'normal':
                    return now()->addSeconds(30);
                case 'low':
                    return now()->addMinute();
                default:
                    return now()->addSeconds(30);
            }
        } catch (\Exception $e) {
            Log::error("Failed to calculate delay", [
                'priority' => $priority,
                'error' => $e->getMessage()
            ]);
            return now()->addSeconds(30); // Safe default
        }
    }

    /**
     * Get queue name based on priority safely
     */
    public function getQueueName($priority)
    {
        try {
            switch ($priority) {
                case 'urgent':
                    return 'urgent';
                case 'high':
                    return 'high';
                case 'medium':
                    return 'medium';
                case 'normal':
                    return 'default';
                case 'low':
                    return 'low';
                default:
                    return 'default';
            }
        } catch (\Exception $e) {
            Log::error("Failed to get queue name", [
                'priority' => $priority,
                'error' => $e->getMessage()
            ]);
            return 'default'; // Safe default
        }
    }
    /**
     * Generate test HTML body directly with variables replaced
     */
    private function generateTestHtmlBodyDirect(array $notification, User $user)
    {
        $message = $notification['message'] ?? 'This is a test notification to verify your notification preferences.';
        $testTime = now()->format('Y-m-d H:i:s');
        $priority = $notification['priority'] ?? 'medium';
        $userName = $user->display_name ?? $user->username;
        $userPreferences = $user->preferences ? 'Configured' : 'Default';
        $title = $notification['title'] ?? 'Test Notification';
        
        // Priority badge color
        $priorityColor = $this->getPriorityColor($priority);
        $priorityLabel = $this->getPriorityLabel($priority);
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e1e5e9; border-radius: 8px; overflow: hidden;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center;'>
                <h1 style='margin: 0; font-size: 24px;'>🔔 Test Notification</h1>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>Smart Notification System</p>
                <div style='margin-top: 10px;'>
                    <span style='background: {$priorityColor}; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold;'>
                        {$priorityLabel}
                    </span>
                </div>
            </div>
            
            <div style='padding: 30px; background: #f8f9fa;'>
                <h2 style='color: #2d3748; margin-top: 0;'>{$title}</h2>
                
                <div style='background: white; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid {$priorityColor};'>
                    <p style='margin: 0; color: #4a5568; line-height: 1.6;'>{$message}</p>
                </div>
                
                <div style='background: #e2e8f0; padding: 15px; border-radius: 6px; font-size: 14px;'>
                    <strong style='color: #2d3748;'>📋 Test Information:</strong><br><br>
                    <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 10px;'>
                        <div>👤 <strong>Sent to:</strong> {$userName}</div>
                        <div>⏰ <strong>Test time:</strong> {$testTime}</div>
                        <div>⚙️ <strong>User preferences:</strong> {$userPreferences}</div>
                        <div>🔥 <strong>Priority:</strong> {$priorityLabel}</div>
                    </div>
                </div>
                
                <div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 6px; margin-top: 20px;'>
                    <div style='color: #155724; font-weight: bold; margin-bottom: 5px;'>✅ Test Successful!</div>
                    <div style='color: #155724; font-size: 13px;'>
                        This is a test notification. If you received this, your notification settings are working correctly.
                    </div>
                </div>
                
                <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 12px; color: #718096;'>
                    Generated by Smart Notification System | 
                    <a href='#' style='color: #667eea; text-decoration: none;'>Manage Preferences</a>
                </div>
            </div>
        </div>";
    }

    /**
     * Generate test text body directly with variables replaced
     */
    private function generateTestTextBodyDirect(array $notification, User $user)
    {
        $message = $notification['message'] ?? 'This is a test notification to verify your notification preferences.';
        $testTime = now()->format('Y-m-d H:i:s');
        $priority = $notification['priority'] ?? 'medium';
        $userName = $user->display_name ?? $user->username;
        $userPreferences = $user->preferences ? 'Configured' : 'Default';
        $title = $notification['title'] ?? 'Test Notification';
        $priorityLabel = $this->getPriorityLabel($priority);
        
        return "
🔔 TEST NOTIFICATION - Smart Notification System

{$title}
Priority: {$priorityLabel}

{$message}

📋 Test Information:
• Sent to: {$userName}
• Test time: {$testTime}
• User preferences: {$userPreferences}
• Priority: {$priorityLabel}

✅ Test Successful!
This is a test notification. If you received this, your notification settings are working correctly.

---
Generated by Smart Notification System
        ";
    }

    /**
     * Get priority color for HTML display
     */
    private function getPriorityColor($priority)
    {
        switch ($priority) {
            case 'low':
                return '#28a745';
            case 'medium':
                return '#ffc107';
            case 'normal':
                return '#17a2b8';
            case 'high':
                return '#fd7e14';
            case 'urgent':
                return '#dc3545';
            default:
                return '#6c757d';
        }
    }

    /**
     * Get priority label for display
     */
    private function getPriorityLabel($priority)
    {
        switch ($priority) {
            case 'low':
                return 'LOW PRIORITY';
            case 'medium':
                return 'MEDIUM PRIORITY';
            case 'normal':
                return 'NORMAL PRIORITY';
            case 'high':
                return 'HIGH PRIORITY';
            case 'urgent':
                return 'URGENT PRIORITY';
            default:
                return strtoupper($priority) . ' PRIORITY';
        }
    }

    /**
     * Get enabled channels for user
     */
    private function getEnabledChannels(User $user, array $requestedChannels)
    {
        $preferences = $user->preferences;
        $enabledChannels = [];

        foreach ($requestedChannels as $channel) {
            switch ($channel) {
                case 'email':
                    if (!$preferences || $preferences->enable_email) {
                        if ($user->email) {
                            $enabledChannels[] = 'email';
                        } else {
                            Log::warning("Email channel requested but user has no email address: {$user->username}");
                        }
                    } else {
                        Log::info("Email channel disabled in user preferences: {$user->username}");
                    }
                    break;

                case 'teams':
                    if (!$preferences || $preferences->enable_teams) {
                        $enabledChannels[] = 'teams';
                    } else {
                        Log::info("Teams channel disabled in user preferences: {$user->username}");
                    }
                    break;

                case 'webhook':
                    // Webhook is always enabled if requested (no user preferences for webhook)
                    $enabledChannels[] = 'webhook';
                    break;

                default:
                    Log::warning("Unknown channel requested: {$channel}");
                    break;
            }
        }

        return $enabledChannels;
    }

    // ===============================================
    // Production notification methods
    // ===============================================

    /**
     * Create notification record with template support
     */
    public function createNotification(array $data)
    {
        // If template_id is provided, render the template
        if (!empty($data['template_id'])) {
            $template = \App\Models\NotificationTemplate::find($data['template_id']);
            
            if (!$template || !$template->is_active) {
                throw new \Exception('Template not found or inactive');
            }

            // Render template with provided variables
            $rendered = $template->render($data['variables'] ?? []);
            
            // Override content with rendered template
            $data['subject'] = $rendered['subject'];
            $data['body_html'] = $rendered['body_html'];
            $data['body_text'] = $rendered['body_text'];
            
            // Use template's channels if not specified
            if (empty($data['channels'])) {
                $data['channels'] = $template->supported_channels;
            }
            
            // Use template's priority if not specified
            if (empty($data['priority'])) {
                $data['priority'] = $template->priority ?? 'medium';
            }

            Log::info("Notification created using template", [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'variables_count' => count($data['variables'] ?? [])
            ]);
        }

        $notification = Notification::create([
            'uuid' => Str::uuid(),
            'template_id' => $data['template_id'] ?? null,
            'subject' => $data['subject'],
            'body_html' => $data['body_html'] ?? null,
            'body_text' => $data['body_text'] ?? null,
            'channels' => $data['channels'] ?? ['email'],
            'recipients' => $data['recipients'] ?? [],
            'recipient_groups' => $data['recipient_groups'] ?? [],
            'variables' => $data['variables'] ?? [],
            'webhook_config' => $data['webhook_config'] ?? null,
            'priority' => $this->validatePriority($data['priority'] ?? 'medium'),
            'status' => $this->validateStatus($data['status'] ?? 'draft'),
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'api_key_id' => $data['api_key_id'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        Log::info("Notification created", [
            'uuid' => $notification->uuid,
            'template_id' => $notification->template_id,
            'priority' => $notification->priority,
            'status' => $notification->status,
            'channels' => $notification->channels
        ]);

        return $notification;
    }

    /**
     * Schedule notification for delivery
     */
    public function scheduleNotification(Notification $notification)
    {
        // Get all recipient users
        $recipients = $notification->getRecipientUsers();
        
        if ($recipients->isEmpty()) {
            $notification->update([
                'status' => 'failed',
                'failure_reason' => 'No valid recipients found'
            ]);
            return false;
        }

        // Create notification logs for each recipient
        foreach ($recipients as $recipient) {
            foreach ($notification->channels as $channel) {
                NotificationLog::create([
                    'notification_id' => $notification->id,
                    'recipient_email' => $recipient->email,
                    'recipient_name' => $recipient->full_name ?? $recipient->display_name,
                    'channel' => $channel,
                    'status' => 'pending',
                ]);
            }
        }

        // Update notification status
        $notification->update([
            'status' => $notification->scheduled_at ? 'scheduled' : 'queued',
            'total_recipients' => $recipients->count() * count($notification->channels),
        ]);

        // Queue notification if not scheduled for future
        if (!$notification->scheduled_at || $notification->scheduled_at->isPast()) {
            $this->queueNotification($notification);
        }

        Log::info("Notification scheduled", [
            'uuid' => $notification->uuid,
            'recipients_count' => $recipients->count(),
            'status' => $notification->status
        ]);

        return true;
    }

    /**
     * Process scheduled notifications
     */
    public function processScheduledNotifications()
    {
        $notifications = Notification::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($notifications as $notification) {
            $this->queueNotification($notification);
        }

        Log::info("Processed scheduled notifications", [
            'count' => $notifications->count()
        ]);

        return $notifications->count();
    }

    /**
     * Retry failed notifications
     */
    public function retryFailedNotifications()
    {
        $logs = NotificationLog::where('status', 'failed')
            ->where('retry_count', '<', 3)
            ->where('created_at', '>', now()->subHours(24))
            ->get();

        foreach ($logs as $log) {
            switch ($log->channel) {
                case 'email':
                    SendEmailNotification::dispatch($log)->onQueue('retry');
                    break;
                    
                case 'teams':
                    SendTeamsNotification::dispatch($log)->onQueue('retry');
                    break;

                case 'webhook':
                    SendWebhookNotification::dispatch($log)->onQueue('retry');
                    break;
            }
            
            $log->increment('retry_count');
        }

        Log::info("Retried failed notifications", [
            'count' => $logs->count()
        ]);

        return $logs->count();
    }
    /**
     * Get notification delivery status
     */
    public function getNotificationStatus($notificationId)
    {
        $notification = Notification::where('uuid', $notificationId)->first();
        
        if (!$notification) {
            return null; 
        }

        $stats = $notification->logs()
            ->selectRaw('
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending
            ')
            ->groupBy('channel')
            ->get()
            ->keyBy('channel');

        return [
            'notification_id' => $notification->uuid,
            'status' => $notification->status,
            'priority' => $notification->priority,
            'sent_at' => $notification->sent_at,
            'delivery_stats' => [
                'total' => $notification->total_recipients,
                'delivered' => $notification->delivered_count,
                'failed' => $notification->failed_count,
                'pending' => $notification->total_recipients - $notification->delivered_count - $notification->failed_count,
            ],
            'channels' => $stats->toArray(),
        ];
    }

    /**
     * Update notification status based on logs
     */
    public function updateNotificationStatusx(Notification $notification)
    {
        $logs = $notification->logs;
        $totalLogs = $logs->count();

        if ($totalLogs === 0) {
            return;
        }

        $sentLogs = $logs->whereIn('status', ['sent', 'delivered'])->count();
        $failedLogs = $logs->where('status', 'failed')->count();
        $pendingLogs = $logs->whereIn('status', ['pending', 'processing'])->count();

        if ($pendingLogs > 0) {
            $notification->update(['status' => 'processing']);
        } elseif ($sentLogs === $totalLogs) {
            $notification->update(['status' => 'sent']);
        } elseif ($failedLogs === $totalLogs) {
            $notification->update([
                'status' => 'failed',
                'failure_reason' => 'All deliveries failed'
            ]);
        } elseif ($sentLogs > 0) {
            $notification->update(['status' => 'partially_sent']);
        }
    }

    public function updateNotificationStatus($notificationId)
    {
        $notification = Notification::where('uuid', $notificationId)->first();
        
        if (!$notification) {
            return;
        }
        
        $logs = $notification->logs;
        $totalLogs = $logs->count();
        $deliveredLogs = $logs->where('status', 'delivered')->count();
        $failedLogs = $logs->where('status', 'failed')->count();
        $pendingLogs = $logs->where('status', 'pending')->count();
        
        // คำนวณสถานะ
        if ($pendingLogs == 0) {
            // ทุก log ประมวลผลเสร็จแล้ว
            if ($failedLogs == 0) {
                $status = 'sent'; // ส่งสำเร็จทั้งหมด
            } elseif ($deliveredLogs == 0) {
                $status = 'failed'; // ส่งไม่สำเร็จทั้งหมด
            } else {
                $status = 'partial'; // ส่งสำเร็จบางส่วน
            }
        } else {
            $status = 'processing'; // ยังมี pending logs
        }
        
        $notification->update([
            'status' => $status,
            'delivered_count' => $deliveredLogs,
            'failed_count' => $failedLogs,
            'sent_at' => $pendingLogs == 0 ? now() : null
        ]);
        
        Log::info('Notification status updated', [
            'uuid' => $notificationId,
            'status' => $status,
            'delivered' => $deliveredLogs,
            'failed' => $failedLogs,
            'pending' => $pendingLogs
        ]);
    }

    /**
     * Retry failed notifications (for specific notification)
     */
    public function retryFailedNotificationsForNotification(Notification $notification)
    {
        $failedLogs = $notification->logs()->where('status', 'failed')->get();

        foreach ($failedLogs as $log) {
            $log->update([
                'status' => 'pending',
                'retry_count' => 0,
                'error_message' => null,
                'next_retry_at' => null
            ]);
        }

        if ($failedLogs->count() > 0) {
            $notification->update(['status' => 'processing']);
            $this->queueNotification($notification);
        }

        return $failedLogs->count();
    }

    /**
     * Cancel scheduled notification
     */
    public function cancelNotification(Notification $notification)
    {
        if (!in_array($notification->status, ['scheduled', 'queued'])) {
            throw new \Exception('Only scheduled or queued notifications can be cancelled');
        }

        // Update notification status
        $notification->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        // Update pending logs
        $notification->logs()
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now()
                    ]);

        return true;
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats($dateFrom = null, $dateTo = null)
    {
        $query = Notification::query();

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $stats = [
            'total' => $query->count(),
            'sent' => (clone $query)->where('status', 'sent')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'processing' => (clone $query)->whereIn('status', ['queued', 'processing'])->count(),
            'scheduled' => (clone $query)->where('status', 'scheduled')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
        ];

        // Channel statistics
        $channelStats = NotificationLog::selectRaw('channel, count(*) as count')
                                      ->groupBy('channel')
                                      ->pluck('count', 'channel')
                                      ->toArray();

        // Priority statistics
        $priorityStats = $query->selectRaw('priority, count(*) as count')
                              ->groupBy('priority')
                              ->pluck('count', 'priority')
                              ->toArray();

        return [
            'general' => $stats,
            'channels' => $channelStats,
            'priorities' => $priorityStats
        ];
    }

    /**
     * Get priority statistics
     */
    public function getPriorityStats()
    {
        $stats = Notification::selectRaw('
                priority,
                COUNT(*) as total,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                AVG(CASE WHEN sent_at IS NOT NULL THEN EXTRACT(EPOCH FROM (sent_at - created_at)) ELSE NULL END) as avg_delivery_time
            ')
            ->groupBy('priority')
            ->get()
            ->keyBy('priority');

        return $stats->toArray();
    }

    // ===============================================
    // API Key notification methods
    // ===============================================

    /**
     * Send API key created notification
     */
    public function sendApiKeyCreatedNotification(User $user, ApiKey $apiKey, User $createdBy): void
    {
        try {
            Log::info('API Key created notification sent', [
                'recipient' => $user->email,
                'api_key_name' => $apiKey->name,
                'created_by' => $createdBy->username
            ]);

            // TODO: Implement actual email notification
            // Mail::to($user)->send(new ApiKeyCreatedMail($apiKey, $createdBy));
            
        } catch (\Exception $e) {
            Log::error('Failed to send API key created notification', [
                'recipient' => $user->email,
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send API key regenerated notification
     */
    public function sendApiKeyRegeneratedNotification(User $user, ApiKey $apiKey, User $regeneratedBy): void
    {
        try {
            Log::info('API Key regenerated notification sent', [
                'recipient' => $user->email,
                'api_key_name' => $apiKey->name,
                'regenerated_by' => $regeneratedBy->username
            ]);

            // TODO: Implement actual email notification
            // Mail::to($user)->send(new ApiKeyRegeneratedMail($apiKey, $regeneratedBy));
            
        } catch (\Exception $e) {
            Log::error('Failed to send API key regenerated notification', [
                'recipient' => $user->email,
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send API key revoked notification
     */
    public function sendApiKeyRevokedNotification(User $user, ApiKey $apiKey, User $revokedBy): void
    {
        try {
            Log::info('API Key revoked notification sent', [
                'recipient' => $user->email,
                'api_key_name' => $apiKey->name,
                'revoked_by' => $revokedBy->username
            ]);

            // TODO: Implement actual email notification
            // Mail::to($user)->send(new ApiKeyRevokedMail($apiKey, $revokedBy));
            
        } catch (\Exception $e) {
            Log::error('Failed to send API key revoked notification', [
                'recipient' => $user->email,
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send API key status changed notification
     */
    public function sendApiKeyStatusChangedNotification(User $user, ApiKey $apiKey, bool $newStatus, User $changedBy): void
    {
        try {
            $status = $newStatus ? 'activated' : 'deactivated';
            
            Log::info('API Key status changed notification sent', [
                'recipient' => $user->email,
                'api_key_name' => $apiKey->name,
                'new_status' => $status,
                'changed_by' => $changedBy->username
            ]);

            // TODO: Implement actual email notification
            // Mail::to($user)->send(new ApiKeyStatusChangedMail($apiKey, $newStatus, $changedBy));
            
        } catch (\Exception $e) {
            Log::error('Failed to send API key status changed notification', [
                'recipient' => $user->email,
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send API key assignment notification
     */
    public function sendApiKeyAssignmentNotification(User $user, ApiKey $apiKey): void
    {
        try {
            Log::info('API Key assignment notification sent', [
                'recipient' => $user->email,
                'api_key_name' => $apiKey->name
            ]);

            // TODO: Implement actual email notification
            // Mail::to($user)->send(new ApiKeyAssignedMail($apiKey));
            
        } catch (\Exception $e) {
            Log::error('Failed to send API key assignment notification', [
                'recipient' => $user->email,
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}