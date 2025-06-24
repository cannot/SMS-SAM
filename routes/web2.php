<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\NotificationTemplateController;
// use App\Http\Controllers\Web\NotificationLogController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\GroupController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\UserPreferenceController;
use App\Http\Controllers\Web\ActivityLogController;
// use App\Http\Controllers\Web\SystemController;
// use App\Http\Controllers\Web\ApiAnalyticsController;
// use App\Http\Controllers\Web\QueueController;
// use App\Http\Controllers\Admin\ApiKeyController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ===========================================
// PUBLIC ROUTES (No Authentication Required)
// ===========================================

// Root redirect
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Logout routes - support both GET and POST
Route::match(['GET', 'POST'], '/logout', [AuthController::class, 'logout'])->name('logout');

// System status (public)
// Route::get('/status', [SystemController::class, 'publicStatus'])->name('system.public-status');

// Test route (no middleware)
Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Application is working',
        'timestamp' => now(),
        'environment' => app()->environment()
    ]);
})->name('test');

// Project status pages
Route::prefix('projects')->name('projects.')->group(function () {
    Route::get('/sit', function () {
        return view('projects.sit');
    })->name('sit');
    
    Route::get('/status', function () {
        return view('projects.status');
    })->name('status');
});

// Coming soon placeholder
Route::get('/coming-soon', function () {
    return view('coming-soon');
})->name('coming-soon');

