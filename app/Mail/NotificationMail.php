<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Mailables\Attachment;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailData;
    public $mailType;
    public $attachmentPaths;

    /**
     * Create a new message instance.
     */
    public function __construct(array $emailData, string $type = 'notification', array $attachmentPaths = [])
    {
        $this->emailData = $emailData;
        $this->mailType = $type;
        $this->attachmentPaths = $attachmentPaths;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailData['subject'] ?? 'Notification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Use HTML or text based on format preference
        $format = $this->emailData['format'] ?? 'html';

        $attachmentInfo = $this->getAttachmentInfo();
        $viewData = [
            'emailData' => $this->emailData,
            'mailType' => $this->mailType,
            'hasAttachments' => !empty($this->attachmentPaths),
            'attachmentCount' => count($this->attachmentPaths),
            'attachmentInfo' => $attachmentInfo,
            'htmlContent' => $this->emailData['body_html'] ?? '',
            'textContent' => $this->emailData['body_text'] ?? strip_tags($this->emailData['body_html'] ?? '')
        ];
        
        // if ($format === 'html' && !empty($this->emailData['body_html'])) {
        //     return new Content(
        //         view: 'emails.notification-html',
        //         text: 'emails.notification-text',
        //         with: [
        //             'emailData' => $this->emailData,
        //             'mailType' => $this->mailType,
        //             'htmlContent' => $this->emailData['body_html'],
        //             'textContent' => $this->emailData['body_text'] ?? strip_tags($this->emailData['body_html']),
        //             'hasAttachments' => !empty($this->attachmentPaths),
        //             'attachmentCount' => count($this->attachmentPaths),
        //             'attachmentInfo' => $attachmentInfo
        //         ]
        //     );
        // } else {
        //     return new Content(
        //         text: 'emails.notification-text',
        //         with: [
        //             'emailData' => $this->emailData,
        //             'mailType' => $this->mailType,
        //             'textContent' => $this->emailData['body_text'] ?? strip_tags($this->emailData['body_html'] ?? ''),
        //             'hasAttachments' => !empty($this->attachmentPaths),
        //             'attachmentCount' => count($this->attachmentPaths),
        //             'attachmentInfo' => $attachmentInfo
        //         ]
        //     );
        // }
        if (!empty($this->emailData['body_html'])) {
            return new Content(
                view: 'emails.notification-html',
                with: $viewData
            );
        } else {
            return new Content(
                text: 'emails.notification-text', 
                with: $viewData
            );
        }
    }

    /**
     * ✅ เพิ่ม method เพื่อดึงข้อมูลไฟล์แนบ
     */
    private function getAttachmentInfo(): array
    {
        $attachmentInfo = [];
        
        foreach ($this->attachmentPaths as $path) {
            if (file_exists($path)) {
                $attachmentInfo[] = [
                    'name' => basename($path),
                    'size' => filesize($path),
                    'size_formatted' => $this->formatBytes(filesize($path)),
                    'path' => $path
                ];
            }
        }
        
        return $attachmentInfo;
    }

    /**
     * ✅ Helper method สำหรับ format file size
     */
    private function formatBytes($size, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];
        $validAttachments = [];
        $invalidAttachments = [];

        Log::info('Processing email attachments', [
            'total_paths' => count($this->attachmentPaths),
            'paths' => $this->attachmentPaths
        ]);

        foreach ($this->attachmentPaths as $path) {
            try {
                Log::debug('Processing attachment path', [
                    'path' => $path,
                    'exists' => file_exists($path)
                ]);
                
                if (file_exists($path)) {
                    $fileSize = filesize($path);
                    $maxSize = 25 * 1024 * 1024; // 25MB limit per file
                    
                    if ($fileSize <= $maxSize) {
                        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
                        
                        $attachment = Attachment::fromPath($path)
                            ->withMime($mimeType);
                        
                        $attachments[] = $attachment;
                        $validAttachments[] = [
                            'path' => $path,
                            'name' => basename($path),
                            'size' => $fileSize,
                            'mime_type' => $mimeType
                        ];
                        
                        Log::info('Attachment successfully added to email', [
                            'path' => $path,
                            'name' => basename($path),
                            'size' => $fileSize,
                            'mime_type' => $mimeType
                        ]);
                    } else {
                        $invalidAttachments[] = [
                            'path' => $path,
                            'reason' => 'File too large',
                            'size' => $fileSize,
                            'max_size' => $maxSize
                        ];
                        
                        Log::warning('Attachment skipped - file too large', [
                            'path' => $path,
                            'size' => $fileSize,
                            'max_size' => $maxSize
                        ]);
                    }
                } else {
                    $invalidAttachments[] = [
                        'path' => $path,
                        'reason' => 'File not found'
                    ];
                    
                    Log::warning('Attachment skipped - file not found', [
                        'path' => $path
                    ]);
                }
            } catch (\Exception $e) {
                $invalidAttachments[] = [
                    'path' => $path,
                    'reason' => 'Error processing file',
                    'error' => $e->getMessage()
                ];
                
                Log::error('Error processing attachment', [
                    'path' => $path,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Email attachments processing completed', [
            'total_requested' => count($this->attachmentPaths),
            'valid_attachments' => count($validAttachments),
            'invalid_attachments' => count($invalidAttachments),
            'final_attachment_count' => count($attachments),
            'valid_files' => array_column($validAttachments, 'name'),
            'invalid_reasons' => array_column($invalidAttachments, 'reason')
        ]);

        // ✅ ถ้าไม่มีไฟล์แนบเลย ให้แจ้งเตือน
        if (empty($attachments) && !empty($this->attachmentPaths)) {
            Log::warning('No valid attachments found despite paths provided', [
                'provided_paths' => $this->attachmentPaths,
                'invalid_attachments' => $invalidAttachments
            ]);
        }

        return $attachments;
    }
}