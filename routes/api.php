<?php

// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TemplateController;
use App\Http\Controllers\Api\V1\DeliveryController;
use App\Http\Controllers\Api\V1\LdapController;
use App\Http\Controllers\Api\V1\SystemController;
use App\Http\Controllers\Api\V1\WebhookController;
use App\Http\Middleware\ApiKeyMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Controllers\Admin\ApiKeyController;

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
Route::prefix('v1')->group(function () {
    
    // API Health Check
    Route::get('/health', [SystemController::class, 'health'])->name('api.health');

    // API Documentation endpoint
    Route::get('/docs', function () {
        return response()->json([
            'success' => true,
            'message' => 'Smart Notification System API Documentation',
            'version' => '1.0.0',
            'base_url' => url('/api/v1'),
            'authentication' => [
                'method' => 'API Key',
                'header' => 'X-API-Key: your-api-key-here',
                'alternative' => 'Authorization: Bearer your-api-key-here'
            ],
            'rate_limits' => [
                'default' => '60 requests per minute',
                'bulk' => '20 notifications per request',
                'configurable' => 'Per API key settings'
            ],
            'endpoints' => [
                'notifications' => [
                    'POST /notifications/send' => 'Send single notification',
                    'POST /notifications/bulk' => 'Send bulk notifications',
                    'POST /notifications/schedule' => 'Schedule notification',
                    'GET /notifications/{id}/status' => 'Get notification status',
                    'GET /notifications/history' => 'Get notification history',
                    'DELETE /notifications/{id}/cancel' => 'Cancel scheduled notification',
                    'POST /notifications/{id}/retry' => 'Retry failed notification'
                ],
                'users' => [
                    'GET /users' => 'List users from LDAP',
                    'GET /users/search' => 'Search users',
                    'GET /users/{id}' => 'Get user details',
                    'GET /users/{id}/preferences' => 'Get user preferences',
                    'PUT /users/{id}/preferences' => 'Update user preferences'
                ],
                'groups' => [
                    'GET /groups' => 'List notification groups',
                    'POST /groups' => 'Create notification group',
                    'GET /groups/{id}' => 'Get group details',
                    'PUT /groups/{id}' => 'Update notification group',
                    'DELETE /groups/{id}' => 'Delete notification group',
                    'GET /groups/{id}/members' => 'Get group members',
                    'POST /groups/{id}/members' => 'Add group members',
                    'DELETE /groups/{id}/members' => 'Remove group members'
                ],
                'templates' => [
                    'GET /templates' => 'List notification templates',
                    'POST /templates' => 'Create notification template',
                    'GET /templates/{id}' => 'Get template details',
                    'PUT /templates/{id}' => 'Update notification template',
                    'DELETE /templates/{id}' => 'Delete notification template',
                    'POST /templates/{id}/render' => 'Render template with data'
                ]
            ]
        ]);
    })->name('api.docs');

    // API Status endpoint
    Route::get('/status', [SystemController::class, 'status'])->name('api.status');
});

