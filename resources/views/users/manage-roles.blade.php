@extends('layouts.app')

@section('title', 'Manage Roles - ' . $user->display_name)

@push('styles')
<style>
.role-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.role-card:hover {
    border-color: #007bff;
    background: rgba(0, 123, 255, 0.02);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.1);
}

.role-card.selected {
    border-color: #007bff;
    background: rgba(0, 123, 255, 0.1);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
}

.role-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: #f8f9fa;
}

.role-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: white;
    font-weight: bold;
}

.role-admin { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
.role-manager { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); }
.role-user { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
.role-api-user { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }
.role-notification-manager { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); }
.role-default { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }

.permission-list {
    max-height: 200px;
    overflow-y: auto;
    background: #f8f9fa;
    border-radius: 8px;
    padding: 0.75rem;
    margin-top: 1rem;
}

.permission-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    margin: 0.1rem;
    background: rgba(0, 123, 255, 0.1);
    color: #007bff;
    border: 1px solid rgba(0, 123, 255, 0.2);
}

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

.current-roles {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.save-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
    display: none;
}

.role-search {
    position: sticky;
    top: 0;
    z-index: 10;
    background: white;
    padding: 1rem 0;
    margin-bottom: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.quick-actions {
    position: sticky;
    bottom: 20px;
    left: 0;
    right: 0;
    z-index: 100;
    display: flex;
    justify-content: center;
}

.floating-save-btn {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    border-radius: 50px;
    padding: 1rem 2rem;
    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.3);
    color: white;
    font-weight: bold;
    transition: all 0.3s ease;
}

.floating-save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(40, 167, 69, 0.4);
}

.role-stats {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 0.75rem;
    margin-top: 1rem;
    font-size: 0.875rem;
}

.breadcrumb-modern {
    background: none;
    padding: 0;
    margin-bottom: 1rem;
}

