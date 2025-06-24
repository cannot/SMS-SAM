<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\User;
use App\Models\ApiKey;
use App\Jobs\SendTeamsNotification;
use App\Jobs\SendEmailNotification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    protected $teamsService;
    protected $emailService;

    // Valid priority values that match database constraint (including medium)
    const VALID_PRIORITIES = ['low', 'medium', 'normal', 'high', 'urgent'];
    
    // Valid status values that match database constraint
    const VALID_STATUSES = ['draft', 'queued', 'scheduled', 'processing', 'sent', 'failed', 'cancelled'];

    public function __construct(TeamsService $teamsService, EmailService $emailService)
    {
        $this->teamsService = $teamsService;
        $this->emailService = $emailService;
        
        Log::info('NotificationService initialized', [
            'teams_service' => get_class($teamsService),
            'email_service' => get_class($emailService)
        ]);
    }

    /**
     * Send test notification to user
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
        if (!$notification->isScheduled()) {
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
     * Queue notification for immediate processing
     */
    public function queueNotification(Notification $notification)
    {
        $logs = $notification->logs()->where('status', 'pending')->get();

        foreach ($logs as $log) {
            $delay = $this->calculateDelay($notification->priority);
            $queueName = $this->getQueueName($notification->priority);
            
            switch ($log->channel) {
                case 'email':
                    SendEmailNotification::dispatch($log)
                        ->delay($delay)
                        ->onQueue($queueName);
                    break;
                    
                case 'teams':
                    SendTeamsNotification::dispatch($log)
                        ->delay($delay)
                        ->onQueue($queueName);
                    break;
            }
        }

        $notification->update(['status' => 'processing']);

        Log::info("Notification queued", [
            'uuid' => $notification->uuid,
            'logs_count' => $logs->count(),
            'priority' => $notification->priority
        ]);
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
            }
            
            $log->increment('retry_count');
        }

        Log::info("Retried failed notifications", [
            'count' => $logs->count()
        ]);

        return $logs->count();
    }

    /**
     * Calculate delay based on priority
     */
    protected function calculateDelay($priority)
    {
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
    }

    /**
     * Get queue name based on priority
     */
    protected function getQueueName($priority)
    {
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

    /**
     * Send API key created notification
     */
    public function sendApiKeyCreatedNotification(User $user, ApiKey $apiKey, User $createdBy): void
    {
        try {
            // For now, just log the notification
            // Later can implement actual email sending
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