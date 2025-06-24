<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Smart Notification System'))</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            /* Primary Colors */
            --primary-green: #256B36;
            --primary-pink: #E1A6AD;
            --primary-brown: #4B4058;

            /* Secondary Colors */
            --light-green: #65D1B5;
            --aqua: #659DAB;
            --blue: #315470;
            --purple: #47566A0;
            --orange: #BB864E;

            /* Gradient Colors */
            --gradient-start: #25A854;
            --gradient-mid: #65D1B5;
            --gradient-end: #E1A6AD;
        }

        .sidebar {
            min-height: calc(100vh - 56px);
            background: linear-gradient(180deg, var(--primary-green) 0%, var(--light-green) 50%, var(--primary-pink) 100%);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Top Navigation */
        .navbar {
            background: linear-gradient(90deg, #315470 0%, #6B8394 25%, #8FA4B3 50%, #6B8394 75%, #315470 100%) !important;
            box-shadow: 0 2px 15px rgba(49, 84, 112, 0.2);
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
        }

        /* Adjust body padding for fixed navbar */
        body {
            padding-top: 56px;
        }

        .sidebar {
            min-height: calc(100vh - 56px);
            background: linear-gradient(180deg, var(--primary-green) 0%, var(--light-green) 50%, var(--primary-pink) 100%);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 56px;
            left: 0;
            width: 16.66667%;
            /* col-lg-2 equivalent */
            overflow-y: auto;
            z-index: 1020;
        }

        /* Adjust main content for fixed sidebar */
        .main-content {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: calc(100vh - 56px);
            margin-left: 16.66667%;
            /* col-lg-2 equivalent */
            padding-top: 0 !important;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
                min-height: auto;
                top: auto;
                z-index: auto;
            }

            .main-content {
                margin-left: 0;
            }

            body {
                padding-top: 56px;
            }
        }

        @media (max-width: 767.98px) {
            .navbar-brand span {
                display: none;
            }

            .go-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
                font-size: 18px;
            }
        }

        /* Go to Top Button */
        .go-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #315470 0%, #23477C 100%);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1025;
            box-shadow: 0 4px 20px rgba(49, 84, 112, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .go-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: scale(1);
        }

        .go-to-top:hover {
            background: linear-gradient(135deg, #23477C 0%, #315470 100%);
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 30px rgba(49, 84, 112, 0.4);
        }

        .go-to-top:active {
            transform: translateY(-1px) scale(1.02);
        }

        /* Cards */
        .card {
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(90deg, var(--light-green) 0%, var(--aqua) 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            border: none;
            font-weight: 600;
        }

        /* Buttons */
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-green) 0%, var(--light-green) 100%);
            border: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, var(--light-green) 0%, var(--primary-green) 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(37, 107, 54, 0.3);
        }

        .btn-success {
            background: linear-gradient(45deg, var(--light-green) 0%, var(--aqua) 100%);
            border: none;
        }

        .btn-warning {
            background: linear-gradient(45deg, var(--orange) 0%, #ffc107 100%);
            border: none;
            color: white;
        }

        .btn-danger {
            background: linear-gradient(45deg, var(--primary-pink) 0%, #dc3545 100%);
            border: none;
        }

        .btn-info {
            background: linear-gradient(45deg, var(--aqua) 0%, var(--blue) 100%);
            border: none;
        }

        .btn-secondary {
            background: linear-gradient(45deg, var(--primary-brown) 0%, #6c757d 100%);
            border: none;
        }

        /* Badges */
        .badge {
            border-radius: 6px;
            font-weight: 500;
        }

        .badge.bg-primary {
            background: linear-gradient(45deg, var(--primary-green) 0%, var(--light-green) 100%) !important;
        }

        .badge.bg-success {
            background: linear-gradient(45deg, var(--light-green) 0%, var(--aqua) 100%) !important;
        }

        .badge.bg-info {
            background: linear-gradient(45deg, var(--aqua) 0%, var(--blue) 100%) !important;
        }

        .badge.bg-warning {
            background: linear-gradient(45deg, var(--orange) 0%, #ffc107 100%) !important;
        }

        .badge.bg-danger {
            background: linear-gradient(45deg, var(--primary-pink) 0%, #dc3545 100%) !important;
        }

        /* Alerts */
        .alert-success {
            background: linear-gradient(135deg, rgba(101, 209, 181, 0.1) 0%, rgba(101, 157, 171, 0.1) 100%);
            border-color: var(--light-green);
            color: var(--primary-green);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(225, 166, 173, 0.1) 0%, rgba(220, 53, 69, 0.1) 100%);
            border-color: var(--primary-pink);
            color: #842029;
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(187, 134, 78, 0.1) 0%, rgba(255, 193, 7, 0.1) 100%);
            border-color: var(--orange);
            color: #664d03;
        }

        /* Form Controls */
        .form-control:focus,
        .form-select:focus {
            border-color: var(--light-green);
            box-shadow: 0 0 0 0.25rem rgba(101, 209, 181, 0.25);
        }

        /* Sidebar Headers */
        .sidebar-heading {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.6) !important;
        }

        /* Dropdown Menus */
        .dropdown-menu {
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
        }

        .dropdown-item:hover {
            background: linear-gradient(90deg, var(--light-green) 0%, var(--aqua) 100%);
            color: white;
        }

        /* Progress Bars */
        .progress {
            background-color: rgba(101, 209, 181, 0.2);
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--primary-green) 0%, var(--light-green) 100%);
        }

        /* Table Styling */
        .table-hover tbody tr:hover {
            background-color: rgba(101, 209, 181, 0.05);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--light-green) 0%, var(--primary-green) 100%);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--primary-green) 0%, var(--light-green) 100%);
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInUp 0.5s ease-out;
        }

        /* Status Indicators */
        .status-active {
            color: var(--primary-green);
            background: rgba(37, 107, 54, 0.1);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-inactive {
            color: var(--primary-brown);
            background: rgba(75, 64, 88, 0.1);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Pagination Styling */
        .pagination {
            --bs-pagination-color: var(--primary-green);
            --bs-pagination-border-color: var(--light-green);
            --bs-pagination-hover-color: white;
            --bs-pagination-hover-bg: var(--light-green);
            --bs-pagination-hover-border-color: var(--primary-green);
            --bs-pagination-active-color: white;
            --bs-pagination-active-bg: var(--primary-green);
            --bs-pagination-active-border-color: var(--primary-green);
            --bs-pagination-disabled-color: #6c757d;
            --bs-pagination-disabled-bg: transparent;
            --bs-pagination-disabled-border-color: #dee2e6;
        }

        .pagination .page-link {
            border-radius: 6px;
            margin: 0 2px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .pagination .page-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(37, 107, 54, 0.2);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(45deg, var(--primary-green) 0%, var(--light-green) 100%);
            border-color: var(--primary-green);
            box-shadow: 0 2px 8px rgba(37, 107, 54, 0.3);
        }
    </style>

    @yield('styles')
    @stack('styles')
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo-p.png') }}" alt="Logo" height="40" class="me-2"
                    style="border-radius: 4px;"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                <span class="fw-bold">{{ config('app.name', 'Smart Notification System') }}</span>
            </a>

            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        {{ Auth::user()->display_name ?? (Auth::user()->username ?? 'User') }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ route('users.preferences') }}">
                                <i class="bi bi-gear me-2"></i>ตั้งค่า
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}"
                                href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('notifications*') ? 'active' : '' }}"
                                href="{{ route('notifications.index') }}">
                                <i class="bi bi-envelope me-2"></i>การแจ้งเตือน
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('groups*') ? 'active' : '' }}"
                                href="{{ route('groups.index') }}">
                                <i class="bi bi-people me-2"></i>กลุ่มผู้รับ
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users*') ? 'active' : '' }}"
                                href="{{ route('users.index') }}">
                                <i class="bi bi-person-lines-fill me-2"></i>ผู้ใช้งาน
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('reports*') ? 'active' : '' }}"
                                href="{{ route('reports.index') }}">
                                <i class="bi bi-graph-up me-2"></i>รายงาน
                            </a>
                        </li>

                        <!-- Admin Section -->
                        @canany(['manage-users', 'view-api-keys', 'view-logs'])
                        <li class="nav-item">
                            <hr class="text-white-50 mx-3">
                            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1">
                                <span>ผู้ดูแลระบบ</span>
                            </h6>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('templates*') ? 'active' : '' }}"
                                href="{{ route('templates.index') }}">
                                <i class="bi bi-file-earmark-text me-2"></i>Templates
                            </a>
                        </li>
                        
                        @can('manage-users')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('roles*') ? 'active' : '' }}"
                                href="{{ route('roles.index') }}">
                                <i class="bi bi-shield-check me-2"></i>บทบาท
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('permissions*') ? 'active' : '' }}"
                                href="{{ route('permissions.index') }}">
                                <i class="bi bi-key-fill me-2"></i>สิทธิ์
                            </a>
                        </li>
                        @endcan
                        
                        @can('view-api-keys')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.api-keys*') ? 'active' : '' }}"
                                href="{{ route('admin.api-keys.index') }}">
                                <i class="bi bi-key me-2"></i>API Keys
                            </a>
                        </li>
                        @endcan
                        
                        @can('view-logs')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.system-logs*') ? 'active' : '' }}"
                                href="{{ route('admin.system-logs') }}">
                                <i class="bi bi-file-text me-2"></i>System Logs
                            </a>
                        </li>
                        @endcan
                        @endcanany
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content ">
                <div class="pt-3 pb-2 mb-3">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Go to Top Button -->
    <button class="go-to-top" id="goToTop" onclick="scrollToTop()">
        <i class="bi bi-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // Go to Top Button Functionality
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Show/Hide Go to Top button based on scroll position
        window.addEventListener('scroll', function() {
            const goToTopBtn = document.getElementById('goToTop');
            if (window.pageYOffset > 300) {
                goToTopBtn.classList.add('show');
            } else {
                goToTopBtn.classList.remove('show');
            }
        });

        // Enhanced sidebar active states
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Remove active class from all links
                    sidebarLinks.forEach(l => l.classList.remove('active'));
                    // Add active class to clicked link
                    this.classList.add('active');
                });
            });
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert && alert.parentNode) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            });
        });

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
    </script>

    @yield('scripts')
    @stack('scripts')
</body>

</html>