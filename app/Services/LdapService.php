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

    public function syncUserx($ldapUser)
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

    public function syncAllUsersx()
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

    /**
     * Get all users from LDAP
     */
    public function getAllUsers()
    {
        try {
            Log::info('Getting all users from LDAP...');
            
            // Get all users from LDAP with basic filters
            $ldapUsers = LdapUser::where('objectclass', 'user')
                ->where('objectcategory', 'person')
                ->whereHas('samaccountname') // Must have username
                ->whereHas('mail') // Must have email
                ->get();

            Log::info('Found ' . count($ldapUsers) . ' users in LDAP');
            
            return $ldapUsers;
            
        } catch (\Exception $e) {
            Log::error('Error getting all LDAP users: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Enhanced sync user method with better error handling
     */
    public function syncUser($ldapUser)
    {
        try {
            // Validate required fields
            $username = $ldapUser->getFirstAttribute('samaccountname');
            $email = $ldapUser->getFirstAttribute('mail');
            
            if (empty($username) || empty($email)) {
                Log::warning('LDAP user missing required fields', [
                    'username' => $username,
                    'email' => $email
                ]);
                return false;
            }

            $userData = [
                'ldap_guid' => $ldapUser->getConvertedGuid(),
                'username' => $username,
                'email' => $email,
                'first_name' => $ldapUser->getFirstAttribute('givenname') ?: '',
                'last_name' => $ldapUser->getFirstAttribute('sn') ?: '',
                'display_name' => $ldapUser->getFirstAttribute('displayname') ?: 
                                trim(($ldapUser->getFirstAttribute('givenname') ?: '') . ' ' . 
                                     ($ldapUser->getFirstAttribute('sn') ?: '')),
                'department' => $ldapUser->getFirstAttribute('department'),
                'title' => $ldapUser->getFirstAttribute('title'),
                'phone' => $ldapUser->getFirstAttribute('telephonenumber'),
                'is_active' => !$this->isUserDisabled($ldapUser),
                'auth_source' => 'ldap',
                'ldap_synced_at' => now(),
            ];

            // Remove null values
            $userData = array_filter($userData, function($value) {
                return $value !== null && $value !== '';
            });

            $user = User::updateOrCreate(
                ['ldap_guid' => $userData['ldap_guid']],
                $userData
            );

            Log::info("User synced successfully: {$userData['username']}", [
                'user_id' => $user->id,
                'was_recently_created' => $user->wasRecentlyCreated
            ]);
            
            return $user;
            
        } catch (\Exception $e) {
            Log::error('LDAP User Sync Error: ' . $e->getMessage(), [
                'username' => $ldapUser->getFirstAttribute('samaccountname') ?? 'unknown'
            ]);
            return false;
        }
    }

    /**
     * Enhanced sync all users with better progress tracking
     */
    public function syncAllUsers()
    {
        try {
            Log::info('Starting LDAP sync for all users...');
            
            $ldapUsers = $this->getAllUsers();
            $syncedCount = 0;
            $errors = [];
            $newUsers = 0;
            $updatedUsers = 0;

            foreach ($ldapUsers as $ldapUser) {
                try {
                    $existingUser = User::where('ldap_guid', $ldapUser->getConvertedGuid())->first();
                    $wasNew = !$existingUser;
                    
                    $user = $this->syncUser($ldapUser);
                    
                    if ($user) {
                        $syncedCount++;
                        if ($wasNew) {
                            $newUsers++;
                        } else {
                            $updatedUsers++;
                        }
                    }
                } catch (\Exception $e) {
                    $username = $ldapUser->getFirstAttribute('samaccountname') ?? 'unknown';
                    $errors[] = "Failed to sync user {$username}: " . $e->getMessage();
                    Log::error("Failed to sync LDAP user", [
                        'username' => $username,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $results = [
                'total_users' => count($ldapUsers),
                'synced_users' => $syncedCount,
                'new_users' => $newUsers,
                'updated_users' => $updatedUsers,
                'errors' => count($errors),
                'error_details' => $errors
            ];

            if (!empty($errors)) {
                Log::warning('LDAP Sync completed with errors', $results);
            } else {
                Log::info('LDAP Sync completed successfully', $results);
            }

            return $results;
            
        } catch (\Exception $e) {
            Log::error('LDAP Bulk Sync Error: ' . $e->getMessage());
            throw $e;
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
     * Get all departments from LDAP users
     */
    public function getDepartments(): array
    {
        try {
            Log::info('Getting departments from LDAP...');
            
            // ดึงแผนกจากผู้ใช้ที่มีอยู่ในฐานข้อมูลก่อน (เร็วกว่า)
            $dbDepartments = User::whereNotNull('department')
                ->where('department', '!=', '')
                ->distinct()
                ->pluck('department')
                ->filter()
                ->sort()
                ->values()
                ->toArray();

            if (!empty($dbDepartments)) {
                Log::info('Found ' . count($dbDepartments) . ' departments from database');
                return $dbDepartments;
            }

            // ถ้าไม่มีในฐานข้อมูล ให้ดึงจาก LDAP
            $ldapUsers = LdapUser::get();
            $departments = collect();

            foreach ($ldapUsers as $user) {
                $department = $user->getFirstAttribute('department');
                if ($department && !empty(trim($department))) {
                    $departments->push(trim($department));
                }
            }

            $uniqueDepartments = $departments->unique()->filter()->sort()->values()->toArray();
            
            Log::info('Found ' . count($uniqueDepartments) . ' departments from LDAP');
            return $uniqueDepartments;
            
        } catch (\Exception $e) {
            Log::error('Error getting departments: ' . $e->getMessage());
            return [
                'IT',
                'HR', 
                'Finance',
                'Marketing',
                'Sales',
                'Operations'
            ]; // Fallback departments
        }
    }

    /**
     * Get all LDAP groups
     */
    public function getGroups(): array
    {
        try {
            Log::info('Getting LDAP groups...');
            
            $groups = LdapGroup::get();
            $groupNames = collect();

            foreach ($groups as $group) {
                $groupName = $group->getFirstAttribute('cn');
                if ($groupName && !empty(trim($groupName))) {
                    // กรองเฉพาะกลุ่มที่ไม่ใช่ system groups
                    if (!$this->isSystemGroup($groupName)) {
                        $groupNames->push(trim($groupName));
                    }
                }
            }

            $uniqueGroups = $groupNames->unique()->filter()->sort()->values()->toArray();
            
            Log::info('Found ' . count($uniqueGroups) . ' LDAP groups');
            return $uniqueGroups;
            
        } catch (\Exception $e) {
            Log::error('Error getting LDAP groups: ' . $e->getMessage());
            return [
                'Domain Users',
                'Domain Admins',
                'IT-Support',
                'HR-Team',
                'Finance-Team'
            ]; // Fallback groups
        }
    }

    /**
     * Get group members by group name
     */
    public function getGroupMembers($groupName): array
    {
        try {
            Log::info("Getting members for LDAP group: {$groupName}");
            
            $group = LdapGroup::where('cn', $groupName)->first();
            
            if (!$group) {
                Log::warning("LDAP group not found: {$groupName}");
                return [];
            }

            $members = $group->members()->get();
            $emails = collect();

            foreach ($members as $member) {
                if ($member instanceof LdapUser) {
                    $email = $member->getFirstAttribute('mail');
                    if ($email && !empty(trim($email))) {
                        $emails->push(trim($email));
                    }
                }
            }

            $uniqueEmails = $emails->unique()->filter()->values()->toArray();
            
            Log::info("Found " . count($uniqueEmails) . " members in group: {$groupName}");
            return $uniqueEmails;
            
        } catch (\Exception $e) {
            Log::error("Error getting group members for {$groupName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if group is a system group (should be filtered out)
     */
    private function isSystemGroup($groupName): bool
    {
        $systemGroups = [
            'Schema Admins',
            'Enterprise Admins', 
            'Domain Computers',
            'Domain Controllers',
            'Cert Publishers',
            'DHCP Users',
            'DHCP Administrators',
            'DnsAdmins',
            'Group Policy Creator Owners',
            'RAS and IAS Servers',
            'Pre-Windows 2000 Compatible Access',
            'Windows Authorization Access Group',
            'Network Configuration Operators',
            'Performance Monitor Users',
            'Performance Log Users',
            'Distributed COM Users',
            'IIS_IUSRS',
            'Cryptographic Operators',
            'Event Log Readers',
            'Certificate Service DCOM Access',
        ];

        return in_array($groupName, $systemGroups) || 
               str_starts_with($groupName, 'CN=') ||
               str_starts_with($groupName, 'OU=') ||
               str_contains($groupName, '$');
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

    /**
     * Enhanced test connection with detailed error reporting
     */
    public function testConnection()
    {
        try {
            Log::info('Testing LDAP connection...');
            
            // Try to get at least one user to test connection
            $testUser = LdapUser::limit(1)->first();
            
            if ($testUser) {
                Log::info('LDAP connection test successful');
                return true;
            } else {
                Log::warning('LDAP connection test failed - no users found');
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('LDAP Connection Test Error: ' . $e->getMessage(), [
                'config' => [
                    'host' => config('ldap.default.hosts.0', 'not set'),
                    'base_dn' => config('ldap.default.base_dn', 'not set'),
                    'username' => config('ldap.default.username', 'not set')
                ]
            ]);
            return false;
        }
    }
}