@extends('layouts.app')

@section('title', 'Create Notification')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Create New Notification</h1>
            <p class="mb-0 text-muted">Send notifications to users via Teams and Email</p>
        </div>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <form method="POST" action="{{ route('admin.notifications.store') }}" id="notificationForm">
        @csrf
        
        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-edit"></i> Notification Content
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Template Selection -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="template_id" class="form-label">Use Template (Optional)</label>
                                <select class="form-control @error('template_id') is-invalid @enderror" 
                                        id="template_id" name="template_id" onchange="loadTemplate()">
                                    <option value="">Create Custom Notification</option>
                                    @foreach($templates as $template)
                                        <option value="{{ $template->id }}" 
                                                data-title="{{ $template->title }}"
                                                data-content="{{ $template->content }}"
                                                {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                            {{ $template->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('template_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Select a template to pre-fill content, or create a custom notification
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-control @error('priority') is-invalid @enderror" 
                                        id="priority" name="priority" required>
                                    @foreach($priorities as $priority)
                                        <option value="{{ $priority }}" 
                                                {{ old('priority', 'normal') == $priority ? 'selected' : '' }}>
                                            {{ ucfirst($priority) }}
                                            @if($priority == 'urgent') - Immediate delivery @endif
                                            @if($priority == 'high') - High priority queue @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                   id="title" name="title" value="{{ old('title') }}" 
                                   placeholder="Enter notification title" required maxlength="255">
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <span id="titleCounter">0</span>/255 characters
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="mb-3">
                            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                            <div class="form-group">
                                <div class="btn-group btn-group-sm mb-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertVariable('{{ $templateVariables['name'] }}')" title="Insert Name Variable">
                                        <i class="fas fa-user"></i> Name
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertVariable('{{ $templateVariables['email'] }}')" title="Insert Email Variable">
                                        <i class="fas fa-envelope"></i> Email
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertVariable('{{ $templateVariables['department'] }}')" title="Insert Department Variable">
                                        <i class="fas fa-building"></i> Department
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertVariable('{{ $templateVariables['date'] }}')" title="Insert Current Date">
                                        <i class="fas fa-calendar"></i> Date
                                    </button>
                                </div>
                                <textarea class="form-control @error('content') is-invalid @enderror" 
                                          id="content" name="content" rows="8" required 
                                          placeholder="Enter notification content. You can use variables like {{$templateVariables['name']}}, {{$templateVariables['email']}}, {{$templateVariables['department']}}">{{ old('content') }}</textarea>
                                @error('content')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    Use variables in double braces like {{ $templateVariables['name'] }} for personalization. Markdown supported.
                                </small>
                            </div>
                        </div>

                        <!-- Custom Data (JSON) -->
                        <div class="mb-3">
                            <label for="data" class="form-label">Custom Data (Optional)</label>
                            <textarea class="form-control @error('data') is-invalid @enderror" 
                                      id="data" name="data" rows="3" 
                                      placeholder='{"key": "value", "another_key": "another_value"}'>{{ old('data') }}</textarea>
                            @error('data')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Additional data in JSON format for template variables and integrations
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Recipients -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-users"></i> Recipients
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Select Recipients <span class="text-danger">*</span></label>
                                
                                <!-- Tab Navigation -->
                                <ul class="nav nav-tabs mb-3" id="recipientTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="groups-tab" data-toggle="tab" href="#groups" role="tab">
                                            <i class="fas fa-users"></i> Groups
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="users-tab" data-toggle="tab" href="#users" role="tab">
                                            <i class="fas fa-user"></i> Individual Users
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="emails-tab" data-toggle="tab" href="#emails" role="tab">
                                            <i class="fas fa-envelope"></i> Email Addresses
                                        </a>
                                    </li>
                                </ul>

                                <!-- Tab Content -->
                                <div class="tab-content" id="recipientTabContent">
                                    <!-- Groups Tab -->
                                    <div class="tab-pane fade show active" id="groups" role="tabpanel">
                                        <div class="form-group">
                                            <label for="groupSelect" class="form-label">Select Groups</label>
                                            <select class="form-control select2" id="groupSelect" multiple="multiple" 
                                                    style="width: 100%;" data-placeholder="Choose groups...">
                                                @foreach($groups as $group)
                                                    <option value="{{ $group->id }}" data-type="group">
                                                        {{ $group->name }} ({{ $group->users->count() }} members)
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">
                                                Select notification groups. Members will be automatically included.
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Individual Users Tab -->
                                    <div class="tab-pane fade" id="users" role="tabpanel">
                                        <div class="form-group">
                                            <label for="userSelect" class="form-label">Select Users</label>
                                            <select class="form-control select2" id="userSelect" multiple="multiple" 
                                                    style="width: 100%;" data-placeholder="Choose users...">
                                                @foreach($users as $user)
                                                    <option value="{{ $user['id'] }}" data-type="user">
                                                        {{ $user['name'] }} ({{ $user['email'] }})
                                                        @if(isset($user['department'])) - {{ $user['department'] }} @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted">
                                                Select individual users from LDAP directory.
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Email Addresses Tab -->
                                    <div class="tab-pane fade" id="emails" role="tabpanel">
                                        <div class="form-group">
                                            <label for="emailList" class="form-label">Email Addresses</label>
                                            <textarea class="form-control" id="emailList" rows="4" 
                                                      placeholder="Enter email addresses (one per line or comma-separated)"></textarea>
                                            <small class="form-text text-muted">
                                                Enter email addresses for external recipients not in LDAP.
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Selected Recipients Display -->
                                <div class="mt-3">
                                    <label class="form-label">Selected Recipients</label>
                                    <div id="selectedRecipients" class="border rounded p-3 bg-light min-height-100">
                                        <span class="text-muted">No recipients selected</span>
                                    </div>
                                    <small class="form-text text-muted">
                                        Total recipients: <span id="recipientCount">0</span>
                                    </small>
                                </div>

                                <!-- Hidden input for form submission -->
                                <input type="hidden" id="recipients" name="recipients" value="">
                                @error('recipients')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Delivery Settings -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-cog"></i> Delivery Settings
                        </h6>
                    </div>
                    <div class="card-body">
                        <!-- Channels -->
                        <div class="mb-3">
                            <label class="form-label">Delivery Channels <span class="text-danger">*</span></label>
                            @foreach($channels as $channel)
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input @error('channels') is-invalid @enderror" 
                                           id="channel_{{ $channel }}" name="channels[]" value="{{ $channel }}"
                                           {{ in_array($channel, old('channels', [])) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="channel_{{ $channel }}">
                                        <i class="fas fa-{{ $channel == 'teams' ? 'users' : 'envelope' }}"></i>
                                        {{ ucfirst($channel) }}
                                        @if($channel == 'teams')
                                            <small class="text-muted d-block">Microsoft Teams messages</small>
                                        @else
                                            <small class="text-muted d-block">Email notifications</small>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                            @error('channels')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Scheduling -->
                        <div class="mb-3">
                            <label class="form-label">Delivery Schedule</label>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="send_now" name="schedule_type" value="now" 
                                       class="custom-control-input" checked onchange="toggleSchedule()">
                                <label class="custom-control-label" for="send_now">
                                    Send Immediately
                                </label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="send_later" name="schedule_type" value="later" 
                                       class="custom-control-input" onchange="toggleSchedule()">
                                <label class="custom-control-label" for="send_later">
                                    Schedule for Later
                                </label>
                            </div>
                        </div>

                        <!-- Schedule DateTime -->
                        <div class="mb-3" id="scheduleDateTime" style="display: none;">
                            <label for="scheduled_at" class="form-label">Scheduled Date & Time</label>
                            <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" 
                                   id="scheduled_at" name="scheduled_at" value="{{ old('scheduled_at') }}"
                                   min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Minimum 5 minutes from now
                            </small>
                        </div>

                        <!-- Preview Button -->
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-info btn-block" onclick="previewNotification()">
                                <i class="fas fa-eye"></i> Preview Notification
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Summary Card -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-info-circle"></i> Summary
                        </h6>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-6">Priority:</dt>
                            <dd class="col-sm-6" id="summaryPriority">Normal</dd>
                            
                            <dt class="col-sm-6">Channels:</dt>
                            <dd class="col-sm-6" id="summaryChannels">None</dd>
                            
                            <dt class="col-sm-6">Recipients:</dt>
                            <dd class="col-sm-6" id="summaryRecipients">0</dd>
                            
                            <dt class="col-sm-6">Schedule:</dt>
                            <dd class="col-sm-6" id="summarySchedule">Immediate</dd>
                        </dl>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card shadow">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fas fa-paper-plane"></i> Create & Send Notification
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-block mt-2" onclick="saveDraft()">
                            <i class="fas fa-save"></i> Save as Draft
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye"></i> Notification Preview
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Preview will be loaded here -->
                <div id="previewContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="submitForm()">
                    Looks Good - Send Notification
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.min-height-100 {
    min-height: 100px;
}

.select2-container--default .select2-selection--multiple {
    border: 1px solid #d1d3e2;
    border-radius: 0.35rem;
}

.recipient-tag {
    display: inline-block;
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 15px;
    padding: 4px 12px;
    margin: 2px;
    font-size: 0.875rem;
}

.recipient-tag .remove-btn {
    margin-left: 6px;
    cursor: pointer;
    color: #f44336;
}

.preview-section {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.preview-section h6 {
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-text {
    color: #6c757d;
}

.nav-tabs .nav-link.active {
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
let selectedRecipients = [];

$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });

    // Initialize form events
    initializeFormEvents();
    
    // Load saved draft if exists
    loadDraft();
});

function initializeFormEvents() {
    // Character counter for title
    $('#title').on('input', function() {
        const length = $(this).val().length;
        $('#titleCounter').text(length);
        
        if (length > 240) {
            $('#titleCounter').addClass('text-warning');
        } else {
            $('#titleCounter').removeClass('text-warning');
        }
    });

    // Update summary when form changes
    $('#priority').on('change', updateSummary);
    $('input[name="channels[]"]').on('change', updateSummary);
    $('input[name="schedule_type"]').on('change', updateSummary);
    $('#scheduled_at').on('change', updateSummary);

    // Recipient selection events
    $('#groupSelect').on('change', function() {
        const selectedGroups = $(this).val();
        updateSelectedRecipients('group', selectedGroups);
    });

    $('#userSelect').on('change', function() {
        const selectedUsers = $(this).val();
        updateSelectedRecipients('user', selectedUsers);
    });

    $('#emailList').on('input', function() {
        const emails = parseEmailList($(this).val());
        updateSelectedRecipients('email', emails);
    });

    // Form validation
    $('#notificationForm').on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });
}

function loadTemplate() {
    const templateSelect = $('#template_id');
    const selectedOption = templateSelect.find('option:selected');
    
    if (selectedOption.val()) {
        $('#title').val(selectedOption.data('title') || '');
        $('#content').val(selectedOption.data('content') || '');
        updateSummary();
    }
}

function toggleSchedule() {
    const scheduleType = $('input[name="schedule_type"]:checked').val();
    const scheduleDiv = $('#scheduleDateTime');
    
    if (scheduleType === 'later') {
        scheduleDiv.show();
        $('#scheduled_at').prop('required', true);
    } else {
        scheduleDiv.hide();
        $('#scheduled_at').prop('required', false);
    }
    
    updateSummary();
}

function updateSelectedRecipients(type, values) {
    // Remove existing recipients of this type
    selectedRecipients = selectedRecipients.filter(r => r.type !== type);
    
    // Add new recipients
    if (values && values.length > 0) {
        values.forEach(value => {
            if (value.trim()) {
                selectedRecipients.push({
                    type: type,
                    id: value,
                    display: getDisplayName(type, value)
                });
            }
        });
    }
    
    updateRecipientsDisplay();
    updateSummary();
}

function getDisplayName(type, value) {
    if (type === 'group') {
        const option = $(`#groupSelect option[value="${value}"]`);
        return option.text();
    } else if (type === 'user') {
        const option = $(`#userSelect option[value="${value}"]`);
        return option.text();
    } else if (type === 'email') {
        return value;
    }
    return value;
}

function updateRecipientsDisplay() {
    const container = $('#selectedRecipients');
    
    if (selectedRecipients.length === 0) {
        container.html('<span class="text-muted">No recipients selected</span>');
    } else {
        let html = '';
        selectedRecipients.forEach((recipient, index) => {
            const iconClass = recipient.type === 'group' ? 'users' : 
                            (recipient.type === 'user' ? 'user' : 'envelope');
            html += `
                <span class="recipient-tag">
                    <i class="fas fa-${iconClass}"></i> ${recipient.display}
                    <span class="remove-btn" onclick="removeRecipient(${index})">&times;</span>
                </span>
            `;
        });
        container.html(html);
    }
    
    // Update hidden input
    $('#recipients').val(JSON.stringify(selectedRecipients));
    
    // Update count
    $('#recipientCount').text(selectedRecipients.length);
}

function removeRecipient(index) {
    selectedRecipients.splice(index, 1);
    updateRecipientsDisplay();
    updateSummary();
}

function parseEmailList(text) {
    if (!text.trim()) return [];
    
    return text.split(/[,\n]/)
               .map(email => email.trim())
               .filter(email => email && isValidEmail(email));
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function updateSummary() {
    // Priority
    const priority = $('#priority').val();
    $('#summaryPriority').text(priority.charAt(0).toUpperCase() + priority.slice(1));
    
    // Channels
    const channels = $('input[name="channels[]"]:checked').map(function() {
        return this.value.charAt(0).toUpperCase() + this.value.slice(1);
    }).get();
    $('#summaryChannels').text(channels.join(', ') || 'None');
    
    // Recipients
    $('#summaryRecipients').text(selectedRecipients.length);
    
    // Schedule
    const scheduleType = $('input[name="schedule_type"]:checked').val();
    if (scheduleType === 'later') {
        const scheduleDate = $('#scheduled_at').val();
        if (scheduleDate) {
            const date = new Date(scheduleDate);
            $('#summarySchedule').text(date.toLocaleString());
        } else {
            $('#summarySchedule').text('Not set');
        }
    } else {
        $('#summarySchedule').text('Immediate');
    }
}

function insertVariable(variable) {
    const content = $('#content');
    const pos = content[0].selectionStart;
    const val = content.val();
    
    content.val(val.slice(0, pos) + variable + val.slice(pos));
    content.focus();
    
    // Set cursor after inserted variable
    content[0].setSelectionRange(pos + variable.length, pos + variable.length);
}

function previewNotification() {
    if (!validateForm()) {
        return;
    }
    
    const formData = {
        title: $('#title').val(),
        content: $('#content').val(),
        priority: $('#priority').val(),
        channels: $('input[name="channels[]"]:checked').map(function() { return this.value; }).get(),
        recipients: selectedRecipients
    };
    
    // Generate preview HTML
    let previewHtml = '';
    
    // Email Preview
    if (formData.channels.includes('email')) {
        previewHtml += `
            <div class="preview-section">
                <h6><i class="fas fa-envelope"></i> Email Preview</h6>
                <div class="border rounded p-3" style="background: #f8f9fa;">
                    <strong>Subject:</strong> ${formData.title}<br>
                    <strong>Priority:</strong> <span class="badge badge-${getPriorityClass(formData.priority)}">${formData.priority.toUpperCase()}</span><br><br>
                    <div style="white-space: pre-wrap;">${formData.content}</div>
                </div>
            </div>
        `;
    }
    
    // Teams Preview
    if (formData.channels.includes('teams')) {
        previewHtml += `
            <div class="preview-section">
                <h6><i class="fas fa-users"></i> Microsoft Teams Preview</h6>
                <div class="border rounded p-3" style="background: #f3f2f1;">
                    <div class="font-weight-bold mb-2">${formData.title}</div>
                    <div style="white-space: pre-wrap;">${formData.content}</div>
                    <small class="text-muted mt-2 d-block">Priority: ${formData.priority.toUpperCase()}</small>
                </div>
            </div>
        `;
    }
    
    // Recipients Preview
    previewHtml += `
        <div class="preview-section">
            <h6><i class="fas fa-users"></i> Recipients (${formData.recipients.length})</h6>
            <div class="border rounded p-3">
                ${formData.recipients.map(r => `<span class="badge badge-secondary mr-1">${r.display}</span>`).join('')}
            </div>
        </div>
    `;
    
    $('#previewContent').html(previewHtml);
    $('#previewModal').modal('show');
}

function getPriorityClass(priority) {
    switch(priority) {
        case 'urgent': return 'danger';
        case 'high': return 'warning';
        case 'normal': return 'primary';
        case 'low': return 'secondary';
        default: return 'secondary';
    }
}

function validateForm() {
    let isValid = true;
    
    // Check required fields
    if (!$('#title').val().trim()) {
        showError('#title', 'Title is required');
        isValid = false;
    }
    
    if (!$('#content').val().trim()) {
        showError('#content', 'Content is required');
        isValid = false;
    }
    
    if ($('input[name="channels[]"]:checked').length === 0) {
        showError('input[name="channels[]"]', 'At least one channel must be selected');
        isValid = false;
    }
    
    if (selectedRecipients.length === 0) {
        showError('#recipients', 'At least one recipient must be selected');
        isValid = false;
    }
    
    // Validate schedule if set
    if ($('input[name="schedule_type"]:checked').val() === 'later') {
        const scheduleDate = new Date($('#scheduled_at').val());
        const now = new Date();
        
        if (scheduleDate <= now) {
            showError('#scheduled_at', 'Scheduled time must be in the future');
            isValid = false;
        }
    }
    
    return isValid;
}

function showError(selector, message) {
    const element = $(selector);
    element.addClass('is-invalid');
    
    // Remove existing error message
    element.siblings('.invalid-feedback').remove();
    
    // Add error message
    element.after(`<div class="invalid-feedback">${message}</div>`);
    
    // Remove error class after user interaction
    element.one('input change', function() {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').remove();
    });
}

function submitForm() {
    $('#previewModal').modal('hide');
    $('#notificationForm').submit();
}

function saveDraft() {
    const draftData = {
        template_id: $('#template_id').val(),
        title: $('#title').val(),
        content: $('#content').val(),
        priority: $('#priority').val(),
        channels: $('input[name="channels[]"]:checked').map(function() { return this.value; }).get(),
        recipients: selectedRecipients,
        schedule_type: $('input[name="schedule_type"]:checked').val(),
        scheduled_at: $('#scheduled_at').val(),
        data: $('#data').val()
    };
    
    localStorage.setItem('notificationDraft', JSON.stringify(draftData));
    
    // Show success message
    showToast('Draft saved successfully!', 'success');
}

function loadDraft() {
    const draftData = localStorage.getItem('notificationDraft');
    if (draftData) {
        try {
            const draft = JSON.parse(draftData);
            
            // Show option to load draft
            if (confirm('Found a saved draft. Would you like to load it?')) {
                $('#template_id').val(draft.template_id || '');
                $('#title').val(draft.title || '');
                $('#content').val(draft.content || '');
                $('#priority').val(draft.priority || 'normal');
                $('#data').val(draft.data || '');
                
                // Set channels
                draft.channels.forEach(channel => {
                    $(`#channel_${channel}`).prop('checked', true);
                });
                
                // Set schedule
                if (draft.schedule_type === 'later') {
                    $('#send_later').prop('checked', true);
                    $('#scheduled_at').val(draft.scheduled_at || '');
                    toggleSchedule();
                }
                
                // Set recipients
                selectedRecipients = draft.recipients || [];
                updateRecipientsDisplay();
                updateSummary();
                
                // Clear draft
                localStorage.removeItem('notificationDraft');
                showToast('Draft loaded successfully!', 'info');
            }
        } catch (e) {
            console.error('Error loading draft:', e);
        }
    }
}

function showToast(message, type = 'info') {
    // Simple toast implementation
    const toast = $(`
        <div class="alert alert-${type} alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `);
    
    $('body').append(toast);
    
    setTimeout(() => {
        toast.alert('close');
    }, 5000);
}
</script>
@endpush
@endsection