.breadcrumb-modern .breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #6c757d;
    font-weight: bold;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Save Indicator -->
    <div id="saveIndicator" class="save-indicator alert alert-success">
        <i class="fas fa-check me-2"></i>Changes saved successfully!
    </div>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb" class="breadcrumb-modern">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.show', $user) }}">{{ $user->display_name }}</a></li>
                    <li class="breadcrumb-item active">Manage Roles</li>
                </ol>
            </nav>
            <h2><i class="fas fa-user-shield me-2"></i>Manage User Roles</h2>
            <p class="text-muted mb-0">Assign roles and permissions to {{ $user->display_name }}</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Profile
                </a>
                <button type="button" class="btn btn-outline-info" onclick="showRoleHelp()">
                    <i class="fas fa-question-circle me-1"></i>Help
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- User Info & Current Roles -->
        <div class="col-lg-4 mb-4">
            <!-- User Info Card -->
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
                    @if($user->title)
                        <p class="text-muted mt-2 mb-0">{{ $user->title }}</p>
                    @endif
                </div>
            </div>

            <!-- Current Roles -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Current Roles</h6>
                </div>
                <div class="card-body">
                    <div id="currentRolesDisplay">
                        @forelse($user->roles as $role)
                            <div class="d-flex align-items-center mb-2" id="current-role-{{ $role->id }}">
                                <div class="role-icon role-{{ str_replace(['_', '-'], '-', $role->name) }} me-2" style="width: 30px; height: 30px; font-size: 12px;">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <strong>{{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $role->permissions->count() }} permissions</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRole({{ $role->id }})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @empty
                            <div class="text-center text-muted" id="no-roles-message">
                                <i class="fas fa-info-circle mb-2"></i>
                                <p>No roles assigned</p>
                                <small>Select roles below to assign them</small>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Role Statistics -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Role Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h4 class="text-primary" id="totalRolesCount">{{ $user->roles->count() }}</h4>
                            <small>Assigned Roles</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h4 class="text-success" id="totalPermissionsCount">{{ $user->getAllPermissions()->count() }}</h4>
                            <small>Total Permissions</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info">{{ $allRoles->count() }}</h4>
                            <small>Available Roles</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning" id="directPermissionsCount">{{ $user->permissions->count() }}</h4>
                            <small>Direct Permissions</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Selection -->
        <div class="col-lg-8">
            <!-- Search and Filters -->
            <div class="role-search">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="roleSearch" placeholder="Search roles...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="roleFilter">
                            <option value="">All Categories</option>
                            <option value="admin">Admin Roles</option>
                            <option value="manager">Manager Roles</option>
                            <option value="user">User Roles</option>
                            <option value="api">API Roles</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="btn-group w-100">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAllVisible()">
                                <i class="fas fa-check-square me-1"></i>Select All
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllSelections()">
                                <i class="fas fa-times me-1"></i>Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Assignment Form -->
            <form method="POST" action="{{ route('users.update-roles', $user) }}" id="roleForm">
                @csrf
                @method('PATCH')
                
                <div class="row" id="rolesContainer">
                    @foreach($allRoles as $role)
                        <div class="col-md-6 mb-3 role-item" 
                             data-role-name="{{ strtolower($role->name) }}"
                             data-role-category="{{ getRoleCategory($role->name) }}"
                             data-permissions-count="{{ $role->permissions->count() }}">
                            <div class="role-card {{ in_array($role->id, $userRoles) ? 'selected' : '' }}" 
                                 onclick="toggleRole({{ $role->id }}, '{{ $role->name }}')"
                                 id="role-card-{{ $role->id }}">
                                
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                                       id="role_{{ $role->id }}" class="d-none role-checkbox"
                                       {{ in_array($role->id, $userRoles) ? 'checked' : '' }}
                                       @if($role->name === 'super-admin' && !auth()->user()->hasRole('super-admin')) disabled @endif>
                                
                                <!-- Role Header -->
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="role-icon role-{{ str_replace(['_', '-'], '-', $role->name) }}">
                                        <i class="fas {{ getRoleIcon($role->name) }}"></i>
                                    </div>
                                    <div class="text-end">
                                        @if(in_array($role->id, $userRoles))
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Assigned
                                            </span>
                                        @endif
                                        @if($role->name === 'super-admin')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-crown me-1"></i>Super Admin
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Role Info -->
                                <h6 class="mb-2">{{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}</h6>
                                
                                @if($role->description)
                                    <p class="text-muted small mb-2">{{ $role->description }}</p>
                                @else
                                    <p class="text-muted small mb-2">{{ getDefaultRoleDescription($role->name) }}</p>
                                @endif
                                
                                <!-- Role Statistics -->
                                <div class="role-stats">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <strong>{{ $role->permissions->count() }}</strong>
                                            <br><small>Permissions</small>
                                        </div>
                                        <div class="col-6">
                                            <strong>{{ $role->users->count() }}</strong>
                                            <br><small>Users</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Permissions List -->
                                @if($role->permissions->count() > 0)
                                    <div class="permission-list">
                                        <small class="text-muted fw-bold">Permissions:</small>
                                        <div class="mt-2">
                                            @foreach($role->permissions->take(8) as $permission)
                                                <span class="permission-badge">{{ $permission->display_name ?? ucfirst(str_replace('_', ' ', $permission->name)) }}</span>
                                            @endforeach
                                            @if($role->permissions->count() > 8)
                                                <span class="permission-badge bg-light text-dark">
                                                    +{{ $role->permissions->count() - 8 }} more
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <!-- Role Actions -->
                                <div class="mt-3 d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Created: {{ $role->created_at ? $role->created_at->format('M Y') : 'System' }}
                                    </small>
                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                            onclick="viewRoleDetails({{ $role->id }}); event.stopPropagation();">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- No roles found message -->
                <div id="noRolesFound" class="text-center py-5" style="display: none;">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No roles found</h5>
                    <p class="text-muted">Try adjusting your search criteria.</p>
                </div>
            </form>
        </div>
    </div>

    <!-- Floating Save Button -->
    <div class="quick-actions">
        <button type="submit" form="roleForm" class="floating-save-btn" id="saveBtn">
            <i class="fas fa-save me-2"></i>Save Changes
            <span class="badge bg-white text-dark ms-2" id="changesCount" style="display: none;">0</span>
        </button>
    </div>
</div>

<!-- Role Details Modal -->
<div class="modal fade" id="roleDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-shield-alt me-2"></i>Role Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="roleDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-question-circle me-2"></i>Role Management Help
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-mouse me-2"></i>How to Use</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-click text-primary me-2"></i>Click on role cards to select/deselect</li>
                            <li class="mb-2"><i class="fas fa-search text-info me-2"></i>Use search to find specific roles</li>
                            <li class="mb-2"><i class="fas fa-filter text-warning me-2"></i>Filter by role category</li>
                            <li class="mb-2"><i class="fas fa-save text-success me-2"></i>Click save to apply changes</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-keyboard me-2"></i>Keyboard Shortcuts</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><kbd>Ctrl</kbd> + <kbd>S</kbd> - Save changes</li>
                            <li class="mb-2"><kbd>Ctrl</kbd> + <kbd>F</kbd> - Focus search</li>
                            <li class="mb-2"><kbd>Ctrl</kbd> + <kbd>A</kbd> - Select all visible</li>
                            <li class="mb-2"><kbd>Escape</kbd> - Clear search</li>
                        </ul>
                    </div>
                </div>
                
                <hr>
                
                <h6><i class="fas fa-shield-alt me-2"></i>Role Types</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <div class="role-icon role-admin me-2" style="width: 25px; height: 25px; font-size: 10px;">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div>
                                <strong>Admin Roles</strong>
                                <br><small class="text-muted">Full system access</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="role-icon role-manager me-2" style="width: 25px; height: 25px; font-size: 10px;">
                                <i class="fas fa-users-cog"></i>
                            </div>
                            <div>
                                <strong>Manager Roles</strong>
                                <br><small class="text-muted">Management permissions</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <div class="role-icon role-user me-2" style="width: 25px; height: 25px; font-size: 10px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <strong>User Roles</strong>
                                <br><small class="text-muted">Standard user access</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="role-icon role-api-user me-2" style="width: 25px; height: 25px; font-size: 10px;">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div>
                                <strong>API Roles</strong>
                                <br><small class="text-muted">API access only</small>
                            </div>
                        </div>
                    </div>
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
let originalRoles = [];
let currentRoles = [];
let changesCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    initializeRoleManager();
    setupEventListeners();
    trackChanges();
});

