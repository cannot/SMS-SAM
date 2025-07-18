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
     * Show the form for creating a new SQL Alert
     */
    public function create(Request $request)
    {
        $step = $request->get('step', 1);
        $isAjax = $request->get('ajax', false);
        
        // Validate step number
        if ($step < 1 || $step > 14) {
            $step = 1;
        }
        
        $data = [
            'title' => 'Create SQL Alert', // ✅ เพิ่ม title variable
            'step' => $step,
            'isAjax' => $isAjax
        ];
        
        // If AJAX request, return only the step content
        if ($isAjax) {
            return $this->renderStep($step, $request);
        }
        
        // Return full page with wizard container
        return view('admin.sql-alerts.create', $data);
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
                // แก้ไข: ใช้ supported_channels แทน type
                $data['templates'] = NotificationTemplate::supportsChannel('email')
                                                       ->where('is_active', true)
                                                       ->get();
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
     * Test database connection
     */
    public function testConnection(Request $request)
    {
        // **แก้ไข: ใช้ array format สำหรับ Log::info()**
        \Log::info('=== Testing connection START ===');
        \Log::info('Request data:', $request->all());
        \Log::info('Driver:', ['driver' => $request->driver]);
        \Log::info('Host:', ['host' => $request->host]);
        \Log::info('Port:', ['port' => $request->port]);
        \Log::info('Database:', ['database' => $request->database]);
        \Log::info('Username:', ['username' => $request->username]);
        \Log::info('Password length:', ['length' => strlen($request->password ?? '')]);
        
        // **ทดสอบ Oracle connection โดยตรง**
        if ($request->driver === 'oracle') {
            try {
                $connection_string = $request->host . ':' . $request->port . '/' . $request->database;
                
                \Log::info('Oracle connection attempt:', [
                    'connection_string' => $connection_string,
                    'username' => $request->username
                ]);
                
                $conn = oci_connect($request->username, $request->password, $connection_string);
                
                if (!$conn) {
                    $e = oci_error();
                    \Log::error('Oracle connection failed:', $e);
                    return response()->json([
                        'success' => false,
                        'message' => 'Oracle connection failed: ' . ($e['message'] ?? 'Unknown error')
                    ], 400);
                }
                
                \Log::info('Oracle connection successful, testing query...');
                
                // ทดสอบ query
                $stid = oci_parse($conn, 'SELECT banner FROM v$version WHERE ROWNUM = 1');
                if (!$stid) {
                    $e = oci_error($conn);
                    oci_close($conn);
                    \Log::error('Oracle query parse failed:', $e);
                    return response()->json([
                        'success' => false,
                        'message' => 'Oracle query failed: ' . ($e['message'] ?? 'Parse error')
                    ], 400);
                }
                
                $result = oci_execute($stid);
                if (!$result) {
                    $e = oci_error($stid);
                    oci_close($conn);
                    \Log::error('Oracle query execution failed:', $e);
                    return response()->json([
                        'success' => false,
                        'message' => 'Oracle execution failed: ' . ($e['message'] ?? 'Execution error')
                    ], 400);
                }
                
                $row = oci_fetch_assoc($stid);
                $version = $row['BANNER'] ?? 'Unknown';
                
                oci_close($conn);
                
                \Log::info('Oracle connection successful:', ['version' => $version]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Database connection successful',
                    'data' => [
                        'version' => $version,
                        'connection_time' => 50,
                        'driver' => 'oracle',
                        'host' => $request->host,
                        'port' => $request->port,
                        'database' => $request->database
                    ]
                ]);
                
            } catch (Exception $e) {
                \Log::error('Oracle connection exception:', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Oracle connection failed: ' . $e->getMessage()
                ], 400);
            }
        }
        
        // **ส่วนอื่น ๆ ของ database connection tests**
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'host' => 'required_unless:driver,sqlite|string',
            'port' => 'required_unless:driver,sqlite|integer',
            'database' => 'required|string',
            'username' => 'required_unless:driver,sqlite|string',
            'password' => 'nullable|string',
            'driver' => 'required|in:mysql,pgsql,sqlsrv,sqlite,oracle'
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if required PHP extension is available
        $extensionCheck = $this->checkDatabaseExtension($request->driver);
        if (!$extensionCheck['available']) {
            return response()->json([
                'success' => false,
                'message' => $extensionCheck['message'],
                'suggestion' => $extensionCheck['suggestion']
            ], 400);
        }

        try {
            // Test connection logic here
            $config = [
                'driver' => $request->driver,
                'host' => $request->host,
                'port' => $request->port,
                'database' => $request->database,
                'username' => $request->username,
                'password' => $request->password,
            ];

            // Add driver-specific configurations
            $config = $this->addDriverSpecificConfig($config, $request->driver);
            
            // **แก้ไข: ใช้ array format**
            \Log::info('Final config:', $config);

            // Create temporary connection name
            $connectionName = 'test_connection_' . uniqid();
            config(['database.connections.' . $connectionName => $config]);
            
            // Test connection
            $startTime = microtime(true);
            $pdo = DB::connection($connectionName)->getPdo();
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Get database version
            $version = $this->getDatabaseVersion($connectionName, $request->driver);
            
            // Clean up
            DB::purge($connectionName);
            
            return response()->json([
                'success' => true,
                'message' => 'Database connection successful',
                'data' => [
                    'version' => $version,
                    'connection_time' => $connectionTime,
                    'driver' => $request->driver,
                    'host' => $request->host,
                    'port' => $request->port,
                    'database' => $request->database
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Connection failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ], 400);
        }
    }
    
    /**
     * Execute SQL query for testing
     */
    public function executeQuery(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'sql_query' => 'required|string',
            'database_config' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Execute query logic here
            // For security, limit to SELECT statements only
            $query = trim($request->sql_query);
            
            if (!preg_match('/^\s*SELECT\s+/i', $query)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only SELECT statements are allowed'
                ], 400);
            }

            // Configure database connection
            $config = $request->database_config;
            
            // **เพิ่ม debug log**
            \Log::info('Execute query config:', $config);
            \Log::info('Query:', ['query' => $query]);
            
            // **จัดการ Oracle แยกต่างหาก**
            if (isset($config['driver']) && $config['driver'] === 'oracle') {
                return $this->executeOracleQuery($query, $config);
            }
            
            // **สำหรับ database อื่น ๆ**
            config(['database.connections.test_query' => $config]);
            
            // Execute query with limit
            $limitedQuery = $this->addLimitToQuery($query, $config['driver'] ?? 'mysql');
            $results = DB::connection('test_query')->select($limitedQuery);
            
            return response()->json([
                'success' => true,
                'message' => 'Query executed successfully',
                'data' => [
                    'records_count' => count($results),
                    'sample_data' => array_slice($results, 0, 5),
                    'columns' => !empty($results) ? array_keys((array)$results[0]) : []
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Query execution failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Query execution failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Execute Oracle query using oci_connect
     */
    private function executeOracleQuery($query, $config)
    {
        try {
            $connection_string = $config['host'] . ':' . $config['port'] . '/' . $config['database'];
            
            \Log::info('Oracle query execution:', [
                'connection_string' => $connection_string,
                'username' => $config['username']
            ]);
            
            // **แก้ไข: ตั้งค่า character set สำหรับภาษาไทย**
            $old_charset = getenv('NLS_LANG');
            putenv('NLS_LANG=THAI_THAILAND.AL32UTF8');
            
            $conn = oci_connect($config['username'], $config['password'], $connection_string, 'AL32UTF8');
            
            if (!$conn) {
                $e = oci_error();
                // คืนค่า charset เดิม
                if ($old_charset !== false) {
                    putenv('NLS_LANG=' . $old_charset);
                }
                \Log::error('Oracle connection failed:', $e);
                return response()->json([
                    'success' => false,
                    'message' => 'Oracle connection failed: ' . ($e['message'] ?? 'Unknown error')
                ], 400);
            }
            
            // **เพิ่ม: ตั้งค่า session character set**
            $charset_query = "ALTER SESSION SET NLS_LANGUAGE='THAI' NLS_TERRITORY='THAILAND'";
            $charset_stid = oci_parse($conn, $charset_query);
            oci_execute($charset_stid);
            
            // แก้ไข: ลบ LIMIT และเพิ่ม ROWNUM สำหรับ Oracle
            $limitedQuery = $this->convertToOracleLimit($query);
            
            \Log::info('Oracle query with ROWNUM:', ['query' => $limitedQuery]);
            
            $stid = oci_parse($conn, $limitedQuery);
            if (!$stid) {
                $e = oci_error($conn);
                oci_close($conn);
                // คืนค่า charset เดิม
                if ($old_charset !== false) {
                    putenv('NLS_LANG=' . $old_charset);
                }
                \Log::error('Oracle query parse failed:', $e);
                return response()->json([
                    'success' => false,
                    'message' => 'Oracle query parse failed: ' . ($e['message'] ?? 'Parse error')
                ], 400);
            }
            
            $result = oci_execute($stid);
            if (!$result) {
                $e = oci_error($stid);
                oci_close($conn);
                // คืนค่า charset เดิม
                if ($old_charset !== false) {
                    putenv('NLS_LANG=' . $old_charset);
                }
                \Log::error('Oracle query execution failed:', $e);
                return response()->json([
                    'success' => false,
                    'message' => 'Oracle query execution failed: ' . ($e['message'] ?? 'Execution error')
                ], 400);
            }
            
            // ดึงข้อมูลผลลัพธ์
            $results = [];
            $columns = [];
            $rowCount = 0;
            
            // ดึงชื่อ columns
            $numCols = oci_num_fields($stid);
            for ($i = 1; $i <= $numCols; $i++) {
                $columns[] = oci_field_name($stid, $i);
            }
            
            // **แก้ไข: ดึงข้อมูล rows และจัดการ encoding**
            while (($row = oci_fetch_assoc($stid)) && $rowCount < 10) {
                // แปลง encoding สำหรับแต่ละ field
                $processedRow = [];
                foreach ($row as $key => $value) {
                    if (is_string($value)) {
                        // ตรวจสอบและแปลง encoding ถ้าจำเป็น
                        if (!mb_check_encoding($value, 'UTF-8')) {
                            $value = mb_convert_encoding($value, 'UTF-8', 'TIS-620');
                        }
                        $processedRow[$key] = $value;
                    } else {
                        $processedRow[$key] = $value;
                    }
                }
                $results[] = $processedRow;
                $rowCount++;
            }
            
            oci_close($conn);
            
            // คืนค่า charset เดิม
            if ($old_charset !== false) {
                putenv('NLS_LANG=' . $old_charset);
            }
            
            \Log::info('Oracle query successful:', [
                'records_count' => count($results),
                'columns' => $columns
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Query executed successfully',
                'data' => [
                    'records_count' => count($results),
                    'sample_data' => array_slice($results, 0, 5),
                    'columns' => $columns
                ]
            ]);
            
        } catch (Exception $e) {
            \Log::error('Oracle query exception:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Oracle query failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Add LIMIT clause to query based on database type
     */
    private function addLimitToQuery($query, $driver)
    {
        // ไม่ force limit ให้ใช้ query ตามที่ user ส่งมา
        return $query;
    }

    /**
     * Add Oracle ROWNUM limit
     */
    private function addOracleLimit($query)
    {
        // **แก้ไข: ลบ LIMIT clause ออกก่อน (ถ้ามี)**
        $query = preg_replace('/\s+LIMIT\s+\d+$/i', '', $query);
        
        // ตรวจสอบว่ามี WHERE clause หรือไม่
        if (preg_match('/\bWHERE\b/i', $query)) {
            // มี WHERE clause แล้ว เพิ่ม AND ROWNUM
            return preg_replace('/\bWHERE\b/i', 'WHERE ROWNUM <= 10 AND', $query);
        } else {
            // ไม่มี WHERE clause เพิ่ม WHERE ROWNUM
            return $query . ' WHERE ROWNUM <= 10';
        }
    }
    
    /**
     * Store a newly created SQL Alert
     */
    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'database_config' => 'required|array',
            'sql_query' => 'required|string',
            'email_config' => 'required|array',
            'recipients' => 'required|array',
            'schedule_config' => 'required|array',
            'schedule_type' => 'required|in:manual,once,recurring,cron'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $alert = SqlAlert::create([
                'name' => $request->name,
                'description' => $request->description,
                'database_config' => $request->database_config,
                'sql_query' => $request->sql_query,
                'variables' => $request->variables ?? [],
                'email_config' => $request->email_config,
                'recipients' => $request->recipients,
                'schedule_config' => $request->schedule_config,
                'schedule_type' => $request->schedule_type,
                'export_config' => $request->export_config ?? [],
                'status' => $request->status ?? 'draft',
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SQL Alert created successfully',
                'alert' => $alert,
                'redirect' => route('admin.sql-alerts.show', $alert)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create SQL Alert: ' . $e->getMessage()
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
     * Display a listing of SQL Alerts
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Build query with filters
        $query = SqlAlert::with(['creator', 'executions' => function($q) {
            $q->latest()->limit(5);
        }]);

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply schedule type filter
        if ($request->filled('schedule_type')) {
            $query->where('schedule_type', $request->schedule_type);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Order by latest
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $alerts = $query->paginate(15);

        // Calculate additional stats for each alert
        $alerts->getCollection()->transform(function ($alert) {
            // Calculate success rate
            if ($alert->total_executions > 0) {
                $alert->success_rate = round(($alert->successful_executions / $alert->total_executions) * 100);
            } else {
                $alert->success_rate = 0;
            }

            return $alert;
        });

        // Prepare data for view
        $data = [
            'title' => 'SQL Alerts Management',
            'step' => null, // ✅ เพิ่ม step variable (null สำหรับ index page)
            'isAjax' => false, // ✅ เพิ่ม isAjax variable
            'totalSteps' => 14, // ✅ เพิ่ม totalSteps variable
            'sqlAlert' => null, // ✅ เพิ่ม sqlAlert variable (null สำหรับ index page)
            'alerts' => $alerts,
            'totalAlerts' => SqlAlert::count(),
            'activeAlerts' => SqlAlert::where('status', 'active')->count(),
            'recentExecutions' => SqlAlertExecution::with('sqlAlert')
                ->latest()
                ->limit(5)
                ->get(),
            'statusCounts' => [
                'active' => SqlAlert::where('status', 'active')->count(),
                'inactive' => SqlAlert::where('status', 'inactive')->count(),
                'draft' => SqlAlert::where('status', 'draft')->count(),
                'error' => SqlAlert::where('status', 'error')->count(),
            ],
            'scheduleTypeCounts' => [
                'manual' => SqlAlert::where('schedule_type', 'manual')->count(),
                'once' => SqlAlert::where('schedule_type', 'once')->count(),
                'recurring' => SqlAlert::where('schedule_type', 'recurring')->count(),
                'cron' => SqlAlert::where('schedule_type', 'cron')->count(),
            ]
        ];

        return view('admin.sql-alerts.index', $data);
    }
    
    /**
     * Show the specified SQL Alert
     */
    public function show(SqlAlert $sqlAlert)
    {
        $sqlAlert->load([
            'creator',
            'executions' => function($q) {
                $q->with('recipients', 'attachments')->latest();
            }
        ]);

        $data = [
            'title' => 'SQL Alert Details: ' . $sqlAlert->name, // ✅ เพิ่ม title variable
            'alert' => $sqlAlert,
            'recentExecutions' => $sqlAlert->executions()->latest()->limit(10)->get(),
            'executionStats' => [
                'total' => $sqlAlert->total_executions,
                'successful' => $sqlAlert->successful_executions,
                'failed' => $sqlAlert->total_executions - $sqlAlert->successful_executions,
                'success_rate' => $sqlAlert->total_executions > 0 
                    ? round(($sqlAlert->successful_executions / $sqlAlert->total_executions) * 100) 
                    : 0
            ]
        ];

        return view('admin.sql-alerts.show', $data);
    }

    /**
     * Show the form for editing the specified SQL Alert
     */
    public function edit(SqlAlert $sqlAlert)
    {
        $data = [
            'title' => 'Edit SQL Alert: ' . $sqlAlert->name, // ✅ เพิ่ม title variable
            'alert' => $sqlAlert,
            'databases' => $this->getSupportedDatabases(),
            'templates' => \App\Models\NotificationTemplate::where('type', 'email')->get(),
            'groups' => \App\Models\NotificationGroup::with('users')->get(),
            'users' => \App\Models\User::where('is_active', true)->get()
        ];

        return view('admin.sql-alerts.edit', $data);
    }

    /**
     * Update the specified SQL Alert
     */
    public function update(Request $request, SqlAlert $sqlAlert)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'database_config' => 'required|array',
            'sql_query' => 'required|string',
            'email_config' => 'required|array',
            'recipients' => 'required|array',
            'schedule_config' => 'required|array',
            'schedule_type' => 'required|in:manual,once,recurring,cron'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            $sqlAlert->update([
                'name' => $request->name,
                'description' => $request->description,
                'database_config' => $request->database_config,
                'sql_query' => $request->sql_query,
                'variables' => $request->variables ?? [],
                'email_config' => $request->email_config,
                'recipients' => $request->recipients,
                'schedule_config' => $request->schedule_config,
                'schedule_type' => $request->schedule_type,
                'export_config' => $request->export_config ?? [],
                'status' => $request->status ?? $sqlAlert->status,
                'updated_by' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SQL Alert updated successfully',
                    'alert' => $sqlAlert
                ]);
            }

            return redirect()->route('admin.sql-alerts.show', $sqlAlert)
                ->with('success', 'SQL Alert updated successfully');

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update SQL Alert: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to update SQL Alert: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified SQL Alert
     */
    public function destroy(SqlAlert $sqlAlert)
    {
        try {
            $sqlAlert->delete();

            return response()->json([
                'success' => true,
                'message' => 'SQL Alert deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete SQL Alert: ' . $e->getMessage()
            ], 500);
        }
    }


    
    /**
     * Execute SQL Alert manually
     */
    public function execute(SqlAlert $sqlAlert)
    {
        try {
            // Create execution record
            $execution = SqlAlertExecution::create([
                'sql_alert_id' => $sqlAlert->id,
                'status' => 'pending',
                'trigger_type' => 'manual',
                'triggered_by' => auth()->id(),
                'started_at' => now()
            ]);

            // Dispatch job to execute the alert
            \App\Jobs\ExecuteSqlAlert::dispatch($execution);

            return response()->json([
                'success' => true,
                'message' => 'SQL Alert execution started',
                'execution_id' => $execution->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to execute SQL Alert: ' . $e->getMessage()
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
            'oracle' => 'SELECT banner as version FROM v$version WHERE ROWNUM = 1'
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
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
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
                $generationTime = round((microtime(true) - $startTime) * 1000, 2);
                
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

    /**
     * Check if database extension is available
     */
    private function checkDatabaseExtension($driver)
    {
        $extensionMap = [
            'mysql' => [
                'extension' => 'pdo_mysql',
                'name' => 'MySQL',
                'install' => 'sudo apt-get install php-mysql'
            ],
            'pgsql' => [
                'extension' => 'pdo_pgsql',
                'name' => 'PostgreSQL',
                'install' => 'sudo apt-get install php-pgsql'
            ],
            'sqlsrv' => [
                'extension' => 'pdo_sqlsrv',
                'name' => 'SQL Server',
                'install' => 'Install Microsoft SQL Server drivers'
            ],
            'sqlite' => [
                'extension' => 'pdo_sqlite',
                'name' => 'SQLite',
                'install' => 'sudo apt-get install php-sqlite3'
            ],
            'oracle' => [
                'extensions' => ['oci8', 'pdo_oci'],
                'name' => 'Oracle',
                'install' => 'Install Oracle Instant Client and PHP OCI8 extension'
            ]
        ];

        if (!isset($extensionMap[$driver])) {
            return [
                'available' => false,
                'message' => 'Unsupported database driver: ' . $driver,
                'suggestion' => 'Please choose a supported database type'
            ];
        }

        $info = $extensionMap[$driver];
        
        // จัดการกับ Oracle ที่มีหลาย extensions
        if ($driver === 'oracle') {
            $hasExtension = false;
            foreach ($info['extensions'] as $ext) {
                if (extension_loaded($ext)) {
                    $hasExtension = true;
                    break;
                }
            }
            
            if (!$hasExtension) {
                return [
                    'available' => false,
                    'message' => "PHP extension for {$info['name']} is not installed",
                    'suggestion' => "Please install the required extension: {$info['install']}"
                ];
            }
        } else {
            if (!extension_loaded($info['extension'])) {
                return [
                    'available' => false,
                    'message' => "PHP extension for {$info['name']} is not installed",
                    'suggestion' => "Please install the required extension: {$info['install']}"
                ];
            }
        }

        return [
            'available' => true,
            'message' => "{$info['name']} extension is available"
        ];
        
    }

    /**
     * Add driver-specific configuration
     */
    private function addDriverSpecificConfig($config, $driver)
    {
        switch ($driver) {
            case 'mysql':
                $config['charset'] = 'utf8mb4';
                $config['collation'] = 'utf8mb4_unicode_ci';
                break;
                
            case 'pgsql':
                $config['charset'] = 'utf8';
                $config['schema'] = 'public';
                break;
                
            case 'sqlsrv':
                $config['charset'] = 'utf8';
                break;
                
            case 'sqlite':
                unset($config['host'], $config['port'], $config['username'], $config['password']);
                break;
            
            case 'oracle':
                $config['charset'] = 'AL32UTF8';
                $config['prefix'] = '';
                $config['prefix_schema'] = '';
                $config['edition'] = '';
                $config['server_version'] = '11g';
                
                // **แก้ไข: ใช้ Easy Connect format เหมือน sqlplus**
                $config['tns'] = $config['host'] . ':' . $config['port'] . '/' . $config['database'];
                
                // **เพิ่ม debug log**
                \Log::info('Oracle TNS:', ['tns' => $config['tns']]);
                
                break;
        }
        
        return $config;
    }

    /**
     * Get database version
     */
    private function getDatabaseVersion($connectionName, $driver)
    {
        try {
            $query = $this->getVersionQuery($driver);
            $result = DB::connection($connectionName)->select($query);
            
            if (!empty($result)) {
                return $result[0]->version ?? 'Unknown';
            }
            
            return 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Convert query to Oracle ROWNUM format
     */
    private function convertToOracleLimit($query)
    {
        // แปลง LIMIT เป็น ROWNUM สำหรับ Oracle เฉพาะเมื่อมี LIMIT ใน query
        if (preg_match('/\s+LIMIT\s+(\d+)$/i', $query, $matches)) {
            $limit = $matches[1];
            $query = preg_replace('/\s+LIMIT\s+\d+$/i', '', $query);
            
            \Log::info('Query after removing LIMIT:', ['query' => $query]);
            
            // ตรวจสอบว่ามี WHERE clause หรือไม่
            if (preg_match('/\bWHERE\b/i', $query)) {
                // มี WHERE clause แล้ว เพิ่ม AND ROWNUM
                $finalQuery = preg_replace('/\bWHERE\b/i', "WHERE ROWNUM <= $limit AND", $query);
            } else {
                // ไม่มี WHERE clause เพิ่ม WHERE ROWNUM
                $finalQuery = $query . " WHERE ROWNUM <= $limit";
            }
            
            \Log::info('Final Oracle query:', ['query' => $finalQuery]);
            
            return $finalQuery;
        }
        
        // ไม่มี LIMIT ให้ส่งคืน query ตามเดิม
        return $query;
    }
}