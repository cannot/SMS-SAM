<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\LdapService;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $ldapService;

    public function __construct(LdapService $ldapService)
    {
        $this->ldapService = $ldapService;
    }

    /**
     * Show login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->username;
        $password = $request->password;

        try {
            // ลองใช้ LDAP Authentication ก่อน (ถ้า LDAP เปิดใช้งาน)
            if ($this->isLdapEnabled()) {
                $user = $this->attemptLdapLogin($username, $password);
                
                if ($user) {
                    return $this->loginSuccess($user, 'LDAP Authentication successful');
                }
            }

        } catch (\Exception $e) {
            // หาก LDAP ล้มเหลว ให้ใช้ Fallback Authentication
            Log::warning('LDAP Authentication failed: ' . $e->getMessage());
        }

        // ใช้ Fallback Authentication
        $user = $this->attemptFallbackLogin($username, $password);
        
        if ($user) {
            $message = $this->isLdapEnabled() 
                ? 'Fallback Authentication successful (LDAP unavailable)' 
                : 'Database Authentication successful';
            return $this->loginSuccess($user, $message);
        }

        // หากทั้งสองวิธีล้มเหลว
        return back()->withErrors([
            'username' => 'Invalid credentials or authentication service unavailable.'
        ])->withInput($request->except('password'));
    }

    /**
     * ตรวจสอบว่า LDAP เปิดใช้งานหรือไม่
     */
    private function isLdapEnabled()
    {
        return config('ldap.enabled', false) && 
               config('ldap.host') && 
               function_exists('ldap_connect');
    }

    /**
     * ลองใช้ LDAP Authentication
     */
    private function attemptLdapLogin($username, $password)
    {
        try {
            // ตรวจสอบ credentials กับ LDAP โดยใช้ method ที่มีอยู่
            $ldapUser = $this->ldapService->authenticate($username, $password);
            
            if (!$ldapUser) {
                return null;
            }

            // หา user ในฐานข้อมูล หรือสร้างใหม่
            $user = User::where('username', $username)->first();
            
            if (!$user) {
                // สร้าง user ใหม่จากข้อมูล LDAP
                $user = $this->createUserFromLdap($username, $ldapUser);
            } else {
                // อัพเดทข้อมูลจาก LDAP
                $this->updateUserFromLdap($user, $ldapUser);
            }

            return $user;
            
        } catch (\Exception $e) {
            Log::error('LDAP authentication error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * สร้าง user จากข้อมูล LDAP
     */
    private function createUserFromLdap($username, $ldapUser)
    {
        $userData = [
            'ldap_guid' => $ldapUser['guid'] ?? \Str::uuid(),
            'username' => $username,
            'email' => $ldapUser['email'] ?? $username . '@company.local',
            'first_name' => $ldapUser['first_name'] ?? '',
            'last_name' => $ldapUser['last_name'] ?? '',
            'display_name' => $ldapUser['display_name'] ?? $username,
            'department' => $ldapUser['department'] ?? '',
            'title' => $ldapUser['title'] ?? '',
            'phone' => $ldapUser['phone'] ?? '',
            'is_active' => true,
            'ldap_synced_at' => now(),
        ];

        $user = User::create($userData);
        
        // กำหนด role เริ่มต้น
        $defaultRole = config('ldap.default_role', 'end_user');
        if ($user && !$user->hasAnyRole()) {
            $user->assignRole($defaultRole);
        }

        Log::info("Created new user from LDAP: {$username}");
        return $user;
    }

    /**
     * อัพเดทข้อมูล user จาก LDAP
     */
    private function updateUserFromLdap($user, $ldapUser)
    {
        $updateData = [];
        
        if (isset($ldapUser['email']) && $ldapUser['email'] !== $user->email) {
            $updateData['email'] = $ldapUser['email'];
        }
        if (isset($ldapUser['first_name']) && $ldapUser['first_name'] !== $user->first_name) {
            $updateData['first_name'] = $ldapUser['first_name'];
        }
        if (isset($ldapUser['last_name']) && $ldapUser['last_name'] !== $user->last_name) {
            $updateData['last_name'] = $ldapUser['last_name'];
        }
        if (isset($ldapUser['display_name']) && $ldapUser['display_name'] !== $user->display_name) {
            $updateData['display_name'] = $ldapUser['display_name'];
        }
        if (isset($ldapUser['department']) && $ldapUser['department'] !== $user->department) {
            $updateData['department'] = $ldapUser['department'];
        }
        if (isset($ldapUser['title']) && $ldapUser['title'] !== $user->title) {
            $updateData['title'] = $ldapUser['title'];
        }
        if (isset($ldapUser['phone']) && $ldapUser['phone'] !== $user->phone) {
            $updateData['phone'] = $ldapUser['phone'];
        }

        if (!empty($updateData)) {
            $updateData['ldap_synced_at'] = now();
            $user->update($updateData);
            Log::info("Updated user from LDAP: {$user->username}");
        }
    }

    /**
     * ใช้ Fallback Authentication (ตรวจสอบกับฐานข้อมูล)
     */
    private function attemptFallbackLogin($username, $password)
    {
        // หา user ในฐานข้อมูล
        $user = User::where('username', $username)
                   ->orWhere('email', $username)
                   ->first();

        if (!$user || !$user->is_active) {
            Log::info("Fallback login failed: User not found or inactive - {$username}");
            return null;
        }

        // ตรวจสอบ password (สำหรับ development users)
        if ($this->checkFallbackPassword($user, $password)) {
            Log::info("Fallback login successful: {$username}");
            return $user;
        }

        Log::info("Fallback login failed: Invalid password - {$username}");
        return null;
    }

    /**
     * ตรวจสอบ password สำหรับ fallback authentication
     */
    private function checkFallbackPassword($user, $password)
    {
        // สำหรับ development environment - อนุญาตให้ใช้ password เริ่มต้น
        if (app()->environment(['local', 'development', 'testing'])) {
            if ($password === 'password' || $password === 'admin123') {
                return true;
            }
        }

        // ตรวจสอบ hashed password หากมี
        if ($user->password && Hash::check($password, $user->password)) {
            return true;
        }

        return false;
    }

    /**
     * จัดการเมื่อ login สำเร็จ
     */
    private function loginSuccess($user, $message = 'Login successful')
    {
        try {
            // อัพเดท last login
            $user->updateLastLogin();

            // สร้าง session ก่อน
            Auth::login($user, true);

            // สร้าง JWT Token (ถ้าทำได้)
            $token = null;
            try {
                $token = JWTAuth::fromUser($user);
                // เก็บ token ใน session สำหรับ API calls
                session(['jwt_token' => $token]);
                Log::info("JWT token created successfully for user: {$user->username}");
            } catch (\Exception $jwtError) {
                Log::warning("Failed to create JWT token for user: {$user->username}, Error: " . $jwtError->getMessage());
                // ไม่ต้องหยุดการทำงาน เพราะ session authentication ยังใช้งานได้
            }

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->withProperties([
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'jwt_created' => $token ? 'yes' : 'no'
                ])
                ->log('User logged in');

            Log::info("Login successful: {$user->username} - {$message}");

            return redirect()->intended('/dashboard')->with('success', $message);

        } catch (\Exception $e) {
            Log::error("Login success handler failed: " . $e->getMessage());
            
            // Fallback: ใช้ Laravel session authentication อย่างเดียว
            Auth::login($user, true);
            
            return redirect()->intended('/dashboard')->with('success', $message . ' (Session only)');
        }
    }

    /**
     * Handle logout request (รองรับทั้ง GET และ POST)
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user) {
                // Log activity
                try {
                    activity()
                        ->performedOn($user)
                        ->causedBy($user)
                        ->withProperties([
                            'ip' => request()->ip(),
                            'user_agent' => request()->userAgent()
                        ])
                        ->log('User logged out');
                } catch (\Exception $e) {
                    Log::warning('Failed to log activity: ' . $e->getMessage());
                }

                Log::info("User logged out: {$user->username}");
            }

            // Invalidate JWT token
            try {
                if ($token = session('jwt_token')) {
                    JWTAuth::setToken($token)->invalidate();
                }
            } catch (\Exception $e) {
                Log::warning('Failed to invalidate JWT token: ' . $e->getMessage());
            }

            // Clear session
            Auth::logout();
            
            // Invalidate session properly
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            // Return appropriate response based on request method
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Logged out successfully']);
            }

            return redirect('/login')->with('success', 'Logged out successfully');

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            
            // Force logout even if there's an error
            Auth::logout();
            
            if ($request->hasSession()) {
                try {
                    $request->session()->flush();
                    $request->session()->regenerate();
                } catch (\Exception $sessionError) {
                    Log::warning('Session cleanup error: ' . $sessionError->getMessage());
                }
            }

            return redirect('/login')->with('warning', 'Logged out (with some issues)');
        }
    }
}