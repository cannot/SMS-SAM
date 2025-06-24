@extends('layouts.app')

@section('title', 'Edit Permission - ' . $permission->display_name)

@push('styles')
<style>
.edit-form-container {
    max-width: 1000px;
    margin: 0 auto;
}

.form-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.form-section:hover {
    box-shadow: 0 6px 20px rgba(101, 209, 181, 0.15);
}

.form-section-header {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
    color: white;
    padding: 1.5rem;
    margin: 0;
}

.form-section-body {
    padding: 2rem;
}

.permission-info-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
}

.permission-info-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-green), var(--light-green));
}

.current-assignments {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.role-pill {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
    font-weight: 500;
    margin: 0.25rem;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.role-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    text-decoration: none;
}

.role-admin { 
    background: linear-gradient(135deg, #dc3545, #c82333); 
    color: white; 
    border-color: #dc3545;
}
.role-manager { 
    background: linear-gradient(135deg, #28a745, #1e7e34); 
    color: white; 
    border-color: #28a745;
}
.role-user { 
    background: linear-gradient(135deg, #6c757d, #545b62); 
    color: white; 
    border-color: #6c757d;
}
.role-api { 
    background: linear-gradient(135deg, #ffc107, #e0a800); 
    color: #212529; 
    border-color: #ffc107;
}
.role-support { 
    background: linear-gradient(135deg, #17a2b8, #138496); 
    color: white; 
    border-color: #17a2b8;
}

.stat-badge {
    background: linear-gradient(135deg, rgba(101, 209, 181, 0.1), rgba(101, 209, 181, 0.2));
    color: var(--primary-green);
    padding: 0.75rem 1.25rem;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 600;
    border: 2px solid rgba(101, 209, 181, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.warning-card {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    border: 2px solid #ffc107;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.danger-card {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    border: 2px solid #dc3545;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.form-help {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    border-left: 4px solid #2196f3;
    padding: 1.25rem;
    border-radius: 0 8px 8px 0;
    margin-bottom: 1rem;
}

.change-tracker {
    background: linear-gradient(135deg, #fff8e1, #ffecb3);
    border-left: 4px solid #ff9800;
    padding: 1.25rem;
    border-radius: 0 8px 8px 0;
    margin-bottom: 1rem;
    display: none;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.breadcrumb-item a {
    color: var(--primary-green);
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb-item a:hover {
    color: var(--dark-green);
}

.impact-analysis {
    border: 3px dashed #ffc107;
    border-radius: 12px;
    padding: 2rem;
    margin: 1.5rem 0;
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.05), rgba(255, 193, 7, 0.1));
    position: relative;
}

.impact-analysis::before {
    content: '⚠️';
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 1.5rem;
}

.role-assignment-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}

.role-group {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border-left: 4px solid #dee2e6;
    transition: all 0.3s ease;
}

.role-group:hover {
    border-left-color: var(--primary-green);
    box-shadow: 0 4px 12px rgba(101, 209, 181, 0.1);
}

.role-group.admin { border-left-color: #dc3545; }
.role-group.manager { border-left-color: #28a745; }
.role-group.support { border-left-color: #17a2b8; }
.role-group.api { border-left-color: #ffc107; }
.role-group.user { border-left-color: #6c757d; }

.activity-timeline {
    position: relative;
    padding-left: 2rem;
}

.activity-timeline::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, var(--primary-green), var(--light-green));
}

.activity-item {
    position: relative;
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.activity-item:hover {
    box-shadow: 0 4px 12px rgba(101, 209, 181, 0.1);
    transform: translateX(5px);
}

.activity-item::before {
    content: '';
    position: absolute;
    left: -2.25rem;
    top: 1rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--primary-green);
    border: 3px solid white;
    box-shadow: 0 0 0 2px var(--primary-green);
}

.form-floating-custom {
    position: relative;
}

.form-floating-custom .form-control {
    padding-top: 1.625rem;
    padding-bottom: 0.625rem;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-floating-custom .form-control:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 0.2rem rgba(101, 209, 181, 0.25);
}

.form-floating-custom label {
    padding: 1rem 0.75rem;
    color: #6c757d;
    transition: all 0.3s ease;
}

.action-buttons {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    border-top: 3px solid var(--primary-green);
}

.btn-custom {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.875rem;
}

.btn-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.modal-custom .modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-custom .modal-header {
    background: linear-gradient(135deg, var(--primary-green), var(--light-green));
    color: white;
    border-radius: 12px 12px 0 0;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.quick-stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    border: 2px solid #f1f3f4;
    transition: all 0.3s ease;
}

.quick-stat-card:hover {
    border-color: var(--primary-green);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(101, 209, 181, 0.15);
}

.quick-stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.quick-stat-label {
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.permission-code {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    font-family: 'Courier New', monospace;
    font-size: 1.1rem;
    color: #495057;
    text-align: center;
    margin: 1rem 0;
}

.section-divider {
    height: 2px;
    background: linear-gradient(to right, transparent, var(--primary-green), transparent);
    margin: 2rem 0;
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
            <li class="breadcrumb-item"><a href="{{ route('permissions.show', $permission) }}">{{ $permission->display_name }}</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </nav>

    <div class="edit-form-container">
        <!-- Header -->
        <div class="text-center mb-4">
            <h2>
                <i class="fas fa-edit text-warning me-2"></i>
                Edit Permission
            </h2>
            <p class="text-muted">Modify permission details and manage role assignments</p>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="quick-stat-card">
                <div class="quick-stat-number text-primary">{{ $permission->roles->count() }}</div>
                <div class="quick-stat-label">Assigned Roles</div>
            </div>
            <div class="quick-stat-card">
                <div class="quick-stat-number text-success">{{ \App\Models\User::permission($permission->name)->count() }}</div>
                <div class="quick-stat-label">Affected Users</div>
            </div>
            <div class="quick-stat-card">
                <div class="quick-stat-number text-info">{{ $permission->created_at->diffInDays() }}</div>
                <div class="quick-stat-label">Days Old</div>
            </div>
            <div class="quick-stat-card">
                <div class="quick-stat-number" style="color: var(--primary-green);">{{ ucfirst(explode('-', $permission->name)[0] ?? 'general') }}</div>
                <div class="quick-stat-label">Category</div>
            </div>
        </div>

        <!-- Current Permission Info -->
        <div class="form-section">
            <div class="form-section-header">
                <h4 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Current Permission Details
                </h4>
            </div>
            <div class="form-section-body">
                <div class="permission-info-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="text-dark mb-2">{{ $permission->display_name }}</h3>
                            <div class="permission-code">
                                <i class="fas fa-code me-2"></i>
                                {{ $permission->name }}
                            </div>
                            @if($permission->description)
                                <p class="text-muted mb-3">{{ $permission->description }}</p>
                            @endif
                            
                            <div class="d-flex flex-wrap gap-2">
                                <span class="stat-badge">
                                    <i class="fas fa-shield-alt"></i>
                                    {{ $permission->roles->count() }} Roles
                                </span>
                                <span class="stat-badge">
                                    <i class="fas fa-users"></i>
                                    {{ \App\Models\User::permission($permission->name)->count() }} Users
                                </span>
                                <span class="stat-badge">
                                    <i class="fas fa-clock"></i>
                                    {{ $permission->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="permission-icon">
                                @php $category = explode('-', $permission->name)[0] ?? 'general'; @endphp
                                <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-green), var(--light-green)); display: flex; align-items: center; justify-content: center; margin: 0 auto; color: white; font-size: 2rem;">
                                    <i class="fas fa-key"></i>
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-info">{{ ucfirst($category) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Role Assignments -->
                @if($permission->roles->count() > 0)
                <div class="role-assignment-section">
                    <h6 class="mb-3">
                        <i class="fas fa-shield-alt me-2"></i>
                        Currently Assigned to {{ $permission->roles->count() }} Role(s)
                    </h6>
                    
                    @php
                        $rolesByType = $permission->roles->groupBy(function($role) {
                            if (str_contains(strtolower($role->name), 'admin')) return 'admin';
                            if (str_contains(strtolower($role->name), 'manager')) return 'manager';
                            if (str_contains(strtolower($role->name), 'support')) return 'support';
                            if (str_contains(strtolower($role->name), 'api')) return 'api';
                            return 'user';
                        });
                    @endphp

                    @foreach($rolesByType as $type => $roles)
                        <div class="role-group {{ $type }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-capitalize">
                                    <i class="fas fa-users me-2"></i>
                                    {{ $type }} Roles ({{ $roles->count() }})
                                </h6>
                                <small class="text-muted">
                                    {{ $roles->sum(function($role) { return $role->users->count(); }) }} users affected
                                </small>
                            </div>
                            <div class="roles-list">
                                @foreach($roles as $role)
                                    <a href="{{ route('roles.show', $role) }}" class="role-pill role-{{ $type }}">
                                        {{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                                        <small>({{ $role->users->count() }})</small>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Warning Messages -->
        @if($permission->roles->count() > 0)
        <div class="warning-card">
            <div class="d-flex align-items-start">
                <i class="fas fa-exclamation-triangle text-warning me-3 fa-2x"></i>
                <div>
                    <h6 class="text-warning mb-2">Impact Warning</h6>
                    <p class="mb-2">
                        This permission is currently assigned to <strong>{{ $permission->roles->count() }} role(s)</strong> 
                        affecting <strong>{{ \App\Models\User::permission($permission->name)->count() }} user(s)</strong>.
                    </p>
                    <p class="mb-0 small">
                        Changes to the display name and description will be immediately visible to all affected users.
                        The permission code cannot be changed to maintain system integrity.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <!-- Change Tracker -->
        <div class="change-tracker" id="changeTracker">
            <div class="d-flex align-items-center">
                <i class="fas fa-clock text-warning me-3 fa-lg"></i>
                <div>
                    <strong>Unsaved Changes Detected</strong>
                    <p class="mb-0" id="changesList">You have made changes that haven't been saved yet.</p>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <form method="POST" action="{{ route('permissions.update', $permission) }}" id="editForm">
            @csrf
            @method('PUT')

            <!-- Basic Information -->
            <div class="form-section">
                <div class="form-section-header">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        Edit Basic Information
                    </h4>
                </div>
                <div class="form-section-body">
                    <div class="form-help">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-3 mt-1"></i>
                            <div>
                                <strong>Important Notes:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>The permission name cannot be changed as it may break existing code references</li>
                                    <li>Display name changes will be visible immediately to all users</li>
                                    <li>Description changes help users understand permission scope</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating-custom mb-3">
                                <input type="text" class="form-control" id="name" value="{{ $permission->name }}" readonly>
                                <label for="name">
                                    <i class="fas fa-lock me-2"></i>Permission Name (Read-only)
                                </label>
                                <small class="form-text text-muted">System identifier - cannot be changed</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating-custom mb-3">
                                <input type="text" class="form-control @error('display_name') is-invalid @enderror" 
                                       id="display_name" name="display_name" 
                                       value="{{ old('display_name', $permission->display_name) }}" 
                                       required data-original="{{ $permission->display_name }}">
                                <label for="display_name">
                                    <i class="fas fa-tag me-2"></i>Display Name <span class="text-danger">*</span>
                                </label>
                                <small class="form-text text-muted">Human-readable name for the permission</small>
                                @error('display_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-floating-custom mb-3">
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="4" style="height: 120px;"
                                  data-original="{{ $permission->description }}"
                                  placeholder="Describe what this permission allows users to do...">{{ old('description', $permission->description) }}</textarea>
                        <label for="description">
                            <i class="fas fa-align-left me-2"></i>Description
                        </label>
                        <small class="form-text text-muted">Help users understand what this permission controls</small>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Impact Analysis -->
            <div class="form-section">
                <div class="form-section-header">
                    <h4 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Change Impact Analysis
                    </h4>
                </div>
                <div class="form-section-body">
                    <div class="impact-analysis">
                        <h6 class="text-warning mb-3">
                            <i class="fas fa-analytics me-2"></i>
                            What will be affected by your changes:
                        </h6>
                        
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="impact-stat">
                                    <div class="display-4 fw-bold text-primary">{{ $permission->roles->count() }}</div>
                                    <small class="text-muted">Roles will see updated display name</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="impact-stat">
                                    <div class="display-4 fw-bold text-success">{{ \App\Models\User::permission($permission->name)->count() }}</div>
                                    <small class="text-muted">Users will be affected</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="impact-stat">
                                    <div class="display-4 fw-bold text-info">0</div>
                                    <small class="text-muted">Code references remain unchanged</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="impact-stat">
                                    <div class="display-4 fw-bold text-warning">100%</div>
                                    <small class="text-muted">Functionality preserved</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($permission->roles->count() > 0)
                    <div class="mt-4">
                        <h6 class="mb-3">Roles that will be affected:</h6>
                        <div class="row">
                            @foreach($permission->roles as $role)
                            <div class="col-md-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3 border">
                                    <div>
                                        <strong>{{ $role->display_name ?? $role->name }}</strong>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-users me-1"></i>
                                            {{ $role->users->count() }} users will see changes
                                        </small>
                                    </div>
                                    <div>
                                        <a href="{{ route('roles.show', $role) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Advanced Options -->
            <div class="form-section">
                <div class="form-section-header">
                    <h4 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Advanced Options
                    </h4>
                </div>
                <div class="form-section-body">
                    <!-- Role Management -->
                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-shield-alt me-2"></i>
                            Role Assignment Management
                        </h6>
                        <p class="text-muted mb-3">Quickly assign or remove this permission from roles.</p>
                        
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <button type="button" class="btn btn-outline-success w-100" 
                                        data-bs-toggle="modal" data-bs-target="#assignRoleModal">
                                    <i class="fas fa-plus me-2"></i>
                                    Assign to Additional Roles
                                </button>
                            </div>
                            <div class="col-md-6 mb-2">
                                <button type="button" class="btn btn-outline-warning w-100"
                                        data-bs-toggle="modal" data-bs-target="#removeRoleModal">
                                    <i class="fas fa-minus me-2"></i>
                                    Remove from Roles
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Logging -->
                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-history me-2"></i>
                            Change Logging & Notifications
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="log_changes" name="log_changes" checked>
                                    <label class="form-check-label" for="log_changes">
                                        <strong>Log this modification</strong>
                                        <small class="text-muted d-block">Record changes in activity history for audit purposes</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notify_users" name="notify_users">
                                    <label class="form-check-label" for="notify_users">
                                        <strong>Notify affected users</strong>
                                        <small class="text-muted d-block">Send notification about permission changes</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preview Changes -->
                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-eye me-2"></i>
                            Preview Changes
                        </h6>
                        <div class="bg-light border rounded-3 p-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">Current Display Name:</small>
                                    <div id="currentDisplayName">{{ $permission->display_name }}</div>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">New Display Name:</small>
                                    <div id="newDisplayName" class="fw-bold text-primary">{{ $permission->display_name }}</div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <small class="text-muted">Current Description:</small>
                                    <div id="currentDescription">{{ $permission->description ?: 'No description' }}</div>
                                    <small class="text-muted mt-2 d-block">New Description:</small>
                                    <div id="newDescription" class="fw-bold text-primary">{{ $permission->description ?: 'No description' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('permissions.show', $permission) }}" class="btn btn-outline-secondary btn-custom">
                            <i class="fas fa-arrow-left me-2"></i>Cancel
                        </a>
                        <a href="{{ route('permissions.index') }}" class="btn btn-outline-primary btn-custom">
                            <i class="fas fa-list me-2"></i>Back to List
                        </a>
                        <button type="button" class="btn btn-outline-info btn-custom" onclick="previewChanges()">
                            <i class="fas fa-eye me-2"></i>Preview Changes
                        </button>
                    </div>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-warning btn-custom" id="saveDraftButton" onclick="saveDraft()">
                            <i class="fas fa-save me-2"></i>Save Draft
                        </button>
                        <button type="submit" class="btn btn-success btn-custom" id="saveButton">
                            <i class="fas fa-check me-2"></i>Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="section-divider"></div>

        <!-- Recent Activity -->
        @if(isset($recentActivity) && $recentActivity->count() > 0)
        <div class="form-section">
            <div class="form-section-header">
                <h4 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Recent Activity
                </h4>
            </div>
            <div class="form-section-body">
                <div class="activity-timeline">
                    @foreach($recentActivity->take(5) as $activity)
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">{{ $activity->description }}</h6>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        {{ $activity->causer->display_name ?? $activity->causer->name ?? 'System' }}
                                        <i class="fas fa-clock mx-2"></i>
                                        {{ $activity->created_at->diffForHumans() }}
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
                
                @if($recentActivity->count() > 5)
                <div class="text-center mt-3">
                    <button class="btn btn-outline-primary btn-sm" onclick="loadMoreActivity()">
                        <i class="fas fa-plus me-1"></i>
                        Load More Activity ({{ $recentActivity->count() - 5 }} more)
                    </button>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Danger Zone -->
        @can('delete-permissions')
        <div class="form-section">
            <div class="form-section-header" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                <h4 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Danger Zone
                </h4>
            </div>
            <div class="form-section-body">
                <div class="danger-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="text-danger mb-2">
                                <i class="fas fa-trash me-2"></i>
                                Delete This Permission
                            </h6>
                            <p class="mb-2">
                                Once deleted, this permission will be removed from all roles and cannot be recovered. 
                                This action cannot be undone and may break system functionality.
                            </p>
                            @if($permission->roles->count() > 0)
                            <div class="alert alert-warning mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> This permission is currently assigned to {{ $permission->roles->count() }} role(s) 
                                affecting {{ \App\Models\User::permission($permission->name)->count() }} user(s).
                            </div>
                            @endif
                            <small class="text-muted">
                                <strong>What will happen:</strong>
                                Permission will be removed from all roles, affected users will lose access, 
                                and any code referencing this permission may fail.
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-danger btn-custom" onclick="confirmDelete()"
                                    {{ $permission->roles->count() > 0 ? 'disabled title="Cannot delete permission assigned to roles"' : '' }}>
                                <i class="fas fa-trash me-2"></i>
                                Delete Permission
                            </button>
                            @if($permission->roles->count() > 0)
                            <div class="mt-2">
                                <small class="text-muted">
                                    Remove from all roles first to enable deletion
                                </small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Form -->
        <form id="deleteForm" method="POST" action="{{ route('permissions.destroy', $permission) }}" style="display: none;">
            @csrf
            @method('DELETE')
        </form>
        @endcan
    </div>
</div>

<!-- Assign Role Modal -->
<div class="modal fade modal-custom" id="assignRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>
                    Assign to Additional Roles
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('permissions.bulk-assign-to-roles', $permission) }}">
                @csrf
                <div class="modal-body">
                    @php
                        $availableRoles = \Spatie\Permission\Models\Role::whereDoesntHave('permissions', function($q) use ($permission) {
                            $q->where('id', $permission->id);
                        })->get()->groupBy(function($role) {
                            if (str_contains(strtolower($role->name), 'admin')) return 'admin';
                            if (str_contains(strtolower($role->name), 'manager')) return 'manager';
                            if (str_contains(strtolower($role->name), 'support')) return 'support';
                            if (str_contains(strtolower($role->name), 'api')) return 'api';
                            return 'user';
                        });
                    @endphp

                    @if($availableRoles->count() > 0)
                        @foreach(['admin', 'manager', 'support', 'api', 'user'] as $roleType)
                            @if($availableRoles->has($roleType))
                                <div class="role-group {{ $roleType }} mb-3">
                                    <h6 class="mb-3 text-capitalize">
                                        <i class="fas fa-users me-2"></i>
                                        {{ $roleType }} Roles ({{ $availableRoles[$roleType]->count() }})
                                    </h6>
                                    @foreach($availableRoles[$roleType] as $role)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="role_ids[]" 
                                                   value="{{ $role->id }}" id="assign_role_{{ $role->id }}">
                                            <label class="form-check-label w-100" for="assign_role_{{ $role->id }}">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $role->display_name ?? $role->name }}</strong>
                                                        <small class="text-muted d-block">
                                                            <i class="fas fa-users me-1"></i>{{ $role->users->count() }} users
                                                            <i class="fas fa-shield-alt mx-2"></i>{{ $role->permissions->count() }} permissions
                                                        </small>
                                                    </div>
                                                    <span class="role-badge role-{{ $roleType }}">{{ $roleType }}</span>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h6 class="text-muted">All Available Roles Assigned</h6>
                            <p class="text-muted">All existing roles already have this permission assigned.</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Assign to Selected Roles
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Role Modal -->
<div class="modal fade modal-custom" id="removeRoleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                <h5 class="modal-title">
                    <i class="fas fa-minus me-2"></i>
                    Remove from Roles
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('permissions.bulk-remove-from-roles', $permission) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Removing this permission from roles will immediately affect all users in those roles.
                        Users will lose access to functionality controlled by this permission.
                    </div>

                    @if($permission->roles->count() > 0)
                        @foreach($permission->roles->groupBy(function($role) {
                            if (str_contains(strtolower($role->name), 'admin')) return 'admin';
                            if (str_contains(strtolower($role->name), 'manager')) return 'manager';
                            if (str_contains(strtolower($role->name), 'support')) return 'support';
                            if (str_contains(strtolower($role->name), 'api')) return 'api';
                            return 'user';
                        }) as $roleType => $roles)
                            <div class="role-group {{ $roleType }} mb-3">
                                <h6 class="mb-3 text-capitalize">
                                    <i class="fas fa-users me-2"></i>
                                    {{ $roleType }} Roles ({{ $roles->count() }})
                                </h6>
                                @foreach($roles as $role)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="role_ids[]" 
                                               value="{{ $role->id }}" id="remove_role_{{ $role->id }}">
                                        <label class="form-check-label w-100" for="remove_role_{{ $role->id }}">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ $role->display_name ?? $role->name }}</strong>
                                                    <small class="text-muted d-block">
                                                        <i class="fas fa-users me-1"></i>{{ $role->users->count() }} users will be affected
                                                        <i class="fas fa-exclamation-triangle mx-2 text-warning"></i>Will lose access
                                                    </small>
                                                </div>
                                                <span class="role-badge role-{{ $roleType }}">{{ $roleType }}</span>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                            <h6 class="text-muted">No Roles to Remove</h6>
                            <p class="text-muted">This permission is not currently assigned to any roles.</p>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-minus me-1"></i>Remove from Selected Roles
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Changes Modal -->
<div class="modal fade modal-custom" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>
                    Preview Changes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="submitForm()">
                    <i class="fas fa-save me-1"></i>Save These Changes
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let originalValues = {};
let hasChanges = false;
let autoSaveTimer;

// Track original values
document.addEventListener('DOMContentLoaded', function() {
    originalValues = {
        display_name: document.getElementById('display_name').value,
        description: document.getElementById('description').value
    };

    // Watch for changes
    ['display_name', 'description'].forEach(id => {
        document.getElementById(id).addEventListener('input', function() {
            trackChanges();
            updatePreview();
            scheduleAutoSave();
        });
    });

    // Load draft if exists
    loadDraft();
});

function trackChanges() {
    const currentValues = {
        display_name: document.getElementById('display_name').value,
        description: document.getElementById('description').value
    };

    hasChanges = JSON.stringify(currentValues) !== JSON.stringify(originalValues);
    
    const tracker = document.getElementById('changeTracker');
    const changesList = document.getElementById('changesList');
    const saveButton = document.getElementById('saveButton');
    
    if (hasChanges) {
        tracker.style.display = 'block';
        saveButton.classList.remove('btn-success');
        saveButton.classList.add('btn-warning');
        saveButton.innerHTML = '<i class="fas fa-save me-2"></i>Save Changes *';
        
        // List changes
        let changes = [];
        if (currentValues.display_name !== originalValues.display_name) {
            changes.push('Display Name');
        }
        if (currentValues.description !== originalValues.description) {
            changes.push('Description');
        }
        changesList.textContent = `Modified: ${changes.join(', ')}`;
    } else {
        tracker.style.display = 'none';
        saveButton.classList.remove('btn-warning');
        saveButton.classList.add('btn-success');
        saveButton.innerHTML = '<i class="fas fa-check me-2"></i>Save Changes';
    }
}

function updatePreview() {
    const displayName = document.getElementById('display_name').value;
    const description = document.getElementById('description').value;
    
    document.getElementById('newDisplayName').textContent = displayName || 'No display name';
    document.getElementById('newDescription').textContent = description || 'No description';
}

function previewChanges() {
    const currentValues = {
        display_name: document.getElementById('display_name').value,
        description: document.getElementById('description').value
    };

    let previewHtml = '<h6>Changes Summary:</h6>';
    previewHtml += '<div class="table-responsive"><table class="table table-sm">';
    previewHtml += '<thead><tr><th>Field</th><th>Current</th><th>New</th></tr></thead><tbody>';
    
    // Display Name
    if (currentValues.display_name !== originalValues.display_name) {
        previewHtml += `<tr class="table-warning">
            <td><strong><i class="fas fa-tag me-1"></i>Display Name</strong></td>
            <td>${originalValues.display_name || '<em class="text-muted">Empty</em>'}</td>
            <td><strong class="text-primary">${currentValues.display_name}</strong></td>
        </tr>`;
    } else {
        previewHtml += `<tr>
            <td><i class="fas fa-tag me-1"></i>Display Name</td>
            <td colspan="2">${currentValues.display_name} <small class="text-muted">(unchanged)</small></td>
        </tr>`;
    }
    
    // Description
    if (currentValues.description !== originalValues.description) {
        previewHtml += `<tr class="table-warning">
            <td><strong><i class="fas fa-align-left me-1"></i>Description</strong></td>
            <td>${originalValues.description || '<em class="text-muted">Empty</em>'}</td>
            <td><strong class="text-primary">${currentValues.description || '<em class="text-muted">Empty</em>'}</strong></td>
        </tr>`;
    } else {
        previewHtml += `<tr>
            <td><i class="fas fa-align-left me-1"></i>Description</td>
            <td colspan="2">${currentValues.description || '<em class="text-muted">Empty</em>'} <small class="text-muted">(unchanged)</small></td>
        </tr>`;
    }
    
    previewHtml += '</tbody></table></div>';
    
    // Impact summary
    previewHtml += `
        <h6 class="mt-4">Impact Summary:</h6>
        <div class="alert alert-info">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="h4 text-primary">{{ $permission->roles->count() }}</div>
                    <small>Roles affected</small>
                </div>
                <div class="col-md-3">
                    <div class="h4 text-success">{{ \App\Models\User::permission($permission->name)->count() }}</div>
                    <small>Users affected</small>
                </div>
                <div class="col-md-3">
                    <div class="h4 text-info">Instant</div>
                    <small>Changes apply</small>
                </div>
                <div class="col-md-3">
                    <div class="h4 text-warning">100%</div>
                    <small>Compatibility</small>
                </div>
            </div>
        </div>
    `;

    document.getElementById('previewContent').innerHTML = previewHtml;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

function submitForm() {
    document.getElementById('editForm').submit();
}

// Auto-save functionality
function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(function() {
        if (hasChanges) {
            saveDraft();
        }
    }, 30000); // Auto-save after 30 seconds of inactivity
}

function saveDraft() {
    const draftData = {
        display_name: document.getElementById('display_name').value,
        description: document.getElementById('description').value,
        permission_id: '{{ $permission->id }}',
        timestamp: new Date().toISOString()
    };
    
    localStorage.setItem('permission_edit_draft_{{ $permission->id }}', JSON.stringify(draftData));
    
    // Show indication
    const saveButton = document.getElementById('saveDraftButton');
    const originalText = saveButton.innerHTML;
    saveButton.innerHTML = '<i class="fas fa-check me-2"></i>Draft Saved';
    saveButton.classList.remove('btn-outline-warning');
    saveButton.classList.add('btn-success');
    
    setTimeout(() => {
        saveButton.innerHTML = originalText;
        saveButton.classList.remove('btn-success');
        saveButton.classList.add('btn-outline-warning');
    }, 2000);
}

function loadDraft() {
    const draftKey = 'permission_edit_draft_{{ $permission->id }}';
    const draft = localStorage.getItem(draftKey);
    
    if (draft) {
        try {
            const draftData = JSON.parse(draft);
            const draftAge = new Date() - new Date(draftData.timestamp);
            
            // Only restore if draft is less than 1 hour old
            if (draftAge < 3600000) {
                if (confirm('A recent draft was found. Would you like to restore it?')) {
                    document.getElementById('display_name').value = draftData.display_name;
                    document.getElementById('description').value = draftData.description;
                    trackChanges();
                    updatePreview();
                }
            }
        } catch (e) {
            localStorage.removeItem(draftKey);
        }
    }
}

@can('delete-permissions')
function confirmDelete() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Delete Permission?',
            html: `
                <div class="text-start">
                    <p>Are you sure you want to delete "<strong>{{ $permission->display_name }}</strong>"?</p>
                    <div class="alert alert-danger">
                        <strong>This action will:</strong>
                        <ul class="mb-0">
                            <li>Remove the permission from all roles</li>
                            <li>Affect {{ \App\Models\User::permission($permission->name)->count() }} users</li>
                            <li>Cannot be undone</li>
                            <li>May break code references</li>
                        </ul>
                    </div>
                    <p class="mt-3">Type <code>{{ $permission->name }}</code> to confirm:</p>
                    <input type="text" id="confirmInput" class="form-control" placeholder="Enter permission name">
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            preConfirm: () => {
                const input = document.getElementById('confirmInput');
                if (input.value !== '{{ $permission->name }}') {
                    Swal.showValidationMessage('Permission name does not match');
                    return false;
                }
                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteForm').submit();
            }
        });
    } else {
        const confirmText = prompt('Type "{{ $permission->name }}" to confirm deletion:');
        if (confirmText === '{{ $permission->name }}') {
            if (confirm('Are you absolutely sure? This action cannot be undone.')) {
                document.getElementById('deleteForm').submit();
            }
        } else if (confirmText !== null) {
            alert('Permission name does not match. Deletion cancelled.');
        }
    }
}
@endcan

// Load more activity
function loadMoreActivity() {
    // Implementation for loading more activity items
    console.log('Loading more activity...');
}

// Warn user about unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = '';
        return 'You have unsaved changes. Are you sure you want to leave?';
    }
});

// Form submission - remove beforeunload warning
document.getElementById('editForm').addEventListener('submit', function() {
    hasChanges = false;
    localStorage.removeItem('permission_edit_draft_{{ $permission->id }}');
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+S or Cmd+S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (hasChanges) {
            document.getElementById('editForm').submit();
        }
    }
    
    // Ctrl+P or Cmd+P to preview
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        previewChanges();
    }
    
    // Ctrl+D or Cmd+D to save draft
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        saveDraft();
    }
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>
@endpush