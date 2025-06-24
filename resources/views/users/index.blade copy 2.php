@extends('layouts.app')

@section('title', 'User Management')

@push('styles')
<style>
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 16px;
}

.status-badge {
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.status-active {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.status-inactive {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.bulk-actions {
    background: rgba(13, 110, 253, 0.05);
    border: 1px solid #0d6efd;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    display: none;
}

.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 5px solid rgba(255, 255, 255, 0.3);
    border-top: 5px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.form-floating .form-control:focus ~ label,
.form-floating .form-control:not(:placeholder-shown) ~ label {
    opacity: .65;
    transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: 0.25rem;
}

.avatar-sm {
    width: 35px;
    height: 35px;
    font-size: 14px;
}
</style>
@endpush

@section('content')
<div class="container-fluid mt-4">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-users me-2"></i>User Management</h2>
            <p class="text-muted mb-0">Manage system users and their permissions</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group" role="group">
                @can('create-users')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus me-1"></i>Add User
                    </button>
                @endcan
                <a href="{{ route('users.export', request()->query()) }}" class="btn btn-outline-success">
                    <i class="fas fa-download me-1"></i>Export
                </a>
                @can('sync-ldap')
                    <button type="button" class="btn btn-outline-info" onclick="showLdapSyncModal()">
                        <i class="fas fa-sync me-1"></i>LDAP Sync
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">{{ number_format($stats['total_users'] ?? 0) }}</h3>
                            <p class="mb-0">Total Users</p>
                        </div>
                        <div>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">{{ number_format($stats['active_users'] ?? 0) }}</h3>
                            <p class="mb-0">Active Users</p>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">{{ number_format($stats['inactive_users'] ?? 0) }}</h3>
                            <p class="mb-0">Inactive Users</p>
                        </div>
                        <div>
                            <i class="fas fa-times-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">{{ number_format($stats['recent_logins'] ?? 0) }}</h3>
                            <p class="mb-0">Recent Logins (7 days)</p>
                        </div>
                        <div>
                            <i class="fas fa-sign-in-alt fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions (Hidden by default) -->
    <div id="bulkActions" class="bulk-actions">
        <form id="bulkForm" method="POST" action="{{ route('users.bulk-action') }}">
            @csrf
            <div class="row align-items-center">
                <div class="col-auto">
                    <span class="fw-bold"><span id="selectedCount">0</span> users selected</span>
                </div>
                <div class="col-auto">
                    <select name="action" class="form-select form-select-sm" required>
                        <option value="">Choose action...</option>
                        <option value="activate">Activate</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="reset_preferences">Reset Preferences</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">Clear</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Name, username, email...">
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>
                                    {{ $dept }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>
                                    {{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i>Search
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card table-container">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Users List</h5>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">
                        Select All
                    </label>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">
                                    <input type="checkbox" id="selectAllTable" class="form-check-input">
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'display_name', 'sort_direction' => request('sort_direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark">
                                        Name
                                        @if(request('sort_by') === 'display_name')
                                            <i class="fas fa-sort-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'username', 'sort_direction' => request('sort_direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark">
                                        Username
                                        @if(request('sort_by') === 'username')
                                            <i class="fas fa-sort-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'email', 'sort_direction' => request('sort_direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark">
                                        Email
                                        @if(request('sort_by') === 'email')
                                            <i class="fas fa-sort-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'department', 'sort_direction' => request('sort_direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark">
                                        Department
                                        @if(request('sort_by') === 'department')
                                            <i class="fas fa-sort-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th>Roles</th>
                                <th>Groups</th>
                                <th>Status</th>
                                <th>
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'last_login_at', 'sort_direction' => request('sort_direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark">
                                        Last Login
                                        @if(request('sort_by') === 'last_login_at')
                                            <i class="fas fa-sort-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th width="120" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="user-checkbox form-check-input" value="{{ $user->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ $user->initials }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $user->display_name }}</div>
                                                @if($user->title)
                                                    <small class="text-muted">{{ $user->title }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-monospace">{{ $user->username }}</span>
                                        @if($user->auth_source === 'ldap')
                                            <small class="badge bg-info ms-1">LDAP</small>
                                        @endif
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @if($user->department)
                                            <span class="badge bg-secondary">{{ $user->department }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @foreach($user->roles->take(2) as $role)
                                            <span class="badge bg-info me-1">
                                                {{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                                            </span>
                                        @endforeach
                                        @if($user->roles->count() > 2)
                                            <span class="badge bg-light text-dark">+{{ $user->roles->count() - 2 }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-users me-1"></i>{{ $user->notificationGroups->count() }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($user->is_active)
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>Active
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times me-1"></i>Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->last_login_at)
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $user->last_login_at->diffForHumans() }}
                                            </small>
                                        @else
                                            <span class="text-muted">
                                                <i class="fas fa-minus me-1"></i>Never
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('users.show', $user) }}" 
                                               class="btn btn-outline-primary" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('manage-users')
                                                <button type="button" 
                                                        class="btn btn-outline-{{ $user->is_active ? 'warning' : 'success' }}" 
                                                        title="{{ $user->is_active ? 'Deactivate' : 'Activate' }} User"
                                                        onclick="toggleUserStatus({{ $user->id }}, '{{ $user->display_name }}', {{ $user->is_active ? 'true' : 'false' }})">
                                                    <i class="fas fa-{{ $user->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            @endcan
                                            <div class="btn-group" role="group">
                                                <button type="button" 
                                                        class="btn btn-outline-secondary dropdown-toggle" 
                                                        data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('users.show', $user) }}">
                                                            <i class="fas fa-eye me-2"></i>View Profile
                                                        </a>
                                                    </li>
                                                    @can('edit-users')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('users.edit', $user) }}">
                                                                <i class="fas fa-edit me-2"></i>Edit User
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('manage-users')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('users.preferences.show', $user) }}">
                                                                <i class="fas fa-cog me-2"></i>Preferences
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <button class="dropdown-item text-danger" 
                                                                    onclick="deleteUser({{ $user->id }}, '{{ $user->display_name }}')">
                                                                <i class="fas fa-trash me-2"></i>Delete User
                                                            </button>
                                                        </li>
                                                    @endcan
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results
                        </div>
                        <div>
                            {{ $users->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No users found</h5>
                    <p class="text-muted">Try adjusting your search criteria or add new users.</p>
                    @can('create-users')
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-2"></i>Add First User
                        </button>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add New User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addUserForm" method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                                <label for="username">Username *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                                <label for="email">Email Address *</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" required>
                                <label for="first_name">First Name *</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" required>
                                <label for="last_name">Last Name *</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="display_name" name="display_name" placeholder="Display Name">
                        <label for="display_name">Display Name (Auto-generated if empty)</label>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="add_department" name="department">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept }}">{{ $dept }}</option>
                                    @endforeach
                                </select>
                                <label for="add_department">Department</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="title" name="title" placeholder="Job Title">
                                <label for="title">Job Title</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone Number">
                        <label for="phone">Phone Number</label>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <label for="password">Temporary Password *</label>
                                <div class="form-text">
                                    User will be required to change this password on first login
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm Password" required>
                                <label for="password_confirmation">Confirm Password *</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Assign Roles</label>
                        <div class="row">
                            @foreach($roles->where('name', '!=', 'super-admin') as $role)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" id="role_{{ $role->id }}">
                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                            {{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active User
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="send_welcome_email" name="send_welcome_email" checked>
                            <label class="form-check-label" for="send_welcome_email">
                                Send welcome email with login credentials
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="must_change_password" name="must_change_password" checked>
                            <label class="form-check-label" for="must_change_password">
                                Force password change on first login
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="auth_source" value="manual">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info" onclick="generatePassword()">
                        <i class="fas fa-key me-1"></i>Generate Password
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- LDAP Sync Modal -->
<div class="modal fade" id="ldapSyncModal" tabindex="-1" aria-labelledby="ldapSyncModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ldapSyncModalLabel">
                    <i class="fas fa-sync me-2"></i>LDAP Synchronization
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="syncStatus">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Checking LDAP sync status...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="startSyncBtn" onclick="startLdapSync()">
                    <i class="fas fa-play me-1"></i>Start Sync
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">
                    <i class="fas fa-exclamation-triangle me-2 text-warning"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                <p class="text-muted">This action will deactivate the user account. The user can be restored later if needed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>Delete User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form id="toggle-status-form" method="POST" style="display: none;">
    @csrf
    @method('PATCH')
</form>

<form id="delete-user-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
// Global variables
let currentUserId = null;
let currentUserName = null;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeBulkSelection();
    initializeAutoFilters();
    initializeFormValidation();
});

// Bulk selection functionality
function initializeBulkSelection() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const selectAllTableCheckbox = document.getElementById('selectAllTable');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');

    // Main select all
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            if (selectAllTableCheckbox) {
                selectAllTableCheckbox.checked = this.checked;
            }
            updateBulkActions();
        });
    }

    // Table header select all
    if (selectAllTableCheckbox) {
        selectAllTableCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = this.checked;
            }
            updateBulkActions();
        });
    }

    // Individual checkboxes
    userCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActions();
            updateSelectAllState();
        });
    });
}

