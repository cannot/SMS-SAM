@extends('layouts.app')

@section('title', $template->name)

@push('styles')
<style>
.template-content {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}
.variable-badge {
    font-size: 0.875em;
}
.code-block {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    white-space: pre-wrap;
    max-height: 400px;
    overflow-y: auto;
}
.usage-stats {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $template->name }}</h1>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="badge bg-{{ $template->type === 'both' ? 'primary' : ($template->type === 'email' ? 'info' : 'success') }} fs-6">
                            {{ $template->type_display }}
                        </span>
                        <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }} fs-6">
                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <small class="text-muted">
                            Created {{ $template->created_at->diffForHumans() }} by {{ $template->creator->name ?? 'Unknown' }}
                        </small>
                    </div>
                    @if($template->description)
                    <p class="text-muted mb-0">{{ $template->description }}</p>
                    @endif
                </div>
                
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="previewTemplate()">
                        <i class="fas fa-eye me-2"></i>Preview
                    </button>
                    <a href="{{ route('templates.edit', $template) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Edit
                    </a>
                    <form action="{{ route('templates.duplicate', $template) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">
                            <i class="fas fa-copy me-2"></i>Duplicate
                        </button>
                    </form>
                    <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Template Content -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Template Content</h5>
                        </div>
                        <div class="card-body">
                            <!-- Subject -->
                            <div class="mb-4">
                                <h6 class="fw-bold">Subject/Title:</h6>
                                <div class="template-content">
                                    {{ $template->subject }}
                                </div>
                            </div>

                            <!-- Email Content -->
                            @if($template->canSendEmail())
                            <div class="mb-4">
                                <h6 class="fw-bold">Email Content:</h6>
                                
                                @if($template->body_html)
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">HTML Version:</label>
                                    <div class="template-content">
                                        {!! $template->body_html !!}
                                    </div>
                                </div>
                                @endif

                                @if($template->body_text)
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">Plain Text Version:</label>
                                    <div class="template-content" style="white-space: pre-wrap;">{{ $template->body_text }}</div>
                                </div>
                                @endif

                                @if(!$template->body_html && !$template->body_text)
                                <div class="text-muted">No email content defined</div>
                                @endif
                            </div>
                            @endif

                            <!-- Teams Content -->
                            @if($template->canSendTeams())
                            <div class="mb-4">
                                <h6 class="fw-bold">Microsoft Teams Content:</h6>
                                
                                @if($template->teams_card_template)
                                <div class="mb-3">
                                    <label class="small text-muted fw-bold">Adaptive Card Template:</label>
                                    <div class="code-block">{{ json_encode($template->teams_card_template, JSON_PRETTY_PRINT) }}</div>
                                </div>
                                @else
                                <div class="text-muted">No Teams card template defined</div>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Variables -->
                    @if($template->variables && count($template->variables) > 0)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-code me-2"></i>Template Variables
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                This template uses {{ count($template->variables) }} variable(s). 
                                When sending notifications, these placeholders will be replaced with actual data.
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($template->variables as $variable)
                                <span class="badge bg-primary variable-badge">{!! '{{' . $variable . '}}' !!}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Recent Usage -->
                    @if($template->notifications()->exists())
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>Recent Usage
                            </h5>
                            <small class="text-muted">Last 10 notifications</small>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Recipients</th>
                                            <th>Status</th>
                                            <th>Channels</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($template->notifications()->latest()->limit(10)->get() as $notification)
                                        <tr>
                                            <td>{{ $notification->created_at->format('M d, Y H:i') }}</td>
                                            <td>
                                                @if($notification->notification_groups)
                                                    {{ count($notification->notification_groups) }} group(s)
                                                @else
                                                    Individual recipients
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $notification->status === 'sent' ? 'success' : ($notification->status === 'failed' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($notification->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($notification->channels)
                                                    @foreach($notification->channels as $channel)
                                                    <span class="badge bg-light text-dark">{{ $channel }}</span>
                                                    @endforeach
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Usage Statistics -->
                    <div class="card mb-4">
                        <div class="card-body usage-stats text-center">
                            <h6 class="card-title text-white mb-3">
                                <i class="fas fa-chart-bar me-2"></i>Usage Statistics
                            </h6>
                            <div class="row">
                                <div class="col-6">
                                    <div class="fs-2 fw-bold">{{ $template->notifications()->count() }}</div>
                                    <small>Total Sent</small>
                                </div>
                                <div class="col-6">
                                    <div class="fs-2 fw-bold">
                                        @if($template->notifications()->exists())
                                            {{ $template->notifications()->latest()->first()->created_at->diffInDays() }}d
                                        @else
                                            -
                                        @endif
                                    </div>
                                    <small>Days Since Last Use</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Template Details -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Template Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row small">
                                <div class="col-5 text-muted">Slug:</div>
                                <div class="col-7"><code>{{ $template->slug }}</code></div>
                            </div>
                            <hr class="my-2">
                            <div class="row small">
                                <div class="col-5 text-muted">Type:</div>
                                <div class="col-7">{{ $template->type_display }}</div>
                            </div>
                            <hr class="my-2">
                            <div class="row small">
                                <div class="col-5 text-muted">Status:</div>
                                <div class="col-7">
                                    <span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row small">
                                <div class="col-5 text-muted">Variables:</div>
                                <div class="col-7">{{ $template->variables ? count($template->variables) : 0 }}</div>
                            </div>
                            <hr class="my-2">
                            <div class="row small">
                                <div class="col-5 text-muted">Created:</div>
                                <div class="col-7">{{ $template->created_at->format('M d, Y') }}</div>
                            </div>
                            <hr class="my-2">
                            <div class="row small">
                                <div class="col-5 text-muted">Updated:</div>
                                <div class="col-7">{{ $template->updated_at->format('M d, Y') }}</div>
                            </div>
                            <hr class="my-2">
                            <div class="row small">
                                <div class="col-5 text-muted">Creator:</div>
                                <div class="col-7">{{ $template->creator->name ?? 'Unknown' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-cog me-2"></i>Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('templates.edit', $template) }}" class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>Edit Template
                                </a>
                                
                                <form action="{{ route('templates.toggle-status', $template) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-{{ $template->is_active ? 'danger' : 'success' }} w-100">
                                        <i class="fas fa-{{ $template->is_active ? 'pause' : 'play' }} me-2"></i>
                                        {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>

                                <form action="{{ route('templates.duplicate', $template) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-copy me-2"></i>Duplicate Template
                                    </button>
                                </form>

                                @if($template->notifications()->count() === 0)
                                <form action="{{ route('templates.destroy', $template) }}" method="POST" 
                                      onsubmit="return confirm('Are you sure you want to delete this template? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="fas fa-trash me-2"></i>Delete Template
                                    </button>
                                </form>
                                @else
                                <button type="button" class="btn btn-outline-danger w-100" disabled title="Cannot delete template that is being used">
                                    <i class="fas fa-trash me-2"></i>Delete Template
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Preview: {{ $template->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="previewContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
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
function previewTemplate() {
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    const previewContent = document.getElementById('previewContent');
    
    // Show loading
    previewContent.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch preview
    fetch(`{{ route('templates.preview', $template) }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = `
                <div class="row">
                    <div class="col-12 mb-3">
                        <h6>Subject/Title:</h6>
                        <div class="bg-light p-2 rounded">${data.preview.subject}</div>
                    </div>
            `;
            
            if (data.preview.body_html) {
                html += `
                    <div class="col-12 mb-3">
                        <h6>HTML Email Content:</h6>
                        <div class="border p-3 rounded" style="max-height: 300px; overflow-y: auto;">${data.preview.body_html}</div>
                    </div>
                `;
            }
            
            if (data.preview.body_text) {
                html += `
                    <div class="col-12 mb-3">
                        <h6>Plain Text Email Content:</h6>
                        <div class="bg-light p-2 rounded" style="white-space: pre-wrap; max-height: 300px; overflow-y: auto;">${data.preview.body_text}</div>
                    </div>
                `;
            }
            
            if (data.preview.teams_card) {
                html += `
                    <div class="col-12 mb-3">
                        <h6>Teams Adaptive Card:</h6>
                        <div class="bg-light p-2 rounded" style="max-height: 300px; overflow-y: auto;">
                            <pre><code>${JSON.stringify(data.preview.teams_card, null, 2)}</code></pre>
                        </div>
                    </div>
                `;
            }

            @verbatim
            const badgesHtml = Object.entries(data.sample_data)
                .map(([key, value]) => `<span class="badge bg-light text-dark me-1">{{${key}}} = "${value}"</span>`)
                .join('');
            @endverbatim

            html += `
                <div class="col-12">
                    <h6>Sample Data Used:</h6>
                    <div class="small text-muted">
                        ${badgesHtml}
                    </div>
                </div>
            </div>
            `;
            
            previewContent.innerHTML = html;
        } else {
            previewContent.innerHTML = '<div class="alert alert-danger">Failed to load preview</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        previewContent.innerHTML = '<div class="alert alert-danger">Error loading preview</div>';
    });
}
</script>
@endpush