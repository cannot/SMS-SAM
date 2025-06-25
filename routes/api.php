<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\UserController as V1UserController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TemplateController;
use App\Http\Controllers\Api\V1\DeliveryController;
use App\Http\Controllers\Api\V1\LdapController;
use App\Http\Controllers\Api\V1\SystemController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\RateLimitMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ===========================================
// PUBLIC API ENDPOINTS (No Authentication)
// ===========================================

// API Health Check
Route::get('/health', [SystemController::class, 'health'])->name('api.health');

// API Documentation
Route::get('/docs', function () {
    return response()->json([
        'success' => true,
        'message' => 'Smart Notification System API Documentation',
        'version' => '1.0.0',
        'base_url' => url('/api'),
        'authentication' => [
            'web' => 'Session-based (CSRF token required)',
            'api_key' => 'X-API-Key header',
            'sanctum' => 'Bearer token'
        ],
        'endpoints' => [
            'v1' => '/api/v1/* - External API with API Key authentication',
            'dashboard' => '/api/dashboard/* - Dashboard widgets (auth required)',
            'admin' => '/api/admin/* - Admin functions (admin permission required)',
            'user' => '/api/user/* - User functions (auth required)',
            'notifications' => '/api/notifications/* - Notification functions (auth required)'
        ]
    ]);
})->name('api.docs');

// ===========================================
// QUICK FIX ROUTES (แก้ 404 errors)
// ===========================================
Route::middleware(['auth', 'web'])->group(function () {
    
    // Dashboard quick stats - เรียกจาก JavaScript
    Route::get('/dashboard/quick-stats', function () {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_users' => \App\Models\User::count(),
                    'active_users' => \App\Models\User::where('is_active', true)->count(),
                    'total_notifications' => \App\Models\Notification::count(),
                    'notifications_today' => \App\Models\Notification::whereDate('created_at', today())->count(),
                    'active_api_keys' => \App\Models\ApiKey::where('is_active', true)->count(),
                    'sent_notifications' => \App\Models\Notification::where('status', 'sent')->count(),
                    'failed_notifications' => \App\Models\Notification::where('status', 'failed')->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Failed to load stats'], 500);
        }
    })->name('api.dashboard.quick-stats');

    // Admin stats
    Route::get('/admin/stats', function () {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'users' => [
                        'total' => \App\Models\User::count(),
                        'active' => \App\Models\User::where('is_active', true)->count(),
                        'new_this_week' => \App\Models\User::where('created_at', '>=', now()->startOfWeek())->count(),
                    ],
                    'notifications' => [
                        'total' => \App\Models\Notification::count(),
                        'sent' => \App\Models\Notification::where('status', 'sent')->count(),
                        'failed' => \App\Models\Notification::where('status', 'failed')->count(),
                        'today' => \App\Models\Notification::whereDate('created_at', today())->count(),
                    ],
                    'api_keys' => [
                        'total' => \App\Models\ApiKey::count(),
                        'active' => \App\Models\ApiKey::where('is_active', true)->count(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Failed to load admin stats'], 500);
        }
    })->name('api.admin.stats')->middleware('can:manage-system');

    // User stats  
    Route::get('/user/stats', [UserController::class, 'getUserStats'])->name('api.user.stats');

    // Unread notifications count
    Route::get('/notifications/unread-count', [UserController::class, 'getUnreadNotifications'])->name('api.notifications.unread-count');
});

// ===========================================
// DASHBOARD API ENDPOINTS
// ===========================================
Route::middleware(['auth', 'web'])->prefix('dashboard')->name('api.dashboard.')->group(function () {
    Route::get('/chart-data', [DashboardController::class, 'chartData'])->name('chart-data');
    Route::get('/recent-activities', [DashboardController::class, 'recentActivities'])->name('recent-activities');
    Route::get('/widget/{type}', [DashboardController::class, 'widget'])->name('widget');
});

