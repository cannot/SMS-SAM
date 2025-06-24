@extends('layouts.app')

@section('title', 'Create New Template')

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
.step-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
}
.step {
    flex: 1;
    text-align: center;
    position: relative;
}
.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 15px;
    right: -50%;
    width: 100%;
    height: 2px;
    background-color: #dee2e6;
    z-index: 0;
}
.step.active:not(:last-child)::after {
    background-color: #0d6efd;
}
.step-circle {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: #dee2e6;
    color: #6c757d;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    position: relative;
    z-index: 1;
}
.step.active .step-circle {
    background-color: #0d6efd;
    color: white;
}
.step.completed .step-circle {
    background-color: #198754;
    color: white;
}
.quick-start-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.template-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}
.template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.border-dashed {
    border: 2px dashed #dee2e6 !important;
}
.content-tabs .nav-link {
    border-bottom: 2px solid transparent;
}
.content-tabs .nav-link.active {
    border-bottom-color: #0d6efd;
    background-color: transparent;
}
.syntax-highlight {
    background-color: #f8f9fa;
    border-left: 4px solid #0d6efd;
    padding: 0.75rem;
    border-radius: 0 0.25rem 0.25rem 0;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
}
.variable-badge {
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}

.variable-badge:hover {
    transform: scale(1.05);
    opacity: 0.9;
}

.variable-badge:active {
    transform: scale(0.95);
}

#content-preview {
    transition: all 0.3s ease;
}

/* Fix: Initially hide sections properly */
#templateGallery {
    display: none;
}

#mainForm {
    display: none;
}