// ===========================================
// PROTECTED API ENDPOINTS (Require API Key)
// ===========================================
Route::prefix('v1')->middleware([ApiKeyMiddleware::class, RateLimitMiddleware::class])->group(function () {
    
    // ===========================================
    // AUTHENTICATION & VALIDATION
    // ===========================================
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('/validate', [AuthController::class, 'validateKey'])->name('validate');
        Route::get('/info', [AuthController::class, 'getApiKeyInfo'])->name('info');
    });

    // ===========================================
    // NOTIFICATION MANAGEMENT
    // ===========================================
    Route::prefix('notifications')->name('api.notifications.')->controller(NotificationController::class)->group(function () {
        
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
    });

    // ===========================================
    // USER MANAGEMENT
    // ===========================================
    Route::prefix('users')->name('api.users.')->controller(UserController::class)->group(function () {
        
        // User listing and search
        Route::get('/', 'index')->name('index');
        Route::get('/search', 'search')->name('search');
        Route::get('/active', 'getActive')->name('active');
        Route::get('/departments', 'getDepartments')->name('departments');
        
        // User details
        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/details', 'getDetails')->name('details');
        
        // User preferences
        Route::get('/{id}/preferences', 'getPreferences')->name('preferences');
        Route::put('/{id}/preferences', 'updatePreferences')->name('preferences.update');
        Route::delete('/{id}/preferences', 'resetPreferences')->name('preferences.reset');
        
        // User groups and roles
        Route::get('/{id}/groups', 'getUserGroups')->name('groups');
        Route::get('/{id}/permissions', 'getUserPermissions')->name('permissions');
        
        // Bulk operations
        Route::post('/bulk/validate', 'bulkValidate')->name('bulk.validate');
        Route::post('/bulk/preferences', 'bulkUpdatePreferences')->name('bulk.preferences');
        
        // Statistics
        Route::get('/stats/overview', 'getOverviewStats')->name('stats.overview');
        Route::get('/stats/by-department', 'getStatsByDepartment')->name('stats.department');
    });

    // ===========================================
    // GROUP MANAGEMENT
    // ===========================================
    Route::prefix('groups')->name('api.groups.')->controller(GroupController::class)->group(function () {
        
        // Group CRUD operations
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        
        // Group member management
        Route::get('/{id}/members', 'getMembers')->name('members');
        Route::post('/{id}/members', 'addMembers')->name('members.add');
        Route::delete('/{id}/members', 'removeMembers')->name('members.remove');
        Route::put('/{id}/members/sync', 'syncMembers')->name('members.sync');
        
        // Group operations
        Route::post('/{id}/duplicate', 'duplicate')->name('duplicate');
        Route::get('/{id}/stats', 'getGroupStats')->name('stats');
        Route::post('/preview-members', 'previewMembers')->name('preview-members');
        
        // Bulk operations
        Route::post('/bulk/create', 'bulkCreate')->name('bulk.create');
        Route::post('/bulk/update', 'bulkUpdate')->name('bulk.update');
        Route::delete('/bulk/delete', 'bulkDelete')->name('bulk.delete');
        
        // Group statistics
        Route::get('/stats/overview', 'getStats')->name('overview.stats');
        Route::get('/stats/usage', 'getUsageStats')->name('usage.stats');
    });

    // ===========================================
    // TEMPLATE MANAGEMENT
    // ===========================================
    Route::prefix('templates')->name('api.templates.')->controller(TemplateController::class)->group(function () {
        
        // Template CRUD operations
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        
        // Template metadata
        Route::get('/categories', 'getCategories')->name('categories');
        Route::get('/{id}/variables', 'getVariables')->name('variables');
        Route::get('/{id}/usage-stats', 'getUsageStats')->name('usage-stats');
        
        // Template operations
        Route::post('/{id}/render', 'render')->name('render');
        Route::post('/{id}/send', 'sendNotification')->name('send');
        Route::post('/{id}/duplicate', 'duplicate')->name('duplicate');
        Route::post('/{id}/test', 'test')->name('test');
        
        // Template validation and preview
        Route::post('/validate', 'validate')->name('validate');
        Route::post('/preview', 'preview')->name('preview');
        Route::post('/{id}/preview/{channel}', 'previewChannel')->name('preview.channel');
        
        // Bulk operations
        Route::post('/bulk/validate', 'bulkValidate')->name('bulk.validate');
        Route::post('/bulk/update', 'bulkUpdate')->name('bulk.update');
        Route::delete('/bulk/delete', 'bulkDelete')->name('bulk.delete');
        
        // Import/Export
        Route::get('/export/{format}', 'export')->name('export');
        Route::post('/import', 'import')->name('import');
    });

    // ===========================================
    // DELIVERY & STATUS TRACKING
    // ===========================================
    Route::prefix('delivery')->name('api.delivery.')->controller(DeliveryController::class)->group(function () {
        
        // Delivery statistics
        Route::get('/stats', 'getDeliveryStats')->name('stats');
        Route::get('/stats/summary', 'getSummaryStats')->name('stats.summary');
        Route::get('/stats/by-channel', 'getStatsByChannel')->name('stats.channel');
        Route::get('/stats/by-date', 'getStatsByDate')->name('stats.date');
        
        // Failed delivery management
        Route::get('/failed', 'getFailedDeliveries')->name('failed');
        Route::get('/failed/{id}/details', 'getFailureDetails')->name('failure-details');
        Route::post('/failed/retry', 'retryFailed')->name('retry-failed');
        Route::post('/failed/bulk-retry', 'bulkRetryFailed')->name('bulk-retry-failed');
        
        // Delivery tracking
        Route::get('/tracking/{id}', 'getTrackingInfo')->name('tracking');
        Route::get('/logs', 'getDeliveryLogs')->name('logs');
        Route::get('/logs/export', 'exportLogs')->name('logs.export');
        
        // Real-time status
        Route::get('/status/live', 'getLiveStatus')->name('status.live');
        Route::get('/queue/status', 'getQueueStatus')->name('queue.status');
    });

    // ===========================================
    // LDAP INTEGRATION
    // ===========================================
    Route::prefix('ldap')->name('api.ldap.')->controller(LdapController::class)->group(function () {
        
        // LDAP user operations
        Route::get('/users', 'getUsers')->name('users');
        Route::get('/users/search', 'searchUsers')->name('users.search');
        Route::get('/users/{username}', 'getUser')->name('user');
        
        // Organization structure
        Route::get('/departments', 'getDepartments')->name('departments');
        Route::get('/departments/{dept}/users', 'getDepartmentUsers')->name('dept-users');
        Route::get('/structure', 'getOrganizationStructure')->name('structure');
        
        // LDAP synchronization
        Route::post('/sync', 'sync')->name('sync');
        Route::post('/sync/manual', 'manualSync')->name('manual-sync');
        Route::get('/sync/status', 'getSyncStatus')->name('sync-status');
        Route::get('/sync/history', 'getSyncHistory')->name('sync-history');
        
        // LDAP health and testing
        Route::get('/health', 'checkHealth')->name('health');
        Route::post('/test-connection', 'testConnection')->name('test-connection');
        Route::get('/stats', 'getStats')->name('stats');
    });

    // ===========================================
    // SYSTEM ADMINISTRATION
    // ===========================================
    Route::prefix('system')->name('api.system.')->controller(SystemController::class)->group(function () {
        
        // System health monitoring
        Route::get('/health/detailed', 'detailedHealth')->name('health.detailed');
        Route::get('/health/components', 'checkComponents')->name('health.components');
        
        // Queue management
        Route::get('/queue/status', 'getQueueStatus')->name('queue.status');
        Route::get('/queue/stats', 'getQueueStats')->name('queue.stats');
        Route::post('/queue/clear', 'clearQueue')->name('queue.clear');
        
        // External services status
        Route::get('/services/status', 'getServicesStatus')->name('services.status');
        Route::post('/services/test', 'testServices')->name('services.test');
        
        // System metrics
        Route::get('/metrics', 'getMetrics')->name('metrics');
        Route::get('/performance', 'getPerformanceMetrics')->name('performance');
        Route::get('/usage-stats', 'getUsageStats')->name('usage-stats');
    });

    // ===========================================
    // API USAGE & ANALYTICS
    // ===========================================
    Route::prefix('analytics')->name('api.analytics.')->group(function () {
        
        // Current API key usage
        Route::get('/usage', function () {
            $apiKey = request()->apiKey;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'api_key_name' => $apiKey->name,
                    'usage_count' => $apiKey->usage_count,
                    'rate_limit' => $apiKey->rate_limit_per_minute,
                    'requests_remaining' => $apiKey->rate_limit_per_minute - ($apiKey->usage_count_current_minute ?? 0),
                    'last_used_at' => $apiKey->last_used_at,
                    'created_at' => $apiKey->created_at,
                    'expires_at' => $apiKey->expires_at,
                    'status' => $apiKey->is_active ? 'active' : 'inactive'
                ]
            ]);
        })->name('usage');
        
        // API usage history
        Route::get('/history', [ApiKeyController::class, 'getUsageHistory'])->name('history');
        Route::get('/stats/daily', [ApiKeyController::class, 'getDailyStats'])->name('stats.daily');
        Route::get('/stats/monthly', [ApiKeyController::class, 'getMonthlyStats'])->name('stats.monthly');
        
        // Rate limiting info
        Route::get('/rate-limits', function () {
            $apiKey = request()->apiKey;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'rate_limit_per_minute' => $apiKey->rate_limit_per_minute,
                    'current_usage' => $apiKey->usage_count_current_minute ?? 0,
                    'remaining' => $apiKey->rate_limit_per_minute - ($apiKey->usage_count_current_minute ?? 0),
                    'reset_time' => now()->addMinute()->startOfMinute(),
                    'retry_after' => $apiKey->rate_limit_exceeded ? 60 : null
                ]
            ]);
        })->name('rate-limits');
    });

    // ===========================================
    // DEVELOPMENT & TESTING ENDPOINTS
    // ===========================================
    Route::prefix('test')->name('api.test.')->group(function () {
        
        // Test API key authentication
        Route::get('/auth', function () {
            $apiKey = request()->apiKey;
            
            return response()->json([
                'success' => true,
                'message' => 'API key authentication successful',
                'api_key_info' => [
                    'id' => $apiKey->id,
                    'name' => $apiKey->name,
                    'permissions' => $apiKey->permissions ?? [],
                    'rate_limit' => $apiKey->rate_limit_per_minute,
                    'last_used' => $apiKey->last_used_at,
                    'expires_at' => $apiKey->expires_at
                ],
                'request_info' => [
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'timestamp' => now()->toISOString()
                ]
            ]);
        })->name('auth');
        
        // Test notification (dry run)
        Route::post('/notification', function () {
            $apiKey = request()->apiKey;
            
            $result = [
                'success' => true,
                'message' => 'Test notification validated (dry run)',
                'test_data' => request()->all(),
                'api_key' => $apiKey->name,
                'validation_results' => [
                    'recipients_valid' => true,
                    'template_valid' => true,
                    'channels_available' => ['email', 'teams'],
                    'estimated_delivery_time' => now()->addMinutes(2)->toISOString()
                ],
                'timestamp' => now()->toISOString()
            ];
            
            \Log::info('API test notification', [
                'api_key' => $apiKey->name,
                'data' => request()->all(),
                'ip' => request()->ip()
            ]);
            
            return response()->json($result);
        })->name('notification');
        
        // Test system connectivity
        Route::get('/connectivity', [SystemController::class, 'testConnectivity'])->name('connectivity');
        
        // Echo endpoint for debugging
        Route::post('/echo', function () {
            return response()->json([
                'success' => true,
                'echo' => request()->all(),
                'headers' => request()->headers->all(),
                'timestamp' => now()->toISOString()
            ]);
        })->name('echo');
    });
});

