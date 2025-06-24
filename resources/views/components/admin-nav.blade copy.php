{{-- Admin Navigation Component --}}
@canany(['view-roles', 'view-permissions', 'view-permission-matrix', 'view-api-keys', 'manage-api-keys', 'view-reports', 'view-analytics', 'view-system-logs', 'manage-settings', 'system-maintenance'])

<!-- Admin Section Header -->
<li class="nav-item">
    <h6 class="sidebar-heading">
        <i class="fas fa-shield-alt text-warning"></i> 
        <span class="nav-text">การจัดการระบบ</span>
    </h6>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('users*') && !request()->routeIs('users.preferences*') && !request()->routeIs('users.permissions*') ? 'active' : '' }}"
        href="{{ route('users.index') }}">
        <i class="bi bi-person-lines-fill"></i>
        <span class="nav-text">จัดการผู้ใช้งาน</span>
        <span class="badge bg-light text-dark ms-auto" data-stat="active_users">
            {{ \App\Models\User::where('is_active', true)->count() }}
        </span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link py-1 {{ request()->routeIs('templates*') ? 'active' : '' }}" 
       href="{{ route('templates.index') }}">
        <i class="bi bi-file-earmark-text"></i>
        <span class="nav-text">จัดการ Templates</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link py-1 {{ request()->routeIs('activity-logs*') ? 'active' : '' }}" 
       href="{{ route('activity-logs.index') }}">
        <i class="bi bi-file-earmark-text"></i>
        <span class="nav-text">รายงานการทำงานของผู้ใช้งาน</span>
    </a>
</li>

<!-- Access Control Section -->
@canany(['view-roles', 'view-permissions', 'view-permission-matrix'])
<li class="nav-item">
    <h6 class="sidebar-heading ms-3">
        <i class="fas fa-key text-info"></i> 
        <span class="nav-text">การจัดการสิทธิ์</span>
    </h6>
</li>

@can('view-roles')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('roles*') ? 'active' : '' }}"
        href="{{ route('roles.index') }}">
        <i class="fas fa-shield-alt"></i>
        <span class="nav-text">บทบาท (Roles)</span>
        <span class="badge bg-light text-dark ms-auto" data-stat="roles_count">
            {{ \Spatie\Permission\Models\Role::count() }}
        </span>
    </a>
</li>
@endcan

@can('view-permissions')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('permissions*') && !request()->routeIs('permissions.matrix') ? 'active' : '' }}"
        href="{{ route('permissions.index') }}">
        <i class="fas fa-key"></i>
        <span class="nav-text">สิทธิ์ (Permissions)</span>
        <span class="badge bg-light text-dark ms-auto" data-stat="permissions_count">
            {{ \Spatie\Permission\Models\Permission::count() }}
        </span>
    </a>
</li>
@endcan

@can('view-permission-matrix')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('permissions.matrix') ? 'active' : '' }}"
        href="{{ route('permissions.matrix') }}">
        <i class="fas fa-table"></i>
        <span class="nav-text">Permission Matrix</span>
    </a>
</li>
@endcan

<!-- Quick Actions Dropdown for Access Control -->
@canany(['create-roles', 'create-permissions', 'export-roles', 'export-permissions'])
<li class="nav-item">
    <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#accessControlActions" 
       aria-expanded="false" aria-controls="accessControlActions">
        <i class="fas fa-tools"></i>
        <span class="nav-text">เครื่องมือสิทธิ์</span>
        <i class="fas fa-chevron-down ms-auto nav-text"></i>
    </a>
    <div class="collapse" id="accessControlActions">
        <ul class="nav flex-column ms-3">
            @can('create-roles')
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('roles.create') }}">
                    <i class="fas fa-plus"></i>
                    <span class="nav-text">สร้างบทบาท</span>
                </a>
            </li>
            @endcan
            
            @can('create-permissions')
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('permissions.create') }}">
                    <i class="fas fa-plus"></i>
                    <span class="nav-text">สร้างสิทธิ์</span>
                </a>
            </li>
            @endcan
            
            @can('export-roles')
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('roles.export') }}">
                    <i class="fas fa-download"></i>
                    <span class="nav-text">Export Roles</span>
                </a>
            </li>
            @endcan
            
            @can('export-permissions')
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('permissions.export') }}">
                    <i class="fas fa-download"></i>
                    <span class="nav-text">Export Permissions</span>
                </a>
            </li>
            @endcan
        </ul>
    </div>
