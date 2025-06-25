<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function quickStats(): JsonResponse
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'total_notifications' => Notification::count(),
                'notifications_today' => Notification::whereDate('created_at', today())->count(),
                'notifications_this_week' => Notification::where('created_at', '>=', now()->startOfWeek())->count(),
                'active_api_keys' => ApiKey::where('is_active', true)->count(),
                'sent_notifications' => Notification::where('status', 'sent')->count(),
                'failed_notifications' => Notification::where('status', 'failed')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load stats: ' . $e->getMessage()
            ], 500);
        }
    }

    public function chartData(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'daily');
            $data = [];

            switch ($type) {
                case 'daily':
                    // Last 7 days
                    for ($i = 6; $i >= 0; $i--) {
                        $date = now()->subDays($i);
                        $data[] = [
                            'date' => $date->format('M d'),
                            'notifications' => Notification::whereDate('created_at', $date)->count()
                        ];
                    }
                    break;

                case 'weekly':
                    // Last 4 weeks
                    for ($i = 3; $i >= 0; $i--) {
                        $startOfWeek = now()->subWeeks($i)->startOfWeek();
                        $endOfWeek = now()->subWeeks($i)->endOfWeek();
                        $data[] = [
                            'week' => $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d'),
                            'notifications' => Notification::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count()
                        ];
                    }
                    break;

                case 'monthly':
                    // Last 6 months
                    for ($i = 5; $i >= 0; $i--) {
                        $month = now()->subMonths($i);
                        $data[] = [
                            'month' => $month->format('M Y'),
                            'notifications' => Notification::whereYear('created_at', $month->year)
                                                         ->whereMonth('created_at', $month->month)
                                                         ->count()
                        ];
                    }
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load chart data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function recentActivities(): JsonResponse
    {
        try {
            $activities = [];

            // Get recent notifications
            $recentNotifications = Notification::with('creator')
                                             ->latest()
                                             ->limit(10)
                                             ->get();

            foreach ($recentNotifications as $notification) {
                $activities[] = [
                    'type' => 'notification',
                    'title' => 'Notification: ' . $notification->subject,
                    'description' => 'Sent to ' . $notification->total_recipients . ' recipients',
                    'status' => $notification->status,
                    'created_at' => $notification->created_at,
                    'created_by' => $notification->creator?->display_name ?? 'System',
                ];
            }

            // Sort by created_at desc
            usort($activities, function($a, $b) {
                return $b['created_at']->timestamp - $a['created_at']->timestamp;
            });

            return response()->json([
                'success' => true,
                'data' => array_slice($activities, 0, 15)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load recent activities: ' . $e->getMessage()
            ], 500);
        }
    }

    public function widget(Request $request, $type): JsonResponse
    {
        try {
            $data = [];

            switch ($type) {
                case 'notifications':
                    $data = [
                        'total' => Notification::count(),
                        'today' => Notification::whereDate('created_at', today())->count(),
                        'pending' => Notification::where('status', 'queued')->count(),
                        'failed' => Notification::where('status', 'failed')->count(),
                    ];
                    break;

                case 'users':
                    $data = [
                        'total' => User::count(),
                        'active' => User::where('is_active', true)->count(),
                        'new_this_week' => User::where('created_at', '>=', now()->startOfWeek())->count(),
                    ];
                    break;

                case 'performance':
                    $data = [
                        'avg_delivery_time' => '2.3 seconds', // Calculate actual
                        'success_rate' => '98.5%', // Calculate actual
                        'queue_size' => 0, // Get from queue
                    ];
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'error' => 'Unknown widget type'
                    ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to load widget data: ' . $e->getMessage()
            ], 500);
        }
    }
}