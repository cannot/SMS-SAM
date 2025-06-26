<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiUsageLogs;
use App\Models\Notification;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ApiKeysExport;
use App\Services\NotificationService;
use App\Events\ApiKeyCreated;
use App\Events\ApiKeyRegenerated;
use App\Events\ApiKeyRevoked;

class ApiKeyController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        // $this->middleware('auth');
        // $this->middleware('can:manage-api-keys');
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of API keys
     */
    public function index(Request $request)
    {
        $query = ApiKey::with(['createdBy', 'notifications'])
            ->withCount(['usageLogs', 'notifications']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true)
                      ->where(function($q) {
                          $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                      });
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'expired') {
                $query->where('expires_at', '<=', now());
            }
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $apiKeys = $query->paginate(15)->withQueryString();

        // Statistics for dashboard
        $stats = [
            'total' => ApiKey::count(),
            'active' => ApiKey::active()->count(),
            'expired' => ApiKey::expired()->count(),
            'total_requests_today' => ApiUsageLogs::whereDate('created_at', today())->count(),
            'total_requests_month' => ApiUsageLogs::whereMonth('created_at', now()->month)->count(),
        ];

        // Recent activity
        $recentActivity = ApiUsageLogs::with('apiKey')
            ->latest()
            ->limit(10)
            ->get();

        // API Keys expiring soon (within 30 days)
        $expiringSoon = ApiKey::active()
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->orderBy('expires_at')
            ->get();

        return view('admin.api-keys.index', compact(
            'apiKeys', 
            'stats', 
            'recentActivity', 
            'expiringSoon'
        ));
    }

    /**
     * Show the form for creating a new API key
     */
    public function create()
    {
        // Get available permissions/scopes
        $availablePermissions = [
            'notifications.send' => 'Send notifications',
            'notifications.bulk' => 'Send bulk notifications',
            'notifications.schedule' => 'Schedule notifications',
            'notifications.status' => 'Check notification status',
            'notifications.history' => 'Access notification history',
            'users.read' => 'Read user information',
            'users.search' => 'Search users',
            'groups.read' => 'Read groups',
            'groups.manage' => 'Manage groups',
            'templates.read' => 'Read templates',
            'templates.render' => 'Render templates',
            'analytics.read' => 'Access analytics',
            'system.health' => 'Check system health'
        ];

        // Get users for assignment
        $users = User::active()->orderBy('display_name')->get();

        // Default rate limits
        $defaultRateLimits = [
            60 => '60 per minute (Standard)',
            120 => '120 per minute (Medium)',
            300 => '300 per minute (High)',
            600 => '600 per minute (Premium)',
            1200 => '1200 per minute (Enterprise)'
        ];

        return view('admin.api-keys.create', compact(
            'availablePermissions', 
            'users', 
            'defaultRateLimits'
        ));
    }

    /**
     * Store a newly created API key
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:api_keys,name',
            'description' => 'nullable|string|max:1000',
            'rate_limit_per_minute' => 'required|integer|min:1|max:10000',
            'expires_at' => 'nullable|date|after:today',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string',
            'ip_whitelist' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'auto_notifications' => 'boolean',
            'notification_webhook' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Generate secure API key
            $apiKeyValue = 'sns_' . Str::random(32) . '_' . time();
            
            // Parse IP whitelist
            $ipWhitelist = null;
            if ($request->filled('ip_whitelist')) {
                $ipWhitelist = array_filter(
                    array_map('trim', explode(',', $request->ip_whitelist))
                );
            }

            // Create API key
            $apiKey = ApiKey::create([
                'name' => $request->name,
                'description' => $request->description,
                'key_hash' => Hash::make($apiKeyValue),
                'is_active' => true,
                'rate_limit_per_minute' => $request->rate_limit_per_minute,
                'permissions' => $request->permissions,
                'ip_whitelist' => $ipWhitelist,
                'assigned_to' => $request->assigned_to,
                'expires_at' => $request->expires_at,
                'auto_notifications' => $request->boolean('auto_notifications'),
                'notification_webhook' => $request->notification_webhook,
                'created_by' => auth()->id(),
                'usage_count' => 0,
            ]);

            // Log the creation
            Log::info('API Key created', [
                'api_key_id' => $apiKey->id,
                'name' => $apiKey->name,
                'created_by' => auth()->user()->username,
                'permissions' => $request->permissions,
                'rate_limit' => $request->rate_limit_per_minute
            ]);

            // Fire event
            event(new ApiKeyCreated($apiKey, auth()->user()));

            // Send notification to assigned user if specified
            if ($request->assigned_to) {
                $this->notificationService->sendApiKeyAssignmentNotification(
                    User::find($request->assigned_to),
                    $apiKey
                );
            }

            DB::commit();

            // Store API key value in session to show once
            session()->flash('new_api_key', $apiKeyValue);
            session()->flash('api_key_id', $apiKey->id);

            return redirect()->route('admin.api-keys.show', $apiKey)
                ->with('success', 'API Key created successfully! Please save the key as it will not be shown again.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create API Key', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create API Key. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified API key
     */
    public function showx(ApiKey $apiKey)
    {
        $apiKey->load(['createdBy', 'assignedTo', 'notifications']);

        // Usage statistics
        $usageStats = $this->getUsageStatistics($apiKey);

        // Recent usage logs
        $recentLogs = ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->with(['notification'])
            ->latest()
            ->limit(50)
            ->get();

        // Performance metrics
        $performanceMetrics = $this->getPerformanceMetrics($apiKey);

        // Security alerts
        $securityAlerts = $this->getSecurityAlerts($apiKey);

        // Associated notifications
        $associatedNotifications = $apiKey->notifications()
            ->with(['template', 'logs'])
            ->latest()
            ->limit(10)
            ->get();

        // Chart data
        $hourlyData = $this->getHourlyDistribution($apiKey);
        $dailyData = $this->getDailyTrend($apiKey);
        // $monthlyData = $this->getMonthlyData($apiKey);
        $topEndpoints = $this->getTopEndpoints($apiKey);
        // $errorDistribution = $this->getErrorDistribution($apiKey);
        // $geographicData = $this->getGeographicDistribution($apiKey);

        return view('admin.api-keys.show', compact(
            'apiKey',
            'usageStats',
            'recentLogs',
            'performanceMetrics',
            'securityAlerts',
            'associatedNotifications',
            'hourlyData',
            'dailyData',
            // 'monthlyData',
            'topEndpoints',
            // 'errorDistribution',
            // 'geographicData'
        ));
    }

    public function show(ApiKey $apiKey)
    {
    // Load relationships
    $apiKey->load(['createdBy', 'assignedTo', 'notifications']);
    
    // Get usage statistics
    $usageStats = [
        'total_requests' => $apiKey->usage_logs()->count(),
        'today' => $apiKey->usage_logs()->whereDate('created_at', today())->count(),
        'this_week' => $apiKey->usage_logs()->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count(),
        'this_month' => $apiKey->usage_logs()->whereMonth('created_at', now()->month)->count(),
    ];
    
    // Get performance statistics
    $performanceStats = [
        'avg_response_time' => $apiKey->usage_logs()
            ->avg('response_time') ? round($apiKey->usage_logs()->avg('response_time')) . 'ms' : '0ms',
        'success_rate' => $this->calculateSuccessRate($apiKey),
        'rate_limit_utilization' => $this->calculateRateLimitUtilization($apiKey),
    ];
    
    return view('admin.api-keys.show', compact('apiKey', 'usageStats', 'performanceStats'));
    }

    private function calculateSuccessRate(ApiKey $apiKey): string
    {
    $totalRequests = $apiKey->usage_logs()->count();
    if ($totalRequests === 0) return '100.0%';
    
    $successfulRequests = $apiKey->usage_logs()
        ->whereBetween('response_code', [200, 299])
        ->count();
    
    $rate = ($successfulRequests / $totalRequests) * 100;
    return number_format($rate, 1) . '%';
    }

    private function calculateRateLimitUtilization(ApiKey $apiKey): string
    {
    // Calculate current hour usage vs rate limit
    $currentHourUsage = $apiKey->usage_logs()
        ->where('created_at', '>=', now()->startOfHour())
        ->count();
    
    $rateLimit = $apiKey->rate_limit_per_hour ?? 3600; // Default 60/min * 60min
    $utilization = $rateLimit > 0 ? ($currentHourUsage / $rateLimit) * 100 : 0;
    
    return number_format($utilization, 1) . '%';
    }

    /**
     * Show the form for editing the specified API key
     */
    public function edit(ApiKey $apiKey)
    {
        $availablePermissions = [
            'notifications.send' => 'Send notifications',
            'notifications.bulk' => 'Send bulk notifications',
            'notifications.schedule' => 'Schedule notifications',
            'notifications.status' => 'Check notification status',
            'notifications.history' => 'Access notification history',
            'users.read' => 'Read user information',
            'users.search' => 'Search users',
            'groups.read' => 'Read groups',
            'groups.manage' => 'Manage groups',
            'templates.read' => 'Read templates',
            'templates.render' => 'Render templates',
            'analytics.read' => 'Access analytics',
            'system.health' => 'Check system health'
        ];

        $users = User::active()->orderBy('display_name')->get();

        $defaultRateLimits = [
            60 => '60 per minute (Standard)',
            120 => '120 per minute (Medium)',
            300 => '300 per minute (High)',
            600 => '600 per minute (Premium)',
            1200 => '1200 per minute (Enterprise)'
        ];

        return view('admin.api-keys.edit', compact(
            'apiKey',
            'availablePermissions',
            'users',
            'defaultRateLimits'
        ));
    }

    /**
     * Update the specified API key
     */
    public function update(Request $request, ApiKey $apiKey)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:api_keys,name,' . $apiKey->id,
            'description' => 'nullable|string|max:1000',
            'rate_limit_per_minute' => 'required|integer|min:1|max:10000',
            'expires_at' => 'nullable|date|after:today',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string',
            'ip_whitelist' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'auto_notifications' => 'boolean',
            'notification_webhook' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $oldData = $apiKey->toArray();

            // Parse IP whitelist
            $ipWhitelist = null;
            if ($request->filled('ip_whitelist')) {
                $ipWhitelist = array_filter(
                    array_map('trim', explode(',', $request->ip_whitelist))
                );
            }

            // Update API key
            $apiKey->update([
                'name' => $request->name,
                'description' => $request->description,
                'rate_limit_per_minute' => $request->rate_limit_per_minute,
                'permissions' => $request->permissions,
                'ip_whitelist' => $ipWhitelist,
                'assigned_to' => $request->assigned_to,
                'expires_at' => $request->expires_at,
                'auto_notifications' => $request->boolean('auto_notifications'),
                'notification_webhook' => $request->notification_webhook,
            ]);

            // Log the update
            Log::info('API Key updated', [
                'api_key_id' => $apiKey->id,
                'name' => $apiKey->name,
                'updated_by' => auth()->user()->username,
                'changes' => array_diff_assoc($apiKey->toArray(), $oldData)
            ]);

            // Clear cache for this API key
            Cache::forget("api_key_validation_{$apiKey->id}");

            // Send notification if assigned user changed
            if ($oldData['assigned_to'] != $request->assigned_to && $request->assigned_to) {
                $this->notificationService->sendApiKeyAssignmentNotification(
                    User::find($request->assigned_to),
                    $apiKey
                );
            }

            DB::commit();

            return redirect()->route('admin.api-keys.show', $apiKey)
                ->with('success', 'API Key updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update API Key', [
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update API Key. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified API key
     */
    public function destroy(ApiKey $apiKey)
    {
        try {
            DB::beginTransaction();

            // Check if API key has active notifications
            $activeNotifications = $apiKey->notifications()
                ->whereIn('status', ['pending', 'processing', 'scheduled'])
                ->count();

            if ($activeNotifications > 0) {
                return redirect()->back()
                    ->with('error', "Cannot delete API Key. It has {$activeNotifications} active notifications.");
            }

            $apiKeyName = $apiKey->name;
            $assignedUser = $apiKey->assignedTo;

            // Log before deletion
            Log::warning('API Key deleted', [
                'api_key_id' => $apiKey->id,
                'name' => $apiKeyName,
                'deleted_by' => auth()->user()->username,
                'usage_count' => $apiKey->usage_count
            ]);

            // Fire event before deletion
            event(new ApiKeyRevoked($apiKey, auth()->user()));

            // Delete the API key (this will also delete related usage logs due to foreign key)
            $apiKey->delete();

            // Clear any cached data
            Cache::forget("api_key_validation_{$apiKey->id}");

            // Notify assigned user if applicable
            if ($assignedUser) {
                $this->notificationService->sendApiKeyRevokedNotification(
                    $assignedUser,
                    $apiKeyName
                );
            }

            DB::commit();

            return redirect()->route('admin.api-keys.index')
                ->with('success', 'API Key deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete API Key', [
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete API Key. Please try again.');
        }
    }

    /**
     * Regenerate API key
     */
    public function regenerate(ApiKey $apiKey)
    {
        // dd($apiKey);
        try {
            DB::beginTransaction();

            // Generate new API key
            $newApiKeyValue = 'sns_' . Str::random(32) . '_' . time();

            // Update the hash
            $apiKey->update([
                'key_hash' => Hash::make($newApiKeyValue),
                'regenerated_at' => now(),
                'regenerated_by' => auth()->id()
            ]);

            // Log the regeneration
            Log::warning('API Key regenerated', [
                'api_key_id' => $apiKey->id,
                'name' => $apiKey->name,
                'regenerated_by' => auth()->user()->username
            ]);

            // Fire event
            event(new ApiKeyRegenerated($apiKey, auth()->user()));

            // Clear cache
            Cache::forget("api_key_validation_{$apiKey->id}");

            // Notify assigned user
            if ($apiKey->assignedTo) {
                $this->notificationService->sendApiKeyRegeneratedNotification(
                    $apiKey->assignedTo,
                    $apiKey
                );
            }

            DB::commit();
            // Store new API key in session to show once
            session()->flash('new_api_key', $newApiKeyValue);
            session()->flash('api_key_id', $apiKey->id);

            return redirect()->route('admin.api-keys.show', $apiKey)
                ->with('success', 'API Key regenerated successfully! Please save the new key as it will not be shown again.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to regenerate API Key', [
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to regenerate API Key. Please try again.');
        }
    }

    /**
     * Toggle API key status
     */
    public function toggleStatus(ApiKey $apiKey)
    {
        try {
            $oldStatus = $apiKey->is_active;
            $newStatus = !$oldStatus;

            $apiKey->update([
                'is_active' => $newStatus,
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id()
            ]);

            // Log the status change
            Log::info('API Key status changed', [
                'api_key_id' => $apiKey->id,
                'name' => $apiKey->name,
                'old_status' => $oldStatus ? 'active' : 'inactive',
                'new_status' => $newStatus ? 'active' : 'inactive',
                'changed_by' => auth()->user()->username
            ]);

            // Clear cache
            Cache::forget("api_key_validation_{$apiKey->id}");

            // Notify assigned user
            if ($apiKey->assignedTo) {
                $this->notificationService->sendApiKeyStatusChangeNotification(
                    $apiKey->assignedTo,
                    $apiKey,
                    $newStatus
                );
            }

            $statusText = $newStatus ? 'activated' : 'deactivated';
            return redirect()->back()
                ->with('success', "API Key {$statusText} successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to toggle API Key status', [
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update API Key status. Please try again.');
        }
    }

    /**
     * Get usage statistics for API key
     */
    public function usageStats(ApiKey $apiKey)
    {
        $stats = $this->getUsageStatistics($apiKey);
        return response()->json($stats);
    }

    /**
     * Get usage history for API key
     */
    public function usageHistory(ApiKey $apiKey, Request $request)
    {
        $query = ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->with(['notification']);

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Endpoint filter
        if ($request->filled('endpoint')) {
            $query->where('endpoint', 'like', "%{$request->endpoint}%");
        }

        // Status filter
        if ($request->filled('status_code')) {
            $query->where('response_code', $request->status_code);
        }

        $logs = $query->latest()->paginate(50);

        if ($request->expectsJson()) {
            return response()->json($logs);
        }

        return view('admin.api-keys.usage-history', compact('apiKey', 'logs'));
    }

    /**
     * Reset usage statistics
     */
    public function resetUsage(ApiKey $apiKey)
    {
        try {
            DB::beginTransaction();

            $oldUsageCount = $apiKey->usage_count;

            $apiKey->update([
                'usage_count' => 0,
                'usage_reset_at' => now(),
                'usage_reset_by' => auth()->id()
            ]);

            // Log the reset
            Log::info('API Key usage reset', [
                'api_key_id' => $apiKey->id,
                'name' => $apiKey->name,
                'old_usage_count' => $oldUsageCount,
                'reset_by' => auth()->user()->username
            ]);

            // Clear cache
            Cache::forget("api_key_validation_{$apiKey->id}");
            Cache::forget("api_key_usage_stats_{$apiKey->id}");

            DB::commit();

            return redirect()->back()
                ->with('success', 'API Key usage statistics reset successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset API Key usage', [
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to reset usage statistics. Please try again.');
        }
    }

    /**
     * Bulk operations
     */
    public function bulkToggleStatus(Request $request)
    {
        $request->validate([
            'api_key_ids' => 'required|array',
            'api_key_ids.*' => 'exists:api_keys,id',
            'action' => 'required|in:activate,deactivate'
        ]);

        try {
            $apiKeyIds = $request->api_key_ids;
            $isActive = $request->action === 'activate';

            ApiKey::whereIn('id', $apiKeyIds)->update([
                'is_active' => $isActive,
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id()
            ]);

            // Clear cache for all affected keys
            foreach ($apiKeyIds as $keyId) {
                Cache::forget("api_key_validation_{$keyId}");
            }

            $actionText = $isActive ? 'activated' : 'deactivated';
            $count = count($apiKeyIds);

            Log::info("Bulk API Key status change", [
                'api_key_ids' => $apiKeyIds,
                'action' => $request->action,
                'changed_by' => auth()->user()->username
            ]);

            return redirect()->back()
                ->with('success', "{$count} API Keys {$actionText} successfully.");

        } catch (\Exception $e) {
            Log::error('Failed bulk API Key status change', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update API Key statuses. Please try again.');
        }
    }

    public function bulkUpdateLimits(Request $request)
    {
        $request->validate([
            'api_key_ids' => 'required|array',
            'api_key_ids.*' => 'exists:api_keys,id',
            'rate_limit_per_minute' => 'required|integer|min:1|max:10000'
        ]);

        try {
            $apiKeyIds = $request->api_key_ids;
            $rateLimit = $request->rate_limit_per_minute;

            ApiKey::whereIn('id', $apiKeyIds)->update([
                'rate_limit_per_minute' => $rateLimit
            ]);

            // Clear cache
            foreach ($apiKeyIds as $keyId) {
                Cache::forget("api_key_validation_{$keyId}");
            }

            $count = count($apiKeyIds);

            Log::info("Bulk API Key rate limit update", [
                'api_key_ids' => $apiKeyIds,
                'new_rate_limit' => $rateLimit,
                'updated_by' => auth()->user()->username
            ]);

            return redirect()->back()
                ->with('success', "{$count} API Keys rate limits updated successfully.");

        } catch (\Exception $e) {
            Log::error('Failed bulk API Key rate limit update', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update rate limits. Please try again.');
        }
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'api_key_ids' => 'required|array',
            'api_key_ids.*' => 'exists:api_keys,id'
        ]);

        try {
            DB::beginTransaction();

            $apiKeyIds = $request->api_key_ids;
            
            // Check for active notifications
            $activeNotifications = Notification::whereIn('api_key_id', $apiKeyIds)
                ->whereIn('status', ['pending', 'processing', 'scheduled'])
                ->count();

            if ($activeNotifications > 0) {
                return redirect()->back()
                    ->with('error', "Cannot delete API Keys. They have {$activeNotifications} active notifications.");
            }

            // Get API keys for logging
            $apiKeys = ApiKey::whereIn('id', $apiKeyIds)->get();

            // Delete API keys
            ApiKey::whereIn('id', $apiKeyIds)->delete();

            // Clear cache
            foreach ($apiKeyIds as $keyId) {
                Cache::forget("api_key_validation_{$keyId}");
            }

            $count = count($apiKeyIds);

            Log::warning("Bulk API Key deletion", [
                'api_key_ids' => $apiKeyIds,
                'api_key_names' => $apiKeys->pluck('name')->toArray(),
                'deleted_by' => auth()->user()->username
            ]);

            DB::commit();

            return redirect()->back()
                ->with('success', "{$count} API Keys deleted successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed bulk API Key deletion', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete API Keys. Please try again.');
        }
    }

    /**
     * Export API keys
     */
    public function export(Request $request, $format = 'csv')
    {
        try {
            $query = ApiKey::with(['createdBy', 'assignedTo'])
                ->withCount(['usageLogs', 'notifications']);

            // Apply filters
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $apiKeys = $query->get();

            $filename = 'api_keys_' . now()->format('Y-m-d_H-i-s');

            switch ($format) {
                case 'excel':
                    return Excel::download(new ApiKeysExport($apiKeys), "{$filename}.xlsx");
                
                case 'pdf':
                    // Implementation for PDF export
                    $pdf = app('dompdf.wrapper');
                    $pdf->loadView('admin.api-keys.export-pdf', compact('apiKeys'));
                    return $pdf->download("{$filename}.pdf");
                
                default: // csv
                    return Excel::download(new ApiKeysExport($apiKeys), "{$filename}.csv");
            }

        } catch (\Exception $e) {
            Log::error('Failed to export API Keys', [
                'format' => $format,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to export API Keys. Please try again.');
        }
    }

    /**
     * Get audit log
     */
    public function auditLog(Request $request)
    {
        // This would typically use a dedicated audit log table
        // For now, we'll use Laravel's activity log or create a custom implementation
        
        $logs = \Spatie\Activitylog\Models\Activity::where('subject_type', ApiKey::class)
            ->with(['causer', 'subject'])
            ->when($request->filled('api_key_id'), function($query) use ($request) {
                $query->where('subject_id', $request->api_key_id);
            })
            ->when($request->filled('date_from'), function($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->date_to);
            })
            ->latest()
            ->paginate(50);

        return view('admin.api-keys.audit-log', compact('logs'));
    }

    /**
     * Get security report
     */
    public function securityReport(Request $request)
    {
        $report = [
            'expiring_soon' => ApiKey::active()
                ->where('expires_at', '>', now())
                ->where('expires_at', '<=', now()->addDays(30))
                ->count(),
            
            'expired' => ApiKey::expired()->count(),
            
            'high_usage_keys' => ApiKey::active()
                ->where('usage_count', '>', 10000)
                ->get(['id', 'name', 'usage_count', 'rate_limit_per_minute']),
            
            'suspicious_ips' => ApiUsageLogs::select('ip_address')
                ->selectRaw('COUNT(*) as request_count')
                ->selectRaw('COUNT(DISTINCT api_key_id) as unique_keys')
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->groupBy('ip_address')
                ->having('request_count', '>', 1000)
                ->orHaving('unique_keys', '>', 5)
                ->get(),
            
            'failed_requests' => ApiUsageLogs::where('response_code', '>=', 400)
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->with('apiKey')
                ->get()
                ->groupBy('api_key_id')
                ->map(function($logs) {
                    return [
                        'api_key' => $logs->first()->apiKey,
                        'error_count' => $logs->count(),
                        'error_rate' => $logs->count() / max($logs->first()->apiKey->usage_count, 1) * 100
                    ];
                })
                ->filter(function($data) {
                    return $data['error_rate'] > 10; // More than 10% error rate
                }),
            
            'rate_limit_violations' => ApiUsageLogs::whereDate('created_at', '>=', now()->subDays(7))
                ->where('response_code', 429)
                ->with('apiKey')
                ->get()
                ->groupBy('api_key_id')
                ->map(function($logs) {
                    return [
                        'api_key' => $logs->first()->apiKey,
                        'violation_count' => $logs->count()
                    ];
                })
        ];

        return view('admin.api-keys.security-report', compact('report'));
    }

    /**
     * Get daily stats for API key usage
     */
    public function getDailyStats(ApiKey $apiKey = null)
    {
        $query = ApiUsageLogs::selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as request_count')
            ->selectRaw('AVG(response_time) as avg_response_time')
            ->selectRaw('COUNT(CASE WHEN response_code >= 400 THEN 1 END) as error_count')
            ->whereDate('created_at', '>=', now()->subDays(30));

        if ($apiKey) {
            $query->where('api_key_id', $apiKey->id);
        }

        $stats = $query->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($stats);
    }

    /**
     * Get monthly stats for API key usage
     */
    public function getMonthlyStats(ApiKey $apiKey = null)
    {
        $query = ApiUsageLogs::selectRaw('YEAR(created_at) as year')
            ->selectRaw('MONTH(created_at) as month')
            ->selectRaw('COUNT(*) as request_count')
            ->selectRaw('AVG(response_time) as avg_response_time')
            ->selectRaw('COUNT(CASE WHEN response_code >= 400 THEN 1 END) as error_count')
            ->whereDate('created_at', '>=', now()->subMonths(12));

        if ($apiKey) {
            $query->where('api_key_id', $apiKey->id);
        }

        $stats = $query->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        return response()->json($stats);
    }

    /**
     * Get usage history for external API
     */
    public function getUsageHistory(ApiKey $apiKey = null)
    {
        $query = ApiUsageLogs::with('apiKey:id,name');

        if ($apiKey) {
            $query->where('api_key_id', $apiKey->id);
        }

        $history = $query->latest()
            ->paginate(100);

        return response()->json($history);
    }

    // ===================================
    // PRIVATE HELPER METHODS
    // ===================================

    /**
     * Get usage statistics for an API key
     */
    private function getUsageStatistics(ApiKey $apiKey)
    {
        return Cache::remember("api_key_usage_stats_{$apiKey->id}", 300, function() use ($apiKey) {
            $baseQuery = ApiUsageLogs::where('api_key_id', $apiKey->id);

            return [
                'total_requests' => $apiKey->usage_count,
                'requests_today' => $baseQuery->whereDate('created_at', today())->count(),
                'requests_this_week' => $baseQuery->whereBetween('created_at', [
                    now()->startOfWeek(), 
                    now()->endOfWeek()
                ])->count(),
                'requests_this_month' => $baseQuery->whereMonth('created_at', now()->month)->count(),
                'average_response_time' => $baseQuery->avg('response_time') ?: 0,
                'error_rate' => $this->calculateErrorRate($apiKey),
                'top_endpoints' => $this->getTopEndpoints($apiKey),
                'hourly_distribution' => $this->getHourlyDistribution($apiKey),
                'daily_trend' => $this->getDailyTrend($apiKey),
                'geographic_distribution' => $this->getGeographicDistribution($apiKey)
            ];
        });
    }

    /**
     * Calculate error rate for API key
     */
    private function calculateErrorRate(ApiKey $apiKey)
    {
        $totalRequests = $apiKey->usage_count;
        if ($totalRequests === 0) return 0;

        $errorRequests = ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->where('response_code', '>=', 400)
            ->count();

        return round(($errorRequests / $totalRequests) * 100, 2);
    }

    /**
     * Get top endpoints for API key
     */
    private function getTopEndpoints(ApiKey $apiKey)
    {
        return ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->selectRaw('endpoint, method, COUNT(*) as count')
            ->groupBy('endpoint', 'method')
            ->orderByDesc('count')
            ->limit(10)
            ->get();
    }

    /**
     * Get hourly distribution of API usage
     */
    private function getHourlyDistribution(ApiKey $apiKey)
    {
        // return ApiUsageLogs::where('api_key_id', $apiKey->id)
        //     ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
        //     ->whereDate('created_at', '>=', now()->subDays(7))
        //     ->groupBy('hour')
        //     ->orderBy('hour')
        //     ->get()
        //     ->keyBy('hour');
        return ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->selectRaw('EXTRACT(HOUR FROM created_at) as hour, COUNT(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->groupBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
            ->orderBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
            ->get()
            ->keyBy('hour');
    }

    /**
     * Get daily trend for the last 30 days
     */
    private function getDailyTrend(ApiKey $apiKey)
    {
        return ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->selectRaw('created_at::date as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('created_at::date'))
            ->orderBy(DB::raw('created_at::date'))
            ->get();
    }

    /**
     * Get geographic distribution of requests
     */
    private function getGeographicDistribution(ApiKey $apiKey)
    {
        // This would require IP geolocation service
        // For now, we'll return IP-based distribution
        return ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->selectRaw('ip_address, COUNT(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('ip_address')
            ->orderByDesc('count')
            ->limit(20)
            ->get();
    }

    /**
     * Get performance metrics for API key
     */
    private function getPerformanceMetrics(ApiKey $apiKey)
    {
        $baseQuery = ApiUsageLogs::where('api_key_id', $apiKey->id);

        return [
            'avg_response_time' => $baseQuery->avg('response_time') ?: 0,
            'min_response_time' => $baseQuery->min('response_time') ?: 0,
            'max_response_time' => $baseQuery->max('response_time') ?: 0,
            'p95_response_time' => $this->getPercentileResponseTime($apiKey, 95),
            'p99_response_time' => $this->getPercentileResponseTime($apiKey, 99),
            'throughput_per_minute' => $this->getThroughputPerMinute($apiKey),
            'success_rate' => 100 - $this->calculateErrorRate($apiKey),
            'rate_limit_utilization' => $this->getRateLimitUtilization($apiKey)
        ];
    }

    /**
     * Get percentile response time
     */
    private function getPercentileResponseTime(ApiKey $apiKey, $percentile)
    {
        $total = ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->whereNotNull('response_time')
            ->count();

        if ($total === 0) return 0;

        $offset = floor($total * $percentile / 100);

        return ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->whereNotNull('response_time')
            ->orderBy('response_time')
            ->offset($offset)
            ->limit(1)
            ->value('response_time') ?: 0;
    }

    /**
     * Get throughput per minute
     */
    private function getThroughputPerMinute(ApiKey $apiKey)
    {
        $recentRequests = ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return round($recentRequests / 60, 2);
    }

    /**
     * Get rate limit utilization percentage
     */
    private function getRateLimitUtilization(ApiKey $apiKey)
    {
        $currentMinuteRequests = ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->where('created_at', '>=', now()->startOfMinute())
            ->count();

        if ($apiKey->rate_limit_per_minute === 0) return 0;

        return round(($currentMinuteRequests / $apiKey->rate_limit_per_minute) * 100, 2);
    }

    /**
     * Get security alerts for API key
     */
    private function getSecurityAlerts(ApiKey $apiKey)
    {
        $alerts = [];

        // Check if API key is expiring soon
        if ($apiKey->expires_at && $apiKey->expires_at <= now()->addDays(30)) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'API Key expires in ' . now()->diffInDays($apiKey->expires_at) . ' days',
                'action' => 'Extend expiration date'
            ];
        }

        // Check for high error rate
        $errorRate = $this->calculateErrorRate($apiKey);
        if ($errorRate > 10) {
            $alerts[] = [
                'type' => 'error',
                'message' => "High error rate: {$errorRate}%",
                'action' => 'Investigate error patterns'
            ];
        }

        // Check for suspicious activity
        $recentErrors = ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->where('response_code', '>=', 400)
            ->whereDate('created_at', '>=', now()->subDays(1))
            ->count();

        if ($recentErrors > 100) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "{$recentErrors} failed requests in the last 24 hours",
                'action' => 'Review request patterns'
            ];
        }

        // Check rate limit violations
        $rateLimitViolations = ApiUsageLogs::where('api_key_id', $apiKey->id)
            ->where('response_code', 429)
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->count();

        if ($rateLimitViolations > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "{$rateLimitViolations} rate limit violations this week",
                'action' => 'Consider increasing rate limits'
            ];
        }

        return $alerts;
    }
}