</li>
@endcanany
@endcanany

<!-- API Management Section -->
@canany(['view-api-keys', 'manage-api-keys', 'view-api-usage'])
<li class="nav-item">
    <h6 class="sidebar-heading ms-3">
        <i class="fas fa-code text-secondary"></i> 
        <span class="nav-text">API Management</span>
    </h6>
</li>

@can('view-api-keys')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.api-keys*') ? 'active' : '' }}"
        href="{{ route('admin.api-keys.index') }}">
        <i class="fas fa-key"></i>
        <span class="nav-text">API Keys</span>
        <span class="badge bg-light text-dark ms-auto" data-stat="active_api_keys">
            {{ \App\Models\ApiKey::where('is_active', true)->count() }}
        </span>
    </a>
</li>
@endcan

{{-- @can('view-api-usage')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('api-usage*') ? 'active' : '' }}"
        href="{{ route('api-usage.index') }}">
        <i class="fas fa-chart-line"></i>
        <span class="nav-text">API Usage</span>
    </a>
</li>
@endcan

@can('manage-api-keys')
<li class="nav-item">
    <a class="nav-link" href="{{ route('admin.api-keys.create') }}">
        <i class="fas fa-plus"></i>
        <span class="nav-text">Create API Key</span>
    </a>
</li>
@endcan
@endcanany

<!-- Reports & Analytics Section -->
@canany(['view-reports', 'view-analytics', 'view-system-logs'])
<li class="nav-item">
    <h6 class="sidebar-heading ms-3">
        <i class="fas fa-chart-bar text-danger"></i> 
        <span class="nav-text">รายงาน & วิเคราะห์</span>
    </h6>
</li>

@can('view-analytics')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('analytics*') ? 'active' : '' }}"
        href="{{ route('analytics.dashboard') }}">
        <i class="fas fa-analytics"></i>
        <span class="nav-text">Analytics</span>
    </a>
</li>
@endcan

@can('view-system-logs')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.system-logs*') ? 'active' : '' }}"
        href="{{ route('admin.system-logs') }}">
        <i class="fas fa-list-alt"></i>
        <span class="nav-text">System Logs</span>
    </a>
</li>
@endcan --}}

<!-- Reports Dropdown -->
@canany(['export-user-reports', 'export-notification-reports', 'export-api-reports'])
<li class="nav-item">
    <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#reportsActions" 
       aria-expanded="false" aria-controls="reportsActions">
        <i class="fas fa-download"></i>
        <span class="nav-text">Export Reports</span>
        <i class="fas fa-chevron-down ms-auto nav-text"></i>
    </a>
    <div class="collapse" id="reportsActions">
        <ul class="nav flex-column ms-3">
            @can('export-user-reports')
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('reports.users.export') }}">
                    <i class="fas fa-users"></i>
                    <span class="nav-text">User Report</span>
                </a>
            </li>
            @endcan
            
            @can('export-notification-reports')
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('reports.notifications.export') }}">
                    <i class="fas fa-bell"></i>
                    <span class="nav-text">Notification Report</span>
                </a>
            </li>
            @endcan
            
            @can('export-api-reports')
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('reports.api.export') }}">
                    <i class="fas fa-code"></i>
                    <span class="nav-text">API Usage Report</span>
                </a>
            </li>
            @endcan
        </ul>
    </div>
</li>
@endcanany
@endcanany

<!-- System Settings Section -->
@canany(['manage-settings', 'system-maintenance', 'view-system-status'])
<li class="nav-item">
    <h6 class="sidebar-heading ms-3">
        <i class="fas fa-cogs text-dark"></i> 
        <span class="nav-text">ระบบ</span>
    </h6>
</li>

@can('manage-settings')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('settings*') ? 'active' : '' }}"
        href="{{ route('settings.index') }}">
        <i class="fas fa-cog"></i>
        <span class="nav-text">Settings</span>
    </a>
