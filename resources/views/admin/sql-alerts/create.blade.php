@extends('layouts.app')

@section('title', 'สร้างการแจ้งเตือนแบบ SQL')

@push('styles')
    <style>
        .wizard-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            min-height: 600px;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        /* Loading styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            display: none;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e5e7eb;
            border-left: 4px solid #4f46e5;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

@section('content')
    <div class="container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-database text-primary me-2"></i>
                สร้างการแจ้งเตือนแบบ SQL
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">หน้าหลัก</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('notifications.index') }}">การแจ้งเตือน</a></li>
                    <li class="breadcrumb-item active">สร้างการแจ้งเตือนแบบ SQL</li>
                </ol>
            </nav>
        </div>
            
            <!-- เพิ่มปุ่มเคลียร์ -->
            <div>
                <button type="button" class="btn btn-outline-warning btn-sm" onclick="clearAllData()">
                    <i class="fas fa-refresh"></i>
                    เริ่มใหม่
                </button>
            </div>
    </div>

        <!-- เหมือนเดิม -->
        <div id="stepContent">
            <!-- Step 1 content will be loaded here by default -->
            @include('admin.sql-alerts.steps.step1')
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    </div>
@endsection

    @push('scripts')
<!-- **เพิ่ม jQuery สำหรับ wizard เท่านั้น** -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

        <script>
// **เพิ่ม jQuery safety check**
if (typeof jQuery === 'undefined') {
    console.warn('jQuery failed to load, using vanilla JavaScript fallback');
    window.jQuery = undefined;
    window.$ = undefined;
}

            // Global wizard state
            window.SqlAlertWizard = {
                currentStep: 1,
                totalSteps: 14,

                // Initialize wizard
                init: function() {
        console.log('Initializing SQL Alert Wizard...');
                    this.loadCurrentStep();
                    this.setupEventListeners();
                },

                // Load current step from URL or session
                loadCurrentStep: function() {
                    const urlParams = new URLSearchParams(window.location.search);
        const stepFromUrl = parseInt(urlParams.get('step')) || null;
                    const stepFromSession = parseInt(sessionStorage.getItem('sql_alert_step')) || 1;

        // ให้ URL parameter มีความสำคัญกว่า sessionStorage
        if (stepFromUrl !== null) {
            // ถ้ามี step ใน URL ให้ใช้ค่านั้น
            this.currentStep = stepFromUrl;
            
            // ถ้า step ใน URL คือ 1 ให้เคลียร์ sessionStorage (เริ่มต้นใหม่)
            if (stepFromUrl === 1) {
                this.clearState();
            }
        } else {
            // ถ้าไม่มี step ใน URL ให้เริ่มที่ step 1 เสมอ
            this.currentStep = 1;
            this.clearState();
        }

        // ตรวจสอบขอบเขต
                    this.currentStep = Math.min(this.currentStep, this.totalSteps);
                    this.currentStep = Math.max(this.currentStep, 1);

        console.log('Loading step:', this.currentStep);

        // Only load via AJAX if not step 1 (step 1 is already included)
        if (this.currentStep !== 1) {
                    this.loadStep(this.currentStep);
        } else {
            // Step 1 is already loaded, just initialize scripts
            this.initializeStepScripts();
        }
                },

                // Load specific step
                loadStep: function(stepNumber) {
                    if (stepNumber < 1 || stepNumber > this.totalSteps) {
                        console.error('Invalid step number:', stepNumber);
                        return;
                    }

        console.log('Loading step via AJAX:', stepNumber);
        
        // **เพิ่มการ cleanup intervals ก่อนโหลด step ใหม่**
        this.cleanupStepResources();

                    this.showLoading();

                    // Update URL without page reload
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.set('step', stepNumber);
                    window.history.pushState({}, '', newUrl);

                    // Load step content via AJAX
        fetch(`{{ route('admin.sql-alerts.create') }}?step=${stepNumber}&ajax=1`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text();
            })
                        .then(html => {
                console.log('Step loaded successfully');
                            document.getElementById('stepContent').innerHTML = html;
                            this.currentStep = stepNumber;
                            sessionStorage.setItem('sql_alert_step', stepNumber);
                            this.hideLoading();
                            this.initializeStepScripts();
                        })
                        .catch(error => {
                            console.error('Error loading step:', error);
                            this.hideLoading();
                alert('เกิดข้อผิดพลาดในการโหลดขั้นตอน: ' + error.message);
                
                // Fallback to step 1
                if (stepNumber !== 1) {
                    console.log('Falling back to step 1');
                    window.location.href = '{{ route("admin.sql-alerts.create") }}?step=1';
                }
                        });
                },

                // Initialize step-specific scripts
                initializeStepScripts: function() {
        console.log('Initializing step scripts...');
        
                    const scripts = document.getElementById('stepContent').querySelectorAll('script');
        scripts.forEach((script, index) => {
                        if (script.innerHTML) {
                            try {
                    console.log(`Executing script ${index + 1}...`);
                    
                    // **ใช้ eval() แบบเดิม**
                                eval(script.innerHTML);
                    
                    console.log(`Script ${index + 1} executed successfully`);
                    
                            } catch (error) {
                    console.error(`Error executing script ${index + 1}:`, error);
                    console.error('Script content preview:', script.innerHTML.substring(0, 200) + '...');
                            }
                        }
                    });

        // **เรียก init functions ทันที**
        setTimeout(() => {
            try {
                // Call step-specific initialization if available
                    if (typeof window.initializeCurrentStep === 'function') {
                    console.log('Calling initializeCurrentStep...');
                        window.initializeCurrentStep();
                }
                
                // Alternative: call step-specific init functions
                const stepNum = this.currentStep;
                if (typeof window[`initStep${stepNum}`] === 'function') {
                    console.log(`Calling initStep${stepNum}...`);
                    window[`initStep${stepNum}`]();
                }
                
                // **ลบบรรทัดนี้ออก**
                // this.validateStepFunctions();
                
            } catch (error) {
                console.error('Error calling step initialization:', error);
            }
        }, 100);
    },

    // **ปรับปรุง validateStepFunctions**
    validateStepFunctions: function() {
        const stepNum = this.currentStep;
        const requiredFunctions = {
            4: ['loadTemplate', 'testSQL', 'validateSQL', 'formatSQL'],
            5: ['addVariable', 'removeVariable', 'filterVariables'],
            6: ['executeQuery', 'updateCurrentTime'],
            7: ['toggleExport', 'updatePreview'],
            8: ['refreshStats', 'generateStats'],
            9: ['selectTemplate', 'updatePreview']
        };
        
        if (requiredFunctions[stepNum]) {
            const missing = requiredFunctions[stepNum].filter(func => 
                typeof window[func] !== 'function'
            );
            
            if (missing.length > 0) {
                console.warn(`Missing functions for step ${stepNum}:`, missing);
                
                // **ลองโหลด step ใหม่ถ้าฟังก์ชันไม่ครบ**
                console.log(`Reloading step ${stepNum} due to missing functions...`);
                setTimeout(() => {
                    this.loadStep(stepNum);
                }, 500);
            } else {
                console.log(`All required functions loaded for step ${stepNum}`);
            }
                    }
                },

                // Navigate to next step
                nextStep: function() {
                    if (this.currentStep < this.totalSteps) {
                        this.loadStep(this.currentStep + 1);
                    }
                },

                // Navigate to previous step
                previousStep: function() {
                    if (this.currentStep > 1) {
                        this.loadStep(this.currentStep - 1);
                    }
                },

                // Go to specific step
                goToStep: function(stepNumber) {
                    this.loadStep(stepNumber);
                },

                // Show loading overlay
                showLoading: function() {
                    document.getElementById('loadingOverlay').style.display = 'flex';
                },

                // Hide loading overlay
                hideLoading: function() {
                    document.getElementById('loadingOverlay').style.display = 'none';
                },

    // **เพิ่มฟังก์ชัน cleanup resources**
    cleanupStepResources: function() {
        console.log('Cleaning up step resources...');
        
        // **Clear all possible intervals**
        const intervalNames = [
            'currentTimeInterval',
            'refreshStatsInterval', 
            'previewUpdateInterval',
            'autoSaveInterval',
            'connectionCheckInterval',
            'queryUpdateInterval'
        ];
        
        intervalNames.forEach(intervalName => {
            if (window[intervalName]) {
                clearInterval(window[intervalName]);
                window[intervalName] = null;
                console.log(`Cleared ${intervalName}`);
            }
        });
        
        // **Clear all possible timeouts**
        const timeoutNames = [
            'autoSaveTimer',
            'validationTimer',
            'previewTimer',
            'updateTimer'
        ];
        
        timeoutNames.forEach(timeoutName => {
            if (window[timeoutName]) {
                clearTimeout(window[timeoutName]);
                window[timeoutName] = null;
                console.log(`Cleared ${timeoutName}`);
            }
        });
        
        // **Clear event listeners**
        if (window.currentStepEventListeners) {
            window.currentStepEventListeners.forEach(listener => {
                document.removeEventListener(listener.type, listener.handler);
            });
            window.currentStepEventListeners = [];
        }
        
        // **Override updateCurrentTime globally to prevent errors**
        window.updateCurrentTime = function() {
            // Safe no-op function
            return;
        };
        
        console.log('Step resources cleaned up');
    },

                // Setup global event listeners
                setupEventListeners: function() {
        window.addEventListener('popstate', (e) => {
                        this.loadCurrentStep();
                    });

        // **เพิ่มการ cleanup เมื่อออกจากหน้า**
                    window.addEventListener('beforeunload', (e) => {
            this.cleanupStepResources();
            
                        if (this.hasUnsavedChanges()) {
                            e.preventDefault();
                e.returnValue = '';
            }
        });
        
        // **เพิ่มการ cleanup เมื่อ tab เปลี่ยน focus**
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.cleanupStepResources();
                        }
                    });
                },

                // Check for unsaved changes
                hasUnsavedChanges: function() {
                    const keys = Object.keys(sessionStorage).filter(key => key.startsWith('sql_alert_'));
        return keys.length > 1;
                },

                // Clear all saved state
                clearState: function() {
                    const keys = Object.keys(sessionStorage).filter(key => key.startsWith('sql_alert_'));
                    keys.forEach(key => sessionStorage.removeItem(key));
        console.log('Cleared all session data');
                },

                // Validate current step
                validateCurrentStep: function() {
                    if (typeof window.validateCurrentStep === 'function') {
                        return window.validateCurrentStep();
                    }
                    return true;
                }
            };

            // Initialize when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing wizard...');
                window.SqlAlertWizard.init();
            });

// Global navigation functions
            window.nextStep = function() {
                if (window.SqlAlertWizard.validateCurrentStep()) {
                    window.SqlAlertWizard.nextStep();
                }
            };

            window.previousStep = function() {
                window.SqlAlertWizard.previousStep();
            };

            window.goToStep = function(stepNumber) {
                window.SqlAlertWizard.goToStep(stepNumber);
            };

// เพิ่มฟังก์ชันเคลียร์ข้อมูลทั้งหมด
window.clearAllData = function() {
    if (confirm('ต้องการเคลียร์ข้อมูลทั้งหมดและเริ่มใหม่หรือไม่?')) {
        window.SqlAlertWizard.clearState();
        window.location.href = '{{ route("admin.sql-alerts.create") }}?step=1';
    }
};

// แก้ไขฟังก์ชัน updateCurrentTime() ให้มี safety check
function updateCurrentTime() {
    const currentTimeElement = document.getElementById('currentTime');
    if (!currentTimeElement) {
        // Element not found, clear the interval
        if (window.currentTimeInterval) {
            clearInterval(window.currentTimeInterval);
            window.currentTimeInterval = null;
            console.log('Cleared currentTimeInterval - element not found');
        }
                    return;
                }
    
    try {
        const now = new Date();
        const timeString = now.toLocaleString('th-TH', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        currentTimeElement.value = timeString;
                } catch (error) {
        console.error('Error updating current time:', error);
        if (window.currentTimeInterval) {
            clearInterval(window.currentTimeInterval);
            window.currentTimeInterval = null;
        }
    }
}

// **เพิ่มฟังก์ชัน global safe wrapper**
window.safeExecute = function(func, context = null) {
    if (typeof func === 'function') {
        try {
            return func.call(context);
                } catch (error) {
            console.error('Safe execute error:', error);
            return null;
        }
    }
    return null;
};

// **Override all potentially problematic functions**
window.updateCurrentTime = function() {
    return window.safeExecute(function() {
        const currentTimeElement = document.getElementById('currentTime');
        if (!currentTimeElement) return;
        
        const now = new Date();
        const timeString = now.toLocaleString('th-TH', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        currentTimeElement.value = timeString;
    });
};

window.loadSavedStats = function() {
    return window.safeExecute(function() {
        const saved = sessionStorage.getItem('sql_alert_stats');
        const timestamp = sessionStorage.getItem('sql_alert_stats_timestamp');
        
        if (!saved) return;
        
        const statsData = JSON.parse(saved);
        if (timestamp) {
            const date = new Date(timestamp);
            const timestampElement = document.getElementById('statsTimestamp');
            if (timestampElement) {
                timestampElement.textContent = date.toLocaleString('th-TH');
            }
        }
        
        // Only display if elements exist
        if (document.getElementById('totalRecords')) {
            // Display stats logic here
        }
    });
};
        </script>
    @endpush
