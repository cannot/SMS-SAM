<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiKey;
use App\Models\ApiUsageLogs;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Extract API key from request
        $apiKeyValue = $this->extractApiKey($request);
        
        if (!$apiKeyValue) {
            return $this->unauthorizedResponse('API key is required');
        }
        
        // Find and validate API key
        $apiKey = $this->findApiKey($apiKeyValue);
        
        if (!$apiKey) {
            $this->logFailedAttempt($request, 'Invalid API key', 401);
            return $this->unauthorizedResponse('Invalid API key');
        }
        
        // Check if API key can be used
        $validation = $apiKey->canBeUsed(
            $request->ip(),
            $this->extractRequiredPermission($request)
        );
        
        if (!$validation['can_use']) {
            $this->logFailedAttempt($request, implode(', ', $validation['errors']), 403, $apiKey->id);
            return $this->forbiddenResponse($validation['errors'][0]);
        }
        
        // Check rate limit
        $rateLimitCheck = $apiKey->checkRateLimit();
        
        if ($rateLimitCheck['limit_exceeded']) {
            $this->logFailedAttempt($request, 'Rate limit exceeded', 429, $apiKey->id);
            return $this->rateLimitResponse($rateLimitCheck);
        }
        
        // Store API key in request for use in controllers
        $request->attributes->set('api_key', $apiKey);
        $request->apiKey = $apiKey; // For easier access
        
        // Process the request
        $response = $next($request);
        
        // Log successful request and update usage
        $this->logSuccessfulRequest($request, $response, $apiKey, $startTime);
        
        // Add rate limit headers to response
        $this->addRateLimitHeaders($response, $rateLimitCheck);
        
        return $response;
    }
    
    /**
     * Extract API key from request headers
     */
    private function extractApiKey(Request $request): ?string
    {
        // Check X-API-Key header (preferred)
        $apiKey = $request->header('X-API-Key');
        
        // Check Authorization header as fallback
        if (!$apiKey) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && Str::startsWith($authHeader, 'Bearer ')) {
                $apiKey = Str::substr($authHeader, 7);
            }
        }
        
        // Check query parameter as last resort (not recommended for production)
        if (!$apiKey && config('app.env') !== 'production') {
            $apiKey = $request->query('api_key');
        }
        
        return $apiKey;
    }
    
    /**
     * Find API key in database with caching
     */
    private function findApiKey(string $apiKeyValue): ?ApiKey
    {
        // Cache key based on hash of the API key for security
        $cacheKey = 'api_key_' . hash('sha256', $apiKeyValue);
        
        return Cache::remember($cacheKey, 300, function () use ($apiKeyValue) {
            return ApiKey::with(['assignedTo', 'createdBy'])
                ->get()
                ->first(function ($apiKey) use ($apiKeyValue) {
                    return $apiKey->verifyKey($apiKeyValue);
                });
        });
    }
    
    /**
     * Extract required permission for current endpoint
     */
    private function extractRequiredPermission(Request $request): ?string
    {
        $route = $request->route();
        
        if (!$route) {
            return null;
        }
        
        $routeName = $route->getName();
        $method = $request->method();
        $path = $request->path();
        
        // Map routes to permissions
        $permissionMap = [
            // Notification endpoints
            'api.notifications.send' => 'notifications.send',
            'api.notifications.bulk' => 'notifications.bulk',
            'api.notifications.schedule' => 'notifications.schedule',
            'api.notifications.status' => 'notifications.status',
            'api.notifications.history' => 'notifications.history',
            'api.notifications.cancel' => 'notifications.send',
            'api.notifications.retry' => 'notifications.send',
            
            // User endpoints
            'api.users.index' => 'users.read',
            'api.users.search' => 'users.search',
            'api.users.show' => 'users.read',
            'api.users.preferences' => 'users.read',
            'api.users.preferences.update' => 'users.manage',
            
            // Group endpoints
            'api.groups.index' => 'groups.read',
            'api.groups.show' => 'groups.read',
            'api.groups.store' => 'groups.manage',
            'api.groups.update' => 'groups.manage',
            'api.groups.destroy' => 'groups.manage',
            'api.groups.members' => 'groups.read',
            'api.groups.members.add' => 'groups.manage',
            'api.groups.members.remove' => 'groups.manage',
            
            // Template endpoints
            'api.templates.index' => 'templates.read',
            'api.templates.show' => 'templates.read',
            'api.templates.render' => 'templates.render',
            'api.templates.send' => 'notifications.send',
            
            // System endpoints
            'api.system.health' => 'system.health',
            'api.analytics.usage' => 'analytics.read',
        ];
        
        // Check exact route name match
        if (isset($permissionMap[$routeName])) {
            return $permissionMap[$routeName];
        }
        
        // Fallback: determine permission by path pattern
        if (Str::startsWith($path, 'api/v1/notifications')) {
            switch ($method) {
                case 'GET':
                    return 'notifications.status';
                case 'POST':
                    return 'notifications.send';
                case 'DELETE':
                    return 'notifications.send';
                default:
                    return 'notifications.send';
            }
        }
        
        if (Str::startsWith($path, 'api/v1/users')) {
            return $method === 'GET' ? 'users.read' : 'users.manage';
        }
        
        if (Str::startsWith($path, 'api/v1/groups')) {
            return $method === 'GET' ? 'groups.read' : 'groups.manage';
        }
        
        if (Str::startsWith($path, 'api/v1/templates')) {
            return $method === 'GET' ? 'templates.read' : 'templates.manage';
        }
        
        // Default to null (no specific permission required)
        return null;
    }
    
    /**
     * Log successful API request
     */
    private function logSuccessfulRequest(Request $request, Response $response, ApiKey $apiKey, float $startTime): void
    {
        $responseTime = round((microtime(true) - $startTime) * 1000); // Convert to milliseconds
        
        // Increment usage count
        $apiKey->incrementUsage();
        
        // Log to usage logs table
        ApiUsageLogs::logRequest(
            apiKeyId: $apiKey->id,
            endpoint: $request->path(),
            method: $request->method(),
            ipAddress: $request->ip(),
            responseCode: $response->getStatusCode(),
            responseTime: $responseTime,
            requestData: $this->sanitizeRequestData($request),
            responseData: $this->sanitizeResponseData($response),
            userAgent: $request->userAgent(),
            notificationId: $request->get('notification_id'), // If available
            requestId: $request->header('X-Request-ID') ?? Str::uuid()
        );
        
        // Log to application log for monitoring
        Log::info('API request processed', [
            'api_key_id' => $apiKey->id,
            'api_key_name' => $apiKey->name,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'response_code' => $response->getStatusCode(),
            'response_time' => $responseTime,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
    }
    
    /**
     * Log failed API attempt
     */
    private function logFailedAttempt(Request $request, string $reason, int $statusCode, ?int $apiKeyId = null): void
    {
        // Log to usage logs if we have an API key
        if ($apiKeyId) {
            ApiUsageLogs::logRequest(
                apiKeyId: $apiKeyId,
                endpoint: $request->path(),
                method: $request->method(),
                ipAddress: $request->ip(),
                responseCode: $statusCode,
                errorMessage: $reason,
                userAgent: $request->userAgent(),
                requestId: $request->header('X-Request-ID') ?? Str::uuid()
            );
        }
        
        // Log to application log for security monitoring
        Log::warning('API request failed', [
            'api_key_id' => $apiKeyId,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'reason' => $reason,
            'status_code' => $statusCode,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        // Additional security logging for suspicious activity
        if ($statusCode === 401 || $statusCode === 403) {
            $this->logSecurityEvent($request, $reason, $statusCode);
        }
    }
    
    /**
     * Log security events for monitoring
     */
    private function logSecurityEvent(Request $request, string $reason, int $statusCode): void
    {
        $ip = $request->ip();
        $cacheKey = "security_events_{$ip}";
        
        // Count failed attempts from this IP
        $attempts = Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $attempts, 3600); // Store for 1 hour
        
        // Log security event
        Log::channel('security')->warning('API security event', [
            'ip_address' => $ip,
            'endpoint' => $request->path(),
            'reason' => $reason,
            'status_code' => $statusCode,
            'attempts_count' => $attempts,
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
        
        // Alert on suspicious activity (multiple failed attempts)
        if ($attempts >= 10) {
            Log::channel('security')->critical('Possible API abuse detected', [
                'ip_address' => $ip,
                'attempts_in_hour' => $attempts,
                'latest_endpoint' => $request->path(),
                'latest_reason' => $reason
            ]);
            
            // TODO: Consider implementing automatic IP blocking here
            // TODO: Send alert notification to administrators
        }
    }
    
    /**
     * Sanitize request data for logging
     */
    private function sanitizeRequestData(Request $request): ?array
    {
        $data = $request->all();
        
        if (empty($data)) {
            return null;
        }
        
        // Remove sensitive fields
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'api_key'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***REDACTED***';
            }
        }
        
        // Limit size to prevent large payloads in logs
        $json = json_encode($data);
        if (strlen($json) > 10000) { // 10KB limit
            return ['note' => 'Request data too large for logging'];
        }
        
        return $data;
    }
    
    /**
     * Sanitize response data for logging
     */
    private function sanitizeResponseData(Response $response): ?array
    {
        // Only log response for certain content types and small responses
        $contentType = $response->headers->get('Content-Type', '');
        
        if (!Str::contains($contentType, 'application/json')) {
            return null;
        }
        
        $content = $response->getContent();
        
        if (strlen($content) > 5000) { // 5KB limit
            return ['note' => 'Response data too large for logging'];
        }
        
        $decoded = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $decoded;
    }
    
    /**
     * Add rate limit headers to response
     */
    private function addRateLimitHeaders(Response $response, array $rateLimitCheck): void
    {
        $response->headers->set('X-RateLimit-Limit', $rateLimitCheck['limit']);
        $response->headers->set('X-RateLimit-Remaining', $rateLimitCheck['remaining']);
        $response->headers->set('X-RateLimit-Reset', $rateLimitCheck['reset_time']->timestamp);
        
        if ($rateLimitCheck['limit_exceeded']) {
            $response->headers->set('Retry-After', 60); // Retry after 1 minute
        }
    }
    
    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'UNAUTHORIZED',
            'timestamp' => now()->toISOString()
        ], 401);
    }
    
    /**
     * Return forbidden response
     */
    private function forbiddenResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => 'FORBIDDEN',
            'timestamp' => now()->toISOString()
        ], 403);
    }
    
    /**
     * Return rate limit exceeded response
     */
    private function rateLimitResponse(array $rateLimitCheck): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Rate limit exceeded',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'rate_limit' => [
                'limit' => $rateLimitCheck['limit'],
                'remaining' => $rateLimitCheck['remaining'],
                'reset_time' => $rateLimitCheck['reset_time']->toISOString(),
                'retry_after' => 60
            ],
            'timestamp' => now()->toISOString()
        ], 429)->header('Retry-After', 60);
    }
}