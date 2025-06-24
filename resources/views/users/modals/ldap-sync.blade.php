<!-- LDAP Sync Modal -->
<div class="modal fade" id="ldapSyncModal" tabindex="-1" aria-labelledby="ldapSyncModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="ldapSyncModalLabel">
                    <i class="fas fa-sync me-2"></i>LDAP Synchronization
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>About LDAP Synchronization:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Imports user accounts from your organization's LDAP directory</li>
                        <li>Updates existing user information with latest data from LDAP</li>
                        <li>Synchronization may take several minutes depending on directory size</li>
                        <li>User passwords are managed by LDAP - users login with domain credentials</li>
                    </ul>
                </div>

                <div id="syncStatus">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Checking LDAP sync status...</p>
                    </div>
                </div>

                <!-- Sync History -->
                <div id="syncHistory" class="mt-4" style="display: none;">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-history me-2"></i>Recent Sync History
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Status</th>
                                    <th>New Users</th>
                                    <th>Updated</th>
                                    <th>Errors</th>
                                </tr>
                            </thead>
                            <tbody id="syncHistoryBody">
                                <!-- History will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sync Options -->
                <div id="syncOptions" class="mt-4" style="display: none;">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-cogs me-2"></i>Sync Options
                    </h6>
                    <form id="syncOptionsForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="syncNewUsers" checked>
                                    <label class="form-check-label" for="syncNewUsers">
                                        <strong>Import New Users</strong>
                                        <br><small class="text-muted">Create accounts for new LDAP users</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="updateExisting" checked>
                                    <label class="form-check-label" for="updateExisting">
                                        <strong>Update Existing Users</strong>
                                        <br><small class="text-muted">Update profile information for existing users</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="syncDisabled">
                                    <label class="form-check-label" for="syncDisabled">
                                        <strong>Include Disabled Accounts</strong>
                                        <br><small class="text-muted">Sync users with disabled LDAP accounts</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="assignDefaultRole" checked>
                                    <label class="form-check-label" for="assignDefaultRole">
                                        <strong>Assign Default Role</strong>
                                        <br><small class="text-muted">Assign "user" role to new accounts</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <label for="ldapFilter" class="form-label">
                                    <i class="fas fa-filter me-1"></i>LDAP Filter (Advanced)
                                </label>
                                <input type="text" class="form-control" id="ldapFilter" 
                                       placeholder="(objectClass=user)" 
                                       value="(&(objectClass=user)(objectCategory=person))">
                                <div class="form-text">
                                    Leave empty to use default filter. Advanced users only.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- LDAP Configuration Info -->
                <div id="ldapConfig" class="mt-4" style="display: none;">
                    <h6 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-server me-2"></i>LDAP Configuration
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Server:</small>
                            <div id="ldapServer" class="fw-semibold">-</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Base DN:</small>
                            <div id="ldapBaseDn" class="fw-semibold">-</div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <small class="text-muted">Port:</small>
                            <div id="ldapPort" class="fw-semibold">-</div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted">Encryption:</small>
                            <div id="ldapEncryption" class="fw-semibold">-</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Close
                </button>
                <button type="button" class="btn btn-outline-info" onclick="showSyncOptions()">
                    <i class="fas fa-cogs me-1"></i>Options
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="showSyncHistory()">
                    <i class="fas fa-history me-1"></i>History
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="testLdapConnection()">
                    <i class="fas fa-plug me-1"></i>Test Connection
                </button>
                <button type="button" class="btn btn-primary" id="startSyncBtn" onclick="startLdapSync()">
                    <i class="fas fa-play me-1"></i>Start Sync
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// LDAP Sync Modal JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeLdapSyncModal();
});

function initializeLdapSyncModal() {
    const ldapSyncModal = document.getElementById('ldapSyncModal');
    if (ldapSyncModal) {
        ldapSyncModal.addEventListener('shown.bs.modal', function() {
            checkLdapSyncStatus();
        });
    }
}

