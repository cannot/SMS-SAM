@extends('layouts.app')

@section('title', 'Permissions Management')

@push('styles')
<style>
.permission-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
    background: white;
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
            <p class="text-muted">Define and manage system permissions</p>
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
                <a href="{{ route('roles.index') }}" class="btn btn-outline-primary">
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

    <!-- Search and Filters -->
    <div class="category-filter">
        <form method="GET" action="{{ route('permissions.index') }}">
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
                        <option value="grid" {{ request('view_mode', 'grid') === 'grid' ? 'selected' : '' }}>Grid View</option>
                        <option value="grouped" {{ request('view_mode') === 'grouped' ? 'selected' : '' }}>Grouped View</option>
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

    <!-- Permissions Display -->
    @if($permissions->count() > 0)
        @if(request('view_mode') === 'grouped')
            <!-- Grouped View -->
            <div class="grouped-view">
                @foreach($groupedPermissions as $category => $categoryPermissions)
                    <div class="category-section">
                        <div class="card">
                            <div class="category-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-key"></i> {{ ucfirst($category) }} Permissions
                                    </h5>
                                    <span class="badge bg-light text-dark">{{ $categoryPermissions->count() }} permissions</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @foreach($categoryPermissions as $permission)
                                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                            <div class="permission-card h-100">
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
                        <div class="permission-card h-100">
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
            <p class="text-muted">Create your first permission to get started.</p>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPermissionModal">
                <i class="fas fa-plus"></i> Create First Permission
            </button>
        </div>
    @endif
</div>

