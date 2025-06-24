<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ApiKeyController extends Controller
{
    /**
     * List API keys
     */
    public function index(Request $request): JsonResponse
    {
        $apiKeys = ApiKey::with(['creator:id,display_name'])
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->map(function ($key) {
                            return [
                                'id' => $key->id,
                                'name' => $key->name,
                                'permissions' => $key->permissions,
                                'rate_limit_per_minute' => $key->rate_limit_per_minute,
                                'is_active' => $key->is_active,
                                'expires_at' => $key->expires_at,
                                'last_used_at' => $key->last_used_at,
                                'created_by' => $key->creator?->display_name,
                                'created_at' => $key->created_at,
                                // Don't expose the actual key
                                'key_preview' => substr($key->key, 0, 8) . '...' . substr($key->key, -4),
                            ];
                        });

        return response()->json([
            'success' => true,
            'data' => $apiKeys
        ]);
    }

    /**
     * Create new API key
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'string|in:notifications.send,notifications.bulk,notifications.status,notifications.history,users.list,groups.list,groups.create',
            'rate_limit_per_minute' => 'sometimes|integer|min:1|max:10000',
            'expires_in_days' => 'sometimes|integer|min:1|max:3650',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiKeyValue = ApiKey::generateKey();
            
            $apiKey = ApiKey::create([
                'name' => $request->name,
                'key' => $apiKeyValue,
                'permissions' => $request->permissions,
                'rate_limit_per_minute' => $request->get('rate_limit_per_minute', 100),
                'expires_at' => $request->has('expires_in_days') ? 
                    now()->addDays($request->expires_in_days) : null,
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'API key created successfully',
                'data' => [
                    'id' => $apiKey->id,
                    'name' => $apiKey->name,
                    'key' => $apiKeyValue, // Only shown once during creation
                    'permissions' => $apiKey->permissions,
                    'rate_limit_per_minute' => $apiKey->rate_limit_per_minute,
                    'expires_at' => $apiKey->expires_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create API key'
            ], 500);
        }
    }

    /**
     * Update API key
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'string|in:notifications.send,notifications.bulk,notifications.status,notifications.history,users.list,groups.list,groups.create',
            'rate_limit_per_minute' => 'sometimes|integer|min:1|max:10000',
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
            $apiKey = ApiKey::findOrFail($id);
            
            $apiKey->update($request->only([
                'name', 'permissions', 'rate_limit_per_minute', 'is_active'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'API key updated successfully',
                'data' => [
                    'id' => $apiKey->id,
                    'name' => $apiKey->name,
                    'permissions' => $apiKey->permissions,
                    'rate_limit_per_minute' => $apiKey->rate_limit_per_minute,
                    'is_active' => $apiKey->is_active,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API key not found'
            ], 404);
        }
    }

    /**
     * Revoke API key
     */
    public function destroy($id): JsonResponse
    {
        try {
            $apiKey = ApiKey::findOrFail($id);
            $apiKey->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'API key revoked successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'API key not found'
            ], 404);
        }
    }
}