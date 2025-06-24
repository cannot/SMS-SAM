<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\LdapService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected $ldapService;

    public function __construct(LdapService $ldapService)
    {
        $this->ldapService = $ldapService;
    }

    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $username = $request->username;
        $password = $request->password;

        Log::info("Login attempt for user: {$username}");

        try {
            // Method 1: Try LDAP/AD Authentication first
            $user = $this->tryLdapAuthentication($username, $password);
            
            if ($user) {
                return $this->loginUser($user, 'LDAP Authentication successful');
            }

            // Method 2: Try Fallback Authentication if LDAP fails
            $user = $this->tryFallbackAuthentication($username, $password);
            
            if ($user) {
                return $this->loginUser($user, 'Fallback Authentication successful');
            }

            // Both methods failed
            Log::warning("Login failed for user: {$username} - Invalid credentials");
            return back()->withErrors([
                'login' => 'Invalid credentials. Please check your username and password.'
            ])->withInput($request->except('password'));

        } catch (\Exception $e) {
            Log::error("Login Error for user {$username}: " . $e->getMessage());
            return back()->withErrors([
                'login' => 'Login system temporarily unavailable. Please try again later.'
            ])->withInput($request->except('password'));
        }
    }

    /**
     * Try LDAP Authentication
     */
    private function tryLdapAuthentication($username, $password)
    {
        try {
            Log::info("Attempting LDAP authentication for: {$username}");
            
            // Check if LDAP is configured and available
            if (!$this->isLdapAvailable()) {
                Log::info("LDAP not available, skipping LDAP authentication");
                return null;
            }

            // Authenticate with LDAP using the original working method
            $user = $this->ldapService->authenticate($username, $password);
            
            if (!$user) {
                Log::info("LDAP authentication failed for: {$username}");
                return null;
            }

            // Check if user is active
            if (!$user->is_active) {
                Log::warning("LDAP user account disabled: {$username}");
                throw new \Exception('Your account is disabled. Please contact administrator.');
            }

            Log::info("LDAP authentication successful for: {$username}");
            return $user;

        } catch (\Exception $e) {
            Log::error("LDAP authentication error for {$username}: " . $e->getMessage());
            
            // If it's an account disabled error, re-throw it
            if (strpos($e->getMessage(), 'disabled') !== false) {
                throw $e;
            }
            
            // For other LDAP errors, return null to try fallback
            return null;
        }
    }

    /**
     * Try Fallback Authentication (Database)
     */
    private function tryFallbackAuthentication($username, $password)
    {
        try {
            Log::info("Attempting fallback authentication for: {$username}");

            // Find user in database by username or email
            $user = User::where('username', $username)
                       ->orWhere('email', $username)
                       ->first();

            if (!$user) {
                Log::info("User not found in database: {$username}");
                return null;
            }

            // Check if user is active
            if (!$user->is_active) {
                Log::warning("Database user account disabled: {$username}");
                throw new \Exception('Your account is disabled. Please contact administrator.');
            }

            // Verify password
            if (!$this->verifyFallbackPassword($user, $password)) {
                Log::info("Fallback password verification failed for: {$username}");
                return null;
            }

            Log::info("Fallback authentication successful for: {$username}");
            return $user;

        } catch (\Exception $e) {
            Log::error("Fallback authentication error for {$username}: " . $e->getMessage());
            
            // If it's an account disabled error, re-throw it
            if (strpos($e->getMessage(), 'disabled') !== false) {
                throw $e;
            }
            
            return null;
        }
    }

    /**
     * Check if LDAP is available
     */
    private function isLdapAvailable()
    {
        try {
            // Check if LDAP is configured
            if (!config('ldap.enabled', false)) {
                return false;
            }

            if (!config('ldap.host')) {
                return false;
            }

            if (!function_exists('ldap_connect')) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::warning("LDAP availability check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify fallback password
     */
    private function verifyFallbackPassword($user, $password)
    {
        // For development environment - allow default passwords
        if (app()->environment(['local', 'development', 'testing'])) {
            $devPasswords = ['password', 'admin123', 'dev123'];
            if (in_array($password, $devPasswords)) {
                Log::info("Development password accepted for user: {$user->username}");
                return true;
            }
        }

        // Check hashed password if exists
        if ($user->password && Hash::check($password, $user->password)) {
            return true;
        }

        // Emergency access (only if configured)
        $emergencyPasswords = config('auth.emergency_passwords', []);
        if (!empty($emergencyPasswords) && in_array($password, $emergencyPasswords)) {
            Log::warning("Emergency password used for user: {$user->username}");
            return true;
        }

        return false;
    }

    /**
     * Login user and create session
     */
    private function loginUser($user, $message)
    {
        try {
            // Update last login if method exists
            if (method_exists($user, 'updateLastLogin')) {
                $user->updateLastLogin();
            } else {
                // Fallback: update manually
                $user->update(['last_login_at' => now()]);
            }

            // Create Laravel session
            Auth::login($user);

            // Try to create JWT token (optional)
            try {
                $token = JWTAuth::fromUser($user);
                session(['jwt_token' => $token]);
                Log::info("JWT token created for user: {$user->username}");
            } catch (\Exception $jwtError) {
                Log::warning("JWT token creation failed for user: {$user->username}, continuing without JWT");
                // Continue without JWT - Laravel session is enough
            }

            // Log successful login
            Log::info("User logged in successfully: {$user->username} - {$message}");

            // Log activity if available
            try {
                if (function_exists('activity')) {
                    activity()
                        ->performedOn($user)
                        ->causedBy($user)
                        ->withProperties([
                            'ip' => request()->ip(),
                            'user_agent' => request()->userAgent()
                        ])
                        ->log('User logged in');
                }
            } catch (\Exception $activityError) {
                Log::warning("Activity logging failed: " . $activityError->getMessage());
            }

            return redirect()->intended('/dashboard')->with('success', $message);

        } catch (\Exception $e) {
            Log::error("Login user process failed: " . $e->getMessage());
            
            // Fallback: basic Laravel authentication
            Auth::login($user);
            return redirect()->intended('/dashboard')->with('success', $message . ' (Basic mode)');
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            
            if ($user) {
                Log::info("User logging out: {$user->username}");
                
                // Log activity if available
                try {
                    if (function_exists('activity')) {
                        activity()
                            ->performedOn($user)
                            ->causedBy($user)
                            ->withProperties([
                                'ip' => request()->ip(),
                                'user_agent' => request()->userAgent()
                            ])
                            ->log('User logged out');
                    }
                } catch (\Exception $activityError) {
                    Log::warning("Activity logging failed during logout: " . $activityError->getMessage());
                }
            }

            // Invalidate JWT token if exists
            try {
                if (session('jwt_token')) {
                    JWTAuth::setToken(session('jwt_token'))->invalidate();
                    session()->forget('jwt_token');
                }
            } catch (\Exception $jwtError) {
                Log::warning("JWT token invalidation failed: " . $jwtError->getMessage());
            }

            // Logout from Laravel
            Auth::logout();
            
            // Invalidate session
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Log::info("User logged out successfully");
            
            return redirect()->route('login')->with('success', 'You have been logged out successfully.');

        } catch (\Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());
            
            // Force logout even if there are errors
            Auth::logout();
            
            try {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            } catch (\Exception $sessionError) {
                Log::warning('Session cleanup error: ' . $sessionError->getMessage());
            }
            
            return redirect()->route('login')->with('warning', 'Logged out with some issues.');
        }
    }
}