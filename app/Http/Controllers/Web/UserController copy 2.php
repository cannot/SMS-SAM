<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\NotificationGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        try {
            $query = User::with(['roles', 'notificationGroups' => function($query) {
                $query->select('notification_groups.id', 'notification_groups.name')
                      ->where('notification_groups.is_active', true);
            }]);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->search($search);
            }

            // Filter by department
            if ($request->filled('department')) {
                $query->byDepartment($request->department);
            }

            // Filter by role
            if ($request->filled('role')) {
                $query->withRole($request->role);
            }

            // Filter by status
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'display_name');
            $sortDirection = $request->get('sort_direction', 'asc');

            if (in_array($sortBy, ['display_name', 'username', 'email', 'department', 'created_at', 'last_login_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            }

            // Pagination
            $users = $query->paginate(20)->appends($request->query());

            // Get filter options
            $departments = User::active()
                ->whereNotNull('department')
                ->where('department', '!=', '')
                ->distinct()
                ->pluck('department')
                ->sort();

            // Get roles with display names (fallback to name if display_name doesn't exist)
            $roles = collect();
            try {
                if (Schema::hasColumn('roles', 'display_name')) {
                    $roles = DB::table('roles')
                        ->select('name', 'display_name')
                        ->orderBy('display_name')
                        ->get();
                } else {
                    $roles = DB::table('roles')
                        ->select('name', 'name as display_name')
                        ->orderBy('name')
                        ->get();
                }
            } catch (\Exception $e) {
                // Fallback if there's any issue
                $roles = DB::table('roles')
                    ->select('name')
                    ->orderBy('name')
                    ->get()
                    ->map(function($role) {
                        $role->display_name = ucfirst(str_replace('_', ' ', $role->name));
                        return $role;
                    });
            }

            // Statistics
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::active()->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'recent_logins' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
            ];

            return view('users.index', compact(
                'users', 
                'departments', 
                'roles', 
                'stats'
            ));

        } catch (\Exception $e) {
            \Log::error('Error in UserController@index: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load users: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        try {
            // Load relationships
            $user->load([
                'roles',
                'notificationGroups' => function($query) {
                    $query->where('is_active', true)
                          ->withPivot('joined_at', 'added_by');
                },
                'preferences',
                'createdNotifications' => function($query) {
                    $query->latest()->take(5);
                }
            ]);

            // Get user statistics
            $stats = $user->getNotificationStats();

            // Get recent activity
            $recentActivity = activity()
                ->causedBy($user)
                ->latest()
                ->take(10)
                ->get();

            // Get notification groups user can join
            $availableGroups = NotificationGroup::active()
                ->where('type', 'manual')
                ->whereDoesntHave('users', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('name')
                ->get();

            return view('users.show', compact(
                'user',
                'stats',
                'recentActivity',
                'availableGroups'
            ));

        } catch (\Exception $e) {
            \Log::error('Error in UserController@show: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load user details: ' . $e->getMessage());
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user)
    {
        try {
            $user->update(['is_active' => !$user->is_active]);

            $status = $user->is_active ? 'activated' : 'deactivated';
            
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['old_status' => !$user->is_active, 'new_status' => $user->is_active])
                ->log("User {$status}");

            return back()->with('success', "User {$user->display_name} has been {$status}.");

        } catch (\Exception $e) {
            \Log::error('Error toggling user status: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to update user status.');
        }
    }

    /**
     * Join user to notification group
     */
    public function joinGroup(Request $request, User $user)
    {
        $request->validate([
            'group_id' => 'required|exists:notification_groups,id'
        ]);

        try {
            $group = NotificationGroup::findOrFail($request->group_id);

            if ($group->hasMember($user->id)) {
                return back()->with('warning', 'User is already a member of this group.');
            }

            $group->addUser($user->id, auth()->id());

            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['group_name' => $group->name])
                ->log('User added to notification group');

            return back()->with('success', "User added to {$group->name} group successfully.");

        } catch (\Exception $e) {
            \Log::error('Error joining user to group: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to add user to group.');
        }
    }

    /**
     * Remove user from notification group
     */
    public function leaveGroup(Request $request, User $user)
    {
        $request->validate([
            'group_id' => 'required|exists:notification_groups,id'
        ]);

        try {
            $group = NotificationGroup::findOrFail($request->group_id);

            if (!$group->hasMember($user->id)) {
                return back()->with('warning', 'User is not a member of this group.');
            }

            $group->removeUser($user->id);

            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['group_name' => $group->name])
                ->log('User removed from notification group');

            return back()->with('success', "User removed from {$group->name} group successfully.");

        } catch (\Exception $e) {
            \Log::error('Error removing user from group: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to remove user from group.');
        }
    }

    /**
     * Update user roles
     */
    public function updateRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'array',
            'roles.*' => 'exists:roles,name'
        ]);

        try {
            $oldRoles = $user->roles->pluck('name')->toArray();
            $newRoles = $request->roles ?? [];

            // Sync roles
            $user->syncRoles($newRoles);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_roles' => $oldRoles,
                    'new_roles' => $newRoles
                ])
                ->log('User roles updated');

            return back()->with('success', 'User roles updated successfully.');

        } catch (\Exception $e) {
            \Log::error('Error updating user roles: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to update user roles.');
        }
    }

    /**
     * Update user permissions
     */
    public function updatePermissions(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        try {
            $oldPermissions = $user->permissions->pluck('name')->toArray();
            $newPermissions = $request->permissions ?? [];

            // Sync direct permissions
            $user->syncPermissions($newPermissions);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_permissions' => $oldPermissions,
                    'new_permissions' => $newPermissions
                ])
                ->log('User permissions updated');

            return back()->with('success', 'User permissions updated successfully.');

        } catch (\Exception $e) {
            \Log::error('Error updating user permissions: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to update user permissions.');
        }
    }

    /**
     * Export users to CSV
     */
    public function export(Request $request)
    {
        try {
            $query = User::with('roles');

            // Apply same filters as index
            if ($request->filled('search')) {
                $query->search($request->search);
            }
            if ($request->filled('department')) {
                $query->byDepartment($request->department);
            }
            if ($request->filled('role')) {
                $query->withRole($request->role);
            }
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            $users = $query->orderBy('display_name')->get();

            $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($users) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'ID', 'Username', 'Display Name', 'Email', 
                    'First Name', 'Last Name', 'Department', 'Title', 
                    'Status', 'Roles', 'Last Login', 'Created At'
                ]);

                // CSV data
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->id,
                        $user->username,
                        $user->display_name,
                        $user->email,
                        $user->first_name,
                        $user->last_name,
                        $user->department,
                        $user->title,
                        $user->is_active ? 'Active' : 'Inactive',
                        $user->roles->pluck('name')->join(', '),
                        $user->last_login_at ? $user->last_login_at->format('Y-m-d H:i:s') : '',
                        $user->created_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Error exporting users: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to export users.');
        }
    }
}