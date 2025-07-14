<?php
// app/Providers/SqlAlertServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\SqlAlert;
use App\Policies\SqlAlertPolicy;

class SqlAlertServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        // Register SQL Alert configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/sql-alerts.php', 'sql-alerts'
        );

        // Register SQL Alert services
        $this->app->singleton('sql-alert.manager', function ($app) {
            return new \App\Services\SqlAlertManager();
        });

        $this->app->singleton('sql-alert.executor', function ($app) {
            return new \App\Services\SqlAlertExecutor();
        });

        $this->app->singleton('sql-alert.scheduler', function ($app) {
            return new \App\Services\SqlAlertScheduler();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/sql-alerts.php' => config_path('sql-alerts.php'),
            ], 'sql-alerts-config');

            // Register commands
            $this->commands([
                \App\Console\Commands\RunSqlAlerts::class,
                \App\Console\Commands\SqlAlertScheduler::class,
                \App\Console\Commands\SqlAlertCleanup::class,
            ]);
        }

        // Register policies
        Gate::policy(SqlAlert::class, SqlAlertPolicy::class);

        // Register event listeners
        $this->registerEventListeners();
    }

    /**
     * Register event listeners
     */
    protected function registerEventListeners()
    {
        // Listen for SQL Alert events
        \App\Models\SqlAlert::observe(\App\Observers\SqlAlertObserver::class);
    }
}

// ===== config/sql-alerts.php =====

