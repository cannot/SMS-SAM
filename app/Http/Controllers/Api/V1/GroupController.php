<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    /**
     * List notification groups
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:manual,department,role,ldap_group,system,dynamic',
            'active_only' => 'sometimes|boolean',
            'search' => 'sometimes|string|min:2|max:50',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = NotificationGroup::query();

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->get('active_only', true)) {
                $query->where('is_active', true);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $limit = $request->get('limit', 50);
            $groups = $query->withCount('users')->orderBy('name')->limit($limit)->get();

            $data = $groups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'type' => $group->type,
                    'is_active' => $group->is_active,
                    'members_count' => $group->users_count,
                    'created_at' => $group->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => $groups->count(),
                    'limit' => $limit,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new notification group
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:notification_groups,name',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:manual,department,role,ldap_group,system,dynamic',
            'criteria' => 'nullable|array',
            'ldap_filter' => 'nullable|string',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $group = NotificationGroup::create([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'criteria' => $request->criteria,
                'ldap_filter' => $request->ldap_filter,
                'is_active' => true,
                'created_by' => auth()->id() ?? 1, // Default to system user if no auth
            ]);

            // Add users if provided
            if ($request->has('user_ids') && !empty($request->user_ids)) {
                $users = User::whereIn('id', $request->user_ids)->get();
                foreach ($users as $user) {
                    $group->users()->attach($user->id, [
                        'joined_at' => now(),
                        'added_by' => auth()->id() ?? 1,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Notification group created successfully',
                'data' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'type' => $group->type,
                    'members_count' => $group->users()->count(),
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create notification group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show group details
     */
    public function show($id): JsonResponse
    {
        try {
            $group = NotificationGroup::with(['users', 'creator'])->findOrFail($id);

            $data = [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'type' => $group->type,
                'criteria' => $group->criteria,
                'ldap_filter' => $group->ldap_filter,
                'is_active' => $group->is_active,
                'created_at' => $group->created_at,
                'created_by' => $group->creator ? [
                    'id' => $group->creator->id,
                    'name' => $group->creator->display_name,
                ] : null,
                'members_count' => $group->users->count(),
                'members' => $group->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'display_name' => $user->display_name,
                        'department' => $user->department,
                        'joined_at' => $user->pivot->joined_at,
                    ];
                }),
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification group not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get group details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification group
     */
    public function update($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:notification_groups,name,' . $id,
            'description' => 'nullable|string|max:500',
            'type' => 'sometimes|in:manual,department,role,ldap_group,system,dynamic',
            'criteria' => 'nullable|array',
            'ldap_filter' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $group = NotificationGroup::findOrFail($id);
            
            $group->update($request->only([
                'name', 'description', 'type', 'criteria', 'ldap_filter', 'is_active'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Notification group updated successfully',
                'data' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'type' => $group->type,
                    'is_active' => $group->is_active,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification group not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete notification group
     */
    public function destroy($id): JsonResponse
    {
        try {
            $group = NotificationGroup::findOrFail($id);
            
            // Check if group is used in any notifications
            $notificationCount = $group->notifications()->count();
            if ($notificationCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete group. It's used in {$notificationCount} notifications."
                ], 400);
            }

            $group->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification group deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification group not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get group members
     */
    public function getMembers($id): JsonResponse
    {
        try {
            $group = NotificationGroup::with('users')->findOrFail($id);

            $members = $group->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'display_name' => $user->display_name,
                    'department' => $user->department,
                    'is_active' => $user->is_active,
                    'joined_at' => $user->pivot->joined_at,
                    'added_by' => $user->pivot->added_by,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'members_count' => $members->count(),
                    'members' => $members,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification group not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get group members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add member to group
     */
    public function addMember($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $group = NotificationGroup::findOrFail($id);
            $user = User::findOrFail($request->user_id);

            // Check if user is already in group
            if ($group->users()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already a member of this group'
                ], 400);
            }

            $group->users()->attach($user->id, [
                'joined_at' => now(),
                'added_by' => auth()->id() ?? 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User added to group successfully',
                'data' => [
                    'group_id' => $group->id,
                    'user_id' => $user->id,
                    'user_name' => $user->display_name,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Group or user not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add member to group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove member from group
     */
    public function removeMember($id, $userId): JsonResponse
    {
        try {
            $group = NotificationGroup::findOrFail($id);
            $user = User::findOrFail($userId);

            if (!$group->users()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a member of this group'
                ], 400);
            }

            $group->users()->detach($user->id);

            return response()->json([
                'success' => true,
                'message' => 'User removed from group successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Group or user not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove member from group',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync group members based on criteria
     */
    public function syncMembers($id): JsonResponse
    {
        try {
            $group = NotificationGroup::findOrFail($id);

            if ($group->type === 'manual') {
                return response()->json([
                    'success' => false,
                    'message' => 'Manual groups cannot be synced automatically'
                ], 400);
            }

            $syncedCount = 0;

            // Sync based on group type
            switch ($group->type) {
                case 'department':
                    if (isset($group->criteria['department'])) {
                        $users = User::where('department', $group->criteria['department'])
                                   ->where('is_active', true)
                                   ->get();
                        $group->users()->sync($users->pluck('id'));
                        $syncedCount = $users->count();
                    }
                    break;

                case 'role':
                    if (isset($group->criteria['role'])) {
                        $users = User::role($group->criteria['role'])
                                   ->where('is_active', true)
                                   ->get();
                        $group->users()->sync($users->pluck('id'));
                        $syncedCount = $users->count();
                    }
                    break;

                // Add more sync logic for other types
            }

            return response()->json([
                'success' => true,
                'message' => 'Group members synced successfully',
                'data' => [
                    'group_id' => $group->id,
                    'synced_members' => $syncedCount,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification group not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync group members',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}