function initializeRoleManager() {
    // Store original roles
    originalRoles = Array.from(document.querySelectorAll('.role-checkbox:checked')).map(cb => cb.value);
    currentRoles = [...originalRoles];
    
    // Initialize search
    setupSearch();
    
    // Initialize filters
    setupFilters();
}

function toggleRole(roleId, roleName) {
    const checkbox = document.getElementById(`role_${roleId}`);
    const card = document.getElementById(`role-card-${roleId}`);
    
    // Don't allow super-admin toggle for non-super-admins
    if (roleName === 'super-admin' && checkbox.disabled) {
        showAlert('warning', 'Only super administrators can assign the super-admin role.');
        return;
    }
    
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        card.classList.add('selected');
        currentRoles.push(String(roleId));
        addToCurrentRoles(roleId, roleName);
    } else {
        card.classList.remove('selected');
        currentRoles = currentRoles.filter(id => id !== String(roleId));
        removeFromCurrentRoles(roleId);
    }
    
    updateChangesCounter();
    updateCurrentRolesDisplay();
}

function addToCurrentRoles(roleId, roleName) {
    const currentRolesDisplay = document.getElementById('currentRolesDisplay');
    const noRolesMessage = document.getElementById('no-roles-message');
    
    if (noRolesMessage) {
        noRolesMessage.style.display = 'none';
    }
    
    // Don't add if already exists
    if (document.getElementById(`current-role-${roleId}`)) {
        return;
    }
    
    const roleElement = document.createElement('div');
    roleElement.className = 'd-flex align-items-center mb-2';
    roleElement.id = `current-role-${roleId}`;
    roleElement.innerHTML = `
        <div class="role-icon role-${roleName.replace(/[_-]/g, '-')} me-2" style="width: 30px; height: 30px; font-size: 12px;">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="flex-grow-1">
            <strong>${formatRoleName(roleName)}</strong>
            <br>
            <small class="text-muted">Recently assigned</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRole(${roleId})">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    currentRolesDisplay.appendChild(roleElement);
}

function removeFromCurrentRoles(roleId) {
    const element = document.getElementById(`current-role-${roleId}`);
    if (element) {
        element.remove();
    }
    
    // Show no roles message if no roles left
    const currentRolesDisplay = document.getElementById('currentRolesDisplay');
    if (currentRolesDisplay.children.length === 0) {
        currentRolesDisplay.innerHTML = `
            <div class="text-center text-muted" id="no-roles-message">
                <i class="fas fa-info-circle mb-2"></i>
                <p>No roles assigned</p>
                <small>Select roles below to assign them</small>
            </div>
        `;
    }
}

function removeRole(roleId) {
    const checkbox = document.getElementById(`role_${roleId}`);
    const card = document.getElementById(`role-card-${roleId}`);
    
    if (checkbox && card) {
        checkbox.checked = false;
        card.classList.remove('selected');
        currentRoles = currentRoles.filter(id => id !== String(roleId));
        removeFromCurrentRoles(roleId);
        updateChangesCounter();
    }
}

function updateChangesCounter() {
    const added = currentRoles.filter(role => !originalRoles.includes(role));
    const removed = originalRoles.filter(role => !currentRoles.includes(role));
    changesCount = added.length + removed.length;
    
    const counter = document.getElementById('changesCount');
    const saveBtn = document.getElementById('saveBtn');
    
    if (changesCount > 0) {
        counter.textContent = changesCount;
        counter.style.display = 'inline-block';
        saveBtn.classList.remove('btn-secondary');
        saveBtn.classList.add('btn-success');
    } else {
        counter.style.display = 'none';
        saveBtn.classList.remove('btn-success');
        saveBtn.classList.add('btn-secondary');
    }
    
    updateStatistics();
}

function updateCurrentRolesDisplay() {
    // Update role count
    document.getElementById('totalRolesCount').textContent = currentRoles.length;
    
    // Calculate total permissions (this is an approximation)
    let totalPermissions = 0;
    currentRoles.forEach(roleId => {
        const roleItem = document.querySelector(`[data-permissions-count]`);
        if (roleItem) {
            totalPermissions += parseInt(roleItem.dataset.permissionsCount || 0);
        }
    });
    
    document.getElementById('totalPermissionsCount').textContent = totalPermissions;
}

function updateStatistics() {
    // This would typically make an AJAX call to get updated statistics
    // For now, we'll update what we can calculate client-side
    document.getElementById('totalRolesCount').textContent = currentRoles.length;
}

function setupSearch() {
    const searchInput = document.getElementById('roleSearch');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filterRoles();
        }, 300);
    });
}

function setupFilters() {
    document.getElementById('roleFilter').addEventListener('change', filterRoles);
}

function filterRoles() {
    const searchTerm = document.getElementById('roleSearch').value.toLowerCase();
    const categoryFilter = document.getElementById('roleFilter').value;
    const roleItems = document.querySelectorAll('.role-item');
    let visibleCount = 0;
    
    roleItems.forEach(item => {
        const roleName = item.dataset.roleName;
        const roleCategory = item.dataset.roleCategory;
        
        const matchesSearch = roleName.includes(searchTerm) || 
                            item.textContent.toLowerCase().includes(searchTerm);
        const matchesCategory = !categoryFilter || roleCategory === categoryFilter;
        
        if (matchesSearch && matchesCategory) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    document.getElementById('noRolesFound').style.display = visibleCount === 0 ? 'block' : 'none';
}

function selectAllVisible() {
    const visibleRoles = document.querySelectorAll('.role-item:not([style*="display: none"]) .role-checkbox');
    visibleRoles.forEach(checkbox => {
        if (!checkbox.checked && !checkbox.disabled) {
            checkbox.checked = true;
            const roleId = checkbox.value;
            const roleName = checkbox.id.replace('role_', '');
            currentRoles.push(String(roleId));
            document.getElementById(`role-card-${roleId}`).classList.add('selected');
        }
    });
    
    updateChangesCounter();
    updateCurrentRolesDisplay();
}

function clearAllSelections() {
    if (!confirm('Are you sure you want to clear all role selections? This will reset to the original state.')) {
        return;
    }
    
    // Reset to original state
    document.querySelectorAll('.role-checkbox').forEach(checkbox => {
        const roleId = checkbox.value;
        const shouldBeChecked = originalRoles.includes(String(roleId));
        
        checkbox.checked = shouldBeChecked;
        const card = document.getElementById(`role-card-${roleId}`);
        if (shouldBeChecked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    });
    
    currentRoles = [...originalRoles];
    updateChangesCounter();
    updateCurrentRolesDisplay();
}

function viewRoleDetails(roleId) {
    const modal = new bootstrap.Modal(document.getElementById('roleDetailsModal'));
    modal.show();
    
    // Load role details via AJAX
    fetch(`/admin/roles/${roleId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('roleDetailsContent').innerHTML = buildRoleDetailsHTML(data.role);
            } else {
                document.getElementById('roleDetailsContent').innerHTML = 
                    '<div class="alert alert-danger">Failed to load role details.</div>';
            }
        })
        .catch(error => {
            document.getElementById('roleDetailsContent').innerHTML = 
                '<div class="alert alert-danger">Error loading role details.</div>';
        });
}

