@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Activity Log Details</h4>
                    <a href="{{ route('activity-logs.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Activity Logs
                    </a>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Log ID:</th>
                                    <td>#{{ $activity->id }}</td>
                                </tr>
                                <tr>
                                    <th>Date/Time:</th>
                                    <td>{{ $activity->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <th>Time Ago:</th>
                                    <td>{{ $activity->created_at->diffForHumans() }}</td>
                                </tr>
                                <tr>
                                    <th>Log Type:</th>
                                    <td>
                                        <span class="badge badge-info">{{ $activity->log_name ?? 'default' }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">User:</th>
                                    <td>
                                        @if($activity->causer)
                                            <a href="{{ route('users.show', $activity->causer->id) }}">
                                                {{ $activity->causer->name }}
                                            </a>
                                            <small class="text-muted">({{ $activity->causer->email }})</small>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>IP Address:</th>
                                    <td>{{ $activity->getExtraProperty('ip_address') ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>User Agent:</th>
                                    <td>
                                        <small>{{ $activity->getExtraProperty('user_agent') ?? 'N/A' }}</small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-12">
                            <h5>Activity Description</h5>
                            <div class="alert alert-light">
                                <strong>{{ $activity->description }}</strong>
                            </div>
                        </div>
                    </div>

                    @if($activity->subject)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Subject Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Subject Type:</th>
                                    <td>{{ class_basename($activity->subject_type) }}</td>
                                </tr>
                                <tr>
                                    <th>Subject ID:</th>
                                    <td>{{ $activity->subject_id }}</td>
                                </tr>
                                @if($activity->subject)
                                <tr>
                                    <th>Current Status:</th>
                                    <td>
                                        @if(method_exists($activity->subject, 'trashed') && $activity->subject->trashed())
                                            <span class="badge badge-danger">Deleted</span>
                                        @else
                                            <span class="badge badge-success">Active</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($activity->properties && $activity->properties->count() > 0)
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5>Additional Properties</h5>
                            
                            @if($activity->properties->has('old') && $activity->properties->has('attributes'))
                            <!-- Show changes in a nice format -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Changes Made</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Field</th>
                                                <th>Old Value</th>
                                                <th>New Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($activity->properties->get('attributes') as $key => $newValue)
                                                @if(isset($activity->properties->get('old')[$key]) && $activity->properties->get('old')[$key] != $newValue)
                                                <tr>
                                                    <td><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}</strong></td>
                                                    <td class="text-danger">
                                                        <del>{{ $activity->properties->get('old')[$key] ?? 'N/A' }}</del>
                                                    </td>
                                                    <td class="text-success">{{ $newValue }}</td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @else
                            <!-- Show raw properties -->
                            <div class="card">
                                <div class="card-body">
                                    <pre class="mb-0"><code>{{ json_encode($activity->properties->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <div>
                                    @if($activity->created_at->diffInHours() < 24)
                                        <span class="badge badge-success">Recent Activity</span>
                                    @elseif($activity->created_at->diffInDays() < 7)
                                        <span class="badge badge-warning">This Week</span>
                                    @else
                                        <span class="badge badge-secondary">Older Activity</span>
                                    @endif
                                </div>
                                <div>
                                    <a href="{{ route('activity-logs.index', ['user_id' => $activity->causer_id]) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        View All Activities by This User
                                    </a>
                                </div>
                            </div>
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
    pre {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 1rem;
        max-height: 400px;
        overflow-y: auto;
    }
    
    code {
        color: #495057;
        font-size: 87.5%;
    }
    
    .table-borderless th {
        color: #6c757d;
        font-weight: 600;
    }
</style>
@endpush