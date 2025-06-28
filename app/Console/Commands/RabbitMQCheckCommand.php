<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQCheckCommand extends Command
{
    protected $signature = 'rabbitmq:check {--test-send : Send a test message}';
    protected $description = 'Check RabbitMQ connection and status';

    public function handle()
    {
        $this->info('🐰 RabbitMQ Connection Check');
        $this->info('================================');

        // Check configuration
        $this->checkConfiguration();

        // Test connection
        if ($this->testConnection()) {
            $this->info('✅ RabbitMQ connection successful');
            
            if ($this->option('test-send')) {
                $this->testMessageSending();
            }
            
            $this->showQueueStatus();
        } else {
            $this->error('❌ RabbitMQ connection failed');
            return 1;
        }

        return 0;
    }

    private function checkConfiguration()
    {
        $this->info('📋 Configuration Check:');
        
        $config = [
            'QUEUE_CONNECTION' => env('QUEUE_CONNECTION'),
            'RABBITMQ_HOST' => env('RABBITMQ_HOST', '127.0.0.1'),
            'RABBITMQ_PORT' => env('RABBITMQ_PORT', 5672),
            'RABBITMQ_USER' => env('RABBITMQ_USER', 'guest'),
            'RABBITMQ_VHOST' => env('RABBITMQ_VHOST', '/'),
            'RABBITMQ_QUEUE' => env('RABBITMQ_QUEUE', 'default'),
        ];

        foreach ($config as $key => $value) {
            $this->line("  {$key}: " . ($value ?: 'not set'));
        }
        
        $this->newLine();
    }

    private function testConnection()
    {
        try {
            $host = env('RABBITMQ_HOST', '127.0.0.1');
            $port = env('RABBITMQ_PORT', 5672);
            $user = env('RABBITMQ_USER', 'guest');
            $password = env('RABBITMQ_PASSWORD', 'guest');
            $vhost = env('RABBITMQ_VHOST', '/');

            $this->info("🔌 Connecting to {$user}@{$host}:{$port}/{$vhost}");

            $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
            $channel = $connection->channel();

            // Test queue declaration
            $queueName = 'health_check_' . time();
            $channel->queue_declare($queueName, false, false, true, false);
            
            // Clean up
            $channel->queue_delete($queueName);
            $channel->close();
            $connection->close();

            return true;
        } catch (\Exception $e) {
            $this->error("Connection failed: " . $e->getMessage());
            $this->newLine();
            $this->error("Common solutions:");
            $this->line("1. Check if RabbitMQ server is running");
            $this->line("2. Verify connection credentials");
            $this->line("3. Check firewall settings");
            $this->line("4. Ensure RabbitMQ PHP extension is installed");
            
            return false;
        }
    }

    private function testMessageSending()
    {
        $this->info('📤 Testing message sending...');

        try {
            $host = env('RABBITMQ_HOST', '127.0.0.1');
            $port = env('RABBITMQ_PORT', 5672);
            $user = env('RABBITMQ_USER', 'guest');
            $password = env('RABBITMQ_PASSWORD', 'guest');
            $vhost = env('RABBITMQ_VHOST', '/');

            $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
            $channel = $connection->channel();

            $queueName = 'test_queue';
            $channel->queue_declare($queueName, false, true, false, false);

            // Send test message
            $messageBody = json_encode([
                'type' => 'test',
                'message' => 'Test message from RabbitMQ check command',
                'timestamp' => now()->toISOString(),
                'sender' => 'artisan:rabbitmq:check'
            ]);

            $message = new AMQPMessage($messageBody, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]);

            $channel->basic_publish($message, '', $queueName);
            $this->info("✅ Test message sent to queue: {$queueName}");

            // Consume the message back
            $receivedMessage = null;
            $channel->basic_consume($queueName, '', false, true, false, false, 
                function ($msg) use (&$receivedMessage) {
                    $receivedMessage = $msg->body;
                }
            );

            $channel->wait(null, false, 5); // Wait max 5 seconds

            if ($receivedMessage) {
                $data = json_decode($receivedMessage, true);
                $this->info("✅ Test message received: " . $data['message']);
            } else {
                $this->warn("⚠️  Test message not received within timeout");
            }

            $channel->close();
            $connection->close();

        } catch (\Exception $e) {
            $this->error("Message test failed: " . $e->getMessage());
        }
    }

    private function showQueueStatus()
    {
        $this->info('📊 Laravel Queue Status:');

        // Check database queue tables
        try {
            $pendingJobs = \DB::table('jobs')->count();
            $failedJobs = \DB::table('failed_jobs')->count();
            
            $this->line("  Pending jobs: {$pendingJobs}");
            $this->line("  Failed jobs: {$failedJobs}");
            
            if ($failedJobs > 0) {
                $this->warn("⚠️  There are {$failedJobs} failed jobs");
                $this->line("  Run 'php artisan queue:retry all' to retry them");
            }
            
        } catch (\Exception $e) {
            $this->warn("Could not check Laravel queue status: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('💡 Useful commands:');
        $this->line('  php artisan queue:work rabbitmq --verbose');
        $this->line('  php artisan queue:listen rabbitmq');
        $this->line('  php artisan queue:restart');
        $this->line('  php artisan queue:failed');
        $this->line('  php artisan queue:retry all');
    }
}