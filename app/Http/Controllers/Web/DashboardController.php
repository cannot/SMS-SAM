<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\ApiKey;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Get current user
            $user = auth()->user();

            // Dashboard statistics
            $stats = [
                'total_users' => User::count(),
                'total_notifications' => Notification::count(),
                'notifications_today' => Notification::whereDate('created_at', today())->count(),
                'active_api_keys' => ApiKey::where('is_active', true)->count(),
            ];

            // Recent activities - CORRECT way
            $recentActivities = Activity::with('causer', 'subject')
                ->latest()
                ->limit(10)
                ->get();

            // User's recent activities - CORRECT way
            $userActivities = Activity::where('causer_type', User::class)
                ->where('causer_id', $user->id)
                ->latest()
                ->limit(5)
                ->get();

            // Recent notifications
            $recentNotifications = Notification::with('template', 'createdBy')
                ->latest()
                ->limit(10)
                ->get();

            // Notification delivery stats for chart
            $deliveryStats = Notification::selectRaw('DATE(created_at) as date, COUNT(*) as count, status')
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->groupBy('date', 'status')
                ->get();

            return view('dashboard.index', compact(
                'user',
                'stats',
                'recentActivities',
                'userActivities',
                'recentNotifications',
                'deliveryStats'
            ));

        } catch (\Exception $e) {
            // Log error
            \Log::error('Dashboard loading error: ' . $e->getMessage());
            
            // Return view with empty data
            return view('dashboard.index', [
                'user' => auth()->user(),
                'stats' => [
                    'total_users' => 0,
                    'total_notifications' => 0,
                    'notifications_today' => 0,
                    'active_api_keys' => 0,
                ],
                'recentActivities' => collect(),
                'userActivities' => collect(),
                'recentNotifications' => collect(),
                'deliveryStats' => collect(),
                'error' => 'Failed to load dashboard data. Please refresh the page.'
            ]);
        }
    }
}