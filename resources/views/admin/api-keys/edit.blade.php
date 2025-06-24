@extends('layouts.app')

@section('title', 'Edit API Key - ' . $apiKey->name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.api-keys.index') }}">API Keys</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.api-keys.show', $apiKey) }}">{{ $apiKey->name }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">
                Edit API Key: {{ $apiKey->name }}
                @switch($apiKey->status)
                    @case('active')
                        <span class="badge badge-success ml-2">Active</span>
                        @break
                    @case('inactive')
                        <span class="badge badge-secondary ml-2">Inactive</span>
                        @break
                    @case('expired')
                        <span class="badge badge-danger ml-2">Expired</span>
                        @break
                    @case('expiring_soon')
                        <span class="badge badge-warning ml-2">Expiring Soon</span>
                        @break
                @endswitch
            </h1>
            <p class="mb-0 text-muted">Modify API key settings and permissions</p>
        </div>
        <div>
            <a href="{{ route('admin.api-keys.show', $apiKey) }}" class="btn btn-info mr-2">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="{{ route('admin.api-keys.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Security Warning -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <h6 class="alert-heading"><i class="fas fa-info-circle"></i> API Key Security</h6>
        <p class="mb-0">
            <strong>Note:</strong> The actual API key value cannot be displayed for security reasons. 
            If you need a new key value, use the "Regenerate" function instead.
        </p>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>

    <!-- Edit Form -->
    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.api-keys.update', $apiKey) }}" method="POST" id="edit-api-key-form">
                @csrf
                @method('PUT')
                
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Basic Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="required">API Key Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $apiKey->name) }}" required
                                           placeholder="e.g., Production System, Mobile App">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">A descriptive name to identify this API key</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="assigned_to">Assign To User</label>
                                    <select class="form-control @error('assigned_to') is-invalid @enderror" 
                                            id="assigned_to" name="assigned_to">
                                        <option value="">Select a user (optional)</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" 
                                                {{ old('assigned_to', $apiKey->assigned_to) == $user->id ? 'selected' : '' }}>
                                                {{ $user->display_name }} ({{ $user->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('assigned_to')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">User responsible for this API key</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Describe the purpose and usage of this API key">{{ old('description', $apiKey->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Current API Key Display -->
                        <div class="form-group">
                            <label>Current API Key</label>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" 
                                       value="{{ $apiKey->masked_key }}" readonly>
                                <div class="input-group-append">
                                    <form action="{{ route('admin.api-keys.regenerate', $apiKey) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-warning"
                                                onclick="return confirm('Are you sure you want to regenerate this API key? This will invalidate the current key and any systems using it will need to be updated.')">
                                            <i class="fas fa-sync"></i> Regenerate
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                The actual key value is hidden for security. Click "Regenerate" to create a new key.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Access Control</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rate_limit_per_minute" class="required">Rate Limit</label>
                                    <select class="form-control @error('rate_limit_per_minute') is-invalid @enderror" 
                                            id="rate_limit_per_minute" name="rate_limit_per_minute" required>
                                        @foreach($defaultRateLimits as $value => $label)
                                            <option value="{{ $value }}" 
                                                {{ old('rate_limit_per_minute', $apiKey->rate_limit_per_minute) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                        <option value="custom" 
                                            {{ !in_array(old('rate_limit_per_minute', $apiKey->rate_limit_per_minute), array_keys($defaultRateLimits)) ? 'selected' : '' }}>
                                            Custom
                                        </option>
                                    </select>
                                    <input type="number" class="form-control mt-2 @error('rate_limit_per_minute') is-invalid @enderror" 
                                           id="custom_rate_limit" name="custom_rate_limit" 
                                           min="1" max="10000" 
                                           value="{{ !in_array(old('rate_limit_per_minute', $apiKey->rate_limit_per_minute), array_keys($defaultRateLimits)) ? old('rate_limit_per_minute', $apiKey->rate_limit_per_minute) : '' }}"
                                           style="{{ !in_array(old('rate_limit_per_minute', $apiKey->rate_limit_per_minute), array_keys($defaultRateLimits)) ? 'display: block;' : 'display: none;' }}"
                                           placeholder="Enter custom rate limit">
                                    @error('rate_limit_per_minute')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Maximum requests per minute. Current usage: {{ $apiKey->usage_percentage }}%
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expires_at">Expiration Date</label>
                                    <input type="date" class="form-control @error('expires_at') is-invalid @enderror" 
                                           id="expires_at" name="expires_at" 
                                           value="{{ old('expires_at', $apiKey->expires_at ? $apiKey->expires_at->format('Y-m-d') : '') }}"
                                           min="{{ now()->addDay()->format('Y-m-d') }}">
                                    @error('expires_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Leave empty for no expiration. 
                                        @if($apiKey->expires_at)
                                            Currently expires: {{ $apiKey->expires_at->format('M j, Y') }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="required">Permissions</label>
                            <div class="row">
                                @php $currentPermissions = old('permissions', $apiKey->permissions ?? []); @endphp
                                @foreach($availablePermissions as $permission => $description)
                                    <div class="col-md-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" 
                                                   id="permission_{{ $loop->index }}" 
                                                   name="permissions[]" value="{{ $permission }}"
                                                   {{ in_array($permission, $currentPermissions) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="permission_{{ $loop->index }}">
                                                <strong>{{ $permission }}</strong>
                                                <br><small class="text-muted">{{ $description }}</small>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @error('permissions')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="select-all-permissions">
                                    Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="select-none-permissions">
                                    Select None
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" id="select-common-permissions">
                                    Select Common
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="ip_whitelist">IP Whitelist</label>
                            <textarea class="form-control @error('ip_whitelist') is-invalid @enderror" 
                                      id="ip_whitelist" name="ip_whitelist" rows="3"
                                      placeholder="Enter IP addresses separated by commas (e.g., 192.168.1.1, 10.0.0.0/24)">{{ old('ip_whitelist', $apiKey->ip_whitelist ? implode(', ', $apiKey->ip_whitelist) : '') }}</textarea>
                            @error('ip_whitelist')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Leave empty to allow all IP addresses. Supports CIDR notation.</small>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Notification Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="auto_notifications" name="auto_notifications" value="1"
                                           {{ old('auto_notifications', $apiKey->auto_notifications) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="auto_notifications">
                                        <strong>Auto Notifications</strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Automatically send notifications about API key events (regeneration, status changes, etc.)
                                </small>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notification_webhook">Notification Webhook</label>
                                    <input type="url" class="form-control @error('notification_webhook') is-invalid @enderror" 
                                           id="notification_webhook" name="notification_webhook" 
                                           value="{{ old('notification_webhook', $apiKey->notification_webhook) }}"
                                           placeholder="https://your-system.com/webhook">
                                    @error('notification_webhook')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">URL to receive webhook notifications</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Usage Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Current Usage Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h5 mb-0 font-weight-bold text-primary">
                                        {{ number_format($apiKey->usage_count) }}
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">Total Requests</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h5 mb-0 font-weight-bold text-info">
                                        {{ $apiKey->last_used_at ? $apiKey->last_used_at->diffForHumans() : 'Never' }}
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">Last Used</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h5 mb-0 font-weight-bold text-success">
                                        {{ number_format($apiKey->getSuccessRate(), 1) }}%
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">Success Rate</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="h5 mb-0 font-weight-bold text-warning">
                                        {{ number_format($apiKey->usage_percentage, 1) }}%
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">Current Minute Usage</div>
                                </div>
                            </div>
                        </div>

                        @if($apiKey->usage_count > 0)
                            <div class="alert alert-warning mt-3">
                                <small>
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Note:</strong> This API key has been used {{ number_format($apiKey->usage_count) }} times. 
                                    Changes to permissions or rate limits will take effect immediately and may impact existing integrations.
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <button type="button" class="btn btn-secondary" onclick="history.back()">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <a href="{{ route('admin.api-keys.show', $apiKey) }}" class="btn btn-info ml-2">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update API Key
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Column - Information & Actions -->
        <div class="col-lg-4">
            <!-- Change History -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Change History</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Created</h6>
                                <p class="timeline-text">
                                    {{ $apiKey->created_at->format('M j, Y H:i') }}
                                    <br><small class="text-muted">by {{ $apiKey->createdBy->display_name ?? 'System' }}</small>
                                </p>
                            </div>
                        </div>

                        @if($apiKey->regenerated_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-warning"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Last Regenerated</h6>
                                    <p class="timeline-text">
                                        {{ $apiKey->regenerated_at->format('M j, Y H:i') }}
                                        <br><small class="text-muted">by {{ $apiKey->regeneratedBy->display_name ?? 'System' }}</small>
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if($apiKey->status_changed_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Status Changed</h6>
                                    <p class="timeline-text">
                                        {{ $apiKey->status_changed_at->format('M j, Y H:i') }}
                                        <br><small class="text-muted">by {{ $apiKey->statusChangedBy->display_name ?? 'System' }}</small>
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if($apiKey->usage_reset_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Usage Reset</h6>
                                    <p class="timeline-text">
                                        {{ $apiKey->usage_reset_at->format('M j, Y H:i') }}
                                        <br><small class="text-muted">by {{ $apiKey->usageResetBy->display_name ?? 'System' }}</small>
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="text-center mt-3">
                        <a href="{{ route('admin.api-keys.audit-log') }}?api_key_id={{ $apiKey->id }}" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-history"></i> View Full History
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <form action="{{ route('admin.api-keys.toggle-status', $apiKey) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="list-group-item list-group-item-action"
                                    onclick="return confirm('Are you sure you want to {{ $apiKey->is_active ? 'deactivate' : 'activate' }} this API key?')">
                                <i class="fas fa-{{ $apiKey->is_active ? 'pause' : 'play' }} text-{{ $apiKey->is_active ? 'warning' : 'success' }}"></i>
                                <span class="ml-2">{{ $apiKey->is_active ? 'Deactivate' : 'Activate' }} API Key</span>
                            </button>
                        </form>

                        <form action="{{ route('admin.api-keys.reset-usage', $apiKey) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="list-group-item list-group-item-action"
                                    onclick="return confirm('Are you sure you want to reset usage statistics?')">
                                <i class="fas fa-chart-line text-info"></i>
                                <span class="ml-2">Reset Usage Statistics</span>
                            </button>
                        </form>

                        <a href="{{ route('admin.api-keys.usage-history', $apiKey) }}" 
                           class="list-group-item list-group-item-action">
                            <i class="fas fa-history text-primary"></i>
                            <span class="ml-2">View Usage History</span>
                        </a>

                        <form action="{{ route('admin.api-keys.destroy', $apiKey) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="list-group-item list-group-item-action text-danger"
                                    onclick="return confirm('Are you sure you want to delete this API key? This action cannot be undone and will immediately revoke access for any systems using this key.')">
                                <i class="fas fa-trash text-danger"></i>
                                <span class="ml-2">Delete API Key</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Recommendations -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Security Recommendations</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info small">
                        <ul class="mb-0">
                            <li>Regularly review and update permissions</li>
                            <li>Monitor usage patterns for anomalies</li>
                            <li>Set expiration dates for temporary access</li>
                            <li>Use IP whitelist when possible</li>
                            <li>Regenerate keys periodically</li>
                            <li>Remove unused API keys promptly</li>
                        </ul>
                    </div>

                    @if($apiKey->expires_at && $apiKey->expires_at <= now()->addDays(30))
                        <div class="alert alert-warning small">
                            <i class="fas fa-exclamation-triangle"></i>
                            This API key expires in {{ $apiKey->days_until_expiry }} days. 
                            Consider extending the expiration date if still needed.
                        </div>
                    @endif

                    @if(!$apiKey->ip_whitelist || empty($apiKey->ip_whitelist))
                        <div class="alert alert-warning small">
                            <i class="fas fa-globe"></i>
                            This API key allows access from any IP address. 
                            Consider adding IP restrictions for better security.
                        </div>
                    @endif

                    @if($apiKey->usage_count > 10000)
                        <div class="alert alert-info small">
                            <i class="fas fa-chart-line"></i>
                            This is a high-usage API key ({{ number_format($apiKey->usage_count) }} requests). 
                            Monitor it carefully for security and performance.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .required::after {
        content: " *";
        color: red;
    }
    
    .font-monospace {
        font-family: 'Courier New', Courier, monospace;
    }
    
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }
    
    .timeline-marker {
        position: absolute;
        left: -25px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px #dee2e6;
    }
    
    .timeline-title {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .timeline-text {
        font-size: 0.8rem;
        margin-bottom: 0;
        color: #6c757d;
    }
    
    .list-group-item-action:hover {
        background-color: #f8f9fc;
    }
    
    .alert ul {
        padding-left: 1.2rem;
        margin-bottom: 0;
    }
    
    .custom-control-label strong {
        font-weight: 600;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Handle rate limit selection
    $('#rate_limit_per_minute').change(function() {
        if ($(this).val() === 'custom') {
            $('#custom_rate_limit').show().attr('required', true);
            $(this).removeAttr('name');
            $('#custom_rate_limit').attr('name', 'rate_limit_per_minute');
        } else {
            $('#custom_rate_limit').hide().removeAttr('required');
            $('#custom_rate_limit').removeAttr('name');
            $(this).attr('name', 'rate_limit_per_minute');
        }
    });

    // Permission selection helpers
    $('#select-all-permissions').click(function() {
        $('input[name="permissions[]"]').prop('checked', true);
    });

    $('#select-none-permissions').click(function() {
        $('input[name="permissions[]"]').prop('checked', false);
    });

    $('#select-common-permissions').click(function() {
        $('input[name="permissions[]"]').prop('checked', false);
        
        // Select common permissions
        const commonPermissions = [
            'notifications.send',
            'notifications.status',
            'users.read',
            'groups.read',
            'templates.read',
            'templates.render'
        ];
        
        commonPermissions.forEach(function(permission) {
            $('input[value="' + permission + '"]').prop('checked', true);
        });
    });

    // Form validation
    $('#edit-api-key-form').submit(function(e) {
        // Check if at least one permission is selected
        if ($('input[name="permissions[]"]:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one permission.');
            return false;
        }

        // Validate rate limit
        const rateLimitSelect = $('#rate_limit_per_minute');
        const customRateLimit = $('#custom_rate_limit');
        
        if (rateLimitSelect.val() === 'custom') {
            const customValue = parseInt(customRateLimit.val());
            if (!customValue || customValue < 1 || customValue > 10000) {
                e.preventDefault();
                alert('Please enter a valid custom rate limit between 1 and 10000.');
                customRateLimit.focus();
                return false;
            }
        }

        // Validate IP whitelist format
        const ipWhitelist = $('#ip_whitelist').val().trim();
        if (ipWhitelist) {
            const ips = ipWhitelist.split(',').map(ip => ip.trim());
            const ipRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(?:\/(?:[0-9]|[1-2][0-9]|3[0-2]))?$/;
            
            for (let ip of ips) {
                if (ip && !ipRegex.test(ip)) {
                    e.preventDefault();
                    alert('Please enter valid IP addresses or CIDR notation. Invalid IP: ' + ip);
                    $('#ip_whitelist').focus();
                    return false;
                }
            }
        }

        // Show loading state
        $(this).find('button[type="submit"]').prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Updating...');
    });

    // IP whitelist helper
    $('#ip_whitelist').on('blur', function() {
        let value = $(this).val().trim();
        if (value) {
            // Clean up the input - remove extra spaces and normalize commas
            value = value.replace(/\s*,\s*/g, ', ').replace(/,\s*$/, '');
            $(this).val(value);
        }
    });

    // Character counter for description
    $('#description').on('input', function() {
        const maxLength = 1000;
        const currentLength = $(this).val().length;
        const remaining = maxLength - currentLength;
        
        let counterElement = $(this).siblings('.char-counter');
        if (counterElement.length === 0) {
            counterElement = $('<small class="form-text text-muted char-counter"></small>');
            $(this).after(counterElement);
        }
        
        counterElement.text(`${currentLength}/${maxLength} characters`);
        
        if (remaining < 50) {
            counterElement.removeClass('text-muted').addClass('text-warning');
        } else {
            counterElement.removeClass('text-warning').addClass('text-muted');
        }
    });

    // Warn about changes if API key is actively used
    const originalPermissions = @json($apiKey->permissions ?? []);
    const originalRateLimit = {{ $apiKey->rate_limit_per_minute }};
    const usageCount = {{ $apiKey->usage_count }};

    if (usageCount > 0) {
        $('input[name="permissions[]"], #rate_limit_per_minute, #custom_rate_limit').change(function() {
            showUsageWarning();
        });
    }

    function showUsageWarning() {
        const currentPermissions = $('input[name="permissions[]"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        const currentRateLimit = $('#rate_limit_per_minute').val() === 'custom' 
            ? parseInt($('#custom_rate_limit').val()) || 0
            : parseInt($('#rate_limit_per_minute').val()) || 0;

        const permissionsChanged = JSON.stringify(originalPermissions.sort()) !== JSON.stringify(currentPermissions.sort());
        const rateLimitChanged = originalRateLimit !== currentRateLimit;

        if (permissionsChanged || rateLimitChanged) {
            if (!$('.usage-warning').length) {
                const warning = $(`
                    <div class="alert alert-warning usage-warning mt-2">
                        <small>
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Warning:</strong> You have modified critical settings. 
                            These changes will take effect immediately and may impact existing integrations.
                        </small>
                    </div>
                `);
                $('#edit-api-key-form .card:last-child .card-body').prepend(warning);
            }
        } else {
            $('.usage-warning').remove();
        }
    }

    // Initialize expiration date helper
    if (!$('#expires_at').val() && {{ $apiKey->expires_at ? 'false' : 'true' }}) {
        // Suggest 1 year from now if no expiration is set
        const oneYearFromNow = new Date();
        oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
        
        const suggestButton = $(`
            <button type="button" class="btn btn-sm btn-outline-info mt-2" id="suggest-expiry">
                Suggest: 1 year from now
            </button>
        `);
        
        $('#expires_at').after(suggestButton);
        
        $('#suggest-expiry').click(function() {
            $('#expires_at').val(oneYearFromNow.toISOString().split('T')[0]);
            $(this).remove();
        });
    }
});
</script>
@endpush