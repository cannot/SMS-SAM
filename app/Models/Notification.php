<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'template_id',
        'notification_group_id', // เพิ่มใหม่
        'subject',
        'body_html',
        'body_text',
        'channels',
        'recipients',
        'recipient_groups',
        'variables',
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
    ];

    protected $casts = [
        'channels' => 'array',
        'recipients' => 'array',
        'recipient_groups' => 'array',
        'variables' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function getRecipientUsers()
    {
        $users = collect();
        
        // Get users from recipients array (emails)
        if (!empty($this->recipients)) {
            $users = $users->merge(
                \App\Models\User::whereIn('email', $this->recipients)->get()
            );
        }
        
        // Get users from recipient_groups
        if (!empty($this->recipient_groups)) {
            foreach ($this->recipient_groups as $groupId) {
                $group = \App\Models\NotificationGroup::find($groupId);
                if ($group) {
                    $users = $users->merge($group->users);
                }
            }
        }
        
        return $users->unique('id');
    }

    public function updateDeliveryStats()
    {
        $delivered = $this->logs()->where('status', 'sent')->count();
        $failed = $this->logs()->where('status', 'failed')->count();
        
        $this->update([
            'delivered_count' => $delivered,
            'failed_count' => $failed
        ]);
    }

    // ========== RELATIONSHIPS ==========

    /**
     * Notification group
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(NotificationGroup::class, 'notification_group_id');
    }

    /**
     * Template used
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    /**
     * User who created this notification
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Notification logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * API Key used (if sent via API)
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id', 'id');
    }

    // ========== SCOPES ==========

    /**
     * Scope for sent notifications
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for scheduled notifications
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope for processing notifications
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    // ========== ACCESSORS & MUTATORS ==========

    /**
     * Get success rate
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients == 0) {
            return 0;
        }
        
        return round(($this->delivered_count / $this->total_recipients) * 100, 2);
    }

    /**
     * Get failure rate
     */
    public function getFailureRateAttribute(): float
    {
        if ($this->total_recipients == 0) {
            return 0;
        }
        
        return round(($this->failed_count / $this->total_recipients) * 100, 2);
    }

    /**
     * Check if notification is sent
     */
    public function getIsSentAttribute(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if notification failed
     */
    public function getIsFailedAttribute(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if notification is scheduled
     */
    public function getIsScheduledAttribute(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if notification is processing
     */
    public function getIsProcessingAttribute(): bool
    {
        return in_array($this->status, ['queued', 'processing']);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'sent' => 'bg-success',
            'failed' => 'bg-danger',
            'scheduled' => 'bg-warning',
            'processing', 'queued' => 'bg-info',
            'cancelled' => 'bg-secondary',
            default => 'bg-light text-dark'
        };
    }

    /**
     * Get status text in Thai
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'draft' => 'ร่าง',
            'scheduled' => 'กำหนดการ',
            'queued' => 'อยู่ในคิว',
            'processing' => 'กำลังส่ง',
            'sent' => 'ส่งแล้ว',
            'failed' => 'ล้มเหลว',
            'cancelled' => 'ยกเลิก',
            default => $this->status
        };
    }

    /**
     * Get priority badge class
     */
    public function getPriorityBadgeClassAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'bg-danger',
            'high' => 'bg-warning',
            'normal' => 'bg-primary',
            'low' => 'bg-secondary',
            default => 'bg-light text-dark'
        };
    }

    /**
     * Get priority text in Thai
     */
    public function getPriorityTextAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'เร่งด่วน',
            'high' => 'สูง',
            'normal' => 'ปกติ',
            'low' => 'ต่ำ',
            default => $this->priority
        };
    }

    // ========== HELPER METHODS ==========

    /**
     * Mark as sent
     */
    public function markAsSent(int $deliveredCount = null, int $failedCount = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'delivered_count' => $deliveredCount ?? $this->delivered_count,
            'failed_count' => $failedCount ?? $this->failed_count,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
        ]);
    }

    /**
     * Get channel names in Thai
     */
    public function getChannelNamesAttribute(): array
    {
        $channelMap = [
            'email' => 'อีเมล',
            'teams' => 'Microsoft Teams',
            'sms' => 'SMS',
            'line' => 'LINE',
        ];

        return array_map(function($channel) use ($channelMap) {
            return $channelMap[$channel] ?? $channel;
        }, $this->channels ?? []);
    }
}