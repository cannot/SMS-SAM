@extends('layouts.app')

@section('title', 'API Key Details - ' . $apiKey->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">{{ $apiKey->name }}</h1>
            <p class="text-muted mb-0">API Key Details</p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                <i class="fas fa-arrow-left me-2"></i>Back
            </button>
            @can('edit-api-keys')
            <a href="{{ route('admin.api-keys.edit', $apiKey) }}" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Edit
            </a>
            @endcan
            @can('delete-api-keys')
            <button type="button" class="btn btn-outline-danger" onclick="confirmDeactivate()">
                <i class="fas fa-ban me-2"></i>Deactivate
            </button>
            @endcan
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- API Key Information -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-key me-2"></i>API Key Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Name:</label>
                                <div class="fw-bold">{{ $apiKey->name }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Created By:</label>
                                <div class="fw-bold">{{ $apiKey->createdBy->display_name ?? 'Super Administrator' }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Assigned To:</label>
                                <div class="fw-bold">
                                    {{ $apiKey->assignedTo->display_name ?? 'Nutthanai Paleegai' }}
                                    @if($apiKey->assignedTo)
                                    <br><small class="text-muted">{{ $apiKey->assignedTo->email }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Status:</label>
                                <div>
                                    @if($apiKey->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Inactive</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Rate Limit:</label>
                                <div class="fw-bold">{{ $apiKey->rate_limit ?? '60 requests/minute' }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Expires:</label>
                                <div class="fw-bold">
                                    @if($apiKey->expires_at)
                                        {{ $apiKey->expires_at->format('M d, Y H:i') }}
                                    @else
                                        <span class="text-success">Never expires</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Last Used:</label>
                                <div class="fw-bold">
                                    @if($apiKey->last_used_at)
                                        {{ $apiKey->last_used_at->format('M d, Y H:i') }}
                                        <small class="text-muted">({{ $apiKey->last_used_at->diffForHumans() }})</small>
                                    @else
                                        <span class="text-muted">Never used</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Created:</label>
                        <div class="fw-bold">
                            {{ $apiKey->created_at->format('M d, Y H:i') }}
                            <small class="text-muted">({{ $apiKey->created_at->diffForHumans() }})</small>
                        </div>
                    </div>
                    @if($apiKey->description)
                    <div class="mb-3">
                        <label class="form-label text-muted">Description:</label>
                        <div>{{ $apiKey->description }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- API Key Value -->
            <div class="card mb-4">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-code me-2"></i>API Key Value</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Keep this API key secure and never share it publicly.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">API Key:</label>
                        <div class="input-group">
                            <input type="password" 
                                   id="apiKeyValue" 
                                   class="form-control font-monospace" 
                                   value="{{ $apiKey->key_value ?? 'sns_****************************_****' }}" 
                                   readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleApiKeyVisibility()">
                                <i id="toggleIcon" class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard()">
                                <i class="fas fa-copy me-2"></i>Copy
                            </button>
                        </div>
                    </div>

                    @can('regenerate-api-keys')
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-outline-warning" onclick="confirmRegenerate()">
                            <i class="fas fa-sync me-2"></i>Regenerate API Key
                        </button>
                    </div>
                    @endcan
                </div>
            </div>

            <!-- Usage Examples -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-code me-2"></i>Usage Examples</h5>
                </div>
                <div class="card-body">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="exampleTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="curl-tab" data-bs-toggle="tab" data-bs-target="#curl" type="button" role="tab">
                                <i class="fas fa-terminal me-2"></i>cURL
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="javascript-tab" data-bs-toggle="tab" data-bs-target="#javascript" type="button" role="tab">
                                <i class="fab fa-js-square me-2"></i>JavaScript
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="php-tab" data-bs-toggle="tab" data-bs-target="#php" type="button" role="tab">
                                <i class="fab fa-php me-2"></i>PHP
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="python-tab" data-bs-toggle="tab" data-bs-target="#python" type="button" role="tab">
                                <i class="fab fa-python me-2"></i>Python
                            </button>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content mt-3" id="exampleTabsContent">
                        <!-- cURL Example -->
                        <div class="tab-pane fade show active" id="curl" role="tabpanel">
                            <h6 class="fw-bold">Send Notification</h6>
                            <div class="position-relative">
                                <pre class="bg-dark text-light p-3 rounded"><code id="curlCode">curl -X POST {{ config('app.url') }}/api/v1/notifications/send \
  -H "X-API-Key: {{ $apiKey->key_value ?? 'YOUR_API_KEY' }}" \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "System Alert",
    "message": "Database maintenance scheduled at 2 AM",
    "recipients": ["admin@company.com", "it-team@company.com"],
    "channels": ["email", "teams"],
    "priority": "high"
  }'</code></pre>
                                <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2" onclick="copyCode('curlCode')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>

                            <h6 class="fw-bold mt-4">Check Notification Status</h6>
                            <div class="position-relative">
                                <pre class="bg-dark text-light p-3 rounded"><code id="curlStatusCode">curl -H "X-API-Key: {{ $apiKey->key_value ?? 'YOUR_API_KEY' }}" \
  {{ config('app.url') }}/api/v1/notifications/NOTIFICATION_ID/status</code></pre>
                                <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2" onclick="copyCode('curlStatusCode')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- JavaScript Example -->
                        <div class="tab-pane fade" id="javascript" role="tabpanel">
                            <h6 class="fw-bold">Send Notification</h6>
                            <div class="position-relative">
                                <pre class="bg-dark text-light p-3 rounded"><code id="jsCode">const sendNotification = async () => {
  try {
    const response = await fetch('{{ config('app.url') }}/api/v1/notifications/send', {
      method: 'POST',
      headers: {
        'X-API-Key': '{{ $apiKey->key_value ?? 'YOUR_API_KEY' }}',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        subject: 'System Alert',
        message: 'Database maintenance scheduled at 2 AM',
        recipients: ['admin@company.com', 'it-team@company.com'],
        channels: ['email', 'teams'],
        priority: 'high'
      })
    });
    
    const data = await response.json();
    console.log('Notification sent:', data);
  } catch (error) {
    console.error('Error:', error);
  }
};</code></pre>
                                <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2" onclick="copyCode('jsCode')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- PHP Example -->
                        <div class="tab-pane fade" id="php" role="tabpanel">
                            <h6 class="fw-bold">Send Notification</h6>
                            <div class="position-relative">
                                <pre class="bg-dark text-light p-3 rounded"><code id="phpCode">&lt;?php
$apiKey = '{{ $apiKey->key_value ?? 'YOUR_API_KEY' }}';
$url = '{{ config('app.url') }}/api/v1/notifications/send';

$data = [
    'subject' => 'System Alert',
    'message' => 'Database maintenance scheduled at 2 AM',
    'recipients' => ['admin@company.com', 'it-team@company.com'],
    'channels' => ['email', 'teams'],
    'priority' => 'high'
];

$options = [
    'http' => [
        'header' => [
            'X-API-Key: ' . $apiKey,
            'Content-Type: application/json'
        ],
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$response = json_decode($result, true);

echo "Notification sent: " . print_r($response, true);
?&gt;</code></pre>
                                <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2" onclick="copyCode('phpCode')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Python Example -->
                        <div class="tab-pane fade" id="python" role="tabpanel">
                            <h6 class="fw-bold">Send Notification</h6>
                            <div class="position-relative">
                                <pre class="bg-dark text-light p-3 rounded"><code id="pythonCode">import requests
import json

api_key = '{{ $apiKey->key_value ?? 'YOUR_API_KEY' }}'
url = '{{ config('app.url') }}/api/v1/notifications/send'

headers = {
    'X-API-Key': api_key,
    'Content-Type': 'application/json'
}

data = {
    'subject': 'System Alert',
    'message': 'Database maintenance scheduled at 2 AM',
    'recipients': ['admin@company.com', 'it-team@company.com'],
    'channels': ['email', 'teams'],
    'priority': 'high'
}

try:
    response = requests.post(url, headers=headers, json=data)
    response.raise_for_status()
    
    result = response.json()
    print(f"Notification sent: {result}")
    
except requests.exceptions.RequestException as e:
    print(f"Error: {e}")</code></pre>
                                <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2" onclick="copyCode('pythonCode')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- API Endpoints Reference -->
                    <div class="mt-4">
                        <h6 class="fw-bold">Available Endpoints</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Method</th>
                                        <th>Endpoint</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><span class="badge bg-success">POST</span></td>
                                        <td><code>/api/v1/notifications/send</code></td>
                                        <td>Send single notification</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-success">POST</span></td>
                                        <td><code>/api/v1/notifications/bulk</code></td>
                                        <td>Send multiple notifications</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-primary">GET</span></td>
                                        <td><code>/api/v1/notifications/{id}/status</code></td>
                                        <td>Check notification status</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-primary">GET</span></td>
                                        <td><code>/api/v1/notifications/history</code></td>
                                        <td>Get notification history</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-primary">GET</span></td>
                                        <td><code>/api/v1/users</code></td>
                                        <td>List available users</td>
                                    </tr>
                                    <tr>
                                        <td><span class="badge bg-primary">GET</span></td>
                                        <td><code>/api/v1/groups</code></td>
                                        <td>List notification groups</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            @if($apiKey->permissions && $apiKey->permissions->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Permissions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($apiKey->permissions as $permission)
                        <div class="col-md-6 mb-2">
                            <span class="badge bg-info">{{ $permission->display_name }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Usage Statistics -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Usage Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="h4 text-primary">{{ $usageStats['total_requests'] ?? 0 }}</div>
                            <small class="text-muted">TOTAL REQUESTS</small>
                        </div>
                        <div class="col-3">
                            <div class="h4 text-info">{{ $usageStats['today'] ?? 0 }}</div>
                            <small class="text-muted">TODAY</small>
                        </div>
                        <div class="col-3">
                            <div class="h4 text-warning">{{ $usageStats['this_week'] ?? 0 }}</div>
                            <small class="text-muted">THIS WEEK</small>
                        </div>
                        <div class="col-3">
                            <div class="h4 text-success">{{ $usageStats['this_month'] ?? 0 }}</div>
                            <small class="text-muted">THIS MONTH</small>
                        </div>
                    </div>

                    <hr>

                    <div class="row text-center">
                        <div class="col-4">
                            <div class="h5">{{ $performanceStats['avg_response_time'] ?? '0ms' }}</div>
                            <small class="text-muted">AVG RESPONSE TIME</small>
                        </div>
                        <div class="col-4">
                            <div class="h5 text-success">{{ $performanceStats['success_rate'] ?? '100.0%' }}</div>
                            <small class="text-muted">SUCCESS RATE</small>
                        </div>
                        <div class="col-4">
                            <div class="h5">{{ $performanceStats['rate_limit_utilization'] ?? '0.0%' }}</div>
                            <small class="text-muted">RATE LIMIT UTILIZATION</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    @can('view-api-usage')
                    <a href="{{ route('admin.api-keys.usage', $apiKey) }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i class="fas fa-chart-line me-2"></i>View Usage History
                    </a>
                    @endcan
                    
                    @can('view-api-usage')
                    <a href="{{ route('admin.api-keys.audit', $apiKey) }}" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                        <i class="fas fa-clipboard-list me-2"></i>View Audit Log
                    </a>
                    @endcan
                    
                    @can('edit-api-keys')
                    <a href="{{ route('admin.api-keys.edit', $apiKey) }}" class="btn btn-outline-warning btn-sm w-100">
                        <i class="fas fa-cog me-2"></i>Edit Settings
                    </a>
                    @endcan
                </div>
            </div>

            <!-- Usage Trend -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-trending-up me-2"></i>Usage Trend (30 Days)</h5>
                </div>
                <div class="card-body">
                    <!-- Placeholder for chart -->
                    <div class="text-center text-muted">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <p>Usage chart will be displayed here</p>
                    </div>
                </div>
            </div>

            <!-- Configuration -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Auto Notifications:</strong>
                        <span class="text-muted">Disabled</span>
                    </div>
                    <div class="mb-3">
                        <strong>Permissions:</strong>
                        <span class="text-muted">{{ $apiKey->permissions->count() ?? 0 }}</span>
                    </div>
                    <div class="mb-0">
                        <strong>IP Restrictions:</strong>
                        <span class="text-muted">None</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
function toggleApiKeyVisibility() {
    const input = document.getElementById('apiKeyValue');
    const icon = document.getElementById('toggleIcon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function copyToClipboard() {
    const input = document.getElementById('apiKeyValue');
    input.select();
    input.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(input.value).then(function() {
        // Show success message
        showToast('API Key copied to clipboard!', 'success');
    }).catch(function(err) {
        console.error('Could not copy text: ', err);
        showToast('Failed to copy API Key', 'error');
    });
}

function copyCode(elementId) {
    const codeElement = document.getElementById(elementId);
    const text = codeElement.textContent;
    
    navigator.clipboard.writeText(text).then(function() {
        showToast('Code copied to clipboard!', 'success');
    }).catch(function(err) {
        console.error('Could not copy code: ', err);
        showToast('Failed to copy code', 'error');
    });
}

function showToast(message, type = 'success') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
}

function confirmRegenerate() {
    if (confirm('Are you sure you want to regenerate this API key? The current key will be invalidated immediately.')) {
        // Submit regenerate form
        window.location.href = '{{ route('admin.api-keys.regenerate', $apiKey) }}';
    }
}

function confirmDeactivate() {
    if (confirm('Are you sure you want to deactivate this API key? This action cannot be undone.')) {
        // Submit deactivate form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('admin.api-keys.destroy', $apiKey) }}';
        form.innerHTML = '@csrf @method('DELETE')';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<!-- Styles -->
<style>
.font-monospace {
    font-family: 'Courier New', Courier, monospace;
}

pre code {
    font-size: 0.875rem;
    line-height: 1.5;
}

.position-relative .btn {
    border-radius: 0.25rem;
}

.nav-tabs .nav-link {
    color: #6c757d;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}

.alert {
    border-radius: 0.5rem;
}

.card {
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.badge {
    font-size: 0.775em;
}
</style>
@endsection