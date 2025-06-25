<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SystemController extends Controller
{
    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        try {
            $checks = [
                'application' => $this->checkApplication(),
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
                'queue' => $this->checkQueue(),
            ];

            $overallStatus = collect($checks)->every(fn($check) => $check['status'] === 'ok') ? 'healthy' : 'unhealthy';

            return response()->json([
                'success' => true,
                'status' => $overallStatus,
                'timestamp' => now()->toISOString(),
                'version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
                'checks' => $checks,
            ], $overallStatus === 'healthy' ? 200 : 503);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Health check failed',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Get system information
     */
    public function getInfo(): JsonResponse
    {
        try {
            $info = [
                'application' => [
                    'name' => config('app.name'),
                    'version' => config('app.version', '1.0.0'),
                    'environment' => config('app.env'),
                    'debug' => config('app.debug'),
                    'timezone' => config('app.timezone'),
                    'locale' => config('app.locale'),
                ],
                'system' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'server_time' => now()->toISOString(),
                    'uptime' => $this->getUptime(),
                ],
                'database' => [
                    'connection' => config('database.default'),
                    'driver' => config('database.connections.' . config('database.default') . '.driver'),
                ],
                'features' => [
                    'ldap_enabled' => config('ldap.enabled', false),
                    'teams_enabled' => !empty(config('teams.client_id')),
                    'email_enabled' => !empty(config('mail.host')),
                    'queue_enabled' => config('queue.default') !== 'sync',
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'notifications' => [
                    'total' => \App\Models\Notification::count(),
                    'today' => \App\Models\Notification::whereDate('created_at', today())->count(),
                    'this_week' => \App\Models\Notification::where('created_at', '>=', now()->startOfWeek())->count(),
                    'this_month' => \App\Models\Notification::where('created_at', '>=', now()->startOfMonth())->count(),
                ],
                'users' => [
                    'total' => \App\Models\User::count(),
                    'active' => \App\Models\User::where('is_active', true)->count(),
                ],
                'groups' => [
                    'total' => \App\Models\NotificationGroup::count(),
                    'active' => \App\Models\NotificationGroup::where('is_active', true)->count(),
                ],
                'templates' => [
                    'total' => \App\Models\NotificationTemplate::count(),
                    'active' => \App\Models\NotificationTemplate::where('is_active', true)->count(),
                ],
                'api_keys' => [
                    'total' => \App\Models\ApiKey::count(),
                    'active' => \App\Models\ApiKey::where('is_active', true)->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'generated_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get queue status
     */
    public function getQueueStatus(): JsonResponse
    {
        try {
            // This is a simplified version - in real implementation you'd check actual queue status
            $queueStats = [
                'connection' => config('queue.default'),
                'jobs_processed_today' => 0, // Would come from actual queue monitoring
                'failed_jobs' => 0, // Would come from failed_jobs table
                'pending_jobs' => 0, // Would come from queue inspection
                'workers_active' => 1, // Would come from process monitoring
                'last_processed' => now()->subMinutes(rand(1, 30)),
            ];

            return response()->json([
                'success' => true,
                'data' => $queueStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get queue status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformance(): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            // Simple performance tests
            $dbTime = $this->measureDatabasePerformance();
            $cacheTime = $this->measureCachePerformance();
            
            $endTime = microtime(true);
            $totalTime = ($endTime - $startTime) * 1000;

            $metrics = [
                'response_time_ms' => round($totalTime, 2),
                'database_time_ms' => round($dbTime * 1000, 2),
                'cache_time_ms' => round($cacheTime * 1000, 2),
                'memory_usage' => [
                    'current' => $this->formatBytes(memory_get_usage(true)),
                    'peak' => $this->formatBytes(memory_get_peak_usage(true)),
                ],
                'disk_usage' => $this->getDiskUsage(),
                'cpu_load' => $this->getCpuLoad(),
                'timestamp' => now()->toISOString(),
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check application status
     */
    private function checkApplication(): array
    {
        try {
            $status = 'ok';
            $message = 'Application is running';
            
            // Check if app key is set
            if (empty(config('app.key'))) {
                $status = 'error';
                $message = 'Application key not set';
            }

            return [
                'status' => $status,
                'message' => $message,
                'details' => [
                    'environment' => config('app.env'),
                    'debug' => config('app.debug'),
                    'version' => config('app.version', '1.0.0'),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Application check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            $endTime = microtime(true);
            
            $responseTime = ($endTime - $startTime) * 1000;

            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
                'details' => [
                    'connection' => config('database.default'),
                    'response_time_ms' => round($responseTime, 2),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'ok',
                    'message' => 'Cache is working',
                    'details' => [
                        'driver' => config('cache.default'),
                    ]
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Cache read/write test failed'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check storage permissions
     */
    private function checkStorage(): array
    {
        try {
            $logsPath = storage_path('logs');
            $writable = is_writable($logsPath);

            return [
                'status' => $writable ? 'ok' : 'error',
                'message' => $writable ? 'Storage is writable' : 'Storage is not writable',
                'details' => [
                    'logs_writable' => $writable,
                    'storage_path' => $logsPath,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check queue status
     */
    private function checkQueue(): array
    {
        try {
            $queueConnection = config('queue.default');
            
            return [
                'status' => 'ok',
                'message' => 'Queue is configured',
                'details' => [
                    'connection' => $queueConnection,
                    'driver' => config("queue.connections.{$queueConnection}.driver"),
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue check failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Measure database performance
     */
    private function measureDatabasePerformance(): float
    {
        $startTime = microtime(true);
        try {
            DB::select('SELECT 1');
        } catch (\Exception $e) {
            // Ignore errors for performance measurement
        }
        return microtime(true) - $startTime;
    }

    /**
     * Measure cache performance
     */
    private function measureCachePerformance(): float
    {
        $startTime = microtime(true);
        try {
            Cache::get('non_existent_key');
        } catch (\Exception $e) {
            // Ignore errors for performance measurement
        }
        return microtime(true) - $startTime;
    }

    /**
     * Get application uptime (simplified)
     */
    private function getUptime(): string
    {
        // This is a simplified version - in production you'd track actual uptime
        return 'Not implemented';
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }

    /**
     * Get disk usage information
     */
    private function getDiskUsage(): array
    {
        try {
            $path = base_path();
            $free = disk_free_space($path);
            $total = disk_total_space($path);
            $used = $total - $free;
            
            return [
                'total' => $this->formatBytes($total),
                'used' => $this->formatBytes($used),
                'free' => $this->formatBytes($free),
                'percentage_used' => round(($used / $total) * 100, 2),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Unable to get disk usage'];
        }
    }

    /**
     * Get CPU load (simplified)
     */
    private function getCpuLoad(): array
    {
        try {
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                return [
                    '1_min' => $load[0],
                    '5_min' => $load[1],
                    '15_min' => $load[2],
                ];
            }
            return ['message' => 'CPU load not available on this system'];
        } catch (\Exception $e) {
            return ['error' => 'Unable to get CPU load'];
        }
    }
}