<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\NotificationGroup;
use App\Services\LdapService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;

class UserController extends Controller
{
    protected $ldapService;

    public function __construct(LdapService $ldapService = null)
    {
        $this->ldapService = $ldapService;
    }

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

            // Get roles using Eloquent Model
            $roles = Role::orderBy('name')->get();

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
     * Show the form for creating a new user
     */
    public function create()
    {
        try {
            $roles = Role::where('name', '!=', 'super-admin')->orderBy('name')->get();
            $departments = User::active()
                ->whereNotNull('department')
                ->where('department', '!=', '')
                ->distinct()
                ->pluck('department')
                ->sort();

            return view('users.create', compact('roles', 'departments'));

        } catch (\Exception $e) {
            \Log::error('Error in UserController@create: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load create user form.');
        }
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
            'is_active' => 'boolean',
            'send_welcome_email' => 'boolean',
            'must_change_password' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'display_name' => $request->display_name ?: trim($request->first_name . ' ' . $request->last_name),
                'department' => $request->department,
                'title' => $request->title,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'auth_source' => 'manual',
                'is_active' => $request->boolean('is_active', true),
                'must_change_password' => $request->boolean('must_change_password', true),
                'temp_password_expires_at' => $request->boolean('must_change_password', true) ? now()->addDays(1) : null,
            ]);

            // Assign roles
            if ($request->filled('roles')) {
                $user->assignRole($request->roles);
            } else {
                // Default role
                $user->assignRole('user');
            }

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties([
                    'auth_source' => 'manual',
                    'roles' => $request->roles ?? ['user'],
                    'send_welcome_email' => $request->boolean('send_welcome_email')
                ])
                ->log('User created manually');

            // Send welcome email if requested
            if ($request->boolean('send_welcome_email')) {
                try {
                    // TODO: Implement welcome email
                    // Mail::to($user->email)->send(new WelcomeUserMail($user, $request->password));
                    
                    $user->update(['welcome_email_sent_at' => now()]);
                } catch (\Exception $e) {
                    Log::warning('Failed to send welcome email', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', "User {$user->display_name} created successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating user: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create user: ' . $e->getMessage());
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
            $recentActivity = Activity::where('causer_id', $user->id)
                ->where('causer_type', get_class($user))
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
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        try {
            $roles = Role::orderBy('name')->get();
            $departments = User::active()
                ->whereNotNull('department')
                ->where('department', '!=', '')
                ->distinct()
                ->pluck('department')
                ->sort();

            return view('users.edit', compact('user', 'roles', 'departments'));

        } catch (\Exception $e) {
            \Log::error('Error in UserController@edit: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load edit user form.');
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        // Don't allow editing LDAP users' core data
        if ($user->auth_source === 'ldap') {
            $request->validate([
                'phone' => 'nullable|string|max:20',
                'is_active' => 'boolean',
            ]);
        } else {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'display_name' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
                'title' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'is_active' => 'boolean',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            ]);
        }

        try {
            $updateData = [];

            if ($user->auth_source === 'ldap') {
                // Only allow limited updates for LDAP users
                $updateData = [
                    'phone' => $request->phone,
                    'is_active' => $request->boolean('is_active', $user->is_active),
                ];
            } else {
                // Full updates for manual users
                $updateData = [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'display_name' => $request->display_name ?: trim($request->first_name . ' ' . $request->last_name),
                    'department' => $request->department,
                    'title' => $request->title,
                    'phone' => $request->phone,
                    'email' => $request->email,
                    'is_active' => $request->boolean('is_active', $user->is_active),
                ];
            }

            $user->update(array_filter($updateData));

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['updated_fields' => array_keys($updateData)])
                ->log('User information updated');

            return redirect()->route('users.show', $user)
                ->with('success', 'User information updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user from storage
     */
    public function destroy(User $user)
    {
        try {
            // Prevent deleting yourself
            if ($user->id === auth()->id()) {
                return back()->with('error', 'You cannot delete your own account.');
            }

            // Prevent deleting super admin
            if ($user->hasRole('super-admin')) {
                return back()->with('error', 'Cannot delete super admin user.');
            }

            $user->delete();

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log('User soft deleted');

            return redirect()->route('users.index')
                ->with('success', "User {$user->display_name} has been deleted.");

        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to delete user.');
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
     * Handle bulk actions on users
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,reset_preferences',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id'
        ]);

        try {
            $users = User::whereIn('id', $request->user_ids)->get();
            $successCount = 0;

            foreach ($users as $user) {
                switch ($request->action) {
                    case 'activate':
                        if (!$user->is_active) {
                            $user->update(['is_active' => true]);
                            $successCount++;
                        }
                        break;
                        
                    case 'deactivate':
                        if ($user->is_active && $user->id !== auth()->id()) {
                            $user->update(['is_active' => false]);
                            $successCount++;
                        }
                        break;
                        
                    case 'reset_preferences':
                        if ($user->preferences) {
                            $user->preferences->delete();
                            $successCount++;
                        }
                        break;
                }
            }

            // Log bulk activity
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => $request->action,
                    'user_count' => count($request->user_ids),
                    'success_count' => $successCount
                ])
                ->log("Bulk action '{$request->action}' performed on users");

            $actionNames = [
                'activate' => 'activated',
                'deactivate' => 'deactivated', 
                'reset_preferences' => 'preferences reset for'
            ];

            return back()->with('success', 
                "Successfully {$actionNames[$request->action]} {$successCount} user(s).");

        } catch (\Exception $e) {
            Log::error('Error in bulk action: ' . $e->getMessage());
            
            return back()->with('error', 'Bulk action failed: ' . $e->getMessage());
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
     * Update user roles - Enhanced with debugging and validation
     */
    public function updateRoles(Request $request, User $user)
    {
        // Log the incoming request for debugging
        Log::info('updateRoles called', [
            'user_id' => $user->id,
            'request_data' => $request->all(),
            'authenticated_user' => auth()->id(),
            'method' => $request->method(),
            'path' => $request->path()
        ]);

        // Check authorization first
        if (!auth()->user()->can('manage-user-roles')) {
            Log::warning('Unauthorized access to updateRoles', [
                'user_id' => auth()->id(),
                'target_user' => $user->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to manage user roles'
            ], 403);
        }

        // Enhanced validation with detailed error messages
        try {
            $validatedData = $request->validate([
                'roles' => 'nullable|array',
                'roles.*' => [
                    'exists:roles,id',
                    function ($attribute, $value, $fail) {
                        $role = Role::find($value);
                        if (!$role) {
                            $fail("Role with ID {$value} does not exist.");
                            return;
                        }
                        
                        // Prevent non-super-admins from assigning super-admin role
                        if ($role->name === 'super-admin' && !auth()->user()->hasRole('super-admin')) {
                            $fail('Only super administrators can assign the super-admin role.');
                        }
                    }
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed in updateRoles', [
                'errors' => $e->errors(),
                'user_id' => $user->id
            ]);
            
            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Validation failed: ' . implode(', ', array_flatten($e->errors())));
        }

        try {
            DB::beginTransaction();

            // Get current roles for comparison
            $oldRoles = $user->roles->pluck('name')->toArray();
            $oldRoleIds = $user->roles->pluck('id')->toArray();
            
            // Get new role IDs (convert to integers for consistency)
            $newRoleIds = array_map('intval', $request->input('roles', []));
            
            // Prevent removing super-admin from super-admin user by non-super-admin
            if (in_array('super-admin', $oldRoles) && !auth()->user()->hasRole('super-admin')) {
                $superAdminRole = Role::where('name', 'super-admin')->first();
                if ($superAdminRole && !in_array($superAdminRole->id, $newRoleIds)) {
                    throw new \Exception('Cannot remove super-admin role without super-admin privileges.');
                }
            }

            // Get role objects for the new roles
            $newRoles = Role::whereIn('id', $newRoleIds)->get();
            $newRoleNames = $newRoles->pluck('name')->toArray();

            // Sync roles - this will add new roles and remove ones not in the array
            $user->syncRoles($newRoleIds);

            // Verify the sync worked
            $user->refresh();
            $currentRoleIds = $user->roles->pluck('id')->toArray();
            
            Log::info('Role sync completed', [
                'user_id' => $user->id,
                'old_role_ids' => $oldRoleIds,
                'new_role_ids' => $newRoleIds,
                'current_role_ids' => $currentRoleIds
            ]);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_roles' => $oldRoles,
                    'new_roles' => $newRoleNames,
                    'old_role_ids' => $oldRoleIds,
                    'new_role_ids' => $newRoleIds
                ])
                ->log('User roles updated');

            DB::commit();

            // Return appropriate response based on request type
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User roles updated successfully.',
                    'data' => [
                        'user_id' => $user->id,
                        'roles' => $user->fresh()->roles->map(function($role) {
                            return [
                                'id' => $role->id,
                                'name' => $role->name,
                                'display_name' => $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name))
                            ];
                        })
                    ]
                ]);
            }

            return redirect()->back()->with('success', 'User roles updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating user roles', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update user roles: ' . $e->getMessage()
                ], 500);
            }
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update user roles: ' . $e->getMessage());
        }
    }

    /**
     * Alternative method using role names instead of IDs for better debugging
     */
    public function updateRolesByName(Request $request, User $user)
    {
        $request->validate([
            'role_names' => 'nullable|array',
            'role_names.*' => 'exists:roles,name'
        ]);

        try {
            $oldRoles = $user->roles->pluck('name')->toArray();
            $newRoles = $request->input('role_names', []);

            // Sync roles by name
            $user->syncRoles($newRoles);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_roles' => $oldRoles,
                    'new_roles' => $newRoles
                ])
                ->log('User roles updated by name');

            return back()->with('success', 'User roles updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating user roles by name: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to update user roles: ' . $e->getMessage());
        }
    }

    /**
     * Debug method to check role assignment status
     */
    public function debugRoles(User $user)
    {
        $debug = [
            'user' => [
                'id' => $user->id,
                'name' => $user->display_name,
                'email' => $user->email
            ],
            'current_roles' => $user->roles->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'permissions_count' => $role->permissions->count()
                ];
            }),
            'all_permissions' => $user->getAllPermissions()->pluck('name'),
            'direct_permissions' => $user->permissions->pluck('name'),
            'role_permissions' => $user->getPermissionsViaRoles()->pluck('name'),
            'available_roles' => Role::all()->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name
                ];
            })
        ];

        return response()->json($debug);
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

    /**
     * Import users from file
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,xlsx|max:2048',
            'has_header' => 'boolean',
            'send_welcome_email' => 'boolean',
            'default_password' => 'nullable|string|min:8'
        ]);

        try {
            $file = $request->file('import_file');
            $hasHeader = $request->boolean('has_header', true);
            $sendWelcome = $request->boolean('send_welcome_email', false);
            $defaultPassword = $request->default_password ?: Str::random(12);

            // TODO: Implement CSV/Excel import logic using Laravel Excel
            // This is a placeholder implementation
            
            $results = [
                'total_rows' => 0,
                'successful_imports' => 0,
                'failed_imports' => 0,
                'errors' => []
            ];

            // Log import activity
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'filename' => $file->getClientOriginalName(),
                    'results' => $results
                ])
                ->log('User import completed');

            return back()->with('success', 
                "Import completed. Success: {$results['successful_imports']}, Failed: {$results['failed_imports']}");

        } catch (\Exception $e) {
            Log::error('User import failed: ' . $e->getMessage());
            
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Sync users with LDAP - Enhanced with proper user creation and role assignment
     */
    public function syncLdap(Request $request)
    {
        try {
            // ตรวจสอบว่า LDAP เปิดใช้งานหรือไม่
            if (!config('ldap.enabled', false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'LDAP synchronization is disabled.'
                ], 400);
            }

            // Check if LDAP service is available
            if (!$this->ldapService) {
                return response()->json([
                    'success' => false,
                    'message' => 'LDAP service is not available.'
                ], 500);
            }

            // Check if sync is already running
            $isRunning = cache()->get('ldap_sync_running', false);
            if ($isRunning) {
                return response()->json([
                    'success' => false,
                    'message' => 'LDAP sync is already running.'
                ], 409);
            }

            // Set sync running flag
            cache()->put('ldap_sync_running', true, 1800); // 30 minutes timeout

            // Test LDAP connection first
            if (!$this->ldapService->testConnection()) {
                cache()->forget('ldap_sync_running');
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot connect to LDAP server.'
                ], 500);
            }

            // Start sync process
            $syncResults = $this->performLdapSync();

            // Clear sync running flag
            cache()->forget('ldap_sync_running');
            
            // Update last sync time
            cache()->put('ldap_last_sync', now()->toISOString(), 86400); // 24 hours

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->withProperties($syncResults)
                ->log('LDAP sync completed');

            return response()->json([
                'success' => true,
                'message' => "LDAP sync completed successfully. New: {$syncResults['new_users']}, Updated: {$syncResults['updated_users']}, Errors: {$syncResults['errors']}",
                'results' => $syncResults,
                'synced_at' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            cache()->forget('ldap_sync_running');
            Log::error('Error in LDAP sync: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'LDAP sync failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform the actual LDAP synchronization
     */
    private function performLdapSync()
    {
        $syncResults = [
            'total_users' => 0,
            'new_users' => 0,
            'updated_users' => 0,
            'errors' => 0,
            'error_details' => []
        ];

        try {
            // Get all LDAP users
            $ldapUsers = $this->ldapService->getAllUsers(); // You'll need to implement this method
            $syncResults['total_users'] = count($ldapUsers);

            // Get default user role
            $defaultRole = Role::where('name', 'user')->first();
            if (!$defaultRole) {
                Log::warning('Default "user" role not found. Creating it.');
                $defaultRole = Role::create([
                    'name' => 'user',
                    'display_name' => 'User',
                    'description' => 'Standard user role'
                ]);
            }

            foreach ($ldapUsers as $ldapUser) {
                try {
                    $userData = $this->extractUserDataFromLdap($ldapUser);
                    
                    if (empty($userData['username']) || empty($userData['email'])) {
                        $syncResults['errors']++;
                        $syncResults['error_details'][] = "Missing required data for LDAP user";
                        continue;
                    }

                    // Check if user exists by LDAP GUID or username
                    $existingUser = User::where('ldap_guid', $userData['ldap_guid'])
                                      ->orWhere('username', $userData['username'])
                                      ->first();

                    if ($existingUser) {
                        // Update existing user
                        $existingUser->update($userData);
                        $syncResults['updated_users']++;
                        
                        Log::info("Updated LDAP user: {$userData['username']}");
                    } else {
                        // Create new user
                        $newUser = User::create(array_merge($userData, [
                            'password' => Hash::make(Str::random(32)), // Random password, they'll use LDAP auth
                            'auth_source' => 'ldap',
                            'is_active' => true,
                            'email_verified_at' => now()
                        ]));

                        // Assign default user role
                        $newUser->assignRole($defaultRole);
                        
                        $syncResults['new_users']++;
                        
                        Log::info("Created new LDAP user: {$userData['username']} with default user role");
                    }

                } catch (\Exception $e) {
                    $syncResults['errors']++;
                    $syncResults['error_details'][] = "Error syncing user: " . $e->getMessage();
                    Log::error('Error syncing individual LDAP user', [
                        'user_data' => $userData ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('LDAP sync completed', $syncResults);
            return $syncResults;

        } catch (\Exception $e) {
            Log::error('Error in LDAP sync process: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract user data from LDAP user object
     */
    private function extractUserDataFromLdap($ldapUser)
    {
        try {
            return [
                'ldap_guid' => $ldapUser->getConvertedGuid(),
                'username' => $ldapUser->getFirstAttribute('samaccountname'),
                'email' => $ldapUser->getFirstAttribute('mail'),
                'first_name' => $ldapUser->getFirstAttribute('givenname') ?: '',
                'last_name' => $ldapUser->getFirstAttribute('sn') ?: '',
                'display_name' => $ldapUser->getFirstAttribute('displayname') ?: 
                                ($ldapUser->getFirstAttribute('givenname') . ' ' . $ldapUser->getFirstAttribute('sn')),
                'department' => $ldapUser->getFirstAttribute('department'),
                'title' => $ldapUser->getFirstAttribute('title'),
                'phone' => $ldapUser->getFirstAttribute('telephonenumber'),
                'is_active' => !$this->isLdapUserDisabled($ldapUser),
                'ldap_synced_at' => now(),
            ];
        } catch (\Exception $e) {
            Log::error('Error extracting LDAP user data', [
                'error' => $e->getMessage(),
                'ldap_user' => $ldapUser
            ]);
            throw $e;
        }
    }

    /**
     * Check if LDAP user account is disabled
     */
    private function isLdapUserDisabled($ldapUser)
    {
        try {
            $userAccountControl = $ldapUser->getFirstAttribute('useraccountcontrol');
            
            if (!$userAccountControl) {
                return false;
            }

            // Check if ACCOUNTDISABLE flag (0x0002) is set
            return (intval($userAccountControl) & 0x0002) !== 0;
        } catch (\Exception $e) {
            Log::warning('Error checking LDAP user status', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get LDAP sync status - Enhanced with better error handling
     */
    public function getLdapSyncStatus()
    {
        try {
            $isRunning = cache()->get('ldap_sync_running', false);
            $lastSync = cache()->get('ldap_last_sync');
            
            $status = [
                'is_running' => $isRunning,
                'last_sync' => $lastSync ? Carbon::parse($lastSync)->diffForHumans() : null,
                'last_sync_date' => $lastSync,
                'total_users' => User::count(),
                'ldap_users' => User::where('auth_source', 'ldap')->count(),
                'manual_users' => User::where('auth_source', 'manual')->count(),
                'ldap_enabled' => config('ldap.enabled', false),
                'ldap_connection' => false,
                'ldap_config' => [
                    'host' => config('ldap.default.hosts.0', 'Not configured'),
                    'base_dn' => config('ldap.default.base_dn', 'Not configured')
                ]
            ];

            // Test LDAP connection if enabled and service available
            if ($status['ldap_enabled'] && $this->ldapService) {
                try {
                    $status['ldap_connection'] = $this->ldapService->testConnection();
                } catch (\Exception $e) {
                    $status['ldap_connection'] = false;
                    $status['ldap_error'] = $e->getMessage();
                }
            }

            // Set appropriate message
            if ($isRunning) {
                $status['message'] = 'Sync is currently running...';
                $status['can_sync'] = false;
            } elseif (!$status['ldap_enabled']) {
                $status['message'] = 'LDAP is disabled in configuration';
                $status['can_sync'] = false;
            } elseif (!$this->ldapService) {
                $status['message'] = 'LDAP service is not available';
                $status['can_sync'] = false;
            } elseif (!$status['ldap_connection']) {
                $status['message'] = 'Cannot connect to LDAP server: ' . ($status['ldap_error'] ?? 'Connection failed');
                $status['can_sync'] = false;
            } else {
                $status['message'] = 'Ready to sync';
                $status['can_sync'] = true;
            }

            return response()->json($status);

        } catch (\Exception $e) {
            Log::error('Error getting LDAP sync status: ' . $e->getMessage());
            
            return response()->json([
                'error' => true,
                'message' => 'Failed to get sync status: ' . $e->getMessage(),
                'can_sync' => false
            ], 500);
        }
    }

    /**
     * Test LDAP connection
     */
    public function testLdapConnection()
    {
        try {
            if (!config('ldap.enabled', false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'LDAP is disabled in configuration'
                ], 400);
            }

            if (!$this->ldapService) {
                return response()->json([
                    'success' => false,
                    'message' => 'LDAP service is not available'
                ], 500);
            }

            $startTime = microtime(true);
            $connectionResult = $this->ldapService->testConnection();
            $responseTime = round((microtime(true) - $startTime) * 1000);

            if ($connectionResult) {
                // Try to get user count
                $userCount = 0;
                try {
                    $users = $this->ldapService->getAllUsers();
                    $userCount = count($users);
                } catch (\Exception $e) {
                    Log::warning('Could not count LDAP users during test: ' . $e->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'message' => 'LDAP connection successful',
                    'server' => config('ldap.default.hosts.0', 'Unknown'),
                    'user_count' => $userCount,
                    'response_time' => $responseTime
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'LDAP connection failed'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error testing LDAP connection: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get LDAP sync history
     */
    public function getSyncHistory()
    {
        try {
            // Get sync history from cache or database
            $history = cache()->get('ldap_sync_history', []);
            
            // If no cache, try to get from activity log
            if (empty($history)) {
                $activities = Activity::where('description', 'LDAP sync completed')
                    ->latest()
                    ->take(10)
                    ->get();
                    
                $history = $activities->map(function($activity) {
                    $properties = $activity->properties ?? [];
                    return [
                        'date' => $activity->created_at->toISOString(),
                        'status' => isset($properties['errors']) && $properties['errors'] > 0 ? 'warning' : 'success',
                        'new_users' => $properties['new_users'] ?? 0,
                        'updated_users' => $properties['updated_users'] ?? 0,
                        'errors' => $properties['errors'] ?? 0
                    ];
                })->toArray();
            }

            return response()->json([
                'success' => true,
                'history' => $history
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting sync history: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sync history'
            ], 500);
        }
    }

    /**
     * Get LDAP sync progress
     */
    public function getSyncProgress()
    {
        try {
            $isRunning = cache()->get('ldap_sync_running', false);
            $progress = cache()->get('ldap_sync_progress', null);

            return response()->json([
                'success' => true,
                'is_running' => $isRunning,
                'progress' => $progress
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting sync progress: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sync progress'
            ], 500);
        }
    }

    /**
     * Search users for AJAX requests
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        try {
            $query = $request->q;
            $limit = $request->integer('limit', 10);

            $users = User::where(function($q) use ($query) {
                    $q->where('display_name', 'ILIKE', "%{$query}%")
                      ->orWhere('username', 'ILIKE', "%{$query}%")
                      ->orWhere('email', 'ILIKE', "%{$query}%")
                      ->orWhere('first_name', 'ILIKE', "%{$query}%")
                      ->orWhere('last_name', 'ILIKE', "%{$query}%");
                })
                ->active()
                ->limit($limit)
                ->get(['id', 'username', 'display_name', 'email', 'department']);

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show user statistics page
     */
    public function stats()
    {
        try {
            $stats = $this->getStats();
            
            return view('users.stats', compact('stats'));

        } catch (\Exception $e) {
            Log::error('Error loading user statistics: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load user statistics.');
        }
    }

    /**
     * Generate user statistics
     */
    public function getStats()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'recent_logins' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
                'manual_users' => User::where('auth_source', 'manual')->count(),
                'ldap_users' => User::where('auth_source', 'ldap')->count(),
                'users_with_preferences' => User::whereHas('preferences')->count(),
                'users_in_groups' => User::whereHas('notificationGroups')->count(),
            ];

            // Department breakdown
            $stats['by_department'] = User::select('department', DB::raw('count(*) as count'))
                ->whereNotNull('department')
                ->where('department', '!=', '')
                ->groupBy('department')
                ->orderBy('count', 'desc')
                ->get();

            // Role breakdown
            $stats['by_role'] = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('roles.name', 'roles.display_name', DB::raw('count(*) as count'))
                ->where('model_type', User::class)
                ->groupBy('roles.id', 'roles.name', 'roles.display_name')
                ->orderBy('count', 'desc')
                ->get();

            // Activity stats
            $stats['recent_activity'] = [
                'logins_today' => User::whereDate('last_login_at', today())->count(),
                'logins_this_week' => User::where('last_login_at', '>=', now()->subWeek())->count(),
                'created_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
            ];

            return $stats;

        } catch (\Exception $e) {
            Log::error('Error generating user stats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user activity logs
     */
    public function activities(User $user)
    {
        try {
            $activities = Activity::where(function($query) use ($user) {
                    $query->where('causer_id', $user->id)
                          ->where('causer_type', get_class($user));
                })
                ->orWhere(function($query) use ($user) {
                    $query->where('subject_id', $user->id)
                          ->where('subject_type', get_class($user));
                })
                ->latest()
                ->paginate(20);

            return view('users.activities', compact('user', 'activities'));

        } catch (\Exception $e) {
            Log::error('Error getting user activities: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load user activities.');
        }
    }

    /**
     * Show deleted users
     */
    public function deleted()
    {
        try {
            $users = User::onlyTrashed()
                ->with('roles')
                ->orderBy('deleted_at', 'desc')
                ->paginate(20);

            return view('users.deleted', compact('users'));

        } catch (\Exception $e) {
            Log::error('Error loading deleted users: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load deleted users.');
        }
    }

    /**
     * Restore soft deleted user
     */
    public function restore($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log('User restored');

            return back()->with('success', "User {$user->display_name} has been restored.");

        } catch (\Exception $e) {
            Log::error('Error restoring user: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to restore user.');
        }
    }

    /**
     * Force delete user permanently
     */
    public function forceDestroy($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            
            // Prevent deleting yourself
            if ($user->id === auth()->id()) {
                return back()->with('error', 'You cannot permanently delete your own account.');
            }

            $displayName = $user->display_name;
            $user->forceDelete();

            // Log activity
            activity()
                ->causedBy(auth()->user())
                ->withProperties(['deleted_user' => $displayName])
                ->log('User permanently deleted');

            return back()->with('success', "User {$displayName} has been permanently deleted.");

        } catch (\Exception $e) {
            Log::error('Error force deleting user: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to permanently delete user.');
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
            'force_change' => 'boolean'
        ]);

        try {
            // Only allow password reset for manual users
            if ($user->auth_source === 'ldap') {
                return back()->with('error', 'Cannot reset password for LDAP users.');
            }

            $user->update([
                'password' => Hash::make($request->new_password),
                'must_change_password' => $request->boolean('force_change', true),
                'temp_password_expires_at' => $request->boolean('force_change', true) ? now()->addDays(1) : null,
            ]);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['force_change' => $request->boolean('force_change')])
                ->log('User password reset');

            return back()->with('success', 'Password reset successfully.');

        } catch (\Exception $e) {
            Log::error('Error resetting password: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to reset password.');
        }
    }

    /**
     * Unlock user account
     */
    public function unlock(User $user)
    {
        try {
            $user->update([
                'is_active' => true,
                'locked_until' => null,
                'failed_login_attempts' => 0
            ]);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log('User account unlocked');

            return back()->with('success', "User {$user->display_name} has been unlocked.");

        } catch (\Exception $e) {
            Log::error('Error unlocking user: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to unlock user.');
        }
    }

    /**
     * Send welcome email to user
     */
    public function sendWelcomeEmail(User $user)
    {
        try {
            // TODO: Implement welcome email
            // Mail::to($user->email)->send(new WelcomeUserMail($user));
            
            $user->update(['welcome_email_sent_at' => now()]);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log('Welcome email sent');

            return back()->with('success', 'Welcome email sent successfully.');

        } catch (\Exception $e) {
            Log::error('Error sending welcome email: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to send welcome email.');
        }
    }

    /**
     * Show user permissions page
     */
    public function permissions(User $user)
    {
        try {
            $user->load(['roles.permissions', 'permissions']);
            $allPermissions = Permission::orderBy('name')->get();
            $allRoles = Role::orderBy('name')->get();

            return view('users.permissions', compact('user', 'allPermissions', 'allRoles'));

        } catch (\Exception $e) {
            Log::error('Error loading user permissions: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load user permissions.');
        }
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        try {
            if ($user->hasRole($request->role)) {
                return back()->with('warning', 'User already has this role.');
            }

            $user->assignRole($request->role);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['role' => $request->role])
                ->log('Role assigned to user');

            return back()->with('success', 'Role assigned successfully.');

        } catch (\Exception $e) {
            Log::error('Error assigning role: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to assign role.');
        }
    }

    /**
     * Remove role from user
     */
    public function removeRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        try {
            if (!$user->hasRole($request->role)) {
                return back()->with('warning', 'User does not have this role.');
            }

            $user->removeRole($request->role);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['role' => $request->role])
                ->log('Role removed from user');

            return back()->with('success', 'Role removed successfully.');

        } catch (\Exception $e) {
            Log::error('Error removing role: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to remove role.');
        }
    }

    /**
     * Assign permission to user
     */
    public function assignPermission(Request $request, User $user)
    {
        $request->validate([
            'permission' => 'required|exists:permissions,name'
        ]);

        try {
            if ($user->hasPermissionTo($request->permission)) {
                return back()->with('warning', 'User already has this permission.');
            }

            $user->givePermissionTo($request->permission);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['permission' => $request->permission])
                ->log('Permission assigned to user');

            return back()->with('success', 'Permission assigned successfully.');

        } catch (\Exception $e) {
            Log::error('Error assigning permission: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to assign permission.');
        }
    }

    /**
     * Remove permission from user
     */
    public function removePermission(Request $request, User $user)
    {
        $request->validate([
            'permission' => 'required|exists:permissions,name'
        ]);

        try {
            if (!$user->hasDirectPermission($request->permission)) {
                return back()->with('warning', 'User does not have this direct permission.');
            }

            $user->revokePermissionTo($request->permission);

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties(['permission' => $request->permission])
                ->log('Permission removed from user');

            return back()->with('success', 'Permission removed successfully.');

        } catch (\Exception $e) {
            Log::error('Error removing permission: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to remove permission.');
        }
    }

    /**
     * Show manage roles page
     */
    public function manageRoles(User $user)
    {
        try {
            $allRoles = Role::orderBy('name')->get();
            $userRoles = $user->roles->pluck('id')->toArray();

            return view('users.manage-roles', compact('user', 'allRoles', 'userRoles'));

        } catch (\Exception $e) {
            Log::error('Error loading manage roles page: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load manage roles page.');
        }
    }

    /**
     * Show manage permissions page
     */
    public function managePermissions(User $user)
    {
        try {
            $allPermissions = Permission::orderBy('name')->get();
            $userPermissions = $user->permissions->pluck('id')->toArray();

            return view('users.manage-permissions', compact('user', 'allPermissions', 'userPermissions'));

        } catch (\Exception $e) {
            Log::error('Error loading manage permissions page: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load manage permissions page.');
        }
    }

    /**
     * Show manage groups page
     */
    public function manageGroups(User $user)
    {
        try {
            $allGroups = NotificationGroup::active()->orderBy('name')->get();
            $userGroups = $user->notificationGroups->pluck('id')->toArray();

            return view('users.manage-groups', compact('user', 'allGroups', 'userGroups'));

        } catch (\Exception $e) {
            Log::error('Error loading manage groups page: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to load manage groups page.');
        }
    }

    /**
     * Update user groups
     */
    public function updateGroups(Request $request, User $user)
    {
        $request->validate([
            'groups' => 'array',
            'groups.*' => 'exists:notification_groups,id'
        ]);

        try {
            $oldGroups = $user->notificationGroups->pluck('name')->toArray();
            $newGroupIds = $request->groups ?? [];
            
            // Sync groups
            $user->notificationGroups()->sync($newGroupIds);
            
            $newGroups = NotificationGroup::whereIn('id', $newGroupIds)->pluck('name')->toArray();

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->withProperties([
                    'old_groups' => $oldGroups,
                    'new_groups' => $newGroups
                ])
                ->log('User groups updated');

            return back()->with('success', 'User groups updated successfully.');

        } catch (\Exception $e) {
            Log::error('Error updating user groups: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to update user groups.');
        }
    }

    /**
     * AJAX methods for autocomplete and dynamic loading
     */
    public function ajaxSearch(Request $request)
    {
        return $this->search($request);
    }

    public function ajaxGetPermissions(User $user)
    {
        try {
            $permissions = $user->getAllPermissions()->map(function($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name ?? ucfirst(str_replace('_', ' ', $permission->name)),
                    'via_role' => $permission->pivot ? false : true
                ];
            });

            return response()->json(['permissions' => $permissions]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load permissions'], 500);
        }
    }

    public function ajaxGetRoles(User $user)
    {
        try {
            $roles = $user->roles->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)),
                    'permissions_count' => $role->permissions->count()
                ];
            });

            return response()->json(['roles' => $roles]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load roles'], 500);
        }
    }

    public function ajaxGetGroups(User $user)
    {
        try {
            $groups = $user->notificationGroups->map(function($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'type' => $group->type,
                    'members_count' => $group->users->count()
                ];
            });

            return response()->json(['groups' => $groups]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load groups'], 500);
        }
    }

    /**
     * Dashboard widget data
     */
    public function dashboardWidget()
    {
        try {
            $data = [
                'total_users' => User::count(),
                'active_users' => User::active()->count(),
                'new_users_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
                'recent_logins' => User::where('last_login_at', '>=', now()->subDay())->count(),
            ];

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load widget data'], 500);
        }
    }

    public function chartData(Request $request)
    {
        try {
            $type = $request->get('type', 'monthly');
            $data = [];

            switch ($type) {
                case 'daily':
                    // Last 7 days
                    for ($i = 6; $i >= 0; $i--) {
                        $date = now()->subDays($i);
                        $data[] = [
                            'date' => $date->format('M d'),
                            'users' => User::whereDate('created_at', $date)->count(),
                            'logins' => User::whereDate('last_login_at', $date)->count()
                        ];
                    }
                    break;

                case 'monthly':
                default:
                    // Last 12 months
                    for ($i = 11; $i >= 0; $i--) {
                        $date = now()->subMonths($i);
                        $data[] = [
                            'date' => $date->format('M Y'),
                            'users' => User::whereYear('created_at', $date->year)
                                          ->whereMonth('created_at', $date->month)
                                          ->count(),
                            'logins' => User::whereYear('last_login_at', $date->year)
                                           ->whereMonth('last_login_at', $date->month)
                                           ->count()
                        ];
                    }
                    break;
            }

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load chart data'], 500);
        }
    }
}