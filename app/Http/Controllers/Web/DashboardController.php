<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\NotificationGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        return view('dashboard.index', compact('user'));
    }

    /**
     * Get dashboard data for AJAX calls
     */
    public function getDashboardData(Request $request)
    {
        try {
            $stats = [
                'total' => Notification::count(),
                'sent' => Notification::where('status', 'sent')->count(),
                'pending' => Notification::whereIn('status', ['queued', 'processing', 'scheduled'])->count(),
                'failed' => Notification::where('status', 'failed')->count(),
                'today' => Notification::whereDate('created_at', today())->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard Data Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'ไม่สามารถโหลดข้อมูลได้',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific widget data
     */
    public function getWidget(Request $request, $widget)
    {
        switch ($widget) {
            case 'recent-notifications':
                return $this->getRecentNotificationsWidget();
            
            case 'system-status':
                return $this->getSystemStatusWidget();
            
            case 'user-activity':
                return $this->getUserActivityWidget();
            
            default:
                return response()->json(['error' => 'Widget not found'], 404);
        }
    }

    /**
     * Recent Notifications Widget
     */
    private function getRecentNotificationsWidget()
    {
        try {
            $notifications = Notification::with(['template', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $html = '';
            
            if ($notifications->isEmpty()) {
                $html = '<div class="text-center text-muted py-3">
                    <i class="fas fa-bell fa-2x mb-2 d-block"></i>
                    ไม่มีการแจ้งเตือน
                </div>';
            } else {
                foreach ($notifications as $notification) {
                    $statusClass = $this->getStatusClass($notification->status);
                    $statusText = $this->getStatusText($notification->status);
                    
                    // ปรับปรุงการแสดงผล
                    $html .= '<div class="d-flex align-items-center py-2 border-bottom">
                        <div class="flex-shrink-0 me-3">
                            <span class="badge ' . $statusClass . '">' . $statusText . '</span>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">' . e($notification->subject ?? 'ไม่มีหัวข้อ') . '</h6>
                            <small class="text-muted">
                                ' . ($notification->template ? e($notification->template->name) : 'ไม่ระบุเทมเพลต') . ' • 
                                ' . $notification->created_at->format('d/m/Y H:i') . '
                            </small>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="' . route('admin.notifications.show', $notification->uuid) . '" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>';
                }
            }

            return response($html);
        } catch (\Exception $e) {
            \Log::error('Dashboard Recent Notifications Widget Error: ' . $e->getMessage());
            return response('<div class="text-center text-danger py-3">
                <i class="fas fa-exclamation-triangle"></i> เกิดข้อผิดพลาด: ' . $e->getMessage() . '
            </div>');
        }
    }

    /**
     * System Status Widget
     */
    private function getSystemStatusWidget()
    {
        // Check database connection
        try {
            DB::connection()->getPdo();
            $dbStatus = 'online';
        } catch (\Exception $e) {
            $dbStatus = 'offline';
        }

        // Check queue status (simplified)
        $queueJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();

        // Check recent activity
        $recentActivity = Notification::where('created_at', '>=', now()->subHours(24))->count();

        $html = '<div class="row text-center">
            <div class="col-6 mb-3">
                <div class="p-3 border rounded">
                    <div class="mb-2">
                        <i class="fas fa-database fa-2x ' . ($dbStatus === 'online' ? 'text-success' : 'text-danger') . '"></i>
                    </div>
                    <h6 class="mb-1">ฐานข้อมูล</h6>
                    <span class="badge ' . ($dbStatus === 'online' ? 'bg-success' : 'bg-danger') . '">
                        ' . ($dbStatus === 'online' ? 'ออนไลน์' : 'ออฟไลน์') . '
                    </span>
                </div>
            </div>
            <div class="col-6 mb-3">
                <div class="p-3 border rounded">
                    <div class="mb-2">
                        <i class="fas fa-tasks fa-2x ' . ($queueJobs > 0 ? 'text-warning' : 'text-success') . '"></i>
                    </div>
                    <h6 class="mb-1">คิวงาน</h6>
                    <span class="badge ' . ($queueJobs > 0 ? 'bg-warning' : 'bg-success') . '">
                        ' . number_format($queueJobs) . ' งาน
                    </span>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 border rounded">
                    <div class="mb-2">
                        <i class="fas fa-exclamation-triangle fa-2x ' . ($failedJobs > 0 ? 'text-danger' : 'text-success') . '"></i>
                    </div>
                    <h6 class="mb-1">งานล้มเหลว</h6>
                    <span class="badge ' . ($failedJobs > 0 ? 'bg-danger' : 'bg-success') . '">
                        ' . number_format($failedJobs) . ' งาน
                    </span>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 border rounded">
                    <div class="mb-2">
                        <i class="fas fa-chart-line fa-2x text-info"></i>
                    </div>
                    <h6 class="mb-1">กิจกรรม 24 ชม.</h6>
                    <span class="badge bg-info">
                        ' . number_format($recentActivity) . ' รายการ
                    </span>
                </div>
            </div>
        </div>';

        return response($html);
    }

    /**
     * User Activity Widget
     */
    private function getUserActivityWidget()
    {
        if (!Auth::user()->can('view-activity-logs')) {
            return response('<div class="text-center text-muted">ไม่มีสิทธิ์เข้าถึง</div>');
        }

        // Get user's recent activities from activity_log table
        $activities = DB::table('activity_log')
            ->where('causer_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $html = '';
        
        if ($activities->isEmpty()) {
            $html = '<div class="text-center text-muted py-3">ไม่มีกิจกรรมล่าสุด</div>';
        } else {
            foreach ($activities as $activity) {
                $iconClass = $this->getActivityIcon($activity->description);
                
                $html .= '<div class="d-flex align-items-center py-2 border-bottom">
                    <div class="flex-shrink-0 me-3">
                        <i class="' . $iconClass . ' text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">' . e($activity->description) . '</h6>
                        <small class="text-muted">' . \Carbon\Carbon::parse($activity->created_at)->format('d/m/Y H:i') . '</small>
                    </div>
                </div>';
            }
            
            $html .= '<div class="text-center mt-3">
                <a href="' . route('activity-logs.index') . '" class="btn btn-sm btn-outline-primary">
                    ดูกิจกรรมทั้งหมด
                </a>
            </div>';
        }

        return response($html);
    }

    /**
     * Get status class for notifications
     */
    private function getStatusClass($status)
    {
        $classes = [
            'draft' => 'bg-secondary',
            'queued' => 'bg-info',
            'scheduled' => 'bg-warning',
            'processing' => 'bg-primary',
            'sent' => 'bg-success',
            'failed' => 'bg-danger',
            'cancelled' => 'bg-dark'
        ];

        return $classes[$status] ?? 'bg-secondary';
    }

    /**
     * Get status text for notifications
     */
    private function getStatusText($status)
    {
        $texts = [
            'draft' => 'ร่าง',
            'queued' => 'รอส่ง',
            'scheduled' => 'นัดส่ง',
            'processing' => 'กำลังส่ง',
            'sent' => 'ส่งแล้ว',
            'failed' => 'ล้มเหลว',
            'cancelled' => 'ยกเลิก'
        ];

        return $texts[$status] ?? 'ไม่ทราบ';
    }

    /**
     * Get activity icon
     */
    private function getActivityIcon($description)
    {
        if (str_contains($description, 'created')) {
            return 'fas fa-plus-circle';
        } elseif (str_contains($description, 'updated')) {
            return 'fas fa-edit';
        } elseif (str_contains($description, 'deleted')) {
            return 'fas fa-trash';
        } elseif (str_contains($description, 'login')) {
            return 'fas fa-sign-in-alt';
        } else {
            return 'fas fa-info-circle';
        }
    }
}