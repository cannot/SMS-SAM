<?php

// สร้างไฟล์ app/Console/Commands/FixTemplateVariables.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;

class FixTemplateVariables extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'templates:fix-variables 
                            {--dry-run : Preview changes without applying them}
                            {--template= : Fix specific template by ID}';

    /**
     * The console command description.
     */
    protected $description = 'Fix and normalize template variables data structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $templateId = $this->option('template');
        
        $this->info('Starting template variables fix...');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }
        
        // Query templates
        $query = NotificationTemplate::query();
        if ($templateId) {
            $query->where('id', $templateId);
        }
        
        $templates = $query->get();
        
        if ($templates->isEmpty()) {
            $this->error('No templates found');
            return 1;
        }
        
        $this->info("Found {$templates->count()} template(s) to process");
        
        $fixed = 0;
        $errors = 0;
        
        foreach ($templates as $template) {
            try {
                $result = $this->processTemplate($template, $isDryRun);
                
                if ($result['updated']) {
                    $fixed++;
                    $this->line("✓ Fixed: {$template->name} (ID: {$template->id})");
                    
                    if ($result['changes']) {
                        foreach ($result['changes'] as $change) {
                            $this->line("  - {$change}");
                        }
                    }
                } else {
                    $this->line("- OK: {$template->name} (ID: {$template->id})");
                }
                
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Error processing {$template->name} (ID: {$template->id}): {$e->getMessage()}");
                Log::error('Template variables fix error', [
                    'template_id' => $template->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        $this->newLine();
        
        if ($isDryRun) {
            $this->info("DRY RUN COMPLETE:");
            $this->info("- Templates that would be fixed: {$fixed}");
        } else {
            $this->info("OPERATION COMPLETE:");
            $this->info("- Templates fixed: {$fixed}");
        }
        
        if ($errors > 0) {
            $this->error("- Errors encountered: {$errors}");
        }
        
        return 0;
    }
    
    /**
     * Process a single template
     */
    private function processTemplate(NotificationTemplate $template, bool $isDryRun): array
    {
        $result = [
            'updated' => false,
            'changes' => []
        ];
        
        $updateData = [];
        
        // Debug current state
        $this->line("Processing: {$template->name}");
        $this->line("  Current variables: " . ($template->variables ? 'Present' : 'NULL'));
        $this->line("  Current default_variables: " . ($template->default_variables ? 'Present' : 'NULL'));
        
        // ตรวจสอบ variables column
        $variablesFixed = $this->fixVariablesColumn($template, $updateData);
        if ($variablesFixed) {
            $result['changes'][] = 'Fixed variables column structure';
        }
        
        // ตรวจสอบ default_variables column
        $defaultVarsFixed = $this->fixDefaultVariablesColumn($template, $updateData);
        if ($defaultVarsFixed) {
            $result['changes'][] = 'Fixed default_variables column structure';
        }
        
        // สร้าง variables จาก default_variables ถ้าจำเป็น
        $createdFromDefault = $this->createVariablesFromDefault($template, $updateData);
        if ($createdFromDefault) {
            $result['changes'][] = 'Created variables from default_variables';
        }
        
        // อัปเดตข้อมูลถ้ามีการเปลี่ยนแปลง
        if (!empty($updateData)) {
            $result['updated'] = true;
            
            if (!$isDryRun) {
                $template->update($updateData);
                
                Log::info('Template variables fixed', [
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'changes' => $updateData
                ]);
            }
        }
        
        return $result;
    }
    
    /**
     * แก้ไข variables column
     */
    private function fixVariablesColumn(NotificationTemplate $template, array &$updateData): bool
    {
        if (empty($template->variables)) {
            return false;
        }
        
        // ถ้าเป็น array ให้แปลงเป็น JSON string
        if (is_array($template->variables)) {
            $updateData['variables'] = json_encode($template->variables, JSON_UNESCAPED_UNICODE);
            return true;
        }
        
        // ถ้าเป็น string ให้ตรวจสอบว่าเป็น valid JSON หรือไม่
        if (is_string($template->variables)) {
            $decoded = json_decode($template->variables, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // ถ้าไม่ใช่ valid JSON ให้ทำให้เป็น empty array
                $updateData['variables'] = json_encode([], JSON_UNESCAPED_UNICODE);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * แก้ไข default_variables column
     */
    private function fixDefaultVariablesColumn(NotificationTemplate $template, array &$updateData): bool
    {
        if (empty($template->default_variables)) {
            return false;
        }
        
        // ถ้าเป็น array ให้แปลงเป็น JSON string
        if (is_array($template->default_variables)) {
            $updateData['default_variables'] = json_encode($template->default_variables, JSON_UNESCAPED_UNICODE);
            return true;
        }
        
        // ถ้าเป็น string ให้ตรวจสอบว่าเป็น valid JSON หรือไม่
        if (is_string($template->default_variables)) {
            $decoded = json_decode($template->default_variables, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // ถ้าไม่ใช่ valid JSON ให้ทำให้เป็น empty object
                $updateData['default_variables'] = json_encode(new \stdClass(), JSON_UNESCAPED_UNICODE);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * สร้าง variables จาก default_variables ถ้าไม่มี variables
     */
    private function createVariablesFromDefault(NotificationTemplate $template, array &$updateData): bool
    {
        // ถ้ามี variables อยู่แล้วไม่ต้องสร้าง
        if (!empty($template->variables)) {
            return false;
        }
        
        // ถ้าไม่มี default_variables ก็ไม่สามารถสร้างได้
        if (empty($template->default_variables)) {
            return false;
        }
        
        $defaultVars = [];
        
        // ดึง default_variables
        if (is_string($template->default_variables)) {
            $defaultVars = json_decode($template->default_variables, true) ?: [];
        } elseif (is_array($template->default_variables)) {
            $defaultVars = $template->default_variables;
        }
        
        if (empty($defaultVars)) {
            return false;
        }
        
        // สร้าง variables structure
        $variables = [];
        foreach ($defaultVars as $varName => $varValue) {
            $variables[] = [
                'name' => $varName,
                'default' => $varValue,
                'type' => 'text'
            ];
        }
        
        $updateData['variables'] = json_encode($variables, JSON_UNESCAPED_UNICODE);
        return true;
    }
}

// เพิ่มใน app/Console/Kernel.php ใน commands array:
// protected $commands = [
//     Commands\FixTemplateVariables::class,
// ];

// วิธีใช้งาน:
// php artisan templates:fix-variables --dry-run  (ดูตัวอย่างการเปลี่ยนแปลง)
// php artisan templates:fix-variables             (ดำเนินการจริง)
// php artisan templates:fix-variables --template=1  (แก้ไขเฉพาะ template ID 1)