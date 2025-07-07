<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'template_id',
        'notification_group_id',
        'subject',
        'body_html',
        'body_text',
        'channels',
        'recipients',
        'recipient_groups',
        'variables',
        'attachments',
        'attachment_urls',
        'attachments_size',
        'priority',
        'status',
        'scheduled_at',
        'sent_at',
        'total_recipients',
        'delivered_count',
        'failed_count',
        'failure_reason',
        'api_key_id',
        'created_by',
        'webhook_url',
        'processed_content',
        'personalized_recipients_count',
    ];

    protected $casts = [
        'channels' => 'array',
        'recipients' => 'array',
        'recipient_groups' => 'array',
        'variables' => 'array',
        'attachments' => 'array',
        'attachment_urls' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_recipients' => 'integer',
        'delivered_count' => 'integer',
        'failed_count' => 'integer',
        'processed_content' => 'array',
    ];

    protected $attributes = [
        'status' => 'draft',
        'priority' => 'medium',
        'delivered_count' => 0,
        'failed_count' => 0,
    ];

    // ===============================================
    // RELATIONSHIPS
    // ===============================================

    /**
     * Get the template that owns the notification
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    /**
     * Get the notification group that owns the notification
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(NotificationGroup::class, 'notification_group_id');
    }

    /**
     * Get the API key that was used to create this notification
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }

    /**
     * Get the user who created this notification
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the notification logs for this notification
     */
    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    // ===============================================
    // SCOPES
    // ===============================================

    /**
     * Scope for notifications with specific status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for notifications with specific priority
     */
    public function scopePriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for scheduled notifications
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
                     ->whereNotNull('scheduled_at');
    }

    /**
     * Scope for notifications ready to be sent
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', 'scheduled')
                     ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope for notifications by channel
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->whereJsonContains('channels', $channel);
    }

    /**
     * Scope for notifications created in date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ===============================================
    // METHODS
    // ===============================================

    /**
     * Check if notification is scheduled for future delivery
     */
    public function isScheduled(): bool
    {
        return $this->scheduled_at !== null && $this->scheduled_at->isFuture();
    }

    /**
     * Check if notification is overdue (scheduled but past due)
     */
    public function isOverdue(): bool
    {
        return $this->status === 'scheduled' && 
               $this->scheduled_at !== null && 
               $this->scheduled_at->isPast();
    }

    /**
     * Check if notification can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'queued', 'scheduled']);
    }

    /**
     * Check if notification can be retried
     */
    public function canBeRetried(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get all recipient users from recipients and recipient_groups
     */
    public function getRecipientUsers()
    {
        $users = collect();
        
        // Get users from recipients array (emails)
        if (!empty($this->recipients)) {
            $users = $users->merge(
                User::whereIn('email', $this->recipients)->get()
            );
        }
        
        // Get users from recipient_groups
        if (!empty($this->recipient_groups)) {
            foreach ($this->recipient_groups as $groupId) {
                $group = NotificationGroup::find($groupId);
                if ($group) {
                    $users = $users->merge($group->users);
                }
            }
        }
        
        // Remove duplicates based on email
        return $users->unique('email');
    }

    /**
     * Get delivery statistics
     */
    public function getDeliveryStats(): array
    {
        $total = $this->logs->count();
        $delivered = $this->logs->where('status', 'delivered')->count();
        $failed = $this->logs->where('status', 'failed')->count();
        $pending = $this->logs->where('status', 'pending')->count();

        return [
            'total' => $total,
            'delivered' => $delivered,
            'failed' => $failed,
            'pending' => $pending,
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get channel statistics
     */
    public function getChannelStats(): array
    {
        return $this->logs->groupBy('channel')->map(function ($logs, $channel) {
            $total = $logs->count();
            $delivered = $logs->where('status', 'delivered')->count();
            $failed = $logs->where('status', 'failed')->count();

            return [
                'channel' => $channel,
                'total' => $total,
                'delivered' => $delivered,
                'failed' => $failed,
                'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get priority color for display
     */
    public function getPriorityColor(): string
    {
        switch ($this->priority) {
            case 'low':
                return 'success';
            case 'medium':
                return 'warning';
            case 'normal':
                return 'info';
            case 'high':
                return 'orange';
            case 'urgent':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    /**
     * Get status color for display
     */
    public function getStatusColor(): string
    {
        switch ($this->status) {
            case 'draft':
                return 'secondary';
            case 'queued':
                return 'warning';
            case 'scheduled':
                return 'info';
            case 'processing':
                return 'primary';
            case 'sent':
                return 'success';
            case 'failed':
                return 'danger';
            case 'cancelled':
                return 'dark';
            default:
                return 'secondary';
        }
    }

    /**
     * Get formatted status for display
     */
    public function getStatusText(): string
    {
        switch ($this->status) {
            case 'draft':
                return 'Draft';
            case 'queued':
                return 'Queued';
            case 'scheduled':
                return 'Scheduled';
            case 'processing':
                return 'Processing';
            case 'sent':
                return 'Sent';
            case 'failed':
                return 'Failed';
            case 'cancelled':
                return 'Cancelled';
            default:
                return ucfirst($this->status);
        }
    }

    /**
     * Get formatted priority for display
     */
    public function getPriorityText(): string
    {
        return ucfirst($this->priority);
    }

    /**
     * Get estimated delivery time based on priority
     */
    public function getEstimatedDeliveryTime(): string
    {
        if ($this->isScheduled()) {
            return $this->scheduled_at->format('Y-m-d H:i:s');
        }

        switch ($this->priority) {
            case 'urgent':
                return 'Immediate';
            case 'high':
                return '5 seconds';
            case 'medium':
                return '15 seconds';
            case 'normal':
                return '30 seconds';
            case 'low':
                return '1 minute';
            default:
                return '30 seconds';
        }
    }

    /**
     * Update delivery counters
     */
    public function updateDeliveryCounters(): void
    {
        $stats = $this->getDeliveryStats();
        
        $this->update([
            'delivered_count' => $stats['delivered'],
            'failed_count' => $stats['failed'],
        ]);

        // Update sent_at if all notifications are delivered -- && !$this->sent_at
        if ($stats['pending'] === 0 && $stats['total'] > 0 ) {
            $this->update([
                'status' => $stats['failed'] > 0 ? 'failed' : 'sent',
                'sent_at' => now(),
            ]);
        }else{
            $this->update([
                'status' => 'queued',
            ]);
        }
    }

    /**
     * Cancel the notification
     */
    public function cancel(): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->update(['status' => 'cancelled']);
        
        // Cancel pending logs
        $this->logs()->where('status', 'pending')->update(['status' => 'cancelled']);

        return true;
    }

    /**
     * Mark notification as failed with reason
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Get template variables with defaults
     */
    public function getTemplateVariables(): array
    {
        $variables = $this->variables ?? [];
        
        // Add system variables
        $variables['notification_id'] = $this->uuid;
        $variables['notification_title'] = $this->subject;
        $variables['notification_subject'] = $this->subject;
        $variables['notification_priority'] = $this->getPriorityText();
        $variables['notification_created_at'] = $this->created_at->format('Y-m-d H:i:s');
        $variables['content'] = $this->body_text ?? strip_tags($this->body_html ?? '');
        
        // Add recipient variables for individual rendering
        $variables['recipient_name'] = $variables['recipient_name'] ?? 'Dear User';
        $variables['recipient_email'] = $variables['recipient_email'] ?? '';
        
        if ($this->creator) {
            $variables['created_by_name'] = $this->creator->display_name ?? $this->creator->username;
            $variables['created_by_email'] = $this->creator->email;
        }

        // Add additional system info
        $variables['additional_info'] = $variables['additional_info'] ?? 'No additional information provided.';
        
        return $variables;
    }

    // ===============================================
    // MUTATORS & ACCESSORS
    // ===============================================

    /**
     * Set channels attribute - ensure it's always an array
     */
    public function setChannelsAttribute($value)
    {
        $this->attributes['channels'] = json_encode(is_array($value) ? $value : [$value]);
    }

    /**
     * Set recipients attribute - ensure it's always an array
     */
    public function setRecipientsAttribute($value)
    {
        $this->attributes['recipients'] = json_encode(is_array($value) ? $value : []);
    }

    /**
     * Set recipient_groups attribute - ensure it's always an array
     */
    public function setRecipientGroupsAttribute($value)
    {
        $this->attributes['recipient_groups'] = json_encode(is_array($value) ? $value : []);
    }

    /**
     * Set variables attribute - ensure it's always an array
     */
    public function setVariablesAttribute($value)
    {
        $this->attributes['variables'] = json_encode(is_array($value) ? $value : []);
    }

    public function setProcessedContentAttribute($value)
    {
        // Log::debug("Setting processed_content attribute", [
        //     'value_type' => gettype($value),
        //     'value_is_array' => is_array($value),
        //     'value_keys' => is_array($value) ? array_keys($value) : 'not_array',
        //     'has_personalized_content' => is_array($value) && !empty($value['personalized_content'] ?? [])
        // ]);

        $this->attributes['processed_content'] = json_encode($value);
    }

    // ✅ เพิ่ม accessor เพื่อ debug
    public function getProcessedContentAttribute($value)
    {
        $decoded = json_decode($value, true);
        
        // Log::debug("Getting processed_content attribute", [
        //     'raw_value' => substr($value ?? '', 0, 100),
        //     'decoded_type' => gettype($decoded),
        //     'decoded_is_array' => is_array($decoded),
        //     'has_personalized_content' => is_array($decoded) && !empty($decoded['personalized_content'] ?? [])
        // ]);

        return $decoded;
    }

    public function processAttachments($attachments, $attachmentsBase64 = null)
    {
        $processedAttachments = [];
        $totalSize = 0;

        // จัดการ file uploads
        if ($attachments) {
            foreach ($attachments as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('attachments/' . $this->uuid, $filename, 'local');
                
                $attachment = [
                    'name' => $file->getClientOriginalName(),
                    'filename' => $filename,
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'type' => 'file'
                ];
                
                $processedAttachments[] = $attachment;
                $totalSize += $file->getSize();
            }
        }

        // จัดการ base64 attachments
        if ($attachmentsBase64) {
            foreach ($attachmentsBase64 as $base64File) {
                $filename = time() . '_' . $base64File['name'];
                $data = base64_decode($base64File['data']);
                $path = 'attachments/' . $this->uuid . '/' . $filename;
                
                Storage::disk('local')->put($path, $data);
                
                $attachment = [
                    'name' => $base64File['name'],
                    'filename' => $filename,
                    'path' => $path,
                    'size' => strlen($data),
                    'mime_type' => $base64File['mime_type'],
                    'type' => 'base64'
                ];
                
                $processedAttachments[] = $attachment;
                $totalSize += strlen($data);
            }
        }

        $this->update([
            'attachments' => $processedAttachments,
            'attachments_size' => $totalSize
        ]);

        return $processedAttachments;
    }

    /**
     * จัดการไฟล์แนบทุกประเภท (files, base64, URLs)
     */
    public function processAllAttachmentsz($fileAttachments = null, $base64Attachments = null, $urlAttachments = null)
    {
        $processedAttachments = [];
        $totalSize = 0;

        try {
            // 1. จัดการ file uploads
            if ($fileAttachments) {
                foreach ($fileAttachments as $file) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('attachments/' . $this->uuid, $filename, 'local');
                    
                    $attachment = [
                        'name' => $file->getClientOriginalName(),
                        'filename' => $filename,
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'type' => 'file'
                    ];
                    
                    $processedAttachments[] = $attachment;
                    $totalSize += $file->getSize();
                }
            }

            // 2. จัดการ base64 attachments
            if ($base64Attachments) {
                foreach ($base64Attachments as $base64File) {
                    $filename = time() . '_' . $base64File['name'];
                    $data = base64_decode($base64File['data']);
                    $path = 'attachments/' . $this->uuid . '/' . $filename;
                    
                    Storage::disk('local')->put($path, $data);
                    
                    $attachment = [
                        'name' => $base64File['name'],
                        'filename' => $filename,
                        'path' => $path,
                        'size' => strlen($data),
                        'mime_type' => $base64File['mime_type'],
                        'type' => 'base64'
                    ];
                    
                    $processedAttachments[] = $attachment;
                    $totalSize += strlen($data);
                }
            }

            // 3. จัดการ URL attachments
            if ($urlAttachments) {
                foreach ($urlAttachments as $url) {
                    try {
                        $urlAttachment = $this->downloadAndStoreFromUrl($url);
                        if ($urlAttachment) {
                            $processedAttachments[] = $urlAttachment;
                            $totalSize += $urlAttachment['size'];
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Failed to download URL attachment', [
                            'url' => $url,
                            'notification_id' => $this->uuid,
                            'error' => $e->getMessage()
                        ]);
                        
                        // เก็บ URL ที่ล้มเหลวไว้
                        $processedAttachments[] = [
                            'name' => basename(parse_url($url, PHP_URL_PATH)) ?: 'failed_download',
                            'filename' => null,
                            'path' => null,
                            'size' => 0,
                            'mime_type' => 'application/octet-stream',
                            'type' => 'url_failed',
                            'original_url' => $url,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            // บันทึกข้อมูล
            $this->update([
                'attachments' => $processedAttachments,
                'attachment_urls' => $urlAttachments ?: [],
                'attachments_size' => $totalSize
            ]);

            return $processedAttachments;

        } catch (\Exception $e) {
            \Log::error('Failed to process attachments', [
                'notification_id' => $this->uuid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * ดาวน์โหลดไฟล์จาก URL
     */
    private function downloadAndStoreFromUrlz($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \Exception('Invalid URL format: ' . $url);
        }

        try {
            // ใช้ Guzzle HTTP Client
            $client = new \GuzzleHttp\Client([
                'timeout' => 30,
                'connect_timeout' => 10,
                'verify' => false // สำหรับ localhost testing
            ]);

            $response = $client->get($url, [
                'headers' => [
                    'User-Agent' => 'Smart-Notification-System/1.0'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('HTTP ' . $response->getStatusCode() . ' response');
            }

            $fileData = $response->getBody()->getContents();
            
            if (empty($fileData)) {
                throw new \Exception('Downloaded file is empty');
            }

            $fileSize = strlen($fileData);
            if ($fileSize > 25 * 1024 * 1024) { // 25MB limit
                throw new \Exception('File too large: ' . number_format($fileSize) . ' bytes');
            }

            // กำหนดชื่อไฟล์
            $urlPath = parse_url($url, PHP_URL_PATH);
            $originalName = $urlPath ? basename($urlPath) : 'downloaded_file_' . time();
            
            // ถ้าไม่มี extension ให้เดาจาก Content-Type
            if (strpos($originalName, '.') === false) {
                $contentType = $response->getHeaderLine('Content-Type');
                $extension = $this->getExtensionFromMimeType($contentType);
                if ($extension) {
                    $originalName .= '.' . $extension;
                }
            }

            $filename = time() . '_' . $originalName;
            $path = 'attachments/' . $this->uuid . '/' . $filename;

            // บันทึกไฟล์
            Storage::disk('local')->put($path, $fileData);

            // ตรวจหา MIME type
            $tempFile = tempnam(sys_get_temp_dir(), 'attachment');
            file_put_contents($tempFile, $fileData);
            $mimeType = mime_content_type($tempFile) ?: 'application/octet-stream';
            unlink($tempFile);

            \Log::info('URL attachment downloaded successfully', [
                'url' => $url,
                'filename' => $filename,
                'size' => $fileSize,
                'mime_type' => $mimeType
            ]);

            return [
                'name' => $originalName,
                'filename' => $filename,
                'path' => $path,
                'size' => $fileSize,
                'mime_type' => $mimeType,
                'type' => 'url',
                'original_url' => $url
            ];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \Exception('Failed to download from URL: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception('Error processing URL attachment: ' . $e->getMessage());
        }
    }

    public function processAllAttachments($fileAttachments = null, $base64Attachments = null, $urlAttachments = null)
    {
        $processedAttachments = [];
        $totalSize = 0;

        try {
            Log::info('Starting synchronous attachment processing', [
                'notification_id' => $this->uuid,
                'file_count' => $fileAttachments ? count($fileAttachments) : 0,
                'base64_count' => $base64Attachments ? count($base64Attachments) : 0,
                'url_count' => $urlAttachments ? count($urlAttachments) : 0
            ]);

            // 1. จัดการ file uploads
            if ($fileAttachments) {
                foreach ($fileAttachments as $file) {
                    try {
                        $filename = time() . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs('attachments/' . $this->uuid, $filename, 'local');
                        
                        // ตรวจสอบว่าไฟล์ถูกบันทึกแล้ว
                        $fullPath = storage_path('app/' . $path);
                        if (!file_exists($fullPath)) {
                            throw new \Exception("Failed to save uploaded file: {$filename}");
                        }
                        
                        $attachment = [
                            'name' => $file->getClientOriginalName(),
                            'filename' => $filename,
                            'path' => $path,
                            'size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                            'type' => 'file'
                        ];
                        
                        $processedAttachments[] = $attachment;
                        $totalSize += $file->getSize();
                        
                        Log::info('File attachment processed', [
                            'filename' => $filename,
                            'size' => $file->getSize(),
                            'path' => $path
                        ]);
                        
                    } catch (\Exception $e) {
                        Log::error('Failed to process file attachment', [
                            'filename' => $file->getClientOriginalName(),
                            'error' => $e->getMessage()
                        ]);
                        
                        // เพิ่ม failed attachment
                        $processedAttachments[] = [
                            'name' => $file->getClientOriginalName(),
                            'filename' => null,
                            'path' => null,
                            'size' => 0,
                            'mime_type' => 'application/octet-stream',
                            'type' => 'file_failed',
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            // 2. จัดการ base64 attachments
            if ($base64Attachments) {
                foreach ($base64Attachments as $base64File) {
                    try {
                        Log::info('Processing base64 attachment', [
                            'name' => $base64File['name'],
                            'mime_type' => $base64File['mime_type'] ?? 'unknown',
                            'data_length' => isset($base64File['data']) ? strlen($base64File['data']) : 0
                        ]);
    
                        // ตรวจสอบข้อมูล base64
                        if (empty($base64File['data'])) {
                            throw new \Exception('Base64 data is empty');
                        }
    
                        if (empty($base64File['name'])) {
                            throw new \Exception('Filename is required');
                        }
    
                        // ทำการ decode base64
                        $data = base64_decode($base64File['data'], true);
                        if ($data === false) {
                            throw new \Exception('Invalid base64 data');
                        }
    
                        $fileSize = strlen($data);
                        if ($fileSize === 0) {
                            throw new \Exception('Decoded file is empty');
                        }
    
                        // ตรวจสอบขนาดไฟล์
                        $maxSize = 10 * 1024 * 1024; // 10MB limit
                        if ($fileSize > $maxSize) {
                            throw new \Exception('File too large: ' . number_format($fileSize / 1024 / 1024, 2) . ' MB');
                        }
    
                        $filename = $base64File['name'];
                        $path = 'attachments/' . $this->uuid . '/' . $filename;
                        $fullPath = storage_path('app/' . $path);
    
                        // สร้าง directory ถ้าไม่มี
                        $directory = dirname($fullPath);
                        if (!is_dir($directory)) {
                            if (!mkdir($directory, 0755, true)) {
                                throw new \Exception("Failed to create directory: {$directory}");
                            }
                        }
    
                        // บันทึกไฟล์ด้วย file_put_contents
                        $written = file_put_contents($fullPath, $data);
                        if ($written === false) {
                            throw new \Exception("Failed to write file: {$fullPath}");
                        }
    
                        // ตรวจสอบว่าไฟล์ถูกบันทึกแล้ว
                        if (!file_exists($fullPath)) {
                            throw new \Exception("File not found after write: {$fullPath}");
                        }
    
                        $actualSize = filesize($fullPath);
                        if ($actualSize !== $fileSize) {
                            Log::warning('File size mismatch after write', [
                                'expected' => $fileSize,
                                'actual' => $actualSize,
                                'path' => $fullPath
                            ]);
                        }
    
                        $attachment = [
                            'name' => $base64File['name'],
                            'filename' => $filename,
                            'path' => $path,
                            'size' => $actualSize,
                            'mime_type' => $base64File['mime_type'] ?? 'application/octet-stream',
                            'type' => 'base64'
                        ];
                        
                        $processedAttachments[] = $attachment;
                        $totalSize += $actualSize;
                        
                        Log::info('Base64 attachment processed successfully', [
                            'filename' => $filename,
                            'size' => $actualSize,
                            'path' => $path,
                            'full_path' => $fullPath
                        ]);
                        
                    } catch (\Exception $e) {
                        Log::error('Failed to process base64 attachment', [
                            'filename' => $base64File['name'] ?? 'unknown',
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        $processedAttachments[] = [
                            'name' => $base64File['name'] ?? 'unknown',
                            'filename' => null,
                            'path' => null,
                            'size' => 0,
                            'mime_type' => 'application/octet-stream',
                            'type' => 'base64_failed',
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            // 3. 🔧 **แก้ไขหลัก: จัดการ URL attachments แบบ synchronous**
            if ($urlAttachments) {
                foreach ($urlAttachments as $url) {
                    try {
                        Log::info('Starting URL download (synchronous)', [
                            'url' => $url,
                            'notification_id' => $this->uuid
                        ]);
                        
                        // ⚠️ สำคัญ: รอให้ดาวน์โหลดเสร็จก่อน
                        $urlAttachment = $this->downloadAndStoreFromUrl($url);
                        
                        if ($urlAttachment) {
                            // ตรวจสอบว่าไฟล์ถูกบันทึกแล้วจริง
                            $fullPath = storage_path('app/' . $urlAttachment['path']);
                            if (!file_exists($fullPath)) {
                                throw new \Exception("Downloaded file not found at: {$fullPath}");
                            }
                            
                            $actualSize = filesize($fullPath);
                            if ($actualSize !== $urlAttachment['size']) {
                                Log::warning('File size mismatch after download', [
                                    'expected' => $urlAttachment['size'],
                                    'actual' => $actualSize,
                                    'path' => $fullPath
                                ]);
                            }
                            
                            $processedAttachments[] = $urlAttachment;
                            $totalSize += $urlAttachment['size'];
                            
                            Log::info('URL attachment downloaded successfully', [
                                'url' => $url,
                                'filename' => $urlAttachment['filename'],
                                'size' => $urlAttachment['size'],
                                'path' => $urlAttachment['path'],
                                'file_exists' => file_exists($fullPath),
                                'actual_size' => $actualSize
                            ]);
                        } else {
                            throw new \Exception('downloadAndStoreFromUrl returned null');
                        }
                        
                    } catch (\Exception $e) {
                        Log::error('Failed to download URL attachment', [
                            'url' => $url,
                            'notification_id' => $this->uuid,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        
                        // เก็บ URL ที่ล้มเหลวไว้
                        $processedAttachments[] = [
                            'name' => basename(parse_url($url, PHP_URL_PATH)) ?: 'downloaded_file',
                            'filename' => null,
                            'path' => null,
                            'size' => 0,
                            'mime_type' => 'application/octet-stream',
                            'type' => 'url_failed',
                            'original_url' => $url,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            // บันทึกข้อมูล attachments
            $this->update([
                'attachments' => $processedAttachments,
                'attachments_size' => $totalSize
            ]);

            $successCount = count(array_filter($processedAttachments, function($att) {
                return !in_array($att['type'], ['url_failed', 'file_failed', 'base64_failed']);
            }));

            Log::info('Attachment processing completed', [
                'notification_id' => $this->uuid,
                'total_attachments' => count($processedAttachments),
                'successful_attachments' => $successCount,
                'failed_attachments' => count($processedAttachments) - $successCount,
                'total_size' => $totalSize
            ]);

            return $processedAttachments;

        } catch (\Exception $e) {
            Log::error('Failed to process attachments', [
                'notification_id' => $this->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * แก้ไข downloadAndStoreFromUrl ให้ timeout เหมาะสม
     */
    private function downloadAndStoreFromUrl($url)
    {
        try {
            Log::info('Starting URL download', [
                'url' => $url,
                'notification_id' => $this->uuid
            ]);

            // ตรวจสอบ URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \Exception('Invalid URL format');
            }

            // ดาวน์โหลดไฟล์พร้อม timeout ที่เหมาะสม
            $client = new \GuzzleHttp\Client([
                'timeout' => 60, // เพิ่มเป็น 60 วินาที
                'connect_timeout' => 10,
                'verify' => false,
                'headers' => [
                    'User-Agent' => 'Smart-Notification-System/1.0'
                ]
            ]);

            $response = $client->get($url);
            
            if ($response->getStatusCode() !== 200) {
                throw new \Exception("HTTP error: " . $response->getStatusCode());
            }

            $fileData = $response->getBody()->getContents();

            if (empty($fileData)) {
                throw new \Exception('Downloaded file is empty');
            }

            // ตรวจสอบขนาดไฟล์
            $fileSize = strlen($fileData);
            if ($fileSize > 10 * 1024 * 1024) { // 10MB limit
                throw new \Exception('File too large: ' . number_format($fileSize / 1024 / 1024, 2) . ' MB');
            }

            // สร้างชื่อไฟล์
            $urlPath = parse_url($url, PHP_URL_PATH);
            $originalName = $urlPath ? basename($urlPath) : 'downloaded_file_' . time();

            // ถ้าไม่มี extension ให้เดาจาก Content-Type
            if (strpos($originalName, '.') === false) {
                $contentType = $response->getHeaderLine('Content-Type');
                $extension = $this->getExtensionFromMimeType($contentType);
                if ($extension) {
                    $originalName .= '.' . $extension;
                }
            }

            $filename = time() . '_' . $originalName;
            $relativePath = 'attachments/' . $this->uuid . '/' . $filename;

            Log::info('Preparing to save file', [
                'original_name' => $originalName,
                'filename' => $filename,
                'relative_path' => $relativePath,
                'file_size' => $fileSize
            ]);

            $fullPath = storage_path('app/' . $relativePath);
            $directory = dirname($fullPath);

            Log::info('Directory info', [
                'full_path' => $fullPath,
                'directory' => $directory,
                'directory_exists' => is_dir($directory),
                'storage_path' => storage_path('app'),
                'is_writable' => is_writable(storage_path('app'))
            ]);

            // สร้าง directory ถ้าไม่มี
            if (!is_dir($directory)) {
                $created = mkdir($directory, 0755, true);
                Log::info('Directory creation', [
                    'directory' => $directory,
                    'created' => $created,
                    'exists_after' => is_dir($directory)
                ]);
                
                if (!$created || !is_dir($directory)) {
                    throw new \Exception("Failed to create directory: {$directory}");
                }
            }

            // ตรวจสอบสิทธิ์เขียน
            if (!is_writable($directory)) {
                // พยายามเปลี่ยน permission
                chmod($directory, 0755);
                
                if (!is_writable($directory)) {
                    throw new \Exception("Directory not writable: {$directory}");
                }
            }

            // 🔧 **ใช้ file_put_contents แทน Storage::put เพื่อ debug**
            $written = file_put_contents($fullPath, $fileData);
            
            Log::info('File write result', [
                'full_path' => $fullPath,
                'bytes_written' => $written,
                'expected_size' => $fileSize,
                'file_exists' => file_exists($fullPath),
                'actual_size' => file_exists($fullPath) ? filesize($fullPath) : 0
            ]);

            if ($written === false) {
                throw new \Exception("Failed to write file to: {$fullPath}");
            }

            if (!file_exists($fullPath)) {
                throw new \Exception("File not found after write: {$fullPath}");
            }

            $actualSize = filesize($fullPath);
            if ($actualSize !== $fileSize) {
                Log::warning('File size mismatch', [
                    'expected' => $fileSize,
                    'actual' => $actualSize,
                    'path' => $fullPath
                ]);
            }

            // ตรวจหา MIME type
            $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';

            $result = [
                'name' => $originalName,
                'filename' => $filename,
                'path' => $relativePath, // บันทึก relative path
                'size' => $actualSize,
                'mime_type' => $mimeType,
                'type' => 'url',
                'original_url' => $url
            ];

            Log::info('URL attachment saved successfully', $result);

            return $result;

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMsg = 'HTTP request failed: ' . $e->getMessage();
            Log::error('HTTP request error', [
                'url' => $url,
                'error' => $errorMsg,
                'has_response' => $e->hasResponse(),
                'response_code' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null
            ]);
            throw new \Exception($errorMsg);
            
        } catch (\Exception $e) {
            $errorMsg = 'File processing error: ' . $e->getMessage();
            Log::error('File processing error', [
                'url' => $url,
                'error' => $errorMsg,
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception($errorMsg);
        }
    }

    public function testStorageWrite()
    {
        try {
            $testPath = 'attachments/test_' . time() . '.txt';
            $testContent = 'Test file content ' . date('Y-m-d H:i:s');
            
            // ทดสอบด้วย Storage facade
            $storagePutResult = Storage::disk('local')->put($testPath, $testContent);
            
            // ทดสอบด้วย file_put_contents
            $fullPath = storage_path('app/' . $testPath);
            $filePutResult = file_put_contents($fullPath . '_direct', $testContent);
            
            Log::info('Storage test results', [
                'storage_put_result' => $storagePutResult,
                'file_put_result' => $filePutResult,
                'storage_file_exists' => Storage::disk('local')->exists($testPath),
                'direct_file_exists' => file_exists($fullPath . '_direct'),
                'storage_path' => storage_path('app'),
                'test_path' => $testPath,
                'full_path' => $fullPath
            ]);
            
            // ลบไฟล์ทดสอบ
            if (Storage::disk('local')->exists($testPath)) {
                Storage::disk('local')->delete($testPath);
            }
            if (file_exists($fullPath . '_direct')) {
                unlink($fullPath . '_direct');
            }
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Storage test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    /**
     * เดา file extension จาก MIME type
     */
    private function getExtensionFromMimeType($mimeType)
    {
        $mimeToExt = [
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip'
        ];

        $mimeType = strtolower(explode(';', $mimeType)[0]); // ลบ charset ออก
        return $mimeToExt[$mimeType] ?? null;
    }

    /**
     * ได้รับ attachment paths ทั้งหมด (รวมที่ดาวน์โหลดจาก URL)
     */
    public function getAttachmentPaths()
    {
        if (!$this->attachments) {
            return [];
        }

        return array_map(function($attachment) {
            // ข้าม attachments ที่ล้มเหลว
            if ($attachment['type'] === 'url_failed' || empty($attachment['path'])) {
                return null;
            }
            return storage_path('app/' . $attachment['path']);
        }, $this->attachments);
    }

    /**
     * ตรวจสอบว่ามี attachments หรือไม่
     */
    public function hasAttachments(): bool
    {
        return !empty($this->attachments) || !empty($this->attachment_urls);
    }

    /**
     * นับจำนวน attachments ที่ใช้งานได้
     */
    public function getValidAttachmentCount(): int
    {
        if (!$this->attachments) {
            return 0;
        }

        return count(array_filter($this->attachments, function($attachment) {
            return $attachment['type'] !== 'url_failed' && !empty($attachment['path']);
        }));
    }
}