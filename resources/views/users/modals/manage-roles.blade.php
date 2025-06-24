<!-- Manage Roles Modal -->
<div class="modal fade" id="manageRolesModal" tabindex="-1" aria-labelledby="manageRolesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-purple text-white">
                <h5 class="modal-title" id="manageRolesModalLabel">
                    <i class="fas fa-user-tag me-2"></i>Manage User Roles
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Managing roles for:</strong> <span id="manageRolesUserName" class="fw-bold"></span>
                </div>

                <!-- Current Roles -->
                <div class="mb-4">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-check-circle me-2 text-success"></i>Current Roles
                    </h6>
                    <div id="currentRoles">
                        <div class="text-center text-muted">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            Loading current roles...
                        </div>
                    </div>
                </div>

                <!-- Available Roles -->
                <div class="mb-4">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>Available Roles
                    </h6>
                    
                    <!-- Role Search -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="roleSearch" placeholder="Search roles...">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Filter
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="filterRoles('all')">All Roles</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterRoles('admin')">Admin Roles</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterRoles('user')">User Roles</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterRoles('system')">System Roles</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="filterRoles('high-permissions')">High Permissions (10+)</a></li>
                            </ul>
                        </div>
                    </div>

                    <form id="manageRolesForm">
                        @csrf
                        <div class="row" id="availableRoles">
                            @foreach($roles as $role)
                                <div class="col-lg-4 col-md-6 mb-3" data-role-type="{{ $role->name }}" data-permissions-count="{{ $role->permissions->count() }}">
                                    <div class="card role-card h-100" data-role="{{ $role->name }}">
                                        <div class="card-body p-3">
                                            <div class="form-check">
                                                <input class="form-check-input role-checkbox" 
                                                       type="checkbox" 
                                                       name="roles[]" 
                                                       value="{{ $role->id }}" 
                                                       id="role_manage_{{ $role->id }}"
                                                       data-role-name="{{ $role->name }}"
                                                       data-permissions="{{ json_encode($role->permissions->pluck('name')) }}">
                                                <label class="form-check-label w-100" for="role_manage_{{ $role->id }}">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <strong class="d-block role-name">{{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}</strong>
                                                                @if($role->name === 'super-admin')
                                                                    <span class="badge bg-danger role-type-badge">
                                                                        <i class="fas fa-crown me-1"></i>Super
                                                                    </span>
                                                                @elseif($role->name === 'admin')
                                                                    <span class="badge bg-warning role-type-badge">
                                                                        <i class="fas fa-shield-alt me-1"></i>Admin
                                                                    </span>
                                                                @elseif(str_contains($role->name, 'manager'))
                                                                    <span class="badge bg-info role-type-badge">
                                                                        <i class="fas fa-user-tie me-1"></i>Manager
                                                                    </span>
                                                                @else
                                                                    <span class="badge bg-secondary role-type-badge">
                                                                        <i class="fas fa-user me-1"></i>User
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            
                                                            @if($role->description)
                                                                <small class="text-muted d-block mb-2 role-description">{{ $role->description }}</small>
                                                            @endif
                                                            
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <span class="badge bg-light text-dark permissions-count">
                                                                    <i class="fas fa-key me-1"></i>{{ $role->permissions->count() }} permissions
                                                                </span>
                                                                
                                                                @if($role->permissions->count() > 0)
                                                                    <button type="button" class="btn btn-sm btn-outline-info" onclick="showRolePermissions('{{ $role->id }}', '{{ $role->name }}')">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                            
                                                            <!-- Permission Preview (First 3 permissions) -->
                                                            @if($role->permissions->count() > 0)
                                                                <div class="mt-2">
                                                                    <small class="text-muted">Key permissions:</small>
                                                                    <div class="permission-preview">
                                                                        @foreach($role->permissions->take(3) as $permission)
                                                                            <span class="badge bg-light text-dark permission-badge">
                                                                                {{ $permission->display_name ?? str_replace('-', ' ', $permission->name) }}
                                                                            </span>
                                                                        @endforeach
                                                                        @if($role->permissions->count() > 3)
                                                                            <span class="badge bg-secondary">+{{ $role->permissions->count() - 3 }}</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>

                <!-- Role Details -->
                <div class="mb-4" id="roleDetails" style="display: none;">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-info-circle me-2 text-info"></i>Role Change Summary
                    </h6>
                    <div id="roleDetailsContent">
                        <!-- Role details will be loaded here -->
                    </div>
                </div>

                <!-- Permissions Preview -->
                <div class="mb-4" id="permissionsPreview" style="display: none;">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-key me-2 text-warning"></i>Effective Permissions Preview
                    </h6>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>This shows all permissions the user will have after role changes are applied.</small>
                    </div>
                    <div id="permissionsPreviewContent">
                        <!-- Permissions will be loaded here -->
                    </div>
                </div>

                <!-- Role Conflicts Warning -->
                <div class="mb-4" id="roleConflicts" style="display: none;">
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>Role Conflicts Detected
                        </h6>
                        <div id="roleConflictsContent">
                            <!-- Conflict details will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-outline-info" onclick="previewRoleChanges()">
                    <i class="fas fa-eye me-1"></i>Preview Changes
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="showPermissionsMatrix()">
                    <i class="fas fa-table me-1"></i>Permissions Matrix
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="resetRoleSelection()">
                    <i class="fas fa-undo me-1"></i>Reset
                </button>
                <button type="button" class="btn btn-primary" onclick="saveRoleChanges()" id="saveRolesBtn">
                    <i class="fas fa-save me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Role Permissions Detail Modal -->
<div class="modal fade" id="rolePermissionsModal" tabindex="-1" aria-labelledby="rolePermissionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="rolePermissionsModalLabel">
                    <i class="fas fa-key me-2"></i>Role Permissions
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="rolePermissionsContent">
                    <!-- Role permissions will be loaded here -->
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

<!-- Permissions Matrix Modal -->
<div class="modal fade" id="permissionsMatrixModal" tabindex="-1" aria-labelledby="permissionsMatrixModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="permissionsMatrixModalLabel">
                    <i class="fas fa-table me-2"></i>Permissions Matrix
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="permissionsMatrixContent">
                    <!-- Permissions matrix will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
                <button type="button" class="btn btn-outline-primary" onclick="exportPermissionsMatrix()">
                    <i class="fas fa-download me-1"></i>Export Matrix
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.role-card {
    transition: all 0.2s ease-in-out;
    border: 2px solid transparent;
    cursor: pointer;
    position: relative;
}

.role-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.role-card.selected {
    border-color: #6f42c1;
    background-color: rgba(111, 66, 193, 0.05);
    box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
}

.role-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.role-card.current {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}

.bg-purple {
    background-color: #6f42c1 !important;
}

.text-purple {
    color: #6f42c1 !important;
}

.permission-badge {
    font-size: 0.7rem;
    padding: 2px 6px;
    margin: 1px;
    border-radius: 8px;
}

.current-role-badge {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
    border: none;
}

.new-role-badge {
    background: linear-gradient(45deg, #007bff, #6610f2);
    color: white;
    border: none;
}

.removed-role-badge {
    background: linear-gradient(45deg, #dc3545, #fd7e14);
    color: white;
    border: none;
    text-decoration: line-through;
}

.role-type-badge {
    font-size: 0.7rem;
    padding: 2px 6px;
}

.permissions-count {
    font-size: 0.75rem;
}

.permission-preview .permission-badge {
    margin: 1px;
    font-size: 0.65rem;
}

.role-name {
    font-size: 0.9rem;
}

.role-description {
    font-size: 0.8rem;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.permissions-matrix-table {
    font-size: 0.8rem;
}

.permissions-matrix-table th {
    writing-mode: vertical-lr;
    text-orientation: mixed;
    min-width: 40px;
    padding: 8px 4px;
}

.role-change-summary {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
}

.conflict-item {
    background: rgba(255, 193, 7, 0.1);
    border-left: 4px solid #ffc107;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
}
</style>

<script>
// Manage Roles Modal JavaScript
let currentUserRoles = [];
let originalRoles = [];
let allRoles = @json($roles ?? []);
let filteredRoles = [...allRoles];

function initializeManageRolesModal() {
    // Role card click handlers
    document.querySelectorAll('.role-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox' && !e.target.closest('button')) {
                const checkbox = this.querySelector('.role-checkbox');
                if (!checkbox.disabled) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            }
        });
    });

    // Role checkbox change handlers
    document.querySelectorAll('.role-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateRoleCardAppearance(this);
            checkRoleConflicts();
        });
    });

    // Role search functionality
    const roleSearch = document.getElementById('roleSearch');
    if (roleSearch) {
        roleSearch.addEventListener('input', function() {
            searchRoles(this.value);
        });
    }
}