// ===========================================
// PROTECTED WEB INTERFACE ROUTES
// ===========================================
Route::middleware(['auth', 'web'])->group(function () {
    
    // ===========================================
    // DASHBOARD
    // ===========================================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getDashboardData'])->name('dashboard.data');
    Route::get('/dashboard/widgets/{widget}', [DashboardController::class, 'getWidget'])->name('dashboard.widget');
    
    // ===========================================
    // USER MANAGEMENT ROUTES
    // ===========================================
    Route::prefix('users')->name('users.')->group(function () {
        
        // Basic CRUD Routes
        Route::get('/', [UserController::class, 'index'])->name('index')->middleware('can:view-users');
        Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('can:create-users');
        Route::post('/', [UserController::class, 'store'])->name('store')->middleware('can:create-users');
        Route::get('/export', [UserController::class, 'export'])->name('export')->middleware('can:export-users');
        
        // User Statistics and Reports
        Route::get('/stats', [UserController::class, 'stats'])->name('stats')->middleware('can:view-reports');
        Route::get('/deleted', [UserController::class, 'deleted'])->name('deleted')->middleware('can:manage-users');
        
        // LDAP Sync routes (must be before {user} routes)
        Route::post('/sync-ldap', [UserController::class, 'syncLdap'])->name('sync-ldap')->middleware('can:manage-ldap');
        Route::get('/ldap-sync-status', [UserController::class, 'getLdapSyncStatus'])->name('ldap-sync-status')->middleware('can:manage-ldap');
        Route::post('/test-ldap-connection', [UserController::class, 'testLdapConnection'])->name('test-ldap-connection')->middleware('can:manage-ldap');
        Route::get('/sync-history', [UserController::class, 'getSyncHistory'])->name('sync-history')->middleware('can:manage-ldap');
        Route::get('/sync-progress', [UserController::class, 'getSyncProgress'])->name('sync-progress')->middleware('can:manage-ldap');

        // Bulk Operations (must be before {user} routes)
        Route::post('/bulk-action', [UserController::class, 'bulkAction'])->name('bulk-action')->middleware('can:manage-users');
        Route::post('/import', [UserController::class, 'import'])->name('import')->middleware('can:create-users');
        Route::get('/search', [UserController::class, 'search'])->name('search');
        
        // Current user preferences (must be before {user} routes)
        Route::get('/preferences', [UserPreferenceController::class, 'show'])->name('preferences');
        Route::patch('/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');
        Route::delete('/preferences/reset', [UserPreferenceController::class, 'reset'])->name('preferences.reset');
        Route::post('/preferences/test', [UserPreferenceController::class, 'testNotification'])->name('preferences.test');
        Route::get('/preferences/export', [UserPreferenceController::class, 'export'])->name('preferences.export');
        Route::post('/preferences/import', [UserPreferenceController::class, 'import'])->name('preferences.import');

        Route::get('/{user}/preferences', [UserPreferenceController::class, 'show'])->name('preferences.show')->middleware('can:manage-user-preferences');
        Route::patch('/{user}/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update.user')->middleware('can:manage-user-preferences');
        Route::delete('/{user}/preferences/reset', [UserPreferenceController::class, 'reset'])->name('preferences.reset.user')->middleware('can:manage-user-preferences');
        Route::post('/{user}/preferences/test', [UserPreferenceController::class, 'testNotification'])->name('preferences.test.user')->middleware('can:manage-user-preferences');
        Route::get('/{user}/preferences/export', [UserPreferenceController::class, 'export'])->name('preferences.export.user')->middleware('can:manage-user-preferences');
        Route::post('/{user}/preferences/import', [UserPreferenceController::class, 'import'])->name('preferences.import.user')->middleware('can:manage-user-preferences');

        
        // User specific routes (with {user} parameter)
        Route::get('/{user}', [UserController::class, 'show'])->name('show')->middleware('can:view-users');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('can:edit-users');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('can:edit-users');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('can:delete-users');
        
        // User Status Management
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status')->middleware('can:manage-users');
        Route::patch('/{user}/unlock', [UserController::class, 'unlock'])->name('unlock')->middleware('can:manage-users');
        
        // Password Management
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password')->middleware('can:manage-users');
        Route::post('/{user}/send-welcome-email', [UserController::class, 'sendWelcomeEmail'])->name('send-welcome-email')->middleware('can:manage-users');
        
        // User permissions and roles
        Route::get('/{user}/permissions', [UserController::class, 'permissions'])->name('permissions')->middleware('can:view-user-permissions');
        Route::post('/{user}/assign-role', [UserController::class, 'assignRole'])->name('assign-role')->middleware('can:manage-user-roles');
        Route::post('/{user}/remove-role', [UserController::class, 'removeRole'])->name('remove-role')->middleware('can:manage-user-roles');
        Route::post('/{user}/assign-permission', [UserController::class, 'assignPermission'])->name('assign-permission')->middleware('can:assign-user-permissions');
        Route::post('/{user}/remove-permission', [UserController::class, 'removePermission'])->name('remove-permission')->middleware('can:assign-user-permissions');
        Route::patch('/{user}/update-roles', [UserController::class, 'updateRoles'])->name('update-roles')->middleware('can:manage-user-roles');
        Route::patch('/{user}/update-permissions', [UserController::class, 'updatePermissions'])->name('update-permissions')->middleware('can:assign-user-permissions');
        
        // Role and Permission Management Pages
        Route::get('/{user}/manage-roles', [UserController::class, 'manageRoles'])->name('manage-roles')->middleware('can:manage-user-roles');
        Route::get('/{user}/manage-permissions', [UserController::class, 'managePermissions'])->name('manage-permissions')->middleware('can:assign-user-permissions');
        
        // Group Management
        Route::post('/{user}/join-group', [UserController::class, 'joinGroup'])->name('join-group')->middleware('can:manage-group-members');
        Route::delete('/{user}/leave-group', [UserController::class, 'leaveGroup'])->name('leave-group')->middleware('can:manage-group-members');
        Route::get('/{user}/manage-groups', [UserController::class, 'manageGroups'])->name('manage-groups')->middleware('can:manage-group-members');
        Route::put('/{user}/update-groups', [UserController::class, 'updateGroups'])->name('update-groups')->middleware('can:manage-group-members');
        
        // User preferences for specific user (admin only)
        Route::get('/{user}/user-preferences', [UserPreferenceController::class, 'showUser'])->name('user-preferences.show')->middleware('can:manage-user-preferences');
        Route::patch('/{user}/user-preferences', [UserPreferenceController::class, 'updateUser'])->name('user-preferences.update')->middleware('can:manage-user-preferences');
        Route::delete('/{user}/user-preferences/reset', [UserPreferenceController::class, 'resetUser'])->name('user-preferences.reset')->middleware('can:manage-user-preferences');
        Route::post('/{user}/user-preferences/test', [UserPreferenceController::class, 'testNotificationUser'])->name('user-preferences.test')->middleware('can:manage-user-preferences');
        
        // User Activity and Logs
        Route::get('/{user}/activities', [UserController::class, 'activities'])->name('activities')->middleware('can:view-activity-logs');
        
        // Soft Delete Management
        Route::post('/{id}/restore', [UserController::class, 'restore'])->name('restore')->middleware('can:manage-users');
        Route::delete('/{id}/force-destroy', [UserController::class, 'forceDestroy'])->name('force-destroy')->middleware('can:delete-users');
    });

    // ===========================================
    // PERMISSIONS MANAGEMENT ROUTES
    // ===========================================
    Route::prefix('permissions')->name('permissions.')->group(function () {
        // Basic CRUD Routes
        Route::get('/', [PermissionController::class, 'index'])->name('index')->middleware('can:view-permissions');
        Route::get('/create', [PermissionController::class, 'create'])->name('create')->middleware('can:create-permissions');
        Route::post('/', [PermissionController::class, 'store'])->name('store')->middleware('can:create-permissions');
        Route::get('/{permission}', [PermissionController::class, 'show'])->name('show')->middleware('can:view-permissions');
        Route::get('/{permission}/edit', [PermissionController::class, 'edit'])->name('edit')->middleware('can:edit-permissions');
        Route::put('/{permission}', [PermissionController::class, 'update'])->name('update')->middleware('can:edit-permissions');
        Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy')->middleware('can:delete-permissions');
        
        // Special Views
        Route::get('/matrix/view', [PermissionController::class, 'matrix'])->name('matrix')->middleware('can:view-permission-matrix');
        Route::get('/search/advanced', [PermissionController::class, 'search'])->name('search')->middleware('can:view-permissions');
        Route::get('/compare/roles', [PermissionController::class, 'compareRoles'])->name('compare-roles')->middleware('can:view-permission-matrix');
        
        // Bulk Operations
        Route::post('/bulk/create', [PermissionController::class, 'bulkCreate'])->name('bulk-create')->middleware('can:create-permissions');
        Route::delete('/bulk/delete', [PermissionController::class, 'bulkDelete'])->name('bulk-delete')->middleware('can:delete-permissions');
        
        // Role Assignment Operations
        Route::post('/{permission}/assign-to-roles', [PermissionController::class, 'bulkAssignToRoles'])->name('bulk-assign-to-roles')->middleware('can:manage-roles-permissions');
        Route::post('/{permission}/remove-from-roles', [PermissionController::class, 'bulkRemoveFromRoles'])->name('bulk-remove-from-roles')->middleware('can:manage-roles-permissions');
        Route::post('/bulk/assign-multiple-to-roles', [PermissionController::class, 'bulkAssignMultipleToRoles'])->name('bulk-assign-multiple-to-roles')->middleware('can:manage-roles-permissions');
        
        // Utility Operations
        Route::post('/{permission}/duplicate', [PermissionController::class, 'duplicate'])->name('duplicate')->middleware('can:create-permissions');
        
        // Export routes with different formats
        Route::get('/export/csv', [PermissionController::class, 'export'])->name('export')->middleware('can:export-permissions');
        Route::get('/export/excel', [PermissionController::class, 'exportExcel'])->name('export.excel')->middleware('can:export-permissions');
        Route::get('/export/pdf', [PermissionController::class, 'exportPdf'])->name('export.pdf')->middleware('can:export-permissions');
        Route::get('/export/json', [PermissionController::class, 'exportJson'])->name('export.json')->middleware('can:export-permissions');
        
        // Import routes with different formats
        Route::get('/import', [PermissionController::class, 'importForm'])->name('import.form')->middleware('can:import-permissions');
        Route::post('/import/csv', [PermissionController::class, 'importCsv'])->name('import.csv')->middleware('can:import-permissions');
        Route::post('/import/excel', [PermissionController::class, 'importExcel'])->name('import.excel')->middleware('can:import-permissions');
        Route::post('/import/json', [PermissionController::class, 'importJson'])->name('import.json')->middleware('can:import-permissions');
        Route::get('/import/template/{format}', [PermissionController::class, 'downloadTemplate'])->name('import.template')->middleware('can:import-permissions');
        
        // Permission details API
        Route::get('/{permission}/details', [PermissionController::class, 'getDetails'])->name('details')->middleware('can:view-permissions');
    });

    // ===========================================
    // ROLES MANAGEMENT ROUTES
    // ===========================================
    Route::prefix('roles')->name('roles.')->group(function () {
        // Basic CRUD Routes
        Route::get('/', [RoleController::class, 'index'])->name('index')->middleware('can:view-roles');
        Route::get('/create', [RoleController::class, 'create'])->name('create')->middleware('can:create-roles');
        Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('can:create-roles');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show')->middleware('can:view-roles');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit')->middleware('can:edit-roles');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update')->middleware('can:edit-roles');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware('can:delete-roles');
        
        // Role-specific permission management
        Route::post('/{role}/assign-permissions', [RoleController::class, 'assignPermissions'])->name('assign-permissions')->middleware('can:assign-permissions');
        Route::post('/{role}/remove-permissions', [RoleController::class, 'removePermissions'])->name('remove-permissions')->middleware('can:assign-permissions');
        Route::post('/{role}/sync-permissions', [RoleController::class, 'syncPermissions'])->name('sync-permissions')->middleware('can:assign-permissions');
        
        // Bulk operations
        Route::post('/{role}/clone', [RoleController::class, 'clone'])->name('clone')->middleware('can:create-roles');
        Route::post('/{role}/bulk-assign', [RoleController::class, 'bulkAssign'])->name('bulk-assign')->middleware('can:assign-roles');
        Route::post('/{role}/bulk-remove', [RoleController::class, 'bulkRemove'])->name('bulk-remove')->middleware('can:assign-roles');
        
        // Export
        Route::get('/export/csv', [RoleController::class, 'export'])->name('export')->middleware('can:export-roles');
        Route::get('/export/excel', [RoleController::class, 'exportExcel'])->name('export.excel')->middleware('can:export-roles');
        Route::get('/export/pdf', [RoleController::class, 'exportPdf'])->name('export.pdf')->middleware('can:export-roles');
        
        // Import
        Route::get('/import', [RoleController::class, 'importForm'])->name('import.form')->middleware('can:import-roles');
        Route::post('/import/csv', [RoleController::class, 'importCsv'])->name('import.csv')->middleware('can:import-roles');
        Route::post('/import/excel', [RoleController::class, 'importExcel'])->name('import.excel')->middleware('can:import-roles');
        Route::post('/import/json', [RoleController::class, 'importJson'])->name('import.json')->middleware('can:import-roles');
    });

    // ===========================================
    // NOTIFICATION TEMPLATES ROUTES
    // ===========================================
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [NotificationTemplateController::class, 'index'])->name('index')->middleware('can:view-templates');
        Route::get('/create', [NotificationTemplateController::class, 'create'])->name('create')->middleware('can:create-templates');
        Route::post('/', [NotificationTemplateController::class, 'store'])->name('store')->middleware('can:create-templates');
        Route::get('/{template}', [NotificationTemplateController::class, 'show'])->name('show')->middleware('can:view-templates');
        Route::get('/{template}/edit', [NotificationTemplateController::class, 'edit'])->name('edit')->middleware('can:edit-templates');
        Route::put('/{template}', [NotificationTemplateController::class, 'update'])->name('update')->middleware('can:edit-templates');
        Route::delete('/{template}', [NotificationTemplateController::class, 'destroy'])->name('destroy')->middleware('can:delete-templates');
        
        // Template operations
        Route::get('/{template}/preview', [NotificationTemplateController::class, 'preview'])->name('preview')->middleware('can:view-templates');
        Route::post('/{template}/preview/{channel}', [NotificationTemplateController::class, 'previewChannel'])->name('preview.channel')->middleware('can:view-templates');
        Route::post('/{template}/duplicate', [NotificationTemplateController::class, 'duplicate'])->name('duplicate')->middleware('can:create-templates');
        Route::post('/{template}/toggle-status', [NotificationTemplateController::class, 'toggleStatus'])->name('toggle-status')->middleware('can:edit-templates');
        Route::post('/{template}/test-send', [NotificationTemplateController::class, 'testSend'])->name('test-send')->middleware('can:test-templates');
        
        // Bulk operations
        Route::post('/bulk-action', [NotificationTemplateController::class, 'bulkAction'])->name('bulk-action')->middleware('can:edit-templates');
        Route::post('/bulk/duplicate', [NotificationTemplateController::class, 'bulkDuplicate'])->name('bulk.duplicate')->middleware('can:create-templates');
        Route::post('/bulk/toggle-status', [NotificationTemplateController::class, 'bulkToggleStatus'])->name('bulk.toggle-status')->middleware('can:edit-templates');
        
        // Import/Export
        Route::get('/export/{format}', [NotificationTemplateController::class, 'export'])->name('export')->middleware('can:export-templates');
        Route::get('/import', [NotificationTemplateController::class, 'importForm'])->name('import.form')->middleware('can:import-templates');
        Route::post('/import', [NotificationTemplateController::class, 'import'])->name('import')->middleware('can:import-templates');
        
        // Template categories and variables
        Route::get('/categories/manage', [NotificationTemplateController::class, 'manageCategories'])->name('categories.manage')->middleware('can:manage-template-categories');
        Route::get('/variables/reference', [NotificationTemplateController::class, 'variablesReference'])->name('variables.reference')->middleware('can:view-templates');
    });

    // ===========================================
    // NOTIFICATION MANAGEMENT ROUTES
    // ===========================================
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index')->middleware('can:view-notifications');
        Route::get('/create', [NotificationController::class, 'create'])->name('create')->middleware('can:create-notifications');
        Route::post('/', [NotificationController::class, 'store'])->name('store')->middleware('can:create-notifications');
        Route::get('/{notification}', [NotificationController::class, 'show'])->name('show')->middleware('can:view-notifications');
        Route::get('/{notification}/edit', [NotificationController::class, 'edit'])->name('edit')->middleware('can:edit-notifications');
        Route::put('/{notification}', [NotificationController::class, 'update'])->name('update')->middleware('can:edit-notifications');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy')->middleware('can:delete-notifications');
        
        // Notification actions
        Route::post('/{notification}/send', [NotificationController::class, 'send'])->name('send')->middleware('can:send-notifications');
        Route::post('/{notification}/schedule', [NotificationController::class, 'schedule'])->name('schedule')->middleware('can:schedule-notifications');
        Route::post('/{notification}/cancel', [NotificationController::class, 'cancel'])->name('cancel')->middleware('can:cancel-notifications');
        Route::post('/{notification}/retry', [NotificationController::class, 'retry'])->name('retry')->middleware('can:retry-notifications');
        Route::get('/{notification}/status', [NotificationController::class, 'status'])->name('status')->middleware('can:view-notifications');
        Route::get('/{notification}/delivery-details', [NotificationController::class, 'deliveryDetails'])->name('delivery-details')->middleware('can:view-delivery-details');
        
        // Notification management views
        Route::get('/scheduled/list', [NotificationController::class, 'scheduledList'])->name('scheduled.list')->middleware('can:view-scheduled-notifications');
        Route::get('/failed/list', [NotificationController::class, 'failedList'])->name('failed.list')->middleware('can:view-failed-notifications');
        Route::get('/sent/list', [NotificationController::class, 'sentList'])->name('sent.list')->middleware('can:view-sent-notifications');
        
        // Bulk operations
        Route::post('/bulk/send', [NotificationController::class, 'bulkSend'])->name('bulk-send')->middleware('can:send-notifications');
        Route::post('/bulk/schedule', [NotificationController::class, 'bulkSchedule'])->name('bulk-schedule')->middleware('can:schedule-notifications');
        Route::post('/bulk/cancel', [NotificationController::class, 'bulkCancel'])->name('bulk-cancel')->middleware('can:cancel-notifications');
        Route::post('/bulk/retry', [NotificationController::class, 'bulkRetry'])->name('bulk-retry')->middleware('can:retry-notifications');
        Route::delete('/bulk/delete', [NotificationController::class, 'bulkDelete'])->name('bulk-delete')->middleware('can:delete-notifications');
        
        // Quick send
        Route::get('/quick-send', [NotificationController::class, 'quickSendForm'])->name('quick-send.form')->middleware('can:quick-send-notifications');
        Route::post('/quick-send', [NotificationController::class, 'quickSend'])->name('quick-send')->middleware('can:quick-send-notifications');
    });
    
    // ===========================================
    // NOTIFICATION LOGS ROUTES
    // ===========================================
    // Route::prefix('notification-logs')->name('notification-logs.')->group(function () {
    //     Route::get('/', [NotificationLogController::class, 'index'])->name('index')->middleware('can:view-notification-logs');
    //     Route::get('/{log}', [NotificationLogController::class, 'show'])->name('show')->middleware('can:view-notification-logs');
    //     Route::get('/notification/{notification}', [NotificationLogController::class, 'getByNotification'])->name('by-notification')->middleware('can:view-notification-logs');
        
    //     // Failed notifications management
    //     Route::get('/failed/list', [NotificationLogController::class, 'failedList'])->name('failed.list')->middleware('can:view-failed-notifications');
    //     Route::post('/failed/retry', [NotificationLogController::class, 'retryFailed'])->name('failed.retry')->middleware('can:retry-failed-notifications');
    //     Route::post('/failed/bulk-retry', [NotificationLogController::class, 'bulkRetryFailed'])->name('failed.bulk-retry')->middleware('can:retry-failed-notifications');
    //     Route::delete('/failed/cleanup', [NotificationLogController::class, 'cleanupFailed'])->name('failed.cleanup')->middleware('can:cleanup-failed-notifications');
        
    //     // Log management
    //     Route::delete('/cleanup', [NotificationLogController::class, 'cleanup'])->name('cleanup')->middleware('can:cleanup-notification-logs');
    //     Route::get('/export/{format}', [NotificationLogController::class, 'export'])->name('export')->middleware('can:export-notification-logs');
        
    //     // Delivery tracking
    //     Route::get('/delivery/tracking/{trackingId}', [NotificationLogController::class, 'trackDelivery'])->name('delivery.track')->middleware('can:track-deliveries');
    //     Route::get('/delivery/stats', [NotificationLogController::class, 'deliveryStats'])->name('delivery.stats')->middleware('can:view-delivery-stats');
    // });

    // ===========================================
    // GROUP MANAGEMENT ROUTES
    // ===========================================
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index')->middleware('can:view-groups');
        Route::get('/create', [GroupController::class, 'create'])->name('create')->middleware('can:create-groups');
        Route::post('/', [GroupController::class, 'store'])->name('store')->middleware('can:create-groups');
        Route::get('/{group}', [GroupController::class, 'show'])->name('show')->middleware('can:view-groups');
        Route::get('/{group}/edit', [GroupController::class, 'edit'])->name('edit')->middleware('can:edit-groups');
        Route::put('/{group}', [GroupController::class, 'update'])->name('update')->middleware('can:edit-groups');
        Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy')->middleware('can:delete-groups');
        
        // Group member management
        Route::get('/{group}/members', [GroupController::class, 'manageMembers'])->name('members.manage')->middleware('can:manage-group-members');
        Route::post('/{group}/add-user', [GroupController::class, 'addUser'])->name('add-user')->middleware('can:manage-group-members');
        Route::post('/{group}/remove-user', [GroupController::class, 'removeUser'])->name('remove-user')->middleware('can:manage-group-members');
        Route::post('/{group}/sync', [GroupController::class, 'syncMembers'])->name('sync')->middleware('can:manage-group-members');
        Route::post('/{group}/bulk-add-users', [GroupController::class, 'bulkAddUsers'])->name('bulk-add-users')->middleware('can:manage-group-members');
        Route::post('/{group}/bulk-remove-users', [GroupController::class, 'bulkRemoveUsers'])->name('bulk-remove-users')->middleware('can:manage-group-members');
        
        // Group operations
        Route::post('/{group}/duplicate', [GroupController::class, 'duplicate'])->name('duplicate')->middleware('can:create-groups');
        Route::get('/{group}/preview-members', [GroupController::class, 'previewMembers'])->name('preview-members')->middleware('can:view-groups');
        
        // Bulk group operations
        Route::post('/bulk-sync', [GroupController::class, 'bulkSync'])->name('bulk-sync')->middleware('can:manage-group-members');
        Route::post('/bulk-action', [GroupController::class, 'bulkAction'])->name('bulk-action')->middleware('can:manage-groups');
        
        // Export/Import
        Route::get('/{group}/export', [GroupController::class, 'exportMembers'])->name('export')->middleware('can:export-groups');
        Route::get('/export/all/{format}', [GroupController::class, 'exportAllGroups'])->name('export.all')->middleware('can:export-groups');
        Route::get('/import', [GroupController::class, 'importForm'])->name('import.form')->middleware('can:import-groups');
        Route::post('/import', [GroupController::class, 'import'])->name('import')->middleware('can:import-groups');
        
        // AJAX helpers
        Route::get('/users', [GroupController::class, 'getUsers'])->name('users');
    });
    
    // ===========================================
    // REPORTS ROUTES
    // ===========================================
    Route::prefix('reports')->name('reports.')->middleware('can:view-reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        
        // Delivery reports
        Route::get('/delivery', [ReportController::class, 'delivery'])->name('delivery');
        Route::get('/delivery/detailed', [ReportController::class, 'deliveryDetailed'])->name('delivery.detailed');
        Route::get('/delivery/by-channel', [ReportController::class, 'deliveryByChannel'])->name('delivery.by-channel');
        Route::get('/delivery/failure-analysis', [ReportController::class, 'failureAnalysis'])->name('delivery.failure-analysis');
        
        // API usage reports
        Route::get('/api-usage', [ReportController::class, 'apiUsage'])->name('api-usage')->middleware('can:view-api-usage-reports');
        Route::get('/api-usage/by-key', [ReportController::class, 'apiUsageByKey'])->name('api-usage.by-key')->middleware('can:view-api-usage-reports');
        Route::get('/api-usage/rate-limits', [ReportController::class, 'rateLimitAnalysis'])->name('api-usage.rate-limits')->middleware('can:view-api-usage-reports');
        
        // User activity reports
        Route::get('/user-activity', [ReportController::class, 'userActivity'])->name('user-activity');
        Route::get('/user-activity/detailed', [ReportController::class, 'userActivityDetailed'])->name('user-activity.detailed');
        Route::get('/user-activity/by-department', [ReportController::class, 'userActivityByDepartment'])->name('user-activity.by-department');
        
        // Permission and role reports
        Route::get('/permission-usage', [ReportController::class, 'permissionUsage'])->name('permission-usage');
        Route::get('/role-distribution', [ReportController::class, 'roleDistribution'])->name('role-distribution');
        Route::get('/access-control-audit', [ReportController::class, 'accessControlAudit'])->name('access-control-audit');
        
        // System reports
        Route::get('/system-health', [ReportController::class, 'systemHealth'])->name('system-health');
        Route::get('/performance', [ReportController::class, 'performance'])->name('performance');
        Route::get('/queue-performance', [ReportController::class, 'queuePerformance'])->name('queue-performance');
        
        // Report generation and export
        Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
        Route::get('/export/{type}/{format}', [ReportController::class, 'export'])->name('export');
        Route::get('/scheduled', [ReportController::class, 'scheduledReports'])->name('scheduled');
        Route::post('/schedule', [ReportController::class, 'scheduleReport'])->name('schedule');
    });

    // ===========================================
    // API ANALYTICS ROUTES
    // ===========================================
    // Route::prefix('api-analytics')->name('api-analytics.')->middleware('can:view-api-analytics')->group(function () {
    //     Route::get('/', [ApiAnalyticsController::class, 'index'])->name('index');
    //     Route::get('/overview', [ApiAnalyticsController::class, 'overview'])->name('overview');
    //     Route::get('/by-key', [ApiAnalyticsController::class, 'byKey'])->name('by-key');
    //     Route::get('/rate-limits', [ApiAnalyticsController::class, 'rateLimits'])->name('rate-limits');
    //     Route::get('/top-consumers', [ApiAnalyticsController::class, 'topConsumers'])->name('top-consumers');
    //     Route::get('/usage-trends', [ApiAnalyticsController::class, 'usageTrends'])->name('usage-trends');
    //     Route::get('/endpoint-popularity', [ApiAnalyticsController::class, 'endpointPopularity'])->name('endpoint-popularity');
    //     Route::get('/error-analysis', [ApiAnalyticsController::class, 'errorAnalysis'])->name('error-analysis');
        
    //     // Real-time monitoring
    //     Route::get('/real-time', [ApiAnalyticsController::class, 'realTime'])->name('real-time');
    //     Route::get('/live-requests', [ApiAnalyticsController::class, 'liveRequests'])->name('live-requests');
        
    //     // Export
    //     Route::get('/export/{type}/{format}', [ApiAnalyticsController::class, 'export'])->name('export');
    // });

    // ===========================================
    // SYSTEM MANAGEMENT ROUTES
    // ===========================================
    // Route::prefix('system')->name('system.')->middleware('can:access-system-management')->group(function () {
        
    //     // System health and monitoring
    //     Route::get('/health', [SystemController::class, 'health'])->name('health');
    //     Route::get('/health/detailed', [SystemController::class, 'detailedHealth'])->name('health.detailed');
    //     Route::get('/health/components', [SystemController::class, 'componentsHealth'])->name('health.components');
    //     Route::get('/health/history', [SystemController::class, 'healthHistory'])->name('health.history');
        
    //     // Queue monitoring and management
    //     Route::get('/queue', [QueueController::class, 'index'])->name('queue.index');
    //     Route::get('/queue/status', [QueueController::class, 'status'])->name('queue.status');
    //     Route::get('/queue/stats', [QueueController::class, 'stats'])->name('queue.stats');
    //     Route::post('/queue/pause', [QueueController::class, 'pause'])->name('queue.pause')->middleware('can:manage-queue');
    //     Route::post('/queue/resume', [QueueController::class, 'resume'])->name('queue.resume')->middleware('can:manage-queue');
    //     Route::post('/queue/clear', [QueueController::class, 'clear'])->name('queue.clear')->middleware('can:manage-queue');
    //     Route::post('/queue/restart', [QueueController::class, 'restart'])->name('queue.restart')->middleware('can:manage-queue');
        
    //     // Failed jobs management
    //     Route::get('/queue/failed', [QueueController::class, 'failed'])->name('queue.failed');
    //     Route::post('/queue/failed/{id}/retry', [QueueController::class, 'retryFailed'])->name('queue.retry-failed')->middleware('can:manage-queue');
    //     Route::post('/queue/failed/retry-all', [QueueController::class, 'retryAllFailed'])->name('queue.retry-all-failed')->middleware('can:manage-queue');
    //     Route::delete('/queue/failed/{id}', [QueueController::class, 'deleteFailed'])->name('queue.delete-failed')->middleware('can:manage-queue');
    //     Route::delete('/queue/failed/clear', [QueueController::class, 'clearFailed'])->name('queue.clear-failed')->middleware('can:manage-queue');
        
    //     // External services status
    //     Route::get('/services', [SystemController::class, 'servicesStatus'])->name('services');
    //     Route::post('/services/test', [SystemController::class, 'testServices'])->name('services.test');
    //     Route::get('/services/ldap', [SystemController::class, 'ldapStatus'])->name('services.ldap');
    //     Route::get('/services/teams', [SystemController::class, 'teamsStatus'])->name('services.teams');
    //     Route::get('/services/email', [SystemController::class, 'emailStatus'])->name('services.email');
    //     Route::get('/services/rabbitmq', [SystemController::class, 'rabbitmqStatus'])->name('services.rabbitmq');
        
    //     // System metrics and performance
    //     Route::get('/metrics', [SystemController::class, 'metrics'])->name('metrics');
    //     Route::get('/performance', [SystemController::class, 'performance'])->name('performance');
    //     Route::get('/usage-stats', [SystemController::class, 'usageStats'])->name('usage-stats');
    //     Route::get('/capacity-planning', [SystemController::class, 'capacityPlanning'])->name('capacity-planning');
        
    //     // System logs
    //     Route::get('/logs', [SystemController::class, 'logs'])->name('logs')->middleware('can:view-system-logs');
    //     Route::get('/logs/application', [SystemController::class, 'applicationLogs'])->name('logs.application')->middleware('can:view-system-logs');
    //     Route::get('/logs/error', [SystemController::class, 'errorLogs'])->name('logs.error')->middleware('can:view-system-logs');
    //     Route::get('/logs/security', [SystemController::class, 'securityLogs'])->name('logs.security')->middleware('can:view-system-logs');
    //     Route::delete('/logs/cleanup', [SystemController::class, 'cleanupLogs'])->name('logs.cleanup')->middleware('can:manage-system-logs');
        
    //     // System settings
    //     Route::get('/settings', [SystemController::class, 'settings'])->name('settings')->middleware('can:manage-system-settings');
    //     Route::post('/settings', [SystemController::class, 'updateSettings'])->name('settings.update')->middleware('can:manage-system-settings');
    //     Route::post('/settings/reset', [SystemController::class, 'resetSettings'])->name('settings.reset')->middleware('can:manage-system-settings');
    //     Route::get('/settings/backup', [SystemController::class, 'backupSettings'])->name('settings.backup')->middleware('can:backup-system-settings');
    //     Route::post('/settings/restore', [SystemController::class, 'restoreSettings'])->name('settings.restore')->middleware('can:restore-system-settings');
        
    //     // System maintenance
    //     Route::get('/maintenance', [SystemController::class, 'maintenance'])->name('maintenance')->middleware('can:system-maintenance');
    //     Route::post('/maintenance/mode/enable', [SystemController::class, 'enableMaintenanceMode'])->name('maintenance.enable')->middleware('can:system-maintenance');
    //     Route::post('/maintenance/mode/disable', [SystemController::class, 'disableMaintenanceMode'])->name('maintenance.disable')->middleware('can:system-maintenance');
    //     Route::post('/cache/clear', [SystemController::class, 'clearCache'])->name('cache.clear')->middleware('can:system-maintenance');
    //     Route::post('/cache/optimize', [SystemController::class, 'optimizeCache'])->name('cache.optimize')->middleware('can:system-maintenance');
    //     Route::post('/permissions/rebuild', [PermissionController::class, 'rebuildCache'])->name('permissions.rebuild')->middleware('can:system-maintenance');
    // });

    // ===========================================
    // ACTIVITY LOGS ROUTES
    // ===========================================
    Route::prefix('activity-logs')->name('activity-logs.')->middleware('can:view-activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/{id}', [ActivityLogController::class, 'show'])->name('show');
        Route::get('/users/{userId}/activities', [ActivityLogController::class, 'getUserActivities'])->name('user-activities');
        Route::get('/models/{model}/activities', [ActivityLogController::class, 'getModelActivities'])->name('model-activities');
        
        // Activity log filtering and search
        Route::get('/search/advanced', [ActivityLogController::class, 'advancedSearch'])->name('search.advanced');
        Route::get('/by-date/{date}', [ActivityLogController::class, 'getByDate'])->name('by-date');
        Route::get('/by-action/{action}', [ActivityLogController::class, 'getByAction'])->name('by-action');
        Route::get('/by-causer/{causerId}', [ActivityLogController::class, 'getByCauser'])->name('by-causer');
        
        // Export and cleanup
        Route::get('/export/{format}', [ActivityLogController::class, 'export'])->name('export');
        Route::delete('/cleanup', [ActivityLogController::class, 'cleanup'])->name('cleanup')->middleware('can:delete-activity-logs');
        Route::delete('/cleanup/old', [ActivityLogController::class, 'cleanupOld'])->name('cleanup.old')->middleware('can:delete-activity-logs');
        
        // Statistics
        Route::get('/stats/overview', [ActivityLogController::class, 'getOverviewStats'])->name('stats.overview');
        Route::get('/stats/by-user', [ActivityLogController::class, 'getStatsByUser'])->name('stats.by-user');
        Route::get('/stats/by-action', [ActivityLogController::class, 'getStatsByAction'])->name('stats.by-action');
    });

    // ===========================================
    // ADMIN ROUTES
    // ===========================================
    Route::prefix('admin')->name('admin.')->middleware('can:access-admin-panel')->group(function () {
        
        // API Keys Management
        Route::prefix('api-keys')->name('api-keys.')->middleware('can:manage-api-keys')->group(function () {
            Route::get('/', [ApiKeyController::class, 'index'])->name('index');
            Route::get('/create', [ApiKeyController::class, 'create'])->name('create');
            Route::post('/', [ApiKeyController::class, 'store'])->name('store');
            Route::get('/{apiKey}', [ApiKeyController::class, 'show'])->name('show');
            Route::get('/{apiKey}/edit', [ApiKeyController::class, 'edit'])->name('edit');
            Route::put('/{apiKey}', [ApiKeyController::class, 'update'])->name('update');
            Route::delete('/{apiKey}', [ApiKeyController::class, 'destroy'])->name('destroy');
            
            // API Key actions
            Route::post('/{apiKey}/regenerate', [ApiKeyController::class, 'regenerate'])->name('regenerate');
            Route::post('/{apiKey}/toggle-status', [ApiKeyController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/{apiKey}/usage-stats', [ApiKeyController::class, 'usageStats'])->name('usage-stats');
            Route::get('/{apiKey}/usage-history', [ApiKeyController::class, 'usageHistory'])->name('usage-history');
            Route::post('/{apiKey}/reset-usage', [ApiKeyController::class, 'resetUsage'])->name('reset-usage');
            
            // Bulk operations
            Route::post('/bulk/toggle-status', [ApiKeyController::class, 'bulkToggleStatus'])->name('bulk.toggle-status');
            Route::post('/bulk/update-limits', [ApiKeyController::class, 'bulkUpdateLimits'])->name('bulk.update-limits');
            Route::delete('/bulk/delete', [ApiKeyController::class, 'bulkDelete'])->name('bulk.delete');
            
            // Export and audit
            Route::get('/export/{format}', [ApiKeyController::class, 'export'])->name('export');
            Route::get('/audit-log', [ApiKeyController::class, 'auditLog'])->name('audit-log');
            Route::get('/security-report', [ApiKeyController::class, 'securityReport'])->name('security-report');
        });
        
        // Database management
        Route::prefix('database')->name('database.')->middleware('can:manage-database')->group(function () {
            Route::get('/', [SystemController::class, 'databaseStatus'])->name('status');
            Route::get('/tables', [SystemController::class, 'databaseTables'])->name('tables');
            Route::get('/size-analysis', [SystemController::class, 'databaseSizeAnalysis'])->name('size-analysis');
            Route::post('/optimize', [SystemController::class, 'optimizeDatabase'])->name('optimize');
            Route::post('/backup', [SystemController::class, 'backupDatabase'])->name('backup');
            Route::get('/backups', [SystemController::class, 'listBackups'])->name('backups');
        });
        
        // Configuration management
        Route::prefix('config')->name('config.')->middleware('can:manage-configuration')->group(function () {
            Route::get('/', [SystemController::class, 'configOverview'])->name('overview');
            Route::get('/environment', [SystemController::class, 'environmentConfig'])->name('environment');
            Route::get('/ldap', [SystemController::class, 'ldapConfig'])->name('ldap');
            Route::get('/notifications', [SystemController::class, 'notificationConfig'])->name('notifications');
            Route::get('/security', [SystemController::class, 'securityConfig'])->name('security');
            Route::post('/update', [SystemController::class, 'updateConfig'])->name('update');
            Route::post('/test', [SystemController::class, 'testConfig'])->name('test');
        });
        
        // Security and audit
        Route::prefix('security')->name('security.')->middleware('can:manage-security')->group(function () {
            Route::get('/overview', [SystemController::class, 'securityOverview'])->name('overview');
            Route::get('/failed-logins', [SystemController::class, 'failedLogins'])->name('failed-logins');
            Route::get('/suspicious-activity', [SystemController::class, 'suspiciousActivity'])->name('suspicious-activity');
            Route::get('/api-abuse', [SystemController::class, 'apiAbuse'])->name('api-abuse');
            Route::post('/block-ip', [SystemController::class, 'blockIp'])->name('block-ip');
            Route::post('/unblock-ip', [SystemController::class, 'unblockIp'])->name('unblock-ip');
            Route::get('/blocked-ips', [SystemController::class, 'blockedIps'])->name('blocked-ips');
        });
    });

});