// ===========================================
// WEBHOOK ENDPOINTS (External Services)
// ===========================================
Route::prefix('webhooks')->name('api.webhooks.')->middleware(['webhook.auth'])->group(function () {
    
    // Microsoft Teams webhooks
    Route::post('/teams/message-sent', [WebhookController::class, 'teamsMessageSent'])->name('teams.sent');
    Route::post('/teams/message-delivered', [WebhookController::class, 'teamsMessageDelivered'])->name('teams.delivered');
    Route::post('/teams/message-failed', [WebhookController::class, 'teamsMessageFailed'])->name('teams.failed');
    
    // Email webhooks
    Route::post('/email/delivered', [WebhookController::class, 'emailDelivered'])->name('email.delivered');
    Route::post('/email/bounced', [WebhookController::class, 'emailBounced'])->name('email.bounced');
    Route::post('/email/opened', [WebhookController::class, 'emailOpened'])->name('email.opened');
    Route::post('/email/clicked', [WebhookController::class, 'emailClicked'])->name('email.clicked');
    
    // LDAP webhooks (if supported by LDAP system)
    Route::post('/ldap/user-updated', [WebhookController::class, 'ldapUserUpdated'])->name('ldap.user-updated');
    Route::post('/ldap/user-deleted', [WebhookController::class, 'ldapUserDeleted'])->name('ldap.user-deleted');
});

