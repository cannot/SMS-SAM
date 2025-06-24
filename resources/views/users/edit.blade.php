@extends('layouts.app')

@section('title', 'Edit User - ' . $user->display_name)

@push('styles')
<style>
.user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 24px;
}

.form-floating .form-control:focus ~ label,
.form-floating .form-control:not(:placeholder-shown) ~ label {
    opacity: .65;
    transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
}

.auth-source-badge {
    position: absolute;
    top: 10px;
    right: 10px;
}

.readonly-field {
    background-color: #f8f9fa;
    border-color: #e9ecef;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>
@endpush

@section('content')
<div class="container-fluid mt-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.show', $user) }}">{{ $user->display_name }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
            <h2><i class="fas fa-user-edit me-2"></i>Edit User</h2>
            <p class="text-muted mb-0">Update user information and settings</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Profile
            </a>
            <a href="{{ route('users.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-list me-1"></i>All Users
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('users.update', $user) }}" id="editUserForm">
        @csrf
        @method('PUT')
        
        <div class="row">
            <!-- User Information Card -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>User Information</h5>
                        <div class="auth-source-badge">
                            @if($user->auth_source === 'ldap')
                                <span class="badge bg-info">
                                    <i class="fas fa-server me-1"></i>LDAP User
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    <i class="fas fa-user-plus me-1"></i>Manual User
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        @if($user->auth_source === 'ldap')
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>LDAP User:</strong> Some fields are automatically synchronized from LDAP and cannot be edited here.
                            </div>
                        @endif

                        <div class="row">
                            <!-- Username -->
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control readonly-field" id="username" name="username" 
                                           value="{{ $user->username }}" readonly>
                                    <label for="username">Username</label>
                                    <div class="form-text">Username cannot be changed</div>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control {{ $user->auth_source === 'ldap' ? 'readonly-field' : '' }}" 
                                           id="email" name="email" value="{{ $user->email }}" 
                                           {{ $user->auth_source === 'ldap' ? 'readonly' : 'required' }}>
                                    <label for="email">Email Address {{ $user->auth_source !== 'ldap' ? '*' : '' }}</label>
                                    @if($user->auth_source === 'ldap')
                                        <div class="form-text">Synchronized from LDAP</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- First Name -->
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control {{ $user->auth_source === 'ldap' ? 'readonly-field' : '' }}" 
                                           id="first_name" name="first_name" value="{{ $user->first_name }}" 
                                           {{ $user->auth_source === 'ldap' ? 'readonly' : 'required' }}>
                                    <label for="first_name">First Name {{ $user->auth_source !== 'ldap' ? '*' : '' }}</label>
                                    @if($user->auth_source === 'ldap')
                                        <div class="form-text">Synchronized from LDAP</div>
                                    @endif
                                </div>
                            </div>

                            <!-- Last Name -->
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control {{ $user->auth_source === 'ldap' ? 'readonly-field' : '' }}" 
                                           id="last_name" name="last_name" value="{{ $user->last_name }}" 
                                           {{ $user->auth_source === 'ldap' ? 'readonly' : 'required' }}>
                                    <label for="last_name">Last Name {{ $user->auth_source !== 'ldap' ? '*' : '' }}</label>
                                    @if($user->auth_source === 'ldap')
                                        <div class="form-text">Synchronized from LDAP</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Display Name -->
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control {{ $user->auth_source === 'ldap' ? 'readonly-field' : '' }}" 
                                   id="display_name" name="display_name" value="{{ $user->display_name }}" 
                                   {{ $user->auth_source === 'ldap' ? 'readonly' : '' }}>
                            <label for="display_name">Display Name</label>
                            @if($user->auth_source === 'ldap')
                                <div class="form-text">Synchronized from LDAP</div>
                            @else
                                <div class="form-text">Auto-generated from first and last name if empty</div>
                            @endif
                        </div>

                        <div class="row">
                            <!-- Department -->
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    @if($user->auth_source === 'ldap')
                                        <input type="text" class="form-control readonly-field" id="department" 
                                               value="{{ $user->department }}" readonly>
                                        <label for="department">Department</label>
                                        <div class="form-text">Synchronized from LDAP</div>
                                    @else
                                        <select class="form-select" id="department" name="department">
                                            <option value="">Select Department</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept }}" {{ $user->department === $dept ? 'selected' : '' }}>
                                                    {{ $dept }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="department">Department</label>
                                    @endif
                                </div>
                            </div>

                            <!-- Title -->
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control {{ $user->auth_source === 'ldap' ? 'readonly-field' : '' }}" 
                                           id="title" name="title" value="{{ $user->title }}" 
                                           {{ $user->auth_source === 'ldap' ? 'readonly' : '' }}>
                                    <label for="title">Job Title</label>
                                    @if($user->auth_source === 'ldap')
                                        <div class="form-text">Synchronized from LDAP</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="phone" name="phone" value="{{ $user->phone }}">
                            <label for="phone">Phone Number</label>
                            <div class="form-text">Can be updated regardless of user source</div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       {{ $user->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    <strong>Active User</strong>
                                </label>
                                <div class="form-text">Inactive users cannot log in to the system</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Management Card (Manual Users Only) -->
                @if($user->auth_source !== 'ldap')
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-key me-2"></i>Password Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Password Reset:</strong> Use the "Reset Password" button in the user profile to change the password securely.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Last Password Change:</strong> 
                                        {{ $user->password_changed_at ? $user->password_changed_at->diffForHumans() : 'Never' }}
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Must Change Password:</strong> 
                                        {{ $user->must_change_password ? 'Yes' : 'No' }}
                                    </p>
                                </div>
                            </div>

                            <a href="{{ route('users.show', $user) }}" class="btn btn-outline-warning">
                                <i class="fas fa-key me-1"></i>Manage Password
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- User Preview Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-eye me-2"></i>User Preview</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="user-avatar mx-auto mb-3">
                            {{ $user->initials }}
                        </div>
                        <h5 class="mb-1" id="preview-display-name">{{ $user->display_name }}</h5>
                        <p class="text-muted mb-2" id="preview-title">{{ $user->title ?: 'No title' }}</p>
                        <p class="mb-1" id="preview-email">{{ $user->email }}</p>
                        <p class="text-muted" id="preview-department">{{ $user->department ?: 'No department' }}</p>
                        
                        <div class="mt-3">
                            <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}" id="preview-status">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- User Statistics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>User Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="text-primary">{{ $user->roles->count() }}</h4>
                                <small>Roles</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-info">{{ $user->notificationGroups->count() }}</h4>
                                <small>Groups</small>
                            </div>
                            <div class="col-6 mt-3">
                                <h4 class="text-success">{{ $user->createdNotifications->count() }}</h4>
                                <small>Notifications</small>
                            </div>
                            <div class="col-6 mt-3">
                                <h4 class="text-warning">{{ $user->created_at->diffInDays() }}</h4>
                                <small>Days Old</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('users.preferences.show', $user) }}" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-cog me-1"></i>Manage Preferences
                            </a>
                            <a href="{{ route('users.manage-roles', $user) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-user-tag me-1"></i>Manage Roles
                            </a>
                            <a href="{{ route('users.manage-groups', $user) }}" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-users me-1"></i>Manage Groups
                            </a>
                            <a href="{{ route('users.activities', $user) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-history me-1"></i>View Activities
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-save me-1"></i>Update User
                        </button>
                        <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate display name for manual users
    @if($user->auth_source !== 'ldap')
        const firstNameField = document.getElementById('first_name');
        const lastNameField = document.getElementById('last_name');
        const displayNameField = document.getElementById('display_name');
        
        function updateDisplayName() {
            const firstName = firstNameField.value.trim();
            const lastName = lastNameField.value.trim();
            
            if (firstName && lastName && !displayNameField.value.trim()) {
                displayNameField.value = `${firstName} ${lastName}`;
                updatePreview();
            }
        }
        
        firstNameField.addEventListener('input', updateDisplayName);
        lastNameField.addEventListener('input', updateDisplayName);
    @endif
    
    // Live preview updates
    function updatePreview() {
        const displayName = document.getElementById('display_name').value || 'Unknown User';
        const title = document.getElementById('title').value || 'No title';
        const email = document.getElementById('email').value || 'No email';
        const department = document.getElementById('department').value || 'No department';
        const isActive = document.getElementById('is_active').checked;
        
        document.getElementById('preview-display-name').textContent = displayName;
        document.getElementById('preview-title').textContent = title;
        document.getElementById('preview-email').textContent = email;
        document.getElementById('preview-department').textContent = department;
        
        const statusBadge = document.getElementById('preview-status');
        statusBadge.textContent = isActive ? 'Active' : 'Inactive';
        statusBadge.className = `badge bg-${isActive ? 'success' : 'danger'}`;
    }
    
    // Add event listeners for live preview
    const previewFields = ['display_name', 'title', 'email', 'is_active'];
    previewFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updatePreview);
            field.addEventListener('change', updatePreview);
        }
    });
    
    // Department preview update
    const departmentField = document.getElementById('department');
    if (departmentField && departmentField.tagName === 'SELECT') {
        departmentField.addEventListener('change', updatePreview);
    }
    
    // Form submission
    document.getElementById('editUserForm').addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';
        submitBtn.disabled = true;
    });
    
    // Confirm navigation away with unsaved changes
    let formChanged = false;
    const formInputs = document.querySelectorAll('#editUserForm input, #editUserForm select, #editUserForm textarea');
    
    formInputs.forEach(input => {
        input.addEventListener('change', function() {
            formChanged = true;
        });
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
    
    // Reset form changed flag on submit
    document.getElementById('editUserForm').addEventListener('submit', function() {
        formChanged = false;
    });
});
</script>
@endpush