// ===========================================
// AJAX ROUTES FOR DYNAMIC FUNCTIONALITY
// ===========================================
Route::middleware(['auth'])->prefix('ajax')->name('ajax.')->group(function () {
    
    // User autocomplete and search
    Route::get('/users/search', [UserController::class, 'ajaxSearch'])->name('users.search');
    Route::get('/users/{user}/permissions', [UserController::class, 'ajaxGetPermissions'])->name('users.get-permissions');
    Route::get('/users/{user}/roles', [UserController::class, 'ajaxGetRoles'])->name('users.get-roles');
    Route::get('/users/{user}/groups', [UserController::class, 'ajaxGetGroups'])->name('users.get-groups');
    Route::get('/users/by-department/{department}', [UserController::class, 'ajaxGetByDepartment'])->name('users.by-department');
    
    // Permission autocomplete and management
    Route::get('/permissions/search', [PermissionController::class, 'ajaxSearch'])->name('permissions.search');
    Route::get('/permissions/{permission}/roles', [PermissionController::class, 'ajaxGetRoles'])->name('permissions.get-roles');
    Route::get('/permissions/{permission}/users', [PermissionController::class, 'ajaxGetUsers'])->name('permissions.get-users');
    Route::get('/permissions/categories', [PermissionController::class, 'getCategories'])->name('permissions.categories');
    Route::post('/permissions/categories', [PermissionController::class, 'createCategory'])->name('permissions.create-category');
    
    // Role autocomplete and management
    Route::get('/roles/search', [RoleController::class, 'ajaxSearch'])->name('roles.search');
    Route::get('/roles/{role}/permissions', [RoleController::class, 'ajaxGetPermissions'])->name('roles.get-permissions');
    Route::get('/roles/{role}/users', [RoleController::class, 'ajaxGetUsers'])->name('roles.get-users');
    
    // Group autocomplete and management
    Route::get('/groups/search', [GroupController::class, 'ajaxSearch'])->name('groups.search');
    Route::get('/groups/{group}/members', [GroupController::class, 'ajaxGetMembers'])->name('groups.get-members');
    Route::post('/groups/preview-members', [GroupController::class, 'ajaxPreviewMembers'])->name('groups.preview-members');
    
    // Template autocomplete and variables
    Route::get('/templates/search', [NotificationTemplateController::class, 'ajaxSearch'])->name('templates.search');
    Route::get('/templates/{template}/variables', [NotificationTemplateController::class, 'ajaxGetVariables'])->name('templates.get-variables');
    Route::post('/templates/{template}/preview', [NotificationTemplateController::class, 'ajaxPreview'])->name('templates.preview');
    
    // Notification management
    Route::get('/notifications/search', [NotificationController::class, 'ajaxSearch'])->name('notifications.search');
    Route::get('/notifications/{notification}/status', [NotificationController::class, 'ajaxGetStatus'])->name('notifications.get-status');
    Route::post('/notifications/validate-recipients', [NotificationController::class, 'ajaxValidateRecipients'])->name('notifications.validate-recipients');
    
    // Dashboard widgets
    Route::get('/dashboard/permissions/widget', [PermissionController::class, 'dashboardWidget'])->name('dashboard.permissions.widget');
    Route::get('/dashboard/permissions/chart-data', [PermissionController::class, 'chartData'])->name('dashboard.permissions.chart-data');
    Route::get('/dashboard/users/widget', [UserController::class, 'dashboardWidget'])->name('dashboard.users.widget');
    Route::get('/dashboard/users/chart-data', [UserController::class, 'chartData'])->name('dashboard.users.chart-data');
    Route::get('/dashboard/users/stats', [UserController::class, 'getStats'])->name('dashboard.users.stats');
    Route::get('/dashboard/notifications/widget', [NotificationController::class, 'dashboardWidget'])->name('dashboard.notifications.widget');
    Route::get('/dashboard/system/status', [SystemController::class, 'ajaxSystemStatus'])->name('dashboard.system.status');
    
    // Real-time updates
    // Route::get('/system/live-status', [SystemController::class, 'liveStatus'])->name('system.live-status');
    // Route::get('/queue/live-stats', [QueueController::class, 'liveStats'])->name('queue.live-stats');
    Route::get('/notifications/live-stats', [NotificationController::class, 'liveStats'])->name('notifications.live-stats');
    
    // Quick actions
    Route::post('/users/{user}/quick-toggle-status', [UserController::class, 'quickToggleStatus'])->name('users.quick-toggle-status');
    Route::post('/groups/{group}/quick-sync', [GroupController::class, 'quickSync'])->name('groups.quick-sync');
    Route::post('/notifications/{notification}/quick-cancel', [NotificationController::class, 'quickCancel'])->name('notifications.quick-cancel');
    
    // Autocomplete helpers
    Route::get('/departments', function() {
        return response()->json(
            \App\Models\User::active()
                ->whereNotNull('department')
                ->where('department', '!=', '')
                ->distinct()
                ->pluck('department')
                ->sort()
                ->values()
        );
    })->name('departments');
    
    Route::get('/ldap/organizational-units', [UserController::class, 'getLdapOrganizationalUnits'])->name('ldap.organizational-units');
});

