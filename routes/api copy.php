<?php

// routes/api.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Middleware\ApiKeyMiddleware;
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

// Public API endpoints (no authentication required)
Route::prefix('v1')->group(function () {
    
    // API Health Check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'service' => 'Smart Notification System API',
            'version' => '1.0.0',
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'server_time' => now()->format('Y-m-d H:i:s T'),
            'environment' => app()->environment()
        ]);
    });

    // API Documentation endpoint
    Route::get('/docs', function () {
        return response()->json([
            'success' => true,
            'message' => 'Smart Notification System API Documentation',
            'version' => '1.0.0',
            'endpoints' => [
                'POST /api/v1/notifications/send' => 'Send single notification',
                'POST /api/v1/notifications/bulk' => 'Send bulk notifications',
                'GET /api/v1/notifications/{id}/status' => 'Get notification status',
                'GET /api/v1/notifications/history' => 'Get notification history',
                'GET /api/v1/users' => 'List users from LDAP',
                'GET /api/v1/groups' => 'List notification groups',
                'POST /api/v1/groups' => 'Create notification group'
            ],
            'authentication' => [
                'method' => 'API Key',
                'header' => 'X-API-Key: your-api-key-here',
                'alternative' => 'Authorization: Bearer your-api-key-here'
            ],
            'rate_limits' => [
                'default' => '60 requests per minute',
                'bulk' => '20 notifications per request',
                'configurable' => 'Per API key settings'
            ]
        ]);
    });
});

// User CRUD API
Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index')->middleware('can:view-users');
    Route::post('/', [UserController::class, 'store'])->name('store')->middleware('can:create-users');
    Route::get('/{user}', [UserController::class, 'show'])->name('show')->middleware('can:view-users');
    Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('can:edit-users');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('can:delete-users');
    
    // Bulk operations
    Route::post('/bulk', [UserController::class, 'bulkAction'])->name('bulk')->middleware('can:manage-users');
    Route::post('/import', [UserController::class, 'import'])->name('import')->middleware('can:create-users');
    Route::get('/export', [UserController::class, 'export'])->name('export')->middleware('can:view-users');
    
    // User statistics
    Route::get('/stats', [UserController::class, 'getStats'])->name('stats')->middleware('can:view-reports');
});

// LDAP operations
Route::prefix('ldap')->name('ldap.')->group(function () {
    Route::post('/sync', [UserController::class, 'syncLdap'])->name('sync')->middleware('can:manage-ldap');
    Route::get('/status', [UserController::class, 'getLdapSyncStatus'])->name('status')->middleware('can:manage-ldap');
    // Route::post('/test', [LdapController::class, 'testConnection'])->name('test')->middleware('can:manage-ldap');
    // Route::get('/users/{username}', [LdapController::class, 'getUser'])->name('user')->middleware('can:manage-ldap');
});

// Protected API endpoints (require API key authentication)
Route::prefix('v1')->middleware([ApiKeyMiddleware::class])->group(function () {
    
    // Notification API endpoints
    Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
        
        // Send notifications
        Route::post('/send', 'send')->name('api.notifications.send');
        Route::post('/bulk', 'sendBulk')->name('api.notifications.bulk');
        
        // Get notification status and history
        Route::get('/{id}/status', 'getStatus')->name('api.notifications.status');
        Route::get('/history', 'getHistory')->name('api.notifications.history');
        
        // Cancel scheduled notification
        Route::delete('/{id}/cancel', 'cancel')->name('api.notifications.cancel');
        
        // Retry failed notification
        Route::post('/{id}/retry', 'retry')->name('api.notifications.retry');
    });

    // User Management API endpoints
    Route::prefix('users')->controller(UserController::class)->group(function () {
        
        // List users from LDAP
        Route::get('/', 'index')->name('api.users.index');
        Route::get('/search', 'search')->name('api.users.search');
        Route::get('/{id}', 'show')->name('api.users.show');
        
        // User preferences
        Route::get('/{id}/preferences', 'getPreferences')->name('api.users.preferences');
        Route::put('/{id}/preferences', 'updatePreferences')->name('api.users.preferences.update');
    });

    // Group Management API endpoints  
    // Route::prefix('groups')->controller(GroupController::class)->group(function () {
        
    //     // CRUD operations for notification groups
    //     Route::get('/', 'index')->name('api.groups.index');
    //     Route::post('/', 'store')->name('api.groups.store');
    //     Route::get('/{id}', 'show')->name('api.groups.show');
    //     Route::put('/{id}', 'update')->name('api.groups.update');
    //     Route::delete('/{id}', 'destroy')->name('api.groups.destroy');
        
    //     // Group members
    //     Route::get('/{id}/members', 'getMembers')->name('api.groups.members');
    //     Route::post('/{id}/members', 'addMembers')->name('api.groups.members.add');
    //     Route::delete('/{id}/members', 'removeMembers')->name('api.groups.members.remove');
    // });
    Route::prefix('groups')->controller(GroupController::class)->group(function () {
        Route::get('/stats', [GroupController::class, 'getStats'])->name('api.groups.getstats');
        Route::post('/preview-members', [GroupController::class, 'previewMembers'])->name('api.groups.previewmembers');
    });

    // Template Management API endpoints
    Route::prefix('templates')->controller(\App\Http\Controllers\Api\V1\TemplateController::class)->group(function () {
        
        // Template CRUD operations
        Route::get('/', 'index')->name('api.templates.index');
        Route::get('/categories', 'getCategories')->name('api.templates.categories');
        Route::get('/{id}', 'show')->name('api.templates.show');
        Route::get('/{id}/variables', 'getVariables')->name('api.templates.variables');
        
        // Template rendering and usage
        Route::post('/{id}/render', 'render')->name('api.templates.render');
        Route::post('/{id}/send', 'sendNotification')->name('api.templates.send');
    });

    // API Key validation endpoint
    Route::post('/auth/validate', [AuthController::class, 'validateKey'])->name('api.auth.validate');
    
    // API usage statistics
    Route::get('/stats/usage', function () {
        $apiKey = request()->apiKey;
        
        return response()->json([
            'success' => true,
            'data' => [
                'api_key_name' => $apiKey->name,
                'usage_count' => $apiKey->usage_count,
                'rate_limit' => $apiKey->rate_limit_per_minute,
                'last_used_at' => $apiKey->last_used_at,
                'created_at' => $apiKey->created_at,
                'expires_at' => $apiKey->expires_at
            ]
        ]);
    })->name('api.stats.usage');
    
    // Test endpoints for development
    Route::prefix('test')->group(function () {
        
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
                    'last_used' => $apiKey->last_used_at
                ]
            ]);
        })->name('api.test.auth');
        
        // Test notification (simplified)
        Route::post('/notification', function () {
            $apiKey = request()->apiKey;
            
            // Simple test notification
            $result = [
                'success' => true,
                'message' => 'Test notification would be sent',
                'test_data' => request()->all(),
                'api_key' => $apiKey->name,
                'timestamp' => now()->toISOString()
            ];
            
            \Log::info('API test notification', [
                'api_key' => $apiKey->name,
                'data' => request()->all()
            ]);
            
            return response()->json($result);
        })->name('api.test.notification');
    });
});

// Error handling for API routes
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'error_code' => 'ENDPOINT_NOT_FOUND',
        'timestamp' => now()->toISOString()
    ], 404);
});