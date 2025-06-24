@extends('layouts.app')

@section('title', 'Roles Management')

@push('styles')
<style>
.role-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    transition: all 0.3s ease;
    height: 100%;
}

.role-card:hover {
    border-color: var(--primary-green);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(101, 209, 181, 0.15);
}

.role-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.role-admin { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
.role-manager { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); }
.role-user { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
.role-api-user { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }
.role-default { background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); }

.permission-count {
    background: rgba(101, 209, 181, 0.1);
    color: var(--primary-green);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.user-count {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.stats-card {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
    color: white;
    border-radius: 12px;
    border: none;
}

.system-role-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #ffc107;
    color: #000;
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-weight: bold;
}

.action-buttons {
    opacity: 0;
    transition: opacity 0.3s ease;
}

.role-card:hover .action-buttons {
    opacity: 1;
}

.bulk-actions {
    background: rgba(101, 209, 181, 0.05);
    border: 1px solid var(--light-green);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    display: none;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Roles Management</li>
                </ol>
            </nav>
            <h2><i class="fas fa-shield-alt"></i> Roles Management</h2>
            <p class="text-muted">Create and manage user roles and their permissions</p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                    <i class="fas fa-plus"></i> Create Role
                </button>
                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#bulkPermissionsModal">
                    <i class="fas fa-layer-group"></i> Bulk Permissions
                </button>
                <a href="{{ route('roles.export', request()->query()) }}" class="btn btn-outline-success">
                    <i class="fas fa-download"></i> Export
                </a>
                <a href="{{ route('permissions.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-key"></i> Manage Permissions
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $stats['total_roles'] }}</h3>
                    <p class="mb-0">Total Roles</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $stats['total_permissions'] }}</h3>
                    <p class="mb-0">Total Permissions</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $stats['roles_with_users'] }}</h3>
                    <p class="mb-0">Active Roles</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $stats['unused_roles'] }}</h3>
                    <p class="mb-0">Unused Roles</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('roles.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Roles</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Role name or description...">
                    </div>
                    <div class="col-md-3">
                        <label for="sort_by" class="form-label">Sort By</label>
                        <select class="form-select" id="sort_by" name="sort_by">
                            <option value="display_name" {{ request('sort_by') === 'display_name' ? 'selected' : '' }}>Display Name</option>
                            <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Name</option>
                            <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Created Date</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="sort_direction" class="form-label">Order</label>
                        <select class="form-select" id="sort_direction" name="sort_direction">
                            <option value="asc" {{ request('sort_direction') === 'asc' ? 'selected' : '' }}>Ascending</option>
                            <option value="desc" {{ request('sort_direction') === 'desc' ? 'selected' : '' }}>Descending</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions (Hidden by default) -->
    <div id="bulkActions" class="bulk-actions">
        <div class="row align-items-center">
            <div class="col-md-6">
                <span id="selectedCount">0</span> role(s) selected
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-danger" onclick="bulkDelete()">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" onclick="bulkClone()">
                        <i class="fas fa-copy"></i> Clone Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        <i class="fas fa-times"></i> Clear Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Roles Grid -->
    @if($roles->count() > 0)
        <div class="row">
            @foreach($roles as $role)
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card role-card position-relative">
                        <!-- System Role Badge -->
                        @if(in_array($role->name, ['admin', 'super-admin']))
                            <span class="system-role-badge">SYSTEM</span>
                        @endif

                        <!-- Selection Checkbox -->
                        <div class="position-absolute" style="top: 10px; left: 10px;">
                            <input type="checkbox" class="form-check-input role-checkbox" 
                                   value="{{ $role->id }}" onchange="updateBulkActions()">
                        </div>

                        <div class="card-body text-center">
                            <!-- Role Icon -->
                            <div class="role-icon role-{{ str_replace(['_', '-'], '', $role->name) }} text-white mx-auto">
                                <i class="fas fa-shield-alt"></i>
                            </div>

                            <!-- Role Info -->
                            <h5 class="card-title mb-2">{{ $role->display_name ?? ucfirst(str_replace(['_', '-'], ' ', $role->name)) }}</h5>
                            
                            @if($role->description)
                                <p class="text-muted small mb-3">{{ Str::limit($role->description, 100) }}</p>
                            @endif

                            <!-- Counts -->
                            <div class="d-flex justify-content-center gap-3 mb-3">
                                <span class="permission-count">
                                    <i class="fas fa-key"></i> {{ $role->permissions->count() }} permissions
                                </span>
                                <span class="user-count">
                                    <i class="fas fa-users"></i> {{ $role->users->count() }} users
                                </span>
                            </div>

                            <!-- Recent Permissions Preview -->
                            @if($role->permissions->count() > 0)
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Key Permissions:</small>
                                    <div class="d-flex flex-wrap justify-content-center gap-1">
                                        @foreach($role->permissions->take(3) as $permission)
                                            <span class="badge bg-light text-dark">{{ explode('-', $permission->name)[0] }}</span>
                                        @endforeach
                                        @if($role->permissions->count() > 3)
                                            <span class="badge bg-secondary">+{{ $role->permissions->count() - 3 }} more</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Action Buttons -->
                            <div class="action-buttons">
                                <div class="btn-group btn-group-sm w-100">
                                    <a href="{{ route('roles.show', $role) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    @can('edit-roles')
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-outline-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    @endcan
                                    @can('delete-roles')
                                        @if(!in_array($role->name, ['admin', 'super-admin']))
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deleteRole({{ $role->id }}, '{{ $role->name }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                                
                                <div class="btn-group btn-group-sm w-100 mt-2">
                                    <button type="button" class="btn btn-outline-info" 
                                            onclick="cloneRole({{ $role->id }})">
                                        <i class="fas fa-copy"></i> Clone
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" 
                                            data-bs-toggle="modal" data-bs-target="#assignUsersModal"
                                            onclick="setTargetRole({{ $role->id }}, '{{ $role->name }}')">
                                        <i class="fas fa-user-plus"></i> Assign
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Created Date -->
                        <div class="card-footer text-center bg-transparent border-0">
                            <small class="text-muted">
                                Created {{ $role->created_at->diffForHumans() }}
                            </small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Showing {{ $roles->firstItem() }} to {{ $roles->lastItem() }} of {{ $roles->total() }} roles
            </div>
            <div>
                {{ $roles->links() }}
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No roles found</h5>
            <p class="text-muted">Create your first role to get started with permission management.</p>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                <i class="fas fa-plus"></i> Create First Role
            </button>
        </div>
    @endif
</div>

<!-- Create Role Modal -->
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                                <small class="form-text text-muted">Lowercase, no spaces (e.g., content_manager)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="display_name" name="display_name" required>
                                <small class="form-text text-muted">Human-readable name</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Describe what this role can do..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div class="row">
                            @php
                                $permissionGroups = \Spatie\Permission\Models\Permission::all()->groupBy(function($permission) {
                                    return explode('-', $permission->name)[0];
                                });
                            @endphp
                            
                            @foreach($permissionGroups as $group => $permissions)
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-header py-2">
                                            <div class="form-check">
                                                <input class="form-check-input group-checkbox" type="checkbox" 
                                                       id="group_{{ $group }}" data-group="{{ $group }}">
                                                <label class="form-check-label fw-bold" for="group_{{ $group }}">
                                                    {{ ucfirst($group) }} Permissions
                                                </label>
                                            </div>
                                        </div>
                                        <div class="card-body py-2">
                                            @foreach($permissions as $permission)
                                                <div class="form-check">
                                                    <input class="form-check-input permission-checkbox" type="checkbox" 
                                                           name="permissions[]" value="{{ $permission->name }}" 
                                                           id="perm_{{ $permission->id }}" data-group="{{ $group }}">
                                                    <label class="form-check-label small" for="perm_{{ $permission->id }}">
                                                        {{ $permission->display_name ?? ucfirst(str_replace(['_', '-'], ' ', $permission->name)) }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create Role
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Users Modal -->
<div class="modal fade" id="assignUsersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Users to Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignUsersForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Users:</label>
                        <select class="form-select" name="user_ids[]" multiple size="10">
                            @foreach(\App\Models\User::active()->orderBy('display_name')->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->display_name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple users</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Users</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form id="deleteRoleForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<form id="cloneRoleForm" method="POST" style="display: none;">
    @csrf
</form>
@endsection

@push('scripts')
<script>
// Group checkbox functionality
document.querySelectorAll('.group-checkbox').forEach(groupCheckbox => {
    groupCheckbox.addEventListener('change', function() {
        const group = this.dataset.group;
        const permissionCheckboxes = document.querySelectorAll(`input[data-group="${group}"].permission-checkbox`);
        
        permissionCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
});

// Update group checkbox when individual permissions change
document.querySelectorAll('.permission-checkbox').forEach(permissionCheckbox => {
    permissionCheckbox.addEventListener('change', function() {
        const group = this.dataset.group;
        const groupCheckbox = document.querySelector(`#group_${group}`);
        const groupPermissions = document.querySelectorAll(`input[data-group="${group}"].permission-checkbox`);
        const checkedPermissions = document.querySelectorAll(`input[data-group="${group}"].permission-checkbox:checked`);
        
        if (checkedPermissions.length === 0) {
            groupCheckbox.checked = false;
            groupCheckbox.indeterminate = false;
        } else if (checkedPermissions.length === groupPermissions.length) {
            groupCheckbox.checked = true;
            groupCheckbox.indeterminate = false;
        } else {
            groupCheckbox.checked = false;
            groupCheckbox.indeterminate = true;
        }
    });
});

// Bulk selection functionality
function updateBulkActions() {
    const selected = document.querySelectorAll('.role-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = selected.length;
    bulkActions.style.display = selected.length > 0 ? 'block' : 'none';
}

function clearSelection() {
    document.querySelectorAll('.role-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateBulkActions();
}

// Role actions
function deleteRole(roleId, roleName) {
    if (confirm(`Are you sure you want to delete the role "${roleName}"? This action cannot be undone.`)) {
        const form = document.getElementById('deleteRoleForm');
        form.action = `/roles/${roleId}`;
        form.submit();
    }
}

function cloneRole(roleId) {
    if (confirm('Are you sure you want to clone this role?')) {
        const form = document.getElementById('cloneRoleForm');
        form.action = `/roles/${roleId}/clone`;
        form.submit();
    }
}

function setTargetRole(roleId, roleName) {
    const form = document.getElementById('assignUsersForm');
    form.action = `/roles/${roleId}/bulk-assign`;
    document.querySelector('#assignUsersModal .modal-title').textContent = `Assign Users to "${roleName}"`;
}

// Bulk operations
function bulkDelete() {
    const selected = document.querySelectorAll('.role-checkbox:checked');
    if (selected.length === 0) {
        alert('Please select roles to delete');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${selected.length} role(s)? This action cannot be undone.`)) {
        // Implementation for bulk delete
        console.log('Bulk delete:', Array.from(selected).map(cb => cb.value));
    }
}

function bulkClone() {
    const selected = document.querySelectorAll('.role-checkbox:checked');
    if (selected.length === 0) {
        alert('Please select roles to clone');
        return;
    }
    
    if (confirm(`Are you sure you want to clone ${selected.length} role(s)?`)) {
        // Implementation for bulk clone
        console.log('Bulk clone:', Array.from(selected).map(cb => cb.value));
    }
}

// Auto-generate role name from display name
document.getElementById('display_name').addEventListener('input', function() {
    const nameField = document.getElementById('name');
    if (!nameField.value) {
        nameField.value = this.value.toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }
});
</script>
@endpush