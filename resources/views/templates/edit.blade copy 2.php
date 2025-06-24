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
                                        <label for="slug" class="form-label">Slug</label>
                                        <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                               id="slug" name="slug" value="{{ old('slug', $template->slug) }}"
                                               placeholder="Auto-generated from name">
                                        <div class="form-text">Leave empty to auto-generate from name</div>
                                        @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
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
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" 
                                                   id="is_active" name="is_active" value="1" 
                                                   {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">
                                                Active Template
                                            </label>
                                        </div>
                                    </div>
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
                                              placeholder='{"variable_name": "default_value"}'>@php
$defaultVars = old('default_variables_json') ?: $template->default_variables;
if (empty($defaultVars) || (is_array($defaultVars) && count($defaultVars) === 0)) {
    echo '';
} else {
    echo is_string($defaultVars) ? $defaultVars : json_encode($defaultVars, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
@endphp</textarea>
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

<script src="{{ asset('js/template-editor2.js') }}"></script>

@endpush