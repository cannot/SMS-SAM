<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Validate API Key
     */
    public function validateKey(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->attributes->get('api_key');

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not provided or invalid'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'API key is valid',
                'data' => [
                    'api_key_id' => $apiKey->id,
                    'name' => $apiKey->name,
                    'is_active' => $apiKey->is_active,
                    'rate_limit_per_minute' => $apiKey->rate_limit_per_minute,
                    'permissions' => $apiKey->permissions ?? [],
                    'expires_at' => $apiKey->expires_at,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate API key',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get API Key information
     */
    public function getApiKeyInfo(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->attributes->get('api_key');

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not provided'
                ], 401);
            }

            $data = [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'description' => $apiKey->description,
                'is_active' => $apiKey->is_active,
                'rate_limit_per_minute' => $apiKey->rate_limit_per_minute,
                'usage_count' => $apiKey->usage_count,
                'last_used_at' => $apiKey->last_used_at,
                'expires_at' => $apiKey->expires_at,
                'permissions' => $apiKey->permissions ?? [],
                'ip_whitelist' => $apiKey->ip_whitelist ?? [],
                'created_at' => $apiKey->created_at,
            ];

            // Calculate usage statistics
            $usageStats = [
                'usage_today' => $apiKey->usage_count, // Simplified - should be daily count
                'rate_limit_remaining' => max(0, $apiKey->rate_limit_per_minute - ($apiKey->usage_count_current_minute ?? 0)),
                'is_near_rate_limit' => ($apiKey->usage_count_current_minute ?? 0) > ($apiKey->rate_limit_per_minute * 0.8),
            ];

            return response()->json([
                'success' => true,
                'data' => array_merge($data, ['usage_stats' => $usageStats])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get API key information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test API Key permissions
     */
    public function testPermissions(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->attributes->get('api_key');
            $permission = $request->input('permission');

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not provided'
                ], 401);
            }

            $hasPermission = $apiKey->hasPermission($permission);

            return response()->json([
                'success' => true,
                'data' => [
                    'permission' => $permission,
                    'has_permission' => $hasPermission,
                    'all_permissions' => $apiKey->permissions ?? [],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get API usage statistics
     */
    public function getUsageStats(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->attributes->get('api_key');

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not provided'
                ], 401);
            }

            // Get usage logs for this API key (if you have usage logging)
            $stats = [
                'api_key_id' => $apiKey->id,
                'total_requests' => $apiKey->usage_count,
                'requests_today' => $apiKey->usage_count, // Simplified
                'rate_limit' => $apiKey->rate_limit_per_minute,
                'last_used' => $apiKey->last_used_at,
                'created' => $apiKey->created_at,
                'days_active' => $apiKey->created_at->diffInDays(now()),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get usage statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refresh API Key usage counter
     */
    public function refreshUsage(Request $request): JsonResponse
    {
        try {
            $apiKey = $request->attributes->get('api_key');

            if (!$apiKey) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key not provided'
                ], 401);
            }

            // Reset usage counter (admin function)
            $apiKey->update([
                'usage_count' => 0,
                'usage_reset_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Usage counter reset successfully',
                'data' => [
                    'usage_count' => 0,
                    'reset_at' => now(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh usage counter',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}