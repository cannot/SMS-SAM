<?php

namespace App\Services;

use App\Models\User;
use LdapRecord\Laravel\Facades\LdapRecord;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use LdapRecord\Models\ActiveDirectory\Group as LdapGroup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class LdapService
{
    public function authenticate($username, $password)
    {
        try {
            // Log the search attempt
            Log::info("LDAP Authentication attempt for: {$username}");
            
            // Search for user with multiple methods
            $ldapUser = null;
            
            // Method 1: Search by samaccountname (most common)
            $ldapUser = LdapUser::where('samaccountname', $username)->first();
            
            // Method 2: If not found, try userprincipalname
            if (!$ldapUser && strpos($username, '@') !== false) {
                $ldapUser = LdapUser::where('userprincipalname', $username)->first();
            }
            
            // Method 3: If not found, try mail attribute
            if (!$ldapUser) {
                $ldapUser = LdapUser::where('mail', $username)->first();
            }
            
            // Method 4: If still not found, try case-insensitive search
            if (!$ldapUser) {
                $users = LdapUser::get();
                foreach ($users as $user) {
                    $samAccount = $user->getFirstAttribute('samaccountname');
                    $userPrincipal = $user->getFirstAttribute('userprincipalname');
                    $email = $user->getFirstAttribute('mail');
                    
                    if (strtolower($samAccount) === strtolower($username) ||
                        strtolower($userPrincipal) === strtolower($username) ||
                        strtolower($email) === strtolower($username)) {
                        $ldapUser = $user;
                        break;
                    }
                }
            }
            
            if (!$ldapUser) {
                Log::warning("LDAP user not found: {$username}");
                return false;
            }

            Log::info("LDAP user found: " . $ldapUser->getFirstAttribute('samaccountname'));

            // Check if user account is disabled
            if ($this->isUserDisabled($ldapUser)) {
                Log::warning("LDAP user account is disabled: {$username}");
                return false;
            }

            // Authenticate user using LDAP bind
            try {
                $userDn = $ldapUser->getDn();
                $connection = $ldapUser->getConnection();
                
                // Try to bind with user credentials
                if (!$connection->auth()->attempt($userDn, $password)) {
                    Log::warning("LDAP authentication failed for: {$username}");
                    return false;
                }
                
                Log::info("LDAP bind successful for: {$username}");
                
            } catch (\Exception $authException) {
                Log::error("LDAP authentication error: " . $authException->getMessage());
                return false;
            }

            // Sync user data
            $user = $this->syncUser($ldapUser);
            
            if (!$user) {
                Log::error("Failed to sync user data for: {$username}");
                return false;
            }

            Log::info("LDAP authentication successful for: {$username}");
            return $user; // Return User model instead of array
            
        } catch (\Exception $e) {
            Log::error('LDAP Authentication Error: ' . $e->getMessage());
            Log::error('LDAP Authentication Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    public function syncUser($ldapUser)
    {
        try {
            $userData = [
                'ldap_guid' => $ldapUser->getConvertedGuid(),
                'username' => $ldapUser->getFirstAttribute('samaccountname'),
                'email' => $ldapUser->getFirstAttribute('mail'),
                'first_name' => $ldapUser->getFirstAttribute('givenname'),
                'last_name' => $ldapUser->getFirstAttribute('sn'),
                'display_name' => $ldapUser->getFirstAttribute('displayname'),
                'department' => $ldapUser->getFirstAttribute('department'),
                'title' => $ldapUser->getFirstAttribute('title'),
                'phone' => $ldapUser->getFirstAttribute('telephonenumber'),
                'is_active' => !$this->isUserDisabled($ldapUser),
                'ldap_synced_at' => now(),
            ];

            $user = User::updateOrCreate(
                ['ldap_guid' => $userData['ldap_guid']],
                array_filter($userData) // Remove null values
            );

            Log::info("User synced successfully: {$userData['username']}");
            return $user;
            
        } catch (\Exception $e) {
            Log::error('LDAP User Sync Error: ' . $e->getMessage());
            return false;
        }
    }

    public function syncAllUsers()
    {
        try {
            $ldapUsers = LdapUser::get();
            $syncedCount = 0;
            $errors = [];

            foreach ($ldapUsers as $ldapUser) {
                try {
                    if ($this->syncUser($ldapUser)) {
                        $syncedCount++;
                    }
                } catch (\Exception $e) {
                    $username = $ldapUser->getFirstAttribute('samaccountname');
                    $errors[] = "Failed to sync user {$username}: " . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                Log::warning('LDAP Sync Errors: ' . implode(', ', $errors));
            }

            Log::info("LDAP Sync completed. Synced {$syncedCount} users.");
            return $syncedCount;
            
        } catch (\Exception $e) {
            Log::error('LDAP Bulk Sync Error: ' . $e->getMessage());
            return 0;
        }
    }

    public function searchUsers($query, $limit = 50): Collection
    {
        try {
            $users = LdapUser::where('cn', 'contains', $query)
                ->orWhere('mail', 'contains', $query)
                ->orWhere('samaccountname', 'contains', $query)
                ->limit($limit)
                ->get();

            return collect($users);
            
        } catch (\Exception $e) {
            Log::error('LDAP Search Error: ' . $e->getMessage());
            return collect();
        }
    }

    public function getUsersByGroup($groupName): Collection
    {
        try {
            $group = LdapGroup::where('cn', $groupName)->first();
            
            if (!$group) {
                Log::warning("LDAP group not found: {$groupName}");
                return collect();
            }

            $members = $group->members()->get();
            
            // Filter only user objects
            $users = $members->filter(function ($member) {
                return $member instanceof LdapUser;
            });

            return collect($users);
            
        } catch (\Exception $e) {
            Log::error('LDAP Group Search Error: ' . $e->getMessage());
            return collect();
        }
    }

    public function getUserGroups($username): Collection
    {
        try {
            $ldapUser = LdapUser::where('samaccountname', $username)->first();
            
            if (!$ldapUser) {
                return collect();
            }

            $groups = $ldapUser->groups()->get();
            
            return collect($groups)->map(function ($group) {
                return $group->getFirstAttribute('cn');
            });
            
        } catch (\Exception $e) {
            Log::error('LDAP User Groups Error: ' . $e->getMessage());
            return collect();
        }
    }

    public function isUserInGroup($username, $groupName): bool
    {
        try {
            $userGroups = $this->getUserGroups($username);
            return $userGroups->contains($groupName);
            
        } catch (\Exception $e) {
            Log::error('LDAP Group Check Error: ' . $e->getMessage());
            return false;
        }
    }

    public function getUserByGuid($guid)
    {
        try {
            return LdapUser::where('objectguid', $guid)->first();
            
        } catch (\Exception $e) {
            Log::error('LDAP GUID Search Error: ' . $e->getMessage());
            return null;
        }
    }

    public function refreshUser($ldapGuid)
    {
        try {
            $ldapUser = $this->getUserByGuid($ldapGuid);
            
            if (!$ldapUser) {
                return false;
            }

            return $this->syncUser($ldapUser);
            
        } catch (\Exception $e) {
            Log::error('LDAP User Refresh Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user account is disabled
     */
    private function isUserDisabled($ldapUser): bool
    {
        $userAccountControl = $ldapUser->getFirstAttribute('useraccountcontrol');
        
        if (!$userAccountControl) {
            return false;
        }

        // Check if ACCOUNTDISABLE flag (0x0002) is set
        return (intval($userAccountControl) & 0x0002) !== 0;
    }

    /**
     * Get user's manager
     */
    public function getUserManager($username)
    {
        try {
            $ldapUser = LdapUser::where('samaccountname', $username)->first();
            
            if (!$ldapUser) {
                return null;
            }

            $managerDn = $ldapUser->getFirstAttribute('manager');
            
            if (!$managerDn) {
                return null;
            }

            return LdapUser::find($managerDn);
            
        } catch (\Exception $e) {
            Log::error('LDAP Manager Search Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Simple authentication test method
     */
    public function testAuthentication($username, $password)
    {
        try {
            Log::info("Testing LDAP authentication for: {$username}");
            
            // Find user
            $ldapUser = LdapUser::where('samaccountname', $username)->first();
            
            if (!$ldapUser) {
                Log::warning("User not found: {$username}");
                return false;
            }
            
            Log::info("User found: " . $ldapUser->getFirstAttribute('samaccountname'));
            Log::info("User DN: " . $ldapUser->getDn());
            
            // Test authentication with connection
            $connection = $ldapUser->getConnection();
            $userDn = $ldapUser->getDn();
            
            Log::info("Attempting to authenticate with DN: {$userDn}");
            
            $authResult = $connection->auth()->attempt($userDn, $password);
            
            if ($authResult) {
                Log::info("Authentication successful!");
                return true;
            } else {
                Log::warning("Authentication failed!");
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error("Authentication test error: " . $e->getMessage());
            return false;
        }
    }

    public function testConnection()
    {
        try {
            // Test LDAP connection
            $connection = ldap_connect($this->host, $this->port);
            if ($connection) {
                ldap_close($connection);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
}