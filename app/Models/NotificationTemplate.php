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
        'description',
        'slug',
        'category',
        'subject_template',
        'body_html_template',
        'body_text_template',
        'variables',
        'default_variables',
        'supported_channels',
        'priority',
        'is_active',
        'version',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'variables' => 'array',
        'default_variables' => 'array',
        'supported_channels' => 'array',
        'is_active' => 'boolean',
        'version' => 'integer'
    ];

    /**
     * Boot method to generate slug
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
            $template->version = 1;
        });

        static::updating(function ($template) {
            if ($template->isDirty(['subject_template', 'body_html_template', 'body_text_template'])) {
                $template->version++;
            }
        });
    }

    /**
     * Generate unique slug
     */
    public function generateUniqueSlug()
    {
        $baseSlug = Str::slug($this->name);
        $slug = $baseSlug;
        $counter = 1;

        while (self::where('slug', $slug)->where('id', '!=', $this->id)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
    
    /**
     * Render template with variables
     */
    public function render(array $variables = []): array
    {
        // Merge with default variables - ใช้เมธอด helper
        $defaultVars = $this->getDefaultVariablesArray();
        $allVariables = array_merge($defaultVars, $variables);
        
        // Add system variables
        $systemVariables = $this->getSystemVariables();
        $allVariables = array_merge($systemVariables, $allVariables);

        return [
            'subject' => $this->renderContent($this->subject_template, $allVariables),
            'body_html' => $this->renderContent($this->body_html_template, $allVariables),
            'body_text' => $this->renderContent($this->body_text_template, $allVariables),
            'variables_used' => $allVariables
        ];
    }

    /**
     * Render content with variables
     */
    private function renderContent(?string $template, array $variables): ?string
    {
        if (empty($template)) {
            return null;
        }

        $content = $template;

        // ตรวจสอบว่า variables เป็น array
        if (!is_array($variables)) {
            $variables = [];
        }

        // Replace simple variables {{variable}}
        foreach ($variables as $key => $value) {
            // ตรวจสอบให้แน่ใจว่า key เป็น string และ value สามารถแปลงเป็น string ได้
            if (is_string($key) && (is_string($value) || is_numeric($value) || is_bool($value))) {
                $content = str_replace('{{' . $key . '}}', (string)$value, $content);
            }
        }

        // Handle conditional blocks {{#if variable}} content {{/if}}
        $content = $this->processConditionals($content, $variables);

        // Handle loops {{#each items}} content {{/each}}
        $content = $this->processLoops($content, $variables);

        // Handle date formatting {{date:format|variable}}
        $content = $this->processDateFormatting($content, $variables);

        return $content;
    }

    /**
     * Process conditional blocks
     */
    private function processConditionals(string $content, array $variables): string
    {
        // Simple if conditions: {{#if variable}} content {{/if}}
        return preg_replace_callback(
            '/\{\{#if\s+([^}]+)\}\}(.*?)\{\{\/if\}\}/s',
            function ($matches) use ($variables) {
                $variable = trim($matches[1]);
                $content = $matches[2];
                
                if (!empty($variables[$variable])) {
                    return $content;
                }
                
                return '';
            },
            $content
        );
    }

    /**
     * Process loop blocks
     */
    private function processLoops(string $content, array $variables): string
    {
        // Simple each loops: {{#each items}} {{this.name}} {{/each}}
        return preg_replace_callback(
            '/\{\{#each\s+([^}]+)\}\}(.*?)\{\{\/each\}\}/s',
            function ($matches) use ($variables) {
                $variable = trim($matches[1]);
                $template = $matches[2];
                
                if (!isset($variables[$variable]) || !is_array($variables[$variable])) {
                    return '';
                }
                
                $result = '';
                foreach ($variables[$variable] as $item) {
                    $itemContent = $template;
                    
                    if (is_array($item)) {
                        foreach ($item as $key => $value) {
                            $itemContent = str_replace('{{this.' . $key . '}}', $value, $itemContent);
                        }
                    } else {
                        $itemContent = str_replace('{{this}}', $item, $itemContent);
                    }
                    
                    $result .= $itemContent;
                }
                
                return $result;
            },
            $content
        );
    }

    /**
     * Process date formatting
     */
    private function processDateFormatting(string $content, array $variables): string
    {
        // Date formatting: {{date:Y-m-d|created_at}}
        return preg_replace_callback(
            '/\{\{date:([^|]+)\|([^}]+)\}\}/',
            function ($matches) use ($variables) {
                $format = $matches[1];
                $variable = $matches[2];
                
                if (!isset($variables[$variable])) {
                    return '';
                }
                
                try {
                    $date = $variables[$variable];
                    if (!$date instanceof \DateTime) {
                        $date = new \DateTime($date);
                    }
                    return $date->format($format);
                } catch (\Exception $e) {
                    return $variables[$variable]; // Return original if date parsing fails
                }
            },
            $content
        );
    }

    /**
     * Get system variables
     */
    private function getSystemVariables(): array
    {
        return [
            'app_name' => config('app.name', 'Smart Notification System'),
            'app_url' => config('app.url'),
            'current_date' => now()->format('Y-m-d'),
            'current_time' => now()->format('H:i:s'),
            'current_datetime' => now()->format('Y-m-d H:i:s'),
            'year' => now()->format('Y'),
            'month' => now()->format('m'),
            'day' => now()->format('d'),
        ];
    }

    public function renderSubject($variables)
    {
        return $this->renderContent($this->subject_template, $variables);
    }

    public function renderBodyHtml($variables)  
    {
        return $this->renderContent($this->body_html_template, $variables);
    }

    public function renderBodyText($variables)
    {
        return $this->renderContent($this->body_text_template, $variables);
    }

/**
     * Validate template syntax
     */
    public function validateTemplate(): array
    {
        $errors = [];

        // Check for unclosed tags
        $content = ($this->subject_template ?? '') . ' ' . ($this->body_html_template ?? '') . ' ' . ($this->body_text_template ?? '');
        
        // Check for unmatched if statements
        $ifCount = preg_match_all('/\{\{#if\s+[^}]+\}\}/', $content);
        $endIfCount = preg_match_all('/\{\{\/if\}\}/', $content);
        
        if ($ifCount !== $endIfCount) {
            $errors[] = 'Unmatched {{#if}} and {{/if}} tags';
        }

        // Check for unmatched each statements
        $eachCount = preg_match_all('/\{\{#each\s+[^}]+\}\}/', $content);
        $endEachCount = preg_match_all('/\{\{\/each\}\}/', $content);
        
        if ($eachCount !== $endEachCount) {
            $errors[] = 'Unmatched {{#each}} and {{/each}} tags';
        }

        // Check required variables - ใช้เมธอด helper ที่ปลอดภัย
        $requiredVars = $this->getVariablesArray();
        $foundVars = [];
        
        // ตรวจสอบให้แน่ใจว่า $content ไม่เป็น null
        if (!empty($content)) {
            preg_match_all('/\{\{([^}#\/][^}]*)\}\}/', $content, $matches);
            
            if (isset($matches[1]) && is_array($matches[1])) {
                foreach ($matches[1] as $match) {
                    $varName = trim(explode(':', $match)[0]); // Handle date formatting
                    $varName = trim(explode('|', $varName)[0]); // Handle pipes
                    if (!empty($varName)) {
                        $foundVars[] = $varName;
                    }
                }
            }
        }

        // ตรวจสอบให้แน่ใจว่าทั้ง $requiredVars และ $foundVars เป็น array
        if (is_array($requiredVars) && is_array($foundVars)) {
            $missingRequired = array_diff($requiredVars, $foundVars);
            if (!empty($missingRequired)) {
                // แปลง array elements เป็น string ก่อน implode
                $missingStrings = array_map(function($item) {
                    return is_string($item) ? $item : (string)$item;
                }, $missingRequired);
                $errors[] = 'Missing required variables: ' . implode(', ', $missingStrings);
            }
        }

        return $errors;
    }

    /**
     * Extract variables from template
     */
    public function extractVariables(): array
    {
        $content = ($this->subject_template ?? '') . ' ' . ($this->body_html_template ?? '') . ' ' . ($this->body_text_template ?? '');
        $variables = [];

        // ตรวจสอบว่า content ไม่เป็นค่าว่าง
        if (empty($content)) {
            return [];
        }

        // Extract simple variables {{variable}}
        preg_match_all('/\{\{([^}#\/][^}]*)\}\}/', $content, $matches);
        
        if (isset($matches[1]) && is_array($matches[1])) {
            foreach ($matches[1] as $match) {
                // Clean up variable name
                $varName = trim($match);
                $varName = explode(':', $varName)[0]; // Remove date formatting
                $varName = explode('|', $varName)[0]; // Remove pipes
                $varName = trim($varName);
                
                if (!empty($varName) && !in_array($varName, $variables) && !$this->isSystemVariable($varName)) {
                    $variables[] = $varName;
                }
            }
        }

        // Extract conditional variables {{#if variable}}
        preg_match_all('/\{\{#if\s+([^}]+)\}\}/', $content, $ifMatches);
        if (isset($ifMatches[1]) && is_array($ifMatches[1])) {
            foreach ($ifMatches[1] as $match) {
                $varName = trim($match);
                if (!empty($varName) && !in_array($varName, $variables) && !$this->isSystemVariable($varName)) {
                    $variables[] = $varName;
                }
            }
        }

        // Extract loop variables {{#each items}}
        preg_match_all('/\{\{#each\s+([^}]+)\}\}/', $content, $eachMatches);
        if (isset($eachMatches[1]) && is_array($eachMatches[1])) {
            foreach ($eachMatches[1] as $match) {
                $varName = trim($match);
                if (!empty($varName) && !in_array($varName, $variables) && !$this->isSystemVariable($varName)) {
                    $variables[] = $varName;
                }
            }
        }

        return array_unique($variables);
    }

    /**
     * Check if variable is a system variable
     */
    private function isSystemVariable(string $variable): bool
    {
        $systemVars = array_keys($this->getSystemVariables());
        return in_array($variable, $systemVars);
    }

    /**
     * Create preview with sample data
     */
    public function previewx($sampleData = [])
    {
        $preview = [
            'subject' => $this->subject_template,
            'body_html' => $this->body_html_template,
            'body_text' => $this->body_text_template
        ];

        // ถ้าไม่มี sample data ให้ใช้ default variables
        if (empty($sampleData)) {
            $sampleData = $this->default_variables ?? [];
        }

        // แทนที่ตัวแปรในแต่ละส่วน
        foreach ($preview as $key => $content) {
            if ($content) {
                foreach ($sampleData as $var => $value) {
                    $preview[$key] = str_replace(
                        '{{' . $var . '}}',  // search
                        (string)$value,      // replace (แปลงเป็น string)
                        $preview[$key]       // subject
                    );
                }
            }
        }

        return $preview;
    }

    /**
     * Create preview with sample data
     */
    public function preview($sampleData = [])
    {
        $preview = [
            'subject' => $this->subject_template,
            'body_html' => $this->body_html_template,
            'body_text' => $this->body_text_template
        ];

        // ตรวจสอบและแปลง $sampleData ให้เป็น array
        if (is_string($sampleData)) {
            // ลองแปลงจาก JSON string
            $decoded = json_decode($sampleData, true);
            $sampleData = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($sampleData)) {
            // ถ้าไม่ใช่ array ให้ใช้ array เปล่า
            $sampleData = [];
        }

        // ถ้าไม่มี sample data ให้ใช้ default variables
        if (empty($sampleData)) {
            $defaultVars = $this->default_variables;
            
            // ตรวจสอบ default_variables ด้วย
            if (is_string($defaultVars)) {
                $decoded = json_decode($defaultVars, true);
                $defaultVars = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($defaultVars)) {
                $defaultVars = [];
            }
            
            $sampleData = $defaultVars;
        }

        // เพิ่ม system variables
        $systemVars = $this->getSystemVariables();
        $sampleData = array_merge($systemVars, $sampleData);

        // แทนที่ตัวแปรในแต่ละส่วน
        foreach ($preview as $key => $content) {
            if ($content && is_array($sampleData)) {
                foreach ($sampleData as $var => $value) {
                    // ตรวจสอบว่า $var และ $value เป็น string
                    if (is_string($var) && (is_string($value) || is_numeric($value))) {
                        $preview[$key] = str_replace(
                            '{{' . $var . '}}',     // search
                            (string)$value,         // replace (แปลงเป็น string)
                            $preview[$key]          // subject
                        );
                    }
                }
            }
        }

        return $preview;
    }

    /**
     * Relationships
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSupportsChannel($query, $channel)
    {
        return $query->whereJsonContains('supported_channels', $channel);
    }

    /**
     * Static methods
     */
    public static function getCategories()
    {
        return [
            'system' => 'System Notifications',
            'user' => 'User Notifications', 
            'alert' => 'Alerts & Warnings',
            'marketing' => 'Marketing Messages',
            'transactional' => 'Transactional Messages',
            'reminder' => 'Reminders',
            'welcome' => 'Welcome Messages',
            'custom' => 'Custom Templates'
        ];
    }

    /**
     * Check if template can send email
     */
    public function canSendEmail(): bool
    {
        return in_array('email', $this->supported_channels ?? []);
    }

    /**
     * Check if template can send to Teams
     */
    public function canSendTeams(): bool
    {
        return in_array('teams', $this->supported_channels ?? []);
    }

    /**
     * Check if template can send SMS
     */
    public function canSendSms(): bool
    {
        return in_array('sms', $this->supported_channels ?? []);
    }

    /**
     * Get type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return match($this->type) {
            'email' => 'Email Only',
            'teams' => 'Teams Only',
            'both' => 'Email & Teams',
            default => ucfirst($this->type)
        };
    }

    public static function getAvailableVariables()
    {
        return [
            'user_name' => 'User full name',
            'user_email' => 'User email address',
            'user_first_name' => 'User first name',
            'user_last_name' => 'User last name',
            'user_department' => 'User department',
            'user_title' => 'User job title',
            'company' => 'Company name',
            'message' => 'Custom message content',
            'subject' => 'Custom subject',
            'url' => 'Custom URL/link',
            'deadline' => 'Deadline date',
            'amount' => 'Amount/price',
            'date' => 'Custom date',
            'time' => 'Custom time',
            'items' => 'List of items (array)',
            'is_urgent' => 'Urgent flag (boolean)',
            'priority' => 'Priority level',
            'status' => 'Status information'
        ];

        // return [
        //     'user_name' => 'ชื่อ-นามสกุลผู้ใช้',
        //     'user_email' => 'อีเมลผู้ใช้',
        //     'user_first_name' => 'ชื่อผู้ใช้',
        //     'user_last_name' => 'นามสกุลผู้ใช้',
        //     'user_department' => 'แผนกของผู้ใช้',
        //     'user_title' => 'ตำแหน่งงานของผู้ใช้',
        //     'company' => 'ชื่อบริษัท',
        //     'message' => 'เนื้อหาข้อความที่กำหนดเอง',
        //     'subject' => 'หัวข้อที่กำหนดเอง',
        //     'url' => 'URL/ลิงก์ที่กำหนดเอง',
        //     'deadline' => 'วันที่กำหนดส่ง',
        //     'amount' => 'จำนวนเงิน/ราคา',
        //     'date' => 'วันที่ที่กำหนดเอง',
        //     'time' => 'เวลาที่กำหนดเอง',
        //     'items' => 'รายการ (array)',
        //     'is_urgent' => 'สถานะเร่งด่วน (boolean)',
        //     'priority' => 'ระดับความสำคัญ',
        //     'status' => 'ข้อมูลสถานะ'
        // ];
    }

/**
     * Safely get variables as array
     */
    public function getVariablesArray()
    {
        $vars = $this->variables;
        
        // ถ้าเป็น null หรือ empty ให้คืน array เปล่า
        if (empty($vars)) {
            return [];
        }
        
        if (is_string($vars)) {
            $decoded = json_decode($vars, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        if (is_array($vars)) {
            // ตรวจสอบให้แน่ใจว่า array elements เป็น string
            return array_filter($vars, function($item) {
                return is_string($item) || is_numeric($item);
            });
        }
        
        return [];
    }

    /**
     * Safely get default variables as array
     */
    public function getDefaultVariablesArray()
    {
        $vars = $this->default_variables;
        
        // ถ้าเป็น null หรือ empty ให้คืน array เปล่า
        if (empty($vars)) {
            return [];
        }
        
        if (is_string($vars)) {
            $decoded = json_decode($vars, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        if (is_array($vars)) {
            // ตรวจสอบและทำความสะอาด array
            $cleaned = [];
            foreach ($vars as $key => $value) {
                if (is_string($key) || is_numeric($key)) {
                    $cleaned[$key] = is_string($value) || is_numeric($value) || is_bool($value) ? $value : (string)$value;
                }
            }
            return $cleaned;
        }
        
        return [];
    }

    /**
     * Safely get supported channels as array
     */
    public function getSupportedChannelsArray()
    {
        $channels = $this->supported_channels;
        
        // ถ้าเป็น null หรือ empty ให้คืน array เปล่า
        if (empty($channels)) {
            return [];
        }
        
        if (is_string($channels)) {
            $decoded = json_decode($channels, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        if (is_array($channels)) {
            // ตรวจสอบให้แน่ใจว่า array elements เป็น string
            return array_filter($channels, function($item) {
                return is_string($item);
            });
        }
        
        return [];
    }

    /**
     * Clean and validate variable data
     */
    public function cleanVariableData($data)
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return is_array($data) ? $data : [];
    }

    /**
     * Generate sample data for preview
     */
    public function generateSampleData()
    {
        $extractedVars = $this->extractVariables();
        $defaultVars = $this->getDefaultVariablesArray();
        $systemVars = $this->getSystemVariables();
        
        $sampleData = [];
        
        // เพิ่ม system variables
        $sampleData = array_merge($sampleData, $systemVars);
        
        // เพิ่ม default variables
        $sampleData = array_merge($sampleData, $defaultVars);
        
        // สร้างข้อมูลตัวอย่างสำหรับตัวแปรที่ยังไม่มี
        foreach ($extractedVars as $var) {
            if (!isset($sampleData[$var])) {
                $sampleData[$var] = $this->generateSampleValueForVariable($var);
            }
        }
        
        return $sampleData;
    }

    /**
     * Generate sample value for a variable
     */
    private function generateSampleValueForVariable($varName)
    {
        $samples = [
            'user_name' => 'นายสมชาย ใจดี',
            'user_email' => 'somchai@company.com',
            'user_first_name' => 'สมชาย',
            'user_last_name' => 'ใจดี',
            'user_department' => 'เทคโนโลยีสารสนเทศ',
            'user_title' => 'นักพัฒนาระบบ',
            'company' => 'บริษัท เทคโนโลยี จำกัด',
            'message' => 'นี่คือข้อความแจ้งเตือนตัวอย่าง',
            'subject' => 'การแจ้งเตือนระบบที่สำคัญ',
            'url' => 'https://example.com/action',
            'deadline' => now()->addDays(7)->format('Y-m-d'),
            'amount' => '1,250.00',
            'priority' => 'สูง',
            'status' => 'ใช้งาน',
            'project_name' => 'โครงการพัฒนาระบบ',
            'meeting_title' => 'ประชุมทีมประจำสัปดาห์',
            'meeting_date' => now()->addDays(1)->format('Y-m-d'),
            'meeting_time' => '10:00 - 11:00 น.',
            'meeting_location' => 'ห้องประชุม A',
            'agenda' => '1. ทบทวนความคืบหน้า\n2. วางแผนสัปดาห์หน้า'
        ];
        
        return $samples[$varName] ?? "ตัวอย่าง {$varName}";
    }

    /**
     * Accessor for variables attribute
     */
    public function getVariablesAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Accessor for default_variables attribute
     */
    public function getDefaultVariablesAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Accessor for supported_channels attribute
     */
    public function getSupportedChannelsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Mutator for variables attribute
     */
    public function setVariablesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['variables'] = json_encode($value);
        } else {
            $this->attributes['variables'] = $value;
        }
    }

    /**
     * Mutator for default_variables attribute
     */
    public function setDefaultVariablesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['default_variables'] = json_encode($value);
        } else {
            $this->attributes['default_variables'] = $value;
        }
    }

    /**
     * Mutator for supported_channels attribute
     */
    public function setSupportedChannelsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['supported_channels'] = json_encode($value);
        } else {
            $this->attributes['supported_channels'] = $value;
        }
    }
}