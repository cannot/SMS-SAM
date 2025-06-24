@extends('layouts.app')

@section('title', 'User Details - ' . $user->display_name)

@section('content')
<div class="container-fluid">
    <!-- Header with Actions -->
    <div class="row mb-4">
        <div class="col-md-6">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active">{{ $user->display_name }}</li>
                </ol>
            </nav>
            <h2><i class="fas fa-user"></i> {{ $user->display_name }}</h2>
        </div>
        <div class="col-md-6 text-end">
            @can('edit-users')
            <a class="btn btn-warning" href="{{ route('users.edit', $user) }}">
                    <i class="fas fa-edit"></i> Edit User
            </a>
            @endcan
            
            <!-- User Preferences Button -->
            @if($user->id === auth()->id())
                <a href="{{ route('users.preferences') }}" class="btn btn-info">
                    <i class="fas fa-cog"></i> My Preferences
                </a>
            @elseif(auth()->user()->can('manage-users'))
                <a href="{{ route('users.preferences.show', $user) }}" class="btn btn-info">
                    <i class="fas fa-cog"></i> Preferences
                </a>
            @endif
            
            @can('manage-users')
                <button type="button" class="btn btn-{{ $user->is_active ? 'danger' : 'success' }}" 
                        onclick="toggleUserStatus({{ $user->id }}, '{{ $user->display_name }}')">
                    <i class="fas fa-{{ $user->is_active ? 'pause' : 'play' }}"></i> 
                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            @endcan
        </div>
    </div>

    <div class="row">
        <!-- User Information -->
        <div class="col-lg-4">
            <!-- Basic Info Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-id-card"></i> Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-lg bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center">
                            <span style="font-size: 24px;">{{ $user->initials }}</span>
                        </div>
                        <h5 class="mt-2 mb-1">{{ $user->display_name }}</h5>
                        <p class="text-muted mb-2">{{ $user->title ?? 'No title' }}</p>
                        @if($user->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </div>

                    <table class="table table-sm table-borderless">
                        <tr>
                            <td><strong>Username:</strong></td>
                            <td>{{ $user->username }}</td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <td><strong>Department:</strong></td>
                            <td>{{ $user->department ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Phone:</strong></td>
                            <td>{{ $user->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Last Login:</strong></td>
                            <td>
                                @if($user->last_login_at)
                                    {{ $user->last_login_at->format('M d, Y H:i') }}
                                    <br><small class="text-muted">{{ $user->last_login_at->diffForHumans() }}</small>
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>LDAP Sync:</strong></td>
                            <td>
                                @if($user->ldap_synced_at)
                                    {{ $user->ldap_synced_at->format('M d, Y H:i') }}
                                @else
                                    <span class="text-muted">Not synced</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Member Since:</strong></td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar"></i> Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary">{{ $stats['total_received'] ?? 0 }}</h4>
                            <small>Notifications Received</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info">{{ $stats['created'] ?? 0 }}</h4>
                            <small>Notifications Created</small>
                        </div>
                        <div class="col-6 mt-3">
                            <h4 class="text-success">{{ $stats['this_month'] ?? 0 }}</h4>
                            <small>This Month</small>
                        </div>
                        <div class="col-6 mt-3">
                            <h4 class="text-warning">{{ $stats['groups_count'] ?? 0 }}</h4>
                            <small>Groups Joined</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Roles & Permissions -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-shield-alt"></i> Roles & Permissions</h5>
                    @can('manage-users')
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#manageRolesModal">
                            <i class="fas fa-edit"></i> Manage Roles
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Current Roles:</h6>
                            @if($user->roles->count() > 0)
                                @foreach($user->roles as $role)
                                    <span class="badge bg-primary me-1 mb-1">
                                        {{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                                    </span>
                                @endforeach
                            @else
                                <p class="text-muted">No roles assigned</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6>Direct Permissions:</h6>
                            @if($user->permissions->count() > 0)
                                <div class="permission-list" style="max-height: 200px; overflow-y: auto;">
                                    @foreach($user->permissions as $permission)
                                        <span class="badge bg-secondary me-1 mb-1">
                                            {{ $permission->display_name ?? ucfirst(str_replace(['_', '-'], ' ', $permission->name)) }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">No direct permissions</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Groups -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-users"></i> Notification Groups</h5>
                    @can('manage-group-members')
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#manageGroupsModal">
                            <i class="fas fa-plus"></i> Join Group
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    @if($user->notificationGroups->count() > 0)
                        <div class="row">
                            @foreach($user->notificationGroups as $group)
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">{{ $group->name }}</h6>
                                                <p class="text-muted small mb-1">{{ $group->description }}</p>
                                                <small class="text-muted">
                                                    Type: {{ ucfirst($group->type) }} • 
                                                    Joined: {{ $group->pivot->joined_at ? \Carbon\Carbon::parse($group->pivot->joined_at)->format('M d, Y') : 'Unknown' }}
                                                </small>
                                            </div>
                                            @can('manage-group-members')
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="leaveGroup({{ $group->id }}, '{{ $group->name }}')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">Not a member of any notification groups</p>
                    @endif
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-history"></i> Recent Activity</h5>
                </div>
                <div class="card-body">
                    @if(isset($recentActivity) && $recentActivity->count() > 0)
                        <div class="timeline">
                            @foreach($recentActivity as $activity)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">{{ $activity->description }}</h6>
                                        <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                        @if($activity->properties && is_array($activity->properties) && count($activity->properties) > 0)
                                            <div class="mt-1">
                                                @foreach($activity->properties as $key => $value)
                                                    @if(is_string($value) || is_numeric($value))
                                                        <span class="badge bg-light text-dark">{{ $key }}: {{ $value }}</span>
                                                    @elseif(is_array($value))
                                                        <span class="badge bg-light text-dark">{{ $key }}: {{ implode(', ', $value) }}</span>
                                                    @else
                                                        <span class="badge bg-light text-dark">{{ $key }}: {{ json_encode($value) }}</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No recent activity</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Manage Roles Modal -->
@can('manage-users')
<div class="modal fade" id="manageRolesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Roles for {{ $user->display_name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('users.update-roles', $user) }}">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Assign Roles:</label>
                        @foreach(\Spatie\Permission\Models\Role::all() as $role)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" 
                                       value="{{ $role->id }}" id="role_{{ $role->id }}"
                                       {{ $user->hasRole($role->name) ? 'checked' : '' }}>
                                <label class="form-check-label" for="role_{{ $role->id }}">
                                    {{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                                    @if($role->description)
                                        <small class="text-muted d-block">{{ $role->description }}</small>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Roles</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<!-- Manage Groups Modal -->
@can('manage-group-members')
<div class="modal fade" id="manageGroupsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Join Notification Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('users.join-group', $user) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="group_id" class="form-label">Select Group:</label>
                        <select class="form-select" id="group_id" name="group_id" required>
                            <option value="">Choose a group...</option>
                            @foreach($availableGroups as $group)
                                <option value="{{ $group->id }}">
                                    {{ $group->name }} ({{ $group->users_count ?? 0 }} members)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Join Group</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<!-- Hidden Forms -->
<form id="toggle-status-form" method="POST" style="display: none;">
    @csrf
    @method('PATCH')
</form>

<form id="leave-group-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('styles')
<style>
.avatar-lg {
    width: 80px;
    height: 80px;
    font-size: 24px;
    font-weight: bold;
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: -21px;
    top: 20px;
    bottom: -20px;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.permission-list {
    max-height: 200px;
    overflow-y: auto;
}
</style>
@endsection

@section('scripts')
<script>
function toggleUserStatus(userId, userName) {
    if (confirm(`Are you sure you want to toggle the status for ${userName}?`)) {
        const form = document.getElementById('toggle-status-form');
        form.action = `/users/${userId}/toggle-status`;
        form.submit();
    }
}

function leaveGroup(groupId, groupName) {
    if (confirm(`Are you sure you want to remove this user from ${groupName}?`)) {
        const form = document.getElementById('leave-group-form');
        form.action = `/users/{{ $user->id }}/leave-group`;
        
        // Add group_id as hidden input
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'group_id';
        input.value = groupId;
        form.appendChild(input);
        
        form.submit();
    }
}
</script>
@endsection