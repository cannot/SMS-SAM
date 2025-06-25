<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TemplateController extends Controller
{
    /**
     * List notification templates
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category' => 'sometimes|string|max:50',
            'active_only' => 'sometimes|boolean',
            'search' => 'sometimes|string|min:2|max:50',
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
            $query = NotificationTemplate::query();

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->get('active_only', true)) {
                $query->where('is_active', true);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $limit = $request->get('limit', 50);
            $templates = $query->orderBy('name')->limit($limit)->get();

            $data = $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'description' => $template->description,
                    'slug' => $template->slug,
                    'category' => $template->category,
                    'priority' => $template->priority,
                    'supported_channels' => $template->supported_channels,
                    'variables' => $template->variables ?? [],
                    'is_active' => $template->is_active,
                    'version' => $template->version,
                    'created_at' => $template->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => $templates->count(),
                    'limit' => $limit,
                ]
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
     * Get template details
     */
    public function show($id): JsonResponse
    {
        try {
            $template = NotificationTemplate::findOrFail($id);

            $data = [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'slug' => $template->slug,
                'category' => $template->category,
                'subject_template' => $template->subject_template,
                'body_html_template' => $template->body_html_template,
                'body_text_template' => $template->body_text_template,
                'variables' => $template->variables ?? [],
                'default_variables' => $template->default_variables ?? [],
                'supported_channels' => $template->supported_channels,
                'priority' => $template->priority,
                'is_active' => $template->is_active,
                'version' => $template->version,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get template details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Render template with variables
     */
    public function render($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
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
            $template = NotificationTemplate::findOrFail($id);
            $variables = array_merge(
                $template->default_variables ?? [],
                $request->variables ?? []
            );

            // Simple template rendering (replace {{variable}} with values)
            $subject = $template->subject_template;
            $bodyHtml = $template->body_html_template;
            $bodyText = $template->body_text_template;

            foreach ($variables as $key => $value) {
                $placeholder = "{{" . $key . "}}";
                $subject = str_replace($placeholder, $value, $subject);
                $bodyHtml = str_replace($placeholder, $value, $bodyHtml);
                $bodyText = str_replace($placeholder, $value, $bodyText);
            }

            $rendered = [
                'subject' => $subject,
                'body_html' => $bodyHtml,
                'body_text' => $bodyText,
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'variables_used' => $variables,
                    'rendered' => $rendered,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to render template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview template
     */
    public function preview($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'variables' => 'sometimes|array',
            'sample_data' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template = NotificationTemplate::findOrFail($id);
            
            // Use sample data if requested
            $variables = $request->variables ?? [];
            if ($request->get('sample_data', false)) {
                $sampleData = [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'date' => now()->format('Y-m-d'),
                    'time' => now()->format('H:i'),
                    'title' => 'Sample Notification Title',
                    'message' => 'This is a sample notification message',
                    'department' => 'Information Technology',
                    'company' => 'Your Company Name',
                ];
                $variables = array_merge($sampleData, $variables);
            }

            // Merge with default variables
            $variables = array_merge(
                $template->default_variables ?? [],
                $variables
            );

            // Render the template
            $subject = $template->subject_template;
            $bodyHtml = $template->body_html_template;
            $bodyText = $template->body_text_template;

            foreach ($variables as $key => $value) {
                $placeholder = "{{" . $key . "}}";
                $subject = str_replace($placeholder, $value, $subject);
                $bodyHtml = str_replace($placeholder, $value, $bodyHtml);
                $bodyText = str_replace($placeholder, $value, $bodyText);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'template' => [
                        'id' => $template->id,
                        'name' => $template->name,
                        'category' => $template->category,
                        'supported_channels' => $template->supported_channels,
                    ],
                    'variables' => $variables,
                    'preview' => [
                        'subject' => $subject,
                        'body_html' => $bodyHtml,
                        'body_text' => $bodyText,
                    ],
                    'metadata' => [
                        'character_count' => [
                            'subject' => strlen($subject),
                            'body_text' => strlen($bodyText),
                            'body_html' => strlen($bodyHtml),
                        ],
                        'estimated_size' => [
                            'text' => round(strlen($bodyText) / 1024, 2) . ' KB',
                            'html' => round(strlen($bodyHtml) / 1024, 2) . ' KB',
                        ]
                    ]
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification using template
     */
    public function sendNotification($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'variables' => 'sometimes|array',
            'channels' => 'sometimes|array',
            'channels.*' => 'in:email,teams',
            'priority' => 'sometimes|in:low,normal,high,urgent',
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
            $template = NotificationTemplate::findOrFail($id);
            
            // Use template's supported channels if not specified
            $channels = $request->channels ?? $template->supported_channels;
            
            // Merge variables
            $variables = array_merge(
                $template->default_variables ?? [],
                $request->variables ?? []
            );

            // Render template
            $subject = $template->subject_template;
            $bodyHtml = $template->body_html_template;
            $bodyText = $template->body_text_template;

            foreach ($variables as $key => $value) {
                $placeholder = "{{" . $key . "}}";
                $subject = str_replace($placeholder, $value, $subject);
                $bodyHtml = str_replace($placeholder, $value, $bodyHtml);
                $bodyText = str_replace($placeholder, $value, $bodyText);
            }

            // Create notification
            $notification = \App\Models\Notification::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'template_id' => $template->id,
                'subject' => $subject,
                'body_html' => $bodyHtml,
                'body_text' => $bodyText,
                'channels' => $channels,
                'recipients' => $request->recipients,
                'variables' => $variables,
                'priority' => $request->priority ?? $template->priority,
                'status' => $request->scheduled_at ? 'scheduled' : 'queued',
                'scheduled_at' => $request->scheduled_at,
                'total_recipients' => count($request->recipients),
                'api_key_id' => $request->attributes->get('api_key')?->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification created using template',
                'data' => [
                    'notification_id' => $notification->uuid,
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'status' => $notification->status,
                    'recipients_count' => $notification->total_recipients,
                    'channels' => $notification->channels,
                    'scheduled_at' => $notification->scheduled_at,
                ]
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification using template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get template categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = NotificationTemplate::where('is_active', true)
                                             ->distinct()
                                             ->pluck('category')
                                             ->filter()
                                             ->sort()
                                             ->values();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get template categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate template variables
     */
    public function validateVariables($id, Request $request): JsonResponse
    {
        try {
            $template = NotificationTemplate::findOrFail($id);
            $providedVariables = $request->variables ?? [];
            
            $requiredVariables = $template->variables ?? [];
            $missingVariables = array_diff($requiredVariables, array_keys($providedVariables));
            $extraVariables = array_diff(array_keys($providedVariables), $requiredVariables);

            $validation = [
                'is_valid' => empty($missingVariables),
                'required_variables' => $requiredVariables,
                'provided_variables' => array_keys($providedVariables),
                'missing_variables' => array_values($missingVariables),
                'extra_variables' => array_values($extraVariables),
                'default_variables' => array_keys($template->default_variables ?? []),
            ];

            return response()->json([
                'success' => true,
                'data' => $validation
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate template variables',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}