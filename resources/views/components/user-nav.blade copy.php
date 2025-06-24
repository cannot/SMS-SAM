
@php
    $unreadCount = Auth::user()->unreadNotifications->count();
@endphp

<!-- Notification Section -->
<li class="nav-item">
    {{-- <a class="nav-link {{ request()->routeIs('notifications*') ? 'active' : '' }}"
        href="{{ route('notifications.index') }}">
        <i class="bi bi-envelope"></i>
        <span class="nav-text">การแจ้งเตือน</span>
        @php
            // Combine both Laravel notifications and custom notifications
            $laravelUnread = Auth::user()->unreadNotifications->count();
            $customUnread = 0; // Implement if you need custom notification read status
            $totalUnread = $laravelUnread + $customUnread;
        @endphp
        @if($totalUnread > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            {{ $totalUnread }}
        </span>
        @endif
    </a> --}}
</li>

<!-- Notification Sub-menu -->
@canany(['create-notifications', 'view-notification-templates'])
<li class="nav-item">
    <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#notificationActions" 
       aria-expanded="false" aria-controls="notificationActions">
        <i class="bi bi-plus-circle"></i>
        <span class="nav-text">จัดการการแจ้งเตือน</span>
        <i class="fas fa-chevron-down ms-auto nav-text"></i>
    </a>
    <div class="collapse" id="notificationActions">
        <ul class="nav flex-column ms-3">
            {{-- @can('create-notifications')
            <li class="nav-item">
                <a class="nav-link py-1 {{ request()->routeIs('notifications.create') ? 'active' : '' }}" 
                   href="{{ route('notifications.create') }}">
                    <i class="bi bi-plus"></i>
                    <span class="nav-text">ส่งการแจ้งเตือน</span>
                </a>
            </li>
            @endcan --}}
            
            @can('view-notification-templates')
            <li class="nav-item">
                <a class="nav-link py-1 {{ request()->routeIs('templates*') ? 'active' : '' }}" 
                   href="{{ route('templates.index') }}">
                    <i class="bi bi-file-earmark-text"></i>
                    <span class="nav-text">Templates</span>
                </a>
            </li>
            @endcan
            
            {{-- <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('notifications.index', ['status' => 'unread']) }}">
                    <i class="bi bi-envelope-open"></i>
                    <span class="nav-text">ยังไม่อ่าน</span>
                    @if($unreadCount > 0)
                    <span class="badge bg-warning text-dark ms-auto">{{ $unreadCount }}</span>
                    @endif
                </a>
            </li> --}}
            
            {{-- <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('notifications.index', ['status' => 'read']) }}">
                    <i class="bi bi-envelope-check"></i>
                    <span class="nav-text">อ่านแล้ว</span>
                </a>
            </li> --}}
        </ul>
    </div>
</li>
@endcanany

<!-- Groups Section -->
@canany(['view-groups', 'manage-groups'])
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('groups*') ? 'active' : '' }}"
        href="{{ route('groups.index') }}">
        <i class="bi bi-people"></i>
        <span class="nav-text">กลุ่มผู้รับ</span>
        <span class="badge bg-light text-dark ms-auto" data-stat="user_groups">
            {{ \App\Models\NotificationGroup::where('is_active', true)->count() }}
        </span>
    </a>
</li>

<!-- My Groups Sub-menu -->
<li class="nav-item">
    <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#groupActions" 
       aria-expanded="false" aria-controls="groupActions">
        <i class="bi bi-person-lines-fill"></i>
        <span class="nav-text">กลุ่มของฉัน</span>
        <i class="fas fa-chevron-down ms-auto nav-text"></i>
    </a>
    <div class="collapse" id="groupActions">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('groups.index', ['member' => Auth::id()]) }}">
                    <i class="bi bi-people-fill"></i>
                    <span class="nav-text">กลุ่มที่เข้าร่วม</span>
                    <span class="badge bg-info ms-auto">{{ Auth::user()->notificationGroups->count() }}</span>
                </a>
            </li>
            
            @can('manage-groups')
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('groups.index', ['created_by' => Auth::id()]) }}">
                    <i class="bi bi-person-gear"></i>
                    <span class="nav-text">กลุ่มที่สร้าง</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('groups.create') }}">
                    <i class="bi bi-plus-circle"></i>
                    <span class="nav-text">สร้างกลุ่มใหม่</span>
                </a>
            </li>
            @endcan
        </ul>
    </div>
