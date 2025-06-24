<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Send email directly - Internal SMTP (No Auth Required)
     */
    public function sendDirect(array $emailData)
    {
        try {
            Log::info('🚀 Sending email via Internal SMTP', [
                'to' => $emailData['to'],
                'subject' => $emailData['subject'],
                'smtp_host' => config('mail.mailers.smtp.host'),
                'smtp_port' => config('mail.mailers.smtp.port')
            ]);

            // Basic validation only
            if (empty($emailData['to']) || !filter_var($emailData['to'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Invalid recipient email address'];
            }

            if (empty($emailData['subject'])) {
                return ['success' => false, 'error' => 'Email subject is required'];
            }

            if (empty($emailData['body_html']) && empty($emailData['body_text'])) {
                return ['success' => false, 'error' => 'Email content is required'];
            }

            // Check if SMTP host is configured
            if (empty(config('mail.mailers.smtp.host'))) {
                return ['success' => false, 'error' => 'SMTP host not configured'];
            }

            // Replace variables in content
            $bodyHtml = $this->replaceVariables($emailData['body_html'] ?? '', $emailData['variables'] ?? []);
            $bodyText = $this->replaceVariables($emailData['body_text'] ?? '', $emailData['variables'] ?? []);
            
            // Determine format
            $format = $emailData['user_preferences']['format'] ?? 'html';
            
            // Set from address
            $fromAddress = config('mail.from.address', 'noreply@sam.or.th');
            $fromName = config('mail.from.name', 'Smart Notification System');
            
            // Send email
            if ($format === 'html' && !empty($bodyHtml)) {
                Mail::html($bodyHtml, function ($message) use ($emailData, $fromAddress, $fromName) {
                    $message->to($emailData['to'])
                            ->subject($emailData['subject'])
                            ->from($fromAddress, $fromName);
                });
            } else {
                Mail::raw($bodyText, function ($message) use ($emailData, $fromAddress, $fromName) {
                    $message->to($emailData['to'])
                            ->subject($emailData['subject'])
                            ->from($fromAddress, $fromName);
                });
            }
            
            Log::info('✅ Email sent successfully', [
                'to' => $emailData['to'],
                'subject' => $emailData['subject'],
                'format' => $format,
                'from' => $fromAddress
            ]);
            
            return [
                'success' => true, 
                'message' => 'Email sent successfully',
                'method' => 'internal_smtp',
                'format' => $format
            ];
            
        } catch (\Exception $e) {
            Log::error('❌ Email send failed', [
                'to' => $emailData['to'] ?? 'unknown',
                'subject' => $emailData['subject'] ?? 'unknown',
                'error' => $e->getMessage(),
                'smtp_host' => config('mail.mailers.smtp.host'),
                'smtp_port' => config('mail.mailers.smtp.port'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false, 
                'error' => $this->getUserFriendlyError($e->getMessage()),
                'technical_error' => $e->getMessage()
            ];
        }
    }

    public function sendNotification($recipient, $subject, $bodyHtml, $bodyText)
    {
        return $this->sendDirect([
            'to' => $recipient['email'],
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'user_preferences' => ['format' => 'html']
        ]);
    }

    /**
     * Test email configuration
     */
    public function testConfiguration()
    {
        try {
            Log::info('🧪 Testing email configuration');

            // Check basic SMTP config
            $host = config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port');
            $fromAddress = config('mail.from.address');

            if (empty($host)) {
                return [
                    'success' => false,
                    'error' => 'SMTP host not configured',
                    'config_status' => 'invalid'
                ];
            }

            if (empty($fromAddress)) {
                return [
                    'success' => false,
                    'error' => 'From address not configured',
                    'config_status' => 'invalid'
                ];
            }

            // Try to send test email
            $testEmail = [
                'to' => $fromAddress, // Send to same address
                'subject' => '[TEST] Internal SMTP Test - ' . now()->format('Y-m-d H:i:s'),
                'body_html' => $this->getTestEmailHtml(),
                'body_text' => $this->getTestEmailText(),
                'variables' => [
                    'test_time' => now()->format('Y-m-d H:i:s'),
                    'smtp_host' => $host,
                    'smtp_port' => $port
                ],
                'user_preferences' => ['format' => 'html']
            ];

            $result = $this->sendDirect($testEmail);

            return array_merge($result, [
                'test_email_sent_to' => $fromAddress,
                'config_details' => [
                    'driver' => config('mail.default'),
                    'host' => $host,
                    'port' => $port,
                    'encryption' => config('mail.mailers.smtp.encryption') ?: 'none',
                    'auth_required' => false,
                    'from_address' => $fromAddress,
                    'from_name' => config('mail.from.name')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('🚨 Email configuration test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'config_status' => 'test_failed'
            ];
        }
    }

    /**
     * Replace variables in content
     */
    private function replaceVariables($content, $variables)
    {
        if (empty($content) || empty($variables)) {
            return $content;
        }

        foreach ($variables as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }
        
        return $content;
    }

    /**
     * Get user-friendly error message
     */
    private function getUserFriendlyError($error)
    {
        if (strpos($error, 'Connection refused') !== false || strpos($error, 'Connection could not be established') !== false) {
            return 'Cannot connect to mail server. Please check if mail server is running and accessible.';
        }
        
        if (strpos($error, 'Connection timed out') !== false) {
            return 'Mail server connection timed out. Please check network connectivity.';
        }

        if (strpos($error, 'Could not authenticate') !== false || strpos($error, 'authentication') !== false) {
            return 'Mail server authentication failed.';
        }

        if (strpos($error, 'relay not permitted') !== false) {
            return 'Mail relay not permitted. Please check server configuration.';
        }

        // Return a cleaned version of the error
        $lines = explode("\n", $error);
        $firstLine = trim($lines[0]);
        
        return "Mail service error: " . substr($firstLine, 0, 100);
    }

    /**
     * Get test email HTML
     */
    private function getTestEmailHtml()
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd;">
            <div style="background: #2196F3; color: white; padding: 20px; text-align: center;">
                <h1>📧 Internal SMTP Test</h1>
                <p>Smart Notification System</p>
            </div>
            <div style="padding: 20px; background: #f9f9f9;">
                <h2>SMTP Configuration Test Successful!</h2>
                <p>Your internal SMTP server is working correctly.</p>
                <div style="background: white; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #2196F3;">
                    <strong>📋 Test Details:</strong><br>
                    • Test Time: {{test_time}}<br>
                    • SMTP Host: {{smtp_host}}<br>
                    • SMTP Port: {{smtp_port}}<br>
                    • Authentication: Not Required<br>
                    • Status: ✅ Working
                </div>
                <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; border: 1px solid #4caf50;">
                    <strong style="color: #2e7d32;">✅ Success!</strong><br>
                    <span style="color: #2e7d32;">Internal SMTP server is configured and working properly.</span>
                </div>
            </div>
        </div>';
    }

    /**
     * Get test email text
     */
    private function getTestEmailText()
    {
        return '
📧 INTERNAL SMTP TEST - Smart Notification System

SMTP Configuration Test Successful!

Your internal SMTP server is working correctly.

📋 Test Details:
• Test Time: {{test_time}}
• SMTP Host: {{smtp_host}}
• SMTP Port: {{smtp_port}}
• Authentication: Not Required
• Status: ✅ Working

✅ Success!
Internal SMTP server is configured and working properly.
        ';
    }
}