// ===========================================
// ROUTE MODEL BINDING
// ===========================================
Route::bind('user', function ($value) {
    return \App\Models\User::where('id', $value)
        ->orWhere('username', $value)
        ->firstOrFail();
});

Route::bind('permission', function ($value) {
    return \Spatie\Permission\Models\Permission::where('id', $value)
        ->orWhere('name', $value)
        ->firstOrFail();
});

Route::bind('role', function ($value) {
    return \Spatie\Permission\Models\Role::where('id', $value)
        ->orWhere('name', $value)
        ->firstOrFail();
});

Route::bind('notification', function ($value) {
    return \App\Models\Notification::findOrFail($value);
});

Route::bind('template', function ($value) {
    return \App\Models\NotificationTemplate::findOrFail($value);
});

Route::bind('group', function ($value) {
    return \App\Models\NotificationGroup::findOrFail($value);
});

Route::bind('log', function ($value) {
    return \App\Models\NotificationLog::findOrFail($value);
});

Route::bind('apiKey', function ($value) {
    return \App\Models\ApiKey::findOrFail($value);
});

// ===========================================
// WEBHOOK ROUTES FOR EXTERNAL INTEGRATIONS
// ===========================================
Route::middleware(['webhook.auth'])->prefix('webhooks')->name('webhooks.')->group(function () {
    
    // Permission system webhooks
    Route::post('/permissions/created', [PermissionController::class, 'webhookCreated'])->name('permissions.created');
    Route::post('/permissions/updated', [PermissionController::class, 'webhookUpdated'])->name('permissions.updated');
    Route::post('/permissions/deleted', [PermissionController::class, 'webhookDeleted'])->name('permissions.deleted');
    
    // Role system webhooks
    Route::post('/roles/permission-assigned', [RoleController::class, 'webhookPermissionAssigned'])->name('roles.permission-assigned');
    Route::post('/roles/permission-removed', [RoleController::class, 'webhookPermissionRemoved'])->name('roles.permission-removed');
    Route::post('/roles/user-assigned', [RoleController::class, 'webhookUserAssigned'])->name('roles.user-assigned');
    Route::post('/roles/user-removed', [RoleController::class, 'webhookUserRemoved'])->name('roles.user-removed');
    
    // User system webhooks
    Route::post('/users/created', [UserController::class, 'webhookCreated'])->name('users.created');
    Route::post('/users/updated', [UserController::class, 'webhookUpdated'])->name('users.updated');
    Route::post('/users/deleted', [UserController::class, 'webhookDeleted'])->name('users.deleted');
    Route::post('/users/status-changed', [UserController::class, 'webhookStatusChanged'])->name('users.status-changed');
    
    // Notification system webhooks
    Route::post('/notifications/sent', [NotificationController::class, 'webhookSent'])->name('notifications.sent');
    Route::post('/notifications/delivered', [NotificationController::class, 'webhookDelivered'])->name('notifications.delivered');
    Route::post('/notifications/failed', [NotificationController::class, 'webhookFailed'])->name('notifications.failed');
});

