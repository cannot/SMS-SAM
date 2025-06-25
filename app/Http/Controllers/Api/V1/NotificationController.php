<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService = null)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * List notifications with filters
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:draft,scheduled,queued,processing,sent,failed,cancelled',
            'priority' => 'sometimes|in:low,normal,high,urgent',
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
            $query = Notification::query();

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            $limit = $request->get('limit', 20);
            $notifications = $query->latest()->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => $notifications->items(),
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a single notification
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,teams',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'template_id' => 'sometimes|exists:notification_templates,id',
            'variables' => 'sometimes|array',
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
            $uuid = Str::uuid();
            
            $notification = Notification::create([
                'uuid' => $uuid,
                'template_id' => $request->template_id,
                'subject' => $request->subject,
                'body_text' => $request->message,
                'channels' => $request->channels,
                'recipients' => $request->recipients,
                'variables' => $request->variables ?? [],
                'priority' => $request->priority ?? 'normal',
                'status' => $request->scheduled_at ? 'scheduled' : 'queued',
                'scheduled_at' => $request->scheduled_at,
                'total_recipients' => count($request->recipients),
                'api_key_id' => $request->attributes->get('api_key')?->id,
            ]);

            // Process immediately if not scheduled
            if (!$request->scheduled_at && $this->notificationService) {
                $this->notificationService->processNotification($notification);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification created successfully',
                'data' => [
                    'notification_id' => $notification->uuid,
                    'status' => $notification->status,
                    'recipients_count' => $notification->total_recipients,
                    'estimated_delivery' => $notification->scheduled_at ?? now()->addMinutes(2),
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send bulk notifications
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notifications' => 'required|array|min:1|max:20',
            'notifications.*.recipients' => 'required|array|min:1',
            'notifications.*.recipients.*' => 'email',
            'notifications.*.channels' => 'required|array|min:1',
            'notifications.*.channels.*' => 'in:email,teams',
            'notifications.*.subject' => 'required|string|max:255',
            'notifications.*.message' => 'required|string',
            'notifications.*.priority' => 'sometimes|in:low,normal,high,urgent',
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
            
            foreach ($request->notifications as $notifData) {
                $uuid = Str::uuid();
                
                $notification = Notification::create([
                    'uuid' => $uuid,
                    'subject' => $notifData['subject'],
                    'body_text' => $notifData['message'],
                    'channels' => $notifData['channels'],
                    'recipients' => $notifData['recipients'],
                    'priority' => $notifData['priority'] ?? 'normal',
                    'status' => 'queued',
                    'total_recipients' => count($notifData['recipients']),
                    'api_key_id' => $request->attributes->get('api_key')?->id,
                ]);

                $results[] = [
                    'notification_id' => $notification->uuid,
                    'status' => 'queued',
                    'recipients_count' => $notification->total_recipients,
                ];

                // Process if service available
                if ($this->notificationService) {
                    $this->notificationService->processNotification($notification);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk notifications created successfully',
                'data' => $results,
                'summary' => [
                    'total_notifications' => count($results),
                    'total_recipients' => array_sum(array_column($results, 'recipients_count')),
                ]
            ], 201);
        } catch (\Exception $e) {
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
            $notification = Notification::where('uuid', $id)->firstOrFail();
            
            $logs = $notification->logs()->get()->groupBy('status');
            
            return response()->json([
                'success' => true,
                'data' => [
                    'notification_id' => $notification->uuid,
                    'status' => $notification->status,
                    'created_at' => $notification->created_at,
                    'sent_at' => $notification->sent_at,
                    'delivery_stats' => [
                        'total' => $notification->total_recipients,
                        'delivered' => $logs->get('delivered', collect())->count(),
                        'failed' => $logs->get('failed', collect())->count(),
                        'pending' => $logs->get('pending', collect())->count(),
                    ],
                    'channels' => $notification->channels,
                    'priority' => $notification->priority,
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
     * Get notification history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:draft,scheduled,queued,processing,sent,failed,cancelled',
            'days' => 'sometimes|integer|min:1|max:90',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = Notification::query();

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $days = $request->get('days', 30);
            $query->where('created_at', '>=', now()->subDays($days));

            $limit = $request->get('limit', 50);
            $notifications = $query->latest()->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => $notifications->map(function ($notification) {
                    return [
                        'id' => $notification->uuid,
                        'subject' => $notification->subject,
                        'status' => $notification->status,
                        'priority' => $notification->priority,
                        'channels' => $notification->channels,
                        'recipients_count' => $notification->total_recipients,
                        'delivered_count' => $notification->delivered_count,
                        'failed_count' => $notification->failed_count,
                        'created_at' => $notification->created_at,
                        'sent_at' => $notification->sent_at,
                    ];
                })
            ]);
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
            $notification = Notification::where('uuid', $id)->firstOrFail();

            if ($notification->status !== 'scheduled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only scheduled notifications can be cancelled'
                ], 400);
            }

            $notification->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Notification cancelled successfully'
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
            
            $limit = $request->get('limit', 20);
            $notifications = $query->latest()->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data' => $notifications->map(function ($notification) {
                    return [
                        'id' => $notification->uuid,
                        'subject' => $notification->subject,
                        'priority' => $notification->priority,
                        'recipients_count' => $notification->total_recipients,
                        'failure_reason' => $notification->failure_reason,
                        'created_at' => $notification->created_at,
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
     * Get statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_notifications' => Notification::count(),
                'sent_today' => Notification::whereDate('created_at', today())->count(),
                'by_status' => Notification::selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'by_priority' => Notification::selectRaw('priority, count(*) as count')
                    ->groupBy('priority')
                    ->pluck('count', 'priority'),
                'delivery_rate' => [
                    'total_sent' => Notification::where('status', 'sent')->count(),
                    'total_failed' => Notification::where('status', 'failed')->count(),
                ]
            ];

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
            // This would typically be user-specific
            $count = NotificationLog::whereNull('read_at')->count();

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
}