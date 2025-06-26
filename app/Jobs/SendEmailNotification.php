<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationLog;
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(NotificationLog $notificationLog)
    {
        $this->notificationLog = $notificationLog;
    }

    public function handle(EmailService $emailService)
    {
        try {
            $notification = $this->notificationLog->notification;
            
            // Check if user preferences allow email notifications
            if (!$this->shouldSendEmail()) {
                $this->notificationLog->update([
                    'status' => 'skipped',
                    'error_message' => 'User preferences do not allow email notifications'
                ]);
                return;
            }

            // Prepare email content
            $subject = $notification->subject;
            $bodyHtml = $notification->body_html;
            $bodyText = $notification->body_text;

            // Replace variables if template is used
            if ($notification->template && !empty($notification->variables)) {
                $subject = $notification->template->renderSubject($notification->variables);
                $bodyHtml = $notification->template->renderBodyHtml($notification->variables);
                $bodyText = $notification->template->renderBodyText($notification->variables);
            }

            $recipient = [
                'email' => $this->notificationLog->recipient_email,
                'name' => $this->notificationLog->recipient_name
            ];

            // Send email
            $result = $emailService->sendNotification(
                $recipient,
                $subject,
                $bodyHtml,
                $bodyText
            );

            if ($result['success']) {
                $this->notificationLog->markAsSent($result);
                $this->updateNotificationStats();
            } else {
                $this->fail(new \Exception($result['error']));
            }

        } catch (\Exception $e) {
            Log::error('SendEmailNotification Job Failed: ' . $e->getMessage(), [
                'notification_log_id' => $this->notificationLog->id,
                'recipient' => $this->notificationLog->recipient_email
            ]);
            
            $this->fail($e);
        }
    }

    public function failed(\Throwable $exception)
    {
        $this->notificationLog->markAsFailed($exception->getMessage(), false);
        $this->updateNotificationStats();
    }

    protected function shouldSendEmail()
    {
        // Find user by email
        $user = \App\Models\User::where('email', $this->notificationLog->recipient_email)->first();
        
        if (!$user || !$user->preferences) {
            return true; // Default to sending if no preferences found
        }

        // Check user preferences
        if (!$user->preferences->allowsEmailNotifications()) {
            return false;
        }

        // Check quiet hours
        if ($user->preferences->isInQuietHours()) {
            return false;
        }

        // Check weekend notifications
        if (!$user->preferences->allowsWeekendNotifications()) {
            return false;
        }

        return true;
    }

    protected function updateNotificationStats()
    {
        $this->notificationLog->notification->updateDeliveryCounters();
    }
}