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
use App\Jobs\SendWebhookNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Osama\LaravelTeamsNotification\TeamsNotification;
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
        $query = Notification::with(['template', 'creator', 'logs']);

        // Advanced filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
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
        $users = User::orderBy('display_name')->get(); // เปลี่ยนจาก display_name เป็น name
        $statuses = ['draft', 'queued', 'scheduled', 'processing', 'sent', 'failed', 'cancelled'];
        $priorities = ['low', 'medium', 'normal', 'high', 'urgent'];
        $channels = ['teams', 'email', 'webhook'];

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
        $channels = ['teams', 'email', 'webhook'];
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
        $rules = [
            'template_id' => 'nullable|exists:notification_templates,id',
            'subject' => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:teams,email,webhook',
            'priority' => 'required|in:low,medium,normal,high,urgent',
            'recipient_type' => 'required|in:manual,groups,all_users',
            'recipients' => 'required_if:recipient_type,manual',
            'recipient_groups' => 'nullable|array',
            'recipient_groups.*' => 'exists:notification_groups,id',
            'scheduled_at' => 'nullable|date|after:now',
            'variables' => 'nullable|array'
        ];

        // Add webhook validation rules if webhook channel is selected
        if ($request->has('channels') && in_array('webhook', $request->channels)) {
            $rules['webhook_url'] = 'required|string';
        }

        $request->validate($rules);

        $notification = null;

        try {
            // Create notification first
            $notification = DB::transaction(function() use ($request) {
                // Prepare recipients
                $recipients = $this->prepareRecipients($request);

                // Prepare webhook configuration
                $webhookUrl = null;
                if ($request->has('channels') && in_array('webhook', $request->channels)) {
                    $webhookUrl = $request->webhook_url;
                }

                // Prepare variables for replacement
                $variables = $this->prepareVariablesForReplacement($request);

                // Replace variables in subject and body content
                $processedSubject = $this->replaceVariables($request->subject, $variables);
                $processedBodyHtml = $this->replaceVariables($request->body_html, $variables);
                $processedBodyText = $this->replaceVariables($request->body_text, $variables);

                // Generate UUID
                $uuid = \Illuminate\Support\Str::uuid();

                // Create notification
                return Notification::create([
                    'uuid' => $uuid,
                    'template_id' => $request->template_id,
                    'subject' => $processedSubject,
                    'body_html' => $processedBodyHtml,
                    'body_text' => $processedBodyText,
                    'channels' => $request->channels,
                    'priority' => $request->priority,
                    'recipients' => $recipients['recipients'],
                    'recipient_groups' => $recipients['recipient_groups'],
                    'variables' => $request->variables ?? [],
                    'webhook_url' => $webhookUrl,
                    'scheduled_at' => $request->scheduled_at,
                    'status' => $request->has('save_as_draft') ? 'draft' : ($request->scheduled_at ? 'scheduled' : 'queued'),
                    'created_by' => auth()->id()
                ]);
            });

            Log::info('Notification created successfully:', [
                'notification_id' => $notification->id,
                'uuid' => $notification->uuid,
                'status' => $notification->status
            ]);

            // Process notification outside of transaction if needed
            if (!$request->scheduled_at && !$request->has('save_as_draft')) {
                Log::info('Processing notification immediately');
                
                // Process in separate try-catch to avoid affecting main transaction
                try {
                    $processResult = $this->notificationService->processNotification($notification);
                    
                    if (!$processResult) {
                        Log::warning('Notification processing returned false but notification was created');
                    }
                } catch (\Exception $processError) {
                    Log::error('Failed to process notification after creation', [
                        'notification_id' => $notification->id,
                        'error' => $processError->getMessage()
                    ]);
                    
                    // Don't fail the entire operation, just log the error
                    // The notification was created successfully, processing can be retried later
                }
            }

            $message = $request->has('save_as_draft') ? 
                'Notification draft saved successfully!' : 
                'Notification created successfully!';

            return redirect()->route('admin.notifications.show', $notification->uuid)
            ->with('success', $message);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during notification creation', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A',
                'user_id' => auth()->id()
            ]);
            
            return back()->withInput()->withErrors([
                'error' => 'Database error occurred. Please check your input and try again.'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error during notification creation', [
                'errors' => $e->errors(),
                'user_id' => auth()->id()
            ]);
            
            return back()->withInput()->withErrors($e->errors());
            
        } catch (\Exception $e) {
            Log::error('Notification creation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);
            
            return back()->withInput()->withErrors([
                'error' => 'An unexpected error occurred. Please try again.'
            ]);
        }
    }
    /**
     * Prepare variables for replacement including system variables
     */
    private function prepareVariablesForReplacement(Request $request): array
    {
        // Get system variables
        $systemVariables = $this->getSystemVariables();
        
        // Get user-provided variables
        $userVariables = $request->variables ?? [];
        
        // Get template default variables if template is selected
        $templateVariables = [];
        if ($request->template_id) {
            $template = NotificationTemplate::find($request->template_id);
            if ($template && $template->default_variables) {
                $templateVariables = $template->default_variables;
            }
        }
        
        // Merge variables (user variables override template defaults, and template defaults override system variables)
        return array_merge($systemVariables, $templateVariables, $userVariables);
    }

    /**
     * Get system variables
     */
    private function getSystemVariables(): array
    {
        $user = auth()->user();
        
        return [
            // User data
            'user_name' => $user->name ?? 'User',
            'user_email' => $user->email ?? '',
            'user_first_name' => $user->name ? explode(' ', $user->name)[0] : 'User',
            'user_last_name' => $user->name ? (explode(' ', $user->name)[1] ?? '') : '',
            'user_department' => $user->department ?? 'Unknown Department',
            'user_title' => $user->title ?? 'Employee',
            
            // System variables
            'current_date' => now()->format('Y-m-d'),
            'current_time' => now()->format('H:i:s'),
            'current_datetime' => now()->format('Y-m-d H:i:s'),
            'app_name' => config('app.name', 'Smart Notification'),
            'app_url' => config('app.url', url('/')),
            'year' => now()->format('Y'),
            'month' => now()->format('m'),
            'day' => now()->format('d'),
            'company' => config('app.name', 'Your Company'),
        ];
    }

    /**
     * Replace variables in content
     */
    private function replaceVariables(?string $content, array $variables): ?string
    {
        if (empty($content)) {
            return $content;
        }
        
        $processedContent = $content;
        
        // Replace each variable
        foreach ($variables as $key => $value) {
            // Handle array values by converting to string
            if (is_array($value)) {
                $value = implode(', ', $value);
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            } elseif (is_null($value)) {
                $value = '';
            }
            
            // Replace {{variable}} pattern (with optional spaces)
            $pattern = '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/';
            $processedContent = preg_replace($pattern, $value, $processedContent);
        }
        
        // Handle conditional blocks {{#if variable}}...{{/if}}
        $processedContent = $this->processConditionalBlocks($processedContent, $variables);
        
        // Handle loop blocks {{#each items}}...{{/each}}
        $processedContent = $this->processLoopBlocks($processedContent, $variables);
        
        // Clean up any remaining unreplaced variables by replacing with empty string or placeholder
        $processedContent = preg_replace('/\{\{[^}]+\}\}/', '[Variable Not Found]', $processedContent);
        
        return $processedContent;
    }

    /**
     * Process conditional blocks {{#if variable}}...{{/if}}
     */
    private function processConditionalBlocks(string $content, array $variables): string
    {
        $pattern = '/\{\{#if\s+([^}]+)\}\}(.*?)\{\{\/if\}\}/s';
        
        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $condition = trim($matches[1]);
            $blockContent = $matches[2];
            
            // Check if condition variable exists and is truthy
            if (isset($variables[$condition]) && !empty($variables[$condition])) {
                return $this->replaceVariables($blockContent, $variables);
            }
            
            return ''; // Remove block if condition is false
        }, $content);
    }

    /**
     * Process loop blocks {{#each items}}...{{/each}}
     */
    private function processLoopBlocks(string $content, array $variables): string
    {
        $pattern = '/\{\{#each\s+([^}]+)\}\}(.*?)\{\{\/each\}\}/s';
        
        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $arrayVariable = trim($matches[1]);
            $blockContent = $matches[2];
            
            if (!isset($variables[$arrayVariable]) || !is_array($variables[$arrayVariable])) {
                return '';
            }
            
            $result = '';
            foreach ($variables[$arrayVariable] as $index => $item) {
                $itemContent = $blockContent;
                
                // Replace {{this}} with current item
                $itemContent = str_replace('{{this}}', $item, $itemContent);
                
                // Replace {{@index}} with current index
                $itemContent = str_replace('{{@index}}', $index, $itemContent);
                
                // If item is an object/array, replace properties
                if (is_array($item)) {
                    foreach ($item as $prop => $value) {
                        $itemContent = str_replace('{{' . $prop . '}}', $value, $itemContent);
                    }
                }
                
                $result .= $itemContent;
            }
            
            return $result;
        }, $content);
    }

    /**
     * Test notification endpoint
     */
    public function test(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'test_email' => 'required|email',
            'variables' => 'nullable|array'
        ]);

        try {
            // Create a temporary notification for testing
            $testData = [
                'subject' => $request->subject,
                'body_html' => $request->body_html,
                'body_text' => $request->body_text,
                'variables' => $request->variables ?? [],
                'recipients' => [$request->test_email],
                'channels' => ['email'], // Only test email for now
                'priority' => 'normal'
            ];

            // Send test email using notification service
            $result = $this->notificationService->sendTestNotification($testData);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Test notification failed', [
                'error' => $e->getMessage(),
                'test_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification: ' . $e->getMessage()
            ], 500);
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
    // public function show($uuid)
    // {
    //     $notification = Notification::where('uuid', $uuid)->firstOrFail();
    //     $notification->load(['template', 'group', 'creator', 'logs']);

    //     // Update delivery counters first
    //     $notification->updateDeliveryCounters();
        
    //     // Refresh notification to get updated data
    //     $notification->refresh();

    //     // Render content with variables for display
    //     $renderedContent = $this->renderNotificationContent($notification);

    //     // Delivery statistics
    //     $deliveryStats = $notification->logs->groupBy('status')->map->count();
        
    //     // Channel statistics
    //     $channelStats = $notification->logs->groupBy('channel')->map->count();
        
    //     // Recent logs (last 50)
    //     $recentLogs = $notification->logs()
    //                               ->orderBy('created_at', 'desc')
    //                               ->limit(50)
    //                               ->get();

    //     // Performance metrics
    //     $metrics = [
    //         'total_recipients' => $notification->logs->count(),
    //         'delivery_rate' => $notification->logs->count() > 0 
    //             ? round(($notification->logs->whereIn('status', ['sent', 'delivered'])->count() / $notification->logs->count()) * 100, 2)
    //             : 0,
    //         'avg_delivery_time' => $notification->logs->whereIn('status', ['sent', 'delivered'])
    //             ->filter(function($log) {
    //                 return $log->delivered_at || $log->sent_at;
    //             })
    //             ->avg(function($log) {
    //                 $endTime = $log->delivered_at ?? $log->sent_at;
    //                 return $endTime ? abs($endTime->diffInSeconds($log->created_at)) : 0;
    //             }),
    //         'failure_rate' => $notification->logs->count() > 0
    //             ? round(($notification->logs->where('status', 'failed')->count() / $notification->logs->count()) * 100, 2)
    //             : 0
    //     ];

    //     return view('admin.notifications.show', compact(
    //         'notification', 'deliveryStats', 'channelStats', 'recentLogs', 'metrics', 'renderedContent'
    //     ));
    // }
    /**
     * Display the specified notification
     */
    public function show($uuid)
    {
        $notification = Notification::where('uuid', $uuid)
                                    ->with(['template', 'creator', 'logs'])
                                    ->firstOrFail();

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
                    
                case 'webhook':
                    SendWebhookNotification::dispatch($log)
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

    /**
     * Test webhook endpoint with improved Teams notification
     */
    public function testWebhook(Request $request)
    {
        $request->validate([
            'webhook_url' => 'required|url',
            'subject' => 'nullable|string',
            'body_text' => 'nullable|string'
        ]);

        try {
            $notification = new TeamsNotification();
            
            // ใช้ subject เป็น message หลัก หรือใช้ข้อความเริ่มต้น
            $message = $request->subject ?? "Test Webhook Notification";
            
            // สร้าง details จาก body_text
            $details = [];
            
            if ($request->body_text) {
                // แยกข้อมูลจาก body_text แบบต่างๆ
                $bodyText = $request->body_text;
                
                // วิธีที่ 1: ถ้าเป็น JSON format
                $jsonData = json_decode($bodyText, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $details = $jsonData;
                } 
                // วิธีที่ 2: ถ้าเป็นรูปแบบ key: value แยกด้วย newline
                else {
                    $lines = explode("\n", $bodyText);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (strpos($line, ':') !== false) {
                            list($key, $value) = explode(':', $line, 2);
                            $details[trim($key)] = trim($value);
                        }
                    }
                    
                    // ถ้าไม่มี key: value ให้ใช้เป็น message เดียว
                    if (empty($details)) {
                        $details = [
                            'Message' => $bodyText,
                            'Test Time' => now()->format('Y-m-d H:i:s'),
                            'Sent By' => auth()->user()->name ?? 'System'
                        ];
                    }
                }
            } else {
                // ใช้ข้อมูลเริ่มต้นถ้าไม่มี body_text
                $details = [
                    'Status' => 'Testing',
                    'Server' => 'Development',
                    'Test Time' => now()->format('Y-m-d H:i:s'),
                    'Sent By' => auth()->user()->name ?? 'System'
                ];
            }
            
            // เพิ่ม webhook info
            // $details['Webhook URL'] = $request->webhook_url;
            
            // ส่งข้อความผ่าน Teams
            $response = $notification->sendMessageSetWebhook($request->webhook_url, $message, $details);

            return response()->json([
                'success' => true,
                'message' => 'Webhook test successful',
                'status_code' => $response->getStatusCode(),
                'details' => [
                    'webhook_url' => $request->webhook_url,
                    'message' => $message,
                    'details_sent' => $details,
                    'response_headers' => $response->getHeaders()
                ]
            ]);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;
            
            return response()->json([
                'success' => false,
                'message' => 'Webhook test failed: ' . $e->getMessage(),
                'status_code' => $statusCode,
                'response_body' => $responseBody,
                'details' => [
                    'webhook_url' => $request->webhook_url,
                    'error_type' => 'Request Exception'
                ]
            ], 400);
        } catch (\Exception $e) {
            Log::error('Webhook test failed', [
                'webhook_url' => $request->webhook_url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Webhook test failed: ' . $e->getMessage(),
                'details' => [
                    'webhook_url' => $request->webhook_url,
                    'error_type' => 'General Exception'
                ]
            ], 500);
        }
    }
}