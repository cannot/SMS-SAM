<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Notification System - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-bell text-2xl text-blue-600"></i>
                        <span class="ml-2 text-xl font-semibold text-gray-900">Smart Notification</span>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-user-circle text-gray-500"></i>
                        <span class="text-gray-700">{{ $user->display_name ?? $user->username }}</span>
                    </div>
                    
                    <form action="/logout" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm transition duration-200">
                            <i class="fas fa-sign-out-alt mr-1"></i>
                            ออกจากระบบ
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">
                        ยินดีต้อนรับสู่ Smart Notification System
                    </h1>
                    <p class="text-gray-600 mb-4">
                        สวัสดี คุณ <strong>{{ $user->display_name ?? $user->username }}</strong>
                    </p>
                    
                    <!-- User Info Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                        <!-- User Profile Card -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-user text-blue-500 text-xl mr-3"></i>
                                <div>
                                    <h3 class="font-semibold text-gray-900">ข้อมูลผู้ใช้</h3>
                                    <p class="text-sm text-gray-600">{{ $user->email ?? 'ไม่ระบุ' }}</p>
                                    <p class="text-sm text-gray-600">{{ $user->department ?? 'ไม่ระบุแผนก' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Last Login Card -->
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-clock text-green-500 text-xl mr-3"></i>
                                <div>
                                    <h3 class="font-semibold text-gray-900">เข้าสู่ระบบล่าสุด</h3>
                                    <p class="text-sm text-gray-600">{{ $user->updated_at ? $user->updated_at->format('d/m/Y H:i') : 'ไม่ระบุ' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- System Status Card -->
                        <div class="bg-yellow-50 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-server text-yellow-500 text-xl mr-3"></i>
                                <div>
                                    <h3 class="font-semibold text-gray-900">สถานะระบบ</h3>
                                    <p class="text-sm text-green-600">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        ทำงานปกติ
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">เมนูหลัก</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Notifications Menu -->
                        <a href="/notifications" class="block p-6 bg-blue-50 hover:bg-blue-100 rounded-lg transition duration-200">
                            <div class="text-center">
                                <i class="fas fa-bell text-3xl text-blue-500 mb-3"></i>
                                <h3 class="font-semibold text-gray-900">การแจ้งเตือน</h3>
                                <p class="text-sm text-gray-600 mt-1">จัดการการแจ้งเตือน</p>
                            </div>
                        </a>

                        <!-- Templates Menu -->
                        <a href="/templates" class="block p-6 bg-green-50 hover:bg-green-100 rounded-lg transition duration-200">
                            <div class="text-center">
                                <i class="fas fa-file-alt text-3xl text-green-500 mb-3"></i>
                                <h3 class="font-semibold text-gray-900">เทมเพลต</h3>
                                <p class="text-sm text-gray-600 mt-1">จัดการเทมเพลตข้อความ</p>
                            </div>
                        </a>

                        <!-- Users Menu -->
                        <a href="/users" class="block p-6 bg-purple-50 hover:bg-purple-100 rounded-lg transition duration-200">
                            <div class="text-center">
                                <i class="fas fa-users text-3xl text-purple-500 mb-3"></i>
                                <h3 class="font-semibold text-gray-900">ผู้ใช้งาน</h3>
                                <p class="text-sm text-gray-600 mt-1">จัดการผู้ใช้งาน</p>
                            </div>
                        </a>

                        <!-- Reports Menu -->
                        <a href="/reports" class="block p-6 bg-orange-50 hover:bg-orange-100 rounded-lg transition duration-200">
                            <div class="text-center">
                                <i class="fas fa-chart-bar text-3xl text-orange-500 mb-3"></i>
                                <h3 class="font-semibold text-gray-900">รายงาน</h3>
                                <p class="text-sm text-gray-600 mt-1">ดูรายงานสถิติ</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">กิจกรรมล่าสุด</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-900">เข้าสู่ระบบเรียบร้อยแล้ว</p>
                                <p class="text-xs text-gray-500">{{ now()->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-sync text-green-500 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-900">ข้อมูลจาก LDAP ได้รับการซิงค์แล้ว</p>
                                <p class="text-xs text-gray-500">{{ $user->ldap_synced_at ? $user->ldap_synced_at->format('d/m/Y H:i') : 'ไม่ระบุ' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-sm text-gray-500">
                © 2025 Smart Notification System. All rights reserved.
            </p>
        </div>
    </footer>
</body>
</html>