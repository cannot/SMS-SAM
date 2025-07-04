<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Smart Notification System') }} @yield('title')</title>

    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet"> --}}

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Bootstrap Icons CDN - โหลดก่อน Vite assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Vite CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @yield('styles')
    @stack('styles')
</head>

<body>
    <!-- Navigation -->
    @include('components.nevigation-header')

    <!-- Sidebar Toggle Button for Mobile -->
    <button class="sidebar-toggle d-lg-none" id="sidebarToggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="position-sticky pt-3">
            <!-- Sidebar Toggle for Desktop -->
            <div class="d-flex justify-content-end p-2 d-none d-lg-block">
                <button class="sidebar-toggle-desktop" onclick="toggleSidebarDesktop()" title="ย่อ/ขยาย Sidebar">
                    <i class="fas fa-bars" id="sidebarToggleIcon"></i>
                </button>
            </div>

            <!-- Main Navigation -->
            <ul class="nav flex-column">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('dashboard*') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">
                        <i class="bi bi-speedometer2"></i>
                        <span class="nav-text">Dashboard</span>
                        <div class="nav-tooltip">Dashboard</div>
                    </a>
                </li>

                <!-- Include User Navigation -->
                @include('components.user-nav')

                <!-- Include Admin Navigation -->
                @include('components.admin-nav')
            </ul>
        </div>
    </nav>

    <!-- System Info Modal -->
    <div class="modal fade" id="systemInfoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">System Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Application Info</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td>Version:</td>
                                    <td>{{ config('app.version', '1.0.0') }}</td>
                                </tr>
                                <tr>
                                    <td>Environment:</td>
                                    <td><span
                                            class="badge bg-{{ app()->environment() === 'production' ? 'success' : 'warning' }}">{{ app()->environment() }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Debug Mode:</td>
                                    <td><span
                                            class="badge bg-{{ config('app.debug') ? 'danger' : 'success' }}">{{ config('app.debug') ? 'ON' : 'OFF' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>PHP Version:</td>
                                    <td>{{ PHP_VERSION }}</td>
                                </tr>
                                <tr>
                                    <td>Laravel Version:</td>
                                    <td>{{ app()->version() }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>System Stats</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td>Total Users:</td>
                                    <td><span class="badge bg-primary">{{ \App\Models\User::count() }}</span></td>
                                </tr>
                                <tr>
                                    <td>Total Roles:</td>
                                    <td><span
                                            class="badge bg-info">{{ \Spatie\Permission\Models\Role::count() }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Total Permissions:</td>
                                    <td><span
                                            class="badge bg-success">{{ \Spatie\Permission\Models\Permission::count() }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Active API Keys:</td>
                                    <td><span
                                            class="badge bg-warning">{{ \App\Models\ApiKey::where('is_active', true)->count() }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Total Notifications:</td>
                                    <td><span class="badge bg-secondary">{{ \App\Models\Notification::count() }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="content-wrapper">
            <!-- Alerts -->
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

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>เกิดข้อผิดพลาด!</strong>
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Page Content -->
            @yield('content')
        </div>
    </main>

    <!-- Go to Top Button -->
    <button class="go-to-top" id="goToTop" onclick="scrollToTop()">
        <i class="bi bi-arrow-up"></i>
    </button>

    <!-- Scripts -->
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script> --}}

    <!-- Scripts -->
    {{-- @vite(['resources/js/app.js']) --}}

    <script>
        // Global App Variables
        window.App = {
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            user: @json(Auth::user()),
            routes: {
                dashboard: "{{ route('dashboard') }}",
                logout: "{{ route('logout') }}",
            }
        };

        // Setup CSRF token for AJAX requests
        if (window.jQuery) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': window.App.csrfToken
                }
            });
        }

        // Set user data
        window.App.user = @json(auth()->user());
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                if (alert.classList.contains('alert-success')) {
                    alert.remove();
                }
            });
        }, 5000);

        // Enhanced Sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        function toggleSidebarDesktop() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleIcon = document.getElementById('sidebarToggleIcon');

            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');

            // Update icon
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.className = 'fas fa-angle-right';
                localStorage.setItem('sidebarCollapsed', 'true');
            } else {
                toggleIcon.className = 'fas fa-bars';
                localStorage.setItem('sidebarCollapsed', 'false');
            }

            // Update tooltips with current stats
            updateTooltipsWithStats();
        }

        // Load sidebar state from localStorage
        function loadSidebarState() {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.getElementById('mainContent');
                const toggleIcon = document.getElementById('sidebarToggleIcon');

                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                if (toggleIcon) {
                    toggleIcon.className = 'fas fa-angle-right';
                }
            }
        }

        // Update tooltips with current badge values
        function updateTooltipsWithStats() {
            document.querySelectorAll('.nav-item').forEach(item => {
                const badge = item.querySelector('[data-stat]');
                const tooltip = item.querySelector('.nav-tooltip');

                if (badge && tooltip && badge.dataset.stat) {
                    const baseText = tooltip.textContent.split(' (')[0];
                    const count = badge.textContent.trim();
                    tooltip.textContent = `${baseText} (${count})`;
                }
            });
        }

        // Go to Top functionality
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Show/Hide Go to Top button
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
            // Load sidebar state
            loadSidebarState();

            // Initialize tooltips
            updateTooltipsWithStats();

            const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');

            sidebarLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (!this.hasAttribute('data-bs-toggle')) {
                        sidebarLinks.forEach(l => {
                            if (!l.hasAttribute('data-bs-toggle')) {
                                l.classList.remove('active');
                            }
                        });
                        this.classList.add('active');
                    }
                });
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert && alert.parentNode) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Handle responsive sidebar
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 991) {
                sidebar.classList.remove('show');
            }
        });

        // Loading state management
        function showLoading(element) {
            const spinner = '<span class="loading-spinner me-2"></span>';
            const originalText = element.innerHTML;
            element.innerHTML = spinner + 'กำลังโหลด...';
            element.disabled = true;
            element.dataset.originalText = originalText;
        }

        function hideLoading(element) {
            element.innerHTML = element.dataset.originalText;
            element.disabled = false;
        }

        // Global notification function
        function showNotification(message, type = 'success') {
            const alertTypes = {
                'success': 'alert-success',
                'error': 'alert-danger',
                'warning': 'alert-warning',
                'info': 'alert-info'
            };

            const icons = {
                'success': 'bi-check-circle',
                'error': 'bi-exclamation-triangle',
                'warning': 'bi-exclamation-triangle',
                'info': 'bi-info-circle'
            };

            const alert = document.createElement('div');
            alert.className = `alert ${alertTypes[type]} alert-dismissible fade show position-fixed`;
            alert.style.cssText = 'top: 80px; right: 20px; z-index: 1055; min-width: 300px; max-width: 500px;';
            alert.innerHTML = `
                <i class="bi ${icons[type]} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(alert);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }

        // Confirm dialog with custom styling
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        // Update page title
        function updatePageTitle(title) {
            document.title = title + ' - {{ config('app.name') }}';
        }

        // Handle form submissions with loading states
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');

            if (submitBtn && !form.dataset.noLoading) {
                showLoading(submitBtn);
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Alt + S for sidebar toggle
            if (e.altKey && e.key === 's') {
                e.preventDefault();
                if (window.innerWidth <= 991) {
                    toggleSidebar();
                } else {
                    toggleSidebarDesktop();
                }
            }

            // Ctrl + / for search (if search input exists)
            if (e.ctrlKey && e.key === '/') {
                e.preventDefault();
                const searchInput = document.querySelector(
                    'input[type="search"], input[placeholder*="search"], input[placeholder*="ค้นหา"]');
                if (searchInput) {
                    searchInput.focus();
                }
            }

            // Escape to close modals
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    const modal = bootstrap.Modal.getInstance(openModal);
                    if (modal) modal.hide();
                }
            }
        });

        // Auto-refresh functionality for real-time data
        function startAutoRefresh(interval = 300000) { // 5 minutes default
            setInterval(() => {

                const webHeaders = {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                };

                // Update notification count
                fetch('/api/notifications/unread-count', {
                            method: 'GET',
                            headers: webHeaders,
                            credentials: 'same-origin'
                        })
                    .then(response => response.json())
                    .then(data => {
                        const badge = document.querySelector('.navbar .badge');
                        if (badge && data.count !== undefined) {
                            badge.textContent = data.count;
                            badge.style.display = data.count > 0 ? 'block' : 'none';
                        }
                    })
                    .catch(error => console.log('Failed to update notification count:', error));

                // Update sidebar stats if they exist
                const statsElements = document.querySelectorAll('[data-stat]');
                if (statsElements.length > 0) {
                    fetch('/api/dashboard/quick-stats', {
                            method: 'GET',
                            headers: webHeaders,
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(data => {
                            statsElements.forEach(element => {
                                const stat = element.dataset.stat;
                                if (data[stat] !== undefined) {
                                    element.textContent = data[stat];
                                }
                            });
                            // Update tooltips after stats change
                            updateTooltipsWithStats();
                        })
                        .catch(error => console.log('Failed to update stats:', error));
                }
            }, interval);
        }

        // Start auto-refresh when page is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Only start auto-refresh if user is authenticated and page is visible
            if (window.App.user && document.visibilityState === 'visible') {
                startAutoRefresh();
            }
        });

        // Pause auto-refresh when page is not visible
        document.addEventListener('visibilitychange', function() {
            // You can implement pause/resume logic here if needed
        });

        // Dark mode toggle (optional feature)
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        // Load dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }

        // Print functionality
        function printPage() {
            window.print();
        }

        // Service Worker registration and management
        if ('serviceWorker' in navigator) {
            console.log('🔧 Service Worker supported');
            
            // Configuration
            const isDevelopment = {{ app()->environment('local') ? 'true' : 'false' }};
            const isProduction = {{ app()->environment('production') ? 'true' : 'false' }};
            
            // Register Service Worker
            async function registerServiceWorker() {
                try {
                    console.log('📝 Registering Service Worker...');
                    
                    const registration = await navigator.serviceWorker.register('/sw.js', {
                        scope: '/',
                        updateViaCache: 'none'
                    });
                    
                    console.log('✅ Service Worker registered successfully');
                    console.log('📍 Scope:', registration.scope);
                    console.log('🆔 Registration ID:', registration);
                    
                    // Handle updates
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        console.log('🔄 New Service Worker found, installing...');
                        
                        newWorker.addEventListener('statechange', () => {
                            console.log('📱 SW State changed to:', newWorker.state);
                            
                            if (newWorker.state === 'installed') {
                                if (navigator.serviceWorker.controller) {
                                    // New worker is available
                                    console.log('🔄 New Service Worker available');
                                    showUpdateNotification();
                                } else {
                                    // First install
                                    console.log('✨ Service Worker installed for first time');
                                    showInstallNotification();
                                }
                            }
                        });
                    });
                    
                    // Check for waiting worker
                    if (registration.waiting) {
                        console.log('⏳ Service Worker waiting');
                        showUpdateNotification();
                    }
                    
                    return registration;
                    
                } catch (error) {
                    console.error('❌ Service Worker registration failed:');
                    console.error('Error details:', {
                        name: error.name,
                        message: error.message,
                        stack: error.stack
                    });
                    
                    // Show user-friendly error in development
                    if (isDevelopment) {
                        showErrorNotification('Service Worker registration failed: ' + error.message);
                    }
                    
                    return null;
                }
            }
            
            // Handle Service Worker messages
            navigator.serviceWorker.addEventListener('message', event => {
                console.log('📨 Message from SW:', event.data);
                
                if (event.data && event.data.type) {
                    switch (event.data.type) {
                        case 'SW_ACTIVATED':
                            console.log('🚀 Service Worker activated, version:', event.data.version);
                            break;
                            
                        case 'ERROR':
                            console.error('🚨 Service Worker error:', event.data.error);
                            if (isDevelopment) {
                                showErrorNotification('SW Error: ' + event.data.error.message);
                            }
                            break;
                            
                        default:
                            console.log('📋 SW Message:', event.data);
                    }
                }
            });
            
            // Show update notification
            function showUpdateNotification() {
                if (window.showNotification) {
                    window.showNotification('แอปมีการอัปเดตใหม่ กรุณารีเฟรชหน้าเว็บ', 'info');
                } else {
                    console.log('💡 App update available - refresh to apply');
                }
            }
            
            // Show install notification
            function showInstallNotification() {
                if (window.showNotification) {
                    window.showNotification('แอปพร้อมใช้งานออฟไลน์แล้ว!', 'success');
                } else {
                    console.log('✨ App ready for offline use');
                }
            }
            
            // Show error notification
            function showErrorNotification(message) {
                if (window.showNotification) {
                    window.showNotification(message, 'error');
                } else {
                    console.error('🚨 SW Error:', message);
                }
            }
            
            // Debug function for development
            window.debugServiceWorker = async function() {
                console.log('🔍 Service Worker Debug Information');
                console.log('==================================');
                
                // Check registrations
                const registrations = await navigator.serviceWorker.getRegistrations();
                console.log('📋 Active Registrations:', registrations.length);
                
                registrations.forEach((reg, index) => {
                    console.log(`Registration ${index + 1}:`);
                    console.log('- Scope:', reg.scope);
                    console.log('- Active Worker:', reg.active?.scriptURL);
                    console.log('- State:', reg.active?.state);
                    console.log('- Update Via Cache:', reg.updateViaCache);
                });
                
                // Check controller
                if (navigator.serviceWorker.controller) {
                    console.log('🎮 Controller:', navigator.serviceWorker.controller.scriptURL);
                    console.log('🎮 State:', navigator.serviceWorker.controller.state);
                } else {
                    console.log('❌ No controller found');
                }
                
                // Check caches
                try {
                    const cacheNames = await caches.keys();
                    console.log('💾 Available Caches:', cacheNames);
                    
                    for (const cacheName of cacheNames) {
                        const cache = await caches.open(cacheName);
                        const keys = await cache.keys();
                        console.log(`Cache "${cacheName}":`, keys.length, 'items');
                    }
                } catch (error) {
                    console.error('❌ Cache error:', error);
                }
                
                // Test SW messaging
                if (navigator.serviceWorker.controller) {
                    console.log('📨 Testing SW messaging...');
                    navigator.serviceWorker.controller.postMessage({
                        type: 'GET_VERSION'
                    });
                }
            };
            
            // Clear Service Worker (for development)
            window.clearServiceWorker = async function() {
                console.log('🧹 Clearing Service Worker...');
                
                const registrations = await navigator.serviceWorker.getRegistrations();
                
                for (const registration of registrations) {
                    await registration.unregister();
                    console.log('🗑️ Unregistered:', registration.scope);
                }
                
                // Clear caches
                const cacheNames = await caches.keys();
                for (const cacheName of cacheNames) {
                    await caches.delete(cacheName);
                    console.log('🗑️ Deleted cache:', cacheName);
                }
                
                console.log('✅ Service Worker cleared');
                
                if (confirm('Service Worker cleared. Reload page?')) {
                    window.location.reload();
                }
            };
            
            // Register when page loads
            window.addEventListener('load', () => {
                // Only register in production or when explicitly enabled in development
                if (isProduction || (isDevelopment && localStorage.getItem('enableSW') === 'true')) {
                    registerServiceWorker();
                } else {
                    console.log('🚫 Service Worker disabled in development');
                    console.log('💡 Enable with: localStorage.setItem("enableSW", "true"); location.reload()');
                }
            });
            
            // Auto-debug in development after 3 seconds
            if (isDevelopment) {
                setTimeout(() => {
                    if (window.debugServiceWorker) {
                        window.debugServiceWorker();
                    }
                }, 3000);
            }
            
        } else {
            console.warn('❌ Service Worker not supported in this browser');
        }

        // Export functions for global use
        window.AppFunctions = {
            showNotification,
            confirmAction,
            updatePageTitle,
            showLoading,
            hideLoading,
            toggleSidebar,
            toggleSidebarDesktop,
            scrollToTop,
            printPage,
            toggleDarkMode,
            updateTooltipsWithStats
        };
    </script>

    @yield('scripts')
    @stack('scripts')
</body>

</html>