function buildRoleDetailsHTML(role) {
    return `
        <div class="row">
            <div class="col-md-4 text-center">
                <div class="role-icon role-${role.name.replace(/[_-]/g, '-')} mx-auto mb-3">
                    <i class="fas ${getRoleIconJS(role.name)}"></i>
                </div>
                <h5>${role.display_name || formatRoleName(role.name)}</h5>
                <p class="text-muted">${role.description || getDefaultRoleDescriptionJS(role.name)}</p>
            </div>
            <div class="col-md-8">
                <h6>Role Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Name:</strong></td><td>${role.name}</td></tr>
                    <tr><td><strong>Users:</strong></td><td>${role.users_count || 0}</td></tr>
                    <tr><td><strong>Permissions:</strong></td><td>${role.permissions_count || 0}</td></tr>
                    <tr><td><strong>Created:</strong></td><td>${formatDate(role.created_at)}</td></tr>
                </table>
                
                ${role.permissions && role.permissions.length > 0 ? `
                    <h6 class="mt-3">Permissions</h6>
                    <div class="permission-list" style="max-height: 200px;">
                        ${role.permissions.map(permission => 
                            `<span class="permission-badge">${permission.display_name || formatRoleName(permission.name)}</span>`
                        ).join('')}
                    </div>
                ` : ''}
            </div>
        </div>
    `;
}

