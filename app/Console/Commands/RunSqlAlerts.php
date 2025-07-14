<?php
// app/Console/Commands/RunSqlAlerts.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SqlAlert;
use App\Jobs\ExecuteSqlAlertJob;
use Carbon\Carbon;

class RunSqlAlerts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sql-alerts:run
                          {--id=* : Specific alert IDs to run}
                          {--force : Force run even if not scheduled}
                          {--dry-run : Show what would be executed without running}';

    /**
     * The console command description.
     */
    protected $description = 'Run scheduled SQL alerts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting SQL Alert Runner...');
        
        $alertIds = $this->option('id');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        
        if (!empty($alertIds)) {
            $this->runSpecificAlerts($alertIds, $force, $dryRun);
        } else {
            $this->runScheduledAlerts($dryRun);
        }
        
        $this->info('✅ SQL Alert Runner completed');
    }
    
    /**
     * Run specific alerts by ID
     */
    private function runSpecificAlerts(array $alertIds, bool $force, bool $dryRun)
    {
        $this->info("Running specific alerts: " . implode(', ', $alertIds));
        
        $alerts = SqlAlert::whereIn('id', $alertIds)->get();
        
        if ($alerts->isEmpty()) {
            $this->warn('No alerts found with the specified IDs');
            return;
        }
        
        foreach ($alerts as $alert) {
            if (!$force && !$alert->canExecute()) {
                $this->warn("Alert '{$alert->name}' (ID: {$alert->id}) cannot be executed (Status: {$alert->status})");
                continue;
            }
            
            if ($dryRun) {
                $this->line("Would execute: {$alert->name} (ID: {$alert->id})");
            } else {
                $this->executeAlert($alert, 'manual');
            }
        }
    }
    
    /**
     * Run scheduled alerts
     */
    private function runScheduledAlerts(bool $dryRun)
    {
        $this->info('Checking for scheduled alerts...');
        
        $alerts = SqlAlert::readyToRun()->get();
        
        if ($alerts->isEmpty()) {
            $this->info('No alerts are ready to run at this time');
            return;
        }
        
        $this->info("Found {$alerts->count()} alert(s) ready to run");
        
        foreach ($alerts as $alert) {
            if ($dryRun) {
                $this->line("Would execute: {$alert->name} (ID: {$alert->id}) - Next run: {$alert->next_run}");
            } else {
                $this->executeAlert($alert, 'scheduled');
            }
        }
    }
    
    /**
     * Execute a single alert
     */
    private function executeAlert(SqlAlert $alert, string $triggerType)
    {
        try {
            $this->line("Executing: {$alert->name} (ID: {$alert->id})");
            
            // Dispatch job to queue for background processing
            ExecuteSqlAlertJob::dispatch($alert, $triggerType)
                             ->onQueue('sql-alerts');
            
            $this->info("✓ Queued alert: {$alert->name}");
            
        } catch (\Exception $e) {
            $this->error("✗ Failed to queue alert {$alert->name}: {$e->getMessage()}");
        }
    }
}