</li>
@endcanany

<!-- Users Section -->
@canany(['view-users', 'view-user-profiles'])
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('users*') && !request()->routeIs('users.preferences*') && !request()->routeIs('users.permissions*') ? 'active' : '' }}"
        href="{{ route('users.index') }}">
        <i class="bi bi-person-lines-fill"></i>
        <span class="nav-text">ผู้ใช้งาน</span>
        <span class="badge bg-light text-dark ms-auto" data-stat="active_users">
            {{ \App\Models\User::where('is_active', true)->count() }}
        </span>
    </a>
</li>

<!-- User Quick Actions -->
<li class="nav-item">
    <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#userActions" 
       aria-expanded="false" aria-controls="userActions">
        <i class="bi bi-person-gear"></i>
        <span class="nav-text">จัดการโปรไฟล์</span>
        <i class="fas fa-chevron-down ms-auto nav-text"></i>
    </a>
    <div class="collapse" id="userActions">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a class="nav-link py-1 {{ request()->routeIs('users.show', Auth::user()) ? 'active' : '' }}" 
                   href="{{ route('users.show', Auth::user()) }}">
                    <i class="bi bi-person"></i>
                    <span class="nav-text">โปรไฟล์ของฉัน</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link py-1 {{ request()->routeIs('users.preferences*') ? 'active' : '' }}" 
                   href="{{ route('users.preferences') }}">
                    <i class="bi bi-gear"></i>
                    <span class="nav-text">ตั้งค่าการแจ้งเตือน</span>
                </a>
            </li>
            
            @can('manage-user-permissions')
            <li class="nav-item">
                <a class="nav-link py-1 {{ request()->routeIs('users.permissions.show', Auth::user()) ? 'active' : '' }}" 
                   href="{{ route('users.permissions.show', Auth::user()) }}">
                    <i class="fas fa-key"></i>
                    <span class="nav-text">สิทธิ์ของฉัน</span>
                </a>
            </li>
            @endcan
            
            <li class="nav-item">
                <a class="nav-link py-1" href="#" onclick="testNotificationSettings(event)">
                    <i class="bi bi-bell"></i>
                    <span class="nav-text">ทดสอบการแจ้งเตือน</span>
                </a>
            </li>
        </ul>
    </div>
</li>
@endcanany

<!-- Reports Section -->
@canany(['view-reports', 'view-personal-reports'])
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('reports*') ? 'active' : '' }}"
        href="{{ route('reports.index') }}">
        <i class="bi bi-graph-up"></i>
        <span class="nav-text">รายงาน</span>
    </a>
</li>

<!-- Personal Reports Sub-menu -->
<li class="nav-item">
    <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#reportActions" 
       aria-expanded="false" aria-controls="reportActions">
        <i class="bi bi-file-earmark-bar-graph"></i>
        <span class="nav-text">รายงานส่วนตัว</span>
        <i class="fas fa-chevron-down ms-auto nav-text"></i>
    </a>
    <div class="collapse" id="reportActions">
        <ul class="nav flex-column ms-3">
            {{-- <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('reports.personal.notifications') }}">
                    <i class="bi bi-envelope-paper"></i>
                    <span class="nav-text">การแจ้งเตือนของฉัน</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('reports.personal.activity') }}">
                    <i class="bi bi-activity"></i>
                    <span class="nav-text">กิจกรรมของฉัน</span>
                </a>
            </li>
            
            @can('create-notifications')
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('reports.personal.sent') }}">
                    <i class="bi bi-send"></i>
                    <span class="nav-text">การแจ้งเตือนที่ส่ง</span>
                </a>
            </li>
            @endcan --}}
        </ul>
    </div>
</li>
@endcanany

<!-- Quick Stats for User -->
<li class="nav-item mt-3">
    <h6 class="sidebar-heading">
        <i class="fas fa-chart-pie"></i> 
        <span class="nav-text">สถิติส่วนตัว</span>
    </h6>
