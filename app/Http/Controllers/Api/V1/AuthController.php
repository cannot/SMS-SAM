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
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey) {
            return response()->json([
                'valid' => false,
                'message' => 'API key is required'
            ], 401);
        }

        $key = ApiKey::where('key', $apiKey)
                    ->active()
                    ->first();

        if (!$key) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired API key'
            ], 401);
        }

        $key->updateLastUsed();

        return response()->json([
            'valid' => true,
            'key_name' => $key->name,
            'permissions' => $key->permissions,
            'rate_limit' => $key->rate_limit_per_minute,
            'expires_at' => $key->expires_at,
        ], 200);
    }
}