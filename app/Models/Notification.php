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
        'notification_group_id',
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
        'total_recipients' => 'integer',
        'delivered_count' => 'integer',
        'failed_count' => 'integer',
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

        // Update sent_at if all notifications are delivered
        if ($stats['pending'] === 0 && $stats['total'] > 0 && !$this->sent_at) {
            $this->update([
                'status' => $stats['failed'] > 0 ? 'failed' : 'sent',
                'sent_at' => now(),
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
}