</li>
@endcan

@can('system-maintenance')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('maintenance*') ? 'active' : '' }}"
        href="{{ route('maintenance.index') }}">
        <i class="fas fa-tools"></i>
        <span class="nav-text">Maintenance</span>
    </a>
</li>
@endcan

@can('view-system-status')
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('system.status') ? 'active' : '' }}"
        href="{{ route('system.status') }}">
        <i class="fas fa-heartbeat"></i>
        <span class="nav-text">System Status</span>
        <span class="status-indicator status-online ms-auto"></span>
    </a>
</li>
@endcan

@can('manage-users')
<li class="nav-item">
    <a class="nav-link" href="#" onclick="syncLdapUsers(event)">
        <i class="fas fa-sync"></i>
        <span class="nav-text">Sync LDAP</span>
    </a>
</li>
@endcan
@endcanany

<!-- Admin Quick Stats Section -->
@canany(['view-dashboard', 'view-users', 'view-notifications'])
<li class="nav-item mt-4">
    <h6 class="sidebar-heading">
        <i class="fas fa-info-circle"></i> 
        <span class="nav-text">สถิติด่วน</span>
    </h6>
</li>

<li class="nav-item">
    <div class="mx-3 p-3 rounded" style="background: rgba(255, 255, 255, 0.1);">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-light">ผู้ใช้งาน</small>
            <span class="badge bg-success" data-stat="active_users">
                {{ \App\Models\User::where('is_active', true)->count() }}
            </span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-light">บทบาท</small>
            <span class="badge bg-primary" data-stat="total_roles">
                {{ \Spatie\Permission\Models\Role::count() }}
            </span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-light">สิทธิ์</small>
            <span class="badge bg-info" data-stat="total_permissions">
                {{ \Spatie\Permission\Models\Permission::count() }}
            </span>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-light">API Keys</small>
            <span class="badge bg-warning" data-stat="active_api_keys">
                {{ \App\Models\ApiKey::where('is_active', true)->count() }}
            </span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-light">แจ้งเตือนวันนี้</small>
            <span class="badge bg-danger" data-stat="today_notifications">
                {{ \App\Models\Notification::whereDate('created_at', today())->count() }}
            </span>
        </div>
    </div>
</li>
@endcanany

<!-- Admin Tools Section -->
@canany(['manage-user-permissions', 'bulk-operations'])
<li class="nav-item mt-3">
    <h6 class="sidebar-heading">
        <i class="fas fa-tools"></i> 
        <span class="nav-text">เครื่องมือ Admin</span>
    </h6>
</li>

@can('manage-user-permissions')
<li class="nav-item">
    <a class="nav-link" href="#" onclick="openUserPermissionModal(event)">
        <i class="fas fa-user-cog"></i>
        <span class="nav-text">จัดการสิทธิ์ผู้ใช้</span>
    </a>
</li>
@endcan

@can('bulk-operations')
<li class="nav-item">
    <a class="nav-link" href="#" onclick="openBulkOperationsModal(event)">
        <i class="fas fa-layer-group"></i>
        <span class="nav-text">Bulk Operations</span>
    </a>
</li>
@endcan

<li class="nav-item">
    <a class="nav-link" href="#" onclick="openSystemInfoModal(event)">
        <i class="fas fa-info-circle"></i>
        <span class="nav-text">System Info</span>
    </a>
</li>
@endcanany

@endcanany

