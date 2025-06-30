<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Notification;

class TestNotificationWithAttachments extends Command
{
    protected $signature = 'test:email-attachments {notification_id} {email}';
    protected $description = 'Test email sending with attachments from existing notification';

    public function handle()
    {
        $notificationId = $this->argument('notification_id');
        $testEmail = $this->argument('email');
        
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address');
            return 1;
        }
        
        $notification = Notification::where('uuid', $notificationId)->first();
        
        if (!$notification) {
            $this->error('Notification not found');
            return 1;
        }
        
        $this->info('📧 Testing Email with Attachments');
        $this->info('================================');
        $this->line("Notification: {$notification->uuid}");
        $this->line("Test Email: {$testEmail}");
        $this->line('');
        
        // Initialize attachment paths array
        $attachmentPaths = [];
        
        // ตรวจสอบไฟล์แนบ
        if (empty($notification->attachments)) {
            $this->warn('No attachments found in notification');
        } else {
            $this->info('📎 Attachments found:');
            foreach ($notification->attachments as $attachment) {
                // Fix the path construction - remove duplicate 'private'
                $relativePath = $attachment['path'];
                
                // If path already contains 'attachments/', use storage_path directly
                if (strpos($relativePath, 'attachments/') === 0) {
                    $fullPath = storage_path('app/' . $relativePath);
                } else {
                    // If path doesn't start with attachments/, assume it's in private folder
                    $fullPath = storage_path('app/private/' . $relativePath);
                }
                
                $exists = file_exists($fullPath);
                $this->line("   - {$attachment['name']} (" . number_format($attachment['size']) . " bytes) " . ($exists ? '✅' : '❌'));
                
                // Add to attachment paths if file exists and it's not a failed URL
                if ($exists && ($attachment['type'] ?? '') !== 'url_failed') {
                    $attachmentPaths[] = $fullPath;
                }
            }
        }
        
        try {
            // ส่งอีเมลทดสอบ
            $emailData = [
                'subject' => '🧪 Test: ' . $notification->subject,
                'body_html' => '<h2>🧪 Attachment Test</h2><p>This is a test email to verify attachment functionality.</p><p><strong>Original Notification:</strong> ' . $notification->uuid . '</p><p><strong>Attachments:</strong> ' . count($attachmentPaths) . ' files</p>',
                'body_text' => "🧪 Attachment Test\n\nThis is a test email to verify attachment functionality.\n\nOriginal Notification: {$notification->uuid}\nAttachments: " . count($attachmentPaths) . " files",
                'recipient_name' => 'Test User',
                'recipient_email' => $testEmail,
                'format' => 'html'
            ];
            
            // Use the corrected NotificationMail class
            \Illuminate\Support\Facades\Mail::to($testEmail)
                ->send(new \App\Mail\NotificationMail($emailData, 'test', $attachmentPaths));
            
            $this->info('✅ Test email sent successfully!');
            $this->table(['Detail', 'Value'], [
                ['Recipient', $testEmail],
                ['Subject', $emailData['subject']],
                ['Attachments', count($attachmentPaths)],
                ['Files', implode(', ', array_map('basename', $attachmentPaths))]
            ]);
            
        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->line('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}