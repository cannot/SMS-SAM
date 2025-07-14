<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\SqlAlert;
use App\Models\SqlAlertExecution;
use App\Models\SqlAlertRecipient;
use App\Models\SqlAlertAttachment;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Models\NotificationGroup;
use Exception;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class SqlAlertController extends Controller
{
    /**
     * Display the SQL Alert creation wizard
     */
    public function create(Request $request)
    {
        $step = $request->get('step', 1);
        $isAjax = $request->get('ajax', false);
        
        // Validate step number
        if ($step < 1 || $step > 14) {
            $step = 1;
        }
        
        // If AJAX request, return only the step content
        if ($isAjax) {
            return $this->renderStep($step, $request);
        }
        
        // Return full page with wizard container
        return view('admin.sql-alerts.create');
    }
    
    /**
     * Render specific step content
     */
    private function renderStep($step, Request $request)
    {
        $stepFile = "admin.sql-alerts.steps.step{$step}";
        
        if (!view()->exists($stepFile)) {
            // If step view doesn't exist, return inline content
            return $this->getInlineStepContent($step);
        }
        
        // Load data for specific steps
        $data = [];
        switch ($step) {
            case 1:
                $data['databases'] = $this->getSupportedDatabases();
                break;
            case 9:
                $data['templates'] = NotificationTemplate::where('type', 'email')->get();
                break;
            case 12:
                $data['groups'] = NotificationGroup::with('users')->get();
                $data['users'] = User::where('is_active', true)->get();
                break;
        }
        
        return view($stepFile, $data);
    }

    /**
     * Get inline step content for steps without dedicated views
     */
    private function getInlineStepContent($step)
    {
        // For now, return a simple step content
        // You can expand this based on the existing step files
        $stepTitles = [
            6 => 'ดูตัวอย่างข้อมูล',
            7 => 'กำหนดรูปแบบการส่งออก',
            8 => 'สร้างเทมเพลตอีเมล',
            9 => 'เลือกเทมเพลตอีเมล',
            10 => 'ทดสอบการส่งอีเมล',
            11 => 'กำหนดผู้รับ',
            12 => 'เลือกผู้รับ',
            13 => 'กำหนดตารางเวลา',
            14 => 'สรุปและบันทึก'
        ];

        $title = $stepTitles[$step] ?? "ขั้นตอนที่ {$step}";
        
        return view('admin.sql-alerts.steps.generic-step', [
            'step' => $step,
            'title' => $title,
            'totalSteps' => 14
        ]);
    }
    
    /**
     * Test database connection with enhanced validation
     */
    public function testConnection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_type' => 'required|string|in:mysql,postgresql,sqlserver,oracle,sqlite,mariadb',
            'db_host' => 'required_unless:db_type,sqlite|string|max:255',
            'db_port' => 'required_unless:db_type,sqlite|integer|min:1|max:65535',
            'db_name' => 'required|string|max:255',
            'db_username' => 'required_unless:db_type,sqlite|string|max:255',
            'db_password' => 'nullable|string',
            'ssl_enabled' => 'boolean',
            'connection_timeout' => 'integer|min:5|max:300',
            'charset' => 'nullable|string|max:50'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ครบถ้วนหรือไม่ถูกต้อง',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $startTime = microtime(true);
            
            // Create temporary connection config
            $config = $this->buildConnectionConfig($request);
            
            // Generate unique connection name
            $connectionName = 'sql_alert_test_' . uniqid();
            
            // Test connection with timeout
            config(['database.connections.' . $connectionName => $config]);
            
            // Test basic connection
            $pdo = DB::connection($connectionName)->getPdo();
            
            // Test database access
            $versionQuery = $this->getVersionQuery($request->db_type);
            $result = DB::connection($connectionName)->selectOne($versionQuery);
            
            // Test SELECT permissions
            $testQuery = $this->getTestQuery($request->db_type);
            DB::connection($connectionName)->select($testQuery);
            
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Clean up test connection
            DB::purge($connectionName);
            
            Log::info('Database connection test successful', [
                'db_type' => $request->db_type,
                'host' => $request->db_host,
                'database' => $request->db_name,
                'connection_time' => $connectionTime . 'ms'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'เชื่อมต่อฐานข้อมูลสำเร็จ',
                'data' => [
                    'version' => $result->version ?? 'Unknown',
                    'connection_time' => $connectionTime . 'ms',
                    'status' => 'Connected',
                    'driver' => $config['driver'],
                    'charset' => $config['charset'] ?? 'utf8',
                    'ssl_enabled' => $request->get('ssl_enabled', false)
                ]
            ]);
            
        } catch (Exception $e) {
            Log::warning('Database connection test failed', [
                'db_type' => $request->db_type,
                'host' => $request->db_host,
                'database' => $request->db_name,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ]);
            
            // Determine error type for better user feedback
            $errorMessage = $this->getConnectionErrorMessage($e);
            
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้',
                'error' => $errorMessage,
                'error_code' => $e->getCode(),
                'suggestions' => $this->getConnectionErrorSuggestions($e)
            ], 500);
        }
    }
    
    /**
     * Execute SQL query for preview with enhanced security
     */
    public function executeQuery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sql_query' => 'required|string|min:10',
            'connection_config' => 'required|array',
            'limit' => 'nullable|integer|min:1|max:1000'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ครบถ้วน',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $query = trim($request->sql_query);
            $limit = $request->get('limit', 25);
            
            // Enhanced SQL injection protection
            $securityCheck = $this->validateQuerySecurity($query);
            if (!$securityCheck['safe']) {
                return response()->json([
                    'success' => false,
                    'message' => 'SQL Query ไม่ปลอดภัย: ' . $securityCheck['reason'],
                    'security_issues' => $securityCheck['issues']
                ], 422);
            }
            
            // Add LIMIT if not exists and limit is specified
            if ($limit && stripos($query, 'LIMIT') === false) {
                $query .= " LIMIT {$limit}";
            }
            
            // Setup temporary connection
            $connectionName = 'sql_alert_preview_' . uniqid();
            $config = $request->connection_config;
            
            config(['database.connections.' . $connectionName => $config]);
            
            $startTime = microtime(true);
            
            // Execute with timeout protection
            $results = DB::connection($connectionName)
                       ->timeout(30) // 30 second timeout
                       ->select($query);
            
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Clean up
            DB::purge($connectionName);
            
            $columns = $this->getColumnNames($results);
            $dataSize = $this->estimateDataSize($results);
            $previewData = array_slice($results, 0, min(50, count($results)));
            
            Log::info('SQL Query executed successfully for preview', [
                'query_length' => strlen($query),
                'rows_returned' => count($results),
                'execution_time' => $executionTime . 'ms',
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'results' => $previewData,
                    'row_count' => count($results),
                    'execution_time' => $executionTime . 'ms',
                    'columns' => $columns,
                    'data_size' => $dataSize,
                    'truncated' => count($results) > 50,
                    'query_hash' => md5($query)
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('SQL Query execution failed', [
                'query' => substr($request->sql_query, 0, 500), // Log first 500 chars only
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการรัน SQL',
                'error' => $this->getSqlErrorMessage($e),
                'sql_state' => $e->getCode(),
                'suggestions' => $this->getSqlErrorSuggestions($e)
            ], 500);
        }
    }
    
    /**
     * Store the SQL Alert with comprehensive validation
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:sql_alerts,name',
            'description' => 'nullable|string|max:1000',
            'database_config' => 'required|array',
            'database_config.type' => 'required|string',
            'database_config.host' => 'required_unless:database_config.type,sqlite|string',
            'database_config.database' => 'required|string',
            'sql_query' => 'required|string|min:10',
            'email_config' => 'required|array',
            'email_config.subject' => 'required|string|max:255',
            'email_config.body_template' => 'required|string',
            'recipients' => 'required|array|min:1',
            'recipients.*.email' => 'required|email',
            'schedule_config' => 'required|array',
            'schedule_config.type' => 'required|in:manual,once,recurring,cron',
            'export_config' => 'nullable|array',
            'variables' => 'nullable|array',
            'status' => 'required|in:active,inactive,draft'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'ข้อมูลไม่ครบถ้วนหรือไม่ถูกต้อง',
                'errors' => $validator->errors()
            ], 422);
        }
        
        DB::beginTransaction();
        
        try {
            // Test connection before saving
            $testResult = $this->testConnectionFromConfig($request->database_config);
            if (!$testResult['success']) {
                throw new Exception('ไม่สามารถเชื่อมต่อฐานข้อมูลได้: ' . $testResult['error']);
            }
            
            // Validate SQL query security
            $securityCheck = $this->validateQuerySecurity($request->sql_query);
            if (!$securityCheck['safe']) {
                throw new Exception('SQL Query ไม่ปลอดภัย: ' . $securityCheck['reason']);
            }
            
            // Calculate next run time
            $nextRun = $this->calculateNextRunTime($request->schedule_config);
            
            // Create the SQL Alert record
            $sqlAlert = SqlAlert::create([
                'name' => $request->name,
                'description' => $request->description,
                'database_config' => $request->database_config,
                'sql_query' => $request->sql_query,
                'email_config' => $request->email_config,
                'recipients' => $request->recipients,
                'schedule_config' => $request->schedule_config,
                'schedule_type' => $request->schedule_config['type'],
                'export_config' => $request->export_config ?? [],
                'variables' => $request->variables ?? [],
                'status' => $request->status,
                'created_by' => auth()->id(),
                'next_run' => $nextRun
            ]);
            
            // Set up scheduling if needed
            if ($request->schedule_config['type'] !== 'manual') {
                $this->setupSchedule($sqlAlert);
            }
            
            DB::commit();
            
            Log::info('SQL Alert created successfully', [
                'alert_id' => $sqlAlert->id,
                'name' => $sqlAlert->name,
                'created_by' => auth()->id(),
                'schedule_type' => $sqlAlert->schedule_type,
                'recipient_count' => count($request->recipients)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'สร้างการแจ้งเตือน SQL สำเร็จ',
                'data' => [
                    'alert_id' => $sqlAlert->id,
                    'next_run' => $sqlAlert->next_run?->toISOString(),
                    'status' => $sqlAlert->status,
                    'redirect_url' => route('sql-alerts.show', $sqlAlert)
                ]
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create SQL Alert', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->except(['database_config.password'])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Send test notification with real email
     */
    public function sendTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'email_config' => 'required|array',
            'email_config.subject' => 'required|string',
            'email_config.body_template' => 'required|string',
            'database_config' => 'required|array',
            'sql_query' => 'required|string',
            'variables' => 'nullable|array'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Setup temporary connection
            $connectionName = 'sql_alert_test_mail_' . uniqid();
            $config = $request->database_config;
            
            config(['database.connections.' . $connectionName => $config]);
            
            // Execute query with limit for testing
            $query = $request->sql_query;
            if (stripos($query, 'LIMIT') === false) {
                $query .= ' LIMIT 5';
            }
            
            $startTime = microtime(true);
            $results = DB::connection($connectionName)->select($query);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Clean up connection
            DB::purge($connectionName);
            
            // Process variables
            $variables = array_merge([
                'record_count' => count($results),
                'execution_time' => $executionTime . 'ms',
                'current_date' => now()->format('Y-m-d'),
                'current_datetime' => now()->format('Y-m-d H:i:s'),
                'current_time' => now()->format('H:i:s')
            ], $request->variables ?? []);
            
            // Replace variables in subject and body
            $subject = $this->replaceVariables($request->email_config['subject'], $variables);
            $body = $this->replaceVariables($request->email_config['body_template'], $variables, $results);
            
            // Send test email using Laravel Mail
            Mail::raw($body, function ($message) use ($request, $subject) {
                $message->to($request->email)
                       ->subject('[TEST] ' . $subject)
                       ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            Log::info('Test email sent successfully', [
                'recipient' => $request->email,
                'data_rows' => count($results),
                'execution_time' => $executionTime . 'ms',
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'ส่งอีเมลทดสอบสำเร็จ',
                'data' => [
                    'recipient' => $request->email,
                    'data_rows' => count($results),
                    'execution_time' => $executionTime . 'ms',
                    'sent_at' => now()->toISOString(),
                    'subject' => $subject,
                    'preview' => substr(strip_tags($body), 0, 200) . '...'
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Test email failed', [
                'recipient' => $request->email,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถส่งอีเมลทดสอบได้: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get list of SQL Alerts with filtering and pagination
     */
    public function index(Request $request)
    {
        $query = SqlAlert::with(['creator', 'latestExecution'])
                         ->select(['id', 'name', 'description', 'status', 'schedule_type', 
                                  'last_run', 'next_run', 'total_executions', 'successful_executions', 
                                  'created_by', 'created_at', 'updated_at']);
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('schedule_type')) {
            $query->where('schedule_type', $request->schedule_type);
        }
        
        if ($request->filled('creator')) {
            $query->where('created_by', $request->creator);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Order by
        $orderBy = $request->get('order_by', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($orderBy, $direction);
        
        $alerts = $query->paginate(20);
        
        return view('admin.sql-alerts.index', compact('alerts'));
    }
    
    /**
     * Show specific SQL Alert with execution history
     */
    public function show(SqlAlert $sqlAlert)
    {
        $sqlAlert->load([
            'creator', 
            'executions' => function($query) {
                $query->latest()->limit(20);
            },
            'executions.recipients',
            'executions.attachments'
        ]);
        
        $recentExecutions = $sqlAlert->executions;
        $statistics = $this->getAlertStatistics($sqlAlert);
        
        return view('admin.sql-alerts.show', compact('sqlAlert', 'recentExecutions', 'statistics'));
    }
    
    /**
     * Execute SQL Alert manually
     */
    public function execute(SqlAlert $sqlAlert)
    {
        if (!$sqlAlert->canExecute()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถรันการแจ้งเตือนได้ สถานะปัจจุบัน: ' . $sqlAlert->status_display
            ], 422);
        }
        
        try {
            $execution = $this->executeSqlAlert($sqlAlert, 'manual', auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => 'รันการแจ้งเตือน SQL สำเร็จ',
                'data' => [
                    'execution_id' => $execution->id,
                    'status' => $execution->status,
                    'rows_returned' => $execution->rows_returned,
                    'notifications_sent' => $execution->notifications_sent,
                    'execution_time' => $execution->execution_time_human
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Manual SQL Alert execution failed', [
                'alert_id' => $sqlAlert->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการรัน: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // ===================== PRIVATE HELPER METHODS =====================
    
    /**
     * Get supported databases configuration
     */
    private function getSupportedDatabases()
    {
        return [
            'mysql' => [
                'name' => 'MySQL',
                'driver' => 'mysql',
                'default_port' => 3306,
                'description' => 'ฐานข้อมูลโอเพนซอร์สยอดนิยม เหมาะสำหรับเว็บแอปพลิเคชัน'
            ],
            'postgresql' => [
                'name' => 'PostgreSQL',
                'driver' => 'pgsql',
                'default_port' => 5432,
                'description' => 'ฐานข้อมูลขั้นสูงพร้อมฟีเจอร์ครบครัน เหมาะสำหรับองค์กร'
            ],
            'sqlserver' => [
                'name' => 'SQL Server',
                'driver' => 'sqlsrv',
                'default_port' => 1433,
                'description' => 'ฐานข้อมูลของ Microsoft เหมาะสำหรับ Enterprise'
            ],
            'oracle' => [
                'name' => 'Oracle',
                'driver' => 'oci',
                'default_port' => 1521,
                'description' => 'ฐานข้อมูลระดับองค์กรสำหรับงานหนัก'
            ],
            'sqlite' => [
                'name' => 'SQLite',
                'driver' => 'sqlite',
                'default_port' => null,
                'description' => 'ฐานข้อมูลแบบไฟล์ เหมาะสำหรับแอปพลิเคชันขนาดเล็ก'
            ],
            'mariadb' => [
                'name' => 'MariaDB',
                'driver' => 'mysql',
                'default_port' => 3306,
                'description' => 'ฐานข้อมูลโอเพนซอร์สที่พัฒนาจาก MySQL'
            ]
        ];
    }
    
    /**
     * Build database connection configuration
     */
    private function buildConnectionConfig(Request $request)
    {
        $driver = $this->mapDatabaseDriver($request->db_type);
        
        $config = [
            'driver' => $driver,
            'charset' => $request->get('charset', 'utf8mb4'),
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];
        
        if ($request->db_type === 'sqlite') {
            $config['database'] = $request->db_name;
        } else {
            $config['host'] = $request->db_host;
            $config['port'] = $request->db_port;
            $config['database'] = $request->db_name;
            $config['username'] = $request->db_username;
            $config['password'] = $request->db_password;
            
            if ($request->get('ssl_enabled')) {
                $config['options'] = [
                    \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                ];
            }
            
            // Add connection timeout
            $timeout = $request->get('connection_timeout', 30);
            $config['options'][\PDO::ATTR_TIMEOUT] = $timeout;
        }
        
        return $config;
    }
    
    /**
     * Map database type to Laravel driver
     */
    private function mapDatabaseDriver($dbType)
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
     * Get version query for different database types
     */
    private function getVersionQuery($dbType)
    {
        $queries = [
            'mysql' => 'SELECT VERSION() as version',
            'mariadb' => 'SELECT VERSION() as version',
            'postgresql' => 'SELECT VERSION() as version',
            'sqlserver' => 'SELECT @@VERSION as version',
            'sqlite' => 'SELECT sqlite_version() as version',
            'oracle' => 'SELECT * FROM v$version WHERE ROWNUM = 1'
        ];
        
        return $queries[$dbType] ?? 'SELECT VERSION() as version';
    }
    
    /**
     * Get test query for permissions check
     */
    private function getTestQuery($dbType)
    {
        $queries = [
            'mysql' => 'SELECT 1 as test_result',
            'mariadb' => 'SELECT 1 as test_result',
            'postgresql' => 'SELECT 1 as test_result',
            'sqlserver' => 'SELECT 1 as test_result',
            'sqlite' => 'SELECT 1 as test_result',
            'oracle' => 'SELECT 1 as test_result FROM DUAL'
        ];
        
        return $queries[$dbType] ?? 'SELECT 1 as test_result';
    }
    
    /**
     * Enhanced SQL security validation
     */
    private function validateQuerySecurity($query)
    {
        $query = trim(strtoupper($query));
        $issues = [];
        
        // Remove SQL comments
        $cleanQuery = preg_replace('/--.*$/m', '', $query);
        $cleanQuery = preg_replace('/\/\*.*?\*\//s', '', $cleanQuery);
        $cleanQuery = preg_replace('/\s+/', ' ', $cleanQuery);
        
        // Must start with SELECT
        if (!preg_match('/^SELECT\s+/i', $cleanQuery)) {
            return [
                'safe' => false,
                'reason' => 'อนุญาตเฉพาะ SELECT statement เท่านั้น',
                'issues' => ['ไม่ใช่ SELECT statement']
            ];
        }
        
        // Check for dangerous keywords
        $dangerousKeywords = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE',
            'TRUNCATE', 'GRANT', 'REVOKE', 'EXEC', 'EXECUTE', 'CALL',
            'DECLARE', 'SET', 'USE', 'SHOW', 'DESCRIBE', 'EXPLAIN',
            'LOAD_FILE', 'INTO OUTFILE', 'INTO DUMPFILE'
        ];
        
        foreach ($dangerousKeywords as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/i', $cleanQuery)) {
                $issues[] = "พบคำสั่งที่ไม่อนุญาต: {$keyword}";
            }
        }
        
        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/xp_/i' => 'SQL Server extended procedures',
            '/sp_/i' => 'SQL Server stored procedures',
            '/;\s*SELECT/i' => 'Multiple statements',
            '/UNION.*SELECT/i' => 'UNION-based injection',
            '/@@/i' => 'System variables',
            '/BENCHMARK\s*\(/i' => 'Performance functions',
            '/SLEEP\s*\(/i' => 'Delay functions',
            '/WAITFOR\s+DELAY/i' => 'SQL Server delay'
        ];
        
        foreach ($suspiciousPatterns as $pattern => $description) {
            if (preg_match($pattern, $cleanQuery)) {
                $issues[] = "ตรวจพบรูปแบบที่น่าสงสัย: {$description}";
            }
        }
        
        return [
            'safe' => empty($issues),
            'reason' => empty($issues) ? 'Query ปลอดภัย' : implode(', ', $issues),
            'issues' => $issues
        ];
    }
    
    /**
     * Get column names from query results
     */
    private function getColumnNames($results)
    {
        if (empty($results)) {
            return [];
        }
        
        return array_keys((array) $results[0]);
    }
    
    /**
     * Estimate data size in bytes
     */
    private function estimateDataSize($results)
    {
        if (empty($results)) {
            return 0;
        }
        
        $sampleSize = strlen(json_encode(array_slice($results, 0, min(10, count($results)))));
        $estimatedTotal = ($sampleSize / min(10, count($results))) * count($results);
        
        return round($estimatedTotal);
    }
    
    /**
     * Test connection from saved config
     */
    private function testConnectionFromConfig($config)
    {
        try {
            $connectionName = 'sql_alert_test_' . uniqid();
            config(['database.connections.' . $connectionName => $config]);
            
            $pdo = DB::connection($connectionName)->getPdo();
            DB::purge($connectionName);
            
            return ['success' => true];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate next run time based on schedule configuration
     */
    private function calculateNextRunTime($scheduleConfig)
    {
        if (!$scheduleConfig || $scheduleConfig['type'] === 'manual') {
            return null;
        }
        
        switch ($scheduleConfig['type']) {
            case 'once':
                return isset($scheduleConfig['datetime']) 
                    ? Carbon::parse($scheduleConfig['datetime'])
                    : now()->addMinute();
                    
            case 'recurring':
                $interval = $scheduleConfig['interval'] ?? 'daily';
                $time = $scheduleConfig['time'] ?? '09:00';
                
                switch ($interval) {
                    case 'hourly':
                        return now()->addHour();
                    case 'daily':
                        return now()->addDay()->setTimeFromTimeString($time);
                    case 'weekly':
                        $dayOfWeek = $scheduleConfig['day_of_week'] ?? 1;
                        return now()->addWeek()->startOfWeek()->addDays($dayOfWeek - 1)->setTimeFromTimeString($time);
                    case 'monthly':
                        $dayOfMonth = $scheduleConfig['day_of_month'] ?? 1;
                        return now()->addMonth()->startOfMonth()->addDays($dayOfMonth - 1)->setTimeFromTimeString($time);
                    default:
                        return now()->addDay()->setTimeFromTimeString($time);
                }
                
            case 'cron':
                // For cron expressions, calculate next occurrence
                // This is simplified - in production use a cron expression parser
                return now()->addHour();
                
            default:
                return null;
        }
    }
    
    /**
     * Setup scheduling for SQL Alert
     */
    private function setupSchedule(SqlAlert $sqlAlert)
    {
        // In production, this would integrate with Laravel's task scheduler
        // or a job queue system like Redis/Database queues
        
        Log::info('Schedule setup for SQL Alert', [
            'alert_id' => $sqlAlert->id,
            'schedule_type' => $sqlAlert->schedule_type,
            'next_run' => $sqlAlert->next_run?->toISOString()
        ]);
        
        // You could dispatch a job here or add to scheduler
        // dispatch(new ExecuteSqlAlertJob($sqlAlert))->delay($sqlAlert->next_run);
    }
    
    /**
     * Execute SQL Alert with full process
     */
    private function executeSqlAlert(SqlAlert $sqlAlert, $triggerType = 'scheduled', $triggeredBy = null)
    {
        // Create execution record
        $execution = SqlAlertExecution::create([
            'sql_alert_id' => $sqlAlert->id,
            'trigger_type' => $triggerType,
            'triggered_by' => $triggeredBy,
            'status' => 'pending'
        ]);
        
        try {
            $execution->markAsStarted();
            
            // Setup database connection
            $connectionName = 'sql_alert_exec_' . $execution->id;
            config(['database.connections.' . $connectionName => $sqlAlert->database_config]);
            
            // Execute SQL query
            $startTime = microtime(true);
            $results = DB::connection($connectionName)->select($sqlAlert->sql_query);
            $executionTime = round((microtime(true) - $startTime) * 1000);
            
            // Clean up connection
            DB::purge($connectionName);
            
            // Update execution with results
            $execution->update([
                'rows_returned' => count($results),
                'rows_processed' => count($results),
                'query_results' => array_slice($results, 0, 10), // Store sample data
                'execution_time_ms' => $executionTime
            ]);
            
            // Process notifications if there are results or if configured to always send
            $shouldSendNotification = count($results) > 0 || 
                                    ($sqlAlert->email_config['send_empty'] ?? false);
            
            if ($shouldSendNotification) {
                $this->processNotifications($execution, $results);
            }
            
            $execution->markAsCompleted(true);
            
            Log::info('SQL Alert executed successfully', [
                'alert_id' => $sqlAlert->id,
                'execution_id' => $execution->id,
                'rows_returned' => count($results),
                'execution_time' => $executionTime . 'ms'
            ]);
            
            return $execution;
            
        } catch (Exception $e) {
            $execution->markAsCompleted(false, $e->getMessage());
            
            Log::error('SQL Alert execution failed', [
                'alert_id' => $sqlAlert->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Process notifications for execution
     */
    private function processNotifications(SqlAlertExecution $execution, $results)
    {
        $sqlAlert = $execution->sqlAlert;
        $sentCount = 0;
        $failedCount = 0;
        
        // Process variables
        $variables = $this->processVariables($sqlAlert, $execution, $results);
        
        // Generate attachments if configured
        $attachments = [];
        if (!empty($sqlAlert->export_config)) {
            $attachments = $this->generateAttachments($execution, $results);
        }
        
        // Send to each recipient
        foreach ($sqlAlert->recipients as $recipientConfig) {
            try {
                $recipient = SqlAlertRecipient::create([
                    'sql_alert_id' => $sqlAlert->id,
                    'execution_id' => $execution->id,
                    'recipient_type' => $recipientConfig['type'] ?? 'email',
                    'recipient_id' => $recipientConfig['id'] ?? null,
                    'recipient_email' => $recipientConfig['email'],
                    'recipient_name' => $recipientConfig['name'] ?? null,
                    'personalized_variables' => $variables,
                    'attachments' => $attachments
                ]);
                
                $this->sendNotificationEmail($recipient, $variables, $results, $attachments);
                
                $recipient->markAsSent();
                $sentCount++;
                
            } catch (Exception $e) {
                if (isset($recipient)) {
                    $recipient->markAsFailed($e->getMessage());
                }
                $failedCount++;
                
                Log::warning('Failed to send notification', [
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
    }
    
    /**
     * Process template variables
     */
    private function processVariables(SqlAlert $sqlAlert, SqlAlertExecution $execution, $results)
    {
        $systemVariables = [
            'record_count' => count($results),
            'execution_time' => $execution->execution_time_human,
            'current_date' => now()->format('Y-m-d'),
            'current_datetime' => now()->format('Y-m-d H:i:s'),
            'current_time' => now()->format('H:i:s'),
            'database_name' => $sqlAlert->database_config['database'] ?? 'Unknown',
            'alert_name' => $sqlAlert->name,
            'execution_id' => $execution->id
        ];
        
        $customVariables = $sqlAlert->variables ?? [];
        
        return array_merge($systemVariables, $customVariables);
    }
    
    /**
     * Replace variables in templates
     */
    private function replaceVariables($template, $variables, $results = [])
    {
        $content = $template;
        
        // Replace simple variables
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        // Replace data table if needed
        if (strpos($content, '{{data_table}}') !== false && !empty($results)) {
            $tableHtml = $this->generateDataTable($results);
            $content = str_replace('{{data_table}}', $tableHtml, $content);
        }
        
        return $content;
    }
    
    /**
     * Generate HTML table from results
     */
    private function generateDataTable($results, $limit = 100)
    {
        if (empty($results)) {
            return '<p>ไม่พบข้อมูล</p>';
        }
        
        $columns = array_keys((array) $results[0]);
        $limitedResults = array_slice($results, 0, $limit);
        
        $html = '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">';
        
        // Header
        $html .= '<thead><tr style="background-color: #f5f5f5;">';
        foreach ($columns as $column) {
            $html .= '<th style="padding: 8px; text-align: left;">' . htmlspecialchars($column) . '</th>';
        }
        $html .= '</tr></thead>';
        
        // Body
        $html .= '<tbody>';
        foreach ($limitedResults as $row) {
            $html .= '<tr>';
            foreach ($columns as $column) {
                $value = $row->$column ?? '';
                $html .= '<td style="padding: 8px;">' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        
        $html .= '</table>';
        
        if (count($results) > $limit) {
            $html .= '<p><em>แสดง ' . $limit . ' จากทั้งหมด ' . count($results) . ' รายการ</em></p>';
        }
        
        return $html;
    }
    
    /**
     * Generate file attachments
     */
    private function generateAttachments(SqlAlertExecution $execution, $results)
    {
        $attachments = [];
        $exportConfig = $execution->sqlAlert->export_config;
        
        if (empty($exportConfig) || empty($results)) {
            return $attachments;
        }
        
        foreach ($exportConfig['formats'] ?? ['excel'] as $format) {
            try {
                $attachment = SqlAlertAttachment::create([
                    'execution_id' => $execution->id,
                    'filename' => $this->generateFilename($execution, $format),
                    'original_filename' => $this->generateFilename($execution, $format),
                    'file_type' => $format,
                    'total_rows' => count($results),
                    'total_columns' => count(array_keys((array) $results[0])),
                    'column_headers' => array_keys((array) $results[0])
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
                
                $attachments[] = [
                    'id' => $attachment->id,
                    'filename' => $attachment->filename,
                    'path' => $attachment->file_path,
                    'size' => $attachment->file_size
                ];
                
            } catch (Exception $e) {
                if (isset($attachment)) {
                    $attachment->markAsFailed($e->getMessage());
                }
                
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
     * Generate filename for export
     */
    private function generateFilename(SqlAlertExecution $execution, $format)
    {
        $alertName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $execution->sqlAlert->name);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $extension = $this->getFileExtension($format);
        
        return "{$alertName}_{$timestamp}.{$extension}";
    }
    
    /**
     * Generate file from results
     */
    private function generateFile($results, $format, $filename)
    {
        $filePath = "sql-alerts/attachments/{$filename}";
        
        switch ($format) {
            case 'excel':
                $this->generateExcelFile($results, $filePath);
                break;
            case 'csv':
                $this->generateCsvFile($results, $filePath);
                break;
            default:
                throw new Exception("Unsupported format: {$format}");
        }
        
        return $filePath;
    }
    
    /**
     * Generate Excel file
     */
    private function generateExcelFile($results, $filePath)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        if (empty($results)) {
            $sheet->setCellValue('A1', 'ไม่พบข้อมูล');
        } else {
            $columns = array_keys((array) $results[0]);
            
            // Set headers
            foreach ($columns as $index => $column) {
                $sheet->setCellValueByColumnAndRow($index + 1, 1, $column);
            }
            
            // Set data
            foreach ($results as $rowIndex => $row) {
                foreach ($columns as $colIndex => $column) {
                    $value = $row->$column ?? '';
                    $sheet->setCellValueByColumnAndRow($colIndex + 1, $rowIndex + 2, $value);
                }
            }
            
            // Auto-size columns
            foreach ($columns as $index => $column) {
                $sheet->getColumnDimensionByColumn($index + 1)->setAutoSize(true);
            }
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::path($filePath));
    }
    
    /**
     * Generate CSV file
     */
    private function generateCsvFile($results, $filePath)
    {
        $handle = fopen(Storage::path($filePath), 'w');
        
        if (empty($results)) {
            fputcsv($handle, ['ไม่พบข้อมูล']);
        } else {
            $columns = array_keys((array) $results[0]);
            
            // Write headers
            fputcsv($handle, $columns);
            
            // Write data
            foreach ($results as $row) {
                $rowData = [];
                foreach ($columns as $column) {
                    $rowData[] = $row->$column ?? '';
                }
                fputcsv($handle, $rowData);
            }
        }
        
        fclose($handle);
    }
    
    /**
     * Get file extension for format
     */
    private function getFileExtension($format)
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
    private function getMimeType($format)
    {
        $mimeTypes = [
            'excel' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf'
        ];
        
        return $mimeTypes[$format] ?? 'application/octet-stream';
    }
    
    /**
     * Send notification email
     */
    private function sendNotificationEmail(SqlAlertRecipient $recipient, $variables, $results, $attachments)
    {
        $sqlAlert = $recipient->sqlAlert;
        $emailConfig = $sqlAlert->email_config;
        
        // Replace variables in subject and body
        $subject = $this->replaceVariables($emailConfig['subject'], $variables);
        $body = $this->replaceVariables($emailConfig['body_template'], $variables, $results);
        
        // Store email content
        $recipient->update([
            'email_subject' => $subject,
            'email_content' => $body
        ]);
        
        // Send email
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
                        $attachment['filename']
                    );
                }
            }
        });
    }
    
    /**
     * Get alert statistics
     */
    private function getAlertStatistics(SqlAlert $sqlAlert)
    {
        return [
            'total_executions' => $sqlAlert->total_executions,
            'successful_executions' => $sqlAlert->successful_executions,
            'failed_executions' => $sqlAlert->total_executions - $sqlAlert->successful_executions,
            'success_rate' => $sqlAlert->success_rate,
            'last_run' => $sqlAlert->last_run?->diffForHumans(),
            'next_run' => $sqlAlert->next_run?->diffForHumans(),
            'avg_execution_time' => $this->getAverageExecutionTime($sqlAlert),
            'total_notifications_sent' => $this->getTotalNotificationsSent($sqlAlert)
        ];
    }
    
    /**
     * Get average execution time
     */
    private function getAverageExecutionTime(SqlAlert $sqlAlert)
    {
        $avgTime = $sqlAlert->executions()
                           ->where('status', 'success')
                           ->whereNotNull('execution_time_ms')
                           ->avg('execution_time_ms');
                           
        return $avgTime ? round($avgTime, 2) . 'ms' : 'N/A';
    }
    
    /**
     * Get total notifications sent
     */
    private function getTotalNotificationsSent(SqlAlert $sqlAlert)
    {
        return $sqlAlert->executions()
                       ->sum('notifications_sent');
    }
    
    /**
     * Get connection error message
     */
    private function getConnectionErrorMessage(Exception $e)
    {
        $message = $e->getMessage();
        
        // Common error patterns
        if (strpos($message, 'Connection refused') !== false) {
            return 'ไม่สามารถเชื่อมต่อไปยังเซิร์ฟเวอร์ได้ ตรวจสอบ Host และ Port';
        } elseif (strpos($message, 'Access denied') !== false) {
            return 'Username หรือ Password ไม่ถูกต้อง';
        } elseif (strpos($message, 'Unknown database') !== false) {
            return 'ไม่พบฐานข้อมูลที่ระบุ';
        } elseif (strpos($message, 'timeout') !== false) {
            return 'การเชื่อมต่อหมดเวลา (Timeout)';
        } else {
            return $message;
        }
    }
    
    /**
     * Get connection error suggestions
     */
    private function getConnectionErrorSuggestions(Exception $e)
    {
        $message = $e->getMessage();
        
        if (strpos($message, 'Connection refused') !== false) {
            return [
                'ตรวจสอบว่าเซิร์ฟเวอร์ฐานข้อมูลทำงานอยู่',
                'ตรวจสอบ Host และ Port ให้ถูกต้อง',
                'ตรวจสอบ Firewall ที่อาจบล็อกการเชื่อมต่อ'
            ];
        } elseif (strpos($message, 'Access denied') !== false) {
            return [
                'ตรวจสอบ Username และ Password',
                'ตรวจสอบสิทธิ์การเข้าถึงฐานข้อมูล',
                'ลองเชื่อมต่อด้วยเครื่องมืออื่นเพื่อยืนยัน'
            ];
        } elseif (strpos($message, 'Unknown database') !== false) {
            return [
                'ตรวจสอบชื่อฐานข้อมูลให้ถูกต้อง',
                'ตรวจสอบว่าฐานข้อมูลมีอยู่จริง',
                'ตรวจสอบสิทธิ์การเข้าถึงฐานข้อมูล'
            ];
        } else {
            return [
                'ตรวจสอบการตั้งค่าการเชื่อมต่อ',
                'ลองเชื่อมต่อด้วยเครื่องมืออื่น',
                'ติดต่อผู้ดูแลระบบฐานข้อมูล'
            ];
        }
    }
    
    /**
     * Get SQL error message
     */
    private function getSqlErrorMessage(Exception $e)
    {
        $message = $e->getMessage();
        
        // Common SQL error patterns
        if (strpos($message, "doesn't exist") !== false) {
            return 'ไม่พบตารางหรือคอลัมน์ที่ระบุ';
        } elseif (strpos($message, 'syntax error') !== false) {
            return 'รูปแบบ SQL ไม่ถูกต้อง';
        } elseif (strpos($message, 'timeout') !== false) {
            return 'การรัน SQL หมดเวลา (Query ใช้เวลานานเกินไป)';
        } elseif (strpos($message, 'permission') !== false || strpos($message, 'denied') !== false) {
            return 'ไม่มีสิทธิ์ในการเข้าถึงตารางหรือดำเนินการ';
        } else {
            return $message;
        }
    }
    
    /**
     * Get SQL error suggestions
     */
    private function getSqlErrorSuggestions(Exception $e)
    {
        $message = $e->getMessage();
        
        if (strpos($message, "doesn't exist") !== false) {
            return [
                'ตรวจสอบชื่อตารางและคอลัมน์ให้ถูกต้อง',
                'ใช้ SHOW TABLES เพื่อดูรายชื่อตาราง',
                'ใช้ DESCRIBE table_name เพื่อดูโครงสร้างตาราง'
            ];
        } elseif (strpos($message, 'syntax error') !== false) {
            return [
                'ตรวจสอบรูปแบบ SQL ให้ถูกต้อง',
                'ตรวจสอบเครื่องหมาย comma และ quote',
                'ใช้ SQL Formatter เพื่อช่วยตรวจสอบ'
            ];
        } elseif (strpos($message, 'timeout') !== false) {
            return [
                'เพิ่ม WHERE clause เพื่อกรองข้อมูล',
                'เพิ่ม LIMIT เพื่อจำกัดจำนวนผลลัพธ์',
                'สร้าง Index บนคอลัมน์ที่ใช้ใน WHERE'
            ];
        } else {
            return [
                'ตรวจสอบ SQL Query ให้ถูกต้อง',
                'ตรวจสอบสิทธิ์การเข้าถึงข้อมูล',
                'ลองรัน Query บนเครื่องมืออื่นเพื่อทดสอบ'
            ];
        }
    }
}