<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'enable_teams',
        'enable_email',
        'teams_user_id',
        'teams_channel_preference',
        'email_address',
        'email_format',
        'min_priority',
        'enable_quiet_hours',
        'quiet_hours_start',
        'quiet_hours_end',
        'quiet_days',
        'override_high_priority',
        'enable_grouping',
        'grouping_method',
        'language',
        'timezone'
    ];

    protected $casts = [
        'enable_teams' => 'boolean',
        'enable_email' => 'boolean',
        'enable_quiet_hours' => 'boolean',
        'override_high_priority' => 'boolean',
        'enable_grouping' => 'boolean',
        'quiet_days' => 'array'
    ];

    protected $attributes = [
        'enable_teams' => true,
        'enable_email' => true,
        'enable_quiet_hours' => false,
        'min_priority' => 'low',
        'language' => 'th',
        'timezone' => 'Asia/Bangkok',
        'email_format' => 'html',
        'teams_channel_preference' => 'direct',
        'enable_grouping' => true,
        'grouping_method' => 'sender',
        'override_high_priority' => false
    ];

    /**
     * Get the user that owns the preferences
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user should receive notification based on priority
     */
    public function shouldReceivePriority(string $priority): bool
    {
        $priorities = ['low' => 1, 'normal' => 2, 'high' => 3, 'urgent' => 4];
        $minPriority = $priorities[$this->min_priority] ?? 1;
        $notificationPriority = $priorities[$priority] ?? 1;
        
        return $notificationPriority >= $minPriority;
    }

    /**
     * Check if notification should be sent during quiet hours
     */
    public function isQuietTime(\Carbon\Carbon $time = null): bool
    {
        if (!$this->enable_quiet_hours) {
            return false;
        }

        $time = $time ?? now($this->timezone);
        
        // Check if today is in quiet days
        $dayName = strtolower($time->format('l'));
        $quietDays = $this->quiet_days ?? [];
        
        if (!in_array($dayName, $quietDays)) {
            return false;
        }

        // Check if time is within quiet hours
        $currentTime = $time->format('H:i');
        $startTime = $this->quiet_hours_start ?? '22:00';
        $endTime = $this->quiet_hours_end ?? '08:00';

        if ($startTime <= $endTime) {
            // Same day range (e.g., 10:00 to 18:00)
            return $currentTime >= $startTime && $currentTime <= $endTime;
        } else {
            // Cross midnight range (e.g., 22:00 to 08:00)
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }
    }

    /**
     * Check if notification should be sent
     */
    public function shouldReceiveNotification(string $priority, string $channel = null): bool
    {
        // Check priority
        if (!$this->shouldReceivePriority($priority)) {
            return false;
        }

        // Check channel
        if ($channel === 'teams' && !$this->enable_teams) {
            return false;
        }
        
        if ($channel === 'email' && !$this->enable_email) {
            return false;
        }

        // Check quiet hours
        if ($this->isQuietTime()) {
            // Allow high priority during quiet hours if override is enabled
            if ($this->override_high_priority && in_array($priority, ['high', 'urgent'])) {
                return true;
            }
            return false;
        }

        return true;
    }

    /**
     * Get effective email address
     */
    public function getEffectiveEmailAttribute(): ?string
    {
        return $this->email_address ?? $this->user->email;
    }

    /**
     * Get effective teams user ID
     */
    public function getEffectiveTeamsUserIdAttribute(): ?string
    {
        return $this->teams_user_id ?? $this->user->teams_user_id ?? null;
    }

    public function allowsEmailNotifications()
    {
        return $this->enable_email ?? true;
    }

    public function allowsTeamsNotifications()
    {
        return $this->enable_teams ?? true;
    }

    public function isInQuietHours()
    {
        // Logic to check quiet hours
        return false; // Placeholder
    }

    public function allowsWeekendNotifications()
    {
        return $this->enable_weekend_notifications ?? true;
    }
}