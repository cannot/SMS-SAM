<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'username',
        'name', 
        'display_name',
        'email',
        'password',
        'department',
        'title',
        'phone',
        'ldap_dn',
        'ldap_guid',
        'last_login_at',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    /**
     * Get custom notifications created by this user
     */
    public function createdNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'created_by');
    }

    /**
     * Get custom notifications sent to this user
     * (Check if user ID is in recipients array)
     */
    public function receivedNotifications()
    {
        return Notification::whereJsonContains('recipients', $this->id)
            ->orWhereJsonContains('recipients', $this->email)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get recent notifications for this user
     */
    public function getRecentNotificationsAttribute()
    {
        return $this->receivedNotifications()->take(10)->get();
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadNotificationsCountAttribute()
    {
        // ใช้ notification_logs table เพื่อตรวจสอบสถานะการอ่าน
        return NotificationLog::whereHas('notification', function($query) {
                $query->whereJsonContains('recipients', $this->id)
                      ->orWhereJsonContains('recipients', $this->email);
            })
            ->where('recipient_id', $this->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * User preferences
     */
    public function preferences()
    {
        return $this->hasOne(UserPreference::class);
    }

    /**
     * Get user preferences with defaults
     */
    public function getPreferencesAttribute()
    {
        $preferences = $this->preferences ?? new UserPreference();
        
        // Set defaults if no preferences exist
        if (!$preferences->exists) {
            $preferences->fill([
                'enable_teams' => true,
                'enable_email' => true,
                'enable_quiet_hours' => false,
                'min_priority' => 'low',
                'language' => 'th',
                'timezone' => 'Asia/Bangkok',
                'email_format' => 'html',
                'teams_channel_preference' => 'direct',
                'enable_grouping' => true,
                'grouping_method' => 'sender'
            ]);
        }
        
        return $preferences;
    }

    /**
     * Groups that this user belongs to
     */
    public function notificationGroups(): BelongsToMany
    {
        return $this->belongsToMany(NotificationGroup::class, 'notification_group_users');
    }

    /**
     * API usage logs for this user
     */
    public function apiUsageLogs(): HasMany
    {
        return $this->hasMany(ApiUsageLogs::class);
    }

    /**
     * Check if user is active (logged in within last 30 days)
     */
    public function getIsActiveUserAttribute(): bool
    {
        return $this->last_login_at && $this->last_login_at->gt(now()->subDays(30));
    }

    /**
     * Get user's full name or fallback
     */
    public function getDisplayNameAttribute($value)
    {
        return $value ?? $this->name ?? $this->username;
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('last_login_at', '>=', now()->subDays(30));
    }

    /**
     * Scope for users by department
     */
    public function scopeByDepartment($query, $department)
    {
        if ($department) {
            return $query->where('department', $department);
        }
        return $query;
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function($q) use ($search) {
                $q->where('username', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('display_name', 'ILIKE', "%{$search}%");
            });
        }
        return $query;
    }
}