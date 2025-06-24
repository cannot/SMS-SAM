@extends('layouts.app')

@section('title', 'Template: ' . $template->name)

@push('styles')
<style>
.preview-section {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
}

.preview-header {
    background-color: #f8f9fa;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}

.preview-content {
    padding: 1rem;
    background-color: #fff;
}

.variable-badge {
    cursor: help;
    transition: all 0.2s;
}

.variable-badge:hover {
    transform: scale(1.05);
}

.template-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.usage-stats {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.channel-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.channel-email {
    background-color: #e3f2fd;
    color: #1976d2;
}

.channel-teams {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.channel-sms {
    background-color: #e8f5e8;
    color: #388e3c;
}

.test-data-form {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
}

.syntax-highlight {
    background-color: #f1f3f4;
    border-left: 4px solid #4285f4;
    padding: 0.75rem;
    margin: 0.5rem 0;
    border-radius: 0 0.25rem 0.25rem 0;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-file-alt text-primary me-2"></i>
                        {{ $template->name }}
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('templates.index') }}">Templates</a></li>
                            <li class="breadcrumb-item active">{{ $template->name }}</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('templates.edit', $template) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Edit Template
                    </a>
                    <button type="button" class="btn btn-outline-success" onclick="duplicateTemplate()">
                        <i class="fas fa-copy me-2"></i>Duplicate
                    </button>
                    <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Templates
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Main Content Area -->
                <div class="col-lg-8">
                    <!-- Template Info Cards -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card template-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Template Information</h6>
                                            <div class="small opacity-75">{{ ucfirst($template->category) }} • {{ ucfirst($template->priority) }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="h4 mb-0">v{{ $template->version ?? '1' }}</div>
                                            <div class="small opacity-75">Version</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card usage-stats text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Usage Statistics</h6>
                                            <div class="small opacity-75">Total notifications sent</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="h4 mb-0">{{ $template->notifications()->count() }}</div>
                                            <div class="small opacity-75">Times Used</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Validation Status -->
                    @php
                        $validationErrors = $template->validateTemplate();
                    @endphp
                    @if(!empty($validationErrors))
                        <div class="alert alert-warning mb-4">
                            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Template Validation Issues</h6>
                            <ul class="mb-0">
                                @foreach($validationErrors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="alert alert-success mb-4">
                            <i class="fas fa-check-circle"></i> Template syntax is valid
                        </div>
                    @endif

                    <!-- Supported Channels -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-broadcast-tower me-2"></i>Supported Channels
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                @if(in_array('email', $template->supported_channels ?? []))
                                <span class="channel-indicator channel-email">
                                    <i class="fas fa-envelope"></i> Email
                                </span>
                                @endif
                                @if(in_array('teams', $template->supported_channels ?? []))
                                <span class="channel-indicator channel-teams">
                                    <i class="fab fa-microsoft"></i> Microsoft Teams
                                </span>
                                @endif
                                @if(in_array('sms', $template->supported_channels ?? []))
                                <span class="channel-indicator channel-sms">
                                    <i class="fas fa-sms"></i> SMS
                                </span>
                                @endif
                            </div>
                            @if($template->description)
                            <hr>
                            <p class="text-muted mb-0">{{ $template->description }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Preview Sections -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-eye me-2"></i>Template Preview
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <!-- Subject Preview -->
                            @if($template->subject_template)
                            <div class="preview-section">
                                <div class="preview-header">
                                    <i class="fas fa-heading me-2"></i>Subject Template
                                </div>
                                <div class="preview-content">
                                    <div class="mb-2">
                                        <strong>Template:</strong>
                                        <div class="syntax-highlight">{{ $template->subject_template }}</div>
                                    </div>
                                    <div>
                                        <strong>Preview:</strong>
                                        <div class="alert alert-info mb-0" id="subject-preview">
                                            {{ $preview['subject'] ?? $template->subject_template }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- HTML Content Preview -->
                            @if($template->body_html_template)
                            <div class="preview-section">
                                <div class="preview-header">
                                    <i class="fas fa-code me-2"></i>HTML Template
                                    <span class="badge bg-primary ms-2">Email & Teams</span>
                                </div>
                                <div class="preview-content">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Template Code:</strong>
                                            <div class="syntax-highlight" style="max-height: 300px; overflow-y: auto;">
                                                <pre><code>{{ $template->body_html_template }}</code></pre>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Rendered Output:</strong>
                                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto; background-color: #fff;" id="html-preview">
                                                {!! $preview['body_html'] ?? $template->body_html_template !!}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Text Content Preview -->
                            @if($template->body_text_template)
                            <div class="preview-section">
                                <div class="preview-header">
                                    <i class="fas fa-file-text me-2"></i>Text Template
                                    <span class="badge bg-success ms-2">SMS & Fallback</span>
                                </div>
                                <div class="preview-content">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Template:</strong>
                                            <div class="syntax-highlight">
                                                <pre>{{ $template->body_text_template }}</pre>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Preview:</strong>
                                            <div class="bg-light border rounded p-3" id="text-preview">
                                                <pre>{{ $preview['body_text'] ?? $template->body_text_template }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Variables Information -->
                    @if(!empty($template->variables) || !empty($extractedVariables))
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tags me-2"></i>Template Variables
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(!empty($extractedVariables))
                            <div class="mb-3">
                                <h6 class="text-primary">Detected Variables:</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($extractedVariables as $variable)
                                    <span class="badge bg-primary variable-badge" title="Variable: {{ $variable }}">
                                        &#123;&#123;{{ $variable }}&#125;&#125;
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            @if(!empty($template->variables))
                            <div class="mb-3">
                                <h6 class="text-success">Required Variables:</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($template->variables as $variable)
                                    <span class="badge bg-success variable-badge" title="Required: {{ $variable }}">
                                        &#123;&#123;{{ $variable }}&#125;&#125;
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            @if(!empty($template->default_variables))
                            <div>
                                <h6 class="text-info">Default Values:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Variable</th>
                                                <th>Default Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($template->default_variables as $key => $value)
                                            <tr>
                                                <td><code>{{ $key }}</code></td>
                                                <td>{{ $value }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Test Preview -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-play me-2"></i>Test Preview
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Enter test data to see how your template will look with real values.</p>
                            
                            <form id="previewForm" class="test-data-form">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Test Data (JSON)</label>
                                    <textarea class="form-control" id="testData" rows="8" placeholder='{"user_name": "John Doe", "message": "Test message"}'></textarea>
                                    <div class="form-text">Enter JSON object with variable values</div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="updatePreview()">
                                        <i class="fas fa-sync me-1"></i>Update Preview
                                    </button>
                                    {{-- <button type="button" class="btn btn-outline-secondary btn-sm" onclick="loadSampleData()">
                                        <i class="fas fa-magic me-1"></i>Load Sample Data
                                    </button> --}}
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Template Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Template Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }} mb-2">
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="badge bg-info mb-2">
                                        v{{ $template->version }}
                                    </span>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="small text-muted">
                                <div class="mb-2">
                                    <strong>Category:</strong> 
                                    {{ $template->getCategories()[$template->category] ?? $template->category }}
                                </div>
                                <div class="mb-2">
                                    <strong>Priority:</strong> 
                                    <span class="badge bg-{{ $template->priority === 'urgent' ? 'danger' : ($template->priority === 'high' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($template->priority) }}
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <strong>Slug:</strong> 
                                    <code>{{ $template->slug }}</code>
                                </div>
                                <div class="mb-2">
                                    <strong>Created:</strong> 
                                    {{ $template->created_at->format('d M Y H:i') }}
                                </div>
                                <div class="mb-2">
                                    <strong>Updated:</strong> 
                                    {{ $template->updated_at->format('d M Y H:i') }}
                                </div>
                                @if($template->creator)
                                <div class="mb-2">
                                    <strong>Created by:</strong> 
                                    {{ $template->creator->name }}
                                </div>
                                @endif
                            </div>

                            <!-- Status Toggle -->
                            <div class="mt-3">
                                <button type="button" class="btn btn-sm btn-outline-{{ $template->is_active ? 'warning' : 'success' }}" 
                                        onclick="toggleStatus()">
                                    <i class="fas fa-{{ $template->is_active ? 'pause' : 'play' }}"></i>
                                    {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Usage Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Usage Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="h4 mb-0 text-primary">{{ number_format($template->notifications()->count()) }}</div>
                                    <small class="text-muted">Total Used</small>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="h4 mb-0 text-success">
                                        {{ number_format($template->notifications()->whereHas('logs', function($q) { $q->where('status', 'delivered'); })->count()) }}
                                    </div>
                                    <small class="text-muted">Delivered</small>
                                </div>
                                <div class="col-6">
                                    <div class="h4 mb-0 text-danger">
                                        {{ number_format($template->notifications()->whereHas('logs', function($q) { $q->where('status', 'failed'); })->count()) }}
                                    </div>
                                    <small class="text-muted">Failed</small>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">
                                        @php 
                                            $lastUsed = $template->notifications()->latest()->first();
                                        @endphp
                                        @if($lastUsed)
                                            Last used:<br>{{ $lastUsed->created_at->diffForHumans() }}
                                        @else
                                            Never used
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('notifications.create', ['template' => $template->id]) }}" class="btn btn-success">
                                    <i class="fas fa-paper-plane me-2"></i>Send Notification
                                </a>
                                <a href="{{ route('templates.duplicate', $template) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-copy me-2"></i>Duplicate Template
                                </a>
                                <button class="btn btn-outline-info" onclick="exportTemplate()">
                                    <i class="fas fa-download me-2"></i>Export Template
                                </button>
                                @if($template->is_active)
                                <button class="btn btn-outline-warning" onclick="toggleTemplate(false)">
                                    <i class="fas fa-pause me-2"></i>Deactivate
                                </button>
                                @else
                                <button class="btn btn-outline-success" onclick="toggleTemplate(true)">
                                    <i class="fas fa-play me-2"></i>Activate
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Template Details -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Template Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">ID:</div>
                                    <div class="col-7">{{ $template->id }}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">Slug:</div>
                                    <div class="col-7"><code>{{ $template->slug }}</code></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">Status:</div>
                                    <div class="col-7">
                                        @if($template->is_active)
                                        <span class="badge bg-success">Active</span>
                                        @else
                                        <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">Created:</div>
                                    <div class="col-7">{{ $template->created_at->format('M d, Y H:i') }}</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 fw-bold">Updated:</div>
                                    <div class="col-7">{{ $template->updated_at->format('M d, Y H:i') }}</div>
                                </div>
                                <div class="row">
                                    <div class="col-5 fw-bold">Creator:</div>
                                    <div class="col-7">{{ optional($template->creator)->name ?? 'Unknown' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Template data passed from PHP
const templateData = {
    subject: @json($template->subject_template),
    html: @json($template->body_html_template),
    text: @json($template->body_text_template),
    defaultVariables: @json($template->default_variables ?? [])
};

// Functions that need PHP data
function toggleStatus() {
    fetch('{{ route("templates.toggle-status", $template) }}', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating template status.');
    });
}

function duplicateTemplate() {
    if (confirm('Are you sure you want to duplicate this template?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("templates.duplicate", $template) }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Export template
function exportTemplate() {
    const templateExport = {
        name: @json($template->name),
        slug: @json($template->slug),
        category: @json($template->category),
        priority: @json($template->priority),
        description: @json($template->description ?? ""),
        subject_template: templateData.subject,
        body_html_template: templateData.html,
        body_text_template: templateData.text,
        supported_channels: @json($template->supported_channels ?? []),
        variables: @json($template->variables ?? []),
        default_variables: templateData.defaultVariables,
        version: @json($template->version ?? "1"),
        exported_at: new Date().toISOString()
    };
    
    const dataStr = JSON.stringify(templateExport, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = 'template-' + @json($template->slug) + '-' + new Date().toISOString().split('T')[0] + '.json';
    link.click();
}

// Toggle template status
function toggleTemplate(activate) {
    if (confirm('Are you sure you want to ' + (activate ? 'activate' : 'deactivate') + ' this template?')) {
        fetch('{{ route("templates.toggle-status", $template) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                is_active: activate
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating template status');
        });
    }
}

// Load default data from template with PHP values
function loadDefaultDataWithPHP() {
    const sampleData = {
        // User variables
        user_name: @json(optional(auth()->user())->name ?: "John Doe"),
        user_email: @json(optional(auth()->user())->email ?: "john.doe@company.com"),
        user_first_name: @json(optional(auth()->user())->name ? explode(" ", auth()->user()->name)[0] : "John"),
        user_last_name: @json(optional(auth()->user())->name ? (explode(" ", auth()->user()->name)[1] ?? "") : "Doe"),
        user_department: 'Information Technology',
        user_title: 'Software Developer',
        
        // System variables
        app_name: @json(config("app.name", "Smart Notification")),
        app_url: @json(config("app.url", "http://localhost")),
        company: @json(config("app.name", "Your Company")),
        
        // Override with template defaults
        ...templateData.defaultVariables
    };
    
    return sampleData;
}

// Extract variables from template content
function extractVariablesFromTemplate() {
    const variables = new Set();
    const content = (templateData.subject || '') + ' ' + (templateData.html || '') + ' ' + (templateData.text || '');
    
    const matches = content.match(/\{\{([^}#\/][^}]*)\}\}/g);
    if (matches) {
        matches.forEach(match => {
            const varName = match.replace(/[{}]/g, '').trim();
            // Clean up variable name (remove formatting like date:format|variable)
            const cleanVarName = varName.split(':')[0].split('|')[0].trim();
            if (cleanVarName && !isSystemVariable(cleanVarName)) {
                variables.add(cleanVarName);
            }
        });
    }
    
    return Array.from(variables);
}

// Check if variable is a system variable
function isSystemVariable(variable) {
    const systemVars = ['app_name', 'app_url', 'current_date', 'current_time', 'current_datetime', 'year', 'month', 'day'];
    return systemVars.includes(variable);
}

// Simple variable replacement function
function replaceVariables(template, data) {
    let result = template;
    
    Object.entries(data).forEach(function([key, value]) {
        const regex = new RegExp('\\{\\{\\s*' + key + '\\s*\\}\\}', 'g');
        result = result.replace(regex, value || '[' + key + ']');
    });
    
    // Replace any remaining variables with placeholder
    result = result.replace(/\{\{([^}]+)\}\}/g, function(match, varName) {
        return '[' + varName.trim() + ']';
    });
    
    return result;
}

// Update preview with test data
function updatePreview() {
    let testData = {};
    
    try {
        const testDataText = document.getElementById('testData').value;
        if (testDataText.trim()) {
            testData = JSON.parse(testDataText);
        }
    } catch (e) {
        alert('Invalid JSON format in test data: ' + e.message);
        return;
    }
    
    // Update subject preview
    if (templateData.subject) {
        const subjectPreview = replaceVariables(templateData.subject, testData);
        const subjectEl = document.getElementById('subject-preview');
        if (subjectEl) {
            subjectEl.textContent = subjectPreview;
        }
    }
    
    // Update HTML preview
    if (templateData.html) {
        const htmlPreview = replaceVariables(templateData.html, testData);
        const htmlEl = document.getElementById('html-preview');
        if (htmlEl) {
            htmlEl.innerHTML = htmlPreview;
        }
    }
    
    // Update text preview
    if (templateData.text) {
        const textPreview = replaceVariables(templateData.text, testData);
        const textEl = document.getElementById('text-preview');
        if (textEl && textEl.querySelector('pre')) {
            textEl.querySelector('pre').textContent = textPreview;
        }
    }
}

// Keep loadSampleData for the "Load Sample Data" button
function loadSampleData() {
    const sampleData = {
        ...loadDefaultDataWithPHP(),
        
        // Additional sample data
        current_date: new Date().toISOString().split('T')[0],
        current_time: new Date().toTimeString().split(' ')[0],
        current_datetime: new Date().toISOString().replace('T', ' ').split('.')[0],
        year: new Date().getFullYear().toString(),
        month: (new Date().getMonth() + 1).toString().padStart(2, '0'),
        day: new Date().getDate().toString().padStart(2, '0'),
        
        // Custom sample variables
        message: 'This is a sample notification message for testing purposes.',
        subject: 'Important System Notification',
        url: 'https://example.com/action-required',
        priority: 'High',
        status: 'Active',
        amount: '1,250.00',
        deadline: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        
        // Override with template defaults
        ...templateData.defaultVariables
    };
    
    document.getElementById('testData').value = JSON.stringify(sampleData, null, 2);
    updatePreview();
}

// Load default data from template
function loadDefaultData() {
    // Start with template default variables if available
    let defaultData = {};
    
    console.log('Template data:', templateData); // Debug
    
    if (templateData.defaultVariables && Object.keys(templateData.defaultVariables).length > 0) {
        // Use template defaults and merge with PHP data
        defaultData = { ...loadDefaultDataWithPHP(), ...templateData.defaultVariables };
        console.log('Using template default variables:', templateData.defaultVariables); // Debug
    } else {
        // If no default variables, create sample data based on detected variables
        const extractedVars = extractVariablesFromTemplate();
        console.log('Extracted variables:', extractedVars); // Debug
        
        const phpData = loadDefaultDataWithPHP();
        
        // Set default values for common variables
        extractedVars.forEach(varName => {
            if (phpData[varName]) {
                defaultData[varName] = phpData[varName];
            } else {
                switch(varName) {
                    case 'current_date':
                        defaultData[varName] = new Date().toISOString().split('T')[0];
                        break;
                    case 'current_time':
                        defaultData[varName] = new Date().toTimeString().split(' ')[0];
                        break;
                    case 'current_datetime':
                        defaultData[varName] = new Date().toISOString().replace('T', ' ').split('.')[0];
                        break;
                    case 'year':
                        defaultData[varName] = new Date().getFullYear().toString();
                        break;
                    case 'month':
                        defaultData[varName] = (new Date().getMonth() + 1).toString().padStart(2, '0');
                        break;
                    case 'day':
                        defaultData[varName] = new Date().getDate().toString().padStart(2, '0');
                        break;
                    case 'message':
                        defaultData[varName] = 'This is a sample notification message for testing purposes.';
                        break;
                    case 'subject':
                        defaultData[varName] = 'Important System Notification';
                        break;
                    case 'url':
                        defaultData[varName] = 'https://example.com/action-required';
                        break;
                    case 'priority':
                        defaultData[varName] = 'High';
                        break;
                    case 'status':
                        defaultData[varName] = 'Active';
                        break;
                    case 'amount':
                        defaultData[varName] = '1,250.00';
                        break;
                    case 'deadline':
                        defaultData[varName] = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
                        break;
                    default:
                        defaultData[varName] = `Sample ${varName}`;
                }
            }
        });
        
        console.log('Generated default data:', defaultData); // Debug
    }
    
    document.getElementById('testData').value = JSON.stringify(defaultData, null, 2);
    updatePreview();
}// Initialize preview on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDefaultData(); // Load default data from template first
    
    // Add auto-update on textarea change
    const testDataTextarea = document.getElementById('testData');
    if (testDataTextarea) {
        // Auto-update preview when user types (with debounce)
        let timeoutId;
        testDataTextarea.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                try {
                    const testData = JSON.parse(this.value);
                    updatePreview();
                } catch (e) {
                    // Invalid JSON, don't update preview
                    console.log('Invalid JSON, skipping auto-update');
                }
            }, 1000); // 1 second delay
        });
        
        // Also update on blur (when user leaves the field)
        testDataTextarea.addEventListener('blur', function() {
            try {
                const testData = JSON.parse(this.value);
                updatePreview();
            } catch (e) {
                console.log('Invalid JSON format');
            }
        });
    }
});
</script>

<script>
@verbatim
// Empty - all functions moved outside
@endverbatim
</script>
@endpush
@endsection