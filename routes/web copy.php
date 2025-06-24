<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\NotificationTemplateController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\GroupController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\UserPreferenceController;
use App\Http\Controllers\Web\ActivityLogController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root redirect - ใส่ไว้ด้านบนสุด
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Logout routes - รองรับทั้ง GET และ POST
Route::match(['GET', 'POST'], '/logout', [AuthController::class, 'logout'])->name('logout');

// Test route ไม่ใช้ middleware
Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Application is working',
        'timestamp' => now()
    ]);
});

// Project Status
Route::prefix('projects')->group(function () {
    Route::get('/sit', function () {
        return view('projects.sit');
    });
    
    Route::get('/status', function () {
        return view('projects.status');
    });
});

Route::get('/coming-soon', function () {
    return view('coming-soon');
});

Route::middleware(['auth', 'web'])->group(function () {
    
    // ===========================================
    // ROLES MANAGEMENT ROUTES
    // ===========================================
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index')->middleware('can:view-roles');
        Route::get('/create', [RoleController::class, 'create'])->name('create')->middleware('can:create-roles');
        Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('can:create-roles');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show')->middleware('can:view-roles');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit')->middleware('can:edit-roles');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update')->middleware('can:edit-roles');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware('can:delete-roles');
        
        // Bulk operations
        Route::post('/{role}/clone', [RoleController::class, 'clone'])->name('clone')->middleware('can:create-roles');
        Route::post('/{role}/bulk-assign', [RoleController::class, 'bulkAssign'])->name('bulk-assign')->middleware('can:assign-roles');
        Route::post('/{role}/bulk-remove', [RoleController::class, 'bulkRemove'])->name('bulk-remove')->middleware('can:assign-roles');
        
        // Export
        Route::get('/export/csv', [RoleController::class, 'export'])->name('export')->middleware('can:export-roles');
    });

    // ===========================================
    // PERMISSIONS MANAGEMENT ROUTES  
    // ===========================================
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index')->middleware('can:view-permissions');
        Route::get('/create', [PermissionController::class, 'create'])->name('create')->middleware('can:create-permissions');
        Route::post('/', [PermissionController::class, 'store'])->name('store')->middleware('can:create-permissions');
        Route::get('/{permission}', [PermissionController::class, 'show'])->name('show')->middleware('can:view-permissions');
        Route::get('/{permission}/edit', [PermissionController::class, 'edit'])->name('edit')->middleware('can:edit-permissions');
        Route::put('/{permission}', [PermissionController::class, 'update'])->name('update')->middleware('can:edit-permissions');
        Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy')->middleware('can:delete-permissions');
        
        // Bulk operations
        Route::post('/bulk-create', [PermissionController::class, 'bulkCreate'])->name('bulk-create')->middleware('can:create-permissions');
        Route::post('/bulk-assign-to-roles', [PermissionController::class, 'bulkAssignToRoles'])->name('bulk-assign-to-roles')->middleware('can:assign-permissions');
        Route::delete('/bulk-delete', [PermissionController::class, 'bulkDelete'])->name('bulk-delete')->middleware('can:delete-permissions');
        
        // Matrix view
        Route::get('/matrix/view', [PermissionController::class, 'matrix'])->name('matrix')->middleware('can:view-permission-matrix'); 
        Route::post('/matrix/bulk-update', [PermissionController::class, 'matrixBulkUpdate'])->name('matrix.bulk-update')->middleware('can:edit-permission-matrix');
        
        // Export
        Route::get('/export/csv', [PermissionController::class, 'export'])->name('export')->middleware('can:export-permissions');
        
        // API for details
        Route::get('/{permission}/details', [PermissionController::class, 'getDetails'])->name('details');
    });

});
// Protected web interface routes
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Users Management
    // Users Management (working)
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/export', [UserController::class, 'export'])->name('export');
        
        // LDAP Sync routes
        Route::post('/sync-ldap', [UserController::class, 'syncLdap'])->name('sync-ldap');
        Route::get('/sync-ldap/status', [UserController::class, 'getLdapSyncStatus'])->name('sync-ldap.status');
        
        // Current user preferences (must be before {user} routes)
        Route::get('/preferences', [UserPreferenceController::class, 'show'])->name('preferences');
        Route::patch('/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');
        Route::delete('/preferences', [UserPreferenceController::class, 'reset'])->name('preferences.reset');
        Route::post('/preferences/test', [UserPreferenceController::class, 'testNotification'])->name('preferences.test');
        Route::get('/preferences/export', [UserPreferenceController::class, 'export'])->name('preferences.export');
        Route::post('/preferences/import', [UserPreferenceController::class, 'import'])->name('preferences.import');
        
        // User specific routes
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/preferences', [UserPreferenceController::class, 'show'])->name('preferences.show');
        Route::patch('/{user}/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update.user');
        Route::delete('/{user}/preferences', [UserPreferenceController::class, 'reset'])->name('preferences.reset.user');
        Route::post('/{user}/preferences/test', [UserPreferenceController::class, 'testNotification'])->name('preferences.test.user');
        Route::get('/{user}/preferences/export', [UserPreferenceController::class, 'export'])->name('preferences.export.user');
        Route::post('/{user}/preferences/import', [UserPreferenceController::class, 'import'])->name('preferences.import.user');
        
        // User management actions
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::patch('/{user}/update-roles', [UserController::class, 'updateRoles'])->name('update-roles');
        Route::post('/{user}/join-group', [UserController::class, 'joinGroup'])->name('join-group');
        Route::delete('/{user}/leave-group', [UserController::class, 'leaveGroup'])->name('leave-group');
    });

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/{id}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
    Route::get('/users/{userId}/activities', [ActivityLogController::class, 'getUserActivities'])->name('users.activities');

    // Roles Management
    // Route::prefix('roles')->name('roles.')->middleware('can:manage-users')->group(function () {
    //     Route::get('/', [RoleController::class, 'index'])->name('index');
    //     Route::get('/create', [RoleController::class, 'create'])->name('create');
    //     Route::post('/', [RoleController::class, 'store'])->name('store');
    //     Route::get('/export', [RoleController::class, 'export'])->name('export');
    //     Route::get('/{role}', [RoleController::class, 'show'])->name('show');
    //     Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
    //     Route::patch('/{role}', [RoleController::class, 'update'])->name('update');
    //     Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        
    //     // Additional role actions
    //     Route::post('/{role}/clone', [RoleController::class, 'clone'])->name('clone');
    //     Route::post('/{role}/bulk-assign', [RoleController::class, 'bulkAssign'])->name('bulk-assign');
    //     Route::post('/{role}/bulk-remove', [RoleController::class, 'bulkRemove'])->name('bulk-remove');
    // });

    // // Permissions Management
    // Route::prefix('permissions')->name('permissions.')->middleware('can:manage-users')->group(function () {
    //     Route::get('/', [PermissionController::class, 'index'])->name('index');
    //     Route::get('/create', [PermissionController::class, 'create'])->name('create');
    //     Route::post('/', [PermissionController::class, 'store'])->name('store');
    //     Route::post('/bulk-create', [PermissionController::class, 'bulkCreate'])->name('bulk-create');
    //     Route::get('/export', [PermissionController::class, 'export'])->name('export');
    //     Route::get('/{permission}', [PermissionController::class, 'show'])->name('show');
    //     Route::get('/{permission}/edit', [PermissionController::class, 'edit'])->name('edit');
    //     Route::patch('/{permission}', [PermissionController::class, 'update'])->name('update');
    //     Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');
        
    //     // Additional permission actions
    //     Route::post('/{permission}/bulk-assign-roles', [PermissionController::class, 'bulkAssignToRoles'])->name('bulk-assign-roles');
    // });
    
    // Notifications management
    // Route::prefix('notifications')->name('notifications.')->group(function () {
    //     Route::get('/', [NotificationController::class, 'index'])->name('index');
    //     Route::get('/create', [NotificationController::class, 'create'])->name('create');
    //     Route::post('/', [NotificationController::class, 'store'])->name('store');
    //     Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
    //     Route::get('/{notification}/edit', [NotificationController::class, 'edit'])->name('edit');
    //     Route::put('/{notification}', [NotificationController::class, 'update'])->name('update');
    //     Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    // });
    
    // Group management
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::get('/create', [GroupController::class, 'create'])->name('create');
        Route::post('/', [GroupController::class, 'store'])->name('store');
        Route::get('/{group}', [GroupController::class, 'show'])->name('show');
        Route::get('/{group}/edit', [GroupController::class, 'edit'])->name('edit');
        Route::put('/{group}', [GroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy');
    });
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/delivery', [ReportController::class, 'delivery'])->name('delivery');
        Route::get('/api-usage', [ReportController::class, 'apiUsage'])->name('api-usage');
        Route::get('/user-activity', [ReportController::class, 'userActivity'])->name('user-activity');
        Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
    });

    // Notification templates management
    // Route::prefix('templates')->name('templates.')->group(function () {
    //     Route::get('/', [NotificationTemplateController::class, 'index'])->name('index');
    //     Route::get('/create', [NotificationTemplateController::class, 'create'])->name('create');
    //     Route::post('/', [NotificationTemplateController::class, 'store'])->name('store');
    //     Route::get('/{template}', [NotificationTemplateController::class, 'show'])->name('show');
    //     Route::get('/{template}/edit', [NotificationTemplateController::class, 'edit'])->name('edit');
    //     Route::put('/{template}', [NotificationTemplateController::class, 'update'])->name('update');
    //     Route::delete('/{template}', [NotificationTemplateController::class, 'destroy'])->name('destroy');
        
    //     // Additional routes
    //     Route::post('/{template}/toggle', [NotificationTemplateController::class, 'toggle'])->name('toggle');
    //     Route::post('/{template}/duplicate', [NotificationTemplateController::class, 'duplicate'])->name('duplicate');
    //     Route::post('/{template}/preview', [NotificationTemplateController::class, 'preview'])->name('preview');
    // });
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [NotificationTemplateController::class, 'index'])->name('index');
        Route::get('/create', [NotificationTemplateController::class, 'create'])->name('create');
        Route::post('/', [NotificationTemplateController::class, 'store'])->name('store');
        Route::get('/{template}', [NotificationTemplateController::class, 'show'])->name('show');
        Route::get('/{template}/edit', [NotificationTemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [NotificationTemplateController::class, 'update'])->name('update');
        Route::delete('/{template}', [NotificationTemplateController::class, 'destroy'])->name('destroy');
        
        // Additional routes for the view
        Route::get('/{template}/preview', [NotificationTemplateController::class, 'preview'])->name('preview');
        Route::get('/{template}/duplicate', [NotificationTemplateController::class, 'duplicate'])->name('duplicate');
        Route::post('/{template}/toggle-status', [NotificationTemplateController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/export/{format}', [NotificationTemplateController::class, 'export'])->name('export');
        Route::post('/bulk-action', [NotificationTemplateController::class, 'bulkAction'])->name('bulk-action');
    });

    // API Key management (Admin only)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/api-keys', function () {
            return "API Keys management - Coming soon!";
        })->name('api-keys.index');
        
        Route::get('/api-keys/create', function () {
            return "Create API Key - Coming soon!";
        })->name('api-keys.create');
        
        Route::get('/system-logs', function () {
            return "System logs - Coming soon!";
        })->name('system-logs');
    });

});