<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\NotificationGroup;
use App\Services\LdapService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $ldapService;

    public function __construct(LdapService $ldapService = null)
    {
        $this->ldapService = $ldapService;
    }

    /**
     * List users from LDAP
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'search' => 'sometimes|string|min:2|max:50',
            'department' => 'sometimes|string|max:100',
            'active_only' => 'sometimes|boolean',
            'limit' => 'sometimes|integer|min:1|max:500',
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
                      ->orWhere('username', 'like', "%{$search}%");
                });
            }

            if ($request->has('department')) {
                $query->where('department', $request->department);
            }

            if ($request->get('active_only', true)) {
                $query->where('is_active', true);
            }

            $limit = $request->get('limit', 100);
            $users = $query->limit($limit)->get();

            $data = $users->map(function ($user) {
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
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => $users->count(),
                    'limit' => $limit,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users
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
                          ->orWhere('username', 'like', "%{$search}%");
                })
                ->limit($limit)
                ->get(['id', 'username', 'email', 'display_name', 'department']);

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
     * Get user details
     */
    public function show($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

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
                'message' => 'Failed to get user details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user preferences
     */
    public function getPreferences($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $preferences = $user->preferences;

            if (!$preferences) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'user_id' => $user->id,
                        'preferences' => null,
                        'message' => 'No preferences set for this user'
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'preferences' => [
                        'enable_teams' => $preferences->enable_teams,
                        'enable_email' => $preferences->enable_email,
                        'min_priority' => $preferences->min_priority,
                        'enable_quiet_hours' => $preferences->enable_quiet_hours,
                        'quiet_hours_start' => $preferences->quiet_hours_start,
                        'quiet_hours_end' => $preferences->quiet_hours_end,
                        'language' => $preferences->language,
                        'timezone' => $preferences->timezone,
                    ]
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user preferences
     */
    public function updatePreferences($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enable_teams' => 'sometimes|boolean',
            'enable_email' => 'sometimes|boolean',
            'min_priority' => 'sometimes|in:low,normal,high,urgent',
            'enable_quiet_hours' => 'sometimes|boolean',
            'quiet_hours_start' => 'sometimes|date_format:H:i',
            'quiet_hours_end' => 'sometimes|date_format:H:i',
            'language' => 'sometimes|string|in:th,en',
            'timezone' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($id);
            
            $preferences = $user->preferences ?? new \App\Models\UserPreference();
            $preferences->user_id = $user->id;
            $preferences->fill($request->only([
                'enable_teams', 'enable_email', 'min_priority', 'enable_quiet_hours',
                'quiet_hours_start', 'quiet_hours_end', 'language', 'timezone'
            ]));
            $preferences->save();

            return response()->json([
                'success' => true,
                'message' => 'User preferences updated successfully',
                'data' => $preferences
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's notification groups
     */
    public function getUserGroups($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            $groups = $user->notificationGroups()
                          ->where('is_active', true)
                          ->get(['id', 'name', 'description', 'type']);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'groups' => $groups
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync user from LDAP
     */
    public function syncFromLdap($id): JsonResponse
    {
        try {
            if (!$this->ldapService) {
                return response()->json([
                    'success' => false,
                    'message' => 'LDAP service not available'
                ], 503);
            }

            $user = User::findOrFail($id);
            
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
     * Get active users
     */
    public function getActive(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 50);
            
            $users = User::where('is_active', true)
                        ->orderBy('display_name')
                        ->limit($limit)
                        ->get(['id', 'username', 'email', 'display_name', 'department']);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get active users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get departments
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
}