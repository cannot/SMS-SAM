<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email=nutthawut@sam.or.th}';
    protected $description = 'Test email configuration with SSL bypass';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info('Testing email configuration...');
        $this->info('Target email: ' . $email);
        
        // Method 1: Laravel Mail with config changes
        $this->info("\n=== Method 1: Laravel Mail ===");
        try {
            Mail::raw('Test message from Laravel Mail', function($msg) use ($email) {
                $msg->to($email)->subject('Test Email - Method 1');
            });
            $this->info('✅ Method 1: SUCCESS');
        } catch (\Exception $e) {
            $this->error('❌ Method 1: FAILED - ' . $e->getMessage());
        }

        // Method 2: Direct Symfony Mailer with SSL bypass
        $this->info("\n=== Method 2: Direct Symfony Mailer ===");
        try {
            $this->sendWithSymfonyMailer($email);
            $this->info('✅ Method 2: SUCCESS');
        } catch (\Exception $e) {
            $this->error('❌ Method 2: FAILED - ' . $e->getMessage());
        }

        // Method 3: No encryption
        $this->info("\n=== Method 3: No Encryption ===");
        try {
            $this->sendWithoutEncryption($email);
            $this->info('✅ Method 3: SUCCESS');
        } catch (\Exception $e) {
            $this->error('❌ Method 3: FAILED - ' . $e->getMessage());
        }

        // Method 4: Different port
        $this->info("\n=== Method 4: Port 25 ===");
        try {
            $this->sendWithPort25($email);
            $this->info('✅ Method 4: SUCCESS');
        } catch (\Exception $e) {
            $this->error('❌ Method 4: FAILED - ' . $e->getMessage());
        }

        $this->info("\n=== Test completed ===");
    }

    private function sendWithSymfonyMailer($email)
    {
        $transport = new EsmtpTransport(
            config('mail.mailers.smtp.host'),
            config('mail.mailers.smtp.port'),
            false // ไม่ใช้ TLS
        );

        $transport->setUsername(config('mail.mailers.smtp.username'));
        $transport->setPassword(config('mail.mailers.smtp.password'));

        // ตั้งค่า stream options
        $transport->setStreamOptions([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'SNI_enabled' => false,
                'ciphers' => 'DEFAULT@SECLEVEL=1'
            ]
        ]);

        $mailer = new \Symfony\Component\Mailer\Mailer($transport);

        $message = (new Email())
            ->from(config('mail.from.address'))
            ->to($email)
            ->subject('Test Email - Method 2 (Symfony Direct)')
            ->text('Test message from direct Symfony Mailer')
            ->html('<p>Test message from direct Symfony Mailer</p>');

        $mailer->send($message);
    }

    private function sendWithoutEncryption($email)
    {
        // ชั่วคราวเปลี่ยน config
        $originalEncryption = config('mail.mailers.smtp.encryption');
        $originalPort = config('mail.mailers.smtp.port');
        
        config(['mail.mailers.smtp.encryption' => null]);
        config(['mail.mailers.smtp.port' => 25]);

        try {
            Mail::raw('Test message without encryption', function($msg) use ($email) {
                $msg->to($email)->subject('Test Email - Method 3 (No Encryption)');
            });
        } finally {
            // คืนค่า config เดิม
            config(['mail.mailers.smtp.encryption' => $originalEncryption]);
            config(['mail.mailers.smtp.port' => $originalPort]);
        }
    }

    private function sendWithPort25($email)
    {
        $originalPort = config('mail.mailers.smtp.port');
        $originalEncryption = config('mail.mailers.smtp.encryption');
        
        config(['mail.mailers.smtp.port' => 25]);
        config(['mail.mailers.smtp.encryption' => null]);

        try {
            Mail::raw('Test message with port 25', function($msg) use ($email) {
                $msg->to($email)->subject('Test Email - Method 4 (Port 25)');
            });
        } finally {
            config(['mail.mailers.smtp.port' => $originalPort]);
            config(['mail.mailers.smtp.encryption' => $originalEncryption]);
        }
    }
}