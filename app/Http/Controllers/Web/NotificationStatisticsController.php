<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\NotificationGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NotificationStatisticsController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * แสดงหน้าสถิติการแจ้งเตือน
     */
    public function index(Request $request)
    {
        try {
            $dateRange = $request->get('date_range', 'last_30_days');
            $startDate = $this->getStartDate($dateRange);
            $endDate = now();

            // สถิติพื้นฐาน
            $basicStats = $this->getBasicStats($startDate, $endDate);
            
            // สถิติการส่งตามช่องทาง
            $channelStats = $this->getChannelStats($startDate, $endDate);
            
            // สถิติการส่งรายวัน
            $dailyStats = $this->getDailyStats($startDate, $endDate);
            
            // สถิติ Template ที่ใช้มากที่สุด
            $templateStats = $this->getTemplateStats($startDate, $endDate);
            
            // สถิติกลุ่มผู้รับ (แก้ไขให้ใช้ recipient_groups)
            $groupStats = $this->getGroupStats($startDate, $endDate);
            
            // สถิติการส่งผลสำเร็จ/ล้มเหลว
            $deliveryStats = $this->getDeliveryStats($startDate, $endDate);

            return view('admin.statistics.index', compact(
                'basicStats', 
                'channelStats', 
                'dailyStats', 
                'templateStats', 
                'groupStats',
                'deliveryStats',
                'dateRange'
            ));
        } catch (\Exception $e) {
            \Log::error('Statistics Page Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาดในการโหลดข้อมูลสถิติ: ' . $e->getMessage());
        }
    }

    /**
     * API สำหรับดึงข้อมูลสถิติแบบ Real-time
     */
    public function apiStats(Request $request)
    {
        try {
            $dateRange = $request->get('date_range', 'last_7_days');
            $startDate = $this->getStartDate($dateRange);
            $endDate = now();

            $stats = [
                'basic' => $this->getBasicStats($startDate, $endDate),
                'channels' => $this->getChannelStats($startDate, $endDate),
                'daily' => $this->getDailyStats($startDate, $endDate),
                'delivery' => $this->getDeliveryStats($startDate, $endDate),
                'templates' => $this->getTemplateStats($startDate, $endDate),
                'groups' => $this->getGroupStats($startDate, $endDate),
                'last_updated' => now()->format('Y-m-d H:i:s')
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            \Log::error('API Stats Error: ' . $e->getMessage());
            return response()->json(['error' => 'เกิดข้อผิดพลาดในการโหลดข้อมูล'], 500);
        }
    }

    /**
     * Export ข้อมูลสถิติ
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'json');
        $dateRange = $request->get('date_range', 'last_30_days');
        
        $startDate = $this->getStartDate($dateRange);
        $endDate = now();

        $data = [
            'basic' => $this->getBasicStats($startDate, $endDate),
            'channels' => $this->getChannelStats($startDate, $endDate),
            'daily' => $this->getDailyStats($startDate, $endDate),
            'templates' => $this->getTemplateStats($startDate, $endDate),
            'groups' => $this->getGroupStats($startDate, $endDate),
            'delivery' => $this->getDeliveryStats($startDate, $endDate),
            'exported_at' => now()->format('Y-m-d H:i:s'),
            'date_range' => $dateRange
        ];

        return response()->json($data);
    }

    /**
     * ดึงสถิติพื้นฐาน
     */
    private function getBasicStats($startDate, $endDate)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('notifications')) {
                return $this->getEmptyBasicStats();
            }

            $totalNotifications = Notification::whereBetween('created_at', [$startDate, $endDate])->count();
            
            $totalSent = Notification::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'sent')->count();
            $totalPending = Notification::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'pending')->count();
            $totalFailed = Notification::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'failed')->count();
            
            // ตรวจสอบตารางอื่นๆ
            $totalUsers = $this->safeCount('users', ['is_active' => true]);
            $totalTemplates = $this->safeCount('notification_templates', ['is_active' => true]);
            $totalGroups = $this->safeCount('notification_groups', ['is_active' => true]);

            return [
                'total_notifications' => $totalNotifications,
                'total_sent' => $totalSent,
                'total_pending' => $totalPending,
                'total_failed' => $totalFailed,
                'success_rate' => $totalNotifications > 0 ? round(($totalSent / $totalNotifications) * 100, 2) : 0,
                'total_users' => $totalUsers,
                'total_templates' => $totalTemplates,
                'total_groups' => $totalGroups
            ];
        } catch (\Exception $e) {
            \Log::error('Basic Stats Error: ' . $e->getMessage());
            return $this->getEmptyBasicStats();
        }
    }

    /**
     * ดึงสถิติตามช่องทาง
     */
    private function getChannelStats($startDate, $endDate)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('notifications')) {
                return collect([]);
            }

            // สำหรับ PostgreSQL ที่ channels เป็น JSON
            if ($this->isPostgreSQL()) {
                try {
                    $results = DB::select("
                        SELECT 
                            TRIM(BOTH '\"' FROM json_array_elements_text(channels)) as channel,
                            COUNT(*) as total
                        FROM notifications 
                        WHERE created_at BETWEEN ? AND ?
                            AND channels IS NOT NULL
                            AND json_typeof(channels) = 'array'
                        GROUP BY TRIM(BOTH '\"' FROM json_array_elements_text(channels))
                        ORDER BY total DESC
                    ", [$startDate, $endDate]);

                    return collect($results)->map(function($item) {
                        return [
                            'channel' => $item->channel,
                            'total' => (int)$item->total,
                            'label' => $this->getChannelLabel($item->channel)
                        ];
                    });
                } catch (\Exception $e) {
                    \Log::warning('JSON array function failed, trying fallback: ' . $e->getMessage());
                    return $this->getChannelStatsGeneric($startDate, $endDate);
                }
            }

            return $this->getChannelStatsGeneric($startDate, $endDate);

        } catch (\Exception $e) {
            \Log::error('Channel Stats Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * ดึงสถิติช่องทางสำหรับ database ทั่วไป
     */
    private function getChannelStatsGeneric($startDate, $endDate)
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('notifications');
        $channelColumn = in_array('channels', $columns) ? 'channels' : 'channel';

        if (!in_array($channelColumn, $columns)) {
            return collect([]);
        }

        return Notification::whereBetween('created_at', [$startDate, $endDate])
            ->select($channelColumn, DB::raw('count(*) as total'))
            ->groupBy($channelColumn)
            ->orderBy('total', 'desc')
            ->get()
            ->map(function($item) use ($channelColumn) {
                $channel = $item->$channelColumn;
                $parsedChannel = $this->parseJsonChannels($channel);
                
                return [
                    'channel' => $parsedChannel,
                    'total' => $item->total,
                    'label' => $this->getChannelLabel($parsedChannel)
                ];
            });
    }

    /**
     * ดึงสถิติรายวัน
     */
    private function getDailyStats($startDate, $endDate)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('notifications')) {
                return collect([]);
            }

            return Notification::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN status = \'sent\' THEN 1 ELSE 0 END) as sent'),
                    DB::raw('SUM(CASE WHEN status = \'failed\' THEN 1 ELSE 0 END) as failed')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get()
                ->map(function($item) {
                    return [
                        'date' => Carbon::parse($item->date)->format('Y-m-d'),
                        'total' => (int)$item->total,
                        'sent' => (int)$item->sent,
                        'failed' => (int)$item->failed
                    ];
                });
        } catch (\Exception $e) {
            \Log::error('Daily Stats Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * ดึงสถิติ Template
     */
    private function getTemplateStats($startDate, $endDate)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('notifications') || 
                !DB::getSchemaBuilder()->hasTable('notification_templates')) {
                return collect([]);
            }

            // ตรวจสอบว่ามี column template_id หรือไม่
            $columns = DB::getSchemaBuilder()->getColumnListing('notifications');
            if (!in_array('template_id', $columns)) {
                \Log::info('Column template_id not found in notifications table');
                return collect([]);
            }

            $results = DB::table('notification_templates as nt')
                ->leftJoin('notifications as n', function($join) use ($startDate, $endDate) {
                    $join->on('nt.id', '=', 'n.template_id')
                         ->whereBetween('n.created_at', [$startDate, $endDate]);
                })
                ->select(
                    'nt.id',
                    'nt.name as template_name',
                    DB::raw('COUNT(n.id) as usage_count')
                )
                ->where('nt.is_active', true)
                ->groupBy('nt.id', 'nt.name')
                ->orderBy('usage_count', 'desc')
                ->limit(10)
                ->get();

            // ถ้าไม่มีข้อมูลจาก JOIN ให้แสดง template ทั้งหมดที่มี
            if ($results->isEmpty() || $results->every(fn($item) => $item->usage_count == 0)) {
                $templates = DB::table('notification_templates')
                    ->select('id', 'name as template_name')
                    ->where('is_active', true)
                    ->limit(5)
                    ->get();

                return $templates->map(function($template) {
                    return (object)[
                        'template_name' => $template->template_name,
                        'usage_count' => 0
                    ];
                });
            }

            return $results;
        } catch (\Exception $e) {
            \Log::error('Template Stats Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * ดึงสถิติกลุ่ม (แก้ไขให้ใช้ recipient_groups)
     */
    private function getGroupStatsc($startDate, $endDate)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('notifications') || 
                !DB::getSchemaBuilder()->hasTable('notification_groups')) {
                return collect([]);
            }

            // ตรวจสอบว่ามี column recipient_groups หรือไม่
            $columns = DB::getSchemaBuilder()->getColumnListing('notifications');
            if (!in_array('recipient_groups', $columns)) {
                \Log::info('Column recipient_groups not found in notifications table');
                return collect([]);
            }

            // สำหรับ PostgreSQL ที่ recipient_groups เป็น JSON array
            if ($this->isPostgreSQL()) {
                try {
                    // ลองใช้ json_array_elements_text เพื่อแยก group IDs
                    $results = DB::select("
                        SELECT 
                            ng.name as group_name,
                            COUNT(n.id) as notification_count
                        FROM notification_groups ng
                        JOIN (
                            SELECT 
                                id,
                                CAST(json_array_elements_text(recipient_groups) AS INTEGER) as group_id
                            FROM notifications 
                            WHERE created_at BETWEEN ? AND ?
                                AND recipient_groups IS NOT NULL
                                AND json_typeof(recipient_groups) = 'array'
                        ) n ON ng.id = n.group_id
                        WHERE ng.is_active = true
                        GROUP BY ng.id, ng.name
                        ORDER BY notification_count DESC
                        LIMIT 10
                    ", [$startDate, $endDate]);

                    if (!empty($results)) {
                        return collect($results)->map(function($item) {
                            return (object)[
                                'group_name' => $item->group_name,
                                'notification_count' => (int)$item->notification_count
                            ];
                        });
                    }
                } catch (\Exception $e) {
                    \Log::warning('JSON group parsing failed, trying alternative: ' . $e->getMessage());
                }

                // Alternative: ใช้ text matching
                try {
                    $results = DB::select("
                        SELECT 
                            ng.name as group_name,
                            COUNT(n.id) as notification_count
                        FROM notification_groups ng,
                             notifications n
                        WHERE n.created_at BETWEEN ? AND ?
                            AND n.recipient_groups IS NOT NULL
                            AND n.recipient_groups::text LIKE '%' || ng.id::text || '%'
                        GROUP BY ng.id, ng.name
                        ORDER BY notification_count DESC
                        LIMIT 10
                    ", [$startDate, $endDate]);

                    if (!empty($results)) {
                        return collect($results)->map(function($item) {
                            return (object)[
                                'group_name' => $item->group_name,
                                'notification_count' => (int)$item->notification_count
                            ];
                        });
                    }
                } catch (\Exception $e) {
                    \Log::warning('Alternative group matching failed: ' . $e->getMessage());
                }
            }

            // Fallback: แสดง groups ทั้งหมดที่มี
            $groups = DB::table('notification_groups')
                ->select('id', 'name as group_name')
                ->where('is_active', true)
                ->limit(5)
                ->get();

            return $groups->map(function($group) {
                return (object)[
                    'group_name' => $group->group_name,
                    'notification_count' => 0
                ];
            });

        } catch (\Exception $e) {
            \Log::error('Group Stats Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    private function getGroupStats($startDate, $endDate)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('notifications') || 
                !DB::getSchemaBuilder()->hasTable('notification_groups')) {
                return collect([]);
            }

            // ตรวจสอบว่ามี column recipient_groups หรือไม่
            $columns = DB::getSchemaBuilder()->getColumnListing('notifications');
            if (!in_array('recipient_groups', $columns)) {
                \Log::info('Column recipient_groups not found in notifications table');
                return collect([]);
            }

            $results = collect([]);

            // สำหรับ PostgreSQL ที่ recipient_groups เป็น JSON array
            if ($this->isPostgreSQL()) {
                try {
                    // 1. ดึงข้อมูลกลุ่มที่มี ID (แก้ไขการเปรียบเทียบ JSON)
                    $groupResults = DB::select("
                        SELECT 
                            ng.name as group_name,
                            COUNT(n.id) as notification_count
                        FROM notification_groups ng
                        JOIN (
                            SELECT 
                                id,
                                CAST(json_array_elements_text(recipient_groups) AS INTEGER) as group_id
                            FROM notifications 
                            WHERE created_at BETWEEN ? AND ?
                                AND recipient_groups IS NOT NULL
                                AND json_typeof(recipient_groups) = 'array'
                                AND json_array_length(recipient_groups) > 0
                        ) n ON ng.id = n.group_id
                        WHERE ng.is_active = true
                        GROUP BY ng.id, ng.name
                        ORDER BY notification_count DESC
                    ", [$startDate, $endDate]);

                    // 2. ดึงข้อมูลการแจ้งเตือนที่ไม่ระบุกลุ่ม (แก้ไขการเปรียบเทียบ)
                    $noGroupResult = DB::select("
                        SELECT COUNT(*) as notification_count
                        FROM notifications 
                        WHERE created_at BETWEEN ? AND ?
                            AND (
                                recipient_groups IS NULL 
                                OR recipient_groups::text = '[]'
                                OR json_array_length(recipient_groups) = 0
                            )
                    ", [$startDate, $endDate]);

                    // รวมผลลัพธ์
                    $results = collect($groupResults)->map(function($item) {
                        return (object)[
                            'group_name' => $item->group_name,
                            'notification_count' => (int)$item->notification_count
                        ];
                    });

                    // เพิ่ม "ไม่ระบุกลุ่ม" ถ้ามีข้อมูล
                    $noGroupCount = (int)($noGroupResult[0]->notification_count ?? 0);
                    if ($noGroupCount > 0) {
                        $results->prepend((object)[
                            'group_name' => 'ไม่ระบุกลุ่ม',
                            'notification_count' => $noGroupCount
                        ]);
                    }

                    // เรียงลำดับใหม่ตามจำนวน
                    $results = $results->sortByDesc('notification_count')->take(10);

                    if ($results->isNotEmpty()) {
                        return $results;
                    }
                } catch (\Exception $e) {
                    \Log::warning('JSON group parsing failed, trying alternative: ' . $e->getMessage());
                }

                // Alternative 1: ใช้ text matching (แก้ไขการเปรียบเทียบ JSON)
                try {
                    $groupResults = DB::select("
                        SELECT 
                            ng.name as group_name,
                            COUNT(n.id) as notification_count
                        FROM notification_groups ng,
                             notifications n
                        WHERE n.created_at BETWEEN ? AND ?
                            AND n.recipient_groups IS NOT NULL
                            AND n.recipient_groups::text != '[]'
                            AND n.recipient_groups::text LIKE '%' || ng.id::text || '%'
                        GROUP BY ng.id, ng.name
                        ORDER BY notification_count DESC
                    ", [$startDate, $endDate]);

                    // ดึงข้อมูลไม่ระบุกลุ่ม (แก้ไขการเปรียบเทียบ)
                    $noGroupResult = DB::select("
                        SELECT COUNT(*) as notification_count
                        FROM notifications 
                        WHERE created_at BETWEEN ? AND ?
                            AND (
                                recipient_groups IS NULL 
                                OR recipient_groups::text = '[]'
                            )
                    ", [$startDate, $endDate]);

                    $results = collect($groupResults)->map(function($item) {
                        return (object)[
                            'group_name' => $item->group_name,
                            'notification_count' => (int)$item->notification_count
                        ];
                    });

                    $noGroupCount = (int)($noGroupResult[0]->notification_count ?? 0);
                    if ($noGroupCount > 0) {
                        $results->prepend((object)[
                            'group_name' => 'ไม่ระบุกลุ่ม',
                            'notification_count' => $noGroupCount
                        ]);
                    }

                    if ($results->isNotEmpty()) {
                        return $results->sortByDesc('notification_count')->take(10);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Alternative group matching failed: ' . $e->getMessage());
                }

                // Alternative 2: ใช้ PHP เพื่อ parse JSON
                try {
                    $notifications = DB::table('notifications')
                        ->select('id', 'recipient_groups')
                        ->whereBetween('created_at', [$startDate, $endDate])
                        ->whereNotNull('recipient_groups')
                        ->get();

                    $groupCounts = [];
                    $noGroupCount = 0;

                    foreach ($notifications as $notification) {
                        $groups = $notification->recipient_groups;
                        
                        // ตรวจสอบว่าเป็น JSON string หรือไม่
                        if (is_string($groups)) {
                            try {
                                $decoded = json_decode($groups, true);
                                if (is_array($decoded)) {
                                    if (empty($decoded)) {
                                        $noGroupCount++;
                                    } else {
                                        foreach ($decoded as $groupId) {
                                            if (is_numeric($groupId)) {
                                                $groupCounts[(int)$groupId] = ($groupCounts[(int)$groupId] ?? 0) + 1;
                                            }
                                        }
                                    }
                                } else {
                                    $noGroupCount++;
                                }
                            } catch (\Exception $e) {
                                $noGroupCount++;
                            }
                        } elseif (is_null($groups)) {
                            $noGroupCount++;
                        } else {
                            $noGroupCount++;
                        }
                    }

                    // ดึงชื่อกลุ่มจาก database
                    $groupNames = DB::table('notification_groups')
                        ->whereIn('id', array_keys($groupCounts))
                        ->where('is_active', true)
                        ->pluck('name', 'id');

                    $results = collect($groupCounts)->map(function($count, $groupId) use ($groupNames) {
                        return (object)[
                            'group_name' => $groupNames[$groupId] ?? "กลุ่ม #{$groupId}",
                            'notification_count' => $count
                        ];
                    });

                    // เพิ่มไม่ระบุกลุ่ม
                    if ($noGroupCount > 0) {
                        $results->prepend((object)[
                            'group_name' => 'ไม่ระบุกลุ่ม',
                            'notification_count' => $noGroupCount
                        ]);
                    }

                    if ($results->isNotEmpty()) {
                        return $results->sortByDesc('notification_count')->take(10);
                    }
                } catch (\Exception $e) {
                    \Log::warning('PHP JSON parsing failed: ' . $e->getMessage());
                }
            }

            // Fallback: สร้างข้อมูลตัวอย่างพร้อมกับ "ไม่ระบุกลุ่ม"
            $groups = DB::table('notification_groups')
                ->select('id', 'name as group_name')
                ->where('is_active', true)
                ->limit(4)
                ->get();

            $results = $groups->map(function($group) {
                return (object)[
                    'group_name' => $group->group_name,
                    'notification_count' => 0
                ];
            });

            // เพิ่มไม่ระบุกลุ่ม
            $results->prepend((object)[
                'group_name' => 'ไม่ระบุกลุ่ม',
                'notification_count' => 0
            ]);

            return $results;

        } catch (\Exception $e) {
            \Log::error('Group Stats Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * ตรวจสอบว่า JSON array ว่างหรือไม่ (helper method)
     */
    private function isEmptyJsonArray($jsonData)
    {
        if (is_null($jsonData)) {
            return true;
        }

        if (is_string($jsonData)) {
            $trimmed = trim($jsonData);
            if ($trimmed === '[]' || $trimmed === 'null' || $trimmed === '') {
                return true;
            }

            try {
                $decoded = json_decode($jsonData, true);
                if (is_array($decoded) && empty($decoded)) {
                    return true;
                }
            } catch (\Exception $e) {
                return true;
            }
        }

        return false;
    }

    /**
     * Debug method สำหรับตรวจสอบข้อมูล recipient_groups
     */
    public function debugRecipientGroups()
    {
        try {
            $notifications = DB::table('notifications')
                ->select('id', 'subject', 'recipient_groups', 'created_at')
                ->whereNotNull('recipient_groups')
                ->limit(10)
                ->get();

            $debug = [];
            foreach ($notifications as $notification) {
                $groups = $notification->recipient_groups;
                
                $debug[] = [
                    'id' => $notification->id,
                    'subject' => $notification->subject,
                    'recipient_groups_raw' => $groups,
                    'recipient_groups_type' => gettype($groups),
                    'is_empty' => $this->isEmptyJsonArray($groups),
                    'created_at' => $notification->created_at
                ];
            }

            return response()->json($debug);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * ดึงสถิติการส่งผลสำเร็จ/ล้มเหลว
     */
    private function getDeliveryStats($startDate, $endDate)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('notifications')) {
                return [];
            }

            if ($this->isPostgreSQL()) {
                try {
                    $results = DB::select("
                        SELECT 
                            TRIM(BOTH '\"' FROM json_array_elements_text(channels)) as channel,
                            status,
                            COUNT(*) as count
                        FROM notifications 
                        WHERE created_at BETWEEN ? AND ?
                            AND channels IS NOT NULL
                            AND json_typeof(channels) = 'array'
                        GROUP BY TRIM(BOTH '\"' FROM json_array_elements_text(channels)), status
                        ORDER BY channel, status
                    ", [$startDate, $endDate]);

                    $grouped = collect($results)->groupBy('channel');
                    
                    $result = [];
                    foreach ($grouped as $channel => $channelStats) {
                        $result[$channel] = [
                            'channel' => $channel,
                            'label' => $this->getChannelLabel($channel),
                            'sent' => (int)($channelStats->where('status', 'sent')->first()->count ?? 0),
                            'failed' => (int)($channelStats->where('status', 'failed')->first()->count ?? 0),
                            'pending' => (int)($channelStats->where('status', 'pending')->first()->count ?? 0)
                        ];
                    }

                    return $result;
                } catch (\Exception $e) {
                    \Log::warning('JSON delivery stats failed, using fallback: ' . $e->getMessage());
                }
            }

            return $this->getDeliveryStatsGeneric($startDate, $endDate);

        } catch (\Exception $e) {
            \Log::error('Delivery Stats Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * ดึงสถิติการส่งสำหรับ database ทั่วไป
     */
    private function getDeliveryStatsGeneric($startDate, $endDate)
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('notifications');
        $channelColumn = in_array('channels', $columns) ? 'channels' : 'channel';

        if (!in_array($channelColumn, $columns)) {
            return [];
        }

        $stats = Notification::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'status',
                $channelColumn,
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('status', $channelColumn)
            ->get()
            ->groupBy($channelColumn);

        $result = [];
        foreach ($stats as $channel => $channelStats) {
            $parsedChannel = $this->parseJsonChannels($channel);
            
            $result[$parsedChannel] = [
                'channel' => $parsedChannel,
                'label' => $this->getChannelLabel($parsedChannel),
                'sent' => (int)($channelStats->where('status', 'sent')->first()->count ?? 0),
                'failed' => (int)($channelStats->where('status', 'failed')->first()->count ?? 0),
                'pending' => (int)($channelStats->where('status', 'pending')->first()->count ?? 0)
            ];
        }

        return $result;
    }

    /**
     * แปลง JSON channels เป็น string
     */
    private function parseJsonChannels($channels)
    {
        if (empty($channels)) {
            return 'unknown';
        }

        if (is_string($channels) && (str_starts_with($channels, '[') || str_starts_with($channels, '"'))) {
            try {
                $decoded = json_decode($channels, true);
                if (is_array($decoded)) {
                    return implode(', ', $decoded);
                } else if (is_string($decoded)) {
                    return $decoded;
                }
            } catch (\Exception $e) {
                return trim($channels, '[""]');
            }
        }

        return $channels;
    }

    /**
     * ตรวจสอบว่าใช้ PostgreSQL หรือไม่
     */
    private function isPostgreSQL()
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    /**
     * นับจำนวนข้อมูลในตารางอย่างปลอดภัย
     */
    private function safeCount($table, $conditions = [])
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                return 0;
            }

            $query = DB::table($table);
            foreach ($conditions as $column => $value) {
                if (DB::getSchemaBuilder()->hasColumn($table, $column)) {
                    $query->where($column, $value);
                }
            }

            return $query->count();
        } catch (\Exception $e) {
            \Log::error("Safe Count Error for table {$table}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * คำนวณวันที่เริ่มต้นตาม date range
     */
    private function getStartDate($dateRange)
    {
        switch ($dateRange) {
            case 'today':
                return now()->startOfDay();
            case 'yesterday':
                return now()->subDay()->startOfDay();
            case 'last_7_days':
                return now()->subDays(7);
            case 'last_30_days':
                return now()->subDays(30);
            case 'last_3_months':
                return now()->subMonths(3);
            case 'last_6_months':
                return now()->subMonths(6);
            case 'last_year':
                return now()->subYear();
            default:
                return now()->subDays(30);
        }
    }

    /**
     * แปลงชื่อช่องทางเป็นภาษาไทย
     */
    private function getChannelLabel($channel)
    {
        if (empty($channel) || $channel === 'unknown') {
            return 'ไม่ระบุ';
        }

        $labels = [
            'email' => 'อีเมล',
            'teams' => 'Microsoft Teams',
            'sms' => 'SMS',
            'webhook' => 'Webhook',
            'slack' => 'Slack'
        ];

        if (str_contains($channel, ',')) {
            $channels = explode(',', $channel);
            $translatedChannels = array_map(function($ch) use ($labels) {
                $ch = trim($ch);
                return $labels[$ch] ?? $ch;
            }, $channels);
            return implode(', ', $translatedChannels);
        }

        return $labels[$channel] ?? $channel;
    }

    /**
     * สร้างข้อมูลสถิติพื้นฐานเปล่า
     */
    private function getEmptyBasicStats()
    {
        return [
            'total_notifications' => 0,
            'total_sent' => 0,
            'total_pending' => 0,
            'total_failed' => 0,
            'success_rate' => 0,
            'total_users' => 0,
            'total_templates' => 0,
            'total_groups' => 0
        ];
    }
}