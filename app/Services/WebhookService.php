<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Notification;
use App\Models\NotificationLog;

class WebhookService
{
    /**
     * Send webhook notification to multiple URLs (notification + group webhooks)
     * ✅ ปรับปรุงให้รองรับการส่ง group webhooks อย่างเดียว
     */
    public function sendWebhookNotification(Notification $notification, array $groupWebhooks = [])
    {
        try {
            Log::info("WebhookService: Starting webhook notification", [
                'notification_id' => $notification->uuid,
                'notification_webhook' => $notification->webhook_url,
                'group_webhooks_count' => count($groupWebhooks),
                'group_webhooks' => array_keys($groupWebhooks)
            ]);

            $results = [];
            
            // 1. ส่ง webhook หลักของ notification (ถ้ามี และไม่เป็น null)
            if (!empty($notification->webhook_url)) {
                $mainResult = $this->sendSingleWebhook(
                    $notification->webhook_url,
                    $notification,
                    'notification_main',
                    [
                        'webhook_type' => 'notification',
                        'source' => 'main_notification'
                    ]
                );
                $results['main_webhook'] = $mainResult;
                
                Log::info("Main notification webhook sent", [
                    'url' => substr($notification->webhook_url, -30),
                    'success' => $mainResult['success']
                ]);
            } else {
                Log::info("No main webhook URL, skipping main webhook");
            }

            // 2. ส่ง webhook ของแต่ละ group (ถ้ามี)
            if (!empty($groupWebhooks)) {
                $results['group_webhooks'] = [];
                
                foreach ($groupWebhooks as $groupId => $webhookUrl) {
                    if (!empty($webhookUrl)) {
                        $groupResult = $this->sendSingleWebhook(
                            $webhookUrl,
                            $notification,
                            "group_{$groupId}",
                            [
                                'webhook_type' => 'group',
                                'group_id' => $groupId,
                                'source' => 'notification_group'
                            ]
                        );
                        $results['group_webhooks'][$groupId] = $groupResult;
                        
                        Log::info("Group webhook sent", [
                            'group_id' => $groupId,
                            'url' => substr($webhookUrl, -30),
                            'success' => $groupResult['success']
                        ]);
                    }
                }
            } else {
                Log::info("No group webhooks to process");
            }

            // สรุปผลลัพธ์
            $successCount = 0;
            $totalCount = 0;
            
            if (isset($results['main_webhook'])) {
                $totalCount++;
                if ($results['main_webhook']['success']) $successCount++;
            }
            
            if (isset($results['group_webhooks'])) {
                foreach ($results['group_webhooks'] as $groupResult) {
                    $totalCount++;
                    if ($groupResult['success']) $successCount++;
                }
            }

            // ✅ ถือว่าสำเร็จถ้ามีอย่างน้อย 1 webhook ส่งสำเร็จ หรือไม่มี webhook เลย
            $overallSuccess = $totalCount === 0 || $successCount > 0;

            Log::info("WebhookService: Completed webhook notification", [
                'notification_id' => $notification->uuid,
                'total_webhooks' => $totalCount,
                'successful_webhooks' => $successCount,
                'failed_webhooks' => $totalCount - $successCount,
                'overall_success' => $overallSuccess
            ]);

            return [
                'success' => $overallSuccess,
                'total_sent' => $totalCount,
                'successful' => $successCount,
                'failed' => $totalCount - $successCount,
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error("WebhookService: Failed to send webhook notification", [
                'notification_id' => $notification->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'total_sent' => 0,
                'successful' => 0,
                'failed' => 1
            ];
        }
    }

    /**
     * Send webhook to single URL
     */
    public function sendSingleWebhook(string $webhookUrl, Notification $notification, string $identifier, array $metadata = [])
    {
        try {
            Log::info("Sending single webhook", [
                'webhook_url' => substr($webhookUrl, -30),
                'notification_id' => $notification->uuid,
                'identifier' => $identifier,
                'metadata' => $metadata
            ]);

            // เตรียม payload
            $payload = $this->prepareWebhookPayload($notification, $metadata);
            
            // ส่ง webhook
            $response = $this->sendHttpWebhook($webhookUrl, $payload);
            
            // สร้าง log entry
            $this->createWebhookLog($notification, $webhookUrl, $identifier, $payload, $response, $metadata);

            if ($response['success']) {
                Log::info("Single webhook sent successfully", [
                    'webhook_url' => substr($webhookUrl, -30),
                    'status_code' => $response['status_code'],
                    'identifier' => $identifier
                ]);

                return [
                    'success' => true,
                    'status_code' => $response['status_code'],
                    'response_body' => $response['response_body'] ?? '',
                    'identifier' => $identifier,
                    'webhook_url' => $webhookUrl
                ];
            } else {
                Log::warning("Single webhook failed", [
                    'webhook_url' => substr($webhookUrl, -30),
                    'error' => $response['error'],
                    'identifier' => $identifier
                ]);

                return [
                    'success' => false,
                    'error' => $response['error'],
                    'status_code' => $response['status_code'] ?? null,
                    'identifier' => $identifier,
                    'webhook_url' => $webhookUrl
                ];
            }

        } catch (\Exception $e) {
            Log::error("Single webhook exception", [
                'webhook_url' => substr($webhookUrl, -30),
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'identifier' => $identifier,
                'webhook_url' => $webhookUrl
            ];
        }
    }

    /**
     * เตรียม payload สำหรับ webhook
     */
    private function prepareWebhookPayload(Notification $notification, array $metadata = [])
    {
        // Base payload
        $payload = [
            'notification_id' => (string) $notification->uuid,
            'subject' => $notification->subject,
            'message' => $notification->body_text ?: strip_tags($notification->body_html ?? ''),
            'priority' => $notification->priority,
            'channels' => $notification->channels,
            'timestamp' => now()->toISOString(),
            'status' => 'sent',
            'webhook_type' => $metadata['webhook_type'] ?? 'notification',
            'source' => $metadata['source'] ?? 'notification_system'
        ];

        // เพิ่มข้อมูล group ถ้ามี
        if (isset($metadata['group_id'])) {
            $payload['group_id'] = $metadata['group_id'];
        }

        // เพิ่ม variables ถ้ามี
        if (!empty($notification->variables)) {
            $payload['variables'] = $notification->variables;
        }

        // เพิ่มข้อมูล recipients ถ้ามี
        if (!empty($notification->recipients)) {
            $payload['recipients_count'] = count($notification->recipients);
        }

        if (!empty($notification->recipient_groups)) {
            $payload['recipient_groups_count'] = count($notification->recipient_groups);
        }

        return $payload;
    }

    /**
     * ส่ง HTTP webhook
     */
    private function sendHttpWebhook(string $webhookUrl, array $payload)
    {
        try {
            // ใช้ Teams notification library สำหรับความเข้ากันได้
            $teamsNotification = new \Osama\LaravelTeamsNotification\TeamsNotification();
            
            // เตรียม message และ details สำหรับ Teams format
            $message = $payload['subject'] ?: 'Smart Notification Alert';
            
            $details = [
                'Notification ID' => $payload['notification_id'],
                'Priority' => strtoupper($payload['priority']),
                'Sent At' => now()->format('Y-m-d H:i:s'),
                'Webhook Type' => ucfirst($payload['webhook_type']),
                'Source' => ucfirst(str_replace('_', ' ', $payload['source']))
            ];

            // เพิ่ม variables ถ้ามี
            // if (!empty($payload['variables'])) {
            //     foreach ($payload['variables'] as $key => $value) {
            //         $details[ucfirst(str_replace('_', ' ', $key))] = (string) $value;
            //     }
            // }

            // เพิ่มข้อมูลจาก message ถ้าเป็น JSON หรือมี structure
            if (!empty($payload['message'])) {
                $this->parseMessageIntoDetails($payload['message'], $details);
            }

            Log::info("Sending HTTP webhook", [
                'webhook_url' => substr($webhookUrl, -30),
                'message' => substr($message, 0, 100),
                'details_count' => count($details)
            ]);

            $response = $teamsNotification->sendMessageSetWebhook($webhookUrl, $message, $details);
            
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            return [
                'success' => $statusCode >= 200 && $statusCode < 300,
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'payload_sent' => $payload
            ];

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage(),
                'type' => 'connection_error'
            ];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
            
            return [
                'success' => false,
                'error' => 'HTTP error: ' . $e->getMessage(),
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'type' => 'http_error'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'General error: ' . $e->getMessage(),
                'type' => 'general_error'
            ];
        }
    }

    /**
     * แยก message เป็น details
     */
    private function parseMessageIntoDetails(string $message, array &$details)
    {
        // ลองแปลง JSON ก่อน
        $jsonData = json_decode($message, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            foreach ($jsonData as $key => $value) {
                $details[ucfirst(str_replace('_', ' ', $key))] = (string) $value;
            }
            return;
        }

        // แยกข้อมูลแบบ key: value
        $lines = explode("\n", $message);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $details[trim($key)] = trim($value);
            }
        }

        // ถ้าไม่มี key: value format ให้ใช้เป็น message
        if (count($details) <= 5) { // มีแค่ข้อมูลพื้นฐาน
            $details['Message'] = substr($message, 0, 500); // จำกัดความยาว
        }
    }