function updateBulkActions() {
    const selected = document.querySelectorAll('.user-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selectedCount) {
        selectedCount.textContent = selected.length;
    }
    
    if (bulkActions) {
        if (selected.length > 0) {
            bulkActions.style.display = 'block';
            
            // Clear existing hidden inputs
            const existingInputs = document.querySelectorAll('#bulkForm input[name="user_ids[]"]');
            existingInputs.forEach(input => input.remove());
            
            // Add selected IDs to form
            const bulkForm = document.getElementById('bulkForm');
            selected.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_ids[]';
                input.value = checkbox.value;
                bulkForm.appendChild(input);
            });
        } else {
            bulkActions.style.display = 'none';
        }
    }
}

function updateSelectAllState() {
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    const selectAllCheckbox = document.getElementById('selectAll');
    const selectAllTableCheckbox = document.getElementById('selectAllTable');
    
    const allChecked = userCheckboxes.length > 0 && checkedBoxes.length === userCheckboxes.length;
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = allChecked;
    }
    if (selectAllTableCheckbox) {
        selectAllTableCheckbox.checked = allChecked;
    }
}

function clearSelection() {
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    const selectAllCheckbox = document.getElementById('selectAll');
    const selectAllTableCheckbox = document.getElementById('selectAllTable');
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    if (selectAllTableCheckbox) selectAllTableCheckbox.checked = false;
    updateBulkActions();
}

