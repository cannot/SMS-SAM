<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TemplateController extends Controller
{
    /**
     * List available templates
     * GET /api/v1/templates
     */
    public function index(Request $request)
    {
        try {
            $apiKey = $request->apiKey;

            $validator = Validator::make($request->all(), [
                'category' => 'nullable|string|max:50',
                'channel' => 'nullable|in:email,teams',
                'active_only' => 'nullable|boolean',
                'limit' => 'integer|min:1|max:100',
                'offset' => 'integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', $validator->errors(), 422);
            }

            $query = NotificationTemplate::query();

            // Apply filters
            if ($request->category) {
                $query->byCategory($request->category);
            }

            if ($request->channel) {
                $query->supportsChannel($request->channel);
            }

            if ($request->boolean('active_only', true)) {
                $query->active();
            }

            // Pagination
            $limit = $request->limit ?? 20;
            $offset = $request->offset ?? 0;
            
            $total = $query->count();
            $templates = $query->orderBy('name')
                              ->offset($offset)
                              ->limit($limit)
                              ->get();

            // Format response
            $data = $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'slug' => $template->slug,
                    'name' => $template->name,
                    'description' => $template->description,
                    'category' => $template->category,
                    'supported_channels' => $template->supported_channels,
                    'variables' => $template->variables,
                    'default_variables' => $template->default_variables,
                    'priority' => $template->priority,
                    'is_active' => $template->is_active,
                    'version' => $template->version,
                    'created_at' => $template->created_at->toISOString()
                ];
            });

            // Log API usage
            $this->logApiUsage($apiKey, $request, 'list_templates', [
                'total_results' => $total,
                'returned_count' => $templates->count()
            ]);

            return $this->successResponse([
                'templates' => $data,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API template list failed', [
                'error' => $e->getMessage(),
                'api_key_id' => $request->apiKey->id ?? null
            ]);

            return $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Get template details
     * GET /api/v1/templates/{id}
     */
    public function show(Request $request, $templateId)
    {
        try {
            $apiKey = $request->apiKey;

            // Find template by ID or slug
            $template = NotificationTemplate::where('id', $templateId)
                                           ->orWhere('slug', $templateId)
                                           ->active()
                                           ->first();

            if (!$template) {
                return $this->errorResponse('Template not found', [], 404);
            }

            $data = [
                'id' => $template->id,
                'slug' => $template->slug,
                'name' => $template->name,
                'description' => $template->description,
                'category' => $template->category,
                'subject_template' => $template->subject_template,
                'body_html_template' => $template->body_html_template,
                'body_text_template' => $template->body_text_template,
                'variables' => $template->variables,
                'default_variables' => $template->default_variables,
                'supported_channels' => $template->supported_channels,
                'priority' => $template->priority,
                'version' => $template->version,
                'created_at' => $template->created_at->toISOString(),
                'updated_at' => $template->updated_at->toISOString()
            ];

            // Log API usage
            $this->logApiUsage($apiKey, $request, 'get_template', [
                'template_id' => $template->id,
                'template_slug' => $template->slug
            ]);

            return $this->successResponse($data);

        } catch (\Exception $e) {
            Log::error('API template show failed', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
                'api_key_id' => $request->apiKey->id ?? null
            ]);

            return $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Render template with variables
     * POST /api/v1/templates/{id}/render
     */
    public function render(Request $request, $templateId)
    {
        try {
            $apiKey = $request->apiKey;

            $validator = Validator::make($request->all(), [
                'variables' => 'nullable|array',
                'preview_only' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', $validator->errors(), 422);
            }

            // Find template
            $template = NotificationTemplate::where('id', $templateId)
                                           ->orWhere('slug', $templateId)
                                           ->active()
                                           ->first();

            if (!$template) {
                return $this->errorResponse('Template not found', [], 404);
            }

            // Get variables
            $variables = $request->variables ?? [];
            $previewOnly = $request->boolean('preview_only', false);

            // Validate required variables
            $requiredVars = $template->variables ?? [];
            $missingVars = array_diff($requiredVars, array_keys($variables));

            if (!$previewOnly && !empty($missingVars)) {
                return $this->errorResponse('Missing required variables', [
                    'missing_variables' => $missingVars,
                    'required_variables' => $requiredVars
                ], 422);
            }

            // Render template
            if ($previewOnly) {
                $rendered = $template->preview($variables);
            } else {
                $rendered = $template->render($variables);
            }

            $data = [
                'template_id' => $template->id,
                'template_slug' => $template->slug,
                'template_name' => $template->name,
                'rendered' => $rendered,
                'supported_channels' => $template->supported_channels,
                'is_preview' => $previewOnly
            ];

            // Log API usage
            $this->logApiUsage($apiKey, $request, 'render_template', [
                'template_id' => $template->id,
                'variables_count' => count($variables),
                'is_preview' => $previewOnly
            ]);

            return $this->successResponse($data);

        } catch (\Exception $e) {
            Log::error('API template render failed', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
                'api_key_id' => $request->apiKey->id ?? null
            ]);

            return $this->errorResponse('Template rendering failed', [
                'error_details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get template variables information
     * GET /api/v1/templates/{id}/variables
     */
    public function getVariables(Request $request, $templateId)
    {
        try {
            $apiKey = $request->apiKey;

            // Find template
            $template = NotificationTemplate::where('id', $templateId)
                                           ->orWhere('slug', $templateId)
                                           ->active()
                                           ->first();

            if (!$template) {
                return $this->errorResponse('Template not found', [], 404);
            }

            $data = [
                'template_id' => $template->id,
                'template_slug' => $template->slug,
                'template_name' => $template->name,
                'variables' => [
                    'required' => $template->variables ?? [],
                    'default' => $template->default_variables ?? [],
                    'system' => array_keys($template->getSystemVariables()),
                    'descriptions' => NotificationTemplate::getAvailableVariables()
                ]
            ];

            // Log API usage
            $this->logApiUsage($apiKey, $request, 'get_template_variables', [
                'template_id' => $template->id
            ]);

            return $this->successResponse($data);

        } catch (\Exception $e) {
            Log::error('API template variables failed', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
                'api_key_id' => $request->apiKey->id ?? null
            ]);

            return $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Send notification using template
     * POST /api/v1/templates/{id}/send
     */
    public function sendNotification(Request $request, $templateId)
    {
        try {
            $apiKey = $request->apiKey;

            $validator = Validator::make($request->all(), [
                'recipients' => 'required|array|min:1',
                'recipients.*' => 'email',
                'variables' => 'nullable|array',
                'channels' => 'nullable|array',
                'channels.*' => 'in:email,teams',
                'priority' => 'nullable|in:low,medium,normal,high,urgent',
                'scheduled_at' => 'nullable|date|after:now'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', $validator->errors(), 422);
            }

            // Find template
            $template = NotificationTemplate::where('id', $templateId)
                                           ->orWhere('slug', $templateId)
                                           ->active()
                                           ->first();

            if (!$template) {
                return $this->errorResponse('Template not found', [], 404);
            }

            // Validate required variables
            $variables = $request->variables ?? [];
            $requiredVars = $template->variables ?? [];
            $missingVars = array_diff($requiredVars, array_keys($variables));

            if (!empty($missingVars)) {
                return $this->errorResponse('Missing required variables', [
                    'missing_variables' => $missingVars,
                    'required_variables' => $requiredVars
                ], 422);
            }

            // Use template's supported channels if not specified
            $channels = $request->channels ?? $template->supported_channels;
            
            // Render template
            $rendered = $template->render($variables);

            // Prepare notification data
            $notificationData = [
                'template_id' => $template->id,
                'subject' => $rendered['subject'],
                'body_html' => $rendered['body_html'],
                'body_text' => $rendered['body_text'],
                'channels' => $channels,
                'recipients' => $request->recipients,
                'variables' => $variables,
                'priority' => $request->priority ?? $template->priority ?? 'medium',
                'scheduled_at' => $request->scheduled_at ? new \DateTime($request->scheduled_at) : null,
                'api_key_id' => $apiKey->id,
                'created_by' => null
            ];

            // Create and schedule notification
            $notificationService = app(\App\Services\NotificationService::class);
            $notification = $notificationService->createNotification($notificationData);

            if (!$notification) {
                return $this->errorResponse('Failed to create notification', [], 500);
            }

            $scheduled = $notificationService->scheduleNotification($notification);
            
            if (!$scheduled) {
                return $this->errorResponse('Failed to schedule notification', [], 500);
            }

            $data = [
                'notification_id' => $notification->uuid,
                'template_id' => $template->id,
                'template_name' => $template->name,
                'status' => $notification->status,
                'recipients_count' => $notification->total_recipients,
                'channels' => $notification->channels,
                'priority' => $notification->priority,
                'scheduled_at' => $notification->scheduled_at,
                'message' => 'Notification sent successfully using template'
            ];

            // Log API usage
            $this->logApiUsage($apiKey, $request, 'send_notification_with_template', [
                'template_id' => $template->id,
                'notification_id' => $notification->uuid,
                'recipients_count' => count($request->recipients),
                'channels' => $channels
            ]);

            return $this->successResponse($data, 201);

        } catch (\Exception $e) {
            Log::error('API template send notification failed', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
                'api_key_id' => $request->apiKey->id ?? null
            ]);

            return $this->errorResponse('Failed to send notification', [
                'error_details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get template categories
     * GET /api/v1/templates/categories
     */
    public function getCategories(Request $request)
    {
        try {
            $apiKey = $request->apiKey;

            $categories = NotificationTemplate::getCategories();
            
            // Get template count per category
            $categoryCounts = NotificationTemplate::active()
                ->selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category');

            $data = [];
            foreach ($categories as $key => $name) {
                $data[] = [
                    'key' => $key,
                    'name' => $name,
                    'template_count' => $categoryCounts[$key] ?? 0
                ];
            }

            // Log API usage
            $this->logApiUsage($apiKey, $request, 'get_template_categories', []);

            return $this->successResponse([
                'categories' => $data,
                'total_categories' => count($categories)
            ]);

        } catch (\Exception $e) {
            Log::error('API template categories failed', [
                'error' => $e->getMessage(),
                'api_key_id' => $request->apiKey->id ?? null
            ]);

            return $this->errorResponse('Internal server error', [], 500);
        }
    }

    /**
     * Log API usage
     */
    private function logApiUsage($apiKey, $request, $action, $metadata = [])
    {
        try {
            \App\Models\ApiUsageLog::create([
                'api_key_id' => $apiKey->id,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'action' => $action,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_size' => strlen($request->getContent()),
                'metadata' => $metadata,
                'created_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log API usage: ' . $e->getMessage());
        }
    }

    /**
     * Success response format
     */
    private function successResponse($data, $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ], $statusCode);
    }

    /**
     * Error response format
     */
    private function errorResponse($message, $errors = [], $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toISOString()
        ], $statusCode);
    }
}