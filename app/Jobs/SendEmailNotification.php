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
use App\Mail\NotificationMail;
use App\Services\ImprovedEmailService;

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
        // ✅ เพิ่ม Debug Log เมื่อเริ่มทำงาน
        Log::info('SendEmailNotification Job STARTED', [
            'log_id' => $this->notificationLog->id,
            'recipient' => $this->notificationLog->recipient_email,
            'current_status' => $this->notificationLog->status,
            'queue_connection' => config('queue.default'),
            'job_id' => $this->job->getJobId() ?? 'unknown'
        ]);

        try {
            
            $notification = $this->notificationLog->notification;
            
            if (!$notification) {
                throw new Exception('Notification not found for log ID: ' . $this->notificationLog->id);
            }
            
            // ตรวจสอบสถานะปัจจุบัน
            if ($this->notificationLog->status !== 'pending') {
                Log::warning('Job skipped - status not pending', [
                    'log_id' => $this->notificationLog->id,
                    'current_status' => $this->notificationLog->status
                ]);
                return;
            }
            
            if (!$this->shouldSendEmail()) {
                $this->notificationLog->update([
                    'status' => 'failed',
                    'error_message' => 'User preferences do not allow email notifications',
                    'failed_at' => now()
                ]);
                
                $this->updateNotificationStatus();
                return;
            }

            // ✅ เตรียมเนื้อหาอีเมลโดยใช้ personalized content ก่อน
            $emailContent = $this->prepareEmailContent($notification);
            $attachmentPaths = $this->prepareAttachments();
            
            $recipient = [
                'email' => $this->notificationLog->recipient_email,
                'name' => $this->notificationLog->recipient_name ?: $this->extractNameFromEmail($this->notificationLog->recipient_email)
            ];

            Log::info('Sending email with final content', [
                'recipient' => $recipient['email'],
                'recipient_name' => $recipient['name'],
                'subject' => $emailContent['subject'],
                'has_html' => !empty($emailContent['body_html']),
                'has_text' => !empty($emailContent['body_text']),
                'subject_length' => strlen($emailContent['subject']),
                'content_source' => $this->detectContentSource(),
                'attachment_count' => count($attachmentPaths),
                'attachment_paths' => $attachmentPaths,
            ]);

            // ส่งอีเมลโดยใช้ Laravel Mail หรือ EmailService
            // $result = $this->sendEmail($recipient, $emailContent);
            // $result = $this->sendEmailWithAttachments($recipient, $emailContent, $attachmentPaths);
            // $result = $this->sendEmailDirect($recipient, $emailContent, $attachmentPaths);
            $result = $this->sendEmailViaImprovedService($recipient, $emailContent, $attachmentPaths);

            if ($result['success']) {
                // สำเร็จ - ใส่ delivered_at
                $this->notificationLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'delivered_at' => now(),
                    'content_sent' => $emailContent,
                    'response_data' => [
                        'success' => true,
                        'method' => $result['method'] ?? 'improved_email_service',
                        'message_id' => $result['message_id'] ?? null,
                        'attachment_count' => count($attachmentPaths),
                        'timestamp' => now()->toISOString()
                    ]
                ]);

                Log::info('Email notification sent successfully', [
                    'log_id' => $this->notificationLog->id,
                    'recipient' => $recipient['email'],
                    'recipient_name' => $recipient['name'],
                    'subject_sent' => $emailContent['subject'],
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
                'recipient_name' => $this->notificationLog->recipient_name,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            $this->handleFailure($e->getMessage());
        }
    }

    private function sendEmailViaImprovedService($recipient, $emailContent, $attachmentPaths = [])
    {
        try {
            Log::info('Using ImprovedEmailService for complete attachment support', [
                'recipient' => $recipient['email'],
                'has_legacy_attachments' => !empty($attachmentPaths),
                'legacy_attachment_count' => count($attachmentPaths)
            ]);

            // สร้าง ImprovedEmailService instance
            $emailService = new ImprovedEmailService();
            
            // ✅ เตรียมข้อมูลอีเมลพร้อมไฟล์แนบทั้ง 3 ประเภท
            $emailData = [
                'to' => $recipient['email'],
                'subject' => $emailContent['subject'],
                'body_html' => $emailContent['body_html'],
                'body_text' => $emailContent['body_text'],
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'recipient_name' => $recipient['name'],
                
                // ✅ รองรับไฟล์แนบทั้ง 3 ประเภท
                'attachments' => $attachmentPaths, // File paths (legacy)
                'attachment_urls' => $this->getAttachmentUrls(), // URL downloads 
                'attachments_base64' => $this->getBase64Attachments() // Base64 encoded
            ];

            Log::info("Complete attachment data prepared", [
                'file_paths' => count($attachmentPaths),
                'url_attachments' => count($emailData['attachment_urls']),
                'base64_attachments' => count($emailData['attachments_base64'])
            ]);

            // ✅ ส่งผ่าน ImprovedEmailService ที่รองรับครบ 3 ประเภท
            $result = $emailService->sendEmail($emailData);
            
            if ($result['success']) {
                Log::info("Email sent successfully with complete attachment support", [
                    'recipient' => $recipient['email'],
                    'method' => $result['method'],
                    'attachment_summary' => $result['attachment_summary'] ?? []
                ]);
                
                return [
                    'success' => true,
                    'method' => $result['method'] ?? 'improved_email_service_complete',
                    'message_id' => 'complete_' . time(),
                    'attachment_summary' => $result['attachment_summary'] ?? []
                ];
            } else {
                Log::warning("ImprovedEmailService failed, trying fallback", [
                    'recipient' => $recipient['email'],
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                
                // ถ้า ImprovedEmailService ล้มเหลว ลอง fallback method (เฉพาะ file paths)
                return $this->sendEmailFallback($recipient, $emailContent, $attachmentPaths);
            }
            
        } catch (\Exception $e) {
            Log::error("ImprovedEmailService exception, trying fallback", [
                'recipient' => $recipient['email'],
                'error' => $e->getMessage(),
                'attachment_count' => count($attachmentPaths)
            ]);
            
            // ลอง fallback method
            return $this->sendEmailFallback($recipient, $emailContent, $attachmentPaths);
        }
    }

    private function sendEmailFallback($recipient, $emailContent, $attachmentPaths = [])
    {
        try {
            Log::info('Using fallback email method', [
                'recipient' => $recipient['email'],
                'has_attachments' => !empty($attachmentPaths)
            ]);

            if (empty($attachmentPaths)) {
                // ไม่มีไฟล์แนบ - ใช้ Laravel Mail
                Mail::send([], [], function ($message) use ($recipient, $emailContent) {
                    $message->to($recipient['email'], $recipient['name'])
                           ->subject($emailContent['subject']);
                    
                    if (!empty($emailContent['body_html'])) {
                        $message->html($emailContent['body_html']);
                    }
                    
                    if (!empty($emailContent['body_text'])) {
                        $message->text($emailContent['body_text']);
                    }
                    
                    $fromEmail = config('mail.from.address', 'noreply@company.com');
                    $fromName = config('mail.from.name', config('app.name'));
                    $message->from($fromEmail, $fromName);
                });
                
                return [
                    'success' => true,
                    'method' => 'laravel_mail_fallback',
                    'message_id' => 'fallback_' . time()
                ];
            } else {
                // มีไฟล์แนบ - ใช้ NotificationMail
                $emailData = [
                    'subject' => $emailContent['subject'],
                    'body_html' => $emailContent['body_html'],
                    'body_text' => $emailContent['body_text'],
                    'recipient_name' => $recipient['name'],
                    'recipient_email' => $recipient['email'],
                    'format' => !empty($emailContent['body_html']) ? 'html' : 'text',
                ];

                Mail::to($recipient['email'], $recipient['name'])
                    ->send(new NotificationMail($emailData, 'notification', $attachmentPaths));

                return [
                    'success' => true,
                    'method' => 'notification_mail_fallback',
                    'message_id' => 'fallback_notification_' . time(),
                    'attachments_count' => count($attachmentPaths)
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Fallback email method also failed", [
                'recipient' => $recipient['email'],
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'All email methods failed: ' . $e->getMessage(),
                'method' => 'all_failed'
            ];
        }
    }

    private function sendEmailDirect($recipient, $emailContent, $attachmentPaths = [])
    {
        try {
            Log::info('Attempting direct email send with attachments', [
                'recipient' => $recipient['email'],
                'has_attachments' => !empty($attachmentPaths),
                'attachment_count' => count($attachmentPaths),
                'attachment_paths' => $attachmentPaths
            ]);

            if (empty($attachmentPaths)) {
                // ไม่มีไฟล์แนับ - ใช้วิธีเดิมที่ทำงาน
                Mail::send([], [], function ($message) use ($recipient, $emailContent) {
                    $message->to($recipient['email'], $recipient['name'])
                           ->subject($emailContent['subject']);
                    
                    if (!empty($emailContent['body_html'])) {
                        $message->html($emailContent['body_html']);
                    }
                    
                    if (!empty($emailContent['body_text'])) {
                        $message->text($emailContent['body_text']);
                    }
                    
                    $fromEmail = config('mail.from.address', 'noreply@company.com');
                    $fromName = config('mail.from.name', config('app.name'));
                    $message->from($fromEmail, $fromName);
                });
                
                return [
                    'success' => true,
                    'method' => 'mail_send_direct',
                    'message_id' => 'direct_' . time()
                ];
            } else {
                // มีไฟล์แนบ - ใช้ NotificationMail
                $emailData = [
                    'subject' => $emailContent['subject'],
                    'body_html' => $emailContent['body_html'],
                    'body_text' => $emailContent['body_text'],
                    'recipient_name' => $recipient['name'],
                    'recipient_email' => $recipient['email'],
                    'format' => !empty($emailContent['body_html']) ? 'html' : 'text',
                ];

                Mail::to($recipient['email'], $recipient['name'])
                    ->send(new NotificationMail($emailData, 'notification', $attachmentPaths));

                return [
                    'success' => true,
                    'method' => 'notification_mail_with_attachments',
                    'message_id' => 'notification_' . time(),
                    'attachments_count' => count($attachmentPaths)
                ];
            }
            
        } catch (Exception $e) {
            Log::error("Direct email sending failed", [
                'recipient' => $recipient['email'],
                'error' => $e->getMessage(),
                'mail_config' => [
                    'driver' => config('mail.default'),
                    'from_address' => config('mail.from.address'),
                    'from_name' => config('mail.from.name')
                ]
            ]);
            
            return [
                'success' => false,
                'error' => 'Direct email failed: ' . $e->getMessage(),
                'method' => 'direct_failed'
            ];
        }
    }

    /**
     * ✅ ดึง URL attachments จาก notification
     */
    private function getAttachmentUrls(): array
    {
        try {
            $notification = $this->notificationLog->notification;
            
            // ลองหาจาก notification data
            if (!empty($notification->attachment_urls)) {
                Log::info("Found attachment URLs in notification", [
                    'urls' => $notification->attachment_urls
                ]);
                return $notification->attachment_urls;
            }
            
            // ลองหาจาก attachments metadata
            if (!empty($notification->attachments)) {
                $urls = [];
                foreach ($notification->attachments as $attachment) {
                    if (isset($attachment['type']) && $attachment['type'] === 'url' && !empty($attachment['url'])) {
                        $urls[] = $attachment['url'];
                    }
                }
                
                if (!empty($urls)) {
                    Log::info("Found attachment URLs in attachments metadata", [
                        'urls' => $urls
                    ]);
                    return $urls;
                }
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error("Error getting attachment URLs", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ✅ ดึง Base64 attachments จาก notification
     */
    private function getBase64Attachments(): array
    {
        try {
            $notification = $this->notificationLog->notification;
            
            // ลองหาจาก notification data
            if (!empty($notification->attachments_base64)) {
                Log::info("Found base64 attachments in notification", [
                    'count' => count($notification->attachments_base64)
                ]);
                return $notification->attachments_base64;
            }
            
            // ลองหาจาก attachments metadata
            if (!empty($notification->attachments)) {
                $base64Attachments = [];
                foreach ($notification->attachments as $attachment) {
                    if (isset($attachment['type']) && $attachment['type'] === 'base64' && 
                        !empty($attachment['name']) && !empty($attachment['data'])) {
                        
                        $base64Attachments[] = [
                            'name' => $attachment['name'],
                            'data' => $attachment['data'],
                            'mime_type' => $attachment['mime_type'] ?? 'application/octet-stream'
                        ];
                    }
                }
                
                if (!empty($base64Attachments)) {
                    Log::info("Found base64 attachments in attachments metadata", [
                        'count' => count($base64Attachments)
                    ]);
                    return $base64Attachments;
                }
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::error("Error getting base64 attachments", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function prepareEmailContent($notification)
    {
        try {
            // 1. ลองใช้ personalized content จาก log ก่อน
            if (!empty($this->notificationLog->personalized_content)) {
                $personalizedContent = $this->notificationLog->personalized_content;
                
                Log::info("Using personalized content from log", [
                    'log_id' => $this->notificationLog->id,
                    'has_subject' => !empty($personalizedContent['subject']),
                    'has_body_html' => !empty($personalizedContent['body_html']),
                    'has_body_text' => !empty($personalizedContent['body_text']),
                    'subject_preview' => !empty($personalizedContent['subject']) ? substr($personalizedContent['subject'], 0, 100) : 'EMPTY'
                ]);
                
                if (!empty($personalizedContent['subject'])) {
                    return [
                        'subject' => $personalizedContent['subject'],
                        'body_html' => $personalizedContent['body_html'] ?? '',
                        'body_text' => $personalizedContent['body_text'] ?? strip_tags($personalizedContent['body_html'] ?? '')
                    ];
                }
            }
            
            // 2. ถ้าไม่มี personalized content ให้สร้างใหม่
            Log::info("No personalized content found, creating content with variables", [
                'log_id' => $this->notificationLog->id,
                'notification_subject' => $notification->subject,
                'recipient_name' => $this->notificationLog->recipient_name
            ]);
            
            return $this->createContentWithVariables($notification);
            
        } catch (\Exception $e) {
            Log::error("Error preparing email content", [
                'log_id' => $this->notificationLog->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to basic content
            return [
                'subject' => $notification->subject ?: 'Smart Notification',
                'body_html' => $notification->body_html ?: '',
                'body_text' => $notification->body_text ?: 'Notification content'
            ];
        }
    }


    private function prepareAttachmentsx(): array
    {
        try {
            $attachmentPaths = $this->notificationLog->getAttachmentPaths();
            
            if (empty($attachmentPaths)) {
                Log::info("No attachments found for email", [
                    'log_id' => $this->notificationLog->id
                ]);
                return [];
            }
            
            // ตรวจสอบว่าไฟล์ยังมีอยู่จริง
            $validPaths = [];
            $invalidPaths = [];
            
            foreach ($attachmentPaths as $path) {
                if (file_exists($path)) {
                    $fileSize = filesize($path);
                    $maxSize = 25 * 1024 * 1024; // 25MB limit
                    
                    if ($fileSize <= $maxSize) {
                        $validPaths[] = $path;
                    } else {
                        Log::warning("Attachment file too large", [
                            'path' => $path,
                            'size' => $fileSize,
                            'max_size' => $maxSize
                        ]);
                        $invalidPaths[] = $path;
                    }
                } else {
                    Log::warning("Attachment file not found", [
                        'path' => $path
                    ]);
                    $invalidPaths[] = $path;
                }
            }
            
            if (!empty($invalidPaths)) {
                Log::warning("Some attachments are invalid", [
                    'valid_count' => count($validPaths),
                    'invalid_count' => count($invalidPaths),
                    'invalid_paths' => $invalidPaths
                ]);
            }
            
            Log::info("Attachments prepared", [
                'total_requested' => count($attachmentPaths),
                'valid_attachments' => count($validPaths),
                'invalid_attachments' => count($invalidPaths)
            ]);
            
            return $validPaths;
            
        } catch (\Exception $e) {
            Log::error("Error preparing attachments", [
                'log_id' => $this->notificationLog->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function prepareAttachmentsy(): array
    {
        try {
            $validPaths = [];
            
            // 1. ลองจาก log ก่อน
            $logAttachmentPaths = $this->notificationLog->getAttachmentPaths();
            
            // 2. ถ้าไม่มีใน log ให้ลองจาก notification
            if (empty($logAttachmentPaths)) {
                $notification = $this->notificationLog->notification;
                if ($notification && $notification->attachments) {
                    $logAttachmentPaths = $notification->getAttachmentPaths();
                }
            }
            
            Log::info("Preparing attachments from multiple sources", [
                'log_id' => $this->notificationLog->id,
                'log_attachment_paths' => $logAttachmentPaths,
                'notification_attachments' => $this->notificationLog->notification->attachments ?? []
            ]);
            
            if (empty($logAttachmentPaths)) {
                Log::info("No attachment paths found", [
                    'log_id' => $this->notificationLog->id
                ]);
                return [];
            }
            
            // ตรวจสอบไฟล์ทั้งหมด
            foreach ($logAttachmentPaths as $path) {
                if (empty($path)) {
                    continue;
                }
                
                Log::debug("Checking attachment path", [
                    'path' => $path,
                    'exists' => file_exists($path),
                    'full_path' => $path
                ]);
                
                if (file_exists($path)) {
                    $fileSize = filesize($path);
                    $maxSize = 25 * 1024 * 1024; // 25MB limit
                    
                    if ($fileSize <= $maxSize) {
                        $validPaths[] = $path;
                        Log::info("Valid attachment added", [
                            'path' => $path,
                            'size' => $fileSize
                        ]);
                    } else {
                        Log::warning("Attachment file too large", [
                            'path' => $path,
                            'size' => $fileSize,
                            'max_size' => $maxSize
                        ]);
                    }
                } else {
                    Log::warning("Attachment file not found", [
                        'path' => $path
                    ]);
                }
            }
            
            Log::info("Final attachments prepared", [
                'total_requested' => count($logAttachmentPaths),
                'valid_attachments' => count($validPaths),
                'paths' => $validPaths
            ]);
            
            return $validPaths;
            
        } catch (\Exception $e) {
            Log::error("Error preparing attachments", [
                'log_id' => $this->notificationLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    private function prepareAttachments(): array
    {
        try {
            $validPaths = [];
            
            // 1. ลองจาก log ก่อน
            $logAttachmentPaths = $this->notificationLog->getAttachmentPaths();
            
            // 2. ถ้าไม่มีใน log ให้ลองจาก notification
            if (empty($logAttachmentPaths)) {
                $notification = $this->notificationLog->notification;
                if ($notification && $notification->attachments) {
                    $logAttachmentPaths = $notification->getAttachmentPaths();
                }
            }
            
            Log::info("Preparing attachments from multiple sources", [
                'log_id' => $this->notificationLog->id,
                'log_attachment_paths' => $logAttachmentPaths,
                'notification_attachments' => $this->notificationLog->notification->attachments ?? []
            ]);
            
            if (empty($logAttachmentPaths)) {
                return [];
            }
            
            // ตรวจสอบไฟล์ทั้งหมด
            foreach ($logAttachmentPaths as $path) {
                if (empty($path)) {
                    continue;
                }
                
                if (file_exists($path)) {
                    $fileSize = filesize($path);
                    $maxSize = 25 * 1024 * 1024; // 25MB limit
                    
                    if ($fileSize <= $maxSize) {
                        $validPaths[] = $path;
                        Log::info("Valid attachment added", [
                            'path' => $path,
                            'size' => $fileSize
                        ]);
                    } else {
                        Log::warning("Attachment file too large", [
                            'path' => $path,
                            'size' => $fileSize,
                            'max_size' => $maxSize
                        ]);
                    }
                } else {
                    Log::warning("Attachment file not found", [
                        'path' => $path
                    ]);
                }
            }
            
            Log::info("Final attachments prepared", [
                'total_requested' => count($logAttachmentPaths),
                'valid_attachments' => count($validPaths),
                'paths' => $validPaths
            ]);
            
            return $validPaths;
            
        } catch (\Exception $e) {
            Log::error("Error preparing attachments", [
                'log_id' => $this->notificationLog->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * ✅ ส่งอีเมลพร้อม attachments
     */
    private function sendEmailWithAttachments($recipient, $emailContent, $attachmentPaths)
    {
        try {
            // ✅ ใช้ NotificationMail class ที่รองรับ attachments
            $emailData = [
                'subject' => $emailContent['subject'],
                'body_html' => $emailContent['body_html'],
                'body_text' => $emailContent['body_text'],
                'recipient_name' => $recipient['name'],
                'recipient_email' => $recipient['email'],
                'format' => !empty($emailContent['body_html']) ? 'html' : 'text',
            ];

            // ส่งผ่าน NotificationMail
            Mail::to($recipient['email'], $recipient['name'])
                ->send(new NotificationMail($emailData, 'notification', $attachmentPaths));

            // เตรียมข้อมูล attachments ที่ส่งไป
            $attachmentsInfo = array_map(function($path) {
                return [
                    'path' => $path,
                    'name' => basename($path),
                    'size' => file_exists($path) ? filesize($path) : 0,
                    'sent' => true
                ];
            }, $attachmentPaths);

            return [
                'success' => true,
                'method' => 'notification_mail',
                'message_id' => 'notification_' . time(),
                'attachments_info' => $attachmentsInfo
            ];
            
        } catch (\Exception $e) {
            Log::error("Failed to send email with attachments", [
                'recipient' => $recipient['email'],
                'attachment_count' => count($attachmentPaths),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Email with attachments failed: ' . $e->getMessage(),
                'method' => 'notification_mail'
            ];
        }
    }

    /**
     * ✅ สร้าง content ใหม่โดยใช้ variables
     */
    private function createContentWithVariables($notification)
    {
        // เตรียม variables สำหรับ recipient นี้
        $variables = $this->getTemplateVariables($notification, $this->notificationLog);
        
        Log::info("Creating content with variables", [
            'recipient' => $this->notificationLog->recipient_email,
            'recipient_name' => $variables['recipient_name'] ?? 'N/A',
            'variables_count' => count($variables),
            'key_variables' => [
                'recipient_name' => $variables['recipient_name'] ?? 'MISSING',
                'recipient_email' => $variables['recipient_email'] ?? 'MISSING',
                'system_name' => $variables['system_name'] ?? 'MISSING'
            ]
        ]);
        
        // ใช้ template ถ้ามี
        $template = $notification->template;
        if ($template) {
            $subject = $this->replaceVariables($template->subject_template, $variables);
            $bodyHtml = $this->replaceVariables($template->body_html_template, $variables);
            $bodyText = $this->replaceVariables($template->body_text_template, $variables);
        } else {
            $subject = $this->replaceVariables($notification->subject, $variables);
            $bodyHtml = $this->replaceVariables($notification->body_html ?? '', $variables);
            $bodyText = $this->replaceVariables($notification->body_text ?? '', $variables);
        }
        
        // ตรวจสอบผลลัพธ์
        if (empty($subject) || trim($subject) === '') {
            $subject = 'แจ้งเตือนสำหรับ ' . ($variables['recipient_name'] ?? 'คุณ');
        }
        
        Log::info("Content created with variables", [
            'final_subject' => $subject,
            'subject_length' => strlen($subject),
            'has_html' => !empty($bodyHtml),
            'has_text' => !empty($bodyText)
        ]);
        
        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText ?: strip_tags($bodyHtml)
        ];
    }

    /**
     * ✅ ตรวจสอบแหล่งที่มาของ content
     */
    private function detectContentSource(): string
    {
        if (!empty($this->notificationLog->personalized_content)) {
            return 'personalized_content';
        }
        
        $notification = $this->notificationLog->notification;
        if ($notification->template) {
            return 'template';
        }
        
        return 'notification_direct';
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
                
                // ตั้งค่า From
                $fromEmail = config('mail.from.address', 'noreply@company.com');
                $fromName = config('mail.from.name', config('app.name'));
                $message->from($fromEmail, $fromName);
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
     * ✅ ได้ template variables ที่ถูกต้อง
     */
    private function getTemplateVariables($notification, $log)
    {
        try {
            // 1. System Variables
            $systemVariables = [
                'notification_id' => $notification->uuid,
                'subject' => $notification->subject,
                'current_date' => now()->format('Y-m-d'),
                'current_time' => now()->format('H:i:s'),
                'current_datetime' => now()->format('Y-m-d H:i:s'),
                'app_name' => config('app.name', 'Smart Notification System'),
                'app_url' => config('app.url'),
                'priority' => $notification->priority ?? 'normal',
                'system_name' => config('app.name', 'Smart Notification System'),
            ];
    
            // 2. Notification Variables (user provided)
            $notificationVariables = $notification->variables ?? [];
            
            // 3. Template Variables (cleaned)
            $cleanTemplateVariables = [];
            $template = $notification->template;
            if ($template && !empty($template->default_variables)) {
                foreach ($template->default_variables as $key => $value) {
                    if (!is_string($value) || strpos(strtolower($value), 'sample') === false) {
                        $cleanTemplateVariables[$key] = $value;
                    }
                }
            }
    
            // 4. Recipient Variables (ต้องมาท้ายสุด - Priority สูงสุด)
            $recipientName = $this->getActualRecipientName($log);
            
            $recipientVariables = [
                'recipient_name' => $recipientName,
                'recipient_email' => $log->recipient_email,
                'recipient_first_name' => $this->extractFirstName($recipientName),
                'recipient_last_name' => $this->extractLastName($recipientName),
                'user_name' => $recipientName,
                'user_email' => $log->recipient_email,
                'user_first_name' => $this->extractFirstName($recipientName),
                'user_last_name' => $this->extractLastName($recipientName),
            ];
    
            // 5. Merge ตามลำดับความสำคัญ (Recipient Variables มาท้ายสุด)
            $finalVariables = array_merge(
                $systemVariables,
                $cleanTemplateVariables,
                $notificationVariables,
                $recipientVariables  // ✅ สำคัญที่สุด
            );
    
            Log::debug("Template variables prepared", [
                'recipient' => $log->recipient_email,
                'final_recipient_name' => $finalVariables['recipient_name'],
                'variables_count' => count($finalVariables),
            ]);
    
            return $finalVariables;
    
        } catch (\Exception $e) {
            Log::error("Failed to get template variables", [
                'error' => $e->getMessage(),
                'log_id' => $log->id ?? 'unknown',
            ]);
            
            // Fallback
            return [
                'recipient_name' => $this->getActualRecipientName($log),
                'recipient_email' => $log->recipient_email,
                'user_name' => $this->getActualRecipientName($log),
                'user_email' => $log->recipient_email,
                'system_name' => config('app.name', 'Smart Notification System'),
                'current_date' => now()->format('Y-m-d'),
            ];
        }
    }

    private function getActualRecipientName($log): string
    {
        $recipientName = $log->recipient_name;
        $recipientEmail = $log->recipient_email;
        
        // ตรวจสอบว่าชื่อใน log ไม่ใช่ sample หรือ empty
        if (!$recipientName || 
            empty(trim($recipientName)) || 
            strpos($recipientName, 'Sample') !== false) {
            
            // ดึงจาก database
            $user = \App\Models\User::where('email', $recipientEmail)->first();
            if ($user) {
                $recipientName = $user->display_name ?? $user->name ?? $user->first_name;
                if (!$recipientName && ($user->first_name || $user->last_name)) {
                    $recipientName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                }
            }
            
            // ถ้ายังไม่มี ให้ extract จาก email
            if (!$recipientName) {
                $recipientName = $this->extractNameFromEmail($recipientEmail);
            }
        }
        
        return $recipientName;
    }

    private function replaceVariables($content, $variables)
    {
        if (empty($content)) {
            return $content;
        }

        try {
            $processedContent = $content;

            foreach ($variables as $key => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                } elseif (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                } elseif (is_null($value)) {
                    $value = '';
                } elseif ($value instanceof \Carbon\Carbon) {
                    $value = $value->format('Y-m-d H:i:s');
                }

                // ข้าม Sample values
                if (is_string($value) && strpos(strtolower($value), 'sample') !== false) {
                    continue;
                }

                $pattern = '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/u';
                $processedContent = preg_replace($pattern, $value, $processedContent);
            }

            // ทำความสะอาด variables ที่เหลือ
            $processedContent = preg_replace('/\{\{[^}]+\}\}/u', '', $processedContent);
            $processedContent = preg_replace('/\s+/', ' ', $processedContent);
            $processedContent = trim($processedContent);

            return $processedContent;

        } catch (\Exception $e) {
            Log::error("Failed to replace variables", [
                'error' => $e->getMessage(),
                'content_length' => strlen($content ?? ''),
                'variables_count' => count($variables),
            ]);
            
            return $content;
        }
    }

    /**
     * แยกชื่อจากอีเมล
     */
    private function extractNameFromEmail($email)
    {
        try {
            $username = explode('@', $email)[0];
            $name = str_replace(['.', '_', '-'], ' ', $username);
            $words = explode(' ', $name);
            $formattedWords = array_map(function($word) {
                return ucfirst(strtolower($word));
            }, $words);
            return implode(' ', $formattedWords);
        } catch (\Exception $e) {
            return $email;
        }
    }

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

    protected function shouldSendEmail()
    {
        try {
            $user = \App\Models\User::where('email', $this->notificationLog->recipient_email)->first();
            
            if (!$user) {
                return true;
            }

            if (!$user->preferences) {
                return true;
            }

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
            
            return true;
        }
    }

    private function handleFailure($errorMessage)
    {
        $retryCount = $this->notificationLog->retry_count + 1;
        $maxRetries = $this->tries;

        if ($retryCount < $maxRetries) {
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

            throw new \Exception($errorMessage);
            
        } else {
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

    private function updateNotificationStatus()
    {
        try {
            $notification = $this->notificationLog->notification;
            $logs = $notification->logs;
            
            $totalLogs = $logs->count();
            $sentLogs = $logs->where('status', 'sent')->count();
            $failedLogs = $logs->where('status', 'failed')->count();
            $pendingLogs = $logs->where('status', 'pending')->count();

            if ($pendingLogs == 0) {
                if ($failedLogs == 0) {
                    $status = 'sent';
                } elseif ($sentLogs == 0) {
                    $status = 'failed';
                } else {
                    $status = 'sent';
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