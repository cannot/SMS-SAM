<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'key_hash',
        'key_value',
        'is_active',
        'rate_limit_per_minute',
        'rate_limit_per_hour',
        'rate_limit_per_day',
        'usage_count',
        'last_used_at',
        'expires_at',
        'permissions',
        'allowed_ips',
        'metadata',
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
        'is_active' => 'boolean',
        'auto_notifications' => 'boolean',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'status_changed_at' => 'datetime',
        'regenerated_at' => 'datetime',
        'usage_reset_at' => 'datetime',
        'permissions' => 'array',
        'allowed_ips' => 'array',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'key_hash',
        'key_value',
    ];

    protected $appends = [
        'display_key',
        'is_expired',
        'is_valid',
        'status',
        'usage_percentage',
        'masked_key'
    ];

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusChangedBy()
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function regeneratedBy()
    {
        return $this->belongsTo(User::class, 'regenerated_by');
    }

    public function usageResetBy()
    {
        return $this->belongsTo(User::class, 'usage_reset_by');
    }

    public function apiPermissions()
    {
        return $this->belongsToMany(Permission::class, 'api_key_permissions')
                    ->where('guard_name', 'api')
                    ->withTimestamps();
    }

    public function permissions()
    {
        return $this->apiPermissions();
    }

    /**
     * เพิ่ม accessor สำหรับการ access ที่ปลอดภัย
     */
    public function getPermissionsCountAttribute(): int
    {
        return $this->apiPermissions()->count();
    }

    /**
     * Get permission names as array
     */
    public function getPermissionNamesAttribute(): array
    {
        return $this->apiPermissions()->pluck('name')->toArray();
    }

    public function usageLogs()
    {
        return $this->hasMany(ApiUsageLogs::class);
    }

    public function events()
    {
        return $this->hasMany(ApiKeyEvent::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // ===========================================
    // ACCESSORS & MUTATORS
    // ===========================================

    public function getDisplayKeyAttribute(): string
    {
        if (!$this->key_hash && !$this->key_value) {
            return 'Key not generated';
        }
        
        if ($this->key_value) {
            return $this->key_value;
        }
        
        $prefix = 'sns_';
        $suffix = substr($this->key_hash, -4);
        return $prefix . str_repeat('*', 28) . '_' . $suffix;
    }

    public function getMaskedKeyAttribute(): string
    {
        if (!$this->key_hash) {
            return 'Key not generated';
        }
        
        $prefix = 'sns_';
        $suffix = substr($this->key_hash, -4);
        return $prefix . str_repeat('*', 28) . '_' . $suffix;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsValidAttribute(): bool
    {
        return $this->is_active && !$this->is_expired && !$this->trashed();
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if ($this->is_expired) {
            return 'expired';
        }
        
        if ($this->expires_at && $this->expires_at->lte(now()->addDays(30))) {
            return 'expiring_soon';
        }
        
        return 'active';
    }

    public function getUsagePercentageAttribute(): float
    {
        $currentUsage = $this->getUsageCount('minute');
        $rateLimit = $this->rate_limit_per_minute ?? 60;
        
        if ($rateLimit <= 0) return 0;
        
        return min(($currentUsage / $rateLimit) * 100, 100);
    }

    public function setKeyValueAttribute($value)
    {
        if ($value) {
            $this->attributes['key_value'] = $value;
            $this->attributes['key_hash'] = hash('sha256', $value);
        }
    }

    // ===========================================
    // SCOPES
    // ===========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now());
    }

    // ===========================================
    // PERMISSION METHODS
    // ===========================================

    public function hasPermission(string $permissionName): bool
    {
        return $this->apiPermissions()->where('name', $permissionName)->exists();
    }

    public function givePermissionTo(string|Permission $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)
                                   ->where('guard_name', 'api')
                                   ->firstOrFail();
        }

        $this->apiPermissions()->syncWithoutDetaching([$permission->id]);
        
        return $this;
    }

    // ===========================================
    // RATE LIMITING METHODS
    // ===========================================

    public function getUsageCount(string $period = 'minute'): int
    {
        $startTime = match($period) {
            'minute' => now()->startOfMinute(),
            'hour' => now()->startOfHour(),
            'day' => now()->startOfDay(),
            default => now()->startOfMinute(),
        };

        return $this->usageLogs()
                   ->where('created_at', '>=', $startTime)
                   ->count();
    }

    public function getRateLimit(string $period = 'minute'): int
    {
        return match($period) {
            'minute' => $this->rate_limit_per_minute ?? 60,
            'hour' => $this->rate_limit_per_hour ?? 3600,
            'day' => $this->rate_limit_per_day ?? 86400,
            default => $this->rate_limit_per_minute ?? 60,
        };
    }

    public function getSuccessRate(): float
    {
        $totalRequests = $this->usageLogs()->count();
        
        if ($totalRequests === 0) {
            return 100.0;
        }
        
        $successfulRequests = $this->usageLogs()
                                  ->where('response_code', '>=', 200)
                                  ->where('response_code', '<', 400)
                                  ->count();
        
        return ($successfulRequests / $totalRequests) * 100;
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    // ===========================================
    // UTILITY METHODS
    // ===========================================

    public static function generateKeyValue(): string
    {
        return 'sns_' . Str::random(32) . '_' . time();
    }

    public static function findByKey(string $keyValue): ?self
    {
        $keyHash = hash('sha256', $keyValue);
        
        return self::where('key_hash', $keyHash)
                   ->active()
                   ->first();
    }

    public function clearKeyValue(): void
    {
        $this->update(['key_value' => null]);
    }

    public function toggleStatus(User $performedBy = null): void
    {
        $oldStatus = $this->is_active;
        $newStatus = !$oldStatus;
        
        $this->update([
            'is_active' => $newStatus,
            'status_changed_at' => now(),
            'status_changed_by' => $performedBy?->id,
        ]);
    }

    public function regenerate(User $performedBy = null): string
    {
        $newKeyValue = self::generateKeyValue();
        
        $this->update([
            'key_value' => $newKeyValue,
            'regenerated_at' => now(),
            'regenerated_by' => $performedBy?->id,
        ]);

        return $newKeyValue;
    }

    // ===========================================
    // BOOT METHOD
    // ===========================================
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Verify if the provided API key value matches this API key
     */
    public function verifyKey(string $keyValue): bool
    {
        return hash('sha256', $keyValue) === $this->key_hash;
    }

    /**
     * Check if API key can be used for the request
     */
    public function canBeUsed(?string $ipAddress = null, ?string $requiredPermission = null): array
    {
        $errors = [];
        
        // Check if API key is active
        if (!$this->is_active) {
            $errors[] = 'API key is inactive';
        }
        
        // Check if API key is expired
        if ($this->expires_at && $this->expires_at->isPast()) {
            $errors[] = 'API key has expired';
        }
        
        // Check IP restrictions
        if ($ipAddress && $this->allowed_ips && !empty($this->allowed_ips)) {
            $isIpAllowed = false;
            foreach ($this->allowed_ips as $allowedIp) {
                if ($this->ipMatches($ipAddress, $allowedIp)) {
                    $isIpAllowed = true;
                    break;
                }
            }
            
            if (!$isIpAllowed) {
                $errors[] = 'IP address not allowed';
            }
        }
        
        // Check permissions
        if ($requiredPermission && !$this->hasPermission($requiredPermission)) {
            $errors[] = 'Insufficient permissions';
        }
        
        return [
            'can_use' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check rate limit for this API key
     */
    public function checkRateLimit(): array
    {
        $currentMinuteUsage = $this->getUsageCount('minute');
        $currentHourUsage = $this->getUsageCount('hour');
        $currentDayUsage = $this->getUsageCount('day');
        
        $limitExceeded = false;
        $resetTime = null;
        $retryAfter = null;
        
        // Check minute limit
        if ($currentMinuteUsage >= $this->rate_limit_per_minute) {
            $limitExceeded = true;
            $resetTime = now()->addMinute()->startOfMinute();
            $retryAfter = $resetTime->diffInSeconds(now());
        }
        
        // Check hour limit
        if ($currentHourUsage >= $this->rate_limit_per_hour) {
            $limitExceeded = true;
            $hourResetTime = now()->addHour()->startOfHour();
            if (!$resetTime || $hourResetTime->greaterThan($resetTime)) {
                $resetTime = $hourResetTime;
                $retryAfter = $resetTime->diffInSeconds(now());
            }
        }
        
        // Check day limit
        if ($currentDayUsage >= $this->rate_limit_per_day) {
            $limitExceeded = true;
            $dayResetTime = now()->addDay()->startOfDay();
            if (!$resetTime || $dayResetTime->greaterThan($resetTime)) {
                $resetTime = $dayResetTime;
                $retryAfter = $resetTime->diffInSeconds(now());
            }
        }
        
        return [
            'limit_exceeded' => $limitExceeded,
            'current_usage' => [
                'minute' => $currentMinuteUsage,
                'hour' => $currentHourUsage,
                'day' => $currentDayUsage
            ],
            'limits' => [
                'minute' => $this->rate_limit_per_minute,
                'hour' => $this->rate_limit_per_hour,
                'day' => $this->rate_limit_per_day
            ],
            'reset_time' => $resetTime,
            'retry_after' => $retryAfter
        ];
    }

    /**
     * Check if IP address matches the allowed pattern
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        // Exact match
        if ($ip === $pattern) {
            return true;
        }
        
        // CIDR notation
        if (strpos($pattern, '/') !== false) {
            return $this->ipInCidr($ip, $pattern);
        }
        
        // Wildcard patterns (192.168.1.*)
        if (strpos($pattern, '*') !== false) {
            $pattern = str_replace('*', '.*', $pattern);
            return preg_match("/^{$pattern}$/", $ip);
        }
        
        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        list($network, $mask) = explode('/', $cidr);
        
        $ipLong = ip2long($ip);
        $networkLong = ip2long($network);
        
        if ($ipLong === false || $networkLong === false) {
            return false;
        }
        
        $maskLong = -1 << (32 - (int)$mask);
        
        return ($ipLong & $maskLong) === ($networkLong & $maskLong);
    }


}