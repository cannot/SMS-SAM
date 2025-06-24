<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="resetPasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Reset User Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Resetting password for:</strong> <span id="resetPasswordUserName" class="fw-bold"></span>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> This feature is only available for manually created users. 
                    LDAP users must reset their passwords through your domain administrator.
                </div>

                <form id="resetPasswordForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="New Password" required>
                                <label for="new_password">
                                    <i class="fas fa-lock me-1"></i>New Password *
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" placeholder="Confirm Password" required>
                                <label for="new_password_confirmation">
                                    <i class="fas fa-lock me-1"></i>Confirm Password *
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Password Strength Indicator -->
                    <div class="mb-3">
                        <label class="form-label">Password Strength</label>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small id="passwordStrengthText" class="form-text text-muted">Enter a password to see strength</small>
                    </div>

                    <!-- Password Requirements -->
                    <div class="mb-3">
                        <label class="form-label">Password Requirements</label>
                        <ul class="list-unstyled" id="passwordRequirements">
                            <li id="req-length"><i class="fas fa-times text-danger me-2"></i>At least 8 characters</li>
                            <li id="req-lowercase"><i class="fas fa-times text-danger me-2"></i>One lowercase letter</li>
                            <li id="req-uppercase"><i class="fas fa-times text-danger me-2"></i>One uppercase letter</li>
                            <li id="req-number"><i class="fas fa-times text-danger me-2"></i>One number</li>
                            <li id="req-special"><i class="fas fa-times text-danger me-2"></i>One special character</li>
                        </ul>
                    </div>

                    <!-- Options -->
                    <div class="mb-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="force_change" name="force_change" checked>
                            <label class="form-check-label" for="force_change">
                                <strong>Force password change on next login</strong>
                                <br><small class="text-muted">User must change this password when they login</small>
                            </label>
                        </div>
                        
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="send_notification" name="send_notification">
                            <label class="form-check-label" for="send_notification">
                                <strong>Send password reset notification</strong>
                                <br><small class="text-muted">Email the user about the password reset</small>
                            </label>
                        </div>
                        
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="logout_all_sessions" name="logout_all_sessions" checked>
                            <label class="form-check-label" for="logout_all_sessions">
                                <strong>Logout all existing sessions</strong>
                                <br><small class="text-muted">Force user to login again on all devices</small>
                            </label>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mb-3">
                        <label class="form-label">Quick Actions</label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="generateSecurePassword()">
                                <i class="fas fa-random me-1"></i>Generate Secure
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="generateSimplePassword()">
                                <i class="fas fa-key me-1"></i>Generate Simple
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="togglePasswordVisibility('reset')">
                                <i class="fas fa-eye me-1"></i>Show/Hide
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-outline-info" onclick="previewPasswordReset()">
                    <i class="fas fa-eye me-1"></i>Preview
                </button>
                <button type="button" class="btn btn-warning" onclick="resetUserPassword()" id="resetPasswordBtn">
                    <i class="fas fa-key me-1"></i>Reset Password
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Reset Password Modal JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializePasswordReset();
});

function initializePasswordReset() {
    const passwordField = document.getElementById('new_password');
    const confirmField = document.getElementById('new_password_confirmation');
    
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            updatePasswordStrength(this.value);
            checkPasswordRequirements(this.value);
            validatePasswordMatch();
        });
    }
    
    if (confirmField) {
        confirmField.addEventListener('input', validatePasswordMatch);
    }
}

function updatePasswordStrength(password) {
    const strengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('passwordStrengthText');
    
    if (!password) {
        strengthBar.style.width = '0%';
        strengthBar.className = 'progress-bar';
        strengthText.textContent = 'Enter a password to see strength';
        return;
    }
    
    let strength = 0;
    let feedback = [];
    
    // Length check
    if (password.length >= 8) strength += 20;
    else feedback.push('Use at least 8 characters');
    
    // Character variety checks
    if (/[a-z]/.test(password)) strength += 15;
    else feedback.push('Add lowercase letters');
    
    if (/[A-Z]/.test(password)) strength += 15;
    else feedback.push('Add uppercase letters');
    
    if (/[0-9]/.test(password)) strength += 15;
    else feedback.push('Add numbers');
    
    if (/[^A-Za-z0-9]/.test(password)) strength += 15;
    else feedback.push('Add special characters');
    
    // Bonus points for length and complexity
    if (password.length >= 12) strength += 10;
    if (password.length >= 16) strength += 10;
    
    // Update progress bar
    strengthBar.style.width = Math.min(strength, 100) + '%';
    
    let strengthClass = 'bg-danger';
    let strengthLabel = 'Very Weak';
    
    if (strength >= 80) {
        strengthClass = 'bg-success';
        strengthLabel = 'Strong';
    } else if (strength >= 60) {
        strengthClass = 'bg-info';
        strengthLabel = 'Good';
    } else if (strength >= 40) {
        strengthClass = 'bg-warning';
        strengthLabel = 'Fair';
    } else if (strength >= 20) {
        strengthClass = 'bg-warning';
        strengthLabel = 'Weak';
    }
    
    strengthBar.className = `progress-bar ${strengthClass}`;
    strengthText.textContent = `${strengthLabel} (${Math.min(strength, 100)}%)`;
    
    if (feedback.length > 0) {
        strengthText.textContent += ` - ${feedback.join(', ')}`;
    }
}

