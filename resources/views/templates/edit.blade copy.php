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
                                        <label for="type" class="form-label">Delivery Type <span class="text-danger">*</span></label>
                                        <select class="form-select @error('type') is-invalid @enderror" 
                                                id="type" name="type" required>
                                            <option value="">Select delivery type</option>
                                            <option value="email" {{ old('type', $template->type) === 'email' ? 'selected' : '' }}>Email Only</option>
                                            <option value="teams" {{ old('type', $template->type) === 'teams' ? 'selected' : '' }}>Microsoft Teams Only</option>
                                            <option value="both" {{ old('type', $template->type) === 'both' ? 'selected' : '' }}>Both Email & Teams</option>
                                        </select>
                                        @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
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
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject/Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                                           id="subject" name="subject" value="{{ old('subject', $template->subject) }}" 
                                           placeholder="Enter notification subject or title" required>
                                    @error('subject')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Email Content -->
                                <div id="email-content" style="display: none;">
                                    <div class="mb-3">
                                        <label for="body_html" class="form-label">HTML Content</label>
                                        <textarea class="form-control @error('body_html') is-invalid @enderror" 
                                                  id="body_html" name="body_html" rows="10">{{ old('body_html', $template->body_html) }}</textarea>
                                        @error('body_html')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="body_text" class="form-label">Plain Text Content</label>
                                        <textarea class="form-control @error('body_text') is-invalid @enderror" 
                                                  id="body_text" name="body_text" rows="8" 
                                                  placeholder="Plain text version for email clients that don't support HTML">{{ old('body_text', $template->body_text) }}</textarea>
                                        @error('body_text')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Teams Content -->
                                <div id="teams-content" style="display: none;">
                                    <div class="mb-3">
                                        <label for="teams_card_template" class="form-label">Teams Adaptive Card Template</label>
                                        <textarea class="form-control @error('teams_card_template') is-invalid @enderror" 
                                                  id="teams_card_template" name="teams_card_template" rows="15" 
                                                  placeholder="Enter JSON for Teams Adaptive Card">{{ old('teams_card_template', $template->teams_card_template ? json_encode($template->teams_card_template, JSON_PRETTY_PRINT) : '') }}</textarea>
                                        <div class="form-text">
                                            <a href="https://adaptivecards.io/designer/" target="_blank">
                                                <i class="fas fa-external-link-alt"></i> Use Adaptive Cards Designer
                                            </a>
                                        </div>
                                        @error('teams_card_template')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Current Variables -->
                        @if($template->variables && count($template->variables) > 0)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-tags me-2"></i>Current Variables
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($template->variables as $variable)
                                    <span class="badge bg-primary variable-badge">{!! '{{' . $variable . '}}' !!}</span>
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
                                        <div class="fw-bold text-success fs-4">{{ $template->is_active ? 'Active' : 'Inactive' }}</div>
                                        <small class="text-muted">Status</small>
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
                                        @verbatim
                                        <span class="badge bg-light text-dark variable-badge" data-variable="user.name">{{user.name}}</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="user.email">{{user.email}}</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="user.department">{{user.department}}</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="user.title">{{user.title}}</span>
                                        @endverbatim
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="small fw-bold text-uppercase">Date & Time</h6>
                                    <div class="d-flex flex-wrap gap-1">
                                        @verbatim
                                        <span class="badge bg-light text-dark variable-badge" data-variable="date">{{date}}</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="time">{{time}}</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="datetime">{{datetime}}</span>
                                        @endverbatim
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="small fw-bold text-uppercase">Common</h6>
                                    <div class="d-flex flex-wrap gap-1">
                                        @verbatim
                                        <span class="badge bg-light text-dark variable-badge" data-variable="title">{{title}}</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="message">{{message}}</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="company">{{company}}</span>
                                        <span class="badge bg-light text-dark variable-badge" data-variable="url">{{url}}</span>
                                        @endverbatim
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <h6 class="small fw-bold text-uppercase">Custom Variable</h6>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="customVariable" placeholder="variable.name">
                                        <button class="btn btn-outline-primary" type="button" id="addCustomVariable">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>

                                <hr>

                                <div>
                                    <h6 class="small fw-bold text-uppercase">Teams Card Templates</h6>
                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadTeamsTemplate('basic')">
                                            Basic Card
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadTeamsTemplate('hero')">
                                            Hero Card
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadTeamsTemplate('factset')">
                                            Fact Set Card
                                        </button>
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
{{-- <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script> --}}
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>

<script src="{{ asset('js/template-editor.js') }}"></script>

@endpush