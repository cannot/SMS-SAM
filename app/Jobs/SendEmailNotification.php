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
use Illuminate\Support\Facades\Mail;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationLog;
    public $tries = 3;
    public $timeout = 30;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(NotificationLog $notificationLog)
    {
        $this->notificationLog = $notificationLog;
        $this->onQueue('emails'); // ใช้ queue เฉพาะ
    }

    public function handle()
    {
        try {
            Log::info('Processing email notification', [
                'log_id' => $this->notificationLog->id,
                'recipient' => $this->notificationLog->recipient_email,
                'current_status' => $this->notificationLog->status
            ]);

            $notification = $this->notificationLog->notification;
            
            // ตรวจสอบว่าควรส่งอีเมลหรือไม่
            if (!$this->shouldSendEmail()) {
                $this->notificationLog->update([
                    'status' => 'failed', // ใช้ 'failed' แทน 'skipped'
                    'error_message' => 'User preferences do not allow email notifications',
                    'failed_at' => now()
                ]);
                
                Log::info('Email notification skipped due to user preferences', [
                    'log_id' => $this->notificationLog->id,
                    'recipient' => $this->notificationLog->recipient_email
                ]);
                
                $this->updateNotificationStatus();
                return;
            }

            // เตรียมเนื้อหาอีเมล
            $emailContent = $this->prepareEmailContent($notification);
            
            $recipient = [
                'email' => $this->notificationLog->recipient_email,
                'name' => $this->notificationLog->recipient_name ?: $this->extractNameFromEmail($this->notificationLog->recipient_email)
            ];

            Log::info('Sending email', [
                'recipient' => $recipient['email'],
                'subject' => substr($emailContent['subject'], 0, 50) . '...'
            ]);

            // ส่งอีเมลโดยใช้ Laravel Mail หรือ EmailService
            $result = $this->sendEmail($recipient, $emailContent);

            if ($result['success']) {
                // สำเร็จ - ใส่ delivered_at
                $this->notificationLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'delivered_at' => now(), // ✅ เพิ่ม delivered_at
                    'response_data' => [
                        'success' => true,
                        'method' => $result['method'] ?? 'mail',
                        'message_id' => $result['message_id'] ?? null,
                        'timestamp' => now()->toISOString()
                    ]
                ]);

                Log::info('Email notification sent successfully', [
                    'log_id' => $this->notificationLog->id,
                    'recipient' => $recipient['email'],
                    'method' => $result['method'] ?? 'mail'
                ]);

                $this->updateNotificationStatus();
                
            } else {
                throw new \Exception($result['error'] ?? 'Unknown email sending error');
            }

        } catch (\Exception $e) {
            Log::error('Email notification failed', [
                'log_id' => $this->notificationLog->id,
                'recipient' => $this->notificationLog->recipient_email,
                'error' => $e->getMessage()
            ]);
            
            $this->handleFailure($e->getMessage());
        }
    }

    /**
     * เตรียมเนื้อหาอีเมล
     */
    private function prepareEmailContent($notification)
    {
        $subject = $notification->subject ?? 'Notification';
        $bodyHtml = $notification->body_html ?? '';
        $bodyText = $notification->body_text ?? '';

        // แทนที่ variables ถ้ามี
        $variables = $this->getTemplateVariables($notification);
        
        if (!empty($variables)) {
            $subject = $this->replaceVariables($subject, $variables);
            $bodyHtml = $this->replaceVariables($bodyHtml, $variables);
            $bodyText = $this->replaceVariables($bodyText, $variables);
        }

        // ถ้าไม่มี bodyText ให้แปลงจาก HTML
        if (empty($bodyText) && !empty($bodyHtml)) {
            $bodyText = strip_tags($bodyHtml);
        }

        // ถ้าไม่มี bodyHtml ให้ใช้ bodyText
        if (empty($bodyHtml) && !empty($bodyText)) {
            $bodyHtml = nl2br(htmlspecialchars($bodyText));
        }

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText
        ];
    }

    /**
     * ส่งอีเมล
     */
    private function sendEmail($recipient, $emailContent)
    {
        try {
            // ลองใช้ EmailService ก่อน
            if (class_exists(\App\Services\EmailService::class)) {
                $emailService = app(\App\Services\EmailService::class);
                
                if (method_exists($emailService, 'sendNotification')) {
                    return $emailService->sendNotification(
                        $recipient,
                        $emailContent['subject'],
                        $emailContent['body_html'],
                        $emailContent['body_text']
                    );
                }
            }

            // ถ้าไม่มี EmailService ใช้ Laravel Mail
            return $this->sendViaLaravelMail($recipient, $emailContent);
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Email sending failed: ' . $e->getMessage(),
                'method' => 'failed'
            ];
        }
    }

    /**
     * ส่งผ่าน Laravel Mail
     */
    private function sendViaLaravelMail($recipient, $emailContent)
    {
        try {
            Mail::send([], [], function ($message) use ($recipient, $emailContent) {
                $message->to($recipient['email'], $recipient['name'])
                        ->subject($emailContent['subject']);
                
                // ใส่เนื้อหา
                if (!empty($emailContent['body_html'])) {
                    $message->html($emailContent['body_html']);
                }
                
                if (!empty($emailContent['body_text'])) {
                    $message->text($emailContent['body_text']);
                }
            });

            return [
                'success' => true,
                'method' => 'laravel_mail',
                'message_id' => 'laravel_' . time()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Laravel Mail failed: ' . $e->getMessage(),
                'method' => 'laravel_mail'
            ];
        }
    }

    /**
     * ได้ template variables
     */
    private function getTemplateVariables($notification)
    {
        $variables = $notification->variables ?? [];
        
        // เพิ่ม system variables
        $systemVariables = [
            'notification_id' => $notification->uuid,
            'subject' => $notification->subject,
            'recipient_name' => $this->notificationLog->recipient_name,
            'recipient_email' => $this->notificationLog->recipient_email,
            'current_date' => now()->format('Y-m-d'),
            'current_time' => now()->format('H:i:s'),
            'current_datetime' => now()->format('Y-m-d H:i:s'),
            'app_name' => config('app.name', 'Smart Notification System'),
            'app_url' => config('app.url', 'http://localhost'),
            'priority' => $notification->priority ?? 'normal'
        ];

        return array_merge($systemVariables, $variables);
    }

    /**
     * แทนที่ variables
     */
    private function replaceVariables($content, $variables)
    {
        if (empty($content)) {
            return $content;
        }

        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', (string)$value, $content);
            $content = str_replace('{{ ' . $key . ' }}', (string)$value, $content);
        }

        return $content;
    }

    /**
     * แยกชื่อจากอีเมล
     */
    private function extractNameFromEmail($email)
    {
        $username = explode('@', $email)[0];
        return ucfirst(str_replace(['.', '_', '-'], ' ', $username));
    }

    /**
     * ตรวจสอบว่าควรส่งอีเมลหรือไม่
     */
    protected function shouldSendEmail()
    {
        try {
            // หาผู้ใช้จากอีเมล
            $user = \App\Models\User::where('email', $this->notificationLog->recipient_email)->first();
            
            if (!$user) {
                // ถ้าไม่มีผู้ใช้ในระบบ ให้ส่งได้ (external email)
                return true;
            }

            // ตรวจสอบ user preferences
            if (!$user->preferences) {
                return true; // ไม่มี preferences ให้ส่งได้
            }

            // ตรวจสอบการตั้งค่าต่างๆ
            if (method_exists($user->preferences, 'allowsEmailNotifications')) {
                if (!$user->preferences->allowsEmailNotifications()) {
                    return false;
                }
            }

            if (method_exists($user->preferences, 'isInQuietHours')) {
                if ($user->preferences->isInQuietHours()) {
                    return false;
                }
            }

            if (method_exists($user->preferences, 'allowsWeekendNotifications')) {
                if (!$user->preferences->allowsWeekendNotifications() && now()->isWeekend()) {
                    return false;
                }
            }

            return true;
            
        } catch (\Exception $e) {
            Log::warning('Error checking email preferences, defaulting to send', [
                'recipient' => $this->notificationLog->recipient_email,
                'error' => $e->getMessage()
            ]);
            
            // หากเกิดข้อผิดพลาด ให้ส่งได้
            return true;
        }
    }

    /**
     * จัดการความล้มเหลว
     */
    private function handleFailure($errorMessage)
    {
        $retryCount = $this->notificationLog->retry_count + 1;
        $maxRetries = $this->tries;

        if ($retryCount < $maxRetries) {
            // ยังสามารถ retry ได้
            $nextRetryAt = now()->addSeconds($this->backoff[$retryCount - 1] ?? 900);
            
            $this->notificationLog->update([
                'status' => 'pending',
                'retry_count' => $retryCount,
                'error_message' => substr($errorMessage, 0, 400),
                'next_retry_at' => $nextRetryAt
            ]);

            Log::info('Email notification queued for retry', [
                'log_id' => $this->notificationLog->id,
                'retry_count' => $retryCount,
                'retry_at' => $nextRetryAt->format('Y-m-d H:i:s')
            ]);

            // ปล่อยให้ Laravel Queue จัดการ retry เอง
            throw new \Exception($errorMessage);
            
        } else {
            // ล้มเหลวถาวร
            $this->notificationLog->update([
                'status' => 'failed',
                'retry_count' => $retryCount,
                'error_message' => substr($errorMessage, 0, 400),
                'failed_at' => now()
            ]);

            Log::error('Email notification permanently failed', [
                'log_id' => $this->notificationLog->id,
                'error' => $errorMessage
            ]);

            $this->updateNotificationStatus();
        }
    }

    /**
     * อัปเดตสถานะ notification
     */
    private function updateNotificationStatus()
    {
        try {
            $notification = $this->notificationLog->notification;
            $logs = $notification->logs;
            
            $totalLogs = $logs->count();
            $sentLogs = $logs->where('status', 'sent')->count();
            $failedLogs = $logs->where('status', 'failed')->count();
            $pendingLogs = $logs->where('status', 'pending')->count();

            // คำนวณสถานะใหม่
            if ($pendingLogs == 0) {
                if ($failedLogs == 0) {
                    $status = 'sent';
                } elseif ($sentLogs == 0) {
                    $status = 'failed';
                } else {
                    $status = 'sent'; // บางส่วนสำเร็จ
                }
            } else {
                $status = 'queued';
            }

            $notification->update([
                'status' => $status,
                'delivered_count' => $sentLogs,
                'failed_count' => $failedLogs,
                'sent_at' => ($pendingLogs == 0 && $sentLogs > 0) ? now() : null
            ]);

            Log::info('Notification status updated from email job', [
                'notification_id' => $notification->id,
                'new_status' => $status,
                'sent_logs' => $sentLogs,
                'failed_logs' => $failedLogs
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update notification status from email job', [
                'notification_id' => $this->notificationLog->notification_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * เมื่อ job ล้มเหลวถาวร
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Email notification job permanently failed', [
            'log_id' => $this->notificationLog->id,
            'error' => $exception->getMessage()
        ]);

        try {
            $this->notificationLog->update([
                'status' => 'failed',
                'error_message' => substr($exception->getMessage(), 0, 400),
                'failed_at' => now()
            ]);

            $this->updateNotificationStatus();
            
        } catch (\Exception $e) {
            Log::error('Failed to update log after email job failure', [
                'log_id' => $this->notificationLog->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}