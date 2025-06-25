<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LdapService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LdapController extends Controller
{
    protected $ldapService;

    public function __construct(LdapService $ldapService = null)
    {
        $this->ldapService = $ldapService;
    }

    public function getUsers(Request $request): JsonResponse
    {
        try {
            if (!$this->ldapService) {
                return response()->json(['success' => false, 'message' => 'LDAP service not available'], 503);
            }

            $users = $this->ldapService->getUsers($request->get('search'));
            return response()->json(['success' => true, 'data' => $users]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getGroups(): JsonResponse
    {
        try {
            if (!$this->ldapService) {
                return response()->json(['success' => false, 'message' => 'LDAP service not available'], 503);
            }

            $groups = $this->ldapService->getGroups();
            return response()->json(['success' => true, 'data' => $groups]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function syncUsers(): JsonResponse
    {
        try {
            if (!$this->ldapService) {
                return response()->json(['success' => false, 'message' => 'LDAP service not available'], 503);
            }

            $result = $this->ldapService->syncAllUsers();
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function testConnection(): JsonResponse
    {
        try {
            if (!$this->ldapService) {
                return response()->json(['success' => false, 'message' => 'LDAP service not available'], 503);
            }

            $isConnected = $this->ldapService->testConnection();
            return response()->json([
                'success' => true,
                'data' => ['connected' => $isConnected]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}