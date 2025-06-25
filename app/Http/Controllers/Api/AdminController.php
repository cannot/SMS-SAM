<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\ApiKey;
use App\Models\NotificationGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('is_active', true)->count(),
                    'inactive' => User::where('is_active', false)->count(),
                    'new_this_week' => User::where('created_at', '>=', now()->startOfWeek())->count(),
                ],
                'notifications' => [
                    'total' => Notification::count(),
                    'sent' => Notification::where('status', 'sent')->count(),
                    'pending' => Notification::whereIn('status', ['queued', 'processing'])->count(),
                    'failed' => Notification::where('status', 'failed')->count(),
                    'today' => Notification::whereDate('created_at', today())->count(),
                    'this_week' => Notification::where('created_at', '>=', now()->startOfWeek())->count(),
                ],
                'groups' => [
                    'total' => NotificationGroup::count(),
                    'active' => NotificationGroup::where('is_active', true)->count(),
                ],
                'api_keys' => [
                    'total' => ApiKey::count(),
                    'active' => ApiKey::where('is_active', true)->count(),
                    'expired' => ApiKey::where('expires_at', '<', now())->count(),
                ],
                'system' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'environment' => config('app.env'),
                    'debug_mode' => config('app.debug'),
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load admin stats: ' . $e->getMessage()
            ], 500);
        }
    }

    public function systemInfo(): JsonResponse
    {
        try {
            $info = [
                'server' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'operating_system' => PHP_OS,
                ],
                'database' => [
                    'driver' => config('database.default'),
                    'version' => $this->getDatabaseVersion(),
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'status' => $this->getCacheStatus(),
                ],
                'queue' => [
                    'driver' => config('queue.default'),
                    'status' => 'operational', // Implement actual check
                ],
                'storage' => [
                    'logs_writable' => is_writable(storage_path('logs')),
                    'cache_writable' => is_writable(storage_path('framework/cache')),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get system info: ' . $e->getMessage()
            ], 500);
        }
    }

    public function performance(): JsonResponse
    {
        try {
            $metrics = [
                'memory' => [
                    'current' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                    'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
                ],
                'response_times' => [
                    'average' => '150ms', // Calculate from logs
                    'p95' => '300ms', // Calculate from logs
                    'p99' => '500ms', // Calculate from logs
                ],
                'database' => [
                    'query_count' => 0, // Get from query log
                    'slow_queries' => 0, // Get from monitoring
                ],
                'cache' => [
                    'hit_rate' => '95%', // Calculate actual
                    'miss_rate' => '5%', // Calculate actual
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get performance metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'All caches cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    public function restartQueue(): JsonResponse
    {
        try {
            // This would typically restart queue workers
            // Implementation depends on your deployment setup
            Artisan::call('queue:restart');

            return response()->json([
                'success' => true,
                'message' => 'Queue restart signal sent'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to restart queue: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getDatabaseVersion(): string
    {
        try {
            $result = \DB::select('SELECT version() as version');
            return $result[0]->version ?? 'Unknown';
        } catch (\Exception $e) {
            return 'Unable to determine';
        }
    }

    private function getCacheStatus(): string
    {
        try {
            Cache::put('test_key', 'test_value', 1);
            $value = Cache::get('test_key');
            Cache::forget('test_key');
            
            return $value === 'test_value' ? 'operational' : 'error';
        } catch (\Exception $e) {
            return 'error';
        }
    }
}