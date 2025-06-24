<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ApiKey extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'key_hash',
        'is_active',
        'rate_limit_per_minute',
        'usage_count',
        'last_used_at',
        'expires_at',
        'permissions',
        'ip_whitelist',
        'assigned_to',
        'created_by',
        'auto_notifications',
        'notification_webhook',
        'status_changed_at',
        'status_changed_by',
        'regenerated_at',
        'regenerated_by',
        'usage_reset_at',
        'usage_reset_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'ip_whitelist' => 'array',
        'is_active' => 'boolean',
        'auto_notifications' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'regenerated_at' => 'datetime',
        'usage_reset_at' => 'datetime',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected $appends = [
        'status',
        'is_expired',
        'days_until_expiry',
        'usage_percentage',
        'masked_key',
    ];

    // ===================================
    // RELATIONSHIPS
    // ===================================

    /**
     * The user who created this API key
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The user assigned to this API key
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * User who last changed the status
     */
    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    /**
     * User who regenerated the key
     */
    public function regeneratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'regenerated_by');
    }

    /**
     * User who reset the usage
     */
    public function usageResetBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usage_reset_by');
    }

    /**
     * Notifications created using this API key
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'api_key_id');
    }

    /**
     * Usage logs for this API key
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(ApiUsageLogs::class, 'api_key_id');
    }

    // ===================================
    // SCOPES
    // ===================================

    /**
     * Scope for active API keys
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for inactive API keys
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope for expired API keys
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope for API keys expiring soon
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expires_at', '>', now())
                    ->where('expires_at', '<=', now()->addDays($days));
    }

    /**
     * Scope for API keys assigned to a user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope for API keys created by a user
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    // ===================================
    // ACCESSORS
    // ===================================

    /**
     * Get the status of the API key
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->expires_at && $this->expires_at <= now()) {
            return 'expired';
        }

        if ($this->expires_at && $this->expires_at <= now()->addDays(30)) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /**
     * Check if API key is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at <= now();
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        $days = now()->diffInDays($this->expires_at, false);
        return $days < 0 ? 0 : $days;
    }

    /**
     * Get usage percentage of rate limit
     */
    public function getUsagePercentageAttribute(): float
    {
        if ($this->rate_limit_per_minute === 0) {
            return 0;
        }

        $currentMinuteUsage = $this->usageLogs()
            ->where('created_at', '>=', now()->startOfMinute())
            ->count();

        return round(($currentMinuteUsage / $this->rate_limit_per_minute) * 100, 2);
    }

    /**
     * Get masked API key for display
     */
    public function getMaskedKeyAttribute(): string
    {
        return 'sns_' . str_repeat('*', 28) . '_****';
    }

    // ===================================
    // METHODS
    // ===================================

    /**
     * Verify if a given key matches this API key
     */
    public function verifyKey(string $key): bool
    {
        return Hash::check($key, $this->key_hash);
    }

    /**
     * Check if API key has permission
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->permissions) {
            return false;
        }

        return in_array($permission, $this->permissions);
    }

    /**
     * Check if IP is whitelisted
     */
    public function isIpWhitelisted(string $ip): bool
    {
        if (!$this->ip_whitelist || empty($this->ip_whitelist)) {
            return true; // No whitelist means all IPs are allowed
        }

        return in_array($ip, $this->ip_whitelist);
    }

    /**
     * Check if API key can be used
     */
    public function canBeUsed(string $ip = null, string $permission = null): array
    {
        $errors = [];

        // Check if active
        if (!$this->is_active) {
            $errors[] = 'API key is inactive';
        }

        // Check if expired
        if ($this->is_expired) {
            $errors[] = 'API key has expired';
        }

        // Check IP whitelist
        if ($ip && !$this->isIpWhitelisted($ip)) {
            $errors[] = 'IP address not whitelisted';
        }

        // Check permission
        if ($permission && !$this->hasPermission($permission)) {
            $errors[] = 'Insufficient permissions';
        }

        return [
            'can_use' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check rate limit
     */
    public function checkRateLimit(): array
    {
        $currentMinuteUsage = $this->usageLogs()
            ->where('created_at', '>=', now()->startOfMinute())
            ->count();

        $limitExceeded = $currentMinuteUsage >= $this->rate_limit_per_minute;

        return [
            'limit_exceeded' => $limitExceeded,
            'current_usage' => $currentMinuteUsage,
            'limit' => $this->rate_limit_per_minute,
            'remaining' => max(0, $this->rate_limit_per_minute - $currentMinuteUsage),
            'reset_time' => now()->addMinute()->startOfMinute()
        ];
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats(): array
    {
        $baseQuery = $this->usageLogs();

        return [
            'total_requests' => $this->usage_count,
            'requests_today' => $baseQuery->whereDate('created_at', today())->count(),
            'requests_this_week' => $baseQuery->whereBetween('created_at', [
                now()->startOfWeek(), 
                now()->endOfWeek()
            ])->count(),
            'requests_this_month' => $baseQuery->whereMonth('created_at', now()->month)->count(),
            'average_response_time' => $baseQuery->avg('response_time') ?: 0,
            'success_rate' => $this->getSuccessRate(),
            'last_used' => $this->last_used_at?->diffForHumans(),
        ];
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        $totalRequests = $this->usage_count;
        if ($totalRequests === 0) {
            return 100;
        }

        $successfulRequests = $this->usageLogs()
            ->where('response_code', '<', 400)
            ->count();

        return round(($successfulRequests / $totalRequests) * 100, 2);
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $this->usageLogs()
            ->with('notification')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get error logs
     */
    public function getErrorLogs(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return $this->usageLogs()
            ->where('response_code', '>=', 400)
            ->whereDate('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get top endpoints
     */
    public function getTopEndpoints(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->usageLogs()
            ->selectRaw('endpoint, method, COUNT(*) as count')
            ->groupBy('endpoint', 'method')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    // ===================================
    // ACTIVITY LOG CONFIGURATION
    // ===================================

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'description',
                'is_active',
                'rate_limit_per_minute',
                'permissions',
                'ip_whitelist',
                'assigned_to',
                'expires_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ===================================
    // BOOT METHOD
    // ===================================

    protected static function boot()
    {
        parent::boot();

        // Auto-delete related usage logs when API key is deleted
        static::deleting(function (ApiKey $apiKey) {
            $apiKey->usageLogs()->delete();
        });
    }
}