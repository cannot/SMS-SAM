@extends('layouts.app')

@section('title', 'Notification Templates')

@section('content')
    <div class="container-fluid">
        
        <div class="page-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-swatchbook me-3"></i> Notification Templates</h2>
                        <p class="mb-0">Manage notification templates for different types of messages</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal"
                                data-bs-target="#bulkActionModal">
                                <i class="fas fa-tasks me-2"></i>Bulk Actions
                            </button>
                            <a href="{{ route('templates.create') }}" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-plus me-2"></i>Create Template
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('templates.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search"
                            value="{{ request('search') }}" placeholder="Search templates...">
                    </div>
                    <div class="col-md-2">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">All Categories</option>
                            <option value="system" {{ request('category') == 'system' ? 'selected' : '' }}>System</option>
                            <option value="user" {{ request('category') == 'user' ? 'selected' : '' }}>User</option>
                            <option value="alert" {{ request('category') == 'alert' ? 'selected' : '' }}>Alert</option>
                            <option value="marketing" {{ request('category') == 'marketing' ? 'selected' : '' }}>Marketing
                            </option>
                            <option value="transactional" {{ request('category') == 'transactional' ? 'selected' : '' }}>
                                Transactional</option>
                            <option value="reminder" {{ request('category') == 'reminder' ? 'selected' : '' }}>Reminder
                            </option>
                            <option value="welcome" {{ request('category') == 'welcome' ? 'selected' : '' }}>Welcome
                            </option>
                            <option value="custom" {{ request('category') == 'custom' ? 'selected' : '' }}>Custom</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="">All Priorities</option>
                            <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="normal" {{ request('priority') == 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>High</option>
                            <option value="urgent" {{ request('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-outline-primary flex-fill">
                                <i class="fas fa-search me-2"></i>Filter
                            </button>
                            <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Templates</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Templates
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['active'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Categories</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['categories'] ?? 0 }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Most Used</div>
                                <div class="h6 mb-0 font-weight-bold text-gray-800">{{ $stats['most_used'] ?? 'N/A' }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-star fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates Table -->
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Templates List</h6>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted">{{ $templates->total() }} total templates</span>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('templates.export', ['format' => 'excel']) }}">
                                    <i class="fas fa-file-excel me-2"></i>Excel
                                </a></li>
                            <li><a class="dropdown-item" href="{{ route('templates.export', ['format' => 'pdf']) }}">
                                    <i class="fas fa-file-pdf me-2"></i>PDF
                                </a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                @if ($templates->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="text-decoration-none text-dark">
                                            Name
                                            @if (request('sort') == 'name')
                                                <i
                                                    class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Channels</th>
                                    <th>Status</th>
                                    <th>Usage</th>
                                    <th>
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'direction' => request('direction') == 'asc' ? 'desc' : 'asc']) }}"
                                            class="text-decoration-none text-dark">
                                            Created
                                            @if (request('sort') == 'created_at')
                                                <i
                                                    class="fas fa-sort-{{ request('direction') == 'asc' ? 'up' : 'down' }} ms-1"></i>
                                            @endif
                                        </a>
                                    </th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($templates as $template)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input template-checkbox"
                                                value="{{ $template->id }}">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    @switch($template->category)
                                                        @case('system')
                                                            <i class="fas fa-cog text-danger"></i>
                                                        @break

                                                        @case('user')
                                                            <i class="fas fa-user text-primary"></i>
                                                        @break

                                                        @case('alert')
                                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                                        @break

                                                        @case('marketing')
                                                            <i class="fas fa-bullhorn text-success"></i>
                                                        @break

                                                        @case('transactional')
                                                            <i class="fas fa-receipt text-info"></i>
                                                        @break

                                                        @case('reminder')
                                                            <i class="fas fa-bell text-primary"></i>
                                                        @break

                                                        @case('welcome')
                                                            <i class="fas fa-hand-wave text-success"></i>
                                                        @break

                                                        @default
                                                            <i class="fas fa-file-alt text-secondary"></i>
                                                    @endswitch
                                                </div>
                                                <div>
                                                    <h6 class="mb-1">{{ $template->name }}</h6>
                                                    <small
                                                        class="text-muted">{{ Str::limit($template->description, 50) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $categoryColors = [
                                                    'system' => 'danger',
                                                    'user' => 'primary',
                                                    'alert' => 'warning',
                                                    'marketing' => 'success',
                                                    'transactional' => 'info',
                                                    'reminder' => 'primary',
                                                    'welcome' => 'success',
                                                    'custom' => 'secondary',
                                                ];
                                            @endphp
                                            <span
                                                class="badge bg-{{ $categoryColors[$template->category] ?? 'secondary' }}">
                                                {{ ucfirst($template->category) }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $priorityColors = [
                                                    'low' => 'secondary',
                                                    'medium' => 'info',
                                                    'normal' => 'primary',
                                                    'high' => 'warning',
                                                    'urgent' => 'danger',
                                                ];
                                            @endphp
                                            <span
                                                class="badge bg-{{ $priorityColors[$template->priority] ?? 'secondary' }}">
                                                {{ ucfirst($template->priority) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                @php
                                                    $channels = is_array($template->supported_channels)
                                                        ? $template->supported_channels
                                                        : json_decode($template->supported_channels, true) ?? [];
                                                @endphp
                                                @foreach ($channels as $channel)
                                                    @if ($channel == 'email')
                                                        <i class="fas fa-envelope text-primary" title="Email"></i>
                                                    @elseif($channel == 'teams')
                                                        <i class="fab fa-microsoft text-info" title="Teams"></i>
                                                    @elseif($channel == 'sms')
                                                        <i class="fas fa-sms text-success" title="SMS"></i>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            @if ($template->is_active)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Active
                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-pause me-1"></i>Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <small class="text-muted me-2">{{ $template->notifications_count ?? 0 }}
                                                    times</small>
                                                <div class="progress flex-fill" style="height: 5px; width: 50px;">
                                                    <div class="progress-bar" role="progressbar"
                                                        style="width: {{ min(100, ($template->notifications_count ?? 0) * 2) }}%">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $template->created_at->format('M d, Y') }}<br>
                                                <span class="text-xs">v{{ $template->version }}</span>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                    type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('templates.show', $template) }}">
                                                            <i class="fas fa-eye me-2"></i>View
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('templates.edit', $template) }}">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('templates.duplicate', $template) }}">
                                                            <i class="fas fa-copy me-2"></i>Duplicate
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item"
                                                            onclick="toggleStatus({{ $template->id }})">
                                                            <i
                                                                class="fas fa-{{ $template->is_active ? 'pause' : 'play' }} me-2"></i>
                                                            {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </li>
                                                    @if ($template->notifications_count == 0)
                                                        <li>
                                                            <button class="dropdown-item text-danger"
                                                                onclick="deleteTemplate({{ $template->id }})">
                                                                <i class="fas fa-trash me-2"></i>Delete
                                                            </button>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Showing {{ $templates->firstItem() }} to {{ $templates->lastItem() }} of
                            {{ $templates->total() }} results
                        </div>
                        {{ $templates->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-file-alt fa-3x text-gray-300 mb-3"></i>
                        <h5 class="text-gray-600">No templates found</h5>
                        <p class="text-muted">
                            @if (request()->hasAny(['search', 'category', 'priority', 'status']))
                                Try adjusting your filters or <a href="{{ route('templates.index') }}">clear all
                                    filters</a>
                            @else
                                Start by creating your first notification template
                            @endif
                        </p>
                        @if (!request()->hasAny(['search', 'category', 'priority', 'status']))
                            <a href="{{ route('templates.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Template
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Bulk Action Modal -->
    <div class="modal fade" id="bulkActionModal" tabindex="-1" aria-labelledby="bulkActionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkActionModalLabel">Bulk Actions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="bulkActionForm">
                        <div class="mb-3">
                            <label for="bulkAction" class="form-label">Select Action</label>
                            <select class="form-select" id="bulkAction" name="action" required>
                                <option value="">Choose action...</option>
                                <option value="activate">Activate Selected</option>
                                <option value="deactivate">Deactivate Selected</option>
                                <option value="delete">Delete Selected</option>
                                <option value="export">Export Selected</option>
                            </select>
                        </div>
                        <div id="selectedCount" class="text-muted">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="countText">No templates selected</span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="executeBulkAction()">Execute</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select All functionality
            const selectAllCheckbox = document.getElementById('selectAll');
            const templateCheckboxes = document.querySelectorAll('.template-checkbox');

            selectAllCheckbox.addEventListener('change', function() {
                templateCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkActionCount();
            });

            templateCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateBulkActionCount();

                    // Update select all checkbox
                    const checkedCount = document.querySelectorAll('.template-checkbox:checked')
                        .length;
                    selectAllCheckbox.checked = checkedCount === templateCheckboxes.length;
                    selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount <
                        templateCheckboxes.length;
                });
            });

            function updateBulkActionCount() {
                const checkedCount = document.querySelectorAll('.template-checkbox:checked').length;
                const countText = document.getElementById('countText');

                if (checkedCount === 0) {
                    countText.textContent = 'No templates selected';
                } else if (checkedCount === 1) {
                    countText.textContent = '1 template selected';
                } else {
                    countText.textContent = `${checkedCount} templates selected`;
                }
            }
        });

        // Toggle template status
        function toggleStatus(templateId) {
            if (confirm('Are you sure you want to change the status of this template?')) {
                fetch(`/templates/${templateId}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error updating template status');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error updating template status');
                    });
            }
        }

        // Delete template
        function deleteTemplate(templateId) {
            if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
                fetch(`/templates/${templateId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting template');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting template');
                    });
            }
        }

        // Execute bulk action
        function executeBulkAction() {
            const action = document.getElementById('bulkAction').value;
            const selectedTemplates = Array.from(document.querySelectorAll('.template-checkbox:checked'))
                .map(checkbox => checkbox.value);

            if (!action) {
                alert('Please select an action');
                return;
            }

            if (selectedTemplates.length === 0) {
                alert('Please select at least one template');
                return;
            }

            if (['delete', 'deactivate'].includes(action)) {
                if (!confirm(`Are you sure you want to ${action} ${selectedTemplates.length} template(s)?`)) {
                    return;
                }
            }

            fetch('/templates/bulk-action', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        action: action,
                        template_ids: selectedTemplates
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('bulkActionModal')).hide();
                        location.reload();
                    } else {
                        alert('Error executing bulk action: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error executing bulk action');
                });
        }
    </script>
@endpush

@push('styles')
    <style>
        .border-left-primary {
            border-left: 4px solid #4e73df !important;
        }

        .border-left-success {
            border-left: 4px solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 4px solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 4px solid #f6c23e !important;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #5a5c69;
            font-size: 0.85rem;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.875rem;
        }

        .progress {
            background-color: #f8f9fc;
        }

        .dropdown-toggle::after {
            border: none;
            font-family: 'Font Awesome 5 Free';
            content: '\f078';
            font-weight: 900;
            vertical-align: middle;
        }

        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        }

        .badge {
            font-size: 0.75rem;
        }

        .text-xs {
            font-size: 0.7rem;
        }

        .fas.fa-hand-wave {
            color: #1cc88a;
        }
    </style>
@endpush
