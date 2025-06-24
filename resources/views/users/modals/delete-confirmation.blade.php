<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="fas fa-user-times fa-4x text-danger mb-3"></i>
                    <h6>Are you sure you want to delete this user?</h6>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>User to be deleted:</strong> <span id="deleteUserName" class="fw-bold"></span>
                </div>
                
                <div class="alert alert-info">
                    <h6 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>What happens when you delete a user:
                    </h6>
                    <ul class="mb-0">
                        <li>The user account will be <strong>soft deleted</strong> (can be restored later)</li>
                        <li>User will no longer be able to login to the system</li>
                        <li>User will be removed from all notification groups</li>
                        <li>User's notification history will be preserved</li>
                        <li>Created notifications and templates will remain intact</li>
                    </ul>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="confirmDeleteUnderstand">
                    <label class="form-check-label" for="confirmDeleteUnderstand">
                        I understand that this action will deactivate the user account
                    </label>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="removeFromGroups" checked>
                    <label class="form-check-label" for="removeFromGroups">
                        Remove user from all notification groups
                    </label>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="sendDeleteNotification">
                    <label class="form-check-label" for="sendDeleteNotification">
                        Send notification email to user about account deactivation
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="showDeletePreview()">
                    <i class="fas fa-eye me-1"></i>Preview Impact
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDeleteUser()" disabled>
                    <i class="fas fa-trash me-1"></i>Delete User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Preview Modal -->
<div class="modal fade" id="deletePreviewModal" tabindex="-1" aria-labelledby="deletePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="deletePreviewModalLabel">
                    <i class="fas fa-search me-2"></i>Delete Impact Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="deletePreviewContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Analyzing user impact...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Delete Confirmation Modal JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeDeleteModal();
});

function initializeDeleteModal() {
    // Enable/disable delete button based on confirmation checkbox
    const confirmCheckbox = document.getElementById('confirmDeleteUnderstand');
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    
    if (confirmCheckbox && deleteBtn) {
        confirmCheckbox.addEventListener('change', function() {
            deleteBtn.disabled = !this.checked;
            
            if (this.checked) {
                deleteBtn.classList.remove('btn-outline-danger');
                deleteBtn.classList.add('btn-danger');
            } else {
                deleteBtn.classList.remove('btn-danger');
                deleteBtn.classList.add('btn-outline-danger');
            }
        });
    }
}

function confirmDeleteUser() {
    if (!currentUserId) {
        showAlert('error', 'No user selected for deletion');
        return;
    }
    
    const confirmCheckbox = document.getElementById('confirmDeleteUnderstand');
    if (!confirmCheckbox.checked) {
        showAlert('warning', 'Please confirm that you understand the consequences');
        return;
    }
    
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    const originalText = deleteBtn.innerHTML;
    
    // Show loading state
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Deleting...';
    
    // Prepare form data
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    formData.append('_method', 'DELETE');
    formData.append('remove_from_groups', document.getElementById('removeFromGroups').checked);
    formData.append('send_notification', document.getElementById('sendDeleteNotification').checked);
    
    // Submit delete request
    fetch(`/users/${currentUserId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            // Success - close modal and reload page
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteUserModal'));
            modal.hide();
            
            showAlert('success', `User ${currentUserName} has been deleted successfully`);
            
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            throw new Error('Delete request failed');
        }
    })
    .catch(error => {
        console.error('Error deleting user:', error);
        showAlert('danger', 'Failed to delete user. Please try again.');
        
        // Reset button
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = originalText;
    });
}

function showDeletePreview() {
    if (!currentUserId) {
        showAlert('error', 'No user selected');
        return;
    }
    
    // Show preview modal
    const previewModal = new bootstrap.Modal(document.getElementById('deletePreviewModal'));
    previewModal.show();
    
    // Load user impact data
    loadDeleteImpact(currentUserId);
}

function loadDeleteImpact(userId) {
    const contentDiv = document.getElementById('deletePreviewContent');
    
    fetch(`/users/${userId}/delete-impact`)
        .then(response => response.json())
        .then(data => {
            displayDeleteImpact(data);
        })
        .catch(error => {
            console.error('Error loading delete impact:', error);
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Failed to load impact analysis. The user can still be deleted, but impact information is not available.
                </div>
            `;
        });
}

