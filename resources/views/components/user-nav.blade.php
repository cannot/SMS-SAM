@php
    // ดึง notification ที่ user คนนี้เป็นผู้รับ
    $user = Auth::user();
    $receivedNotifications = \App\Models\Notification::where(function($query) use ($user) {
        $query->whereJsonContains('recipients', $user->email)
              ->orWhereHas('group.users', function($subQuery) use ($user) {
                  $subQuery->where('user_id', $user->id);
              });
    })
    ->where('status', 'sent')
    ->count();
    
    $unreadNotifications = \App\Models\Notification::where(function($query) use ($user) {
        $query->whereJsonContains('recipients', $user->email)
              ->orWhereHas('group.users', function($subQuery) use ($user) {
                  $subQuery->where('user_id', $user->id);
              });
    })
    ->where('status', 'sent')
    ->whereDoesntHave('logs', function($query) use ($user) {
        $query->where('recipient_email', $user->email)
              ->where('status', 'read');
    })
    ->count();
@endphp

<!-- Received Notifications Section -->
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('notifications.received*') ? 'active' : '' }}"
        href="{{ route('notifications.received') }}">
        <i class="bi bi-envelope"></i>
        <span class="nav-text">การแจ้งเตือนที่ได้รับ</span>
        @if($unreadNotifications > 0)
        <span class="badge bg-danger ms-auto">{{ $unreadNotifications }}</span>
        @endif
        <div class="nav-tooltip">การแจ้งเตือนที่ได้รับ ({{ $receivedNotifications }} ทั้งหมด)</div>
    </a>
</li>

<!-- Notification Quick Access for Users -->
<li class="nav-item">
    <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#userNotificationActions" 
       aria-expanded="false" aria-controls="userNotificationActions">
       <i class="bi bi-bell"></i>
        <span class="nav-text">หมวดหมู่การแจ้งเตือน</span>
        <i class="fas fa-chevron-down ms-auto nav-text"></i>
        <div class="nav-tooltip">หมวดหมู่การแจ้งเตือน</div>
    </a>
    <div class="collapse" id="userNotificationActions">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('notifications.received', ['status' => 'unread']) }}">
                    <i class="bi bi-envelope"></i>
                    <span class="nav-text">ยังไม่ได้อ่าน</span>
                    @if($unreadNotifications > 0)
                    <span class="badge bg-danger ms-auto">{{ $unreadNotifications }}</span>
                    @endif
                    <div class="nav-tooltip">ยังไม่ได้อ่าน ({{ $unreadNotifications }})</div>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('notifications.received', ['status' => 'read']) }}">
                    <i class="bi bi-envelope-open"></i>
                    <span class="nav-text">อ่านแล้ว</span>
                    @php $readCount = $receivedNotifications - $unreadNotifications; @endphp
                    @if($readCount > 0)
                    <span class="badge bg-success ms-auto">{{ $readCount }}</span>
                    @endif
                    <div class="nav-tooltip">อ่านแล้ว ({{ $readCount }})</div>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('notifications.received', ['priority' => 'urgent']) }}">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span class="nav-text">เร่งด่วน</span>
                    @php 
                        $urgentCount = \App\Models\Notification::where(function($query) use ($user) {
                            $query->whereJsonContains('recipients', $user->email)
                                  ->orWhereHas('group.users', function($subQuery) use ($user) {
                                      $subQuery->where('user_id', $user->id);
                                  });
                        })
                        ->where('priority', 'urgent')
                        ->where('status', 'sent')
                        ->count(); 
                    @endphp
                    @if($urgentCount > 0)
                    <span class="badge bg-warning ms-auto">{{ $urgentCount }}</span>
                    @endif
                    <div class="nav-tooltip">เร่งด่วน ({{ $urgentCount }})</div>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link py-1" href="{{ route('notifications.received', ['date_from' => today()->format('Y-m-d')]) }}">
                    <i class="bi bi-calendar-day"></i>
                    <span class="nav-text">วันนี้</span>
                    @php 
                        $todayCount = \App\Models\Notification::where(function($query) use ($user) {
                            $query->whereJsonContains('recipients', $user->email)
                                  ->orWhereHas('group.users', function($subQuery) use ($user) {
                                      $subQuery->where('user_id', $user->id);
                                  });
                        })
                        ->whereDate('created_at', today())
                        ->where('status', 'sent')
                        ->count(); 
                    @endphp
                    @if($todayCount > 0)
                    <span class="badge bg-info ms-auto">{{ $todayCount }}</span>
                    @endif
                    <div class="nav-tooltip">วันนี้ ({{ $todayCount }})</div>
                </a>
            </li>
        </ul>
    </div>
