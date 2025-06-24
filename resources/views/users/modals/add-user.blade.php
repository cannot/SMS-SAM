<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addUserModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Add New User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addUserForm" method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="modal-body">
                    <!-- User Type Selection -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-user-cog me-2"></i>User Type
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="auth_source" value="manual" id="authManual" checked>
                                                <label class="form-check-label" for="authManual">
                                                    <i class="fas fa-user me-2 text-primary"></i>
                                                    <strong>Manual User</strong>
                                                    <br><small class="text-muted">Create user with username and password</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="auth_source" value="ldap" id="authLdap">
                                                <label class="form-check-label" for="authLdap">
                                                    <i class="fas fa-server me-2 text-info"></i>
                                                    <strong>LDAP User</strong>
                                                    <br><small class="text-muted">Import user from LDAP directory</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-id-card me-2 text-primary"></i>Basic Information
                            </h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                                <label for="username">
                                    <i class="fas fa-at me-1"></i>Username *
                                </label>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Unique identifier for login
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                                <label for="email">
                                    <i class="fas fa-envelope me-1"></i>Email Address *
                                </label>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Primary email for notifications
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="First Name" required>
                                <label for="first_name">
                                    <i class="fas fa-user me-1"></i>First Name *
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Last Name" required>
                                <label for="last_name">
                                    <i class="fas fa-user me-1"></i>Last Name *
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="display_name" name="display_name" placeholder="Display Name">
                        <label for="display_name">
                            <i class="fas fa-signature me-1"></i>Display Name
                        </label>
                        <div class="form-text">
                            <i class="fas fa-magic me-1"></i>
                            Auto-generated from first and last name if empty
                        </div>
                    </div>

                    <!-- Work Information -->
                    <div class="row mb-3 mt-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-briefcase me-2 text-success"></i>Work Information
                            </h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="add_department" name="department">
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept }}">{{ $dept }}</option>
                                    @endforeach
                                    <option value="other">Other (specify below)</option>
                                </select>
                                <label for="add_department">
                                    <i class="fas fa-building me-1"></i>Department
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="title" name="title" placeholder="Job Title">
                                <label for="title">
                                    <i class="fas fa-id-badge me-1"></i>Job Title
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="custom_department" name="custom_department" placeholder="Department Name" style="display: none;">
                                <label for="custom_department">
                                    <i class="fas fa-building me-1"></i>Custom Department
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone Number">
                                <label for="phone">
                                    <i class="fas fa-phone me-1"></i>Phone Number
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Authentication (Manual Users Only) -->
                    <div id="authSection" class="mb-3 mt-4">
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-key me-2 text-warning"></i>Authentication
                                </h6>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password">
                                    <label for="password">
                                        <i class="fas fa-lock me-1"></i>Temporary Password *
                                    </label>
                                    <div class="form-text">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        User will be required to change on first login
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm Password">
                                    <label for="password_confirmation">
                                        <i class="fas fa-lock me-1"></i>Confirm Password *
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="generatePassword()">
                                    <i class="fas fa-random me-1"></i>Generate Secure Password
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="togglePasswordVisibility()">
                                    <i class="fas fa-eye me-1"></i>Show/Hide Password
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Roles & Permissions -->
                    <div class="row mb-3 mt-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-user-shield me-2 text-purple"></i>Roles & Permissions
                            </h6>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user-tag me-1"></i>Assign Roles
                        </label>
                        <div class="row">
                            @foreach($roles->where('name', '!=', 'super-admin') as $role)
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" id="role_{{ $role->id }}">
                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                            <strong>{{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}</strong>
                                            @if($role->description)
                                                <br><small class="text-muted">{{ $role->description }}</small>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            If no roles selected, user will be assigned the default "user" role
                        </div>
                    </div>

                    <!-- User Settings -->
                    <div class="row mb-3 mt-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-cogs me-2 text-info"></i>User Settings
                            </h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    <i class="fas fa-toggle-on me-1 text-success"></i>
                                    <strong>Active User</strong>
                                    <br><small class="text-muted">User can login and access the system</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="send_welcome_email" name="send_welcome_email" checked>
                                <label class="form-check-label" for="send_welcome_email">
                                    <i class="fas fa-envelope me-1 text-primary"></i>
                                    <strong>Send Welcome Email</strong>
                                    <br><small class="text-muted">Email login credentials to user</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="must_change_password" name="must_change_password" checked>
                                <label class="form-check-label" for="must_change_password">
                                    <i class="fas fa-key me-1 text-warning"></i>
                                    <strong>Force Password Change</strong>
                                    <br><small class="text-muted">Require password change on first login</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_notifications" name="enable_notifications" checked>
                                <label class="form-check-label" for="enable_notifications">
                                    <i class="fas fa-bell me-1 text-info"></i>
                                    <strong>Enable Notifications</strong>
                                    <br><small class="text-muted">User can receive system notifications</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="previewUser()">
                        <i class="fas fa-eye me-1"></i>Preview
                    </button>
                    <button type="submit" class="btn btn-primary" data-original-text="Create User">
                        <i class="fas fa-save me-1"></i>Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add User Modal JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeAddUserModal();
});

