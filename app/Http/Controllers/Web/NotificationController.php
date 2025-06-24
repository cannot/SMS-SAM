<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\NotificationGroup;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        // $this->middleware('auth'); // Require authentication
    }

    /**
     * Display notifications list
     */
    public function index(Request $request)
    {
        $query = Notification::with(['template', 'creator', 'logs'])
                            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && !empty($request->priority)) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('template') && !empty($request->template)) {
            $query->where('template_id', $request->template);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('subject', 'ILIKE', "%{$search}%")
                  ->orWhere('uuid', 'ILIKE', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $notifications = $query->paginate(20);

        // Get filter options
        $templates = NotificationTemplate::active()->orderBy('name')->get();
        $statuses = ['draft', 'scheduled', 'queued', 'processing', 'sent', 'failed', 'cancelled'];
        $priorities = ['low', 'normal', 'high', 'urgent'];

        return view('notifications.index', compact(
            'notifications', 'templates', 'statuses', 'priorities'
        ));
    }

    /**
     * Show create notification form
     */
    public function create(Request $request) 
    {
        $templates = NotificationTemplate::active()->orderBy('name')->get();
        $groups = NotificationGroup::active()->orderBy('name')->get();
        $users = User::active()->orderBy('display_name')->get();

        // Pre-fill from template if specified
        $selectedTemplate = null;
        if ($request->has('template_id')) {
            $selectedTemplate = NotificationTemplate::find($request->template_id);
        }

        return view('notifications.create', compact(
            'templates', 'groups', 'users', 'selectedTemplate'
        ));
    }

    /**
     * Store new notification
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'nullable|exists:notification_templates,id',
            'subject' => 'required_without:template_id|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,teams',
            'recipient_type' => 'required|in:manual,groups,all_users',
            'recipients' => 'required_if:recipient_type,manual|array',
            'recipients.*' => 'email',
            'recipient_groups' => 'required_if:recipient_type,groups|array',
            'recipient_groups.*' => 'exists:notification_groups,id',
            'variables' => 'nullable|array',
            'priority' => 'required|in:low,normal,high,urgent',
            'schedule_type' => 'required|in:immediate,scheduled',
            'scheduled_at' => 'required_if:schedule_type,scheduled|nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            // Prepare recipients based on type
            $recipients = [];
            $recipientGroups = [];

            switch ($request->recipient_type) {
                case 'manual':
                    $recipients = $request->recipients ?? [];
                    break;
                    
                case 'groups':
                    $recipientGroups = $request->recipient_groups ?? [];
                    break;
                    
                case 'all_users':
                    $recipients = User::active()->pluck('email')->toArray();
                    break;
            }

            // Prepare notification data
            $notificationData = [
                'template_id' => $request->template_id,
                'subject' => $request->subject,
                'body_html' => $request->body_html,
                'body_text' => $request->body_text,
                'channels' => $request->channels,
                'recipients' => $recipients,
                'recipient_groups' => $recipientGroups,
                'variables' => $request->variables ?? [],
                'priority' => $request->priority,
                'scheduled_at' => $request->schedule_type === 'scheduled' ? 
                    \Carbon\Carbon::parse($request->scheduled_at) : null,
                'created_by' => Auth::id(),
            ];

            // Create notification
            $notification = $this->notificationService->createNotification($notificationData);
            
            // Schedule for delivery
            $result = $this->notificationService->scheduleNotification($notification);

            if ($result) {
                $message = $request->schedule_type === 'immediate' 
                    ? 'Notification has been queued for immediate delivery'
                    : 'Notification has been scheduled successfully';
                    
                return redirect()->route('notifications.show', $notification->uuid)
                               ->with('success', $message);
            } else {
                return redirect()->back()
                               ->with('error', 'Failed to schedule notification: ' . $notification->failure_reason)
                               ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Notification creation failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                           ->with('error', 'Failed to create notification: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Show notification details
     */
    public function show($uuid)
    {
        $notification = Notification::where('uuid', $uuid)
                                  ->with(['template', 'creator', 'logs'])
                                  ->firstOrFail();

        // Get delivery statistics
        $stats = [
            'total' => $notification->total_recipients,
            'delivered' => $notification->delivered_count,
            'failed' => $notification->failed_count,
            'pending' => $notification->total_recipients - $notification->delivered_count - $notification->failed_count,
        ];

        // Get logs grouped by channel
        $logsByChannel = $notification->logs()
                                    ->selectRaw('channel, status, COUNT(*) as count')
                                    ->groupBy('channel', 'status')
                                    ->get()
                                    ->groupBy('channel');

        return view('notifications.show', compact('notification', 'stats', 'logsByChannel'));
    }

    /**
     * Show edit notification form
     */
    public function edit($uuid)
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();

        // Only allow editing of draft notifications
        if ($notification->status !== 'draft') {
            return redirect()->route('notifications.show', $uuid)
                           ->with('error', 'Only draft notifications can be edited');
        }

        $templates = NotificationTemplate::active()->orderBy('name')->get();
        $groups = NotificationGroup::active()->orderBy('name')->get();
        $users = User::active()->orderBy('display_name')->get();

        return view('notifications.edit', compact('notification', 'templates', 'groups', 'users'));
    }

    /**
     * Update notification
     */
    public function update(Request $request, $uuid)
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();

        // Only allow editing of draft notifications
        if ($notification->status !== 'draft') {
            return redirect()->route('notifications.show', $uuid)
                           ->with('error', 'Only draft notifications can be edited');
        }

        $validator = Validator::make($request->all(), [
            'template_id' => 'nullable|exists:notification_templates,id',
            'subject' => 'required_without:template_id|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,teams',
            'recipient_type' => 'required|in:manual,groups,all_users',
            'recipients' => 'required_if:recipient_type,manual|array',
            'recipients.*' => 'email',
            'recipient_groups' => 'required_if:recipient_type,groups|array',
            'recipient_groups.*' => 'exists:notification_groups,id',
            'variables' => 'nullable|array',
            'priority' => 'required|in:low,normal,high,urgent',
            'schedule_type' => 'required|in:immediate,scheduled',
            'scheduled_at' => 'required_if:schedule_type,scheduled|nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        try {
            // Prepare recipients
            $recipients = [];
            $recipientGroups = [];

            switch ($request->recipient_type) {
                case 'manual':
                    $recipients = $request->recipients ?? [];
                    break;
                    
                case 'groups':
                    $recipientGroups = $request->recipient_groups ?? [];
                    break;
                    
                case 'all_users':
                    $recipients = User::active()->pluck('email')->toArray();
                    break;
            }

            // Update notification
            $notification->update([
                'template_id' => $request->template_id,
                'subject' => $request->subject,
                'body_html' => $request->body_html,
                'body_text' => $request->body_text,
                'channels' => $request->channels,
                'recipients' => $recipients,
                'recipient_groups' => $recipientGroups,
                'variables' => $request->variables ?? [],
                'priority' => $request->priority,
                'scheduled_at' => $request->schedule_type === 'scheduled' ? 
                    \Carbon\Carbon::parse($request->scheduled_at) : null,
            ]);

            return redirect()->route('notifications.show', $notification->uuid)
                           ->with('success', 'Notification updated successfully');

        } catch (\Exception $e) {
            Log::error('Notification update failed', [
                'error' => $e->getMessage(),
                'notification_id' => $notification->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                           ->with('error', 'Failed to update notification: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Delete notification
     */
    public function destroy($uuid)
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();

        // Only allow deletion of draft or failed notifications
        if (!in_array($notification->status, ['draft', 'failed', 'cancelled'])) {
            return redirect()->route('notifications.show', $uuid)
                           ->with('error', 'Only draft, failed, or cancelled notifications can be deleted');
        }

        try {
            $notification->delete();

            return redirect()->route('notifications.index')
                           ->with('success', 'Notification deleted successfully');

        } catch (\Exception $e) {
            Log::error('Notification deletion failed', [
                'error' => $e->getMessage(),
                'notification_id' => $notification->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                           ->with('error', 'Failed to delete notification: ' . $e->getMessage());
        }
    }

    /**
     * Cancel scheduled notification
     */
    public function cancel($uuid)
    {
        $notification = Notification::where('uuid', $uuid)->firstOrFail();

        // Only allow cancelling of scheduled notifications
        if ($notification->status !== 'scheduled') {
            return redirect()->route('notifications.show', $uuid)
                           ->with('error', 'Only scheduled notifications can be cancelled');
        }

        try {
            $notification->update([
                'status' => 'cancelled',
                'failure_reason' => 'Cancelled by user'
            ]);

            return redirect()->route('notifications.show', $notification->uuid)
                           ->with('success', 'Notification cancelled successfully');

        } catch (\Exception $e) {
            Log::error('Notification cancellation failed', [
                'error' => $e->getMessage(),
                'notification_id' => $notification->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                           ->with('error', 'Failed to cancel notification: ' . $e->getMessage());
        }
    }

    /**
     * Duplicate notification
     */
    public function duplicate($uuid)
    {
        $original = Notification::where('uuid', $uuid)->firstOrFail();

        try {
            $duplicate = $original->replicate();
            $duplicate->uuid = Str::uuid();
            $duplicate->status = 'draft';
            $duplicate->scheduled_at = null;
            $duplicate->sent_at = null;
            $duplicate->total_recipients = 0;
            $duplicate->delivered_count = 0;
            $duplicate->failed_count = 0;
            $duplicate->failure_reason = null;
            $duplicate->created_by = Auth::id();
            $duplicate->save();

            return redirect()->route('notifications.edit', $duplicate->uuid)
                           ->with('success', 'Notification duplicated successfully');

        } catch (\Exception $e) {
            Log::error('Notification duplication failed', [
                'error' => $e->getMessage(),
                'original_notification_id' => $original->id,
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                           ->with('error', 'Failed to duplicate notification: ' . $e->getMessage());
        }
    }

    /**
     * Send test notification
     */
    public function sendTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'test_email' => 'required|email',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,teams',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find user by test email
            $user = User::where('email', $request->test_email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found with email: ' . $request->test_email
                ], 404);
            }

            // Prepare test notification data
            $testData = [
                'title' => $request->subject,
                'message' => $request->message,
                'priority' => $request->priority,
                'channels' => $request->channels
            ];

            // Send test notification
            $result = NotificationService::sendTest($user, $testData);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'details' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Test notification failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test notification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview template with variables
     */
    public function previewTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|exists:notification_templates,id',
            'variables' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template = NotificationTemplate::findOrFail($request->template_id);
            $variables = $request->variables ?? [];

            $preview = $template->render($variables);

            return response()->json([
                'success' => true,
                'preview' => $preview
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template preview failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification statistics for dashboard
     */
    public function statistics()
    {
        try {
            $stats = [
                'total' => Notification::count(),
                'today' => Notification::whereDate('created_at', today())->count(),
                'this_week' => Notification::whereBetween('created_at', [
                    now()->startOfWeek(), now()->endOfWeek()
                ])->count(),
                'this_month' => Notification::whereMonth('created_at', now()->month)
                                          ->whereYear('created_at', now()->year)
                                          ->count(),
                'by_status' => Notification::selectRaw('status, COUNT(*) as count')
                                         ->groupBy('status')
                                         ->pluck('count', 'status'),
                'by_priority' => Notification::selectRaw('priority, COUNT(*) as count')
                                           ->groupBy('priority')
                                           ->pluck('count', 'priority'),
                'recent' => Notification::with(['template', 'creator'])
                                      ->orderBy('created_at', 'desc')
                                      ->limit(10)
                                      ->get()
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Failed to get notification statistics', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get statistics'
            ], 500);
        }
    }
}