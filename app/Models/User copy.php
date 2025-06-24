<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles, LogsActivity;

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
        'last_login_at',
        'ldap_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'ldap_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'remember_token',
    ];

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
            ->logOnly(['username', 'email', 'is_active'])
            ->logOnlyDirty();
    }

    // Relationships
    public function preferences()
    {
        return $this->hasOne(UserPreference::class);
    }

    public function createdNotifications()
    {
        return $this->hasMany(Notification::class, 'created_by');
    }

    public function createdApiKeys()
    {
        return $this->hasMany(ApiKey::class, 'created_by');
    }

    public function createdGroups()
    {
        return $this->hasMany(NotificationGroup::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}