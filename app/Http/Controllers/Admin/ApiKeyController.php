<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ApiKeyEvent;
use App\Models\ApiUsageLogs; // เปลี่ยนเป็น ApiUsageLog
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class ApiKeyController extends Controller
{
    /**
     * Display a listing of API keys
     */
    public function index(Request $request)
    {
        $query = ApiKey::with(['createdBy', 'assignedTo'])
            ->withCount(['apiPermissions', 'usageLogs', 'notifications']);

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'expired') {
                $query->expired();
            }
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $apiKeys = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $users = User::select('id', 'display_name', 'username')
            ->orderBy('display_name')
            ->get();

        // Statistics
        $stats = [
            'total'                => ApiKey::count(),
            'active'               => ApiKey::where('is_active', true)->count(),
            'expired'              => ApiKey::where('expires_at', '<', now())->count(),
            'total_requests_today' => ApiUsageLogs::whereDate('created_at', today())->count(),
        ];

        // Expiring soon
        $expiringSoon = ApiKey::where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->where('is_active', true)
            ->get();

        // Recent activity
        $recentActivity = ApiUsageLogs::with('apiKey')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.api-keys.index', compact(
            'apiKeys',
            'users',
            'stats',
            'expiringSoon',
            'recentActivity'
        ));
    }

    /**
     * Show the form for creating a new API key
     */
    public function create()
    {
        $users = User::select('id', 'display_name', 'username', 'email')
            ->orderBy('display_name')
            ->get();

        $apiPermissions = Permission::where('guard_name', 'api')
            ->orderBy('category')
            ->orderBy('display_name')
            ->get()
            ->groupBy('category');

        // Default rate limits
        $defaultRateLimits = [
            60   => '60 requests/minute (Development)',
            120  => '120 requests/minute (Testing)',
            300  => '300 requests/minute (Production Light)',
            600  => '600 requests/minute (Production Heavy)',
            1200 => '1200 requests/minute (Enterprise)',
        ];

        // Available permissions with descriptions
        $availablePermissions = [
            'notifications.send'     => 'Send single notification',
            'notifications.bulk'     => 'Send multiple notifications',
            'notifications.schedule' => 'Schedule notifications',
            'notifications.status'   => 'Check notification status',
            'users.read'             => 'Read user information',
            'groups.read'            => 'Read group information',
            'groups.manage'          => 'Manage group members',
            'templates.read'         => 'Access templates',
            'templates.render'       => 'Render templates',
        ];

        return view('admin.api-keys.create', compact(
            'users',
            'apiPermissions',
            'defaultRateLimits',
            'availablePermissions'
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
            'assigned_to' => 'nullable|exists:users,id',
            'expires_at' => 'nullable|date|after:now',
            'rate_limit_per_minute' => 'required|integer|min:1|max:1000',
            'rate_limit_per_hour' => 'required|integer|min:1|max:100000',
            'rate_limit_per_day' => 'required|integer|min:1|max:1000000',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string', // เปลี่ยนจาก exists:permissions,id เป็น string
            'auto_notifications' => 'boolean',
            'notification_webhook' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validate permissions exist
        $validPermissions = Permission::where('guard_name', 'api')
                                    ->whereIn('name', $request->permissions)
                                    ->pluck('name')
                                    ->toArray();

        if (count($validPermissions) !== count($request->permissions)) {
            return redirect()->back()
                ->with('error', 'One or more selected permissions are invalid.')
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Generate API key
            $keyValue = ApiKey::generateKeyValue();

            // Create API key
            $apiKey = ApiKey::create([
                'name' => $request->name,
                'description' => $request->description,
                'key_value' => $keyValue,
                'assigned_to' => $request->assigned_to,
                'expires_at' => $request->expires_at,
                'rate_limit_per_minute' => $request->rate_limit_per_minute,
                'rate_limit_per_hour' => $request->rate_limit_per_hour,
                'rate_limit_per_day' => $request->rate_limit_per_day,
                'allowed_ips' => $request->allowed_ips,
                'auto_notifications' => $request->boolean('auto_notifications'),
                'notification_webhook' => $request->notification_webhook,
                'created_by' => auth()->id(),
            ]);

            // Assign permissions by name
            foreach ($request->permissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)
                                    ->where('guard_name', 'api')
                                    ->first();
                if ($permission) {
                    $apiKey->givePermissionTo($permission);
                }
            }

            DB::commit();

            return redirect()->route('admin.api-keys.show', $apiKey)
                ->with('success', 'API Key created successfully!')
                ->with('new_api_key', true);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create API Key', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->except(['permissions'])
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create API Key. Please try again.')
                ->withInput();
        }
    }

    /**
     * Update the specified API key
     */
    public function update(Request $request, ApiKey $apiKey)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:api_keys,name,' . $apiKey->id,
            'description' => 'nullable|string|max:1000',
            'assigned_to' => 'nullable|exists:users,id',
            'expires_at' => 'nullable|date|after:now',
            'rate_limit_per_minute' => 'required|integer|min:1|max:1000',
            'rate_limit_per_hour' => 'required|integer|min:1|max:100000',
            'rate_limit_per_day' => 'required|integer|min:1|max:1000000',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string', // เปลี่ยนจาก exists:permissions,id เป็น string
            'auto_notifications' => 'boolean',
            'notification_webhook' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Validate permissions exist
        $validPermissions = Permission::where('guard_name', 'api')
                                    ->whereIn('name', $request->permissions)
                                    ->pluck('name')
                                    ->toArray();

        if (count($validPermissions) !== count($request->permissions)) {
            return redirect()->back()
                ->with('error', 'One or more selected permissions are invalid.')
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Update API key
            $apiKey->update([
                'name' => $request->name,
                'description' => $request->description,
                'assigned_to' => $request->assigned_to,
                'expires_at' => $request->expires_at,
                'rate_limit_per_minute' => $request->rate_limit_per_minute,
                'rate_limit_per_hour' => $request->rate_limit_per_hour,
                'rate_limit_per_day' => $request->rate_limit_per_day,
                'allowed_ips' => $request->allowed_ips,
                'auto_notifications' => $request->boolean('auto_notifications'),
                'notification_webhook' => $request->notification_webhook,
            ]);

            // Update permissions - clear existing and add new ones
            $apiKey->apiPermissions()->detach();
            foreach ($request->permissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)
                                    ->where('guard_name', 'api')
                                    ->first();
                if ($permission) {
                    $apiKey->givePermissionTo($permission);
                }
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
     * Display the specified API key
     */
    public function show(ApiKey $apiKey)
    {
        // Load ทั้ง apiPermissions และ permissions
        $apiKey->load(['createdBy', 'assignedTo', 'permissions']);

        // ลบ dd() ออก
        // dd($apiKey->permissions);

        // Get usage statistics
        $usageStats = [
            'total_requests' => $apiKey->usageLogs()->count(),
            'today'          => $apiKey->usageLogs()->whereDate('created_at', today())->count(),
            'this_week'      => $apiKey->usageLogs()->whereBetween('created_at', [
                now()->startOfWeek(), now()->endOfWeek(),
            ])->count(),
            'this_month'     => $apiKey->usageLogs()->whereMonth('created_at', now()->month)->count(),
        ];

        // Get performance statistics
        $performanceStats = [
            'avg_response_time'      => number_format($apiKey->usageLogs()->avg('response_time') ?? 0, 2) . 'ms',
            'success_rate'           => number_format($apiKey->getSuccessRate(), 1) . '%',
            'rate_limit_utilization' => $this->calculateRateLimitUtilization($apiKey),
        ];

        // Get recent events
        $recentEvents = $apiKey->events()->latest()->limit(10)->get();

        // Get top endpoints
        $topEndpoints = $apiKey->usageLogs()
            ->selectRaw('endpoint, method, COUNT(*) as request_count, AVG(response_time) as avg_response_time')
            ->groupBy('endpoint', 'method')
            ->orderByDesc('request_count')
            ->limit(5)
            ->get();

        return view('admin.api-keys.show', compact(
            'apiKey',
            'usageStats',
            'performanceStats',
            'recentEvents',
            'topEndpoints'
        ));
    }

/**
 * Show the form for editing the API key
 */
    public function edit(ApiKey $apiKey)
    {
        $apiKey->load(['assignedTo', 'apiPermissions']);

        $users = User::select('id', 'display_name', 'username')
            ->orderBy('display_name')
            ->get();

        $apiPermissions = Permission::where('guard_name', 'api')
            ->orderBy('category')
            ->orderBy('display_name')
            ->get()
            ->groupBy('category');

        $assignedPermissions = $apiKey->apiPermissions->pluck('id')->toArray();

        // Default rate limits
        $defaultRateLimits = [
            60   => '60 requests/minute (Development)',
            120  => '120 requests/minute (Testing)',
            300  => '300 requests/minute (Production Light)',
            600  => '600 requests/minute (Production Heavy)',
            1200 => '1200 requests/minute (Enterprise)',
        ];

        // Available permissions
        $availablePermissions = [
            'notifications.send'     => 'Send single notification',
            'notifications.bulk'     => 'Send multiple notifications',
            'notifications.schedule' => 'Schedule notifications',
            'notifications.status'   => 'Check notification status',
            'users.read'             => 'Read user information',
            'groups.read'            => 'Read group information',
            'groups.manage'          => 'Manage group members',
            'templates.read'         => 'Access templates',
            'templates.render'       => 'Render templates',
        ];

        return view('admin.api-keys.edit', compact(
            'apiKey',
            'users',
            'apiPermissions',
            'assignedPermissions',
            'defaultRateLimits',
            'availablePermissions'
        ));
    }

/**
 * Remove the specified API key
 */
    public function destroy(ApiKey $apiKey)
    {
        try {
            DB::beginTransaction();

            $apiKeyName = $apiKey->name;
            $apiKey->delete();

            DB::commit();

            return redirect()->route('admin.api-keys.index')
                ->with('success', "API Key '{$apiKeyName}' deleted successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete API Key', [
                'api_key_id' => $apiKey->id,
                'error'      => $e->getMessage(),
                'user_id'    => auth()->id(),
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
        try {
            DB::beginTransaction();

            $newKeyValue = $apiKey->regenerate(auth()->user());

            DB::commit();

            return redirect()->route('admin.api-keys.show', $apiKey)
                ->with('success', 'API Key regenerated successfully!')
                ->with('new_api_key', true);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to regenerate API Key', [
                'api_key_id' => $apiKey->id,
                'error'      => $e->getMessage(),
                'user_id'    => auth()->id(),
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
            $apiKey->toggleStatus(auth()->user());

            $status = $apiKey->is_active ? 'activated' : 'deactivated';

            return redirect()->back()
                ->with('success', "API Key {$status} successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to toggle API Key status', [
                'api_key_id' => $apiKey->id,
                'error'      => $e->getMessage(),
                'user_id'    => auth()->id(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update API Key status. Please try again.');
        }
    }

/**
 * Show usage history
 */
    public function usageHistory(Request $request, ApiKey $apiKey)
    {
        $query = $apiKey->usageLogs();

        // Apply filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('endpoint')) {
            $query->where('endpoint', 'like', '%' . $request->endpoint . '%');
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('status_code')) {
            $query->where('response_code', $request->status_code);
        }

        $logs = $query->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.api-keys.usage-history', compact('apiKey', 'logs'));
    }

/**
 * Clear API key value after showing
 */
    public function clearKeyValue(ApiKey $apiKey)
    {
        $apiKey->clearKeyValue();

        return response()->json(['success' => true]);
    }

/**
 * Reset usage statistics
 */
    public function resetUsage(ApiKey $apiKey)
    {
        try {
            $apiKey->update([
                'usage_count'    => 0,
                'usage_reset_at' => now(),
                'usage_reset_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', 'Usage statistics reset successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to reset usage', [
                'api_key_id' => $apiKey->id,
                'error'      => $e->getMessage(),
                'user_id'    => auth()->id(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to reset usage statistics.');
        }
    }

/**
 * Export API keys
 */
    public function export($format)
    {
        // Implementation for export functionality
        // You can implement CSV, Excel, PDF export here

        return redirect()->back()
            ->with('info', 'Export functionality will be implemented.');
    }

/**
 * Export usage data
 */
    public function exportUsage(ApiKey $apiKey, $format)
    {
        // Implementation for usage export functionality

        return redirect()->back()
            ->with('info', 'Usage export functionality will be implemented.');
    }

/**
 * Get security metrics for AJAX
 */
    public function getSecurityMetrics()
    {
        $totalKeys              = ApiKey::count();
        $keysWithIpRestrictions = ApiKey::whereNotNull('allowed_ips')
            ->whereJsonLength('allowed_ips', '>', 0)
            ->count();
        $keysWithExpiry = ApiKey::whereNotNull('expires_at')->count();

        return response()->json([
            'keys_with_ip_restrictions' => $keysWithIpRestrictions,
            'keys_with_expiry'          => $keysWithExpiry,
            'last_scan_time'            => now()->format('M j, Y H:i'),
            'total_keys'                => $totalKeys,
        ]);
    }

    /**
     * Show usage statistics
     */
    public function usage(ApiKey $apiKey)
    {
        $usageStats = [
            'hourly'  => ApiUsageLogs::getUsageStats($apiKey, 'hour'),
            'daily'   => ApiUsageLogs::getUsageStats($apiKey, 'day'),
            'weekly'  => ApiUsageLogs::getUsageStats($apiKey, 'week'),
            'monthly' => ApiUsageLogs::getUsageStats($apiKey, 'month'),
        ];

        $topEndpoints  = ApiUsageLogs::getTopEndpoints($apiKey);
        $errorAnalysis = ApiUsageLogs::getErrorAnalysis($apiKey);
        $recentErrors  = ApiUsageLogs::getRecentErrors($apiKey);
        $slowRequests  = ApiUsageLogs::getSlowRequests($apiKey);

        return view('admin.api-keys.usage', compact(
            'apiKey',
            'usageStats',
            'topEndpoints',
            'errorAnalysis',
            'recentErrors',
            'slowRequests'
        ));
    }

    /**
     * Show audit log
     */
    public function audit(ApiKey $apiKey)
    {
        $events = $apiKey->events()
            ->with('performedBy:id,display_name,username')
            ->orderByDesc('created_at')
            ->paginate(20);

        $eventStats = ApiKeyEvent::getEventStats($apiKey);

        return view('admin.api-keys.audit', compact('apiKey', 'events', 'eventStats'));
    }

    /**
     * Calculate rate limit utilization
     */
    private function calculateRateLimitUtilization(ApiKey $apiKey): string
    {
        $currentHourUsage = $apiKey->getUsageCount('hour');
        $rateLimit        = $apiKey->getRateLimit('hour');

        if ($rateLimit <= 0) {
            return '0.0%';
        }

        $utilization = ($currentHourUsage / $rateLimit) * 100;

        return number_format($utilization, 1) . '%';
    }
}
