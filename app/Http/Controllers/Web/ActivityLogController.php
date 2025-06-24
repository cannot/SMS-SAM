<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;

class ActivityLogController extends Controller
{
    /**
     * Display user activity logs
     */
    public function getUserActivities(Request $request, $userId = null)
    {
        try {
            // If no user ID provided, use current authenticated user
            if (!$userId) {
                $userId = auth()->id();
            }

            $user = User::findOrFail($userId);

            // Get activities for the user - CORRECT WAY
            $activities = Activity::query()
                ->where('causer_type', User::class)
                ->where('causer_id', $user->id)
                ->latest() // This is called on the query builder, not ActivityLogger
                ->paginate(20);

            return view('users.activities', compact('user', 'activities'));

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load user activities: ' . $e->getMessage());
        }
    }

    /**
     * Get all system activities
     */
    public function index(Request $request)
    {
        $activities = Activity::query()
            ->with('causer', 'subject')
            ->when($request->get('user_id'), function ($query, $userId) {
                $query->where('causer_type', User::class)
                      ->where('causer_id', $userId);
            })
            ->when($request->get('log_name'), function ($query, $logName) {
                $query->where('log_name', $logName);
            })
            ->when($request->get('description'), function ($query, $description) {
                $query->where('description', 'like', '%' . $description . '%');
            })
            ->latest()
            ->paginate(20);

        $users = User::all();
        $logNames = Activity::distinct()->pluck('log_name');

        return view('activity-logs.index', compact('activities', 'users', 'logNames'));
    }

    /**
     * Show activity detail
     */
    public function show($id)
    {
        $activity = Activity::with('causer', 'subject')->findOrFail($id);
        
        return view('activity-logs.show', compact('activity'));
    }
}