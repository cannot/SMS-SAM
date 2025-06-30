<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'user_id',
        'recipient_email',
        'recipient_name',
        'channel',
        'status',
        'response_data',
        'error_message',
        'sent_at',
        'delivered_at',
        'read_at',
        'archived_at',
        'retry_count',
        'attempts',
        'next_retry_at',
        'personalized_content',
        'content_sent',
        'webhook_url',
        'webhook_response_code',
        'variables'
    ];

    protected $casts = [
        'variables' => 'array',
        'response_data' => 'array',
        'content_sent' => 'array',
        'personalized_content' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'retry_count' => 'integer',
        'webhook_response_code' => 'integer'
    ];

    // ========== RELATIONSHIPS ==========

    /**
     * Get the notification that this log belongs to
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Get the user that received this notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========== METHODS ==========

    /**
     * Mark notification as sent
     */
    public function markAsSent($responseData = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'response_data' => $responseData,
        ]);
    }

    /**
     * Mark notification as delivered
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed
     */
    public function markAsFailed($errorMessage, $shouldRetry = true): void
    {
        $updateData = [
            'status' => 'failed',
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'attempts' => $this->attempts + 1,
        ];

        if ($shouldRetry && $this->retry_count < 3) {
            // Exponential backoff: 5min, 15min, 45min
            $delay = pow(3, $this->retry_count) * 5;
            $updateData['next_retry_at'] = now()->addMinutes($delay);
            $updateData['status'] = 'pending';
        }

        $this->update($updateData);
    }

    /**
     * Mark notification as read by user
     */
    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Mark notification as unread by user
     */
    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Archive notification for user
     */
    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    /**
     * Restore archived notification
     */
    public function restore(): void
    {
        $this->update(['archived_at' => null]);
    }

    /**
     * Check if notification should be retried
     */
    public function shouldRetry(): bool
    {
        return $this->status === 'pending' && 
               $this->next_retry_at && 
               $this->next_retry_at->isPast() &&
               $this->retry_count < 3;
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if notification is archived
     */
    public function isArchived(): bool
    {
        return !is_null($this->archived_at);
    }

    /**
     * Check if delivery was successful
     */
    public function isDelivered(): bool
    {
        return in_array($this->status, ['sent', 'delivered']);
    }

    /**
     * Check if delivery failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get delivery time in seconds
     */
    public function getDeliveryTimeAttribute(): ?int
    {
        if ($this->delivered_at && $this->sent_at) {
            return abs($this->delivered_at->diffInSeconds($this->sent_at));
        }
        
        if ($this->delivered_at && $this->created_at) {
            return abs($this->delivered_at->diffInSeconds($this->created_at));
        }

        return null;
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'delivered', 'sent' => 'bg-success',
            'failed' => 'bg-danger',
            'pending' => 'bg-warning',
            default => 'bg-secondary'
        };
    }

    /**
     * Get channel icon for UI
     */
    public function getChannelIconAttribute(): string
    {
        return match($this->channel) {
            'teams' => 'fa-users',
            'email' => 'fa-envelope',
            'sms' => 'fa-sms',
            'line' => 'fa-line',
            default => 'fa-bell'
        };
    }

    // ========== SCOPES ==========

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by channel
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope to get notifications for retry
     */
    public function scopeForRetry($query)
    {
        return $query->where('status', 'pending')
                    ->where('next_retry_at', '<=', now())
                    ->where('retry_count', '<', 3);
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by email (for users not in system)
     */
    public function scopeForEmail($query, $email)
    {
        return $query->where('recipient_email', $email);
    }

    /**
     * Scope to get unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to get read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to get non-archived notifications
     */
    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Scope to get archived notifications
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Scope to get delivered notifications
     */
    public function scopeDelivered($query)
    {
        return $query->whereIn('status', ['sent', 'delivered']);
    }

    /**
     * Scope to get failed notifications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get pending notifications
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get recent notifications
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get notifications for a date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope to search in recipient info
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('recipient_email', 'LIKE', "%{$search}%")
              ->orWhere('recipient_name', 'LIKE', "%{$search}%")
              ->orWhere('error_message', 'LIKE', "%{$search}%")
              ->orWhereHas('user', function ($uq) use ($search) {
                  $uq->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
              });
        });
    }
}