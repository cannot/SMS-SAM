<?php

use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\User\NotificationController as UserNotificationController;
use App\Http\Controllers\Web\ActivityLogController;
// use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\GroupController;
use App\Http\Controllers\Web\NotificationTemplateController;
use App\Http\Controllers\Web\PermissionController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\UserPreferenceController;
use App\Http\Controllers\Api\V1\UserController as ApiUserController;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Services\TeamsService;

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
        'status'    => 'ok',
        'message'   => 'Application is working',
        'timestamp' => now(),
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

// Protected web interface routes
Route::middleware(['auth', 'web'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Route::prefix('user')->name('api.user.')->group(function () {
    //     Route::get('/stats', [ApiUserController::class, 'getUserStats'])->name('stats');
    //     Route::get('/notifications/unread', [ApiUserController::class, 'getUnreadNotifications'])->name('notifications.unread');
    //     Route::get('/profile', [ApiUserController::class, 'getProfile'])->name('profile');
    // });
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
        Route::post('/{permission}/assign-to-roles', [PermissionController::class, 'bulkAssignToRoles'])->name('bulk-assign-to-roles')->middleware('can:assign-permissions');
        Route::post('/{permission}/remove-from-roles', [PermissionController::class, 'bulkRemoveFromRoles'])->name('bulk-remove-from-roles')->middleware('can:delete-permissions');
        Route::post('/bulk/assign-multiple-to-roles', [PermissionController::class, 'bulkAssignMultipleToRoles'])->name('bulk-assign-multiple-to-roles')->middleware('can:assign-permissions');

        // Utility Operations
        Route::post('/{permission}/duplicate', [PermissionController::class, 'duplicate'])->name('duplicate')->middleware('can:create-permissions');
        Route::get('/export/csv', [PermissionController::class, 'export'])->name('export')->middleware('can:export-permissions');
        Route::post('/import/json', [PermissionController::class, 'import'])->name('import')->middleware('can:import-permissions');

        // Export routes with different formats
        Route::get('/export/excel', [PermissionController::class, 'exportExcel'])->name('export.excel')->middleware('can:export-permissions');
        Route::get('/export/pdf', [PermissionController::class, 'exportPdf'])->name('export.pdf')->middleware('can:export-permissions');
        Route::get('/export/json', [PermissionController::class, 'exportJson'])->name('export.json')->middleware('can:export-permissions');

        // Import routes with different formats
        Route::get('/import', [PermissionController::class, 'importForm'])->name('import.form')->middleware('can:import-permissions');
        Route::post('/import/csv', [PermissionController::class, 'importCsv'])->name('import.csv')->middleware('can:import-permissions');
        Route::post('/import/excel', [PermissionController::class, 'importExcel'])->name('import.excel')->middleware('can:import-permissions');
        Route::get('/import/template/{format}', [PermissionController::class, 'downloadTemplate'])->name('import.template')->middleware('can:import-permissions');

        // API Endpoints for AJAX
        Route::get('/api/stats', [PermissionController::class, 'getStats'])->name('api.stats')->middleware('can:view-permissions');
        Route::get('/api/search', [PermissionController::class, 'search'])->name('api.search')->middleware('can:view-permissions');

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
    });

    // ===========================================
    // USERS MANAGEMENT ROUTES (UPDATED)
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
        Route::get('/{user}/preferences', [UserPreferenceController::class, 'show'])->name('preferences.show')->middleware('can:manage-user-preferences');
        Route::patch('/{user}/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update.user')->middleware('can:manage-user-preferences');
        Route::delete('/{user}/preferences/reset', [UserPreferenceController::class, 'reset'])->name('preferences.reset.user')->middleware('can:manage-user-preferences');
        Route::post('/{user}/preferences/test', [UserPreferenceController::class, 'testNotification'])->name('preferences.test.user')->middleware('can:manage-user-preferences');
        Route::get('/{user}/preferences/export', [UserPreferenceController::class, 'export'])->name('preferences.export.user')->middleware('can:manage-user-preferences');
        Route::post('/{user}/preferences/import', [UserPreferenceController::class, 'import'])->name('preferences.import.user')->middleware('can:manage-user-preferences');

        // User Activity and Logs
        Route::get('/{user}/activities', [UserController::class, 'activities'])->name('activities')->middleware('can:view-activity-logs');

        // Soft Delete Management
        Route::post('/{id}/restore', [UserController::class, 'restore'])->name('restore')->middleware('can:manage-users');
        Route::delete('/{id}/force-destroy', [UserController::class, 'forceDestroy'])->name('force-destroy')->middleware('can:delete-users');
    });

    // // ===========================================
    // // API ROUTES FOR USER MANAGEMENT (ADD THESE)
    // // ===========================================
    // Route::middleware(['auth:api', 'throttle:60,1'])->prefix('api/admin')->name('api.admin.')->group(function () {

    //     // User CRUD API
    //     Route::prefix('users')->name('users.')->group(function () {
    //         Route::get('/', [UserController::class, 'index'])->name('index')->middleware('can:view-users');
    //         Route::post('/', [UserController::class, 'store'])->name('store')->middleware('can:create-users');
    //         Route::get('/{user}', [UserController::class, 'show'])->name('show')->middleware('can:view-users');
    //         Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('can:edit-users');
    //         Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('can:delete-users');

    //         // Bulk operations
    //         Route::post('/bulk', [UserController::class, 'bulkAction'])->name('bulk')->middleware('can:manage-users');
    //         Route::post('/import', [UserController::class, 'import'])->name('import')->middleware('can:create-users');
    //         Route::get('/export', [UserController::class, 'export'])->name('export')->middleware('can:view-users');

    //         // User statistics
    //         Route::get('/stats', [UserController::class, 'getStats'])->name('stats')->middleware('can:view-reports');
    //     });

    //     // LDAP operations
    //     Route::prefix('ldap')->name('ldap.')->group(function () {
    //         Route::post('/sync', [UserController::class, 'syncLdap'])->name('sync')->middleware('can:manage-ldap');
    //         Route::get('/status', [UserController::class, 'getLdapSyncStatus'])->name('status')->middleware('can:manage-ldap');
    //         // Route::post('/test', [LdapController::class, 'testConnection'])->name('test')->middleware('can:manage-ldap');
    //         // Route::get('/users/{username}', [LdapController::class, 'getUser'])->name('user')->middleware('can:manage-ldap');
    //     });
    // });

    // ===========================================
    // EXTERNAL API ROUTES (ADD THESE FOR INTEGRATIONS)
    // ===========================================
    // Route::middleware(['auth:api', 'throttle:300,1'])->prefix('api/v1')->name('api.v1.')->group(function () {

    //     // User information (read-only)
    //     Route::prefix('users')->name('users.')->group(function () {
    //         Route::get('/', [UserController::class, 'index'])->name('index');
    //         Route::get('/search', [UserController::class, 'search'])->name('search');
    //         Route::get('/{user}', [UserController::class, 'show'])->name('show');
    //     });

    //     // User groups
    //     Route::prefix('groups')->name('groups.')->group(function () {
    //         Route::get('/', [GroupController::class, 'index'])->name('index');
    //         Route::get('/{group}/users', [GroupController::class, 'getUsers'])->name('users');
    //     });

    //     // Department and organization structure
    //     Route::get('/departments', function() {
    //         $departments = \App\Models\User::active()
    //             ->whereNotNull('department')
    //             ->where('department', '!=', '')
    //             ->distinct()
    //             ->pluck('department')
    //             ->sort()
    //             ->values();

    //         return response()->json(['departments' => $departments]);
    //     })->name('departments');

    //     Route::get('/departments/{department}/users', function($department) {
    //         $users = \App\Models\User::active()
    //             ->where('department', $department)
    //             ->select(['id', 'username', 'display_name', 'email', 'department'])
    //             ->get();

    //         return response()->json(['users' => $users]);
    //     })->name('department-users');
    // });

    // ===========================================
    // AJAX ROUTES FOR DYNAMIC FUNCTIONALITY (UPDATE EXISTING)
    // ===========================================
    Route::middleware(['auth'])->prefix('ajax')->name('ajax.')->group(function () {
        // User autocomplete and search (ADD THESE)
        Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
        Route::get('/users/{user}/permissions', [UserController::class, 'ajaxGetPermissions'])->name('users.get-permissions');
        Route::get('/users/{user}/roles', [UserController::class, 'ajaxGetRoles'])->name('users.get-roles');
        Route::get('/users/{user}/groups', [UserController::class, 'ajaxGetGroups'])->name('users.get-groups');

        // Existing permission and role routes remain the same...
        Route::get('/permissions/search', [PermissionController::class, 'ajaxSearch'])->name('permissions.search');
        Route::get('/permissions/{permission}/roles', [PermissionController::class, 'ajaxGetRoles'])->name('permissions.get-roles');
        Route::get('/permissions/{permission}/users', [PermissionController::class, 'ajaxGetUsers'])->name('permissions.get-users');

        Route::get('/roles/search', [RoleController::class, 'ajaxSearch'])->name('roles.search');
        Route::get('/roles/{role}/permissions', [RoleController::class, 'ajaxGetPermissions'])->name('roles.get-permissions');

        // Category management
        Route::get('/permissions/categories', [PermissionController::class, 'getCategories'])->name('permissions.categories');
        Route::post('/permissions/categories', [PermissionController::class, 'createCategory'])->name('permissions.create-category');

        // Dashboard widgets
        Route::get('/dashboard/permissions/widget', [PermissionController::class, 'dashboardWidget'])->name('dashboard.permissions.widget');
        Route::get('/dashboard/permissions/chart-data', [PermissionController::class, 'chartData'])->name('dashboard.permissions.chart-data');

        // User dashboard widgets (ADD THESE)
        Route::get('/dashboard/users/widget', [UserController::class, 'dashboardWidget'])->name('dashboard.users.widget');
        Route::get('/dashboard/users/chart-data', [UserController::class, 'chartData'])->name('dashboard.users.chart-data');
        Route::get('/dashboard/users/stats', [UserController::class, 'getStats'])->name('dashboard.users.stats');
    });

    // ===========================================
    // ADDITIONAL ROUTE MODEL BINDING (ADD THIS)
    // ===========================================
    Route::bind('user', function ($value) {
        return \App\Models\User::where('id', $value)
            ->orWhere('username', $value)
            ->firstOrFail();
    });

    // Existing bindings remain the same
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

    // ===========================================
    // ACTIVITY LOGS ROUTES
    // ===========================================
    Route::prefix('activity-logs')->name('activity-logs.')->middleware('can:view-activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/{id}', [ActivityLogController::class, 'show'])->name('show');
        Route::get('/users/{userId}/activities', [ActivityLogController::class, 'getUserActivities'])->name('user-activities');
        Route::get('/export/csv', [ActivityLogController::class, 'export'])->name('export');
        Route::delete('/cleanup', [ActivityLogController::class, 'cleanup'])->name('cleanup')->middleware('can:delete-activity-logs');
    });

    // ===========================================
    // NOTIFICATION TEMPLATES ROUTES
    // ===========================================
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [NotificationTemplateController::class, 'index'])->name('index')->middleware('can:view-notification-templates');
        Route::get('/create', [NotificationTemplateController::class, 'create'])->name('create')->middleware('can:create-notification-templates');
        Route::post('/', [NotificationTemplateController::class, 'store'])->name('store')->middleware('can:create-notification-templates');
        Route::get('/{template}', [NotificationTemplateController::class, 'show'])->name('show')->middleware('can:view-notification-templates');
        Route::get('/{template}/edit', [NotificationTemplateController::class, 'edit'])->name('edit')->middleware('can:edit-notification-templates');
        Route::put('/{template}', [NotificationTemplateController::class, 'update'])->name('update')->middleware('can:edit-notification-templates');
        Route::delete('/{template}', [NotificationTemplateController::class, 'destroy'])->name('destroy')->middleware('can:delete-notification-templates');

        // Additional routes for the view
        Route::get('/{template}/preview', [NotificationTemplateController::class, 'preview'])->name('preview')->middleware('can:view-notification-templates');
        Route::get('/{template}/duplicate', [NotificationTemplateController::class, 'duplicate'])->name('duplicate')->middleware('can:create-notification-templates');
        Route::post('/{template}/toggle-status', [NotificationTemplateController::class, 'toggleStatus'])->name('toggle-status')->middleware('can:edit-notification-templates');
        Route::get('/export/{format}', [NotificationTemplateController::class, 'export'])->name('export')->middleware('can:export-templates');
        Route::post('/bulk-action', [NotificationTemplateController::class, 'bulkAction'])->name('bulk-action')->middleware('can:edit-notification-templates');
    });

    // ===========================================
    // NOTIFICATION MANAGEMENT ROUTES
    // ===========================================
    // Route::prefix('notifications')->name('notifications.')->group(function () {
    //     Route::get('/', [NotificationController::class, 'index'])->name('index')->middleware('can:view-notifications');
    //     Route::get('/create', [NotificationController::class, 'create'])->name('create')->middleware('can:create-notifications');
    //     Route::post('/', [NotificationController::class, 'store'])->name('store')->middleware('can:create-notifications');
    //     Route::get('/{notification}', [NotificationController::class, 'show'])->name('show')->middleware('can:view-notifications');
    //     Route::get('/{notification}/edit', [NotificationController::class, 'edit'])->name('edit')->middleware('can:edit-notifications');
    //     Route::put('/{notification}', [NotificationController::class, 'update'])->name('update')->middleware('can:edit-notifications');
    //     Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy')->middleware('can:delete-notifications');

    //     // Notification actions
    //     Route::post('/{notification}/send', [NotificationController::class, 'send'])->name('send')->middleware('can:send-notifications');
    //     Route::post('/{notification}/schedule', [NotificationController::class, 'schedule'])->name('schedule')->middleware('can:schedule-notifications');
    //     Route::post('/{notification}/cancel', [NotificationController::class, 'cancel'])->name('cancel')->middleware('can:cancel-notifications');
    //     Route::get('/{notification}/status', [NotificationController::class, 'status'])->name('status')->middleware('can:view-notifications');

    //     // Bulk operations
    //     Route::post('/bulk/send', [NotificationController::class, 'bulkSend'])->name('bulk-send')->middleware('can:send-notifications');
    //     Route::post('/bulk/schedule', [NotificationController::class, 'bulkSchedule'])->name('bulk-schedule')->middleware('can:schedule-notifications');
    //     Route::delete('/bulk/delete', [NotificationController::class, 'bulkDelete'])->name('bulk-delete')->middleware('can:delete-notifications');
    // });
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('received', [UserNotificationController::class, 'received'])->name('received');
        Route::get('received/{uuid}', [UserNotificationController::class, 'show'])->name('received.show');
        Route::post('received/{uuid}/mark-read', [UserNotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('received/{uuid}/mark-unread', [UserNotificationController::class, 'markAsUnread'])->name('mark-unread');
        Route::post('received/mark-all-read', [UserNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::post('received/{uuid}/delete', [UserNotificationController::class, 'delete'])->name('delete');
        Route::get('received/{uuid}/show', [UserNotificationController::class, 'show'])->name('show');
        Route::get('preferences', [UserNotificationController::class, 'preferences'])->name('preferences');
        Route::post('update-preferences', [UserNotificationController::class, 'update-preferences'])->name('update-preferences');
        Route::get('export', [UserNotificationController::class, 'export'])->name('export');
        Route::get('unread-count', [UserNotificationController::class, 'unreadCount'])->name('unread-count');

        Route::get('/', [UserNotificationController::class, 'index'])->name('index');
        Route::get('/{uuid}', [UserNotificationController::class, 'show'])->name('show');
        Route::get('/{uuid}/preview', [UserNotificationController::class, 'preview'])->name('preview');

    });

    Route::prefix('admin/notifications')->name('admin.notifications.')->group(function () {
        // Route::get('/', [AdminNotificationController::class, 'index'])->name('index')->middleware('can:manage-notifications');
        // Route::get('create', [AdminNotificationController::class, 'create'])->name('create')->middleware('can:manage-notifications');
        // Route::post('/', [AdminNotificationController::class, 'store'])->name('store')->middleware('can:manage-notifications');
        // Route::get('{uuid}', [AdminNotificationController::class, 'show'])->name('show')->middleware('can:manage-notifications');
        // Route::get('{uuid}/edit', [AdminNotificationController::class, 'edit'])->name('edit')->middleware('can:manage-notifications');
        // Route::put('{uuid}', [AdminNotificationController::class, 'update'])->name('update')->middleware('can:manage-notifications');
        // Route::delete('{uuid}', [AdminNotificationController::class, 'destroy'])->name('destroy')->middleware('can:manage-notifications');
        // Route::get('logs', [AdminNotificationController::class, 'logs'])->name('logs')->middleware('can:manage-notifications');

        // // New resend routes
        // Route::post('/{uuid}/resend', [AdminNotificationController::class, 'resend'])->name('resend');
        // Route::post('/{uuid}/logs/{log}/resend', [AdminNotificationController::class, 'resendLog'])->name('resend-log');
        // Route::get('/{uuid}/preview', [AdminNotificationController::class, 'preview'])->name('preview');

        // // Additional admin actions
        // Route::post('test', [AdminNotificationController::class, 'sendTest'])->name('test')->middleware('can:manage-notifications');
        // Route::post('{uuid}/cancel', [AdminNotificationController::class, 'cancel'])->name('cancel')->middleware('can:manage-notifications');
        // Route::post('{uuid}/duplicate', [AdminNotificationController::class, 'duplicate'])->name('duplicate')->middleware('can:manage-notifications');
        // Route::post('template-preview', [AdminNotificationController::class, 'templatePreview'])->name('template-preview')->middleware('can:manage-notifications');

        // // Analytics
        // Route::get('analytics/dashboard', [AdminNotificationController::class, 'analytics'])->name('analytics')->middleware('can:manage-notifications');
        // Route::get('statistics', [AdminNotificationController::class, 'statistics'])->name('statistics')->middleware('can:manage-notifications');

        // Route::post('/{uuid}/cancel', [AdminNotificationController::class, 'cancel'])->name('cancel');
        // Route::post('/{uuid}/retry', [AdminNotificationController::class, 'retry'])->name('retry');

        // Main CRUD routes
        Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
        Route::get('/create', [AdminNotificationController::class, 'create'])->name('create');
        Route::post('/', [AdminNotificationController::class, 'store'])->name('store');
        Route::get('/{uuid}', [AdminNotificationController::class, 'show'])->name('show');
        Route::get('/{uuid}/edit', [AdminNotificationController::class, 'edit'])->name('edit');
        Route::put('/{uuid}', [AdminNotificationController::class, 'update'])->name('update');
        Route::delete('/{uuid}', [AdminNotificationController::class, 'destroy'])->name('destroy');
        
        // Resend routes
        Route::post('/{uuid}/resend', [AdminNotificationController::class, 'resend'])->name('resend');
        Route::post('/{uuid}/logs/{log}/resend', [AdminNotificationController::class, 'resendLog'])->name('resend-log');
        
        // Action routes
        Route::post('/{uuid}/cancel', [AdminNotificationController::class, 'cancel'])->name('cancel');
        Route::post('/{uuid}/duplicate', [AdminNotificationController::class, 'duplicate'])->name('duplicate');
        
        // Preview and logs
        Route::get('/{uuid}/preview', [AdminNotificationController::class, 'preview'])->name('preview');
        Route::get('/{uuid}/logs', [AdminNotificationController::class, 'logs'])->name('logs');
        
        // Bulk actions
        Route::post('/bulk-action', [AdminNotificationController::class, 'bulkAction'])->name('bulk-action');
        
        // Export and stats
        Route::get('/export', [AdminNotificationController::class, 'export'])->name('export');
        Route::get('/stats', [AdminNotificationController::class, 'stats'])->name('stats');
        
        // Analytics
        Route::get('/analytics', [AdminNotificationController::class, 'analytics'])->name('analytics');
        
        // Template preview
        Route::post('/template-preview', [AdminNotificationController::class, 'templatePreview'])->name('template-preview');
        
        // Test notification
        Route::post('/send-test', [AdminNotificationController::class, 'sendTest'])->name('send-test');

        Route::post('/test-webhook', [AdminNotificationController::class, 'testWebhook'])
        ->name('test-webhook');

    });

    // ===========================================
    // GROUPS ROUTES (Fixed Order)
    // ===========================================
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::get('/create', [GroupController::class, 'create'])->name('create');
        Route::post('/', [GroupController::class, 'store'])->name('store');
        
        // ⚠️ IMPORTANT: Static routes MUST come BEFORE dynamic routes with parameters
        Route::get('/users', [GroupController::class, 'getUsers'])->name('users');
        Route::post('/bulk-sync', [GroupController::class, 'bulkSync'])->name('bulk-sync');
        Route::post('/preview-members', [GroupController::class, 'previewMembers'])->name('preview-members');
        
        // Dynamic routes with {group} parameter come AFTER static routes
        Route::get('/{group}', [GroupController::class, 'show'])->name('show');
        Route::get('/{group}/edit', [GroupController::class, 'edit'])->name('edit');
        Route::put('/{group}', [GroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy');

        // Group member management
        Route::post('/{group}/add-user', [GroupController::class, 'addUser'])->name('add-user');
        Route::post('/{group}/remove-user', [GroupController::class, 'removeUser'])->name('remove-user');
        Route::post('/{group}/sync', [GroupController::class, 'syncMembers'])->name('sync');
        Route::get('/{group}/export', [GroupController::class, 'exportMembers'])->name('export');
    });

    // Route::prefix('groups')->name('groups.')->group(function () {
    //     Route::get('/', [GroupController::class, 'index'])->name('index');
    //     Route::get('/create', [GroupController::class, 'create'])->name('create');
    //     Route::post('/', [GroupController::class, 'store'])->name('store');
    //     Route::get('/{group}', [GroupController::class, 'show'])->name('show');
    //     Route::get('/{group}/edit', [GroupController::class, 'edit'])->name('edit');
    //     Route::put('/{group}', [GroupController::class, 'update'])->name('update');
    //     Route::delete('/{group}', [GroupController::class, 'destroy'])->name('destroy');

    //     // Group member management
    //     Route::post('/{group}/add-user', [GroupController::class, 'addUser'])->name('add-user');
    //     Route::post('/{group}/remove-user', [GroupController::class, 'removeUser'])->name('remove-user');
    //     Route::post('/{group}/sync', [GroupController::class, 'syncMembers'])->name('sync');
    //     Route::post('/bulk-sync', [GroupController::class, 'bulkSync'])->name('bulk-sync');
    //     Route::get('/{group}/export', [GroupController::class, 'exportMembers'])->name('export');
    //     Route::get('/users', [GroupController::class, 'getUsers'])->name('users');
    // });

    // ===========================================
    // REPORTS ROUTES
    // ===========================================
    Route::prefix('reports')->name('reports.')->middleware('can:view-reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/delivery', [ReportController::class, 'delivery'])->name('delivery');
        Route::get('/api-usage', [ReportController::class, 'apiUsage'])->name('api-usage')->middleware('can:view-api-usage-reports');
        Route::get('/user-activity', [ReportController::class, 'userActivity'])->name('user-activity');
        Route::get('/permission-usage', [ReportController::class, 'permissionUsage'])->name('permission-usage');
        Route::get('/role-distribution', [ReportController::class, 'roleDistribution'])->name('role-distribution');
        Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
        Route::get('/export/{type}/{format}', [ReportController::class, 'export'])->name('export');
    });

    // ===========================================
    // ADMIN ROUTES
    // ===========================================
    Route::prefix('admin')->name('admin.')->middleware('can:manage-api-keys')->group(function () {

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
            Route::post('/{apiKey}/regenerate', [ApiKeyController::class, 'regenerate'])->name('regenerate')->middleware('permission:regenerate-api-keys');
            Route::post('/{apiKey}/toggle-status', [ApiKeyController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/{apiKey}/usage-stats', [ApiKeyController::class, 'usageStats'])->name('usage-stats');
            Route::get('/{apiKey}/usage-history', [ApiKeyController::class, 'usageHistory'])->name('usage-history');
            Route::get('/{apiKey}/usage', [ApiKeyController::class, 'usage'])->name('usage');
            Route::get('/{apiKey}/audit', [ApiKeyController::class, 'audit'])->name('audit');
            Route::post('/{apiKey}/reset-usage', [ApiKeyController::class, 'resetUsage'])->name('reset-usage');
            Route::get('/export/csv', [ApiKeyController::class, 'export'])->name('export');

            // Export and audit
            Route::get('/export/{format}', [ApiKeyController::class, 'export'])->name('export');
            Route::get('/audit-log', [ApiKeyController::class, 'auditLog'])->name('audit-log');

            // Bulk operations
            Route::post('/bulk/toggle-status', [ApiKeyController::class, 'bulkToggleStatus'])->name('bulk.toggle-status');
            Route::post('/bulk/update-limits', [ApiKeyController::class, 'bulkUpdateLimits'])->name('bulk.update-limits');
            Route::delete('/bulk/delete', [ApiKeyController::class, 'bulkDelete'])->name('bulk.delete');

        });

        // System Logs
        Route::get('/system-logs', function () {
            return "System logs - Coming soon!";
        })->name('system-logs')->middleware('can:view-system-logs');

        // System Settings
        Route::get('/settings', function () {
            return "System settings - Coming soon!";
        })->name('settings')->middleware('can:manage-system-settings');

        // System Maintenance
        Route::prefix('maintenance')->name('maintenance.')->middleware('can:system-maintenance')->group(function () {
            Route::get('/', function () {return "System maintenance - Coming soon!";})->name('index');
            Route::post('/cache/clear', function () {return "Cache cleared!";})->name('cache.clear');
            Route::post('/permissions/rebuild', [PermissionController::class, 'rebuildCache'])->name('permissions.rebuild');
            Route::post('/queue/restart', function () {return "Queue restarted!";})->name('queue.restart');
        });
    });

});

