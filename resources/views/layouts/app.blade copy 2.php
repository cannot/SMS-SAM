<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSRF Token Meta Tag -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Smart Notification System')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --dark-color: #212529;
            --light-color: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        /* Navigation Styles */
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
            padding: 0.75rem 0;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
        }

        .navbar-nav .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            margin: 0 0.125rem;
            transition: all 0.15s ease-in-out;
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .navbar-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            padding: 0.5rem 0;
        }

        .dropdown-item {
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: background-color 0.15s ease-in-out;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .dropdown-item i {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
            opacity: 0.7;
        }

        .dropdown-item:hover i {
            opacity: 1;
        }

        /* Avatar Styles */
        .avatar-sm {
            width: 32px;
            height: 32px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-md {
            width: 40px;
            height: 40px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Quick Stats Bar */
        .quick-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 0;
            font-size: 0.875rem;
        }

        .quick-stats .stat-item {
            display: flex;
            align-items: center;
            padding: 0 1rem;
            border-right: 1px solid rgba(255, 255, 255, 0.2);
        }

        .quick-stats .stat-item:last-child {
            border-right: none;
        }

        .quick-stats .stat-label {
            opacity: 0.8;
            margin-right: 0.5rem;
        }

        .quick-stats .stat-value {
            font-weight: 600;
            font-size: 1rem;
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.625rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        /* Content Area */
        .main-content {
            min-height: calc(100vh - 120px);
            padding: 2rem 0;
        }

        /* Card Enhancements */
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: box-shadow 0.15s ease-in-out;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background-color: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            font-weight: 600;
        }

        /* Status Indicators */
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .status-online { background-color: var(--success-color); }
        .status-offline { background-color: var(--secondary-color); }
        .status-busy { background-color: var(--warning-color); }
        .status-away { background-color: var(--info-color); }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .quick-stats {
                display: none;
            }
            
            .navbar-nav .nav-link {
                padding: 0.75rem 1rem;
            }
        }

        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
        }

        /* Sidebar for mobile */
        @media (max-width: 767.98px) {
            .navbar-collapse {
                background-color: rgba(0, 0, 0, 0.95);
                margin-top: 1rem;
                border-radius: 0.5rem;
                padding: 1rem;
            }
        }
    </style>
    
    @yield('styles')