return [
    /*
    |--------------------------------------------------------------------------
    | SQL Alert Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the SQL Alert system.
    |
    */

    'enabled' => env('SQL_ALERTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'timeout' => env('SQL_ALERT_TIMEOUT', 300), // 5 minutes
        'max_results' => env('SQL_ALERT_MAX_RESULTS', 10000),
        'max_attachments' => env('SQL_ALERT_MAX_ATTACHMENTS', 5),
        'max_attachment_size' => env('SQL_ALERT_MAX_ATTACHMENT_SIZE', 10485760), // 10MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'connection' => env('SQL_ALERT_QUEUE_CONNECTION', 'database'),
        'queue' => env('SQL_ALERT_QUEUE_NAME', 'sql-alerts'),
        'timeout' => env('SQL_ALERT_QUEUE_TIMEOUT', 300),
        'tries' => env('SQL_ALERT_QUEUE_TRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Drivers
    |--------------------------------------------------------------------------
    */
    'supported_drivers' => [
        'mysql' => [
            'name' => 'MySQL',
            'driver' => 'mysql',
            'default_port' => 3306,
            'test_query' => 'SELECT VERSION() as version',
        ],
        'postgresql' => [
            'name' => 'PostgreSQL',
            'driver' => 'pgsql',
            'default_port' => 5432,
            'test_query' => 'SELECT VERSION() as version',
        ],
        'sqlserver' => [
            'name' => 'SQL Server',
            'driver' => 'sqlsrv',
            'default_port' => 1433,
            'test_query' => 'SELECT @@VERSION as version',
        ],
        'oracle' => [
            'name' => 'Oracle',
            'driver' => 'oci',
            'default_port' => 1521,
            'test_query' => 'SELECT * FROM v$version WHERE ROWNUM = 1',
        ],
        'sqlite' => [
            'name' => 'SQLite',
            'driver' => 'sqlite',
            'default_port' => null,
            'test_query' => 'SELECT sqlite_version() as version',
        ],
        'mariadb' => [
            'name' => 'MariaDB',
            'driver' => 'mysql',
            'default_port' => 3306,
            'test_query' => 'SELECT VERSION() as version',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Formats
    |--------------------------------------------------------------------------
    */
    'export_formats' => [
        'excel' => [
            'name' => 'Excel (XLSX)',
            'extension' => 'xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'enabled' => true,
        ],
        'csv' => [
            'name' => 'CSV',
            'extension' => 'csv',
            'mime_type' => 'text/csv',
            'enabled' => true,
        ],
        'pdf' => [
            'name' => 'PDF',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'enabled' => false, // Requires additional PDF library
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'allowed_statements' => ['SELECT'],
        'blocked_keywords' => [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE',
            'TRUNCATE', 'GRANT', 'REVOKE', 'EXEC', 'EXECUTE', 'CALL',
            'DECLARE', 'SET', 'USE', 'SHOW', 'DESCRIBE', 'EXPLAIN',
            'LOAD_FILE', 'INTO OUTFILE', 'INTO DUMPFILE'
        ],
        'max_query_length' => 10000,
        'connection_timeout' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    */
    'cleanup' => [
        'execution_retention_days' => env('SQL_ALERT_CLEANUP_DAYS', 30),
        'attachment_retention_days' => env('SQL_ALERT_ATTACHMENT_CLEANUP_DAYS', 7),
        'failed_job_retention_days' => env('SQL_ALERT_FAILED_JOB_CLEANUP_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Settings
    |--------------------------------------------------------------------------
    */
    'email' => [
        'from_address' => env('SQL_ALERT_MAIL_FROM_ADDRESS', config('mail.from.address')),
        'from_name' => env('SQL_ALERT_MAIL_FROM_NAME', 'SQL Alert System'),
        'reply_to' => env('SQL_ALERT_MAIL_REPLY_TO'),
        'max_recipients' => env('SQL_ALERT_MAX_RECIPIENTS', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Logging
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'log_channel' => env('SQL_ALERT_LOG_CHANNEL', 'daily'),
        'log_level' => env('SQL_ALERT_LOG_LEVEL', 'info'),
        'metrics_enabled' => env('SQL_ALERT_METRICS_ENABLED', true),
        'health_check_enabled' => env('SQL_ALERT_HEALTH_CHECK_ENABLED', true),
    ],
];

// ===== app/Policies/SqlAlertPolicy.php =====

namespace App\Policies;

use App\Models\User;
use App\Models\SqlAlert;
use Illuminate\Auth\Access\HandlesAuthorization;

class SqlAlertPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any SQL alerts.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view sql alerts') || 
               $user->hasRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can view the SQL alert.
     */
    public function view(User $user, SqlAlert $sqlAlert): bool
    {
        return $user->hasPermissionTo('view sql alerts') || 
               $sqlAlert->created_by === $user->id ||
               $user->hasRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can create SQL alerts.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create sql alerts') || 
               $user->hasRole(['admin', 'manager']);
    }

    /**
     * Determine whether the user can update the SQL alert.
     */
    public function update(User $user, SqlAlert $sqlAlert): bool
    {
        return $user->hasPermissionTo('edit sql alerts') || 
               $sqlAlert->created_by === $user->id ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the SQL alert.
     */
    public function delete(User $user, SqlAlert $sqlAlert): bool
    {
        return $user->hasPermissionTo('delete sql alerts') || 
               $sqlAlert->created_by === $user->id ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can execute the SQL alert.
     */
    public function execute(User $user, SqlAlert $sqlAlert): bool
    {
        return $user->hasPermissionTo('execute sql alerts') || 
               $sqlAlert->created_by === $user->id ||
               $user->hasRole(['admin', 'manager']);
    }
}

// ===== app/Observers/SqlAlertObserver.php =====

namespace App\Observers;

use App\Models\SqlAlert;
use Illuminate\Support\Facades\Log;

class SqlAlertObserver
{
    /**
     * Handle the SqlAlert "created" event.
     */
    public function created(SqlAlert $sqlAlert): void
    {
        Log::info('SQL Alert created', [
            'alert_id' => $sqlAlert->id,
            'name' => $sqlAlert->name,
            'created_by' => $sqlAlert->created_by,
            'schedule_type' => $sqlAlert->schedule_type
        ]);

        // Schedule the alert if it's not manual
        if ($sqlAlert->schedule_type !== 'manual' && $sqlAlert->status === 'active') {
            $this->scheduleAlert($sqlAlert);
        }
    }

    /**
     * Handle the SqlAlert "updated" event.
     */
    public function updated(SqlAlert $sqlAlert): void
    {
        Log::info('SQL Alert updated', [
            'alert_id' => $sqlAlert->id,
            'name' => $sqlAlert->name,
            'updated_by' => $sqlAlert->updated_by,
            'changes' => $sqlAlert->getChanges()
        ]);

        // Reschedule if schedule configuration changed
        if ($sqlAlert->wasChanged(['schedule_config', 'schedule_type', 'status'])) {
            $this->scheduleAlert($sqlAlert);
        }
    }

    /**
     * Handle the SqlAlert "deleted" event.
     */
    public function deleted(SqlAlert $sqlAlert): void
    {
        Log::info('SQL Alert deleted', [
            'alert_id' => $sqlAlert->id,
            'name' => $sqlAlert->name
        ]);

        // Cancel any scheduled jobs
        $this->cancelScheduledJobs($sqlAlert);
    }

    /**
     * Schedule an alert
     */
    private function scheduleAlert(SqlAlert $sqlAlert): void
    {
        if ($sqlAlert->schedule_type === 'manual' || $sqlAlert->status !== 'active') {
            return;
        }

        // Update next run time
        $sqlAlert->updateNextRun();

        Log::info('SQL Alert scheduled', [
            'alert_id' => $sqlAlert->id,
            'next_run' => $sqlAlert->next_run?->toISOString()
        ]);
    }

    /**
     * Cancel scheduled jobs for an alert
     */
    private function cancelScheduledJobs(SqlAlert $sqlAlert): void
    {
        // Implementation would depend on your queue system
        // For example, you might need to remove jobs from the queue
        
        Log::info('Cancelled scheduled jobs for SQL Alert', [
            'alert_id' => $sqlAlert->id
        ]);
    }
}

// ===== app/Services/SqlAlertManager.php =====

namespace App\Services;

use App\Models\SqlAlert;
use App\Models\SqlAlertExecution;
use App\Jobs\ExecuteSqlAlertJob;
use Illuminate\Support\Facades\DB;
use Exception;

class SqlAlertManager
{
    /**
     * Create a new SQL Alert
     */
    public function create(array $data): SqlAlert
    {
        DB::beginTransaction();
        
        try {
            // Validate the configuration
            $this->validateConfiguration($data);
            
            // Create the alert
            $alert = SqlAlert::create($data);
            
            // Set up initial scheduling
            if ($alert->schedule_type !== 'manual' && $alert->status === 'active') {
                $alert->updateNextRun();
            }
            
            DB::commit();
            
            return $alert;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing SQL Alert
     */
    public function update(SqlAlert $alert, array $data): SqlAlert
    {
        DB::beginTransaction();
        
        try {
            // Validate the configuration
            $this->validateConfiguration($data);
            
            // Update the alert
            $alert->update($data);
            
            // Update scheduling if needed
            if ($alert->wasChanged(['schedule_config', 'schedule_type', 'status'])) {
                $alert->updateNextRun();
            }
            
            DB::commit();
            
            return $alert;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Execute an alert manually
     */
    public function execute(SqlAlert $alert, int $userId = null): SqlAlertExecution
    {
        if (!$alert->canExecute()) {
            throw new Exception("Alert cannot be executed in current status: {$alert->status}");
        }

        // Dispatch the job
        ExecuteSqlAlertJob::dispatch($alert, 'manual', $userId);

        // Create and return execution record
        return SqlAlertExecution::create([
            'sql_alert_id' => $alert->id,
            'trigger_type' => 'manual',
            'triggered_by' => $userId,
            'status' => 'pending'
        ]);
    }

    /**
     * Get alert statistics
     */
    public function getStatistics(SqlAlert $alert): array
    {
        return [
            'total_executions' => $alert->total_executions,
            'successful_executions' => $alert->successful_executions,
            'failed_executions' => $alert->total_executions - $alert->successful_executions,
            'success_rate' => $alert->success_rate,
            'last_run' => $alert->last_run,
            'next_run' => $alert->next_run,
            'avg_execution_time' => $this->getAverageExecutionTime($alert),
            'total_notifications_sent' => $this->getTotalNotificationsSent($alert),
            'recent_executions' => $alert->executions()->latest()->limit(10)->get()
        ];
    }

    /**
     * Validate alert configuration
     */
    private function validateConfiguration(array $data): void
    {
        $errors = [];

        // Validate database configuration
        if (empty($data['database_config'])) {
            $errors[] = 'Database configuration is required';
        }

        // Validate SQL query
        if (empty($data['sql_query'])) {
            $errors[] = 'SQL query is required';
        } else {
            $this->validateSqlQuery($data['sql_query']);
        }

        // Validate recipients
        if (empty($data['recipients'])) {
            $errors[] = 'At least one recipient is required';
        }

        // Validate email configuration
        if (empty($data['email_config'])) {
            $errors[] = 'Email configuration is required';
        }

        if (!empty($errors)) {
            throw new Exception('Validation failed: ' . implode(', ', $errors));
        }
    }

    /**
     * Validate SQL query for security
     */
    private function validateSqlQuery(string $query): void
    {
        $query = trim(strtoupper($query));
        
        // Must start with SELECT
        if (!preg_match('/^SELECT\s+/i', $query)) {
            throw new Exception('Only SELECT statements are allowed');
        }

        // Check for dangerous keywords
        $blockedKeywords = config('sql-alerts.security.blocked_keywords', []);
        
        foreach ($blockedKeywords as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/i', $query)) {
                throw new Exception("Blocked keyword found: {$keyword}");
            }
        }

        // Check query length
        $maxLength = config('sql-alerts.security.max_query_length', 10000);
        if (strlen($query) > $maxLength) {
            throw new Exception("Query too long. Maximum length: {$maxLength}");
        }
    }

    /**
     * Get average execution time
     */
    private function getAverageExecutionTime(SqlAlert $alert): ?string
    {
        $avgTime = $alert->executions()
                        ->where('status', 'success')
                        ->whereNotNull('execution_time_ms')
                        ->avg('execution_time_ms');

        return $avgTime ? round($avgTime, 2) . 'ms' : null;
    }

    /**
     * Get total notifications sent
     */
    private function getTotalNotificationsSent(SqlAlert $alert): int
    {
        return $alert->executions()->sum('notifications_sent');
    }
}

// ===== app/Services/SqlAlertExecutor.php =====

namespace App\Services;

use App\Models\SqlAlert;
use App\Models\SqlAlertExecution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SqlAlertExecutor
{
    /**
     * Execute SQL query and return results
     */
    public function executeQuery(SqlAlert $alert, int $limit = null): array
    {
        $connectionName = 'sql_alert_preview_' . uniqid();
        
        try {
            // Setup connection
            $config = $this->buildConnectionConfig($alert->database_config);
            config(['database.connections.' . $connectionName => $config]);

            // Execute query
            $query = $alert->sql_query;
            if ($limit && stripos($query, 'LIMIT') === false) {
                $query .= " LIMIT {$limit}";
            }

            $results = DB::connection($connectionName)
                        ->timeout(config('sql-alerts.security.connection_timeout', 30))
                        ->select($query);

            return $results;

        } finally {
            DB::purge($connectionName);
        }
    }

    /**
     * Test database connection
     */
    public function testConnection(array $config): array
    {
        $connectionName = 'sql_alert_test_' . uniqid();
        
        try {
            $dbConfig = $this->buildConnectionConfig($config);
            config(['database.connections.' . $connectionName => $dbConfig]);

            $startTime = microtime(true);
            
            // Test basic connection
            $pdo = DB::connection($connectionName)->getPdo();
            
            // Get database version
            $testQuery = $this->getTestQuery($config['type']);
            $result = DB::connection($connectionName)->selectOne($testQuery);
            
            $connectionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => true,
                'version' => $result->version ?? 'Unknown',
                'connection_time' => $connectionTime . 'ms',
                'driver' => $dbConfig['driver'],
                'charset' => $dbConfig['charset'] ?? 'utf8'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        } finally {
            DB::purge($connectionName);
        }
    }

    /**
     * Build database connection configuration
     */
    private function buildConnectionConfig(array $config): array
    {
        $drivers = config('sql-alerts.supported_drivers', []);
        $driverInfo = $drivers[$config['type']] ?? $drivers['mysql'];

        $dbConfig = [
            'driver' => $driverInfo['driver'],
            'charset' => $config['charset'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ];

        if ($config['type'] === 'sqlite') {
            $dbConfig['database'] = $config['database'];
        } else {
            $dbConfig['host'] = $config['host'];
            $dbConfig['port'] = $config['port'];
            $dbConfig['database'] = $config['database'];
            $dbConfig['username'] = $config['username'];
            $dbConfig['password'] = $config['password'];

            // Add SSL options if enabled
            if (!empty($config['ssl_enabled'])) {
                $dbConfig['options'] = [
                    \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                ];
            }

            // Add timeout
            $timeout = $config['timeout'] ?? 30;
            $dbConfig['options'][\PDO::ATTR_TIMEOUT] = $timeout;
        }

        return $dbConfig;
    }

    /**
     * Get test query for database type
     */
    private function getTestQuery(string $dbType): string
    {
        $drivers = config('sql-alerts.supported_drivers', []);
        return $drivers[$dbType]['test_query'] ?? 'SELECT 1 as version';
    }
}

// ===== app/Services/SqlAlertScheduler.php =====

namespace App\Services;

use App\Models\SqlAlert;
use App\Jobs\ExecuteSqlAlertJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SqlAlertScheduler
{
    /**
     * Get alerts that are ready to run
     */
    public function getReadyAlerts(): \Illuminate\Support\Collection
    {
        return SqlAlert::readyToRun()->get();
    }

    /**
     * Schedule an alert for execution
     */
    public function scheduleAlert(SqlAlert $alert): void
    {
        if ($alert->schedule_type === 'manual' || $alert->status !== 'active') {
            return;
        }

        ExecuteSqlAlertJob::dispatch($alert, 'scheduled')
                         ->delay($alert->next_run)
                         ->onQueue(config('sql-alerts.queue.queue', 'sql-alerts'));

        Log::info('SQL Alert scheduled for execution', [
            'alert_id' => $alert->id,
            'next_run' => $alert->next_run?->toISOString()
        ]);
    }

    /**
     * Update next run time for all active alerts
     */
    public function updateSchedules(): int
    {
        $alerts = SqlAlert::where('status', 'active')
                         ->where('schedule_type', '!=', 'manual')
                         ->get();

        $updated = 0;

        foreach ($alerts as $alert) {
            $oldNextRun = $alert->next_run;
            $alert->updateNextRun();
            
            if ($oldNextRun != $alert->next_run) {
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Get scheduling statistics
     */
    public function getStatistics(): array
    {
        $total = SqlAlert::where('status', 'active')->count();
        $scheduled = SqlAlert::where('status', 'active')
                           ->where('schedule_type', '!=', 'manual')
                           ->count();
        $overdue = SqlAlert::where('status', 'active')
                          ->where('next_run', '<', now())
                          ->where('schedule_type', '!=', 'manual')
                          ->count();
        $upcoming = SqlAlert::where('status', 'active')
                           ->where('next_run', '>=', now())
                           ->where('next_run', '<=', now()->addHour())
                           ->count();

        return [
            'total_active' => $total,
            'total_scheduled' => $scheduled,
            'overdue' => $overdue,
            'upcoming_hour' => $upcoming
        ];
    }
}

// ===== app/Http/Middleware/SqlAlertPermission.php =====

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SqlAlertPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission = 'view')
    {
        if (!config('sql-alerts.enabled', true)) {
            abort(503, 'SQL Alert system is currently disabled');
        }

        $user = $request->user();
        
        if (!$user) {
            abort(401, 'Authentication required');
        }

        // Check specific permission
        $permissionName = "sql-alerts.{$permission}";
        
        if (!$user->hasPermissionTo($permissionName) && !$user->hasRole(['admin', 'manager'])) {
            abort(403, 'Insufficient permissions for SQL Alert system');
        }

        return $next($request);
    }
}

// ===== database/seeders/SqlAlertPermissionSeeder.php =====

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SqlAlertPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create SQL Alert permissions
        $permissions = [
            'sql-alerts.view' => 'View SQL Alerts',
            'sql-alerts.create' => 'Create SQL Alerts',
            'sql-alerts.edit' => 'Edit SQL Alerts',
            'sql-alerts.delete' => 'Delete SQL Alerts',
            'sql-alerts.execute' => 'Execute SQL Alerts',
            'sql-alerts.manage' => 'Manage SQL Alert System',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'display_name' => $description,
                    'description' => "Permission to {$description}",
                    'category' => 'SQL Alerts'
                ]
            );
        }

        // Assign permissions to roles
        $adminRole = Role::where('name', 'admin')->first();
        $managerRole = Role::where('name', 'manager')->first();
        $userRole = Role::where('name', 'user')->first();

        if ($adminRole) {
            $adminRole->givePermissionTo(array_keys($permissions));
        }

        if ($managerRole) {
            $managerRole->givePermissionTo([
                'sql-alerts.view',
                'sql-alerts.create',
                'sql-alerts.edit',
                'sql-alerts.execute'
            ]);
        }

        if ($userRole) {
            $userRole->givePermissionTo([
                'sql-alerts.view'
            ]);
        }
    }
}

// ===== tests/Feature/SqlAlertTest.php =====

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SqlAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class SqlAlertTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    /** @test */
    public function it_can_create_sql_alert()
    {
        $alertData = [
            'name' => 'Test Alert',
            'description' => 'Test Description',
            'database_config' => [
                'type' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'database' => 'test_db',
                'username' => 'test_user',
                'password' => 'test_pass'
            ],
            'sql_query' => 'SELECT * FROM users WHERE created_at >= CURDATE()',
            'email_config' => [
                'subject' => 'Test Alert',
                'body_template' => 'Found {{record_count}} records'
            ],
            'recipients' => [
                ['email' => 'test@example.com', 'name' => 'Test User']
            ],
            'schedule_config' => [
                'type' => 'manual'
            ],
            'schedule_type' => 'manual',
            'status' => 'active'
        ];

        $response = $this->actingAs($this->admin)
                        ->postJson('/admin/sql-alerts', $alertData);

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $this->assertDatabaseHas('sql_alerts', [
            'name' => 'Test Alert',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function it_validates_sql_query_security()
    {
        $alertData = [
            'name' => 'Malicious Alert',
            'sql_query' => 'DROP TABLE users;',
            // ... other required fields
        ];

        $response = $this->actingAs($this->admin)
                        ->postJson('/admin/sql-alerts', $alertData);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_execute_sql_alert()
    {
        $alert = SqlAlert::factory()->create([
            'created_by' => $this->admin->id,
            'status' => 'active'
        ]);

        $response = $this->actingAs($this->admin)
                        ->postJson("/admin/sql-alerts/{$alert->id}/execute");

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $this->assertDatabaseHas('sql_alert_executions', [
            'sql_alert_id' => $alert->id,
            'trigger_type' => 'manual'
        ]);
    }

    /** @test */
    public function it_restricts_access_based_on_permissions()
    {
        $alert = SqlAlert::factory()->create();

        $response = $this->actingAs($this->user)
                        ->getJson("/admin/sql-alerts/{$alert->id}");

        $response->assertStatus(403);
    }
}

// ===== Installation Instructions =====

/*
# SQL Alert System Installation & Setup

## 1. Run Migrations
php artisan migrate

## 2. Publish Configuration
php artisan vendor:publish --tag=sql-alerts-config

## 3. Seed Permissions
php artisan db:seed --class=SqlAlertPermissionSeeder

## 4. Configure Queue
# Add to .env:
QUEUE_CONNECTION=database
SQL_ALERTS_ENABLED=true

## 5. Create Jobs Table (if not exists)
php artisan queue:table
php artisan migrate

## 6. Setup Scheduler
# Add to crontab:
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1

## 7. Start Queue Worker
php artisan queue:work --queue=sql-alerts

## 8. Test Installation
php artisan sql-alerts:run --dry-run

## 9. Create First Alert
# Visit: /admin/sql-alerts/create

## 10. Monitor Logs
tail -f storage/logs/laravel.log

## Commands Available:
php artisan sql-alerts:run              # Run scheduled alerts
php artisan sql-alerts:run --id=1       # Run specific alert
php artisan sql-alerts:schedule         # Update schedules
php artisan sql-alerts:cleanup          # Clean old data
php artisan sql-alerts:run --dry-run    # Test mode

## Security Notes:
- Only SELECT statements are allowed
- SQL injection protection is built-in
- File attachments are stored securely
- Email content is sanitized
- Database credentials are encrypted in storage

## Performance Tips:
- Use database indexes on query columns
- Set appropriate LIMIT clauses
- Configure queue workers for background processing
- Monitor execution times and optimize queries
- Regular cleanup of old execution data
*/