    /**
     * สร้าง webhook log
     */
    private function createWebhookLog(Notification $notification, string $webhookUrl, string $identifier, array $payload, array $response, array $metadata)
    {
        try {
            // หา existing log หรือสร้างใหม่
            $log = NotificationLog::where('notification_id', $notification->id)
                                 ->where('channel', 'webhook')
                                 ->where('webhook_url', $webhookUrl)
                                 ->first();

            if (!$log) {
                $log = NotificationLog::create([
                    'notification_id' => $notification->id,
                    'recipient_email' => 'system@webhook',
                    'recipient_name' => $this->getWebhookLogName($metadata),
                    'channel' => 'webhook',
                    'status' => 'pending',
                    'webhook_url' => $webhookUrl,
                ]);
            }

            // อัพเดต log ตามผลลัพธ์
            $updateData = [
                'webhook_response_code' => $response['status_code'] ?? null,
                'response_data' => [
                    'success' => $response['success'],
                    'status_code' => $response['status_code'] ?? null,
                    'response_body' => substr($response['response_body'] ?? '', 0, 1000),
                    'webhook_type' => $metadata['webhook_type'] ?? 'notification',
                    'identifier' => $identifier,
                    'timestamp' => now()->toISOString()
                ],
                'content_sent' => [
                    'payload' => $payload,
                    'metadata' => $metadata
                ]
            ];

            if ($response['success']) {
                $updateData['status'] = 'sent';
                $updateData['sent_at'] = now();
                $updateData['delivered_at'] = now();
            } else {
                $updateData['status'] = 'failed';
                $updateData['error_message'] = substr($response['error'] ?? 'Unknown error', 0, 500);
                $updateData['retry_count'] = ($log->retry_count ?? 0) + 1;
                $updateData['failed_at'] = now();
            }

            $log->update($updateData);

            Log::info("Webhook log updated", [
                'log_id' => $log->id,
                'status' => $updateData['status'],
                'webhook_url' => substr($webhookUrl, -30),
                'identifier' => $identifier
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to create/update webhook log", [
                'notification_id' => $notification->id,
                'webhook_url' => substr($webhookUrl, -30),
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * สร้างชื่อสำหรับ webhook log
     */
    private function getWebhookLogName(array $metadata)
    {
        if ($metadata['webhook_type'] === 'group' && isset($metadata['group_id'])) {
            return "Group Webhook #{$metadata['group_id']}";
        }
        
        return "Webhook System";
    }

    /**
     * Test webhook connectivity
     */
    public function testWebhook(string $webhookUrl, array $testData = [])
    {
        try {
            $payload = array_merge([
                'test' => true,
                'message' => 'Test webhook from Smart Notification System',
                'timestamp' => now()->toISOString(),
                'test_id' => uniqid('test_'),
                'system' => 'Smart Notification System'
            ], $testData);

            $result = $this->sendHttpWebhook($webhookUrl, $payload);

            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 'Webhook test successful' : 'Webhook test failed',
                'details' => $result,
                'test_payload' => $payload
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Webhook test failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get webhook logs for notification
     */
    public function getWebhookLogs(Notification $notification)
    {
        return NotificationLog::where('notification_id', $notification->id)
                             ->where('channel', 'webhook')
                             ->orderBy('created_at', 'desc')
                             ->get();
    }

    /**
     * Retry failed webhook
     */
    public function retryWebhook(NotificationLog $log)
    {
        if ($log->channel !== 'webhook') {
            throw new \Exception('Log is not a webhook log');
        }

        if (empty($log->webhook_url)) {
            throw new \Exception('No webhook URL found in log');
        }

        // ดึงข้อมูลจาก content_sent
        $content = $log->content_sent ?? [];
        $payload = $content['payload'] ?? [];
        $metadata = $content['metadata'] ?? [];

        if (empty($payload)) {
            // สร้าง payload ใหม่จาก notification
            $notification = $log->notification;
            $payload = $this->prepareWebhookPayload($notification, $metadata);
        }

        // ส่งใหม่
        $response = $this->sendHttpWebhook($log->webhook_url, $payload);
        
        // อัพเดต log
        $this->createWebhookLog(
            $log->notification, 
            $log->webhook_url, 
            'retry_' . time(), 
            $payload, 
            $response, 
            $metadata
        );

        return $response;
    }
}