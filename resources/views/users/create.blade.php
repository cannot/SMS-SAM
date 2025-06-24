@extends('layouts.app')

@section('title', 'Create New User')

@push('styles')
<style>
.form-floating .form-control:focus ~ label,
.form-floating .form-control:not(:placeholder-shown) ~ label {
    opacity: .65;
    transform: scale(.85) translateY(-0.5rem) translateX(0.15rem);
}

.password-strength {
    height: 5px;
    border-radius: 3px;
    transition: all 0.3s ease;
}

.password-strength.weak { background-color: #dc3545; }
.password-strength.medium { background-color: #ffc107; }
.password-strength.strong { background-color: #28a745; }

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.step-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
}

.step {
    flex: 1;
    text-align: center;
    position: relative;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 15px;
    right: -50%;
    width: 100%;
    height: 2px;
    background-color: #dee2e6;
    z-index: 1;
}

.step.active:not(:last-child)::after {
    background-color: #007bff;
}

.step-circle {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #dee2e6;
    color: #6c757d;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    position: relative;
    z-index: 2;
}

.step.active .step-circle {
    background-color: #007bff;
    color: white;
}

.step.completed .step-circle {
    background-color: #28a745;
    color: white;
}

.user-avatar-preview {
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
                    <li class="breadcrumb-item active">Create User</li>
                </ol>
            </nav>
            <h2><i class="fas fa-user-plus me-2"></i>Create New User</h2>
            <p class="text-muted mb-0">Add a new user to the system</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Users
            </a>
        </div>
    </div>

    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step active" id="step1">
            <div class="step-circle">1</div>
            <div class="step-label">Basic Info</div>
        </div>
        <div class="step" id="step2">
            <div class="step-circle">2</div>
            <div class="step-label">Account Setup</div>
        </div>
        <div class="step" id="step3">
            <div class="step-circle">3</div>
            <div class="step-label">Roles & Settings</div>
        </div>
    </div>

    <form method="POST" action="{{ route('users.store') }}" id="createUserForm">
        @csrf
        
        <!-- Step 1: Basic Information -->
        <div class="step-content" id="step1-content">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               placeholder="First Name" required value="{{ old('first_name') }}">
                                        <label for="first_name">First Name *</label>
                                        @error('first_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               placeholder="Last Name" required value="{{ old('last_name') }}">
                                        <label for="last_name">Last Name *</label>
                                        @error('last_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="display_name" name="display_name" 
                                       placeholder="Display Name" value="{{ old('display_name') }}">
                                <label for="display_name">Display Name</label>
                                <div class="form-text">Auto-generated from first and last name if empty</div>
                                @error('display_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Email" required value="{{ old('email') }}">
                                <label for="email">Email Address *</label>
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <select class="form-select" id="department" name="department">
                                            <option value="">Select Department</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept }}" {{ old('department') === $dept ? 'selected' : '' }}>
                                                    {{ $dept }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="department">Department</label>
                                        @error('department')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="title" name="title" 
                                               placeholder="Job Title" value="{{ old('title') }}">
                                        <label for="title">Job Title</label>
                                        @error('title')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       placeholder="Phone Number" value="{{ old('phone') }}">
                                <label for="phone">Phone Number</label>
                                @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>User Preview</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="user-avatar-preview mx-auto mb-3" id="preview-avatar">
                                ?
                            </div>
                            <h5 class="mb-1" id="preview-name">New User</h5>
                            <p class="text-muted mb-2" id="preview-title">No title</p>
                            <p class="mb-1" id="preview-email">email@example.com</p>
                            <p class="text-muted" id="preview-department">No department</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Account Setup -->
        <div class="step-content" id="step2-content" style="display: none;">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-key me-2"></i>Account Setup</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Username" required value="{{ old('username') }}">
                                <label for="username">Username *</label>
                                <div class="form-text">Must be unique. Only letters, numbers, dots, and underscores allowed.</div>
                                @error('username')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Password" required>
                                        <label for="password">Temporary Password *</label>
                                        <div class="password-strength mt-2" id="password-strength"></div>
                                        <div class="form-text">
                                            Minimum 8 characters. User will be required to change on first login.
                                        </div>
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="password_confirmation" 
                                               name="password_confirmation" placeholder="Confirm Password" required>
                                        <label for="password_confirmation">Confirm Password *</label>
                                        <div id="password-match" class="form-text"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Generate secure password:</span>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="generatePassword()">
                                    <i class="fas fa-dice me-1"></i>Generate Password
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="must_change_password" 
                                           name="must_change_password" checked>
                                    <label class="form-check-label" for="must_change_password">
                                        <strong>Force password change on first login</strong>
                                    </label>
                                    <div class="form-text">Recommended for security</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" 
                                           name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        <strong>Active User</strong>
                                    </label>
                                    <div class="form-text">User can log in to the system</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="send_welcome_email" 
                                           name="send_welcome_email" checked>
                                    <label class="form-check-label" for="send_welcome_email">
                                        <strong>Send welcome email</strong>
                                    </label>
                                    <div class="form-text">Email with login credentials</div>
                                </div>
                            </div>

                            <input type="hidden" name="auth_source" value="manual">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Roles & Settings -->
        <div class="step-content" id="step3-content" style="display: none;">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user-tag me-2"></i>Role Assignment</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Select the roles to assign to this user. Roles determine what the user can access and do in the system.</p>
                            
                            <div class="row">
                                @forelse($roles->where('name', '!=', 'super-admin') as $role)
                                    <div class="col-md-6 mb-3">
                                        <div class="card border">
                                            <div class="card-body p-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="roles[]" 
                                                           value="{{ $role->name }}" id="role_{{ $loop->index }}"
                                                           {{ $role->name === 'user' ? 'checked' : '' }}>
                                                    <label class="form-check-label w-100" for="role_{{ $loop->index }}">
                                                        <strong>{{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}</strong>
                                                        @if($role->description)
                                                            <br><small class="text-muted">{{ $role->description }}</small>
                                                        @endif
                                                    </label>
                                                </div>
                                                @if($role->permissions->count() > 0)
                                                    <small class="text-info">
                                                        <i class="fas fa-key me-1"></i>
                                                        {{ $role->permissions->count() }} permissions
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            No roles available. Please create roles first.
                                        </div>
                                    </div>
                                @endforelse
                            </div>

                            @if($roles->where('name', '!=', 'super-admin')->count() > 0)
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Default Role:</strong> If no roles are selected, the user will be assigned the "user" role automatically.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Summary</h5>
                        </div>
                        <div class="card-body">
                            <h6>User Information:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Name:</strong> <span id="summary-name">-</span></li>
                                <li><strong>Email:</strong> <span id="summary-email">-</span></li>
                                <li><strong>Username:</strong> <span id="summary-username">-</span></li>
                                <li><strong>Department:</strong> <span id="summary-department">-</span></li>
                                <li><strong>Title:</strong> <span id="summary-title">-</span></li>
                            </ul>

                            <h6 class="mt-3">Settings:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-circle text-success me-2"></i><span id="summary-active">Active user</span></li>
                                <li><i class="fas fa-circle text-warning me-2"></i><span id="summary-password">Must change password</span></li>
                                <li><i class="fas fa-circle text-info me-2"></i><span id="summary-email-setting">Send welcome email</span></li>
                            </ul>

                            <h6 class="mt-3">Selected Roles:</h6>
                            <div id="summary-roles">
                                <span class="badge bg-secondary">user</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <button type="button" class="btn btn-outline-secondary me-2" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                            <i class="fas fa-arrow-left me-1"></i>Previous
                        </button>
                        <button type="button" class="btn btn-primary me-2" id="nextBtn" onclick="changeStep(1)">
                            Next <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                        <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                            <i class="fas fa-user-plus me-1"></i>Create User
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
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
let currentStep = 1;
const totalSteps = 3;

document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    setupPasswordValidation();
    setupLivePreview();
    updateSummary();
});

function initializeForm() {
    // Auto-generate username from email
    document.getElementById('email').addEventListener('input', function() {
        const email = this.value;
        const username = email.split('@')[0].replace(/[^a-zA-Z0-9._]/g, '');
        document.getElementById('username').value = username;
        updateSummary();
    });

    // Auto-generate display name
    document.getElementById('first_name').addEventListener('input', generateDisplayName);
    document.getElementById('last_name').addEventListener('input', generateDisplayName);
}

function generateDisplayName() {
    const firstName = document.getElementById('first_name').value;
    const lastName = document.getElementById('last_name').value;
    const displayName = document.getElementById('display_name');
    
    if (firstName && lastName && !displayName.value) {
        displayName.value = `${firstName} ${lastName}`;
    }
    updatePreview();
    updateSummary();
}

function setupPasswordValidation() {
    const passwordField = document.getElementById('password');
    const confirmField = document.getElementById('password_confirmation');
    const strengthBar = document.getElementById('password-strength');
    const matchText = document.getElementById('password-match');

    passwordField.addEventListener('input', function() {
        const password = this.value;
        const strength = getPasswordStrength(password);
        
        strengthBar.className = `password-strength ${strength.class}`;
        strengthBar.style.width = strength.width;
    });

    confirmField.addEventListener('input', function() {
        const password = passwordField.value;
        const confirm = this.value;
        
        if (confirm) {
            if (password === confirm) {
                matchText.innerHTML = '<i class="fas fa-check text-success me-1"></i>Passwords match';
                matchText.className = 'form-text text-success';
            } else {
                matchText.innerHTML = '<i class="fas fa-times text-danger me-1"></i>Passwords do not match';
                matchText.className = 'form-text text-danger';
            }
        } else {
            matchText.innerHTML = '';
        }
    });
}

function getPasswordStrength(password) {
    let score = 0;
    if (password.length >= 8) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    if (score < 3) return { class: 'weak', width: '33%' };
    if (score < 5) return { class: 'medium', width: '66%' };
    return { class: 'strong', width: '100%' };
}

function generatePassword() {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    
    document.getElementById('password').value = password;
    document.getElementById('password_confirmation').value = password;
    
    // Trigger validation
    document.getElementById('password').dispatchEvent(new Event('input'));
    document.getElementById('password_confirmation').dispatchEvent(new Event('input'));
    
    // Show password briefly
    const passwordField = document.getElementById('password');
    const confirmField = document.getElementById('password_confirmation');
    
    passwordField.type = 'text';
    confirmField.type = 'text';
    
    setTimeout(() => {
        passwordField.type = 'password';
        confirmField.type = 'password';
    }, 2000);
}

function setupLivePreview() {
    const fields = ['first_name', 'last_name', 'display_name', 'email', 'department', 'title'];
    
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updatePreview);
            field.addEventListener('change', updatePreview);
        }
    });
}

