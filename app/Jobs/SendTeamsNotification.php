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
                $this->fail(new \Exception('Teams user not found'));
                return;
            }

            // Prepare message content
            $subject = $notification->subject;
            $body = $notification->body_html ?? $notification->body_text ?? '';

            // Replace variables if template is used
            if ($notification->template && !empty($notification->variables)) {
                $subject = $notification->template->renderSubject($notification->variables);
                $body = $notification->template->renderBodyHtml($notification->variables) ?? 
                       $notification->template->renderBodyText($notification->variables);
            }

            // Create Adaptive Card if template has card configuration
            $cardTemplate = null;
            if ($notification->template && $notification->template->teams_card_template) {
                $cardTemplate = $this->renderCardTemplate(
                    $notification->template->teams_card_template,
                    $notification->variables ?? []
                );
            } else {
                // Create basic adaptive card
                $cardTemplate = $teamsService->createAdaptiveCard($subject, $body);
            }

            // Send Teams message
            $result = $teamsService->sendDirectMessage(
                $teamsUser->getId(),
                $body,
                $cardTemplate
            );

            if ($result['success']) {
                $this->notificationLog->markAsSent($result);
                $this->updateNotificationStats();
            } else {
                $this->fail(new \Exception($result['error']));
            }

        } catch (\Exception $e) {
            Log::error('SendTeamsNotification Job Failed: ' . $e->getMessage(), [
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

    protected function shouldSendTeamsMessage()
    {
        $user = \App\Models\User::where('email', $this->notificationLog->recipient_email)->first();
        
        if (!$user || !$user->preferences) {
            return true;
        }

        if (!$user->preferences->allowsTeamsNotifications()) {
            return false;
        }

        if ($user->preferences->isInQuietHours()) {
            return false;
        }

        if (!$user->preferences->allowsWeekendNotifications()) {
            return false;
        }

        return true;
    }

    protected function renderCardTemplate($template, $variables)
    {
        $jsonString = json_encode($template);
        
        foreach ($variables as $key => $value) {
            $jsonString = str_replace("{{" . $key . "}}", $value, $jsonString);
        }
        
        return json_decode($jsonString, true);
    }

    protected function updateNotificationStats()
    {
        $this->notificationLog->notification->updateDeliveryStats();
    }
}