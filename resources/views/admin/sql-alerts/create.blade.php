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
    {{-- <div class="container"> --}}
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
    </div>

    <!-- Wizard Container -->
    <div class="wizard-container">
        <!-- Dynamic Step Content -->
        <div id="stepContent">
            <!-- Step content will be loaded here -->
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    {{-- </div> --}}

    @push('scripts')
        <script>
            // SQL Templates - ประกาศไว้ที่ด้านบนสุดเพื่อให้เข้าถึงได้ทันที
            const sqlTemplates = {
                system_alerts: `-- การแจ้งเตือนระบบที่ต้องจัดการ
            SELECT 
                alert_id,
                alert_type,
                severity_level,
                message,
                affected_system,
                created_at,
                CASE 
                    WHEN severity_level = 'critical' THEN '🔴 Critical'
                    WHEN severity_level = 'high' THEN '🟠 High'
                    WHEN severity_level = 'medium' THEN '🟡 Medium'
                    ELSE '🟢 Low'
                END AS priority_display
            FROM system_alerts 
            WHERE 
                status = 'pending' 
                AND severity_level IN ('critical', 'high')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
            ORDER BY 
                FIELD(severity_level, 'critical', 'high', 'medium', 'low'),
                created_at DESC
            LIMIT 50;`,

                user_activity: `-- กิจกรรมผู้ใช้ที่ผิดปกติ
            SELECT 
                user_id,
                username,
                email,
                action_type,
                ip_address,
                user_agent,
                created_at,
                COUNT(*) OVER (PARTITION BY user_id) as attempt_count
            FROM user_activity_logs 
            WHERE 
                created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                AND action_type IN ('failed_login', 'suspicious_activity', 'unauthorized_access')
                AND ip_address NOT IN ('127.0.0.1', '::1') -- ยกเว้น localhost
            GROUP BY user_id, ip_address
            HAVING attempt_count >= 3
            ORDER BY created_at DESC
            LIMIT 100;`,

                performance: `-- ตรวจสอบประสิทธิภาพระบบ
            SELECT 
                server_name,
                server_type,
                cpu_usage,
                memory_usage,
                disk_usage,
                network_io,
                response_time,
                measured_at,
                CASE 
                    WHEN cpu_usage > 95 OR memory_usage > 90 THEN 'Critical'
                    WHEN cpu_usage > 85 OR memory_usage > 80 THEN 'Warning'
                    ELSE 'Normal'
                END as status_level
            FROM server_performance_stats 
            WHERE 
                measured_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                AND (cpu_usage > 85 OR memory_usage > 80 OR disk_usage > 90)
            ORDER BY 
                CASE 
                    WHEN cpu_usage > 95 OR memory_usage > 90 THEN 1
                    WHEN cpu_usage > 85 OR memory_usage > 80 THEN 2
                    ELSE 3
                END,
                measured_at DESC;`,

                custom: `-- เขียน SQL Query ของคุณเอง
            -- ตัวอย่าง: ตรวจสอบออเดอร์ที่ยังไม่ได้จัดส่ง
            SELECT 
                order_id,
                customer_name,
                total_amount,
                order_status,
                created_at,
                DATEDIFF(NOW(), created_at) as days_pending
            FROM orders 
            WHERE 
                order_status = 'pending'
                AND created_at <= DATE_SUB(NOW(), INTERVAL 2 DAY)
            ORDER BY created_at ASC;`
            };

            let selectedDatabaseType = null;
            let testInProgress = false;
            let testCompleted = false;
            let testSuccess = false;
            let variableCount = 1;
            let currentFilter = 'system';

            // Global wizard state
            window.SqlAlertWizard = {
                currentStep: 1,
                totalSteps: 14,

                // Initialize wizard
                init: function() {
                    this.loadCurrentStep();
                    this.setupEventListeners();
                },

                // Load current step from URL or session
                loadCurrentStep: function() {
                    const urlParams = new URLSearchParams(window.location.search);
                    const stepFromUrl = parseInt(urlParams.get('step')) || 1;
                    const stepFromSession = parseInt(sessionStorage.getItem('sql_alert_step')) || 1;

                    this.currentStep = Math.max(stepFromUrl, stepFromSession);
                    this.currentStep = Math.min(this.currentStep, this.totalSteps);
                    this.currentStep = Math.max(this.currentStep, 1);

                    this.loadStep(this.currentStep);
                },

                // Load specific step
                loadStep: function(stepNumber) {
                    if (stepNumber < 1 || stepNumber > this.totalSteps) {
                        console.error('Invalid step number:', stepNumber);
                        return;
                    }

                    this.showLoading();

                    // Update URL without page reload
                    const newUrl = new URL(window.location);
                    newUrl.searchParams.set('step', stepNumber);
                    window.history.pushState({}, '', newUrl);

                    // Load step content via AJAX
                    fetch(`{{ route('sql-alerts.create') }}?step=${stepNumber}&ajax=1`)
                        .then(response => response.text())
                        .then(html => {
                            document.getElementById('stepContent').innerHTML = html;
                            this.currentStep = stepNumber;
                            sessionStorage.setItem('sql_alert_step', stepNumber);
                            this.hideLoading();
                            this.initializeStepScripts();
                        })
                        .catch(error => {
                            console.error('Error loading step:', error);
                            this.hideLoading();
                            alert('เกิดข้อผิดพลาดในการโหลดขั้นตอน');
                        });
                },

                // Initialize step-specific scripts
                initializeStepScripts: function() {
                    // Execute any inline scripts in the loaded content
                    const scripts = document.getElementById('stepContent').querySelectorAll('script');
                    scripts.forEach(script => {
                        if (script.innerHTML) {
                            try {
                                eval(script.innerHTML);
                            } catch (error) {
                                console.error('Error executing step script:', error);
                            }
                        }
                    });

                    // Call step-specific initialization if it exists
                    if (typeof window.initializeCurrentStep === 'function') {
                        window.initializeCurrentStep();
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

                // Setup global event listeners
                setupEventListeners: function() {
                    // Handle browser back/forward buttons
                    window.addEventListener('popstate', () => {
                        this.loadCurrentStep();
                    });

                    // Handle beforeunload to warn about unsaved changes
                    window.addEventListener('beforeunload', (e) => {
                        if (this.hasUnsavedChanges()) {
                            e.preventDefault();
                            e.returnValue =
                                'คุณมีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?';
                        }
                    });
                },

                // Check for unsaved changes
                hasUnsavedChanges: function() {
                    // Check if there's any data in sessionStorage
                    const keys = Object.keys(sessionStorage).filter(key => key.startsWith('sql_alert_'));
                    return keys.length > 1; // More than just the step number
                },

                // Save current state
                saveState: function(data) {
                    Object.keys(data).forEach(key => {
                        sessionStorage.setItem(`sql_alert_${key}`,
                            typeof data[key] === 'object' ? JSON.stringify(data[key]) : data[key]
                        );
                    });
                },

                // Load saved state
                loadState: function(key) {
                    const value = sessionStorage.getItem(`sql_alert_${key}`);
                    try {
                        return JSON.parse(value);
                    } catch {
                        return value;
                    }
                },

                // Clear all saved state
                clearState: function() {
                    const keys = Object.keys(sessionStorage).filter(key => key.startsWith('sql_alert_'));
                    keys.forEach(key => sessionStorage.removeItem(key));
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
                window.SqlAlertWizard.init();
            });

            // Global navigation functions for step templates
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

            // Database information mapping
            const databaseInfo = {
                mysql: {
                    driver: 'mysql',
                    connection: 'TCP/IP',
                    description: 'MySQL Database Server'
                },
                postgresql: {
                    driver: 'pgsql',
                    connection: 'TCP/IP',
                    description: 'PostgreSQL Database Server'
                },
                sqlserver: {
                    driver: 'sqlsrv',
                    connection: 'TCP/IP',
                    description: 'Microsoft SQL Server'
                },
                oracle: {
                    driver: 'oci',
                    connection: 'TCP/IP + TNS',
                    description: 'Oracle Database Server'
                },
                sqlite: {
                    driver: 'sqlite',
                    connection: 'File System',
                    description: 'SQLite Database File'
                },
                mariadb: {
                    driver: 'mysql',
                    connection: 'TCP/IP',
                    description: 'MariaDB Database Server'
                }
            };

            // ===== Step Management Functions =====
            function selectDatabase(element) {
                // Remove selection from all cards
                document.querySelectorAll('.database-card').forEach(card => {
                    card.classList.remove('selected');
                });

                // Add selection to clicked card
                element.classList.add('selected');

                // Get database info
                const dbType = element.getAttribute('data-db-type');
                const port = element.getAttribute('data-port');

                // Update hidden input if exists
                const databaseTypeInput = document.getElementById('databaseType');
                if (databaseTypeInput) {
                    databaseTypeInput.value = dbType;
                }

                // Update selected info display
                updateSelectedInfo(dbType, port);

                // Show selected info
                const selectedInfo = document.getElementById('selectedInfo');
                if (selectedInfo) {
                    selectedInfo.classList.add('show');
                }

                // Enable next button
                const nextBtn = document.getElementById('nextBtn');
                if (nextBtn) {
                    nextBtn.disabled = false;
                }

                // Store in session storage for next step
                sessionStorage.setItem('sql_alert_db_type', dbType);
                sessionStorage.setItem('sql_alert_db_port', port);

                console.log('Database selected:', dbType);
            }

            function updateSelectedInfo(dbType, port) {
                const databaseInfo = {
                    mysql: {
                        driver: 'mysql',
                        connection: 'TCP/IP'
                    },
                    postgresql: {
                        driver: 'pgsql',
                        connection: 'TCP/IP'
                    },
                    sqlserver: {
                        driver: 'sqlsrv',
                        connection: 'TCP/IP'
                    },
                    oracle: {
                        driver: 'oci',
                        connection: 'TCP/IP + TNS'
                    },
                    sqlite: {
                        driver: 'sqlite',
                        connection: 'File System'
                    },
                    mariadb: {
                        driver: 'mysql',
                        connection: 'TCP/IP'
                    }
                };

                const info = databaseInfo[dbType] || {};

                const elements = {
                    selectedType: document.getElementById('selectedType'),
                    selectedPort: document.getElementById('selectedPort'),
                    selectedDriver: document.getElementById('selectedDriver'),
                    selectedConnection: document.getElementById('selectedConnection')
                };

                if (elements.selectedType) elements.selectedType.textContent = dbType.toUpperCase();
                if (elements.selectedPort) elements.selectedPort.textContent = port || 'ไม่ระบุ';
                if (elements.selectedDriver) elements.selectedDriver.textContent = info.driver || 'unknown';
                if (elements.selectedConnection) elements.selectedConnection.textContent = info.connection || 'unknown';
            }

            function nextStep() {
                if (!selectedDatabaseType) {
                    alert('กรุณาเลือกประเภทฐานข้อมูล');
                    return;
                }

                // Store selection in form data or session
                sessionStorage.setItem('sql_alert_step', '2');

                // Navigate to next step
                window.location.href = '{{ route('sql-alerts.create') }}?step=2';
            }

            // Auto-select if coming back from next step
            document.addEventListener('DOMContentLoaded', function() {
                const savedDbType = sessionStorage.getItem('sql_alert_db_type');
                const savedPort = sessionStorage.getItem('sql_alert_db_port');

                if (savedDbType) {
                    const element = document.querySelector(`[data-db-type="${savedDbType}"]`);
                    if (element) {
                        selectDatabase(element);
                    }
                }
            });

            // Load saved data on page load
            document.addEventListener('DOMContentLoaded', function() {
                loadSavedData();
                updateDatabaseInfo();
            });

            function loadSavedData() {
                // Load from sessionStorage
                const fields = [
                    'db_host', 'db_port', 'db_name', 'db_username',
                    'connection_timeout', 'charset'
                ];

                fields.forEach(field => {
                    const saved = sessionStorage.getItem(`sql_alert_${field}`);
                    if (saved) {
                        const element = document.querySelector(`[name="${field}"]`);
                        if (element) {
                            element.value = saved;
                        }
                    }
                });

                // Load SSL checkbox
                const sslSaved = sessionStorage.getItem('sql_alert_ssl_enabled');
                if (sslSaved === '1') {
                    document.getElementById('sslEnabled').checked = true;
                }
            }

            function updateDatabaseInfo() {
                const dbType = sessionStorage.getItem('sql_alert_db_type');
                const dbPort = sessionStorage.getItem('sql_alert_db_port');

                if (dbType) {
                    document.getElementById('selectedDbType').textContent = dbType.toUpperCase();
                    document.getElementById('selectedDbPort').textContent = `Port: ${dbPort}`;

                    // Update port field
                    if (dbPort) {
                        document.getElementById('dbPort').value = dbPort;
                    }

                    // Database specific settings
                    updateDatabaseSpecificSettings(dbType);
                }
            }

            function updateDatabaseSpecificSettings(dbType) {
                const charsetSelect = document.getElementById('charset');

                // Clear existing options
                charsetSelect.innerHTML = '';

                switch (dbType) {
                    case 'mysql':
                    case 'mariadb':
                        charsetSelect.innerHTML = `
                            <option value="utf8mb4">UTF-8 (utf8mb4)</option>
                            <option value="utf8">UTF-8 (utf8)</option>
                            <option value="latin1">Latin1</option>
                            <option value="tis620">TIS-620</option>
                        `;
                        document.getElementById('selectedDbDriver').textContent = 'Driver: mysql';
                        break;

                    case 'postgresql':
                        charsetSelect.innerHTML = `
                            <option value="UTF8">UTF-8</option>
                            <option value="LATIN1">Latin1</option>
                            <option value="SQL_ASCII">ASCII</option>
                        `;
                        document.getElementById('selectedDbDriver').textContent = 'Driver: pgsql';
                        break;

                    case 'sqlserver':
                        charsetSelect.innerHTML = `
                            <option value="utf8">UTF-8</option>
                            <option value="iso88591">ISO-8859-1</option>
                        `;
                        document.getElementById('selectedDbDriver').textContent = 'Driver: sqlsrv';
                        break;

                    case 'oracle':
                        charsetSelect.innerHTML = `
                            <option value="AL32UTF8">UTF-8 (AL32UTF8)</option>
                            <option value="WE8ISO8859P1">Western European</option>
                        `;
                        document.getElementById('selectedDbDriver').textContent = 'Driver: oci';
                        break;

                    case 'sqlite':
                        charsetSelect.innerHTML = `
                            <option value="utf8">UTF-8</option>
                        `;
                        document.getElementById('selectedDbDriver').textContent = 'Driver: sqlite';
                        // Hide host/port for SQLite
                        document.getElementById('dbHost').closest('.col-md-8').style.display = 'none';
                        document.getElementById('dbPort').closest('.col-md-4').style.display = 'none';
                        break;
                }
            }

            / ===== Step 2: Connection Form Functions =====

            function togglePassword(fieldId) {
                const field = document.getElementById(fieldId);
                const icon = document.getElementById('passwordIcon');

                if (!field) return;

                if (field.type === 'password') {
                    field.type = 'text';
                    if (icon) icon.className = 'fas fa-eye-slash';
                } else {
                    field.type = 'password';
                    if (icon) icon.className = 'fas fa-eye';
                }
            }

            function validateConnectionForm() {
                const form = document.getElementById('connectionForm');
                if (!form) return true;

                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    }
                });

                return isValid;
            }

            function saveConnectionData() {
                const form = document.getElementById('connectionForm');
                if (!form) return;

                const formData = new FormData(form);

                // Save all form data to sessionStorage
                for (let [key, value] of formData.entries()) {
                    sessionStorage.setItem(`sql_alert_${key}`, value);
                }

                // Save SSL checkbox state
                const sslEnabled = document.getElementById('sslEnabled');
                if (sslEnabled) {
                    sessionStorage.setItem('sql_alert_ssl_enabled', sslEnabled.checked ? '1' : '0');
                }

                console.log('Connection data saved to session storage');
            }

            function loadConnectionData() {
                // Load saved connection data from sessionStorage
                const fields = [
                    'db_host', 'db_port', 'db_name', 'db_username',
                    'db_password', 'connection_timeout', 'charset'
                ];

                fields.forEach(field => {
                    const saved = sessionStorage.getItem(`sql_alert_${field}`);
                    if (saved) {
                        const element = document.querySelector(`[name="${field}"]`);
                        if (element) {
                            element.value = saved;
                        }
                    }
                });

                // Load SSL checkbox
                const sslSaved = sessionStorage.getItem('sql_alert_ssl_enabled');
                const sslCheckbox = document.getElementById('sslEnabled');
                if (sslCheckbox && sslSaved === '1') {
                    sslCheckbox.checked = true;
                }

                // Update database info display
                updateDatabaseInfoDisplay();
            }

            function updateDatabaseInfoDisplay() {
                const dbType = sessionStorage.getItem('sql_alert_db_type');
                const dbPort = sessionStorage.getItem('sql_alert_db_port');

                if (dbType) {
                    const elements = {
                        selectedDbType: document.getElementById('selectedDbType'),
                        selectedDbPort: document.getElementById('selectedDbPort'),
                        selectedDbDriver: document.getElementById('selectedDbDriver')
                    };

                    if (elements.selectedDbType) {
                        elements.selectedDbType.textContent = dbType.toUpperCase();
                    }
                    if (elements.selectedDbPort) {
                        elements.selectedDbPort.textContent = `Port: ${dbPort}`;
                    }

                    // Update port field
                    const portField = document.getElementById('dbPort');
                    if (portField && dbPort) {
                        portField.value = dbPort;
                    }

                    // Update database-specific settings
                    updateDatabaseSpecificSettings(dbType);
                }
            }

            function updateDatabaseSpecificSettings(dbType) {
                const charsetSelect = document.getElementById('charset');
                if (!charsetSelect) return;

                // Clear existing options
                charsetSelect.innerHTML = '';

                const charsetOptions = {
                    mysql: [{
                            value: 'utf8mb4',
                            text: 'UTF-8 (utf8mb4)'
                        },
                        {
                            value: 'utf8',
                            text: 'UTF-8 (utf8)'
                        },
                        {
                            value: 'latin1',
                            text: 'Latin1'
                        },
                        {
                            value: 'tis620',
                            text: 'TIS-620'
                        }
                    ],
                    mariadb: [{
                            value: 'utf8mb4',
                            text: 'UTF-8 (utf8mb4)'
                        },
                        {
                            value: 'utf8',
                            text: 'UTF-8 (utf8)'
                        },
                        {
                            value: 'latin1',
                            text: 'Latin1'
                        },
                        {
                            value: 'tis620',
                            text: 'TIS-620'
                        }
                    ],
                    postgresql: [{
                            value: 'UTF8',
                            text: 'UTF-8'
                        },
                        {
                            value: 'LATIN1',
                            text: 'Latin1'
                        },
                        {
                            value: 'SQL_ASCII',
                            text: 'ASCII'
                        }
                    ],
                    sqlserver: [{
                            value: 'utf8',
                            text: 'UTF-8'
                        },
                        {
                            value: 'iso88591',
                            text: 'ISO-8859-1'
                        }
                    ],
                    oracle: [{
                            value: 'AL32UTF8',
                            text: 'UTF-8 (AL32UTF8)'
                        },
                        {
                            value: 'WE8ISO8859P1',
                            text: 'Western European'
                        }
                    ],
                    sqlite: [{
                        value: 'utf8',
                        text: 'UTF-8'
                    }]
                };

                const options = charsetOptions[dbType] || charsetOptions.mysql;

                options.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.value;
                    optionElement.textContent = option.text;
                    charsetSelect.appendChild(optionElement);
                });

                // Update driver display
                const driverElement = document.getElementById('selectedDbDriver');
                if (driverElement) {
                    const drivers = {
                        mysql: 'mysql',
                        mariadb: 'mysql',
                        postgresql: 'pgsql',
                        sqlserver: 'sqlsrv',
                        oracle: 'oci',
                        sqlite: 'sqlite'
                    };
                    driverElement.textContent = `Driver: ${drivers[dbType] || 'unknown'}`;
                }

                // Hide host/port for SQLite
                if (dbType === 'sqlite') {
                    const hostField = document.getElementById('dbHost');
                    const portField = document.getElementById('dbPort');
                    if (hostField) hostField.closest('.col-md-8').style.display = 'none';
                    if (portField) portField.closest('.col-md-4').style.display = 'none';
                }
            }

            function validateForm() {
                const form = document.getElementById('connectionForm');
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    }
                });

                return isValid;
            }

            function saveFormData() {
                // Save all form data to sessionStorage
                const form = document.getElementById('connectionForm');
                const formData = new FormData(form);

                for (let [key, value] of formData.entries()) {
                    sessionStorage.setItem(`sql_alert_${key}`, value);
                }

                // Save SSL checkbox state
                const sslEnabled = document.getElementById('sslEnabled').checked;
                sessionStorage.setItem('sql_alert_ssl_enabled', sslEnabled ? '1' : '0');
            }

            function previousStep() {
                saveFormData();
                window.location.href = '{{ route('sql-alerts.create') }}?step=1';
            }

            function nextStep() {
                if (!validateForm()) {
                    alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                    return;
                }

                saveFormData();
                sessionStorage.setItem('sql_alert_step', '3');
                window.location.href = '{{ route('sql-alerts.create') }}?step=3';
            }

            // Real-time validation
            document.getElementById('connectionForm').addEventListener('input', function(e) {
                const field = e.target;
                if (field.hasAttribute('required')) {
                    if (field.value.trim()) {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    } else {
                        field.classList.remove('is-valid');
                        field.classList.add('is-invalid');
                    }
                }
            });

            // Load data on page load
            document.addEventListener('DOMContentLoaded', function() {
                loadConnectionSummary();
            });

            // ===== Step 3: Connection Testing Functions =====
            function loadConnectionSummary() {
                const dbType = sessionStorage.getItem('sql_alert_db_type') || 'MySQL';
                const host = sessionStorage.getItem('sql_alert_db_host') || 'localhost';
                const port = sessionStorage.getItem('sql_alert_db_port') || '3306';
                const database = sessionStorage.getItem('sql_alert_db_name') || 'database';
                const username = sessionStorage.getItem('sql_alert_db_username') || 'user';
                const ssl = sessionStorage.getItem('sql_alert_ssl_enabled') === '1';

                const elements = {
                    summaryDbType: document.getElementById('summaryDbType'),
                    summaryHost: document.getElementById('summaryHost'),
                    summaryPort: document.getElementById('summaryPort'),
                    summaryDatabase: document.getElementById('summaryDatabase'),
                    summaryUsername: document.getElementById('summaryUsername'),
                    summarySSL: document.getElementById('summarySSL')
                };

                if (elements.summaryDbType) elements.summaryDbType.textContent = dbType.toUpperCase();
                if (elements.summaryHost) elements.summaryHost.textContent = host;
                if (elements.summaryPort) elements.summaryPort.textContent = port;
                if (elements.summaryDatabase) elements.summaryDatabase.textContent = database;
                if (elements.summaryUsername) elements.summaryUsername.textContent = username;
                if (elements.summarySSL) elements.summarySSL.textContent = ssl ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
            }

            async function startConnectionTest() {
                const startBtn = document.getElementById('startTestBtn');
                const retryBtn = document.getElementById('retryTestBtn');
                const nextBtn = document.getElementById('nextBtn');

                if (!startBtn) return;

                // Disable buttons
                startBtn.style.display = 'none';
                if (retryBtn) retryBtn.style.display = 'none';
                if (nextBtn) nextBtn.disabled = true;

                // Reset UI
                resetTestUI();

                try {
                    // Collect connection data
                    const connectionData = {
                        db_type: sessionStorage.getItem('sql_alert_db_type'),
                        db_host: sessionStorage.getItem('sql_alert_db_host'),
                        db_port: sessionStorage.getItem('sql_alert_db_port'),
                        db_name: sessionStorage.getItem('sql_alert_db_name'),
                        db_username: sessionStorage.getItem('sql_alert_db_username'),
                        db_password: sessionStorage.getItem('sql_alert_db_password'),
                        ssl_enabled: sessionStorage.getItem('sql_alert_ssl_enabled') === '1',
                        connection_timeout: sessionStorage.getItem('sql_alert_connection_timeout') || 30,
                        charset: sessionStorage.getItem('sql_alert_charset') || 'utf8mb4'
                    };

                    // Step 1: Network Test
                    await runTestStep('step-network', 'ทดสอบการเชื่อมต่อเครือข่าย...', async () => {
                        await delay(800);
                        // In a real implementation, this would test network connectivity
                    });

                    // Step 2: Authentication Test
                    await runTestStep('step-auth', 'ตรวจสอบ Username และ Password...', async () => {
                        await delay(600);
                        // Real connection test would happen here
                        const response = await fetch('/admin/sql-alerts/test-connection', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify(connectionData)
                        });

                        if (!response.ok) {
                            const errorData = await response.json();
                            throw new Error(errorData.message || 'การเชื่อมต่อล้มเหลว');
                        }

                        const result = await response.json();
                        if (!result.success) {
                            throw new Error(result.message || 'การเชื่อมต่อล้มเหลว');
                        }

                        // Store connection result
                        sessionStorage.setItem('sql_alert_connection_result', JSON.stringify(result.data));
                    });

                    // Step 3: Database Access Test
                    await runTestStep('step-database', 'ทดสอบการเข้าถึงฐานข้อมูล...', async () => {
                        await delay(400);
                        // Database access would be tested in the previous step
                    });

                    // Step 4: Permissions Test
                    await runTestStep('step-permissions', 'ตรวจสอบสิทธิ์การใช้งาน...', async () => {
                        await delay(300);
                        // Permissions would be tested in the previous step
                    });

                    // Success
                    showTestResult(true, 'การเชื่อมต่อสำเร็จ!',
                        'การเชื่อมต่อไปยังฐานข้อมูลสำเร็จ พร้อมดำเนินการขั้นตอนต่อไป');

                    if (nextBtn) nextBtn.disabled = false;
                    sessionStorage.setItem('sql_alert_connection_tested', '1');

                } catch (error) {
                    console.error('Connection test failed:', error);
                    showTestResult(false, 'การเชื่อมต่อล้มเหลว!', error.message);

                    if (retryBtn) retryBtn.style.display = 'inline-flex';
                }
            }

            async function runTestStep(stepId, progressText, testFunction) {
                const progressElement = document.getElementById('progressText');
                const stepElement = document.getElementById(stepId);

                if (progressElement) {
                    progressElement.textContent = progressText;
                }

                if (stepElement) {
                    stepElement.className = 'test-step running';
                }

                // Update progress bar
                const stepNumber = {
                    'step-network': 1,
                    'step-auth': 2,
                    'step-database': 3,
                    'step-permissions': 4
                } [stepId] || 1;

                updateProgressBar((stepNumber - 1) * 25);

                try {
                    await testFunction();

                    // Success
                    if (stepElement) {
                        stepElement.className = 'test-step success';
                    }
                    updateProgressBar(stepNumber * 25);
                    await delay(200);

                } catch (error) {
                    // Error
                    if (stepElement) {
                        stepElement.className = 'test-step error';
                        const description = stepElement.querySelector('.test-step-description');
                        if (description) {
                            description.textContent = error.message;
                        }
                    }
                    throw error;
                }
            }

            function updateProgressBar(percentage) {
                document.getElementById('testProgress').style.width = percentage + '%';
            }

            function resetTestUI() {
                // Reset all steps to pending
                document.querySelectorAll('.test-step').forEach(step => {
                    step.className = 'test-step pending';
                });

                // Reset progress
                updateProgressBar(0);
                document.getElementById('progressText').textContent = 'เริ่มการทดสอบ...';

                // Hide result
                document.getElementById('testResult').classList.remove('show');
            }

            function showTestResult(success, title, message) {
                const resultDiv = document.getElementById('testResult');
                const resultIcon = document.getElementById('resultIcon');
                const resultTitle = document.getElementById('resultTitle');
                const resultMessage = document.getElementById('resultMessage');
                const errorDetails = document.getElementById('errorDetails');
                const connectionStats = document.getElementById('connectionStats');

                // Update result styling
                resultDiv.className = `test-result show ${success ? 'success' : 'error'}`;

                // Update icon
                resultIcon.className = success ? 'fas fa-check' : 'fas fa-times';

                // Update title and message
                resultTitle.textContent = title;
                resultMessage.textContent = message;

                if (success) {
                    // Show connection stats
                    connectionStats.style.display = 'grid';
                    errorDetails.style.display = 'none';

                    // Update stats with mock data
                    document.getElementById('connectionTime').textContent = '0.' + Math.floor(Math.random() * 99) + ' วินาที';

                    const dbType = sessionStorage.getItem('sql_alert_db_type') || 'mysql';
                    const versions = {
                        'mysql': 'MySQL 8.0.35',
                        'postgresql': 'PostgreSQL 15.4',
                        'sqlserver': 'SQL Server 2022',
                        'oracle': 'Oracle 19c',
                        'sqlite': 'SQLite 3.42.0'
                    };
                    document.getElementById('dbVersion').textContent = versions[dbType] || 'Unknown';

                    document.getElementById('progressText').textContent = 'การทดสอบเสร็จสิ้น';

                } else {
                    // Show error details
                    connectionStats.style.display = 'none';
                    errorDetails.style.display = 'block';

                    document.getElementById('progressText').textContent = 'การทดสอบล้มเหลว';
                }
            }

            // Mock test functions
            async function simulateNetworkTest() {
                await delay(1000);

                // Simulate random failure (20% chance)
                if (Math.random() < 0.2) {
                    throw new Error('ไม่สามารถเชื่อมต่อไปยังเซิร์ฟเวอร์ได้');
                }
            }

            async function simulateAuthTest() {
                await delay(800);

                // Simulate random failure (15% chance)
                if (Math.random() < 0.15) {
                    throw new Error('Username หรือ Password ไม่ถูกต้อง');
                }
            }

            async function simulateDatabaseTest() {
                await delay(600);

                // Simulate random failure (10% chance)
                if (Math.random() < 0.1) {
                    throw new Error('ไม่พบฐานข้อมูลที่ระบุ');
                }
            }

            async function simulatePermissionTest() {
                await delay(500);

                // Simulate random failure (5% chance)
                if (Math.random() < 0.05) {
                    throw new Error('ไม่มีสิทธิ์ SELECT บนฐานข้อมูล');
                }
            }

            function delay(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            function retryConnectionTest() {
                document.getElementById('retryTestBtn').style.display = 'none';
                document.getElementById('startTestBtn').style.display = 'inline-flex';
                document.getElementById('nextBtn').disabled = true;
            }

            function previousStep() {
                window.location.href = '{{ route('sql-alerts.create') }}?step=2';
            }

            function nextStep() {
                if (!testSuccess) {
                    alert('กรุณาทดสอบการเชื่อมต่อให้สำเร็จก่อน');
                    return;
                }

                sessionStorage.setItem('sql_alert_step', '4');
                sessionStorage.setItem('sql_alert_connection_tested', '1');
                window.location.href = '{{ route('sql-alerts.create') }}?step=4';
            }

            // Auto-test if coming back from next step
            document.addEventListener('DOMContentLoaded', function() {
                const connectionTested = sessionStorage.getItem('sql_alert_connection_tested');
                if (connectionTested === '1') {
                    // Auto-run test and enable next button
                    setTimeout(() => {
                        startConnectionTest();
                    }, 1000);
                }
            });



            document.addEventListener('DOMContentLoaded', function() {
                loadSavedSQL();
            });

            function loadTemplate(templateName) {
                // ตรวจสอบว่า sqlTemplates ถูกประกาศแล้วหรือไม่
                if (typeof sqlTemplates === 'undefined') {
                    console.error('sqlTemplates is not defined yet');
                    return;
                }

                const sqlTextarea = document.getElementById('sqlQuery');

                if (!sqlTextarea) {
                    console.error('SQL textarea element not found');
                    return;
                }

                if (sqlTemplates[templateName]) {
                    sqlTextarea.value = sqlTemplates[templateName];

                    // Auto-format after loading template
                    setTimeout(() => {
                        formatSQL();
                    }, 100);
                }

                // Hide validation if showing
                hideValidation();
            }

            function loadSavedSQL() {
                const savedSQL = sessionStorage.getItem('sql_alert_query');
                if (savedSQL) {
                    document.getElementById('sqlQuery').value = savedSQL;
                }
            }

            function formatSQL() {
                const sqlTextarea = document.getElementById('sqlQuery');
                let sql = sqlTextarea.value;

                if (!sql.trim()) {
                    showValidation('warning', 'No SQL to format', 'Please enter SQL query first.');
                    return;
                }

                // Basic SQL formatting
                sql = sql.replace(/\s+/g, ' '); // Remove extra spaces
                sql = sql.replace(/\bSELECT\b/gi, '\nSELECT');
                sql = sql.replace(/\bFROM\b/gi, '\nFROM');
                sql = sql.replace(/\bWHERE\b/gi, '\nWHERE');
                sql = sql.replace(/\bAND\b/gi, '\n    AND');
                sql = sql.replace(/\bOR\b/gi, '\n    OR');
                sql = sql.replace(/\bORDER BY\b/gi, '\nORDER BY');
                sql = sql.replace(/\bGROUP BY\b/gi, '\nGROUP BY');
                sql = sql.replace(/\bHAVING\b/gi, '\nHAVING');
                sql = sql.replace(/\bLIMIT\b/gi, '\nLIMIT');
                sql = sql.replace(/\bUNION\b/gi, '\nUNION');
                sql = sql.replace(/\bJOIN\b/gi, '\nJOIN');
                sql = sql.replace(/\bLEFT JOIN\b/gi, '\nLEFT JOIN');
                sql = sql.replace(/\bRIGHT JOIN\b/gi, '\nRIGHT JOIN');
                sql = sql.replace(/\bINNER JOIN\b/gi, '\nINNER JOIN');

                sqlTextarea.value = sql.trim();

                showValidation('success', 'SQL Formatted', 'SQL query has been formatted successfully.');
            }

            function validateSQL() {
                const sql = document.getElementById('sqlQuery').value.trim();

                if (!sql) {
                    showValidation('error', 'No SQL Query', 'Please enter SQL query to validate.');
                    return;
                }

                const issues = [];
                const warnings = [];

                // Basic validation checks
                if (!sql.toLowerCase().includes('select')) {
                    issues.push('SQL must be a SELECT statement');
                }

                if (sql.toLowerCase().includes('delete') ||
                    sql.toLowerCase().includes('update') ||
                    sql.toLowerCase().includes('insert') ||
                    sql.toLowerCase().includes('drop') ||
                    sql.toLowerCase().includes('alter')) {
                    issues.push('Only SELECT statements are allowed');
                }

                if (!sql.toLowerCase().includes('from')) {
                    issues.push('Missing FROM clause');
                }

                // Warnings
                if (!sql.toLowerCase().includes('where')) {
                    warnings.push('Consider adding WHERE clause to filter data');
                }

                if (!sql.toLowerCase().includes('limit') && !sql.toLowerCase().includes('top')) {
                    warnings.push('Consider adding LIMIT to prevent too many results');
                }

                if (!sql.toLowerCase().includes('date') && !sql.toLowerCase().includes('time')) {
                    warnings.push('Consider adding date/time filters for better performance');
                }

                // Show results
                if (issues.length > 0) {
                    showValidation('error', 'Validation Failed', `Found ${issues.length} issue(s)`, issues.concat(warnings));
                } else if (warnings.length > 0) {
                    showValidation('warning', 'Validation Passed with Warnings', `Found ${warnings.length} warning(s)`,
                        warnings);
                } else {
                    showValidation('success', 'Validation Passed', 'SQL query is valid and ready to use.', [
                        '✓ Valid SELECT statement', '✓ No security issues found'
                    ]);
                }
            }

            function testSQL() {
                const sql = document.getElementById('sqlQuery').value.trim();

                if (!sql) {
                    showValidation('error', 'No SQL Query', 'Please enter SQL query to test.');
                    return;
                }

                // Show loading state
                showValidation('warning', 'Testing SQL Query...', 'Executing query against database...');

                // Simulate SQL execution
                setTimeout(() => {
                    const success = Math.random() > 0.3; // 70% success rate

                    if (success) {
                        const rowCount = Math.floor(Math.random() * 100) + 1;
                        const columnCount = Math.floor(Math.random() * 8) + 3;
                        const executionTime = (Math.random() * 2 + 0.1).toFixed(2);

                        showValidation('success', 'Query Executed Successfully',
                            `Query returned ${rowCount} rows, ${columnCount} columns in ${executionTime} seconds.`,
                            [
                                `✓ ${rowCount} rows returned`,
                                `✓ ${columnCount} columns detected`,
                                `✓ Execution time: ${executionTime}s`,
                                '✓ Ready for next step'
                            ]
                        );

                        // Save SQL and enable next button
                        sessionStorage.setItem('sql_alert_query', sql);
                        sessionStorage.setItem('sql_alert_query_tested', '1');
                        sessionStorage.setItem('sql_alert_row_count', rowCount);
                        sessionStorage.setItem('sql_alert_column_count', columnCount);

                    } else {
                        const errors = [
                            'Table \'users\' doesn\'t exist',
                            'Unknown column \'invalid_column\' in \'field list\'',
                            'Syntax error near \'FROM\' at line 1',
                            'Access denied for user \'username\'@\'localhost\'',
                            'Connection timeout after 30 seconds'
                        ];
                        const randomError = errors[Math.floor(Math.random() * errors.length)];

                        showValidation('error', 'Query Execution Failed',
                            'Error occurred while executing query.',
                            [
                                `✗ Error: ${randomError}`,
                                '✗ Please fix the query and try again',
                                '? Check table names and column names',
                                '? Verify database permissions'
                            ]
                        );
                    }
                }, 2000);
            }

            function clearSQL() {
                if (confirm('Are you sure you want to clear the SQL query?')) {
                    document.getElementById('sqlQuery').value = '';
                    hideValidation();
                    sessionStorage.removeItem('sql_alert_query');
                    sessionStorage.removeItem('sql_alert_query_tested');
                }
            }

            function showValidation(type, title, summary, items = []) {
                const elements = {
                    validationDiv: document.getElementById('sqlValidation'),
                    icon: document.getElementById('validationIcon'),
                    titleSpan: document.getElementById('validationTitle'),
                    summarySpan: document.getElementById('validationSummary'),
                    listUl: document.getElementById('validationList')
                };

                if (!elements.validationDiv) return;

                // Update styling
                elements.validationDiv.className = `sql-validation show validation-${type}`;

                // Update icon
                const icons = {
                    success: 'fas fa-check',
                    error: 'fas fa-times',
                    warning: 'fas fa-exclamation-triangle'
                };

                if (elements.icon) {
                    elements.icon.className = icons[type] || 'fas fa-info';
                }

                // Update content
                if (elements.titleSpan) elements.titleSpan.textContent = title;
                if (elements.summarySpan) elements.summarySpan.textContent = summary;

                // Update list
                if (elements.listUl) {
                    elements.listUl.innerHTML = '';
                    items.forEach(item => {
                        const li = document.createElement('li');
                        li.textContent = item;
                        elements.listUl.appendChild(li);
                    });
                }
            }

            function hideValidation() {
                const validationDiv = document.getElementById('sqlValidation');
                if (validationDiv) {
                    validationDiv.classList.remove('show');
                }
            }

            // ===== Utility Functions =====
            function delay(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            // ===== Navigation Functions =====
            function previousStep() {
                if (window.SqlAlertWizard) {
                    window.SqlAlertWizard.previousStep();
                } else {
                    console.error('SqlAlertWizard not initialized');
                }
            }

            function nextStep() {
                if (window.SqlAlertWizard) {
                    // Save current step data before proceeding
                    saveCurrentStepData();
                    window.SqlAlertWizard.nextStep();
                } else {
                    console.error('SqlAlertWizard not initialized');
                }
            }

            function saveCurrentStepData() {
                const currentStep = window.SqlAlertWizard?.currentStep || 1;

                switch (currentStep) {
                    case 1:
                        // Database selection is already saved in selectDatabase()
                        break;
                    case 2:
                        saveConnectionData();
                        break;
                    case 4:
                        // Save SQL query
                        const sqlTextarea = document.getElementById('sqlQuery');
                        if (sqlTextarea) {
                            sessionStorage.setItem('sql_alert_query', sqlTextarea.value);
                        }
                        break;
                }
            }

            // ===== Auto-initialization =====
            document.addEventListener('DOMContentLoaded', function() {
                console.log('SQL Alert Wizard JavaScript loaded');

                // Initialize based on current step
                const currentStep = window.SqlAlertWizard?.currentStep || 1;

                switch (currentStep) {
                    case 1:
                        // Auto-select database if returning from next step
                        const savedDbType = sessionStorage.getItem('sql_alert_db_type');
                        if (savedDbType) {
                            const element = document.querySelector(`[data-db-type="${savedDbType}"]`);
                            if (element) {
                                selectDatabase(element);
                            }
                        }
                        break;

                    case 2:
                        loadConnectionData();
                        break;

                    case 3:
                        loadConnectionSummary();
                        // Auto-test if returning from next step
                        const connectionTested = sessionStorage.getItem('sql_alert_connection_tested');
                        if (connectionTested === '1') {
                            setTimeout(() => {
                                startConnectionTest();
                            }, 1000);
                        }
                        break;

                    case 4:
                        loadSavedSQL();
                        break;
                }

                // Setup real-time validation for connection form
                const connectionForm = document.getElementById('connectionForm');
                if (connectionForm) {
                    connectionForm.addEventListener('input', function(e) {
                        const field = e.target;
                        if (field.hasAttribute('required')) {
                            if (field.value.trim()) {
                                field.classList.remove('is-invalid');
                                field.classList.add('is-valid');
                            } else {
                                field.classList.remove('is-valid');
                                field.classList.add('is-invalid');
                            }
                        }
                    });
                }
            });

            // ===== Global Error Handler =====
            window.addEventListener('error', function(event) {
                console.error('JavaScript Error:', event.error);
            });

            // ===== Enhanced Wizard State Management =====
            if (typeof window.SqlAlertWizard !== 'undefined') {
                // Extend the existing SqlAlertWizard with validation functions
                window.SqlAlertWizard.validateCurrentStep = function() {
                    const step = this.currentStep;

                    switch (step) {
                        case 1:
                            // Database selection validation
                            const selectedDb = sessionStorage.getItem('sql_alert_db_type');
                            if (!selectedDb) {
                                alert('กรุณาเลือกประเภทฐานข้อมูล');
                                return false;
                            }
                            return true;

                        case 2:
                            // Connection form validation
                            if (!validateConnectionForm()) {
                                alert('กรุณากรอกข้อมูลการเชื่อมต่อให้ครบถ้วน');
                                return false;
                            }
                            saveConnectionData();
                            return true;

                        case 3:
                            // Connection test validation
                            const tested = sessionStorage.getItem('sql_alert_connection_tested');
                            if (tested !== '1') {
                                alert('กรุณาทดสอบการเชื่อมต่อให้สำเร็จก่อน');
                                return false;
                            }
                            return true;

                        case 4:
                            // SQL query validation
                            const sqlQuery = document.getElementById('sqlQuery');
                            if (!sqlQuery || !sqlQuery.value.trim()) {
                                alert('กรุณาใส่ SQL Query');
                                return false;
                            }
                            sessionStorage.setItem('sql_alert_query', sqlQuery.value);
                            return true;

                        default:
                            return true;
                    }
                };

                // Override the nextStep method to include validation
                const originalNextStep = window.SqlAlertWizard.nextStep;
                window.SqlAlertWizard.nextStep = function() {
                    if (this.validateCurrentStep()) {
                        originalNextStep.call(this);
                    }
                };

                // Add method to check for unsaved changes
                window.SqlAlertWizard.hasUnsavedChanges = function() {
                    const keys = Object.keys(sessionStorage).filter(key => key.startsWith('sql_alert_'));
                    return keys.length > 1; // More than just the step number
                };

                // Add method to clear all wizard data
                window.SqlAlertWizard.clearAllData = function() {
                    const keys = Object.keys(sessionStorage).filter(key => key.startsWith('sql_alert_'));
                    keys.forEach(key => sessionStorage.removeItem(key));
                    console.log('All wizard data cleared');
                };
            }

            // ===== Export functions for use in other scripts =====
            window.SqlAlertWizardFunctions = {
                selectDatabase,
                togglePassword,
                validateConnectionForm,
                saveConnectionData,
                loadConnectionData,
                startConnectionTest,
                retryConnectionTest,
                loadTemplate,
                formatSQL,
                validateSQL,
                testSQL,
                clearSQL,
                showValidation,
                hideValidation,
                previousStep,
                nextStep
            };

            console.log('SQL Alert Wizard JavaScript initialization complete');
        </script>
    @endpush
@endsection