// ===========================================
// FALLBACK ROUTES FOR BACKWARD COMPATIBILITY
// ===========================================
Route::get('/admin/permissions', function () {
    return redirect()->route('permissions.index');
})->name('admin.permissions');

Route::get('/admin/roles', function () {
    return redirect()->route('roles.index');
})->name('admin.roles');

Route::get('/admin/users', function () {
    return redirect()->route('users.index');
})->name('admin.users');

Route::get('/notifications/templates', function () {
    return redirect()->route('templates.index');
})->name('notifications.templates');

// ===========================================
// LEGACY API ROUTES (Backward Compatibility)
// ===========================================
Route::prefix('legacy-api')->name('legacy-api.')->group(function () {
    Route::get('/v1/health', function () {
        return redirect()->route('api.health');
    });
    
    Route::get('/v1/status', function () {
        return redirect()->route('api.status');
    });
});

// ===========================================
// DEVELOPMENT ROUTES (Only in Development)
// ===========================================
if (app()->environment(['local', 'development'])) {
    Route::prefix('dev')->name('dev.')->group(function () {
        
        // Development tools
        Route::get('/phpinfo', function () {
            return phpinfo();
        })->name('phpinfo');
        
        Route::get('/test-email', [SystemController::class, 'testEmail'])->name('test-email');
        Route::get('/test-teams', [SystemController::class, 'testTeams'])->name('test-teams');
        Route::get('/test-ldap', [SystemController::class, 'testLdap'])->name('test-ldap');
        Route::get('/test-queue', [SystemController::class, 'testQueue'])->name('test-queue');
        
        // Database seeding
        Route::post('/seed/permissions', function () {
            Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
            return back()->with('success', 'Permissions seeded successfully');
        })->name('seed.permissions');
        
        Route::post('/seed/users', function () {
            Artisan::call('db:seed', ['--class' => 'DefaultUserSeeder']);
            return back()->with('success', 'Users seeded successfully');
        })->name('seed.users');
        
        Route::post('/seed/templates', function () {
            Artisan::call('db:seed', ['--class' => 'NotificationTemplateSeeder']);
            return back()->with('success', 'Templates seeded successfully');
        })->name('seed.templates');
        
        // Cache management
        Route::post('/cache/clear-all', function () {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            return back()->with('success', 'All caches cleared successfully');
        })->name('cache.clear-all');
        
        // Migration helpers
        Route::get('/migrations/status', function () {
            $output = Artisan::call('migrate:status');
            return '<pre>' . Artisan::output() . '</pre>';
        })->name('migrations.status');
    });
}