</li>

<li class="nav-item">
    <div class="mx-3 p-3 rounded" style="background: rgba(255, 255, 255, 0.1);">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-light">ได้รับการแจ้งเตือน</small>
            <span class="badge bg-primary" data-stat="user_received_notifications">
                {{ Auth::user()->notifications->count() }}
            </span>
        </div>
        
        @can('create-notifications')
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-light">ส่งการแจ้งเตือน</small>
            <span class="badge bg-success" data-stat="user_sent_notifications">
                {{ Auth::user()->createdNotifications->count() }}
            </span>
        </div>
        @endcan
        
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-light">กลุ่มที่เข้าร่วม</small>
            <span class="badge bg-info" data-stat="user_groups_count">
                {{ Auth::user()->notificationGroups->count() }}
            </span>
        </div>
        
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-light">ยังไม่อ่าน</small>
            <span class="badge bg-warning text-dark" data-stat="user_unread_count">
                {{ Auth::user()->unreadNotifications->count() }}
            </span>
        </div>
    </div>
</li>

<!-- Quick Actions -->
<li class="nav-item mt-3">
    <h6 class="sidebar-heading">
        <i class="fas fa-bolt"></i> 
        <span class="nav-text">การดำเนินการด่วน</span>
    </h6>
</li>

{{-- @can('create-notifications')
<li class="nav-item">
    <a class="nav-link" href="{{ route('notifications.create') }}">
        <i class="bi bi-lightning"></i>
        <span class="nav-text">ส่งการแจ้งเตือนด่วน</span>
    </a>
</li>
@endcan --}}

<li class="nav-item">
    <a class="nav-link" href="#" onclick="markAllAsRead(event)">
        <i class="bi bi-check-all"></i>
        <span class="nav-text">อ่านทั้งหมดแล้ว</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link" href="#" onclick="refreshNotifications(event)">
        <i class="bi bi-arrow-clockwise"></i>
        <span class="nav-text">รีเฟรชการแจ้งเตือน</span>
    </a>
</li>

@canany(['manage-groups', 'join-groups'])
<li class="nav-item">
    <a class="nav-link" href="#" onclick="quickJoinGroup(event)">
        <i class="bi bi-person-plus"></i>
        <span class="nav-text">เข้าร่วมกลุ่มด่วน</span>
    </a>
</li>
@endcanany

<!-- User Help Section -->
<li class="nav-item mt-3">
    <h6 class="sidebar-heading">
        <i class="fas fa-question-circle"></i> 
        <span class="nav-text">ความช่วยเหลือ</span>
    </h6>
</li>

<li class="nav-item">
    <a class="nav-link" href="#" onclick="openHelpModal(event)">
        <i class="bi bi-question-circle"></i>
        <span class="nav-text">คู่มือการใช้งาน</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link" href="#" onclick="openFeedbackModal(event)">
        <i class="bi bi-chat-dots"></i>
        <span class="nav-text">แสดงความคิดเห็น</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link" href="#" onclick="openShortcutsModal(event)">
        <i class="bi bi-keyboard"></i>
        <span class="nav-text">คีย์ลัด</span>
    </a>
</li>

<!-- Test Notification Modal -->
<div class="modal fade" id="testNotificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ทดสอบการแจ้งเตือน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="testNotificationForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ช่องทางการแจ้งเตือน:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="test_email" checked>
                            <label class="form-check-label" for="test_email">
                                <i class="bi bi-envelope me-1"></i> Email
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="teams" id="test_teams" checked>
                            <label class="form-check-label" for="test_teams">
                                <i class="fab fa-microsoft me-1"></i> Microsoft Teams
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="test_message" class="form-label">ข้อความทดสอบ:</label>
                        <textarea class="form-control" id="test_message" name="message" rows="3" 
                                  placeholder="ข้อความทดสอบการแจ้งเตือน...">นี่คือการทดสอบการตั้งค่าการแจ้งเตือนของคุณ</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="test_priority" class="form-label">ระดับความสำคัญ:</label>
                        <select class="form-select" id="test_priority" name="priority">
                            <option value="low">ต่ำ</option>
                            <option value="medium" selected>ปานกลาง</option>
                            <option value="high">สูง</option>
                            <option value="critical">วิกฤต</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">ส่งทดสอบ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Join Group Modal -->
