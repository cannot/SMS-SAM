<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\NotificationLog;
use App\Models\NotificationGroup;
use App\Models\UserPreference;
use App\Services\LdapService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    protected $ldapService;

    public function __construct(LdapService $ldapService = null)
    {
        $this->ldapService = $ldapService;
    }

    /**
     * Get user stats for dashboard
     */
    public function getUserStats(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $stats = [
                'notifications_received' => NotificationLog::where('user_id', $user->id)->count(),
                'notifications_read' => NotificationLog::where('user_id', $user->id)->whereNotNull('read_at')->count(),
                'notifications_unread' => NotificationLog::where('user_id', $user->id)->whereNull('read_at')->count(),
                'notifications_this_week' => NotificationLog::where('user_id', $user->id)
                    ->where('created_at', '>=', now()->startOfWeek())->count(),
                'notifications_today' => NotificationLog::where('user_id', $user->id)
                    ->whereDate('created_at', today())->count(),
                'groups_count' => $user->notificationGroups()->count(),
                'preferences_set' => $user->preferences ? true : false,
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadNotifications(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $count = NotificationLog::where('user_id', $user->id)
                ->whereNull('read_at')
                ->whereNull('archived_at')
                ->count();

            // Get recent unread notifications
            $recentUnread = NotificationLog::where('user_id', $user->id)
                ->whereNull('read_at')
                ->whereNull('archived_at')
                ->with('notification')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'subject' => $log->notification->subject ?? 'No subject',
                        'channel' => $log->channel,
                        'priority' => $log->notification->priority ?? 'normal',
                        'created_at' => $log->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $count,
                    'recent_unread' => $recentUnread
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load unread count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user profile
     */
    public function getProfile(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $data = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'display_name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'department' => $user->department,
                'title' => $user->title,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at,
                'auth_source' => $user->auth_source,
                'ldap_synced_at' => $user->ldap_synced_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List users with search and filters
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'sometimes|string|min:2|max:50',
            'department' => 'sometimes|string|max:100',
            'active_only' => 'sometimes|boolean',
            'limit' => 'sometimes|integer|min:1|max:500',
            'page' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = User::query();

            // Apply filters
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('display_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%");
                });
            }

            if ($request->has('department')) {
                $query->where('department', $request->department);
            }

            if ($request->get('active_only', true)) {
                $query->where('is_active', true);
            }

            $limit = $request->get('limit', 20);
            $users = $query->paginate($limit);

            $data = $users->getCollection()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'display_name' => $user->display_name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'department' => $user->department,
                    'title' => $user->title,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'last_page' => $users->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users (for autocomplete)
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2|max:50',
            'limit' => 'sometimes|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $search = $request->q;
            $limit = $request->get('limit', 10);

            $users = User::where('is_active', true)
                ->where(function ($query) use ($search) {
                    $query->where('display_name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('username', 'like', "%{$search}%")
                          ->orWhere('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->limit($limit)
                ->get(['id', 'username', 'email', 'display_name', 'department', 'title']);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user by ID
     */
    public function show($id): JsonResponse
    {
        try {
            $user = User::with(['preferences', 'notificationGroups', 'roles'])->findOrFail($id);

            $data = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'display_name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'department' => $user->department,
                'title' => $user->title,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at,
                'notification_groups' => $user->notificationGroups->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'type' => $group->type,
                        'is_active' => $group->is_active,
                    ];
                }),
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                    ];
                }),
                'preferences' => $user->preferences ? [
                    'enable_teams' => $user->preferences->enable_teams,
                    'enable_email' => $user->preferences->enable_email,
                    'min_priority' => $user->preferences->min_priority,
                    'language' => $user->preferences->language,
                    'timezone' => $user->preferences->timezone,
                ] : null,
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enable_teams' => 'sometimes|boolean',
            'enable_email' => 'sometimes|boolean',
            'min_priority' => 'sometimes|in:low,normal,high,urgent',
            'enable_quiet_hours' => 'sometimes|boolean',
            'quiet_hours_start' => 'sometimes|date_format:H:i',
            'quiet_hours_end' => 'sometimes|date_format:H:i',
            'quiet_days' => 'sometimes|array',
            'quiet_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'language' => 'sometimes|string|in:th,en',
            'timezone' => 'sometimes|string',
            'email_format' => 'sometimes|in:html,plain',
            'teams_channel_preference' => 'sometimes|in:direct,channel',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $preferences = $user->preferences ?? new UserPreference();
            $preferences->user_id = $user->id;
            $preferences->fill($request->only([
                'enable_teams', 'enable_email', 'min_priority', 'enable_quiet_hours',
                'quiet_hours_start', 'quiet_hours_end', 'quiet_days', 'language', 
                'timezone', 'email_format', 'teams_channel_preference'
            ]));
            $preferences->save();

            return response()->json([
                'success' => true,
                'message' => 'Preferences updated successfully',
                'data' => $preferences
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's notification groups
     */
    public function getGroups(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $groups = $user->notificationGroups()
                          ->where('is_active', true)
                          ->withCount('users')
                          ->get(['id', 'name', 'description', 'type']);

            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's notification history
     */
    public function getNotificationHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $query = NotificationLog::where('user_id', $user->id)
                                   ->with('notification');

            // Filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('channel')) {
                $query->where('channel', $request->channel);
            }

            if ($request->has('unread_only') && $request->unread_only) {
                $query->whereNull('read_at');
            }

            $limit = $request->get('limit', 20);
            $logs = $query->orderBy('created_at', 'desc')->paginate($limit);

            $data = $logs->getCollection()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'notification_id' => $log->notification_id,
                    'subject' => $log->notification->subject ?? 'No subject',
                    'channel' => $log->channel,
                    'status' => $log->status,
                    'read_at' => $log->read_at,
                    'created_at' => $log->created_at,
                    'delivered_at' => $log->delivered_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load notification history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($logId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $log = NotificationLog::where('id', $logId)
                                 ->where('user_id', $user->id)
                                 ->firstOrFail();

            $log->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $updated = NotificationLog::where('user_id', $user->id)
                                     ->whereNull('read_at')
                                     ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => "Marked {$updated} notifications as read"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get departments list
     */
    public function getDepartments(): JsonResponse
    {
        try {
            $departments = User::where('is_active', true)
                              ->whereNotNull('department')
                              ->distinct()
                              ->pluck('department')
                              ->sort()
                              ->values();

            return response()->json([
                'success' => true,
                'data' => $departments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get departments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync user data from LDAP (if available)
     */
    public function syncFromLdap($id = null): JsonResponse
    {
        try {
            if (!$this->ldapService) {
                return response()->json([
                    'success' => false,
                    'message' => 'LDAP service not available'
                ], 503);
            }

            // If no ID provided, sync current user
            $userId = $id ?? Auth::id();
            $user = User::findOrFail($userId);
            
            // Check permission to sync other users
            if ($id && $id != Auth::id() && !Auth::user()->can('manage-users')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions to sync other users'
                ], 403);
            }

            // Sync user data from LDAP
            $syncResult = $this->ldapService->syncUser($user);
            
            return response()->json([
                'success' => true,
                'message' => 'User synced successfully from LDAP',
                'data' => $syncResult
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync user from LDAP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's dashboard widget data
     */
    public function getDashboardWidget(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $data = [
                'user_info' => [
                    'display_name' => $user->display_name,
                    'department' => $user->department,
                    'title' => $user->title,
                    'last_login' => $user->last_login_at,
                ],
                'notification_stats' => [
                    'total_received' => NotificationLog::where('user_id', $user->id)->count(),
                    'unread_count' => NotificationLog::where('user_id', $user->id)
                                                    ->whereNull('read_at')
                                                    ->count(),
                    'today_count' => NotificationLog::where('user_id', $user->id)
                                                   ->whereDate('created_at', today())
                                                   ->count(),
                ],
                'groups' => $user->notificationGroups()
                                ->where('is_active', true)
                                ->count(),
                'preferences_configured' => $user->preferences ? true : false,
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard widget',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}