function loadUserRoles(userId) {
    const currentRolesDiv = document.getElementById('currentRoles');
    
    fetch(`/users/ajax/${userId}/roles`)
        .then(response => response.json())
        .then(data => {
            if (data.roles) {
                currentUserRoles = data.roles;
                originalRoles = [...data.roles];
                displayCurrentRoles(data.roles);
                updateRoleSelections(data.roles);
            }
        })
        .catch(error => {
            console.error('Error loading user roles:', error);
            currentRolesDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Failed to load current roles. Using defaults.
                </div>
            `;
            // Show default if API fails
            displayCurrentRoles([]);
            updateRoleSelections([]);
        });
}

function displayCurrentRoles(roles) {
    const currentRolesDiv = document.getElementById('currentRoles');
    
    if (roles.length === 0) {
        currentRolesDiv.innerHTML = `
            <div class="text-muted text-center">
                <i class="fas fa-user me-2"></i>
                No roles assigned (will have default user permissions)
            </div>
        `;
    } else {
        const rolesHtml = roles.map(role => `
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                <div class="flex-grow-1">
                    <span class="badge current-role-badge me-2">
                        <i class="fas fa-user-tag me-1"></i>
                        ${role.display_name || role.name}
                    </span>
                    <small class="text-muted">${role.description || 'No description'}</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark permissions-count">
                        ${role.permissions_count || 0} permissions
                    </span>
                    ${role.name === 'super-admin' ? '<span class="badge bg-danger ms-1">Super</span>' : ''}
                    ${role.name === 'admin' ? '<span class="badge bg-warning ms-1">Admin</span>' : ''}
                </div>
            </div>
        `).join('');
        
        currentRolesDiv.innerHTML = rolesHtml;
    }
}

function updateRoleSelections(roles) {
    // Clear all selections
    document.querySelectorAll('.role-checkbox').forEach(checkbox => {
        checkbox.checked = false;
        updateRoleCardAppearance(checkbox);
    });
    
    // Check current roles
    roles.forEach(role => {
        const checkbox = document.querySelector(`input[value="${role.id}"]`);
        if (checkbox) {
            checkbox.checked = true;
            updateRoleCardAppearance(checkbox);
        }
    });
    
    // Disable super-admin role for non-super-admin users
    const currentUserHasSuperAdmin = getCurrentUserRoles().includes('super-admin');
    const superAdminCheckbox = document.querySelector('input[data-role-name="super-admin"]');
    if (superAdminCheckbox && !currentUserHasSuperAdmin) {
        superAdminCheckbox.disabled = true;
        superAdminCheckbox.closest('.role-card').classList.add('disabled');
        
        // Add tooltip or message
        const card = superAdminCheckbox.closest('.role-card');
        if (!card.querySelector('.super-admin-notice')) {
            const notice = document.createElement('div');
            notice.className = 'super-admin-notice alert alert-warning mt-2 mb-0';
            notice.innerHTML = '<small><i class="fas fa-lock me-1"></i>Only super admins can assign this role</small>';
            card.querySelector('.card-body').appendChild(notice);
        }
    }
}

function updateRoleCardAppearance(checkbox) {
    const card = checkbox.closest('.role-card');
    
    if (checkbox.checked) {
        card.classList.add('selected');
    } else {
        card.classList.remove('selected');
    }
}

function searchRoles(query) {
    const roleCards = document.querySelectorAll('#availableRoles .col-lg-4');
    
    roleCards.forEach(card => {
        const roleName = card.querySelector('.role-name').textContent.toLowerCase();
        const roleDescription = card.querySelector('.role-description')?.textContent.toLowerCase() || '';
        
        if (!query.trim() || 
            roleName.includes(query.toLowerCase()) || 
            roleDescription.includes(query.toLowerCase())) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

function filterRoles(filterType) {
    const roleCards = document.querySelectorAll('#availableRoles .col-lg-4');
    
    roleCards.forEach(card => {
        let shouldShow = true;
        const roleType = card.getAttribute('data-role-type');
        const permissionsCount = parseInt(card.getAttribute('data-permissions-count'));
        
        switch (filterType) {
            case 'admin':
                shouldShow = roleType.includes('admin') || roleType.includes('manager');
                break;
            case 'user':
                shouldShow = roleType === 'user' || (!roleType.includes('admin') && !roleType.includes('manager'));
                break;
            case 'system':
                shouldShow = roleType.includes('system') || roleType.includes('super');
                break;
            case 'high-permissions':
                shouldShow = permissionsCount >= 10;
                break;
            case 'all':
            default:
                shouldShow = true;
                break;
        }
        
        card.style.display = shouldShow ? '' : 'none';
    });
}

function previewRoleChanges() {
    const selectedRoles = getSelectedRoles();
    const changes = calculateRoleChanges(originalRoles, selectedRoles);
    
    displayRoleChangesPreview(changes);
    
    // Show permissions preview
    document.getElementById('permissionsPreview').style.display = 'block';
    updatePermissionsPreview();
    
    // Check for conflicts
    checkRoleConflicts();
}

function calculateRoleChanges(original, selected) {
    const originalIds = original.map(role => role.id);
    const selectedData = selected.map(checkbox => {
        const roleCard = checkbox.closest('.role-card');
        return {
            id: parseInt(checkbox.value),
            name: checkbox.getAttribute('data-role-name'),
            display_name: roleCard.querySelector('.role-name').textContent
        };
    });
    const selectedIds = selectedData.map(role => role.id);
    
    const added = selectedData.filter(role => !originalIds.includes(role.id));
    const removed = original.filter(role => !selectedIds.includes(role.id));
    const unchanged = original.filter(role => selectedIds.includes(role.id));
    
    return { added, removed, unchanged };
}

function displayRoleChangesPreview(changes) {
    const roleDetailsDiv = document.getElementById('roleDetails');
    const roleDetailsContent = document.getElementById('roleDetailsContent');
    
    let previewHtml = '<div class="row role-change-summary">';
    
    // Unchanged roles
    if (changes.unchanged.length > 0) {
        previewHtml += `
            <div class="col-md-4 mb-3">
                <h6 class="text-success">
                    <i class="fas fa-check me-1"></i>Keeping (${changes.unchanged.length})
                </h6>
                ${changes.unchanged.map(role => `
                    <span class="badge current-role-badge mb-1 d-block text-start">
                        <i class="fas fa-user-tag me-1"></i>${role.display_name || role.name}
                    </span>
                `).join('')}
            </div>
        `;
    }
    
    // Added roles
    if (changes.added.length > 0) {
        previewHtml += `
            <div class="col-md-4 mb-3">
                <h6 class="text-primary">
                    <i class="fas fa-plus me-1"></i>Adding (${changes.added.length})
                </h6>
                ${changes.added.map(role => `
                    <span class="badge new-role-badge mb-1 d-block text-start">
                        <i class="fas fa-plus-circle me-1"></i>${role.display_name || role.name}
                    </span>
                `).join('')}
            </div>
        `;
    }
    
    // Removed roles
    if (changes.removed.length > 0) {
        previewHtml += `
            <div class="col-md-4 mb-3">
                <h6 class="text-danger">
                    <i class="fas fa-minus me-1"></i>Removing (${changes.removed.length})
                </h6>
                ${changes.removed.map(role => `
                    <span class="badge removed-role-badge mb-1 d-block text-start">
                        <i class="fas fa-minus-circle me-1"></i>${role.display_name || role.name}
                    </span>
                `).join('')}
            </div>
        `;
    }
    
    previewHtml += '</div>';
    
    if (changes.added.length === 0 && changes.removed.length === 0) {
        previewHtml = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No changes to apply - current role selection matches existing roles.
            </div>
        `;
    }
    
    roleDetailsContent.innerHTML = previewHtml;
    roleDetailsDiv.style.display = 'block';
}

function updatePermissionsPreview() {
    const selectedRoles = getSelectedRoles();
    const permissionsPreviewContent = document.getElementById('permissionsPreviewContent');
    
    if (selectedRoles.length === 0) {
        permissionsPreviewContent.innerHTML = `
            <div class="text-muted">
                <i class="fas fa-info-circle me-2"></i>
                No roles selected - user will have default permissions only
            </div>
        `;
        return;
    }
    
    // Show loading
    permissionsPreviewContent.innerHTML = `
        <div class="text-center">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Loading permissions preview...
        </div>
    `;
    
    // Get permissions for selected roles
    const allPermissions = new Set();
    selectedRoles.forEach(checkbox => {
        const permissions = JSON.parse(checkbox.getAttribute('data-permissions') || '[]');
        permissions.forEach(permission => allPermissions.add(permission));
    });
    
    displayPermissionsPreview(Array.from(allPermissions));
}

function displayPermissionsPreview(permissions) {
    const permissionsPreviewContent = document.getElementById('permissionsPreviewContent');
    
    if (permissions.length === 0) {
        permissionsPreviewContent.innerHTML = `
            <div class="text-muted">
                <i class="fas fa-info-circle me-2"></i>
                No specific permissions granted by selected roles
            </div>
        `;
        return;
    }
    
    // Group permissions by category
    const grouped = permissions.reduce((groups, permission) => {
        // Simple categorization based on permission name
        let category = 'General';
        if (permission.includes('user')) category = 'User Management';
        else if (permission.includes('role') || permission.includes('permission')) category = 'Role & Permissions';
        else if (permission.includes('notification')) category = 'Notifications';
        else if (permission.includes('api')) category = 'API Management';
        else if (permission.includes('system') || permission.includes('admin')) category = 'System Administration';
        
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(permission);
        return groups;
    }, {});
    
    let permissionsHtml = '<div class="row">';
    
    Object.keys(grouped).forEach(category => {
        permissionsHtml += `
            <div class="col-md-6 mb-3">
                <h6 class="text-secondary">
                    <i class="fas fa-folder me-1"></i>${category}
                </h6>
                <div>
                    ${grouped[category].map(permission => `
                        <span class="badge permission-badge bg-light text-dark">
                            ${permission.replace(/-/g, ' ').replace(/_/g, ' ')}
                        </span>
                    `).join('')}
                </div>
            </div>
        `;
    });
    
    permissionsHtml += '</div>';
    
    // Add summary
    permissionsHtml = `
        <div class="alert alert-success">
            <i class="fas fa-key me-2"></i>
            <strong>Total Permissions:</strong> ${permissions.length}
        </div>
    ` + permissionsHtml;
    
    permissionsPreviewContent.innerHTML = permissionsHtml;
}

function checkRoleConflicts() {
    const selectedRoles = getSelectedRoles();
    const conflictsDiv = document.getElementById('roleConflicts');
    const conflictsContent = document.getElementById('roleConflictsContent');
    
    const conflicts = [];
    const roleNames = selectedRoles.map(checkbox => checkbox.getAttribute('data-role-name'));
    
    // Check for common conflicts
    if (roleNames.includes('super-admin') && roleNames.length > 1) {
        conflicts.push({
            type: 'redundant',
            message: 'Super Admin role includes all permissions. Other roles are redundant.',
            roles: roleNames.filter(name => name !== 'super-admin')
        });
    }
    
    if (roleNames.includes('admin') && roleNames.includes('user')) {
        conflicts.push({
            type: 'hierarchy',
            message: 'Admin role already includes user permissions. User role is redundant.',
            roles: ['user']
        });
    }
    
    // Check for manager + admin conflicts
    const managerRoles = roleNames.filter(name => name.includes('manager'));
    if (managerRoles.length > 0 && roleNames.includes('admin')) {
        conflicts.push({
            type: 'overlap',
            message: 'Admin role may overlap with manager roles. Review permissions carefully.',
            roles: managerRoles
        });
    }
    
    if (conflicts.length > 0) {
        let conflictsHtml = conflicts.map(conflict => `
            <div class="conflict-item">
                <strong>${conflict.type.charAt(0).toUpperCase() + conflict.type.slice(1)} Conflict:</strong>
                ${conflict.message}
                ${conflict.roles.length > 0 ? `<br><small class="text-muted">Affected roles: ${conflict.roles.join(', ')}</small>` : ''}
            </div>
        `).join('');
        
        conflictsContent.innerHTML = conflictsHtml;
        conflictsDiv.style.display = 'block';
    } else {
        conflictsDiv.style.display = 'none';
    }
}

function showRolePermissions(roleId, roleName) {
    const modal = new bootstrap.Modal(document.getElementById('rolePermissionsModal'));
    const content = document.getElementById('rolePermissionsContent');
    
    // Update modal title
    document.getElementById('rolePermissionsModalLabel').innerHTML = `
        <i class="fas fa-key me-2"></i>Permissions for "${roleName}"
    `;
    
    // Show loading
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Loading role permissions...</p>
        </div>
    `;
    
    modal.show();
    
    // Load permissions (simulated - you can replace with actual API call)
    setTimeout(() => {
        // Find role data
        const role = allRoles.find(r => r.id == roleId);
        if (role && role.permissions) {
            displayRolePermissions(role);
        } else {
            content.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No permissions data available for this role.
                </div>
            `;
        }
    }, 500);
}

function displayRolePermissions(role) {
    const content = document.getElementById('rolePermissionsContent');
    
    if (!role.permissions || role.permissions.length === 0) {
        content.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                This role has no specific permissions assigned.
            </div>
        `;
        return;
    }
    
    // Group permissions by category
    const grouped = role.permissions.reduce((groups, permission) => {
        const category = permission.category || 'General';
        if (!groups[category]) {
            groups[category] = [];
        }
        groups[category].push(permission);
        return groups;
    }, {});
    
    let html = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>${role.display_name || role.name}</strong> has ${role.permissions.length} permission(s)
        </div>
    `;
    
    Object.keys(grouped).forEach(category => {
        html += `
            <div class="mb-4">
                <h6 class="border-bottom pb-2">
                    <i class="fas fa-folder me-2"></i>${category}
                </h6>
                <div class="row">
                    ${grouped[category].map(permission => `
                        <div class="col-md-6 mb-2">
                            <div class="card border-light">
                                <div class="card-body p-2">
                                    <strong class="d-block">${permission.display_name || permission.name}</strong>
                                    ${permission.description ? `<small class="text-muted">${permission.description}</small>` : ''}
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    });
    
    content.innerHTML = html;
}

function showPermissionsMatrix() {
    const modal = new bootstrap.Modal(document.getElementById('permissionsMatrixModal'));
    const content = document.getElementById('permissionsMatrixContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Building permissions matrix...</p>
        </div>
    `;
    
    modal.show();
    
    // Build matrix (simulated)
    setTimeout(() => {
        buildPermissionsMatrix();
    }, 500);
}

function buildPermissionsMatrix() {
    const content = document.getElementById('permissionsMatrixContent');
    
    // Get all unique permissions
    const allPermissions = new Set();
    allRoles.forEach(role => {
        if (role.permissions) {
            role.permissions.forEach(permission => {
                allPermissions.add(permission.name || permission);
            });
        }
    });
    
    const permissions = Array.from(allPermissions).sort();
    
    // Build matrix table
    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-bordered permissions-matrix-table">
                <thead class="table-dark">
                    <tr>
                        <th style="writing-mode: initial;">Role</th>
                        ${permissions.map(permission => `
                            <th title="${permission}">${permission.split('-').pop()}</th>
                        `).join('')}
                    </tr>
                </thead>
                <tbody>
    `;
    
    allRoles.forEach(role => {
        const rolePermissions = role.permissions ? role.permissions.map(p => p.name || p) : [];
        html += `
            <tr>
                <td class="fw-bold">${role.display_name || role.name}</td>
                ${permissions.map(permission => `
                    <td class="text-center">
                        ${rolePermissions.includes(permission) ? 
                            '<i class="fas fa-check text-success"></i>' : 
                            '<i class="fas fa-times text-muted"></i>'
                        }
                    </td>
                `).join('')}
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                Matrix shows which permissions are granted by each role. 
                <i class="fas fa-check text-success"></i> = Has permission, 
                <i class="fas fa-times text-muted"></i> = No permission
            </small>
        </div>
    `;
    
    content.innerHTML = html;
}

function exportPermissionsMatrix() {
    // Simple export to CSV
    const matrix = [];
    const permissions = [];
    
    // Get headers
    allRoles.forEach(role => {
        if (role.permissions) {
            role.permissions.forEach(permission => {
                const permName = permission.name || permission;
                if (!permissions.includes(permName)) {
                    permissions.push(permName);
                }
            });
        }
    });
    
    // Add header row
    matrix.push(['Role', ...permissions]);
    
    // Add data rows
    allRoles.forEach(role => {
        const rolePermissions = role.permissions ? role.permissions.map(p => p.name || p) : [];
        const row = [role.display_name || role.name];
        permissions.forEach(permission => {
            row.push(rolePermissions.includes(permission) ? 'Yes' : 'No');
        });
        matrix.push(row);
    });
    
    // Convert to CSV
    const csv = matrix.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    
    // Download
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'permissions_matrix.csv';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showAlert('success', 'Permissions matrix exported successfully!');
}

function getSelectedRoles() {
    return Array.from(document.querySelectorAll('.role-checkbox:checked'));
}

function resetRoleSelection() {
    updateRoleSelections(originalRoles);
    document.getElementById('roleDetails').style.display = 'none';
    document.getElementById('permissionsPreview').style.display = 'none';
    document.getElementById('roleConflicts').style.display = 'none';
}

function saveRoleChanges() {
    if (!currentUserId) {
        showAlert('error', 'No user selected');
        return;
    }
    
    const selectedRoles = getSelectedRoles();
    const roleIds = selectedRoles.map(role => role.value);
    
    const saveBtn = document.getElementById('saveRolesBtn');
    const originalText = saveBtn.innerHTML;
    
    // Show loading state
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
    
    // Submit changes
    fetch(`/users/${currentUserId}/roles`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ roles: roleIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'User roles updated successfully');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('manageRolesModal'));
            modal.hide();
            
            // Reload page to show changes
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            throw new Error(data.message || 'Failed to update roles');
        }
    })
    .catch(error => {
        console.error('Error saving roles:', error);
        showAlert('danger', 'Failed to update roles: ' + error.message);
        
        // Reset button
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

function getCurrentUserRoles() {
    // This should return the current user's roles to check permissions
    // You can get this from a meta tag or global variable
    return window.currentUserRoles || [];
}

// Initialize when modal is shown
document.addEventListener('DOMContentLoaded', function() {
    const manageRolesModal = document.getElementById('manageRolesModal');
    if (manageRolesModal) {
        manageRolesModal.addEventListener('shown.bs.modal', function() {
            initializeManageRolesModal();
        });
        
        manageRolesModal.addEventListener('hidden.bs.modal', function() {
            // Reset modal state
            document.getElementById('roleDetails').style.display = 'none';
            document.getElementById('permissionsPreview').style.display = 'none';
            document.getElementById('roleConflicts').style.display = 'none';
            
            // Clear search
            const roleSearch = document.getElementById('roleSearch');
            if (roleSearch) roleSearch.value = '';
            
            // Reset button
            const saveBtn = document.getElementById('saveRolesBtn');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Changes';
            
            // Clear selections
            document.querySelectorAll('.role-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                updateRoleCardAppearance(checkbox);
            });
            
            // Show all role cards
            document.querySelectorAll('#availableRoles .col-lg-4').forEach(card => {
                card.style.display = '';
            });
        });
    }
});
</script><!-- Manage Roles Modal -->