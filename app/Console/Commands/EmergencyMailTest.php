<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmergencyEmailService;
use App\Services\ImprovedEmailService;

class EmergencyMailTest extends Command
{
    protected $signature = 'mail:emergency-test {email} {--retry-failed} {--network-diag} {--method=all}';
    protected $description = 'Emergency email testing with multiple fallback methods';

    public function handle()
    {
        $email = $this->argument('email');
        $this->info("🚨 Emergency Email Testing");
        $this->info("Target Email: {$email}");
        $this->line('');

        // Network Diagnostics
        if ($this->option('network-diag')) {
            $this->runNetworkDiagnostics();
        }

        // Retry Failed Emails
        if ($this->option('retry-failed')) {
            $this->retryFailedEmails();
            return;
        }

        // Test Emergency Send
        $this->testEmergencySend($email);
    }

    private function runNetworkDiagnostics()
    {
        $this->info("🔍 Running Network Diagnostics...");
        
        try {
            $service = new ImprovedEmailService();
            $reflection = new \ReflectionClass($service);
            $method = $reflection->getMethod('checkNetworkConnectivity');
            $method->setAccessible(true);
            
            $networkStatus = $method->invoke($service);
            
            $this->line("📡 Network Status:");
            $this->line("Host: " . $networkStatus['host']);
            $this->line("Primary Port: " . $networkStatus['primary_port']);
            $this->line('');
            
            $this->line("🔌 Connectivity Tests:");
            foreach ($networkStatus['connectivity'] as $test => $result) {
                $status = $result['status'] === 'open' || $result['status'] === 'reachable' ? '✅' : '❌';
                $this->line("  {$test}: {$status} {$result['status']}");
                if (!empty($result['error'])) {
                    $this->line("    Error: " . $result['error']);
                }
            }
            
            $this->line('');
            $this->line("💡 Recommendations:");
            foreach ($networkStatus['recommendations'] as $recommendation) {
                $this->line("  • " . $recommendation);
            }
            
        } catch (\Exception $e) {
            $this->error("Network diagnostics failed: " . $e->getMessage());
        }
        
        $this->line('');
    }

    private function testEmergencySend(string $email)
    {
        $this->info("📧 Testing Emergency Email Send...");
        
        $emailData = [
            'to' => $email,
            'subject' => '[EMERGENCY TEST] Email System Diagnostics - ' . now()->format('Y-m-d H:i:s'),
            'body_html' => $this->getTestEmailHtml(),
            'body_text' => $this->getTestEmailText()
        ];

        try {
            $emergencyService = new EmergencyEmailService();
            $result = $emergencyService->emergencySend($emailData);
            
            if ($result['success']) {
                $this->info("✅ Emergency email sent successfully!");
                $this->line("Method used: " . $result['method']);
                $this->line("Description: " . $result['config_used']['description']);
                $this->line("Host: " . $result['config_used']['host'] . ":" . $result['config_used']['port']);
            } else {
                $this->error("❌ All emergency email methods failed");
                $this->line("Total attempts: " . count($result['attempts']));
                
                $this->line("\nAttempt Details:");
                foreach ($result['attempts'] as $attempt) {
                    $status = $attempt['success'] ? '✅' : '❌';
                    $this->line("  {$status} {$attempt['method']}: " . ($attempt['error'] ?? 'Success'));
                }
                
                if (!empty($result['file_backup']) && $result['file_backup']['success']) {
                    $this->warn("📁 Email saved to file as backup: " . $result['file_backup']['filepath']);
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Emergency email test failed: " . $e->getMessage());
        }
    }

    private function retryFailedEmails()
    {
        $this->info("🔄 Retrying Failed Emails...");
        
        try {
            $emergencyService = new EmergencyEmailService();
            $result = $emergencyService->retryFailedEmails();
            
            if ($result['success']) {
                $this->info("Total emails retried: " . $result['total_retried']);
                
                $successCount = 0;
                $failCount = 0;
                
                foreach ($result['results'] as $retry) {
                    if ($retry['retry_result']['success']) {
                        $successCount++;
                        $this->line("✅ " . $retry['original_email']['to'] . " - SUCCESS");
                    } else {
                        $failCount++;
                        $this->line("❌ " . $retry['original_email']['to'] . " - FAILED");
                    }
                }
                
                $this->line('');
                $this->info("Summary: {$successCount} successful, {$failCount} failed");
                
            } else {
                $this->error("Failed to retry emails: " . $result['error']);
            }
            
        } catch (\Exception $e) {
            $this->error("Retry failed emails error: " . $e->getMessage());
        }
    }

    private function getTestEmailHtml()
    {
        return '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 2px solid #ff6b35;">
            <div style="background: #ff6b35; color: white; padding: 20px; text-align: center;">
                <h1>🚨 EMERGENCY EMAIL TEST</h1>
                <p>Smart Notification System - Emergency Fallback</p>
            </div>
            <div style="padding: 20px; background: #fff3cd;">
                <h2>Emergency Email System Working!</h2>
                <p>This email was sent using the emergency fallback email system after the primary SMTP server failed.</p>
                
                <div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #c3e6cb;">
                    <strong style="color: #155724;">✅ Emergency System Status:</strong><br>
                    <span style="color: #155724;">
                    • Multiple SMTP configurations tested<br>
                    • Network connectivity diagnosed<br>
                    • Alternative ports attempted<br>
                    • Fallback methods activated<br>
                    • Email delivery successful
                    </span>
                </div>
                
                <div style="background: #f8d7da; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;">
                    <strong style="color: #721c24;">⚠️ Primary SMTP Issue:</strong><br>
                    <span style="color: #721c24;">The primary SMTP server (10.12.14.26:587) is currently unreachable. Please contact your network administrator.</span>
                </div>
                
                <p><strong>Test Time:</strong> ' . now()->format('Y-m-d H:i:s T') . '</p>
            </div>
        </div>';
    }

    private function getTestEmailText()
    {
        return '
🚨 EMERGENCY EMAIL TEST - Smart Notification System

Emergency Email System Working!

This email was sent using the emergency fallback email system after the primary SMTP server failed.

✅ Emergency System Status:
• Multiple SMTP configurations tested
• Network connectivity diagnosed  
• Alternative ports attempted
• Fallback methods activated
• Email delivery successful

⚠️ Primary SMTP Issue:
The primary SMTP server (10.12.14.26:587) is currently unreachable. Please contact your network administrator.

Test Time: ' . now()->format('Y-m-d H:i:s T') . '

If you received this email, the emergency email system is working correctly.
        ';
    }
}