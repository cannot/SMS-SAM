@extends('layouts.app')

@section('title', 'Manage Permissions - ' . $user->display_name)

@push('styles')
<style>
.permission-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    background: white;
}

.permission-card:hover {
    border-color: #007bff;
    background: rgba(0, 123, 255, 0.02);
    transform: translateY(-1px);
    box-shadow: 0 2px 10px rgba(0, 123, 255, 0.1);
}

.permission-card.selected {
    border-color: #007bff;
    background: rgba(0, 123, 255, 0.08);
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
}

.permission-card.via-role {
    border-color: #28a745;
    background: rgba(40, 167, 69, 0.05);
    position: relative;
    cursor: default;
}

.permission-card.via-role::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #28a745, #20c997);
    border-radius: 10px;
    z-index: -1;
}

.permission-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    color: white;
    font-weight: bold;
}

.permission-create { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
.permission-read { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); }
.permission-update { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); }
.permission-delete { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
.permission-manage { background: linear-gradient(135deg, #6f42c1 0%, #59359a 100%); }
.permission-view { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }
.permission-system { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 20px;
}

.permission-category {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border-left: 4px solid #007bff;
}

.permission-search {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: none;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.floating-actions {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 100;
}

.floating-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.floating-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
}

.btn-save {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.btn-help {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}

.role-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    margin: 0.1rem;
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.permission-summary {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.summary-stat {
    text-align: center;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.category-header {
    position: sticky;
    top: 0;
    z-index: 10;
    background: white;
    padding: 1rem 0;
    margin: 1.5rem 0 1rem 0;
    border-bottom: 2px solid #dee2e6;
}

.permissions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.category-toggle {
    cursor: pointer;
    user-select: none;
    padding: 0.5rem;
    border-radius: 8px;
    transition: background-color 0.2s;
}

.category-toggle:hover {
    background-color: #f8f9fa;
}

.category-content {
    margin-top: 1rem;
}

.category-content.collapsed {
    display: none;
}

.via-role-indicator {
    position: absolute;
    top: 8px;
    right: 8px;
    background: #28a745;
    color: white;
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-weight: bold;
}

.changes-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
    display: none;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Changes Indicator -->
    <div id="changesIndicator" class="changes-indicator alert alert-info">
        <i class="fas fa-edit me-2"></i>
        <span id="changesText">You have unsaved changes</span>
        <button type="button" class="btn btn-sm btn-primary ms-2" onclick="saveChanges()">
            Save Now
        </button>
    </div>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.show', $user) }}">{{ $user->display_name }}</a></li>
                    <li class="breadcrumb-item active">Manage Permissions</li>
                </ol>
            </nav>
            <h2><i class="fas fa-key me-2"></i>Manage User Permissions</h2>
            <p class="text-muted mb-0">Configure direct permissions for {{ $user->display_name }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Profile
            </a>
        </div>
    </div>

    <div class="row">
        <!-- User Info & Summary -->
        <div class="col-lg-4 mb-4">
            <!-- User Info -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="user-avatar mx-auto mb-3">
                        {{ $user->initials }}
                    </div>
                    <h5 class="mb-1">{{ $user->display_name }}</h5>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    @if($user->department)
                        <span class="badge bg-secondary">{{ $user->department }}</span>
                    @endif
                </div>
            </div>

            <!-- Current Roles -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Current Roles</h6>
                </div>
                <div class="card-body">
                    @forelse($user->roles as $role)
                        <div class="d-flex align-items-center mb-2">
                            <div class="permission-icon permission-manage me-2" style="width: 25px; height: 25px; font-size: 10px;">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div>
                                <strong>{{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}</strong>
                                <br>
                                <small class="text-muted">{{ $role->permissions->count() }} permissions</small>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center">No roles assigned</p>
                    @endforelse
                </div>
            </div>

            <!-- Permission Summary -->
            <div class="permission-summary">
                <h6 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Permission Summary</h6>
                <div class="row">
                    <div class="col-6">
                        <div class="summary-stat">
                            <h4 class="text-primary" id="totalDirectPermissions">{{ $user->permissions->count() }}</h4>
                            <small>Direct Permissions</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="summary-stat">
                            <h4 class="text-success" id="totalViaRoles">{{ $user->getPermissionsViaRoles()->count() }}</h4>
                            <small>Via Roles</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="summary-stat">
                            <h4 class="text-info" id="totalAllPermissions">{{ $user->getAllPermissions()->count() }}</h4>
                            <small>Total Permissions</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="summary-stat">
                            <h4 class="text-warning" id="pendingChanges">0</h4>
                            <small>Pending Changes</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectByCategory('create')">
                            <i class="fas fa-plus me-1"></i>Select All Create
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="selectByCategory('read')">
                            <i class="fas fa-eye me-1"></i>Select All Read
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="selectByCategory('update')">
                            <i class="fas fa-edit me-1"></i>Select All Update
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="selectByCategory('delete')">
                            <i class="fas fa-trash me-1"></i>Select All Delete
                        </button>
                        <hr>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllPermissions()">
                            <i class="fas fa-times me-1"></i>Clear All Direct
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Permissions Management -->
        <div class="col-lg-8">
            <!-- Search and Filters -->
            <div class="permission-search">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="permissionSearch" placeholder="Search permissions...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <option value="user">User Management</option>
                            <option value="notification">Notifications</option>
                            <option value="group">Groups</option>
                            <option value="api">API Access</option>
                            <option value="system">System</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="sourceFilter">
                            <option value="">All Sources</option>
                            <option value="direct">Direct Only</option>
                            <option value="role">Via Role Only</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="showRolePermissions" checked>
                            <label class="form-check-label" for="showRolePermissions">
                                Show permissions inherited from roles
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="expandAllCategories()">
                            <i class="fas fa-expand me-1"></i>Expand All
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="collapseAllCategories()">
                            <i class="fas fa-compress me-1"></i>Collapse All
                        </button>
                    </div>
                </div>
            </div>

            <!-- Permissions Form -->
            <form method="POST" action="{{ route('users.update-permissions', $user) }}" id="permissionsForm">
                @csrf
                @method('PATCH')

                @php
                    $permissionsByCategory = $allPermissions->groupBy(function($permission) {
                        return getPermissionCategory($permission->name);
                    });
                    $userDirectPermissions = $user->permissions->pluck('id')->toArray();
                    $userRolePermissions = $user->getPermissionsViaRoles()->pluck('id')->toArray();
                @endphp

                @foreach($permissionsByCategory as $category => $permissions)
                    <div class="permission-category" data-category="{{ $category }}">
                        <div class="category-toggle" onclick="toggleCategory('{{ $category }}')">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-chevron-down me-2" id="chevron-{{ $category }}"></i>
                                    <i class="fas {{ getCategoryIcon($category) }} me-2"></i>
                                    {{ ucfirst($category) }} Permissions
                                </h5>
                                <div>
                                    <span class="badge bg-primary">{{ $permissions->count() }} total</span>
                                    <span class="badge bg-success" id="selected-{{ $category }}">
                                        {{ $permissions->whereIn('id', $userDirectPermissions)->count() }} selected
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="category-content" id="content-{{ $category }}">
                            <div class="permissions-grid">
                                @foreach($permissions as $permission)
                                    @php
                                        $isDirectPermission = in_array($permission->id, $userDirectPermissions);
                                        $isRolePermission = in_array($permission->id, $userRolePermissions);
                                        $viaRoles = $user->getPermissionsViaRoles()->where('id', $permission->id)->first();
                                    @endphp
                                    
                                    <div class="permission-card {{ $isDirectPermission ? 'selected' : '' }} {{ $isRolePermission && !$isDirectPermission ? 'via-role' : '' }}"
                                         data-permission-id="{{ $permission->id }}"
                                         data-permission-name="{{ $permission->name }}"
                                         data-category="{{ $category }}"
                                         onclick="togglePermission({{ $permission->id }}, '{{ $permission->name }}', {{ $isRolePermission ? 'true' : 'false' }})">
                                        
                                        @if($isRolePermission && !$isDirectPermission)
                                            <div class="via-role-indicator">
                                                Via Role
                                            </div>
                                        @endif
                                        
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" 
                                               id="permission_{{ $permission->id }}" class="d-none permission-checkbox"
                                               {{ $isDirectPermission ? 'checked' : '' }}>
                                        
                                        <div class="d-flex align-items-start">
                                            <div class="permission-icon permission-{{ getPermissionType($permission->name) }} me-3">
                                                <i class="fas {{ getPermissionIcon($permission->name) }}"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">{{ $permission->display_name ?? ucfirst(str_replace(['_', '-'], ' ', $permission->name)) }}</h6>
                                                <p class="text-muted small mb-2">{{ $permission->description ?? getDefaultPermissionDescription($permission->name) }}</p>
                                                
                                                @if($isRolePermission)
                                                    <div class="mt-2">
                                                        <small class="text-muted">Via roles:</small>
                                                        @foreach($user->roles as $role)
                                                            @if($role->permissions->contains('id', $permission->id))
                                                                <span class="role-badge">{{ $role->display_name ?? $role->name }}</span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                                
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-tag me-1"></i>{{ $permission->name }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- No permissions found -->
                <div id="noPermissionsFound" class="text-center py-5" style="display: none;">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No permissions found</h5>
                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Action Buttons -->
    <div class="floating-actions">
        <button type="button" class="floating-btn btn-help" onclick="showPermissionHelp()" title="Help">
            <i class="fas fa-question"></i>
        </button>
        <button type="submit" form="permissionsForm" class="floating-btn btn-save" id="saveBtn" title="Save Changes">
            <i class="fas fa-save"></i>
        </button>
    </div>
</div>

<!-- Permission Help Modal -->
<div class="modal fade" id="permissionHelpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-question-circle me-2"></i>Permission Management Help
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle me-2"></i>Permission Types</h6>
                        <div class="mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <div class="permission-icon permission-create me-2" style="width: 20px; height: 20px; font-size: 10px;">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <span><strong>Create:</strong> Add new items</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="permission-icon permission-read me-2" style="width: 20px; height: 20px; font-size: 10px;">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <span><strong>Read:</strong> View existing items</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="permission-icon permission-update me-2" style="width: 20px; height: 20px; font-size: 10px;">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <span><strong>Update:</strong> Modify existing items</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="permission-icon permission-delete me-2" style="width: 20px; height: 20px; font-size: 10px;">
                                    <i class="fas fa-trash"></i>
                                </div>
                                <span><strong>Delete:</strong> Remove items</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-keyboard me-2"></i>Keyboard Shortcuts</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><kbd>Ctrl</kbd> + <kbd>S</kbd> - Save changes</li>
                            <li class="mb-2"><kbd>Ctrl</kbd> + <kbd>F</kbd> - Focus search</li>
                            <li class="mb-2"><kbd>Escape</kbd> - Clear search</li>
                            <li class="mb-2"><kbd>Space</kbd> - Toggle permission</li>
                        </ul>
                    </div>
                </div>
                
                <hr>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> Direct permissions supplement role permissions. Users will have all permissions from both sources.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let originalPermissions = [];
let currentPermissions = [];
let changesCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    initializePermissionManager();
    setupEventListeners();
});

function initializePermissionManager() {
    originalPermissions = Array.from(document.querySelectorAll('.permission-checkbox:checked')).map(cb => cb.value);
    currentPermissions = [...originalPermissions];
    
    setupSearch();
    setupFilters();
    updateCategoryCounts();
}

function togglePermission(permissionId, permissionName, isRolePermission) {
    const checkbox = document.getElementById(`permission_${permissionId}`);
    const card = document.querySelector(`[data-permission-id="${permissionId}"]`);
    
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        card.classList.add('selected');
        if (!currentPermissions.includes(String(permissionId))) {
            currentPermissions.push(String(permissionId));
        }
    } else {
        card.classList.remove('selected');
        currentPermissions = currentPermissions.filter(id => id !== String(permissionId));
    }
    
    updateChangesCounter();
    updateCategoryCounts();
}

function selectByCategory(action) {
    const permissions = document.querySelectorAll(`[data-permission-name*="${action}"]`);
    let selectedCount = 0;
    
    permissions.forEach(card => {
        const permissionId = card.dataset.permissionId;
        const checkbox = document.getElementById(`permission_${permissionId}`);
        
        if (!checkbox.checked) {
            checkbox.checked = true;
            card.classList.add('selected');
            if (!currentPermissions.includes(String(permissionId))) {
                currentPermissions.push(String(permissionId));
            }
            selectedCount++;
        }
    });
    
    if (selectedCount > 0) {
        showAlert('success', `Selected ${selectedCount} ${action} permissions`);
        updateChangesCounter();
        updateCategoryCounts();
    }
}

function clearAllPermissions() {
    if (!confirm('Are you sure you want to clear all direct permissions?')) {
        return;
    }
    
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
        const card = document.querySelector(`[data-permission-id="${checkbox.value}"]`);
        card.classList.remove('selected');
    });
    
    currentPermissions = [];
    updateChangesCounter();
    updateCategoryCounts();
}

function toggleCategory(category) {
    const content = document.getElementById(`content-${category}`);
    const chevron = document.getElementById(`chevron-${category}`);
    
    if (content.classList.contains('collapsed')) {
        content.classList.remove('collapsed');
        chevron.classList.remove('fa-chevron-right');
        chevron.classList.add('fa-chevron-down');
    } else {
        content.classList.add('collapsed');
        chevron.classList.remove('fa-chevron-down');
        chevron.classList.add('fa-chevron-right');
    }
}

function expandAllCategories() {
    document.querySelectorAll('.category-content').forEach(content => {
        content.classList.remove('collapsed');
    });
    document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
        chevron.classList.remove('fa-chevron-right');
        chevron.classList.add('fa-chevron-down');
    });
}

function collapseAllCategories() {
    document.querySelectorAll('.category-content').forEach(content => {
        content.classList.add('collapsed');
    });
    document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
        chevron.classList.remove('fa-chevron-down');
        chevron.classList.add('fa-chevron-right');
    });
}

