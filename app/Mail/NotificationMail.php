<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailData;
    public $mailType;

    /**
     * Create a new message instance.
     */
    public function __construct(array $emailData, string $type = 'notification')
    {
        $this->emailData = $emailData;
        $this->mailType = $type;
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
        
        if ($format === 'html' && !empty($this->emailData['body_html'])) {
            return new Content(
                view: 'emails.notification-html',
                text: 'emails.notification-text',
                with: [
                    'emailData' => $this->emailData,
                    'mailType' => $this->mailType,
                    'htmlContent' => $this->emailData['body_html'],
                    'textContent' => $this->emailData['body_text'] ?? strip_tags($this->emailData['body_html']),
                ]
            );
        } else {
            return new Content(
                text: 'emails.notification-text',
                with: [
                    'emailData' => $this->emailData,
                    'mailType' => $this->mailType,
                    'textContent' => $this->emailData['body_text'] ?? strip_tags($this->emailData['body_html'] ?? ''),
                ]
            );
        }
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}