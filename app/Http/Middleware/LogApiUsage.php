<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ApiKey;
use App\Models\ApiUsageLog;
use Illuminate\Support\Str;

class LogApiUsage
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $requestId = Str::uuid();
        
        // เพิ่ม request ID ไปใน request
        $request->merge(['request_id' => $requestId]);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000); // Convert to milliseconds
        
        // Log API usage
        $this->logApiUsage($request, $response, $responseTime, $requestId);
        
        return $response;
    }

    /**
     * Log API usage to database
     */
    private function logApiUsage(Request $request, $response, int $responseTime, string $requestId): void
    {
        try {
            $apiKey = $this->getApiKeyFromRequest($request);
            
            if (!$apiKey) {
                return; // ไม่มี API Key ไม่ต้อง log
            }

            // ดึง notification_id ถ้ามี
            $notificationId = $this->extractNotificationId($request, $response);
            
            // ดึงข้อมูล error message ถ้ามี
            $errorMessage = $this->extractErrorMessage($response);

            ApiUsageLog::logApiCall(
                $apiKey,
                $request->path(),
                $request->method(),
                $response->getStatusCode(),
                $responseTime,
                $this->getRequestData($request),
                $this->getResponseData($response),
                $errorMessage,
                $notificationId,
                $requestId
            );

            // Update API key usage count และ last used
            $apiKey->incrementUsage();

        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to log API usage', [
                'error' => $e->getMessage(),
                'request_path' => $request->path(),
                'request_id' => $requestId
            ]);
        }
    }

    /**
     * Get API Key from request
     */
    private function getApiKeyFromRequest(Request $request): ?ApiKey
    {
        // ลองหา API Key จาก request attributes ก่อน (จาก ApiKeyAuthentication middleware)
        $apiKey = $request->attributes->get('api_key');
        
        if ($apiKey instanceof ApiKey) {
            return $apiKey;
        }

        // ถ้าไม่มี ลองหาจาก header
        $apiKeyValue = $request->header('X-API-Key');
        
        if ($apiKeyValue) {
            return ApiKey::findByKey($apiKeyValue);
        }

        return null;
    }

    /**
     * Extract notification ID from request or response
     */
    private function extractNotificationId(Request $request, $response): ?int
    {
        // ลองหาจาก response ก่อน
        if ($response instanceof JsonResponse) {
            $responseData = $response->getData(true);
            
            if (isset($responseData['notification_id'])) {
                return $this->extractNumericId($responseData['notification_id']);
            }
            
            if (isset($responseData['data']['id'])) {
                return $this->extractNumericId($responseData['data']['id']);
            }
        }

        // ลองหาจาก request path
        $path = $request->path();
        
        // Pattern: /api/v1/notifications/{id}/...
        if (preg_match('/\/notifications\/([^\/]+)/', $path, $matches)) {
            return $this->extractNumericId($matches[1]);
        }

        // ลองหาจาก request data
        if ($request->has('notification_id')) {
            return $this->extractNumericId($request->input('notification_id'));
        }

        return null;
    }

    /**
     * Extract numeric ID from UUID or numeric string
     */
    private function extractNumericId($value): ?int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        // ถ้าเป็น UUID ลองหา notification จาก UUID
        if (is_string($value) && Str::isUuid($value)) {
            $notification = \App\Models\Notification::where('uuid', $value)->first();
            return $notification?->id;
        }

        return null;
    }

    /**
     * Extract error message from response
     */
    private function extractErrorMessage($response): ?string
    {
        if ($response instanceof JsonResponse && $response->getStatusCode() >= 400) {
            $responseData = $response->getData(true);
            
            if (isset($responseData['message'])) {
                return $responseData['message'];
            }
            
            if (isset($responseData['error'])) {
                return is_string($responseData['error']) 
                    ? $responseData['error'] 
                    : json_encode($responseData['error']);
            }

            if (isset($responseData['errors'])) {
                return is_string($responseData['errors']) 
                    ? $responseData['errors'] 
                    : json_encode($responseData['errors']);
            }
        }

        return null;
    }

    /**
     * Get sanitized request data
     */
    private function getRequestData(Request $request): array
    {
        $data = [
            'path' => $request->path(),
            'method' => $request->method(),
            'query' => $request->query(),
            'headers' => $this->getSafeHeaders($request),
        ];

        // เพิ่ม body data สำหรับ POST/PUT/PATCH
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $data['body'] = $request->except(['password', 'api_key', 'token', 'secret']);
        }

        return $data;
    }

    /**
     * Get safe headers (exclude sensitive ones)
     */
    private function getSafeHeaders(Request $request): array
    {
        $headers = $request->headers->all();
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie', 'x-csrf-token'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[REDACTED]'];
            }
        }

        return $headers;
    }

    /**
     * Get response data
     */
    private function getResponseData($response): ?array
    {
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            
            // ลบข้อมูลที่เป็นความลับ
            $sensitiveKeys = ['api_key', 'password', 'secret', 'token'];
            foreach ($sensitiveKeys as $key) {
                if (isset($data[$key])) {
                    $data[$key] = '[REDACTED]';
                }
            }

            return $data;
        }

        return null;
    }
}