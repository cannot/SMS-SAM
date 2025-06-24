@extends('layouts.app')

@section('title', 'Deleted Users')

@push('styles')
<style>
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 16px;
}

.deleted-user-card {
    background: rgba(108, 117, 125, 0.05);
    border-left: 4px solid #6c757d;
}

.action-buttons .btn {
    margin-right: 0.25rem;
    margin-bottom: 0.25rem;
}

.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.confirmation-modal .modal-body {
    padding: 2rem;
}

.warning-icon {
    font-size: 3rem;
    color: #dc3545;
    margin-bottom: 1rem;
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
                    <li class="breadcrumb-item active">Deleted Users</li>
                </ol>
            </nav>
            <h2><i class="fas fa-trash-restore me-2"></i>Deleted Users</h2>
            <p class="text-muted mb-0">Manage soft-deleted user accounts</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('users.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i>Back to Users
            </a>
            <button type="button" class="btn btn-outline-danger" onclick="showBulkDeleteModal()" id="bulkDeleteBtn" style="display: none;">
                <i class="fas fa-times me-1"></i>Permanent Delete
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">{{ $users->total() }}</h3>
                            <p class="mb-0">Deleted Users</p>
                        </div>
                        <div>
                            <i class="fas fa-trash fa-2x opacity-75"></i>
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
                            <h3 class="mb-0">{{ $users->where('deleted_at', '>=', now()->subDays(30))->count() }}</h3>
                            <p class="mb-0">Deleted This Month</p>
                        </div>
                        <div>
                            <i class="fas fa-calendar fa-2x opacity-75"></i>
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
                            <h3 class="mb-0">{{ $users->where('deleted_at', '>=', now()->subDays(7))->count() }}</h3>
                            <p class="mb-0">Deleted This Week</p>
                        </div>
                        <div>
                            <i class="fas fa-clock fa-2x opacity-75"></i>
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
                            <h3 class="mb-0">{{ $users->where('deleted_at', '>=', now()->subDays(1))->count() }}</h3>
                            <p class="mb-0">Deleted Today</p>
                        </div>
                        <div>
                            <i class="fas fa-today fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div id="bulkActions" class="alert alert-info" style="display: none;">
        <div class="row align-items-center">
            <div class="col-auto">
                <span class="fw-bold"><span id="selectedCount">0</span> users selected</span>
            </div>
            <div class="col-auto">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-success" onclick="bulkRestore()">
                        <i class="fas fa-undo me-1"></i>Restore Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="bulkPermanentDelete()">
                        <i class="fas fa-times me-1"></i>Permanent Delete
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelection()">
                        Clear Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card table-container">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Deleted Users</h5>
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
                                <th>User</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Roles</th>
                                <th>Deleted At</th>
                                <th>Deleted By</th>
                                <th width="200" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr class="deleted-user-card">
                                    <td class="text-center">
                                        <input type="checkbox" class="user-checkbox form-check-input" value="{{ $user->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-2">
                                                {{ $user->initials }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $user->display_name }}</div>
                                                <small class="text-muted">{{ $user->username }}</small>
                                                @if($user->auth_source === 'ldap')
                                                    <small class="badge bg-info ms-1">LDAP</small>
                                                @endif
                                            </div>
                                        </div>
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
                                            <span class="badge bg-secondary me-1 opacity-75">
                                                {{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                                            </span>
                                        @endforeach
                                        @if($user->roles->count() > 2)
                                            <span class="badge bg-light text-dark">+{{ $user->roles->count() - 2 }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $user->deleted_at->format('M d, Y H:i') }}
                                            <br>
                                            <span class="text-muted">{{ $user->deleted_at->diffForHumans() }}</span>
                                        </small>
                                    </td>
                                    <td>
                                        @if($user->deleted_by)
                                            <small>
                                                <i class="fas fa-user me-1"></i>
                                                {{ $user->deletedBy->display_name ?? 'Unknown' }}
                                            </small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            @can('manage-users')
                                                <button type="button" 
                                                        class="btn btn-sm btn-success" 
                                                        title="Restore User"
                                                        onclick="restoreUser({{ $user->id }}, '{{ $user->display_name }}')">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-info" 
                                                        title="View Details"
                                                        onclick="viewDeletedUser({{ $user->id }})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        title="Permanent Delete"
                                                        onclick="permanentDeleteUser({{ $user->id }}, '{{ $user->display_name }}')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endcan
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
                    <i class="fas fa-trash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No deleted users found</h5>
                    <p class="text-muted">All user accounts are currently active.</p>
                    <a href="{{ route('users.index') }}" class="btn btn-primary">
                        <i class="fas fa-users me-2"></i>View Active Users
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restoreModalLabel">
                    <i class="fas fa-undo me-2 text-success"></i>Restore User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-undo text-success warning-icon"></i>
                <h5>Restore User Account?</h5>
                <p class="mb-3">Are you sure you want to restore <strong id="restoreUserName"></strong>?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    The user will be reactivated and can log in again.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmRestoreBtn">
                    <i class="fas fa-undo me-1"></i>Restore User
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Permanent Delete Confirmation Modal -->
<div class="modal fade" id="permanentDeleteModal" tabindex="-1" aria-labelledby="permanentDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="permanentDeleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2 text-danger"></i>Permanent Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center confirmation-modal">
                <i class="fas fa-exclamation-triangle text-danger warning-icon"></i>
                <h5>Permanently Delete User?</h5>
                <p class="mb-3">Are you sure you want to permanently delete <strong id="deleteUserName"></strong>?</p>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. All user data will be permanently removed.
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmPermanentDelete">
                    <label class="form-check-label" for="confirmPermanentDelete">
                        I understand this action cannot be undone
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmPermanentDeleteBtn" disabled>
                    <i class="fas fa-times me-1"></i>Permanent Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkActionModal" tabindex="-1" aria-labelledby="bulkActionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkActionModalLabel">
                    <i class="fas fa-users me-2"></i>Bulk Action
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="bulkActionContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn" id="confirmBulkActionBtn">
                    <i class="fas fa-check me-1"></i>Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1" aria-labelledby="userDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userDetailsModalLabel">
                    <i class="fas fa-user me-2"></i>Deleted User Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form id="restore-form" method="POST" style="display: none;">
    @csrf
    @method('PATCH')
</form>

<form id="permanent-delete-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<form id="bulk-action-form" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="action" id="bulk-action-type">
    <div id="bulk-user-ids"></div>
</form>
@endsection

@push('scripts')
<script>
let currentUserId = null;
let currentUserName = null;
let selectedUsers = [];

document.addEventListener('DOMContentLoaded', function() {
    initializeBulkSelection();
    initializeConfirmationCheckbox();
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
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    
    selectedUsers = Array.from(selected).map(cb => cb.value);
    
    if (selectedCount) {
        selectedCount.textContent = selected.length;
    }
    
    if (bulkActions) {
        bulkActions.style.display = selected.length > 0 ? 'block' : 'none';
    }
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.style.display = selected.length > 0 ? 'inline-block' : 'none';
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

// User actions
function restoreUser(userId, userName) {
    currentUserId = userId;
    currentUserName = userName;
    
    document.getElementById('restoreUserName').textContent = userName;
    
    const modal = new bootstrap.Modal(document.getElementById('restoreModal'));
    modal.show();
}

function permanentDeleteUser(userId, userName) {
    currentUserId = userId;
    currentUserName = userName;
    
    document.getElementById('deleteUserName').textContent = userName;
    
    const modal = new bootstrap.Modal(document.getElementById('permanentDeleteModal'));
    modal.show();
}

function viewDeletedUser(userId) {
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    modal.show();
    
    // Load user details via AJAX
    fetch(`/users/${userId}/details`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('userDetailsContent').innerHTML = data.html;
        })
        .catch(error => {
            document.getElementById('userDetailsContent').innerHTML = 
                '<div class="alert alert-danger">Failed to load user details.</div>';
        });
}

// Bulk actions
function bulkRestore() {
    if (selectedUsers.length === 0) {
        alert('Please select users to restore.');
        return;
    }
    
    const content = `
        <i class="fas fa-undo text-success warning-icon"></i>
        <h5>Restore ${selectedUsers.length} User(s)?</h5>
        <p>Are you sure you want to restore the selected users?</p>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            All selected users will be reactivated.
        </div>
    `;
    
    showBulkActionModal('Restore Users', content, 'restore', 'btn-success');
}

function bulkPermanentDelete() {
    if (selectedUsers.length === 0) {
        alert('Please select users to delete.');
        return;
    }
    
    const content = `
        <i class="fas fa-exclamation-triangle text-danger warning-icon"></i>
        <h5>Permanently Delete ${selectedUsers.length} User(s)?</h5>
        <p>Are you sure you want to permanently delete the selected users?</p>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Warning:</strong> This action cannot be undone. All user data will be permanently removed.
        </div>
        <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" id="confirmBulkDelete">
            <label class="form-check-label" for="confirmBulkDelete">
                I understand this action cannot be undone
            </label>
        </div>
    `;
    
    showBulkActionModal('Permanent Delete Users', content, 'permanent-delete', 'btn-danger');
}

function showBulkActionModal(title, content, action, buttonClass) {
    document.getElementById('bulkActionModalLabel').innerHTML = `<i class="fas fa-users me-2"></i>${title}`;
    document.getElementById('bulkActionContent').innerHTML = content;
    document.getElementById('bulk-action-type').value = action;
    
    const confirmBtn = document.getElementById('confirmBulkActionBtn');
    confirmBtn.className = `btn ${buttonClass}`;
    confirmBtn.innerHTML = `<i class="fas fa-check me-1"></i>${title}`;
    
    // Handle checkbox confirmation for dangerous actions
    if (action === 'permanent-delete') {
        confirmBtn.disabled = true;
        const checkbox = document.getElementById('confirmBulkDelete');
        if (checkbox) {
            checkbox.addEventListener('change', function() {
                confirmBtn.disabled = !this.checked;
            });
        }
    } else {
        confirmBtn.disabled = false;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('bulkActionModal'));
    modal.show();
}

// Initialize confirmation checkbox
function initializeConfirmationCheckbox() {
    const checkbox = document.getElementById('confirmPermanentDelete');
    const confirmBtn = document.getElementById('confirmPermanentDeleteBtn');
    
    if (checkbox && confirmBtn) {
        checkbox.addEventListener('change', function() {
            confirmBtn.disabled = !this.checked;
        });
    }
}

// Event listeners for confirmation buttons
document.addEventListener('DOMContentLoaded', function() {
    const confirmRestoreBtn = document.getElementById('confirmRestoreBtn');
    if (confirmRestoreBtn) {
        confirmRestoreBtn.addEventListener('click', function() {
            if (currentUserId) {
                const form = document.getElementById('restore-form');
                form.action = `/users/${currentUserId}/restore`;
                form.submit();
            }
        });
    }
    
    const confirmPermanentDeleteBtn = document.getElementById('confirmPermanentDeleteBtn');
    if (confirmPermanentDeleteBtn) {
        confirmPermanentDeleteBtn.addEventListener('click', function() {
            if (currentUserId) {
                const form = document.getElementById('permanent-delete-form');
                form.action = `/users/${currentUserId}/force-delete`;
                form.submit();
            }
        });
    }
    
    const confirmBulkActionBtn = document.getElementById('confirmBulkActionBtn');
    if (confirmBulkActionBtn) {
        confirmBulkActionBtn.addEventListener('click', function() {
            const action = document.getElementById('bulk-action-type').value;
            const form = document.getElementById('bulk-action-form');
            
            // Clear existing user IDs
            const userIdsContainer = document.getElementById('bulk-user-ids');
            userIdsContainer.innerHTML = '';
            
            // Add selected user IDs
            selectedUsers.forEach(userId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'user_ids[]';
                input.value = userId;
                userIdsContainer.appendChild(input);
            });
            
            // Set action route
            if (action === 'restore') {
                form.action = '/users/bulk-restore';
                form.method = 'POST';
            } else if (action === 'permanent-delete') {
                form.action = '/users/bulk-force-delete';
                form.method = 'POST';
            }
            
            form.submit();
        });
    }
});

// Auto-refresh deleted count
setInterval(function() {
    // Optional: Auto-refresh counts every 30 seconds
    // You can implement AJAX calls to update statistics
}, 30000);

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape to clear selection
    if (e.key === 'Escape') {
        clearSelection();
    }
    
    // Ctrl+A to select all
    if (e.ctrlKey && e.key === 'a') {
        e.preventDefault();
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = !selectAllCheckbox.checked;
            selectAllCheckbox.dispatchEvent(new Event('change'));
        }
    }
});

// Show confirmation tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endpush