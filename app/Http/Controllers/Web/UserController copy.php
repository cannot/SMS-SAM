<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNotNull('last_login_at');
            } elseif ($request->status === 'inactive') {
                $query->whereNull('last_login_at');
            }
        }

        // Sort by
        $sortBy = $request->get('sort_by', 'username');
        $sortOrder = $request->get('sort_order', 'asc');
        
        $allowedSorts = ['username', 'email', 'department', 'title', 'last_login_at', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $users = $query->paginate(15);

        // Get departments for filter
        $departments = User::whereNotNull('department')
                          ->distinct()
                          ->pluck('department')
                          ->sort()
                          ->values();

        // Get sync status
        $lastSyncUser = User::latest('updated_at')->first();
        $lastSync = $lastSyncUser?->updated_at;
        $totalUsers = User::count();
        $activeUsers = User::whereNotNull('last_login_at')->count();

        return view('users.index', compact(
            'users', 
            'departments', 
            'lastSync', 
            'totalUsers', 
            'activeUsers'
        ));
    }

    public function show(User $user)
    {
        $user->load(['preferences', 'notifications' => function($query) {
            $query->latest()->limit(10);
        }]);

        $userStats = [
            'total_notifications' => $user->notifications()->count(),
            'notifications_this_month' => $user->notifications()
                ->whereMonth('created_at', now()->month)
                ->count(),
            'last_notification' => $user->notifications()->latest()->first(),
            'preferred_channels' => $user->preferences?->notification_channels ?? ['email', 'teams'],
        ];

        return view('users.show', compact('user', 'userStats'));
    }

    public function preferences(User $user)
    {
        // Only allow users to edit their own preferences or admins
        $adminUsers = ['admin', 'administrator', 'sa'];
        $isAdmin = in_array(strtolower(Auth::user()->username), $adminUsers);
        
        if ($user->id !== Auth::id() && !$isAdmin) {
            abort(403, 'Unauthorized to edit this user\'s preferences');
        }

        $preferences = $user->preferences ?: new UserPreference();
        
        return view('users.preferences', compact('user', 'preferences'));
    }

    public function updatePreferences(Request $request, User $user)
    {
        // Only allow users to edit their own preferences or admins
        $adminUsers = ['admin', 'administrator', 'sa'];
        $isAdmin = in_array(strtolower(Auth::user()->username), $adminUsers);
        
        if ($user->id !== Auth::id() && !$isAdmin) {
            abort(403, 'Unauthorized to edit this user\'s preferences');
        }

        $validated = $request->validate([
            'notification_channels' => 'nullable|array',
            'notification_channels.*' => 'in:email,teams',
            'email_frequency' => 'required|in:immediate,daily,weekly',
            'teams_frequency' => 'required|in:immediate,daily,weekly',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
            'timezone' => 'nullable|string|max:50',
            'language' => 'required|in:th,en',
        ]);

        // Set default channels if none selected
        if (empty($validated['notification_channels'])) {
            $validated['notification_channels'] = ['email'];
        }

        $user->preferences()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return redirect()->route('users.show', $user)
                         ->with('success', 'Preferences updated successfully!');
    }

    public function syncLdap(Request $request)
    {
        try {
            // ชั่วคราวปิดการตรวจสอบ role หรือใช้วิธีอื่น
            // Simple admin check - คุณสามารถปรับแก้ตามต้องการ
            $adminUsers = ['admin', 'administrator', 'sa']; // เพิ่ม username ที่เป็น admin
            $isAdmin = in_array(strtolower(Auth::user()->username), $adminUsers);
            
            if (!$isAdmin) {
                // หรือสามารถ comment บรรทัดนี้ออกเพื่อให้ทุกคนใช้ได้ชั่วคราว
                // abort(403, 'Unauthorized to sync LDAP users');
            }

            // Run the sync command
            // Artisan::call('ldap:sync-users');
            // $output = Artisan::output();
            // Use LdapService directly - correct namespace
            $ldapService = app(\App\Services\LdapService::class);
            $syncedCount = $ldapService->syncAllUsers();

            Log::info('LDAP sync initiated by user: ' . Auth::user()->username, [
                'synced_count' => $syncedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "LDAP sync completed successfully! Synced {$syncedCount} users.",
                'synced_count' => $syncedCount,
                'synced_at' => now()->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('LDAP sync failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'LDAP sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        // Only allow admins to export
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized to export users');
        }

        $format = $request->get('format', 'csv');
        
        $users = User::when($request->filled('search'), function($query) use ($request) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->when($request->filled('department'), function($query) use ($request) {
            $query->where('department', $request->department);
        })
        ->orderBy('name')
        ->get();

        $filename = 'users_' . now()->format('Y-m-d_H-i-s') . '.' . $format;

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function() use ($users) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'Username', 'Display Name', 'Email', 
                    'Department', 'Title', 'Last Login', 'Created At'
                ]);

                // CSV data
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->username,
                        $user->display_name,
                        $user->email,
                        $user->department,
                        $user->title,
                        $user->last_login_at?->format('Y-m-d H:i:s'),
                        $user->created_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // For other formats, you could add Excel export here
        abort(400, 'Unsupported export format');
    }

    public function bulkAction(Request $request)
    {
        // Only allow admins
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,reset_preferences',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        $users = User::whereIn('id', $validated['user_ids'])->get();
        $action = $validated['action'];
        $count = 0;

        foreach ($users as $user) {
            switch ($action) {
                case 'activate':
                    // You might want to implement user activation logic
                    $user->update(['email_verified_at' => now()]);
                    $count++;
                    break;
                    
                case 'deactivate':
                    // You might want to implement user deactivation logic
                    $user->update(['email_verified_at' => null]);
                    $count++;
                    break;
                    
                case 'reset_preferences':
                    $user->preferences()->delete();
                    $count++;
                    break;
            }
        }

        $actionName = [
            'activate' => 'activated',
            'deactivate' => 'deactivated', 
            'reset_preferences' => 'reset preferences for'
        ][$action];

        return back()->with('success', "Successfully {$actionName} {$count} user(s).");
    }
}