function showSyncOptions() {
    const optionsDiv = document.getElementById('syncOptions');
    const historyDiv = document.getElementById('syncHistory');
    const configDiv = document.getElementById('ldapConfig');
    
    if (optionsDiv.style.display === 'none') {
        optionsDiv.style.display = 'block';
        historyDiv.style.display = 'none';
        configDiv.style.display = 'block';
    } else {
        optionsDiv.style.display = 'none';
        configDiv.style.display = 'none';
    }
}

function showSyncHistory() {
    const historyDiv = document.getElementById('syncHistory');
    const optionsDiv = document.getElementById('syncOptions');
    const configDiv = document.getElementById('ldapConfig');
    
    if (historyDiv.style.display === 'none') {
        historyDiv.style.display = 'block';
        optionsDiv.style.display = 'none';
        configDiv.style.display = 'none';
        loadSyncHistory();
    } else {
        historyDiv.style.display = 'none';
    }
}

function loadSyncHistory() {
    const historyBody = document.getElementById('syncHistoryBody');
    
    // Show loading
    historyBody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Loading sync history...
            </td>
        </tr>
    `;
    
    // Fetch sync history
    fetch('/users/sync-history')
        .then(response => response.json())
        .then(data => {
            if (data.history && data.history.length > 0) {
                historyBody.innerHTML = data.history.map(sync => `
                    <tr>
                        <td>
                            <small>${new Date(sync.date).toLocaleDateString()}</small><br>
                            <small class="text-muted">${new Date(sync.date).toLocaleTimeString()}</small>
                        </td>
                        <td>
                            <span class="badge bg-${sync.status === 'success' ? 'success' : sync.status === 'failed' ? 'danger' : 'warning'}">
                                <i class="fas fa-${sync.status === 'success' ? 'check' : sync.status === 'failed' ? 'times' : 'exclamation'} me-1"></i>
                                ${sync.status.charAt(0).toUpperCase() + sync.status.slice(1)}
                            </span>
                        </td>
                        <td><span class="badge bg-primary">${sync.new_users || 0}</span></td>
                        <td><span class="badge bg-info">${sync.updated_users || 0}</span></td>
                        <td>
                            ${sync.errors > 0 ? 
                                `<span class="badge bg-danger">${sync.errors}</span>` : 
                                `<span class="badge bg-success">0</span>`
                            }
                        </td>
                    </tr>
                `).join('');
            } else {
                historyBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            <i class="fas fa-history me-2"></i>No sync history available
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading sync history:', error);
            historyBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Failed to load sync history
                    </td>
                </tr>
            `;
        });
}

function testLdapConnection() {
    const testBtn = event.target;
    const originalText = testBtn.innerHTML;
    
    testBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Testing...';
    testBtn.disabled = true;
    
    fetch('/users/test-ldap-connection', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        testBtn.innerHTML = originalText;
        testBtn.disabled = false;
        
        if (data.success) {
            showAlert('success', `
                <strong>Connection Test Successful!</strong><br>
                <small>Server: ${data.server || 'Unknown'}</small><br>
                <small>Users found: ${data.user_count || 0}</small><br>
                <small>Response time: ${data.response_time || 'N/A'}ms</small>
            `, 8000);
        } else {
            showAlert('danger', `
                <strong>Connection Test Failed!</strong><br>
                <small>Error: ${data.message}</small><br>
                <small>Please check LDAP configuration and server connectivity.</small>
            `, 10000);
        }
    })
    .catch(error => {
        testBtn.innerHTML = originalText;
        testBtn.disabled = false;
        console.error('Error testing LDAP connection:', error);
        showAlert('danger', 'Connection test failed: ' + error.message);
    });
}

