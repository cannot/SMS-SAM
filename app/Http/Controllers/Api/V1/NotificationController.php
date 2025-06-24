<?php
// app/Http/Controllers/Api/V1/NotificationController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send a notification via API
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'sometimes|exists:notification_templates,slug',
            'subject' => 'required_without:template_id|string|max:255',
            'message' => 'required_without:template_id|string',
            'recipients' => 'required|array|min:1|max:500',
            'recipients.*' => 'string|email',
            'channels' => 'sometimes|array|in:email,teams',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'schedule_at' => 'sometimes|date|after:now',
            'data' => 'sometimes|array', // Template variables
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiKey = $request->attributes->get('api_key');
            
            // Check API key permissions
            if (!$apiKey->hasPermission('notifications.send')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            $data = $request->validated();
            
            // Get template if specified
            $template = null;
            if (isset($data['template_id'])) {
                $template = NotificationTemplate::where('slug', $data['template_id'])
                                               ->where('is_active', true)
                                               ->first();
                if (!$template) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Template not found or inactive'
                    ], 404);
                }
            }

            // Prepare notification data
            $notificationData = [
                'template_id' => $template?->id,
                'subject' => $data['subject'] ?? $template?->subject,
                'body_html' => $data['message'] ?? null,
                'body_text' => $data['message'] ?? null,
                'channels' => $data['channels'] ?? ['email'],
                'recipients' => $data['recipients'],
                'variables' => $data['data'] ?? [],
                'priority' => $data['priority'] ?? 'normal',
                'scheduled_at' => isset($data['schedule_at']) ? 
                    \Carbon\Carbon::parse($data['schedule_at']) : null,
                'api_key_id' => $apiKey->id,
            ];

            // Create and schedule notification
            $notification = $this->notificationService->createNotification($notificationData);
            $result = $this->notificationService->scheduleNotification($notification);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'notification_id' => $notification->uuid,
                    'status' => $notification->status,
                    'estimated_delivery' => $notification->scheduled_at ?? now()->addMinutes(2),
                    'recipients_count' => count($data['recipients']),
                    'message' => 'Notification queued successfully'
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to schedule notification',
                    'error' => $notification->failure_reason
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('API Notification Send Error: ' . $e->getMessage(), [
                'api_key_id' => $request->attributes->get('api_key')?->id,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Send bulk notifications
     */
    public function bulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notifications' => 'required|array|min:1|max:100',
            'notifications.*.template_id' => 'sometimes|exists:notification_templates,slug',
            'notifications.*.subject' => 'required_without:notifications.*.template_id|string|max:255',
            'notifications.*.message' => 'required_without:notifications.*.template_id|string',
            'notifications.*.recipients' => 'required|array|min:1|max:100',
            'notifications.*.recipients.*' => 'string|email',
            'notifications.*.channels' => 'sometimes|array|in:email,teams',
            'notifications.*.priority' => 'sometimes|in:low,normal,high,urgent',
            'notifications.*.data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiKey = $request->attributes->get('api_key');
            
            if (!$apiKey->hasPermission('notifications.bulk')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            $results = [];
            $successCount = 0;
            $failureCount = 0;

            foreach ($request->notifications as $index => $notificationData) {
                try {
                    $template = null;
                    if (isset($notificationData['template_id'])) {
                        $template = NotificationTemplate::where('slug', $notificationData['template_id'])
                                                       ->where('is_active', true)
                                                       ->first();
                    }

                    $data = [
                        'template_id' => $template?->id,
                        'subject' => $notificationData['subject'] ?? $template?->subject,
                        'body_html' => $notificationData['message'] ?? null,
                        'body_text' => $notificationData['message'] ?? null,
                        'channels' => $notificationData['channels'] ?? ['email'],
                        'recipients' => $notificationData['recipients'],
                        'variables' => $notificationData['data'] ?? [],
                        'priority' => $notificationData['priority'] ?? 'normal',
                        'api_key_id' => $apiKey->id,
                    ];

                    $notification = $this->notificationService->createNotification($data);
                    $result = $this->notificationService->scheduleNotification($notification);

                    if ($result) {
                        $results[] = [
                            'index' => $index,
                            'success' => true,
                            'notification_id' => $notification->uuid,
                            'recipients_count' => count($notificationData['recipients'])
                        ];
                        $successCount++;
                    } else {
                        $results[] = [
                            'index' => $index,
                            'success' => false,
                            'error' => $notification->failure_reason
                        ];
                        $failureCount++;
                    }

                } catch (\Exception $e) {
                    $results[] = [
                        'index' => $index,
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                    $failureCount++;
                }
            }

            return response()->json([
                'success' => $successCount > 0,
                'message' => "Processed {$successCount} successful, {$failureCount} failed",
                'summary' => [
                    'total' => count($request->notifications),
                    'successful' => $successCount,
                    'failed' => $failureCount
                ],
                'results' => $results
            ], 200);

        } catch (\Exception $e) {
            Log::error('API Bulk Notification Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get notification status
     */
    public function status(Request $request, $notificationId): JsonResponse
    {
        try {
            $apiKey = $request->attributes->get('api_key');
            
            if (!$apiKey->hasPermission('notifications.status')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            $status = $this->notificationService->getNotificationStatus($notificationId);

            if (!$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            return response()->json($status, 200);

        } catch (\Exception $e) {
            Log::error('API Notification Status Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get notification history
     */
    public function history(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'status' => 'sometimes|in:draft,scheduled,queued,processing,sent,failed,cancelled',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'channel' => 'sometimes|in:email,teams',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $apiKey = $request->attributes->get('api_key');
            
            if (!$apiKey->hasPermission('notifications.history')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions'
                ], 403);
            }

            $query = Notification::where('api_key_id', $apiKey->id)
                                ->with(['template:id,name,slug']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('channel')) {
                $query->whereJsonContains('channels', $request->channel);
            }

            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $perPage = $request->get('per_page', 20);
            $notifications = $query->orderBy('created_at', 'desc')
                                 ->paginate($perPage);

            $data = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->uuid,
                    'subject' => $notification->subject,
                    'template' => $notification->template?->name,
                    'channels' => $notification->channels,
                    'priority' => $notification->priority,
                    'status' => $notification->status,
                    'recipients_count' => $notification->total_recipients,
                    'delivered_count' => $notification->delivered_count,
                    'failed_count' => $notification->failed_count,
                    'scheduled_at' => $notification->scheduled_at,
                    'sent_at' => $notification->sent_at,
                    'created_at' => $notification->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('API Notification History Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
}