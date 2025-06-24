<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'subject',
        'body_html',
        'body_text',
        'variables',
        'teams_card_template',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'variables' => 'array',
        'teams_card_template' => 'array',
        'is_active' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
        
        static::updating(function ($template) {
            if ($template->isDirty('name') && empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'template_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper Methods
    public function getTypeDisplayAttribute()
    {
        return match($this->type) {
            'email' => 'Email Only',
            'teams' => 'Teams Only',
            'both' => 'Email & Teams',
            default => $this->type
        };
    }

    public function canSendEmail()
    {
        return in_array($this->type, ['email', 'both']);
    }

    public function canSendTeams()
    {
        return in_array($this->type, ['teams', 'both']);
    }

    public function extractVariables($content = null)
    {
        $content = $content ?: ($this->body_html ?: $this->body_text);
        if (!$content) return [];

        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    public function replaceVariables($content, $data = [])
    {
        if (empty($data)) return $content;

        foreach ($data as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value, $content);
        }

        return $content;
    }

    public function getProcessedSubject($data = [])
    {
        return $this->replaceVariables($this->subject, $data);
    }

    public function getProcessedBodyHtml($data = [])
    {
        return $this->replaceVariables($this->body_html, $data);
    }

    public function getProcessedBodyText($data = [])
    {
        return $this->replaceVariables($this->body_text, $data);
    }

    public function getProcessedTeamsCard($data = [])
    {
        if (!$this->teams_card_template) return null;

        $cardJson = json_encode($this->teams_card_template);
        $processedJson = $this->replaceVariables($cardJson, $data);
        
        return json_decode($processedJson, true);
    }

    public function getUsageStatsAttribute()
    {
        return [
            'total_sent' => $this->notifications()->count(),
            'last_used' => $this->notifications()->latest()->first()?->created_at,
        ];
    }
}