// Auto-submit filters
function initializeAutoFilters() {
    const autoSubmitSelects = document.querySelectorAll('select[name="department"], select[name="role"], select[name="status"]');
    autoSubmitSelects.forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
}

// User management functions
function toggleUserStatus(userId, userName, isActive) {
    const action = isActive ? 'deactivate' : 'activate';
    const message = `Are you sure you want to ${action} ${userName}?`;
    
    if (confirm(message)) {
        const form = document.getElementById('toggle-status-form');
        form.action = `/users/${userId}/toggle-status`;
        form.submit();
    }
}

function deleteUser(userId, userName) {
    currentUserId = userId;
    currentUserName = userName;
    
    document.getElementById('deleteUserName').textContent = userName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    modal.show();
}

// Event listener for delete confirmation
document.addEventListener('DOMContentLoaded', function() {
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (currentUserId) {
                const form = document.getElementById('delete-user-form');
                form.action = `/users/${currentUserId}`;
                form.submit();
            }
        });
    }
});

// LDAP Sync functionality
function showLdapSyncModal() {
    const modal = new bootstrap.Modal(document.getElementById('ldapSyncModal'));
    modal.show();
    
    // Check current sync status
    checkLdapSyncStatus();
}

function checkLdapSyncStatus() {
    fetch('{{ route('users.ldap-sync-status') }}')
        .then(response => response.json())
        .then(data => {
            updateSyncStatus(data);
        })
        .catch(error => {
            console.error('Error checking sync status:', error);
            updateSyncStatus({
                error: true,
                message: 'Failed to check sync status'
            });
        });
}

function updateSyncStatus(data) {
    const statusDiv = document.getElementById('syncStatus');
    const startSyncBtn = document.getElementById('startSyncBtn');
    
    if (data.error) {
        statusDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>${data.message}
            </div>
        `;
        if (startSyncBtn) startSyncBtn.disabled = true;
        return;
    }
    
    if (data.is_running) {
        statusDiv.innerHTML = `
            <div class="alert alert-info">
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <span>Sync is currently running...</span>
                </div>
            </div>
        `;
        if (startSyncBtn) startSyncBtn.disabled = true;
        
        // Check again in 5 seconds
        setTimeout(checkLdapSyncStatus, 5000);
    } else {
        statusDiv.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Last Sync:</strong> ${data.last_sync || 'Never'}
                </div>
                <div class="col-md-6">
                    <strong>Total Users:</strong> ${data.total_users || 0}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <strong>LDAP Status:</strong> 
                    <span class="badge bg-${data.ldap_enabled ? 'success' : 'danger'}">
                        ${data.ldap_enabled ? 'Enabled' : 'Disabled'}
                    </span>
                </div>
                <div class="col-md-6">
                    <strong>Connection:</strong>
                    <span class="badge bg-${data.ldap_connection ? 'success' : 'danger'}">
                        ${data.ldap_connection ? 'Connected' : 'Disconnected'}
                    </span>
                </div>
            </div>
            <div class="mt-3">
                <small class="text-muted">${data.message || 'Ready to sync'}</small>
            </div>
        `;
        if (startSyncBtn) {
            startSyncBtn.disabled = !data.ldap_enabled || !data.ldap_connection;
        }
    }
}