function startLdapSyncWithOptions() {
    if (!confirm('Are you sure you want to start LDAP synchronization with the selected options?')) {
        return;
    }
    
    const options = {
        sync_new_users: document.getElementById('syncNewUsers').checked,
        update_existing: document.getElementById('updateExisting').checked,
        sync_disabled: document.getElementById('syncDisabled').checked,
        assign_default_role: document.getElementById('assignDefaultRole').checked,
        ldap_filter: document.getElementById('ldapFilter').value.trim()
    };
    
    showLoading(true);
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch('/users/sync-ldap', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(options)
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
            const details = data.results ? 
                `<br><small>New: ${data.results.new_users}, Updated: ${data.results.updated_users}, Errors: ${data.results.errors}</small>` : '';
            showAlert('success', `${data.message}${details}<br><small>Synced at: ${data.synced_at}</small>`);
            
            // Update sync status
            setTimeout(() => {
                checkLdapSyncStatus();
            }, 1000);
            
            // Close modal after short delay
            setTimeout(() => {
                const modalElement = document.getElementById('ldapSyncModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) modal.hide();
                
                // Reload page to show new users
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }, 3000);
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

function updateLdapConfig(config) {
    if (config) {
        document.getElementById('ldapServer').textContent = config.host || 'Not configured';
        document.getElementById('ldapBaseDn').textContent = config.base_dn || 'Not configured';
        document.getElementById('ldapPort').textContent = config.port || '389';
        document.getElementById('ldapEncryption').textContent = config.encryption || 'None';
    }
}

// Enhanced sync status update to show configuration
function updateSyncStatusEnhanced(data) {
    updateSyncStatus(data); // Call the main function
    
    // Update LDAP configuration if available
    if (data.ldap_config) {
        updateLdapConfig(data.ldap_config);
    }
    
    // Show additional sync statistics
    if (data.sync_stats) {
        const statsHtml = `
            <div class="mt-3 pt-3 border-top">
                <h6 class="mb-2"><i class="fas fa-chart-bar me-2"></i>Sync Statistics</h6>
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted">Total Syncs:</small>
                        <div class="fw-semibold">${data.sync_stats.total_syncs || 0}</div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Success Rate:</small>
                        <div class="fw-semibold">${data.sync_stats.success_rate || '0%'}</div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Avg Duration:</small>
                        <div class="fw-semibold">${data.sync_stats.avg_duration || 'N/A'}</div>
                    </div>
                </div>
            </div>
        `;
        
        const statusDiv = document.getElementById('syncStatus');
        statusDiv.innerHTML += statsHtml;
    }
}

// Progress tracking for long-running syncs
function trackSyncProgress() {
    let progressInterval = setInterval(() => {
        fetch('/users/sync-progress')
            .then(response => response.json())
            .then(data => {
                if (data.is_running) {
                    updateProgressDisplay(data);
                } else {
                    clearInterval(progressInterval);
                    checkLdapSyncStatus(); // Refresh final status
                }
            })
            .catch(error => {
                console.error('Error tracking sync progress:', error);
                clearInterval(progressInterval);
            });
    }, 2000); // Check every 2 seconds
}

function updateProgressDisplay(data) {
    const statusDiv = document.getElementById('syncStatus');
    
    if (data.progress) {
        const progressHtml = `
            <div class="alert alert-info">
                <div class="d-flex align-items-center mb-2">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <span>Sync in progress... (${data.progress.current}/${data.progress.total})</span>
                </div>
                <div class="progress mb-2">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: ${data.progress.percentage}%"
                         aria-valuenow="${data.progress.percentage}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        ${data.progress.percentage}%
                    </div>
                </div>
                <small class="text-muted">
                    Current: ${data.progress.current_operation || 'Processing users...'}<br>
                    Estimated time remaining: ${data.progress.eta || 'Calculating...'}
                </small>
            </div>
        `;
        statusDiv.innerHTML = progressHtml;
    }
}

// Auto-refresh status when modal is visible
let statusRefreshInterval;

document.addEventListener('DOMContentLoaded', function() {
    const ldapSyncModal = document.getElementById('ldapSyncModal');
    if (ldapSyncModal) {
        ldapSyncModal.addEventListener('shown.bs.modal', function() {
            checkLdapSyncStatus();
            // Start auto-refresh
            statusRefreshInterval = setInterval(() => {
                checkLdapSyncStatus();
            }, 10000); // Refresh every 10 seconds
        });
        
        ldapSyncModal.addEventListener('hidden.bs.modal', function() {
            // Stop auto-refresh
            if (statusRefreshInterval) {
                clearInterval(statusRefreshInterval);
            }
        });
    }
});
</script>