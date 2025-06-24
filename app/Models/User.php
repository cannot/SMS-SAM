<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;

    protected $fillable = [
        'ldap_guid',
        'username',
        'email',
        'first_name',
        'last_name',
        'display_name',
        'department',
        'title',
        'phone',
        'is_active',
        'password', // เพิ่ม password สำหรับ fallback authentication
        'last_login_at',
        'ldap_synced_at',
        'auth_source',
    ];

    // ป้องกันไม่ให้ mass assignment ฟิลด์ที่ไม่ต้องการ
    protected $guarded = [
        'id',
        'email_verified_at', // ป้องกันฟิลด์นี้
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'ldap_synced_at' => 'datetime',
        'password' => 'hashed', // เพิ่ม casting สำหรับ password
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static $logAttributes = ['name', 'email', 'department'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'user';

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['username', 'email', 'is_active', 'display_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ========== RELATIONSHIPS ==========

    /**
     * User preferences for notifications
     */
    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    /**
     * Notifications created by this user
     */
    public function createdNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'created_by');
    }

    /**
     * API Keys created by this user
     */
    public function createdApiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class, 'created_by');
    }

    /**
     * Notification groups created by this user
     */
    public function createdGroups(): HasMany
    {
        return $this->hasMany(NotificationGroup::class, 'created_by');
    }

    /**
     * Notification templates created by this user
     */
    public function createdTemplates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class, 'created_by');
    }

    /**
     * Notification groups this user belongs to
     */
    public function notificationGroups(): BelongsToMany
    {
        return $this->belongsToMany(NotificationGroup::class, 'notification_group_users')
                    ->withPivot('joined_at', 'added_by')
                    ->withTimestamps()
                    ->orderBy('pivot_joined_at', 'desc');
    }

    /**
     * API usage logs for this user
     */
    public function apiUsageLogs(): HasMany
    {
        return $this->hasMany(ApiUsageLogs::class, 'user_id');
    }

    // ========== CUSTOM QUERIES ==========

    /**
     * Get notifications received by this user
     * (Check if user ID or email is in recipients array)
     */
    public function receivedNotifications()
    {
        return Notification::where(function($query) {
            $query->whereJsonContains('recipients', $this->id)
                  ->orWhereJsonContains('recipients', $this->email);
        })->orderBy('created_at', 'desc');
    }

    /**
     * Get notifications sent to groups this user belongs to
     */
    public function groupNotifications()
    {
        $groupIds = $this->notificationGroups->pluck('id')->toArray();
        
        if (empty($groupIds)) {
            return Notification::whereRaw('1 = 0'); // Empty query
        }

        return Notification::where(function($query) use ($groupIds) {
            foreach ($groupIds as $groupId) {
                $query->orWhereJsonContains('recipient_groups', $groupId);
            }
        })->orderBy('created_at', 'desc');
    }

    /**
     * Get all notifications for this user (direct + group)
     */
    public function allNotifications()
    {
        $groupIds = $this->notificationGroups->pluck('id')->toArray();
        
        return Notification::where(function($query) use ($groupIds) {
            // Direct notifications
            $query->whereJsonContains('recipients', $this->id)
                  ->orWhereJsonContains('recipients', $this->email);
            
            // Group notifications
            if (!empty($groupIds)) {
                foreach ($groupIds as $groupId) {
                    $query->orWhereJsonContains('recipient_groups', $groupId);
                }
            }
        })->orderBy('created_at', 'desc');
    }

    // ========== SCOPES ==========

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
     * Scope for search functionality
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function($q) use ($search) {
                $q->where('first_name', 'ILIKE', "%{$search}%")
                  ->orWhere('last_name', 'ILIKE', "%{$search}%")
                  ->orWhere('display_name', 'ILIKE', "%{$search}%")
                  ->orWhere('username', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }
        return $query;
    }

    /**
     * Scope for users with specific roles
     */
    public function scopeWithRole($query, $role)
    {
        return $query->whereHas('roles', function($q) use ($role) {
            $q->where('name', $role);
        });
    }

    /**
     * Scope for users in specific notification groups
     */
    public function scopeInGroup($query, $groupId)
    {
        return $query->whereHas('notificationGroups', function($q) use ($groupId) {
            $q->where('notification_groups.id', $groupId);
        });
    }

    // ========== ACCESSORS & MUTATORS ==========

    /**
     * Get user's full name
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get display name with fallback
     */
    public function getDisplayNameAttribute($value): string
    {
        return $value ?? $this->full_name ?? $this->username;
    }

    /**
     * Check if user is recently active (logged in within 30 days)
     */
    public function getIsActiveUserAttribute(): bool
    {
        return $this->is_active && 
               $this->last_login_at && 
               $this->last_login_at->gt(now()->subDays(30));
    }

    /**
     * Get user avatar initials
     */
    public function getInitialsAttribute(): string
    {
        $name = $this->display_name ?? $this->full_name ?? $this->username;
        $words = explode(' ', $name);
        
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        
        return strtoupper(substr($name, 0, 2));
    }

    /**
     * Get user preferences with defaults
     */
    public function getUserPreferencesAttribute()
    {
        $preferences = $this->preferences;
        
        if (!$preferences) {
            // Return default preferences object
            $preferences = new UserPreference([
                'user_id' => $this->id,
                'enable_teams' => true,
                'enable_email' => true,
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

    // ========== HELPER METHODS ==========

    /**
     * Check if user can receive notifications
     */
    public function canReceiveNotifications(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $preferences = $this->user_preferences;
        return $preferences->enable_email || $preferences->enable_teams;
    }

    /**
     * Check if user should receive notification based on priority
     */
    public function shouldReceiveNotification(string $priority, string $channel = null): bool
    {
        if (!$this->canReceiveNotifications()) {
            return false;
        }

        $preferences = $this->user_preferences;
        return $preferences->shouldReceiveNotification($priority, $channel);
    }

    /**
     * Get user's effective email for notifications
     */
    public function getNotificationEmail(): ?string
    {
        $preferences = $this->user_preferences;
        return $preferences->email_address ?? $this->email;
    }

    /**
     * Get user's Teams ID for notifications
     */
    public function getTeamsUserId(): ?string
    {
        $preferences = $this->user_preferences;
        return $preferences->teams_user_id ?? $this->username; // Fallback to username
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(['admin', 'super-admin']);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user can manage other users
     */
    public function canManageUsers(): bool
    {
        return $this->hasRole(['admin', 'user-manager']) || 
               $this->hasPermissionTo('manage-users');
    }

    /**
     * Check if user can create notifications
     */
    public function canCreateNotifications(): bool
    {
        return $this->hasRole(['admin', 'notification-manager', 'user']) || 
               $this->hasPermissionTo('create-notifications');
    }

    /**
     * Check if user can manage API keys
     */
    public function canManageApiKeys(): bool
    {
        return $this->hasRole(['admin', 'api-manager']) || 
               $this->hasPermissionTo('manage-api-keys');
    }

    /**
     * Get notification statistics for this user
     */
    public function getNotificationStats(): array
    {
        $totalReceived = $this->allNotifications()->count();
        $thisMonth = $this->allNotifications()
            ->whereMonth('created_at', now()->month)
            ->count();
        $created = $this->createdNotifications()->count();

        return [
            'total_received' => $totalReceived,
            'this_month' => $thisMonth,
            'created' => $created,
            'groups_count' => $this->notificationGroups()->count()
        ];
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update([
            'last_login_at' => now()
        ]);
    }

    /**
     * Sync user data from LDAP
     */
    public function syncFromLdap(array $ldapData): void
    {
        $this->update([
            'first_name' => $ldapData['first_name'] ?? $this->first_name,
            'last_name' => $ldapData['last_name'] ?? $this->last_name,
            'display_name' => $ldapData['display_name'] ?? $this->display_name,
            'email' => $ldapData['email'] ?? $this->email,
            'department' => $ldapData['department'] ?? $this->department,
            'title' => $ldapData['title'] ?? $this->title,
            'phone' => $ldapData['phone'] ?? $this->phone,
            'ldap_synced_at' => now()
        ]);
    }

    // Method to get user's activities
    public function getActivities()
    {
        return Activity::where('causer_type', self::class)
            ->where('causer_id', $this->id)
            ->latest()
            ->get();
    }

    /**
     * Override fill method to prevent email_verified_at assignment
     */
    public function fill(array $attributes)
    {
        // Remove email_verified_at if it exists in attributes
        unset($attributes['email_verified_at']);
        
        return parent::fill($attributes);
    }
}