function updatePreview() {
    const firstName = document.getElementById('first_name').value;
    const lastName = document.getElementById('last_name').value;
    const displayName = document.getElementById('display_name').value;
    const email = document.getElementById('email').value;
    const department = document.getElementById('department').value;
    const title = document.getElementById('title').value;

    // Update avatar initials
    const initials = getInitials(firstName, lastName, displayName);
    document.getElementById('preview-avatar').textContent = initials;

    // Update preview text
    document.getElementById('preview-name').textContent = displayName || `${firstName} ${lastName}`.trim() || 'New User';
    document.getElementById('preview-email').textContent = email || 'email@example.com';
    document.getElementById('preview-department').textContent = department || 'No department';
    document.getElementById('preview-title').textContent = title || 'No title';
}

function getInitials(firstName, lastName, displayName) {
    if (firstName && lastName) {
        return (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
    }
    if (displayName) {
        const words = displayName.split(' ');
        if (words.length >= 2) {
            return (words[0].charAt(0) + words[1].charAt(0)).toUpperCase();
        }
        return displayName.charAt(0).toUpperCase();
    }
    return '?';
}

function changeStep(direction) {
    if (direction === 1 && !validateCurrentStep()) {
        return;
    }

    // Hide current step
    document.getElementById(`step${currentStep}-content`).style.display = 'none';
    document.getElementById(`step${currentStep}`).classList.remove('active');
    
    if (direction === -1) {
        document.getElementById(`step${currentStep}`).classList.remove('completed');
    } else {
        document.getElementById(`step${currentStep}`).classList.add('completed');
    }

    // Update current step
    currentStep += direction;

    // Show new step
    document.getElementById(`step${currentStep}-content`).style.display = 'block';
    document.getElementById(`step${currentStep}`).classList.add('active');

    // Update buttons
    updateButtons();
    
    // Update summary if on last step
    if (currentStep === totalSteps) {
        updateSummary();
    }
}

function validateCurrentStep() {
    const stepContent = document.getElementById(`step${currentStep}-content`);
    const requiredFields = stepContent.querySelectorAll('input[required], select[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    // Special validation for step 2 (password)
    if (currentStep === 2) {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('password_confirmation').value;
        
        if (password !== confirm) {
            document.getElementById('password_confirmation').classList.add('is-invalid');
            isValid = false;
        }
        
        if (password.length < 8) {
            document.getElementById('password').classList.add('is-invalid');
            isValid = false;
        }
    }

    return isValid;
}

function updateButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    prevBtn.style.display = currentStep > 1 ? 'inline-block' : 'none';
    nextBtn.style.display = currentStep < totalSteps ? 'inline-block' : 'none';
    submitBtn.style.display = currentStep === totalSteps ? 'inline-block' : 'none';
}

function updateSummary() {
    // Update summary information
    const fields = {
        'summary-name': document.getElementById('display_name').value || 
                       `${document.getElementById('first_name').value} ${document.getElementById('last_name').value}`.trim() || '-',
        'summary-email': document.getElementById('email').value || '-',
        'summary-username': document.getElementById('username').value || '-',
        'summary-department': document.getElementById('department').value || 'No department',
        'summary-title': document.getElementById('title').value || 'No title'
    };

    Object.keys(fields).forEach(id => {
        const element = document.getElementById(id);
        if (element) element.textContent = fields[id];
    });

    // Update settings
    const isActive = document.getElementById('is_active') && document.getElementById('is_active').checked;
    const mustChange = document.getElementById('must_change_password') && document.getElementById('must_change_password').checked;
    const sendEmail = document.getElementById('send_welcome_email') && document.getElementById('send_welcome_email').checked;

    const summaryActive = document.getElementById('summary-active');
    const summaryPassword = document.getElementById('summary-password');
    const summaryEmailSetting = document.getElementById('summary-email-setting');

    if (summaryActive) summaryActive.textContent = isActive ? 'Active user' : 'Inactive user';
    if (summaryPassword) summaryPassword.textContent = mustChange ? 'Must change password' : 'Can keep password';
    if (summaryEmailSetting) summaryEmailSetting.textContent = sendEmail ? 'Send welcome email' : 'No welcome email';

    // Update roles
    const selectedRoles = Array.from(document.querySelectorAll('input[name="roles[]"]:checked'))
                               .map(input => input.nextElementSibling.querySelector('strong').textContent);
    
    const rolesContainer = document.getElementById('summary-roles');
    if (rolesContainer) {
        if (selectedRoles.length > 0) {
            rolesContainer.innerHTML = selectedRoles.map(role => 
                `<span class="badge bg-primary me-1">${role}</span>`
            ).join('');
        } else {
            rolesContainer.innerHTML = '<span class="badge bg-secondary">user (default)</span>';
        }
    }
}

// Form submission
document.getElementById('createUserForm').addEventListener('submit', function(e) {
    if (!validateCurrentStep()) {
        e.preventDefault();
        return;
    }

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating User...';
    submitBtn.disabled = true;
});

// Role selection updates
document.addEventListener('change', function(e) {
    if (e.target.name === 'roles[]') {
        updateSummary();
    }
});
</script>
@endpush