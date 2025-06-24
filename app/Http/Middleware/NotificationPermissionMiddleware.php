<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class NotificationPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'กรุณาเข้าสู่ระบบก่อน');
        }

        $user = Auth::user();

        // Check if user has any of the required permissions
        if (!empty($permissions)) {
            foreach ($permissions as $permission) {
                if ($user->can($permission)) {
                    return $next($request);
                }
            }

            // If no permissions match, check for fallback permissions
            if ($this->checkFallbackPermissions($user, $permissions)) {
                return $next($request);
            }

            // Log unauthorized access attempt
            \Log::warning('Unauthorized notification access attempt', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'required_permissions' => $permissions,
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'route' => $request->route()->getName(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'ไม่มีสิทธิ์เข้าถึงฟังก์ชันนี้',
                    'required_permissions' => $permissions
                ], 403);
            }

            abort(403, 'ไม่มีสิทธิ์เข้าถึงฟังก์ชันนี้');
        }

        return $next($request);
    }

    /**
     * Check for fallback permissions (more general permissions that might allow access)
     */
    private function checkFallbackPermissions($user, $requiredPermissions): bool
    {
        $fallbackMap = [
            // If user needs specific notification permissions but has manage-notifications
            'view-all-notifications' => ['manage-notifications'],
            'create-notifications' => ['manage-notifications'],
            'edit-notifications' => ['manage-notifications'],
            'delete-notifications' => ['manage-notifications'],
            'cancel-notifications' => ['manage-notifications'],
            'resend-notifications' => ['manage-notifications'],
            
            // If user needs specific template permissions but has general template access
            'create-notification-templates' => ['manage-notification-templates'],
            'edit-notification-templates' => ['manage-notification-templates'],
            'delete-notification-templates' => ['manage-notification-templates'],
            
            // If user needs specific group permissions but has general group access
            'create-notification-groups' => ['manage-notification-groups'],
            'edit-notification-groups' => ['manage-notification-groups'],
            'delete-notification-groups' => ['manage-notification-groups'],
            'manage-group-members' => ['manage-notification-groups'],
            
            // If user needs specific API permissions but has general API access
            'create-api-keys' => ['manage-api-keys'],
            'edit-api-keys' => ['manage-api-keys'],
            'delete-api-keys' => ['manage-api-keys'],
            'regenerate-api-keys' => ['manage-api-keys'],
        ];

        foreach ($requiredPermissions as $permission) {
            if (isset($fallbackMap[$permission])) {
                foreach ($fallbackMap[$permission] as $fallbackPermission) {
                    if ($user->can($fallbackPermission)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}

/**
 * Middleware specifically for checking if user can view their own notifications
 */
class ViewOwnNotificationsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user can view their own notifications
        if (!$user->can('view-received-notifications')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'ไม่มีสิทธิ์ดูการแจ้งเตือน'], 403);
            }
            abort(403, 'ไม่มีสิทธิ์ดูการแจ้งเตือน');
        }

        return $next($request);
    }
}

/**
 * Middleware for checking notification ownership
 */
class NotificationOwnershipMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Get notification log from route parameter
        $notificationLogId = $request->route('notificationLog') ?? $request->route('notification_log');
        
        if ($notificationLogId) {
            $notificationLog = \App\Models\NotificationLog::find($notificationLogId);
            
            if (!$notificationLog) {
                abort(404, 'ไม่พบการแจ้งเตือน');
            }

            // Check if user owns this notification or has admin permissions
            $canAccess = ($notificationLog->user_id === $user->id) || 
                        ($notificationLog->recipient_email === $user->email) ||
                        ($user->can('view-all-notifications'));

            if (!$canAccess) {
                \Log::warning('Unauthorized notification access attempt', [
                    'user_id' => $user->id,
                    'notification_log_id' => $notificationLog->id,
                    'notification_owner_id' => $notificationLog->user_id,
                    'notification_email' => $notificationLog->recipient_email
                ]);

                if ($request->expectsJson()) {
                    return response()->json(['error' => 'ไม่มีสิทธิ์เข้าถึงการแจ้งเตือนนี้'], 403);
                }
                abort(403, 'ไม่มีสิทธิ์เข้าถึงการแจ้งเตือนนี้');
            }
        }

        return $next($request);
    }
}

/**
 * Middleware for API rate limiting based on permissions
 */
class NotificationApiRateLimitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        
        // Different rate limits based on user permissions
        $rateLimitKey = 'notification_api_' . $user->id;
        $maxRequests = $this->getMaxRequests($user);
        $timeWindow = 60; // 1 minute

        $currentRequests = \Cache::get($rateLimitKey, 0);

        if ($currentRequests >= $maxRequests) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'max_requests' => $maxRequests,
                'time_window' => $timeWindow,
                'retry_after' => $timeWindow
            ], 429);
        }

        \Cache::put($rateLimitKey, $currentRequests + 1, $timeWindow);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $maxRequests);
        $response->headers->set('X-RateLimit-Remaining', max(0, $maxRequests - $currentRequests - 1));
        $response->headers->set('X-RateLimit-Reset', time() + $timeWindow);

        return $response;
    }

    private function getMaxRequests($user): int
    {
        if ($user->can('manage-notifications')) {
            return 1000; // Admin users get higher limits
        }

        if ($user->can('create-notifications')) {
            return 500; // Users who can create notifications
        }

        return 100; // Basic users
    }
}