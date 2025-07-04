<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmergencyEmailService
{
    private $configurations = [];

    public function __construct()
    {
        // ✅ หลายๆ configuration สำหรับ fallback
        $this->configurations = [
            'primary_plain' => [
                'host' => '10.12.14.26',
                'port' => 25,
                'encryption' => null,
                'username' => null,
                'password' => null,
                'description' => 'Primary server, plain SMTP'
            ],
            'primary_ssl' => [
                'host' => '10.12.14.26',
                'port' => 465,
                'encryption' => 'ssl',
                'username' => null,
                'password' => null,
                'description' => 'Primary server, SSL'
            ],
            'localhost' => [
                'host' => 'localhost',
                'port' => 25,
                'encryption' => null,
                'username' => null,
                'password' => null,
                'description' => 'Local sendmail/postfix'
            ],
            'hostname_alternative' => [
                'host' => 'mail.company.local',
                'port' => 587,
                'encryption' => 'tls',
                'username' => null,
                'password' => null,
                'description' => 'Alternative hostname'
            ]
        ];
    }

    /**
     * ✅ Emergency send email - ลองทุกวิธี
     */
    public function emergencySend(array $emailData)
    {
        $attempts = [];
        
        foreach ($this->configurations as $name => $config) {
            try {
                Log::info("🚨 Emergency email attempt: {$name}", [
                    'config' => $config,
                    'to' => $emailData['to']
                ]);

                $result = $this->sendWithConfiguration($emailData, $config);
                
                if ($result['success']) {
                    Log::info("✅ Emergency email SUCCESS with {$name}", [
                        'config' => $config,
                        'to' => $emailData['to']
                    ]);

                    return [
                        'success' => true,
                        'method' => $name,
                        'config_used' => $config,
                        'attempts' => $attempts,
                        'message' => "Emergency email sent successfully using {$config['description']}"
                    ];
                }

                $attempts[] = [
                    'method' => $name,
                    'config' => $config,
                    'success' => false,
                    'error' => $result['error'] ?? 'Unknown error'
                ];

            } catch (\Exception $e) {
                $attempts[] = [
                    'method' => $name,
                    'config' => $config,
                    'success' => false,
                    'error' => $e->getMessage()
                ];

                Log::error("❌ Emergency email FAILED with {$name}", [
                    'config' => $config,
                    'error' => $e->getMessage(),
                    'to' => $emailData['to']
                ]);

                continue;
            }
        }

        // ✅ ถ้าทุกวิธีล้มเหลว ลองใช้ file logging
        $fileResult = $this->saveToFile($emailData);
        $attempts[] = $fileResult;

        Log::error("🚨 ALL emergency email methods failed", [
            'to' => $emailData['to'],
            'attempts' => $attempts
        ]);

        return [
            'success' => false,
            'error' => 'All emergency email methods failed',
            'attempts' => $attempts,
            'file_backup' => $fileResult
        ];
    }

    /**
     * ✅ ส่งอีเมลด้วย configuration เฉพาะ
     */
    private function sendWithConfiguration(array $emailData, array $config)
    {
        // Test connectivity first
        $connectionTest = $this->testConnection($config['host'], $config['port']);
        
        if (!$connectionTest['success']) {
            return [
                'success' => false,
                'error' => "Connection test failed: " . $connectionTest['error']
            ];
        }

        // Backup original config
        $originalConfig = [
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'username' => config('mail.mailers.smtp.username'),
            'password' => config('mail.mailers.smtp.password'),
        ];

        try {
            // Apply emergency configuration
            config([
                'mail.mailers.smtp.host' => $config['host'],
                'mail.mailers.smtp.port' => $config['port'],
                'mail.mailers.smtp.encryption' => $config['encryption'],
                'mail.mailers.smtp.username' => $config['username'],
                'mail.mailers.smtp.password' => $config['password'],
                'mail.mailers.smtp.verify_peer' => false,
                'mail.mailers.smtp.verify_peer_name' => false,
                'mail.mailers.smtp.allow_self_signed' => true,
            ]);

            // Send email
            Mail::send([], [], function ($message) use ($emailData) {
                $message->to($emailData['to'])
                       ->subject($emailData['subject'] ?? 'Emergency Email');
                
                if (!empty($emailData['body_html'])) {
                    $message->html($emailData['body_html']);
                }
                
                if (!empty($emailData['body_text'])) {
                    $message->text($emailData['body_text']);
                } else {
                    $message->text($emailData['body_html'] ? strip_tags($emailData['body_html']) : 'Emergency email content');
                }
                
                $fromEmail = $emailData['from_address'] ?? config('mail.from.address', 'noreply@company.com');
                $fromName = $emailData['from_name'] ?? config('mail.from.name', 'Emergency System');
                $message->from($fromEmail, $fromName);
            });

            return [
                'success' => true,
                'config_used' => $config
            ];

        } finally {
            // Restore original configuration
            config([
                'mail.mailers.smtp.host' => $originalConfig['host'],
                'mail.mailers.smtp.port' => $originalConfig['port'],
                'mail.mailers.smtp.encryption' => $originalConfig['encryption'],
                'mail.mailers.smtp.username' => $originalConfig['username'],
                'mail.mailers.smtp.password' => $originalConfig['password'],
            ]);
        }
    }

    /**
     * ✅ ทดสอบการเชื่อมต่อก่อนส่ง
     */
    private function testConnection(string $host, int $port): array
    {
        try {
            $socket = @fsockopen($host, $port, $errno, $errstr, 5);
            
            if ($socket) {
                fclose($socket);
                return [
                    'success' => true,
                    'message' => "Connection to {$host}:{$port} successful"
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "Connection to {$host}:{$port} failed: {$errstr} ({$errno})"
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Exception testing connection: " . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ บันทึกอีเมลลงไฟล์เป็น backup สุดท้าย
     */
    private function saveToFile(array $emailData): array
    {
        try {
            $filename = 'failed_emails_' . now()->format('Y_m_d') . '.log';
            $filepath = storage_path('logs/' . $filename);
            
            $emailContent = [
                'timestamp' => now()->toISOString(),
                'to' => $emailData['to'],
                'subject' => $emailData['subject'] ?? 'No subject',
                'body_text' => $emailData['body_text'] ?? '',
                'body_html' => $emailData['body_html'] ?? '',
                'from_address' => $emailData['from_address'] ?? config('mail.from.address'),
                'status' => 'saved_to_file_due_to_smtp_failure'
            ];
            
            $logEntry = json_encode($emailContent, JSON_PRETTY_PRINT) . "\n" . str_repeat('=', 80) . "\n";
            
            file_put_contents($filepath, $logEntry, FILE_APPEND | LOCK_EX);
            
            Log::info("📁 Email saved to file as backup", [
                'filepath' => $filepath,
                'to' => $emailData['to']
            ]);
            
            return [
                'method' => 'file_backup',
                'success' => true,
                'filepath' => $filepath,
                'message' => "Email saved to file: {$filename}"
            ];
            
        } catch (\Exception $e) {
            return [
                'method' => 'file_backup',
                'success' => false,
                'error' => "Failed to save to file: " . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ ดู emails ที่ถูกบันทึกในไฟล์
     */
    public function getFailedEmails(string $date = null): array
    {
        try {
            $date = $date ?: now()->format('Y_m_d');
            $filename = 'failed_emails_' . $date . '.log';
            $filepath = storage_path('logs/' . $filename);
            
            if (!file_exists($filepath)) {
                return [
                    'success' => false,
                    'error' => "No failed emails file found for date: {$date}"
                ];
            }
            
            $content = file_get_contents($filepath);
            $emails = [];
            
            // แยก emails ด้วย separator
            $emailBlocks = explode(str_repeat('=', 80), $content);
            
            foreach ($emailBlocks as $block) {
                $block = trim($block);
                if (empty($block)) continue;
                
                $emailData = json_decode($block, true);
                if ($emailData) {
                    $emails[] = $emailData;
                }
            }
            
            return [
                'success' => true,
                'filepath' => $filepath,
                'count' => count($emails),
                'emails' => $emails
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Error reading failed emails: " . $e->getMessage()
            ];
        }
    }

    /**
     * ✅ ลองส่งอีเมลที่ fail ไว้อีกครั้ง
     */
    public function retryFailedEmails(string $date = null): array
    {
        $failedEmails = $this->getFailedEmails($date);
        
        if (!$failedEmails['success']) {
            return $failedEmails;
        }
        
        $results = [];
        
        foreach ($failedEmails['emails'] as $emailData) {
            Log::info("🔄 Retrying failed email", [
                'to' => $emailData['to'],
                'original_timestamp' => $emailData['timestamp']
            ]);
            
            $result = $this->emergencySend($emailData);
            $results[] = [
                'original_email' => $emailData,
                'retry_result' => $result
            ];
        }
        
        return [
            'success' => true,
            'total_retried' => count($results),
            'results' => $results
        ];
    }
}