<div class="modal fade" id="quickJoinGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เข้าร่วมกลุ่มด่วน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="group_search" class="form-label">ค้นหากลุ่ม:</label>
                    <input type="text" class="form-control" id="group_search" placeholder="ชื่อกลุ่ม...">
                    <div id="group_results" class="mt-2 border rounded p-2" style="max-height: 200px; overflow-y: auto; display: none;">
                        <!-- Search results will be populated here -->
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">กลุ่มยอดนิยม:</label>
                    <div id="popular_groups">
                        <!-- Popular groups will be loaded here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">คู่มือการใช้งาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>การใช้งานพื้นฐาน</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bi bi-envelope text-primary me-2"></i>
                                <strong>การแจ้งเตือน:</strong> ดูและจัดการการแจ้งเตือนที่ได้รับ
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-people text-success me-2"></i>
                                <strong>กลุ่มผู้รับ:</strong> เข้าร่วมหรือสร้างกลุ่มสำหรับรับการแจ้งเตือน
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-gear text-warning me-2"></i>
                                <strong>การตั้งค่า:</strong> ปรับแต่งการรับการแจ้งเตือน
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>คุณสมบัติขั้นสูง</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bi bi-lightning text-danger me-2"></i>
                                <strong>ส่งด่วน:</strong> ส่งการแจ้งเตือนเร่งด่วน
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-graph-up text-info me-2"></i>
                                <strong>รายงาน:</strong> ดูสถิติการใช้งาน
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-shield text-secondary me-2"></i>
                                <strong>สิทธิ์:</strong> จัดการสิทธิ์การเข้าถึง
                            </li>
                        </ul>
                    </div>
                </div>
                <hr>
                <div class="text-center">
                    <p class="text-muted">ต้องการความช่วยเหลือเพิ่มเติม?</p>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary" onclick="contactSupport()">
                            <i class="bi bi-telephone me-1"></i> ติดต่อฝ่ายสนับสนุน
                        </button>
                        <button type="button" class="btn btn-outline-info" onclick="openDocumentation()">
                            <i class="bi bi-book me-1"></i> เอกสารประกอบ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แสดงความคิดเห็น</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="feedbackForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="feedback_type" class="form-label">ประเภท:</label>
                        <select class="form-select" id="feedback_type" name="type" required>
                            <option value="">เลือกประเภท...</option>
                            <option value="suggestion">ข้อเสนอแนะ</option>
                            <option value="bug">รายงานปัญหา</option>
                            <option value="feature">ขอฟีเจอร์ใหม่</option>
                            <option value="compliment">ชมเชย</option>
                            <option value="complaint">ร้องเรียน</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="feedback_message" class="form-label">ข้อความ:</label>
                        <textarea class="form-control" id="feedback_message" name="message" rows="4" 
                                  placeholder="แสดงความคิดเห็นของคุณ..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="feedback_rating" class="form-label">คะแนน (1-5):</label>
                        <div class="btn-group w-100" role="group" data-rating="0">
                            <input type="radio" class="btn-check" name="rating" id="rating1" value="1">
                            <label class="btn btn-outline-warning" for="rating1">⭐</label>
                            <input type="radio" class="btn-check" name="rating" id="rating2" value="2">
                            <label class="btn btn-outline-warning" for="rating2">⭐⭐</label>
                            <input type="radio" class="btn-check" name="rating" id="rating3" value="3">
                            <label class="btn btn-outline-warning" for="rating3">⭐⭐⭐</label>
                            <input type="radio" class="btn-check" name="rating" id="rating4" value="4">
                            <label class="btn btn-outline-warning" for="rating4">⭐⭐⭐⭐</label>
                            <input type="radio" class="btn-check" name="rating" id="rating5" value="5">
                            <label class="btn btn-outline-warning" for="rating5">⭐⭐⭐⭐⭐</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success">ส่งความคิดเห็น</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Shortcuts Modal -->
