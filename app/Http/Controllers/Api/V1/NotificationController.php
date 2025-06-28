<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationGroup;
use App\Models\NotificationTemplate;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Osama\LaravelTeamsNotification\TeamsNotification;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService = null)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * List notifications with advanced filters
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:draft,scheduled,queued,processing,sent,failed,cancelled',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'template_id' => 'sometimes|exists:notification_templates,id',
            'channel' => 'sometimes|in:email,teams,webhook',
            'limit' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'search' => 'sometimes|string|max:255',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'sort_by' => 'sometimes|in:created_at,updated_at,scheduled_at,priority,status',
            'sort_order' => 'sometimes|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Notification::query()->with(['template']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('template_id')) {
                $query->where('template_id', $request->template_id);
            }

            if ($request->has('channel')) {
                $query->whereJsonContains('channels', $request->channel);
            }

            if ($request->has('search')) {
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

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filter by API key
            if ($request->attributes->get('api_key')) {
                $query->where('api_key_id', $request->attributes->get('api_key')->id);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $limit = $request->get('limit', 20);
            $notifications = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => $notifications->items()->map(function ($notification) {
                    return [
                        'id' => $notification->uuid,
                        'template_id' => $notification->template_id,
                        'template_name' => $notification->template->name ?? null,
                        'subject' => $notification->subject,
                        'status' => $notification->status,
                        'priority' => $notification->priority,
                        'channels' => $notification->channels,
                        'recipients_count' => $notification->total_recipients,
                        'delivered_count' => $notification->delivered_count,
                        'failed_count' => $notification->failed_count,
                        'scheduled_at' => $notification->scheduled_at,
                        'created_at' => $notification->created_at,
                        'sent_at' => $notification->sent_at,
                    ];
                }),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API notifications index failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a single notification with enhanced features
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'nullable|exists:notification_templates,id',
            'recipient_type' => 'required|in:manual,groups,all_users',
            'recipients' => 'required_if:recipient_type,manual|array',
            'recipients.*' => 'email',
            'recipient_groups' => 'required_if:recipient_type,groups|array',
            'recipient_groups.*' => 'exists:notification_groups,id',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,teams,webhook',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'variables' => 'sometimes|array',
            'webhook_url' => 'required_if:channels.*,webhook|url',
            'scheduled_at' => 'sometimes|date|after:now',
            'save_as_draft' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $uuid = Str::uuid();
            
            // Prepare recipients based on type
            $recipients = $this->prepareRecipients($request);
            
            // Prepare variables for replacement
            $variables = $this->prepareVariablesForReplacement($request);
            
            // Replace variables in content
            $processedSubject = $this->replaceVariables($request->subject, $variables);
            $processedBodyHtml = $this->replaceVariables($request->body_html, $variables);
            $processedBodyText = $this->replaceVariables($request->body_text ?: $request->message, $variables);
            
            // Determine status
            $status = 'queued';
            if ($request->save_as_draft) {
                $status = 'draft';
            } elseif ($request->scheduled_at) {
                $status = 'scheduled';
            }
            
            $notification = Notification::create([
                'uuid' => $uuid,
                'template_id' => $request->template_id,
                'subject' => $processedSubject,
                'body_html' => $processedBodyHtml,
                'body_text' => $processedBodyText,
                'channels' => $request->channels,
                'recipients' => $recipients['recipients'],
                'recipient_groups' => $recipients['recipient_groups'],
                'variables' => $request->variables ?? [],
                'webhook_url' => $request->webhook_url,
                'priority' => $request->priority ?? 'normal',
                'status' => $status,
                'scheduled_at' => $request->scheduled_at,
                'total_recipients' => $this->calculateTotalRecipients($recipients, $request->channels),
                'api_key_id' => $request->attributes->get('api_key')?->id,
            ]);

            // Process immediately if not scheduled and not draft
            if (!$request->scheduled_at && !$request->save_as_draft && $this->notificationService) {
                $processResult = $this->notificationService->processNotification($notification);
                
                if (!$processResult) {
                    Log::warning('Notification processing returned false', [
                        'notification_id' => $notification->id
                    ]);
                }
            }

            $message = $request->save_as_draft ? 
                'Notification draft saved successfully' : 
                'Notification created successfully';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'notification_id' => $notification->uuid,
                    'status' => $notification->status,
                    'recipients_count' => $notification->total_recipients,
                    'estimated_delivery' => $notification->scheduled_at ?? now()->addMinutes(2),
                    'channels' => $notification->channels,
                    'template_used' => $notification->template ? $notification->template->name : null,
                    'variables_processed' => count($variables),
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('API notification send failed', [
                'error' => $e->getMessage(),
                'request' => $request->except(['body_html', 'body_text']) // Exclude large content
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send bulk notifications with enhanced features
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notifications' => 'required|array|min:1|max:20',
            'notifications.*.template_id' => 'nullable|exists:notification_templates,id',
            'notifications.*.recipient_type' => 'required|in:manual,groups,all_users',
            'notifications.*.recipients' => 'required_if:notifications.*.recipient_type,manual|array',
            'notifications.*.recipients.*' => 'email',
            'notifications.*.recipient_groups' => 'required_if:notifications.*.recipient_type,groups|array',
            'notifications.*.recipient_groups.*' => 'exists:notification_groups,id',
            'notifications.*.channels' => 'required|array|min:1',
            'notifications.*.channels.*' => 'in:email,teams,webhook',
            'notifications.*.subject' => 'required|string|max:255',
            'notifications.*.message' => 'nullable|string',
            'notifications.*.body_html' => 'nullable|string',
            'notifications.*.body_text' => 'nullable|string',
            'notifications.*.priority' => 'sometimes|in:low,normal,high,urgent',
            'notifications.*.variables' => 'sometimes|array',
            'notifications.*.webhook_url' => 'required_if:notifications.*.channels.*,webhook|url',
            'notifications.*.scheduled_at' => 'sometimes|date|after:now',
            'notifications.*.save_as_draft' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = [];
            $totalRecipients = 0;
            $successCount = 0;
            $failureCount = 0;
            
            foreach ($request->notifications as $index => $notifData) {
                try {
                    $uuid = Str::uuid();
                    
                    // Prepare recipients
                    $recipients = $this->prepareRecipientsFromData($notifData);
                    
                    // Prepare variables
                    $variables = $this->prepareVariablesFromData($notifData);
                    
                    // Replace variables in content
                    $processedSubject = $this->replaceVariables($notifData['subject'], $variables);
                    $processedBodyHtml = $this->replaceVariables($notifData['body_html'] ?? null, $variables);
                    $processedBodyText = $this->replaceVariables($notifData['body_text'] ?? $notifData['message'] ?? null, $variables);
                    
                    $recipientCount = $this->calculateTotalRecipients($recipients, $notifData['channels']);
                    
                    // Determine status
                    $status = 'queued';
                    if ($notifData['save_as_draft'] ?? false) {
                        $status = 'draft';
                    } elseif ($notifData['scheduled_at'] ?? null) {
                        $status = 'scheduled';
                    }
                    
                    $notification = Notification::create([
                        'uuid' => $uuid,
                        'template_id' => $notifData['template_id'] ?? null,
                        'subject' => $processedSubject,
                        'body_html' => $processedBodyHtml,
                        'body_text' => $processedBodyText,
                        'channels' => $notifData['channels'],
                        'recipients' => $recipients['recipients'],
                        'recipient_groups' => $recipients['recipient_groups'],
                        'variables' => $notifData['variables'] ?? [],
                        'webhook_url' => $notifData['webhook_url'] ?? null,
                        'priority' => $notifData['priority'] ?? 'normal',
                        'status' => $status,
                        'scheduled_at' => $notifData['scheduled_at'] ?? null,
                        'total_recipients' => $recipientCount,
                        'api_key_id' => $request->attributes->get('api_key')?->id,
                    ]);

                    $results[] = [
                        'index' => $index,
                        'notification_id' => $notification->uuid,
                        'status' => $notification->status,
                        'recipients_count' => $notification->total_recipients,
                        'channels' => $notification->channels,
                        'success' => true,
                    ];

                    $totalRecipients += $recipientCount;
                    $successCount++;

                    // Process if service available and not scheduled or draft
                    if (!($notifData['scheduled_at'] ?? null) && !($notifData['save_as_draft'] ?? false) && $this->notificationService) {
                        $this->notificationService->processNotification($notification);
                    }
                    
                } catch (\Exception $e) {
                    Log::error('Bulk notification item failed', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'data' => $notifData
                    ]);
                    
                    $results[] = [
                        'index' => $index,
                        'notification_id' => null,
                        'status' => 'failed',
                        'recipients_count' => 0,
                        'channels' => $notifData['channels'] ?? [],
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                    
                    $failureCount++;
                }
            }

            return response()->json([
                'success' => $successCount > 0,
                'message' => "Bulk operation completed: {$successCount} succeeded, {$failureCount} failed",
                'data' => $results,
                'summary' => [
                    'total_notifications' => count($request->notifications),
                    'successful_notifications' => $successCount,
                    'failed_notifications' => $failureCount,
                    'total_recipients' => $totalRecipients,
                ]
            ], $failureCount > 0 ? 207 : 201); // 207 Multi-Status if partial success
        } catch (\Exception $e) {
            Log::error('API bulk notification send failed', [
                'error' => $e->getMessage(),
                'request_count' => count($request->notifications ?? [])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification status
     */
    public function getStatus($id): JsonResponse
    {
        try {
            $query = Notification::where('uuid', $id);
            
            // Filter by API key
            if (request()->attributes->get('api_key')) {
                $query->where('api_key_id', request()->attributes->get('api_key')->id);
            }
            
            $notification = $query->firstOrFail();
            
            $logs = $notification->logs()->get()->groupBy('status');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'notification_id' => $notification->uuid,
                    'status' => $notification->status,
                    'priority' => $notification->priority,
                    'channels' => $notification->channels,
                    'created_at' => $notification->created_at,
                    'scheduled_at' => $notification->scheduled_at,
                    'sent_at' => $notification->sent_at,
                    'delivery_stats' => [
                        'total' => $notification->total_recipients,
                        'delivered' => $logs->get('delivered', collect())->count() + $logs->get('sent', collect())->count(),
                        'failed' => $logs->get('failed', collect())->count(),
                        'pending' => $logs->get('pending', collect())->count(),
                    ],
                    'template' => $notification->template ? [
                        'id' => $notification->template->id,
                        'name' => $notification->template->name,
                    ] : null,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific notification details
     */
    public function show($id): JsonResponse
    {
        try {
            $query = Notification::where('uuid', $id)->with(['template', 'logs']);
            
            // Filter by API key
            if (request()->attributes->get('api_key')) {
                $query->where('api_key_id', request()->attributes->get('api_key')->id);
            }
            
            $notification = $query->firstOrFail();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $notification->uuid,
                    'template_id' => $notification->template_id,
                    'template' => $notification->template ? [
                        'id' => $notification->template->id,
                        'name' => $notification->template->name,
                        'description' => $notification->template->description,
                    ] : null,
                    'subject' => $notification->subject,
                    'body_html' => $notification->body_html,
                    'body_text' => $notification->body_text,
                    'status' => $notification->status,
                    'priority' => $notification->priority,
                    'channels' => $notification->channels,
                    'recipients' => $notification->recipients,
                    'recipient_groups' => $notification->recipient_groups,
                    'variables' => $notification->variables,
                    'webhook_url' => $notification->webhook_url,
                    'total_recipients' => $notification->total_recipients,
                    'delivered_count' => $notification->delivered_count,
                    'failed_count' => $notification->failed_count,
                    'scheduled_at' => $notification->scheduled_at,
                    'created_at' => $notification->created_at,
                    'sent_at' => $notification->sent_at,
                    'failure_reason' => $notification->failure_reason,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:draft,scheduled,queued,processing,sent,failed,cancelled',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'channel' => 'sometimes|in:email,teams,webhook',
            'days' => 'sometimes|integer|min:1|max:90',
            'limit' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Notification::query()->with(['template']);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('channel')) {
                $query->whereJsonContains('channels', $request->channel);
            }

            $days = $request->get('days', 30);
            $query->where('created_at', '>=', now()->subDays($days));

            // Filter by API key
            if ($request->attributes->get('api_key')) {
                $query->where('api_key_id', $request->attributes->get('api_key')->id);
            }

            $limit = $request->get('limit', 50);
            
            if ($request->has('page')) {
                $notifications = $query->latest()->paginate($limit);
                
                return response()->json([
                    'success' => true,
                    'data' => $notifications->items()->map(function ($notification) {
                        return $this->formatNotificationForApi($notification);
                    }),
                    'meta' => [
                        'current_page' => $notifications->currentPage(),
                        'per_page' => $notifications->perPage(),
                        'total' => $notifications->total(),
                        'last_page' => $notifications->lastPage(),
                    ]
                ]);
            } else {
                $notifications = $query->latest()->limit($limit)->get();
                
                return response()->json([
                    'success' => true,
                    'data' => $notifications->map(function ($notification) {
                        return $this->formatNotificationForApi($notification);
                    })
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a scheduled notification
     */
    public function cancel($id): JsonResponse
    {
        try {
            $query = Notification::where('uuid', $id);
            
            // Filter by API key
            if (request()->attributes->get('api_key')) {
                $query->where('api_key_id', request()->attributes->get('api_key')->id);
            }
            
            $notification = $query->firstOrFail();

            if (!in_array($notification->status, ['scheduled', 'queued', 'draft'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only scheduled, queued, or draft notifications can be cancelled'
                ], 400);
            }

            $notification->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Notification cancelled successfully',
                'data' => [
                    'notification_id' => $notification->uuid,
                    'status' => $notification->status,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get failed notifications
     */
    public function getFailed(Request $request): JsonResponse
    {
        try {
            $query = Notification::where('status', 'failed');
            
            // Filter by API key
            if ($request->attributes->get('api_key')) {
                $query->where('api_key_id', $request->attributes->get('api_key')->id);
            }
            
            $limit = $request->get('limit', 20);
            $notifications = $query->latest()->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => $notifications->map(function ($notification) {
                    return [
                        'id' => $notification->uuid,
                        'subject' => $notification->subject,
                        'priority' => $notification->priority,
                        'channels' => $notification->channels,
                        'recipients_count' => $notification->total_recipients,
                        'failure_reason' => $notification->failure_reason,
                        'created_at' => $notification->created_at,
                        'failed_at' => $notification->updated_at,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get failed notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get scheduled notifications
     */
    public function getScheduled(Request $request): JsonResponse
    {
        try {
            $query = Notification::where('status', 'scheduled');
            
            // Filter by API key
            if ($request->attributes->get('api_key')) {
                $query->where('api_key_id', $request->attributes->get('api_key')->id);
            }
            
            $limit = $request->get('limit', 20);
            $notifications = $query->orderBy('scheduled_at')->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => $notifications->map(function ($notification) {
                    return [
                        'id' => $notification->uuid,
                        'subject' => $notification->subject,
                        'priority' => $notification->priority,
                        'channels' => $notification->channels,
                        'recipients_count' => $notification->total_recipients,
                        'scheduled_at' => $notification->scheduled_at,
                        'created_at' => $notification->created_at,
                        'time_until_send' => $notification->scheduled_at ? $notification->scheduled_at->diffForHumans() : null,
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get scheduled notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $baseQuery = Notification::query();
            
            // Filter by API key if present
            if (request()->attributes->get('api_key')) {
                $baseQuery->where('api_key_id', request()->attributes->get('api_key')->id);
            }

            $stats = [
                'total_notifications' => (clone $baseQuery)->count(),
                'sent_today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
                'sent_this_week' => (clone $baseQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'sent_this_month' => (clone $baseQuery)->whereMonth('created_at', now()->month)->count(),
                'by_status' => (clone $baseQuery)->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'by_priority' => (clone $baseQuery)->selectRaw('priority, count(*) as count')
                    ->groupBy('priority')
                    ->pluck('count', 'priority'),
                'by_channel' => [],
                'delivery_rate' => [
                    'total_sent' => (clone $baseQuery)->where('status', 'sent')->count(),
                    'total_failed' => (clone $baseQuery)->where('status', 'failed')->count(),
                ],
                'recent_activity' => []
            ];

            // Calculate channel statistics
            $channelStats = [];
            $notifications = (clone $baseQuery)->whereNotNull('channels')->get(['channels']);
            foreach ($notifications as $notification) {
                foreach ($notification->channels as $channel) {
                    $channelStats[$channel] = ($channelStats[$channel] ?? 0) + 1;
                }
            }
            $stats['by_channel'] = $channelStats;

            // Calculate delivery rate percentage
            $totalDelivered = $stats['delivery_rate']['total_sent'];
            $totalFailed = $stats['delivery_rate']['total_failed'];
            $totalProcessed = $totalDelivered + $totalFailed;
            
            $stats['delivery_rate']['percentage'] = $totalProcessed > 0 ? round(($totalDelivered / $totalProcessed) * 100, 2) : 0;

            // Recent activity (last 24 hours)
            $recentActivity = (clone $baseQuery)
                ->where('created_at', '>=', now()->subDay())
                ->selectRaw('DATE_FORMAT(created_at, "%H:00") as hour, count(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();
            
            $stats['recent_activity'] = $recentActivity->map(function($item) {
                return [
                    'hour' => $item->hour,
                    'count' => $item->count
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get unread count (placeholder)
     */
    public function getUnreadCount(): JsonResponse
    {
        try {
            // This would typically be user-specific or API key specific
            $query = NotificationLog::whereNull('read_at');
            
            // Filter by API key's notifications if available
            if (request()->attributes->get('api_key')) {
                $query->whereHas('notification', function($q) {
                    $q->where('api_key_id', request()->attributes->get('api_key')->id);
                });
            }
            
            $count = $query->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'unread_count' => $count
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry failed notification
     */
    public function retry($id): JsonResponse
    {
        try {
            $query = Notification::where('uuid', $id);
            
            // Filter by API key
            if (request()->attributes->get('api_key')) {
                $query->where('api_key_id', request()->attributes->get('api_key')->id);
            }
            
            $notification = $query->firstOrFail();

            if ($notification->status !== 'failed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only failed notifications can be retried'
                ], 400);
            }

            // Reset failed logs to pending
            $failedLogs = $notification->logs()->where('status', 'failed')->get();
            
            if ($failedLogs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No failed deliveries found to retry'
                ], 400);
            }

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
            if ($this->notificationService) {
                $this->notificationService->processNotification($notification);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification queued for retry',
                'data' => [
                    'notification_id' => $notification->uuid,
                    'status' => $notification->status,
                    'retry_count' => $failedLogs->count()
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate notification data before sending
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'nullable|exists:notification_templates,id',
            'recipient_type' => 'required|in:manual,groups,all_users',
            'recipients' => 'required_if:recipient_type,manual|array',
            'recipients.*' => 'email',
            'recipient_groups' => 'required_if:recipient_type,groups|array',
            'recipient_groups.*' => 'exists:notification_groups,id',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,teams,webhook',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'variables' => 'sometimes|array',
            'webhook_url' => 'required_if:channels.*,webhook|url',
            'scheduled_at' => 'sometimes|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Calculate estimated recipients
            $recipients = $this->prepareRecipients($request);
            $totalRecipients = $this->calculateTotalRecipients($recipients, $request->channels);
            
            // Validate template if provided
            $templateInfo = null;
            if ($request->template_id) {
                $template = NotificationTemplate::find($request->template_id);
                if ($template) {
                    $templateInfo = [
                        'id' => $template->id,
                        'name' => $template->name,
                        'supported_channels' => $template->supported_channels,
                        'required_variables' => $template->variables ?? [],
                    ];
                    
                    // Check if requested channels are supported
                    $unsupportedChannels = array_diff($request->channels, $template->supported_channels);
                    if (!empty($unsupportedChannels)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Template does not support channels: ' . implode(', ', $unsupportedChannels),
                            'errors' => ['channels' => ['Unsupported channels for selected template']]
                        ], 422);
                    }
                }
            }

            // Validate webhook URL if webhook channel is used
            $webhookValidation = null;
            if (in_array('webhook', $request->channels) && $request->webhook_url) {
                $webhookValidation = [
                    'url' => $request->webhook_url,
                    'is_valid' => filter_var($request->webhook_url, FILTER_VALIDATE_URL) !== false,
                    'is_https' => str_starts_with($request->webhook_url, 'https://'),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification data is valid',
                'data' => [
                    'estimated_recipients' => $totalRecipients,
                    'estimated_cost' => $this->calculateEstimatedCost($totalRecipients, $request->channels),
                    'estimated_delivery_time' => $this->calculateEstimatedDeliveryTime($request->priority ?? 'normal'),
                    'template_info' => $templateInfo,
                    'webhook_validation' => $webhookValidation,
                    'channels_summary' => [
                        'email' => in_array('email', $request->channels),
                        'teams' => in_array('teams', $request->channels),
                        'webhook' => in_array('webhook', $request->channels),
                    ],
                    'variables_count' => count($request->variables ?? []),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bulk status for multiple notifications
     */
    public function getBulkStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array|min:1|max:50',
            'notification_ids.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Notification::whereIn('uuid', $request->notification_ids);
            
            // Filter by API key
            if ($request->attributes->get('api_key')) {
                $query->where('api_key_id', $request->attributes->get('api_key')->id);
            }
            
            $notifications = $query->with('logs')->get();

            $results = $notifications->map(function ($notification) {
                $logs = $notification->logs->groupBy('status');
                
                return [
                    'notification_id' => $notification->uuid,
                    'status' => $notification->status,
                    'priority' => $notification->priority,
                    'channels' => $notification->channels,
                    'created_at' => $notification->created_at,
                    'sent_at' => $notification->sent_at,
                    'delivery_stats' => [
                        'total' => $notification->total_recipients,
                        'delivered' => $logs->get('delivered', collect())->count() + $logs->get('sent', collect())->count(),
                        'failed' => $logs->get('failed', collect())->count(),
                        'pending' => $logs->get('pending', collect())->count(),
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $results,
                'summary' => [
                    'total_requested' => count($request->notification_ids),
                    'total_found' => $results->count(),
                    'by_status' => $results->groupBy('status')->map->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get bulk status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk cancel notifications
     */
    public function bulkCancel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array|min:1|max:50',
            'notification_ids.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Notification::whereIn('uuid', $request->notification_ids)
                                ->whereIn('status', ['scheduled', 'queued', 'draft']);
            
            // Filter by API key
            if ($request->attributes->get('api_key')) {
                $query->where('api_key_id', $request->attributes->get('api_key')->id);
            }
            
            $notifications = $query->get();
            $cancelledCount = 0;
            $cancelledIds = [];

            foreach ($notifications as $notification) {
                try {
                    $notification->update(['status' => 'cancelled']);
                    $cancelledCount++;
                    $cancelledIds[] = $notification->uuid;
                } catch (\Exception $e) {
                    Log::error('Failed to cancel notification in bulk', [
                        'notification_id' => $notification->uuid,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully cancelled {$cancelledCount} notifications",
                'data' => [
                    'requested_count' => count($request->notification_ids),
                    'cancelled_count' => $cancelledCount,
                    'eligible_count' => $notifications->count(),
                    'cancelled_ids' => $cancelledIds,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk retry failed notifications
     */
    public function bulkRetry(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array|min:1|max:50',
            'notification_ids.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Notification::whereIn('uuid', $request->notification_ids)
                                ->where('status', 'failed');
            
            // Filter by API key
            if ($request->attributes->get('api_key')) {
                $query->where('api_key_id', $request->attributes->get('api_key')->id);
            }
            
            $notifications = $query->get();
            $retriedCount = 0;
            $retriedIds = [];

            foreach ($notifications as $notification) {
                try {
                    // Reset failed logs
                    $failedLogs = $notification->logs()->where('status', 'failed')->get();
                    
                    foreach ($failedLogs as $log) {
                        $log->update([
                            'status' => 'pending',
                            'retry_count' => 0,
                            'error_message' => null,
                            'next_retry_at' => null
                        ]);
                    }

                    $notification->update([
                        'status' => 'processing',
                        'failure_reason' => null
                    ]);

                    if ($this->notificationService) {
                        $this->notificationService->processNotification($notification);
                    }

                    $retriedCount++;
                    $retriedIds[] = $notification->uuid;
                } catch (\Exception $e) {
                    Log::error('Failed to retry notification in bulk', [
                        'notification_id' => $notification->uuid,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully queued {$retriedCount} notifications for retry",
                'data' => [
                    'requested_count' => count($request->notification_ids),
                    'retried_count' => $retriedCount,
                    'eligible_count' => $notifications->count(),
                    'retried_ids' => $retriedIds,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhook(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'required|url',
            'subject' => 'nullable|string|max:255',
            'body_text' => 'nullable|string',
            'test_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $notification = new TeamsNotification();
            
            // Use subject as main message or default
            $message = $request->subject ?? "API Webhook Test Notification";
            
            // Create details from body_text or test_data
            $details = [];
            
            if ($request->body_text) {
                // Try JSON decode first
                $jsonData = json_decode($request->body_text, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $details = $jsonData;
                } else {
                    // Parse key: value format
                    $lines = explode("\n", $request->body_text);
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (strpos($line, ':') !== false) {
                            list($key, $value) = explode(':', $line, 2);
                            $details[trim($key)] = trim($value);
                        }
                    }
                    
                    if (empty($details)) {
                        $details['Message'] = $request->body_text;
                    }
                }
            } elseif ($request->test_data) {
                $details = $request->test_data;
            }
            
            // Add default test information
            if (empty($details)) {
                $details = [
                    'Status' => 'API Test',
                    'Test Time' => now()->format('Y-m-d H:i:s'),
                    'API Key' => $request->attributes->get('api_key')?->name ?? 'Unknown',
                ];
            } else {
                $details['Test Time'] = now()->format('Y-m-d H:i:s');
                $details['Source'] = 'API Test';
                $details['API Key'] = $request->attributes->get('api_key')?->name ?? 'Unknown';
            }
            
            // Send message through Teams
            $response = $notification->sendMessageSetWebhook($request->webhook_url, $message, $details);

            return response()->json([
                'success' => true,
                'message' => 'Webhook test successful',
                'data' => [
                    'webhook_url' => $request->webhook_url,
                    'message_sent' => $message,
                    'details_sent' => $details,
                    'status_code' => $response->getStatusCode(),
                    'response_time' => now()->format('Y-m-d H:i:s'),
                ]
            ]);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;
            
            return response()->json([
                'success' => false,
                'message' => 'Webhook test failed: ' . $e->getMessage(),
                'error' => [
                    'type' => 'Request Exception',
                    'status_code' => $statusCode,
                    'response_body' => $responseBody,
                    'webhook_url' => $request->webhook_url,
                ]
            ], 400);
        } catch (\Exception $e) {
            Log::error('API webhook test failed', [
                'webhook_url' => $request->webhook_url,
                'error' => $e->getMessage(),
                'api_key' => $request->attributes->get('api_key')?->name,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Webhook test failed: ' . $e->getMessage(),
                'error' => [
                    'type' => 'General Exception',
                    'webhook_url' => $request->webhook_url,
                ]
            ], 500);
        }
    }

    /**
     * Get available groups for API users
     */
    public function getGroups(): JsonResponse
    {
        try {
            $groups = NotificationGroup::with('users:id,name,email')
                                    ->withCount('users')
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get(['id', 'name', 'description', 'is_active']);

            return response()->json([
                'success' => true,
                'data' => $groups->map(function($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'description' => $group->description,
                        'is_active' => $group->is_active,
                        'users_count' => $group->users_count,
                        'users' => $group->users->map(function($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                            ];
                        }),
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve groups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available templates for API users
     */
    public function getTemplates(): JsonResponse
    {
        try {
            $templates = NotificationTemplate::where('is_active', true)
                                           ->orderBy('name')
                                           ->get(['id', 'name', 'description', 'supported_channels', 'variables', 'default_variables']);

            return response()->json([
                'success' => true,
                'data' => $templates->map(function($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'description' => $template->description,
                        'supported_channels' => $template->supported_channels,
                        'variables' => $template->variables ?? [],
                        'default_variables' => $template->default_variables ?? [],
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview template with variables
     */
    public function previewTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|exists:notification_templates,id',
            'variables' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template = NotificationTemplate::findOrFail($request->template_id);
            $variables = array_merge(
                $this->getSystemVariables(),
                $template->default_variables ?? [],
                $request->variables ?? []
            );
            
            $rendered = $template->render($variables);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'template' => [
                        'id' => $template->id,
                        'name' => $template->name,
                        'description' => $template->description,
                        'supported_channels' => $template->supported_channels,
                    ],
                    'variables_used' => $variables,
                    'preview' => $rendered,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification logs
     */
    public function getLogs($id, Request $request): JsonResponse
    {
        try {
            $query = Notification::where('uuid', $id);
            
            // Filter by API key
            if ($request->attributes->get('api_key')) {
                $query->where('api_key_id', $request->attributes->get('api_key')->id);
            }
            
            $notification = $query->firstOrFail();
            
            $logsQuery = $notification->logs();
            
            // Apply filters
            if ($request->has('status')) {
                $logsQuery->where('status', $request->status);
            }
            
            if ($request->has('channel')) {
                $logsQuery->where('channel', $request->channel);
            }
            
            $limit = $request->get('limit', 50);
            $logs = $logsQuery->orderBy('created_at', 'desc')->limit($limit)->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'notification_id' => $notification->uuid,
                    'logs' => $logs->map(function($log) {
                        return [
                            'id' => $log->id,
                            'recipient_email' => $log->recipient_email,
                            'recipient_name' => $log->recipient_name,
                            'channel' => $log->channel,
                            'status' => $log->status,
                            'error_message' => $log->error_message,
                            'retry_count' => $log->retry_count,
                            'sent_at' => $log->sent_at,
                            'delivered_at' => $log->delivered_at,
                            'created_at' => $log->created_at,
                        ];
                    }),
                    'summary' => [
                        'total_logs' => $notification->logs()->count(),
                        'delivered' => $notification->logs()->whereIn('status', ['sent', 'delivered'])->count(),
                        'failed' => $notification->logs()->where('status', 'failed')->count(),
                        'pending' => $notification->logs()->where('status', 'pending')->count(),
                    ]
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview notification content
     */
    public function preview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'nullable|exists:notification_templates,id',
            'subject' => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'variables' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Prepare variables for replacement
            $variables = $this->getSystemVariables();
            
            if ($request->template_id) {
                $template = NotificationTemplate::find($request->template_id);
                if ($template && $template->default_variables) {
                    $variables = array_merge($variables, $template->default_variables);
                }
            }
            
            if ($request->variables) {
                $variables = array_merge($variables, $request->variables);
            }
            
            // Replace variables in content
            $processedSubject = $this->replaceVariables($request->subject, $variables);
            $processedBodyHtml = $this->replaceVariables($request->body_html, $variables);
            $processedBodyText = $this->replaceVariables($request->body_text, $variables);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'original' => [
                        'subject' => $request->subject,
                        'body_html' => $request->body_html,
                        'body_text' => $request->body_text,
                    ],
                    'processed' => [
                        'subject' => $processedSubject,
                        'body_html' => $processedBodyHtml,
                        'body_text' => $processedBodyText,
                    ],
                    'variables_used' => $variables,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview content',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    /**
     * Prepare recipients based on request data
     */
    private function prepareRecipients(Request $request): array
    {
        return $this->prepareRecipientsFromData($request->all());
    }

    /**
     * Prepare recipients from data array
     */
    private function prepareRecipientsFromData(array $data): array
    {
        $recipients = [];
        $recipientGroups = [];

        switch ($data['recipient_type']) {
            case 'manual':
                $recipients = $data['recipients'] ?? [];
                break;
                
            case 'groups':
                $recipientGroups = $data['recipient_groups'] ?? [];
                break;
                
            case 'all_users':
                $recipients = User::where('is_active', true)
                                ->whereNotNull('email')
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
     * Prepare variables for replacement
     */
    private function prepareVariablesForReplacement(Request $request): array
    {
        return $this->prepareVariablesFromData($request->all());
    }

    /**
     * Prepare variables from data array
     */
    private function prepareVariablesFromData(array $data): array
    {
        $systemVariables = $this->getSystemVariables();
        $userVariables = $data['variables'] ?? [];
        
        $templateVariables = [];
        if (isset($data['template_id'])) {
            $template = NotificationTemplate::find($data['template_id']);
            if ($template && $template->default_variables) {
                $templateVariables = $template->default_variables;
            }
        }
        return array_merge($systemVariables, $templateVariables, $userVariables);
    }

    /**
     * Get system variables
     */
    private function getSystemVariables(): array
    {
        return [
            'current_date' => now()->format('Y-m-d'),
            'current_time' => now()->format('H:i:s'),
            'current_datetime' => now()->format('Y-m-d H:i:s'),
            'app_name' => config('app.name', 'Smart Notification'),
            'app_url' => config('app.url', url('/')),
            'year' => now()->format('Y'),
            'month' => now()->format('m'),
            'day' => now()->format('d'),
            'company' => config('app.name', 'Your Company'),
            'api_version' => 'v1',
            'timestamp' => now()->timestamp,
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
        
        foreach ($variables as $key => $value) {
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
        
        // Clean up unreplaced variables
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
     * Calculate total recipients
     */
    private function calculateTotalRecipients(array $recipients, array $channels): int
    {
        if (in_array('webhook', $channels)) {
            return 1; // Webhook counts as 1 recipient
        }
        
        $totalEmails = count($recipients['recipients']);
        
        if (!empty($recipients['recipient_groups'])) {
            $groupUsers = User::whereHas('notificationGroups', function($query) use ($recipients) {
                $query->whereIn('notification_groups.id', $recipients['recipient_groups']);
            })->distinct()->count();
            
            $totalEmails += $groupUsers;
        }
        
        return $totalEmails;
    }

    /**
     * Calculate estimated cost (placeholder)
     */
    private function calculateEstimatedCost(int $recipients, array $channels): array
    {
        $costs = [
            'email' => 0.001, // $0.001 per email
            'teams' => 0.002, // $0.002 per Teams message
            'webhook' => 0.001, // $0.001 per webhook call
        ];

        $totalCost = 0;
        $breakdown = [];

        foreach ($channels as $channel) {
            $channelCost = ($costs[$channel] ?? 0) * ($channel === 'webhook' ? 1 : $recipients);
            $breakdown[$channel] = round($channelCost, 4);
            $totalCost += $channelCost;
        }

        return [
            'total' => round($totalCost, 4),
            'currency' => 'USD',
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate estimated delivery time
     */
    private function calculateEstimatedDeliveryTime(string $priority): string
    {
        $delays = [
            'low' => 10,     // 10 minutes
            'normal' => 5,   // 5 minutes
            'high' => 2,     // 2 minutes
            'urgent' => 1,   // 1 minute
        ];

        $delayMinutes = $delays[$priority] ?? 5;
        return now()->addMinutes($delayMinutes)->toISOString();
    }

    /**
     * Format notification for API response
     */
    private function formatNotificationForApi($notification): array
    {
        return [
            'id' => $notification->uuid,
            'template_id' => $notification->template_id,
            'template_name' => $notification->template->name ?? null,
            'subject' => $notification->subject,
            'status' => $notification->status,
            'priority' => $notification->priority,
            'channels' => $notification->channels,
            'recipients_count' => $notification->total_recipients,
            'delivered_count' => $notification->delivered_count,
            'failed_count' => $notification->failed_count,
            'scheduled_at' => $notification->scheduled_at,
            'created_at' => $notification->created_at,
            'sent_at' => $notification->sent_at,
            'failure_reason' => $notification->failure_reason,
        ];
    }

    /**
     * Extract name from email address
     */
    private function extractNameFromEmail(string $email): string
    {
        $localPart = explode('@', $email)[0];
        return ucwords(str_replace(['.', '_', '-'], ' ', $localPart));
    }

    /**
     * Schedule notification for later processing
     */
    public function schedule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'nullable|exists:notification_templates,id',
            'recipient_type' => 'required|in:manual,groups,all_users',
            'recipients' => 'required_if:recipient_type,manual|array',
            'recipients.*' => 'email',
            'recipient_groups' => 'required_if:recipient_type,groups|array',
            'recipient_groups.*' => 'exists:notification_groups,id',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,teams,webhook',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'variables' => 'sometimes|array',
            'webhook_url' => 'required_if:channels.*,webhook|url',
            'scheduled_at' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Set scheduled_at and call send method
        $request->merge(['save_as_draft' => false]);
        
        return $this->send($request);
    }

    /**
     * Duplicate an existing notification
     */
    public function duplicate($id, Request $request): JsonResponse
    {
        try {
            $query = Notification::where('uuid', $id);
            
            // Filter by API key
            if ($request->attributes->get('api_key')) {
                $query->where('api_key_id', $request->attributes->get('api_key')->id);
            }
            
            $originalNotification = $query->firstOrFail();
            
            // Prepare new notification data
            $newNotificationData = [
                'template_id' => $originalNotification->template_id,
                'recipient_type' => !empty($originalNotification->recipient_groups) ? 'groups' : 
                                  (!empty($originalNotification->recipients) ? 'manual' : 'all_users'),
                'recipients' => $originalNotification->recipients,
                'recipient_groups' => $originalNotification->recipient_groups,
                'channels' => $originalNotification->channels,
                'subject' => $originalNotification->subject . ' (Copy)',
                'body_html' => $originalNotification->body_html,
                'body_text' => $originalNotification->body_text,
                'priority' => $originalNotification->priority,
                'variables' => $originalNotification->variables,
                'webhook_url' => $originalNotification->webhook_url,
                'save_as_draft' => true, // Always save duplicates as draft
            ];
            
            // Create new request with duplicated data
            $newRequest = new Request($newNotificationData);
            $newRequest->attributes->set('api_key', $request->attributes->get('api_key'));
            
            // Send the duplicated notification
            return $this->send($newRequest);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get delivery report for a notification
     */
    public function getDeliveryReport($id): JsonResponse
    {
        try {
            $query = Notification::where('uuid', $id);
            
            // Filter by API key
            if (request()->attributes->get('api_key')) {
                $query->where('api_key_id', request()->attributes->get('api_key')->id);
            }
            
            $notification = $query->with(['logs', 'template'])->firstOrFail();
            
            // Group logs by status and channel
            $logsByStatus = $notification->logs->groupBy('status');
            $logsByChannel = $notification->logs->groupBy('channel');
            
            // Calculate delivery metrics
            $totalLogs = $notification->logs->count();
            $deliveredCount = $logsByStatus->get('sent', collect())->count() + 
                             $logsByStatus->get('delivered', collect())->count();
            $failedCount = $logsByStatus->get('failed', collect())->count();
            $pendingCount = $logsByStatus->get('pending', collect())->count();
            
            // Calculate delivery rate
            $deliveryRate = $totalLogs > 0 ? round(($deliveredCount / $totalLogs) * 100, 2) : 0;
            
            // Get failure reasons
            $failureReasons = $notification->logs
                ->where('status', 'failed')
                ->whereNotNull('error_message')
                ->groupBy('error_message')
                ->map->count()
                ->sortDesc();
            
            // Calculate average delivery time
            $avgDeliveryTime = $notification->logs
                ->whereIn('status', ['sent', 'delivered'])
                ->filter(function($log) {
                    return $log->sent_at || $log->delivered_at;
                })
                ->avg(function($log) {
                    $endTime = $log->delivered_at ?? $log->sent_at;
                    return $endTime ? $endTime->diffInSeconds($log->created_at) : 0;
                });
            
            $report = [
                'notification' => [
                    'id' => $notification->uuid,
                    'subject' => $notification->subject,
                    'status' => $notification->status,
                    'priority' => $notification->priority,
                    'channels' => $notification->channels,
                    'created_at' => $notification->created_at,
                    'sent_at' => $notification->sent_at,
                    'template' => $notification->template ? [
                        'id' => $notification->template->id,
                        'name' => $notification->template->name,
                    ] : null,
                ],
                'delivery_summary' => [
                    'total_recipients' => $totalLogs,
                    'delivered' => $deliveredCount,
                    'failed' => $failedCount,
                    'pending' => $pendingCount,
                    'delivery_rate' => $deliveryRate,
                    'avg_delivery_time_seconds' => round($avgDeliveryTime ?: 0, 2),
                ],
                'by_status' => $logsByStatus->map->count(),
                'by_channel' => $logsByChannel->map->count(),
                'failure_analysis' => [
                    'total_failures' => $failedCount,
                    'failure_rate' => $totalLogs > 0 ? round(($failedCount / $totalLogs) * 100, 2) : 0,
                    'common_reasons' => $failureReasons->take(5),
                ],
                'timeline' => $notification->logs
                    ->whereIn('status', ['sent', 'delivered', 'failed'])
                    ->sortBy('created_at')
                    ->groupBy(function($log) {
                        return $log->created_at->format('Y-m-d H:00');
                    })
                    ->map->count()
                    ->take(24), // Last 24 hours
            ];
            
            return response()->json([
                'success' => true,
                'data' => $report
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate delivery report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get health check for API
     */
    public function health(): JsonResponse
    {
        try {
            $stats = [
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'version' => 'v1',
                'services' => [
                    'database' => 'connected',
                    'queue' => 'operational',
                    'notification_service' => $this->notificationService ? 'available' : 'unavailable',
                ],
                'recent_stats' => [
                    'notifications_last_hour' => Notification::where('created_at', '>=', now()->subHour())->count(),
                    'failed_last_hour' => Notification::where('status', 'failed')
                        ->where('updated_at', '>=', now()->subHour())->count(),
                    'queue_pending' => Notification::whereIn('status', ['queued', 'processing'])->count(),
                ]
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Health check failed',
                'error' => $e->getMessage(),
                'status' => 'unhealthy'
            ], 500);
        }
    } 
}