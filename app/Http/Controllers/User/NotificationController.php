<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Display notifications received by the authenticated user
     */
    public function received(Request $request)
    {
        $user = Auth::user();
        
        // Get notifications sent to this user through logs
        $query = NotificationLog::with(['notification.template', 'notification.creator'])
                                ->where(function($q) use ($user) {
                                    $q->where('user_id', $user->id)
                                      ->orWhere('recipient_email', $user->email);
                                })
                                ->notArchived(); // Only show non-archived notifications

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by channel
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->whereHas('notification', function($q) use ($request) {
                $q->where('priority', $request->priority);
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in notification content
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->search($search)
                  ->orWhereHas('notification', function($nq) use ($search) {
                      $nq->where('subject', 'LIKE', "%{$search}%")
                        ->orWhere('body_html', 'LIKE', "%{$search}%")
                        ->orWhere('body_text', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Filter by read/unread status
        if ($request->filled('read_status')) {
            if ($request->read_status === 'unread') {
                $query->unread();
            } elseif ($request->read_status === 'read') {
                $query->read();
            }
        }

        // Sort by newest first
        $notifications = $query->orderBy('created_at', 'desc')
                              ->paginate(20)
                              ->appends($request->all());

        // Mark notifications as read when viewed (optional)
        if ($request->get('mark_as_read') === 'true') {
            NotificationLog::forUser($user->id)
                           ->unread()
                           ->update(['read_at' => now()]);
        }

        // Statistics for user dashboard
        $stats = [
            'total' => NotificationLog::forUser($user->id)->notArchived()->count(),
            'unread' => NotificationLog::forUser($user->id)->unread()->notArchived()->count(),
            'today' => NotificationLog::forUser($user->id)->whereDate('created_at', today())->notArchived()->count(),
            'this_week' => NotificationLog::forUser($user->id)
                                         ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                                         ->notArchived()
                                         ->count(),
        ];

        // Filter options
        $statuses = NotificationLog::forUser($user->id)->distinct()->pluck('status');
        $channels = NotificationLog::forUser($user->id)->distinct()->pluck('channel');
        $priorities = ['low', 'normal', 'high', 'urgent'];

        return view('users.notifications.received', compact(
            'notifications', 'stats', 'statuses', 'channels', 'priorities'
        ));
    }

    /**
     * Display a specific notification for the user
     */
    public function show(NotificationLog $notificationLog)
    {
        $user = Auth::user();

        // Ensure the notification belongs to the authenticated user
        if ($notificationLog->user_id !== $user->id && $notificationLog->recipient_email !== $user->email) {
            abort(403, 'Unauthorized access to notification.');
        }

        // Check if notification is archived
        if ($notificationLog->isArchived()) {
            abort(404, 'Notification not found or has been archived.');
        }

        // Load related data
        $notificationLog->load(['notification.template', 'notification.creator']);

        // Mark as read if not already read
        if (!$notificationLog->isRead()) {
            $notificationLog->markAsRead();
        }

        // Get related notifications from the same campaign (if any)
        $relatedNotifications = NotificationLog::forUser($user->id)
                                             ->where('notification_id', $notificationLog->notification_id)
                                             ->where('id', '!=', $notificationLog->id)
                                             ->notArchived()
                                             ->with('notification')
                                             ->orderBy('created_at', 'desc')
                                             ->limit(5)
                                             ->get();

        return view('users.notifications.show', compact('notificationLog', 'relatedNotifications'));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(NotificationLog $notificationLog)
    {
        $user = Auth::user();

        // Ensure the notification belongs to the authenticated user
        if ($notificationLog->user_id !== $user->id && $notificationLog->recipient_email !== $user->email) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notificationLog->markAsRead();

        return response()->json(['success' => true, 'message' => 'Notification marked as read']);
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread(NotificationLog $notificationLog)
    {
        $user = Auth::user();

        // Ensure the notification belongs to the authenticated user
        if ($notificationLog->user_id !== $user->id && $notificationLog->recipient_email !== $user->email) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notificationLog->markAsUnread();

        return response()->json(['success' => true, 'message' => 'Notification marked as unread']);
    }

    /**
     * Mark all notifications as read for the user
     */
    public function markAllAsRead()
    {
        $user = Auth::user();

        NotificationLog::forUser($user->id)
                       ->unread()
                       ->notArchived()
                       ->update(['read_at' => now()]);

        return response()->json(['success' => true, 'message' => 'All notifications marked as read']);
    }

    /**
     * Get unread notification count for the user
     */
    public function unreadCount()
    {
        $user = Auth::user();
        
        $count = NotificationLog::forUser($user->id)
                                ->unread()
                                ->notArchived()
                                ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Archive a notification (soft delete)
     */
    public function delete(NotificationLog $notificationLog)
    {
        $user = Auth::user();

        // Ensure the notification belongs to the authenticated user
        if ($notificationLog->user_id !== $user->id && $notificationLog->recipient_email !== $user->email) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notificationLog->archive();

        return response()->json(['success' => true, 'message' => 'Notification archived']);
    }

    /**
     * Restore archived notification
     */
    public function restore(NotificationLog $notificationLog)
    {
        $user = Auth::user();

        // Ensure the notification belongs to the authenticated user
        if ($notificationLog->user_id !== $user->id && $notificationLog->recipient_email !== $user->email) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notificationLog->restore();

        return response()->json(['success' => true, 'message' => 'Notification restored']);
    }

    /**
     * Get archived notifications
     */
    public function archived(Request $request)
    {
        $user = Auth::user();

        $query = NotificationLog::with(['notification.template', 'notification.creator'])
                                ->forUser($user->id)
                                ->archived();

        // Apply same filters as received method
        if ($request->filled('search')) {
            $search = $request->search;
            $query->search($search);
        }

        $notifications = $query->orderBy('archived_at', 'desc')
                              ->paginate(20)
                              ->appends($request->all());

        return view('users.notifications.archived', compact('notifications'));
    }

    /**
     * Export user's notifications
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $format = $request->get('format', 'pdf'); // pdf, csv, excel

        $notifications = NotificationLog::with(['notification.template', 'notification.creator'])
                                       ->forUser($user->id)
                                       ->notArchived()
                                       ->orderBy('created_at', 'desc')
                                       ->get();

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($notifications);
            case 'excel':
                return $this->exportToExcel($notifications);
            case 'pdf':
            default:
                return $this->exportToPdf($notifications);
        }
    }

    /**
     * Get notification preferences for the user
     */
    public function preferences()
    {
        $user = Auth::user();
        $preferences = UserPreference::where('user_id', $user->id)->first();

        if (!$preferences) {
            $preferences = UserPreference::create([
                'user_id' => $user->id,
                'preferences' => [
                    'email_enabled' => true,
                    'teams_enabled' => true,
                    'priority_filter' => ['normal', 'high', 'urgent'],
                    'quiet_hours' => [
                        'enabled' => false,
                        'start' => '22:00',
                        'end' => '08:00'
                    ]
                ]
            ]);
        }

        return response()->json($preferences);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email_enabled' => 'boolean',
            'teams_enabled' => 'boolean',
            'priority_filter' => 'array',
            'quiet_hours.enabled' => 'boolean',
            'quiet_hours.start' => 'required_if:quiet_hours.enabled,true|date_format:H:i',
            'quiet_hours.end' => 'required_if:quiet_hours.enabled,true|date_format:H:i',
        ]);

        $preferences = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            ['preferences' => $request->all()]
        );

        return response()->json(['success' => true, 'preferences' => $preferences]);
    }

    /**
     * Report an issue with a notification
     */
    public function reportIssue(Request $request)
    {
        $request->validate([
            'notification_log_id' => 'required|exists:notification_logs,id',
            'issue_type' => 'required|string',
            'description' => 'required|string|max:1000'
        ]);

        $user = Auth::user();
        $notificationLog = NotificationLog::findOrFail($request->notification_log_id);

        // Ensure the notification belongs to the user
        if ($notificationLog->user_id !== $user->id && $notificationLog->recipient_email !== $user->email) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Log the issue (you can create a separate IssueReport model if needed)
        \Log::info('Notification issue reported', [
            'user_id' => $user->id,
            'notification_log_id' => $request->notification_log_id,
            'issue_type' => $request->issue_type,
            'description' => $request->description,
            'notification_title' => $notificationLog->notification->subject ?? 'No title'
        ]);

        // You could also send an email to admins here
        // Mail::to(config('app.admin_email'))->send(new NotificationIssueReported(...));

        return response()->json(['success' => true, 'message' => 'Issue reported successfully']);
    }

    /**
     * Bulk operations on notifications
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:mark_read,mark_unread,archive,restore',
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notification_logs,id'
        ]);

        $user = Auth::user();
        $notifications = NotificationLog::whereIn('id', $request->notification_ids)
                                       ->where(function($q) use ($user) {
                                           $q->where('user_id', $user->id)
                                             ->orWhere('recipient_email', $user->email);
                                       })
                                       ->get();

        $count = 0;
        foreach ($notifications as $notification) {
            switch ($request->action) {
                case 'mark_read':
                    $notification->markAsRead();
                    $count++;
                    break;
                case 'mark_unread':
                    $notification->markAsUnread();
                    $count++;
                    break;
                case 'archive':
                    $notification->archive();
                    $count++;
                    break;
                case 'restore':
                    $notification->restore();
                    $count++;
                    break;
            }
        }

        $actionText = str_replace('_', ' ', $request->action);
        return response()->json([
            'success' => true, 
            'message' => "Successfully {$actionText} {$count} notifications"
        ]);
    }

    /**
     * Export notifications to CSV
     */
    private function exportToCsv($notifications)
    {
        $filename = 'notifications_' . now()->format('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($notifications) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Date', 'Title', 'Content', 'Channel', 'Status', 'Priority', 
                'Sender', 'Read At', 'Delivered At', 'Delivery Time'
            ]);

            foreach ($notifications as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->notification->subject ?? '',
                    strip_tags($log->notification->body_text ?? $log->notification->body_html ?? ''),
                    $log->channel,
                    $log->status,
                    $log->notification->priority ?? 'normal',
                    $log->notification->creator->name ?? 'System',
                    $log->read_at ? $log->read_at->format('Y-m-d H:i:s') : '',
                    $log->delivered_at ? $log->delivered_at->format('Y-m-d H:i:s') : '',
                    $log->delivery_time ? $log->delivery_time . 's' : ''
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export notifications to PDF
     */
    private function exportToPdf($notifications)
    {
        // Implementation would use a PDF library like DomPDF or TCPDF
        // For now, return a placeholder response
        return response()->json(['message' => 'PDF export feature coming soon']);
    }

    /**
     * Export notifications to Excel
     */
    private function exportToExcel($notifications)
    {
        // Implementation would use Laravel Excel package
        // For now, return a placeholder response
        return response()->json(['message' => 'Excel export feature coming soon']);
    }
}