#stepIndicator {
    display: none;
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
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        Create New Template
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('templates.index') }}">Templates</a></li>
                            <li class="breadcrumb-item active">Create New</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Templates
                    </a>
                </div>
            </div>

            <!-- Quick Start Options (เพิ่มส่วนนี้ที่หายไป) -->
            <div id="quickStart">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card quick-start-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-rocket me-2"></i>Quick Start
                                </h5>
                                <p class="card-text mb-3">Choose how you'd like to create your template:</p>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <button class="btn btn-light w-100" onclick="startFromScratch()">
                                            <i class="fas fa-file-plus text-primary me-2"></i>
                                            <div>
                                                <div class="fw-bold">Start from Scratch</div>
                                                <small class="text-muted">Create a completely new template</small>
                                            </div>
                                        </button>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <button class="btn btn-light w-100" onclick="showTemplateGallery()">
                                            <i class="fas fa-th-large text-success me-2"></i>
                                            <div>
                                                <div class="fw-bold">Use Template Gallery</div>
                                                <small class="text-muted">Start with predefined templates</small>
                                            </div>
                                        </button>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <button class="btn btn-light w-100" onclick="importTemplate()">
                                            <i class="fas fa-upload text-info me-2"></i>
                                            <div>
                                                <div class="fw-bold">Import Template</div>
                                                <small class="text-muted">Upload existing template file</small>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator" id="stepIndicator">
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label small mt-1">Basic Info</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label small mt-1">Content</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label small mt-1">Variables</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label small mt-1">Preview</div>
                </div>
            </div>

            <!-- Template Gallery (Initially Hidden) -->
            <div id="templateGallery">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-th-large me-2"></i>Template Gallery
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- System Alert Template -->
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card template-card h-100" onclick="useTemplate('system_alert')">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0">System Alert</h6>
                                                    <span class="badge bg-danger">System</span>
                                                </div>
                                                <p class="card-text small text-muted mb-3">Critical system notifications and alerts for emergency situations</p>
                                                <div class="d-flex flex-wrap gap-1 mb-3">
                                                    <span class="badge bg-light text-dark"><i class="fas fa-envelope me-1"></i>Email</span>
                                                    <span class="badge bg-light text-dark"><i class="fab fa-microsoft me-1"></i>Teams</span>
                                                    <span class="badge bg-light text-dark"><i class="fas fa-sms me-1"></i>SMS</span>
                                                </div>
                                                
                                                <div class="syntax-highlight small">
                                                    Subject: [@{{priority}}] System Alert<br>
                                                    Variables: priority, subject, message
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Marketing Email Template -->
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card template-card h-100" onclick="useTemplate('marketing_email')">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0">Marketing Email</h6>
                                                    <span class="badge bg-info">Marketing</span>
                                                </div>
                                                <p class="card-text small text-muted mb-3">Promotional emails and newsletters with beautiful design</p>
                                                <div class="d-flex flex-wrap gap-1 mb-3">
                                                    <span class="badge bg-light text-dark"><i class="fas fa-envelope me-1"></i>Email</span>
                                                </div>
                                                
                                                    <div class="syntax-highlight small">
                                                        Subject: @{{subject}} - @{{company}}<br>
                                                        Variables: subject, message, company
                                                    </div>
                                                
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Meeting Reminder Template -->
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card template-card h-100" onclick="useTemplate('meeting_reminder')">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0">Meeting Reminder</h6>
                                                    <span class="badge bg-success">Operational</span>
                                                </div>
                                                <p class="card-text small text-muted mb-3">Meeting reminders and calendar notifications</p>
                                                <div class="d-flex flex-wrap gap-1 mb-3">
                                                    <span class="badge bg-light text-dark"><i class="fas fa-envelope me-1"></i>Email</span>
                                                    <span class="badge bg-light text-dark"><i class="fab fa-microsoft me-1"></i>Teams</span>
                                                </div>
                                                
                                                <div class="syntax-highlight small">
                                                    Subject: Reminder: @{{meeting_title}}<br>
                                                    Variables: meeting_title, meeting_date
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Update Template -->
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card template-card h-100" onclick="useTemplate('status_update')">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0">Status Update</h6>
                                                    <span class="badge bg-warning">Operational</span>
                                                </div>
                                                <p class="card-text small text-muted mb-3">Project and system status updates</p>
                                                <div class="d-flex flex-wrap gap-1 mb-3">
                                                    <span class="badge bg-light text-dark"><i class="fas fa-envelope me-1"></i>Email</span>
                                                    <span class="badge bg-light text-dark"><i class="fab fa-microsoft me-1"></i>Teams</span>
                                                </div>
                                                
                                                <div class="syntax-highlight small">
                                                    Subject: Status Update: @{{project_name}}<br>
                                                    Variables: project_name, status, message
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Welcome Message Template -->
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card template-card h-100" onclick="useTemplate('welcome_message')">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title mb-0">Welcome Message</h6>
                                                    <span class="badge bg-primary">Marketing</span>
                                                </div>
                                                <p class="card-text small text-muted mb-3">New user welcome and onboarding messages</p>
                                                <div class="d-flex flex-wrap gap-1 mb-3">
                                                    <span class="badge bg-light text-dark"><i class="fas fa-envelope me-1"></i>Email</span>
                                                    <span class="badge bg-light text-dark"><i class="fab fa-microsoft me-1"></i>Teams</span>
                                                </div>
                                                
                                                <div class="syntax-highlight small">
                                                    Subject: Welcome @{{user_name}}!<br>
                                                    Variables: user_name, welcome_message
                                                </div>
                                                    
                                                
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Custom Template -->
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card template-card h-100 border-dashed" onclick="startFromScratch()">
                                            <div class="card-body text-center d-flex flex-column justify-content-center">
                                                <i class="fas fa-plus-circle fa-3x text-muted mb-3"></i>
                                                <h6 class="card-title">Create Custom</h6>
                                                <p class="card-text small text-muted mb-0">Start with a blank template</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-3">
                                    <button class="btn btn-secondary" onclick="hideTemplateGallery()">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Form (Initially Hidden) -->
            <div id="mainForm">
                <form action="{{ route('templates.store') }}" method="POST" id="templateForm">
                    @csrf
                    
                    <div class="row">
                        <!-- Main Form -->
                        <div class="col-lg-8">
                            <!-- Step 1: Basic Information -->
                            <div class="form-step" data-step="1">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-info-circle me-2"></i>Basic Information
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="name" class="form-label">Template Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                       id="name" name="name" value="{{ old('name') }}" required>
                                                @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">A descriptive name for your template</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="slug" class="form-label">Slug</label>
                                                <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                                       id="slug" name="slug" value="{{ old('slug') }}"
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
                                                    <option value="">Select Category</option>
                                                    <option value="system" {{ old('category') === 'system' ? 'selected' : '' }}>System</option>
                                                    <option value="marketing" {{ old('category') === 'marketing' ? 'selected' : '' }}>Marketing</option>
                                                    <option value="operational" {{ old('category') === 'operational' ? 'selected' : '' }}>Operational</option>
                                                    <option value="emergency" {{ old('category') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                                                </select>
                                                @error('category')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                                <select class="form-select @error('priority') is-invalid @enderror" 
                                                        id="priority" name="priority" required>
                                                    <option value="">Select Priority</option>
                                                    <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                                    <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                                                    <option value="normal" {{ old('priority') === 'normal' ? 'selected' : '' }}>Normal</option>
                                                    <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                                                    <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                                </select>
                                                @error('priority')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                                      id="description" name="description" rows="3" 
                                                      placeholder="Brief description of what this template is used for">{{ old('description') }}</textarea>
                                            @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-8 mb-3">
                                                <label class="form-label">Supported Channels <span class="text-danger">*</span></label>
                                                <div class="mt-2">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                               type="checkbox" id="channel_email" name="supported_channels[]" value="email"
                                                               {{ in_array('email', old('supported_channels', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="channel_email">
                                                            <i class="fas fa-envelope me-1"></i>Email
                                                        </label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                               type="checkbox" id="channel_teams" name="supported_channels[]" value="teams"
                                                               {{ in_array('teams', old('supported_channels', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="channel_teams">
                                                            <i class="fab fa-microsoft me-1"></i>Microsoft Teams
                                                        </label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input @error('supported_channels') is-invalid @enderror" 
                                                               type="checkbox" id="channel_sms" name="supported_channels[]" value="sms"
                                                               {{ in_array('sms', old('supported_channels', [])) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="channel_sms">
                                                            <i class="fas fa-sms me-1"></i>SMS
                                                        </label>
                                                    </div>
                                                </div>
                                                @error('supported_channels')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                                <div class="form-text">Select at least one channel for notifications</div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="form-check form-switch mt-4">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="is_active" name="is_active" value="1" 
                                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="is_active">
                                                        <i class="fas fa-toggle-on me-1"></i>Active Template
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Content -->
                            <div class="form-step" data-step="2" style="display: none;">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-file-text me-2"></i>Template Content
                                        </h5>
                                    </div>
                                    
                                        
                                    <div class="card-body">

                                        <!-- Subject Template -->
                                        <div class="mb-4">
                                            <label for="subject_template" class="form-label">Subject Template <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('subject_template') is-invalid @enderror" 
                                                   id="subject_template" name="subject_template" 
                                                   value="{{ old('subject_template') }}" 
                                                   placeholder="Enter notification subject template" required>
                                            <div class="form-text">Use variables like @{{user_name}} or @{{message}} for dynamic content</div>
                                            @error('subject_template')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- Channel-specific content tabs -->
                                        <div class="mb-3">
                                            <ul class="nav nav-tabs content-tabs" id="contentTabs" role="tablist">
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link active" id="html-tab" data-bs-toggle="tab" data-bs-target="#html-content" type="button" role="tab">
                                                        <i class="fas fa-code me-1"></i>HTML Content
                                                        <span class="badge bg-primary ms-1">Email & Teams</span>
                                                    </button>
                                                </li>
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link" id="text-tab" data-bs-toggle="tab" data-bs-target="#text-content" type="button" role="tab">
                                                        <i class="fas fa-file-text me-1"></i>Text Content
                                                        <span class="badge bg-success ms-1">SMS & Fallback</span>
                                                    </button>
                                                </li>
                                            </ul>
                                            <div class="tab-content border border-top-0 p-3">
                                                <!-- HTML Content -->
                                                <div class="tab-pane fade show active" id="html-content" role="tabpanel">
                                                    <label for="body_html_template" class="form-label">HTML Template</label>
                                                    <textarea class="form-control @error('body_html_template') is-invalid @enderror" 
                                                              id="body_html_template" name="body_html_template" rows="15">{{ old('body_html_template') }}</textarea>
                                                    <div class="form-text">
                                                        For email and Teams rich content. You can use HTML tags and inline CSS.
                                                        <a href="#" onclick="showHtmlExamples()" class="ms-2">View Examples</a>
                                                    </div>
                                                    @error('body_html_template')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <!-- Text Content -->
                                                <div class="tab-pane fade" id="text-content" role="tabpanel">
                                                    <label for="body_text_template" class="form-label">Text Template</label>
                                                    <textarea class="form-control @error('body_text_template') is-invalid @enderror" 
                                                              id="body_text_template" name="body_text_template" rows="12" 
                                                              placeholder="Plain text version for SMS and fallback">{{ old('body_text_template') }}</textarea>
                                                    <div class="form-text">
                                                        For SMS and plain text fallback. Keep it concise for SMS (160 chars recommended).
                                                        <span id="text-char-count" class="badge bg-secondary ms-2">0 characters</span>
                                                    </div>
                                                    @error('body_text_template')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Content Tools -->
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <button type="button" class="btn btn-outline-info btn-sm" onclick="validateTemplate()">
                                                <i class="fas fa-check-circle me-1"></i>Validate Syntax
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-sm" onclick="generateSampleContent()">
                                                <i class="fas fa-magic me-1"></i>Generate Sample
                                            </button>
                                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="previewContent()">
                                                <i class="fas fa-eye me-1"></i>Quick Preview
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearContent()">
                                                <i class="fas fa-eraser me-1"></i>Clear All
                                            </button>
                                        </div>
                                        <div id="validation-result" class="mt-2"></div>
                                        <div id="content-preview" class="mt-2"></div>
                                    </div>
                                    
                                </div>
                            </div>

                            <!-- Step 3: Variables -->
                            <div class="form-step" data-step="3" style="display: none;">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-tags me-2"></i>Variables Configuration
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Auto-detected Variables -->
                                        <div id="detected-variables-section" class="mb-4" style="display: none;">
                                            <h6 class="text-info">
                                                <i class="fas fa-search me-1"></i>Detected Variables
                                            </h6>
                                            <div id="detected-variables-list" class="d-flex flex-wrap gap-1 mb-2"></div>
                                            <small class="text-muted">Variables found in your template content. Click to add to required variables.</small>
                                        </div>

                                        <!-- Required Variables -->
                                        <div class="mb-4">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label mb-0">Required Variables</label>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addVariable()">
                                                    <i class="fas fa-plus me-1"></i>Add Variable
                                                </button>
                                            </div>
                                            <div id="variables-container">
                                                <!-- Variables will be added here dynamically -->
                                            </div>
                                            <div class="form-text">Specify which variables must be provided when sending notifications</div>
                                        </div>

                                        <!-- Default Variables -->
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label for="default_variables_json" class="form-label mb-0">Default Variables (JSON format)</label>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="generateDefaultVariablesJSON()">
                                                        <i class="fas fa-magic me-1"></i>Auto Generate
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="formatJSON()">
                                                        <i class="fas fa-code me-1"></i>Format JSON
                                                    </button>
                                                </div>
                                            </div>
                                            <textarea class="form-control @error('default_variables') is-invalid @enderror" 
                                                      id="default_variables_json" name="default_variables_json" rows="8" 
                                                      placeholder='{"variable_name": "default_value", "user_name": "Sample User", "message": "Sample message content"}'>{{ old('default_variables_json') }}</textarea>
                                            @error('default_variables')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">JSON object (not array) for default variable values. These will be used when variables are not provided.</div>
                                        </div>

                                        <!-- Variable Validation -->
                                        <div class="mb-3">
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="validateVariables()">
                                                <i class="fas fa-check-circle me-1"></i>Validate Variables
                                            </button>
                                            <div id="variable-validation-result" class="mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Preview & Save -->
                            <div class="form-step" data-step="4" style="display: none;">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-eye me-2"></i>Preview & Save
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-cog me-1"></i>Test with Sample Data</h6>
                                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadSampleDataForPreview()">
                                                    <i class="fas fa-play me-1"></i>Load Sample Data
                                                </button>
                                            </div>
                                            <div class="col-md-6 text-end">
                                                <button type="button" class="btn btn-outline-success btn-sm" onclick="refreshPreview()">
                                                    <i class="fas fa-sync me-1"></i>Refresh Preview
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div id="final-preview">
                                            <div class="text-center py-4">
                                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                                <p class="mt-2 text-muted">Loading preview...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Navigation Buttons -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary" id="prevBtn" onclick="previousStep()" style="display: none;">
                                            <i class="fas fa-arrow-left me-2"></i>Previous
                                        </button>
                                        <div class="ms-auto d-flex gap-2">
                                            <button type="button" class="btn btn-outline-secondary" onclick="saveDraft()" id="draftBtn">
                                                <i class="fas fa-save me-2"></i>Save Draft
                                            </button>
                                            <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextStep()">
                                                Next<i class="fas fa-arrow-right ms-2"></i>
                                            </button>
                                            <button type="submit" class="btn btn-success" id="saveBtn" style="display: none;">
                                                <i class="fas fa-check me-2"></i>Create Template
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-4">
                            <!-- Progress Card -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-tasks me-2"></i>Creation Progress
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span>Progress</span>
                                            <span id="progress-percentage">25%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 25%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="small">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2" id="step1-check" style="display: none;"></i>
                                            <i class="fas fa-circle text-muted me-2" id="step1-pending"></i>
                                            <span>Basic Information</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2" id="step2-check" style="display: none;"></i>
                                            <i class="fas fa-circle text-muted me-2" id="step2-pending"></i>
                                            <span>Template Content</span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-check-circle text-success me-2" id="step3-check" style="display: none;"></i>
                                            <i class="fas fa-circle text-muted me-2" id="step3-pending"></i>
                                            <span>Variables Setup</span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle text-success me-2" id="step4-check" style="display: none;"></i>
                                            <i class="fas fa-circle text-muted me-2" id="step4-pending"></i>
                                            <span>Review & Save</span>
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
                                            
                                            <span class="badge bg-info text-white variable-badge" data-variable="user_name" title="User's full name">@{{user_name}}</span>
                                            <span class="badge bg-info text-white variable-badge" data-variable="user_email" title="User's email address">@{{user_email}}</span>
                                            <span class="badge bg-info text-white variable-badge" data-variable="user_department" title="User's department">@{{user_department}}</span>
                                            <span class="badge bg-info text-white variable-badge" data-variable="user_title" title="User's job title">@{{user_title}}</span>
                                            <span class="badge bg-info text-white variable-badge" data-variable="user_first_name" title="User's first name">@{{user_first_name}}</span>
                                            <span class="badge bg-info text-white variable-badge" data-variable="user_last_name" title="User's last name">@{{user_last_name}}</span>
                                            
                                        </div>
                                        <small class="text-muted">Live data from LDAP/AD</small>
                                    </div>

                                    <div class="mb-3">
                                        <h6 class="small fw-bold text-uppercase">System Variables</h6>
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="badge bg-success text-white variable-badge" data-variable="current_date" title="Current date">@{{current_date}}</span>
                                            <span class="badge bg-success text-white variable-badge" data-variable="current_time" title="Current time">@{{current_time}}</span>
                                            <span class="badge bg-success text-white variable-badge" data-variable="current_datetime" title="Current date and time">@{{current_datetime}}</span>
                                            <span class="badge bg-success text-white variable-badge" data-variable="app_name" title="Application name">@{{app_name}}</span>
                                            <span class="badge bg-success text-white variable-badge" data-variable="app_url" title="Application URL">@{{app_url}}</span>
                                            <span class="badge bg-success text-white variable-badge" data-variable="year" title="Current year">@{{year}}</span>
                                            <span class="badge bg-success text-white variable-badge" data-variable="month" title="Current month">@{{month}}</span>
                                            <span class="badge bg-success text-white variable-badge" data-variable="day" title="Current day">@{{day}}</span>
                                        </div>
                                        <small class="text-muted">Automatically populated</small>
                                    </div>

                                    <div class="mb-3">
                                        <h6 class="small fw-bold text-uppercase">Custom Variables</h6>
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="badge bg-warning text-dark variable-badge" data-variable="message" title="Custom message content">@{{message}}</span>
                                            <span class="badge bg-warning text-dark variable-badge" data-variable="subject" title="Custom subject">@{{subject}}</span>
                                            <span class="badge bg-warning text-dark variable-badge" data-variable="url" title="Custom URL">@{{url}}</span>
                                            <span class="badge bg-warning text-dark variable-badge" data-variable="priority" title="Priority level">@{{priority}}</span>
                                            <span class="badge bg-warning text-dark variable-badge" data-variable="company" title="Company name">@{{company}}</span>
                                            <span class="badge bg-warning text-dark variable-badge" data-variable="status" title="Status">@{{status}}</span>
                                            <span class="badge bg-warning text-dark variable-badge" data-variable="deadline" title="Deadline">@{{deadline}}</span>
                                            <span class="badge bg-warning text-dark variable-badge" data-variable="amount" title="Amount">@{{amount}}</span>
                                        </div>
                                        <small class="text-muted">User-defined variables</small>
                                    </div>

                                    <div class="mb-3">
                                        <h6 class="small fw-bold text-uppercase">Add Custom Variable</h6>
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
                                        <h6 class="small fw-bold text-uppercase">Syntax Guide</h6>
                                        <div class="small">
                                            
                                            <p class="mb-1"><code>@{{variable}}</code> - Simple variable</p>
                                            <p class="mb-1"><code>@{{#if variable}}text@{{/if}}</code> - Conditional</p>
                                            <p class="mb-1"><code>@{{#each items}}@{{this}}@{{/each}}</code> - Loop</p>
                                            <p class="mb-0"><code>@{{variable|default}}</code> - Default value</p>
                                            
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
</div>

<!-- Modals -->
<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">
                    <i class="bi bi-eye"></i> Template Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="bi bi-envelope"></i> Email Preview</h6>
                        <div class="border rounded p-3 mb-3" style="min-height: 300px; background: #f8f9fa;">
                            <div id="emailPreview">
                                <p class="text-muted text-center mt-5">Content will appear here when you preview</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-microsoft-teams"></i> Teams Preview</h6>
                        <div class="border rounded p-3 mb-3" style="min-height: 300px; background: #f8f9fa;">
                            <div id="teamsPreview">
                                <p class="text-muted text-center mt-5">Content will appear here when you preview</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveTemplate()">
                    <i class="bi bi-check-lg"></i> Looks Good, Save Template
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Variable Helper Modal -->
<div class="modal fade" id="variableHelperModal" tabindex="-1" aria-labelledby="variableHelperModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="variableHelperModalLabel">
                    <i class="bi bi-code-square"></i> Available Variables
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>System Variables</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Variable</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Placeholder for system variables --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Custom Variables</h6>
                        <div id="customVariablesList">
                            <p class="text-muted">Add custom variables in Step 3 to see them here</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>

<script src="{{ asset('js/template-creator.js') }}" defer></script>

@endpush