// ===========================================
// AJAX ROUTES FOR DYNAMIC FUNCTIONALITY
// ===========================================
Route::middleware(['auth'])->prefix('ajax')->name('ajax.')->group(function () {
    // Permission autocomplete
    Route::get('/permissions/search', [PermissionController::class, 'ajaxSearch'])->name('permissions.search');
    Route::get('/permissions/{permission}/roles', [PermissionController::class, 'ajaxGetRoles'])->name('permissions.get-roles');
    Route::get('/permissions/{permission}/users', [PermissionController::class, 'ajaxGetUsers'])->name('permissions.get-users');

    // Role autocomplete
    Route::get('/roles/search', [RoleController::class, 'ajaxSearch'])->name('roles.search');
    Route::get('/roles/{role}/permissions', [RoleController::class, 'ajaxGetPermissions'])->name('roles.get-permissions');

    // User autocomplete
    Route::get('/users/search', [UserController::class, 'ajaxSearch'])->name('users.search');
    Route::get('/users/{user}/permissions', [UserController::class, 'ajaxGetPermissions'])->name('users.get-permissions');
    Route::get('/users/{user}/roles', [UserController::class, 'ajaxGetRoles'])->name('users.get-roles');

    // Category management
    Route::get('/permissions/categories', [PermissionController::class, 'getCategories'])->name('permissions.categories');
    Route::post('/permissions/categories', [PermissionController::class, 'createCategory'])->name('permissions.create-category');

    // Dashboard widgets
    Route::get('/dashboard/permissions/widget', [PermissionController::class, 'dashboardWidget'])->name('dashboard.permissions.widget');
    Route::get('/dashboard/permissions/chart-data', [PermissionController::class, 'chartData'])->name('dashboard.permissions.chart-data');
});