function initializeAddUserModal() {
    // Auth source change handler
    const authRadios = document.querySelectorAll('input[name="auth_source"]');
    authRadios.forEach(radio => {
        radio.addEventListener('change', handleAuthSourceChange);
    });

    // Department change handler
    const departmentSelect = document.getElementById('add_department');
    if (departmentSelect) {
        departmentSelect.addEventListener('change', handleDepartmentChange);
    }

    // Form validation
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', handleAddUserSubmit);
        
        // Auto-generate display name
        const firstNameField = document.getElementById('first_name');
        const lastNameField = document.getElementById('last_name');
        
        if (firstNameField && lastNameField) {
            firstNameField.addEventListener('input', generateDisplayName);
            lastNameField.addEventListener('input', generateDisplayName);
        }
    }

    // Real-time validation
    initializeRealTimeValidation();
}

function handleAuthSourceChange() {
    const authSource = document.querySelector('input[name="auth_source"]:checked').value;
    const authSection = document.getElementById('authSection');
    const passwordField = document.getElementById('password');
    const confirmField = document.getElementById('password_confirmation');
    
    if (authSource === 'manual') {
        authSection.style.display = 'block';
        passwordField.required = true;
        confirmField.required = true;
    } else {
        authSection.style.display = 'none';
        passwordField.required = false;
        confirmField.required = false;
        passwordField.value = '';
        confirmField.value = '';
    }
}

function handleDepartmentChange() {
    const departmentSelect = document.getElementById('add_department');
    const customDepartmentField = document.getElementById('custom_department');
    
    if (departmentSelect.value === 'other') {
        customDepartmentField.style.display = 'block';
        customDepartmentField.required = true;
    } else {
        customDepartmentField.style.display = 'none';
        customDepartmentField.required = false;
        customDepartmentField.value = '';
    }
}

function handleAddUserSubmit(e) {
    const authSource = document.querySelector('input[name="auth_source"]:checked').value;
    
    if (authSource === 'manual') {
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
    }
    
    // Check for custom department
    const departmentSelect = document.getElementById('add_department');
    const customDepartment = document.getElementById('custom_department');
    if (departmentSelect.value === 'other' && !customDepartment.value.trim()) {
        e.preventDefault();
        showAlert('warning', 'Please specify the custom department name');
        customDepartment.focus();
        return;
    }
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';
    submitBtn.disabled = true;
}

function generateDisplayName() {
    const firstName = document.getElementById('first_name').value;
    const lastName = document.getElementById('last_name').value;
    const displayNameField = document.getElementById('display_name');
    
    if (firstName && lastName && !displayNameField.value) {
        displayNameField.value = `${firstName} ${lastName}`;
    }
}

function generatePassword() {
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let password = "";
    
    // Ensure at least one character from each type
    const lowercase = "abcdefghijklmnopqrstuvwxyz";
    const uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const numbers = "0123456789";
    const special = "!@#$%^&*";
    
    password += lowercase[Math.floor(Math.random() * lowercase.length)];
    password += uppercase[Math.floor(Math.random() * uppercase.length)];
    password += numbers[Math.floor(Math.random() * numbers.length)];
    password += special[Math.floor(Math.random() * special.length)];
    
    // Fill the rest randomly
    for (let i = 4; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    
    // Shuffle the password
    password = password.split('').sort(() => 0.5 - Math.random()).join('');
    
    const passwordField = document.getElementById('password');
    const confirmField = document.getElementById('password_confirmation');
    
    passwordField.value = password;
    confirmField.value = password;
    
    // Show password briefly
    const originalType = passwordField.type;
    passwordField.type = 'text';
    confirmField.type = 'text';
    
    setTimeout(() => {
        passwordField.type = originalType;
        confirmField.type = originalType;
    }, 3000);
    
    showAlert('success', 'Secure password generated and filled automatically!', 3000);
}

function togglePasswordVisibility() {
    const passwordField = document.getElementById('password');
    const confirmField = document.getElementById('password_confirmation');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        confirmField.type = 'text';
    } else {
        passwordField.type = 'password';
        confirmField.type = 'password';
    }
}

