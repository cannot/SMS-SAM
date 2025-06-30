<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Osama\LaravelTeamsNotification\TeamsNotification;

class SendWebhookNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationLog;
    public $timeout = 25; // ลดเวลา timeout
    public $tries = 2;
    public $maxExceptions = 2;

    public function __construct(NotificationLog $notificationLog)
    {
        $this->notificationLog = $notificationLog;
        $this->onQueue('webhooks');
    }

    public function handle()
    {
        // Set limits ที่เข้มงวดขึ้น
        ini_set('memory_limit', '256M');
        set_time_limit(20);
        
        try {
            Log::info('Processing webhook notification', [
                'log_id' => $this->notificationLog->id,
                'recipient' => $this->notificationLog->recipient_email,
                'current_status' => $this->notificationLog->status
            ]);

            // ไม่ต้อง update status เป็น 'processing' เพราะ constraint ไม่อนุญาต
            // แทนที่จะใช้ 'processing' ให้ใช้ 'pending' แล้วใส่ timestamp แทน
            
            $notification = $this->notificationLog->notification;
            
            // ตรวจสอบ webhook URL
            if (empty($notification->webhook_url)) {
                throw new \Exception('Webhook URL not configured');
            }

            Log::info('Webhook URL found', [
                'webhook_url' => $notification->webhook_url,
                'notification_id' => $notification->id
            ]);

            // สร้าง TeamsNotification instance
            $teamsNotification = new TeamsNotification();
            
            // เตรียม message (ใช้ subject เป็นหลัก)
            $message = $notification->subject ?? "Notification from Smart Notification System";
            
            // เตรียม details จาก body_text หรือ body_html
            $details = $this->prepareDetails($notification);
            
            // เพิ่มข้อมูลระบบ
            $systemDetails = [
                'Notification ID' => (string) $notification->uuid,
                'Priority' => strtoupper($notification->priority ?? 'normal'),
                'Recipient' => $this->notificationLog->recipient_name ?: $this->notificationLog->recipient_email,
                'Sent At' => now()->format('Y-m-d H:i:s'),
                'System' => 'Smart Notification System'
            ];
            
            // รวม details
            $finalDetails = array_merge($details, $systemDetails);
            
            // แทนที่ variables ถ้ามี
            if (!empty($notification->variables)) {
                $finalDetails = $this->replaceVariablesInArray($finalDetails, $notification->variables);
                $message = $this->replaceVariablesInString($message, $notification->variables);
            }

            Log::info('Sending webhook via Teams', [
                'webhook_url' => substr($notification->webhook_url, 0, 50) . '...', // แสดงบางส่วนเท่านั้น
                'message' => substr($message, 0, 100),
                'details_count' => count($finalDetails)
            ]);

            // ส่งผ่าน Teams webhook พร้อม timeout สั้น
            $response = $teamsNotification->sendMessageSetWebhook(
                $notification->webhook_url, 
                $message, 
                $finalDetails
            );

            $statusCode = $response->getStatusCode();

            // ตรวจสอบผลลัพธ์
            if ($statusCode >= 200 && $statusCode < 300) {
                // สำเร็จ - ใช้ status ที่อนุญาตเท่านั้น
                $this->notificationLog->update([
                    'status' => 'sent', // ✅ ใช้ 'sent' แทน 'delivered'
                    'sent_at' => now(),
                    'delivered_at' => now(),
                    'response_data' => [
                        'status_code' => $statusCode,
                        'success' => true,
                        'webhook_url' => substr($notification->webhook_url, -20),
                        'message_sent' => $message,
                        'details_count' => count($finalDetails)
                    ]
                ]);

                Log::info('Webhook notification sent successfully', [
                    'log_id' => $this->notificationLog->id,
                    'status_code' => $statusCode,
                    'notification_uuid' => (string) $notification->uuid
                ]);

                // อัปเดตสถานะ notification
                $this->updateNotificationStatus();
                
            } else {
                throw new \Exception("Webhook returned HTTP {$statusCode}");
            }

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $this->handleFailure('Connection timeout or refused: ' . $e->getMessage());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
            $this->handleFailure("HTTP {$statusCode} error: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->handleFailure($e->getMessage());
        }
    }

    /**
     * เตรียม details จาก notification content
     */
    private function prepareDetails($notification)
    {
        $details = [];
        
        if ($notification->body_text) {
            $bodyText = $notification->body_text;
            
            // ลองแปลง JSON ก่อน
            $jsonData = json_decode($bodyText, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                $details = $jsonData;
            } else {
                // แยกข้อมูลแบบ key: value
                $lines = explode("\n", $bodyText);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (strpos($line, ':') !== false) {
                        list($key, $value) = explode(':', $line, 2);
                        $details[trim($key)] = trim($value);
                    }
                }
                
                // ถ้าไม่มี key: value format ให้ใช้เป็น message
                if (empty($details)) {
                    $details['Message'] = $bodyText;
                }
            }
        } elseif ($notification->body_html) {
            $details['Message'] = strip_tags($notification->body_html);
        }

        return $details;
    }

    /**
     * แทนที่ variables ในข้อความ
     */
    private function replaceVariablesInString($string, $variables)
    {
        foreach ($variables as $key => $value) {
            $string = str_replace('{{' . $key . '}}', (string)$value, $string);
            $string = str_replace('{{ ' . $key . ' }}', (string)$value, $string);
        }
        return $string;
    }

    /**
     * แทนที่ variables ใน array
     */
    private function replaceVariablesInArray($data, $variables)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $newKey = $this->replaceVariablesInString($key, $variables);
                $result[$newKey] = $this->replaceVariablesInArray($value, $variables);
            }
            return $result;
        } elseif (is_string($data)) {
            return $this->replaceVariablesInString($data, $variables);
        } else {
            return $data;
        }
    }

    /**
     * จัดการเมื่อล้มเหลว
     */
    private function handleFailure($errorMessage)
    {
        $retryCount = $this->notificationLog->retry_count + 1;
        $maxRetries = 2;

        Log::error('Webhook notification failed', [
            'log_id' => $this->notificationLog->id,
            'error' => substr($errorMessage, 0, 200),
            'retry_count' => $retryCount,
            'max_retries' => $maxRetries
        ]);

        if ($retryCount < $maxRetries) {
            // คำนวณเวลา retry ใหม่ (สั้นลง)
            $nextRetryAt = now()->addMinutes(1); // retry ทุก 1 นาที

            $this->notificationLog->update([
                'status' => 'pending', // ✅ ใช้ 'pending' แทน 'failed' ระหว่าง retry
                'retry_count' => $retryCount,
                'error_message' => substr($errorMessage, 0, 400),
                'next_retry_at' => $nextRetryAt
            ]);

            // Queue job ใหม่
            self::dispatch($this->notificationLog)
                ->delay($nextRetryAt)
                ->onQueue('webhooks');

            Log::info('Webhook notification queued for retry', [
                'log_id' => $this->notificationLog->id,
                'retry_at' => $nextRetryAt->format('Y-m-d H:i:s')
            ]);

        } else {
            // ล้มเหลวถาวร
            $this->notificationLog->update([
                'status' => 'failed', // ✅ ใช้ 'failed' เมื่อล้มเหลวจริงๆ
                'retry_count' => $retryCount,
                'error_message' => substr($errorMessage, 0, 400),
                'failed_at' => now()
            ]);

            Log::error('Webhook notification permanently failed', [
                'log_id' => $this->notificationLog->id,
                'final_error' => substr($errorMessage, 0, 100)
            ]);

            // อัปเดตสถานะ notification
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
            $sentLogs = $logs->where('status', 'sent')->count(); // ใช้ 'sent' เท่านั้น
            $failedLogs = $logs->where('status', 'failed')->count();
            $pendingLogs = $logs->where('status', 'pending')->count();

            Log::info('Updating notification status', [
                'notification_id' => $notification->id,
                'total_logs' => $totalLogs,
                'sent_logs' => $sentLogs,
                'failed_logs' => $failedLogs,
                'pending_logs' => $pendingLogs
            ]);

            // คำนวณสถานะใหม่
            if ($pendingLogs == 0) {
                // ทุก log ประมวลผลเสร็จแล้ว
                if ($failedLogs == 0) {
                    $status = 'sent'; // ส่งสำเร็จทั้งหมด
                } elseif ($sentLogs == 0) {
                    $status = 'failed'; // ส่งไม่สำเร็จทั้งหมด
                } else {
                    $status = 'sent'; // ส่งสำเร็จบางส่วน
                }
            } else {
                $status = 'queued'; // ✅ ใช้ 'queued' แทน 'processing'
            }

            $notification->update([
                'status' => $status,
                'delivered_count' => $sentLogs,
                'failed_count' => $failedLogs,
                'sent_at' => ($pendingLogs == 0 && $sentLogs > 0) ? now() : null
            ]);

            Log::info('Notification status updated', [
                'notification_id' => $notification->id,
                'new_status' => $status
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update notification status', [
                'notification_id' => $this->notificationLog->notification_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * เมื่อ job ล้มเหลวถาวร
     */
    public function failed(\Exception $exception)
    {
        Log::error('Webhook job permanently failed', [
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
            Log::error('Failed to update log after job failure', [
                'log_id' => $this->notificationLog->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}