<!-- User Permission Management Modal -->
<div class="modal fade" id="userPermissionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">จัดการสิทธิ์ผู้ใช้</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <label for="user_search" class="form-label">ค้นหาผู้ใช้</label>
                        <input type="text" class="form-control" id="user_search" placeholder="ชื่อหรืออีเมล...">
                        <div id="user_results" class="mt-2 border rounded p-2" style="max-height: 200px; overflow-y: auto; display: none;">
                            <!-- Search results will be populated here -->
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">การดำเนินการ</label>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary" onclick="manageUserRoles()" disabled id="manageRolesBtn">
                                <i class="fas fa-shield-alt me-2"></i>จัดการบทบาท
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="manageUserPermissions()" disabled id="managePermissionsBtn">
                                <i class="fas fa-key me-2"></i>จัดการสิทธิ์
                            </button>
                            <button type="button" class="btn btn-outline-success" onclick="viewUserMatrix()" disabled id="viewMatrixBtn">
                                <i class="fas fa-table me-2"></i>ดู Permission Matrix
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Operations Modal -->
<div class="modal fade" id="bulkOperationsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Operations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>User Operations</h6>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary" onclick="bulkActivateUsers()">
                                <i class="fas fa-user-check me-2"></i>Bulk Activate Users
                            </button>
                            <button type="button" class="btn btn-outline-warning" onclick="bulkDeactivateUsers()">
                                <i class="fas fa-user-slash me-2"></i>Bulk Deactivate Users
                            </button>
                            <button type="button" class="btn btn-outline-info" onclick="bulkAssignRoles()">
                                <i class="fas fa-shield-alt me-2"></i>Bulk Assign Roles
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>System Operations</h6>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-success" onclick="clearSystemCache()">
                                <i class="fas fa-broom me-2"></i>Clear Cache
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="generateBackup()">
                                <i class="fas fa-database me-2"></i>Generate Backup
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="cleanupLogs()">
                                <i class="fas fa-trash me-2"></i>Cleanup Old Logs
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Info Modal -->
<div class="modal fade" id="systemInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">System Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Application Info</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Version:</td>
                                <td>{{ config('app.version', '1.0.0') }}</td>
                            </tr>
                            <tr>
                                <td>Environment:</td>
                                <td><span class="badge bg-{{ app()->environment() === 'production' ? 'success' : 'warning' }}">{{ app()->environment() }}</span></td>
                            </tr>
                            <tr>
                                <td>Debug Mode:</td>
                                <td><span class="badge bg-{{ config('app.debug') ? 'danger' : 'success' }}">{{ config('app.debug') ? 'ON' : 'OFF' }}</span></td>
                            </tr>
                            <tr>
                                <td>PHP Version:</td>
                                <td>{{ PHP_VERSION }}</td>
                            </tr>
                            <tr>
                                <td>Laravel Version:</td>
                                <td>{{ app()->version() }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>System Stats</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Total Users:</td>
                                <td><span class="badge bg-primary">{{ \App\Models\User::count() }}</span></td>
                            </tr>
                            <tr>
                                <td>Total Roles:</td>
                                <td><span class="badge bg-info">{{ \Spatie\Permission\Models\Role::count() }}</span></td>
                            </tr>
                            <tr>
                                <td>Total Permissions:</td>
                                <td><span class="badge bg-success">{{ \Spatie\Permission\Models\Permission::count() }}</span></td>
                            </tr>
                            <tr>
                                <td>Active API Keys:</td>
                                <td><span class="badge bg-warning">{{ \App\Models\ApiKey::where('is_active', true)->count() }}</span></td>
                            </tr>
                            <tr>
                                <td>Total Notifications:</td>
                                <td><span class="badge bg-secondary">{{ \App\Models\Notification::count() }}</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// LDAP Sync function
async function syncLdapUsers(event) {
    event.preventDefault();
    
    if (!confirm('This will sync users from LDAP. This process may take several minutes. Continue?')) {
        return;
    }
    
    const link = event.target.closest('.nav-link');
    const originalContent = link.innerHTML;
    link.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span class="nav-text">Syncing...</span>';
    
    try {
        const response = await fetch('{{ route('users.sync-ldap') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.App.csrfToken
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification('LDAP sync completed successfully', 'success');
            updateAdminStats();
        } else {
            window.AppFunctions.showNotification('LDAP sync failed: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('LDAP sync error:', error);
        window.AppFunctions.showNotification('LDAP sync failed. Please try again.', 'error');
    } finally {
        link.innerHTML = originalContent;
    }
}

// Open user permission modal
function openUserPermissionModal(event) {
    event.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('userPermissionModal'));
    modal.show();
}

// Open bulk operations modal
function openBulkOperationsModal(event) {
    event.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('bulkOperationsModal'));
    modal.show();
}

// Open system info modal
function openSystemInfoModal(event) {
    event.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('systemInfoModal'));
    modal.show();
}

