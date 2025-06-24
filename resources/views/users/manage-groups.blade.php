@extends('layouts.app')

@section('title', 'Manage Groups - ' . $user->display_name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Manage Notification Groups</h1>
            <p class="text-muted">Add {{ $user->display_name }} to notification groups</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('groups.create') }}" class="btn btn-success">
                <i class="bi bi-plus me-2"></i>Create New Group
            </a>
            <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Profile
            </a>
        </div>
    </div>

    <!-- Group Assignment Form -->
    <form method="POST" action="{{ route('users.update-groups', $user) }}">
        @csrf
        @method('PUT')
        
        <div class="row">
            @foreach($allGroups as $group)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" 
                                   name="groups[]" value="{{ $group->id }}" 
                                   id="group_{{ $group->id }}"
                                   {{ in_array($group->id, $userGroups) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="group_{{ $group->id }}">
                                {{ $group->name }}
                            </label>
                        </div>
                        
                        @if($group->description)
                        <p class="text-muted small mb-2">{{ $group->description }}</p>
                        @endif
                        
                        <div class="small">
                            <strong>Members:</strong> {{ $group->users->count() }}
                            <br>
                            <strong>Created:</strong> {{ $group->created_at->format('M d, Y') }}
                        </div>
                        
                        @if($group->tags)
                        <div class="mt-2">
                            @foreach(json_decode($group->tags, true) ?? [] as $tag)
                            <span class="badge bg-light text-dark">{{ $tag }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-check-lg me-2"></i>Update Groups
            </button>
        </div>
    </form>
</div>
@endsection