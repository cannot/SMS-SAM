<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of roles
     */
    public function index(Request $request)
    {
        try {
            // เพิ่มการนับจำนวนผู้ใช้และสิทธิ์สำหรับแต่ละ role
            $query = Role::withCount(['users', 'permissions']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('display_name', 'ILIKE', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'display_name');
            $sortDirection = $request->get('sort_direction', 'asc');

            if (in_array($sortBy, ['name', 'display_name', 'created_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            }

            $roles = $query->paginate(20)->appends($request->query());

            // Statistics
            $stats = [
                'total_roles' => Role::count(),
                'total_permissions' => Permission::count(),
                'roles_with_users' => Role::has('users')->count(),
                'unused_roles' => Role::doesntHave('users')->count(),
            ];

            return view('roles.index', compact('roles', 'stats'));

        } catch (\Exception $e) {
            \Log::error('Error in RoleController@index: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load roles: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get()->groupBy(function($permission) {
            return explode('-', $permission->name)[0]; // Group by prefix (e.g., 'view', 'create', etc.)
        });

        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        try {
            DB::beginTransaction();

            // Create role
            $role = Role::create([
                'name' => $request->name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'guard_name' => 'web'
            ]);

            // Assign permissions
            if ($request->permissions) {
                $role->syncPermissions($request->permissions);
            }

            // Log activity
            activity()
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->withProperties([
                    'permissions_count' => count($request->permissions ?? [])
                ])
                ->log('Role created');

            DB::commit();

            return redirect()->route('roles.index')
                           ->with('success', 'Role created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creating role: ' . $e->getMessage());
            
            return back()->withInput()
                        ->with('error', 'Failed to create role.');
        }
    }

    /**
     * Display the specified role
     */
    public function show(Role $role)
    {
        try {
            $role->load(['permissions', 'users' => function($query) {
                $query->where('is_active', true)
                      ->orderBy('display_name')
                      ->take(50); // Limit for performance
            }]);

            // Get role statistics
            $stats = [
                'total_users' => $role->users()->count(),
                'active_users' => $role->users()->where('is_active', true)->count(),
                'permissions_count' => $role->permissions()->count(),
                'recent_assignments' => $role->users()
                    ->wherePivot('created_at', '>=', now()->subDays(30))
                    ->count()
            ];

            // Get recent activity
            $recentActivity = activity()
                ->performedOn($role)
                ->latest()
                ->take(10)
                ->get();

            return view('roles.show', compact('role', 'stats', 'recentActivity'));

        } catch (\Exception $e) {
            \Log::error('Error in RoleController@show: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load role details.');
        }
    }

    /**
     * Show the form for editing the specified role
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        
        $permissions = Permission::orderBy('name')->get()->groupBy(function($permission) {
            return explode('-', $permission->name)[0];
        });

        return view('roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        try {
            DB::beginTransaction();

            $oldPermissions = $role->permissions->pluck('name')->toArray();

            // Update role
            $role->update([
                'display_name' => $request->display_name,
                'description' => $request->description,
            ]);

            // Update permissions
            $newPermissions = $request->permissions ?? [];
            $role->syncPermissions($newPermissions);

            // Log activity
            activity()
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_permissions_count' => count($oldPermissions),
                    'new_permissions_count' => count($newPermissions),
                    'added_permissions' => array_diff($newPermissions, $oldPermissions),
                    'removed_permissions' => array_diff($oldPermissions, $newPermissions)
                ])
                ->log('Role updated');

            DB::commit();

            return redirect()->route('roles.show', $role)
                           ->with('success', 'Role updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error updating role: ' . $e->getMessage());
            
            return back()->withInput()
                        ->with('error', 'Failed to update role.');
        }
    }

    /**
     * Remove the specified role
     */
    public function destroy(Role $role)
    {
        try {
            // Check if role has users
            if ($role->users()->count() > 0) {
                return back()->with('error', 'Cannot delete role that has users assigned to it.');
            }

            // Check if it's a system role
            $systemRoles = ['admin', 'super-admin'];
            if (in_array($role->name, $systemRoles)) {
                return back()->with('error', 'Cannot delete system roles.');
            }

            DB::beginTransaction();

            // Log activity before deletion
            activity()
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->withProperties([
                    'role_name' => $role->name,
                    'permissions_count' => $role->permissions()->count()
                ])
                ->log('Role deleted');

            $role->delete();

            DB::commit();

            return redirect()->route('roles.index')
                           ->with('success', 'Role deleted successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error deleting role: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to delete role.');
        }
    }

    /**
     * Clone a role
     */
    public function clone(Role $role)
    {
        try {
            DB::beginTransaction();

            $newRole = Role::create([
                'name' => $role->name . '_copy_' . time(),
                'display_name' => $role->display_name . ' (Copy)',
                'description' => $role->description,
                'guard_name' => 'web'
            ]);

            // Copy permissions
            $newRole->syncPermissions($role->permissions);

            // Log activity
            activity()
                ->performedOn($newRole)
                ->causedBy(auth()->user())
                ->withProperties([
                    'cloned_from' => $role->name,
                    'permissions_count' => $role->permissions()->count()
                ])
                ->log('Role cloned');

            DB::commit();

            return redirect()->route('roles.edit', $newRole)
                           ->with('success', 'Role cloned successfully. You can now edit the copy.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error cloning role: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to clone role.');
        }
    }

    /**
     * Assign role to users in bulk
     */
    public function bulkAssign(Request $request, Role $role)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        try {
            $users = \App\Models\User::whereIn('id', $request->user_ids)->get();
            $assigned = 0;

            foreach ($users as $user) {
                if (!$user->hasRole($role->name)) {
                    $user->assignRole($role->name);
                    $assigned++;
                }
            }

            // Log activity
            activity()
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->withProperties([
                    'users_assigned' => $assigned,
                    'total_selected' => count($request->user_ids)
                ])
                ->log('Role bulk assigned');

            return back()->with('success', "Role assigned to {$assigned} users successfully.");

        } catch (\Exception $e) {
            \Log::error('Error in bulk role assignment: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to assign role to users.');
        }
    }

    /**
     * Remove role from users in bulk
     */
    public function bulkRemove(Request $request, Role $role)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        try {
            $users = \App\Models\User::whereIn('id', $request->user_ids)->get();
            $removed = 0;

            foreach ($users as $user) {
                if ($user->hasRole($role->name)) {
                    $user->removeRole($role->name);
                    $removed++;
                }
            }

            // Log activity
            activity()
                ->performedOn($role)
                ->causedBy(auth()->user())
                ->withProperties([
                    'users_removed' => $removed,
                    'total_selected' => count($request->user_ids)
                ])
                ->log('Role bulk removed');

            return back()->with('success', "Role removed from {$removed} users successfully.");

        } catch (\Exception $e) {
            \Log::error('Error in bulk role removal: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to remove role from users.');
        }
    }

    /**
     * Export roles to CSV
     */
    public function export(Request $request)
    {
        try {
            $query = Role::with(['permissions', 'users']);

            // Apply same filters as index
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('display_name', 'ILIKE', "%{$search}%");
                });
            }

            $roles = $query->orderBy('display_name')->get();

            $filename = 'roles_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($roles) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'Name', 'Display Name', 'Description', 
                    'Users Count', 'Permissions Count', 'Permissions', 'Created At'
                ]);

                // CSV data
                foreach ($roles as $role) {
                    fputcsv($file, [
                        $role->name,
                        $role->display_name,
                        $role->description,
                        $role->users()->count(),
                        $role->permissions()->count(),
                        $role->permissions->pluck('name')->join(', '),
                        $role->created_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Error exporting roles: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to export roles.');
        }
    }
}