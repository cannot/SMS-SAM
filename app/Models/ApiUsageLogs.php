<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageLogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_key_id',
        'notification_id',
        'endpoint',
        'method',
        'ip_address',
        'user_agent',
        'response_code',
        'response_time',
        'request_data',
        'response_data',
        'error_message',
        'request_id',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'created_at' => 'datetime',
    ];

    // Disable updated_at since this is a log table
    public $timestamps = false;
    
    // Only use created_at
    protected $dates = ['created_at'];

    protected $appends = [
        'status_text',
        'response_time_human',
        'is_error',
        'is_success',
    ];

    // ===================================
    // RELATIONSHIPS
    // ===================================

    /**
     * The API key that made this request
     */
    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class, 'api_key_id');
    }

    /**
     * The notification that was created (if applicable)
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }

    // ===================================
    // SCOPES
    // ===================================

    /**
     * Scope for successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->where('response_code', '<', 400);
    }

    /**
     * Scope for failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('response_code', '>=', 400);
    }

    /**
     * Scope for error requests (5xx)
     */
    public function scopeErrors($query)
    {
        return $query->where('response_code', '>=', 500);
    }

    /**
     * Scope for client error requests (4xx)
     */
    public function scopeClientErrors($query)
    {
        return $query->whereBetween('response_code', [400, 499]);
    }

    /**
     * Scope for rate limit violations
     */
    public function scopeRateLimited($query)
    {
        return $query->where('response_code', 429);
    }

    /**
     * Scope for slow requests
     */
    public function scopeSlow($query, $thresholdMs = 1000)
    {
        return $query->where('response_time', '>', $thresholdMs);
    }

    /**
     * Scope for recent requests
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for requests from specific IP
     */
    public function scopeFromIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope for specific endpoint
     */
    public function scopeEndpoint($query, $endpoint)
    {
        return $query->where('endpoint', 'like', "%{$endpoint}%");
    }

    /**
     * Scope for specific method
     */
    public function scopeMethod($query, $method)
    {
        return $query->where('method', strtoupper($method));
    }

    // ===================================
    // ACCESSORS
    // ===================================

    /**
     * Get human readable status text
     */
    public function getStatusTextAttribute(): string
    {
        $code = $this->response_code;

        if ($code >= 200 && $code < 300) {
            return 'Success';
        } elseif ($code >= 300 && $code < 400) {
            return 'Redirect';
        } elseif ($code >= 400 && $code < 500) {
            return 'Client Error';
        } elseif ($code >= 500) {
            return 'Server Error';
        }

        return 'Unknown';
    }

    /**
     * Get human readable response time
     */
    public function getResponseTimeHumanAttribute(): string
    {
        if (!$this->response_time) {
            return 'N/A';
        }

        if ($this->response_time < 1000) {
            return $this->response_time . 'ms';
        }

        return round($this->response_time / 1000, 2) . 's';
    }

    /**
     * Check if this is an error response
     */
    public function getIsErrorAttribute(): bool
    {
        return $this->response_code >= 400;
    }

    /**
     * Check if this is a successful response
     */
    public function getIsSuccessAttribute(): bool
    {
        return $this->response_code >= 200 && $this->response_code < 400;
    }

    // ===================================
    // METHODS
    // ===================================

    /**
     * Get color class for status badge
     */
    public function getStatusColorClass(): string
    {
        $code = $this->response_code;

        if ($code >= 200 && $code < 300) {
            return 'success';
        } elseif ($code >= 300 && $code < 400) {
            return 'info';
        } elseif ($code >= 400 && $code < 500) {
            return 'warning';
        } elseif ($code >= 500) {
            return 'danger';
        }

        return 'secondary';
    }

    /**
     * Get response time color class based on performance
     */
    public function getResponseTimeColorClass(): string
    {
        if (!$this->response_time) {
            return 'secondary';
        }

        if ($this->response_time < 200) {
            return 'success'; // Fast
        } elseif ($this->response_time < 1000) {
            return 'info'; // Acceptable
        } elseif ($this->response_time < 3000) {
            return 'warning'; // Slow
        }

        return 'danger'; // Very slow
    }

    /**
     * Get sanitized request data (remove sensitive information)
     */
    public function getSanitizedRequestData(): ?array
    {
        if (!$this->request_data) {
            return null;
        }

        $data = $this->request_data;

        // Remove sensitive fields
        $sensitiveFields = ['password', 'token', 'api_key', 'secret', 'key'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***REDACTED***';
            }
        }

        return $data;
    }

    /**
     * Get error details if this is an error response
     */
    public function getErrorDetails(): ?array
    {
        if (!$this->is_error) {
            return null;
        }

        return [
            'code' => $this->response_code,
            'message' => $this->error_message,
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'timestamp' => $this->created_at,
            'ip' => $this->ip_address,
        ];
    }

    /**
     * Check if this request was rate limited
     */
    public function isRateLimited(): bool
    {
        return $this->response_code === 429;
    }

    /**
     * Check if this request was unauthorized
     */
    public function isUnauthorized(): bool
    {
        return in_array($this->response_code, [401, 403]);
    }

    /**
     * Check if this request was a server error
     */
    public function isServerError(): bool
    {
        return $this->response_code >= 500;
    }

    /**
     * Get geographic location from IP (if service is available)
     */
    public function getGeographicLocation(): ?array
    {
        // This would integrate with a GeoIP service
        // For now, return null
        return null;
    }

    // ===================================
    // STATIC METHODS
    // ===================================

    /**
     * Log an API request
     */
    public static function logRequest(
        int $apiKeyId,
        string $endpoint,
        string $method,
        string $ipAddress,
        int $responseCode,
        ?int $responseTime = null,
        ?array $requestData = null,
        ?array $responseData = null,
        ?string $errorMessage = null,
        ?string $userAgent = null,
        ?int $notificationId = null,
        ?string $requestId = null
    ): self {
        return self::create([
            'api_key_id' => $apiKeyId,
            'notification_id' => $notificationId,
            'endpoint' => $endpoint,
            'method' => strtoupper($method),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'response_code' => $responseCode,
            'response_time' => $responseTime,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'error_message' => $errorMessage,
            'request_id' => $requestId ?: \Illuminate\Support\Str::uuid(),
            'created_at' => now(),
        ]);
    }

    /**
     * Get usage statistics for a time period
     */
    public static function getStatistics(\Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        $query = self::whereBetween('created_at', [$from, $to]);

        return [
            'total_requests' => $query->count(),
            'successful_requests' => $query->where('response_code', '<', 400)->count(),
            'failed_requests' => $query->where('response_code', '>=', 400)->count(),
            'average_response_time' => $query->avg('response_time'),
            'rate_limit_violations' => $query->where('response_code', 429)->count(),
            'server_errors' => $query->where('response_code', '>=', 500)->count(),
            'unique_ips' => $query->distinct('ip_address')->count(),
            'unique_api_keys' => $query->distinct('api_key_id')->count(),
        ];
    }

    /**
     * Get top endpoints by request count
     */
    public static function getTopEndpoints(int $limit = 10, int $days = 30): \Illuminate\Support\Collection
    {
        return self::selectRaw('endpoint, method, COUNT(*) as request_count')
            ->selectRaw('AVG(response_time) as avg_response_time')
            ->selectRaw('COUNT(CASE WHEN response_code >= 400 THEN 1 END) as error_count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('endpoint', 'method')
            ->orderByDesc('request_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get suspicious activity
     */
    public static function getSuspiciousActivity(int $days = 7): \Illuminate\Support\Collection
    {
        return self::selectRaw('ip_address, COUNT(*) as request_count')
            ->selectRaw('COUNT(DISTINCT api_key_id) as unique_keys')
            ->selectRaw('COUNT(CASE WHEN response_code >= 400 THEN 1 END) as error_count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('ip_address')
            ->havingRaw('request_count > 1000 OR unique_keys > 5 OR error_count > 100')
            ->orderByDesc('request_count')
            ->get();
    }

    // ===================================
    // BOOT METHOD
    // ===================================

    protected static function boot()
    {
        parent::boot();

        // Automatically set created_at when creating
        static::creating(function (ApiUsageLogs $log) {
            if (!$log->created_at) {
                $log->created_at = now();
            }
        });
    }
}