<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PWA Debug - Smart Notification System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#256B36">
    
    <style>
        .debug-section {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
        }
        .status-ok { color: #198754; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.5rem;
            font-family: monospace;
            font-size: 0.875rem;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="bi bi-gear me-2"></i>PWA Debug Center</h1>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-1"></i>กลับหน้าหลัก
                    </a>
                </div>
                
                <!-- Service Worker Status -->
                <div class="debug-section">
                    <h3><i class="bi bi-server me-2"></i>Service Worker Status</h3>
                    <div id="sw-status">
                        <p><i class="bi bi-hourglass-split me-2"></i>กำลังตรวจสอบ...</p>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary btn-sm" onclick="checkServiceWorker()">
                            <i class="bi bi-arrow-clockwise me-1"></i>ตรวจสอบใหม่
                        </button>
                        <button class="btn btn-success btn-sm" onclick="registerServiceWorker()">
                            <i class="bi bi-plus-circle me-1"></i>ลงทะเบียน SW
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="unregisterServiceWorker()">
                            <i class="bi bi-trash me-1"></i>ลบ SW
                        </button>
                    </div>
                </div>
                
                <!-- Cache Status -->
                <div class="debug-section">
                    <h3><i class="bi bi-hdd me-2"></i>Cache Status</h3>
                    <div id="cache-status">
                        <p><i class="bi bi-hourglass-split me-2"></i>กำลังตรวจสอบ...</p>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary btn-sm" onclick="checkCaches()">
                            <i class="bi bi-arrow-clockwise me-1"></i>ตรวจสอบ Cache
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="clearCaches()">
                            <i class="bi bi-trash me-1"></i>ล้าง Cache
                        </button>
                    </div>
                </div>
                
                <!-- Network Status -->
                <div class="debug-section">
                    <h3><i class="bi bi-wifi me-2"></i>Network Status</h3>
                    <div id="network-status">
                        <p>สถานะ: <span id="online-status"></span></p>
                        <p>การเชื่อมต่อ: <span id="connection-type"></span></p>
                    </div>
                </div>
                
                <!-- PWA Installation -->
                <div class="debug-section">
                    <h3><i class="bi bi-download me-2"></i>PWA Installation</h3>
                    <div id="pwa-status">
                        <p id="install-status">ตรวจสอบความพร้อมการติดตั้ง...</p>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-success btn-sm d-none" id="install-btn" onclick="installPWA()">
                            <i class="bi bi-download me-1"></i>ติดตั้ง PWA
                        </button>
                    </div>
                </div>
                
                <!-- Test Functions -->
                <div class="debug-section">
                    <h3><i class="bi bi-flask me-2"></i>Test Functions</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-outline-primary w-100 mb-2" onclick="testOfflinePage()">
                                <i class="bi bi-wifi-off me-1"></i>ทดสอบหน้า Offline
                            </button>
                            <button class="btn btn-outline-info w-100 mb-2" onclick="testNotification()">
                                <i class="bi bi-bell me-1"></i>ทดสอบ Notification
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-outline-warning w-100 mb-2" onclick="simulateOffline()">
                                <i class="bi bi-wifi-off me-1"></i>จำลอง Offline
                            </button>
                            <button class="btn btn-outline-secondary w-100 mb-2" onclick="showDebugInfo()">
                                <i class="bi bi-info-circle me-1"></i>ข้อมูล Debug
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Debug Console -->
                <div class="debug-section">
                    <h3><i class="bi bi-terminal me-2"></i>Debug Console</h3>
                    <div class="code-block" id="debug-console">
                        PWA Debug Console พร้อมใช้งาน...
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="clearConsole()">
                            <i class="bi bi-trash me-1"></i>ล้าง Console
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let deferredPrompt;
        
        // Debug logging
        function debugLog(message, type = 'info') {
            const console = document.getElementById('debug-console');
            const timestamp = new Date().toLocaleTimeString();
            const icon = type === 'error' ? '❌' : type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️';
            console.innerHTML += `\n[${timestamp}] ${icon} ${message}`;
            console.scrollTop = console.scrollHeight;
        }
        
        // Check Service Worker
        async function checkServiceWorker() {
            const statusEl = document.getElementById('sw-status');
            debugLog('ตรวจสอบ Service Worker...');
            
            if ('serviceWorker' in navigator) {
                try {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    
                    if (registrations.length > 0) {
                        let html = '<p class="status-ok"><i class="bi bi-check-circle me-2"></i>Service Worker พร้อมใช้งาน</p>';
                        
                        registrations.forEach((reg, index) => {
                            html += `<div class="mt-2">
                                <strong>Registration ${index + 1}:</strong><br>
                                <small>Scope: ${reg.scope}</small><br>
                                <small>State: ${reg.active?.state || 'ไม่ทราบ'}</small><br>
                                <small>Script: ${reg.active?.scriptURL || 'ไม่ทราบ'}</small>
                            </div>`;
                        });
                        
                        statusEl.innerHTML = html;
                        debugLog(`พบ Service Worker ${registrations.length} รายการ`, 'success');
                    } else {
                        statusEl.innerHTML = '<p class="status-warning"><i class="bi bi-exclamation-triangle me-2"></i>ไม่พบ Service Worker</p>';
                        debugLog('ไม่พบ Service Worker', 'warning');
                    }
                } catch (error) {
                    statusEl.innerHTML = `<p class="status-error"><i class="bi bi-x-circle me-2"></i>เกิดข้อผิดพลาด: ${error.message}</p>`;
                    debugLog(`ข้อผิดพลาด SW: ${error.message}`, 'error');
                }
            } else {
                statusEl.innerHTML = '<p class="status-error"><i class="bi bi-x-circle me-2"></i>เบราว์เซอร์ไม่รองรับ Service Worker</p>';
                debugLog('เบราว์เซอร์ไม่รองรับ Service Worker', 'error');
            }
        }
        
        // Register Service Worker
        async function registerServiceWorker() {
            debugLog('กำลังลงทะเบียน Service Worker...');
            
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                debugLog('ลงทะเบียน Service Worker สำเร็จ', 'success');
                setTimeout(checkServiceWorker, 1000);
            } catch (error) {
                debugLog(`การลงทะเบียน SW ล้มเหลว: ${error.message}`, 'error');
            }
        }
        
        // Unregister Service Worker
        async function unregisterServiceWorker() {
            debugLog('กำลังลบ Service Worker...');
            
            try {
                const registrations = await navigator.serviceWorker.getRegistrations();
                
                for (const registration of registrations) {
                    await registration.unregister();
                }
                
                debugLog('ลบ Service Worker สำเร็จ', 'success');
                setTimeout(checkServiceWorker, 1000);
            } catch (error) {
                debugLog(`การลบ SW ล้มเหลว: ${error.message}`, 'error');
            }
        }
        
        // Check caches
        async function checkCaches() {
            const statusEl = document.getElementById('cache-status');
            debugLog('ตรวจสอบ Cache...');
            
            try {
                const cacheNames = await caches.keys();
                
                if (cacheNames.length > 0) {
                    let html = '<p class="status-ok"><i class="bi bi-check-circle me-2"></i>พบ Cache ในระบบ</p>';
                    
                    for (const cacheName of cacheNames) {
                        const cache = await caches.open(cacheName);
                        const keys = await cache.keys();
                        html += `<div class="mt-2">
                            <strong>${cacheName}:</strong> ${keys.length} รายการ<br>
                            <small>${keys.slice(0, 3).map(k => k.url.split('/').pop()).join(', ')}${keys.length > 3 ? '...' : ''}</small>
                        </div>`;
                    }
                    
                    statusEl.innerHTML = html;
                    debugLog(`พบ Cache ${cacheNames.length} รายการ`, 'success');
                } else {
                    statusEl.innerHTML = '<p class="status-warning"><i class="bi bi-exclamation-triangle me-2"></i>ไม่พบ Cache</p>';
                    debugLog('ไม่พบ Cache', 'warning');
                }
            } catch (error) {
                statusEl.innerHTML = `<p class="status-error"><i class="bi bi-x-circle me-2"></i>เกิดข้อผิดพลาด: ${error.message}</p>`;
                debugLog(`ข้อผิดพลาด Cache: ${error.message}`, 'error');
            }
        }
        
        // Clear caches
        async function clearCaches() {
            debugLog('กำลังล้าง Cache...');
            
            try {
                const cacheNames = await caches.keys();
                
                for (const cacheName of cacheNames) {
                    await caches.delete(cacheName);
                }
                
                debugLog('ล้าง Cache สำเร็จ', 'success');
                setTimeout(checkCaches, 1000);
            } catch (error) {
                debugLog(`การล้าง Cache ล้มเหลว: ${error.message}`, 'error');
            }
        }
        
        // Test functions
        function testOfflinePage() {
            debugLog('เปิดหน้า Offline...');
            window.open('/offline.html', '_blank');
        }
        
        function testNotification() {
            debugLog('ทดสอบ Notification...');
            
            if ('Notification' in window) {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification('ทดสอบจาก PWA Debug', {
                            body: 'การแจ้งเตือนทำงานปกติ',
                            icon: '/icon-192x192.png'
                        });
                        debugLog('ส่ง Notification สำเร็จ', 'success');
                    } else {
                        debugLog('ไม่ได้รับอนุญาตให้แสดง Notification', 'warning');
                    }
                });
            } else {
                debugLog('เบราว์เซอร์ไม่รองรับ Notification', 'error');
            }
        }
        
        function simulateOffline() {
            debugLog('จำลองสถานะ Offline (ต้องใช้ DevTools)');
            alert('ไปที่ DevTools > Network > เลือก Offline เพื่อจำลองสถานะออฟไลน์');
        }
        
        function showDebugInfo() {
            const info = {
                userAgent: navigator.userAgent,
                online: navigator.onLine,
                language: navigator.language,
                platform: navigator.platform,
                cookieEnabled: navigator.cookieEnabled,
                serviceWorker: 'serviceWorker' in navigator,
                location: window.location.href
            };
            
            debugLog('ข้อมูลระบบ:');
            debugLog(JSON.stringify(info, null, 2));
        }
        
        function clearConsole() {
            document.getElementById('debug-console').innerHTML = 'PWA Debug Console ล้างแล้ว...';
        }
        
        // Update network status
        function updateNetworkStatus() {
            const onlineStatus = document.getElementById('online-status');
            const connectionType = document.getElementById('connection-type');
            
            onlineStatus.innerHTML = navigator.onLine ? 
                '<span class="status-ok">เชื่อมต่อแล้ว</span>' : 
                '<span class="status-error">ออฟไลน์</span>';
                
            connectionType.innerHTML = navigator.connection?.effectiveType || 'ไม่ทราบ';
        }
        
        // PWA Installation
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            document.getElementById('install-btn').classList.remove('d-none');
            document.getElementById('install-status').innerHTML = 'พร้อมติดตั้ง PWA';
            debugLog('PWA พร้อมติดตั้ง', 'success');
        });
        
        async function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const result = await deferredPrompt.userChoice;
                
                if (result.outcome === 'accepted') {
                    debugLog('ผู้ใช้ติดตั้ง PWA', 'success');
                } else {
                    debugLog('ผู้ใช้ยกเลิกการติดตั้ง PWA', 'warning');
                }
                
                deferredPrompt = null;
                document.getElementById('install-btn').classList.add('d-none');
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('PWA Debug Center เริ่มต้นแล้ว');
            
            // Initial checks
            checkServiceWorker();
            checkCaches();
            updateNetworkStatus();
            
            // Event listeners
            window.addEventListener('online', () => {
                updateNetworkStatus();
                debugLog('กลับมาออนไลน์', 'success');
            });
            
            window.addEventListener('offline', () => {
                updateNetworkStatus();
                debugLog('ตัดการเชื่อมต่อ', 'warning');
            });
            
            // Check PWA installation status
            if (window.matchMedia('(display-mode: standalone)').matches) {
                document.getElementById('install-status').innerHTML = 'PWA ติดตั้งแล้ว';
                debugLog('PWA กำลังทำงานในโหมด standalone', 'success');
            } else {
                document.getElementById('install-status').innerHTML = 'PWA ยังไม่ติดตั้ง';
            }
        });
    </script>
</body>
</html>