// ===========================================
// ROUTE MODEL BINDING
// ===========================================
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

// ===========================================
// FALLBACK ROUTES FOR BACKWARD COMPATIBILITY
// ===========================================
Route::get('/admin/permissions', function () {
    return redirect()->route('permissions.index');
})->name('admin.permissions');

Route::get('/admin/roles', function () {
    return redirect()->route('roles.index');
})->name('admin.roles');

// ===========================================
// WEBHOOK ROUTES FOR EXTERNAL INTEGRATIONS
// ===========================================
Route::middleware(['auth:sanctum'])->prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/permissions/created', [PermissionController::class, 'webhookCreated'])->name('permissions.created');
    Route::post('/permissions/updated', [PermissionController::class, 'webhookUpdated'])->name('permissions.updated');
    Route::post('/permissions/deleted', [PermissionController::class, 'webhookDeleted'])->name('permissions.deleted');
    Route::post('/roles/permission-assigned', [RoleController::class, 'webhookPermissionAssigned'])->name('roles.permission-assigned');
    Route::post('/roles/permission-removed', [RoleController::class, 'webhookPermissionRemoved'])->name('roles.permission-removed');
});

// ===========================================
// ROUTE CACHING OPTIMIZATION (PRODUCTION)
// ===========================================
if (app()->environment('production')) {
    Route::middleware(['cache.headers:public;max_age=3600'])->group(function () {
        Route::get('/permissions/matrix/view', [PermissionController::class, 'matrix'])->name('permissions.matrix.cached');
        Route::get('/permissions/export/csv', [PermissionController::class, 'export'])->name('permissions.export.cached');
        Route::get('/roles/export/csv', [RoleController::class, 'export'])->name('roles.export.cached');
    });
}

