<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SecureEmailService;
use Illuminate\Support\Facades\Log;

class TestMailConnection extends Command
{
    protected $signature = 'mail:test-ssl {--method=all : Test specific method (all|relaxed|plain|custom)}';
    protected $description = 'Test mail connection with different SSL methods';

    public function handle()
    {
        $this->info('🧪 Testing Mail Connection with SSL Methods');
        $this->line('');

        $method = $this->option('method');
        $emailService = new SecureEmailService();

        if ($method === 'all') {
            $this->testAllMethods($emailService);
        } else {
            $this->testSpecificMethod($emailService, $method);
        }
    }

    private function testAllMethods(SecureEmailService $emailService)
    {
        $this->info('Testing all connection methods...');
        $this->line('');

        $results = $emailService->testAllMethods();

        foreach ($results as $method => $result) {
            if ($result['success']) {
                $this->info("✅ {$method}: " . $result['message']);
            } else {
                $this->error("❌ {$method}: " . $result['error']);
            }
        }

        $this->line('');
        $this->info('Test completed. Check logs for details.');
    }

    private function testSpecificMethod(SecureEmailService $emailService, $method)
    {
        $testEmail = [
            'to' => config('mail.from.address'),
            'subject' => "[TEST] {$method} method - " . now()->format('Y-m-d H:i:s'),
            'body_html' => "<h1>{$method} Test</h1><p>Testing {$method} connection method.</p>",
            'body_text' => "{$method} Test - Testing {$method} connection method."
        ];

        try {
            $result = $emailService->sendSecureEmail($testEmail);
            
            if ($result['success']) {
                $this->info("✅ Email sent successfully using {$result['method']} method");
            } else {
                $this->error("❌ Failed to send email: " . $result['error']);
            }
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
        }
    }
}