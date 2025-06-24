@extends('layouts.app')

@section('title', 'User Preferences - ' . $user->display_name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @if($user->id !== auth()->id())
                        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('users.show', $user) }}">{{ $user->display_name }}</a></li>
                    @endif
                    <li class="breadcrumb-item active">Preferences</li>
                </ol>
            </nav>
            <h2><i class="fas fa-cog"></i> Notification Preferences</h2>
            <p class="text-muted">
                @if($user->id === auth()->id())
                    Configure your notification settings and preferences
                @else
                    Configure notification settings for {{ $user->display_name }}
                @endif
            </p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#testNotificationModal">
                    <i class="fas fa-paper-plane"></i> Test Notification
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importExportModal">
                    <i class="fas fa-download"></i> Import/Export
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="resetPreferences()">
                    <i class="fas fa-undo"></i> Reset to Default
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Preferences Form -->
            <form method="POST" action="{{ $user->id === auth()->id() ? route('users.preferences.update') : route('users.preferences.update.user', $user) }}" id="preferencesForm">
                @csrf
                @method('PATCH')

                <!-- Notification Channels -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-broadcast-tower"></i> Notification Channels</h5>
                        <small class="text-muted">Choose how you want to receive notifications</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_email" name="enable_email" 
                                           {{ $preferences->enable_email ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enable_email">
                                        <i class="fas fa-envelope text-primary"></i> Email Notifications
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">Receive notifications via email</small>

                                <div id="emailSettings" class="mt-3" style="display: {{ $preferences->enable_email ? 'block' : 'none' }};">
                                    <div class="mb-3">
                                        <label for="email_address" class="form-label">Email Address (optional)</label>
                                        <input type="email" class="form-control" id="email_address" name="email_address" 
                                               value="{{ $preferences->email_address }}" 
                                               placeholder="{{ $user->email }}">
                                        <small class="form-text text-muted">Leave blank to use account email</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email_format" class="form-label">Email Format</label>
                                        <select class="form-select" id="email_format" name="email_format">
                                            <option value="html" {{ $preferences->email_format === 'html' ? 'selected' : '' }}>HTML (Rich)</option>
                                            <option value="plain" {{ $preferences->email_format === 'plain' ? 'selected' : '' }}>Plain Text</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable_teams" name="enable_teams" 
                                           {{ $preferences->enable_teams ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enable_teams">
                                        <i class="fab fa-microsoft text-info"></i> Microsoft Teams
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">Receive notifications in Teams</small>

                                <div id="teamsSettings" class="mt-3" style="display: {{ $preferences->enable_teams ? 'block' : 'none' }};">
                                    <div class="mb-3">
                                        <label for="teams_user_id" class="form-label">Teams User ID (optional)</label>
                                        <input type="text" class="form-control" id="teams_user_id" name="teams_user_id" 
                                               value="{{ $preferences->teams_user_id }}" 
                                               placeholder="{{ $user->username }}">
                                        <small class="form-text text-muted">Leave blank to use username</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="teams_channel_preference" class="form-label">Delivery Method</label>
                                        <select class="form-select" id="teams_channel_preference" name="teams_channel_preference">
                                            <option value="direct" {{ $preferences->teams_channel_preference === 'direct' ? 'selected' : '' }}>Direct Message</option>
                                            <option value="channel" {{ $preferences->teams_channel_preference === 'channel' ? 'selected' : '' }}>Channel Message</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification Filtering -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-filter"></i> Notification Filtering</h5>
                        <small class="text-muted">Control which notifications you receive</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="min_priority" class="form-label">Minimum Priority</label>
                                    <select class="form-select" id="min_priority" name="min_priority">
                                        <option value="low" {{ $preferences->min_priority === 'low' ? 'selected' : '' }}>
                                            <span class="badge bg-info">Low</span> - All notifications
                                        </option>
                                        <option value="medium" {{ $preferences->min_priority === 'medium' ? 'selected' : '' }}>
                                            <span class="badge bg-warning">Medium</span> - Medium and above
                                        </option>
                                        <option value="high" {{ $preferences->min_priority === 'high' ? 'selected' : '' }}>
                                            <span class="badge bg-danger">High</span> - High and critical only
                                        </option>
                                        <option value="critical" {{ $preferences->min_priority === 'critical' ? 'selected' : '' }}>
                                            <span class="badge bg-dark">Critical</span> - Critical only
                                        </option>
                                    </select>
                                    <small class="form-text text-muted">Only receive notifications at or above this priority level</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Additional Options</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="auto_mark_read" name="auto_mark_read" 
                                               {{ $preferences->auto_mark_read ? 'checked' : '' }}>
                                        <label class="form-check-label" for="auto_mark_read">
                                            Auto-mark as read after delivery
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="show_preview" name="show_preview" 
                                               {{ $preferences->show_preview ? 'checked' : '' }}>
                                        <label class="form-check-label" for="show_preview">
                                            Show message preview
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="notification_sound" name="notification_sound" 
                                               {{ $preferences->notification_sound ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notification_sound">
                                            Enable notification sounds
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Grouping -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-layer-group"></i> Message Grouping</h5>
                        <small class="text-muted">How to group multiple notifications</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="enable_grouping" name="enable_grouping" 
                                           {{ $preferences->enable_grouping ? 'checked' : '' }}>
                                    <label class="form-check-label" for="enable_grouping">
                                        Enable Message Grouping
                                    </label>
                                </div>
                                <small class="text-muted">Group similar notifications together to reduce clutter</small>
                            </div>
                            <div class="col-md-6">
                                <div id="groupingSettings" style="display: {{ $preferences->enable_grouping ? 'block' : 'none' }};">
                                    <label for="grouping_method" class="form-label">Grouping Method</label>
                                    <select class="form-select" id="grouping_method" name="grouping_method">
                                        <option value="sender" {{ $preferences->grouping_method === 'sender' ? 'selected' : '' }}>By Sender</option>
                                        <option value="priority" {{ $preferences->grouping_method === 'priority' ? 'selected' : '' }}>By Priority</option>
                                        <option value="time" {{ $preferences->grouping_method === 'time' ? 'selected' : '' }}>By Time Period</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Digest Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-newspaper"></i> Digest Settings</h5>
                        <small class="text-muted">Receive summary of notifications periodically</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="digest_frequency" class="form-label">Digest Frequency</label>
                                    <select class="form-select" id="digest_frequency" name="digest_frequency">
                                        <option value="none" {{ $preferences->digest_frequency === 'none' ? 'selected' : '' }}>Disabled</option>
                                        <option value="daily" {{ $preferences->digest_frequency === 'daily' ? 'selected' : '' }}>Daily</option>
                                        <option value="weekly" {{ $preferences->digest_frequency === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="digestTimeSettings" style="display: {{ $preferences->digest_frequency !== 'none' ? 'block' : 'none' }};">
                                    <label for="digest_time" class="form-label">Digest Time</label>
                                    <input type="time" class="form-control" id="digest_time" name="digest_time" 
                                           value="{{ $preferences->digest_time }}">
                                    <small class="form-text text-muted">Time to send digest (in your timezone)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Localization -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-globe"></i> Localization</h5>
                        <small class="text-muted">Language and timezone preferences</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="language" class="form-label">Language</label>
                                    <select class="form-select" id="language" name="language">
                                        <option value="th" {{ $preferences->language === 'th' ? 'selected' : '' }}>ไทย (Thai)</option>
                                        <option value="en" {{ $preferences->language === 'en' ? 'selected' : '' }}>English</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">Timezone</label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        @foreach($timezones as $value => $label)
                                            <option value="{{ $value }}" {{ $preferences->timezone === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Preferences
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-lg ms-2" onclick="resetForm()">
                            <i class="fas fa-undo"></i> Reset Form
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar with Tips -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Tips & Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb text-warning"></i> Quick Tips</h6>
                        <ul class="small">
                            <li>Enable both email and Teams for important notifications</li>
                            <li>Set minimum priority to reduce notification noise</li>
                            <li>Use digest mode for non-urgent updates</li>
                            <li>Test your settings after making changes</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <h6><i class="fas fa-shield-alt text-primary"></i> Privacy</h6>
                        <p class="small text-muted">
                            Your notification preferences are private and only accessible by administrators for support purposes.
                        </p>
                    </div>

                    <div class="mb-3">
                        <h6><i class="fas fa-clock text-info"></i> Delivery Times</h6>
                        <p class="small text-muted">
                            Most notifications are delivered within 1-2 minutes. Emergency notifications are delivered immediately.
                        </p>
                    </div>

                    <div>
                        <h6><i class="fas fa-question-circle text-secondary"></i> Need Help?</h6>
                        <p class="small text-muted">
                            Contact IT support if you're not receiving notifications or need assistance with your settings.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Notification Modal -->
<div class="modal fade" id="testNotificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Test Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ $user->id === auth()->id() ? route('users.preferences.test') : route('users.preferences.test.user', $user) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Test Channels:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="test_email">
                            <label class="form-check-label" for="test_email">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="teams" id="test_teams">
                            <label class="form-check-label" for="test_teams">
                                <i class="fab fa-microsoft"></i> Microsoft Teams
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="test_priority" class="form-label">Priority:</label>
                        <select class="form-select" id="test_priority" name="priority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="test_message" class="form-label">Custom Message (optional):</label>
                        <textarea class="form-control" id="test_message" name="message" rows="3" 
                                  placeholder="Enter a custom test message..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Test</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import/Export Modal -->
<div class="modal fade" id="importExportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import/Export Preferences</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-download"></i> Export</h6>
                        <p class="small text-muted">Download your current preferences as a JSON file.</p>
                        <a href="{{ $user->id === auth()->id() ? route('users.preferences.export') : route('users.preferences.export.user', $user) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download"></i> Export Preferences
                        </a>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-upload"></i> Import</h6>
                    <div class="col-md-6">
                        <h6><i class="fas fa-upload"></i> Import</h6>
                        <form method="POST" action="{{ $user->id === auth()->id() ? route('users.preferences.import') : route('users.preferences.import.user', $user) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <input type="file" class="form-control form-control-sm" name="preferences_file" accept=".json">
                                <small class="form-text text-muted">Upload a JSON preferences file</small>
                            </div>
                            <button type="submit" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-upload"></i> Import Preferences
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Reset Form -->
<form id="resetForm" method="POST" action="{{ $user->id === auth()->id() ? route('users.preferences.reset') : route('users.preferences.reset.user', $user) }}" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('styles')
<style>
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.badge {
    font-size: 0.75em;
}

.btn-group .btn {
    border-radius: 0.375rem;
    margin-right: 0.25rem;
}

.btn-group .btn:last-child {
    margin-right: 0;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle email notification toggle
    const emailToggle = document.getElementById('enable_email');
    const emailSettings = document.getElementById('emailSettings');
    
    emailToggle.addEventListener('change', function() {
        emailSettings.style.display = this.checked ? 'block' : 'none';
    });

    // Handle teams notification toggle
    const teamsToggle = document.getElementById('enable_teams');
    const teamsSettings = document.getElementById('teamsSettings');
    
    teamsToggle.addEventListener('change', function() {
        teamsSettings.style.display = this.checked ? 'block' : 'none';
    });

    // Handle grouping toggle
    const groupingToggle = document.getElementById('enable_grouping');
    const groupingSettings = document.getElementById('groupingSettings');
    
    groupingToggle.addEventListener('change', function() {
        groupingSettings.style.display = this.checked ? 'block' : 'none';
    });

    // Handle digest frequency
    const digestFrequency = document.getElementById('digest_frequency');
    const digestTimeSettings = document.getElementById('digestTimeSettings');
    
    digestFrequency.addEventListener('change', function() {
        digestTimeSettings.style.display = this.value !== 'none' ? 'block' : 'none';
    });

    // Form validation
    const form = document.getElementById('preferencesForm');
    form.addEventListener('submit', function(e) {
        const emailEnabled = document.getElementById('enable_email').checked;
        const teamsEnabled = document.getElementById('enable_teams').checked;
        
        if (!emailEnabled && !teamsEnabled) {
            e.preventDefault();
            alert('Please enable at least one notification channel (Email or Teams).');
            return false;
        }
    });

    // Auto-save functionality (optional)
    let autoSaveTimeout;
    const formInputs = form.querySelectorAll('input, select, textarea');
    
    formInputs.forEach(input => {
        input.addEventListener('change', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                showAutoSaveIndicator();
            }, 2000);
        });
    });
});

function resetPreferences() {
    if (confirm('Are you sure you want to reset all preferences to default? This cannot be undone.')) {
        document.getElementById('resetForm').submit();
    }
}

function resetForm() {
    if (confirm('Are you sure you want to reset the form to the last saved values?')) {
        location.reload();
    }
}

function showAutoSaveIndicator() {
    // Show a small indicator that changes were detected
    const indicator = document.createElement('div');
    indicator.className = 'alert alert-info alert-dismissible fade show position-fixed';
    indicator.style.cssText = 'top: 20px; right: 20px; z-index: 1050; width: 300px;';
    indicator.innerHTML = `
        <i class="fas fa-info-circle"></i> Changes detected. Don't forget to save your preferences.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(indicator);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (indicator.parentNode) {
            indicator.remove();
        }
    }, 5000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+S to save
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.getElementById('preferencesForm').submit();
    }
    
    // Ctrl+R to reset form
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        resetForm();
    }
});

// Show loading state on form submission
document.getElementById('preferencesForm').addEventListener('submit', function() {
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;
});
</script>
@endsection