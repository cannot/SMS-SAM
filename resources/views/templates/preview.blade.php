@extends('layouts.app')

@section('title', 'Preview Template')

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
                        <i class="fas fa-eye text-primary me-2"></i>
                        Preview: {{ $template->name }}
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('templates.index') }}">Templates</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('templates.show', $template) }}">{{ $template->name }}</a></li>
                            <li class="breadcrumb-item active">Preview</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('templates.edit', $template) }}" class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Edit Template
                    </a>
                    <a href="{{ route('templates.show', $template) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Details
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Main Preview Area -->
                <div class="col-lg-8">
                    <!-- Template Info Cards -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card template-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="card-title mb-1">Template Information</h6>
                                            <div class="small opacity-75">{{ $template->category }} • {{ ucfirst($template->priority) }}</div>
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
                                            <!-- Will be populated by JavaScript -->
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
                                                <!-- Will be populated by JavaScript -->
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
                                                <pre><!-- Will be populated by JavaScript --></pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Variables Information -->
                    @if(!empty($template->variables) || !empty($detectedVariables))
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tags me-2"></i>Template Variables
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(!empty($detectedVariables))
                            <div class="mb-3">
                                <h6 class="text-primary">Detected Variables:</h6>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($detectedVariables as $variable)
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
                                    <textarea class="form-control" id="testData" rows="8" placeholder='{"user_name": "John Doe", "message": "Test message"}'>{!! json_encode($defaultTestData ?? [], JSON_PRETTY_PRINT) !!}</textarea>
                                    <div class="form-text">Enter JSON object with variable values</div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="updatePreview()">
                                        <i class="fas fa-sync me-1"></i>Update Preview
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="loadSampleData()">
                                        <i class="fas fa-magic me-1"></i>Load Sample Data
                                    </button>
                                </div>
                            </form>
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
@endsection

@push('scripts')
<script>
@verbatim
// Template data passed from PHP
const templateData = {
    subject: {!! json_encode($template->subject_template) !!},
    html: {!! json_encode($template->body_html_template) !!},
    text: {!! json_encode($template->body_text_template) !!},
    defaultVariables: {!! json_encode($template->default_variables ?? []) !!}
};

// Sample test data
function loadSampleData() {
    const sampleData = {
        // User variables
        user_name: '{!! optional(auth()->user())->name ?: "John Doe" !!}',
        user_email: '{!! optional(auth()->user())->email ?: "john.doe@company.com" !!}',
        user_first_name: '{!! optional(auth()->user())->name ? explode(" ", auth()->user()->name)[0] : "John" !!}',
        user_last_name: '{!! optional(auth()->user())->name ? (explode(" ", auth()->user()->name)[1] ?? "") : "Doe" !!}',
        user_department: 'Information Technology',
        user_title: 'Software Developer',
        
        // System variables
        current_date: new Date().toISOString().split('T')[0],
        current_time: new Date().toTimeString().split(' ')[0],
        current_datetime: new Date().toISOString().replace('T', ' ').split('.')[0],
        app_name: '{!! config("app.name", "Smart Notification") !!}',
        app_url: '{!! config("app.url", "http://localhost") !!}',
        year: new Date().getFullYear().toString(),
        month: (new Date().getMonth() + 1).toString().padStart(2, '0'),
        day: new Date().getDate().toString().padStart(2, '0'),
        
        // Custom sample variables
        message: 'This is a sample notification message for testing purposes.',
        subject: 'Important System Notification',
        company: '{!! config("app.name", "Your Company") !!}',
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
        document.getElementById('subject-preview').textContent = subjectPreview;
    }
    
    // Update HTML preview
    if (templateData.html) {
        const htmlPreview = replaceVariables(templateData.html, testData);
        document.getElementById('html-preview').innerHTML = htmlPreview;
    }
    
    // Update text preview
    if (templateData.text) {
        const textPreview = replaceVariables(templateData.text, testData);
        document.getElementById('text-preview').querySelector('pre').textContent = textPreview;
    }
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

// Export template
function exportTemplate() {
    const templateExport = {
        name: '{!! addslashes($template->name) !!}',
        slug: '{!! addslashes($template->slug) !!}',
        category: '{!! addslashes($template->category) !!}',
        priority: '{!! addslashes($template->priority) !!}',
        description: '{!! addslashes($template->description ?? "") !!}',
        subject_template: templateData.subject,
        body_html_template: templateData.html,
        body_text_template: templateData.text,
        supported_channels: {!! json_encode($template->supported_channels ?? []) !!},
        variables: {!! json_encode($template->variables ?? []) !!},
        default_variables: templateData.defaultVariables,
        version: '{!! $template->version ?? "1" !!}',
        exported_at: new Date().toISOString()
    };
    
    const dataStr = JSON.stringify(templateExport, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = 'template-' + '{!! addslashes($template->slug) !!}' + '-' + new Date().toISOString().split('T')[0] + '.json';
    link.click();
}

// Toggle template status
function toggleTemplate(activate) {
    if (confirm('Are you sure you want to ' + (activate ? 'activate' : 'deactivate') + ' this template?')) {
        // Make AJAX request to toggle status
        fetch('{{ route("templates.toggle", $template) }}', {
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

// Initialize preview on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSampleData();
});
@endverbatim
</script>
@endpush