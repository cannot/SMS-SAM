@extends('layouts.app')

@section('title', 'Manage Roles - ' . $user->display_name)

@push('styles')
<style>
.role-card {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.role-card:hover {
    border-color: var(--light-green);
    background: rgba(101, 209, 181, 0.02);
}

.role-card.selected {
    border-color: var(--primary-green);
    background: rgba(101, 209, 181, 0.1);
}

.role-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.role-admin { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
.role-manager { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); }
.role-user { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
.role-api-user { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }

.permission-list {
    max-height: 200px;
    overflow-y: auto;
}

.permission-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 8px;
    margin: 0.1rem;
    background: rgba(101, 209, 181, 0.1);
    color: var(--primary-green);
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Manage User Roles</h1>
            <p class="text-muted">Assign roles and permissions to {{ $user->display_name }}</p>
        </div>
        <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Profile
        </a>
    </div>

    <!-- User Info -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <div class="user-avatar me-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                    {{ $user->initials }}
                </div>
                <div>
                    <h5 class="mb-1">{{ $user->display_name }}</h5>
                    <p class="text-muted mb-1">{{ $user->email }}</p>
                    <div>
                        <strong>Current Roles:</strong>
                        @forelse($user->roles as $role)
                        <span class="badge bg-primary me-1">{{ ucfirst($role->name) }}</span>
                        @empty
                        <span class="text-muted">No roles assigned</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Assignment Form -->
    <form method="POST" action="{{ route('users.update-roles', $user) }}">
        @csrf
        @method('PUT')
        
        <div class="row">
            @foreach($allRoles as $role)
            <div class="col-md-6 col-lg-4">
                <div class="role-card {{ in_array($role->id, $userRoles) ? 'selected' : '' }}" 
                     onclick="toggleRole({{ $role->id }})">
                    
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" 
                           id="role_{{ $role->id }}" class="d-none"
                           {{ in_array($role->id, $userRoles) ? 'checked' : '' }}>
                    
                    <div class="role-icon role-{{ $role->name }} text-white mx-auto">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    
                    <h6 class="text-center mb-2">{{ ucfirst($role->name) }}</h6>
                    <p class="text-muted small text-center mb-3">{{ $role->description ?? 'Role description not available' }}</p>
                    
                    @if($role->permissions->count() > 0)
                    <div class="permission-list">
                        <small class="text-muted">Permissions:</small>
                        <div class="mt-2">
                            @foreach($role->permissions as $permission)
                            <span class="permission-badge">{{ $permission->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-check-lg me-2"></i>Update Roles
            </button>
        </div>
    </form>
</div>

<script>
function toggleRole(roleId) {
    const checkbox = document.getElementById('role_' + roleId);
    const card = checkbox.closest('.role-card');
    
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        card.classList.add('selected');
    } else {
        card.classList.remove('selected');
    }
}
</script>
@endsection