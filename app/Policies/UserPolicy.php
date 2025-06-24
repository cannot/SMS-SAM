<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin', 'manager', 'user-manager']) || 
               $user->hasPermissionTo('view-users');
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Admins and managers can view all users
        return $user->hasRole(['admin', 'super-admin', 'manager', 'user-manager']) || 
               $user->hasPermissionTo('view-users');
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin', 'user-manager']) || 
               $user->hasPermissionTo('create-users');
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own basic profile
        if ($user->id === $model->id) {
            return true;
        }

        // Prevent non-super-admin from updating super-admin
        if ($model->hasRole('super-admin') && !$user->hasRole('super-admin')) {
            return false;
        }

        // Admins can update users
        return $user->hasRole(['admin', 'super-admin', 'user-manager']) || 
               $user->hasPermissionTo('update-users');
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Prevent non-super-admin from deleting super-admin
        if ($model->hasRole('super-admin') && !$user->hasRole('super-admin')) {
            return false;
        }

        // Only admins can delete users
        return $user->hasRole(['admin', 'super-admin']) || 
               $user->hasPermissionTo('delete-users');
    }

    /**
     * Determine whether the user can view user preferences.
     */
    public function viewPreferences(User $user, User $model): bool
    {
        // Users can view their own preferences
        if ($user->id === $model->id) {
            return true;
        }

        // Admins and managers can view user preferences
        return $user->hasRole(['admin', 'super-admin', 'manager', 'user-manager']) || 
               $user->hasPermissionTo('manage-user-preferences');
    }

    /**
     * Determine whether the user can update user preferences.
     */
    public function updatePreferences(User $user, User $model): bool
    {
        // Users can update their own preferences
        if ($user->id === $model->id) {
            return true;
        }

        // Admins can update any user's preferences
        return $user->hasRole(['admin', 'super-admin', 'user-manager']) || 
               $user->hasPermissionTo('manage-user-preferences');
    }

    /**
     * Determine whether the user can manage roles for the user.
     */
    public function manageRoles(User $user, User $model): bool
    {
        // Users cannot manage their own roles
        if ($user->id === $model->id) {
            return false;
        }

        // Prevent non-super-admin from managing super-admin roles
        if ($model->hasRole('super-admin') && !$user->hasRole('super-admin')) {
            return false;
        }

        // Only admins can manage roles
        return $user->hasRole(['admin', 'super-admin']) || 
               $user->hasPermissionTo('manage-user-roles');
    }

    /**
     * Determine whether the user can manage notification groups for the user.
     */
    public function manageGroups(User $user, User $model): bool
    {
        // Users can manage their own groups (limited)
        if ($user->id === $model->id) {
            return true;
        }

        // Admins and managers can manage user groups
        return $user->hasRole(['admin', 'super-admin', 'manager', 'user-manager']) || 
               $user->hasPermissionTo('manage-user-groups');
    }

    /**
     * Determine whether the user can sync LDAP users.
     */
    public function syncLdap(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin', 'system-admin']) || 
               $user->hasPermissionTo('sync-ldap');
    }

    /**
     * Determine whether the user can export user data.
     */
    public function export(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin', 'manager', 'user-manager']) || 
               $user->hasPermissionTo('export-users');
    }

    /**
     * Determine whether the user can perform bulk actions.
     */
    public function bulkAction(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin', 'user-manager']) || 
               $user->hasPermissionTo('bulk-manage-users');
    }

    /**
     * Determine whether the user can send test notifications.
     */
    public function sendTestNotification(User $user, User $model): bool
    {
        // Users can send test notifications to themselves
        if ($user->id === $model->id) {
            return true;
        }

        // Admins and notification managers can send test notifications
        return $user->hasRole(['admin', 'super-admin', 'notification-manager']) || 
               $user->hasPermissionTo('send-test-notifications');
    }

    /**
     * Determine whether the user can toggle user status.
     */
    public function toggleStatus(User $user, User $model): bool
    {
        // Users cannot toggle their own status
        if ($user->id === $model->id) {
            return false;
        }

        // Prevent non-super-admin from toggling super-admin status
        if ($model->hasRole('super-admin') && !$user->hasRole('super-admin')) {
            return false;
        }

        // Only admins can toggle user status
        return $user->hasRole(['admin', 'super-admin']) || 
               $user->hasPermissionTo('toggle-user-status');
    }

    /**
     * Determine whether the user can reset passwords.
     */
    public function resetPassword(User $user, User $model): bool
    {
        // Users cannot reset their own password this way (should use profile)
        if ($user->id === $model->id) {
            return false;
        }

        // Prevent non-super-admin from resetting super-admin password
        if ($model->hasRole('super-admin') && !$user->hasRole('super-admin')) {
            return false;
        }

        // Only admins can reset passwords
        return $user->hasRole(['admin', 'super-admin']) || 
               $user->hasPermissionTo('reset-user-passwords');
    }

    /**
     * Determine whether the user can view API usage.
     */
    public function viewApiUsage(User $user, User $model): bool
    {
        // Users can view their own API usage
        if ($user->id === $model->id) {
            return true;
        }

        // Admins and API managers can view any user's API usage
        return $user->hasRole(['admin', 'super-admin', 'api-manager']) || 
               $user->hasPermissionTo('view-api-usage');
    }

    /**
     * Determine whether the user can view activity logs.
     */
    public function viewActivityLogs(User $user, User $model): bool
    {
        // Users can view their own activity logs
        if ($user->id === $model->id) {
            return true;
        }

        // Admins and managers can view activity logs
        return $user->hasRole(['admin', 'super-admin', 'manager']) || 
               $user->hasPermissionTo('view-activity-logs');
    }

    /**
     * Determine whether the user can merge users.
     */
    public function mergeUsers(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin']) || 
               $user->hasPermissionTo('merge-users');
    }

    /**
     * Determine whether the user can impersonate other users.
     */
    public function impersonate(User $user, User $model): bool
    {
        // Cannot impersonate yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Prevent non-super-admin from impersonating super-admin
        if ($model->hasRole('super-admin') && !$user->hasRole('super-admin')) {
            return false;
        }

        // Only super-admins can impersonate
        return $user->hasRole('super-admin') || 
               $user->hasPermissionTo('impersonate-users');
    }

    /**
     * Determine if user can manage API keys for the target user.
     */
    public function manageApiKeys(User $user, User $model): bool
    {
        // Users can manage their own API keys
        if ($user->id === $model->id) {
            return $user->hasRole(['admin', 'api-user']) || 
                   $user->hasPermissionTo('manage-own-api-keys');
        }

        // Admins can manage any user's API keys
        return $user->hasRole(['admin', 'super-admin', 'api-manager']) || 
               $user->hasPermissionTo('manage-user-api-keys');
    }

    /**
     * Helper method to check if user has any admin role
     */
    private function isAdmin(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin', 'system-admin']);
    }

    /**
     * Helper method to check if user is manager or above
     */
    private function isManagerOrAbove(User $user): bool
    {
        return $user->hasRole(['admin', 'super-admin', 'system-admin', 'manager', 'user-manager']);
    }

    /**
     * Check if user can perform action on target user based on role hierarchy
     */
    private function canActOnUser(User $actor, User $target): bool
    {
        // Super admin can act on anyone except other super admins (unless they are also super admin)
        if ($target->hasRole('super-admin') && !$actor->hasRole('super-admin')) {
            return false;
        }

        // Admin can act on non-admin users
        if ($actor->hasRole(['admin', 'super-admin'])) {
            return true;
        }

        // Manager can act on regular users
        if ($actor->hasRole(['manager', 'user-manager']) && !$target->hasRole(['admin', 'super-admin', 'manager'])) {
            return true;
        }

        return false;
    }

    /**
     * Before hook - runs before all policy methods
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admin can do anything
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null; // Continue to the specific policy method
    }
}