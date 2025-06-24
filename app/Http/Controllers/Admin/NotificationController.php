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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            $query->where('group_id', $request->group_id);
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

        // Search in title and content
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%")
                  ->orWhereHas('template', function($tq) use ($search) {
                      $tq->where('display_name', 'LIKE', "%{$search}%");
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
        $statuses = Notification::distinct()->pluck('status');
        $priorities = ['low', 'normal', 'high', 'urgent'];
        $channels = ['teams', 'email'];

        // Statistics for dashboard cards
        $stats = [
            'total' => Notification::count(),
            'sent' => Notification::where('status', 'sent')->count(),
            'pending' => Notification::where('status', 'pending')->count(),
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
        // $groups = NotificationGroup::with('users')->orderBy('name')->get();
        $groups = NotificationGroup::with('users')
                               ->withCount('users')  // เพิ่มบรรทัดนี้
                               ->orderBy('name')
                               ->get();
        // dd($groups);

        $users = User::where('is_active',true)->take(10)->get();//$this->ldapService->getAllUsers();
        $channels = ['teams', 'email'];
        $priorities = ['low', 'normal', 'high', 'urgent'];

        // Initialize selectedTemplate as null
        $selectedTemplate = null;
        
        // Check if template_id is provided in the request (for pre-filling from a template)
        if ($request->filled('template_id')) {
            $selectedTemplate = NotificationTemplate::find($request->template_id);
        }

        $templateVariables = [
            'name' => '{{name}}',
            'email' => '{{email}}',
            'department' => '{{department}}',
            'date' => '{{date}}',
            'time' => '{{time}}',
            'title' => '{{title}}'
        ];

        return view('admin.notifications.create', compact(
            'templates', 'groups', 'users', 'channels', 'priorities', 'templateVariables', 'selectedTemplate'
        ));
    }

    /**
     * Store a newly created notification
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'template_id' => 'nullable|exists:notification_templates,id',
    //         'title' => 'required|string|max:255',
    //         'content' => 'required|string',
    //         'channels' => 'required|array|min:1',
    //         'channels.*' => 'in:teams,email',
    //         'priority' => 'required|in:low,normal,high,urgent',
    //         'recipients' => 'required',
    //         'scheduled_at' => 'nullable|date|after:now',
    //         'data' => 'nullable|json'
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         // Prepare recipients
    //         $recipients = $this->prepareRecipients($request->recipients);

    //         $notification = Notification::create([
    //             'template_id' => $request->template_id,
    //             'title' => $request->title,
    //             'content' => $request->content,
    //             'channels' => $request->channels,
    //             'priority' => $request->priority,
    //             'recipients' => $recipients,
    //             'scheduled_at' => $request->scheduled_at,
    //             'data' => $request->data ? json_decode($request->data, true) : null,
    //             'status' => $request->scheduled_at ? 'scheduled' : 'pending',
    //             'created_by' => auth()->id()
    //         ]);

    //         // If not scheduled, queue immediately
    //         if (!$request->scheduled_at) {
    //             $this->notificationService->processNotification($notification);
    //         }

    //         DB::commit();

    //         return redirect()->route('admin.notifications.show', $notification)
    //                        ->with('success', 'Notification created successfully!');

    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return back()->withInput()->withErrors(['error' => 'Failed to create notification: ' . $e->getMessage()]);
    //     }
    // }
    /**
     * Store a newly created notification
     */
    public function store(Request $request)
    {
        $request->validate([
            'template_id' => 'nullable|exists:notification_templates,id',
            'subject' => 'required|string|max:255',                    // ✅ เปลี่ยนจาก title
            'body_html' => 'nullable|string',                          // ✅ เพิ่ม body_html
            'body_text' => 'nullable|string',                          // ✅ เพิ่ม body_text
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:teams,email',
            'priority' => 'required|in:low,normal,high,urgent',
            'recipient_type' => 'required|in:manual,groups,all_users', // ✅ เพิ่ม recipient_type
            'recipients' => 'required_if:recipient_type,manual',       // ✅ ปรับ validation
            'recipient_groups' => 'nullable|array',                    // ✅ เพิ่ม recipient_groups
            'recipient_groups.*' => 'exists:notification_groups,id',
            'scheduled_at' => 'nullable|date|after:now',
            'variables' => 'nullable|array'                            // ✅ เปลี่ยนจาก data
        ]);

        try {
            DB::beginTransaction();

            // ✅ Prepare recipients ตาม recipient_type
            $recipients = $this->prepareRecipients($request);

            // ✅ Generate UUID
            $uuid = \Illuminate\Support\Str::uuid();

            $notification = Notification::create([
                'uuid' => $uuid,                                        // ✅ เพิ่ม uuid
                'template_id' => $request->template_id,
                'subject' => $request->subject,                         // ✅ เปลี่ยนจาก title
                'body_html' => $request->body_html,                     // ✅ เพิ่มใหม่
                'body_text' => $request->body_text,                     // ✅ เพิ่มใหม่
                'channels' => $request->channels,
                'priority' => $request->priority,
                'recipients' => $recipients['recipients'],              // ✅ ปรับโครงสร้าง
                'recipient_groups' => $recipients['recipient_groups'],  // ✅ เพิ่มใหม่
                'variables' => $request->variables,                     // ✅ เปลี่ยนจาก data
                'scheduled_at' => $request->scheduled_at,
                'status' => $request->scheduled_at ? 'scheduled' : 'queued',
                'created_by' => auth()->id()
            ]);

            // If not scheduled, queue immediately
            // if (!$request->scheduled_at) {
            //     $this->notificationService->processNotification($notification);
            // }

            DB::commit();

            return redirect()->route('admin.notifications.show', $notification)
                        ->with('success', 'Notification created successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->withErrors(['error' => 'Failed to create notification: ' . $e->getMessage()]);
        }
    }

    /**
     * ✅ ปรับ prepareRecipients ให้ทำงานกับ form ใหม่
     */
    private function prepareRecipients($request)
    {
        $recipients = [];
        $recipientGroups = [];

        switch ($request->recipient_type) {
            case 'manual':
                // Parse recipients from textarea (comma or newline separated)
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
                // Get all active users
                $recipients = \App\Models\User::where('is_active', true)
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
     * Display the specified notification with detailed information
     */
    public function show(Notification $notification)
    {
        $notification->load(['template', 'group', 'creator', 'logs.user']);

        // Delivery statistics
        $deliveryStats = $notification->logs->groupBy('status')->map->count();
        
        // Channel statistics
        $channelStats = $notification->logs->groupBy('channel')->map->count();
        
        // Recent logs (last 50)
        $recentLogs = $notification->logs()->with('user')
                                          ->orderBy('created_at', 'desc')
                                          ->limit(50)
                                          ->get();

        // Performance metrics
        $metrics = [
            'total_recipients' => $notification->logs->count(),
            'delivery_rate' => $notification->logs->count() > 0 
                ? round(($notification->logs->where('status', 'delivered')->count() / $notification->logs->count()) * 100, 2)
                : 0,
            'avg_delivery_time' => $notification->logs->where('status', 'delivered')
                ->where('delivered_at', '!=', null)
                ->avg(function($log) {
                    return $log->delivered_at->diffInSeconds($log->created_at);
                }),
            'failure_rate' => $notification->logs->count() > 0
                ? round(($notification->logs->where('status', 'failed')->count() / $notification->logs->count()) * 100, 2)
                : 0
        ];

        return view('admin.notifications.show', compact(
            'notification', 'deliveryStats', 'channelStats', 'recentLogs', 'metrics'
        ));
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

    /**
     * Send test notification
     */
    public function sendTest(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,teams',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent'
        ]);

        try {
            // Create a test notification
            $testData = [
                'subject' => '[TEST] ' . $request->subject,
                'body_html' => $request->message,
                'body_text' => strip_tags($request->message),
                'channels' => $request->channels,
                'recipients' => [$request->test_email],
                'priority' => $request->priority,
                'status' => 'draft'
            ];

            $notification = $this->notificationService->createNotification($testData);
            $this->notificationService->scheduleNotification($notification);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save notification as draft
     */
    public function saveDraft(Request $request)
    {
        try {
            $data = $request->all();
            $data['status'] = 'draft';
            
            $notification = $this->notificationService->createNotification($data);
            
            return redirect()->route('notifications.edit', $notification)
                        ->with('success', 'Draft saved successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                    ->withErrors(['error' => 'Failed to save draft: ' . $e->getMessage()]);
        }
    }

    /**
     * Display detailed logs for a notification
     */
    public function logs(Notification $notification, Request $request)
    {
        $query = $notification->logs()->with('user');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by channel
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in user info or error message
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('recipient_email', 'LIKE', "%{$search}%")
                  ->orWhere('error_message', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(50)->appends($request->all());

        // Summary statistics
        $summary = [
            'total' => $notification->logs->count(),
            'delivered' => $notification->logs->where('status', 'delivered')->count(),
            'failed' => $notification->logs->where('status', 'failed')->count(),
            'pending' => $notification->logs->where('status', 'pending')->count(),
        ];

        return view('admin.notifications.logs', compact('notification', 'logs', 'summary'));
    }

    /**
     * Display analytics dashboard
     */
    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Daily notification counts
        $dailyStats = Notification::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                 ->whereBetween('created_at', [$dateFrom, $dateTo])
                                 ->groupBy('date')
                                 ->orderBy('date')
                                 ->get();

        // Status distribution
        $statusStats = Notification::selectRaw('status, COUNT(*) as count')
                                  ->whereBetween('created_at', [$dateFrom, $dateTo])
                                  ->groupBy('status')
                                  ->get();

        // Channel performance
        $channelStats = NotificationLog::selectRaw('channel, status, COUNT(*) as count')
                                      ->whereHas('notification', function($q) use ($dateFrom, $dateTo) {
                                          $q->whereBetween('created_at', [$dateFrom, $dateTo]);
                                      })
                                      ->groupBy('channel', 'status')
                                      ->get();

        // Top templates
        $topTemplates = Notification::with('template')
                                   ->selectRaw('template_id, COUNT(*) as usage_count')
                                   ->whereBetween('created_at', [$dateFrom, $dateTo])
                                   ->whereNotNull('template_id')
                                   ->groupBy('template_id')
                                   ->orderBy('usage_count', 'desc')
                                   ->limit(10)
                                   ->get();

        // Peak hours
        $hourlyStats = Notification::selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                                  ->whereBetween('created_at', [$dateFrom, $dateTo])
                                  ->groupBy('hour')
                                  ->orderBy('hour')
                                  ->get();

        // Delivery performance
        $deliveryPerformance = NotificationLog::selectRaw('
                DATE(created_at) as date,
                AVG(CASE WHEN status = "delivered" AND delivered_at IS NOT NULL 
                    THEN TIMESTAMPDIFF(SECOND, created_at, delivered_at) END) as avg_delivery_time,
                (COUNT(CASE WHEN status = "delivered" THEN 1 END) / COUNT(*)) * 100 as delivery_rate
            ')
            ->whereHas('notification', function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('created_at', [$dateFrom, $dateTo]);
            })
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.notifications.analytics', compact(
            'dailyStats', 'statusStats', 'channelStats', 'topTemplates', 
            'hourlyStats', 'deliveryPerformance', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Cancel a scheduled notification
     */
    public function cancel(Notification $notification)
    {
        if ($notification->status !== 'scheduled') {
            return back()->withErrors(['error' => 'Only scheduled notifications can be cancelled.']);
        }

        $notification->update(['status' => 'cancelled']);

        return back()->with('success', 'Notification cancelled successfully.');
    }

    /**
     * Retry a failed notification
     */
    public function retry(Notification $notification)
    {
        if ($notification->status !== 'failed') {
            return back()->withErrors(['error' => 'Only failed notifications can be retried.']);
        }

        try {
            // $this->notificationService->processNotification($notification);
            return back()->with('success', 'Notification retry initiated.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to retry notification: ' . $e->getMessage()]);
        }
    }

    /**
     * Prepare recipients array from form input
     */
    // private function prepareRecipients($recipients)
    // {
    //     if (is_string($recipients)) {
    //         $recipients = json_decode($recipients, true);
    //     }

    //     $result = [
    //         'users' => [],
    //         'groups' => [],
    //         'emails' => []
    //     ];

    //     foreach ($recipients as $recipient) {
    //         if (isset($recipient['type'])) {
    //             switch ($recipient['type']) {
    //                 case 'user':
    //                     $result['users'][] = $recipient['id'];
    //                     break;
    //                 case 'group':
    //                     $result['groups'][] = $recipient['id'];
    //                     break;
    //                 case 'email':
    //                     $result['emails'][] = $recipient['email'];
    //                     break;
    //             }
    //         }
    //     }

    //     return $result;
    // }
}