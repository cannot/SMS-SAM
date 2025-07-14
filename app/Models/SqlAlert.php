<?php
// app/Models/SqlAlert.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class SqlAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'database_config',
        'sql_query',
        'variables',
        'email_config',
        'recipients',
        'schedule_config',
        'schedule_type',
        'export_config',
        'status',
        'last_run',
        'next_run',
        'total_executions',
        'successful_executions',
        'last_success',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'database_config' => 'array',
        'variables' => 'array',
        'email_config' => 'array',
        'recipients' => 'array',
        'schedule_config' => 'array',
        'export_config' => 'array',
        'last_run' => 'datetime',
        'next_run' => 'datetime',
        'last_success' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'schedule_type' => 'manual',
        'total_executions' => 0,
        'successful_executions' => 0,
    ];

    // ===================== RELATIONSHIPS =====================

    /**
     * User who created this SQL Alert
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated this SQL Alert
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Execution history
     */
    public function executions(): HasMany
    {
        return $this->hasMany(SqlAlertExecution::class)->orderBy('created_at', 'desc');
    }

    /**
     * Successful executions only
     */
    public function successfulExecutions(): HasMany
    {
        return $this->hasMany(SqlAlertExecution::class)->where('status', 'success');
    }

    /**
     * Failed executions only
     */
    public function failedExecutions(): HasMany
    {
        return $this->hasMany(SqlAlertExecution::class)->where('status', 'failed');
    }

    /**
     * Most recent execution
     */
    public function latestExecution()
    {
        return $this->executions()->latest()->first();
    }

    // ===================== SCOPES =====================

    /**
     * Active alerts only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Alerts ready to run
     */
    public function scopeReadyToRun($query)
    {
        return $query->where('status', 'active')
                    ->where('schedule_type', '!=', 'manual')
                    ->where(function($q) {
                        $q->whereNull('next_run')
                          ->orWhere('next_run', '<=', now());
                    });
    }

    /**
     * Alerts by creator
     */
    public function scopeByCreator($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Filter by schedule type
     */
    public function scopeByScheduleType($query, $type)
    {
        return $query->where('schedule_type', $type);
    }

    // ===================== ACCESSORS & MUTATORS =====================

    /**
     * Get formatted database type
     */
    public function getDatabaseTypeAttribute()
    {
        return $this->database_config['type'] ?? 'Unknown';
    }

    /**
     * Get database host
     */
    public function getDatabaseHostAttribute()
    {
        return $this->database_config['host'] ?? 'localhost';
    }

    /**
     * Get database name
     */
    public function getDatabaseNameAttribute()
    {
        return $this->database_config['database'] ?? 'Unknown';
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute()
    {
        if ($this->total_executions == 0) {
            return 0;
        }
        
        return round(($this->successful_executions / $this->total_executions) * 100, 1);
    }

    /**
     * Check if alert is overdue
     */
    public function getIsOverdueAttribute()
    {
        if (!$this->next_run || $this->schedule_type === 'manual') {
            return false;
        }

        return $this->next_run->isPast();
    }

    /**
     * Get human readable next run time
     */
    public function getNextRunHumanAttribute()
    {
        if (!$this->next_run) {
            return 'ไม่ได้กำหนด';
        }

        return $this->next_run->diffForHumans();
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute()
    {
        $statuses = [
            'active' => 'เปิดใช้งาน',
            'inactive' => 'ปิดใช้งาน',
            'draft' => 'ร่าง',
            'error' => 'ข้อผิดพลาด'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get schedule type display name
     */
    public function getScheduleTypeDisplayAttribute()
    {
        $types = [
            'manual' => 'รันเอง',
            'once' => 'รันครั้งเดียว',
            'recurring' => 'รันซ้ำ',
            'cron' => 'กำหนดเอง (Cron)'
        ];

        return $types[$this->schedule_type] ?? $this->schedule_type;
    }

    // ===================== METHODS =====================

    /**
     * Calculate next run time based on schedule
     */
    public function calculateNextRun(): ?Carbon
    {
        if ($this->schedule_type === 'manual') {
            return null;
        }

        if ($this->schedule_type === 'once') {
            // For one-time execution, check if already run
            if ($this->last_run) {
                return null; // Already executed
            }
            
            return isset($this->schedule_config['datetime']) 
                ? Carbon::parse($this->schedule_config['datetime'])
                : now()->addMinute();
        }

        if ($this->schedule_type === 'recurring') {
            $interval = $this->schedule_config['interval'] ?? 'daily';
            $time = $this->schedule_config['time'] ?? '09:00';
            
            $baseTime = $this->last_run ? Carbon::parse($this->last_run) : now();
            
            switch ($interval) {
                case 'daily':
                    return $baseTime->addDay()->setTimeFromTimeString($time);
                case 'weekly':
                    $dayOfWeek = $this->schedule_config['day_of_week'] ?? 1;
                    return $baseTime->addWeek()->startOfWeek()->addDays($dayOfWeek - 1)->setTimeFromTimeString($time);
                case 'monthly':
                    $dayOfMonth = $this->schedule_config['day_of_month'] ?? 1;
                    return $baseTime->addMonth()->startOfMonth()->addDays($dayOfMonth - 1)->setTimeFromTimeString($time);
                case 'hourly':
                    return $baseTime->addHour();
                default:
                    return $baseTime->addDay();
            }
        }

        if ($this->schedule_type === 'cron') {
            // For cron expressions, you might want to use a library like mtdowling/cron-expression
            // For now, just add an hour as fallback
            return now()->addHour();
        }

        return null;
    }

    /**
     * Update next run time
     */
    public function updateNextRun(): void
    {
        $this->next_run = $this->calculateNextRun();
        $this->save();
    }

    /**
     * Mark as executed
     */
    public function markAsExecuted(bool $success = true): void
    {
        $this->last_run = now();
        $this->total_executions++;
        
        if ($success) {
            $this->successful_executions++;
            $this->last_success = now();
        }
        
        $this->updateNextRun();
    }

    /**
     * Check if alert can be executed
     */
    public function canExecute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get recipient count
     */
    public function getRecipientCount(): int
    {
        return count($this->recipients ?? []);
    }

    /**
     * Get variable names used in query
     */
    public function getUsedVariables(): array
    {
        $variables = [];
        
        // Extract variables from SQL query
        preg_match_all('/\{\{(\w+)\}\}/', $this->sql_query, $matches);
        if (!empty($matches[1])) {
            $variables = array_merge($variables, $matches[1]);
        }
        
        // Extract variables from email template
        if (isset($this->email_config['body_template'])) {
            preg_match_all('/\{\{(\w+)\}\}/', $this->email_config['body_template'], $matches);
            if (!empty($matches[1])) {
                $variables = array_merge($variables, $matches[1]);
            }
        }
        
        return array_unique($variables);
    }

    /**
     * Validate configuration
     */
    public function validateConfiguration(): array
    {
        $errors = [];
        
        // Check database config
        if (empty($this->database_config)) {
            $errors[] = 'Database configuration is required';
        }
        
        // Check SQL query
        if (empty($this->sql_query)) {
            $errors[] = 'SQL query is required';
        }
        
        // Check recipients
        if (empty($this->recipients)) {
            $errors[] = 'At least one recipient is required';
        }
        
        // Check email config
        if (empty($this->email_config)) {
            $errors[] = 'Email configuration is required';
        }
        
        return $errors;
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });
        
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}

// ===== app/Models/SqlAlertExecution.php =====

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SqlAlertExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'sql_alert_id',
        'status',
        'started_at',
        'completed_at',
        'execution_time_ms',
        'rows_returned',
        'rows_processed',
        'query_results',
        'notifications_sent',
        'notifications_failed',
        'notification_details',
        'error_message',
        'error_details',
        'error_code',
        'trigger_type',
        'triggered_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'query_results' => 'array',
        'notification_details' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'trigger_type' => 'scheduled',
        'notifications_sent' => 0,
        'notifications_failed' => 0,
    ];

    // ===================== RELATIONSHIPS =====================

    /**
     * SQL Alert this execution belongs to
     */
    public function sqlAlert(): BelongsTo
    {
        return $this->belongsTo(SqlAlert::class);
    }

    /**
     * User who triggered this execution
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * Recipients for this execution
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(SqlAlertRecipient::class, 'execution_id');
    }

    /**
     * Attachments for this execution
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SqlAlertAttachment::class, 'execution_id');
    }

    // ===================== SCOPES =====================

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    // ===================== ACCESSORS =====================

    public function getExecutionTimeHumanAttribute()
    {
        if (!$this->execution_time_ms) {
            return 'N/A';
        }

        if ($this->execution_time_ms < 1000) {
            return $this->execution_time_ms . 'ms';
        }

        return round($this->execution_time_ms / 1000, 2) . 's';
    }

    public function getStatusDisplayAttribute()
    {
        $statuses = [
            'pending' => 'รอดำเนินการ',
            'running' => 'กำลังรัน',
            'success' => 'สำเร็จ',
            'failed' => 'ล้มเหลว',
            'cancelled' => 'ยกเลิก'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getTriggerTypeDisplayAttribute()
    {
        $types = [
            'manual' => 'รันเอง',
            'scheduled' => 'ตามกำหนด',
            'webhook' => 'Webhook',
            'api' => 'API'
        ];

        return $types[$this->trigger_type] ?? $this->trigger_type;
    }

    // ===================== METHODS =====================

    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now()
        ]);
    }

    public function markAsCompleted(bool $success = true, ?string $errorMessage = null): void
    {
        $this->update([
            'status' => $success ? 'success' : 'failed',
            'completed_at' => now(),
            'execution_time_ms' => $this->started_at ? 
                now()->diffInMilliseconds($this->started_at) : null,
            'error_message' => $errorMessage
        ]);

        // Update parent SQL Alert statistics
        $this->sqlAlert->markAsExecuted($success);
    }

    public function getDuration(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInMilliseconds($this->started_at);
    }
}

// ===== app/Models/SqlAlertRecipient.php =====

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SqlAlertRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'sql_alert_id',
        'execution_id',
        'recipient_type',
        'recipient_id',
        'recipient_email',
        'recipient_name',
        'delivery_status',
        'sent_at',
        'failure_reason',
        'email_content',
        'email_subject',
        'personalized_variables',
        'attachments',
        'attachment_size',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'personalized_variables' => 'array',
        'attachments' => 'array',
    ];

    protected $attributes = [
        'delivery_status' => 'pending',
    ];

    // ===================== RELATIONSHIPS =====================

    public function sqlAlert(): BelongsTo
    {
        return $this->belongsTo(SqlAlert::class);
    }

    public function execution(): BelongsTo
    {
        return $this->belongsTo(SqlAlertExecution::class, 'execution_id');
    }

    // ===================== SCOPES =====================

    public function scopeSent($query)
    {
        return $query->where('delivery_status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('delivery_status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('delivery_status', 'pending');
    }

    // ===================== ACCESSORS =====================

    public function getDeliveryStatusDisplayAttribute()
    {
        $statuses = [
            'pending' => 'รอส่ง',
            'sent' => 'ส่งสำเร็จ',
            'failed' => 'ส่งล้มเหลว',
            'bounced' => 'อีเมลตีกลับ'
        ];

        return $statuses[$this->delivery_status] ?? $this->delivery_status;
    }

    // ===================== METHODS =====================

    public function markAsSent(): void
    {
        $this->update([
            'delivery_status' => 'sent',
            'sent_at' => now()
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'delivery_status' => 'failed',
            'failure_reason' => $reason
        ]);
    }
}

// ===== app/Models/SqlAlertAttachment.php =====

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SqlAlertAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'execution_id',
        'filename',
        'original_filename',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'generation_status',
        'generated_at',
        'generation_time_ms',
        'total_rows',
        'total_columns',
        'column_headers',
        'error_message',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'column_headers' => 'array',
    ];

    protected $attributes = [
        'generation_status' => 'pending',
    ];

    // ===================== RELATIONSHIPS =====================

    public function execution(): BelongsTo
    {
        return $this->belongsTo(SqlAlertExecution::class, 'execution_id');
    }

    // ===================== SCOPES =====================

    public function scopeCompleted($query)
    {
        return $query->where('generation_status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('generation_status', 'failed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    // ===================== ACCESSORS =====================

    public function getGenerationStatusDisplayAttribute()
    {
        $statuses = [
            'pending' => 'รอสร้าง',
            'generating' => 'กำลังสร้าง',
            'completed' => 'สร้างเสร็จ',
            'failed' => 'สร้างล้มเหลว'
        ];

        return $statuses[$this->generation_status] ?? $this->generation_status;
    }

    public function getFileSizeHumanAttribute()
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    public function getGenerationTimeHumanAttribute()
    {
        if (!$this->generation_time_ms) {
            return 'N/A';
        }

        if ($this->generation_time_ms < 1000) {
            return $this->generation_time_ms . 'ms';
        }

        return round($this->generation_time_ms / 1000, 2) . 's';
    }

    // ===================== METHODS =====================

    public function markAsGenerating(): void
    {
        $this->update([
            'generation_status' => 'generating'
        ]);
    }

    public function markAsCompleted(int $generationTime = null): void
    {
        $this->update([
            'generation_status' => 'completed',
            'generated_at' => now(),
            'generation_time_ms' => $generationTime
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'generation_status' => 'failed',
            'error_message' => $errorMessage
        ]);
    }

    public function getFullPath(): string
    {
        return Storage::path($this->file_path);
    }

    public function exists(): bool
    {
        return Storage::exists($this->file_path);
    }

    public function download()
    {
        if (!$this->exists()) {
            abort(404, 'File not found');
        }

        return Storage::download($this->file_path, $this->original_filename);
    }

    public function getUrl(): ?string
    {
        if (!$this->exists()) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    /**
     * Delete the physical file when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            if ($attachment->exists()) {
                Storage::delete($attachment->file_path);
            }
        });
    }
}