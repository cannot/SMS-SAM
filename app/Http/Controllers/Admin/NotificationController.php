<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\NotificationGroup;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\LdapService;
use App\Jobs\SendEmailNotification;
use App\Jobs\SendTeamsNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class NotificationController extends Controller
{
    protected $notificationService;
    protected $ldapService;

    public function __construct(NotificationService $notificationService, LdapService $ldapService)
    {
        $this->notificationService = $notificationService;
        $this->ldapService = $ldapService;
    }

    /**
     * Display a listing of notifications with advanced filters
     */
    public function index(Request $request)
    {
        $query = Notification::with(['template', 'group', 'creator', 'logs']);

        // Advanced filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        if ($request->filled('group_id')) {
            $query->where('notification_group_id', $request->group_id);
        }

        if ($request->filled('channel')) {
            $query->whereJsonContains('channels', $request->channel);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('scheduled_from')) {
            $query->whereDate('scheduled_at', '>=', $request->scheduled_from);
        }

        if ($request->filled('scheduled_to')) {
            $query->whereDate('scheduled_at', '<=', $request->scheduled_to);
        }

        // Search in subject and body
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('subject', 'LIKE', "%{$search}%")
                  ->orWhere('body_text', 'LIKE', "%{$search}%")
                  ->orWhere('body_html', 'LIKE', "%{$search}%")
                  ->orWhere('uuid', 'LIKE', "%{$search}%")
                  ->orWhereHas('template', function($tq) use ($search) {
                      $tq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $notifications = $query->paginate(20)->appends($request->all());

        // Get filter options
        $templates = NotificationTemplate::orderBy('name')->get();
        $groups = NotificationGroup::orderBy('name')->get();
        $users = User::orderBy('display_name')->get();
        $statuses = ['draft', 'queued', 'scheduled', 'processing', 'sent', 'failed', 'cancelled'];
        $priorities = ['low', 'medium', 'normal', 'high', 'urgent'];
        $channels = ['teams', 'email'];

        // Statistics for dashboard cards
        $stats = [
            'total' => Notification::count(),
            'sent' => Notification::where('status', 'sent')->count(),
            'pending' => Notification::whereIn('status', ['queued', 'processing'])->count(),
            'failed' => Notification::where('status', 'failed')->count(),
            'today' => Notification::whereDate('created_at', today())->count(),
        ];

        return view('admin.notifications.index', compact(
            'notifications', 'templates', 'groups', 'users', 'statuses', 
            'priorities', 'channels', 'stats'
        ));
    }

    /**
     * Show the form for creating a new notification
     */
    public function create(Request $request)
    {
        $templates = NotificationTemplate::orderBy('name')->get();
        $groups = NotificationGroup::with('users')
                               ->withCount('users')
                               ->orderBy('name')
                               ->get();

        $users = User::where('is_active', true)->take(10)->get();
        $channels = ['teams', 'email'];
        $priorities = ['low', 'medium', 'normal', 'high', 'urgent'];

        // Initialize selectedTemplate as null
        $selectedTemplate = null;
        
        // Check if template_id is provided in the request
        if ($request->filled('template_id')) {
            $selectedTemplate = NotificationTemplate::find($request->template_id);
        }

        $templateVariables = [
            'recipient_name' => '{{recipient_name}}',
            'recipient_email' => '{{recipient_email}}',
            'notification_title' => '{{notification_title}}',
            'content' => '{{content}}',
            'additional_info' => '{{additional_info}}',
            'created_by_name' => '{{created_by_name}}',
            'notification_created_at' => '{{notification_created_at}}'
        ];

        return view('admin.notifications.create', compact(
            'templates', 'groups', 'users', 'channels', 'priorities', 'templateVariables', 'selectedTemplate'
        ));
    }

    /**
     * Store a newly created notification
     */
    public function store(Request $request)
    {
        $request->validate([
            'template_id' => 'nullable|exists:notification_templates,id',
            'subject' => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:teams,email',
            'priority' => 'required|in:low,medium,normal,high,urgent',
            'recipient_type' => 'required|in:manual,groups,all_users',
            'recipients' => 'required_if:recipient_type,manual',
            'recipient_groups' => 'nullable|array',
            'recipient_groups.*' => 'exists:notification_groups,id',
            'scheduled_at' => 'nullable|date|after:now',
            'variables' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            // Prepare recipients
            $recipients = $this->prepareRecipients($request);

            // Generate UUID
            $uuid = \Illuminate\Support\Str::uuid();

            $notification = Notification::create([
                'uuid' => $uuid,
                'template_id' => $request->template_id,
                'subject' => $request->subject,
                'body_html' => $request->body_html,
                'body_text' => $request->body_text,
                'channels' => $request->channels,
                'priority' => $request->priority,
                'recipients' => $recipients['recipients'],
                'recipient_groups' => $recipients['recipient_groups'],
                'variables' => $request->variables,
                'scheduled_at' => $request->scheduled_at,
                'status' => $request->scheduled_at ? 'scheduled' : 'queued',
                'created_by' => auth()->id()
            ]);

            // If not scheduled, queue immediately
            if (!$request->scheduled_at) {
                $this->notificationService->processNotification($notification);
            }

            DB::commit();

            return redirect()->route('admin.notifications.show', $notification->uuid)
               ->with('success', 'Notification created successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Notification creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);
            return back()->withInput()->withErrors(['error' => 'Failed to create notification: ' . $e->getMessage()]);
        }
    }

    /**
     * Prepare recipients based on type
     */
    private function prepareRecipients($request)
    {
        $recipients = [];
        $recipientGroups = [];

        switch ($request->recipient_type) {
            case 'manual':
                if ($request->recipients) {
                    if (is_array($request->recipients)) {
                        $recipients = $request->recipients;
                    } else {
                        // Parse textarea input
                        $recipients = array_filter(
                            array_map('trim', 
                                preg_split('/[,\n\r]+/', $request->recipients)
                            )
                        );
                    }
                }
                break;
                
            case 'groups':
                $recipientGroups = $request->recipient_groups ?? [];
                break;
                
            case 'all_users':
                $recipients = User::where('is_active', true)
                                ->pluck('email')
                                ->toArray();
                break;
        }

        return [
            'recipients' => $recipients,
            'recipient_groups' => $recipientGroups
        ];
    }

    /**
     * Display the specified notification
     */
    public function show($uuid)
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();
        $notification->load(['template', 'group', 'creator', 'logs']);

        // Update delivery counters first
        $notification->updateDeliveryCounters();
        
        // Refresh notification to get updated data
        $notification->refresh();

        // Render content with variables for display
        $renderedContent = $this->renderNotificationContent($notification);

        // Delivery statistics
        $deliveryStats = $notification->logs->groupBy('status')->map->count();
        
        // Channel statistics
        $channelStats = $notification->logs->groupBy('channel')->map->count();
        
        // Recent logs (last 50)
        $recentLogs = $notification->logs()
                                  ->orderBy('created_at', 'desc')
                                  ->limit(50)
                                  ->get();

        // Performance metrics
        $metrics = [
            'total_recipients' => $notification->logs->count(),
            'delivery_rate' => $notification->logs->count() > 0 
                ? round(($notification->logs->whereIn('status', ['sent', 'delivered'])->count() / $notification->logs->count()) * 100, 2)
                : 0,
            'avg_delivery_time' => $notification->logs->whereIn('status', ['sent', 'delivered'])
                ->filter(function($log) {
                    return $log->delivered_at || $log->sent_at;
                })
                ->avg(function($log) {
                    $endTime = $log->delivered_at ?? $log->sent_at;
                    return $endTime ? abs($endTime->diffInSeconds($log->created_at)) : 0;
                }),
            'failure_rate' => $notification->logs->count() > 0
                ? round(($notification->logs->where('status', 'failed')->count() / $notification->logs->count()) * 100, 2)
                : 0
        ];

        return view('admin.notifications.show', compact(
            'notification', 'deliveryStats', 'channelStats', 'recentLogs', 'metrics', 'renderedContent'
        ));
    }

    /**
     * Render notification content with variables replaced
     */
    private function renderNotificationContent($notification)
    {
        $variables = $notification->getTemplateVariables();
        
        // Get the base content
        $subject = $notification->subject;
        $bodyHtml = $notification->body_html;
        $bodyText = $notification->body_text;
        
        // Replace variables in content
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $bodyHtml = str_replace($placeholder, $value, $bodyHtml);
            $bodyText = str_replace($placeholder, $value, $bodyText);
        }
        
        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'variables' => $variables
        ];
    }

    /**
     * Resend failed notifications
     */
    public function resend($uuid)
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();
        
        try {
            // Check if notification can be resent
            if (!in_array($notification->status, ['failed', 'sent'])) {
                return back()->withErrors(['error' => 'Only failed or completed notifications can be resent.']);
            }

            // Get failed logs
            $failedLogs = $notification->logs()->where('status', 'failed')->get();
            
            if ($failedLogs->isEmpty()) {
                return back()->withErrors(['error' => 'No failed deliveries found to resend.']);
            }

            // Reset failed logs to pending for retry
            foreach ($failedLogs as $log) {
                $log->update([
                    'status' => 'pending',
                    'retry_count' => 0,
                    'error_message' => null,
                    'next_retry_at' => null
                ]);
            }

            // Update notification status
            $notification->update([
                'status' => 'processing',
                'failure_reason' => null
            ]);

            // Re-queue the notification
            $this->notificationService->queueNotification($notification);

            return back()->with('success', "Resending {$failedLogs->count()} failed notifications.");

        } catch (\Exception $e) {
            Log::error('Resend failed', [
                'notification_uuid' => $uuid,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => 'Failed to resend notifications: ' . $e->getMessage()]);
        }
    }

    /**
     * Resend specific log entry
     */
    public function resendLog($uuid, NotificationLog $log)
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();
        
        try {
            if ($log->notification_id !== $notification->id) {
                return back()->withErrors(['error' => 'Invalid notification log.']);
            }

            if ($log->status !== 'failed') {
                return back()->withErrors(['error' => 'Only failed notifications can be resent.']);
            }

            // Reset log to pending
            $log->update([
                'status' => 'pending',
                'retry_count' => 0,
                'error_message' => null,
                'next_retry_at' => null
            ]);

            // Queue single notification
            $delay = $this->notificationService->calculateDelay($notification->priority);
            $queueName = $this->notificationService->getQueueName($notification->priority);
            
            switch ($log->channel) {
                case 'email':
                    SendEmailNotification::dispatch($log)
                        ->delay($delay)
                        ->onQueue($queueName);
                    break;
                    
                case 'teams':
                    SendTeamsNotification::dispatch($log)
                        ->delay($delay)
                        ->onQueue($queueName);
                    break;
            }

            return back()->with('success', 'Notification queued for resending.');

        } catch (\Exception $e) {
            Log::error('Resend log failed', [
                'notification_uuid' => $uuid,
                'log_id' => $log->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['error' => 'Failed to resend notification: ' . $e->getMessage()]);
        }
    }

    /**
     * Cancel a scheduled notification
     */
    public function cancel($uuid)
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();
        
        if (!$notification->canBeCancelled()) {
            return back()->withErrors(['error' => 'This notification cannot be cancelled.']);
        }

        $notification->cancel();

        return back()->with('success', 'Notification cancelled successfully.');
    }

    /**
     * Get notification statistics for real-time updates
     */
    public function stats()
    {
        $stats = [
            'total' => Notification::count(),
            'draft' => Notification::where('status', 'draft')->count(),
            'scheduled' => Notification::where('status', 'scheduled')->count(),
            'processing' => Notification::whereIn('status', ['queued', 'processing'])->count(),
            'sent' => Notification::where('status', 'sent')->count(),
            'failed' => Notification::where('status', 'failed')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:resend,cancel,export,delete',
            'notifications' => 'required|array|min:1',
            'notifications.*' => 'exists:notifications,id'
        ]);

        try {
            $notifications = Notification::whereIn('id', $request->notifications)->get();
            $results = [];

            switch ($request->action) {
                case 'resend':
                    $results = $this->bulkResend($notifications);
                    break;
                case 'cancel':
                    $results = $this->bulkCancel($notifications);
                    break;
                case 'delete':
                    $results = $this->bulkDelete($notifications);
                    break;
                case 'export':
                    return $this->bulkExport($notifications);
            }

            return response()->json([
                'success' => true,
                'message' => $results['message'],
                'details' => $results['details'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk action failed', [
                'action' => $request->action,
                'notifications' => $request->notifications,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk resend notifications
     */
    private function bulkResend($notifications)
    {
        $resent = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            try {
                $failedLogs = $notification->logs()->where('status', 'failed')->get();
                
                if ($failedLogs->count() > 0) {
                    foreach ($failedLogs as $log) {
                        $log->update([
                            'status' => 'pending',
                            'retry_count' => 0,
                            'error_message' => null,
                            'next_retry_at' => null
                        ]);
                    }
                    
                    $notification->update(['status' => 'processing']);
                    $this->notificationService->queueNotification($notification);
                    $resent++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Bulk resend failed for notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'message' => "Resent {$resent} notifications" . ($failed > 0 ? ", {$failed} failed" : ""),
            'details' => ['resent' => $resent, 'failed' => $failed]
        ];
    }

    /**
     * Bulk cancel notifications
     */
    private function bulkCancel($notifications)
    {
        $cancelled = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            try {
                if ($notification->canBeCancelled()) {
                    $notification->cancel();
                    $cancelled++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Bulk cancel failed for notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'message' => "Cancelled {$cancelled} notifications" . ($failed > 0 ? ", {$failed} failed" : ""),
            'details' => ['cancelled' => $cancelled, 'failed' => $failed]
        ];
    }

    /**
     * Bulk delete notifications
     */
    private function bulkDelete($notifications)
    {
        $deleted = 0;
        $failed = 0;

        foreach ($notifications as $notification) {
            try {
                if (in_array($notification->status, ['draft', 'failed', 'cancelled'])) {
                    $notification->logs()->delete();
                    $notification->delete();
                    $deleted++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Bulk delete failed for notification', [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'message' => "Deleted {$deleted} notifications" . ($failed > 0 ? ", {$failed} failed" : ""),
            'details' => ['deleted' => $deleted, 'failed' => $failed]
        ];
    }

    /**
     * Export notifications
     */
    public function export(Request $request)
    {
        try {
            $query = Notification::with(['template', 'creator', 'logs']);

            // Apply filters if provided
            if ($request->filled('ids')) {
                $ids = explode(',', $request->ids);
                $query->whereIn('id', $ids);
            } else {
                // Apply same filters as index
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }
                // Add other filters...
            }

            $notifications = $query->get();

            // Generate CSV
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="notifications_' . date('Y-m-d_H-i-s') . '.csv"',
            ];

            $callback = function() use ($notifications) {
                $file = fopen('php://output', 'w');
                
                // Headers
                fputcsv($file, [
                    'UUID', 'Subject', 'Status', 'Priority', 'Channels', 
                    'Recipients Count', 'Created At', 'Created By', 'Template'
                ]);

                // Data
                foreach ($notifications as $notification) {
                    fputcsv($file, [
                        $notification->uuid,
                        $notification->subject,
                        $notification->status,
                        $notification->priority,
                        implode(', ', $notification->channels),
                        $notification->logs->count(),
                        $notification->created_at->format('Y-m-d H:i:s'),
                        $notification->creator->display_name ?? 'System',
                        $notification->template->name ?? 'N/A'
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Export failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return back()->withErrors(['error' => 'Export failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Display detailed logs for a notification
     */
    public function logs($uuid, Request $request)
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();
        $query = $notification->logs();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('recipient_email', 'LIKE', "%{$search}%")
                  ->orWhere('recipient_name', 'LIKE', "%{$search}%")
                  ->orWhere('error_message', 'LIKE', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50)->appends($request->all());

        // Summary statistics
        $summary = [
            'total' => $notification->logs->count(),
            'delivered' => $notification->logs->whereIn('status', ['sent', 'delivered'])->count(),
            'failed' => $notification->logs->where('status', 'failed')->count(),
            'pending' => $notification->logs->where('status', 'pending')->count(),
        ];

        return view('admin.notifications.logs', compact('notification', 'logs', 'summary'));
    }

    /**
     * Preview notification with rendered variables
     */
    public function preview($uuid, Request $request)
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();
        
        // Override variables if provided
        if ($request->has('variables')) {
            $notification->variables = array_merge(
                $notification->variables ?? [], 
                $request->variables
            );
        }
        
        $renderedContent = $this->renderNotificationContent($notification);
        
        return response()->json([
            'success' => true,
            'content' => $renderedContent
        ]);
    }

    /**
     * Get template preview for AJAX request
     */
    public function templatePreview(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:notification_templates,id',
            'variables' => 'nullable|array'
        ]);

        try {
            $template = NotificationTemplate::findOrFail($request->template_id);
            $variables = $request->variables ?? [];
            
            // Render template with variables
            $rendered = $template->render($variables);
            
            return response()->json([
                'success' => true,
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'variables' => $template->variables ?? [],
                    'supported_channels' => $template->supported_channels
                ],
                'preview' => [
                    'subject' => $rendered['subject'] ?? $template->subject_template,
                    'body_html' => $rendered['body_html'] ?? $template->body_html_template,
                    'body_text' => $rendered['body_text'] ?? $template->body_text_template
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading template: ' . $e->getMessage()
            ], 500);
        }
    }
}