<!-- Create Permission Modal -->
<div class="modal fade" id="createPermissionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Permission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('permissions.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <div class="input-group">
                            <select class="form-select" id="category_select" name="category">
                                <option value="">Select or create category...</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                                @endforeach
                            </select>
                            <input type="text" class="form-control" id="category_input" name="category" 
                                   placeholder="Or type new category..." style="display: none;">
                            <button type="button" class="btn btn-outline-secondary" id="toggle_category_input">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Permission Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <small class="form-text text-muted">Will be prefixed with category (e.g., view-users)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="display_name" name="display_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Describe what this permission allows..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Assign to Roles (Optional)</label>
                        <div class="row">
                            @foreach(\Spatie\Permission\Models\Role::all() as $role)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" 
                                               value="{{ $role->name }}" id="role_{{ $role->id }}">
                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                            {{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create Permission
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Create Modal -->
<div class="modal fade" id="bulkCreateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Create Permissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('permissions.bulk-create') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bulk_category" class="form-label">Category <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bulk_category" name="category" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Permissions to Create</label>
                        <div id="permissionsList">
                            <div class="permission-input-group mb-2">
                                <div class="row">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="permissions[0][name]" placeholder="Permission name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="permissions[0][display_name]" placeholder="Display name" required>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger" onclick="removePermissionInput(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="row mt-1">
                                    <div class="col-md-10">
                                        <input type="text" class="form-control form-control-sm" name="permissions[0][description]" placeholder="Description (optional)">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPermissionInput()">
                            <i class="fas fa-plus"></i> Add Another Permission
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quick Templates</label>
                        <div class="btn-group btn-group-sm w-100">
                            <button type="button" class="btn btn-outline-secondary" onclick="loadTemplate('crud')">CRUD</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="loadTemplate('admin')">Admin</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="loadTemplate('api')">API</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Assign to Roles Modal -->
<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Permissions to Roles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkAssignForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Select roles to assign the selected permissions to:</p>
                    <div class="row">
                        @foreach(\Spatie\Permission\Models\Role::all() as $role)
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="role_ids[]" 
                                           value="{{ $role->id }}" id="bulk_role_{{ $role->id }}">
                                    <label class="form-check-label" for="bulk_role_{{ $role->id }}">
                                        {{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                                        <small class="text-muted d-block">{{ $role->users->count() }} users</small>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Permissions</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form id="deletePermissionForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
let permissionCounter = 1;

// View mode auto-submit
document.getElementById('view_mode').addEventListener('change', function() {
    this.closest('form').submit();
});

// Category input toggle
document.getElementById('toggle_category_input').addEventListener('click', function() {
    const select = document.getElementById('category_select');
    const input = document.getElementById('category_input');
    
    if (input.style.display === 'none') {
        select.style.display = 'none';
        input.style.display = 'block';
        input.focus();
        this.innerHTML = '<i class="fas fa-list"></i>';
    } else {
        select.style.display = 'block';
        input.style.display = 'none';
        this.innerHTML = '<i class="fas fa-plus"></i>';
    }
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
    document.getElementById('selectAll').checked = false;
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
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('bulkAssignModal'));
    modal.show();
    
    // Set form action and add permission IDs
    const form = document.getElementById('bulkAssignForm');
    form.action = '/permissions/bulk-assign-to-roles';
    
    // Remove existing permission inputs
    const existingInputs = form.querySelectorAll('input[name="permission_ids[]"]');
    existingInputs.forEach(input => input.remove());
    
    // Add selected permission IDs
    selected.forEach(checkbox => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'permission_ids[]';
        input.value = checkbox.value;
        form.appendChild(input);
    });
}

function bulkDelete() {
    const selected = document.querySelectorAll('.permission-checkbox:checked');
    if (selected.length === 0) {
        alert('Please select permissions to delete');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${selected.length} permission(s)? This action cannot be undone.`)) {
        // Implementation for bulk delete
        const permissionIds = Array.from(selected).map(cb => cb.value);
        console.log('Bulk delete permissions:', permissionIds);
        
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

// Bulk create functionality
function addPermissionInput() {
    const container = document.getElementById('permissionsList');
    const newGroup = document.createElement('div');
    newGroup.className = 'permission-input-group mb-2';
    newGroup.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <input type="text" class="form-control" name="permissions[${permissionCounter}][name]" placeholder="Permission name" required>
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" name="permissions[${permissionCounter}][display_name]" placeholder="Display name" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger" onclick="removePermissionInput(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="row mt-1">
            <div class="col-md-10">
                <input type="text" class="form-control form-control-sm" name="permissions[${permissionCounter}][description]" placeholder="Description (optional)">
            </div>
        </div>
    `;
    container.appendChild(newGroup);
    permissionCounter++;
}

function removePermissionInput(button) {
    const container = document.getElementById('permissionsList');
    if (container.children.length > 1) {
        button.closest('.permission-input-group').remove();
    }
}

function loadTemplate(type) {
    const container = document.getElementById('permissionsList');
    const categoryInput = document.getElementById('bulk_category');
    
    // Clear existing inputs
    container.innerHTML = '';
    permissionCounter = 0;
    
    let templates = {};
    
    switch(type) {
        case 'crud':
            categoryInput.value = 'content';
            templates = {
                'view': 'View Content',
                'create': 'Create Content', 
                'edit': 'Edit Content',
                'delete': 'Delete Content'
            };
            break;
        case 'admin':
            categoryInput.value = 'admin';
            templates = {
                'dashboard': 'View Dashboard',
                'settings': 'Manage Settings',
                'logs': 'View System Logs',
                'maintenance': 'System Maintenance'
            };
            break;
        case 'api':
            categoryInput.value = 'api';
            templates = {
                'read': 'API Read Access',
                'write': 'API Write Access',
                'delete': 'API Delete Access',
                'admin': 'API Admin Access'
            };
            break;
    }
    
    Object.entries(templates).forEach(([name, display], index) => {
        const newGroup = document.createElement('div');
        newGroup.className = 'permission-input-group mb-2';
        newGroup.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="permissions[${index}][name]" value="${name}" required>
                </div>
                <div class="col-md-6">
                    <input type="text" class="form-control" name="permissions[${index}][display_name]" value="${display}" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger" onclick="removePermissionInput(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-1">
                <div class="col-md-10">
                    <input type="text" class="form-control form-control-sm" name="permissions[${index}][description]" placeholder="Description (optional)">
                </div>
            </div>
        `;
        container.appendChild(newGroup);
    });
    
    permissionCounter = Object.keys(templates).length;
}

// Auto-generate permission name from display name
document.addEventListener('change', function(e) {
    if (e.target.name && e.target.name.includes('[display_name]')) {
        const nameInput = e.target.closest('.row').querySelector('input[name*="[name]"]');
        if (nameInput && !nameInput.value) {
            nameInput.value = e.target.value.toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        }
    }
});

// Category and name auto-combination for single permission create
document.getElementById('name').addEventListener('input', function() {
    updatePermissionPreview();
});

document.getElementById('category_select').addEventListener('change', function() {
    updatePermissionPreview();
});

document.getElementById('category_input').addEventListener('input', function() {
    updatePermissionPreview();
});

function updatePermissionPreview() {
    const nameInput = document.getElementById('name');
    const categorySelect = document.getElementById('category_select');
    const categoryInput = document.getElementById('category_input');
    
    const category = categoryInput.style.display !== 'none' ? categoryInput.value : categorySelect.value;
    const name = nameInput.value;
    
    if (category && name) {
        const preview = category + '-' + name;
        nameInput.setAttribute('data-preview', preview);
        
        // Show preview
        let previewElement = document.getElementById('name_preview');
        if (!previewElement) {
            previewElement = document.createElement('small');
            previewElement.id = 'name_preview';
            previewElement.className = 'form-text text-info';
            nameInput.parentNode.appendChild(previewElement);
        }
        previewElement.textContent = `Final name: ${preview}`;
    }
}

// Initialize tooltips if using Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush