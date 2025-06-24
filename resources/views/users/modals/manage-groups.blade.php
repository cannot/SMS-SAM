<!-- Manage Groups Modal -->
<div class="modal fade" id="manageGroupsModal" tabindex="-1" aria-labelledby="manageGroupsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="manageGroupsModalLabel">
                    <i class="fas fa-users me-2"></i>Manage Notification Groups
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Managing groups for:</strong> <span id="manageGroupsUserName" class="fw-bold"></span>
                </div>

                <!-- Current Groups -->
                <div class="mb-4">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-check-circle me-2 text-success"></i>Current Groups
                    </h6>
                    <div id="currentGroups">
                        <div class="text-center text-muted">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            Loading current groups...
                        </div>
                    </div>
                </div>

                <!-- Available Groups -->
                <div class="mb-4">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-plus-circle me-2 text-primary"></i>Available Groups
                    </h6>
                    
                    <!-- Group Search -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="groupSearch" placeholder="Search groups...">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                Filter
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="filterGroups('all')">All Groups</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterGroups('manual')">Manual Groups</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterGroups('ldap')">LDAP Groups</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterGroups('department')">Department Groups</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="filterGroups('active')">Active Only</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterGroups('large')">Large Groups (20+ members)</a></li>
                            </ul>
                        </div>
                    </div>

                    <form id="manageGroupsForm">
                        @csrf
                        <div class="row" id="availableGroups">
                            <!-- Groups will be loaded here -->
                            <div class="col-12 text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading available groups...</p>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Group Statistics -->
                <div class="mb-4" id="groupStats" style="display: none;">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-chart-bar me-2 text-info"></i>Group Statistics
                    </h6>
                    <div class="row" id="groupStatsContent">
                        <!-- Stats will be loaded here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-outline-info" onclick="showGroupStats()">
                    <i class="fas fa-chart-bar me-1"></i>Show Stats
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="resetGroupSelection()">
                    <i class="fas fa-undo me-1"></i>Reset
                </button>
                <button type="button" class="btn btn-success" onclick="saveGroupChanges()" id="saveGroupsBtn">
                    <i class="fas fa-save me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.group-card {
    transition: all 0.2s ease-in-out;
    border: 2px solid transparent;
    cursor: pointer;
    position: relative;
}

.group-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.group-card.selected {
    border-color: #198754;
    background-color: rgba(25, 135, 84, 0.05);
}

.group-card.current {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}

.group-card .group-type-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 0.7rem;
}

.current-group-badge {
    background: linear-gradient(45deg, #198754, #20c997);
    color: white;
    border: none;
}

.new-group-badge {
    background: linear-gradient(45deg, #0d6efd, #6610f2);
    color: white;
    border: none;
}

.removed-group-badge {
    background: linear-gradient(45deg, #dc3545, #fd7e14);
    color: white;
    border: none;
    text-decoration: line-through;
}

.group-member-count {
    font-size: 0.8rem;
}

.group-description {
    font-size: 0.85rem;
    color: #6c757d;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
// Manage Groups Modal JavaScript
let currentUserGroups = [];
let originalGroups = [];
let allGroups = [];
let filteredGroups = [];

function initializeManageGroupsModal() {
    // Load available groups
    loadAvailableGroups();
    
    // Group search functionality
    const groupSearch = document.getElementById('groupSearch');
    if (groupSearch) {
        groupSearch.addEventListener('input', function() {
            searchGroups(this.value);
        });
    }
}

function loadUserGroups(userId) {
    const currentGroupsDiv = document.getElementById('currentGroups');
    
    fetch(`/users/ajax/${userId}/groups`)
        .then(response => response.json())
        .then(data => {
            if (data.groups) {
                currentUserGroups = data.groups;
                originalGroups = [...data.groups];
                displayCurrentGroups(data.groups);
            }
        })
        .catch(error => {
            console.error('Error loading user groups:', error);
            currentGroupsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Failed to load current groups
                </div>
            `;
        });
}

function loadAvailableGroups() {
    const availableGroupsDiv = document.getElementById('availableGroups');
    
    fetch('/notification-groups/available')
        .then(response => response.json())
        .then(data => {
            if (data.groups) {
                allGroups = data.groups;
                filteredGroups = [...allGroups];
                displayAvailableGroups(filteredGroups);
            }
        })
        .catch(error => {
            console.error('Error loading available groups:', error);
            availableGroupsDiv.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Failed to load available groups
                    </div>
                </div>
            `;
        });
}

function displayCurrentGroups(groups) {
    const currentGroupsDiv = document.getElementById('currentGroups');
    
    if (groups.length === 0) {
        currentGroupsDiv.innerHTML = `
            <div class="text-muted text-center">
                <i class="fas fa-users me-2"></i>
                Not a member of any notification groups
            </div>
        `;
    } else {
        const groupsHtml = groups.map(group => `
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                <div class="flex-grow-1">
                    <span class="badge current-group-badge me-2">
                        <i class="fas fa-users me-1"></i>
                        ${group.name}
                    </span>
                    <small class="text-muted">${group.description || 'No description'}</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-dark group-member-count">
                        ${group.members_count} members
                    </span>
                    <span class="badge bg-secondary ms-1">${group.type}</span>
                </div>
            </div>
        `).join('');
        
        currentGroupsDiv.innerHTML = groupsHtml;
    }
}

function displayAvailableGroups(groups) {
    const availableGroupsDiv = document.getElementById('availableGroups');
    
    if (groups.length === 0) {
        availableGroupsDiv.innerHTML = `
            <div class="col-12">
                <div class="text-muted text-center">
                    <i class="fas fa-search me-2"></i>
                    No groups found matching your criteria
                </div>
            </div>
        `;
        return;
    }
    
    const groupsHtml = groups.map(group => {
        const isCurrentMember = currentUserGroups.some(ug => ug.id === group.id);
        return `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card group-card h-100 ${isCurrentMember ? 'current' : ''}" data-group-id="${group.id}">
                    <span class="badge group-type-badge bg-${getGroupTypeBadgeColor(group.type)}">${group.type}</span>
                    <div class="card-body p-3">
                        <div class="form-check">
                            <input class="form-check-input group-checkbox" 
                                   type="checkbox" 
                                   name="groups[]" 
                                   value="${group.id}" 
                                   id="group_manage_${group.id}"
                                   ${isCurrentMember ? 'checked' : ''}>
                            <label class="form-check-label w-100" for="group_manage_${group.id}">
                                <div class="d-flex flex-column">
                                    <strong class="d-block">${group.name}</strong>
                                    <div class="group-description mt-1">
                                        ${group.description || 'No description available'}
                                    </div>
                                    <div class="mt-2 d-flex justify-content-between align-items-center">
                                        <span class="badge bg-light text-dark group-member-count">
                                            <i class="fas fa-users me-1"></i>${group.members_count} members
                                        </span>
                                        ${group.is_active ? 
                                            '<span class="badge bg-success">Active</span>' : 
                                            '<span class="badge bg-secondary">Inactive</span>'
                                        }
                                    </div>
                                    ${group.auto_managed ? `
                                        <div class="mt-1">
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-cog me-1"></i>Auto-managed
                                            </span>
                                        </div>
                                    ` : ''}
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    availableGroupsDiv.innerHTML = groupsHtml;
    
    // Initialize group card interactions
    initializeGroupCards();
}

function initializeGroupCards() {
    // Group card click handlers
    document.querySelectorAll('.group-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('.group-checkbox');
                if (!checkbox.disabled) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            }
        });
    });

    // Group checkbox change handlers
    document.querySelectorAll('.group-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateGroupCardAppearance(this);
        });
    });
}

function updateGroupCardAppearance(checkbox) {
    const card = checkbox.closest('.group-card');
    
    if (checkbox.checked) {
        card.classList.add('selected');
    } else {
        card.classList.remove('selected');
    }
}

function getGroupTypeBadgeColor(type) {
    const colors = {
        'manual': 'primary',
        'ldap': 'info',
        'department': 'warning',
        'auto': 'secondary'
    };
    return colors[type] || 'secondary';
}

function searchGroups(query) {
    if (!query.trim()) {
        filteredGroups = [...allGroups];
    } else {
        filteredGroups = allGroups.filter(group => 
            group.name.toLowerCase().includes(query.toLowerCase()) ||
            (group.description && group.description.toLowerCase().includes(query.toLowerCase()))
        );
    }
    displayAvailableGroups(filteredGroups);
}

function filterGroups(filterType) {
    switch (filterType) {
        case 'all':
            filteredGroups = [...allGroups];
            break;
        case 'manual':
            filteredGroups = allGroups.filter(group => group.type === 'manual');
            break;
        case 'ldap':
            filteredGroups = allGroups.filter(group => group.type === 'ldap');
            break;
        case 'department':
            filteredGroups = allGroups.filter(group => group.type === 'department');
            break;
        case 'active':
            filteredGroups = allGroups.filter(group => group.is_active);
            break;
        case 'large':
            filteredGroups = allGroups.filter(group => group.members_count >= 20);
            break;
        default:
            filteredGroups = [...allGroups];
    }
    displayAvailableGroups(filteredGroups);
}

function showGroupStats() {
    const selectedGroups = getSelectedGroups();
    const statsDiv = document.getElementById('groupStats');
    const statsContent = document.getElementById('groupStatsContent');
    
    if (selectedGroups.length === 0) {
        showAlert('info', 'Please select some groups to view statistics');
        return;
    }
    
    // Calculate stats
    const stats = calculateGroupStats(selectedGroups);
    displayGroupStats(stats);
    
    statsDiv.style.display = 'block';
}

function calculateGroupStats(selectedGroups) {
    const totalMembers = selectedGroups.reduce((sum, group) => {
        const groupData = allGroups.find(g => g.id === parseInt(group.value));
        return sum + (groupData ? groupData.members_count : 0);
    }, 0);
    
    const groupTypes = selectedGroups.reduce((types, group) => {
        const groupData = allGroups.find(g => g.id === parseInt(group.value));
        if (groupData) {
            types[groupData.type] = (types[groupData.type] || 0) + 1;
        }
        return types;
    }, {});
    
    const activeGroups = selectedGroups.filter(group => {
        const groupData = allGroups.find(g => g.id === parseInt(group.value));
        return groupData && groupData.is_active;
    }).length;
    
    return {
        total_groups: selectedGroups.length,
        total_members: totalMembers,
        active_groups: activeGroups,
        group_types: groupTypes,
        avg_members: totalMembers / selectedGroups.length
    };
}

function displayGroupStats(stats) {
    const statsContent = document.getElementById('groupStatsContent');
    
    const statsHtml = `
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h4 class="text-primary">${stats.total_groups}</h4>
                    <small class="text-muted">Total Groups</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h4 class="text-success">${stats.total_members}</h4>
                    <small class="text-muted">Total Members</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h4 class="text-info">${stats.active_groups}</h4>
                    <small class="text-muted">Active Groups</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h4 class="text-warning">${Math.round(stats.avg_members)}</h4>
                    <small class="text-muted">Avg Members</small>
                </div>
            </div>
        </div>
        ${Object.keys(stats.group_types).length > 0 ? `
        <div class="col-12 mt-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Group Types</h6>
                </div>
                <div class="card-body">
                    ${Object.entries(stats.group_types).map(([type, count]) => `
                        <span class="badge bg-${getGroupTypeBadgeColor(type)} me-2">
                            ${type}: ${count}
                        </span>
                    `).join('')}
                </div>
            </div>
        </div>
        ` : ''}
    `;
    
    statsContent.innerHTML = statsHtml;
}

function getSelectedGroups() {
    return Array.from(document.querySelectorAll('.group-checkbox:checked'));
}

function resetGroupSelection() {
    // Reset to original groups
    document.querySelectorAll('.group-checkbox').forEach(checkbox => {
        const groupId = parseInt(checkbox.value);
        const wasOriginalMember = originalGroups.some(group => group.id === groupId);
        
        checkbox.checked = wasOriginalMember;
        updateGroupCardAppearance(checkbox);
    });
    
    // Hide stats
    document.getElementById('groupStats').style.display = 'none';
}

function saveGroupChanges() {
    if (!currentUserId) {
        showAlert('error', 'No user selected');
        return;
    }
    
    const selectedGroups = getSelectedGroups();
    const groupIds = selectedGroups.map(group => group.value);
    
    const saveBtn = document.getElementById('saveGroupsBtn');
    const originalText = saveBtn.innerHTML;
    
    // Show loading state
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
    
    // Submit changes
    fetch(`/users/${currentUserId}/update-groups`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ groups: groupIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'User groups updated successfully');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('manageGroupsModal'));
            modal.hide();
            
            // Reload page to show changes
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            throw new Error(data.message || 'Failed to update groups');
        }
    })
    .catch(error => {
        console.error('Error saving groups:', error);
        showAlert('danger', 'Failed to update groups: ' + error.message);
        
        // Reset button
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// Initialize when modal is shown
document.addEventListener('DOMContentLoaded', function() {
    const manageGroupsModal = document.getElementById('manageGroupsModal');
    if (manageGroupsModal) {
        manageGroupsModal.addEventListener('shown.bs.modal', function() {
            initializeManageGroupsModal();
        });
        
        manageGroupsModal.addEventListener('hidden.bs.modal', function() {
            // Reset modal state
            document.getElementById('groupStats').style.display = 'none';
            document.getElementById('groupSearch').value = '';
            
            // Reset button
            const saveBtn = document.getElementById('saveGroupsBtn');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Changes';
            
            // Clear data
            allGroups = [];
            filteredGroups = [];
            currentUserGroups = [];
            originalGroups = [];
        });
    }
});
</script>