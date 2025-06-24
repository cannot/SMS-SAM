@extends('layouts.app')

@section('title', 'Notification Templates')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Notification Templates</h1>
                    <p class="text-muted">Manage email and Teams notification templates</p>
                </div>
                <a href="{{ route('templates.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Template
                </a>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('templates.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search templates..." 
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="email" {{ request('type') === 'email' ? 'selected' : '' }}>Email Only</option>
                                <option value="teams" {{ request('type') === 'teams' ? 'selected' : '' }}>Teams Only</option>
                                <option value="both" {{ request('type') === 'both' ? 'selected' : '' }}>Email & Teams</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Templates Table -->
            <div class="card">
                <div class="card-body">
                    @if($templates->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Template</th>
                                        <th>Type</th>
                                        <th>Subject</th>
                                        <th>Variables</th>
                                        <th>Usage</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $template)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $template->name }}</strong>
                                                @if($template->description)
                                                    <br><small class="text-muted">{{ Str::limit($template->description, 50) }}</small>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $template->type === 'both' ? 'primary' : ($template->type === 'email' ? 'info' : 'success') }}">
                                                {{ $template->type_display }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $template->subject }}">
                                                {{ $template->subject }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($template->variables && count($template->variables) > 0)
                                                <span class="badge bg-light text-dark me-1">{{ count($template->variables) }} vars</span>
                                                <small class="text-muted d-block">
                                                    {{ implode(', ', array_slice($template->variables, 0, 3)) }}
                                                    @if(count($template->variables) > 3)
                                                        <span class="text-muted">...</span>
                                                    @endif
                                                </small>
                                            @else
                                                <span class="text-muted">No variables</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php $stats = $template->usage_stats @endphp
                                            <div class="small">
                                                <strong>{{ $stats['total_sent'] }}</strong> sent
                                                @if($stats['last_used'])
                                                    <br><span class="text-muted">Last: {{ $stats['last_used']->diffForHumans() }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <form action="{{ route('templates.toggle', $template) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-{{ $template->is_active ? 'success' : 'secondary' }} btn-toggle-status">
                                                    <i class="fas fa-{{ $template->is_active ? 'check' : 'times' }}"></i>
                                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="small">
                                                {{ $template->created_at->format('M d, Y') }}
                                                <br><span class="text-muted">{{ $template->creator->name ?? 'Unknown' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('templates.show', $template) }}" 
                                                   class="btn btn-outline-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('templates.edit', $template) }}" 
                                                   class="btn btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-secondary" 
                                                        title="Preview" onclick="previewTemplate({{ $template->id }})">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                                <form action="{{ route('templates.duplicate', $template) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-primary" title="Duplicate">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </form>
                                                @if($template->notifications()->count() === 0)
                                                <form action="{{ route('templates.destroy', $template) }}" method="POST" 
                                                      class="d-inline" onsubmit="return confirm('Are you sure you want to delete this template?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Showing {{ $templates->firstItem() }} to {{ $templates->lastItem() }} of {{ $templates->total() }} results
                            </div>
                            {{ $templates->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                            <h5>No templates found</h5>
                            <p class="text-muted">
                                @if(request()->hasAny(['search', 'type', 'status']))
                                    No templates match your current filters.
                                    <a href="{{ route('templates.index') }}">Clear filters</a> to see all templates.
                                @else
                                    Get started by creating your first notification template.
                                @endif
                            </p>
                            @if(!request()->hasAny(['search', 'type', 'status']))
                            <a href="{{ route('templates.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create First Template
                            </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Preview</h5>
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
function previewTemplate(templateId) {
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
    fetch(`/templates/${templateId}/preview`, {
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
                        <h6>Subject:</h6>
                        <div class="bg-light p-2 rounded">${data.preview.subject}</div>
                    </div>
            `;
            
            if (data.preview.body_html) {
                html += `
                    <div class="col-12 mb-3">
                        <h6>HTML Content:</h6>
                        <div class="border p-3 rounded">${data.preview.body_html}</div>
                    </div>
                `;
            }
            
            if (data.preview.body_text) {
                html += `
                    <div class="col-12 mb-3">
                        <h6>Text Content:</h6>
                        <div class="bg-light p-2 rounded" style="white-space: pre-wrap;">${data.preview.body_text}</div>
                    </div>
                `;
            }
            
            if (data.preview.teams_card) {
                html += `
                    <div class="col-12 mb-3">
                        <h6>Teams Card:</h6>
                        <div class="bg-light p-2 rounded">
                            <pre><code>${JSON.stringify(data.preview.teams_card, null, 2)}</code></pre>
                        </div>
                    </div>
                `;
            }
            
            @verbatim
            const sampleData = data.sample_data || {};
            const badges = Object.entries(sampleData)
                .map(([varName, varValue]) => 
                    `<span class="badge bg-light text-dark me-1">{{${varName}}} = "${varValue}"</span>`
                )
                .join('');
            @endverbatim

            html += `
                <div class="col-12">
                    <h6>Sample Data Used:</h6>
                    <div class="small text-muted">
                        ${badges}
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

// Auto-submit toggle forms with confirmation
document.querySelectorAll('.btn-toggle-status').forEach(button => {
    button.addEventListener('click', function(e) {
        const isActive = this.querySelector('i').classList.contains('fa-check');
        const action = isActive ? 'deactivate' : 'activate';
        
        if (!confirm(`Are you sure you want to ${action} this template?`)) {
            e.preventDefault();
        }
    });
});
</script>
@endpush