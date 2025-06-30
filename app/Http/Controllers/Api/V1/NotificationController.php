<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationGroup;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
            'status'      => 'sometimes|in:draft,scheduled,queued,processing,sent,failed,cancelled',
            'priority'    => 'sometimes|in:low,normal,high,urgent',
            'template_id' => 'sometimes|exists:notification_templates,id',
            'channel'     => 'sometimes|in:email,teams,webhook',
            'limit'       => 'sometimes|integer|min:1|max:100',
            'page'        => 'sometimes|integer|min:1',
            'search'      => 'sometimes|string|max:255',
            'date_from'   => 'sometimes|date',
            'date_to'     => 'sometimes|date',
            'sort_by'     => 'sometimes|in:created_at,updated_at,scheduled_at,priority,status',
            'sort_order'  => 'sometimes|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
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
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'LIKE', "%{$search}%")
                        ->orWhere('body_text', 'LIKE', "%{$search}%")
                        ->orWhere('body_html', 'LIKE', "%{$search}%")
                        ->orWhere('uuid', 'LIKE', "%{$search}%")
                        ->orWhereHas('template', function ($tq) use ($search) {
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
            $sortBy    = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $limit         = $request->get('limit', 20);
            $notifications = $query->paginate($limit);

            return response()->json([
                'success' => true,
                'data'    => $notifications->items()->map(function ($notification) {
                    return [
                        'id'               => $notification->uuid,
                        'template_id'      => $notification->template_id,
                        'template_name'    => $notification->template->name ?? null,
                        'subject'          => $notification->subject,
                        'status'           => $notification->status,
                        'priority'         => $notification->priority,
                        'channels'         => $notification->channels,
                        'recipients_count' => $notification->total_recipients,
                        'delivered_count'  => $notification->delivered_count,
                        'failed_count'     => $notification->failed_count,
                        'scheduled_at'     => $notification->scheduled_at,
                        'created_at'       => $notification->created_at,
                        'sent_at'          => $notification->sent_at,
                    ];
                }),
                'meta'    => [
                    'current_page' => $notifications->currentPage(),
                    'per_page'     => $notifications->perPage(),
                    'total'        => $notifications->total(),
                    'last_page'    => $notifications->lastPage(),
                    'from'         => $notifications->firstItem(),
                    'to'           => $notifications->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('API notifications index failed', [
                'error'   => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a single notification with enhanced features - Updated for unified service
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id'            => 'nullable|exists:notification_templates,id',
            // 'recipient_type' => 'required|in:manual,groups,all_users',
            'recipient_type'         => 'sometimes|in:manual,groups,all_users,mixed',

            // 'recipients' => 'required_if:recipient_type,manual|array',
            // 'recipients.*' => 'email',
            // 'recipient_groups' => 'required_if:recipient_type,groups|array',
            // 'recipient_groups.*' => 'exists:notification_groups,id',
            'recipients'             => 'sometimes|array',
            'recipients.*'           => 'email',
            'recipient_groups'       => 'sometimes|array',
            'recipient_groups.*'     => 'exists:notification_groups,id',
            'include_all_users'      => 'sometimes|boolean',

            'channels'               => 'required|array|min:1',
            'channels.*'             => 'in:email,teams,webhook',
            'subject'                => 'required|string|max:255',
            'message'                => 'nullable|string',
            'body_html'              => 'nullable|string',
            'body_text'              => 'nullable|string',
            'priority'               => 'sometimes|in:low,normal,high,urgent',
            'variables'              => 'sometimes|array',
            'webhook_url'            => 'required_if:channels.*,webhook|url',
            'scheduled_at'           => 'sometimes|date|after:now',
            'save_as_draft'          => 'sometimes|boolean',
            'enable_personalization' => 'sometimes|boolean',

            // เพิ่ม validation สำหรับ attachments
            'attachments'            => 'sometimes|array|max:5', // สูงสุด 5 ไฟล์
            'attachments.*'          => 'file|max:10240', // สูงสุด 10MB ต่อไฟล์
            // หรือใช้ base64 encoding
            'attachments_base64'     => 'sometimes|array|max:5',
            'attachments_base64.*.name' => 'required_with:attachments_base64|string|max:255',
            'attachments_base64.*.data' => 'required_with:attachments_base64|string',
            'attachments_base64.*.mime_type' => 'required_with:attachments_base64|string|in:application/pdf,text/plain,text/csv,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/png',

        ]);

        $validator->after(function ($validator) use ($request) {
            if (($request->has('attachments') || $request->has('attachments_base64')) && 
                !in_array('email', $request->get('channels', []))) {
                $validator->errors()->add('attachments', 'Attachments are only supported for email channel');
            }
        });

        $validator->after(function ($validator) use ($request) {
            $hasRecipients = $request->has('recipients') && ! empty($request->recipients);
            $hasGroups     = $request->has('recipient_groups') && ! empty($request->recipient_groups);
            $hasAllUsers   = $request->get('include_all_users', false);
            $hasWebhook    = in_array('webhook', $request->get('channels', []));

            if (! $hasRecipients && ! $hasGroups && ! $hasAllUsers && ! $hasWebhook) {
                $validator->errors()->add('recipients', 'Must specify at least one recipient type or webhook');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $uuid     = Str::uuid();
            $template = null;

            // Load template if specified
            if ($request->template_id) {
                $template = NotificationTemplate::find($request->template_id);
                if (! $template) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Template not found',
                    ], 404);
                }

                // Check if template supports requested channels
                $unsupportedChannels = array_diff($request->channels, $template->supported_channels ?? []);
                if (! empty($unsupportedChannels)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Template does not support channels: ' . implode(', ', $unsupportedChannels),
                        'errors'  => ['channels' => ['Unsupported channels for selected template']],
                    ], 422);
                }
            }

            // Prepare recipients
            $recipients = $this->prepareRecipients($request);

            // Prepare variables (API style with system variables)
            $systemVariables   = $this->getSystemVariables();
            $templateVariables = [];
            $userVariables     = $request->variables ?? [];

            // Use default_variables from template if available
            if ($template && $template->default_variables) {
                $templateVariables = $template->default_variables;
            }

            // Merge variables by priority
            $baseVariables = array_merge($systemVariables, $templateVariables, $userVariables);

            // Check if personalization is enabled (API feature)
            $enablePersonalization = $request->get('enable_personalization', true);
            $processedContent      = null;

            $processedContent = $this->prepareContentForRecipients($request, $template, $baseVariables, $recipients);

            // ✅ ตรวจสอบว่า processedContent ได้ถูกสร้างถูกต้อง
            Log::info("About to create notification with processed content", [
                'has_processed_content'    => ! empty($processedContent),
                'has_personalized_content' => ! empty($processedContent['personalized_content'] ?? []),
                'personalized_count'       => count($processedContent['personalized_content'] ?? []),
                'processed_content_keys'   => array_keys($processedContent ?? []),
                'sample_personalized'      => ! empty($processedContent['personalized_content']) ?
                array_keys(array_slice($processedContent['personalized_content'], 0, 1, true)) : [],
            ]);

            // Determine status
            $status = 'queued';
            if ($request->save_as_draft) {
                $status = 'draft';
            } elseif ($request->scheduled_at) {
                $status = 'scheduled';
            }

            // Create notification (API style with processed_content)
            $notification = Notification::create([
                'uuid'                          => $uuid,
                'template_id'                   => $request->template_id,
                'subject'                       => $processedContent['subject'] ?? $request->subject,
                'body_html'                     => $processedContent['body_html'] ?? $request->body_html,
                'body_text'                     => $processedContent['body_text'] ?? ($request->body_text ?: $request->message),
                'channels'                      => $request->channels,
                'recipients'                    => $recipients['recipients'],
                'recipient_groups'              => $recipients['recipient_groups'],
                'variables'                     => $baseVariables,
                'webhook_url'                   => $request->webhook_url,
                'priority'                      => $request->priority ?? 'normal',
                'status'                        => $status,
                'scheduled_at'                  => $request->scheduled_at,
                'total_recipients'              => $this->calculateTotalRecipients($recipients, $request->channels),
                'api_key_id'                    => $request->attributes->get('api_key')?->id,
                'processed_content'             => $processedContent, // ✅ IMPORTANT: เก็บ processed_content เสมอ
                'personalized_recipients_count' => count($processedContent['personalized_content'] ?? []),
            ]);

            // Log::info("Notification created, verifying processed content", [
            //     'notification_id'                 => $notification->uuid,
            //     'has_processed_content_in_object' => ! empty($notification->processed_content),
            //     'processed_content_type'          => gettype($notification->processed_content),
            //     'processed_content_keys'          => is_array($notification->processed_content) ?
            //     array_keys($notification->processed_content) : 'not_array',
            //     'personalized_count_in_object'    => count($notification->processed_content['personalized_content'] ?? [])
            // ]);

            // $freshNotification = Notification::find($notification->id);
            // Log::info("Fresh notification from database", [
            //     'notification_id'              => $freshNotification->uuid,
            //     'has_processed_content_in_db'  => ! empty($freshNotification->processed_content),
            //     'processed_content_type_in_db' => gettype($freshNotification->processed_content),
            //     'processed_content_raw'        => $freshNotification->getAttributes()['processed_content'] ?? 'null',
            // ]);

            $attachmentInfo = [];
            if ($request->hasFile('attachments') || $request->has('attachments_base64')) {
                try {
                    $attachments = $notification->processAttachments(
                        $request->file('attachments'),
                        $request->input('attachments_base64')
                    );
                    
                    $attachmentInfo = [
                        'count' => count($attachments),
                        'total_size' => $notification->attachments_size,
                        'files' => array_map(function($att) {
                            return [
                                'name' => $att['name'],
                                'size' => $att['size'],
                                'type' => $att['mime_type']
                            ];
                        }, $attachments)
                    ];

                    Log::info("Attachments processed", [
                        'notification_id' => $notification->uuid,
                        'attachment_count' => count($attachments),
                        'total_size' => $notification->attachments_size
                    ]);

                } catch (\Exception $e) {
                    Log::error('Failed to process attachments', [
                        'notification_id' => $notification->uuid,
                        'error' => $e->getMessage()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to process attachments: ' . $e->getMessage(),
                    ], 400);
                }
            }

            // Process immediately if not scheduled and not draft using unified service
            if (! $request->scheduled_at && ! $request->save_as_draft && $this->notificationService) {
                // The unified service will automatically detect this as API source due to processed_content
                $processResult = $this->notificationService->processNotification($notification);

                if (! $processResult) {
                    Log::warning('API notification processing returned false', [
                        'notification_id' => $notification->id,
                    ]);
                }
            }

            $message = $request->save_as_draft ?
            'Notification draft saved successfully' :
            'Notification created successfully';

            // return response()->json([
            //     'success' => true,
            //     'message' => $message,
            //     'data' => [
            //         'notification_id' => $notification->uuid,
            //         'status' => $notification->status,
            //         'recipients_count' => $notification->total_recipients,
            //         'estimated_delivery' => $notification->scheduled_at ?? now()->addMinutes(2),
            //         'channels' => $notification->channels,
            //         'template_used' => $template ? $template->name : null,
            //         'variables_processed' => count($baseVariables),
            //         'personalization_enabled' => $enablePersonalization,
            //         'personalized_recipients' => $enablePersonalization ? count($processedContent['personalized_content'] ?? []) : 0,
            //         'content_preview' => [
            //             'subject' => substr($processedContent['subject'], 0, 100) . '...',
            //             'body_text_preview' => substr(strip_tags($processedContent['body_text']), 0, 200) . '...',
            //         ]
            //     ]
            // ], 201);

            $responseData = [
                'success' => true,
                'message' => $message,
                'data'    => [
                    'notification_id'         => $notification->uuid,
                    'status'                  => $notification->status,
                    'recipients_count'        => $notification->total_recipients,
                    'estimated_delivery'      => $notification->scheduled_at ?? now()->addMinutes(2),
                    'channels'                => $notification->channels,
                    'template_used'           => $template ? $template->name : null,
                    'variables_processed'     => count($baseVariables),
                    'personalization_enabled' => $enablePersonalization,
                    'personalized_recipients' => $enablePersonalization ? count($processedContent['personalized_content'] ?? []) : 0,
                    'attachments' => $attachmentInfo,
                    'content_preview'         => [
                        'subject'           => $this->cleanUtf8String(substr($processedContent['subject'], 0, 100) . '...'),
                        'body_text_preview' => $this->cleanUtf8String(substr(strip_tags($processedContent['body_text']), 0, 200) . '...'),
                    ]
                ],
            ];

            // ✅ ทำความสะอาด UTF-8 ใน response data
            $cleanResponseData = $this->cleanUtf8Recursively($responseData);

            return response()->json($cleanResponseData, 201, [
                'Content-Type' => 'application/json; charset=utf-8',
            ])->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        } catch (\Exception $e) {
            Log::error('API notification send failed', [
                'error'   => $e->getMessage(),
                'request' => $request->except(['body_html', 'body_text']),
            ]);

            // return response()->json([
            //     'success' => false,
            //     'message' => 'Failed to send notification',
            //     'error' => $e->getMessage()
            // ], 500);

            $errorMessage = $this->cleanUtf8String($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification',
                'error'   => $errorMessage,
            ], 500, [
                'Content-Type' => 'application/json; charset=utf-8',
            ])->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    private function cleanUtf8String(?string $string): ?string
    {
        if ($string === null) {
            return null;
        }

        try {
            // ตรวจสอบและแก้ไข UTF-8 encoding
            if (! mb_check_encoding($string, 'UTF-8')) {
                $string = mb_convert_encoding($string, 'UTF-8', 'auto');
            }

            // ทำความสะอาด invalid UTF-8 sequences
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

            // ลบ control characters ที่อาจทำให้ JSON เสีย
            $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);

            return $string;
        } catch (\Exception $e) {
            Log::warning("Error cleaning UTF-8 string", [
                'error'          => $e->getMessage(),
                'string_preview' => substr($string, 0, 50),
            ]);

            // Fallback: ใช้ filter_var เพื่อลบ invalid characters
            return filter_var($string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
        }
    }

    private function cleanUtf8Recursively($data)
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                $cleanKey           = is_string($key) ? $this->cleanUtf8String($key) : $key;
                $cleaned[$cleanKey] = $this->cleanUtf8Recursively($value);
            }
            return $cleaned;
        } elseif (is_string($data)) {
            return $this->cleanUtf8String($data);
        } else {
            return $data;
        }
    }


    private function prepareContentForRecipients(Request $request, $template, array $baseVariables, array $recipients): array
    {
        try {
            Log::info("prepareContentForRecipients START", [
                'template_id'             => $template ? $template->id : null,
                'base_variables_count'    => count($baseVariables),
                'recipients_from_request' => $recipients,
            ]);

            $allRecipientEmails  = [];
            $personalizedContent = [];
            $processedEmails     = []; // ✅ Track processed emails to prevent duplicates

            // ✅ Collect all recipient emails WITHOUT duplicates
            // 1. Manual recipients
            if (! empty($recipients['recipients'])) {
                foreach ($recipients['recipients'] as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! in_array($email, $processedEmails)) {
                        $allRecipientEmails[] = strtolower($email);
                        $processedEmails[]    = strtolower($email);
                    }
                }
                Log::info("Added manual recipients", [
                    'count'        => count($recipients['recipients']),
                    'unique_count' => count(array_filter($recipients['recipients'], function ($email) use ($processedEmails) {
                        return filter_var($email, FILTER_VALIDATE_EMAIL) && ! in_array($email, $processedEmails);
                    })),
                ]);
            }

            // 2. Group recipients (with duplicate prevention)
            if (! empty($recipients['recipient_groups'])) {
                $groupEmails = $this->getEmailsFromGroups($recipients['recipient_groups']);
                foreach ($groupEmails as $email) {
                    if (! in_array($email, $processedEmails)) {
                        $allRecipientEmails[] = strtolower($email);
                        $processedEmails[]    = strtolower($email);
                    }
                }
                Log::info("Added group recipients", [
                    'total_group_emails' => count($groupEmails),
                    'new_unique_emails'  => count(array_filter($groupEmails, function ($email) use ($processedEmails) {
                        return ! in_array($email, $processedEmails);
                    })),
                    'duplicates_skipped' => count($groupEmails) - count(array_filter($groupEmails, function ($email) use ($processedEmails) {
                        return ! in_array($email, $processedEmails);
                    })),
                ]);
            }

            $allRecipientEmails = array_unique($allRecipientEmails);
            $processedEmails    = array_unique($processedEmails);

            Log::info("Final unique recipients collected", [
                'total_unique_emails' => count($allRecipientEmails),
                'emails'              => $allRecipientEmails,
            ]);

            // Set base content
            $baseSubject  = $request->subject ? $request->subject : ($template ? $template->subject_template : $request->subject);
            $baseBodyHtml = $template ? $template->body_html_template : $request->body_html;
            $baseBodyText = $template ? $template->body_text_template : ($request->body_text ?: $request->message);

            // ✅ สร้าง fallback content โดยใช้ clean base variables
            $cleanBaseVariables = $this->removeRecipientSampleVariables($baseVariables);

            $fallbackContent = [
                'subject'   => $this->replaceVariables($baseSubject, $cleanBaseVariables),
                'body_html' => $this->replaceVariables($baseBodyHtml, $cleanBaseVariables),
                'body_text' => $this->replaceVariables($baseBodyText, $cleanBaseVariables),
            ];

            // ตรวจสอบว่า fallback content ไม่ empty
            if (empty($fallbackContent['subject'])) {
                $fallbackContent['subject'] = $request->subject ?: 'Smart Notification';
            }

            // ✅ สร้าง personalized content สำหรับแต่ละ recipient (NO DUPLICATES)
            foreach ($allRecipientEmails as $email) {
                // ดึงชื่อจริงจาก database
                $recipientName = $this->getRecipientNameFromEmail($email);

                // สร้าง recipient variables ที่สะอาด
                $recipientVariables = array_merge($cleanBaseVariables, [
                    'recipient_email'      => $email,
                    'recipient_name'       => $recipientName,
                    'recipient_first_name' => $this->extractFirstNameFromEmail($email),
                    'recipient_last_name'  => $this->extractLastNameFromEmail($email),
                    'user_name'            => $recipientName,
                    'user_email'           => $email,
                    'user_first_name'      => $this->extractFirstNameFromEmail($email),
                    'user_last_name'       => $this->extractLastNameFromEmail($email),
                ]);

                // Replace variables in content
                $personalizedSubject  = $this->replaceVariables($baseSubject, $recipientVariables);
                $personalizedBodyHtml = $this->replaceVariables($baseBodyHtml, $recipientVariables);
                $personalizedBodyText = $this->replaceVariables($baseBodyText, $recipientVariables);

                // ตรวจสอบ personalized subject
                if (empty($personalizedSubject)) {
                    $personalizedSubject = $fallbackContent['subject'];
                }

                $personalizedContent[$email] = [
                    'subject'   => $personalizedSubject,
                    'body_html' => $personalizedBodyHtml,
                    'body_text' => $personalizedBodyText,
                    'variables' => $recipientVariables,
                ];

                Log::debug("Created personalized content", [
                    'email'           => $email,
                    'recipient_name'  => $recipientName,
                    'subject_preview' => substr($personalizedSubject, 0, 50),
                ]);
            }

            $result = [
                'subject'              => $fallbackContent['subject'],
                'body_html'            => $fallbackContent['body_html'],
                'body_text'            => $fallbackContent['body_text'],
                'personalized_content' => $personalizedContent,
                'base_variables'       => $cleanBaseVariables,
            ];

            // ✅ Final validation
            if (empty($result['subject'])) {
                Log::warning("Empty subject in processed content", [
                    'fallback_subject' => $fallbackContent['subject'],
                    'request_subject'  => $request->subject,
                ]);
                $result['subject'] = $request->subject ?: 'Smart Notification';
            }

            if (empty($result['personalized_content'])) {
                Log::warning("Empty personalized content created", [
                    'recipients_count' => count($allRecipientEmails),
                    'emails'           => $allRecipientEmails,
                ]);
            }

            Log::info("prepareContentForRecipients COMPLETED", [
                'subject_length'          => strlen($result['subject'] ?? ''),
                'personalized_count'      => count($result['personalized_content'] ?? []),
                'unique_emails_processed' => count($allRecipientEmails),
                'personalized_emails'     => array_keys($result['personalized_content'] ?? [])
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error("prepareContentForRecipients FAILED", [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
            ]);
            throw $e;
        }
    }

    private function removeRecipientSampleVariables(array $variables): array
    {
        $cleaned = $variables;

        $recipientKeys = [
            'recipient_name', 'recipient_email', 'recipient_first_name', 'recipient_last_name',
            'user_name', 'user_email', 'user_first_name', 'user_last_name',
        ];

        foreach ($recipientKeys as $key) {
            if (isset($cleaned[$key])) {
                if (empty($cleaned[$key]) ||
                    strpos(strtolower($cleaned[$key]), 'sample') !== false) {
                    unset($cleaned[$key]);
                }
            }
        }

        return $cleaned;
    }

    private function getRecipientNameFromEmail(string $email): string
    {
        try {
            // ดึงจาก database ก่อน
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                $name = $user->display_name ?? $user->name ?? $user->first_name;
                if (! $name && ($user->first_name || $user->last_name)) {
                    $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                }

                if (! empty(trim($name))) {
                    return trim($name);
                }
            }

            // Fallback: extract from email
            return $this->extractNameFromEmail($email);

        } catch (\Exception $e) {
            return $this->extractNameFromEmail($email);
        }
    }

    /**
     * Get emails from groups
     */
    private function getEmailsFromGroups(array $groupIds): array
    {
        if (empty($groupIds)) {
            return [];
        }

        try {
            $emails = [];
            $groups = NotificationGroup::whereIn('id', $groupIds)->with('users')->get();

            foreach ($groups as $group) {
                foreach ($group->users as $user) {
                    if ($user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                        $emails[] = $user->email;
                    }
                }
            }

            return array_unique($emails);
        } catch (\Exception $e) {
            Log::error('Failed to get emails from groups', [
                'groups' => $groupIds,
                'error'  => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * ดึงชื่อจากอีเมล
     */
    private function extractNameFromEmail(string $email): string
    {
        try {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return 'User';
            }

            // ✅ ลองหาจาก database ก่อน
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                $name = $user->name ?? $user->display_name ?? $user->first_name . ' ' . $user->last_name;
                if (! empty(trim($name))) {
                    Log::debug("Found user name from database", [
                        'email' => $email,
                        'name'  => $name,
                    ]);
                    return trim($name);
                }
            }

            // Fallback: extract from email
            $localPart = explode('@', $email)[0];
            $name      = ucwords(str_replace(['.', '_', '-'], ' ', $localPart));

            Log::debug("Extracted name from email", [
                'email' => $email,
                'name'  => $name,
            ]);

            // ตรวจสอบ UTF-8 encoding
            if (! mb_check_encoding($name, 'UTF-8')) {
                $name = mb_convert_encoding($name, 'UTF-8', 'auto');
            }

            return $name;
        } catch (\Exception $e) {
            Log::warning("Error extracting name from email", [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return 'User';
        }
    }

    /**
     * ดึงชื่อแรกจากอีเมล
     */
    private function extractFirstNameFromEmail(string $email): string
    {
        try {
            $name      = $this->extractNameFromEmail($email);
            $parts     = explode(' ', $name);
            $firstName = $parts[0] ?? '';

            // ✅ ตรวจสอบ UTF-8 encoding
            if (! mb_check_encoding($firstName, 'UTF-8')) {
                $firstName = mb_convert_encoding($firstName, 'UTF-8', 'auto');
            }

            return $firstName;
        } catch (\Exception $e) {
            return 'User';
        }
    }
    /**
     * ดึงนามสกุลจากอีเมล
     */
    private function extractLastNameFromEmail(string $email): string
    {
        try {
            $name  = $this->extractNameFromEmail($email);
            $parts = explode(' ', $name);
            if (count($parts) > 1) {
                $lastName = implode(' ', array_slice($parts, 1));

                // ✅ ตรวจสอบ UTF-8 encoding
                if (! mb_check_encoding($lastName, 'UTF-8')) {
                    $lastName = mb_convert_encoding($lastName, 'UTF-8', 'auto');
                }

                return $lastName;
            }
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Get system variables (API context)
     */
    private function getSystemVariables(): array
    {
        return [
            'current_date'      => now()->format('Y-m-d'),
            'current_time'      => now()->format('H:i:s'),
            'current_datetime'  => now()->format('Y-m-d H:i:s'),
            'current_date_thai' => now()->locale('th')->translatedFormat('j F Y'),
            'current_time_12h'  => now()->format('g:i A'),
            'app_name'          => config('app.name', 'Smart Notification'),
            'app_url'           => config('app.url', url('/')),
            'year'              => now()->format('Y'),
            'month'             => now()->format('m'),
            'month_name'        => now()->format('F'),
            'day'               => now()->format('d'),
            'day_name'          => now()->format('l'),
            'company'           => config('app.name', 'Your Company'),
            'api_version'       => 'v1',
            'timestamp'         => now()->timestamp,
            'system_name'       => config('app.name', 'Smart Notification System'),
            'title'             => 'Notification',
            'url'               => config('app.url'),
            'support_email'     => config('mail.support_address', 'support@company.com'),
            'no_reply_email'    => config('mail.from.address', 'noreply@company.com'),

            // API-specific variables
            'greeting_time'     => $this->getGreetingByTime(),
            'quarter'           => 'Q' . ceil(now()->month / 3),
            'week_number'       => now()->weekOfYear,
            'is_weekend'        => now()->isWeekend() ? 'Yes' : 'No',
            'timezone'          => config('app.timezone', 'UTC'),
            'source'            => 'API',
            'created_via'       => 'API Request',

            // ✅ ลบ recipient variables ออกจาก system variables
            // ให้ personalization process เป็นคนเพิ่มเอง
            // 'recipient_name' => '',  // ❌ ลบออก
            // 'recipient_email' => '', // ❌ ลบออก
            // 'user_name' => '',       // ❌ ลบออก
        ];
    }

    /**
     * Create greeting by time
     */
    private function getGreetingByTime(): string
    {
        $hour = now()->hour;

        if ($hour < 12) {
            return 'Good Morning';
        } elseif ($hour < 17) {
            return 'Good Afternoon';
        } else {
            return 'Good Evening';
        }
    }

    private function replaceVariables(?string $content, array $variables): ?string
    {
        if (empty($content)) {
            return $content;
        }

        Log::debug("replaceVariables INPUT", [
            'content_preview'  => substr($content, 0, 200),
            'variables_count'  => count($variables),
            'is_html'          => $this->isHtmlContent($content),
            'sample_variables' => array_slice($variables, 0, 5, true),
        ]);

        // ตรวจสอบ UTF-8 encoding ของ input content
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }

        $processedContent = $content;
        $replacements     = [];
        $isHtml           = $this->isHtmlContent($content);

        foreach ($variables as $key => $value) {
            // จัดการ value types
            if (is_array($value)) {
                $value = implode(', ', $value);
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            } elseif (is_null($value)) {
                $value = '';
            } elseif ($value instanceof \Carbon\Carbon) {
                $value = $value->format('Y-m-d H:i:s');
            }

            // ตรวจสอบ UTF-8 encoding ของ value
            if (is_string($value)) {
                if (! mb_check_encoding($value, 'UTF-8')) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'auto');
                }
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }

            // ตรวจสอบว่า value ไม่เป็น empty
            if (empty($value) && $value !== '0') {
                Log::warning("Empty value for variable", [
                    'variable' => $key,
                    'value'    => $value,
                    'type'     => gettype($value),
                ]);
                $value = $this->getFallbackValue($key);
            }

            // ✅ สำหรับ HTML content - ต้องจัดการ HTML entities
            if ($isHtml) {
                $beforeCount = $this->countHtmlVariableOccurrences($processedContent, $key);
                if ($beforeCount > 0) {
                    $processedContent = $this->replaceHtmlVariables($processedContent, $key, $value);
                    $afterCount       = $this->countHtmlVariableOccurrences($processedContent, $key);

                    $replacements[$key] = [
                        'value'     => $value,
                        'matches'   => $beforeCount,
                        'remaining' => $afterCount,
                        'type'      => 'html',
                    ];

                    Log::debug("HTML Variable replacement", [
                        'variable'          => $key,
                        'value'             => substr($value, 0, 50),
                        'matches_found'     => $beforeCount,
                        'matches_remaining' => $afterCount,
                    ]);
                }
            } else {
                // ✅ สำหรับ Text content - ใช้วิธีเดิม
                $pattern     = '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/u';
                $beforeCount = preg_match_all($pattern, $processedContent);

                if ($beforeCount > 0) {
                    $processedContent = preg_replace($pattern, $value, $processedContent);
                    $afterCount       = preg_match_all($pattern, $processedContent);

                    $replacements[$key] = [
                        'value'     => $value,
                        'matches'   => $beforeCount,
                        'remaining' => $afterCount,
                        'type'      => 'text',
                    ];

                    Log::debug("Text Variable replacement", [
                        'variable'          => $key,
                        'value'             => substr($value, 0, 50),
                        'matches_found'     => $beforeCount,
                        'matches_remaining' => $afterCount,
                    ]);
                }
            }
        }

        Log::debug("replaceVariables RESULT", [
            'replacements_made' => count($replacements),
            'content_preview'   => substr($processedContent, 0, 200),
            'replacements'      => $replacements,
        ]);

        // ตรวจสอบผลลัพธ์ก่อนประมวลผล conditional blocks
        if (! mb_check_encoding($processedContent, 'UTF-8')) {
            $processedContent = mb_convert_encoding($processedContent, 'UTF-8', 'UTF-8');
        }

        // Clean up unreplaced variables
        if ($isHtml) {
            $processedContent = $this->cleanUnresolvedHtmlVariables($processedContent);
        } else {
            $processedContent = preg_replace('/\{\{[^}]+\}\}/u', '', $processedContent);
        }

        return $processedContent;
    }

    private function isHtmlContent(string $content): bool
    {
        // ตรวจสอบ HTML tags ต่างๆ
        return preg_match('/<\s*\w+[^>]*>/', $content) ||
        preg_match('/&[a-zA-Z][a-zA-Z0-9]*;/', $content);
    }

    /**
     * ✅ นับจำนวน variable occurrences ใน HTML content
     */
    private function countHtmlVariableOccurrences(string $content, string $variable): int
    {
        $count = 0;

        // Pattern 1: {{variable}} ปกติ
        $pattern1 = '/\{\{\s*' . preg_quote($variable, '/') . '\s*\}\}/u';
        $count += preg_match_all($pattern1, $content);

        // Pattern 2: HTML encoded {{variable}} เช่น &#123;&#123;variable&#125;&#125;
        $encodedVar = htmlentities('{{' . $variable . '}}', ENT_QUOTES, 'UTF-8');
        if ($encodedVar !== '{{' . $variable . '}}') {
            $count += substr_count($content, $encodedVar);
        }

        // Pattern 3: Partially encoded เช่น {<!-- -->{ variable }<!-- -->}
        $pattern3 = '/\{\s*\{\s*' . preg_quote($variable, '/') . '\s*\}\s*\}/u';
        $count += preg_match_all($pattern3, $content);

        return $count;
    }

    /**
     * ✅ แทนที่ variables ใน HTML content
     */
    private function replaceHtmlVariables(string $content, string $variable, string $value): string
    {
        // Escape value สำหรับ HTML context ถ้าจำเป็น
        $htmlValue = htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);

        // Pattern 1: {{variable}} ปกติ
        $pattern1 = '/\{\{\s*' . preg_quote($variable, '/') . '\s*\}\}/u';
        $content  = preg_replace($pattern1, $value, $content);

        // Pattern 2: HTML encoded {{variable}}
        $encodedVar = htmlentities('{{' . $variable . '}}', ENT_QUOTES, 'UTF-8');
        if ($encodedVar !== '{{' . $variable . '}}') {
            $content = str_replace($encodedVar, $value, $content);
        }

        // Pattern 3: Partially encoded
        $pattern3 = '/\{\s*\{\s*' . preg_quote($variable, '/') . '\s*\}\s*\}/u';
        $content  = preg_replace($pattern3, $value, $content);

        // Pattern 4: Common HTML entity patterns
        $entityPatterns = [
            '&#123;&#123;' . $variable . '&#125;&#125;',
            '&lbrace;&lbrace;' . $variable . '&rbrace;&rbrace;',
            '&#x7B;&#x7B;' . $variable . '&#x7D;&#x7D;',
        ];

        foreach ($entityPatterns as $pattern) {
            $content = str_replace($pattern, $value, $content);
        }

        return $content;
    }

    /**
     * ✅ ทำความสะอาด unresolved variables ใน HTML
     */
    private function cleanUnresolvedHtmlVariables(string $content): string
    {
        // ลบ {{variable}} ปกติ
        $content = preg_replace('/\{\{[^}]+\}\}/u', '', $content);

        // ลบ HTML encoded variables
        $content = preg_replace('/&#123;&#123;[^&]*&#125;&#125;/', '', $content);
        $content = preg_replace('/&lbrace;&lbrace;[^&]*&rbrace;&rbrace;/', '', $content);
        $content = preg_replace('/&#x7B;&#x7B;[^&]*&#x7D;&#x7D;/', '', $content);

        // ลบ partial patterns
        $content = preg_replace('/\{\s*\{[^}]*\}\s*\}/', '', $content);

        return $content;
    }

    /**
     * ✅ Debug method สำหรับ HTML content
     */
    private function debugHtmlContent(string $content, string $variable): array
    {
        $debug = [];

        // Check different patterns
        $patterns = [
            'normal' => '/\{\{\s*' . preg_quote($variable, '/') . '\s*\}\}/u',
            'spaced' => '/\{\s*\{\s*' . preg_quote($variable, '/') . '\s*\}\s*\}/u',
        ];

        foreach ($patterns as $type => $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
            if (! empty($matches[0])) {
                $debug[$type] = [
                    'count'   => count($matches[0]),
                    'matches' => array_slice($matches[0], 0, 3), // Show first 3 matches
                ];
            }
        }

        // Check for HTML entities
        $encodedVar = htmlentities('{{' . $variable . '}}', ENT_QUOTES, 'UTF-8');
        if (strpos($content, $encodedVar) !== false) {
            $debug['html_entities'] = [
                'encoded_pattern' => $encodedVar,
                'count'           => substr_count($content, $encodedVar),
            ];
        }

        return $debug;
    }
    /**
     * Process personalization blocks
     */
    private function processPersonalizationBlocks(string $content, array $variables): string
    {
        try {
            $pattern = '/\{\{#personal\}\}(.*?)\{\{\/personal\}\}/su'; // เพิ่ม 'u' flag

            return preg_replace_callback($pattern, function ($matches) use ($variables) {
                $blockContent = $matches[1];

                // Replace variables in personal block
                return $this->replaceVariables($blockContent, $variables);
            }, $content);
        } catch (\Exception $e) {
            Log::warning("Error processing personalization blocks", [
                'error' => $e->getMessage(),
            ]);
            return $content;
        }
    }

    private function getFallbackValue(string $key): string
    {
        $fallbacks = [
            'recipient_name'       => 'User',
            'recipient_first_name' => 'User',
            'recipient_last_name'  => '',
            'recipient_email'      => 'user@example.com',
            'user_name'            => 'User',
            'user_first_name'      => 'User',
            'user_last_name'       => '',
            'user_email'           => 'user@example.com',
            'system_name'          => 'Smart Notification',
            'title'                => 'Notification',
            'department'           => 'IT',
            'company'              => 'Company',
            'reason'               => 'Notification',
            'message'              => 'Message content',
            'subject'              => 'Notification',
        ];

        return $fallbacks[$key] ?? '[' . $key . ']';
    }

    // ... Keep all other existing API methods unchanged ...

    /**
     * Helper methods for content parsing and variable counting
     */
    private function parseContentToDetails($content, $variables = []): array
    {
        if (empty($content)) {
            return [];
        }

        $details = [];

        // Try JSON decode first
        $jsonData = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            return $jsonData;
        }

        // Parse key: value format
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (strpos($line, ':') !== false) {
                list($key, $value)   = explode(':', $line, 2);
                $details[trim($key)] = trim($value);
            } else {
                // If no : use as line item
                $details['Line ' . (count($details) + 1)] = $line;
            }
        }

        // If no key: value use as single message
        if (empty($details)) {
            $details['Content'] = $content;
        }

        return $details;
    }

    /**
     * Parse body text to details array for Teams
     */
    private function parseBodyTextToDetails($bodyText): array
    {
        return $this->parseContentToDetails($bodyText);
    }

    /**
     * Count variables replaced
     */
    private function countVariablesReplaced($content, $variables): int
    {
        $count = 0;
        foreach ($variables as $key => $value) {
            $pattern = '/\{\{\s*' . preg_quote($key, '/') . '\s*\}\}/';
            $matches = preg_match_all($pattern, $content);
            $count += $matches;
        }
        return $count;
    }

    /**
     * Send bulk notifications with enhanced features
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notifications'                      => 'required|array|min:1|max:20',
            'notifications.*.template_id'        => 'nullable|exists:notification_templates,id',
            'notifications.*.recipient_type'     => 'required|in:manual,groups,all_users',
            'notifications.*.recipients'         => 'required_if:notifications.*.recipient_type,manual|array',
            'notifications.*.recipients.*'       => 'email',
            'notifications.*.recipient_groups'   => 'required_if:notifications.*.recipient_type,groups|array',
            'notifications.*.recipient_groups.*' => 'exists:notification_groups,id',
            'notifications.*.channels'           => 'required|array|min:1',
            'notifications.*.channels.*'         => 'in:email,teams,webhook',
            'notifications.*.subject'            => 'required|string|max:255',
            'notifications.*.message'            => 'nullable|string',
            'notifications.*.body_html'          => 'nullable|string',
            'notifications.*.body_text'          => 'nullable|string',
            'notifications.*.priority'           => 'sometimes|in:low,normal,high,urgent',
            'notifications.*.variables'          => 'sometimes|array',
            'notifications.*.webhook_url'        => 'required_if:notifications.*.channels.*,webhook|url',
            'notifications.*.scheduled_at'       => 'sometimes|date|after:now',
            'notifications.*.save_as_draft'      => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $results         = [];
            $totalRecipients = 0;
            $successCount    = 0;
            $failureCount    = 0;

            foreach ($request->notifications as $index => $notifData) {
                try {
                    $uuid = Str::uuid();

                    // Prepare recipients
                    $recipients = $this->prepareRecipientsFromData($notifData);

                    // Prepare variables
                    $variables = $this->prepareVariablesFromData($notifData);

                    // Replace variables in content
                    $processedSubject  = $this->replaceVariables($notifData['subject'], $variables);
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
                        'uuid'             => $uuid,
                        'template_id'      => $notifData['template_id'] ?? null,
                        'subject'          => $processedSubject,
                        'body_html'        => $processedBodyHtml,
                        'body_text'        => $processedBodyText,
                        'channels'         => $notifData['channels'],
                        'recipients'       => $recipients['recipients'],
                        'recipient_groups' => $recipients['recipient_groups'],
                        'variables'        => $notifData['variables'] ?? [],
                        'webhook_url'      => $notifData['webhook_url'] ?? null,
                        'priority'         => $notifData['priority'] ?? 'normal',
                        'status'           => $status,
                        'scheduled_at'     => $notifData['scheduled_at'] ?? null,
                        'total_recipients' => $recipientCount,
                        'api_key_id'       => $request->attributes->get('api_key')?->id,
                    ]);

                    $results[] = [
                        'index'            => $index,
                        'notification_id'  => $notification->uuid,
                        'status'           => $notification->status,
                        'recipients_count' => $notification->total_recipients,
                        'channels'         => $notification->channels,
                        'success'          => true,
                    ];

                    $totalRecipients += $recipientCount;
                    $successCount++;

                    // Process if service available and not scheduled or draft
                    if (! ($notifData['scheduled_at'] ?? null) && ! ($notifData['save_as_draft'] ?? false) && $this->notificationService) {
                        $this->notificationService->processNotification($notification);
                    }

                } catch (\Exception $e) {
                    Log::error('Bulk notification item failed', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'data'  => $notifData,
                    ]);

                    $results[] = [
                        'index'            => $index,
                        'notification_id'  => null,
                        'status'           => 'failed',
                        'recipients_count' => 0,
                        'channels'         => $notifData['channels'] ?? [],
                        'success'          => false,
                        'error'            => $e->getMessage(),
                    ];

                    $failureCount++;
                }
            }

            return response()->json([
                'success' => $successCount > 0,
                'message' => "Bulk operation completed: {$successCount} succeeded, {$failureCount} failed",
                'data'    => $results,
                'summary' => [
                    'total_notifications'      => count($request->notifications),
                    'successful_notifications' => $successCount,
                    'failed_notifications'     => $failureCount,
                    'total_recipients'         => $totalRecipients,
                ],
            ], $failureCount > 0 ? 207 : 201); // 207 Multi-Status if partial success
        } catch (\Exception $e) {
            Log::error('API bulk notification send failed', [
                'error'         => $e->getMessage(),
                'request_count' => count($request->notifications ?? [])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk notifications',
                'error'   => $e->getMessage(),
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
                'data'    => [
                    'notification_id' => $notification->uuid,
                    'status'          => $notification->status,
                    'priority'        => $notification->priority,
                    'channels'        => $notification->channels,
                    'created_at'      => $notification->created_at,
                    'scheduled_at'    => $notification->scheduled_at,
                    'sent_at'         => $notification->sent_at,
                    'delivery_stats'  => [
                        'total'     => $notification->total_recipients,
                        'delivered' => $logs->get('delivered', collect())->count() + $logs->get('sent', collect())->count(),
                        'failed'    => $logs->get('failed', collect())->count(),
                        'pending'   => $logs->get('pending', collect())->count(),
                    ],
                    'template'        => $notification->template ? [
                        'id'   => $notification->template->id,
                        'name' => $notification->template->name,
                    ] : null,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification status',
                'error'   => $e->getMessage(),
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
                'data'    => [
                    'id'               => $notification->uuid,
                    'template_id'      => $notification->template_id,
                    'template'         => $notification->template ? [
                        'id'          => $notification->template->id,
                        'name'        => $notification->template->name,
                        'description' => $notification->template->description,
                    ] : null,
                    'subject'          => $notification->subject,
                    'body_html'        => $notification->body_html,
                    'body_text'        => $notification->body_text,
                    'status'           => $notification->status,
                    'priority'         => $notification->priority,
                    'channels'         => $notification->channels,
                    'recipients'       => $notification->recipients,
                    'recipient_groups' => $notification->recipient_groups,
                    'variables'        => $notification->variables,
                    'webhook_url'      => $notification->webhook_url,
                    'total_recipients' => $notification->total_recipients,
                    'delivered_count'  => $notification->delivered_count,
                    'failed_count'     => $notification->failed_count,
                    'scheduled_at'     => $notification->scheduled_at,
                    'created_at'       => $notification->created_at,
                    'sent_at'          => $notification->sent_at,
                    'failure_reason'   => $notification->failure_reason,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification details',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status'   => 'sometimes|in:draft,scheduled,queued,processing,sent,failed,cancelled',
            'priority' => 'sometimes|in:low,normal,high,urgent',
            'channel'  => 'sometimes|in:email,teams,webhook',
            'days'     => 'sometimes|integer|min:1|max:90',
            'limit'    => 'sometimes|integer|min:1|max:100',
            'page'     => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
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
                    'data'    => $notifications->items()->map(function ($notification) {
                        return $this->formatNotificationForApi($notification);
                    }),
                    'meta'    => [
                        'current_page' => $notifications->currentPage(),
                        'per_page'     => $notifications->perPage(),
                        'total'        => $notifications->total(),
                        'last_page'    => $notifications->lastPage(),
                    ],
                ]);
            } else {
                $notifications = $query->latest()->limit($limit)->get();

                return response()->json([
                    'success' => true,
                    'data'    => $notifications->map(function ($notification) {
                        return $this->formatNotificationForApi($notification);
                    }),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification history',
                'error'   => $e->getMessage(),
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

            if (! in_array($notification->status, ['scheduled', 'queued', 'draft'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only scheduled, queued, or draft notifications can be cancelled',
                ], 400);
            }

            $notification->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Notification cancelled successfully',
                'data'    => [
                    'notification_id' => $notification->uuid,
                    'status'          => $notification->status,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel notification',
                'error'   => $e->getMessage(),
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

            $limit         = $request->get('limit', 20);
            $notifications = $query->latest()->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data'    => $notifications->map(function ($notification) {
                    return [
                        'id'               => $notification->uuid,
                        'subject'          => $notification->subject,
                        'priority'         => $notification->priority,
                        'channels'         => $notification->channels,
                        'recipients_count' => $notification->total_recipients,
                        'failure_reason'   => $notification->failure_reason,
                        'created_at'       => $notification->created_at,
                        'failed_at'        => $notification->updated_at,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get failed notifications',
                'error'   => $e->getMessage(),
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

            $limit         = $request->get('limit', 20);
            $notifications = $query->orderBy('scheduled_at')->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data'    => $notifications->map(function ($notification) {
                    return [
                        'id'               => $notification->uuid,
                        'subject'          => $notification->subject,
                        'priority'         => $notification->priority,
                        'channels'         => $notification->channels,
                        'recipients_count' => $notification->total_recipients,
                        'scheduled_at'     => $notification->scheduled_at,
                        'created_at'       => $notification->created_at,
                        'time_until_send'  => $notification->scheduled_at ? $notification->scheduled_at->diffForHumans() : null,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get scheduled notifications',
                'error'   => $e->getMessage(),
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
                'sent_today'          => (clone $baseQuery)->whereDate('created_at', today())->count(),
                'sent_this_week'      => (clone $baseQuery)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'sent_this_month'     => (clone $baseQuery)->whereMonth('created_at', now()->month)->count(),
                'by_status'           => (clone $baseQuery)->selectRaw('status, count(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'by_priority'         => (clone $baseQuery)->selectRaw('priority, count(*) as count')
                    ->groupBy('priority')
                    ->pluck('count', 'priority'),
                'by_channel'          => [],
                'delivery_rate'       => [
                    'total_sent'   => (clone $baseQuery)->where('status', 'sent')->count(),
                    'total_failed' => (clone $baseQuery)->where('status', 'failed')->count(),
                ],
                'recent_activity'     => [],
            ];

            // Calculate channel statistics
            $channelStats  = [];
            $notifications = (clone $baseQuery)->whereNotNull('channels')->get(['channels']);
            foreach ($notifications as $notification) {
                foreach ($notification->channels as $channel) {
                    $channelStats[$channel] = ($channelStats[$channel] ?? 0) + 1;
                }
            }
            $stats['by_channel'] = $channelStats;

            // Calculate delivery rate percentage
            $totalDelivered = $stats['delivery_rate']['total_sent'];
            $totalFailed    = $stats['delivery_rate']['total_failed'];
            $totalProcessed = $totalDelivered + $totalFailed;

            $stats['delivery_rate']['percentage'] = $totalProcessed > 0 ? round(($totalDelivered / $totalProcessed) * 100, 2) : 0;

            // Recent activity (last 24 hours)
            $recentActivity = (clone $baseQuery)
                ->where('created_at', '>=', now()->subDay())
                ->selectRaw('DATE_FORMAT(created_at, "%H:00") as hour, count(*) as count')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            $stats['recent_activity'] = $recentActivity->map(function ($item) {
                return [
                    'hour'  => $item->hour,
                    'count' => $item->count,
                ];
            });

            return response()->json([
                'success' => true,
                'data'    => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error'   => $e->getMessage(),
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
                $query->whereHas('notification', function ($q) {
                    $q->where('api_key_id', request()->attributes->get('api_key')->id);
                });
            }

            $count = $query->count();

            return response()->json([
                'success' => true,
                'data'    => [
                    'unread_count' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count',
                'error'   => $e->getMessage(),
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
                    'message' => 'Only failed notifications can be retried',
                ], 400);
            }

            // Reset failed logs to pending
            $failedLogs = $notification->logs()->where('status', 'failed')->get();

            if ($failedLogs->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No failed deliveries found to retry',
                ], 400);
            }

            foreach ($failedLogs as $log) {
                $log->update([
                    'status'        => 'pending',
                    'retry_count'   => 0,
                    'error_message' => null,
                    'next_retry_at' => null,
                ]);
            }

            // Update notification status
            $notification->update([
                'status'         => 'processing',
                'failure_reason' => null,
            ]);

            // Re-queue the notification
            if ($this->notificationService) {
                $this->notificationService->processNotification($notification);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification queued for retry',
                'data'    => [
                    'notification_id' => $notification->uuid,
                    'status'          => $notification->status,
                    'retry_count'     => $failedLogs->count(),
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry notification',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate notification data before sending
     */
    public function validate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id'        => 'nullable|exists:notification_templates,id',
            'recipient_type'     => 'required|in:manual,groups,all_users',
            'recipients'         => 'required_if:recipient_type,manual|array',
            'recipients.*'       => 'email',
            'recipient_groups'   => 'required_if:recipient_type,groups|array',
            'recipient_groups.*' => 'exists:notification_groups,id',
            'channels'           => 'required|array|min:1',
            'channels.*'         => 'in:email,teams,webhook',
            'subject'            => 'required|string|max:255',
            'message'            => 'nullable|string',
            'body_html'          => 'nullable|string',
            'body_text'          => 'nullable|string',
            'priority'           => 'sometimes|in:low,normal,high,urgent',
            'variables'          => 'sometimes|array',
            'webhook_url'        => 'required_if:channels.*,webhook|url',
            'scheduled_at'       => 'sometimes|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            // Calculate estimated recipients
            $recipients      = $this->prepareRecipients($request);
            $totalRecipients = $this->calculateTotalRecipients($recipients, $request->channels);

            // Validate template if provided
            $templateInfo = null;
            if ($request->template_id) {
                $template = NotificationTemplate::find($request->template_id);
                if ($template) {
                    $templateInfo = [
                        'id'                 => $template->id,
                        'name'               => $template->name,
                        'supported_channels' => $template->supported_channels,
                        'required_variables' => $template->variables ?? [],
                    ];

                    // Check if requested channels are supported
                    $unsupportedChannels = array_diff($request->channels, $template->supported_channels);
                    if (! empty($unsupportedChannels)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Template does not support channels: ' . implode(', ', $unsupportedChannels),
                            'errors'  => ['channels' => ['Unsupported channels for selected template']],
                        ], 422);
                    }
                }
            }

            // Validate webhook URL if webhook channel is used
            $webhookValidation = null;
            if (in_array('webhook', $request->channels) && $request->webhook_url) {
                $webhookValidation = [
                    'url'      => $request->webhook_url,
                    'is_valid' => filter_var($request->webhook_url, FILTER_VALIDATE_URL) !== false,
                    'is_https' => str_starts_with($request->webhook_url, 'https://'),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification data is valid',
                'data'    => [
                    'estimated_recipients'    => $totalRecipients,
                    'estimated_cost'          => $this->calculateEstimatedCost($totalRecipients, $request->channels),
                    'estimated_delivery_time' => $this->calculateEstimatedDeliveryTime($request->priority ?? 'normal'),
                    'template_info'           => $templateInfo,
                    'webhook_validation'      => $webhookValidation,
                    'channels_summary'        => [
                        'email'   => in_array('email', $request->channels),
                        'teams'   => in_array('teams', $request->channels),
                        'webhook' => in_array('webhook', $request->channels),
                    ],
                    'variables_count'         => count($request->variables ?? []),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get bulk status for multiple notifications
     */
    public function getBulkStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_ids'   => 'required|array|min:1|max:50',
            'notification_ids.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
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
                    'status'          => $notification->status,
                    'priority'        => $notification->priority,
                    'channels'        => $notification->channels,
                    'created_at'      => $notification->created_at,
                    'sent_at'         => $notification->sent_at,
                    'delivery_stats'  => [
                        'total'     => $notification->total_recipients,
                        'delivered' => $logs->get('delivered', collect())->count() + $logs->get('sent', collect())->count(),
                        'failed'    => $logs->get('failed', collect())->count(),
                        'pending'   => $logs->get('pending', collect())->count(),
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data'    => $results,
                'summary' => [
                    'total_requested' => count($request->notification_ids),
                    'total_found'     => $results->count(),
                    'by_status'       => $results->groupBy('status')->map->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get bulk status',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk cancel notifications
     */
    public function bulkCancel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_ids'   => 'required|array|min:1|max:50',
            'notification_ids.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $query = Notification::whereIn('uuid', $request->notification_ids)
                ->whereIn('status', ['scheduled', 'queued', 'draft']);

            // Filter by API key
            if ($request->attributes->get('api_key')) {
                $query->where('api_key_id', $request->attributes->get('api_key')->id);
            }

            $notifications  = $query->get();
            $cancelledCount = 0;
            $cancelledIds   = [];

            foreach ($notifications as $notification) {
                try {
                    $notification->update(['status' => 'cancelled']);
                    $cancelledCount++;
                    $cancelledIds[] = $notification->uuid;
                } catch (\Exception $e) {
                    Log::error('Failed to cancel notification in bulk', [
                        'notification_id' => $notification->uuid,
                        'error'           => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully cancelled {$cancelledCount} notifications",
                'data'    => [
                    'requested_count' => count($request->notification_ids),
                    'cancelled_count' => $cancelledCount,
                    'eligible_count'  => $notifications->count(),
                    'cancelled_ids'   => $cancelledIds,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel notifications',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk retry failed notifications
     */
    public function bulkRetry(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_ids'   => 'required|array|min:1|max:50',
            'notification_ids.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
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
            $retriedCount  = 0;
            $retriedIds    = [];

            foreach ($notifications as $notification) {
                try {
                    // Reset failed logs
                    $failedLogs = $notification->logs()->where('status', 'failed')->get();

                    foreach ($failedLogs as $log) {
                        $log->update([
                            'status'        => 'pending',
                            'retry_count'   => 0,
                            'error_message' => null,
                            'next_retry_at' => null,
                        ]);
                    }

                    $notification->update([
                        'status'         => 'processing',
                        'failure_reason' => null,
                    ]);

                    if ($this->notificationService) {
                        $this->notificationService->processNotification($notification);
                    }

                    $retriedCount++;
                    $retriedIds[] = $notification->uuid;
                } catch (\Exception $e) {
                    Log::error('Failed to retry notification in bulk', [
                        'notification_id' => $notification->uuid,
                        'error'           => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully queued {$retriedCount} notifications for retry",
                'data'    => [
                    'requested_count' => count($request->notification_ids),
                    'retried_count'   => $retriedCount,
                    'eligible_count'  => $notifications->count(),
                    'retried_ids'     => $retriedIds,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry notifications',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test webhook endpoint
     */
    public function testWebhookOld(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'required|url',
            'subject'     => 'nullable|string|max:255',
            'body_text'   => 'nullable|string',
            'test_data'   => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
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
                            list($key, $value)   = explode(':', $line, 2);
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
                    'Status'    => 'API Test',
                    'Test Time' => now()->format('Y-m-d H:i:s'),
                    'API Key'   => $request->attributes->get('api_key')?->name ?? 'Unknown',
                ];
            } else {
                $details['Test Time'] = now()->format('Y-m-d H:i:s');
                $details['Source']    = 'API Test';
                $details['API Key']   = $request->attributes->get('api_key')?->name ?? 'Unknown';
            }

            // Send message through Teams
            $response = $notification->sendMessageSetWebhook($request->webhook_url, $message, $details);

            return response()->json([
                'success' => true,
                'message' => 'Webhook test successful',
                'data'    => [
                    'webhook_url'   => $request->webhook_url,
                    'message_sent'  => $message,
                    'details_sent'  => $details,
                    'status_code'   => $response->getStatusCode(),
                    'response_time' => now()->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode   = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;

            return response()->json([
                'success' => false,
                'message' => 'Webhook test failed: ' . $e->getMessage(),
                'error'   => [
                    'type'          => 'Request Exception',
                    'status_code'   => $statusCode,
                    'response_body' => $responseBody,
                    'webhook_url'   => $request->webhook_url,
                ],
            ], 400);
        } catch (\Exception $e) {
            Log::error('API webhook test failed', [
                'webhook_url' => $request->webhook_url,
                'error'       => $e->getMessage(),
                'api_key'     => $request->attributes->get('api_key')?->name,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook test failed: ' . $e->getMessage(),
                'error'   => [
                    'type'        => 'General Exception',
                    'webhook_url' => $request->webhook_url,
                ],
            ], 500);
        }
    }

    /**
     * Test webhook endpoint with full template support - Updated for unified service
     */
    public function testWebhook(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'webhook_url'            => 'required|url',
            'template_id'            => 'nullable|exists:notification_templates,id',
            'subject'                => 'nullable|string|max:255',
            'body_text'              => 'nullable|string',
            'body_html'              => 'nullable|string',
            'test_data'              => 'nullable|array',
            'variables'              => 'nullable|array',
            'use_default_variables'  => 'nullable|boolean',
            'enable_personalization' => 'nullable|boolean',     // NEW: Test personalization
            'test_recipients'        => 'nullable|array|max:3', // NEW: Test recipients for personalization
            'test_recipients.*'      => 'email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $notification     = new \Osama\LaravelTeamsNotification\TeamsNotification();
            $template         = null;
            $finalVariables   = [];
            $personalizedTest = $request->get('enable_personalization', false);

            // Load template if provided
            if ($request->template_id) {
                $template = NotificationTemplate::findOrFail($request->template_id);

                // Check if template supports webhook channel
                if (! in_array('webhook', $template->supported_channels ?? [])) {
                    return response()->json([
                        'success'       => false,
                        'message'       => 'Template does not support webhook channel',
                        'template_info' => [
                            'id'                 => $template->id,
                            'name'               => $template->name,
                            'supported_channels' => $template->supported_channels,
                        ],
                    ], 422);
                }

                // Prepare variables
                $systemVariables          = $this->getSystemVariables();
                $templateDefaultVariables = [];

                if ($request->get('use_default_variables', true) && $template->default_variables) {
                    $templateDefaultVariables = $template->default_variables;
                }

                $userVariables = $request->variables ?? [];

                // Merge variables by priority
                $finalVariables = array_merge(
                    $systemVariables,
                    $templateDefaultVariables,
                    $userVariables
                );

                // Handle personalization test
                if ($personalizedTest && $request->has('test_recipients')) {
                    // Test with first recipient for personalization
                    $testEmail      = $request->test_recipients[0];
                    $finalVariables = array_merge($finalVariables, [
                        'recipient_email'      => $testEmail,
                        'recipient_name'       => $this->extractNameFromEmail($testEmail),
                        'recipient_first_name' => $this->extractFirstNameFromEmail($testEmail),
                        'recipient_last_name'  => $this->extractLastNameFromEmail($testEmail),
                        'user_name'            => $this->extractNameFromEmail($testEmail),
                        'user_email'           => $testEmail,
                        'user_first_name'      => $this->extractFirstNameFromEmail($testEmail),
                        'user_last_name'       => $this->extractLastNameFromEmail($testEmail),
                    ]);
                }

                // Replace variables in template
                $processedSubject  = $this->replaceVariables($template->subject_template, $finalVariables);
                $processedBodyHtml = $this->replaceVariables($template->body_html_template, $finalVariables);
                $processedBodyText = $this->replaceVariables($template->body_text_template, $finalVariables);

                $message     = $processedSubject;
                $bodyContent = $processedBodyText ?: strip_tags($processedBodyHtml);

                // Convert body content to details for Teams
                $details = $this->parseContentToDetails($bodyContent, $finalVariables);

            } else {
                // Use data sent directly (legacy method)
                $message = $request->subject ?? "API Webhook Test Notification";

                $details = [];

                if ($request->body_text) {
                    $details = $this->parseBodyTextToDetails($request->body_text);
                } elseif ($request->body_html) {
                    $bodyText = strip_tags($request->body_html);
                    $details  = $this->parseBodyTextToDetails($bodyText);
                } elseif ($request->test_data) {
                    $details = $request->test_data;
                }

                // Use default data if no details
                if (empty($details)) {
                    $details = [
                        'Status'    => 'API Test',
                        'Test Time' => now()->format('Y-m-d H:i:s'),
                        'API Key'   => $request->attributes->get('api_key')?->name ?? 'Unknown',
                    ];
                }
            }

            // Add default info always
            $details = array_merge($details, [
                'Test Time'               => now()->format('Y-m-d H:i:s'),
                'Source'                  => 'API Test',
                'API Key'                 => $request->attributes->get('api_key')?->name ?? 'Unknown',
                'API Version'             => 'v1',
                'Personalization Enabled' => $personalizedTest ? 'Yes' : 'No',
            ]);

            // Add template info if available
            if ($template) {
                $details['Template Used']   = $template->name;
                $details['Template ID']     = $template->id;
                $details['Variables Count'] = count($finalVariables);
                $details['Category']        = $template->category ?? 'General';
            }

            // Add personalization test info
            if ($personalizedTest && $request->has('test_recipients')) {
                $details['Test Recipients']      = implode(', ', $request->test_recipients);
                $details['Personalization Demo'] = 'Using first recipient: ' . $request->test_recipients[0];
            }

            // Send message through Teams
            $response = $notification->sendMessageSetWebhook($request->webhook_url, $message, $details);

            return response()->json([
                'success' => true,
                'message' => 'API webhook test successful',
                'data'    => [
                    'webhook_url'            => $request->webhook_url,
                    'message_sent'           => $message,
                    'details_sent'           => $details,
                    'status_code'            => $response->getStatusCode(),
                    'response_time'          => now()->format('Y-m-d H:i:s'),
                    'personalization_tested' => $personalizedTest,
                    'template_info'          => $template ? [
                        'template_id'              => $template->id,
                        'template_name'            => $template->name,
                        'template_slug'            => $template->slug ?? null,
                        'template_category'        => $template->category ?? null,
                        'supported_channels'       => $template->supported_channels,
                        'available_variables'      => $template->variables ?? [],
                        'default_variables_used'   => $templateDefaultVariables ?? [],
                        'user_variables_provided'  => $userVariables ?? [],
                        'final_variables_used'     => $finalVariables,
                        'variables_replaced_count' => $this->countVariablesReplaced(
                            ($template->subject_template ?? '') . ' ' . ($template->body_text_template ?? ''),
                            $finalVariables
                        ),
                    ] : null,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found',
                'error'   => [
                    'type'        => 'Template Not Found',
                    'template_id' => $request->template_id,
                ],
            ], 404);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode   = $e->getResponse() ? $e->getResponse()->getStatusCode() : null;
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null;

            return response()->json([
                'success' => false,
                'message' => 'API webhook test failed: ' . $e->getMessage(),
                'error'   => [
                    'type'          => 'Request Exception',
                    'status_code'   => $statusCode,
                    'response_body' => substr($responseBody ?? '', 0, 500),
                    'webhook_url'   => $request->webhook_url,
                ],
            ], 400);
        } catch (\Exception $e) {
            Log::error('API webhook test failed', [
                'webhook_url' => $request->webhook_url,
                'template_id' => $request->template_id ?? null,
                'error'       => $e->getMessage(),
                'api_key'     => $request->attributes->get('api_key')?->name,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'API webhook test failed: ' . $e->getMessage(),
                'error'   => [
                    'type'        => 'General Exception',
                    'webhook_url' => $request->webhook_url,
                    'template_id' => $request->template_id ?? null,
                ],
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
                'data'    => $groups->map(function ($group) {
                    return [
                        'id'          => $group->id,
                        'name'        => $group->name,
                        'description' => $group->description,
                        'is_active'   => $group->is_active,
                        'users_count' => $group->users_count,
                        'users'       => $group->users->map(function ($user) {
                            return [
                                'id'    => $user->id,
                                'name'  => $user->name,
                                'email' => $user->email,
                            ];
                        }),
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve groups',
                'error'   => $e->getMessage(),
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
                'data'    => $templates->map(function ($template) {
                    return [
                        'id'                 => $template->id,
                        'name'               => $template->name,
                        'description'        => $template->description,
                        'supported_channels' => $template->supported_channels,
                        'variables'          => $template->variables ?? [],
                        'default_variables'  => $template->default_variables ?? [],
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve templates',
                'error'   => $e->getMessage(),
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
            'variables'   => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $template  = NotificationTemplate::findOrFail($request->template_id);
            $variables = array_merge(
                $this->getSystemVariables(),
                $template->default_variables ?? [],
                $request->variables ?? []
            );

            $rendered = $template->render($variables);

            return response()->json([
                'success' => true,
                'data'    => [
                    'template'       => [
                        'id'                 => $template->id,
                        'name'               => $template->name,
                        'description'        => $template->description,
                        'supported_channels' => $template->supported_channels,
                    ],
                    'variables_used' => $variables,
                    'preview'        => $rendered,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview template',
                'error'   => $e->getMessage(),
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
            $logs  = $logsQuery->orderBy('created_at', 'desc')->limit($limit)->get();

            return response()->json([
                'success' => true,
                'data'    => [
                    'notification_id' => $notification->uuid,
                    'logs'            => $logs->map(function ($log) {
                        return [
                            'id'              => $log->id,
                            'recipient_email' => $log->recipient_email,
                            'recipient_name'  => $log->recipient_name,
                            'channel'         => $log->channel,
                            'status'          => $log->status,
                            'error_message'   => $log->error_message,
                            'retry_count'     => $log->retry_count,
                            'sent_at'         => $log->sent_at,
                            'delivered_at'    => $log->delivered_at,
                            'created_at'      => $log->created_at,
                        ];
                    }),
                    'summary'         => [
                        'total_logs' => $notification->logs()->count(),
                        'delivered'  => $notification->logs()->whereIn('status', ['sent', 'delivered'])->count(),
                        'failed'     => $notification->logs()->where('status', 'failed')->count(),
                        'pending'    => $notification->logs()->where('status', 'pending')->count(),
                    ],
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification logs',
                'error'   => $e->getMessage(),
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
            'subject'     => 'required|string|max:255',
            'body_html'   => 'nullable|string',
            'body_text'   => 'nullable|string',
            'variables'   => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
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
            $processedSubject  = $this->replaceVariables($request->subject, $variables);
            $processedBodyHtml = $this->replaceVariables($request->body_html, $variables);
            $processedBodyText = $this->replaceVariables($request->body_text, $variables);

            return response()->json([
                'success' => true,
                'data'    => [
                    'original'       => [
                        'subject'   => $request->subject,
                        'body_html' => $request->body_html,
                        'body_text' => $request->body_text,
                    ],
                    'processed'      => [
                        'subject'   => $processedSubject,
                        'body_html' => $processedBodyHtml,
                        'body_text' => $processedBodyText,
                    ],
                    'variables_used' => $variables,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to preview content',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================

    /**
     * Prepare recipients based on request data
     */
    // private function prepareRecipients(Request $request): array
    // {
    //     return $this->prepareRecipientsFromData($request->all());
    // }
    private function prepareRecipients1(Request $request): array
    {
        $allRecipients      = [];
        $allRecipientGroups = [];

        // ✅ Manual Recipients
        if ($request->has('recipients') && ! empty($request->recipients)) {
            $allRecipients = array_merge($allRecipients, $request->recipients);
            Log::info("Added manual recipients", ['count' => count($request->recipients)]);
        }

        // ✅ Group Recipients
        if ($request->has('recipient_groups') && ! empty($request->recipient_groups)) {
            $allRecipientGroups = array_merge($allRecipientGroups, $request->recipient_groups);
            Log::info("Added group recipients", ['groups' => $request->recipient_groups]);
        }

        // ✅ All Users
        if ($request->get('include_all_users', false)) {
            $allUserEmails = User::where('is_active', true)
                ->whereNotNull('email')
                ->pluck('email')
                ->toArray();
            $allRecipients = array_merge($allRecipients, $allUserEmails);
            Log::info("Added all users", ['count' => count($allUserEmails)]);
        }

        // ✅ Remove duplicates
        $allRecipients      = array_unique($allRecipients);
        $allRecipientGroups = array_unique($allRecipientGroups);

        Log::info("Mixed recipients prepared", [
            'total_direct_recipients' => count($allRecipients),
            'total_groups'            => count($allRecipientGroups),
        ]);

        return [
            'recipients'       => $allRecipients,
            'recipient_groups' => $allRecipientGroups,
        ];
    }

    private function prepareRecipients(Request $request): array
    {
        $allRecipients      = [];
        $allRecipientGroups = [];
        $allRecipientEmails = []; // ✅ Track emails to prevent duplicates

        // ✅ Manual Recipients
        if ($request->has('recipients') && ! empty($request->recipients)) {
            foreach ($request->recipients as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! in_array($email, $allRecipientEmails)) {
                    $allRecipients[]      = $email;
                    $allRecipientEmails[] = $email;
                }
            }
            Log::info("Added manual recipients", [
                'count'  => count($allRecipients),
                'emails' => $allRecipients,
            ]);
        }

        // ✅ Group Recipients
        if ($request->has('recipient_groups') && ! empty($request->recipient_groups)) {
            $allRecipientGroups = array_merge($allRecipientGroups, $request->recipient_groups);

            // ✅ Pre-check group emails to prevent duplicates with manual recipients
            try {
                $groups = NotificationGroup::whereIn('id', $request->recipient_groups)
                    ->with('users')
                    ->get();

                $groupEmailsCount = 0;
                foreach ($groups as $group) {
                    foreach ($group->users as $user) {
                        if ($user->email &&
                            filter_var($user->email, FILTER_VALIDATE_EMAIL) &&
                            ! in_array($user->email, $allRecipientEmails)) {
                            $allRecipientEmails[] = $user->email;
                            $groupEmailsCount++;
                        }
                    }
                }

                Log::info("Added group recipients", [
                    'groups'                 => $request->recipient_groups,
                    'new_emails_from_groups' => $groupEmailsCount,
                ]);

            } catch (\Exception $e) {
                Log::error("Error processing recipient groups", [
                    'groups' => $request->recipient_groups,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        // ✅ All Users
        if ($request->get('include_all_users', false)) {
            $allUserEmails = User::where('is_active', true)
                ->whereNotNull('email')
                ->whereNotIn('email', $allRecipientEmails) // ✅ Exclude already added emails
                ->pluck('email')
                ->toArray();

            $allRecipients      = array_merge($allRecipients, $allUserEmails);
            $allRecipientEmails = array_merge($allRecipientEmails, $allUserEmails);

            Log::info("Added all users", [
                'count'               => count($allUserEmails),
                'excluded_duplicates' => User::where('is_active', true)
                    ->whereNotNull('email')
                    ->whereIn('email', array_slice($allRecipientEmails, 0, -count($allUserEmails)))
                    ->count(),
            ]);
        }

        // ✅ Final deduplication (safety measure)
        $allRecipients      = array_unique($allRecipients);
        $allRecipientGroups = array_unique($allRecipientGroups);

        Log::info("Final recipients prepared", [
            'total_direct_recipients' => count($allRecipients),
            'total_groups'            => count($allRecipientGroups),
            'total_unique_emails'     => count(array_unique($allRecipientEmails)),
            'has_duplicates_detected' => count($allRecipientEmails) !== count(array_unique($allRecipientEmails)),
        ]);

        return [
            'recipients'       => $allRecipients,
            'recipient_groups' => $allRecipientGroups,
        ];
    }
    /**
     * Prepare recipients from data array
     */
    private function prepareRecipientsFromData(array $data): array
    {
        $recipients      = [];
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
            'recipients'       => $recipients,
            'recipient_groups' => $recipientGroups,
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
        $userVariables   = $data['variables'] ?? [];

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
     * Process conditional blocks {{#if variable}}...{{/if}}
     */
    private function processConditionalBlocks(string $content, array $variables): string
    {
        try {
            $pattern = '/\{\{#if\s+([^}]+)\}\}(.*?)\{\{\/if\}\}/su'; // เพิ่ม 'u' flag

            return preg_replace_callback($pattern, function ($matches) use ($variables) {
                $condition    = trim($matches[1]);
                $blockContent = $matches[2];

                // Check if condition variable exists and is truthy
                if (isset($variables[$condition]) && ! empty($variables[$condition])) {
                    return $this->replaceVariables($blockContent, $variables);
                }

                return ''; // Remove block if condition is false
            }, $content);
        } catch (\Exception $e) {
            Log::warning("Error processing conditional blocks", [
                'error' => $e->getMessage(),
            ]);
            return $content;
        }
    }

    /**
     * Process loop blocks {{#each items}}...{{/each}}
     */
    private function processLoopBlocks(string $content, array $variables): string
    {
        try {
            $pattern = '/\{\{#each\s+([^}]+)\}\}(.*?)\{\{\/each\}\}/su'; // เพิ่ม 'u' flag

            return preg_replace_callback($pattern, function ($matches) use ($variables) {
                $arrayVariable = trim($matches[1]);
                $blockContent  = $matches[2];

                if (! isset($variables[$arrayVariable]) || ! is_array($variables[$arrayVariable])) {
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
        } catch (\Exception $e) {
            Log::warning("Error processing loop blocks", [
                'error' => $e->getMessage(),
            ]);
            return $content;
        }
    }

    /**
     * Calculate total recipients
     */
    // private function calculateTotalRecipients(array $recipients, array $channels): int
    // {
    //     if (in_array('webhook', $channels)) {
    //         return 1; // Webhook counts as 1 recipient
    //     }

    //     $totalEmails = count($recipients['recipients']);

    //     if (!empty($recipients['recipient_groups'])) {
    //         $groupUsers = User::whereHas('notificationGroups', function($query) use ($recipients) {
    //             $query->whereIn('notification_groups.id', $recipients['recipient_groups']);
    //         })->distinct()->count();

    //         $totalEmails += $groupUsers;
    //     }

    //     return $totalEmails;
    // }
    private function calculateTotalRecipientsz(array $recipients, array $channels): int
    {
        // ✅ Webhook ไม่นับเป็น recipient
        if (count($channels) === 1 && in_array('webhook', $channels)) {
            return 1; // Webhook only
        }

        $totalEmails = count($recipients['recipients']);

        // ✅ นับจากกลุ่ม
        if (! empty($recipients['recipient_groups'])) {
            $groupUsers = User::whereHas('notificationGroups', function ($query) use ($recipients) {
                $query->whereIn('notification_groups.id', $recipients['recipient_groups']);
            })->distinct()->count();

            $totalEmails += $groupUsers;
        }

        return $totalEmails;
    }

    private function calculateTotalRecipients(array $recipients, array $channels): int
    {
        // ✅ Webhook ไม่นับเป็น recipient
        if (count($channels) === 1 && in_array('webhook', $channels)) {
            return 1; // Webhook only
        }

        $allEmails = [];

        // ✅ เก็บ manual recipients (no duplicates)
        if (! empty($recipients['recipients'])) {
            foreach ($recipients['recipients'] as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! in_array($email, $allEmails)) {
                    $allEmails[] = $email;
                }
            }
        }

        // ✅ เก็บ group recipients (check duplicates with manual recipients)
        if (! empty($recipients['recipient_groups'])) {
            try {
                $groups = NotificationGroup::whereIn('id', $recipients['recipient_groups'])
                    ->with('users')
                    ->get();

                foreach ($groups as $group) {
                    foreach ($group->users as $user) {
                        if ($user->email &&
                            filter_var($user->email, FILTER_VALIDATE_EMAIL) &&
                            ! in_array($user->email, $allEmails)) {
                            $allEmails[] = $user->email;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error calculating recipients from groups", [
                    'groups' => $recipients['recipient_groups'],
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        $uniqueCount = count($allEmails);

        Log::info("Total recipients calculated", [
            'manual_recipients'   => count($recipients['recipients'] ?? []),
            'recipient_groups'    => count($recipients['recipient_groups'] ?? []),
            'unique_emails_count' => $uniqueCount,
            'channels'            => $channels
        ]);

        return $uniqueCount;
    }

    /**
     * Calculate estimated cost (placeholder)
     */
    private function calculateEstimatedCost(int $recipients, array $channels): array
    {
        $costs = [
            'email'   => 0.001, // $0.001 per email
            'teams'   => 0.002, // $0.002 per Teams message
            'webhook' => 0.001, // $0.001 per webhook call
        ];

        $totalCost = 0;
        $breakdown = [];

        foreach ($channels as $channel) {
            $channelCost         = ($costs[$channel] ?? 0) * ($channel === 'webhook' ? 1 : $recipients);
            $breakdown[$channel] = round($channelCost, 4);
            $totalCost += $channelCost;
        }

        return [
            'total'     => round($totalCost, 4),
            'currency'  => 'USD',
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Calculate estimated delivery time
     */
    private function calculateEstimatedDeliveryTime(string $priority): string
    {
        $delays = [
            'low'    => 10, // 10 minutes
            'normal' => 5,  // 5 minutes
            'high'   => 2,  // 2 minutes
            'urgent' => 1,  // 1 minute
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
            'id'               => $notification->uuid,
            'template_id'      => $notification->template_id,
            'template_name'    => $notification->template->name ?? null,
            'subject'          => $notification->subject,
            'status'           => $notification->status,
            'priority'         => $notification->priority,
            'channels'         => $notification->channels,
            'recipients_count' => $notification->total_recipients,
            'delivered_count'  => $notification->delivered_count,
            'failed_count'     => $notification->failed_count,
            'scheduled_at'     => $notification->scheduled_at,
            'created_at'       => $notification->created_at,
            'sent_at'          => $notification->sent_at,
            'failure_reason'   => $notification->failure_reason,
        ];
    }

    /**
     * Schedule notification for later processing
     */
    public function schedule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'template_id'        => 'nullable|exists:notification_templates,id',
            'recipient_type'     => 'required|in:manual,groups,all_users',
            'recipients'         => 'required_if:recipient_type,manual|array',
            'recipients.*'       => 'email',
            'recipient_groups'   => 'required_if:recipient_type,groups|array',
            'recipient_groups.*' => 'exists:notification_groups,id',
            'channels'           => 'required|array|min:1',
            'channels.*'         => 'in:email,teams,webhook',
            'subject'            => 'required|string|max:255',
            'message'            => 'nullable|string',
            'body_html'          => 'nullable|string',
            'body_text'          => 'nullable|string',
            'priority'           => 'sometimes|in:low,normal,high,urgent',
            'variables'          => 'sometimes|array',
            'webhook_url'        => 'required_if:channels.*,webhook|url',
            'scheduled_at'       => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
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
                'template_id'      => $originalNotification->template_id,
                'recipient_type'   => ! empty($originalNotification->recipient_groups) ? 'groups' :
                (! empty($originalNotification->recipients) ? 'manual' : 'all_users'),
                'recipients'       => $originalNotification->recipients,
                'recipient_groups' => $originalNotification->recipient_groups,
                'channels'         => $originalNotification->channels,
                'subject'          => $originalNotification->subject . ' (Copy)',
                'body_html'        => $originalNotification->body_html,
                'body_text'        => $originalNotification->body_text,
                'priority'         => $originalNotification->priority,
                'variables'        => $originalNotification->variables,
                'webhook_url'      => $originalNotification->webhook_url,
                'save_as_draft'    => true, // Always save duplicates as draft
            ];

            // Create new request with duplicated data
            $newRequest = new Request($newNotificationData);
            $newRequest->attributes->set('api_key', $request->attributes->get('api_key'));

            // Send the duplicated notification
            return $this->send($newRequest);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate notification',
                'error'   => $e->getMessage(),
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
            $logsByStatus  = $notification->logs->groupBy('status');
            $logsByChannel = $notification->logs->groupBy('channel');

            // Calculate delivery metrics
            $totalLogs      = $notification->logs->count();
            $deliveredCount = $logsByStatus->get('sent', collect())->count() +
            $logsByStatus->get('delivered', collect())->count();
            $failedCount  = $logsByStatus->get('failed', collect())->count();
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
                ->filter(function ($log) {
                    return $log->sent_at || $log->delivered_at;
                })
                ->avg(function ($log) {
                    $endTime = $log->delivered_at ?? $log->sent_at;
                    return $endTime ? $endTime->diffInSeconds($log->created_at) : 0;
                });

            $report = [
                'notification'     => [
                    'id'         => $notification->uuid,
                    'subject'    => $notification->subject,
                    'status'     => $notification->status,
                    'priority'   => $notification->priority,
                    'channels'   => $notification->channels,
                    'created_at' => $notification->created_at,
                    'sent_at'    => $notification->sent_at,
                    'template'   => $notification->template ? [
                        'id'   => $notification->template->id,
                        'name' => $notification->template->name,
                    ] : null,
                ],
                'delivery_summary' => [
                    'total_recipients'          => $totalLogs,
                    'delivered'                 => $deliveredCount,
                    'failed'                    => $failedCount,
                    'pending'                   => $pendingCount,
                    'delivery_rate'             => $deliveryRate,
                    'avg_delivery_time_seconds' => round($avgDeliveryTime ?: 0, 2),
                ],
                'by_status'        => $logsByStatus->map->count(),
                'by_channel'       => $logsByChannel->map->count(),
                'failure_analysis' => [
                    'total_failures' => $failedCount,
                    'failure_rate'   => $totalLogs > 0 ? round(($failedCount / $totalLogs) * 100, 2) : 0,
                    'common_reasons' => $failureReasons->take(5),
                ],
                'timeline'         => $notification->logs
                    ->whereIn('status', ['sent', 'delivered', 'failed'])
                    ->sortBy('created_at')
                    ->groupBy(function ($log) {
                        return $log->created_at->format('Y-m-d H:00');
                    })
                    ->map->count()
                    ->take(24), // Last 24 hours
            ];

            return response()->json([
                'success' => true,
                'data'    => $report,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate delivery report',
                'error'   => $e->getMessage(),
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
                'status'       => 'healthy',
                'timestamp'    => now()->toISOString(),
                'version'      => 'v1',
                'services'     => [
                    'database'             => 'connected',
                    'queue'                => 'operational',
                    'notification_service' => $this->notificationService ? 'available' : 'unavailable',
                ],
                'recent_stats' => [
                    'notifications_last_hour' => Notification::where('created_at', '>=', now()->subHour())->count(),
                    'failed_last_hour'        => Notification::where('status', 'failed')
                        ->where('updated_at', '>=', now()->subHour())->count(),
                    'queue_pending'           => Notification::whereIn('status', ['queued', 'processing'])->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data'    => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Health check failed',
                'error'   => $e->getMessage(),
                'status'  => 'unhealthy',
            ], 500);
        }
    }

    /**
     * Create safe JSON response with UTF-8 support
     */
    private function createSafeJsonResponse($data, $statusCode = 200, $message = null): JsonResponse
    {
        try {
            // Clean UTF-8 encoding recursively
            $cleanData = $this->cleanUtf8Recursively($data);

            $response = [
                'success' => $statusCode < 400,
                'data'    => $cleanData,
            ];

            if ($message) {
                $response['message'] = mb_convert_encoding($message, 'UTF-8', 'UTF-8');
            }

            return response()->json($response, $statusCode, [
                'Content-Type' => 'application/json; charset=utf-8',
            ])->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        } catch (\Exception $e) {
            Log::error("Error creating JSON response", [
                'error'     => $e->getMessage(),
                'data_type' => gettype($data),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error'   => 'Failed to encode response',
            ], 500, [
                'Content-Type' => 'application/json; charset=utf-8',
            ]);
        }
    }

    /**
     * Clean UTF-8 encoding recursively
     */
    private function cleanUtf8Recursivelcccy($data)
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                $cleanKey           = is_string($key) ? mb_convert_encoding($key, 'UTF-8', 'UTF-8') : $key;
                $cleaned[$cleanKey] = $this->cleanUtf8Recursively($value);
            }
            return $cleaned;
        } elseif (is_string($data)) {
            // Check and fix UTF-8 encoding
            if (! mb_check_encoding($data, 'UTF-8')) {
                $data = mb_convert_encoding($data, 'UTF-8', 'auto');
            }
            // Clean invalid UTF-8 sequences
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        } else {
            return $data;
        }
    }

}
