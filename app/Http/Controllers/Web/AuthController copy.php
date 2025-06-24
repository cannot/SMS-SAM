<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\LdapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected $ldapService;

    public function __construct(LdapService $ldapService)
    {
        $this->ldapService = $ldapService;
    }

    public function showLogin()
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

        try {
            $ldapService = new LdapService();
            $user = $ldapService->authenticate($request->username, $request->password);
            
            if (!$user) {
                return back()->withErrors([
                    'login' => 'Invalid credentials or user not found in LDAP.'
                ])->withInput();
            }
            
            // Check if user is active
            if (!$user->is_active) {
                return back()->withErrors([
                    'login' => 'Your account is disabled. Please contact administrator.'
                ])->withInput();
            }
            
            // Login successful
            Auth::login($user);
            Log::info("User logged in successfully: " . $user->username);
            
            return redirect()->intended('/dashboard');
            
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            return back()->withErrors([
                'login' => 'Login system error. Please try again later.'
            ])->withInput();
        }
    }

    public function logout(Request $request)
    {
        try {
            // Invalidate JWT token
            if (session('jwt_token')) {
                JWTAuth::setToken(session('jwt_token'))->invalidate();
                session()->forget('jwt_token');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', 'You have been logged out successfully.');

        } catch (\Exception $e) {
            \Log::error('Logout Error: ' . $e->getMessage());
            return redirect()->route('login');
        }
    }
}