// ===========================================
// ADMIN API ENDPOINTS
// ===========================================
Route::middleware(['auth', 'can:manage-system'])->prefix('admin')->name('api.admin.')->group(function () {
    Route::get('/system-info', [AdminController::class, 'systemInfo'])->name('system-info');
    Route::get('/performance', [AdminController::class, 'performance'])->name('performance');
    Route::post('/cache/clear', [AdminController::class, 'clearCache'])->name('cache.clear');
    Route::post('/queue/restart', [AdminController::class, 'restartQueue'])->name('queue.restart');
});

// ===========================================
// USER API ENDPOINTS
// ===========================================
Route::middleware(['auth', 'web'])->prefix('user')->name('api.user.')->group(function () {
    Route::get('/profile', [UserController::class, 'getProfile'])->name('profile');
    Route::get('/groups', [UserController::class, 'getGroups'])->name('groups');
    Route::put('/preferences', [UserController::class, 'updatePreferences'])->name('preferences.update');
    Route::get('/notifications/unread', [UserController::class, 'getUnreadNotifications'])->name('notifications.unread');
    Route::get('/search', [UserController::class, 'search'])->name('search');
});

// ===========================================
// NOTIFICATIONS API ENDPOINTS (Simple)
// ===========================================
Route::middleware(['auth', 'web'])->prefix('notifications')->name('api.notifications.')->group(function () {
    Route::get('/user-stats', [UserController::class, 'getUserStats'])->name('user-stats');
    Route::post('/{id}/mark-read', function ($id) {
        try {
            $log = \App\Models\NotificationLog::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();
            $log->update(['read_at' => now()]);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Failed to mark as read'], 500);
        }
    })->name('mark-read');
});

