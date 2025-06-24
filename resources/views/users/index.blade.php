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

.alert-floating {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1055;
    max-width: 400px;
    margin: 0;
}

.stats-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.filter-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.btn-action {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.role-badge {
    font-size: 0.7rem;
    padding: 2px 6px;
}

.department-badge {
    background: linear-gradient(45deg, #6c757d, #495057);
    color: white;
    border: none;
}

.sync-status-icon {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-users me-3"></i>User Management</h2>
                    <p class="mb-0">Manage system users, roles, and permissions</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group" role="group">
                        @can('create-users')
                            <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus me-1"></i>Add User
                            </button>
                        @endcan
                        <a href="{{ route('users.export', request()->query()) }}" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-download me-1"></i>Export
                        </a>
                        @can('manage-ldap')
                            <button type="button" class="btn btn-outline-light btn-sm" onclick="showLdapSyncModal()">
                                <i class="fas fa-sync me-1"></i>LDAP Sync
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">{{ number_format($stats['total_users'] ?? 0) }}</h3>
                            <p class="mb-0 small">Total Users</p>
                            <small class="opacity-75">
                                <i class="fas fa-chart-line me-1"></i>
                                +{{ $stats['new_this_month'] ?? 0 }} this month
                            </small>
                        </div>
                        <div>
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">{{ number_format($stats['active_users'] ?? 0) }}</h3>
                            <p class="mb-0 small">Active Users</p>
                            <small class="opacity-75">
                                <i class="fas fa-clock me-1"></i>
                                {{ $stats['recent_logins'] ?? 0 }} recent logins
                            </small>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">{{ number_format($stats['inactive_users'] ?? 0) }}</h3>
                            <p class="mb-0 small">Inactive Users</p>
                            <small class="opacity-75">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Require attention
                            </small>
                        </div>
                        <div>
                            <i class="fas fa-times-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card stats-card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-1">{{ number_format(($stats['ldap_users'] ?? 0) + ($stats['manual_users'] ?? 0)) }}</h3>
                            <p class="mb-0 small">Auth Sources</p>
                            <small class="opacity-75">
                                <i class="fas fa-server me-1"></i>
                                {{ $stats['ldap_users'] ?? 0 }} LDAP, {{ $stats['manual_users'] ?? 0 }} Manual
                            </small>
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
                    <i class="fas fa-check-square text-primary me-2"></i>
                    <span class="fw-bold"><span id="selectedCount">0</span> users selected</span>
                </div>
                <div class="col-auto">
                    <select name="action" class="form-select form-select-sm" required>
                        <option value="">Choose action...</option>
                        <option value="activate">
                            <i class="fas fa-play"></i> Activate Users
                        </option>
                        <option value="deactivate">
                            <i class="fas fa-pause"></i> Deactivate Users
                        </option>
                        <option value="reset_preferences">
                            <i class="fas fa-undo"></i> Reset Preferences
                        </option>
                        @can('delete-users')
                            <option value="delete">
                                <i class="fas fa-trash"></i> Delete Users
                            </option>
                        @endcan
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-bolt me-1"></i>Apply
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Filters and Search -->
    <div class="card filter-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="search" class="form-label">
                            <i class="fas fa-search me-1"></i>Search
                        </label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Name, username, email...">
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="department" class="form-label">
                            <i class="fas fa-building me-1"></i>Department
                        </label>
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
                        <label for="role" class="form-label">
                            <i class="fas fa-user-tag me-1"></i>Role
                        </label>
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
                        <label for="status" class="form-label">
                            <i class="fas fa-toggle-on me-1"></i>Status
                        </label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>
                                <i class="fas fa-check-circle"></i> Active
                            </option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>
                                <i class="fas fa-times-circle"></i> Inactive
                            </option>
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
                            <button type="button" class="btn btn-outline-info" onclick="toggleAdvancedFilters()">
                                <i class="fas fa-filter me-1"></i>Advanced
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Filters (Hidden by default) -->
                <div id="advancedFilters" class="row mt-3 pt-3 border-top" style="display: none;">
                    <div class="col-md-3 mb-3">
                        <label for="auth_source" class="form-label">Auth Source</label>
                        <select class="form-select" id="auth_source" name="auth_source">
                            <option value="">All Sources</option>
                            <option value="ldap" {{ request('auth_source') === 'ldap' ? 'selected' : '' }}>LDAP</option>
                            <option value="manual" {{ request('auth_source') === 'manual' ? 'selected' : '' }}>Manual</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="last_login" class="form-label">Last Login</label>
                        <select class="form-select" id="last_login" name="last_login">
                            <option value="">Any Time</option>
                            <option value="today" {{ request('last_login') === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="week" {{ request('last_login') === 'week' ? 'selected' : '' }}>This Week</option>
                            <option value="month" {{ request('last_login') === 'month' ? 'selected' : '' }}>This Month</option>
                            <option value="never" {{ request('last_login') === 'never' ? 'selected' : '' }}>Never</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="created_date" class="form-label">Created Date</label>
                        <select class="form-select" id="created_date" name="created_date">
                            <option value="">Any Date</option>
                            <option value="today" {{ request('created_date') === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="week" {{ request('created_date') === 'week' ? 'selected' : '' }}>This Week</option>
                            <option value="month" {{ request('created_date') === 'month' ? 'selected' : '' }}>This Month</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="has_groups" class="form-label">Group Membership</label>
                        <select class="form-select" id="has_groups" name="has_groups">
                            <option value="">Any</option>
                            <option value="yes" {{ request('has_groups') === 'yes' ? 'selected' : '' }}>Has Groups</option>
                            <option value="no" {{ request('has_groups') === 'no' ? 'selected' : '' }}>No Groups</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card table-container">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2 text-primary"></i>Users List 
                    <span class="badge bg-secondary ms-2">{{ $users->total() }}</span>
                </h5>
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="selectAll">
                        <label class="form-check-label" for="selectAll">
                            Select All
                        </label>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="refreshTable()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-columns"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><h6 class="dropdown-header">Show/Hide Columns</h6></li>
                                <li><a class="dropdown-item" href="#" onclick="toggleColumn('department')">
                                    <i class="fas fa-building me-2"></i>Department
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="toggleColumn('roles')">
                                    <i class="fas fa-user-tag me-2"></i>Roles
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="toggleColumn('groups')">
                                    <i class="fas fa-users me-2"></i>Groups
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="toggleColumn('last_login')">
                                    <i class="fas fa-clock me-2"></i>Last Login
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="usersTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="50" class="text-center">
                                    <input type="checkbox" id="selectAllTable" class="form-check-input">
                                </th>
                                <th class="sortable" data-sort="display_name">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'display_name', 'sort_direction' => request('sort_direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-user me-2"></i>Name
                                        @if(request('sort_by') === 'display_name')
                                            <i class="fas fa-sort-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" data-sort="username">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'username', 'sort_direction' => request('sort_direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-at me-2"></i>Username
                                        @if(request('sort_by') === 'username')
                                            <i class="fas fa-sort-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="sortable" data-sort="email">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'email', 'sort_direction' => request('sort_direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-envelope me-2"></i>Email
                                        @if(request('sort_by') === 'email')
                                            <i class="fas fa-sort-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="department-col sortable" data-sort="department">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'department', 'sort_direction' => request('sort_direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-building me-2"></i>Department
                                        @if(request('sort_by') === 'department')
                                            <i class="fas fa-sort-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th class="roles-col">
                                    <i class="fas fa-user-tag me-2"></i>Roles
                                </th>
                                <th class="groups-col">
                                    <i class="fas fa-users me-2"></i>Groups
                                </th>
                                <th>
                                    <i class="fas fa-toggle-on me-2"></i>Status
                                </th>
                                <th class="last-login-col sortable" data-sort="last_login_at">
                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'last_login_at', 'sort_direction' => request('sort_direction') === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="text-decoration-none text-dark d-flex align-items-center">
                                        <i class="fas fa-clock me-2"></i>Last Login
                                        @if(request('sort_by') === 'last_login_at')
                                            <i class="fas fa-sort-{{ request('sort_direction') === 'asc' ? 'up' : 'down' }} ms-1"></i>
                                        @endif
                                    </a>
                                </th>
                                <th width="120" class="text-center">
                                    <i class="fas fa-cogs me-2"></i>Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr data-user-id="{{ $user->id }}" class="user-row">
                                    <td class="text-center">
                                        <input type="checkbox" class="user-checkbox form-check-input" value="{{ $user->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0">
                                                {{ $user->initials }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="fw-semibold text-truncate">{{ $user->display_name }}</div>
                                                @if($user->auth_source === 'ldap' && $user->ldap_synced_at)
                                            <small class="d-block text-muted">
                                                <i class="fas fa-sync sync-status-icon me-1"></i>
                                                Synced {{ $user->ldap_synced_at->diffForHumans() }}
                                            </small>
                                        @endif
                                        @if($user->last_login_at && $user->last_login_at->isToday())
                                            <span class="badge bg-success ms-1" title="Logged in today">
                                                <i class="fas fa-circle"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-monospace">{{ $user->username }}</span>
                                        @if($user->auth_source === 'ldap')
                                            <span class="badge bg-info ms-1" title="LDAP Authentication">
                                                <i class="fas fa-server"></i> LDAP
                                            </span>
                                        @else
                                            <span class="badge bg-secondary ms-1" title="Manual Authentication">
                                                <i class="fas fa-user"></i> Manual
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="mailto:{{ $user->email }}" class="text-decoration-none">
                                            {{ $user->email }}
                                        </a>
                                    </td>
                                    <td class="department-col">
                                        @if($user->department)
                                            <span class="badge department-badge">
                                                <i class="fas fa-building me-1"></i>{{ $user->department }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="roles-col">
                                        @foreach($user->roles->take(2) as $role)
                                            <span class="badge bg-info me-1 role-badge" title="{{ $role->description ?? '' }}">
                                                {{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                                            </span>
                                        @endforeach
                                        @if($user->roles->count() > 2)
                                            <span class="badge bg-light text-dark role-badge" 
                                                  title="{{ $user->roles->skip(2)->pluck('name')->implode(', ') }}">
                                                +{{ $user->roles->count() - 2 }}
                                            </span>
                                        @endif
                                        @if($user->roles->count() === 0)
                                            <span class="text-muted small">No roles</span>
                                        @endif
                                    </td>
                                    <td class="groups-col">
                                        @if($user->notificationGroups->count() > 0)
                                            <span class="badge bg-light text-dark" title="{{ $user->notificationGroups->pluck('name')->implode(', ') }}">
                                                <i class="fas fa-users me-1"></i>{{ $user->notificationGroups->count() }}
                                            </span>
                                        @else
                                            <span class="text-muted small">No groups</span>
                                        @endif
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
                                    <td class="last-login-col">
                                        @if($user->last_login_at)
                                            <div class="d-flex flex-column">
                                                <small class="text-dark">
                                                    <i class="fas fa-clock me-1"></i>
                                                    {{ $user->last_login_at->format('M d, Y') }}
                                                </small>
                                                <small class="text-muted">
                                                    {{ $user->last_login_at->format('H:i') }} ({{ $user->last_login_at->diffForHumans() }})
                                                </small>
                                            </div>
                                        @else
                                            <span class="text-muted">
                                                <i class="fas fa-minus me-1"></i>Never logged in
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('users.show', $user) }}" 
                                               class="btn btn-outline-primary btn-action" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('manage-users')
                                                <button type="button" 
                                                        class="btn btn-outline-{{ $user->is_active ? 'warning' : 'success' }} btn-action" 
                                                        title="{{ $user->is_active ? 'Deactivate' : 'Activate' }} User"
                                                        onclick="toggleUserStatus({{ $user->id }}, '{{ $user->display_name }}', {{ $user->is_active ? 'true' : 'false' }})">
                                                    <i class="fas fa-{{ $user->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            @endcan
                                            <div class="btn-group" role="group">
                                                <button type="button" 
                                                        class="btn btn-outline-secondary btn-action dropdown-toggle" 
                                                        data-bs-toggle="dropdown"
                                                        title="More actions">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('users.show', $user) }}">
                                                            <i class="fas fa-eye me-2 text-primary"></i>View Profile
                                                        </a>
                                                    </li>
                                                    @can('edit-users')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('users.edit', $user) }}">
                                                                <i class="fas fa-edit me-2 text-info"></i>Edit User
                                                            </a>
                                                        </li>
                                                    @endcan
                                                    @can('manage-users')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('users.preferences.show', $user) }}">
                                                                <i class="fas fa-cog me-2 text-secondary"></i>Preferences
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item" onclick="manageUserRoles({{ $user->id }}, '{{ $user->display_name }}')">
                                                                <i class="fas fa-user-tag me-2 text-purple"></i>Manage Roles
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button class="dropdown-item" onclick="manageUserGroups({{ $user->id }}, '{{ $user->display_name }}')">
                                                                <i class="fas fa-users me-2 text-success"></i>Manage Groups
                                                            </button>
                                                        </li>
                                                        @if($user->auth_source === 'manual')
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <button class="dropdown-item" onclick="resetUserPassword({{ $user->id }}, '{{ $user->display_name }}')">
                                                                    <i class="fas fa-key me-2 text-warning"></i>Reset Password
                                                                </button>
                                                            </li>
                                                        @endif
                                                        @if($user->auth_source === 'ldap')
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <button class="dropdown-item" onclick="syncUserFromLdap({{ $user->id }}, '{{ $user->display_name }}')">
                                                                    <i class="fas fa-sync me-2 text-info"></i>Sync from LDAP
                                                                </button>
                                                            </li>
                                                        @endif
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
                            <i class="fas fa-info-circle me-1"></i>
                            Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ number_format($users->total()) }} results
                        </div>
                        <div>
                            {{ $users->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-users fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">No users found</h5>
                        <p class="text-muted">
                            @if(request()->hasAny(['search', 'department', 'role', 'status']))
                                No users match your current filter criteria.<br>
                                Try adjusting your search or clearing the filters.
                            @else
                                No users have been created yet.
                            @endif
                        </p>
                    </div>
                    <div class="d-flex justify-content-center gap-2">
                        @if(request()->hasAny(['search', 'department', 'role', 'status']))
                            <a href="{{ route('users.index') }}" class="btn btn-outline-primary">
                                <i class="fas fa-times me-2"></i>Clear Filters
                            </a>
                        @endif
                        @can('create-users')
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus me-2"></i>Add First User
                            </button>
                        @endcan
                        @can('sync-ldap')
                            <button type="button" class="btn btn-info" onclick="showLdapSyncModal()">
                                <i class="fas fa-sync me-2"></i>Sync from LDAP
                            </button>
                        @endcan
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Include Modal Files -->
@include('users.modals.add-user')
@include('users.modals.ldap-sync')
@include('users.modals.delete-confirmation')
@include('users.modals.manage-roles')
@include('users.modals.manage-groups')
@include('users.modals.reset-password')

<!-- Hidden Forms -->
<form id="toggle-status-form" method="POST" style="display: none;">
    @csrf
    @method('PATCH')
</form>

<form id="delete-user-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<form id="sync-user-form" method="POST" style="display: none;">
    @csrf
</form>
@endsection

@push('scripts')
<script>
// Global variables
let currentUserId = null;
let currentUserName = null;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializePage();
});

function initializePage() {
    initializeBulkSelection();
    initializeAutoFilters();
    initializeFormValidation();
    initializeTooltips();
    initializeKeyboardShortcuts();
    initializeTableFeatures();
}

// ========== BULK SELECTION ==========
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
    const someChecked = checkedBoxes.length > 0 && checkedBoxes.length < userCheckboxes.length;
    
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = someChecked;
    }
    if (selectAllTableCheckbox) {
        selectAllTableCheckbox.checked = allChecked;
        selectAllTableCheckbox.indeterminate = someChecked;
    }
}

function clearSelection() {
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    const selectAllCheckbox = document.getElementById('selectAll');
    const selectAllTableCheckbox = document.getElementById('selectAllTable');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
    }
    if (selectAllTableCheckbox) {
        selectAllTableCheckbox.checked = false;
        selectAllTableCheckbox.indeterminate = false;
    }
    updateBulkActions();
}