function startLdapSync() {
    if (!confirm('Are you sure you want to start LDAP synchronization? This process may take several minutes.')) {
        return;
    }
    
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
    
    fetch('{{ route('users.sync-ldap') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
        
        if (data.success) {
            // Show success message
            showAlert('success', `${data.message}<br><small>Synced at: ${data.synced_at}</small>`);
            
            // Close modal
            const modalElement = document.getElementById('ldapSyncModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
            
            // Reload page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert('danger', 'Sync failed: ' + data.message);
        }
    })
    .catch(error => {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
        console.error('Error:', error);
        showAlert('danger', 'Sync failed. Please check console for details.');
    });
}

// Add User Form functionality
function initializeFormValidation() {
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showAlert('danger', 'Passwords do not match!');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                showAlert('danger', 'Password must be at least 8 characters long!');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';
            submitBtn.disabled = true;
        });

        // Auto-generate display name
        const firstNameField = document.getElementById('first_name');
        const lastNameField = document.getElementById('last_name');
        
        if (firstNameField && lastNameField) {
            firstNameField.addEventListener('input', generateDisplayName);
            lastNameField.addEventListener('input', generateDisplayName);
        }
    }
}

function generateDisplayName() {
    const firstName = document.getElementById('first_name').value;
    const lastName = document.getElementById('last_name').value;
    const displayName = document.getElementById('display_name');
    
    if (firstName && lastName && !displayName.value) {
        displayName.value = `${firstName} ${lastName}`;
    }
}

function generatePassword() {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    
    const passwordField = document.getElementById('password');
    const confirmField = document.getElementById('password_confirmation');
    
    passwordField.value = password;
    confirmField.value = password;
    
    // Show password briefly
    passwordField.type = 'text';
    confirmField.type = 'text';
    
    setTimeout(() => {
        passwordField.type = 'password';
        confirmField.type = 'password';
    }, 2000);
    
    showAlert('info', 'Password generated and filled automatically!');
}

// Bulk form submission with confirmation
document.addEventListener('DOMContentLoaded', function() {
    const bulkForm = document.getElementById('bulkForm');
    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            const action = this.querySelector('select[name="action"]').value;
            const selectedCount = document.querySelectorAll('.user-checkbox:checked').length;
            
            if (!action) {
                e.preventDefault();
                showAlert('warning', 'Please select an action');
                return;
            }
            
            const actionNames = {
                'activate': 'activate',
                'deactivate': 'deactivate',
                'reset_preferences': 'reset preferences for'
            };
            
            if (!confirm(`Are you sure you want to ${actionNames[action]} ${selectedCount} user(s)?`)) {
                e.preventDefault();
            }
        });
    }
});

// Reset form when modal is closed
document.addEventListener('DOMContentLoaded', function() {
    const addUserModal = document.getElementById('addUserModal');
    if (addUserModal) {
        addUserModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('addUserForm');
            if (form) {
                form.reset();
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Create User';
                    submitBtn.disabled = false;
                }
            }
        });
    }
});

// Utility function to show alerts
function showAlert(type, message, duration = 5000) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert.alert-dismissible.fade.show');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 1055; max-width: 400px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remove after duration
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, duration);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+N to add new user
    if (e.ctrlKey && e.key === 'n') {
        e.preventDefault();
        const addUserBtn = document.querySelector('[data-bs-target="#addUserModal"]');
        if (addUserBtn) {
            addUserBtn.click();
        }
    }
    
    // Escape to clear selection
    if (e.key === 'Escape') {
        clearSelection();
    }
});

// Search with debounce
let searchTimeout;
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    document.getElementById('filterForm').submit();
                }
            }, 500);
        });
    }
});
</script>
@endpush