<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationGroup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    /**
     * List notification groups
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->attributes->get('api_key');
            
            if (!$apiKey->hasPermission('groups.list')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            $groups = NotificationGroup::where('is_active', true)
                                     ->select(['id', 'name', 'description', 'type'])
                                     ->get();

            return response()->json([
                'success' => true,
                'data' => $groups
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create notification group
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:notification_groups,name',
            'description' => 'sometimes|string|max:1000',
            'type' => 'required|in:static,dynamic,ldap_group',
            'user_ids' => 'required_if:type,static|array',
            'user_ids.*' => 'integer|exists:users,id',
            'criteria' => 'required_if:type,dynamic|array',
            'ldap_filter' => 'required_if:type,ldap_group|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiKey = $request->attributes->get('api_key');
            
            if (!$apiKey->hasPermission('groups.create')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            $group = NotificationGroup::create([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'user_ids' => $request->user_ids,
                'criteria' => $request->criteria,
                'ldap_filter' => $request->ldap_filter,
                'created_by' => 1, // System user for API created groups
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Group created successfully',
                'data' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'type' => $group->type,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get group members
     */
    public function members(Request $request, $groupId): JsonResponse
    {
        try {
            $apiKey = $request->attributes->get('api_key');
            
            if (!$apiKey->hasPermission('groups.list')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            $group = NotificationGroup::find($groupId);
            
            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Group not found'
                ], 404);
            }

            $users = $group->getUsers();

            $data = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'display_name' => $user->display_name,
                    'department' => $user->department,
                ];
            });

            return response()->json([
                'success' => true,
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'type' => $group->type,
                ],
                'members' => $data,
                'total_members' => $users->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
}