// ========== FILTERS ==========
function initializeAutoFilters() {
    const autoSubmitSelects = document.querySelectorAll('select[name="department"], select[name="role"], select[name="status"], select[name="auth_source"], select[name="last_login"], select[name="created_date"], select[name="has_groups"]');
    autoSubmitSelects.forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
}

function toggleAdvancedFilters() {
    const advancedFilters = document.getElementById('advancedFilters');
    const isVisible = advancedFilters.style.display !== 'none';
    
    if (isVisible) {
        advancedFilters.style.display = 'none';
    } else {
        advancedFilters.style.display = 'flex';
    }
}

// ========== USER MANAGEMENT ==========
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
    
    // document.getElementById('deleteUserName').textContent = userName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    modal.show();
}

function manageUserRoles(userId, userName) {
    currentUserId = userId;
    currentUserName = userName;
    
    document.getElementById('manageRolesUserName').textContent = userName;
    
    // Load current roles via AJAX
    loadUserRoles(userId);
    
    const modal = new bootstrap.Modal(document.getElementById('manageRolesModal'));
    modal.show();
}

function manageUserGroups(userId, userName) {
    currentUserId = userId;
    currentUserName = userName;
    
    document.getElementById('manageGroupsUserName').textContent = userName;
    
    // Load current groups via AJAX
    loadUserGroups(userId);
    
    const modal = new bootstrap.Modal(document.getElementById('manageGroupsModal'));
    modal.show();
}