</head>
<body>
    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
                <i class="fas fa-bell me-2"></i>
                <span class="d-none d-md-inline">Smart Notification System</span>
                <span class="d-md-none">SNS</span>
            </a>
            
            <!-- Mobile toggle button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" 
                           href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt me-1"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <!-- User Management -->
                    @canany(['view-users', 'manage-users'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') ? 'active' : '' }}" 
                           href="#" id="userManagementDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-users me-1"></i>
                            <span>Users</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userManagementDropdown">
                            @can('view-users')
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('users.*') ? 'active' : '' }}" 
                                   href="{{ route('users.index') }}">
                                    <i class="fas fa-users"></i>
                                    All Users
                                    <small class="text-muted ms-auto">{{ \App\Models\User::count() }}</small>
                                </a>
                            </li>
                            @endcan
                            
                            @can('manage-users')
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('roles.*') ? 'active' : '' }}" 
                                   href="{{ route('roles.index') }}">
                                    <i class="fas fa-shield-alt"></i>
                                    Roles & Permissions
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('permissions.*') ? 'active' : '' }}" 
                                   href="{{ route('permissions.index') }}">
                                    <i class="fas fa-key"></i>
                                    Permissions
                                </a>
                            </li>
                            @endcan
                            
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('users.preferences') }}">
                                    <i class="fas fa-cog"></i>
                                    My Preferences
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endcanany

                    <!-- Notification Management -->
                    @canany(['view-notifications', 'create-notifications', 'view-groups'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('notifications.*') || request()->routeIs('groups.*') || request()->routeIs('templates.*') ? 'active' : '' }}" 
                           href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell me-1"></i>
                            <span>Notifications</span>
                            @if(isset($pendingNotifications) && $pendingNotifications > 0)
                                <span class="notification-badge">{{ $pendingNotifications }}</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="notificationDropdown">
                            @can('view-notifications')
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('notifications.index') ? 'active' : '' }}" 
                                   href="{{ route('notifications.index') }}">
                                    <i class="fas fa-list"></i>
                                    All Notifications
                                    <small class="text-muted ms-auto">{{ \App\Models\Notification::whereDate('created_at', today())->count() }} today</small>
                                </a>
                            </li>
                            @endcan
                            
                            @can('create-notifications')
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('notifications.create') ? 'active' : '' }}" 
                                   href="{{ route('notifications.create') }}">
                                    <i class="fas fa-plus"></i>
                                    Create Notification
                                </a>
                            </li>
                            @endcan
                            
                            @can('view-templates')
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('templates.*') ? 'active' : '' }}" 
                                   href="{{ route('templates.index') }}">
                                    <i class="fas fa-file-alt"></i>
                                    Templates
                                </a>
                            </li>
                            @endcan
                            
                            @can('view-groups')
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('groups.*') ? 'active' : '' }}" 
                                   href="{{ route('groups.index') }}">
                                    <i class="fas fa-users"></i>
                                    Notification Groups
                                    <small class="text-muted ms-auto">{{ \App\Models\NotificationGroup::active()->count() }}</small>
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    <!-- API Management -->
                    {{-- @canany(['view-api-keys', 'view-api-usage'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('api-keys.*') ? 'active' : '' }}" 
                           href="#" id="apiDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-code me-1"></i>
                            <span>API</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="apiDropdown">
                            @can('view-api-keys')
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('api-keys.*') ? 'active' : '' }}" 
                                   href="{{ route('api-keys.index') }}">
                                    <i class="fas fa-key"></i>
                                    API Keys
                                    <small class="text-muted ms-auto">{{ \App\Models\ApiKey::active()->count() }} active</small>
                                </a>
                            </li>
                            @endcan
                            
                            @can('view-api-usage')
                            <li>
                                <a class="dropdown-item" href="{{ route('reports.api-usage') }}">
                                    <i class="fas fa-chart-line"></i>
                                    API Usage Analytics
                                </a>
                            </li>
                            @endcan
                            
                            @can('create-api-keys')
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('api-keys.create') }}">
                                    <i class="fas fa-plus"></i>
                                    Create API Key
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    <!-- Reports & Analytics -->
                    @can('view-reports')
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('reports.*') ? 'active' : '' }}" 
                           href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-chart-bar me-1"></i>
                            <span>Reports</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('reports.index') ? 'active' : '' }}" 
                                   href="{{ route('reports.index') }}">
                                    <i class="fas fa-chart-pie"></i>
                                    Dashboard Overview
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('reports.delivery') ? 'active' : '' }}" 
                                   href="{{ route('reports.delivery') }}">
                                    <i class="fas fa-paper-plane"></i>
                                    Delivery Reports
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('reports.user-activity') ? 'active' : '' }}" 
                                   href="{{ route('reports.user-activity') }}">
                                    <i class="fas fa-user-clock"></i>
                                    User Activity
                                </a>
                            </li>
                            
                            @can('view-logs')
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('reports.system-logs') ? 'active' : '' }}" 
                                   href="{{ route('reports.system-logs') }}">
                                    <i class="fas fa-file-alt"></i>
                                    System Logs
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    <!-- System Administration -->
                    @can('system-settings')
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="systemDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cogs me-1"></i>
                            <span>System</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="systemDropdown">
                            <li>
                                <a class="dropdown-item" href="{{ route('system.settings') }}">
                                    <i class="fas fa-sliders-h"></i>
                                    System Settings
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('system.maintenance') }}">
                                    <i class="fas fa-tools"></i>
                                    Maintenance Mode
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('system.backup') }}">
                                    <i class="fas fa-download"></i>
                                    Backup & Restore
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endcan --}}

                </ul>

                <!-- User Profile Menu -->
                <ul class="navbar-nav">
                    <!-- Notifications Bell -->
                    <li class="nav-item dropdown me-2">
                        <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            @if(isset($userNotificationCount) && $userNotificationCount > 0)
                                <span class="notification-badge">{{ $userNotificationCount > 99 ? '99+' : $userNotificationCount }}</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" style="width: 300px;">
                            <li>
                                <h6 class="dropdown-header">
                                    <i class="fas fa-bell me-1"></i>
                                    Recent Notifications
                                </h6>
                            </li>
                            @if(isset($recentNotifications) && $recentNotifications->count() > 0)
                                @foreach($recentNotifications as $notification)
                                <li>
                                    <a class="dropdown-item py-2" href="#">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-2">
                                                <span class="status-indicator status-{{ $notification->priority }}"></span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold">{{ $notification->title }}</div>
                                                <div class="small text-muted">{{ Str::limit($notification->message, 50) }}</div>
                                                <div class="small text-muted">{{ $notification->created_at->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                @endforeach
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-center" href="{{ route('notifications.index') }}">
                                        View All Notifications
                                    </a>
                                </li>
                            @else
                                <li>
                                    <span class="dropdown-item-text text-muted text-center py-3">
                                        No new notifications
                                    </span>
                                </li>
                            @endif
                        </ul>
                    </li>

                    <!-- User Profile -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar-sm bg-primary text-white rounded-circle me-2">
                                {{ Auth::user()->initials }}
                            </div>
                            <div class="d-none d-md-block">
                                <div class="fw-semibold">{{ Auth::user()->display_name }}</div>
                                <small class="text-muted">{{ Auth::user()->roles->first()->display_name ?? 'User' }}</small>
                            </div>
                            <span class="status-indicator status-online ms-2"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <div class="dropdown-item-text">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-md bg-primary text-white rounded-circle me-3">
                                            {{ Auth::user()->initials }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ Auth::user()->display_name }}</div>
                                            <div class="small text-muted">{{ Auth::user()->email }}</div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('users.show', Auth::user()) }}">
                                    <i class="fas fa-user"></i>
                                    My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('users.preferences') }}">
                                    <i class="fas fa-cog"></i>
                                    Preferences
                                </a>
                            </li>
                            {{-- <li>
                                <a class="dropdown-item" href="{{ route('users.notifications.index') }}">
                                    <i class="fas fa-bell"></i>
                                    My Notifications
                                </a>
                            </li> --}}
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-question-circle"></i>
                                    Help & Support
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" 
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Quick Stats Bar -->
    <div class="quick-stats d-none d-lg-block">
        <div class="container-fluid">
            <div class="d-flex justify-content-center">
                @can('view-users')
                <div class="stat-item">
                    <span class="stat-label">Active Users:</span>
                    <span class="stat-value">{{ \App\Models\User::active()->count() }}</span>
                </div>
                @endcan
                
                @can('view-notifications')
                <div class="stat-item">
                    <span class="stat-label">Notifications Today:</span>
                    <span class="stat-value">{{ \App\Models\Notification::whereDate('created_at', today())->count() }}</span>
                </div>
                @endcan
                
                @can('view-groups')
                <div class="stat-item">
                    <span class="stat-label">Active Groups:</span>
                    <span class="stat-value">{{ \App\Models\NotificationGroup::active()->count() }}</span>
                </div>
                @endcan
                
                @can('view-api-keys')
                <div class="stat-item">
                    <span class="stat-label">API Keys:</span>
                    <span class="stat-value">{{ \App\Models\ApiKey::active()->count() }}</span>
                </div>
                @endcan

                <div class="stat-item">
                    <span class="stat-label">System Status:</span>
                    <span class="stat-value">
                        <i class="fas fa-circle text-success me-1"></i>
                        Online
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Validation Errors -->
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Page Content -->
            @yield('content')
        </div>
    </main>

    <!-- Hidden Logout Form -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <!-- Toast notifications will be inserted here via JavaScript -->
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index: 9999;">
        <div class="d-flex align-items-center justify-content-center h-100">
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Global JavaScript -->
    <script>
        // Setup CSRF token for AJAX requests
        window.Laravel = {
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        };

        // Setup default AJAX headers
        if (window.jQuery) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': window.Laravel.csrfToken
                }
            });
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Global loading state functions
        function showLoading() {
            document.getElementById('loading-overlay').classList.remove('d-none');
        }

        function hideLoading() {
            document.getElementById('loading-overlay').classList.add('d-none');
        }

        // Toast notification function
        function showToast(message, type = 'info') {
            const toastContainer = document.querySelector('.toast-container');
            const toastId = 'toast-' + Date.now();
            
            const toastTypes = {
                'success': { icon: 'fas fa-check-circle', class: 'text-bg-success' },
                'error': { icon: 'fas fa-exclamation-circle', class: 'text-bg-danger' },
                'warning': { icon: 'fas fa-exclamation-triangle', class: 'text-bg-warning' },
                'info': { icon: 'fas fa-info-circle', class: 'text-bg-info' }
            };
            
            const toastType = toastTypes[type] || toastTypes['info'];
            
            const toastHTML = `
                <div id="${toastId}" class="toast ${toastType.class}" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <i class="${toastType.icon} me-2"></i>
                        <strong class="me-auto">${type.charAt(0).toUpperCase() + type.slice(1)}</strong>
                        <small class="text-muted">now</small>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        ${message}
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHTML);
            
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, {
                autohide: true,
                delay: 5000
            });
            
            toast.show();
            
            // Remove toast element after it's hidden
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }

        // Confirmation dialog function
        function confirmAction(message, callback) {
            if (confirm(message)) {
                if (typeof callback === 'function') {
                    callback();
                }
                return true;
            }
            return false;
        }

        // Form submission with loading state
        function submitFormWithLoading(formId) {
            const form = document.getElementById(formId);
            if (form) {
                showLoading();
                form.submit();
            }
        }

        // Auto-refresh notifications count (every 30 seconds)
        setInterval(function() {
            fetch('/api/notifications/count', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': window.Laravel.csrfToken,
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('#notificationsDropdown .notification-badge');
                if (data.count > 0) {
                    if (badge) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                    } else {
                        // Create badge if it doesn't exist
                        const bellIcon = document.querySelector('#notificationsDropdown');
                        if (bellIcon) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'notification-badge';
                            newBadge.textContent = data.count > 99 ? '99+' : data.count;
                            bellIcon.appendChild(newBadge);
                        }
                    }
                } else {
                    if (badge) {
                        badge.remove();
                    }
                }
            })
            .catch(error => {
                console.log('Error fetching notification count:', error);
            });
        }, 30000);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + D for Dashboard
            if (e.altKey && e.key === 'd') {
                e.preventDefault();
                window.location.href = "{{ route('dashboard') }}";
            }
            
            // Alt + U for Users
            if (e.altKey && e.key === 'u') {
                e.preventDefault();
                @can('view-users')
                window.location.href = "{{ route('users.index') }}";
                @endcan
            }
            
            // Alt + N for Notifications
            if (e.altKey && e.key === 'n') {
                e.preventDefault();
                @can('view-notifications')
                window.location.href = "{{ route('notifications.index') }}";
                @endcan
            }
            
            // Alt + R for Reports
            if (e.altKey && e.key === 'r') {
                e.preventDefault();
                @can('view-reports')
                window.location.href = "{{ route('reports.index') }}";
                @endcan
            }
        });

        // Search functionality (if search input exists)
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 3 || this.value.length === 0) {
                        this.form.submit();
                    }
                }, 500);
            });
        }

        // Auto-logout warning (5 minutes before session expires)
        const sessionTimeout = {{ config('session.lifetime') * 60 * 1000 }};
        const warningTime = 5 * 60 * 1000; // 5 minutes before expiry
        
        setTimeout(function() {
            if (confirm('Your session will expire in 5 minutes. Click OK to extend your session.')) {
                // Make a request to extend session
                fetch('/api/extend-session', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': window.Laravel.csrfToken,
                        'Accept': 'application/json',
                    }
                });
            }
        }, sessionTimeout - warningTime);

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Mobile menu auto-close on item click
        document.addEventListener('DOMContentLoaded', function() {
            const navbarCollapse = document.querySelector('.navbar-collapse');
            const navLinks = document.querySelectorAll('.navbar-nav .nav-link:not(.dropdown-toggle)');
            
            navLinks.forEach(function(navLink) {
                navLink.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                            toggle: false
                        });
                        bsCollapse.hide();
                    }
                });
            });
        });

        // Theme toggle (if implemented)
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            }
        });

        // Print functionality
        function printPage() {
            window.print();
        }

        // Export functionality
        function exportData(format, url) {
            showLoading();
            const exportUrl = `${url}?format=${format}`;
            
            // Create temporary link and trigger download
            const link = document.createElement('a');
            link.href = exportUrl;
            link.download = '';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            setTimeout(hideLoading, 1000);
        }

        // Real-time updates (if WebSocket is implemented)
        if (typeof io !== 'undefined') {
            const socket = io();
            
            socket.on('notification-update', function(data) {
                showToast(data.message, 'info');
                // Update UI elements as needed
            });
            
            socket.on('user-status-change', function(data) {
                // Update user status indicators
                const statusIndicators = document.querySelectorAll(`[data-user-id="${data.userId}"] .status-indicator`);
                statusIndicators.forEach(indicator => {
                    indicator.className = `status-indicator status-${data.status}`;
                });
            });
        }
    </script>
    
    @yield('scripts')
</body>
</html>