// ===== app/Jobs/ExecuteSqlAlertJob.php =====

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\SqlAlert;
use App\Models\SqlAlertExecution;
use App\Models\SqlAlertRecipient;
use App\Models\SqlAlertAttachment;
use Exception;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExecuteSqlAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected SqlAlert $sqlAlert;
    protected string $triggerType;
    protected ?int $triggeredBy;

    /**
     * Job timeout in seconds
     */
    public $timeout = 300; // 5 minutes

    /**
     * Number of times the job may be attempted
     */
    public $tries = 3;

    /**
     * Create a new job instance
     */
    public function __construct(SqlAlert $sqlAlert, string $triggerType = 'scheduled', ?int $triggeredBy = null)
    {
        $this->sqlAlert = $sqlAlert;
        $this->triggerType = $triggerType;
        $this->triggeredBy = $triggeredBy;
    }

    /**
     * Execute the job
     */
    public function handle()
    {
        Log::info('Starting SQL Alert execution', [
            'alert_id' => $this->sqlAlert->id,
            'alert_name' => $this->sqlAlert->name,
            'trigger_type' => $this->triggerType
        ]);

        // Create execution record
        $execution = SqlAlertExecution::create([
            'sql_alert_id' => $this->sqlAlert->id,
            'trigger_type' => $this->triggerType,
            'triggered_by' => $this->triggeredBy,
            'status' => 'pending'
        ]);

        try {
            $this->executeAlert($execution);
        } catch (Exception $e) {
            Log::error('SQL Alert execution failed', [
                'alert_id' => $this->sqlAlert->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $execution->markAsCompleted(false, $e->getMessage());
            
            // Re-throw for job retry mechanism
            throw $e;
        }
    }

    /**
     * Execute the SQL Alert
     */
    private function executeAlert(SqlAlertExecution $execution)
    {
        $execution->markAsStarted();

        // Step 1: Execute SQL Query
        $results = $this->executeSqlQuery($execution);

        // Step 2: Process Variables
        $variables = $this->processVariables($execution, $results);

        // Step 3: Generate Attachments (if configured)
        $attachments = $this->generateAttachments($execution, $results);

        // Step 4: Send Notifications
        $this->sendNotifications($execution, $results, $variables, $attachments);

        // Step 5: Update Alert Statistics
        $this->updateAlertStatistics($execution);

        $execution->markAsCompleted(true);

        Log::info('SQL Alert execution completed successfully', [
            'alert_id' => $this->sqlAlert->id,
            'execution_id' => $execution->id,
            'rows_returned' => count($results),
            'notifications_sent' => $execution->notifications_sent
        ]);
    }

    /**
     * Execute SQL query against the database
     */
    private function executeSqlQuery(SqlAlertExecution $execution): array
    {
        // Setup temporary database connection
        $connectionName = 'sql_alert_' . $execution->id;
        $config = $this->buildConnectionConfig($this->sqlAlert->database_config);
        
        config(['database.connections.' . $connectionName => $config]);

        try {
            $startTime = microtime(true);
            
            // Execute the SQL query
            $results = DB::connection($connectionName)
                        ->timeout(120) // 2 minute timeout
                        ->select($this->sqlAlert->sql_query);
            
            $executionTime = round((microtime(true) - $startTime) * 1000);

            // Store sample results (first 10 rows for logging)
            $sampleResults = array_slice($results, 0, 10);

            // Update execution with results
            $execution->update([
                'rows_returned' => count($results),
                'rows_processed' => count($results),
                'query_results' => $sampleResults,
                'execution_time_ms' => $executionTime
            ]);

            Log::info('SQL query executed successfully', [
                'execution_id' => $execution->id,
                'rows_returned' => count($results),
                'execution_time_ms' => $executionTime
            ]);

            return $results;

        } finally {
            // Clean up the temporary connection
            DB::purge($connectionName);
        }
    }

    /**
     * Process template variables
     */
    private function processVariables(SqlAlertExecution $execution, array $results): array
    {
        $systemVariables = [
            'record_count' => count($results),
            'execution_time' => $execution->execution_time_human,
            'current_date' => now()->format('Y-m-d'),
            'current_datetime' => now()->format('Y-m-d H:i:s'),
            'current_time' => now()->format('H:i:s'),
            'database_name' => $this->sqlAlert->database_config['database'] ?? 'Unknown',
            'alert_name' => $this->sqlAlert->name,
            'execution_id' => $execution->id,
            'alert_id' => $this->sqlAlert->id,
            'yesterday' => now()->subDay()->format('Y-m-d'),
            'last_week' => now()->subWeek()->format('Y-m-d'),
            'query_execution_time' => $execution->execution_time_human
        ];

        // Merge with custom variables from alert configuration
        $customVariables = $this->sqlAlert->variables ?? [];

        return array_merge($systemVariables, $customVariables);
    }

    /**
     * Generate file attachments if configured
     */
    private function generateAttachments(SqlAlertExecution $execution, array $results): array
    {
        $exportConfig = $this->sqlAlert->export_config ?? [];
        $attachments = [];

        if (empty($exportConfig['enabled']) || empty($results)) {
            return $attachments;
        }

        $formats = $exportConfig['formats'] ?? ['excel'];

        foreach ($formats as $format) {
            try {
                $attachment = $this->createAttachment($execution, $results, $format);
                $attachments[] = $attachment;

                Log::info('Attachment generated successfully', [
                    'execution_id' => $execution->id,
                    'format' => $format,
                    'file_size' => $attachment['size']
                ]);

            } catch (Exception $e) {
                Log::error('Failed to generate attachment', [
                    'execution_id' => $execution->id,
                    'format' => $format,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $attachments;
    }

    /**
     * Create a single attachment file
     */
    private function createAttachment(SqlAlertExecution $execution, array $results, string $format): array
    {
        $attachment = SqlAlertAttachment::create([
            'execution_id' => $execution->id,
            'filename' => $this->generateFilename($format),
            'original_filename' => $this->generateFilename($format),
            'file_type' => $format,
            'total_rows' => count($results),
            'total_columns' => !empty($results) ? count(array_keys((array) $results[0])) : 0,
            'column_headers' => !empty($results) ? array_keys((array) $results[0]) : []
        ]);

        $attachment->markAsGenerating();

        $startTime = microtime(true);
        $filePath = $this->generateFile($results, $format, $attachment->filename);
        $generationTime = round((microtime(true) - $startTime) * 1000);

        $attachment->update([
            'file_path' => $filePath,
            'file_size' => Storage::size($filePath),
            'mime_type' => $this->getMimeType($format)
        ]);

        $attachment->markAsCompleted($generationTime);

        return [
            'id' => $attachment->id,
            'filename' => $attachment->filename,
            'path' => $attachment->file_path,
            'size' => $attachment->file_size,
            'type' => $format
        ];
    }

    /**
     * Send notifications to all recipients
     */
    private function sendNotifications(SqlAlertExecution $execution, array $results, array $variables, array $attachments)
    {
        $recipients = $this->sqlAlert->recipients ?? [];
        $sentCount = 0;
        $failedCount = 0;

        // Check if we should send notification
        $shouldSend = $this->shouldSendNotification($results);
        if (!$shouldSend) {
            Log::info('Skipping notification - no data and not configured to send empty results', [
                'execution_id' => $execution->id
            ]);
            return;
        }

        foreach ($recipients as $recipientConfig) {
            try {
                $recipient = SqlAlertRecipient::create([
                    'sql_alert_id' => $this->sqlAlert->id,
                    'execution_id' => $execution->id,
                    'recipient_type' => $recipientConfig['type'] ?? 'email',
                    'recipient_id' => $recipientConfig['id'] ?? null,
                    'recipient_email' => $recipientConfig['email'],
                    'recipient_name' => $recipientConfig['name'] ?? null,
                    'personalized_variables' => $variables,
                    'attachments' => $attachments
                ]);

                $this->sendEmailNotification($recipient, $variables, $results, $attachments);
                $recipient->markAsSent();
                $sentCount++;

            } catch (Exception $e) {
                if (isset($recipient)) {
                    $recipient->markAsFailed($e->getMessage());
                }
                $failedCount++;

                Log::error('Failed to send notification', [
                    'execution_id' => $execution->id,
                    'recipient_email' => $recipientConfig['email'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Update execution statistics
        $execution->update([
            'notifications_sent' => $sentCount,
            'notifications_failed' => $failedCount
        ]);

        Log::info('Notifications processed', [
            'execution_id' => $execution->id,
            'sent' => $sentCount,
            'failed' => $failedCount
        ]);
    }

    /**
     * Send email notification to a recipient
     */
    private function sendEmailNotification(SqlAlertRecipient $recipient, array $variables, array $results, array $attachments)
    {
        $emailConfig = $this->sqlAlert->email_config;

        // Process email subject and body with variables
        $subject = $this->replaceVariables($emailConfig['subject'] ?? 'SQL Alert Notification', $variables);
        $body = $this->replaceVariables($emailConfig['body_template'] ?? 'No template configured', $variables, $results);

        // Store processed content
        $recipient->update([
            'email_subject' => $subject,
            'email_content' => $body
        ]);

        // Send email using Laravel Mail
        Mail::send([], [], function ($message) use ($recipient, $subject, $body, $attachments) {
            $message->to($recipient->recipient_email, $recipient->recipient_name)
                   ->subject($subject)
                   ->html($body)
                   ->from(config('mail.from.address'), config('mail.from.name'));

            // Add attachments
            foreach ($attachments as $attachment) {
                if (Storage::exists($attachment['path'])) {
                    $message->attachData(
                        Storage::get($attachment['path']),
                        $attachment['filename'],
                        ['mime' => $this->getMimeType($attachment['type'])]
                    );
                }
            }
        });

        Log::info('Email sent successfully', [
            'recipient' => $recipient->recipient_email,
            'subject' => $subject,
            'attachments_count' => count($attachments)
        ]);
    }

    /**
     * Replace variables in templates
     */
    private function replaceVariables(string $template, array $variables, array $results = []): string
    {
        $content = $template;

        // Replace simple variables
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        // Replace data table if requested
        if (strpos($content, '{{data_table}}') !== false && !empty($results)) {
            $tableHtml = $this->generateDataTable($results);
            $content = str_replace('{{data_table}}', $tableHtml, $content);
        }

        return $content;
    }

    /**
     * Generate HTML table from query results
     */
    private function generateDataTable(array $results, int $limit = 100): string
    {
        if (empty($results)) {
            return '<p style="color: #6b7280;">ไม่พบข้อมูล</p>';
        }

        $columns = array_keys((array) $results[0]);
        $limitedResults = array_slice($results, 0, $limit);

        $html = '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: Arial, sans-serif;">';

        // Header
        $html .= '<thead><tr style="background-color: #f3f4f6;">';
        foreach ($columns as $column) {
            $html .= '<th style="padding: 12px 8px; text-align: left; font-weight: bold; color: #374151;">' . 
                     htmlspecialchars($column) . '</th>';
        }
        $html .= '</tr></thead>';

        // Body
        $html .= '<tbody>';
        foreach ($limitedResults as $index => $row) {
            $bgColor = $index % 2 === 0 ? '#ffffff' : '#f9fafb';
            $html .= '<tr style="background-color: ' . $bgColor . ';">';
            
            foreach ($columns as $column) {
                $value = $row->$column ?? '';
                $html .= '<td style="padding: 8px; border-bottom: 1px solid #e5e7eb;">' . 
                         htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';

        if (count($results) > $limit) {
            $html .= '<p style="margin-top: 12px; color: #6b7280; font-style: italic;">' .
                     'แสดง ' . $limit . ' จากทั้งหมด ' . count($results) . ' รายการ</p>';
        }

        return $html;
    }

    /**
     * Check if notification should be sent
     */
    private function shouldSendNotification(array $results): bool
    {
        // Always send if there are results
        if (!empty($results)) {
            return true;
        }

        // Check if configured to send empty results
        $emailConfig = $this->sqlAlert->email_config ?? [];
        return $emailConfig['send_empty'] ?? false;
    }

    /**
     * Update alert statistics and next run time
     */
    private function updateAlertStatistics(SqlAlertExecution $execution)
    {
        $this->sqlAlert->markAsExecuted($execution->status === 'success');
    }

    /**
     * Build database connection configuration
     */
    private function buildConnectionConfig(array $dbConfig): array
    {
        $config = [
            'driver' => $this->mapDatabaseDriver($dbConfig['type']),
            'charset' => $dbConfig['charset'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];

        if ($dbConfig['type'] === 'sqlite') {
            $config['database'] = $dbConfig['database'];
        } else {
            $config['host'] = $dbConfig['host'];
            $config['port'] = $dbConfig['port'];
            $config['database'] = $dbConfig['database'];
            $config['username'] = $dbConfig['username'];
            $config['password'] = $dbConfig['password'];

            if (!empty($dbConfig['ssl_enabled'])) {
                $config['options'] = [
                    \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                ];
            }
        }

        return $config;
    }

    /**
     * Map database type to Laravel driver
     */
    private function mapDatabaseDriver(string $dbType): string
    {
        $mapping = [
            'mysql' => 'mysql',
            'mariadb' => 'mysql',
            'postgresql' => 'pgsql',
            'sqlserver' => 'sqlsrv',
            'sqlite' => 'sqlite',
            'oracle' => 'oci'
        ];

        return $mapping[$dbType] ?? 'mysql';
    }

    /**
     * Generate filename for export
     */
    private function generateFilename(string $format): string
    {
        $alertName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->sqlAlert->name);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $extension = $this->getFileExtension($format);

        return "sql_alert_{$alertName}_{$timestamp}.{$extension}";
    }

    /**
     * Generate export file
     */
    private function generateFile(array $results, string $format, string $filename): string
    {
        $directory = 'sql-alerts/exports/' . now()->format('Y/m/d');
        Storage::makeDirectory($directory);
        
        $filePath = $directory . '/' . $filename;

        switch ($format) {
            case 'excel':
                $this->generateExcelFile($results, $filePath);
                break;
            case 'csv':
                $this->generateCsvFile($results, $filePath);
                break;
            default:
                throw new Exception("Unsupported export format: {$format}");
        }

        return $filePath;
    }

    /**
     * Generate Excel file
     */
    private function generateExcelFile(array $results, string $filePath)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $spreadsheet->getProperties()
                   ->setCreator('SQL Alert System')
                   ->setTitle($this->sqlAlert->name)
                   ->setDescription('Generated by SQL Alert: ' . $this->sqlAlert->name);

        if (empty($results)) {
            $sheet->setCellValue('A1', 'ไม่พบข้อมูล');
        } else {
            $columns = array_keys((array) $results[0]);

            // Set headers with styling
            foreach ($columns as $index => $column) {
                $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . '1';
                $sheet->setCellValue($cellCoordinate, $column);
                $sheet->getStyle($cellCoordinate)->getFont()->setBold(true);
                $sheet->getStyle($cellCoordinate)->getFill()
                      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                      ->getStartColor()->setRGB('E5E7EB');
            }

            // Set data
            foreach ($results as $rowIndex => $row) {
                foreach ($columns as $colIndex => $column) {
                    $value = $row->$column ?? '';
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1) . ($rowIndex + 2);
                    $sheet->setCellValue($cellCoordinate, $value);
                }
            }

            // Auto-size columns
            foreach ($columns as $index => $column) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::path($filePath));
    }

    /**
     * Get file extension for format
     */
    private function getFileExtension(string $format): string
    {
        $extensions = [
            'excel' => 'xlsx',
            'csv' => 'csv',
            'pdf' => 'pdf'
        ];

        return $extensions[$format] ?? 'txt';
    }

    /**
     * Get MIME type for format
     */
    private function getMimeType(string $format): string
    {
        $mimeTypes = [
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf'
        ];

        return $mimeTypes[$format] ?? 'application/octet-stream';
    }

    /**
     * Handle a job failure
     */
    public function failed(Exception $exception)
    {
        Log::error('SQL Alert Job failed permanently', [
            'alert_id' => $this->sqlAlert->id,
            'trigger_type' => $this->triggerType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Find the latest execution record and mark it as failed
        $execution = SqlAlertExecution::where('sql_alert_id', $this->sqlAlert->id)
                                     ->where('status', '!=', 'success')
                                     ->latest()
                                     ->first();

        if ($execution) {
            $execution->markAsCompleted(false, $exception->getMessage());
        }
    }
}

// ===== app/Console/Commands/SqlAlertScheduler.php =====

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SqlAlert;
use Carbon\Carbon;

class SqlAlertScheduler extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sql-alerts:schedule
                          {--check-only : Only check and display scheduled alerts}';

    /**
     * The console command description.
     */
    protected $description = 'Update next run times for SQL alerts and show scheduling information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $checkOnly = $this->option('check-only');
        
        $this->info('🕐 SQL Alert Scheduler');
        
        if ($checkOnly) {
            $this->showScheduleInformation();
        } else {
            $this->updateSchedules();
        }
    }
    
    /**
     * Show scheduling information
     */
    private function showScheduleInformation()
    {
        $this->info('Current scheduling information:');
        
        $alerts = SqlAlert::where('status', 'active')
                         ->where('schedule_type', '!=', 'manual')
                         ->orderBy('next_run')
                         ->get();
        
        if ($alerts->isEmpty()) {
            $this->warn('No scheduled alerts found');
            return;
        }
        
        $headers = ['ID', 'Name', 'Type', 'Next Run', 'Status', 'Last Run'];
        $rows = [];
        
        foreach ($alerts as $alert) {
            $rows[] = [
                $alert->id,
                Str::limit($alert->name, 30),
                $alert->schedule_type_display,
                $alert->next_run ? $alert->next_run->format('Y-m-d H:i:s') : 'Not set',
                $alert->is_overdue ? '⚠️  Overdue' : '✅ On time',
                $alert->last_run ? $alert->last_run->diffForHumans() : 'Never'
            ];
        }
        
        $this->table($headers, $rows);
        
        // Show summary
        $overdueCount = $alerts->filter(fn($alert) => $alert->is_overdue)->count();
        $upcomingCount = $alerts->filter(fn($alert) => 
            $alert->next_run && $alert->next_run->isBetween(now(), now()->addHour())
        )->count();
        
        $this->newLine();
        $this->info("Summary:");
        $this->line("Total scheduled alerts: {$alerts->count()}");
        $this->line("Overdue alerts: {$overdueCount}");
        $this->line("Due in next hour: {$upcomingCount}");
    }
    
    /**
     * Update alert schedules
     */
    private function updateSchedules()
    {
        $this->info('Updating alert schedules...');
        
        $alerts = SqlAlert::where('status', 'active')
                         ->where('schedule_type', '!=', 'manual')
                         ->get();
        
        $updated = 0;
        
        foreach ($alerts as $alert) {
            $oldNextRun = $alert->next_run;
            $alert->updateNextRun();
            $newNextRun = $alert->next_run;
            
            if ($oldNextRun != $newNextRun) {
                $updated++;
                $this->line("Updated: {$alert->name} - Next run: " . 
                           ($newNextRun ? $newNextRun->format('Y-m-d H:i:s') : 'Not set'));
            }
        }
        
        $this->info("Updated {$updated} alert schedule(s)");
    }
}

// ===== app/Console/Commands/SqlAlertCleanup.php =====

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SqlAlertExecution;
use App\Models\SqlAlertAttachment;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SqlAlertCleanup extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sql-alerts:cleanup
                          {--days=30 : Number of days to keep execution history}
                          {--attachments=7 : Number of days to keep attachment files}
                          {--dry-run : Show what would be deleted without deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old SQL alert executions and attachments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $attachmentDays = (int) $this->option('attachments');
        $dryRun = $this->option('dry-run');
        
        $this->info('🧹 SQL Alert Cleanup');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No actual deletions will be performed');
        }
        
        $this->cleanupExecutions($days, $dryRun);
        $this->cleanupAttachments($attachmentDays, $dryRun);
        
        $this->info('✅ Cleanup completed');
    }
    
    /**
     * Clean up old executions
     */
    private function cleanupExecutions(int $days, bool $dryRun)
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning up executions older than {$days} days (before {$cutoffDate->format('Y-m-d')})");
        
        $query = SqlAlertExecution::where('created_at', '<', $cutoffDate)
                                 ->where('status', '!=', 'running'); // Don't delete running executions
        
        $count = $query->count();
        
        if ($count === 0) {
            $this->info('No old executions found to clean up');
            return;
        }
        
        if ($dryRun) {
            $this->line("Would delete {$count} execution record(s)");
            
            // Show sample of what would be deleted
            $sample = $query->limit(5)->get(['id', 'sql_alert_id', 'status', 'created_at']);
            $headers = ['ID', 'Alert ID', 'Status', 'Created At'];
            $rows = $sample->map(fn($exec) => [
                $exec->id,
                $exec->sql_alert_id,
                $exec->status,
                $exec->created_at->format('Y-m-d H:i:s')
            ])->toArray();
            
            if (!empty($rows)) {
                $this->table($headers, $rows);
                if ($count > 5) {
                    $this->line("... and " . ($count - 5) . " more");
                }
            }
        } else {
            $deleted = $query->delete();
            $this->info("Deleted {$deleted} execution record(s)");
        }
    }
    
    /**
     * Clean up old attachments
     */
    private function cleanupAttachments(int $days, bool $dryRun)
    {
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("Cleaning up attachments older than {$days} days (before {$cutoffDate->format('Y-m-d')})");
        
        $attachments = SqlAlertAttachment::where('created_at', '<', $cutoffDate)->get();
        
        if ($attachments->isEmpty()) {
            $this->info('No old attachments found to clean up');
            return;
        }
        
        $totalSize = 0;
        $deletedFiles = 0;
        $deletedRecords = 0;
        
        foreach ($attachments as $attachment) {
            if ($attachment->exists()) {
                $size = Storage::size($attachment->file_path);
                $totalSize += $size;
                
                if (!$dryRun) {
                    Storage::delete($attachment->file_path);
                    $deletedFiles++;
                }
            }
            
            if (!$dryRun) {
                $attachment->delete();
                $deletedRecords++;
            }
        }
        
        $sizeFormatted = $this->formatBytes($totalSize);
        
        if ($dryRun) {
            $this->line("Would delete {$attachments->count()} attachment file(s) totaling {$sizeFormatted}");
            
            // Show sample
            $sample = $attachments->take(5);
            $headers = ['ID', 'Filename', 'Size', 'Created At'];
            $rows = $sample->map(fn($att) => [
                $att->id,
                $att->filename,
                $this->formatBytes($att->file_size),
                $att->created_at->format('Y-m-d H:i:s')
            ])->toArray();
            
            if (!empty($rows)) {
                $this->table($headers, $rows);
                if ($attachments->count() > 5) {
                    $this->line("... and " . ($attachments->count() - 5) . " more");
                }
            }
        } else {
            $this->info("Deleted {$deletedFiles} file(s) and {$deletedRecords} record(s) totaling {$sizeFormatted}");
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        
        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unit];
    }
}

// ===== .env configuration additions =====

/*
# Queue Configuration for SQL Alerts
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database

# SQL Alert specific settings
SQL_ALERT_TIMEOUT=300
SQL_ALERT_MAX_ATTACHMENTS=5
SQL_ALERT_MAX_ATTACHMENT_SIZE=10485760  # 10MB
SQL_ALERT_CLEANUP_DAYS=30
SQL_ALERT_ATTACHMENT_CLEANUP_DAYS=7

# Email settings for SQL Alerts
MAIL_FROM_ADDRESS=sql-alerts@yourcompany.com
MAIL_FROM_NAME="SQL Alert System"
*/