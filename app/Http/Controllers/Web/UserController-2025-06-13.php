<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Notification;
use App\Models\UserPreference;
use App\Models\NotificationGroup;
use App\Models\ApiUsageLogs;
use App\Services\LdapService;
use App\Jobs\SyncLdapUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class UserController extends Controller
{
    protected $ldapService;

    public function __construct(LdapService $ldapService)
    {
        $this->ldapService = $ldapService;
    }

    /**
     * Display users listing with advanced filtering
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Department filter
        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where(function($q) {
                    $q->where('is_active', false)
                      ->orWhere('last_login_at', '<', now()->subDays(30))
                      ->orWhereNull('last_login_at');
                });
            }
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'username');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSorts = ['username', 'email', 'department', 'title', 'last_login_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        // Statistics
        $totalUsers = User::count();
        $activeUsers = User::active()->count();
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)->count();
        
        // Last sync time
        $lastSync = Cache::get('ldap_last_sync') ? 
            Carbon::parse(Cache::get('ldap_last_sync')) : null;

        // Get departments for filter
        $departments = User::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->sort();

        return view('users.index', compact(
            'users', 
            'totalUsers', 
            'activeUsers', 
            'newUsersThisMonth',
            'lastSync', 
            'departments'
        ));
    }

    /**
     * Display detailed user profile
     */
    public function show(User $user)
    {
        // Load user with relationships
        $user->load(['preferences', 'notificationGroups']);

        // Get user's recent received notifications
        $recentNotifications = $user->receivedNotifications()
            ->with(['template'])
            ->take(10)
            ->get();

        // Get user's notification statistics
        $notificationStats = [
            'total_received' => $user->receivedNotifications()->count(),
            'this_month' => $user->receivedNotifications()
                ->whereMonth('created_at', now()->month)
                ->count(),
            'unread_count' => $this->getUnreadNotificationsCount($user),
            'last_received' => $user->receivedNotifications()->first()
        ];

        // Get user's created notifications (if any)
        $createdNotifications = $user->createdNotifications()
            ->with(['template'])
            ->latest()
            ->take(5)
            ->get();

        // Get API usage statistics if user has API access
        $apiStats = null;
        if ($user->apiUsageLogs()->exists()) {
            $apiStats = [
                'total_requests' => $user->apiUsageLogs()->count(),
                'this_month' => $user->apiUsageLogs()
                    ->whereMonth('created_at', now()->month)
                    ->count(),
                'success_rate' => $this->calculateApiSuccessRate($user),
                'last_used' => $user->apiUsageLogs()->latest()->first()
            ];
        }

        return view('users.show', compact(
            'user', 
            'recentNotifications', 
            'notificationStats',
            'createdNotifications',
            'apiStats'
        ));
    }

    /**
     * Show user preferences form
     */
    public function preferences(User $user)
    {
        // Authorization check
        $this->authorize('viewPreferences', $user);

        // Get user preferences with defaults
        $preferences = $user->preferences ?? new UserPreference([
            'user_id' => $user->id,
            'enable_teams' => true,
            'enable_email' => true,
            'enable_quiet_hours' => false,
            'min_priority' => 'low',
            'language' => 'th',
            'timezone' => 'Asia/Bangkok',
            'email_format' => 'html',
            'teams_channel_preference' => 'direct',
            'enable_grouping' => true,
            'grouping_method' => 'sender'
        ]);

        return view('users.preferences', compact('user', 'preferences'));
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request, User $user)
    {
        $this->authorize('updatePreferences', $user);

        $validated = $request->validate([
            'enable_teams' => 'boolean',
            'enable_email' => 'boolean',
            'teams_user_id' => 'nullable|string|max:255',
            'teams_channel_preference' => 'in:direct,channel',
            'email_address' => 'nullable|email|max:255',
            'email_format' => 'in:html,plain',
            'min_priority' => 'in:low,medium,high,urgent',
            'enable_quiet_hours' => 'boolean',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
            'quiet_days' => 'nullable|array',
            'quiet_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'override_high_priority' => 'boolean',
            'enable_grouping' => 'boolean',
            'grouping_method' => 'in:sender,type,time',
            'language' => 'in:th,en',
            'timezone' => 'string|max:50'
        ]);

        // Convert boolean values properly
        $validated['enable_teams'] = $request->has('enable_teams');
        $validated['enable_email'] = $request->has('enable_email');
        $validated['enable_quiet_hours'] = $request->has('enable_quiet_hours');
        $validated['override_high_priority'] = $request->has('override_high_priority');
        $validated['enable_grouping'] = $request->has('enable_grouping');

        // Validation: At least one channel must be enabled
        if (!$validated['enable_teams'] && !$validated['enable_email']) {
            return back()->withErrors(['channels' => 'At least one notification channel must be enabled.']);
        }

        // Handle quiet_days as JSON
        if (isset($validated['quiet_days'])) {
            $validated['quiet_days'] = json_encode($validated['quiet_days']);
        } else {
            $validated['quiet_days'] = json_encode([]);
        }

        try {
            DB::beginTransaction();

            // Update or create preferences
            $user->preferences()->updateOrCreate(
                ['user_id' => $user->id],
                $validated
            );

            // Log the preference change
            Log::info('User preferences updated', [
                'user_id' => $user->id,
                'updated_by' => Auth::id(),
                'changes' => $validated
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Preferences updated successfully!'
                ]);
            }

            return redirect()->route('users.preferences', $user)
                ->with('success', 'Preferences updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update user preferences', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update preferences: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to update preferences. Please try again.');
        }
    }

    /**
     * Reset user preferences to defaults
     */
    public function resetPreferences(User $user)
    {
        $this->authorize('updatePreferences', $user);

        try {
            // Delete existing preferences (will use defaults)
            $user->preferences()->delete();

            Log::info('User preferences reset to defaults', [
                'user_id' => $user->id,
                'reset_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Preferences reset to defaults successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reset user preferences', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset preferences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export users data
     */
    public function export(Request $request)
    {
        $this->authorize('export', User::class);

        $format = $request->get('format', 'csv');
        
        $query = User::query();
        
        // Apply same filters as index
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where(function($q) {
                    $q->where('is_active', false)
                      ->orWhere('last_login_at', '<', now()->subDays(30))
                      ->orWhereNull('last_login_at');
                });
            }
        }

        $users = $query->with(['preferences', 'notificationGroups'])->get();

        if ($format === 'csv') {
            return $this->exportCsv($users);
        } elseif ($format === 'excel') {
            return $this->exportExcel($users);
        }

        return back()->with('error', 'Export format not supported');
    }

    /**
     * Export users as CSV
     */
    private function exportCsv($users)
    {
        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function() use ($users) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($handle, [
                'ID', 'Username', 'Name', 'Display Name', 'Email',
                'Department', 'Title', 'Phone', 'Groups', 'Notification Channels',
                'Language', 'Last Login', 'Status', 'Created At'
            ]);

            // CSV Data
            foreach ($users as $user) {
                $groups = $user->notificationGroups->pluck('display_name')->join('; ');
                $channels = [];
                if ($user->preferences) {
                    if ($user->preferences->enable_email) $channels[] = 'Email';
                    if ($user->preferences->enable_teams) $channels[] = 'Teams';
                }
                
                fputcsv($handle, [
                    $user->id,
                    $user->username,
                    $user->display_name,
                    $user->display_name,
                    $user->email,
                    $user->department,
                    $user->title,
                    $user->phone,
                    $groups,
                    implode(', ', $channels),
                    $user->preferences->language ?? 'th',
                    $user->last_login_at?->format('Y-m-d H:i:s'),
                    $user->is_active_user ? 'Active' : 'Inactive',
                    $user->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Sync users from LDAP
     */
    public function syncLdap()
    {
        $this->authorize('syncLdap', User::class);

        try {
            // Dispatch LDAP sync job
            SyncLdapUsers::dispatch();
            
            // Update cache
            Cache::put('ldap_last_sync', now(), 3600);
            Cache::put('ldap_sync_status', 'running', 3600);

            Log::info('LDAP sync initiated', [
                'initiated_by' => Auth::id(),
                'timestamp' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'LDAP sync started successfully. Users will be updated in the background.',
                'synced_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start LDAP sync', [
                'error' => $e->getMessage(),
                'initiated_by' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start LDAP sync: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk actions on selected users
     */
    public function bulkAction(Request $request)
    {
        $this->authorize('bulkAction', User::class);

        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,reset_preferences,add_to_group,remove_from_group',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'group_id' => 'nullable|exists:notification_groups,id'
        ]);

        $userIds = $validated['user_ids'];
        $action = $validated['action'];
        $count = count($userIds);

        try {
            DB::beginTransaction();

            switch ($action) {
                case 'activate':
                    User::whereIn('id', $userIds)->update(['is_active' => true]);
                    $message = "Activated {$count} user(s) successfully";
                    break;

                case 'deactivate':
                    User::whereIn('id', $userIds)->update(['is_active' => false]);
                    $message = "Deactivated {$count} user(s) successfully";
                    break;

                case 'reset_preferences':
                    UserPreference::whereIn('user_id', $userIds)->delete();
                    $message = "Reset preferences for {$count} user(s) successfully";
                    break;

                case 'add_to_group':
                    if (!$validated['group_id']) {
                        throw new \Exception('Group ID is required for this action');
                    }
                    $group = NotificationGroup::findOrFail($validated['group_id']);
                    $group->users()->syncWithoutDetaching($userIds);
                    $message = "Added {$count} user(s) to group '{$group->name}' successfully";
                    break;

                case 'remove_from_group':
                    if (!$validated['group_id']) {
                        throw new \Exception('Group ID is required for this action');
                    }
                    $group = NotificationGroup::findOrFail($validated['group_id']);
                    $group->users()->detach($userIds);
                    $message = "Removed {$count} user(s) from group '{$group->name}' successfully";
                    break;
            }

            // Log the bulk action
            Log::info('Bulk user action performed', [
                'action' => $action,
                'user_ids' => $userIds,
                'performed_by' => Auth::id(),
                'group_id' => $validated['group_id'] ?? null
            ]);

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk user action failed', [
                'action' => $action,
                'user_ids' => $userIds,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('users.index')
                ->with('error', 'Bulk action failed: ' . $e->getMessage());
        }
    }

    /**
     * Send test notification to user
     */
    public function sendTestNotification(User $user)
    {
        $this->authorize('sendTestNotification', $user);

        try {
            // Create test notification
            $notification = Notification::create([
                'uuid' => \Str::uuid(),
                'subject' => 'Test Notification',
                'body_html' => '<p>This is a test notification sent from the Smart Notification System.</p>',
                'body_text' => 'This is a test notification sent from the Smart Notification System.',
                'channels' => ['email', 'teams'],
                'recipients' => [$user->id],
                'priority' => 'normal',
                'status' => 'queued',
                'created_by' => Auth::id()
            ]);

            // Dispatch notification job
            \App\Jobs\SendNotification::dispatch($notification);

            Log::info('Test notification sent', [
                'notification_id' => $notification->id,
                'recipient_id' => $user->id,
                'sent_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test notification queued successfully!',
                'notification_id' => $notification->uuid
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'recipient_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user)
    {
        $this->authorize('toggleStatus', $user);

        try {
            $newStatus = !$user->is_active;
            $user->update(['is_active' => $newStatus]);

            Log::info('User status toggled', [
                'user_id' => $user->id,
                'new_status' => $newStatus ? 'active' : 'inactive',
                'changed_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully!',
                'new_status' => $newStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle user status', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time notification counts
     */
    public function getNotificationCounts(User $user)
    {
        return response()->json([
            'total_count' => $user->receivedNotifications()->count(),
            'unread_count' => $this->getUnreadNotificationsCount($user),
            'this_month' => $user->receivedNotifications()
                ->whereMonth('created_at', now()->month)
                ->count()
        ]);
    }

    /**
     * Load more notifications for user
     */
    public function loadMoreNotifications(Request $request, User $user)
    {
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);

        $notifications = $user->receivedNotifications()
            ->with(['template'])
            ->skip($offset)
            ->take($limit)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'has_more' => $user->receivedNotifications()->count() > ($offset + $limit)
        ]);
    }

    /**
     * Helper: Get unread notifications count
     */
    private function getUnreadNotificationsCount(User $user)
    {
        // Implementation depends on how you track read/unread status
        // This could be in notification_logs table or a separate table
        return 0; // Placeholder
    }

    /**
     * Helper: Calculate API success rate
     */
    private function calculateApiSuccessRate(User $user)
    {
        $total = $user->apiUsageLogs()->count();
        if ($total === 0) return 0;

        $successful = $user->apiUsageLogs()
            ->where('status_code', '<', 400)
            ->count();

        return round(($successful / $total) * 100, 1);
    }
}