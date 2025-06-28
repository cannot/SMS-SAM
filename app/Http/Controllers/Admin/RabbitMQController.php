<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQController extends Controller
{
    /**
     * Display RabbitMQ dashboard
     */
    public function index()
    {
        $stats = $this->getRabbitMQStats();
        $queues = $this->getQueueInformation();
        
        return view('admin.rabbitmq.index', compact('stats', 'queues'));
    }

    /**
     * Get RabbitMQ statistics
     */
    public function getStats()
    {
        try {
            $stats = $this->getRabbitMQStats();
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get queue information via RabbitMQ Management API
     */
    private function getRabbitMQStats()
    {
        $host = env('RABBITMQ_HOST', '127.0.0.1');
        $port = env('RABBITMQ_MANAGEMENT_PORT', 15672);
        $user = env('RABBITMQ_USER', 'guest');
        $password = env('RABBITMQ_PASSWORD', 'guest');

        try {
            // ใช้ Management API
            $url = "http://{$host}:{$port}/api/overview";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return [
                    'status' => 'connected',
                    'rabbitmq_version' => $data['rabbitmq_version'] ?? 'Unknown',
                    'erlang_version' => $data['erlang_version'] ?? 'Unknown',
                    'message_stats' => $data['message_stats'] ?? [],
                    'queue_totals' => $data['queue_totals'] ?? [],
                    'object_totals' => $data['object_totals'] ?? []
                ];
            } else {
                throw new \Exception("HTTP Error: {$httpCode}");
            }
        } catch (\Exception $e) {
            return [
                'status' => 'disconnected',
                'error' => $e->getMessage(),
                'rabbitmq_version' => 'Unknown',
                'erlang_version' => 'Unknown'
            ];
        }
    }

    /**
     * Get detailed queue information
     */
    private function getQueueInformation()
    {
        $host = env('RABBITMQ_HOST', '127.0.0.1');
        $port = env('RABBITMQ_MANAGEMENT_PORT', 15672);
        $user = env('RABBITMQ_USER', 'guest');
        $password = env('RABBITMQ_PASSWORD', 'guest');
        $vhost = env('RABBITMQ_VHOST', '/');

        try {
            $url = "http://{$host}:{$port}/api/queues/" . urlencode($vhost);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return json_decode($response, true);
            } else {
                return [];
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Test RabbitMQ connection
     */
    public function testConnection()
    {
        try {
            $host = env('RABBITMQ_HOST', '127.0.0.1');
            $port = env('RABBITMQ_PORT', 5672);
            $user = env('RABBITMQ_USER', 'guest');
            $password = env('RABBITMQ_PASSWORD', 'guest');
            $vhost = env('RABBITMQ_VHOST', '/');

            $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
            $channel = $connection->channel();
            
            // ทดสอบการสร้าง queue
            $channel->queue_declare('test_queue', false, false, false, false);
            
            $channel->close();
            $connection->close();

            return response()->json([
                'success' => true,
                'message' => 'RabbitMQ connection successful'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get Laravel queue status
     */
    public function getLaravelQueueStatus()
    {
        try {
            // ตรวจสอบ failed jobs
            $failedJobs = \DB::table('failed_jobs')->count();
            
            // ตรวจสอบ pending jobs
            $pendingJobs = \DB::table('jobs')->count();
            
            // ตรวจสอบ queue connection
            $connectionName = config('queue.default');
            $connection = Queue::connection($connectionName);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'connection' => $connectionName,
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                    'is_working' => $this->isQueueWorking()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if queue worker is running
     */
    private function isQueueWorking()
    {
        // ตรวจสอบว่า queue worker กำลังทำงานหรือไม่
        // ในระบบ production อาจใช้ supervisor หรือ systemd
        $output = [];
        $return_var = 0;
        
        exec('ps aux | grep "queue:work" | grep -v grep', $output, $return_var);
        
        return count($output) > 0;
    }

    /**
     * Dispatch test job to queue
     */
    public function dispatchTestJob()
    {
        try {
            // สร้าง test job
            $job = new \App\Jobs\TestQueueJob();
            dispatch($job);

            return response()->json([
                'success' => true,
                'message' => 'Test job dispatched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Purge a specific queue
     */
    public function purgeQueue(Request $request)
    {
        $request->validate([
            'queue_name' => 'required|string'
        ]);

        try {
            $host = env('RABBITMQ_HOST', '127.0.0.1');
            $port = env('RABBITMQ_PORT', 5672);
            $user = env('RABBITMQ_USER', 'guest');
            $password = env('RABBITMQ_PASSWORD', 'guest');
            $vhost = env('RABBITMQ_VHOST', '/');

            $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
            $channel = $connection->channel();
            
            $channel->queue_purge($request->queue_name);
            
            $channel->close();
            $connection->close();

            return response()->json([
                'success' => true,
                'message' => "Queue '{$request->queue_name}' purged successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}