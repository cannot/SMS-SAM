@extends('layouts.app')

@section('title', 'Edit Template')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
.variable-badge {
    cursor: pointer;
    transition: all 0.2s;
}
.variable-badge:hover {
    transform: scale(1.05);
    background-color: var(--bs-primary) !important;
}
.template-preview {
    max-height: 400px;
    overflow-y: auto;
}
.variable-helper {
    position: sticky;
    top: 20px;
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
                    <h1 class="h3 mb-0">Edit Template: {{ $template->name }}</h1>
                    <p class="text-muted">Last updated {{ $template->updated_at->diffForHumans() }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('templates.show', $template) }}" class="btn btn-outline-info">
                        <i class="fas fa-eye me-2"></i>View
                    </a>
                    <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Templates
                    </a>
                </div>
            </div>

            <form action="{{ route('templates.update', $template) }}" method="POST" id="templateForm">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <!-- Main Form -->
                    <div class="col-lg-8">
                        <!-- Usage Warning -->
                        @if($template->notifications()->count() > 0)
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> This template is currently being used by {{ $template->notifications()->count() }} notification(s). 
                            Changes will affect future notifications only.
                        </div>
                        @endif

                        <!-- Basic Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Template Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               id="name" name="name" value="{{ old('name', $template->name) }}" required>
                                        @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                        <select class="form-select @error('category') is-invalid @enderror" 
                                                id="category" name="category" required>
                                            @foreach($categories as $key => $label)
                                                <option value="{{ $key }}" {{ old('category', $template->category) === $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="2">{{ old('description', $template->description) }}</textarea>
                                    @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                        <select class="form-select @error('priority') is-invalid @enderror" 
                                                id="priority" name="priority" required>
                                            <option value="low" {{ old('priority', $template->priority) === 'low' ? 'selected' : '' }}>Low</option>
                                            <option value="medium" {{ old('priority', $template->priority) === 'medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="normal" {{ old('priority', $template->priority) === 'normal' ? 'selected' : '' }}>Normal</option>
                                            <option value="high" {{ old('priority', $template->priority) === 'high' ? 'selected' : '' }}>High</option>
                                            <option value="urgent" {{ old('priority', $template->priority) === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                        </select>
                                        @error('priority')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Supported Channels <span class="text-danger">*</span></label>
                                        <div class="mt-2">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                       type="checkbox" id="channel_email" name="supported_channels[]" value="email"
                                                       {{ in_array('email', old('supported_channels', $template->supported_channels ?? [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="channel_email">Email</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                       type="checkbox" id="channel_teams" name="supported_channels[]" value="teams"
                                                       {{ in_array('teams', old('supported_channels', $template->supported_channels ?? [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="channel_teams">Microsoft Teams</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                       type="checkbox" id="channel_sms" name="supported_channels[]" value="sms"
                                                       {{ in_array('sms', old('supported_channels', $template->supported_channels ?? [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="channel_sms">SMS</label>
                                            </div>
                                        </div>
                                        @error('supported_channels')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           id="is_active" name="is_active" value="1" 
                                           {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Active Template
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Template Content</h5>
                            </div>
                            <div class="card-body">
                                <!-- Subject Template -->
                                <div class="mb-3">
                                    <label for="subject_template" class="form-label">Subject Template <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('subject_template') is-invalid @enderror" 
                                           id="subject_template" name="subject_template" 
                                           value="{{ old('subject_template', $template->subject_template) }}" 
                                           placeholder="Enter notification subject template" required>
                                    <div class="form-text">Use variables like &#123;&#123;user_name&#125;&#125; or &#123;&#123;message&#125;&#125;</div>
                                    @error('subject_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- HTML Content -->
                                <div class="mb-3" id="html-content">
                                    <label for="body_html_template" class="form-label">HTML Template</label>
                                    <textarea class="form-control @error('body_html_template') is-invalid @enderror" 
                                              id="body_html_template" name="body_html_template" rows="10">{{ old('body_html_template', $template->body_html_template) }}</textarea>
                                    <div class="form-text">For email and Teams rich content</div>
                                    @error('body_html_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Text Content -->
                                <div class="mb-3" id="text-content">
                                    <label for="body_text_template" class="form-label">Text Template</label>
                                    <textarea class="form-control @error('body_text_template') is-invalid @enderror" 
                                              id="body_text_template" name="body_text_template" rows="8" 
                                              placeholder="Plain text version for SMS and fallback">{{ old('body_text_template', $template->body_text_template) }}</textarea>
                                    <div class="form-text">For SMS and plain text fallback</div>
                                    @error('body_text_template')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Validation Button -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-info" onclick="validateTemplate()">
                                        <i class="fas fa-check-circle me-2"></i>Validate Syntax
                                    </button>
                                    <div id="validation-result" class="mt-2"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Variables Configuration -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Variables Configuration</h5>
                            </div>
                            <div class="card-body">
                                <!-- Required Variables -->
                                <div class="mb-4">
                                    <label class="form-label">Required Variables</label>
                                    <div id="variables-container">
                                        @php
                                            $variables = old('variables', $template->variables ?? []);
                                        @endphp
                                        @if(!empty($variables))
                                            @foreach($variables as $index => $variable)
                                                <div class="input-group mb-2 variable-row">
                                                    <input type="text" class="form-control" name="variables[]" value="{{ $variable }}">
                                                    <button type="button" class="btn btn-outline-danger" onclick="removeVariable(this)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addVariable()">
                                        <i class="fas fa-plus"></i> Add Variable
                                    </button>
                                </div>

                                <!-- Default Variables -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="default_variables_json" class="form-label mb-0">Default Variables (JSON format)</label>
                                        <button type="button" class="btn btn-outline-info btn-sm" onclick="generateDefaultVariablesJSON()">
                                            <i class="fas fa-magic"></i> Auto Generate
                                        </button>
                                    </div>
                                    <textarea class="form-control @error('default_variables') is-invalid @enderror" 
                                            id="default_variables_json" name="default_variables_json" rows="5" 
                                            placeholder='{"variable_name": "default_value"}'>
                                            @php
                                            $defaultVars = old('default_variables_json') ?: $template->default_variables;
                                            if (empty($defaultVars) || (is_array($defaultVars) && count($defaultVars) === 0)) {
                                                echo '';
                                            } else {
                                                echo is_string($defaultVars) ? $defaultVars : json_encode($defaultVars, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                            }
                                            @endphp
                                    </textarea>
                                    @error('default_variables')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">JSON object (not array) for default variable values. Auto-generates from detected variables.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Current Variables -->
                        @if($extractedVariables && count($extractedVariables) > 0)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-tags me-2"></i>Detected Variables
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($extractedVariables as $variable)
                                    <span class="badge bg-primary variable-badge">&#123;&#123;{{ $variable }}&#125;&#125;</span>
                                    @endforeach
                                </div>
                                <small class="text-muted">Variables are automatically detected from your content</small>
                            </div>
                        </div>
                        @endif

                        <!-- Actions -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Template
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="previewBtn">
                                        <i class="fas fa-eye me-2"></i>Preview Changes
                                    </button>
                                    <a href="{{ route('templates.show', $template) }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <!-- Template Info -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Template Information
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="fw-bold text-primary fs-4">{{ $template->notifications()->count() }}</div>
                                        <small class="text-muted">Times Used</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="fw-bold text-success fs-4">v{{ $template->version }}</div>
                                        <small class="text-muted">Version</small>
                                    </div>
                                </div>
                                <hr>
                                <div class="small">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Created:</span>
                                        <span>{{ $template->created_at->format('M d, Y') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Updated:</span>
                                        <span>{{ $template->updated_at->format('M d, Y') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Creator:</span>
                                        <span>{{ $template->creator->name ?? 'Unknown' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Variable Helper -->
                        <div class="card variable-helper">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-code me-2"></i>Available Variables
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted mb-3">Click to insert variables into your content</p>
                                
                                <div class="mb-3">
                                    <h6 class="small fw-bold text-uppercase">User Variables</h6>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-light text-dark variable-badge" data-variable="user_name">&#123;&#123;user_name&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="user_email">&#123;&#123;user_email&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="user_department">&#123;&#123;user_department&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="user_title">&#123;&#123;user_title&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="user_first_name">&#123;&#123;user_first_name&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="user_last_name">&#123;&#123;user_last_name&#125;&#125;</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="small fw-bold text-uppercase">Date & Time</h6>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-light text-dark variable-badge" data-variable="date">&#123;&#123;date&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="time">&#123;&#123;time&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="current_date">&#123;&#123;current_date&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="current_time">&#123;&#123;current_time&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="current_datetime">&#123;&#123;current_datetime&#125;&#125;</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="small fw-bold text-uppercase">Common</h6>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-light text-dark variable-badge" data-variable="message">&#123;&#123;message&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="company">&#123;&#123;company&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="url">&#123;&#123;url&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="priority">&#123;&#123;priority&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="status">&#123;&#123;status&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="subject">&#123;&#123;subject&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="amount">&#123;&#123;amount&#125;&#125;</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="deadline">&#123;&#123;deadline&#125;&#125;</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="small fw-bold text-uppercase">Custom Variable</h6>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="customVariable" placeholder="variable_name">
                                        <button class="btn btn-outline-primary" type="button" onclick="addCustomVariableFunction()">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Enter variable name and click + to add</small>
                                </div>

                                <hr>

                                <div>
                                    <h6 class="small fw-bold text-uppercase">Syntax Helper</h6>
                                    <div class="small">
                                        <p><code>&#123;&#123;variable&#125;&#125;</code> - Simple variable</p>
                                        <p><code>&#123;&#123;#if variable&#125;&#125;content&#123;&#123;/if&#125;&#125;</code> - Conditional</p>
                                        <p><code>&#123;&#123;#each items&#125;&#125;&#123;&#123;this.name&#125;&#125;&#123;&#123;/each&#125;&#125;</code> - Loop</p>
                                        <p><code>&#123;&#123;date:Y-m-d|created_at&#125;&#125;</code> - Date format</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>

<script>
@verbatim
// Global variables
let lastFocusedElement = null;

// Global function for custom variable
function addCustomVariableFunction() {
    const customVar = document.getElementById('customVariable').value.trim();
    if (customVar) {
        insertVariable(customVar);
        addVariableToList(customVar);
        document.getElementById('customVariable').value = '';
        
        // Visual feedback
        const input = document.getElementById('customVariable');
        input.style.borderColor = '#28a745';
        setTimeout(() => {
            input.style.borderColor = '';
        }, 1000);
    } else {
        alert('Please enter a variable name');
        document.getElementById('customVariable').focus();
    }
}

// Variable management functions
function addVariable() {
    const container = document.getElementById('variables-container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2 variable-row';
    div.innerHTML = `
        <input type="text" class="form-control" name="variables[]" placeholder="variable_name">
        <button type="button" class="btn btn-outline-danger" onclick="removeVariable(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

function removeVariable(button) {
    button.closest('.variable-row').remove();
}

function addVariableToList(varName) {
    const existingInputs = document.querySelectorAll('#variables-container input[name="variables[]"]');
    for (let input of existingInputs) {
        if (input.value === varName) {
            return;
        }
    }
    
    const container = document.getElementById('variables-container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2 variable-row';
    div.innerHTML = `
        <input type="text" class="form-control" name="variables[]" value="${varName}">
        <button type="button" class="btn btn-outline-danger" onclick="removeVariable(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

// Insert variable function
function insertVariable(varName) {
    const varText = '{{' + varName + '}}';
    
    if (lastFocusedElement) {
        // TinyMCE editor (new format)
        if (lastFocusedElement.isTinyMCE && lastFocusedElement.editor) {
            lastFocusedElement.editor.insertContent(varText);
            lastFocusedElement.editor.focus();
            return;
        }
        
        // TinyMCE editor (legacy format)
        if (lastFocusedElement.id && tinymce.get(lastFocusedElement.id)) {
            const editor = tinymce.get(lastFocusedElement.id);
            editor.insertContent(varText);
            editor.focus();
            return;
        }
        
        // Regular input/textarea
        if (lastFocusedElement.value !== undefined && typeof lastFocusedElement.value === 'string') {
            const cursorPos = lastFocusedElement.selectionStart || 0;
            const textBefore = lastFocusedElement.value.substring(0, cursorPos);
            const textAfter = lastFocusedElement.value.substring(cursorPos);
            
            lastFocusedElement.value = textBefore + varText + textAfter;
            lastFocusedElement.focus();
            lastFocusedElement.selectionStart = lastFocusedElement.selectionEnd = cursorPos + varText.length;
            lastFocusedElement.dispatchEvent(new Event('input', { bubbles: true }));
            return;
        }
    }
    
    // Fallback to TinyMCE
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        htmlEditor.insertContent(varText);
        htmlEditor.focus();
    } else {
        alert('Please click in a text field first');
    }
}

// Variable detection functions
function detectVariablesFromContent() {
    const subjectTemplate = document.getElementById('subject_template').value;
    const bodyTextTemplate = document.getElementById('body_text_template').value;
    let bodyHtmlTemplate = '';
    
    if (tinymce.get('body_html_template')) {
        bodyHtmlTemplate = tinymce.get('body_html_template').getContent();
    } else {
        bodyHtmlTemplate = document.getElementById('body_html_template').value;
    }
    
    const allContent = subjectTemplate + ' ' + bodyHtmlTemplate + ' ' + bodyTextTemplate;
    const variables = [];
    
    // Extract variables using regex
    const variableMatches = allContent.match(/\{\{([^}#\/][^}]*)\}\}/g);
    if (variableMatches) {
        variableMatches.forEach(match => {
            const varName = match.replace(/[{}]/g, '').split(':')[0].split('|')[0].trim();
            if (!variables.includes(varName) && !isSystemVariable(varName)) {
                variables.push(varName);
            }
        });
    }
    
    // Extract conditional and loop variables
    const conditionalMatches = allContent.match(/\{\{#(if|each)\s+([^}]+)\}\}/g);
    if (conditionalMatches) {
        conditionalMatches.forEach(match => {
            const varName = match.replace(/\{\{#(if|each)\s+/, '').replace(/\}\}/, '').trim();
            if (!variables.includes(varName) && !isSystemVariable(varName)) {
                variables.push(varName);
            }
        });
    }
    
    return variables;
}

function updateDetectedVariables() {
    const detectedVars = detectVariablesFromContent();
    console.log(detectedVars);
    
    let detectedSection = document.getElementById('detected-variables-section');
    if (!detectedSection && detectedVars.length > 0) {
        const variablesCard = document.querySelector('.card:has(#variables-container)');
        if (variablesCard) {
            detectedSection = document.createElement('div');
            detectedSection.id = 'detected-variables-section';
            detectedSection.className = 'mb-4';
            detectedSection.innerHTML = `
                <h6 class="small fw-bold text-uppercase text-info">Detected Variables</h6>
                <div id="detected-variables-list" class="d-flex flex-wrap gap-1 mb-2"></div>
                <small class="text-muted">Variables found in your template content. Click to add to required variables.</small>
            `;
            variablesCard.querySelector('.card-body').insertBefore(detectedSection, variablesCard.querySelector('.card-body').firstChild);
        }
    }
    
    if (detectedSection) {
        const detectedList = document.getElementById('detected-variables-list');
        if (detectedVars.length > 0) {
            detectedList.innerHTML = '';
            detectedVars.forEach(varName => {
                const badge = document.createElement('span');
                badge.className = 'badge bg-info variable-badge';
                badge.textContent = '{{' + varName + '}}';
                badge.title = 'Click to add to required variables';
                badge.style.cursor = 'pointer';
                badge.addEventListener('click', function() {
                    addVariableToList(varName);
                });
                detectedList.appendChild(badge);
            });
            detectedSection.style.display = 'block';
        } else {
            detectedSection.style.display = 'none';
        }
    }
    
    updateDefaultVariablesJSON(detectedVars);
}

function updateDefaultVariablesJSON(detectedVars = null) {
    if (!detectedVars) {
        detectedVars = detectVariablesFromContent();
    }
    
    const defaultVarsField = document.getElementById('default_variables_json');
    let currentJson = {};
    
    try {
        const currentValue = defaultVarsField.value.trim();
        if (currentValue) {
            currentJson = JSON.parse(currentValue);
        }
    } catch (e) {
        currentJson = {};
    }
    
    let hasNewVars = false;
    detectedVars.forEach(varName => {
        if (!(varName in currentJson)) {
            currentJson[varName] = "";
            hasNewVars = true;
        }
    });
    
    if (hasNewVars || defaultVarsField.value.trim() === '') {
        defaultVarsField.value = JSON.stringify(currentJson, null, 2);
    }
}

function isSystemVariable(variable) {
    const systemVars = ['app_name', 'app_url', 'current_date', 'current_time', 'current_datetime', 'year', 'month', 'day'];
    return systemVars.includes(variable);
}

function generateDefaultVariablesJSON() {
    const detectedVars = detectVariablesFromContent();
    const defaultVarsField = document.getElementById('default_variables_json');
    
    if (detectedVars.length === 0) {
        alert('No variables detected in template content. Please add some variables first.');
        return;
    }
    
    const currentValue = defaultVarsField.value.trim();
    if (currentValue && !confirm('This will overwrite existing default variables. Continue?')) {
        return;
    }
    
    const jsonObject = {};
    detectedVars.forEach(varName => {
        if (varName.includes('name')) {
            jsonObject[varName] = 'Sample Name';
        } else if (varName.includes('email')) {
            jsonObject[varName] = 'sample@example.com';
        } else if (varName.includes('date')) {
            jsonObject[varName] = '2025-06-16';
        } else if (varName.includes('time')) {
            jsonObject[varName] = '10:30:00';
        } else if (varName.includes('url')) {
            jsonObject[varName] = 'https://example.com';
        } else if (varName.includes('company')) {
            jsonObject[varName] = 'Sample Company';
        } else if (varName.includes('message')) {
            jsonObject[varName] = 'Sample message content';
        } else {
            jsonObject[varName] = 'Sample value';
        }
    });
    
    defaultVarsField.value = JSON.stringify(jsonObject, null, 2);
    
    const successMsg = document.createElement('div');
    successMsg.className = 'alert alert-success mt-2';
    successMsg.innerHTML = '<i class="fas fa-check"></i> Generated default values for ' + detectedVars.length + ' variables';
    defaultVarsField.parentNode.insertBefore(successMsg, defaultVarsField.nextSibling);
    
    setTimeout(() => {
        if (successMsg.parentNode) {
            successMsg.parentNode.removeChild(successMsg);
        }
    }, 3000);
}

function validateTemplate() {
    const resultDiv = document.getElementById('validation-result');
    resultDiv.innerHTML = '<div class="text-info"><i class="fas fa-spinner fa-spin"></i> Validating...</div>';
    
    const subjectTemplate = document.getElementById('subject_template').value;
    const bodyHtmlTemplate = document.getElementById('body_html_template').value;
    const bodyTextTemplate = document.getElementById('body_text_template').value;
    
    const allContent = subjectTemplate + ' ' + bodyHtmlTemplate + ' ' + bodyTextTemplate;
    const errors = [];
    const variables = [];
    
    // Check for unmatched tags
    const ifMatches = (allContent.match(/\{\{#if\s+[^}]+\}\}/g) || []).length;
    const endIfMatches = (allContent.match(/\{\{\/if\}\}/g) || []).length;
    if (ifMatches !== endIfMatches) {
        errors.push('Unmatched {{#if}} and {{/if}} tags');
    }
    
    const eachMatches = (allContent.match(/\{\{#each\s+[^}]+\}\}/g) || []).length;
    const endEachMatches = (allContent.match(/\{\{\/each\}\}/g) || []).length;
    if (eachMatches !== endEachMatches) {
        errors.push('Unmatched {{#each}} and {{/each}} tags');
    }
    
    // Extract variables
    const variableMatches = allContent.match(/\{\{([^}#\/][^}]*)\}\}/g);
    if (variableMatches) {
        variableMatches.forEach(match => {
            const varName = match.replace(/[{}]/g, '').split(':')[0].split('|')[0].trim();
            if (!variables.includes(varName) && !isSystemVariable(varName)) {
                variables.push(varName);
            }
        });
    }
    
    setTimeout(() => {
        let html = '';
        if (errors.length === 0) {
            html = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Template syntax is valid</div>';
            
            if (variables.length > 0) {
                html += '<div class="mt-2"><strong>Found variables:</strong><br>';
                variables.forEach(variable => {
                    html += '<span class="badge bg-secondary me-1">' + variable + '</span>';
                });
                html += '</div>';
            }
        } else {
            html = '<div class="alert alert-warning"><strong>Issues found:</strong><ul class="mb-0">';
            errors.forEach(error => {
                html += '<li>' + error + '</li>';
            });
            html += '</ul></div>';
        }
        
        resultDiv.innerHTML = html;
    }, 500);
}

function generatePreview() {
    let defaultVars = {};
    try {
        const defaultVarsText = document.getElementById('default_variables_json').value;
        if (defaultVarsText.trim()) {
            defaultVars = JSON.parse(defaultVarsText);
        }
    } catch (e) {
        console.warn('Invalid JSON in default variables');
    }
    
    const sampleData = {
        user_name: 'John Doe',
        user_email: 'john.doe@company.com',
        user_first_name: 'John',
        user_last_name: 'Doe',
        message: 'This is a sample message',
        company: 'Your Company',
        date: '2025-06-16',
        ...defaultVars
    };
    
    fetch('{{ route("templates.preview", $template) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({sample_data: sampleData})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = '<div class="row">';
            
            if (data.preview.subject) {
                html += '<div class="col-12 mb-3"><h6>Subject:</h6><div class="alert alert-info">' + data.preview.subject + '</div></div>';
            }
            
            if (data.preview.body_html) {
                html += '<div class="col-md-6"><h6>HTML Preview:</h6><div class="border rounded p-3">' + data.preview.body_html + '</div></div>';
            }
            
            if (data.preview.body_text) {
                html += '<div class="col-md-6"><h6>Text Preview:</h6><div class="bg-light border rounded p-3"><pre>' + data.preview.body_text + '</pre></div></div>';
            }
            
            html += '</div>';
            
            document.getElementById('previewContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('previewModal')).show();
        } else {
            alert('Error generating preview: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generating preview');
    });
}

// Setup function
function setupVariableBadgeHandlers() {
    document.querySelectorAll('.variable-badge[data-variable]').forEach(badge => {
        badge.addEventListener('click', function() {
            const variable = this.getAttribute('data-variable');
            insertVariable(variable);
            
            // Visual feedback
            this.style.backgroundColor = '#0d6efd';
            this.style.color = 'white';
            setTimeout(() => {
                this.style.backgroundColor = '';
                this.style.color = '';
            }, 200);
        });
        
        badge.style.cursor = 'pointer';
        badge.title = 'Click to insert variable';
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize TinyMCE
    tinymce.init({
        selector: '#body_html_template',
        height: 300,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | code | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        setup: function (editor) {
            editor.on('focus', function () {
                lastFocusedElement = {
                    isTinyMCE: true,
                    editorId: editor.id,
                    editor: editor
                };
            });
            
            editor.on('click', function () {
                lastFocusedElement = {
                    isTinyMCE: true,
                    editorId: editor.id,
                    editor: editor
                };
            });
        }
    });

    // Setup input focus tracking
    const inputs = document.querySelectorAll('textarea:not(#body_html_template), input[type="text"]');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            lastFocusedElement = this;
        });
        
        input.addEventListener('click', function() {
            lastFocusedElement = this;
        });
    });

    // Setup variable badge handlers
    setupVariableBadgeHandlers();

    // Custom variable enter key
    document.getElementById('customVariable').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addCustomVariableFunction();
        }
    });

    // Preview button
    document.getElementById('previewBtn').addEventListener('click', function() {
        generatePreview();
    });

    // Form submission
    document.getElementById('templateForm').addEventListener('submit', function(e) {
        // Sync TinyMCE content
        if (tinymce.get('body_html_template')) {
            tinymce.get('body_html_template').save();
        }

        // Process default variables JSON
        const defaultVarsField = document.getElementById('default_variables_json');
        if (defaultVarsField.value.trim()) {
            try {
                const parsed = JSON.parse(defaultVarsField.value);
                
                if (typeof parsed === 'object' && !Array.isArray(parsed)) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'default_variables';
                    hiddenInput.value = JSON.stringify(parsed);
                    this.appendChild(hiddenInput);
                    
                    defaultVarsField.name = '';
                } else {
                    e.preventDefault();
                    alert('Default variables must be a JSON object, not an array. Example: {"variable": "value"}');
                    defaultVarsField.focus();
                    return false;
                }
            } catch (error) {
                e.preventDefault();
                alert('Invalid JSON format in default variables: ' + error.message);
                defaultVarsField.focus();
                return false;
            }
        } else {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'default_variables';
            hiddenInput.value = '{}';
            this.appendChild(hiddenInput);
            
            defaultVarsField.name = '';
        }
    });

    // Auto-detect variables setup
    function setupVariableDetection() {
        const subjectField = document.getElementById('subject_template');
        const textField = document.getElementById('body_text_template');
        
        [subjectField, textField].forEach(field => {
            field.addEventListener('input', debounce(updateDetectedVariables, 500));
        });
        
        setTimeout(() => {
            const htmlEditor = tinymce.get('body_html_template');
            if (htmlEditor) {
                htmlEditor.on('input', debounce(updateDetectedVariables, 500));
                htmlEditor.on('change', debounce(updateDetectedVariables, 500));
            }
        }, 1500);
    }

    // Initialize after delay
    setTimeout(() => {
        setupVariableDetection();
        updateDetectedVariables();
    }, 1000);
});
@endverbatim
</script>

@endpush