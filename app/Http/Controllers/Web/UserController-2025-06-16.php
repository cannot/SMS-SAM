<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Notification;
use App\Models\UserPreference;
use App\Models\NotificationGroup;
use App\Models\ApiUsageLogs;
use App\Models\ApiKey;
use App\Services\LdapService;
use App\Jobs\SyncLdapUsers;
use App\Exports\UsersExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    protected $ldapService;

    public function __construct(LdapService $ldapService)
    {
        $this->ldapService = $ldapService;
    }

    /**
     * Display users listing with advanced filtering and role management
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'notificationGroups', 'preferences']);

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Department filter
        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }

        // Role filter
        if ($request->filled('role')) {
            $query->withRole($request->role);
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'recently_active') {
                $query->where('last_login_at', '>=', now()->subDays(30));
            }
        }

        // Group filter
        if ($request->filled('group')) {
            $query->inGroup($request->group);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'display_name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSorts = ['display_name', 'username', 'email', 'department', 'title', 'last_login_at', 'created_at'];
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
        $inactiveUsers = User::where('is_active', false)->count();
        
        // Last sync time
        $lastSync = Cache::get('ldap_last_sync') ? 
            Carbon::parse(Cache::get('ldap_last_sync')) : null;

        // Get filter options
        $departments = User::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->sort();

        $roles = Role::orderBy('name')->get();

        $notificationGroups = NotificationGroup::orderBy('name')->get();

        return view('users.index', compact(
            'users', 
            'totalUsers', 
            'activeUsers', 
            'newUsersThisMonth',
            'inactiveUsers',
            'lastSync', 
            'departments',
            'roles',
            'notificationGroups'
        ));
    }

    /**
     * Display detailed user profile with Smart Notification specific data
     */
    public function show(User $user)
    {
        // Load user with all related data
        $user->load([
            'preferences', 
            'notificationGroups', 
            'roles',
            'createdNotifications' => function($query) {
                $query->latest()->take(5);
            },
            'createdApiKeys' => function($query) {
                $query->where('is_active', true);
            }
        ]);

        // Get notification statistics
        $notificationStats = $user->getNotificationStats();

        // Get recent received notifications (all sources)
        $recentNotifications = $user->allNotifications()
            ->with(['template'])
            ->take(10)
            ->get();

        // Get created notifications
        $createdNotifications = $user->createdNotifications;

        // Get API usage statistics if user has API access
        $apiStats = null;
        if ($user->hasRole(['admin', 'api-user']) || $user->createdApiKeys()->exists()) {
            $apiStats = [
                'total_requests' => $user->apiUsageLogs()->count(),
                'this_month' => $user->apiUsageLogs()
                    ->whereMonth('created_at', now()->month)
                    ->count(),
                'success_rate' => $this->calculateApiSuccessRate($user),
                'last_used' => $user->apiUsageLogs()->latest()->first(),
                'active_keys' => $user->createdApiKeys()->where('is_active', true)->count()
            ];
        }

        // Get user's role information
        $roleInfo = [
            'current_roles' => $user->roles->pluck('name')->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'can_manage_users' => $user->canManageUsers(),
            'can_create_notifications' => $user->canCreateNotifications(),
            'can_manage_api_keys' => $user->canManageApiKeys(),
            'is_admin' => $user->isAdmin()
        ];

        return view('users.show', compact(
            'user', 
            'recentNotifications', 
            'notificationStats',
            'createdNotifications',
            'apiStats',
            'roleInfo'
        ));
    }

    /**
     * Show user role and permission management
     */
    public function manageRoles(User $user)
    {
        $this->authorize('manageRoles', $user);

        $allRoles = Role::with('permissions')->orderBy('name')->get();
        $userRoles = $user->roles->pluck('id')->toArray();

        return view('users.manage-roles', compact('user', 'allRoles', 'userRoles'));
    }

    /**
     * Update user roles
     */
    public function updateRoles(Request $request, User $user)
    {
        $this->authorize('manageRoles', $user);

        $validated = $request->validate([
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id'
        ]);

        $roleIds = $validated['roles'] ?? [];

        try {
            DB::beginTransaction();

            // Get old roles for logging
            $oldRoles = $user->roles->pluck('name')->toArray();

            // Sync roles
            $user->roles()->sync($roleIds);

            // Get new roles for logging
            $newRoles = Role::whereIn('id', $roleIds)->pluck('name')->toArray();

            // Log the role change
            Log::info('User roles updated', [
                'user_id' => $user->id,
                'old_roles' => $oldRoles,
                'new_roles' => $newRoles,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('users.show', $user)
                ->with('success', 'User roles updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update user roles', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to update user roles. Please try again.');
        }
    }

    /**
     * Show user notification groups management
     */
    public function manageGroups(User $user)
    {
        $this->authorize('manageGroups', $user);

        $allGroups = NotificationGroup::orderBy('name')->get();
        $userGroups = $user->notificationGroups->pluck('id')->toArray();

        return view('users.manage-groups', compact('user', 'allGroups', 'userGroups'));
    }

    /**
     * Update user's notification groups
     */
    public function updateGroups(Request $request, User $user)
    {
        $this->authorize('manageGroups', $user);

        $validated = $request->validate([
            'groups' => 'nullable|array',
            'groups.*' => 'exists:notification_groups,id'
        ]);

        $groupIds = $validated['groups'] ?? [];

        try {
            DB::beginTransaction();

            // Get old groups for logging
            $oldGroups = $user->notificationGroups->pluck('name')->toArray();

            // Sync groups
            $user->notificationGroups()->sync($groupIds);

            // Get new groups for logging
            $newGroups = NotificationGroup::whereIn('id', $groupIds)->pluck('name')->toArray();

            // Log the group change
            Log::info('User notification groups updated', [
                'user_id' => $user->id,
                'old_groups' => $oldGroups,
                'new_groups' => $newGroups,
                'updated_by' => Auth::id()
            ]);

            DB::commit();

            return redirect()->route('users.show', $user)
                ->with('success', 'User notification groups updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update user notification groups', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to update notification groups. Please try again.');
        }
    }

    /**
     * Show user preferences form
     */
    public function preferences(User $user)
    {
        // Get user preferences with defaults
        $preferences = $user->user_preferences;

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
            'enable_sms' => 'boolean',
            'teams_user_id' => 'nullable|string|max:255',
            'teams_channel_preference' => 'in:direct,channel',
            'teams_channel_id' => 'nullable|string|max:255',
            'email_address' => 'nullable|email|max:255',
            'email_format' => 'in:html,plain',
            'sms_number' => 'nullable|string|max:20',
            'min_priority' => 'in:low,normal,high,urgent',
            'enable_quiet_hours' => 'boolean',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
            'quiet_days' => 'nullable|array',
            'quiet_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'override_high_priority' => 'boolean',
            'enable_grouping' => 'boolean',
            'grouping_method' => 'in:sender,type,time',
            'grouping_interval' => 'nullable|integer|min:1|max:60',
            'email_frequency' => 'in:immediate,hourly,daily,weekly',
            'teams_frequency' => 'in:immediate,hourly,daily',
            'language' => 'in:th,en',
            'timezone' => 'string|max:50',
            'date_format' => 'nullable|string|max:20',
            'time_format' => 'nullable|string|max:10',
            'enable_read_receipts' => 'boolean',
            'enable_delivery_reports' => 'boolean',
            'enable_digest' => 'boolean',
            'digest_frequency' => 'in:daily,weekly,monthly',
            'digest_time' => 'nullable|date_format:H:i',
            'digest_day' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'custom_filters' => 'nullable|json'
        ]);

        // Convert boolean values properly
        $booleanFields = [
            'enable_teams', 'enable_email', 'enable_sms', 'enable_quiet_hours',
            'override_high_priority', 'enable_grouping', 'enable_read_receipts',
            'enable_delivery_reports', 'enable_digest'
        ];

        foreach ($booleanFields as $field) {
            $validated[$field] = $request->has($field);
        }

        // Validation: At least one channel must be enabled
        if (!$validated['enable_teams'] && !$validated['enable_email'] && !$validated['enable_sms']) {
            return back()->withErrors(['channels' => 'At least one notification channel must be enabled.']);
        }

        // Handle quiet_days as JSON
        if (isset($validated['quiet_days'])) {
            $validated['quiet_days'] = json_encode($validated['quiet_days']);
        } else {
            $validated['quiet_days'] = json_encode([]);
        }

        // Set defaults for optional fields
        $validated['grouping_interval'] = $validated['grouping_interval'] ?? 5;
        $validated['date_format'] = $validated['date_format'] ?? 'Y-m-d';
        $validated['time_format'] = $validated['time_format'] ?? 'H:i';
        $validated['digest_time'] = $validated['digest_time'] ?? '08:00';
        $validated['digest_day'] = $validated['digest_day'] ?? 'monday';

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
                'changes' => array_keys($validated)
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
     * Duplicate user preferences from another user
     */
    public function duplicatePreferences(Request $request, User $user)
    {
        $this->authorize('updatePreferences', $user);

        $validated = $request->validate([
            'source_user_id' => 'required|exists:users,id'
        ]);

        $sourceUser = User::findOrFail($validated['source_user_id']);
        $sourcePreferences = $sourceUser->preferences;

        if (!$sourcePreferences) {
            return response()->json([
                'success' => false,
                'message' => 'Source user has no preferences to copy.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Copy preferences
            $preferencesData = $sourcePreferences->toArray();
            $preferencesData['user_id'] = $user->id;
            unset($preferencesData['id'], $preferencesData['created_at'], $preferencesData['updated_at']);

            $user->preferences()->updateOrCreate(
                ['user_id' => $user->id],
                $preferencesData
            );

            Log::info('User preferences duplicated', [
                'target_user_id' => $user->id,
                'source_user_id' => $sourceUser->id,
                'duplicated_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Preferences copied from {$sourceUser->display_name} successfully!"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to duplicate user preferences', [
                'target_user_id' => $user->id,
                'source_user_id' => $sourceUser->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to copy preferences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show user activity logs
     */
    public function activityLogs(User $user)
    {
        $this->authorize('viewActivityLogs', $user);

        $activities = $user->activities()
            ->latest()
            ->paginate(20);

        return view('users.activity-logs', compact('user', 'activities'));
    }

    /**
     * Show user API usage
     */
    public function apiUsage(User $user)
    {
        $this->authorize('viewApiUsage', $user);

        $apiKeys = $user->createdApiKeys()->with('usageLogs')->get();
        $totalRequests = $user->apiUsageLogs()->count();
        $successRate = $this->calculateApiSuccessRate($user);
        
        $usageLogs = $user->apiUsageLogs()
            ->latest()
            ->paginate(50);

        return view('users.api-usage', compact(
            'user', 
            'apiKeys', 
            'totalRequests', 
            'successRate', 
            'usageLogs'
        ));
    }

    /**
     * Export users data
     */
    public function export(Request $request)
    {
        $this->authorize('export', User::class);

        $format = $request->get('format', 'csv');
        
        $query = User::with(['roles', 'notificationGroups', 'preferences']);
        
        // Apply same filters as index
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('department')) {
            $query->byDepartment($request->department);
        }
        if ($request->filled('role')) {
            $query->withRole($request->role);
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        if ($request->filled('group')) {
            $query->inGroup($request->group);
        }

        $users = $query->get();

        if ($format === 'csv') {
            return $this->exportCsv($users);
        } elseif ($format === 'excel') {
            return Excel::download(new UsersExport($users), 'users_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx');
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
                'ID', 'Username', 'Display Name', 'Email', 'First Name', 'Last Name',
                'Department', 'Title', 'Phone', 'Roles', 'Groups', 'Notification Channels',
                'Language', 'Timezone', 'Status', 'Last Login', 'Created At', 'LDAP Synced'
            ]);

            // CSV Data
            foreach ($users as $user) {
                $roles = $user->roles->pluck('name')->join('; ');
                $groups = $user->notificationGroups->pluck('name')->join('; ');
                $channels = [];
                
                if ($user->preferences) {
                    if ($user->preferences->enable_email) $channels[] = 'Email';
                    if ($user->preferences->enable_teams) $channels[] = 'Teams';
                    if ($user->preferences->enable_sms) $channels[] = 'SMS';
                }
                
                fputcsv($handle, [
                    $user->id,
                    $user->username,
                    $user->display_name,
                    $user->email,
                    $user->first_name,
                    $user->last_name,
                    $user->department,
                    $user->title,
                    $user->phone,
                    $roles,
                    $groups,
                    implode(', ', $channels),
                    $user->preferences->language ?? 'th',
                    $user->preferences->timezone ?? 'Asia/Bangkok',
                    $user->is_active ? 'Active' : 'Inactive',
                    $user->last_login_at?->format('Y-m-d H:i:s'),
                    $user->created_at->format('Y-m-d H:i:s'),
                    $user->ldap_synced_at?->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Import users from CSV
     */
    public function importCsv(Request $request)
    {
        $this->authorize('create', User::class);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
        ]);

        try {
            DB::beginTransaction();

            $file = $request->file('csv_file');
            $path = $file->getRealPath();
            $data = array_map('str_getcsv', file($path));
            
            // Remove header row
            $header = array_shift($data);
            
            $imported = 0;
            $errors = [];

            foreach ($data as $row) {
                try {
                    // Map CSV columns to user fields
                    $userData = [
                        'username' => $row[0] ?? null,
                        'first_name' => $row[1] ?? null,
                        'last_name' => $row[2] ?? null,
                        'email' => $row[3] ?? null,
                        'department' => $row[4] ?? null,
                        'title' => $row[5] ?? null,
                        'phone' => $row[6] ?? null,
                        'is_active' => true,
                        'ldap_guid' => \Str::uuid(), // Temporary GUID for imported users
                        'display_name' => trim(($row[1] ?? '') . ' ' . ($row[2] ?? ''))
                    ];

                    // Validate required fields
                    if (!$userData['username'] || !$userData['email']) {
                        $errors[] = "Row " . ($imported + 1) . ": Username and email are required";
                        continue;
                    }

                    // Check for duplicates
                    if (User::where('username', $userData['username'])->exists()) {
                        $errors[] = "Row " . ($imported + 1) . ": Username '{$userData['username']}' already exists";
                        continue;
                    }

                    User::create($userData);
                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Row " . ($imported + 1) . ": " . $e->getMessage();
                }
            }

            DB::commit();

            Log::info('Users imported from CSV', [
                'imported_count' => $imported,
                'error_count' => count($errors),
                'imported_by' => Auth::id()
            ]);

            $message = "Successfully imported {$imported} users.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " errors occurred.";
            }

            return redirect()->route('users.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to import users from CSV', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to import users: ' . $e->getMessage());
        }
    }

    /**
     * Sync users from LDAP/AD
     */
    public function syncLdap()
    {
        $this->authorize('syncLdap', User::class);

        try {
            // Check if sync is already running
            if (Cache::get('ldap_sync_status') === 'running') {
                return response()->json([
                    'success' => false,
                    'message' => 'LDAP sync is already running. Please wait for it to complete.'
                ], 409);
            }

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
     * Get LDAP sync status
     */
    public function getLdapSyncStatus()
    {
        $status = Cache::get('ldap_sync_status', 'idle');
        $progress = Cache::get('ldap_sync_progress', 0);
        $stats = Cache::get('ldap_sync_stats');
        $error = Cache::get('ldap_sync_error');

        return response()->json([
            'status' => $status,
            'progress' => $progress,
            'stats' => $stats,
            'error' => $error,
            'last_sync' => Cache::get('ldap_last_sync')
        ]);
    }

    /**
     * Bulk actions on selected users
     */
    public function bulkAction(Request $request)
    {
        $this->authorize('bulkAction', User::class);

        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,reset_preferences,assign_role,remove_role,add_to_group,remove_from_group,delete',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'role_id' => 'nullable|exists:roles,id',
            'group_id' => 'nullable|exists:notification_groups,id'
        ]);

        $userIds = $validated['user_ids'];
        $action = $validated['action'];
        $count = count($userIds);

        // Prevent self-modification in dangerous actions
        if (in_array($action, ['deactivate', 'delete']) && in_array(Auth::id(), $userIds)) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot perform this action on yourself.');
        }

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

                case 'assign_role':
                    if (!$validated['role_id']) {
                        throw new \Exception('Role ID is required for this action');
                    }
                    $role = Role::findOrFail($validated['role_id']);
                    $users = User::whereIn('id', $userIds)->get();
                    foreach ($users as $user) {
                        $user->assignRole($role);
                    }
                    $message = "Assigned role '{$role->name}' to {$count} user(s) successfully";
                    break;

                case 'remove_role':
                    if (!$validated['role_id']) {
                        throw new \Exception('Role ID is required for this action');
                    }
                    $role = Role::findOrFail($validated['role_id']);
                    $users = User::whereIn('id', $userIds)->get();
                    foreach ($users as $user) {
                        $user->removeRole($role);
                    }
                    $message = "Removed role '{$role->name}' from {$count} user(s) successfully";
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

                case 'delete':
                    // Soft delete users by marking them inactive and adding deleted timestamp
                    User::whereIn('id', $userIds)->update([
                        'is_active' => false,
                        'deleted_at' => now()
                    ]);
                    $message = "Deleted {$count} user(s) successfully";
                    break;
            }

            // Log the bulk action
            Log::info('Bulk user action performed', [
                'action' => $action,
                'user_ids' => $userIds,
                'performed_by' => Auth::id(),
                'role_id' => $validated['role_id'] ?? null,
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
            // Check if user can receive notifications
            if (!$user->canReceiveNotifications()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User cannot receive notifications. Please check their preferences and status.'
                ], 400);
            }

            $preferences = $user->user_preferences;
            $channels = [];
            
            if ($preferences->enable_teams) $channels[] = 'teams';
            if ($preferences->enable_email) $channels[] = 'email';
            if ($preferences->enable_sms) $channels[] = 'sms';

            // Create test notification
            $notification = Notification::create([
                'uuid' => \Str::uuid(),
                'subject' => 'Test Notification from Smart Notification System',
                'body_html' => '<div style="font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h2 style="color: #256B36;">🔔 Test Notification</h2>
                    <p>Hello ' . $user->display_name . ',</p>
                    <p>This is a test notification sent to verify your notification settings are working correctly.</p>
                    <div style="background: white; padding: 15px; border-radius: 5px; margin: 15px 0;">
                        <strong>Your Current Settings:</strong><br>
                        • Channels: ' . implode(', ', $channels) . '<br>
                        • Language: ' . strtoupper($preferences->language) . '<br>
                        • Timezone: ' . $preferences->timezone . '
                    </div>
                    <p>If you received this notification, your Smart Notification System is configured properly! ✅</p>
                    <hr style="margin: 20px 0;">
                    <small style="color: #6c757d;">Sent at: ' . now($preferences->timezone)->format('Y-m-d H:i:s T') . '</small>
                </div>',
                'body_text' => 'Test Notification from Smart Notification System

Hello ' . $user->display_name . ',

This is a test notification sent to verify your notification settings are working correctly.

Your Current Settings:
• Channels: ' . implode(', ', $channels) . '
• Language: ' . strtoupper($preferences->language) . '
• Timezone: ' . $preferences->timezone . '

If you received this notification, your Smart Notification System is configured properly!

Sent at: ' . now($preferences->timezone)->format('Y-m-d H:i:s T'),
                'channels' => $channels,
                'recipients' => [$user->id],
                'priority' => 'normal',
                'status' => 'queued',
                'total_recipients' => 1,
                'created_by' => Auth::id()
            ]);

            // Dispatch notification job
            \App\Jobs\SendNotification::dispatch($notification);

            Log::info('Test notification sent', [
                'notification_id' => $notification->id,
                'recipient_id' => $user->id,
                'channels' => $channels,
                'sent_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test notification queued successfully!',
                'notification_id' => $notification->uuid,
                'channels' => $channels
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

        // Prevent self-deactivation
        if (Auth::id() === $user->id && $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.'
            ], 400);
        }

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
     * Reset user password (if needed for local accounts)
     */
    public function resetPassword(User $user)
    {
        $this->authorize('resetPassword', $user);

        try {
            // Generate temporary password
            $tempPassword = \Str::random(12);
            
            $user->update([
                'password' => bcrypt($tempPassword),
                'must_change_password' => true
            ]);

            // Send password reset notification
            $user->notify(new \App\Notifications\PasswordResetNotification($tempPassword));

            Log::info('User password reset', [
                'user_id' => $user->id,
                'reset_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully. User will receive new password via email.'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reset user password', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time notification counts
     */
    public function getNotificationCounts(User $user)
    {
        $stats = $user->getNotificationStats();
        
        return response()->json([
            'total_count' => $stats['total_received'],
            'unread_count' => 0, // Implement based on your read/unread tracking
            'this_month' => $stats['this_month'],
            'created_count' => $stats['created']
        ]);
    }

    /**
     * Load more notifications for user
     */
    public function loadMoreNotifications(Request $request, User $user)
    {
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 10);

        $notifications = $user->allNotifications()
            ->with(['template'])
            ->skip($offset)
            ->take($limit)
            ->get();

        return response()->json([
            'notifications' => $notifications,
            'has_more' => $user->allNotifications()->count() > ($offset + $limit)
        ]);
    }

    /**
     * Get user statistics for dashboard
     */
    public function getUserStats()
    {
        $this->authorize('viewAny', User::class);

        $stats = [
            'total_users' => User::count(),
            'active_users' => User::active()->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'recently_active' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
            'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
            'by_department' => User::selectRaw('department, count(*) as count')
                ->whereNotNull('department')
                ->groupBy('department')
                ->orderBy('count', 'desc')
                ->get(),
            'by_role' => DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_type', User::class)
                ->selectRaw('roles.name, count(*) as count')
                ->groupBy('roles.name')
                ->orderBy('count', 'desc')
                ->get(),
            'sync_info' => [
                'last_sync' => Cache::get('ldap_last_sync'),
                'sync_status' => Cache::get('ldap_sync_status', 'idle'),
                'sync_stats' => Cache::get('ldap_sync_stats')
            ]
        ];

        return response()->json($stats);
    }

    /**
     * Merge users (combine data from duplicate accounts)
     */
    public function mergeUsers(Request $request)
    {
        $this->authorize('mergeUsers', User::class);

        $validated = $request->validate([
            'primary_user_id' => 'required|exists:users,id',
            'secondary_user_id' => 'required|exists:users,id|different:primary_user_id'
        ]);

        $primaryUser = User::findOrFail($validated['primary_user_id']);
        $secondaryUser = User::findOrFail($validated['secondary_user_id']);

        // Prevent merging admin accounts or current user
        if ($secondaryUser->hasRole('admin') || $secondaryUser->id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot merge admin accounts or your own account.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Merge notifications
            $secondaryUser->createdNotifications()->update(['created_by' => $primaryUser->id]);
            
            // Merge API keys
            $secondaryUser->createdApiKeys()->update(['created_by' => $primaryUser->id]);
            
            // Merge groups (avoid duplicates)
            $primaryGroups = $primaryUser->notificationGroups->pluck('id')->toArray();
            $secondaryGroups = $secondaryUser->notificationGroups->pluck('id')->toArray();
            $mergedGroups = array_unique(array_merge($primaryGroups, $secondaryGroups));
            $primaryUser->notificationGroups()->sync($mergedGroups);
            
            // Merge roles (avoid duplicates)
            $primaryRoles = $primaryUser->roles->pluck('id')->toArray();
            $secondaryRoles = $secondaryUser->roles->pluck('id')->toArray();
            $mergedRoles = array_unique(array_merge($primaryRoles, $secondaryRoles));
            $primaryUser->roles()->sync($mergedRoles);

            // Update notification recipients
            $notifications = Notification::whereJsonContains('recipients', $secondaryUser->id)->get();
            foreach ($notifications as $notification) {
                $recipients = $notification->recipients;
                $recipients = array_map(function($id) use ($primaryUser, $secondaryUser) {
                    return $id == $secondaryUser->id ? $primaryUser->id : $id;
                }, $recipients);
                $notification->update(['recipients' => array_unique($recipients)]);
            }

            // Copy preferences if primary user doesn't have any
            if (!$primaryUser->preferences && $secondaryUser->preferences) {
                $preferencesData = $secondaryUser->preferences->toArray();
                $preferencesData['user_id'] = $primaryUser->id;
                unset($preferencesData['id'], $preferencesData['created_at'], $preferencesData['updated_at']);
                UserPreference::create($preferencesData);
            }

            // Deactivate secondary user
            $secondaryUser->update([
                'is_active' => false,
                'merged_into' => $primaryUser->id,
                'merged_at' => now()
            ]);

            Log::info('Users merged successfully', [
                'primary_user_id' => $primaryUser->id,
                'secondary_user_id' => $secondaryUser->id,
                'merged_by' => Auth::id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Users merged successfully! Secondary user has been deactivated.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to merge users', [
                'primary_user_id' => $primaryUser->id,
                'secondary_user_id' => $secondaryUser->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to merge users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force LDAP sync for specific user
     */
    public function forceLdapSync(User $user)
    {
        $this->authorize('syncLdap', User::class);

        try {
            // Get user data from LDAP
            $ldapData = $this->ldapService->getUserByUsername($user->username);
            
            if (!$ldapData) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found in LDAP directory.'
                ], 404);
            }

            // Update user with LDAP data
            $user->syncFromLdap($ldapData);

            Log::info('Individual user LDAP sync completed', [
                'user_id' => $user->id,
                'synced_by' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User synchronized with LDAP successfully!',
                'synced_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync individual user with LDAP', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync with LDAP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users for autocomplete/selection
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::where(function($q) use ($query) {
                $q->where('display_name', 'ILIKE', "%{$query}%")
                  ->orWhere('username', 'ILIKE', "%{$query}%")
                  ->orWhere('email', 'ILIKE', "%{$query}%");
            })
            ->active()
            ->limit($limit)
            ->get(['id', 'username', 'display_name', 'email'])
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->display_name . ' (' . $user->username . ')',
                    'email' => $user->email
                ];
            });

        return response()->json($users);
    }

    /**
     * Helper: Calculate API success rate
     */
    private function calculateApiSuccessRate(User $user): float
    {
        $total = $user->apiUsageLogs()->count();
        if ($total === 0) return 0;

        $successful = $user->apiUsageLogs()
            ->where('status_code', '<', 400)
            ->count();

        return round(($successful / $total) * 100, 1);
    }

    /**
     * Get user preferences with fallback to defaults
     */
    private function getUserPreferencesWithDefaults(User $user): UserPreference
    {
        $preferences = $user->preferences;
        
        if (!$preferences) {
            $preferences = new UserPreference([
                'user_id' => $user->id,
                'enable_teams' => true,
                'enable_email' => true,
                'enable_sms' => false,
                'min_priority' => 'low',
                'language' => 'th',
                'timezone' => 'Asia/Bangkok',
                'email_format' => 'html',
                'teams_channel_preference' => 'direct',
                'enable_grouping' => true,
                'grouping_method' => 'sender',
                'enable_quiet_hours' => false,
                'override_high_priority' => false,
                'enable_read_receipts' => true,
                'enable_delivery_reports' => true,
                'enable_digest' => false,
                'digest_frequency' => 'daily',
                'digest_time' => '08:00',
                'digest_day' => 'monday'
            ]);
        }
        
        return $preferences;
    }

    /**
     * Validate user permissions for specific action
     */
    private function validateUserAction(User $user, string $action): bool
    {
        // Prevent actions on self for certain operations
        $selfRestrictedActions = ['deactivate', 'delete', 'remove_admin_role'];
        
        if (in_array($action, $selfRestrictedActions) && Auth::id() === $user->id) {
            return false;
        }

        // Prevent actions on super admin by non-super admin
        if ($user->hasRole('super-admin') && !Auth::user()->hasRole('super-admin')) {
            return false;
        }

        return true;
    }
}