// ===========================================
// ROUTE CACHING OPTIMIZATION (Production)
// ===========================================
if (app()->environment('production')) {
    Route::middleware(['cache.headers:public;max_age=3600'])->group(function () {
        Route::get('/permissions/matrix/view', [PermissionController::class, 'matrix'])->name('permissions.matrix.cached');
        Route::get('/permissions/export/csv', [PermissionController::class, 'export'])->name('permissions.export.cached');
        Route::get('/roles/export/csv', [RoleController::class, 'export'])->name('roles.export.cached');
        Route::get('/reports/delivery', [ReportController::class, 'delivery'])->name('reports.delivery.cached');
    });
}

// ===========================================
// ERROR HANDLING ROUTES
// ===========================================
Route::get('/403', function () {
    abort(403);
})->name('error.403');

Route::get('/404', function () {
    abort(404);
})->name('error.404');

Route::get('/500', function () {
    abort(500);
})->name('error.500');

// ===========================================
// MAINTENANCE MODE ROUTE
// ===========================================
Route::get('/maintenance', function () {
    return view('errors.503');
})->name('maintenance');

// ===========================================
// FALLBACK ROUTE (Must be last)
// ===========================================
Route::fallback(function () {
    if (request()->expectsJson()) {
        return response()->json([
            'success' => false,
            'message' => 'Page not found',
            'error_code' => 'PAGE_NOT_FOUND'
        ], 404);
    }
    
    return view('errors.404');
})->name('fallback');