function updateChangesCounter() {
    const added = currentPermissions.filter(p => !originalPermissions.includes(p));
    const removed = originalPermissions.filter(p => !currentPermissions.includes(p));
    changesCount = added.length + removed.length;
    
    document.getElementById('pendingChanges').textContent = changesCount;
    document.getElementById('totalDirectPermissions').textContent = currentPermissions.length;
    
    const indicator = document.getElementById('changesIndicator');
    const saveBtn = document.getElementById('saveBtn');
    
    if (changesCount > 0) {
        indicator.style.display = 'block';
        document.getElementById('changesText').textContent = `You have ${changesCount} unsaved changes`;
        saveBtn.style.background = 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';
    } else {
        indicator.style.display = 'none';
        saveBtn.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
    }
}

function updateCategoryCounts() {
    document.querySelectorAll('[data-category]').forEach(categoryDiv => {
        const category = categoryDiv.dataset.category;
        const selectedInCategory = categoryDiv.querySelectorAll('.permission-checkbox:checked').length;
        const selectedBadge = document.getElementById(`selected-${category}`);
        if (selectedBadge) {
            selectedBadge.textContent = `${selectedInCategory} selected`;
        }
    });
}

function setupSearch() {
    const searchInput = document.getElementById('permissionSearch');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filterPermissions();
        }, 300);
    });
}

