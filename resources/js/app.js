import './bootstrap';
import '../css/app.css';

// Import Bootstrap JavaScript
import * as bootstrap from 'bootstrap';

// Import jQuery (required for DataTables and Select2)
import $ from 'jquery';
window.$ = window.jQuery = $;

// Make Bootstrap available globally
window.bootstrap = bootstrap;

// Import DataTables
import 'datatables.net-bs5';

// Import Select2
import 'select2';

// Import SweetAlert2
import Swal from 'sweetalert2';
window.Swal = Swal;

// Import Chart.js
import Chart from 'chart.js/auto';
window.Chart = Chart;

// App initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Sidebar toggle functionality
    const sidebarToggle = document.querySelector('.sidebar-toggle-desktop');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent?.classList.toggle('expanded');
            
            // Save state to localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });

        // Restore sidebar state
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent?.classList.add('expanded');
        }
    }

    // Mobile sidebar toggle
    const mobileSidebarToggle = document.querySelector('.sidebar-toggle');
    if (mobileSidebarToggle && sidebar) {
        mobileSidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    // Go to top button
    const goToTopBtn = document.querySelector('.go-to-top');
    if (goToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                goToTopBtn.classList.add('show');
            } else {
                goToTopBtn.classList.remove('show');
            }
        });

        goToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // Initialize DataTables
    $('.data-table').each(function() {
        $(this).DataTable({
            responsive: true,
            pageLength: 25,
            language: {
                search: "ค้นหา:",
                lengthMenu: "แสดง _MENU_ รายการ",
                info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                paginate: {
                    first: "หน้าแรก",
                    last: "หน้าสุดท้าย",
                    next: "ถัดไป",
                    previous: "ก่อนหน้า"
                }
            }
        });
    });

    // Initialize Select2
    $('.select2').each(function() {
        $(this).select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });

    // Enhanced error handling for AJAX
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Global AJAX error handler
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (xhr.status === 419) {
            Swal.fire({
                icon: 'error',
                title: 'Session Expired',
                text: 'Your session has expired. Please refresh the page.',
                confirmButtonText: 'Refresh',
                allowOutsideClick: false
            }).then(() => {
                window.location.reload();
            });
        }
    });

    console.log('🚀 App initialized successfully!');
    console.log('✅ Bootstrap:', window.bootstrap ? 'Available' : 'Not available');
    console.log('✅ jQuery:', window.$ ? 'Available' : 'Not available');
    console.log('✅ Swal:', window.Swal ? 'Available' : 'Not available');
});