function previewUser() {
    const formData = new FormData(document.getElementById('addUserForm'));
    const preview = {
        username: formData.get('username'),
        email: formData.get('email'),
        display_name: formData.get('display_name') || `${formData.get('first_name')} ${formData.get('last_name')}`,
        department: formData.get('department') === 'other' ? formData.get('custom_department') : formData.get('department'),
        title: formData.get('title'),
        auth_source: formData.get('auth_source'),
        roles: formData.getAll('roles'),
        is_active: formData.get('is_active') ? 'Yes' : 'No'
    };
    
    let previewHtml = `
        <div class="row">
            <div class="col-md-6">
                <strong>Username:</strong> ${preview.username || 'Not specified'}<br>
                <strong>Email:</strong> ${preview.email || 'Not specified'}<br>
                <strong>Display Name:</strong> ${preview.display_name || 'Not specified'}<br>
                <strong>Department:</strong> ${preview.department || 'Not specified'}<br>
            </div>
            <div class="col-md-6">
                <strong>Title:</strong> ${preview.title || 'Not specified'}<br>
                <strong>Auth Source:</strong> ${preview.auth_source.toUpperCase()}<br>
                <strong>Active:</strong> ${preview.is_active}<br>
                <strong>Roles:</strong> ${preview.roles.length ? preview.roles.join(', ') : 'Default (user)'}
            </div>
        </div>
    `;
    
    showAlert('info', `<strong>User Preview:</strong><br>${previewHtml}`, 10000);
}

function initializeRealTimeValidation() {
    // Username validation
    const usernameField = document.getElementById('username');
    if (usernameField) {
        usernameField.addEventListener('blur', function() {
            if (this.value) {
                // Simple validation - you can add AJAX check for uniqueness
                if (this.value.length < 3) {
                    this.classList.add('is-invalid');
                    showFieldError(this, 'Username must be at least 3 characters');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    clearFieldError(this);
                }
            }
        });
    }

    // Email validation
    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.addEventListener('blur', function() {
            if (this.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.value)) {
                    this.classList.add('is-invalid');
                    showFieldError(this, 'Please enter a valid email address');
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    clearFieldError(this);
                }
            }
        });
    }

    // Password strength validation
    const passwordField = document.getElementById('password');
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            updatePasswordStrengthIndicator(strength);
        });
    }
}

function showFieldError(field, message) {
    clearFieldError(field);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function calculatePasswordStrength(password) {
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    return strength;
}

function updatePasswordStrengthIndicator(strength) {
    // You can add a password strength indicator here
    const strengthTexts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    const strengthColors = ['danger', 'warning', 'info', 'primary', 'success'];
    
    // Implementation depends on your UI design
    console.log(`Password strength: ${strengthTexts[strength]} (${strength}/5)`);
}

// Reset form when modal is closed
document.addEventListener('DOMContentLoaded', function() {
    const addUserModal = document.getElementById('addUserModal');
    if (addUserModal) {
        addUserModal.addEventListener('hidden.bs.modal', function() {
            const form = document.getElementById('addUserForm');
            if (form) {
                form.reset();
                // Reset validation states
                form.querySelectorAll('.is-valid, .is-invalid').forEach(field => {
                    field.classList.remove('is-valid', 'is-invalid');
                });
                form.querySelectorAll('.invalid-feedback').forEach(error => {
                    error.remove();
                });
                // Reset submit button
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Create User';
                    submitBtn.disabled = false;
                }
                // Reset auth source
                document.getElementById('authManual').checked = true;
                handleAuthSourceChange();
                // Reset department
                handleDepartmentChange();
            }
        });
    }
});
</script>