function setupFilters() {
    document.getElementById('categoryFilter').addEventListener('change', filterPermissions);
    document.getElementById('sourceFilter').addEventListener('change', filterPermissions);
    document.getElementById('showRolePermissions').addEventListener('change', function() {
        toggleRolePermissionVisibility(this.checked);
    });
}

function filterPermissions() {
    const searchTerm = document.getElementById('permissionSearch').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const sourceFilter = document.getElementById('sourceFilter').value;
    
    let visibleCount = 0;
    let visibleCategories = new Set();
    
    document.querySelectorAll('.permission-card').forEach(card => {
        const permissionName = card.dataset.permissionName.toLowerCase();
        const category = card.dataset.category;
        const isDirect = card.classList.contains('selected');
        const isViaRole = card.classList.contains('via-role');
        
        const matchesSearch = permissionName.includes(searchTerm) || 
                            card.textContent.toLowerCase().includes(searchTerm);
        const matchesCategory = !categoryFilter || category === categoryFilter;
        
        let matchesSource = true;
        if (sourceFilter === 'direct') {
            matchesSource = isDirect;
        } else if (sourceFilter === 'role') {
            matchesSource = isViaRole && !isDirect;
        }
        
        if (matchesSearch && matchesCategory && matchesSource) {
            card.style.display = 'block';
            visibleCount++;
            visibleCategories.add(category);
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show/hide categories
    document.querySelectorAll('.permission-category').forEach(categoryDiv => {
        const category = categoryDiv.dataset.category;
        if (visibleCategories.has(category)) {
            categoryDiv.style.display = 'block';
        } else {
            categoryDiv.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    document.getElementById('noPermissionsFound').style.display = visibleCount === 0 ? 'block' : 'none';
}

function toggleRolePermissionVisibility(show) {
    document.querySelectorAll('.permission-card.via-role').forEach(card => {
        if (!card.classList.contains('selected')) {
            card.style.display = show ? 'block' : 'none';
        }
    });
}

function saveChanges() {
    document.getElementById('permissionsForm').submit();
}

function showPermissionHelp() {
    const modal = new bootstrap.Modal(document.getElementById('permissionHelpModal'));
    modal.show();
}

function setupEventListeners() {
    // Form submission
    document.getElementById('permissionsForm').addEventListener('submit', function(e) {
        if (changesCount === 0) {
            e.preventDefault();
            showAlert('info', 'No changes to save.');
            return;
        }
        
        const saveBtn = document.getElementById('saveBtn');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        saveBtn.disabled = true;
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveChanges();
        }
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            document.getElementById('permissionSearch').focus();
        }
        if (e.key === 'Escape') {
            document.getElementById('permissionSearch').value = '';
            filterPermissions();
        }
    });
    
    // Prevent accidental navigation
    window.addEventListener('beforeunload', function(e) {
        if (changesCount > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
}

// Utility functions
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 80px; right: 20px; z-index: 1055; max-width: 400px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>

@php
function getPermissionCategory($permissionName) {
    if (str_contains($permissionName, 'user')) return 'user';
    if (str_contains($permissionName, 'notification')) return 'notification';
    if (str_contains($permissionName, 'group')) return 'group';
    if (str_contains($permissionName, 'api')) return 'api';
    return 'system';
}

function getCategoryIcon($category) {
    $icons = [
        'user' => 'fa-users',
        'notification' => 'fa-bell',
        'group' => 'fa-layer-group',
        'api' => 'fa-code',
        'system' => 'fa-cogs'
    ];
    return $icons[$category] ?? 'fa-key';
}

function getPermissionType($permissionName) {
    if (str_contains($permissionName, 'create')) return 'create';
    if (str_contains($permissionName, 'read') || str_contains($permissionName, 'view')) return 'read';
    if (str_contains($permissionName, 'update') || str_contains($permissionName, 'edit')) return 'update';
    if (str_contains($permissionName, 'delete')) return 'delete';
    if (str_contains($permissionName, 'manage')) return 'manage';
    return 'view';
}

function getPermissionIcon($permissionName) {
    if (str_contains($permissionName, 'create')) return 'fa-plus';
    if (str_contains($permissionName, 'read') || str_contains($permissionName, 'view')) return 'fa-eye';
    if (str_contains($permissionName, 'update') || str_contains($permissionName, 'edit')) return 'fa-edit';
    if (str_contains($permissionName, 'delete')) return 'fa-trash';
    if (str_contains($permissionName, 'manage')) return 'fa-cogs';
    return 'fa-key';
}

function getDefaultPermissionDescription($permissionName) {
    $type = getPermissionType($permissionName);
    $category = getPermissionCategory($permissionName);
    
    $descriptions = [
        'create' => "Allows creating new {$category} items",
        'read' => "Allows viewing {$category} information",
        'update' => "Allows modifying existing {$category} items",
        'delete' => "Allows removing {$category} items",
        'manage' => "Full management access for {$category}"
    ];
    
    return $descriptions[$type] ?? "Permission related to {$category} functionality";
}
@endphp
@endpush