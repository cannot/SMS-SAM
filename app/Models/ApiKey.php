<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use SoftDeletes, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'key_hash',
        'key_value', // ชั่วคราว สำหรับแสดงผลครั้งแรก
        'is_active',
        'rate_limit_per_minute',
        'rate_limit_per_hour',
        'rate_limit_per_day',
        'usage_count',
        'last_used_at',
        'expires_at',
        'permissions', // JSON array (เก่า - deprecated)
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
        'permissions' => 'array', // deprecated - ใช้ relationship แทน
        'allowed_ips' => 'array',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'key_hash',
        'key_value', // ซ่อน API key value ไม่ให้แสดงใน JSON response
    ];

    protected $appends = [
        'display_key',
        'is_expired',
        'is_valid',
    ];

    // ===========================================
    // UUID GENERATION
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
        return 'uuid'; // ใช้ UUID แทน ID ใน routes
    }

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

    /**
     * API Key สามารถมีหลาย Permissions (many-to-many)
     * เฉพาะ permissions ที่เป็น guard_name = 'api' เท่านั้น
     */
    public function apiPermissions()
    {
        return $this->belongsToMany(Permission::class, 'api_key_permissions')
                    ->where('guard_name', 'api')
                    ->withTimestamps();
    }

    // Backward compatibility - ใช้ relationship ใหม่
    public function permissions()
    {
        return $this->apiPermissions();
    }

    public function usageLogs()
    {
        return $this->hasMany(ApiUsageLog::class);
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

    /**
     * แสดง API Key ในรูปแบบที่ซ่อนส่วนกลาง
     */
    public function getDisplayKeyAttribute(): string
    {
        if (!$this->key_hash && !$this->key_value) {
            return 'Key not generated';
        }
        
        // ถ้ามี key_value (ครั้งแรกหลังสร้าง) ให้แสดงเต็ม
        if ($this->key_value) {
            return $this->key_value;
        }
        
        // ถ้าไม่มี ให้แสดงแบบซ่อน
        $prefix = 'sns_';
        $suffix = substr($this->key_hash, -4);
        return $prefix . str_repeat('*', 28) . '_' . $suffix;
    }

    /**
     * ตรวจสอบว่า API Key หมดอายุหรือยัง
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * ตรวจสอบว่า API Key ใช้งานได้หรือไม่
     */
    public function getIsValidAttribute(): bool
    {
        return $this->is_active && !$this->is_expired && !$this->trashed();
    }

    /**
     * Hash API key เมื่อ set key_value
     */
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

    public function scopeByPermission($query, string $permissionName)
    {
        return $query->whereHas('apiPermissions', function ($q) use ($permissionName) {
            $q->where('name', $permissionName);
        });
    }

    public function scopeUsedInLastDays($query, int $days = 30)
    {
        return $query->where('last_used_at', '>=', now()->subDays($days));
    }

    // ===========================================
    // PERMISSION METHODS
    // ===========================================

    /**
     * ตรวจสอบว่า API Key มีสิทธิ์ตามที่กำหนดหรือไม่
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->apiPermissions()->where('name', $permissionName)->exists();
    }

    /**
     * ตรวจสอบว่า API Key มีสิทธิ์อย่างใดอย่างหนึ่งในรายการหรือไม่
     */
    public function hasAnyPermission(array $permissions): bool
    {
        return $this->apiPermissions()->whereIn('name', $permissions)->exists();
    }

    /**
     * ตรวจสอบว่า API Key มีสิทธิ์ทั้งหมดในรายการหรือไม่
     */
    public function hasAllPermissions(array $permissions): bool
    {
        return $this->apiPermissions()->whereIn('name', $permissions)->count() === count($permissions);
    }

    /**
     * เพิ่มสิทธิ์ให้กับ API Key
     */
    public function givePermissionTo(string|Permission $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)
                                   ->where('guard_name', 'api')
                                   ->firstOrFail();
        }

        $this->apiPermissions()->syncWithoutDetaching([$permission->id]);
        
        // Log event
        $this->logEvent('permission_added', "Permission '{$permission->name}' added", null, [
            'permission_id' => $permission->id,
            'permission_name' => $permission->name
        ]);
        
        return $this;
    }

    /**
     * ลบสิทธิ์ออกจาก API Key
     */
    public function revokePermissionTo(string|Permission $permission): self
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)
                                   ->where('guard_name', 'api')
                                   ->firstOrFail();
        }

        $this->apiPermissions()->detach($permission->id);
        
        // Log event
        $this->logEvent('permission_removed', "Permission '{$permission->name}' removed", [
            'permission_id' => $permission->id,
            'permission_name' => $permission->name
        ], null);
        
        return $this;
    }

    /**
     * กำหนดสิทธิ์ใหม่ทั้งหมด (ลบเก่าและเพิ่มใหม่)
     */
    public function syncPermissions(array $permissions): self
    {
        $oldPermissions = $this->apiPermissions()->pluck('name')->toArray();
        
        $permissionIds = Permission::where('guard_name', 'api')
                                  ->whereIn('name', $permissions)
                                  ->pluck('id')
                                  ->toArray();

        $this->apiPermissions()->sync($permissionIds);
        
        // Log event
        $this->logEvent('permissions_synced', 'Permissions synchronized', [
            'old_permissions' => $oldPermissions
        ], [
            'new_permissions' => $permissions
        ]);
        
        return $this;
    }

    // ===========================================
    // RATE LIMITING METHODS
    // ===========================================

    /**
     * ตรวจสอบว่าเกิน Rate Limit หรือไม่
     */
    public function isRateLimited(string $period = 'minute'): bool
    {
        $usageCount = $this->getUsageCount($period);
        $limit = $this->getRateLimit($period);

        return $usageCount >= $limit;
    }

    /**
     * ดึงจำนวนการใช้งานในช่วงเวลาที่กำหนด
     */
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

    /**
     * ดึงค่า Rate Limit ตามช่วงเวลา
     */
    public function getRateLimit(string $period = 'minute'): int
    {
        return match($period) {
            'minute' => $this->rate_limit_per_minute,
            'hour' => $this->rate_limit_per_hour,
            'day' => $this->rate_limit_per_day,
            default => $this->rate_limit_per_minute,
        };
    }

    /**
     * ตรวจสอบว่า IP address ได้รับอนุญาตหรือไม่
     */
    public function isIpAllowed(string $ipAddress): bool
    {
        if (empty($this->allowed_ips)) {
            return true; // ถ้าไม่มีการจำกัด IP ให้อนุญาตทั้งหมด
        }

        foreach ($this->allowed_ips as $allowedIp) {
            if ($this->ipMatches($ipAddress, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ตรวจสอบว่า IP ตรงกับ pattern ที่กำหนดหรือไม่
     * รองรับ CIDR notation (เช่น 192.168.1.0/24)
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        if ($ip === $pattern) {
            return true;
        }

        // ตรวจสอบ CIDR notation
        if (strpos($pattern, '/') !== false) {
            [$subnet, $bits] = explode('/', $pattern);
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - (int)$bits);
            
            return ($ip & $mask) === ($subnet & $mask);
        }

        return false;
    }

    // ===========================================
    // UTILITY METHODS
    // ===========================================

    /**
     * เพิ่มจำนวนการใช้งาน
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * รีเซ็ตการใช้งาน
     */
    public function resetUsage(User $performedBy = null): void
    {
        $this->update([
            'usage_count' => 0,
            'usage_reset_at' => now(),
            'usage_reset_by' => $performedBy?->id,
        ]);

        $this->logEvent('usage_reset', 'Usage count reset to 0', [
            'old_usage_count' => $this->usage_count
        ], [
            'new_usage_count' => 0
        ], $performedBy);
    }

    /**
     * Regenerate API Key
     */
    public function regenerate(User $performedBy = null): string
    {
        $newKeyValue = self::generateKeyValue();
        
        $this->update([
            'key_value' => $newKeyValue, // ชั่วคราว สำหรับแสดงผล
            'regenerated_at' => now(),
            'regenerated_by' => $performedBy?->id,
        ]);

        $this->logEvent('regenerated', 'API Key regenerated', null, [
            'regenerated_at' => $this->regenerated_at
        ], $performedBy);

        return $newKeyValue;
    }

    /**
     * Clear key_value หลังจากแสดงผลแล้ว
     */
    public function clearKeyValue(): void
    {
        $this->update(['key_value' => null]);
    }

    /**
     * เปลี่ยนสถานะ
     */
    public function toggleStatus(User $performedBy = null): void
    {
        $oldStatus = $this->is_active;
        $newStatus = !$oldStatus;
        
        $this->update([
            'is_active' => $newStatus,
            'status_changed_at' => now(),
            'status_changed_by' => $performedBy?->id,
        ]);

        $this->logEvent(
            $newStatus ? 'activated' : 'deactivated',
            $newStatus ? 'API Key activated' : 'API Key deactivated',
            ['is_active' => $oldStatus],
            ['is_active' => $newStatus],
            $performedBy
        );
    }

    /**
     * บันทึก Event
     */
    public function logEvent(string $eventType, string $description, ?array $oldValues = null, ?array $newValues = null, ?User $performedBy = null): void
    {
        $this->events()->create([
            'event_type' => $eventType,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'performed_by' => $performedBy?->id ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * สร้าง API Key value ใหม่
     */
    public static function generateKeyValue(): string
    {
        return 'sns_' . Str::random(32) . '_' . time();
    }

    /**
     * ตรวจสอบ API Key จาก value
     */
    public static function findByKey(string $keyValue): ?self
    {
        $keyHash = hash('sha256', $keyValue);
        
        return self::where('key_hash', $keyHash)
                   ->active()
                   ->first();
    }
}