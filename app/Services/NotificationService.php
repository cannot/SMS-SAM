<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationGroup;
use App\Models\NotificationTemplate;
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

    // Valid priority values that match database constraint
    const VALID_PRIORITIES = ['low', 'medium', 'normal', 'high', 'urgent'];
    
    // Valid status values that match database constraint
    const VALID_STATUSES = ['draft', 'queued', 'scheduled', 'processing', 'sent', 'failed', 'cancelled'];

    public function __construct($teamsService = null, $emailService = null)
    {
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
    // MAIN PROCESS METHODS (Admin & API Compatible)
    // ===============================================

    public function processNotification(Notification $notification)
    {
        try {
            Log::info("Processing notification START", [
                'uuid' => $notification->uuid,
                'status' => $notification->status,
                'priority' => $notification->priority,
                'channels' => $notification->channels,
                'source' => $this->detectSource($notification),
                'has_processed_content' => !empty($notification->processed_content),
                'has_personalized_content' => !empty($notification->processed_content['personalized_content'] ?? [])
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
    
            // ✅ ตรวจสอบว่ามี personalized content หรือไม่อย่างถูกต้อง
            $hasPersonalizedContent = !empty($notification->processed_content['personalized_content'] ?? []);
            
            Log::info("Processing decision", [
                'has_processed_content' => !empty($notification->processed_content),
                'has_personalized_content' => $hasPersonalizedContent,
                'personalized_count' => count($notification->processed_content['personalized_content'] ?? []),
                'processing_method' => $hasPersonalizedContent ? 'Enhanced' : 'Standard'
            ]);
            
            // ใช้เมธอดที่เหมาะสมตามประเภท
            if ($hasPersonalizedContent) {
                Log::info("Using Enhanced Processing (with personalization)");
                return $this->processNotificationEnhanced($notification);
            } else {
                Log::info("Using Standard Processing (no personalization)");
                return $this->processNotificationStandard($notification);
            }
    
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
     * Process notification แบบ Enhanced (สำหรับ API ที่มี personalization)
     */
    public function processNotificationEnhancedx(Notification $notification)
    {
        try {
            Log::info("Processing enhanced notification", [
                'notification_id' => $notification->uuid,
                'channels' => $notification->channels,
                'has_personalized_content' => !empty($notification->processed_content['personalized_content'] ?? [])
            ]);
            
            $recipients = $this->getRecipientsForNotificationEnhanced($notification);
            $hasWebhook = in_array('webhook', $notification->channels);
            
            if (empty($recipients) && !$hasWebhook) {
                throw new \Exception('No valid recipients found');
            }
            
            // สร้าง logs สำหรับแต่ละ recipient และ channel
            $this->createNotificationLogsEnhanced($notification, $recipients);
            
            // ประมวลผลแต่ละ channel
            foreach ($notification->channels as $channel) {
                $this->processChannelEnhanced($notification, $channel, $recipients);
            }
            
            // อัพเดตสถานะ
            $this->updateNotificationStatusAfterProcessing($notification);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Enhanced notification processing failed", [
                'notification_id' => $notification->uuid,
                'error' => $e->getMessage()
            ]);
            
            $notification->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    public function processNotificationEnhanced(Notification $notification)
    {
        try {
            Log::info("DEBUG: Processing enhanced notification", [
                'notification_id' => $notification->uuid,
                'channels' => $notification->channels,
                'subject' => $notification->subject,
                'subject_length' => strlen($notification->subject ?? ''),
                'body_text' => substr($notification->body_text ?? '', 0, 200),
                'body_text_length' => strlen($notification->body_text ?? ''),
                'has_processed_content' => !empty($notification->processed_content),
                'processed_content_keys' => !empty($notification->processed_content) ? array_keys($notification->processed_content) : [],
                'personalized_content_count' => count($notification->processed_content['personalized_content'] ?? []),
                'variables' => $notification->variables ?? [],
                'webhook_url' => $notification->webhook_url
            ]);
            
            // ตรวจสอบ processed_content แบบละเอียด
            if (!empty($notification->processed_content)) {
                $processedContent = $notification->processed_content;
                
                Log::info("DEBUG: Processed content details", [
                    'base_subject' => $processedContent['subject'] ?? 'MISSING',
                    'base_body_text' => substr($processedContent['body_text'] ?? '', 0, 200),
                    'base_variables_count' => count($processedContent['base_variables'] ?? []),
                    'personalized_emails' => array_keys($processedContent['personalized_content'] ?? [])
                ]);
                
                // ตรวจสอบ personalized content แต่ละคน
                foreach ($processedContent['personalized_content'] ?? [] as $email => $personalContent) {
                    Log::info("DEBUG: Personalized content for email", [
                        'email' => $email,
                        'personalized_subject' => $personalContent['subject'] ?? 'MISSING',
                        'personalized_subject_length' => strlen($personalContent['subject'] ?? ''),
                        'personalized_body_text' => substr($personalContent['body_text'] ?? '', 0, 100),
                        'has_variables' => !empty($personalContent['variables']),
                        'recipient_name_in_variables' => $personalContent['variables']['recipient_name'] ?? 'NOT_SET'
                    ]);
                }
            }
            
            $recipients = $this->getRecipientsForNotificationEnhanced($notification);
            $hasWebhook = in_array('webhook', $notification->channels);
            
            Log::info("DEBUG: Recipients and webhook info", [
                'recipients_count' => count($recipients),
                'has_webhook' => $hasWebhook,
                'recipient_emails' => array_column($recipients, 'email')
            ]);
            
            if (empty($recipients) && !$hasWebhook) {
                throw new \Exception('No valid recipients found');
            }
            
            // สร้าง logs สำหรับแต่ละ recipient และ channel
            $this->createNotificationLogsEnhanced($notification, $recipients);
            
            // ประมวลผลแต่ละ channel
            foreach ($notification->channels as $channel) {
                Log::info("DEBUG: Processing channel", [
                    'channel' => $channel,
                    'notification_id' => $notification->uuid
                ]);
                
                $this->processChannelEnhanced($notification, $channel, $recipients);
            }
            
            // อัพเดตสถานะ
            $this->updateNotificationStatusAfterProcessing($notification);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Enhanced notification processing failed", [
                'notification_id' => $notification->uuid,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            $notification->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Process notification แบบ Standard (สำหรับ Admin และ API ธรรมดา)
     */
    public function processNotificationStandard(Notification $notification)
    {
        try {
            // Check for webhook-only notifications
            $hasWebhookOnly = count($notification->channels) === 1 && in_array('webhook', $notification->channels);
            
            // Get recipients
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
                    $actualRecipientCount = 1;
                } else {
                    $actualRecipientCount = count(array_filter($recipients, function($r) {
                        return $r['email'] !== 'system@webhook';
                    }));
                }
                
                $notification->update([
                    'total_recipients' => $actualRecipientCount,
                    'total_logs' => $totalLogs
                ]);
            });

            // Queue the notification
            $this->queueNotificationSafely($notification);

            Log::info('Standard notification processed successfully', [
                'notification_id' => $notification->id,
                'total_logs' => $totalLogs
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Standard notification processing failed", [
                'notification_id' => $notification->uuid,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    // ===============================================
    // ENHANCED PERSONALIZATION METHODS (API)
    // ===============================================

    private function getRecipientsForNotificationEnhanced1(Notification $notification): array
    {
        $recipients = [];
        $personalizedContent = $notification->processed_content['personalized_content'] ?? [];
        
        Log::info("Getting recipients for enhanced processing", [
            'notification_id' => $notification->uuid,
            'personalized_emails' => array_keys($personalizedContent),
            'direct_recipients' => $notification->recipients ?? [],
            'recipient_groups' => $notification->recipient_groups ?? []
        ]);
        
        // ✅ ใช้ emails จาก personalized_content เป็น source of truth
        foreach ($personalizedContent as $email => $content) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // ✅ แก้ไข: ลองหาชื่อจาก database ด้วยวิธีที่ถูกต้อง
                $user = \App\Models\User::where('email', $email)->first();
                
                // ✅ เพิ่มการตรวจสอบชื่อแบบละเอียด
                $name = null;
                if ($user) {
                    // ลำดับความสำคัญในการเลือกชื่อ
                    $name = $user->display_name ?? $user->name ?? $user->first_name;
                    
                    // ถ้าไม่มีชื่อเลย ให้รวม first_name + last_name
                    if (!$name && ($user->first_name || $user->last_name)) {
                        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                    }
                    
                    Log::info("Found user in database", [
                        'email' => $email,
                        'display_name' => $user->display_name,
                        'name' => $user->name,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'selected_name' => $name
                    ]);
                }
                
                // ถ้าไม่พบใน database หรือไม่มีชื่อ ให้ extract จาก email
                if (!$name) {
                    $name = $this->extractNameFromEmail($email);
                    Log::info("Name extracted from email", [
                        'email' => $email,
                        'extracted_name' => $name
                    ]);
                }
                
                $recipients[] = [
                    'email' => $email,
                    'name' => $name,
                    'personalized_content' => $content
                ];
            }
        }
        
        Log::info("Recipients prepared for enhanced processing", [
            'recipients_count' => count($recipients),
            'emails' => array_column($recipients, 'email'),
            'names' => array_column($recipients, 'name')
        ]);
        
        return $recipients;
    }

    private function getRecipientsForNotificationEnhanced(Notification $notification): array
    {
        $recipients = [];
        $personalizedContent = $notification->processed_content['personalized_content'] ?? [];
        
        Log::info("Getting recipients for enhanced processing", [
            'notification_id' => $notification->uuid,
            'personalized_emails' => array_keys($personalizedContent),
            'direct_recipients' => $notification->recipients ?? [],
            'recipient_groups' => $notification->recipient_groups ?? []
        ]);
        
        // ✅ ใช้ emails จาก personalized_content เป็น source of truth
        // แต่ต้องตรวจสอบให้แน่ใจว่าไม่ซ้ำกัน
        $processedEmails = [];
        
        foreach ($personalizedContent as $email => $content) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $processedEmails)) {
                // ✅ ลองหาชื่อจาก database ด้วยวิธีที่ถูกต้อง
                $user = \App\Models\User::where('email', $email)->first();
                
                // ✅ เพิ่มการตรวจสอบชื่อแบบละเอียด
                $name = null;
                if ($user) {
                    // ลำดับความสำคัญในการเลือกชื่อ
                    $name = $user->display_name ?? $user->name ?? $user->first_name;
                    
                    // ถ้าไม่มีชื่อเลย ให้รวม first_name + last_name
                    if (!$name && ($user->first_name || $user->last_name)) {
                        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                    }
                    
                    Log::debug("Found user in database", [
                        'email' => $email,
                        'display_name' => $user->display_name,
                        'name' => $user->name,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'selected_name' => $name
                    ]);
                }
                
                // ถ้าไม่พบใน database หรือไม่มีชื่อ ให้ extract จาก email
                if (!$name) {
                    $name = $this->extractNameFromEmail($email);
                    Log::debug("Name extracted from email", [
                        'email' => $email,
                        'extracted_name' => $name
                    ]);
                }
                
                $recipients[] = [
                    'email' => $email,
                    'name' => $name,
                    'personalized_content' => $content
                ];
                
                $processedEmails[] = $email; // ✅ Track processed emails
            } else if (in_array($email, $processedEmails)) {
                Log::warning("Duplicate email detected in personalized content", [
                    'email' => $email,
                    'notification_id' => $notification->uuid
                ]);
            }
        }
        
        Log::info("Recipients prepared for enhanced processing", [
            'recipients_count' => count($recipients),
            'unique_emails' => count($processedEmails),
            'emails' => array_column($recipients, 'email'),
            'names' => array_column($recipients, 'name')
        ]);
        
        return $recipients;
    }
    /**
     * สร้าง notification logs พร้อม personalized content
     */
    private function createNotificationLogsEnhancedz(Notification $notification, array $recipients)
    {
        foreach ($notification->channels as $channel) {
            if ($channel === 'webhook') {
                // Webhook มี log เดียว
                NotificationLog::create([
                    'notification_id' => $notification->id,
                    'recipient_email' => 'system@webhook',
                    'recipient_name' => 'Webhook System',
                    'channel' => 'webhook',
                    'status' => 'pending',
                    'webhook_url' => $notification->webhook_url,
                    'personalized_content' => $notification->processed_content,
                ]);
            } else {
                // สร้าง log สำหรับแต่ละ recipient
                foreach ($recipients as $recipient) {
                    NotificationLog::create([
                        'notification_id' => $notification->id,
                        'recipient_email' => $recipient['email'],
                        'recipient_name' => $recipient['name'],
                        'channel' => $channel,
                        'status' => 'pending',
                        'personalized_content' => $recipient['personalized_content'],
                    ]);
                }
            }
        }
    }

    private function createNotificationLogsEnhanced(Notification $notification, array $recipients)
    {
        $createdLogs = [];
        
        foreach ($notification->channels as $channel) {
            if ($channel === 'webhook') {
                // Webhook มี log เดียว - ตรวจสอบว่ายังไม่มี
                $existingWebhookLog = NotificationLog::where('notification_id', $notification->id)
                                                  ->where('channel', 'webhook')
                                                  ->first();
                
                if (!$existingWebhookLog) {
                    $webhookLog = NotificationLog::create([
                        'notification_id' => $notification->id,
                        'recipient_email' => 'system@webhook',
                        'recipient_name' => 'Webhook System',
                        'channel' => 'webhook',
                        'status' => 'pending',
                        'webhook_url' => $notification->webhook_url,
                        'personalized_content' => $notification->processed_content,
                    ]);
                    
                    $createdLogs[] = $webhookLog;
                    
                    Log::info("Created webhook log", [
                        'notification_id' => $notification->id,
                        'log_id' => $webhookLog->id
                    ]);
                } else {
                    Log::info("Webhook log already exists", [
                        'notification_id' => $notification->id,
                        'existing_log_id' => $existingWebhookLog->id
                    ]);
                }
            } else {
                // สร้าง log สำหรับแต่ละ recipient (ตรวจสอบ duplicate)
                foreach ($recipients as $recipient) {
                    // ✅ ตรวจสอบว่ามี log สำหรับ email และ channel นี้อยู่แล้วหรือไม่
                    $existingLog = NotificationLog::where('notification_id', $notification->id)
                                                 ->where('recipient_email', $recipient['email'])
                                                 ->where('channel', $channel)
                                                 ->first();
                    
                    if (!$existingLog) {
                        $log = NotificationLog::create([
                            'notification_id' => $notification->id,
                            'recipient_email' => $recipient['email'],
                            'recipient_name' => $recipient['name'],
                            'channel' => $channel,
                            'status' => 'pending',
                            'personalized_content' => $recipient['personalized_content'] ?? null,
                        ]);
                        
                        $createdLogs[] = $log;
                        
                        Log::info("Created recipient log", [
                            'notification_id' => $notification->id,
                            'log_id' => $log->id,
                            'recipient_email' => $recipient['email'],
                            'channel' => $channel
                        ]);
                    } else {
                        Log::warning("Log already exists for recipient and channel", [
                            'notification_id' => $notification->id,
                            'recipient_email' => $recipient['email'],
                            'channel' => $channel,
                            'existing_log_id' => $existingLog->id
                        ]);
                    }
                }
            }
        }
        
        Log::info("createNotificationLogsEnhanced completed", [
            'notification_id' => $notification->id,
            'total_logs_created' => count($createdLogs),
            'channels_processed' => $notification->channels,
            'recipients_processed' => count($recipients)
        ]);
        
        return $createdLogs;
    }
    /**
     * ประมวลผลแต่ละ channel พร้อม personalization
     */
    private function processChannelEnhanced(Notification $notification, string $channel, array $recipients)
    {
        try {
            switch ($channel) {
                case 'email':
                    $this->processEmailChannelEnhanced($notification, $recipients);
                    break;
                    
                case 'teams':
                    $this->processTeamsChannelEnhanced($notification, $recipients);
                    break;
                    
                case 'webhook':
                    $this->processWebhookChannelEnhanced($notification);
                    break;
                    
                default:
                    Log::warning("Unknown channel: {$channel}");
            }
        } catch (\Exception $e) {
            Log::error("Channel processing failed", [
                'channel' => $channel,
                'notification_id' => $notification->uuid,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function extractFirstNameFromEmail($email)
    {
        try {
            $fullName = $this->extractNameFromEmail($email);
            $parts = explode(' ', trim($fullName));
            return $parts[0] ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    private function extractLastNameFromEmail($email)
    {
        try {
            $fullName = $this->extractNameFromEmail($email);
            $parts = explode(' ', trim($fullName));
            if (count($parts) > 1) {
                array_shift($parts); // ลบ first name ออก
                return implode(' ', $parts);
            }
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    private function processEmailChannelEnhanced(Notification $notification, array $recipients)
    {
        Log::info("processEmailChannelEnhanced START", [
            'notification_id' => $notification->uuid,
            'recipients_count' => count($recipients),
            'has_processed_content' => !empty($notification->processed_content),
            'personalized_content_count' => count($notification->processed_content['personalized_content'] ?? [])
        ]);
    
        foreach ($recipients as $recipient) {
            $log = NotificationLog::where('notification_id', $notification->id)
                                 ->where('recipient_email', $recipient['email'])
                                 ->where('channel', 'email')
                                 ->first();
            
            if (!$log) {
                Log::warning("No log found for recipient", [
                    'notification_id' => $notification->id,
                    'recipient_email' => $recipient['email']
                ]);
                continue;
            }
            
            try {

                $personalizedData = $notification->processed_content['personalized_content'][$recipient['email']] ?? null;
                
                if ($personalizedData && !empty($personalizedData['subject'])) {
                    // ✅ ใช้ personalized content
                    $content = $personalizedData;
                } else {
                    
                    $template = $notification->template;
                    $recipientVariables = array_merge(
                        $notification->processed_content['base_variables'] ?? [],
                        [
                            'recipient_email' => $recipient['email'],
                            'recipient_name' => $recipient['name'],
                            'recipient_first_name' => $this->extractFirstNameFromEmail($recipient['email']),
                            'recipient_last_name' => $this->extractLastNameFromEmail($recipient['email']),
                            'user_name' => $recipient['name'],
                            'user_email' => $recipient['email'],
                            'user_first_name' => $this->extractFirstNameFromEmail($recipient['email']),
                            'user_last_name' => $this->extractLastNameFromEmail($recipient['email']),
                        ]
                    );
                    
                    $baseSubject = $template ? $template->subject_template : $notification->subject;
                    $baseBodyHtml = $template ? $template->body_html_template : $notification->body_html;
                    $baseBodyText = $template ? $template->body_text_template : $notification->body_text;
                    
                    $content = [
                        'subject' => $this->replaceVariables($baseSubject, $recipientVariables),
                        'body_html' => $this->replaceVariables($baseBodyHtml, $recipientVariables),
                        'body_text' => $this->replaceVariables($baseBodyText, $recipientVariables),
                    ];
                    
                    // ตรวจสอบ subject
                    if (empty($content['subject'])) {
                        $content['subject'] = 'Smart Notification for ' . $recipient['name'];
                    }
                }

                // ✅ เตรียม attachments
                $attachmentPaths = [];
                if (!empty($notification->attachments)) {
                    foreach ($notification->attachments as $attachment) {
                        if ($attachment['type'] !== 'url_failed' && !empty($attachment['path'])) {
                            $fullPath = storage_path('app/' . $attachment['path']);
                            if (file_exists($fullPath)) {
                                $attachmentPaths[] = $fullPath;
                            }
                        }
                    }
                    
                    Log::info("Prepared attachments for email", [
                        'recipient' => $recipient['email'],
                        'attachment_count' => count($attachmentPaths),
                        'total_attachments' => count($notification->attachments),
                        'attachment_paths' => $attachmentPaths
                    ]);
                }
                
                // Log::info("Sending email with content", [
                //     'email' => $recipient['email'],
                //     'subject' => $content['subject'],
                //     'has_html' => !empty($content['body_html']),
                //     'has_text' => !empty($content['body_text'])
                // ]);
                
                // // ส่งอีเมล
                // Mail::send([], [], function ($message) use ($content, $recipient, $notification) {
                //     $message->to($recipient['email'], $recipient['name'])
                //            ->subject($content['subject']);
                    
                //     // ใส่เนื้อหา
                //     if (!empty($content['body_html'])) {
                //         $message->html($content['body_html']);
                //     }
                    
                //     if (!empty($content['body_text'])) {
                //         $message->text($content['body_text']);
                //     }
                    
                //     $fromEmail = config('mail.from.address', 'noreply@company.com');
                //     $fromName = config('mail.from.name', config('app.name'));
                //     $message->from($fromEmail, $fromName);
                // });
                
                // // อัพเดต log
                // $log->update([
                //     'status' => 'sent',
                //     'sent_at' => now(),
                //     'content_sent' => $content
                // ]);
                
                // Log::info("Email sent successfully", [
                //     'recipient' => $recipient['email'],
                //     'subject' => $content['subject'],
                //     'notification_id' => $notification->uuid
                // ]);

                // $log->update([
                // 'content_sent' => array_merge($content, [
                //     'attachment_paths' => $attachmentPaths,
                //     'has_attachments' => !empty($attachmentPaths)
                // ])

                $log->update([
                    'personalized_content' => $content,
                    'attachment_paths' => $attachmentPaths,
                    'attachment_info' => [
                        'count' => count($attachmentPaths),
                        'has_attachments' => !empty($attachmentPaths),
                        'paths' => $attachmentPaths
                    ]
                ]);
                
                // ส่งผ่าน SendEmailNotification Job
                // SendEmailNotification::dispatch($log)
                //     ->delay($this->calculateDelay($notification->priority))
                //     ->onQueue($this->getQueueName($notification->priority));
                
                // Log::info("Email job dispatched with attachments", [
                //     'recipient' => $recipient['email'],
                //     'subject' => substr($content['subject'], 0, 100),
                //     'attachment_count' => count($attachmentPaths)
                // ]);

                Log::info("Sending email directly (enhanced)", [
                    'recipient' => $recipient['email'],
                    'subject' => substr($content['subject'], 0, 100),
                    'attachment_count' => count($attachmentPaths)
                ]);
                
                $this->sendEmailDirectlyFromService($content, $recipient, $attachmentPaths, $log);
            // ]);
            
            // ส่งผ่าน SendEmailNotification Job
            // SendEmailNotification::dispatch($log)
            //     ->delay($this->calculateDelay($notification->priority))
            //     ->onQueue($this->getQueueName($notification->priority));
            
            // Log::info("Email job dispatched with attachments", [
            //     'recipient' => $recipient['email'],
            //     'subject' => substr($content['subject'], 0, 100),
            //     'attachment_count' => count($attachmentPaths)
            // ]);
                
            } catch (\Exception $e) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'retry_count' => ($log->retry_count ?? 0) + 1
                ]);
                
                Log::error("Failed to send email", [
                    'recipient' => $recipient['email'],
                    'error' => $e->getMessage(),
                    'notification_id' => $notification->uuid
                ]);
            }
        }
        
        Log::info("processEmailChannelEnhanced COMPLETED", [
            'notification_id' => $notification->uuid,
            'recipients_processed' => count($recipients)
        ]);
    }
    
    private function sendEmailDirectlyFromServicex($content, $recipient, $attachmentPaths, $log)
    {
        try {
            Log::info("Sending email directly from service with attachments", [
                'recipient' => $recipient['email'],
                'subject' => substr($content['subject'], 0, 100),
                'attachment_count' => count($attachmentPaths),
                'attachment_paths' => $attachmentPaths
            ]);

            // ✅ ส่งพร้อมไฟล์แนบถ้ามี
            if (!empty($attachmentPaths)) {
                $emailData = [
                    'subject' => $content['subject'],
                    'body_html' => $content['body_html'],
                    'body_text' => $content['body_text'],
                    'recipient_name' => $recipient['name'],
                    'recipient_email' => $recipient['email'],
                    'format' => !empty($content['body_html']) ? 'html' : 'text',
                ];

                // ส่งผ่าน NotificationMail พร้อมไฟล์แนบ
                \Illuminate\Support\Facades\Mail::to($recipient['email'], $recipient['name'])
                    ->send(new \App\Mail\NotificationMail($emailData, 'notification', $attachmentPaths));
                    
            } else {
                // ส่งแบบไม่มีไฟล์แนบ
                \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($content, $recipient) {
                    $message->to($recipient['email'], $recipient['name'])
                        ->subject($content['subject']);
                    
                    if (!empty($content['body_html'])) {
                        $message->html($content['body_html']);
                    }
                    
                    if (!empty($content['body_text'])) {
                        $message->text($content['body_text']);
                    }
                    
                    $fromEmail = config('mail.from.address', 'noreply@company.com');
                    $fromName = config('mail.from.name', config('app.name'));
                    $message->from($fromEmail, $fromName);
                });
            }
            
            // อัพเดตสถานะสำเร็จ
            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'delivered_at' => now(),
                'content_sent' => array_merge($content, [
                    'attachment_count' => count($attachmentPaths),
                    'attachments_sent' => array_map('basename', $attachmentPaths)
                ]),
                'response_data' => [
                    'success' => true,
                    'method' => 'service_direct_with_attachments',
                    'attachment_count' => count($attachmentPaths),
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
            Log::info("Email sent successfully from service with attachments", [
                'recipient' => $recipient['email'],
                'subject' => $content['subject'],
                'attachment_count' => count($attachmentPaths),
                'method' => 'service_direct_with_attachments'
            ]);
            
        } catch (\Exception $e) {
            // อัพเดตสถานะล้มเหลว
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => ($log->retry_count ?? 0) + 1,
                'failed_at' => now()
            ]);
            
            Log::error("Failed to send email from service", [
                'recipient' => $recipient['email'],
                'error' => $e->getMessage(),
                'attachment_count' => count($attachmentPaths)
            ]);
            
            throw $e;
        }
    }

    private function sendEmailDirectlyFromService($content, $recipient, $attachmentPaths, $log)
    {
        try {
            Log::info("Sending email directly from service with ImprovedEmailService", [
                'recipient' => $recipient['email'],
                'subject' => substr($content['subject'], 0, 100),
                'attachment_count' => count($attachmentPaths),
                'attachment_paths' => $attachmentPaths
            ]);

            // ✅ ใช้ ImprovedEmailService แทน Laravel Mail โดยตรง
            $emailService = new \App\Services\ImprovedEmailService();
            
            // เตรียมข้อมูลอีเมล
            $emailData = [
                'to' => $recipient['email'],
                'subject' => $content['subject'],
                'body_html' => $content['body_html'],
                'body_text' => $content['body_text'],
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'attachments' => $attachmentPaths
            ];

            // ส่งผ่าน ImprovedEmailService ที่มี auto-fallback
            $result = $emailService->sendEmail($emailData);
            
            if ($result['success']) {
                // อัพเดตสถานะสำเร็จ
                $log->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'delivered_at' => now(),
                    'content_sent' => array_merge($content, [
                        'attachment_count' => count($attachmentPaths),
                        'attachments_sent' => array_map('basename', $attachmentPaths)
                    ]),
                    'response_data' => [
                        'success' => true,
                        'method' => $result['method'] ?? 'improved_email_service',
                        'attachment_count' => count($attachmentPaths),
                        'timestamp' => now()->toISOString()
                    ]
                ]);
                
                Log::info("Email sent successfully from service with ImprovedEmailService", [
                    'recipient' => $recipient['email'],
                    'subject' => $content['subject'],
                    'attachment_count' => count($attachmentPaths),
                    'method' => $result['method'] ?? 'improved_email_service'
                ]);
            } else {
                throw new \Exception($result['error'] ?? 'ImprovedEmailService failed');
            }
            
        } catch (\Exception $e) {
            // อัพเดตสถานะล้มเหลว
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => ($log->retry_count ?? 0) + 1,
                'failed_at' => now()
            ]);
            
            Log::error("Failed to send email from service with ImprovedEmailService", [
                'recipient' => $recipient['email'],
                'error' => $e->getMessage(),
                'attachment_count' => count($attachmentPaths)
            ]);
            
            throw $e;
        }
    }

    /**
     * ประมวลผล Teams channel พร้อม personalization
     */
    private function processTeamsChannelEnhanced(Notification $notification, array $recipients)
    {
        foreach ($recipients as $recipient) {
            $log = NotificationLog::where('notification_id', $notification->id)
                                 ->where('recipient_email', $recipient['email'])
                                 ->where('channel', 'teams')
                                 ->first();
            
            if (!$log) continue;
            
            try {
                // ใช้ personalized content ถ้ามี
                $content = $recipient['personalized_content'] ?? [
                    'subject' => $notification->subject,
                    'body_text' => $notification->body_text,
                ];
                
                // สร้าง Teams message format
                $teamsMessage = $content['subject'];
                $details = $this->parseContentToTeamsDetails($content['body_text'], $recipient);
                
                // ส่งผ่าน Teams notification service
                $teamsNotification = new \Osama\LaravelTeamsNotification\TeamsNotification();
                
                // ใช้ webhook URL จาก config หรือ user settings
                $webhookUrl = $this->getTeamsWebhookUrlForUser($recipient['email'], $notification);
                
                if ($webhookUrl) {
                    $response = $teamsNotification->sendMessageSetWebhook($webhookUrl, $teamsMessage, $details);
                    
                    $log->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                        'content_sent' => $content,
                        'webhook_url' => $webhookUrl
                    ]);
                } else {
                    throw new \Exception('Teams webhook URL not configured for user');
                }
                
            } catch (\Exception $e) {
                $log->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'retry_count' => ($log->retry_count ?? 0) + 1
                ]);
                
                Log::error("Failed to send Teams message", [
                    'recipient' => $recipient['email'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * ประมวลผล Webhook channel
     */
    private function processWebhookChannelEnhanced(Notification $notification)
    {
        Log::info("processWebhookChannelEnhanced START", [
            'notification_id' => $notification->uuid,
            'webhook_url' => $notification->webhook_url
        ]);

        $log = NotificationLog::where('notification_id', $notification->id)
                             ->where('channel', 'webhook')
                             ->first();
        
        if (!$log) {
            Log::error("No webhook log found for notification", [
                'notification_id' => $notification->id
            ]);
            return;
        }
        
        try {
            // ใช้ body_text สำหรับ webhook (ตาม requirement)
            $content = [
                'subject' => $notification->subject,
                'body_text' => $notification->body_text,
                'variables' => $notification->variables ?? []
            ];
            
            // สร้าง webhook payload
            $payload = [
                'notification_id' => (string) $notification->uuid,
                'subject' => $content['subject'],
                'message' => $content['body_text'],
                'priority' => $notification->priority,
                'channels' => $notification->channels,
                'recipients_count' => $notification->total_recipients,
                'timestamp' => now()->toISOString(),
                'variables' => $content['variables'],
                'status' => 'sent'
            ];

            Log::info("Webhook payload prepared", [
                'webhook_url' => $notification->webhook_url,
                'payload_size' => strlen(json_encode($payload)),
                'subject' => $payload['subject'],
                'message_preview' => substr($payload['message'], 0, 100)
            ]);
            
            $teamsNotification = new \Osama\LaravelTeamsNotification\TeamsNotification();
            
            // ✅ เตรียม details array สำหรับ Teams
            $teamsDetails = [];
            $teamsDetails['Notification ID'] = (string) $notification->uuid;
            $teamsDetails['Priority'] = strtoupper($notification->priority);
            $teamsDetails['Sent At'] = now()->format('Y-m-d H:i:s');
            
            // เพิ่ม variables ถ้ามี
            if (!empty($content['variables'])) {
                foreach ($content['variables'] as $key => $value) {
                    $teamsDetails[ucfirst($key)] = (string) $value;
                }
            }

            if (!empty($content['body_text'])) {
                // ลองแปลง JSON ก่อน
                $jsonData = json_decode($content['body_text'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $teamsDetails = array_merge($teamsDetails, $jsonData);
                } else {
                    // แยกข้อมูลแบบ key: value
                    $lines = explode("\n", $content['body_text']);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (strpos($line, ':') !== false) {
                            list($key, $value) = explode(':', $line, 2);
                            $teamsDetails[trim($key)] = trim($value);
                        }
                    }
                    
                    // ถ้าไม่มี key: value format ให้ใช้เป็น message
                    if (count($teamsDetails) <= 3) { // มีแค่ข้อมูลพื้นฐาน
                        $teamsDetails['Message'] = $content['body_text'];
                    }
                }
            }

            $webhookMessage = $content['subject'];
            if (empty(trim($webhookMessage))) {
                $webhookMessage = "Smart Notification Alert";
                Log::warning('Empty subject in enhanced webhook, using fallback', [
                    'notification_id' => $notification->uuid,
                    'original_subject' => $content['subject'],
                    'fallback_message' => $webhookMessage
                ]);
            }
            
            Log::info("Teams webhook payload prepared", [
                'message' => $webhookMessage,
                'message_length' => strlen($webhookMessage),
                'details_count' => count($teamsDetails),
                'webhook_url' => substr($notification->webhook_url, -30)
            ]);
            
            $response = $teamsNotification->sendMessageSetWebhook(
                $notification->webhook_url,
                $webhookMessage, // ใช้ validated message
                $teamsDetails
            );

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            Log::info("Webhook response received", [
                'status_code' => $statusCode,
                'response_body' => substr($responseBody, 0, 500)
            ]);
            
            // ✅ อัพเดต log สำเร็จ
            $log->update([
                'status' => 'sent',
                'sent_at' => now(),
                'content_sent' => $content,
                'webhook_response_code' => $statusCode,
                'response_data' => [
                    'status_code' => $statusCode,
                    'response_body' => substr($responseBody, 0, 1000),
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
            Log::info("Webhook sent successfully", [
                'webhook_url' => $notification->webhook_url,
                'notification_id' => $notification->uuid,
                'response_code' => $statusCode
            ]);
            
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $errorMessage = "Connection error: " . $e->getMessage();
            
            $log->update([
                'status' => 'failed',
                'error_message' => substr($errorMessage, 0, 500),
                'retry_count' => ($log->retry_count ?? 0) + 1
            ]);
            
            Log::error("Webhook connection failed", [
                'webhook_url' => $notification->webhook_url,
                'error' => $errorMessage
            ]);
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : 'unknown';
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $errorMessage = "HTTP {$statusCode} error: " . $e->getMessage();
            
            $log->update([
                'status' => 'failed',
                'error_message' => substr($errorMessage, 0, 500),
                'retry_count' => ($log->retry_count ?? 0) + 1,
                'webhook_response_code' => $statusCode,
                'response_data' => [
                    'status_code' => $statusCode,
                    'response_body' => substr($responseBody, 0, 1000),
                    'error' => $errorMessage
                ]
            ]);
            
            Log::error("Webhook request failed", [
                'webhook_url' => $notification->webhook_url,
                'status_code' => $statusCode,
                'error' => $errorMessage
            ]);
            
        } catch (\Exception $e) {
            $errorMessage = "Webhook error: " . $e->getMessage();
            
            $log->update([
                'status' => 'failed',
                'error_message' => substr($errorMessage, 0, 500),
                'retry_count' => ($log->retry_count ?? 0) + 1
            ]);
            
            Log::error("Webhook processing failed", [
                'webhook_url' => $notification->webhook_url,
                'error' => $errorMessage,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        
        Log::info("processWebhookChannelEnhanced END", [
            'notification_id' => $notification->uuid,
            'final_status' => $log->fresh()->status
        ]);

    }

    // ===============================================
    // STANDARD METHODS (Admin)
    // ===============================================

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
     * Get all recipients for notification with webhook channel handling
     */
    private function getAllRecipients(Notification $notification)
    {
        $recipients = [];
        $allEmails = [];

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
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $allEmails)) {
                        $recipients[] = [
                            'email' => $email,
                            'name' => $this->extractNameFromEmail($email)
                        ];
                        $allEmails[] = $email;
                        
                        Log::debug("Added manual recipient", [
                            'email' => $email,
                            'source' => 'manual'
                        ]);
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
                            if ($user->email && 
                                filter_var($user->email, FILTER_VALIDATE_EMAIL) && 
                                !in_array($user->email, $allEmails)) { // ✅ Check for duplicates
                                
                                $recipients[] = [
                                    'email' => $user->email,
                                    'name' => $user->display_name ?: $user->name ?: $this->extractNameFromEmail($user->email)
                                ];
                                $allEmails[] = $user->email; // ✅ Track email to prevent duplicates
                                
                                Log::debug("Added group recipient", [
                                    'email' => $user->email,
                                    'group_id' => $group->id,
                                    'group_name' => $group->name,
                                    'source' => 'group'
                                ]);
                            } else if ($user->email && in_array($user->email, $allEmails)) {
                                Log::debug("Skipped duplicate recipient", [
                                    'email' => $user->email,
                                    'group_id' => $group->id,
                                    'reason' => 'already_added'
                                ]);
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
        // $uniqueRecipients = [];
        // $emails = [];
        // foreach ($recipients as $recipient) {
        //     if ($recipient['email'] === 'system@webhook' || !in_array($recipient['email'], $emails)) {
        //         $uniqueRecipients[] = $recipient;
        //         if ($recipient['email'] !== 'system@webhook') {
        //             $emails[] = $recipient['email'];
        //         }
        //     }
        // }

        Log::info("Final recipients list", [
            'total_recipients' => count($recipients),
            'has_webhook_system' => in_array('system@webhook', array_column($recipients, 'email')),
            'actual_emails' => $allEmails,
            'unique_emails_count' => count($allEmails)
        ]);

        return $recipients;
    }

    // ===============================================
    // SHARED UTILITY METHODS
    // ===============================================

    /**
     * Detect source (Admin vs API) based on notification properties
     */
    private function detectSource(Notification $notification): string
    {
        // ถ้ามี api_key_id แสดงว่ามาจาก API
        if ($notification->api_key_id) {
            return 'API';
        }
        
        // ถ้ามี created_by แสดงว่ามาจาก Admin
        if ($notification->created_by) {
            return 'Admin';
        }
        
        // ถ้ามี processed_content แสดงว่ามาจาก API (enhanced)
        if (!empty($notification->processed_content)) {
            return 'API_Enhanced';
        }
        
        return 'Unknown';
    }

    /**
     * Extract name from email address safely
     */
    private function extractNameFromEmail($email)
    {
        try {
            $username = explode('@', $email)[0];
            
            // แปลง dot, underscore, dash เป็น space
            $name = str_replace(['.', '_', '-'], ' ', $username);
            
            // แปลงเป็น Title Case สำหรับแต่ละคำ
            $words = explode(' ', $name);
            $formattedWords = array_map(function($word) {
                return ucfirst(strtolower($word));
            }, $words);
            
            $result = implode(' ', $formattedWords);
            
            Log::debug("Name extraction", [
                'original_email' => $email,
                'username' => $username,
                'extracted_name' => $result
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Failed to extract name from email", [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return $email; // Fallback to email if extraction fails
        }
    }

    /**
     * Create notification log entry with webhook channel handling
     */
    private function createNotificationLog(Notification $notification, array $recipient, string $channel)
    {
        try {
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
                    'variables' => $notification->variables ?? [], // ✅ แน่ใจว่าเป็น array
                    'webhook_url' => $notification->webhook_url
                ]);
                
                Log::info("Created webhook log", [
                    'notification_id' => $notification->id,
                    'log_id' => $webhookLog->id,
                    'webhook_url' => $notification->webhook_url
                ]);
                
                return $webhookLog;
            }

            // Regular handling for other channels
            $logData = [
                'notification_id' => $notification->id,
                'channel' => $channel,
                'recipient_email' => $recipient['email'],
                'recipient_name' => $recipient['name'],
                'status' => 'pending',
                'retry_count' => 0,
                'variables' => $notification->variables ?? []
            ];

            // ✅ เพิ่ม attachment paths สำหรับ email channel
            if ($channel === 'email' && !empty($notification->attachments)) {
                $attachmentPaths = [];
                $attachmentInfo = [];
                
                foreach ($notification->attachments as $attachment) {
                    if ($attachment['type'] !== 'url_failed' && !empty($attachment['path'])) {
                        $fullPath = storage_path('app/' . $attachment['path']);
                        $attachmentPaths[] = $fullPath;
                        
                        $attachmentInfo[] = [
                            'name' => $attachment['name'],
                            'size' => $attachment['size'],
                            'mime_type' => $attachment['mime_type'],
                            'path' => $fullPath,
                            'relative_path' => $attachment['path']
                        ];
                    }
                }
                
                if (!empty($attachmentPaths)) {
                    $logData['attachment_paths'] = $attachmentPaths;
                    $logData['attachment_info'] = [
                        'count' => count($attachmentPaths),
                        'total_size' => array_sum(array_column($attachmentInfo, 'size')),
                        'files' => $attachmentInfo
                    ];
                    
                    Log::info("Adding attachment paths to log", [
                        'notification_id' => $notification->id,
                        'recipient' => $recipient['email'],
                        'attachment_count' => count($attachmentPaths),
                        'paths' => $attachmentPaths
                    ]);
                }
            }

            // ✅ เพิ่ม personalized content ถ้ามี
            if (!empty($notification->processed_content['personalized_content'][$recipient['email']])) {
                $logData['personalized_content'] = $notification->processed_content['personalized_content'][$recipient['email']];
            }

            $log = NotificationLog::create($logData);

            Log::info("Notification log created with attachments", [
                'log_id' => $log->id,
                'notification_id' => $notification->id,
                'channel' => $channel,
                'recipient' => $recipient['email'],
                'has_attachments' => !empty($logData['attachment_paths']),
                'attachment_count' => count($logData['attachment_paths'] ?? [])
            ]);

            return $log;

        } catch (\Exception $e) {
            Log::error('Failed to create notification log', [
                'notification_id' => $notification->id,
                'channel' => $channel,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
                'variables_type' => gettype($notification->variables ?? []),
                'variables_content' => is_array($notification->variables ?? []) ? 
                    array_slice($notification->variables ?? [], 0, 3, true) : 
                    $notification->variables
            ]);
            throw $e;
        }
    }

    /**
     * Create notification logs optimized
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
                        ['email' => 'system@webhook', 'name' => 'Webhook Endpoint'], 
                        $channel
                    );
                    $createdLogs[] = $log;
                    $totalLogs++;
                } else {
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
     * Queue notification safely
     */
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
                            SendEmailNotification::dispatch($log)
                                ->delay($delay)
                                ->onQueue($queueName);
                            $successfullyQueued++;
                            break;
    
                        case 'teams':
                            SendTeamsNotification::dispatch($log)
                                ->delay($delay)
                                ->onQueue($queueName);
                            $successfullyQueued++;
                            break;
    
                        case 'webhook':
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
     * อัพเดตสถานะ notification หลังประมวลผล
     */
    private function updateNotificationStatusAfterProcessing(Notification $notification)
    {
        $logs = $notification->logs;
        $totalLogs = $logs->count();
        
        if ($totalLogs === 0) {
            $notification->update(['status' => 'failed', 'failure_reason' => 'No logs created']);
            return;
        }
        
        $sentCount = $logs->where('status', 'sent')->count();
        $failedCount = $logs->where('status', 'failed')->count();
        $pendingCount = $logs->where('status', 'pending')->count();
        
        // กำหนดสถานะตามผลลัพธ์
        if ($sentCount === $totalLogs) {
            $status = 'sent';
        } elseif ($failedCount === $totalLogs) {
            $status = 'failed';
        } elseif ($pendingCount > 0) {
            $status = 'processing';
        } else {
            $status = 'partially_sent';
        }
        
        $notification->update([
            'status' => $status,
            'sent_at' => $sentCount > 0 ? now() : null,
            'delivered_count' => $sentCount,
            'failed_count' => $failedCount,
            'failure_reason' => $status === 'failed' ? 'All deliveries failed' : null
        ]);
        
        Log::info("Notification processing completed", [
            'notification_id' => $notification->uuid,
            'final_status' => $status,
            'sent' => $sentCount,
            'failed' => $failedCount,
            'pending' => $pendingCount
        ]);
    }

    // ===============================================
    // HELPER METHODS FOR ENHANCED FEATURES
    // ===============================================

    /**
     * แปลง content เป็น Teams details format
     */
    private function parseContentToTeamsDetails(string $content, array $recipient): array
    {
        $details = [];
        
        // เพิ่มข้อมูลผู้รับ
        $details['Recipient'] = $recipient['name'] . ' (' . $recipient['email'] . ')';
        
        if (empty($content)) {
            return $details;
        }
        
        // ลองแปลงจาก JSON ก่อน
        $jsonData = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            return array_merge($details, $jsonData);
        }
        
        // แปลงจากรูปแบบ key: value
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $details[trim($key)] = trim($value);
            } else {
                // ถ้าไม่มี : ให้ใช้เป็น line item
                $details['Line ' . (count($details))] = $line;
            }
        }
        
        // ถ้าไม่มี key: value ให้ใช้เป็น message เดียว
        if (count($details) === 1) { // มีแค่ Recipient
            $details['Message'] = $content;
        }
        
        return $details;
    }

    /**
     * ดึง Teams webhook URL สำหรับ user
     */
    private function getTeamsWebhookUrlForUser(string $email, Notification $notification): ?string
    {
        // ลองหา webhook URL จาก user settings ก่อน
        try {
            $user = User::where('email', $email)->first();
            if ($user && !empty($user->teams_webhook_url)) {
                return $user->teams_webhook_url;
            }
        } catch (\Exception $e) {
            Log::debug("Could not get user-specific Teams webhook", [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
        
        // ใช้ default webhook URL จาก notification หรือ config
        return $notification->webhook_url ?? config('teams.default_webhook_url');
    }

    // ===============================================
    // SHARED UTILITY METHODS
    // ===============================================

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
     * Queue notification for delivery (public method for backward compatibility)
     */
    public function queueNotification(Notification $notification)
    {
        return $this->queueNotificationSafely($notification);
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

    // ===============================================
    // TEST NOTIFICATION METHODS (Existing)
    // ===============================================

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

    // ===============================================
    // ADDITIONAL UTILITY METHODS
    // ===============================================

    /**
     * Update notification status based on logs
     */
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
}