function checkPasswordRequirements(password) {
    const requirements = {
        'req-length': password.length >= 8,
        'req-lowercase': /[a-z]/.test(password),
        'req-uppercase': /[A-Z]/.test(password),
        'req-number': /[0-9]/.test(password),
        'req-special': /[^A-Za-z0-9]/.test(password)
    };
    
    Object.entries(requirements).forEach(([id, met]) => {
        const element = document.getElementById(id);
        const icon = element.querySelector('i');
        
        if (met) {
            icon.className = 'fas fa-check text-success me-2';
            element.classList.add('text-success');
            element.classList.remove('text-danger');
        } else {
            icon.className = 'fas fa-times text-danger me-2';
            element.classList.add('text-danger');
            element.classList.remove('text-success');
        }
    });
}

function validatePasswordMatch() {
    const password = document.getElementById('new_password').value;
    const confirm = document.getElementById('new_password_confirmation').value;
    const confirmField = document.getElementById('new_password_confirmation');
    
    if (confirm && password !== confirm) {
        confirmField.classList.add('is-invalid');
        confirmField.classList.remove('is-valid');
        
        // Add or update error message
        let errorDiv = confirmField.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            confirmField.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = 'Passwords do not match';
    } else if (confirm) {
        confirmField.classList.remove('is-invalid');
        confirmField.classList.add('is-valid');
        
        // Remove error message
        const errorDiv = confirmField.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
}

function generateSecurePassword() {
    const length = 16;
    const lowercase = "abcdefghijklmnopqrstuvwxyz";
    const uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const numbers = "0123456789";
    const special = "!@#$%^&*()_+-=[]{}|;:,.<>?";
    
    let password = "";
    
    // Ensure at least one character from each type
    password += lowercase[Math.floor(Math.random() * lowercase.length)];
    password += uppercase[Math.floor(Math.random() * uppercase.length)];
    password += numbers[Math.floor(Math.random() * numbers.length)];
    password += special[Math.floor(Math.random() * special.length)];
    
    // Fill the rest randomly
    const allChars = lowercase + uppercase + numbers + special;
    for (let i = 4; i < length; i++) {
        password += allChars[Math.floor(Math.random() * allChars.length)];
    }
    
    // Shuffle the password
    password = password.split('').sort(() => 0.5 - Math.random()).join('');
    
    setGeneratedPassword(password);
}

function generateSimplePassword() {
    const words = ['Apple', 'Banana', 'Cherry', 'Dragon', 'Eagle', 'Forest', 'Garden', 'Harbor', 'Island', 'Jungle'];
    const word1 = words[Math.floor(Math.random() * words.length)];
    const word2 = words[Math.floor(Math.random() * words.length)];
    const number = Math.floor(Math.random() * 100);
    const special = ['!', '@', '#', '$', '%'][Math.floor(Math.random() * 5)];
    
    const password = `${word1}${word2}${number}${special}`;
    setGeneratedPassword(password);
}

function setGeneratedPassword(password) {
    const passwordField = document.getElementById('new_password');
    const confirmField = document.getElementById('new_password_confirmation');
    
    passwordField.value = password;
    confirmField.value = password;
    
    // Update strength and requirements
    updatePasswordStrength(password);
    checkPasswordRequirements(password);
    validatePasswordMatch();
    
    // Show password briefly
    const originalType = passwordField.type;
    passwordField.type = 'text';
    confirmField.type = 'text';
    
    setTimeout(() => {
        passwordField.type = originalType;
        confirmField.type = originalType;
    }, 3000);
    
    showAlert('success', 'Password generated successfully!', 3000);
}

function togglePasswordVisibility(context) {
    const passwordField = document.getElementById('new_password');
    const confirmField = document.getElementById('new_password_confirmation');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        confirmField.type = 'text';
    } else {
        passwordField.type = 'password';
        confirmField.type = 'password';
    }
}