function displayDeleteImpact(data) {
    const contentDiv = document.getElementById('deletePreviewContent');
    
    const impactHtml = `
        <div class="row">
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-user me-2"></i>User Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Name:</dt>
                            <dd class="col-sm-7">${data.user.display_name}</dd>
                            <dt class="col-sm-5">Email:</dt>
                            <dd class="col-sm-7">${data.user.email}</dd>
                            <dt class="col-sm-5">Department:</dt>
                            <dd class="col-sm-7">${data.user.department || 'N/A'}</dd>
                            <dt class="col-sm-5">Status:</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-${data.user.is_active ? 'success' : 'secondary'}">
                                    ${data.user.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </dd>
                            <dt class="col-sm-5">Last Login:</dt>
                            <dd class="col-sm-7">${data.user.last_login_at || 'Never'}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Impact Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-7">Notification Groups:</dt>
                            <dd class="col-sm-5">
                                <span class="badge bg-primary">${data.impact.groups_count}</span>
                            </dd>
                            <dt class="col-sm-7">Created Notifications:</dt>
                            <dd class="col-sm-5">
                                <span class="badge bg-info">${data.impact.created_notifications}</span>
                            </dd>
                            <dt class="col-sm-7">Created Templates:</dt>
                            <dd class="col-sm-5">
                                <span class="badge bg-success">${data.impact.created_templates}</span>
                            </dd>
                            <dt class="col-sm-7">API Keys:</dt>
                            <dd class="col-sm-5">
                                <span class="badge bg-warning">${data.impact.api_keys}</span>
                            </dd>
                            <dt class="col-sm-7">Received Notifications:</dt>
                            <dd class="col-sm-5">
                                <span class="badge bg-secondary">${data.impact.received_notifications}</span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        ${data.impact.groups_count > 0 ? `
        <div class="mt-4">
            <h6>
                <i class="fas fa-users me-2"></i>Notification Groups (${data.impact.groups_count})
            </h6>
            <div class="row">
                ${data.impact.groups.map(group => `
                    <div class="col-md-6 mb-2">
                        <div class="card card-body border-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${group.name}</strong>
                                    <br><small class="text-muted">${group.description || 'No description'}</small>
                                </div>
                                <span class="badge bg-light text-dark">
                                    ${group.members_count} members
                                </span>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
        ` : ''}
        
        ${data.impact.created_notifications > 0 ? `
        <div class="mt-4">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> This user has created ${data.impact.created_notifications} notification(s) and ${data.impact.created_templates} template(s). 
                These will remain in the system but will be marked as created by a deleted user.
            </div>
        </div>
        ` : ''}
        
        ${data.impact.api_keys > 0 ? `
        <div class="mt-4">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Warning:</strong> This user has ${data.impact.api_keys} active API key(s). 
                These will be deactivated when the user is deleted.
            </div>
        </div>
        ` : ''}
    `;
    
    contentDiv.innerHTML = impactHtml;
}

// Reset modal when closed
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteUserModal');
    if (deleteModal) {
        deleteModal.addEventListener('hidden.bs.modal', function() {
            // Reset form
            document.getElementById('confirmDeleteUnderstand').checked = false;
            document.getElementById('removeFromGroups').checked = true;
            document.getElementById('sendDeleteNotification').checked = false;
            
            // Reset button
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            deleteBtn.disabled = true;
            deleteBtn.classList.remove('btn-danger');
            deleteBtn.classList.add('btn-outline-danger');
            deleteBtn.innerHTML = '<i class="fas fa-trash me-1"></i>Delete User';
            
            // Clear user info
            document.getElementById('deleteUserName').textContent = '';
        });
    }
});

// Fallback delete impact data for when API is not available
function getDefaultDeleteImpact() {
    return {
        user: {
            display_name: currentUserName || 'Unknown User',
            email: 'Not available',
            department: 'Not available',
            is_active: true,
            last_login_at: 'Not available'
        },
        impact: {
            groups_count: 0,
            created_notifications: 0,
            created_templates: 0,
            api_keys: 0,
            received_notifications: 0,
            groups: []
        }
    };
}
</script>