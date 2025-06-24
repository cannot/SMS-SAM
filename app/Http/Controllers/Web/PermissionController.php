<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions
     */
    public function index(Request $request)
    {
        try {
            $query = Permission::with(['roles']);

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                    //   ->orWhere('display_name', 'ILIKE', "%{$search}%");
                });
            }

            // Filter by category (prefix)
            if ($request->filled('category')) {
                $query->where('name', 'LIKE', $request->category . '-%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortDirection = $request->get('sort_direction', 'asc');

            if (in_array($sortBy, ['name', 'created_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            }

            $permissions = $query->paginate(30)->appends($request->query());

            // Group permissions by category for better display
            $groupedPermissions = $permissions->getCollection()->groupBy(function($permission) {
                return explode('-', $permission->name)[0];
            });

            // Get categories for filter
            $categories = Permission::select(DB::raw("split_part(name, '-', 1) as category"))
                ->distinct()
                ->orderBy('category')
                ->pluck('category')
                ->filter();

            // Statistics
            $stats = [
                'total_permissions' => Permission::count(),
                'assigned_permissions' => Permission::has('roles')->count(),
                'unassigned_permissions' => Permission::doesntHave('roles')->count(),
                'categories_count' => $categories->count(),
            ];

            return view('permissions.index', compact(
                'permissions', 
                'groupedPermissions', 
                'categories', 
                'stats'
            ));

        } catch (\Exception $e) {
            \Log::error('Error in PermissionController@index: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load permissions: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new permission
     */
    public function create()
    {
        // Get existing categories
        $categories = Permission::select(DB::raw("split_part(name, '-', 1) as category"))
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter();

        return view('permissions.create', compact('categories'));
    }

    public function matrix()
    {
        return view('permissions.matrix');
    }

    /**
     * Store a newly created permission
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:50'
        ]);

        try {
            // Create permission name with category prefix if provided
            $name = $request->name;
            if ($request->category && !str_starts_with($name, $request->category . '-')) {
                $name = $request->category . '-' . $name;
            }

            $permission = Permission::create([
                'name' => $name,
                'display_name' => $request->display_name,
                'description' => $request->description,
                'guard_name' => 'web'
            ]);

            // Log activity
            activity()
                ->performedOn($permission)
                ->causedBy(auth()->user())
                ->log('Permission created');

            return redirect()->route('permissions.index')
                           ->with('success', 'Permission created successfully.');

        } catch (\Exception $e) {
            \Log::error('Error creating permission: ' . $e->getMessage());
            
            return back()->withInput()
                        ->with('error', 'Failed to create permission.');
        }
    }

    /**
     * Display the specified permission
     */
    public function show(Permission $permission)
    {
        try {
            $permission->load(['roles' => function($query) {
                $query->orderBy('display_name');
            }]);

            // Get users who have this permission (through roles)
            $usersWithPermission = \App\Models\User::permission($permission->name)
                ->with('roles')
                ->orderBy('display_name')
                ->take(50) // Limit for performance
                ->get();

            // Statistics
            $stats = [
                'roles_count' => $permission->roles()->count(),
                'users_count' => \App\Models\User::permission($permission->name)->count(),
                'category' => explode('-', $permission->name)[0] ?? 'uncategorized'
            ];

            // Recent activity
            $recentActivity = activity()
                ->performedOn($permission)
                ->latest()
                ->take(10)
                ->get();

            return view('permissions.show', compact(
                'permission', 
                'usersWithPermission', 
                'stats', 
                'recentActivity'
            ));

        } catch (\Exception $e) {
            \Log::error('Error in PermissionController@show: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load permission details.');
        }
    }

    /**
     * Show the form for editing the specified permission
     */
    public function edit(Permission $permission)
    {
        // Get existing categories
        $categories = Permission::select(DB::raw("split_part(name, '-', 1) as category"))
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter();

        return view('permissions.edit', compact('permission', 'categories'));
    }

    /**
     * Update the specified permission
     */
    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500'
        ]);

        try {
            $permission->update([
                'display_name' => $request->display_name,
                'description' => $request->description,
            ]);

            // Log activity
            activity()
                ->performedOn($permission)
                ->causedBy(auth()->user())
                ->log('Permission updated');

            return redirect()->route('permissions.show', $permission)
                           ->with('success', 'Permission updated successfully.');

        } catch (\Exception $e) {
            \Log::error('Error updating permission: ' . $e->getMessage());
            
            return back()->withInput()
                        ->with('error', 'Failed to update permission.');
        }
    }

    /**
     * Remove the specified permission
     */
    public function destroy(Permission $permission)
    {
        try {
            // Check if permission is assigned to any roles
            if ($permission->roles()->count() > 0) {
                return back()->with('error', 'Cannot delete permission that is assigned to roles.');
            }

            // Check if it's a system permission
            $systemPermissions = [
                'view-dashboard', 'system-settings', 'system-maintenance'
            ];
            if (in_array($permission->name, $systemPermissions)) {
                return back()->with('error', 'Cannot delete system permissions.');
            }

            // Log activity before deletion
            activity()
                ->performedOn($permission)
                ->causedBy(auth()->user())
                ->withProperties([
                    'permission_name' => $permission->name
                ])
                ->log('Permission deleted');

            $permission->delete();

            return redirect()->route('permissions.index')
                           ->with('success', 'Permission deleted successfully.');

        } catch (\Exception $e) {
            \Log::error('Error deleting permission: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to delete permission.');
        }
    }

    /**
     * Assign permission to roles in bulk
     */
    public function bulkAssignToRoles(Request $request, Permission $permission)
    {
        $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        try {
            $roles = Role::whereIn('id', $request->role_ids)->get();
            $assigned = 0;

            foreach ($roles as $role) {
                if (!$role->hasPermissionTo($permission->name)) {
                    $role->givePermissionTo($permission->name);
                    $assigned++;
                }
            }

            // Log activity
            activity()
                ->performedOn($permission)
                ->causedBy(auth()->user())
                ->withProperties([
                    'roles_assigned' => $assigned,
                    'total_selected' => count($request->role_ids)
                ])
                ->log('Permission bulk assigned to roles');

            return back()->with('success', "Permission assigned to {$assigned} roles successfully.");

        } catch (\Exception $e) {
            \Log::error('Error in bulk permission assignment: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to assign permission to roles.');
        }
    }

    /**
     * Create multiple permissions at once
     */
    public function bulkCreate(Request $request)
    {
        $request->validate([
            'permissions' => 'required|array|min:1',
            'permissions.*.name' => 'required|string|max:255|unique:permissions,name',
            'permissions.*.display_name' => 'required|string|max:255',
            'permissions.*.description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:50'
        ]);

        try {
            DB::beginTransaction();

            $created = 0;
            foreach ($request->permissions as $permissionData) {
                // Add category prefix if provided
                $name = $permissionData['name'];
                if ($request->category && !str_starts_with($name, $request->category . '-')) {
                    $name = $request->category . '-' . $name;
                }

                Permission::create([
                    'name' => $name,
                    'display_name' => $permissionData['display_name'],
                    'description' => $permissionData['description'] ?? null,
                    'guard_name' => 'web'
                ]);

                $created++;
            }

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'permissions_created' => $created,
                    'category' => $request->category
                ])
                ->log('Bulk permissions created');

            DB::commit();

            return redirect()->route('permissions.index')
                           ->with('success', "{$created} permissions created successfully.");

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error in bulk permission creation: ' . $e->getMessage());
            
            return back()->withInput()
                        ->with('error', 'Failed to create permissions.');
        }
    }

    /**
     * Export permissions to CSV
     */
    public function export(Request $request)
    {
        try {
            $query = Permission::with(['roles']);

            // Apply same filters as index
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                      ->orWhere('display_name', 'ILIKE', "%{$search}%");
                });
            }

            if ($request->filled('category')) {
                $query->where('name', 'LIKE', $request->category . '-%');
            }

            $permissions = $query->orderBy('display_name')->get();

            $filename = 'permissions_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($permissions) {
                $file = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($file, [
                    'Name', 'Display Name', 'Description', 'Category',
                    'Roles Count', 'Roles', 'Created At'
                ]);

                // CSV data
                foreach ($permissions as $permission) {
                    $category = explode('-', $permission->name)[0] ?? '';
                    
                    fputcsv($file, [
                        $permission->name,
                        $permission->display_name,
                        $permission->description,
                        $category,
                        $permission->roles()->count(),
                        $permission->roles->pluck('name')->join(', '),
                        $permission->created_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Error exporting permissions: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to export permissions.');
        }
    }

        /**
     * Bulk remove permission from roles
     */
    public function bulkRemoveFromRoles(Request $request, Permission $permission)
    {
        $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        try {
            $roles = Role::whereIn('id', $request->role_ids)->get();
            $removed = 0;

            foreach ($roles as $role) {
                if ($role->hasPermissionTo($permission->name)) {
                    $role->revokePermissionTo($permission->name);
                    $removed++;
                }
            }

            // Log activity
            activity()
                ->performedOn($permission)
                ->causedBy(auth()->user())
                ->withProperties([
                    'roles_removed' => $removed,
                    'total_selected' => count($request->role_ids)
                ])
                ->log('Permission bulk removed from roles');

            return back()->with('success', "Permission removed from {$removed} roles successfully.");

        } catch (\Exception $e) {
            \Log::error('Error in bulk permission removal: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to remove permission from roles.');
        }
    }

    /**
     * Bulk assign multiple permissions to roles
     */
    public function bulkAssignMultipleToRoles(Request $request)
    {
        $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        try {
            DB::beginTransaction();

            $permissions = Permission::whereIn('id', $request->permission_ids)->get();
            $roles = Role::whereIn('id', $request->role_ids)->get();
            $assigned = 0;

            foreach ($roles as $role) {
                foreach ($permissions as $permission) {
                    if (!$role->hasPermissionTo($permission->name)) {
                        $role->givePermissionTo($permission->name);
                        $assigned++;
                    }
                }
            }

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'permissions_count' => $permissions->count(),
                    'roles_count' => $roles->count(),
                    'assignments_made' => $assigned
                ])
                ->log('Bulk permissions assigned to roles');

            DB::commit();

            return back()->with('success', "Made {$assigned} permission assignments successfully.");

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error in bulk permission assignment: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to assign permissions to roles.');
        }
    }

    /**
     * Bulk delete multiple permissions
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id'
        ]);

        try {
            DB::beginTransaction();

            $permissions = Permission::whereIn('id', $request->permission_ids)->get();
            $deleted = 0;
            $skipped = 0;

            // System permissions that cannot be deleted
            $systemPermissions = [
                'view-dashboard', 'system-settings', 'system-maintenance'
            ];

            foreach ($permissions as $permission) {
                // Check if it's a system permission
                if (in_array($permission->name, $systemPermissions)) {
                    $skipped++;
                    continue;
                }

                // Check if permission is assigned to any roles
                if ($permission->roles()->count() > 0) {
                    $skipped++;
                    continue;
                }

                // Log activity before deletion
                activity()
                    ->performedOn($permission)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'permission_name' => $permission->name
                    ])
                    ->log('Permission deleted via bulk operation');

                $permission->delete();
                $deleted++;
            }

            DB::commit();

            $message = "Deleted {$deleted} permissions successfully.";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} permissions (system permissions or assigned to roles).";
            }

            return redirect()->route('permissions.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error in bulk permission deletion: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to delete permissions.');
        }
    }

    /**
     * Duplicate a permission
     */
    public function duplicate(Permission $permission)
    {
        try {
            // Generate new name
            $baseName = $permission->name;
            $counter = 1;
            $newName = $baseName . '-copy';
            
            while (Permission::where('name', $newName)->exists()) {
                $counter++;
                $newName = $baseName . '-copy-' . $counter;
            }

            // Create duplicate
            $duplicate = Permission::create([
                'name' => $newName,
                'display_name' => $permission->display_name . ' (Copy)',
                'description' => $permission->description,
                'guard_name' => $permission->guard_name
            ]);

            // Optionally copy role assignments
            if (request('copy_roles') === 'true') {
                foreach ($permission->roles as $role) {
                    $role->givePermissionTo($duplicate->name);
                }
            }

            // Log activity
            activity()
                ->performedOn($duplicate)
                ->causedBy(auth()->user())
                ->withProperties([
                    'original_permission' => $permission->name
                ])
                ->log('Permission duplicated');

            return redirect()->route('permissions.show', $duplicate)
                        ->with('success', 'Permission duplicated successfully.');

        } catch (\Exception $e) {
            \Log::error('Error duplicating permission: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to duplicate permission.');
        }
    }

    /**
     * Get permission statistics for dashboard
     */
    public function getStats()
    {
        try {
            $stats = [
                'total_permissions' => Permission::count(),
                'assigned_permissions' => Permission::has('roles')->count(),
                'unassigned_permissions' => Permission::doesntHave('roles')->count(),
                'categories' => Permission::select(DB::raw("split_part(name, '-', 1) as category"))
                    ->distinct()
                    ->orderBy('category')
                    ->pluck('category')
                    ->filter()
                    ->count(),
                'recent_activity' => activity()
                    ->whereIn('subject_type', ['App\Models\Permission', 'Spatie\Permission\Models\Permission'])
                    ->latest()
                    ->take(5)
                    ->get(),
                'permissions_by_category' => Permission::select(
                        DB::raw("split_part(name, '-', 1) as category"),
                        DB::raw("count(*) as count")
                    )
                    ->groupBy('category')
                    ->orderByDesc('count')
                    ->get(),
                'most_assigned_permissions' => Permission::withCount('roles')
                    ->orderByDesc('roles_count')
                    ->take(10)
                    ->get(),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            \Log::error('Error getting permission stats: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load statistics'], 500);
        }
    }

    /**
     * Search permissions with advanced filters
     */
    public function search(Request $request)
    {
        try {
            $query = Permission::with(['roles']);

            // Text search
            if ($request->filled('q')) {
                $search = $request->q;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('display_name', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
                });
            }

            // Category filter
            if ($request->filled('category')) {
                $query->where('name', 'LIKE', $request->category . '-%');
            }

            // Role filter
            if ($request->filled('role')) {
                $query->whereHas('roles', function($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            // Assignment status filter
            if ($request->filled('assignment_status')) {
                switch ($request->assignment_status) {
                    case 'assigned':
                        $query->has('roles');
                        break;
                    case 'unassigned':
                        $query->doesntHave('roles');
                        break;
                }
            }

            // Date range filter
            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortDirection = $request->get('sort_direction', 'asc');

            if (in_array($sortBy, ['name', 'display_name', 'created_at'])) {
                $query->orderBy($sortBy, $sortDirection);
            } elseif ($sortBy === 'roles_count') {
                $query->withCount('roles')->orderBy('roles_count', $sortDirection);
            }

            $permissions = $query->paginate($request->get('per_page', 30))
                            ->appends($request->query());

            if ($request->expectsJson()) {
                return response()->json([
                    'data' => $permissions->items(),
                    'pagination' => [
                        'current_page' => $permissions->currentPage(),
                        'last_page' => $permissions->lastPage(),
                        'per_page' => $permissions->perPage(),
                        'total' => $permissions->total(),
                    ]
                ]);
            }

            return view('permissions.search-results', compact('permissions'));

        } catch (\Exception $e) {
            \Log::error('Error searching permissions: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Search failed'], 500);
            }
            
            return back()->with('error', 'Search failed. Please try again.');
        }
    }

    /**
     * Compare permissions between roles
     */
    public function compareRoles(Request $request)
    {
        $request->validate([
            'role_ids' => 'required|array|min:2|max:5',
            'role_ids.*' => 'exists:roles,id'
        ]);

        try {
            $roles = Role::with('permissions')->whereIn('id', $request->role_ids)->get();
            
            // Get all permissions
            $allPermissions = Permission::orderBy('name')->get();
            
            // Group permissions by category
            $permissionsByCategory = $allPermissions->groupBy(function($permission) {
                return explode('-', $permission->name)[0] ?? 'general';
            });

            // Build comparison matrix
            $comparison = [];
            foreach ($permissionsByCategory as $category => $permissions) {
                $comparison[$category] = [];
                foreach ($permissions as $permission) {
                    $comparison[$category][$permission->id] = [
                        'permission' => $permission,
                        'roles' => []
                    ];
                    
                    foreach ($roles as $role) {
                        $comparison[$category][$permission->id]['roles'][$role->id] = 
                            $role->permissions->contains('id', $permission->id);
                    }
                }
            }

            return view('permissions.role-comparison', compact('roles', 'comparison'));

        } catch (\Exception $e) {
            \Log::error('Error comparing roles: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to compare roles.');
        }
    }

    /**
     * Import permissions from JSON file
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:json|max:2048'
        ]);

        try {
            $file = $request->file('import_file');
            $content = file_get_contents($file->getRealPath());
            $data = json_decode($content, true);

            if (!$data || !isset($data['permissions'])) {
                return back()->with('error', 'Invalid import file format.');
            }

            DB::beginTransaction();

            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($data['permissions'] as $permissionData) {
                try {
                    // Validate required fields
                    if (!isset($permissionData['name']) || !isset($permissionData['display_name'])) {
                        $errors[] = "Missing required fields for permission";
                        continue;
                    }

                    // Check if permission already exists
                    if (Permission::where('name', $permissionData['name'])->exists()) {
                        $skipped++;
                        continue;
                    }

                    // Create permission
                    $permission = Permission::create([
                        'name' => $permissionData['name'],
                        'display_name' => $permissionData['display_name'],
                        'description' => $permissionData['description'] ?? null,
                        'guard_name' => $permissionData['guard_name'] ?? 'web'
                    ]);

                    // Assign to roles if specified
                    if (isset($permissionData['roles']) && is_array($permissionData['roles'])) {
                        foreach ($permissionData['roles'] as $roleName) {
                            $role = Role::where('name', $roleName)->first();
                            if ($role) {
                                $role->givePermissionTo($permission->name);
                            }
                        }
                    }

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Error importing {$permissionData['name']}: " . $e->getMessage();
                }
            }

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => count($errors)
                ])
                ->log('Permissions imported from file');

            DB::commit();

            $message = "Imported {$imported} permissions successfully.";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} existing permissions.";
            }
            if (count($errors) > 0) {
                $message .= " Encountered " . count($errors) . " errors.";
            }

            return redirect()->route('permissions.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error importing permissions: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to import permissions.');
        }
    }
}