// User search functionality
let searchTimeout;
let selectedUserId = null;

document.addEventListener('DOMContentLoaded', function() {
    const userSearchInput = document.getElementById('user_search');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            if (searchTerm.length >= 2) {
                searchTimeout = setTimeout(() => {
                    searchUsers(searchTerm);
                }, 300);
            } else {
                hideUserResults();
            }
        });
    }
});

async function searchUsers(searchTerm) {
    try {
        const response = await fetch(`/api/users/search?q=${encodeURIComponent(searchTerm)}`);
        const users = await response.json();
        
        const resultsDiv = document.getElementById('user_results');
        
        if (users.length > 0) {
            resultsDiv.innerHTML = users.map(user => `
                <div class="user-result-item p-2 border-bottom cursor-pointer" 
                     onclick="selectUser(${user.id}, '${user.display_name}', '${user.email}')"
                     style="cursor: pointer;">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar me-2" style="width: 24px; height: 24px; font-size: 0.7rem;">
                            ${user.initials || user.display_name.substring(0, 2).toUpperCase()}
                        </div>
                        <div>
                            <strong>${user.display_name}</strong><br>
                            <small class="text-muted">${user.email}</small>
                        </div>
                    </div>
                </div>
            `).join('');
            resultsDiv.style.display = 'block';
        } else {
            resultsDiv.innerHTML = '<div class="p-2 text-muted text-center">ไม่พบผู้ใช้</div>';
            resultsDiv.style.display = 'block';
        }
    } catch (error) {
        console.error('User search error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการค้นหา', 'error');
    }
}

function hideUserResults() {
    const resultsDiv = document.getElementById('user_results');
    resultsDiv.style.display = 'none';
    selectedUserId = null;
    toggleActionButtons(false);
}

function selectUser(userId, displayName, email) {
    selectedUserId = userId;
    
    // Update search input
    document.getElementById('user_search').value = `${displayName} (${email})`;
    
    // Hide results
    hideUserResults();
    
    // Enable action buttons
    toggleActionButtons(true);
    
    // Highlight selected user
    document.querySelectorAll('.user-result-item').forEach(item => {
        item.classList.remove('bg-primary', 'text-white');
    });
}

function toggleActionButtons(enabled) {
    document.getElementById('manageRolesBtn').disabled = !enabled;
    document.getElementById('managePermissionsBtn').disabled = !enabled;
    document.getElementById('viewMatrixBtn').disabled = !enabled;
}

function manageUserRoles() {
    if (!selectedUserId) {
        window.AppFunctions.showNotification('กรุณาเลือกผู้ใช้ก่อน', 'warning');
        return;
    }
    window.location.href = `/users/${selectedUserId}#roles`;
}

function manageUserPermissions() {
    if (!selectedUserId) {
        window.AppFunctions.showNotification('กรุณาเลือกผู้ใช้ก่อน', 'warning');
        return;
    }
    window.location.href = `/users/${selectedUserId}/permissions`;
}

function viewUserMatrix() {
    if (!selectedUserId) {
        window.AppFunctions.showNotification('กรุณาเลือกผู้ใช้ก่อน', 'warning');
        return;
    }
    window.location.href = `/permissions/matrix?user=${selectedUserId}`;
}

// Bulk Operations
async function bulkActivateUsers() {
    const userIds = await promptForUserIds('เลือกผู้ใช้ที่ต้องการเปิดใช้งาน');
    if (userIds.length === 0) return;
    
    try {
        const response = await fetch('/api/users/bulk-activate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.App.csrfToken
            },
            body: JSON.stringify({ user_ids: userIds })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification(`เปิดใช้งานผู้ใช้ ${result.count} คนเรียบร้อยแล้ว`, 'success');
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาด: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Bulk activate error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการเปิดใช้งาน', 'error');
    }
}

