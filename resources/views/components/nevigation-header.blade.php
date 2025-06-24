<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
            <img src="{{ asset('images/logo-p.png') }}" alt="Logo" height="40" class="me-2"
                style="border-radius: 4px;"
                onerror="this.style.display='none';">
            <span class="brand-text fw-bold">{{ config('app.name', 'Smart Notification System') }}</span>
        </a>

        <!-- User Menu -->
        <div class="navbar-nav ms-auto">
            <!-- Notifications Bell -->
            <div class="nav-item dropdown me-2">
                <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-bell fs-5"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ Auth::user()->unreadNotifications->count() }}
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end" style="width: 300px;">
                    <h6 class="dropdown-header">การแจ้งเตือนล่าสุด</h6>
                    <div class="dropdown-divider"></div>
                    @forelse(Auth::user()->notifications->take(5) as $notification)
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-info-circle text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-2">
                                    <div class="fw-bold">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="dropdown-item-text text-center text-muted">
                            ไม่มีการแจ้งเตือน
                        </div>
                    @endforelse
                    <div class="dropdown-divider"></div>
                    {{-- <a class="dropdown-item text-center" href="{{ route('notifications.index') }}">
                        ดูการแจ้งเตือนทั้งหมด
                    </a> --}}
                </div>
            </div>

            <!-- User Profile Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                    <div class="user-avatar me-2">
                        {{ Auth::user()->initials ?? substr(Auth::user()->username ?? 'U', 0, 2) }}
                    </div>
                    <span>{{ Auth::user()->display_name ?? Auth::user()->username ?? 'User' }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <div class="dropdown-header">
                            <div class="fw-bold">{{ Auth::user()->display_name ?? Auth::user()->username }}</div>
                            <small class="text-muted">{{ Auth::user()->email }}</small>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="{{ route('users.show', Auth::user()) }}">
                            <i class="bi bi-person me-2"></i>โปรไฟล์
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('users.preferences') }}">
                            <i class="bi bi-gear me-2"></i>ตั้งค่า
                        </a>
                    </li>
                    {{-- @can('manage-user-permissions')
                    <li>
                        <a class="dropdown-item" href="{{ route('users.permissions.show', Auth::user()) }}">
                            <i class="fas fa-key me-2"></i>สิทธิ์ของฉัน
                        </a>
                    </li>
                    @endcan --}}
                    <li><hr class="dropdown-divider"></li>
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