<div class="modal fade" id="shortcutsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">คีย์ลัด</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>คีย์ลัด</th>
                                <th>การทำงาน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><kbd>Alt</kbd> + <kbd>S</kbd></td>
                                <td>เปิด/ปิด Sidebar</td>
                            </tr>
                            <tr>
                                <td><kbd>Ctrl</kbd> + <kbd>/</kbd></td>
                                <td>ค้นหา</td>
                            </tr>
                            <tr>
                                <td><kbd>Ctrl</kbd> + <kbd>N</kbd></td>
                                <td>สร้างการแจ้งเตือนใหม่</td>
                            </tr>
                            <tr>
                                <td><kbd>Ctrl</kbd> + <kbd>R</kbd></td>
                                <td>รีเฟรชการแจ้งเตือน</td>
                            </tr>
                            <tr>
                                <td><kbd>Ctrl</kbd> + <kbd>A</kbd></td>
                                <td>อ่านทั้งหมดแล้ว</td>
                            </tr>
                            <tr>
                                <td><kbd>Esc</kbd></td>
                                <td>ปิด Modal</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Test notification functionality
function testNotificationSettings(event) {
    event.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('testNotificationModal'));
    modal.show();
}

document.getElementById('testNotificationForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        channels: formData.getAll('channels[]'),
        message: formData.get('message'),
        priority: formData.get('priority')
    };
    
    try {
        const response = await fetch('{{ route('users.preferences.test') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.App.csrfToken
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification('ส่งการทดสอบแล้ว กรุณาตรวจสอบช่องทางการแจ้งเตือน', 'success');
            bootstrap.Modal.getInstance(document.getElementById('testNotificationModal')).hide();
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาด: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Test notification error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการส่งทดสอบ', 'error');
    }
});

// Mark all notifications as read
async function markAllAsRead(event) {
    event.preventDefault();
    
    if (!confirm('คุณต้องการทำเครื่องหมายการแจ้งเตือนทั้งหมดว่าอ่านแล้วหรือไม่?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.App.csrfToken
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification('ทำเครื่องหมายอ่านแล้วทั้งหมด', 'success');
            updateUserStats();
            
            // Update notification badge in navbar
            const badge = document.querySelector('.navbar .badge');
            if (badge) {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาด: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Mark all read error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาด', 'error');
    }
}

// Refresh notifications
async function refreshNotifications(event) {
    event.preventDefault();
    
    const link = event.target.closest('.nav-link');
    const originalContent = link.innerHTML;
    link.innerHTML = '<i class="bi bi-arrow-clockwise fa-spin"></i> <span class="nav-text">กำลังรีเฟรช...</span>';
    
    try {
        const response = await fetch('/api/notifications/refresh');
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification('รีเฟรชเรียบร้อย', 'success');
            updateUserStats();
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาด', 'error');
        }
    } catch (error) {
        console.error('Refresh error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาด', 'error');
    } finally {
        setTimeout(() => {
            link.innerHTML = originalContent;
        }, 1000);
    }
}

// Quick join group
function quickJoinGroup(event) {
    event.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('quickJoinGroupModal'));
    modal.show();
    loadPopularGroups();
}

// Group search functionality
let groupSearchTimeout;
document.getElementById('group_search')?.addEventListener('input', function() {
    clearTimeout(groupSearchTimeout);
    const searchTerm = this.value.trim();
    
    if (searchTerm.length >= 2) {
        groupSearchTimeout = setTimeout(() => {
            searchGroups(searchTerm);
        }, 300);
    } else {
        hideGroupResults();
    }
});

async function searchGroups(searchTerm) {
    try {
        const response = await fetch(`/api/groups/search?q=${encodeURIComponent(searchTerm)}`);
        const groups = await response.json();
        
        const resultsDiv = document.getElementById('group_results');
        
        if (groups.length > 0) {
            resultsDiv.innerHTML = groups.map(group => `
                <div class="group-result-item p-2 border-bottom cursor-pointer" 
                     onclick="joinGroup(${group.id}, '${group.name}')"
                     style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <strong>${group.name}</strong><br>
                            <small class="text-muted">${group.description || 'ไม่มีคำอธิบาย'}</small><br>
                            <small class="text-info">${group.users_count || 0} สมาชิก</small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary">เข้าร่วม</button>
                    </div>
                </div>
            `).join('');
            resultsDiv.style.display = 'block';
        } else {
            resultsDiv.innerHTML = '<div class="p-2 text-muted text-center">ไม่พบกลุ่ม</div>';
            resultsDiv.style.display = 'block';
        }
    } catch (error) {
        console.error('Group search error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการค้นหา', 'error');
    }
}