function resetUserPassword(userId, userName) {
    currentUserId = userId;
    currentUserName = userName;
    
    document.getElementById('resetPasswordUserName').textContent = userName;
    
    const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    modal.show();
}

function syncUserFromLdap(userId, userName) {
    if (!confirm(`Are you sure you want to sync ${userName} from LDAP? This will update their profile information.`)) {
        return;
    }
    
    showLoading(true);
    
    const form = document.getElementById('sync-user-form');
    form.action = `/users/${userId}/sync-ldap`;
    form.submit();
}

// ========== LDAP SYNC ==========
function showLdapSyncModal() {
    const modal = new bootstrap.Modal(document.getElementById('ldapSyncModal'));
    modal.show();
    
    // Check current sync status
    checkLdapSyncStatus();
}

function checkLdapSyncStatus() {
    fetch('/users/ldap-sync-status')
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
                <div class="mt-2">
                    <small class="text-muted">Please wait while the system synchronizes user data from LDAP.</small>
                </div>
            </div>
        `;
        if (startSyncBtn) startSyncBtn.disabled = true;
        
        // Check again in 5 seconds
        setTimeout(checkLdapSyncStatus, 5000);
    } else {
        const connectionBadge = data.ldap_connection ? 
            '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Connected</span>' :
            '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Disconnected</span>';
            
        const enabledBadge = data.ldap_enabled ?
            '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Enabled</span>' :
            '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Disabled</span>';

        statusDiv.innerHTML = `
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="d-flex justify-content-between">
                        <strong>LDAP Status:</strong>
                        ${enabledBadge}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between">
                        <strong>Connection:</strong>
                        ${connectionBadge}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between">
                        <strong>Last Sync:</strong>
                        <small class="text-muted">${data.last_sync || 'Never'}</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between">
                        <strong>Total Users:</strong>
                        <span class="badge bg-secondary">${data.total_users || 0}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between">
                        <strong>LDAP Users:</strong>
                        <span class="badge bg-info">${data.ldap_users || 0}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between">
                        <strong>Manual Users:</strong>
                        <span class="badge bg-primary">${data.manual_users || 0}</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 pt-3 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">${data.message || 'Ready to sync'}</small>
                    ${data.can_sync ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-exclamation-triangle text-warning"></i>'}
                </div>
            </div>
        `;
        if (startSyncBtn) {
            startSyncBtn.disabled = !data.can_sync;
            if (data.can_sync) {
                startSyncBtn.innerHTML = '<i class="fas fa-play me-1"></i>Start Sync';
                startSyncBtn.className = 'btn btn-primary';
            } else {
                startSyncBtn.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>Cannot Sync';
                startSyncBtn.className = 'btn btn-warning';
            }
        }
    }
}

function startLdapSync() {
    if (!confirm('Are you sure you want to start LDAP synchronization? This process may take several minutes.')) {
        return;
    }
    
    showLoading(true);
    
    // Add CSRF token to headers
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch('/users/sync-ldap', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({})
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        showLoading(false);
        
        if (data.success) {
            // Show detailed success message
            const details = data.results ? 
                `<br><small>New: ${data.results.new_users}, Updated: ${data.results.updated_users}, Errors: ${data.results.errors}</small>` : '';
            showAlert('success', `${data.message}${details}<br><small>Synced at: ${data.synced_at}</small>`);
            
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
        showLoading(false);
        console.error('Error:', error);
        showAlert('danger', 'Sync failed: ' + error.message);
    });
}

// ========== FORM VALIDATION ==========
function initializeFormValidation() {
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
                'reset_preferences': 'reset preferences for',
                'delete': 'delete'
            };
            
            if (!confirm(`Are you sure you want to ${actionNames[action]} ${selectedCount} user(s)?`)) {
                e.preventDefault();
            }
        });
    }

    // Delete confirmation
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
}

// ========== TABLE FEATURES ==========
function initializeTableFeatures() {
    // Search with debounce
    const searchInput = document.getElementById('search');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    document.getElementById('filterForm').submit();
                }
            }, 500);
        });
    }
}

function refreshTable() {
    showLoading(true);
    window.location.reload();
}

function toggleColumn(columnName) {
    const columns = document.querySelectorAll(`.${columnName}-col`);
    columns.forEach(col => {
        if (col.style.display === 'none') {
            col.style.display = '';
        } else {
            col.style.display = 'none';
        }
    });
}

// ========== UTILITIES ==========
function initializeTooltips() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

function initializeKeyboardShortcuts() {
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
            // Close any open modals
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            });
        }
        
        // Ctrl+A to select all
        if (e.ctrlKey && e.key === 'a' && e.target.tagName !== 'INPUT') {
            e.preventDefault();
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.dispatchEvent(new Event('change'));
            }
        }
    });
}

function showLoading(show) {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = show ? 'flex' : 'none';
    }
}

function showAlert(type, message, duration = 5000) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-floating');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-floating`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
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

// ========== AJAX FUNCTIONS ==========
function loadUserRoles(userId) {
    fetch(`/users/ajax/${userId}/roles`)
        .then(response => response.json())
        .then(data => {
            if (data.roles) {
                updateRolesDisplay(data.roles);
            }
        })
        .catch(error => {
            console.error('Error loading user roles:', error);
        });
}

function loadUserGroups(userId) {
    fetch(`/users/ajax/${userId}/groups`)
        .then(response => response.json())
        .then(data => {
            if (data.groups) {
                updateGroupsDisplay(data.groups);
            }
        })
        .catch(error => {
            console.error('Error loading user groups:', error);
        });
}

function updateRolesDisplay(roles) {
    // Implementation depends on the manage roles modal structure
    console.log('User roles:', roles);
}

function updateGroupsDisplay(groups) {
    // Implementation depends on the manage groups modal structure
    console.log('User groups:', groups);
}

// Reset forms when modals are closed
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const forms = modal.querySelectorAll('form');
            forms.forEach(form => {
                form.reset();
                // Reset any custom states
                const submitBtns = form.querySelectorAll('button[type="submit"]');
                submitBtns.forEach(btn => {
                    btn.disabled = false;
                    btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
                });
            });
        });
    });
});
</script>
@endpush