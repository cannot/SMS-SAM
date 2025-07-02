<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Mailer;

class SecureEmailService
{
    /**
     * Send email with custom SSL configuration
     */
    public function sendSecureEmail(array $emailData)
    {
        try {
            Log::info('🔐 Sending secure email', [
                'to' => $emailData['to'],
                'subject' => $emailData['subject'],
                'host' => config('mail.mailers.smtp.host')
            ]);

            // Validation
            if (empty($emailData['to']) || !filter_var($emailData['to'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Invalid email address'];
            }

            // Method 1: Try with relaxed SSL settings
            try {
                $result = $this->sendWithRelaxedSSL($emailData);
                if ($result['success']) {
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning('Method 1 failed, trying Method 2', ['error' => $e->getMessage()]);
            }

            // Method 2: Try without encryption
            try {
                $result = $this->sendWithoutEncryption($emailData);
                if ($result['success']) {
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning('Method 2 failed, trying Method 3', ['error' => $e->getMessage()]);
            }

            // Method 3: Try with custom transport
            return $this->sendWithCustomTransport($emailData);

        } catch (\Exception $e) {
            Log::error('All email methods failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Method 1: Send with relaxed SSL settings
     */
    private function sendWithRelaxedSSL(array $emailData)
    {
        // Temporarily override mail config
        config([
            'mail.mailers.smtp.verify_peer' => false,
            'mail.mailers.smtp.verify_peer_name' => false,
            'mail.mailers.smtp.allow_self_signed' => true,
            'mail.mailers.smtp.stream.ssl.verify_peer' => false,
            'mail.mailers.smtp.stream.ssl.verify_peer_name' => false,
            'mail.mailers.smtp.stream.ssl.allow_self_signed' => true,
        ]);

        Mail::raw($emailData['body_text'] ?? 'Test email', function ($message) use ($emailData) {
            $message->to($emailData['to'])
                   ->subject($emailData['subject']);
            
            if (!empty($emailData['body_html'])) {
                $message->html($emailData['body_html']);
            }
        });

        return ['success' => true, 'method' => 'relaxed_ssl'];
    }

    /**
     * Method 2: Send without encryption
     */
    private function sendWithoutEncryption(array $emailData)
    {
        // Temporarily disable encryption
        config([
            'mail.mailers.smtp.encryption' => null,
            'mail.mailers.smtp.port' => env('MAIL_PORT_PLAIN', 25),
        ]);

        Mail::raw($emailData['body_text'] ?? 'Test email', function ($message) use ($emailData) {
            $message->to($emailData['to'])
                   ->subject($emailData['subject']);
            
            if (!empty($emailData['body_html'])) {
                $message->html($emailData['body_html']);
            }
        });

        return ['success' => true, 'method' => 'no_encryption'];
    }

    /**
     * Method 3: Send with custom transport
     */
    private function sendWithCustomTransport(array $emailData)
    {
        // Create custom SMTP transport with relaxed SSL
        $transport = new EsmtpTransport(
            config('mail.mailers.smtp.host'),
            config('mail.mailers.smtp.port'),
            false // Don't use TLS
        );

        // Set authentication if provided
        if (config('mail.mailers.smtp.username')) {
            $transport->setUsername(config('mail.mailers.smtp.username'));
            $transport->setPassword(config('mail.mailers.smtp.password'));
        }

        // Create mailer with custom transport
        $mailer = new Mailer($transport);

        // Create email message
        $email = (new \Symfony\Component\Mime\Email())
            ->from(config('mail.from.address'))
            ->to($emailData['to'])
            ->subject($emailData['subject']);

        if (!empty($emailData['body_html'])) {
            $email->html($emailData['body_html']);
        }

        if (!empty($emailData['body_text'])) {
            $email->text($emailData['body_text']);
        }

        $mailer->send($email);

        return ['success' => true, 'method' => 'custom_transport'];
    }

    /**
     * Test all connection methods
     */
    public function testAllMethods()
    {
        $testEmail = [
            'to' => config('mail.from.address'),
            'subject' => '[TEST] SSL Connection Test - ' . now()->format('Y-m-d H:i:s'),
            'body_html' => '<h1>SSL Test Email</h1><p>Testing different SSL connection methods.</p>',
            'body_text' => 'SSL Test Email - Testing different SSL connection methods.'
        ];

        $results = [];

        // Test Method 1: Relaxed SSL
        try {
            $result = $this->sendWithRelaxedSSL($testEmail);
            $results['relaxed_ssl'] = ['success' => true, 'message' => 'Relaxed SSL method works'];
        } catch (\Exception $e) {
            $results['relaxed_ssl'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Test Method 2: No encryption
        try {
            $result = $this->sendWithoutEncryption($testEmail);
            $results['no_encryption'] = ['success' => true, 'message' => 'No encryption method works'];
        } catch (\Exception $e) {
            $results['no_encryption'] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Test Method 3: Custom transport
        try {
            $result = $this->sendWithCustomTransport($testEmail);
            $results['custom_transport'] = ['success' => true, 'message' => 'Custom transport method works'];
        } catch (\Exception $e) {
            $results['custom_transport'] = ['success' => false, 'error' => $e->getMessage()];
        }

        return $results;
    }
}