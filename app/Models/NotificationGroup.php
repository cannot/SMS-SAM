<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class NotificationGroup extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'type', // manual, department, ldap_group, etc.
        'criteria', // JSON field for dynamic criteria
        'is_active',
        'created_by',
        'webhook_url',
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
        'webhook_url' => 'string',
    ];

    // ========== ACTIVITY LOG ==========

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'type', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ========== RELATIONSHIPS ==========

    /**
     * User who created this group
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Users in this notification group (many-to-many)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_group_users')
                    ->withPivot('joined_at', 'added_by')
                    ->withTimestamps()
                    ->orderBy('notification_group_users.joined_at', 'desc');
    }

    /**
     * Active users in this notification group
     */
    public function activeUsers(): BelongsToMany
    {
        return $this->users()->where('users.is_active', true);
    }

    /**
     * Notifications sent to this group
     * ชั่วคราว: return empty collection ถ้าไม่มีตาราง notifications
     */
    public function notifications(): HasMany
    {
        // ตรวจสอบว่ามีตาราง notifications หรือไม่
        try {
            return $this->hasMany(Notification::class, 'notification_group_id');
        } catch (\Exception $e) {
            // ถ้าไม่มีตาราง notifications ให้ return empty collection
            return $this->hasMany(User::class, 'id')->where('id', -1); // จะ return empty collection
        }
    }

    // ========== SCOPES ==========

    /**
     * Scope for active groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by group type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for search
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where(function($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }
        return $query;
    }

    // ========== ACCESSORS & MUTATORS ==========

    /**
     * Get member count
     */
    public function getMemberCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Get active member count
     */
    public function getActiveMemberCountAttribute(): int
    {
        return $this->activeUsers()->count();
    }

    /**
     * Check if group is manual type
     */
    public function getIsManualAttribute(): bool
    {
        return $this->type === 'manual';
    }

    /**
     * Check if group is department based
     */
    public function getIsDepartmentBasedAttribute(): bool
    {
        return $this->type === 'department';
    }

    /**
     * Check if group is LDAP based
     */
    public function getIsLdapBasedAttribute(): bool
    {
        return $this->type === 'ldap_group';
    }

    // ========== HELPER METHODS ==========

    /**
     * Add user to group
     */
    public function addUser($userId, $addedBy = null): bool
    {
        if ($this->users()->where('user_id', $userId)->exists()) {
            return false; // User already in group
        }

        $this->users()->attach($userId, [
            'joined_at' => now(),
            'added_by' => $addedBy,
        ]);

        return true;
    }

    /**
     * Remove user from group
     */
    public function removeUser($userId): bool
    {
        return $this->users()->detach($userId) > 0;
    }

    /**
     * Add multiple users to group
     */
    public function addUsers(array $userIds, $addedBy = null): int
    {
        $added = 0;
        foreach ($userIds as $userId) {
            if ($this->addUser($userId, $addedBy)) {
                $added++;
            }
        }
        return $added;
    }

    /**
     * Sync users with group (for department/LDAP groups)
     */
    public function syncUsers(array $userIds): array
    {
        $syncData = [];
        foreach ($userIds as $userId) {
            $syncData[$userId] = [
                'joined_at' => now(),
                'added_by' => null, // System sync
            ];
        }

        return $this->users()->sync($syncData);
    }

    /**
     * Get users based on criteria (for dynamic groups)
     */
    public function getEligibleUsers()
    {
        if ($this->is_manual) {
            return $this->users();
        }

        $query = User::active();

        if ($this->is_department_based && isset($this->criteria['department'])) {
            $query->where('department', $this->criteria['department']);
        }

        if (isset($this->criteria['title'])) {
            $query->where('title', 'ILIKE', '%' . $this->criteria['title'] . '%');
        }

        return $query;
    }

    /**
     * Update group membership based on criteria
     */
    public function updateMembership(): int
    {
        if ($this->is_manual) {
            return 0; // Don't auto-update manual groups
        }

        $eligibleUsers = $this->getEligibleUsers()->pluck('id')->toArray();
        $result = $this->syncUsers($eligibleUsers);

        return count($result['attached']) + count($result['detached']);
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(): array
    {
        try {
            $total = $this->notifications()->count();
            $thisMonth = $this->notifications()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            
            $sent = $this->notifications()->where('status', 'sent')->count();
            $failed = $this->notifications()->where('status', 'failed')->count();
            $processing = $this->notifications()->whereIn('status', ['queued', 'processing'])->count();
            
        } catch (\Exception $e) {
            // ถ้าเกิดข้อผิดพลาด
            $total = 0;
            $thisMonth = 0;
            $sent = 0;
            $failed = 0;
            $processing = 0;
        }

        return [
            'total_notifications' => $total,
            'this_month' => $thisMonth,
            'sent_notifications' => $sent,
            'failed_notifications' => $failed,
            'processing_notifications' => $processing,
            'member_count' => $this->member_count,
            'active_member_count' => $this->active_member_count,
        ];
    }

    /**
     * Check if user is member of this group
     */
    public function hasMember($userId): bool
    {
        return $this->users()->where('user_id', $userId)->exists();
    }

    /**
     * Get recent members (joined in last 30 days)
     */
    public function getRecentMembers()
    {
        return $this->users()
            ->wherePivot('joined_at', '>=', now()->subDays(30))
            ->orderBy('pivot_joined_at', 'desc');
    }
}