// Debug routes (only in development)
if (app()->environment(['local', 'development'])) {
    
    Route::prefix('debug')->group(function () {
        
        // 1. Check Teams configuration
        Route::get('/teams-config', function() {
            return response()->json([
                'has_client_id' => !empty(config('services.teams.client_id')),
                'has_client_secret' => !empty(config('services.teams.client_secret')),
                'has_tenant_id' => !empty(config('services.teams.tenant_id')),
                'use_mock' => env('USE_MOCK_TEAMS', false),
                'environment' => app()->environment(),
                'client_id_preview' => config('services.teams.client_id') ? 
                    substr(config('services.teams.client_id'), 0, 8) . '...' : null,
            ]);
        });
        
        Route::get('/curl-test', function() {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://httpbin.org/ip');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            return response()->json([
                'curl_test' => $response ? 'OK' : 'Failed',
                'http_code' => $httpCode,
                'error' => $error,
                'response' => $response
            ]);
        });
    });
}

// Add this to routes/web.php in the debug group

Route::post('/teams-send-test', function(Request $request) {
    try {
        $userEmail = $request->input('user_email', 'panwad@sam.or.th');
        $subject = $request->input('subject', 'Test Message from API');
        $message = $request->input('message', 'This is a test message from Postman');
        $priority = $request->input('priority', 'normal');
        
        // Create a mock user object
        $user = (object) [
            'email' => $userEmail,
            'username' => explode('@', $userEmail)[0],
            'display_name' => 'Test User'
        ];
        
        // Prepare Teams data
        $teamsData = [
            'user' => $user,
            'subject' => $subject,
            'message' => $message,
            'priority' => $priority,
            'delivery_method' => 'direct',
            'variables' => [
                'recipient_name' => 'Test User',
                'recipient_email' => $userEmail,
                'notification_title' => $subject,
                'content' => $message
            ]
        ];
        
        // Get Teams Service
        $teamsService = app(TeamsService::class);
        $serviceClass = get_class($teamsService);
        
        // Send message
        $result = $teamsService->sendDirect($teamsData);
        
        return response()->json([
            'request_data' => $teamsData,
            'service_class' => $serviceClass,
            'is_mock' => $serviceClass === 'App\Services\MockTeamsService',
            'result' => $result,
            'timestamp' => now()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});