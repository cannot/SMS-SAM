<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\NotificationGroup;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;

class StatisticsHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'statistics:health-check 
                          {--fix : พยายามแก้ไขปัญหาอัตโนมัติ}
                          {--verbose : แสดงรายละเอียดเพิ่มเติม}';

    /**
     * The console command description.
     */
    protected $description = 'ตรวจสอบสุขภาพของระบบสถิติการแจ้งเตือน';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 เริ่มตรวจสอบสุขภาพระบบสถิติ...');
        $this->line('');

        $issues = 0;

        // ตรวจสอบฐานข้อมูล
        $issues += $this->checkDatabase();
        
        // ตรวจสอบตารางที่จำเป็น
        $issues += $this->checkTables();
        
        // ตรวจสอบ Indexes
        $issues += $this->checkIndexes();
        
        // ตรวจสอบ Permissions
        $issues += $this->checkPermissions();
        
        // ตรวจสอบข้อมูลสถิติ
        $issues += $this->checkStatisticsData();
        
        // ตรวจสอบประสิทธิภาพ
        $issues += $this->checkPerformance();

        $this->line('');
        
        if ($issues === 0) {
            $this->info('✅ ระบบสถิติทำงานปกติ ไม่พบปัญหา');
        } else {
            $this->warn("⚠️  พบปัญหา {$issues} รายการ");
            
            if ($this->option('fix')) {
                $this->line('');
                $this->info('🔧 พยายามแก้ไขปัญหา...');
                $this->fixIssues();
            } else {
                $this->line('');
                $this->comment('💡 ใช้ --fix เพื่อพยายามแก้ไขปัญหาอัตโนมัติ');
            }
        }

        return $issues === 0 ? 0 : 1;
    }

    /**
     * ตรวจสอบการเชื่อมต่อฐานข้อมูล
     */
    private function checkDatabase(): int
    {
        $this->comment('📊 ตรวจสอบฐานข้อมูล...');
        
        try {
            DB::connection()->getPdo();
            $this->info('  ✅ การเชื่อมต่อฐานข้อมูล: ปกติ');
            
            // ตรวจสอบจำนวนข้อมูล
            $notificationCount = Notification::count();
            $this->info("  📈 จำนวนการแจ้งเตือน: " . number_format($notificationCount));
            
            if ($this->option('verbose')) {
                $recentCount = Notification::where('created_at', '>=', now()->subDays(7))->count();
                $this->line("     - 7 วันล่าสุด: " . number_format($recentCount));
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('  ❌ การเชื่อมต่อฐานข้อมูล: ล้มเหลว');
            $this->error("     Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * ตรวจสอบตารางที่จำเป็น
     */
    private function checkTables(): int
    {
        $this->comment('🗃️  ตรวจสอบตาราง...');
        
        $requiredTables = [
            'notifications' => 'ตารางการแจ้งเตือน',
            'notification_templates' => 'ตาราง Template',
            'notification_groups' => 'ตารางกลุ่ม',
            'users' => 'ตารางผู้ใช้',
            'permissions' => 'ตาราง Permissions',
            'roles' => 'ตาราง Roles'
        ];

        $issues = 0;
        
        foreach ($requiredTables as $table => $description) {
            if (Schema::hasTable($table)) {
                $this->info("  ✅ {$description}: มีอยู่");
                
                if ($this->option('verbose')) {
                    $count = DB::table($table)->count();
                    $this->line("     - จำนวนข้อมูล: " . number_format($count));
                }
            } else {
                $this->error("  ❌ {$description}: ไม่พบ");
                $issues++;
            }
        }

        return $issues;
    }

    /**
     * ตรวจสอบ Database Indexes
     */
    private function checkIndexes(): int
    {
        $this->comment('🔍 ตรวจสอบ Database Indexes...');
        
        $requiredIndexes = [
            'notifications' => [
                'idx_notifications_date_status',
                'idx_notifications_channel_date',
                'idx_notifications_template_date'
            ]
        ];

        $issues = 0;

        try {
            foreach ($requiredIndexes as $table => $indexes) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                $existingIndexes = collect(DB::select("SHOW INDEX FROM {$table}"))
                    ->pluck('Key_name')
                    ->unique()
                    ->toArray();

                foreach ($indexes as $index) {
                    if (in_array($index, $existingIndexes)) {
                        $this->info("  ✅ Index {$index}: มีอยู่");
                    } else {
                        $this->warn("  ⚠️  Index {$index}: ไม่พบ (อาจส่งผลต่อประสิทธิภาพ)");
                        $issues++;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error('  ❌ ไม่สามารถตรวจสอบ indexes ได้');
            $this->error("     Error: " . $e->getMessage());
            $issues++;
        }

        return $issues;
    }

    /**
     * ตรวจสอบ Permissions
     */
    private function checkPermissions(): int
    {
        $this->comment('🔐 ตรวจสอบ Permissions...');
        
        $requiredPermissions = [
            'view-notification-analytics' => 'ดูสถิติการแจ้งเตือน',
            'view-notifications' => 'ดูการแจ้งเตือน',
            'view-users' => 'ดูผู้ใช้',
            'view-templates' => 'ดู Templates'
        ];

        $issues = 0;

        foreach ($requiredPermissions as $permission => $description) {
            $exists = Permission::where('name', $permission)->exists();
            
            if ($exists) {
                $this->info("  ✅ Permission '{$permission}': มีอยู่");
            } else {
                $this->error("  ❌ Permission '{$permission}': ไม่พบ");
                $issues++;
            }
        }

        return $issues;
    }

    /**
     * ตรวจสอบข้อมูลสถิติ
     */
    private function checkStatisticsData(): int
    {
        $this->comment('📊 ตรวจสอบข้อมูลสถิติ...');
        
        $issues = 0;

        try {
            // ตรวจสอบข้อมูลการแจ้งเตือน
            $today = now()->startOfDay();
            $todayCount = Notification::whereDate('created_at', $today)->count();
            $last7Days = Notification::where('created_at', '>=', now()->subDays(7))->count();
            $last30Days = Notification::where('created_at', '>=', now()->subDays(30))->count();

            $this->info("  📈 ข้อมูลการแจ้งเตือน:");
            $this->line("     - วันนี้: " . number_format($todayCount));
            $this->line("     - 7 วันล่าสุด: " . number_format($last7Days));
            $this->line("     - 30 วันล่าสุด: " . number_format($last30Days));

            // ตรวจสอบสถานะ
            $statusCounts = Notification::select('status', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('status')
                ->pluck('count', 'status');

            $this->info("  📊 สถานะการส่ง (30 วันล่าสุด):");
            foreach ($statusCounts as $status => $count) {
                $this->line("     - {$status}: " . number_format($count));
            }

            // ตรวจสอบช่องทาง
            $channelCounts = Notification::select('channel', DB::raw('COUNT(*) as count'))
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('channel')
                ->pluck('count', 'channel');

            $this->info("  📡 การใช้งานช่องทาง (30 วันล่าสุด):");
            foreach ($channelCounts as $channel => $count) {
                $this->line("     - {$channel}: " . number_format($count));
            }

            // ตรวจสอบข้อมูลที่ผิดปกติ
            $nullStatusCount = Notification::whereNull('status')->count();
            if ($nullStatusCount > 0) {
                $this->warn("  ⚠️  พบข้อมูลที่ไม่มีสถานะ: " . number_format($nullStatusCount) . " รายการ");
                $issues++;
            }

            $futureNotifications = Notification::where('created_at', '>', now())->count();
            if ($futureNotifications > 0) {
                $this->warn("  ⚠️  พบข้อมูลที่มีเวลาในอนาคต: " . number_format($futureNotifications) . " รายการ");
                $issues++;
            }

        } catch (\Exception $e) {
            $this->error('  ❌ ไม่สามารถตรวจสอบข้อมูลสถิติได้');
            $this->error("     Error: " . $e->getMessage());
            $issues++;
        }

        return $issues;
    }

    /**
     * ตรวจสอบประสิทธิภาพ
     */
    private function checkPerformance(): int
    {
        $this->comment('⚡ ตรวจสอบประสิทธิภาพ...');
        
        $issues = 0;

        try {
            // ทดสอบ query สำหรับสถิติ
            $start = microtime(true);
            
            Notification::whereBetween('created_at', [now()->subDays(30), now()])
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get();
            
            $queryTime = (microtime(true) - $start) * 1000;
            
            if ($queryTime < 100) {
                $this->info("  ✅ ความเร็ว Query: {$queryTime}ms (ดีมาก)");
            } elseif ($queryTime < 500) {
                $this->info("  ✅ ความเร็ว Query: {$queryTime}ms (ดี)");
            } elseif ($queryTime < 1000) {
                $this->warn("  ⚠️  ความเร็ว Query: {$queryTime}ms (ช้า)");
                $issues++;
            } else {
                $this->error("  ❌ ความเร็ว Query: {$queryTime}ms (ช้ามาก)");
                $issues++;
            }

            // ตรวจสอบขนาดตาราง
            $tableSize = DB::select("
                SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb'
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'notifications'
            ")[0]->size_mb ?? 0;

            $this->info("  📊 ขนาดตาราง notifications: {$tableSize} MB");

            if ($tableSize > 1000) {
                $this->warn("  ⚠️  ตารางมีขนาดใหญ่ อาจต้องการ optimization");
                $issues++;
            }

        } catch (\Exception $e) {
            $this->error('  ❌ ไม่สามารถตรวจสอบประสิทธิภาพได้');
            $this->error("     Error: " . $e->getMessage());
            $issues++;
        }

        return $issues;
    }

    /**
     * พยายามแก้ไขปัญหา
     */
    private function fixIssues(): void
    {
        // แก้ไข Permissions
        $this->fixPermissions();
        
        // แก้ไข Indexes
        $this->fixIndexes();
        
        // ทำความสะอาดข้อมูล
        $this->cleanupData();
    }

    /**
     * แก้ไข Permissions
     */
    private function fixPermissions(): void
    {
        $requiredPermissions = [
            'view-notification-analytics' => 'ดูสถิติการแจ้งเตือน',
            'view-notifications' => 'ดูการแจ้งเตือน',
            'view-users' => 'ดูผู้ใช้',
            'view-templates' => 'ดู Templates'
        ];

        foreach ($requiredPermissions as $permission => $description) {
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'description' => $description
                ]);
                $this->info("  ✅ สร้าง Permission '{$permission}' สำเร็จ");
            }
        }
    }

    /**
     * แก้ไข Indexes
     */
    private function fixIndexes(): void
    {
        try {
            // สร้าง indexes ที่จำเป็น
            $indexes = [
                'CREATE INDEX IF NOT EXISTS idx_notifications_date_status ON notifications(created_at, status)',
                'CREATE INDEX IF NOT EXISTS idx_notifications_channel_date ON notifications(channel, created_at)',
                'CREATE INDEX IF NOT EXISTS idx_notifications_template_date ON notifications(template_id, created_at)'
            ];

            foreach ($indexes as $sql) {
                DB::statement($sql);
            }
            
            $this->info('  ✅ สร้าง Database Indexes สำเร็จ');
        } catch (\Exception $e) {
            $this->error('  ❌ ไม่สามารถสร้าง Indexes ได้: ' . $e->getMessage());
        }
    }

    /**
     * ทำความสะอาดข้อมูล
     */
    private function cleanupData(): void
    {
        try {
            // แก้ไขข้อมูลที่ไม่มีสถานะ
            $updated = Notification::whereNull('status')->update(['status' => 'unknown']);
            if ($updated > 0) {
                $this->info("  ✅ แก้ไขข้อมูลที่ไม่มีสถานะ: {$updated} รายการ");
            }

            // ลบข้อมูลที่มีเวลาในอนาคต (ถ้ามี)
            $deleted = Notification::where('created_at', '>', now())->delete();
            if ($deleted > 0) {
                $this->info("  ✅ ลบข้อมูลที่มีเวลาผิดปกติ: {$deleted} รายการ");
            }

        } catch (\Exception $e) {
            $this->error('  ❌ ไม่สามารถทำความสะอาดข้อมูลได้: ' . $e->getMessage());
        }
    }
}