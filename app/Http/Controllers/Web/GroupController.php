<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationGroup;
use App\Models\User;
use App\Services\LdapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    protected $ldapService;

    public function __construct(LdapService $ldapService)
    {
        // parent::__construct();
        $this->ldapService = $ldapService;
        // $this->middleware('auth');
    }

    /**
     * Display a listing of notification groups
     */
    public function index(Request $request)
    {
        $query = NotificationGroup::with(['creator', 'users'])
            ->withCount(['users', 'notifications']);

        // Search functionality
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        $groups = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => NotificationGroup::count(),
            'active' => NotificationGroup::active()->count(),
            'manual' => NotificationGroup::byType('manual')->count(),
            'department' => NotificationGroup::byType('department')->count(),
            'ldap' => NotificationGroup::byType('ldap_group')->count(),
            'dynamic' => NotificationGroup::byType('dynamic')->count(),
            'role' => NotificationGroup::byType('role')->count(),
        ];

        return view('groups.index', compact('groups', 'stats'));
    }

    /**
     * Show the form for creating a new group
     */
    public function create()
    {
        $departments = $this->ldapService->getDepartments();
        $ldapGroups = $this->ldapService->getGroups();
        
        return view('groups.create', compact('departments', 'ldapGroups'));
    }

    /**
     * Store a newly created group
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:notification_groups',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:manual,department,role,ldap_group,dynamic',
            'criteria' => 'nullable|array',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
            'webhook_url' => 'nullable|url',
        ]);

        try {
            DB::beginTransaction();

            $group = NotificationGroup::create([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'criteria' => $request->criteria,
                'is_active' => true,
                'created_by' => Auth::id(),
                'webhook_url' => $request->webhook_url,
            ]);

            // Add users for manual groups
            if ($request->type === 'manual' && $request->filled('users')) {
                $group->addUsers($request->users, Auth::id());
            }

            // Auto-populate for department/ldap groups
            if (in_array($request->type, ['department', 'ldap_group'])) {
                $this->populateGroupMembers($group);
            }

            DB::commit();

            return redirect()->route('groups.index')
                ->with('success', 'สร้างกลุ่มการแจ้งเตือนเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating notification group: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'เกิดข้อผิดพลาดในการสร้างกลุ่ม: ' . $e->getMessage());
        }
    } 

    /**
     * Display the specified group
     */
    public function show(NotificationGroup $group)
    {
        $group->load(['creator', 'users', 'notifications' => function($query) {
            $query->latest()->take(10);
        }]);

        $stats = $group->getNotificationStats();

        return view('groups.show', compact('group', 'stats'));
    }

    /**
     * Show the form for editing the specified group
     */
    public function edit(NotificationGroup $group)
    {
        $group->load(['users']);
        $departments = $this->ldapService->getDepartments();
        $ldapGroups = $this->ldapService->getGroups();
        $availableUsers = User::active()->orderBy('display_name')->get();

        return view('groups.edit', compact('group', 'departments', 'ldapGroups', 'availableUsers'));
    }

    /**
     * Update the specified group
     */
    public function update(Request $request, NotificationGroup $group)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:notification_groups,name,' . $group->id,
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:manual,department,role,ldap_group,dynamic',
            'criteria' => 'nullable|array',
            'users' => 'nullable|array',
            'users.*' => 'exists:users,id',
            'is_active' => 'boolean',
            'webhook_url' => 'nullable|url',
        ]);

        try {
            DB::beginTransaction();

            $group->update([
                'name' => $request->name,
                'description' => $request->description,
                'type' => $request->type,
                'criteria' => $request->criteria,
                'is_active' => $request->boolean('is_active'),
                'webhook_url' => $request->webhook_url,
            ]);

            // *** แก้ไขตรงนี้ ***
            if ($request->type === 'manual') {
                $userIds = $request->users ?? [];
                
                // ใช้ sync แทน addUsers เพื่อ replace ข้อมูลเก่า
                $syncData = [];
                foreach ($userIds as $userId) {
                    $syncData[$userId] = [
                        'joined_at' => now(),
                        'added_by' => Auth::id(),
                    ];
                }
                
                // Log::info('Syncing manual group users:', [
                //     'group_id' => $group->id,
                //     'user_ids' => $userIds,
                //     'sync_data' => $syncData
                // ]);
                
                $result = $group->users()->sync($syncData);
                
                // Log::info('Sync result:', [
                //     'attached' => $result['attached'],
                //     'detached' => $result['detached'],
                //     'updated' => $result['updated']
                // ]);
            }

            // Auto-populate for department/ldap groups
            if (in_array($request->type, ['department', 'ldap_group', 'dynamic'])) {
                $this->populateGroupMembers($group);
            }

            DB::commit();

            return redirect()->route('groups.show', $group)
                ->with('success', 'อัปเดตกลุ่มเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating notification group: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'เกิดข้อผิดพลาดในการอัปเดต: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified group
     */
    public function destroy(NotificationGroup $group)
    {
        try {
            // Check if group has notifications
            if ($group->notifications()->exists()) {
                return back()->with('error', 'ไม่สามารถลบกลุ่มที่มีการแจ้งเตือนแล้ว');
            }

            $group->delete();

            return redirect()->route('groups.index')
                ->with('success', 'ลบกลุ่มเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            Log::error('Error deleting notification group: ' . $e->getMessage());
            
            return back()->with('error', 'เกิดข้อผิดพลาดในการลบกลุ่ม');
        }
    }

    /**
     * Add user to group
     */
    public function addUser(Request $request, NotificationGroup $group)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            if ($group->addUser($request->user_id, Auth::id())) {
                return response()->json([
                    'success' => true,
                    'message' => 'เพิ่มสมาชิกเรียบร้อยแล้ว'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'สมาชิกนี้อยู่ในกลุ่มแล้ว'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเพิ่มสมาชิก'
            ], 500);
        }
    }

    /**
     * Remove user from group
     */
    public function removeUser(Request $request, NotificationGroup $group)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            if ($group->removeUser($request->user_id)) {
                return response()->json([
                    'success' => true,
                    'message' => 'ลบสมาชิกเรียบร้อยแล้ว'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบสมาชิกในกลุ่ม'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบสมาชิก'
            ], 500);
        }
    }

    /**
     * Sync group membership
     */
    public function syncMembers(NotificationGroup $group)
    {
        try {
            $updated = $this->populateGroupMembers($group);
            
            return response()->json([
                'success' => true,
                'message' => "ซิงค์สมาชิกเรียบร้อยแล้ว (อัปเดต {$updated} คน)",
                'updated_count' => $updated
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการซิงค์: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users for AJAX dropdown
     */
    public function getUsers(Request $request)
    {
        $search = $request->get('q'); 
        
        $users = User::active()
            ->when($search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('display_name', 'ILIKE', "%{$search}%")
                      ->orWhere('email', 'ILIKE', "%{$search}%");
                });
            })
            ->limit(20)
            ->get(['id', 'display_name', 'email']);

        return response()->json($users->map(function($user) {
            return [
                'id' => $user->id,
                'text' => "{$user->display_name} ({$user->email})"
            ];
        }));
    }

    /**
     * Get group statistics for API
     */
    public function getStats()
    {
        try {
            $stats = [
                'total_groups' => NotificationGroup::count(),
                'active_groups' => NotificationGroup::active()->count(),
                'manual_groups' => NotificationGroup::byType('manual')->count(),
                'department_groups' => NotificationGroup::byType('department')->count(),
                'ldap_groups' => NotificationGroup::byType('ldap_group')->count(),
                'dynamic_groups' => NotificationGroup::byType('dynamic')->count(),
                'role_groups' => NotificationGroup::byType('role')->count(),
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch stats'], 500);
        }
    }

    /**
     * Preview members based on criteria
     */
    public function previewMembers(Request $request)
    {
        try {
            $type = $request->input('type');
            $criteria = $request->input('criteria', []);

            if (!$type || $type === 'manual') {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถดูตัวอย่างสมาชิกสำหรับกลุ่มแบบกำหนดเองได้'
                ]);
            }

            $query = User::active();

            // Apply criteria based on type
            switch ($type) {
                case 'department':
                    if (isset($criteria['department']) && $criteria['department']) {
                        $query->where('department', $criteria['department']);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'กรุณาเลือกแผนก'
                        ]);
                    }
                    break;

                case 'role':
                    if (isset($criteria['title']) && $criteria['title']) {
                        $query->where('title', 'ILIKE', '%' . $criteria['title'] . '%');
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'กรุณาระบุตำแหน่ง'
                        ]);
                    }
                    break;

                case 'ldap_group':
                    if (isset($criteria['ldap_group']) && $criteria['ldap_group']) {
                        // ใช้ LDAP service เพื่อดึงสมาชิกจากกลุ่ม LDAP
                        $ldapMembers = $this->ldapService->getGroupMembers($criteria['ldap_group']);
                        if (!empty($ldapMembers)) {
                            $query->whereIn('email', $ldapMembers);
                        } else {
                            $query->where('id', -1); // ไม่มีสมาชิก
                        }
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'กรุณาเลือก LDAP Group'
                        ]);
                    }
                    break;

                case 'dynamic':
                    // Apply multiple criteria
                    if (isset($criteria['department']) && $criteria['department']) {
                        $query->where('department', $criteria['department']);
                    }
                    if (isset($criteria['title']) && $criteria['title']) {
                        $query->where('title', 'ILIKE', '%' . $criteria['title'] . '%');
                    }
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'ประเภทกลุ่มไม่ถูกต้อง'
                    ]);
            }

            $members = $query->orderBy('display_name')
                ->get(['id', 'display_name', 'email', 'department', 'title'])
                ->toArray();

            return response()->json([
                'success' => true,
                'members' => $members,
                'count' => count($members)
            ]);

        } catch (\Exception $e) {
            Log::error('Preview members error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดูตัวอย่างสมาชิก'
            ], 500);
        }
    }

    /**
     * Export group members
     */
    public function exportMembers(NotificationGroup $group)
    {
        try {
            $filename = 'group_' . Str::slug($group->name) . '_members_' . date('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($group) {
                $file = fopen('php://output', 'w');
                
                // Add BOM for UTF-8
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                
                // Headers
                fputcsv($file, [
                    'ชื่อ',
                    'อีเมล', 
                    'แผนก',
                    'ตำแหน่ง',
                    'สถานะ',
                    'เข้าร่วมเมื่อ',
                    'เพิ่มโดย'
                ]);

                // Data
                $group->users()->with('pivot')->each(function($user) use ($file) {
                    fputcsv($file, [
                        $user->display_name,
                        $user->email,
                        $user->department ?? '',
                        $user->title ?? '',
                        $user->is_active ? 'ใช้งาน' : 'ไม่ใช้งาน',
                        $user->pivot->joined_at ? \Carbon\Carbon::parse($user->pivot->joined_at)->format('d/m/Y H:i') : '',
                        $user->pivot->added_by ? User::find($user->pivot->added_by)?->display_name ?? '' : 'ระบบ'
                    ]);
                });

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Export members error: ' . $e->getMessage());
            
            return back()->with('error', 'เกิดข้อผิดพลาดในการ export ข้อมูล');
        }
    }

    /**
     * Populate group members based on criteria
     */
    protected function populateGroupMembers(NotificationGroup $group): int
    {
        if ($group->is_manual) {
            return 0;
        }

        $users = $group->getEligibleUsers()->pluck('id')->toArray();
        
        $result = $group->syncUsers($users);
        
        return count($result['attached']) + count($result['detached']);
    }

    /**
     * Bulk sync all auto groups
     */
    public function bulkSync()
    {
        try {
            $autoGroups = NotificationGroup::active()
                ->whereIn('type', ['department', 'ldap_group', 'dynamic'])
                ->get();

            $totalUpdated = 0;
            $results = [];

            foreach ($autoGroups as $group) {
                $updated = $this->populateGroupMembers($group);
                $totalUpdated += $updated;
                
                $results[] = [
                    'group' => $group->name,
                    'updated' => $updated
                ];
            }

            return response()->json([
                'success' => true,
                'message' => "ซิงค์กลุ่มทั้งหมดเรียบร้อยแล้ว (อัปเดต {$totalUpdated} รายการ)",
                'total_updated' => $totalUpdated,
                'details' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk sync error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการซิงค์: ' . $e->getMessage()
            ], 500);
        }
    }
}