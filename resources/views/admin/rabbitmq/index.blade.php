@extends('layouts.app')

@section('title', 'RabbitMQ Monitor')

@section('styles')
<style>
.stats-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}
.stats-card:hover {
    transform: translateY(-2px);
}
.connection-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}
.status-connected {
    background-color: #d4edda;
    color: #155724;
}
.status-disconnected {
    background-color: #f8d7da;
    color: #721c24;
}
.queue-table {
    font-size: 0.9rem;
}
.metrics-value {
    font-size: 1.5rem;
    font-weight: bold;
}
</style>
@endsection

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-rabbit me-2"></i>
            RabbitMQ Monitor
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-outline-success" onclick="testConnection()">
                    <i class="fas fa-plug"></i> Test Connection
                </button>
                <button type="button" class="btn btn-outline-info" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <a href="http://{{ env('RABBITMQ_HOST', 'localhost') }}:{{ env('RABBITMQ_MANAGEMENT_PORT', 15672) }}" 
                   target="_blank" class="btn btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i> Management UI
                </a>
            </div>
        </div>
    </div>

    <!-- Connection Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-1">
                                Connection Status
                                <span id="connection-status" class="connection-status ms-2">
                                    <i class="fas fa-spinner fa-spin"></i> Checking...
                                </span>
                            </h5>
                            <div id="connection-details"></div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-warning" onclick="dispatchTestJob()">
                                <i class="fas fa-paper-plane"></i> Send Test Job
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Messages</div>
                            <div class="metrics-value" id="total-messages">--</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Ready Messages</div>
                            <div class="metrics-value" id="ready-messages">--</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card bg-warning text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Unacked Messages</div>
                            <div class="metrics-value" id="unacked-messages">--</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Queues</div>
                            <div class="metrics-value" id="total-queues">--</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Laravel Queue Status -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card stats-card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fab fa-laravel me-2"></i>
                        Laravel Queue Status
                    </h5>
                </div>
                <div class="card-body" id="laravel-queue-status">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Details -->
    <div class="row">
        <div class="col-12">
            <div class="card stats-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list-ul me-2"></i>
                        Queue Details
                    </h5>
                    <button class="btn btn-sm btn-outline-danger" onclick="showPurgeModal()">
                        <i class="fas fa-trash"></i> Purge Queue
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover queue-table" id="queues-table">
                            <thead>
                                <tr>
                                    <th>Queue Name</th>
                                    <th>Messages</th>
                                    <th>Ready</th>
                                    <th>Unacked</th>
                                    <th>Consumers</th>
                                    <th>State</th>
                                    <th>Node</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Purge Queue Modal -->
<div class="modal fade" id="purgeQueueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Purge Queue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label for="queueName">Queue Name:</label>
                    <input type="text" class="form-control" id="queueName" placeholder="Enter queue name">
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This will permanently delete all messages in the queue.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="purgeQueue()">Purge Queue</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadRabbitMQData();
    loadLaravelQueueStatus();
    
    // Auto-refresh every 30 seconds
    setInterval(function() {
        loadRabbitMQData();
        loadLaravelQueueStatus();
    }, 30000);
});

function loadRabbitMQData() {
    fetch('/admin/rabbitmq/stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateConnectionStatus(data.data);
                updateStatistics(data.data);
            } else {
                updateConnectionStatus({ status: 'disconnected', error: data.error });
            }
        })
        .catch(error => {
            console.error('Error loading RabbitMQ data:', error);
            updateConnectionStatus({ status: 'disconnected', error: error.message });
        });

    // Load queue details
    loadQueueDetails();
}

function updateConnectionStatus(data) {
    const statusElement = document.getElementById('connection-status');
    const detailsElement = document.getElementById('connection-details');
    
    if (data.status === 'connected') {
        statusElement.className = 'connection-status status-connected ms-2';
        statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Connected';
        
        detailsElement.innerHTML = `
            <small class="text-muted">
                RabbitMQ: ${data.rabbitmq_version} | 
                Erlang: ${data.erlang_version}
            </small>
        `;
    } else {
        statusElement.className = 'connection-status status-disconnected ms-2';
        statusElement.innerHTML = '<i class="fas fa-times-circle"></i> Disconnected';
        
        detailsElement.innerHTML = `
            <small class="text-danger">
                Error: ${data.error || 'Connection failed'}
            </small>
        `;
    }
}

function updateStatistics(data) {
    const stats = data.queue_totals || {};
    const objects = data.object_totals || {};
    
    document.getElementById('total-messages').textContent = stats.messages || 0;
    document.getElementById('ready-messages').textContent = stats.messages_ready || 0;
    document.getElementById('unacked-messages').textContent = stats.messages_unacknowledged || 0;
    document.getElementById('total-queues').textContent = objects.queues || 0;
}

function loadQueueDetails() {
    // This would need a separate endpoint to get queue details
    // For now, show a placeholder
    const tbody = document.querySelector('#queues-table tbody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Queue details available via Management UI</td></tr>';
}

function loadLaravelQueueStatus() {
    fetch('/admin/rabbitmq/laravel-queue-status')
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('laravel-queue-status');
            
            if (data.success) {
                const queueData = data.data;
                container.innerHTML = `
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Connection:</strong><br>
                            <span class="text-muted">${queueData.connection}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Pending Jobs:</strong><br>
                            <span class="badge bg-warning">${queueData.pending_jobs}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Failed Jobs:</strong><br>
                            <span class="badge bg-danger">${queueData.failed_jobs}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Worker Status:</strong><br>
                            <span class="badge ${queueData.is_working ? 'bg-success' : 'bg-secondary'}">
                                ${queueData.is_working ? 'Running' : 'Stopped'}
                            </span>
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = `<div class="text-danger">Error: ${data.error}</div>`;
            }
        })
        .catch(error => {
            console.error('Error loading Laravel queue status:', error);
        });
}

function testConnection() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    button.disabled = true;
    
    fetch('/admin/rabbitmq/test-connection', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Connection test successful!', 'success');
                loadRabbitMQData();
            } else {
                showToast('Connection test failed: ' + data.error, 'error');
            }
        })
        .catch(error => {
            showToast('Connection test error: ' + error.message, 'error');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
}

function dispatchTestJob() {
    fetch('/admin/rabbitmq/dispatch-test-job', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Test job dispatched successfully!', 'success');
                setTimeout(() => loadLaravelQueueStatus(), 2000);
            } else {
                showToast('Failed to dispatch test job: ' + data.error, 'error');
            }
        })
        .catch(error => {
            showToast('Error: ' + error.message, 'error');
        });
}

function showPurgeModal() {
    const modal = new bootstrap.Modal(document.getElementById('purgeQueueModal'));
    modal.show();
}

function purgeQueue() {
    const queueName = document.getElementById('queueName').value;
    if (!queueName) {
        showToast('Please enter a queue name', 'warning');
        return;
    }
    
    fetch('/admin/rabbitmq/purge-queue', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ queue_name: queueName })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('purgeQueueModal')).hide();
            loadRabbitMQData();
        } else {
            showToast('Error: ' + data.error, 'error');
        }
    })
    .catch(error => {
        showToast('Error: ' + error.message, 'error');
    });
}

function refreshData() {
    loadRabbitMQData();
    loadLaravelQueueStatus();
    showToast('Data refreshed', 'info');
}

function showToast(message, type = 'info') {
    // Simple toast implementation
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const toast = document.createElement('div');
    toast.className = `alert ${alertClass} alert-dismissible position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}
</script>
@endsection