async function bulkDeactivateUsers() {
    const userIds = await promptForUserIds('เลือกผู้ใช้ที่ต้องการปิดใช้งาน');
    if (userIds.length === 0) return;
    
    if (!confirm(`คุณแน่ใจหรือไม่ที่จะปิดใช้งานผู้ใช้ ${userIds.length} คน?`)) {
        return;
    }
    
    try {
        const response = await fetch('/api/users/bulk-deactivate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.App.csrfToken
            },
            body: JSON.stringify({ user_ids: userIds })
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification(`ปิดใช้งานผู้ใช้ ${result.count} คนเรียบร้อยแล้ว`, 'success');
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาด: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Bulk deactivate error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการปิดใช้งาน', 'error');
    }
}

async function bulkAssignRoles() {
    window.AppFunctions.showNotification('ฟีเจอร์นี้อยู่ระหว่างการพัฒนา', 'info');
}

// System Operations
async function clearSystemCache() {
    if (!confirm('คุณแน่ใจหรือไม่ที่จะล้าง Cache ระบบ?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/system/clear-cache', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.App.csrfToken
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification('ล้าง Cache เรียบร้อยแล้ว', 'success');
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาด: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Clear cache error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการล้าง Cache', 'error');
    }
}

async function generateBackup() {
    if (!confirm('คุณต้องการสร้าง Backup ระบบหรือไม่? กระบวนการนี้อาจใช้เวลาสักครู่')) {
        return;
    }
    
    try {
        window.AppFunctions.showNotification('กำลังสร้าง Backup...', 'info');
        
        const response = await fetch('/api/system/backup', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.App.csrfToken
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification('สร้าง Backup เรียบร้อยแล้ว', 'success');
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาด: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Backup error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการสร้าง Backup', 'error');
    }
}

async function cleanupLogs() {
    if (!confirm('คุณต้องการล้าง Log เก่าหรือไม่? การดำเนินการนี้จะลบ Log ที่เก่ากว่า 30 วัน')) {
        return;
    }
    
    try {
        const response = await fetch('/api/system/cleanup-logs', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.App.csrfToken
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification(`ล้าง Log เรียบร้อยแล้ว (ลบไป ${result.deleted_count} ไฟล์)`, 'success');
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาด: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Cleanup logs error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการล้าง Log', 'error');
    }
}

// Helper function to prompt for user IDs
async function promptForUserIds(message) {
    // This is a simplified version - in a real app, you'd want a proper user selection modal
    const input = prompt(`${message}\nกรุณาใส่ User ID (คั่นด้วยจุลภาค):`);
    if (!input) return [];
    
    return input.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id));
}

// Update admin statistics
function updateAdminStats() {
    fetch('/api/admin/stats')
        .then(response => response.json())
        .then(data => {
            // Update all stat elements
            document.querySelectorAll('[data-stat]').forEach(element => {
                const stat = element.dataset.stat;
                if (data[stat] !== undefined) {
                    element.textContent = data[stat];
                }
            });
        })
        .catch(error => console.log('Failed to update admin stats:', error));
}

// Auto-update admin stats every 5 minutes
setInterval(updateAdminStats, 300000);

// Initialize admin navigation
document.addEventListener('DOMContentLoaded', function() {
    // Initialize collapse states
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(element => {
        element.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            const icon = this.querySelector('.fa-chevron-down, .fa-chevron-up');
            
            if (icon) {
                setTimeout(() => {
                    if (target.classList.contains('show')) {
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                    } else {
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }
                }, 100);
            }
        });
    });
    
    // Close user results when clicking outside
    document.addEventListener('click', function(event) {
        const userSearch = document.getElementById('user_search');
        const userResults = document.getElementById('user_results');
        
        if (userSearch && userResults && 
            !userSearch.contains(event.target) && 
            !userResults.contains(event.target)) {
            hideUserResults();
        }
    });
});

// Export admin functions for global use
window.AdminFunctions = {
    syncLdapUsers,
    openUserPermissionModal,
    openBulkOperationsModal,
    openSystemInfoModal,
    searchUsers,
    selectUser,
    manageUserRoles,
    manageUserPermissions,
    viewUserMatrix,
    bulkActivateUsers,
    bulkDeactivateUsers,
    bulkAssignRoles,
    clearSystemCache,
    generateBackup,
    cleanupLogs,
    updateAdminStats
};
</script>