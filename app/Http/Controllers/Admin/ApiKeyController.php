<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use App\Models\ApiUsageLog;
use App\Models\ApiKeyEvent;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

        return view('admin.api-keys.index', compact('apiKeys', 'users'));
    }

    /**
     * Show the form for creating a new API key
     */
    public function create()
    {
        $users = User::select('id', 'display_name', 'username')
                    ->orderBy('display_name')
                    ->get();

        $apiPermissions = Permission::where('guard_name', 'api')
                                   ->orderBy('category')
                                   ->orderBy('display_name')
                                   ->get()
                                   ->groupBy('category');

        return view('admin.api-keys.create', compact('users', 'apiPermissions'));
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
            'permissions.*' => 'exists:permissions,id',
            'auto_notifications' => 'boolean',
            'notification_webhook' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
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
                'key_value' => $keyValue, // จะถูกล้างหลังจากแสดงผล
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

            // Assign permissions
            $permissions = Permission::whereIn('id', $request->permissions)
                                   ->where('guard_name', 'api')
                                   ->get();

            foreach ($permissions as $permission) {
                $apiKey->givePermissionTo($permission);
            }

            // Log creation event
            $apiKey->logEvent(
                ApiKeyEvent::EVENT_CREATED,
                "API Key '{$apiKey->name}' created",
                null,
                [
                    'permissions' => $permissions->pluck('name')->toArray(),
                    'rate_limits' => [
                        'per_minute' => $apiKey->rate_limit_per_minute,
                        'per_hour' => $apiKey->rate_limit_per_hour,
                        'per_day' => $apiKey->rate_limit_per_day,
                    ]
                ]
            );

            DB::commit();

            return redirect()->route('admin.api-keys.show', $apiKey)
                ->with('success', 'API Key created successfully!')
                ->with('new_api_key', true); // Flag สำหรับแสดง key เต็ม

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
     * Display the specified API key
     */
    public function show(ApiKey $apiKey)
    {
        $apiKey->load(['createdBy', 'assignedTo', 'apiPermissions']);
        
        // Get usage statistics
        $usageStats = [
            'total_requests' => $apiKey->usageLogs()->count(),
            'today' => $apiKey->usageLogs()->today()->count(),
            'this_week' => $apiKey->usageLogs()->thisWeek()->count(),
            'this_month' => $apiKey->usageLogs()->thisMonth()->count(),
        ];
        
        // Get performance statistics
        $performanceStats = [
            'avg_response_time' => ApiUsageLog::getUsageStats($apiKey, 'month')['avg_response_time'] . 'ms',
            'success_rate' => ApiUsageLog::getUsageStats($apiKey, 'month')['success_rate'] . '%',
            'rate_limit_utilization' => $this->calculateRateLimitUtilization($apiKey),
        ];

        // Get recent events
        $recentEvents = ApiKeyEvent::getRecentActivity($apiKey, 10);

        // Get top endpoints
        $topEndpoints = ApiUsageLog::getTopEndpoints($apiKey, 5);

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

        return view('admin.api-keys.edit', compact(
            'apiKey', 
            'users', 
            'apiPermissions', 
            'assignedPermissions'
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
            'assigned_to' => 'nullable|exists:users,id',
            'expires_at' => 'nullable|date|after:now',
            'rate_limit_per_minute' => 'required|integer|min:1|max:1000',
            'rate_limit_per_hour' => 'required|integer|min:1|max:100000',
            'rate_limit_per_day' => 'required|integer|min:1|max:1000000',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,id',
            'auto_notifications' => 'boolean',
            'notification_webhook' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Store old values for logging
            $oldValues = [
                'name' => $apiKey->name,
                'description' => $apiKey->description,
                'assigned_to' => $apiKey->assigned_to,
                'expires_at' => $apiKey->expires_at,
                'rate_limits' => [
                    'per_minute' => $apiKey->rate_limit_per_minute,
                    'per_hour' => $apiKey->rate_limit_per_hour,
                    'per_day' => $apiKey->rate_limit_per_day,
                ],
                'allowed_ips' => $apiKey->allowed_ips,
                'permissions' => $apiKey->apiPermissions->pluck('name')->toArray(),
            ];

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

            // Update permissions
            $newPermissions = Permission::whereIn('id', $request->permissions)
                                       ->where('guard_name', 'api')
                                       ->pluck('name')
                                       ->toArray();
            
            $apiKey->syncPermissions($newPermissions);

            // Log update event
            $newValues = [
                'name' => $apiKey->name,
                'description' => $apiKey->description,
                'assigned_to' => $apiKey->assigned_to,
                'expires_at' => $apiKey->expires_at,
                'rate_limits' => [
                    'per_minute' => $apiKey->rate_limit_per_minute,
                    'per_hour' => $apiKey->rate_limit_per_hour,
                    'per_day' => $apiKey->rate_limit_per_day,
                ],
                'allowed_ips' => $apiKey->allowed_ips,
                'permissions' => $newPermissions,
            ];

            $apiKey->logEvent(
                ApiKeyEvent::EVENT_UPDATED,
                "API Key '{$apiKey->name}' updated",
                $oldValues,
                $newValues
            );

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

            // Log deletion event before deleting
            $apiKey->logEvent(
                ApiKeyEvent::EVENT_DELETED,
                "API Key '{$apiKeyName}' deleted",
                [
                    'usage_count' => $apiKey->usage_count,
                    'permissions' => $apiKey->apiPermissions->pluck('name')->toArray(),
                ],
                null
            );

            // Soft delete the API key
            $apiKey->delete();

            DB::commit();

            return redirect()->route('admin.api-keys.index')
                ->with('success', "API Key '{$apiKeyName}' deleted successfully.");

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
        try {
            DB::beginTransaction();

            $newKeyValue = $apiKey->regenerate(auth()->user());

            DB::commit();

            return redirect()->route('admin.api-keys.show', $apiKey)
                ->with('success', 'API Key regenerated successfully!')
                ->with('new_api_key', true); // Flag สำหรับแสดง key เต็ม

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
            $apiKey->toggleStatus(auth()->user());

            $status = $apiKey->is_active ? 'activated' : 'deactivated';
            
            return redirect()->back()
                ->with('success', "API Key {$status} successfully.");

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
     * Show usage statistics
     */
    public function usage(ApiKey $apiKey)
    {
        $usageStats = [
            'hourly' => ApiUsageLog::getUsageStats($apiKey, 'hour'),
            'daily' => ApiUsageLog::getUsageStats($apiKey, 'day'),
            'weekly' => ApiUsageLog::getUsageStats($apiKey, 'week'),
            'monthly' => ApiUsageLog::getUsageStats($apiKey, 'month'),
        ];

        $topEndpoints = ApiUsageLog::getTopEndpoints($apiKey);
        $errorAnalysis = ApiUsageLog::getErrorAnalysis($apiKey);
        $recentErrors = ApiUsageLog::getRecentErrors($apiKey);
        $slowRequests = ApiUsageLog::getSlowRequests($apiKey);

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
     * Clear API key value after showing
     */
    public function clearKeyValue(ApiKey $apiKey)
    {
        $apiKey->clearKeyValue();
        
        return response()->json(['success' => true]);
    }

    /**
     * Calculate rate limit utilization
     */
    private function calculateRateLimitUtilization(ApiKey $apiKey): string
    {
        $currentHourUsage = $apiKey->getUsageCount('hour');
        $rateLimit = $apiKey->getRateLimit('hour');
        
        if ($rateLimit <= 0) return '0.0%';
        
        $utilization = ($currentHourUsage / $rateLimit) * 100;
        
        return number_format($utilization, 1) . '%';
    }
}