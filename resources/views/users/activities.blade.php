@extends('layouts.app')

@section('title', 'User Activities - ' . $user->display_name)

@push('styles')
<style>
.timeline-container {
    position: relative;
    margin: 2rem 0;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 2rem;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 40px;
    bottom: -30px;
    width: 2px;
    background: linear-gradient(to bottom, #dee2e6, transparent);
}

.timeline-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 30px;
    height: 30px;
    background: #fff;
    border: 2px solid #dee2e6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
}

.timeline-icon.login { border-color: #28a745; color: #28a745; }
.timeline-icon.logout { border-color: #dc3545; color: #dc3545; }
.timeline-icon.create { border-color: #007bff; color: #007bff; }
.timeline-icon.update { border-color: #ffc107; color: #ffc107; }
.timeline-icon.delete { border-color: #dc3545; color: #dc3545; }
.timeline-icon.system { border-color: #6c757d; color: #6c757d; }

.activity-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.activity-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
}

.activity-meta {
    font-size: 0.875rem;
    color: #6c757d;
}

.activity-properties {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.properties-toggle {
    cursor: pointer;
    user-select: none;
}

.properties-content {
    display: none;
    margin-top: 1rem;
}

.properties-content.show {
    display: block;
}

.filter-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: none;
    border-radius: 12px;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 20px;
}

.badge-activity {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
}

.properties-json {
    background: #2d3748;
    color: #e2e8f0;
    border-radius: 6px;
    padding: 1rem;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    white-space: pre-wrap;
    max-height: 300px;
    overflow-y: auto;
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('users.show', $user) }}">{{ $user->display_name }}</a></li>
                    <li class="breadcrumb-item active">Activities</li>
                </ol>
            </nav>
            <h2><i class="fas fa-history me-2"></i>User Activities</h2>
            <p class="text-muted mb-0">Activity log and audit trail for {{ $user->display_name }}</p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-outline-success" onclick="exportActivities()">
                    <i class="fas fa-download me-1"></i>Export
                </button>
                <button type="button" class="btn btn-outline-info" onclick="refreshActivities()">
                    <i class="fas fa-sync me-1"></i>Refresh
                </button>
                <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Profile
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <!-- User Info Card -->
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="user-avatar mx-auto mb-3">
                        {{ $user->initials }}
                    </div>
                    <h5 class="mb-1">{{ $user->display_name }}</h5>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    @if($user->department)
                        <span class="badge bg-secondary">{{ $user->department }}</span>
                    @endif
                    @if($user->title)
                        <p class="text-muted mt-2 mb-0">{{ $user->title }}</p>
                    @endif
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card filter-card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Activities</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('users.activities', $user) }}" id="filterForm">
                        <div class="mb-3">
                            <label class="form-label">Activity Type</label>
                            <select class="form-select form-select-sm" name="log_name">
                                <option value="">All Types</option>
                                <option value="user" {{ request('log_name') === 'user' ? 'selected' : '' }}>User Activities</option>
                                <option value="notification" {{ request('log_name') === 'notification' ? 'selected' : '' }}>Notifications</option>
                                <option value="group" {{ request('log_name') === 'group' ? 'selected' : '' }}>Groups</option>
                                <option value="system" {{ request('log_name') === 'system' ? 'selected' : '' }}>System</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <select class="form-select form-select-sm" name="date_range" onchange="toggleCustomDate()">
                                <option value="">All Time</option>
                                <option value="today" {{ request('date_range') === 'today' ? 'selected' : '' }}>Today</option>
                                <option value="week" {{ request('date_range') === 'week' ? 'selected' : '' }}>This Week</option>
                                <option value="month" {{ request('date_range') === 'month' ? 'selected' : '' }}>This Month</option>
                                <option value="custom" {{ request('date_range') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                            </select>
                        </div>

                        <div id="customDateRange" style="display: {{ request('date_range') === 'custom' ? 'block' : 'none' }};">
                            <div class="mb-2">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control form-control-sm" name="date_from" value="{{ request('date_from') }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control form-control-sm" name="date_to" value="{{ request('date_to') }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control form-control-sm" name="search" 
                                   value="{{ request('search') }}" placeholder="Search in descriptions...">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-1"></i>Apply Filters
                            </button>
                            <a href="{{ route('users.activities', $user) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Activity Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h5 class="text-primary">{{ $activities->total() }}</h5>
                            <small>Total Activities</small>
                        </div>
                        <div class="col-6 mb-3">
                            <h5 class="text-success">{{ $activities->where('created_at', '>=', now()->subDay())->count() }}</h5>
                            <small>Today</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-info">{{ $activities->where('created_at', '>=', now()->subWeek())->count() }}</h5>
                            <small>This Week</small>
                        </div>
                        <div class="col-6">
                            <h5 class="text-warning">{{ $activities->where('created_at', '>=', now()->subMonth())->count() }}</h5>
                            <small>This Month</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activities Timeline -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Activity Timeline
                            @if(request()->hasAny(['log_name', 'date_range', 'search']))
                                <span class="badge bg-info ms-2">Filtered</span>
                            @endif
                        </h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleView('timeline')" id="timelineBtn">
                                <i class="fas fa-stream"></i> Timeline
                            </button>
                            <button type="button" class="btn btn-outline-secondary active" onclick="toggleView('list')" id="listBtn">
                                <i class="fas fa-list"></i> List
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if($activities->count() > 0)
                        <div id="timelineView" class="timeline-container" style="display: none;">
                            @foreach($activities as $activity)
                                <div class="timeline-item">
                                    <div class="timeline-icon {{ getActivityIconClass($activity->description) }}">
                                        <i class="fas {{ getActivityIcon($activity->description) }}"></i>
                                    </div>
                                    <div class="activity-card card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1">{{ $activity->description }}</h6>
                                                    <div class="activity-meta">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $activity->created_at->format('M d, Y H:i:s') }}
                                                        <span class="ms-2">({{ $activity->created_at->diffForHumans() }})</span>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge badge-activity bg-{{ getActivityColor($activity->description) }}">
                                                        {{ $activity->log_name ?? 'General' }}
                                                    </span>
                                                </div>
                                            </div>

                                            @if($activity->subject)
                                                <div class="mb-2">
                                                    <strong>Subject:</strong> 
                                                    {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                                                </div>
                                            @endif

                                            @if($activity->properties && $activity->properties->count() > 0)
                                                <div class="activity-properties">
                                                    <div class="properties-toggle" onclick="toggleProperties({{ $activity->id }})">
                                                        <i class="fas fa-chevron-right me-1" id="chevron-{{ $activity->id }}"></i>
                                                        <strong>View Details</strong>
                                                    </div>
                                                    <div class="properties-content" id="properties-{{ $activity->id }}">
                                                        <div class="properties-json">{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT) }}</div>
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    @if($activity->causer)
                                                        Performed by: {{ $activity->causer->display_name }}
                                                    @else
                                                        System action
                                                    @endif
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div id="listView">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="50">Type</th>
                                            <th>Description</th>
                                            <th>Subject</th>
                                            <th>Performed By</th>
                                            <th>Date & Time</th>
                                            <th width="100">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activities as $activity)
                                            <tr>
                                                <td>
                                                    <span class="timeline-icon {{ getActivityIconClass($activity->description) }}" style="position: static; width: 25px; height: 25px;">
                                                        <i class="fas {{ getActivityIcon($activity->description) }}" style="font-size: 12px;"></i>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong>{{ $activity->description }}</strong>
                                                    @if($activity->properties && $activity->properties->count() > 0)
                                                        <br><small class="text-muted">Click to view details</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($activity->subject)
                                                        <span class="badge bg-light text-dark">
                                                            {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($activity->causer)
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; font-size: 10px; color: white;">
                                                                {{ substr($activity->causer->display_name, 0, 1) }}
                                                            </div>
                                                            <small>{{ $activity->causer->display_name }}</small>
                                                        </div>
                                                    @else
                                                        <small class="text-muted">System</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div>{{ $activity->created_at->format('M d, Y') }}</div>
                                                    <small class="text-muted">{{ $activity->created_at->format('H:i:s') }}</small>
                                                </td>
                                                <td>
                                                    @if($activity->properties && $activity->properties->count() > 0)
                                                        <button class="btn btn-sm btn-outline-info" onclick="showActivityDetails({{ $activity->id }})" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted">
                                Showing {{ $activities->firstItem() }} to {{ $activities->lastItem() }} of {{ $activities->total() }} activities
                            </div>
                            <div>
                                {{ $activities->appends(request()->query())->links() }}
                            </div>
                        </div>

                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No activities found</h5>
                            @if(request()->hasAny(['log_name', 'date_range', 'search']))
                                <p class="text-muted">Try adjusting your filter criteria.</p>
                                <a href="{{ route('users.activities', $user) }}" class="btn btn-outline-primary">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </a>
                            @else
                                <p class="text-muted">This user hasn't performed any activities yet.</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity Details Modal -->
<div class="modal fade" id="activityDetailsModal" tabindex="-1" aria-labelledby="activityDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activityDetailsModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Activity Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="activityDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">
                    <i class="fas fa-download me-2"></i>Export Activities
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <select class="form-select" name="format">
                            <option value="csv">CSV File</option>
                            <option value="excel">Excel Spreadsheet</option>
                            <option value="pdf">PDF Report</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date Range</label>
                        <select class="form-select" name="export_range">
                            <option value="all">All Activities</option>
                            <option value="today">Today Only</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_properties" id="includeProperties">
                            <label class="form-check-label" for="includeProperties">
                                Include detailed properties data
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="executeExport()">
                    <i class="fas fa-download me-1"></i>Export
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentView = 'list';

document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
});

function toggleView(view) {
    currentView = view;
    
    // Update buttons
    document.getElementById('timelineBtn').classList.toggle('active', view === 'timeline');
    document.getElementById('listBtn').classList.toggle('active', view === 'list');
    
    // Toggle views
    document.getElementById('timelineView').style.display = view === 'timeline' ? 'block' : 'none';
    document.getElementById('listView').style.display = view === 'list' ? 'block' : 'none';
}

function toggleProperties(activityId) {
    const content = document.getElementById(`properties-${activityId}`);
    const chevron = document.getElementById(`chevron-${activityId}`);
    
    if (content.classList.contains('show')) {
        content.classList.remove('show');
        chevron.classList.remove('fa-chevron-down');
        chevron.classList.add('fa-chevron-right');
    } else {
        content.classList.add('show');
        chevron.classList.remove('fa-chevron-right');
        chevron.classList.add('fa-chevron-down');
    }
}

function showActivityDetails(activityId) {
    const modal = new bootstrap.Modal(document.getElementById('activityDetailsModal'));
    modal.show();
    
    // Load activity details via AJAX
    fetch(`/activities/${activityId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('activityDetailsContent').innerHTML = buildActivityDetailsHTML(data.activity);
            } else {
                document.getElementById('activityDetailsContent').innerHTML = 
                    '<div class="alert alert-danger">Failed to load activity details.</div>';
            }
        })
        .catch(error => {
            document.getElementById('activityDetailsContent').innerHTML = 
                '<div class="alert alert-danger">Error loading activity details.</div>';
        });
}

function buildActivityDetailsHTML(activity) {
    return `
        <div class="row">
            <div class="col-md-6">
                <h6>Basic Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Description:</strong></td><td>${activity.description}</td></tr>
                    <tr><td><strong>Log Name:</strong></td><td>${activity.log_name || 'N/A'}</td></tr>
                    <tr><td><strong>Event:</strong></td><td>${activity.event || 'N/A'}</td></tr>
                    <tr><td><strong>Date:</strong></td><td>${formatDate(activity.created_at)}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Related Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Subject Type:</strong></td><td>${activity.subject_type || 'N/A'}</td></tr>
                    <tr><td><strong>Subject ID:</strong></td><td>${activity.subject_id || 'N/A'}</td></tr>
                    <tr><td><strong>Causer:</strong></td><td>${activity.causer ? activity.causer.display_name : 'System'}</td></tr>
                    <tr><td><strong>Batch UUID:</strong></td><td>${activity.batch_uuid || 'N/A'}</td></tr>
                </table>
            </div>
        </div>
        ${activity.properties ? `
            <div class="mt-3">
                <h6>Properties</h6>
                <div class="properties-json">${JSON.stringify(activity.properties, null, 2)}</div>
            </div>
        ` : ''}
    `;
}

function toggleCustomDate() {
    const select = event.target;
    const customDiv = document.getElementById('customDateRange');
    customDiv.style.display = select.value === 'custom' ? 'block' : 'none';
}

function refreshActivities() {
    location.reload();
}

function exportActivities() {
    const modal = new bootstrap.Modal(document.getElementById('exportModal'));
    modal.show();
}

function executeExport() {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);
    
    // Get current filters
    const urlParams = new URLSearchParams(window.location.search);
    for (let [key, value] of urlParams.entries()) {
        formData.append(key, value);
    }
    
    // Add user ID
    formData.append('user_id', {{ $user->id }});
    
    // Create form and submit
    const exportForm = document.createElement('form');
    exportForm.method = 'POST';
    exportForm.action = '{{ route("users.activities.export", $user) }}';
    exportForm.style.display = 'none';
    
    // Add CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    exportForm.appendChild(csrfInput);
    
    // Add form data
    for (let [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        exportForm.appendChild(input);
    }
    
    document.body.appendChild(exportForm);
    exportForm.submit();
    document.body.removeChild(exportForm);
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('exportModal')).hide();
}

function setupEventListeners() {
    // Auto-submit filters on change
    const filterInputs = document.querySelectorAll('#filterForm select, #filterForm input');
    filterInputs.forEach(input => {
        if (input.type !== 'submit' && input.name !== 'search') {
            input.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        }
    });
    
    // Search with debounce
    let searchTimeout;
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    document.getElementById('filterForm').submit();
                }
            }, 500);
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            searchInput?.focus();
        }
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            exportActivities();
        }
        if (e.key === 'Escape') {
            // Close any open modals
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                bootstrap.Modal.getInstance(modal)?.hide();
            });
        }
    });
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function getActivityIcon(description) {
    const desc = description.toLowerCase();
    if (desc.includes('login')) return 'fa-sign-in-alt';
    if (desc.includes('logout')) return 'fa-sign-out-alt';
    if (desc.includes('created')) return 'fa-plus-circle';
    if (desc.includes('updated')) return 'fa-edit';
    if (desc.includes('deleted')) return 'fa-trash';
    return 'fa-info-circle';
}

function getActivityIconClass(description) {
    const desc = description.toLowerCase();
    if (desc.includes('login')) return 'login';
    if (desc.includes('logout')) return 'logout';
    if (desc.includes('created')) return 'create';
    if (desc.includes('updated')) return 'update';
    if (desc.includes('deleted')) return 'delete';
    return 'system';
}

function getActivityColor(description) {
    const desc = description.toLowerCase();
    if (desc.includes('login')) return 'success';
    if (desc.includes('logout')) return 'danger';
    if (desc.includes('created')) return 'primary';
    if (desc.includes('updated')) return 'warning';
    if (desc.includes('deleted')) return 'danger';
    return 'secondary';
}

// Auto-refresh every 30 seconds for real-time updates
setInterval(() => {
    if (document.visibilityState === 'visible') {
        // Only refresh if no modals are open
        const openModals = document.querySelectorAll('.modal.show');
        if (openModals.length === 0) {
            refreshActivities();
        }
    }
}, 30000);
</script>

@php
function getActivityIcon($description) {
    $desc = strtolower($description);
    if (str_contains($desc, 'login')) return 'fa-sign-in-alt';
    if (str_contains($desc, 'logout')) return 'fa-sign-out-alt';
    if (str_contains($desc, 'created')) return 'fa-plus-circle';
    if (str_contains($desc, 'updated')) return 'fa-edit';
    if (str_contains($desc, 'deleted')) return 'fa-trash';
    return 'fa-info-circle';
}

function getActivityIconClass($description) {
    $desc = strtolower($description);
    if (str_contains($desc, 'login')) return 'login';
    if (str_contains($desc, 'logout')) return 'logout';
    if (str_contains($desc, 'created')) return 'create';
    if (str_contains($desc, 'updated')) return 'update';
    if (str_contains($desc, 'deleted')) return 'delete';
    return 'system';
}

function getActivityColor($description) {
    $desc = strtolower($description);
    if (str_contains($desc, 'login')) return 'success';
    if (str_contains($desc, 'logout')) return 'danger';
    if (str_contains($desc, 'created')) return 'primary';
    if (str_contains($desc, 'updated')) return 'warning';
    if (str_contains($desc, 'deleted')) return 'danger';
    return 'secondary';
}
@endphp
@endpush