@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Activity Logs</h4>
                        <div>
                            <span class="badge badge-primary">Total: {{ $activities->total() }}</span>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('activity-logs.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="user_id" class="form-label">User</label>
                                <select name="user_id" id="user_id" class="form-control">
                                    <option value="">All Users</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label for="log_name" class="form-label">Log Type</label>
                                <select name="log_name" id="log_name" class="form-control">
                                    <option value="">All Types</option>
                                    @foreach($logNames as $logName)
                                        <option value="{{ $logName }}" {{ request('log_name') == $logName ? 'selected' : '' }}>
                                            {{ ucfirst($logName) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="description" class="form-label">Search Description</label>
                                <input type="text" name="description" id="description" class="form-control" 
                                       placeholder="Search in descriptions..." 
                                       value="{{ request('description') }}">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                    <a href="{{ route('activity-logs.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-redo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Activity Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="15%">Date/Time</th>
                                    <th width="15%">User</th>
                                    <th width="10%">Type</th>
                                    <th width="25%">Description</th>
                                    <th width="15%">Subject</th>
                                    <th width="10%">IP Address</th>
                                    <th width="5%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activities as $activity)
                                <tr>
                                    <td>{{ $activity->id }}</td>
                                    <td>
                                        {{ $activity->created_at->format('Y-m-d H:i:s') }}
                                        <br>
                                        <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @if($activity->causer)
                                            <a href="{{ route('users.show', $activity->causer->id) }}">
                                                {{ $activity->causer->name }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $activity->causer->email }}</small>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $activity->log_name ?? 'default' }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $activity->description }}</strong>
                                        @if($activity->properties && $activity->properties->count() > 0)
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle"></i> Has additional data
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($activity->subject)
                                            <span class="badge badge-secondary">
                                                {{ class_basename($activity->subject_type) }}
                                            </span>
                                            #{{ $activity->subject_id }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $activity->getExtraProperty('ip_address') ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('activity-logs.show', $activity->id) }}" 
                                           class="btn btn-sm btn-info" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <p class="mb-0">No activity logs found.</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div>
                            Showing {{ $activities->firstItem() ?? 0 }} to {{ $activities->lastItem() ?? 0 }} 
                            of {{ $activities->total() }} entries
                        </div>
                        <div>
                            {{ $activities->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mt-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title">Today's Activities</h5>
                            <h2 class="mb-0">
                                {{ $activities->filter(function($a) { 
                                    return $a->created_at->isToday(); 
                                })->count() }}
                            </h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title">This Week</h5>
                            <h2 class="mb-0">
                                {{ $activities->filter(function($a) { 
                                    return $a->created_at->isCurrentWeek(); 
                                })->count() }}
                            </h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title">Active Users</h5>
                            <h2 class="mb-0">
                                {{ $activities->pluck('causer_id')->unique()->count() }}
                            </h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5 class="card-title">Total Logs</h5>
                            <h2 class="mb-0">{{ $activities->total() }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table td {
        vertical-align: middle;
    }
    
    .badge {
        font-size: 0.875rem;
    }
    
    .card-body h2 {
        font-size: 2.5rem;
        font-weight: 300;
    }
</style>
@endpush

@push('scripts')
<script>
    // Auto-refresh every 30 seconds if on first page
    @if(!request()->get('page') || request()->get('page') == 1)
    setTimeout(function() {
        window.location.reload();
    }, 30000);
    @endif
</script>
@endpush