</li>

<!-- Users Section -->
@canany(['view-users', 'view-user-profiles'])

<!-- User Quick Actions -->
<li class="nav-item">
    <a class="nav-link" href="#" data-bs-toggle="collapse" data-bs-target="#userActions" 
       aria-expanded="false" aria-controls="userActions">
       <i class="bi bi-person-lines-fill"></i>
        <span class="nav-text">จัดการโปรไฟล์</span>
        <i class="fas fa-chevron-down ms-auto nav-text"></i>
        <div class="nav-tooltip">จัดการโปรไฟล์</div>
    </a>
    <div class="collapse" id="userActions">
        <ul class="nav flex-column ms-3">
            <li class="nav-item">
                <a class="nav-link py-1 {{ request()->routeIs('users.show', Auth::user()) ? 'active' : '' }}" 
                   href="{{ route('users.show', Auth::user()) }}">
                    <i class="bi bi-person"></i>
                    <span class="nav-text">โปรไฟล์ของฉัน</span>
                    <div class="nav-tooltip">โปรไฟล์ของฉัน</div>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link py-1 {{ request()->routeIs('users.preferences*') ? 'active' : '' }}" 
                   href="{{ route('users.preferences') }}">
                    <i class="bi bi-gear"></i>
                    <span class="nav-text">ตั้งค่าการแจ้งเตือน</span>
                    <div class="nav-tooltip">ตั้งค่าการแจ้งเตือน</div>
                </a>
            </li>
            
            {{-- @can('manage-user-permissions')
            <li class="nav-item">
                <a class="nav-link py-1 {{ request()->routeIs('users.permissions.show', Auth::user()) ? 'active' : '' }}" 
                   href="{{ route('users.permissions.show', Auth::user()) }}">
                    <i class="fas fa-key"></i>
                    <span class="nav-text">สิทธิ์ของฉัน</span>
                    <div class="nav-tooltip">สิทธิ์ของฉัน</div>
                </a>
            </li>
            @endcan --}}
            
            <li class="nav-item">
                <a class="nav-link py-1" href="#" onclick="testNotificationSettings(event)">
                    <i class="bi bi-bell"></i>
                    <span class="nav-text">ทดสอบการแจ้งเตือน</span>
                    <div class="nav-tooltip">ทดสอบการแจ้งเตือน</div>
                </a>
            </li>
        </ul>
    </div>
</li>
@endcanany

<!-- Reports Section -->
{{-- @canany(['view-reports', 'view-personal-reports'])
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('reports*') ? 'active' : '' }}"
        href="{{ route('reports.index') }}">
        <i class="bi bi-graph-up"></i>
        <span class="nav-text">รายงาน</span>
        <div class="nav-tooltip">รายงาน</div>
    </a>
</li>
@endcanany --}}

<!-- Quick Stats for User -->
{{-- <li class="nav-item">
    <h6 class="sidebar-heading">
        <i class="fas fa-chart-pie"></i> 
        <span class="nav-text">สถิติส่วนตัว</span>
    </h6>
</li> --}}

