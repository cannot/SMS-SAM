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

// Sidebar functionality
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('show');
}

function toggleSidebarDesktop() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
    
    if (toggleBtn) {
        toggleBtn.classList.toggle('collapsed');
    }
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
    document.title = title + ' - {{ config("app.name") }}';
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
        const searchInput = document.querySelector('input[type="search"], input[placeholder*="search"], input[placeholder*="ค้นหา"]');
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
        // Update notification count
        fetch('/api/notifications/unread-count')
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
            fetch('/api/dashboard/quick-stats')
                .then(response => response.json())
                .then(data => {
                    statsElements.forEach(element => {
                        const stat = element.dataset.stat;
                        if (data[stat] !== undefined) {
                            element.textContent = data[stat];
                        }
                    });
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

// Service Worker registration for PWA (optional)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('SW registered: ', registration);
            })
            .catch(function(registrationError) {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

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
    toggleDarkMode
};