function hideGroupResults() {
    document.getElementById('group_results').style.display = 'none';
}

async function loadPopularGroups() {
    try {
        const response = await fetch('/api/groups/popular');
        const groups = await response.json();
        
        const popularDiv = document.getElementById('popular_groups');
        popularDiv.innerHTML = groups.map(group => `
            <div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded">
                <div>
                    <strong>${group.name}</strong>
                    <small class="d-block text-muted">${group.users_count} สมาชิก</small>
                </div>
                <button class="btn btn-sm btn-outline-success" onclick="joinGroup(${group.id}, '${group.name}')">
                    เข้าร่วม
                </button>
            </div>
        `).join('');
    } catch (error) {
        console.error('Load popular groups error:', error);
    }
}

async function joinGroup(groupId, groupName) {
    try {
        const response = await fetch(`/api/groups/${groupId}/join`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.App.csrfToken
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification(`เข้าร่วมกลุ่ม "${groupName}" แล้ว`, 'success');
            bootstrap.Modal.getInstance(document.getElementById('quickJoinGroupModal')).hide();
            updateUserStats();
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาด: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Join group error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการเข้าร่วมกลุ่ม', 'error');
    }
}

// Help and support functions
function openHelpModal(event) {
    event.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('helpModal'));
    modal.show();
}

function openFeedbackModal(event) {
    event.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('feedbackModal'));
    modal.show();
}

function openShortcutsModal(event) {
    event.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('shortcutsModal'));
    modal.show();
}

function contactSupport() {
    window.AppFunctions.showNotification('กำลังเปิดระบบติดต่อฝ่ายสนับสนุน...', 'info');
    // Implement support contact system
}

function openDocumentation() {
    window.open('/docs', '_blank');
}

// Feedback form submission
document.getElementById('feedbackForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('/api/feedback', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.App.csrfToken
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.AppFunctions.showNotification('ขอบคุณสำหรับความคิดเห็น', 'success');
            bootstrap.Modal.getInstance(document.getElementById('feedbackModal')).hide();
            this.reset();
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาด: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Feedback error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการส่งความคิดเห็น', 'error');
    }
});

// Update user statistics
function updateUserStats() {
    fetch('/api/user/stats')
        .then(response => response.json())
        .then(data => {
            document.querySelectorAll('[data-stat]').forEach(element => {
                const stat = element.dataset.stat;
                if (data[stat] !== undefined) {
                    element.textContent = data[stat];
                }
            });
        })
        .catch(error => console.log('Failed to update user stats:', error));
}

// Keyboard shortcuts
// document.addEventListener('keydown', function(e) {
//     if (e.ctrlKey) {
//         switch(e.key) {
//             case 'n':
//                 e.preventDefault();
//                 if (document.querySelector('a[href="{{ route('notifications.create') }}"]')) {
//                     window.location.href = '{{ route('notifications.create') }}';
//                 }
//                 break;
//             case 'r':
//                 e.preventDefault();
//                 refreshNotifications({ target: { closest: () => ({ innerHTML: '<i class="bi bi-arrow-clockwise"></i> <span class="nav-text">รีเฟรช</span>' }) } });
//                 break;
//             case 'a':
//                 e.preventDefault();
//                 markAllAsRead({ preventDefault: () => {} });
//                 break;
//         }
//     }
// });

// Auto-update user stats every 2 minutes
setInterval(updateUserStats, 120000);

// Export user functions for global use
window.UserFunctions = {
    testNotificationSettings,
    markAllAsRead,
    refreshNotifications,
    quickJoinGroup,
    searchGroups,
    joinGroup,
    openHelpModal,
    openFeedbackModal,
    openShortcutsModal,
    updateUserStats
};
</script>