// ===========================================
// V1 API ROUTES (External API with API Key)
// ===========================================
Route::prefix('v1')->middleware([ApiKeyMiddleware::class, RateLimitMiddleware::class])->group(function () {
    
    // Authentication
    Route::prefix('auth')->name('api.v1.auth.')->group(function () {
        Route::post('/validate', [AuthController::class, 'validateKey'])->name('validate');
        Route::get('/info', [AuthController::class, 'getApiKeyInfo'])->name('info');
    });

    // Notification Management
    Route::prefix('notifications')->name('api.v1.notifications.')->controller(NotificationController::class)->group(function () {
        
        // Core notification operations
        Route::get('/', 'index')->name('index');
        Route::post('/send', 'send')->name('send');
        Route::post('/bulk', 'sendBulk')->name('bulk');
        Route::post('/schedule', 'schedule')->name('schedule');
        
        // Notification status and management
        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/status', 'getStatus')->name('status');
        Route::delete('/{id}/cancel', 'cancel')->name('cancel');
        Route::post('/{id}/retry', 'retry')->name('retry');
        
        // History and tracking
        Route::get('/history', 'getHistory')->name('history');
        Route::get('/failed', 'getFailed')->name('failed');
        Route::get('/scheduled', 'getScheduled')->name('scheduled');
        Route::get('/stats', 'getStats')->name('stats');
        
        // Bulk operations
        Route::post('/bulk/cancel', 'bulkCancel')->name('bulk.cancel');
        Route::post('/bulk/retry', 'bulkRetry')->name('bulk.retry');
        Route::post('/bulk/status', 'getBulkStatus')->name('bulk.status');
        
        // Advanced operations
        Route::post('/preview', 'preview')->name('preview');
        Route::post('/validate', 'validate')->name('validate');
        Route::get('/unread-count', 'getUnreadCount')->name('unread-count');
    });

    // User Management
    Route::prefix('users')->name('api.v1.users.')->controller(V1UserController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/search', 'search')->name('search');
        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/preferences', 'getPreferences')->name('preferences');
        Route::put('/{id}/preferences', 'updatePreferences')->name('preferences.update');
        Route::get('/{id}/groups', 'getUserGroups')->name('groups');
        Route::post('/{id}/sync', 'syncFromLdap')->name('sync');
    });

    // Group Management
    Route::prefix('groups')->name('api.v1.groups.')->controller(GroupController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::get('/{id}/members', 'getMembers')->name('members');
        Route::post('/{id}/members', 'addMember')->name('members.add');
        Route::delete('/{id}/members/{userId}', 'removeMember')->name('members.remove');
        Route::post('/{id}/sync', 'syncMembers')->name('sync');
    });

    // Template Management
    Route::prefix('templates')->name('api.v1.templates.')->controller(TemplateController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/{id}/render', 'render')->name('render');
        Route::post('/{id}/send', 'sendNotification')->name('send');
        Route::post('/{id}/preview', 'preview')->name('preview');
    });

    // Delivery Tracking
    Route::prefix('delivery')->name('api.v1.delivery.')->controller(DeliveryController::class)->group(function () {
        Route::get('/status/{id}', 'getStatus')->name('status');
        Route::get('/logs/{id}', 'getLogs')->name('logs');
        Route::post('/webhook', 'handleWebhook')->name('webhook');
        Route::get('/stats', 'getStats')->name('stats');
    });

    // LDAP Integration
    Route::prefix('ldap')->name('api.v1.ldap.')->controller(LdapController::class)->group(function () {
        Route::get('/users', 'getUsers')->name('users');
        Route::get('/groups', 'getGroups')->name('groups');
        Route::post('/sync', 'syncUsers')->name('sync');
        Route::get('/test', 'testConnection')->name('test');
    });

    // System Information
    Route::prefix('system')->name('api.v1.system.')->controller(SystemController::class)->group(function () {
        Route::get('/health', 'health')->name('health');
        Route::get('/info', 'getInfo')->name('info');
        Route::get('/stats', 'getStats')->name('stats');
        Route::get('/queue/status', 'getQueueStatus')->name('queue.status');
    });

    // Webhooks
    Route::prefix('webhooks')->name('api.v1.webhooks.')->controller(WebhookController::class)->group(function () {
        Route::post('/teams', 'handleTeamsWebhook')->name('teams');
        Route::post('/email', 'handleEmailWebhook')->name('email');
        Route::post('/delivery', 'handleDeliveryWebhook')->name('delivery');
    });
});

// ===========================================
// AJAX ROUTES (for Web Interface)
// ===========================================
Route::middleware(['auth', 'web'])->prefix('ajax')->name('api.ajax.')->group(function () {
    
    // User search and autocomplete
    Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
    
    // Group search
    Route::get('/groups/search', function(Request $request) {
        $search = $request->get('q', '');
        $groups = \App\Models\NotificationGroup::where('is_active', true)
            ->where('name', 'like', "%{$search}%")
            ->limit(10)
            ->get(['id', 'name', 'description', 'type']);
        return response()->json(['success' => true, 'data' => $groups]);
    })->name('groups.search');
    
    // Template search
    Route::get('/templates/search', function(Request $request) {
        $search = $request->get('q', '');
        $templates = \App\Models\NotificationTemplate::where('is_active', true)
            ->where('name', 'like', "%{$search}%")
            ->limit(10)
            ->get(['id', 'name', 'category']);
        return response()->json(['success' => true, 'data' => $templates]);
    })->name('templates.search');
});

// ===========================================
// FALLBACK ERROR HANDLING
// ===========================================
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'available_endpoints' => [
            'GET /api/docs' => 'API Documentation',
            'GET /api/health' => 'Health Check',
            'GET /api/v1/*' => 'External API (requires API key)',
            'GET /api/dashboard/*' => 'Dashboard widgets (requires auth)',
            'GET /api/user/*' => 'User functions (requires auth)',
            'GET /api/admin/*' => 'Admin functions (requires admin permission)',
        ]
    ], 404);
});