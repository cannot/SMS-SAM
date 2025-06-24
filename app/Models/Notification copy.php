<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Notification extends Model
{
    use HasFactory, LogsActivity;

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
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($notification) {
            if (empty($notification->uuid)) {
                $notification->uuid = Str::uuid();
            }
        });
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['subject', 'status', 'scheduled_at'])
            ->logOnlyDirty();
    }

    /**
     * Notification group
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(NotificationGroup::class, 'notification_group_id');
    }

    // Relationships
    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function logs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    // Methods
    public function getRecipientUsers()
    {
        $users = collect();

        // Add individual recipients
        if (!empty($this->recipients)) {
            foreach ($this->recipients as $recipient) {
                if (is_numeric($recipient)) {
                    $user = User::find($recipient);
                } else {
                    $user = User::where('email', $recipient)->first();
                }
                if ($user) {
                    $users->push($user);
                }
            }
        }

        // Add group recipients
        if (!empty($this->recipient_groups)) {
            foreach ($this->recipient_groups as $groupId) {
                $group = NotificationGroup::find($groupId);
                if ($group) {
                    $users = $users->merge($group->getUsers());
                }
            }
        }

        return $users->unique('id');
    }

    public function updateDeliveryStats()
    {
        $stats = $this->logs()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status IN ("sent", "delivered") THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status IN ("failed", "bounced") THEN 1 ELSE 0 END) as failed
            ')
            ->first();

        $this->update([
            'total_recipients' => $stats->total,
            'delivered_count' => $stats->delivered,
            'failed_count' => $stats->failed,
        ]);
    }

    public function isScheduled()
    {
        return $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    public function canBeSent()
    {
        return in_array($this->status, ['scheduled', 'queued']) && 
               (!$this->scheduled_at || $this->scheduled_at->isPast());
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeScheduledFor($query, $datetime)
    {
        return $query->where('scheduled_at', '<=', $datetime);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->whereJsonContains('channels', $channel);
    }
}