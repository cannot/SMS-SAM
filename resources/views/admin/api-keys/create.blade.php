@extends('layouts.app')

@section('title', 'Create API Key')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.api-keys.index') }}">API Keys</a></li>
                    <li class="breadcrumb-item active">Create New</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800">Create New API Key</h1>
            <p class="mb-0 text-muted">Create a new API key for external system integration</p>
        </div>
        <div>
            <a href="{{ route('admin.api-keys.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Create Form -->
    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('admin.api-keys.store') }}" method="POST" id="create-api-key-form">
                @csrf
                
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
                                           id="name" name="name" value="{{ old('name') }}" required
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
                                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
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
                                      placeholder="Describe the purpose and usage of this API key">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
                                            <option value="{{ $value }}" {{ old('rate_limit_per_minute', 60) == $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                        <option value="custom">Custom</option>
                                    </select>
                                    <input type="number" class="form-control mt-2 @error('rate_limit_per_minute') is-invalid @enderror" 
                                           id="custom_rate_limit" name="custom_rate_limit" 
                                           min="1" max="10000" style="display: none;"
                                           placeholder="Enter custom rate limit">
                                    @error('rate_limit_per_minute')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Maximum requests per minute</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expires_at">Expiration Date</label>
                                    <input type="date" class="form-control @error('expires_at') is-invalid @enderror" 
                                           id="expires_at" name="expires_at" value="{{ old('expires_at') }}"
                                           min="{{ now()->addDay()->format('Y-m-d') }}">
                                    @error('expires_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Leave empty for no expiration</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="required">Permissions</label>
                            <div class="row">
                                @foreach($availablePermissions as $permission => $description)
                                    <div class="col-md-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" 
                                                   id="permission_{{ $loop->index }}" 
                                                   name="permissions[]" value="{{ $permission }}"
                                                   {{ in_array($permission, old('permissions', [])) ? 'checked' : '' }}>
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
                                      placeholder="Enter IP addresses separated by commas (e.g., 192.168.1.1, 10.0.0.0/24)">{{ old('ip_whitelist') }}</textarea>
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
                                           {{ old('auto_notifications') ? 'checked' : '' }}>
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
                                           value="{{ old('notification_webhook') }}"
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

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> Create API Key
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Right Column - Help & Guidelines -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Security Guidelines</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="fas fa-shield-alt"></i> Important Security Notes
                        </h6>
                        <ul class="mb-0 small">
                            <li>The API key will be shown only once after creation</li>
                            <li>Store the API key securely in your system</li>
                            <li>Never share API keys publicly or in client-side code</li>
                            <li>Use IP whitelist to restrict access when possible</li>
                            <li>Set appropriate rate limits to prevent abuse</li>
                            <li>Regularly rotate API keys for security</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Permission Guide</h6>
                </div>
                <div class="card-body small">
                    <div class="mb-3">
                        <strong>Notification Permissions:</strong>
                        <ul class="mt-1">
                            <li><code>notifications.send</code> - Send single notifications</li>
                            <li><code>notifications.bulk</code> - Send multiple notifications</li>
                            <li><code>notifications.schedule</code> - Schedule notifications</li>
                            <li><code>notifications.status</code> - Check delivery status</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <strong>User & Group Permissions:</strong>
                        <ul class="mt-1">
                            <li><code>users.read</code> - Read user information</li>
                            <li><code>groups.read</code> - Read group information</li>
                            <li><code>groups.manage</code> - Manage group members</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <strong>Template Permissions:</strong>
                        <ul class="mt-1">
                            <li><code>templates.read</code> - Access templates</li>
                            <li><code>templates.render</code> - Render templates</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Rate Limit Recommendations</h6>
                </div>
                <div class="card-body small">
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Development:</strong></td>
                            <td>60/min</td>
                        </tr>
                        <tr>
                            <td><strong>Testing:</strong></td>
                            <td>120/min</td>
                        </tr>
                        <tr>
                            <td><strong>Production (Light):</strong></td>
                            <td>300/min</td>
                        </tr>
                        <tr>
                            <td><strong>Production (Heavy):</strong></td>
                            <td>600/min</td>
                        </tr>
                        <tr>
                            <td><strong>Enterprise:</strong></td>
                            <td>1200/min</td>
                        </tr>
                    </table>
                    <small class="text-muted">
                        Consider your system's capacity and expected load when setting rate limits.
                    </small>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Usage Example</h6>
                </div>
                <div class="card-body">
                    <small>
                        <strong>cURL Example:</strong>
                        <pre class="bg-light p-2 rounded"><code>curl -X POST \
  {{ url('/api/v1/notifications/send') }} \
  -H 'X-API-Key: your-api-key-here' \
  -H 'Content-Type: application/json' \
  -d '{
    "template_id": "welcome",
    "recipients": ["user@example.com"],
    "channels": ["email", "teams"],
    "data": {
      "name": "John Doe"
    }
  }'</code></pre>
                    </small>
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
    
    .custom-control-label strong {
        font-weight: 600;
    }
    
    .form-text {
        font-size: 0.875em;
    }
    
    pre {
        font-size: 0.75rem;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .alert ul {
        padding-left: 1.2rem;
    }
    
    .card-body .table td {
        padding: 0.25rem 0.5rem;
        border: none;
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
    $('#create-api-key-form').submit(function(e) {
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

        // Validate IP whitelist format (basic validation)
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
            .html('<i class="fas fa-spinner fa-spin"></i> Creating...');
    });

    // Auto-suggest expiration date (1 year from now)
    if (!$('#expires_at').val()) {
        const oneYearFromNow = new Date();
        oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
        $('#expires_at').val(oneYearFromNow.toISOString().split('T')[0]);
    }

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
});
</script>
@endpush