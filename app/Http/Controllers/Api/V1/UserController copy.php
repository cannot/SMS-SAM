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

    public function __construct(LdapService $ldapService)
    {
        $this->ldapService = $ldapService;
    }

    public function preferences($id)
    {
        return view('users.preferences', compact('id'));
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
            $apiKey = $request->attributes->get('api_key');
            
            if (!$apiKey->hasPermission('users.list')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

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
                'total' => $users->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get departments list
     */
    public function departments(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->attributes->get('api_key');
            
            if (!$apiKey->hasPermission('users.list')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            $departments = User::select('department')
                              ->whereNotNull('department')
                              ->where('is_active', true)
                              ->distinct()
                              ->orderBy('department')
                              ->pluck('department');

            return response()->json([
                'success' => true,
                'data' => $departments
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
}