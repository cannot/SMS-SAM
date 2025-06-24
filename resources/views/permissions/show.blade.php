@extends('layouts.app')

@section('title', 'Permissions Management')

@push('styles')
<style>
.permission-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
    background: white;
    height: 100%;
}

.permission-card:hover {
    border-color: var(--primary-green);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(101, 209, 181, 0.15);
}

.permission-category {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
    color: white;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    margin-bottom: 1rem;
    font-weight: bold;
}

.permission-category.view { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
.permission-category.create { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); }
.permission-category.edit { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); }
.permission-category.delete { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
.permission-category.manage { background: linear-gradient(135deg, #6f42c1 0%, #59359a 100%); }
.permission-category.admin { background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%); }

.role-filter-tabs {
    background: rgba(101, 209, 181, 0.1);
    border: 1px solid var(--light-green);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.role-tab {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    margin: 0.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: #495057;
    display: inline-block;
}

.role-tab:hover {
    border-color: var(--primary-green);
    color: var(--primary-green);
    text-decoration: none;
}

.role-tab.active {
    border-color: var(--primary-green);
    background: var(--primary-green);
    color: white;
}

.role-badge {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    margin: 0.1rem;
    display: inline-block;
}

.user-count-badge {
    background: rgba(101, 209, 181, 0.1);
    color: var(--primary-green);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.category-filter {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(101, 209, 181, 0.2);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.permission-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
}

.grouped-view .category-section {
    margin-bottom: 2rem;
}

.grouped-view .category-header {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
    color: white;
    padding: 1rem;
    border-radius: 10px 10px 0 0;
    margin-bottom: 0;
}

.bulk-actions {
    background: rgba(101, 209, 181, 0.05);
    border: 1px solid var(--light-green);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    display: none;
}

.permission-matrix-preview {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    background: #f8f9fa;
}

.role-impact-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
}

.role-admin-indicator { background: #dc3545; }
.role-manager-indicator { background: #28a745; }
.role-support-indicator { background: #17a2b8; }
.role-api-indicator { background: #ffc107; }
.role-user-indicator { background: #6c757d; }

.breadcrumb-item a {
    color: var(--primary-green);
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: var(--dark-green);
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
                    <li class="breadcrumb-item active">Permissions Management</li>
                </ol>
            </nav>
            <h2><i class="fas fa-key"></i> Permissions Management</h2>
            <p class="text-muted">Manage system permissions and role assignments</p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                    <i class="fas fa-plus"></i> Create Permission
                </button>
                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#bulkCreateModal">
                    <i class="fas fa-layer-group"></i> Bulk Create
                </button>
                <a href="{{ route('permissions.export', request()->query()) }}" class="btn btn-outline-success">
                    <i class="fas fa-download"></i> Export
                </a>
                <a href="{{ route('permissions.matrix') }}" class="btn btn-outline-primary">
                    <i class="fas fa-table"></i> Permission Matrix
                </a>
                <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-shield-alt"></i> Manage Roles
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
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
                    <h3 class="mb-1">{{ $stats['assigned_permissions'] }}</h3>
                    <p class="mb-0">Assigned Permissions</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $stats['unassigned_permissions'] }}</h3>
                    <p class="mb-0">Unassigned</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card" style="background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: white;">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $stats['categories_count'] }}</h3>
                    <p class="mb-0">Categories</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Role-Based Filter Tabs -->
    <div class="role-filter-tabs">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter by Role Impact</h6>
            <small class="text-muted">View permissions by their role assignments</small>
        </div>
        
        <div class="text-center">
            <a href="{{ route('permissions.index', array_merge(request()->query(), ['role_filter' => 'all'])) }}" 
               class="role-tab {{ request('role_filter', 'all') === 'all' ? 'active' : '' }}">
                <i class="fas fa-globe me-2"></i>All Permissions
                <span class="badge bg-light text-dark ms-2">{{ $stats['total_permissions'] }}</span>
            </a>
            
            <a href="{{ route('permissions.index', array_merge(request()->query(), ['role_filter' => 'admin'])) }}" 
               class="role-tab {{ request('role_filter') === 'admin' ? 'active' : '' }}">
                <span class="role-admin-indicator"></span>Admin Only
                <span class="badge bg-light text-dark ms-2">
                    {{ \Spatie\Permission\Models\Permission::whereHas('roles', function($q) {
                        $q->where('name', 'LIKE', '%admin%');
                    })->count() }}
                </span>
            </a>
            
            <a href="{{ route('permissions.index', array_merge(request()->query(), ['role_filter' => 'manager'])) }}" 
               class="role-tab {{ request('role_filter') === 'manager' ? 'active' : '' }}">
                <span class="role-manager-indicator"></span>Managers
                <span class="badge bg-light text-dark ms-2">
                    {{ \Spatie\Permission\Models\Permission::whereHas('roles', function($q) {
                        $q->where('name', 'LIKE', '%manager%');
                    })->count() }}
                </span>
            </a>
            
            <a href="{{ route('permissions.index', array_merge(request()->query(), ['role_filter' => 'support'])) }}" 
               class="role-tab {{ request('role_filter') === 'support' ? 'active' : '' }}">
                <span class="role-support-indicator"></span>Support
                <span class="badge bg-light text-dark ms-2">
                    {{ \Spatie\Permission\Models\Permission::whereHas('roles', function($q) {
                        $q->where('name', 'LIKE', '%support%');
                    })->count() }}
                </span>
            </a>
            
            <a href="{{ route('permissions.index', array_merge(request()->query(), ['role_filter' => 'api'])) }}" 
               class="role-tab {{ request('role_filter') === 'api' ? 'active' : '' }}">
                <span class="role-api-indicator"></span>API Access
                <span class="badge bg-light text-dark ms-2">
                    {{ \Spatie\Permission\Models\Permission::whereHas('roles', function($q) {
                        $q->where('name', 'LIKE', '%api%');
                    })->count() }}
                </span>
            </a>
            
            <a href="{{ route('permissions.index', array_merge(request()->query(), ['role_filter' => 'unassigned'])) }}" 
               class="role-tab {{ request('role_filter') === 'unassigned' ? 'active' : '' }}">
                <span class="role-user-indicator"></span>Unassigned
                <span class="badge bg-light text-dark ms-2">{{ $stats['unassigned_permissions'] }}</span>
            </a>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="category-filter">
        <form method="GET" action="{{ route('permissions.index') }}">
            <input type="hidden" name="role_filter" value="{{ request('role_filter', 'all') }}">
            
            <div class="row">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search Permissions</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Permission name or description...">
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>
                                {{ ucfirst($cat) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="view_mode" class="form-label">View Mode</label>
                    <select class="form-select" id="view_mode" name="view_mode">
                        <option value="role-grouped" {{ request('view_mode', 'role-grouped') === 'role-grouped' ? 'selected' : '' }}>By Role Impact</option>
                        <option value="category-grouped" {{ request('view_mode') === 'category-grouped' ? 'selected' : '' }}>By Category</option>
                        <option value="grid" {{ request('view_mode') === 'grid' ? 'selected' : '' }}>Grid View</option>
                        <option value="table" {{ request('view_mode') === 'table' ? 'selected' : '' }}>Table View</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Bulk Actions (Hidden by default) -->
    <div id="bulkActions" class="bulk-actions">
        <div class="row align-items-center">
            <div class="col-md-6">
                <span id="selectedCount">0</span> permission(s) selected
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-primary" onclick="bulkAssignToRoles()">
                        <i class="fas fa-shield-alt"></i> Assign to Roles
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="bulkDelete()">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearSelection()">
                        <i class="fas fa-times"></i> Clear Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Permission Matrix Preview -->
    @if(request('role_filter') && request('role_filter') !== 'all')
    <div class="permission-matrix-preview">
        <h6><i class="fas fa-table me-2"></i>Role Impact Preview</h6>
        <p class="text-muted mb-3">Showing permissions that affect <strong>{{ ucfirst(request('role_filter')) }}</strong> roles</p>
        
        @php
            $currentFilter = request('role_filter');
            $filteredPermissions = $permissions->getCollection();
            $affectedRoles = collect();
            
            if ($currentFilter === 'admin') {
                $affectedRoles = \Spatie\Permission\Models\Role::where('name', 'LIKE', '%admin%')->get();
            } elseif ($currentFilter === 'manager') {
                $affectedRoles = \Spatie\Permission\Models\Role::where('name', 'LIKE', '%manager%')->get();
            } elseif ($currentFilter === 'support') {
                $affectedRoles = \Spatie\Permission\Models\Role::where('name', 'LIKE', '%support%')->get();
            } elseif ($currentFilter === 'api') {
                $affectedRoles = \Spatie\Permission\Models\Role::where('name', 'LIKE', '%api%')->get();
            }
        @endphp
        
        @if($affectedRoles->count() > 0)
            <div class="row">
                <div class="col-md-6">
                    <strong>Affected Roles ({{ $affectedRoles->count() }}):</strong><br>
                    @foreach($affectedRoles as $role)
                        <span class="role-badge">{{ $role->display_name ?? $role->name }}</span>
                    @endforeach
                </div>
                <div class="col-md-6">
                    <strong>Total Users Affected:</strong>
                    <span class="user-count-badge">
                        {{ $affectedRoles->sum(function($role) { return $role->users->count(); }) }} users
                    </span>
                </div>
            </div>
        @endif
    </div>
    @endif

    <!-- Permissions Display -->
    @if($permissions->count() > 0)
        @if(request('view_mode') === 'role-grouped')
            <!-- Role-Grouped View -->
            <div class="role-grouped-view">
                @php
                    $permissionsByRoleType = $permissions->getCollection()->groupBy(function($permission) {
                        $roleTypes = [];
                        foreach($permission->roles as $role) {
                            if (str_contains(strtolower($role->name), 'admin')) $roleTypes[] = 'admin';
                            if (str_contains(strtolower($role->name), 'manager')) $roleTypes[] = 'manager';
                            if (str_contains(strtolower($role->name), 'support')) $roleTypes[] = 'support';
                            if (str_contains(strtolower($role->name), 'api')) $roleTypes[] = 'api';
                        }
                        
                        if (empty($roleTypes)) return 'unassigned';
                        if (in_array('admin', $roleTypes)) return 'admin';
                        if (in_array('manager', $roleTypes)) return 'manager';
                        if (in_array('support', $roleTypes)) return 'support';
                        if (in_array('api', $roleTypes)) return 'api';
                        return 'user';
                    });
                @endphp

                @foreach(['admin', 'manager', 'support', 'api', 'user', 'unassigned'] as $roleType)
                    @if($permissionsByRoleType->has($roleType))
                        <div class="category-section">
                            <div class="card">
                                <div class="category-header role-{{ $roleType }}-indicator">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <span class="role-{{ $roleType }}-indicator me-2"></span>
                                            <i class="fas fa-users-cog"></i> {{ ucfirst($roleType) }} Permissions
                                        </h5>
                                        <span class="badge bg-light text-dark">{{ $permissionsByRoleType[$roleType]->count() }} permissions</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($permissionsByRoleType[$roleType] as $permission)
                                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                                <div class="permission-card">
                                                    <div class="card-body p-3">
                                                        <!-- Selection Checkbox -->
                                                        <div class="form-check mb-2">
                                                            <input type="checkbox" class="form-check-input permission-checkbox" 
                                                                   value="{{ $permission->id }}" onchange="updateBulkActions()">
                                                        </div>

                                                        <!-- Permission Icon -->
                                                        @php $category = explode('-', $permission->name)[0] ?? 'general'; @endphp
                                                        <div class="permission-icon permission-category {{ $category }} text-white mx-auto">
                                                            <i class="fas fa-key"></i>
                                                        </div>

                                                        <!-- Permission Details -->
                                                        <h6 class="card-title text-center mb-2">
                                                            {{ $permission->display_name ?? ucfirst(str_replace(['_', '-'], ' ', $permission->name)) }}
                                                        </h6>
                                                        
                                                        <p class="text-muted small text-center mb-2">
                                                            <code>{{ $permission->name }}</code>
                                                        </p>
                                                        
                                                        @if($permission->description)
                                                            <p class="text-muted small mb-3">{{ Str::limit($permission->description, 80) }}</p>
                                                        @endif

                                                        <!-- Role Impact Indicators -->
                                                        <div class="text-center mb-3">
                                                            @php
                                                                $roleImpact = [];
                                                                foreach($permission->roles as $role) {
                                                                    if (str_contains(strtolower($role->name), 'admin')) $roleImpact['admin'] = true;
                                                                    if (str_contains(strtolower($role->name), 'manager')) $roleImpact['manager'] = true;
                                                                    if (str_contains(strtolower($role->name), 'support')) $roleImpact['support'] = true;
                                                                    if (str_contains(strtolower($role->name), 'api')) $roleImpact['api'] = true;
                                                                }
                                                            @endphp
                                                            
                                                            <div class="mb-2">
                                                                @foreach(['admin', 'manager', 'support', 'api'] as $type)
                                                                    <span class="role-impact-indicator role-{{ $type }}-indicator" 
                                                                          title="{{ ucfirst($type) }} access"
                                                                          style="opacity: {{ isset($roleImpact[$type]) ? '1' : '0.2' }}"></span>
                                                                @endforeach
                                                            </div>
                                                            
                                                            <span class="user-count-badge">
                                                                <i class="fas fa-shield-alt"></i> {{ $permission->roles->count() }} roles
                                                            </span>
                                                        </div>

                                                        <!-- Assigned Roles Preview -->
                                                        @if($permission->roles->count() > 0)
                                                            <div class="text-center mb-3">
                                                                @foreach($permission->roles->take(2) as $role)
                                                                    <span class="role-badge">{{ $role->display_name ?? $role->name }}</span>
                                                                @endforeach
                                                                @if($permission->roles->count() > 2)
                                                                    <span class="role-badge">+{{ $permission->roles->count() - 2 }}</span>
                                                                @endif
                                                            </div>
                                                        @endif

                                                        <!-- Actions -->
                                                        <div class="btn-group btn-group-sm w-100">
                                                            <a href="{{ route('permissions.show', $permission) }}" class="btn btn-outline-primary btn-sm">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            @can('edit-permissions')
                                                                <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-outline-warning btn-sm">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endcan
                                                            @can('delete-permissions')
                                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                        onclick="deletePermission({{ $permission->id }}, '{{ $permission->name }}')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            @endcan
                                                        </div>
                                                    </div>

                                                    <!-- Created Date -->
                                                    <div class="card-footer text-center bg-transparent border-0">
                                                        <small class="text-muted">
                                                            Created {{ $permission->created_at->diffForHumans() }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
            
        @elseif(request('view_mode') === 'category-grouped')
            <!-- Category-Grouped View -->
            <div class="category-grouped-view">
                @foreach($groupedPermissions as $category => $categoryPermissions)
                    <div class="category-section">
                        <div class="card">
                            <div class="category-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-folder-open"></i> {{ ucfirst($category) }} Permissions
                                    </h5>
                                    <span class="badge bg-light text-dark">{{ $categoryPermissions->count() }} permissions</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($categoryPermissions as $permission)
                                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                            <div class="permission-card">
                                                <div class="card-body p-3">
                                                    <!-- Selection Checkbox -->
                                                    <div class="form-check mb-2">
                                                        <input type="checkbox" class="form-check-input permission-checkbox" 
                                                               value="{{ $permission->id }}" onchange="updateBulkActions()">
                                                    </div>

                                                    <!-- Permission Icon -->
                                                    <div class="permission-icon permission-category {{ $category }} text-white mx-auto">
                                                        <i class="fas fa-key"></i>
                                                    </div>

                                                    <!-- Permission Details -->
                                                    <h6 class="card-title text-center mb-2">
                                                        {{ $permission->display_name ?? ucfirst(str_replace(['_', '-'], ' ', $permission->name)) }}
                                                    </h6>
                                                    
                                                    <p class="text-muted small text-center mb-2">{{ $permission->name }}</p>
                                                    
                                                    @if($permission->description)
                                                        <p class="text-muted small mb-3">{{ Str::limit($permission->description, 80) }}</p>
                                                    @endif

                                                    <!-- Roles Count -->
                                                    <div class="text-center mb-3">
                                                        <span class="user-count-badge">
                                                            <i class="fas fa-shield-alt"></i> {{ $permission->roles->count() }} roles
                                                        </span>
                                                    </div>

                                                    <!-- Assigned Roles -->
                                                    @if($permission->roles->count() > 0)
                                                        <div class="text-center mb-3">
                                                            @foreach($permission->roles->take(3) as $role)
                                                                <span class="role-badge">{{ $role->display_name ?? $role->name }}</span>
                                                            @endforeach
                                                            @if($permission->roles->count() > 3)
                                                                <span class="role-badge">+{{ $permission->roles->count() - 3 }} more</span>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    <!-- Actions -->
                                                    <div class="btn-group btn-group-sm w-100">
                                                        <a href="{{ route('permissions.show', $permission) }}" class="btn btn-outline-primary btn-sm">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @can('edit-permissions')
                                                            <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-outline-warning btn-sm">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        @endcan
                                                        @can('delete-permissions')
                                                            <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                    onclick="deletePermission({{ $permission->id }}, '{{ $permission->name }}')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        @endcan
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
        @elseif(request('view_mode') === 'table')
            <!-- Table View -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectAll" onchange="toggleAllPermissions()">
                                    </th>
                                    <th>Name</th>
                                    <th>Display Name</th>
                                    <th>Category</th>
                                    <th>Role Impact</th>
                                    <th>Description</th>
                                    <th>Roles</th>
                                    <th>Users</th>
                                    <th>Created</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permissions as $permission)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="permission-checkbox" 
                                                   value="{{ $permission->id }}" onchange="updateBulkActions()">
                                        </td>
                                        <td>
                                            <code class="text-primary">{{ $permission->name }}</code>
                                        </td>
                                        <td>{{ $permission->display_name ?? ucfirst(str_replace(['_', '-'], ' ', $permission->name)) }}</td>
                                        <td>
                                            @php $category = explode('-', $permission->name)[0] ?? 'general'; @endphp
                                            <span class="badge permission-category {{ $category }}">{{ ucfirst($category) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $roleImpact = [];
                                                foreach($permission->roles as $role) {
                                                    if (str_contains(strtolower($role->name), 'admin')) $roleImpact[] = 'admin';
                                                    if (str_contains(strtolower($role->name), 'manager')) $roleImpact[] = 'manager';
                                                    if (str_contains(strtolower($role->name), 'support')) $roleImpact[] = 'support';
                                                    if (str_contains(strtolower($role->name), 'api')) $roleImpact[] = 'api';
                                                }
                                                $roleImpact = array_unique($roleImpact);
                                            @endphp
                                            
                                            @if(empty($roleImpact))
                                                <span class="text-muted">Unassigned</span>
                                            @else
                                                @foreach($roleImpact as $type)
                                                    <span class="role-impact-indicator role-{{ $type }}-indicator" title="{{ ucfirst($type) }}"></span>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>{{ Str::limit($permission->description, 50) }}</td>
                                        <td>
                                            @foreach($permission->roles->take(2) as $role)
                                                <span class="role-badge">{{ $role->display_name ?? $role->name }}</span>
                                            @endforeach
                                            @if($permission->roles->count() > 2)
                                                <span class="text-muted">+{{ $permission->roles->count() - 2 }} more</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="user-count-badge">{{ \App\Models\User::permission($permission->name)->count() }}</span>
                                        </td>
                                        <td>{{ $permission->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('permissions.show', $permission) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('edit-permissions')
                                                    <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-outline-warning btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('delete-permissions')
                                                    <button type="button" class="btn btn-outline-danger btn-sm" 
                                                            onclick="deletePermission({{ $permission->id }}, '{{ $permission->name }}')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <!-- Grid View (Default) -->
            <div class="row">
                @foreach($permissions as $permission)
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="permission-card">
                            <div class="card-body">
                                <!-- Selection Checkbox -->
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input permission-checkbox" 
                                           value="{{ $permission->id }}" onchange="updateBulkActions()">
                                </div>

                                <!-- Category Badge -->
                                @php $category = explode('-', $permission->name)[0] ?? 'general'; @endphp
                                <div class="permission-category {{ $category }} text-center">
                                    {{ ucfirst($category) }}
                                </div>

                                <!-- Permission Details -->
                                <h6 class="card-title text-center mb-2">
                                    {{ $permission->display_name ?? ucfirst(str_replace(['_', '-'], ' ', $permission->name)) }}
                                </h6>
                                
                                <p class="text-muted small text-center mb-2">
                                    <code>{{ $permission->name }}</code>
                                </p>
                                
                                @if($permission->description)
                                    <p class="text-muted small mb-3">{{ Str::limit($permission->description, 80) }}</p>
                                @endif

                                <!-- Statistics -->
                                <div class="d-flex justify-content-center gap-2 mb-3">
                                    <span class="user-count-badge">
                                        <i class="fas fa-shield-alt"></i> {{ $permission->roles->count() }}
                                    </span>
                                    <span class="role-badge">
                                        <i class="fas fa-users"></i> {{ \App\Models\User::permission($permission->name)->count() }}
                                    </span>
                                </div>

                                <!-- Assigned Roles Preview -->
                                @if($permission->roles->count() > 0)
                                    <div class="text-center mb-3">
                                        @foreach($permission->roles->take(2) as $role)
                                            <span class="role-badge">{{ $role->display_name ?? $role->name }}</span>
                                        @endforeach
                                        @if($permission->roles->count() > 2)
                                            <span class="role-badge">+{{ $permission->roles->count() - 2 }}</span>
                                        @endif
                                    </div>
                                @endif

                                <!-- Actions -->
                                <div class="btn-group btn-group-sm w-100">
                                    <a href="{{ route('permissions.show', $permission) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    @can('edit-permissions')
                                        <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('delete-permissions')
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deletePermission({{ $permission->id }}, '{{ $permission->name }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endcan
                                </div>
                            </div>

                            <!-- Created Date -->
                            <div class="card-footer text-center bg-transparent border-0">
                                <small class="text-muted">
                                    Created {{ $permission->created_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Showing {{ $permissions->firstItem() }} to {{ $permissions->lastItem() }} of {{ $permissions->total() }} permissions
            </div>
            <div>
                {{ $permissions->links() }}
            </div>
        </div>
    @else
        <div class="text-center py-5">
            <i class="fas fa-key fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No permissions found</h5>
            @if(request('role_filter') && request('role_filter') !== 'all')
                <p class="text-muted">No permissions found for <strong>{{ ucfirst(request('role_filter')) }}</strong> roles.</p>
                <a href="{{ route('permissions.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i> Show All Permissions
                </a>
            @else
                <p class="text-muted">Create your first permission to get started.</p>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                    <i class="fas fa-plus"></i> Create First Permission
                </button>
            @endif
        </div>
    @endif
</div>

<!-- Include the existing modals from your original template -->
<!-- Create Permission Modal, Bulk Create Modal, etc. -->

<!-- Hidden Forms -->
<form id="deletePermissionForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
// View mode auto-submit
document.getElementById('view_mode').addEventListener('change', function() {
    this.closest('form').submit();
});

// Bulk selection functionality
function updateBulkActions() {
    const selected = document.querySelectorAll('.permission-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = selected.length;
    bulkActions.style.display = selected.length > 0 ? 'block' : 'none';
}

function toggleAllPermissions() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.permission-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    updateBulkActions();
}

function clearSelection() {
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    if (document.getElementById('selectAll')) {
        document.getElementById('selectAll').checked = false;
    }
    updateBulkActions();
}

// Permission actions
function deletePermission(permissionId, permissionName) {
    if (confirm(`Are you sure you want to delete the permission "${permissionName}"? This action cannot be undone.`)) {
        const form = document.getElementById('deletePermissionForm');
        form.action = `/permissions/${permissionId}`;
        form.submit();
    }
}

function bulkAssignToRoles() {
    const selected = document.querySelectorAll('.permission-checkbox:checked');
    if (selected.length === 0) {
        alert('Please select permissions to assign');
        return;
    }
    
    // Implement bulk assign functionality
    console.log('Bulk assign permissions:', Array.from(selected).map(cb => cb.value));
}

function bulkDelete() {
    const selected = document.querySelectorAll('.permission-checkbox:checked');
    if (selected.length === 0) {
        alert('Please select permissions to delete');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${selected.length} permission(s)? This action cannot be undone.`)) {
        const permissionIds = Array.from(selected).map(cb => cb.value);
        
        // Create form for bulk delete
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/permissions/bulk-delete';
        form.style.display = 'none';
        
        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfInput);
        
        // Add method override
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Add permission IDs
        permissionIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'permission_ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Add tooltips for role impact indicators
    document.querySelectorAll('.role-impact-indicator').forEach(indicator => {
        new bootstrap.Tooltip(indicator);
    });
});
</script>
@endpush@extends('layouts.app')

@section('title', 'Permission Details - ' . $permission->display_name)

@push('styles')
<style>
.permission-detail-card {
    border: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 12px;
}

.permission-header {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
    color: white;
    border-radius: 12px 12px 0 0;
    padding: 2rem;
}

.category-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.375rem 0.875rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.stat-card {
    background: white;
    border: 2px solid #f1f3f4;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    height: 100%;
}

.stat-card:hover {
    border-color: var(--primary-green);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(101, 209, 181, 0.15);
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.role-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.3s ease;
}

.role-card:hover {
    border-color: var(--primary-green);
    box-shadow: 0 4px 12px rgba(101, 209, 181, 0.15);
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.role-admin { background: #dc3545; color: white; }
.role-manager { background: #28a745; color: white; }
.role-user { background: #6c757d; color: white; }
.role-api { background: #ffc107; color: #212529; }
.role-support { background: #17a2b8; color: white; }

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-green), var(--light-green));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.75rem;
}

.activity-item {
    border-left: 3px solid var(--primary-green);
    padding-left: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
}

.activity-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
}

.permission-actions {
    background: #f8f9fa;
    border-radius: 0 0 12px 12px;
    padding: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.usage-matrix {
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.usage-matrix .table th {
    background: #f8f9fa;
    border: none;
    font-weight: 600;
    color: #495057;
}

.usage-indicator {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 0.5rem;
}

.usage-active { background: #28a745; }
.usage-inactive { background: #6c757d; }

.breadcrumb-item a {
    color: var(--primary-green);
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: var(--dark-green);
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
            <li class="breadcrumb-item active">{{ $permission->display_name }}</li>
        </ol>
    </nav>

    <!-- Permission Header -->
    <div class="permission-detail-card mb-4">
        <div class="permission-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-3">
                        <h1 class="mb-0 me-3">{{ $permission->display_name }}</h1>
                        <span class="category-badge">{{ ucfirst($stats['category']) }}</span>
                    </div>
                    <p class="mb-2 opacity-90">
                        <i class="fas fa-code me-2"></i>
                        <code style="color: rgba(255,255,255,0.9);">{{ $permission->name }}</code>
                    </p>
                    @if($permission->description)
                        <p class="mb-0 opacity-75">{{ $permission->description }}</p>
                    @endif
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex flex-column align-items-end">
                        <small class="opacity-75 mb-2">Created {{ $permission->created_at->diffForHumans() }}</small>
                        @can('edit-permissions')
                            <div class="btn-group">
                                <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-light btn-sm">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </a>
                                <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#assignRoleModal">
                                    <i class="fas fa-shield-alt me-1"></i> Assign to Roles
                                </button>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        @can('edit-permissions')
        <div class="permission-actions">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Quick Actions</h6>
                    <div class="btn-group">
                        <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit Permission
                        </a>
                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#assignRoleModal">
                            <i class="fas fa-plus me-1"></i> Assign to Role
                        </button>
                        <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Export & Share</h6>
                    <div class="btn-group">
                        <button class="btn btn-outline-info btn-sm" onclick="copyPermissionCode()">
                            <i class="fas fa-copy me-1"></i> Copy Code
                        </button>
                        <button class="btn btn-outline-warning btn-sm" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                        @can('delete-permissions')
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deletePermission()">
                            <i class="fas fa-trash me-1"></i> Delete
                        </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>

    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number text-primary">{{ $stats['roles_count'] }}</div>
                <div class="stat-label">Assigned Roles</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number text-success">{{ $stats['users_count'] }}</div>
                <div class="stat-label">Users with Access</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number text-info">{{ $recentActivity->count() }}</div>
                <div class="stat-label">Recent Activities</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-number" style="color: var(--primary-green);">{{ ucfirst($stats['category']) }}</div>
                <div class="stat-label">Category</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Assigned Roles -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Assigned Roles ({{ $stats['roles_count'] }})
                    </h5>
                </div>
                <div class="card-body">
                    @if($permission->roles->count() > 0)
                        @php
                            $roleTypes = [
                                'admin' => $permission->roles->filter(fn($role) => str_contains(strtolower($role->name), 'admin')),
                                'manager' => $permission->roles->filter(fn($role) => str_contains(strtolower($role->name), 'manager')),
                                'support' => $permission->roles->filter(fn($role) => str_contains(strtolower($role->name), 'support')),
                                'api' => $permission->roles->filter(fn($role) => str_contains(strtolower($role->name), 'api')),
                                'user' => $permission->roles->reject(fn($role) => 
                                    str_contains(strtolower($role->name), 'admin') || 
                                    str_contains(strtolower($role->name), 'manager') || 
                                    str_contains(strtolower($role->name), 'support') || 
                                    str_contains(strtolower($role->name), 'api')
                                )
                            ];
                        @endphp

                        @foreach($roleTypes as $type => $roles)
                            @if($roles->count() > 0)
                                <h6 class="text-muted mb-3 mt-3">
                                    <i class="fas fa-folder-open me-2"></i>
                                    {{ ucfirst($type) }} Roles ({{ $roles->count() }})
                                </h6>
                                @foreach($roles as $role)
                                    <div class="role-card">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <span class="role-badge role-{{ $type }} me-3">{{ $type }}</span>
                                                <div>
                                                    <h6 class="mb-1">{{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}</h6>
                                                    <small class="text-muted">
                                                        <code>{{ $role->name }}</code> • {{ $role->users->count() }} users
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    {{ $role->permissions->count() }} permissions
                                                </small>
                                                @can('edit-roles')
                                                <div class="mt-1">
                                                    <a href="{{ route('roles.show', $role) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No roles assigned</h6>
                            <p class="text-muted mb-3">This permission is not currently assigned to any roles.</p>
                            @can('edit-permissions')
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignRoleModal">
                                <i class="fas fa-plus me-1"></i> Assign to Role
                            </button>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Users with Permission -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Users with Access ({{ $stats['users_count'] }})
                    </h5>
                </div>
                <div class="card-body">
                    @if($usersWithPermission->count() > 0)
                        <div class="mb-3">
                            <small class="text-muted">
                                Showing {{ $usersWithPermission->count() }} of {{ $stats['users_count'] }} users
                            </small>
                        </div>
                        
                        @php
                            $usersByRole = $usersWithPermission->groupBy(function($user) {
                                $roleNames = $user->roles->pluck('name')->toArray();
                                if (collect($roleNames)->contains(fn($name) => str_contains(strtolower($name), 'admin'))) return 'admin';
                                if (collect($roleNames)->contains(fn($name) => str_contains(strtolower($name), 'manager'))) return 'manager';
                                if (collect($roleNames)->contains(fn($name) => str_contains(strtolower($name), 'support'))) return 'support';
                                if (collect($roleNames)->contains(fn($name) => str_contains(strtolower($name), 'api'))) return 'api';
                                return 'user';
                            });
                        @endphp

                        @foreach(['admin', 'manager', 'support', 'api', 'user'] as $roleType)
                            @if($usersByRole->has($roleType))
                                <h6 class="text-muted mb-3 mt-3">
                                    <span class="role-badge role-{{ $roleType }} me-2">{{ $roleType }}</span>
                                    {{ ucfirst($roleType) }} Users ({{ $usersByRole[$roleType]->count() }})
                                </h6>
                                
                                @foreach($usersByRole[$roleType] as $user)
                                    <div class="d-flex align-items-center mb-2 p-2 rounded" style="background: #f8f9fa;">
                                        <div class="user-avatar me-3">
                                            {{ substr($user->display_name ?? $user->name, 0, 1) }}
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $user->display_name ?? $user->name }}</h6>
                                            <small class="text-muted">
                                                {{ $user->email }} • 
                                                Roles: {{ $user->roles->pluck('display_name')->join(', ') }}
                                            </small>
                                        </div>
                                        <span class="usage-indicator usage-active" title="Active access"></span>
                                    </div>
                                @endforeach
                            @endif
                        @endforeach

                        @if($stats['users_count'] > $usersWithPermission->count())
                        <div class="text-center mt-3">
                            <a href="{{ route('users.index', ['permission' => $permission->name]) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye me-1"></i> View All {{ $stats['users_count'] }} Users
                            </a>
                        </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No users found</h6>
                            <p class="text-muted">No users currently have this permission.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Usage Analysis -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Permission Usage Analysis
                    </h5>
                </div>
                <div class="card-body">
                    <div class="usage-matrix">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Role Type</th>
                                        <th>Roles Count</th>
                                        <th>Users Count</th>
                                        <th>Usage Status</th>
                                        <th>Last Activity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $roleAnalysis = [
                                            'admin' => [
                                                'roles' => $permission->roles->filter(fn($r) => str_contains(strtolower($r->name), 'admin')),
                                                'users' => $usersWithPermission->filter(fn($u) => $u->roles->contains(fn($r) => str_contains(strtolower($r->name), 'admin')))
                                            ],
                                            'manager' => [
                                                'roles' => $permission->roles->filter(fn($r) => str_contains(strtolower($r->name), 'manager')),
                                                'users' => $usersWithPermission->filter(fn($u) => $u->roles->contains(fn($r) => str_contains(strtolower($r->name), 'manager')))
                                            ],
                                            'support' => [
                                                'roles' => $permission->roles->filter(fn($r) => str_contains(strtolower($r->name), 'support')),
                                                'users' => $usersWithPermission->filter(fn($u) => $u->roles->contains(fn($r) => str_contains(strtolower($r->name), 'support')))
                                            ],
                                            'api' => [
                                                'roles' => $permission->roles->filter(fn($r) => str_contains(strtolower($r->name), 'api')),
                                                'users' => $usersWithPermission->filter(fn($u) => $u->roles->contains(fn($r) => str_contains(strtolower($r->name), 'api')))
                                            ],
                                            'end-user' => [
                                                'roles' => $permission->roles->reject(fn($r) => 
                                                    str_contains(strtolower($r->name), 'admin') || 
                                                    str_contains(strtolower($r->name), 'manager') || 
                                                    str_contains(strtolower($r->name), 'support') || 
                                                    str_contains(strtolower($r->name), 'api')
                                                ),
                                                'users' => $usersWithPermission->reject(fn($u) => 
                                                    $u->roles->contains(fn($r) => 
                                                        str_contains(strtolower($r->name), 'admin') || 
                                                        str_contains(strtolower($r->name), 'manager') || 
                                                        str_contains(strtolower($r->name), 'support') || 
                                                        str_contains(strtolower($r->name), 'api')
                                                    )
                                                )
                                            ]
                                        ];
                                    @endphp

                                    @foreach($roleAnalysis as $type => $data)
                                        <tr>
                                            <td>
                                                <span class="role-badge role-{{ str_replace('-', '', $type) }} me-2">{{ $type }}</span>
                                                {{ ucfirst(str_replace('-', ' ', $type)) }}
                                            </td>
                                            <td>{{ $data['roles']->count() }}</td>
                                            <td>{{ $data['users']->count() }}</td>
                                            <td>
                                                @if($data['users']->count() > 0)
                                                    <span class="usage-indicator usage-active me-1"></span>
                                                    <span class="text-success">Active</span>
                                                @else
                                                    <span class="usage-indicator usage-inactive me-1"></span>
                                                    <span class="text-muted">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($data['users']->count() > 0)
                                                    <small class="text-muted">{{ $data['users']->max('last_login_at')?->diffForHumans() ?? 'No login data' }}</small>
                                                @else
                                                    <small class="text-muted">-</small>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    @if($recentActivity->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($recentActivity as $activity)
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">{{ $activity->description }}</h6>
                                    <small class="text-muted">
                                        By {{ $activity->causer->display_name ?? $activity->causer->name ?? 'System' }}
                                        • {{ $activity->created_at->diffForHumans() }}
                                    </small>
                                    @if($activity->properties->count() > 0)
                                        <div class="mt-2">
                                            @foreach($activity->properties as $key => $value)
                                                <span class="badge bg-light text-dark me-1">
                                                    {{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <small class="text-muted">{{ $activity->created_at->format('M d, H:i') }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Assign Role Modal -->
@can('edit-permissions')
<div class="modal fade" id="assignRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Permission to Roles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('permissions.bulk-assign-to-roles', $permission) }}">
                @csrf
                <div class="modal-body">
                    <p>Select roles to assign the permission "<strong>{{ $permission->display_name }}</strong>" to:</p>
                    
                    @php
                        $allRoles = \Spatie\Permission\Models\Role::all()->groupBy(function($role) {
                            if (str_contains(strtolower($role->name), 'admin')) return 'admin';
                            if (str_contains(strtolower($role->name), 'manager')) return 'manager';
                            if (str_contains(strtolower($role->name), 'support')) return 'support';
                            if (str_contains(strtolower($role->name), 'api')) return 'api';
                            return 'user';
                        });
                    @endphp

                    @foreach(['admin', 'manager', 'support', 'api', 'user'] as $roleType)
                        @if($allRoles->has($roleType))
                            <h6 class="mt-3 mb-2">
                                <span class="role-badge role-{{ $roleType }} me-2">{{ $roleType }}</span>
                                {{ ucfirst($roleType) }} Roles
                            </h6>
                            @foreach($allRoles[$roleType] as $role)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="role_ids[]" 
                                           value="{{ $role->id }}" id="role_{{ $role->id }}"
                                           {{ $permission->roles->contains($role->id) ? 'checked disabled' : '' }}>
                                    <label class="form-check-label d-flex justify-content-between align-items-center w-100" for="role_{{ $role->id }}">
                                        <div>
                                            <strong>{{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}</strong>
                                            <small class="text-muted d-block">{{ $role->users->count() }} users</small>
                                        </div>
                                        @if($permission->roles->contains($role->id))
                                            <span class="badge bg-success">Already assigned</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    @endforeach
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign to Selected Roles</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" action="{{ route('permissions.destroy', $permission) }}" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endcan
@endsection

@push('scripts')
<script>
function copyPermissionCode() {
    navigator.clipboard.writeText('{{ $permission->name }}').then(function() {
        // Show toast or alert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                icon: 'success',
                title: 'Permission code copied to clipboard!'
            });
        } else {
            alert('Permission code copied to clipboard!');
        }
    });
}

@can('delete-permissions')
function deletePermission() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Permission?',
            text: 'Are you sure you want to delete "{{ $permission->display_name }}"? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm').submit();
            }
        });
    } else {
        if (confirm('Are you sure you want to delete "{{ $permission->display_name }}"? This action cannot be undone.')) {
            document.getElementById('deleteForm').submit();
        }
    }
}
@endcan

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Add smooth animations
    const cards = document.querySelectorAll('.role-card, .stat-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
@endpush