function previewPasswordReset() {
    const options = {
        force_change: document.getElementById('force_change').checked,
        send_notification: document.getElementById('send_notification').checked,
        logout_all_sessions: document.getElementById('logout_all_sessions').checked
    };
    
    let previewHtml = `
        <strong>Password Reset Preview:</strong><br>
        <ul class="mb-0 mt-2">
            <li>User: ${currentUserName}</li>
            <li>Force password change: ${options.force_change ? 'Yes' : 'No'}</li>
            <li>Send notification email: ${options.send_notification ? 'Yes' : 'No'}</li>
            <li>Logout all sessions: ${options.logout_all_sessions ? 'Yes' : 'No'}</li>
        </ul>
    `;
    
    showAlert('info', previewHtml, 8000);
}

function resetUserPassword() {
    if (!currentUserId) {
        showAlert('error', 'No user selected');
        return;
    }
    
    const password = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('new_password_confirmation').value;
    
    // Validate passwords
    if (!password || password.length < 8) {
        showAlert('warning', 'Password must be at least 8 characters long');
        return;
    }
    
    if (password !== confirmPassword) {
        showAlert('warning', 'Passwords do not match');
        return;
    }
    
    // Check password strength
    const hasLower = /[a-z]/.test(password);
    const hasUpper = /[A-Z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecial = /[^A-Za-z0-9]/.test(password);
    
    if (!(hasLower && hasUpper && hasNumber && hasSpecial)) {
        if (!confirm('Password does not meet all complexity requirements. Continue anyway?')) {
            return;
        }
    }
    
    const resetBtn = document.getElementById('resetPasswordBtn');
    const originalText = resetBtn.innerHTML;
    
    // Show loading state
    resetBtn.disabled = true;
    resetBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Resetting...';
    
    // Prepare form data
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    formData.append('new_password', password);
    formData.append('new_password_confirmation', confirmPassword);
    formData.append('force_change', document.getElementById('force_change').checked);
    formData.append('send_notification', document.getElementById('send_notification').checked);
    formData.append('logout_all_sessions', document.getElementById('logout_all_sessions').checked);
    
    // Submit reset request
    fetch(`/users/${currentUserId}/reset-password`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        } else {
            throw new Error('Password reset request failed');
        }
    })
    .then(data => {
        if (data.success) {
            showAlert('success', 'Password reset successfully');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal'));
            modal.hide();
            
            // Show additional info if user was logged out
            if (document.getElementById('logout_all_sessions').checked) {
                showAlert('info', 'User has been logged out of all sessions', 5000);
            }
        } else {
            throw new Error(data.message || 'Password reset failed');
        }
    })
    .catch(error => {
        console.error('Error resetting password:', error);
        showAlert('danger', 'Failed to reset password: ' + error.message);
        
        // Reset button
        resetBtn.disabled = false;
        resetBtn.innerHTML = originalText;
    });
}

// Reset modal when closed
document.addEventListener('DOMContentLoaded', function() {
    const resetPasswordModal = document.getElementById('resetPasswordModal');
    if (resetPasswordModal) {
        resetPasswordModal.addEventListener('hidden.bs.modal', function() {
            // Reset form
            const form = document.getElementById('resetPasswordForm');
            if (form) {
                form.reset();
                
                // Reset validation states
                form.querySelectorAll('.is-valid, .is-invalid').forEach(field => {
                    field.classList.remove('is-valid', 'is-invalid');
                });
                form.querySelectorAll('.invalid-feedback').forEach(error => {
                    error.remove();
                });
            }
            
            // Reset password strength indicator
            const strengthBar = document.getElementById('passwordStrengthBar');
            const strengthText = document.getElementById('passwordStrengthText');
            if (strengthBar) {
                strengthBar.style.width = '0%';
                strengthBar.className = 'progress-bar';
            }
            if (strengthText) {
                strengthText.textContent = 'Enter a password to see strength';
            }
            
            // Reset password requirements
            document.querySelectorAll('#passwordRequirements li').forEach(req => {
                const icon = req.querySelector('i');
                icon.className = 'fas fa-times text-danger me-2';
                req.classList.remove('text-success');
                req.classList.add('text-danger');
            });
            
            // Reset button
            const resetBtn = document.getElementById('resetPasswordBtn');
            resetBtn.disabled = false;
            resetBtn.innerHTML = '<i class="fas fa-key me-1"></i>Reset Password';
            
            // Clear user info
            document.getElementById('resetPasswordUserName').textContent = '';
        });
    }
});
</script>