// ===========================================
// API VERSION 2 (Future Expansion)
// ===========================================
Route::prefix('v2')->name('api.v2.')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API v2 is under development',
            'version' => '2.0.0-beta',
            'available_endpoints' => []
        ]);
    })->name('health');
});

// ===========================================
// API ERROR HANDLING & FALLBACKS
// ===========================================

// Rate limit exceeded response
Route::get('/rate-limit-exceeded', function () {
    return response()->json([
        'success' => false,
        'message' => 'Rate limit exceeded',
        'error_code' => 'RATE_LIMIT_EXCEEDED',
        'retry_after' => 60,
        'timestamp' => now()->toISOString()
    ], 429);
})->name('api.rate-limit-exceeded');

// API key invalid response
Route::get('/unauthorized', function () {
    return response()->json([
        'success' => false,
        'message' => 'Invalid or missing API key',
        'error_code' => 'UNAUTHORIZED',
        'timestamp' => now()->toISOString()
    ], 401);
})->name('api.unauthorized');

// API endpoint not found fallback
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error_code' => 'ENDPOINT_NOT_FOUND',
        'available_versions' => ['v1', 'v2'],
        'documentation' => url('/api/v1/docs'),
        'timestamp' => now()->toISOString()
    ], 404);
})->name('api.not-found');