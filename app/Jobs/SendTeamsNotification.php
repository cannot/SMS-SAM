<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\TeamsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTeamsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationLog;
    public $tries = 3;
    public $backoff = [60, 300, 900];

    public function __construct(NotificationLog $notificationLog)
    {
        $this->notificationLog = $notificationLog;
    }

    public function handle(TeamsService $teamsService)
    {
        try {
            Log::info('Processing Teams notification job', [
                'log_id' => $this->notificationLog->id,
                'recipient' => $this->notificationLog->recipient_email
            ]);

            $notification = $this->notificationLog->notification;
            
            // Check if user preferences allow Teams notifications
            if (!$this->shouldSendTeamsMessage()) {
                $this->notificationLog->update([
                    'status' => 'skipped',
                    'error_message' => 'User preferences do not allow Teams notifications'
                ]);
                return;
            }

            // Get Teams user
            $teamsUser = $teamsService->getUserByEmail($this->notificationLog->recipient_email);
            
            if (!$teamsUser) {
                $this->fail(new \Exception('Teams user not found for email: ' . $this->notificationLog->recipient_email));
                return;
            }

            // Prepare message content with variable replacement
            $subject = $notification->subject;
            $body = $notification->body_html ?? $notification->body_text ?? '';

            // Replace variables
            $variables = $notification->getTemplateVariables();
            
            // Add recipient-specific variables
            $variables['recipient_name'] = $this->notificationLog->recipient_name ?? 'User';
            $variables['recipient_email'] = $this->notificationLog->recipient_email;
            
            // Replace variables in content
            foreach ($variables as $key => $value) {
                $placeholder = '{{' . $key . '}}';
                $subject = str_replace($placeholder, $value, $subject);
                $body = str_replace($placeholder, $value, $body);
            }

            // Create Adaptive Card
            $cardTemplate = null;
            if ($notification->template && !empty($notification->template->teams_card_template)) {
                $cardTemplate = $this->renderCardTemplate(
                    $notification->template->teams_card_template,
                    $variables
                );
            } else {
                // Create priority-based adaptive card
                $priority = $notification->priority ?? 'normal';
                $cardTemplate = $teamsService->createPriorityAdaptiveCard($subject, $body, $priority);
            }

            // Prepare Teams data
            $teamsData = [
                'user' => (object) ['email' => $this->notificationLog->recipient_email],
                'subject' => $subject,
                'message' => $body,
                'priority' => $notification->priority ?? 'normal',
                'delivery_method' => 'direct',
                'variables' => $variables
            ];

            // Send Teams message using sendDirect method
            $result = $teamsService->sendDirect($teamsData);

            if ($result['success']) {
                $this->notificationLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'response_data' => $result
                ]);
                
                Log::info('Teams notification sent successfully', [
                    'log_id' => $this->notificationLog->id,
                    'recipient' => $this->notificationLog->recipient_email,
                    'method' => $result['method'] ?? 'unknown'
                ]);
                
                $this->updateNotificationStats();
            } else {
                $this->fail(new \Exception($result['error'] ?? 'Unknown Teams sending error'));
            }

        } catch (\Exception $e) {
            Log::error('SendTeamsNotification Job Failed', [
                'notification_log_id' => $this->notificationLog->id,
                'recipient' => $this->notificationLog->recipient_email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('SendTeamsNotification Job Failed Permanently', [
            'notification_log_id' => $this->notificationLog->id,
            'recipient' => $this->notificationLog->recipient_email,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts
        ]);

        $this->notificationLog->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'retry_count' => $this->attempts
        ]);
        
        $this->updateNotificationStats();
    }

    protected function shouldSendTeamsMessage()
    {
        try {
            $user = \App\Models\User::where('email', $this->notificationLog->recipient_email)->first();
            
            if (!$user) {
                Log::info('User not found in database, allowing Teams notification', [
                    'email' => $this->notificationLog->recipient_email
                ]);
                return true;
            }

            if (!$user->preferences) {
                Log::info('User has no preferences, allowing Teams notification', [
                    'email' => $this->notificationLog->recipient_email
                ]);
                return true;
            }

            // Check if Teams notifications are enabled
            if (!$user->preferences->enable_teams) {
                Log::info('Teams notifications disabled for user', [
                    'email' => $this->notificationLog->recipient_email
                ]);
                return false;
            }

            // Check priority threshold
            $notification = $this->notificationLog->notification;
            if (!$user->preferences->shouldReceivePriority($notification->priority)) {
                Log::info('Notification priority below user threshold', [
                    'email' => $this->notificationLog->recipient_email,
                    'notification_priority' => $notification->priority,
                    'user_min_priority' => $user->preferences->min_priority
                ]);
                return false;
            }

            // Check quiet hours
            if ($user->preferences->isQuietTime()) {
                // Allow urgent notifications during quiet hours if override is enabled
                if ($notification->priority === 'urgent' && $user->preferences->override_high_priority) {
                    Log::info('Allowing urgent notification during quiet hours', [
                        'email' => $this->notificationLog->recipient_email
                    ]);
                    return true;
                }
                
                Log::info('User in quiet hours, skipping notification', [
                    'email' => $this->notificationLog->recipient_email
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error checking user preferences for Teams notification', [
                'email' => $this->notificationLog->recipient_email,
                'error' => $e->getMessage()
            ]);
            // Default to allow if there's an error checking preferences
            return true;
        }
    }

    protected function renderCardTemplate($template, $variables)
    {
        try {
            $jsonString = json_encode($template);
            
            foreach ($variables as $key => $value) {
                $jsonString = str_replace("{{" . $key . "}}", $value, $jsonString);
            }
            
            return json_decode($jsonString, true);
        } catch (\Exception $e) {
            Log::error('Error rendering card template', [
                'error' => $e->getMessage(),
                'template' => $template
            ]);
            return null;
        }
    }

    protected function updateNotificationStats()
    {
        try {
            $this->notificationLog->notification->updateDeliveryCounters();
        } catch (\Exception $e) {
            Log::error('Error updating notification stats', [
                'notification_log_id' => $this->notificationLog->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}