function showRoleHelp() {
    const modal = new bootstrap.Modal(document.getElementById('helpModal'));
    modal.show();
}

function setupEventListeners() {
    // Form submission
    document.getElementById('roleForm').addEventListener('submit', function(e) {
        if (changesCount === 0) {
            e.preventDefault();
            showAlert('info', 'No changes to save.');
            return;
        }
        
        const saveBtn = document.getElementById('saveBtn');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
        saveBtn.disabled = true;
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            document.getElementById('roleForm').submit();
        }
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            document.getElementById('roleSearch').focus();
        }
        if (e.ctrlKey && e.key === 'a') {
            e.preventDefault();
            selectAllVisible();
        }
        if (e.key === 'Escape') {
            document.getElementById('roleSearch').value = '';
            filterRoles();
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

function trackChanges() {
    // Track changes and show save indicator
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                // Role selection changed
                updateChangesCounter();
            }
        });
    });
    
    document.querySelectorAll('.role-card').forEach(card => {
        observer.observe(card, { attributes: true, attributeFilter: ['class'] });
    });
}

// Utility functions
function formatRoleName(name) {
    return name.split(/[_-]/).map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString();
}

function getRoleIconJS(roleName) {
    const icons = {
        'super-admin': 'fa-crown',
        'admin': 'fa-shield-alt',
        'manager': 'fa-users-cog',
        'notification-manager': 'fa-bell',
        'user': 'fa-user',
        'api-user': 'fa-robot'
    };
    return icons[roleName] || 'fa-shield-alt';
}

function getDefaultRoleDescriptionJS(roleName) {
    const descriptions = {
        'super-admin': 'Complete system administration access',
        'admin': 'Administrative privileges and user management',
        'manager': 'Management functions and team oversight',
        'notification-manager': 'Notification system management',
        'user': 'Standard user access and permissions',
        'api-user': 'API access for automated systems'
    };
    return descriptions[roleName] || 'Standard role permissions';
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1055; max-width: 400px;';
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

// Show save indicator on successful save
@if(session('success'))
    document.addEventListener('DOMContentLoaded', function() {
        const indicator = document.getElementById('saveIndicator');
        indicator.style.display = 'block';
        setTimeout(() => {
            indicator.style.display = 'none';
        }, 3000);
    });
@endif
</script>

@php
function getRoleCategory($roleName) {
    if (str_contains($roleName, 'admin')) return 'admin';
    if (str_contains($roleName, 'manager')) return 'manager';
    if (str_contains($roleName, 'api')) return 'api';
    return 'user';
}

function getRoleIcon($roleName) {
    $icons = [
        'super-admin' => 'fa-crown',
        'admin' => 'fa-shield-alt',
        'manager' => 'fa-users-cog',
        'notification-manager' => 'fa-bell',
        'user' => 'fa-user',
        'api-user' => 'fa-robot'
    ];
    return $icons[$roleName] ?? 'fa-shield-alt';
}

function getDefaultRoleDescription($roleName) {
    $descriptions = [
        'super-admin' => 'Complete system administration access with all privileges',
        'admin' => 'Administrative privileges including user and system management',
        'manager' => 'Management functions with team oversight capabilities',
        'notification-manager' => 'Full notification system management and configuration',
        'user' => 'Standard user access with basic notification permissions',
        'api-user' => 'API access for automated systems and integrations'
    ];
    return $descriptions[$roleName] ?? 'Standard role with appropriate permissions';
}
@endphp
@endpush