<!-- ได้รับการแจ้งเตือน -->
{{-- <li class="nav-item">
    <a class="nav-link" href="#received-notifications">
        <i class="bi bi-envelope"></i>
        <span class="nav-text">ได้รับการแจ้งเตือน</span>
        <span class="badge bg-primary" data-stat="user_received_notifications">
            {{ Auth::user()->notifications->count() }}
        </span>
        <div class="nav-tooltip">ได้รับการแจ้งเตือน ({{ Auth::user()->notifications->count() }})</div>
    </a>
</li>

@can('create-notifications')
<!-- ส่งการแจ้งเตือน -->
<li class="nav-item">
    <a class="nav-link" href="#sent-notifications">
        <i class="bi bi-send"></i>
        <span class="nav-text">ส่งการแจ้งเตือน</span>
        <span class="badge bg-success" data-stat="user_sent_notifications">
            {{ Auth::user()->createdNotifications->count() }}
        </span>
        <div class="nav-tooltip">ส่งการแจ้งเตือน ({{ Auth::user()->createdNotifications->count() }})</div>
    </a>
</li>
@endcan --}}

<!-- สถิติเก่า (ซ่อนใน collapsed) -->
{{-- <li class="nav-item">
    <div class="stats-card">
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

        <div class="d-flex justify-content-between align-items-center">
            <small class="text-light">อ่านแล้ว</small>
            <span class="badge bg-info" data-stat="user_read_notifications">
                {{ Auth::user()->readNotifications->count() }}
            </span>
        </div>
    </div>
</li> --}}

<!-- Quick Actions -->
{{-- <li class="nav-item">
    <h6 class="sidebar-heading">
        <i class="fas fa-bolt"></i> 
        <span class="nav-text">การดำเนินการด่วน</span>
    </h6>
</li>

<li class="nav-item">
    <a class="nav-link" href="#" onclick="refreshNotifications(event)">
        <i class="bi bi-arrow-clockwise"></i>
        <span class="nav-text">รีเฟรชการแจ้งเตือน</span>
        <div class="nav-tooltip">รีเฟรชการแจ้งเตือน</div>
    </a>
</li>

<!-- ติดต่อฝ่ายสนับสนุน -->
<li class="nav-item">
    <a class="nav-link" href="#" onclick="contactSupport()">
        <i class="bi bi-headset"></i>
        <span class="nav-text">ติดต่อฝ่ายสนับสนุน</span>
        <div class="nav-tooltip">ติดต่อฝ่ายสนับสนุน</div>
    </a>
</li> --}}

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
            
            // Animate badges
            document.querySelectorAll('.badge').forEach(badge => {
                badge.classList.add('animate');
                setTimeout(() => badge.classList.remove('animate'), 2000);
            });
        } else {
            window.AppFunctions.showNotification('เกิดข้อผิดพลาด: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Test notification error:', error);
        window.AppFunctions.showNotification('เกิดข้อผิดพลาดในการส่งทดสอบ', 'error');
    }
});

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

function contactSupport() {
    window.AppFunctions.showNotification('กำลังเปิดระบบติดต่อฝ่ายสนับสนุน...', 'info');
    // Implement support contact system
}

function openDocumentation() {
    window.open('/docs', '_blank');
}

// Update user statistics
function updateUserStats() {
    fetch('/api/user/stats')
        .then(response => response.json())
        .then(data => {
            document.querySelectorAll('[data-stat]').forEach(element => {
                const stat = element.dataset.stat;
                if (data[stat] !== undefined) {
                    element.textContent = data[stat];
                    
                    // Update tooltips
                    const navItem = element.closest('.nav-item');
                    if (navItem) {
                        const tooltip = navItem.querySelector('.nav-tooltip');
                        if (tooltip) {
                            const baseText = tooltip.textContent.split(' (')[0];
                            tooltip.textContent = `${baseText} (${data[stat]})`;
                        }
                    }
                }
            });
        })
        .catch(error => console.log('Failed to update user stats:', error));
}

// Auto-update user stats every 2 minutes
setInterval(updateUserStats, 120000);

// Export user functions for global use
window.UserFunctions = {
    testNotificationSettings,
    refreshNotifications,
    updateUserStats
};
</script>