<?php

namespace App\Services;

use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class ActivityLogService
{
    /**
     * Get activities with filters
     */
    public function getActivities(array $filters = [])
    {
        $query = Activity::query()->with('causer', 'subject');

        if (isset($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id'])
                  ->where('causer_type', User::class);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        if (isset($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Get activity summary
     */
    public function getActivitySummary($userId = null)
    {
        $query = Activity::query();

        if ($userId) {
            $query->where('causer_id', $userId)
                  ->where('causer_type', User::class);
        }

        return [
            'total' => $query->count(),
            'today' => (clone $query)->whereDate('created_at', Carbon::today())->count(),
            'this_week' => (clone $query)->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'this_month' => (clone $query)->whereMonth('created_at', Carbon::now()->month)->count(),
        ];
    }

    /**
     * Clean old activity logs
     */
    public function cleanOldLogs($days = 90)
    {
        return Activity::where('created_at', '<', Carbon::now()->subDays($days))->delete();
    }
}