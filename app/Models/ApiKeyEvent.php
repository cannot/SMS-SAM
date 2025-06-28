<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKeyEvent extends Model
{
    use HasFactory;

    const EVENT_CREATED     = 'created';
    const EVENT_UPDATED     = 'updated';
    const EVENT_DELETED     = 'deleted';
    const EVENT_REGENERATED = 'regenerated';
    const EVENT_ACTIVATED   = 'activated';
    const EVENT_DEACTIVATED = 'deactivated';
    const EVENT_USAGE_RESET = 'usage_reset';

    protected $fillable = [
        'api_key_id',
        'event_type',
        'description',
        'old_values',
        'new_values',
        'performed_by',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ===========================================
    // RELATIONSHIPS
    // ===========================================

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ===========================================
    // STATIC METHODS
    // ===========================================

    public static function getRecentActivity(ApiKey $apiKey, int $limit = 10)
    {
        return self::where('api_key_id', $apiKey->id)
            ->with('performedBy:id,display_name,username')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public static function getEventStats(ApiKey $apiKey): array
    {
        $stats = self::where('api_key_id', $apiKey->id)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->event_type => $item->count];
            })
            ->toArray();

        return $stats;
    }
}
