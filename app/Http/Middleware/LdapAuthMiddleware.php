<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class LdapAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // สำหรับ Web Routes - ตรวจสอบ Session
            if ($request->expectsJson() || $request->is('api/*')) {
                // API Routes - ใช้ JWT Token
                return $this->handleApiAuth($request, $next);
            } else {
                // Web Routes - ใช้ Session
                return $this->handleWebAuth($request, $next);
            }
        } catch (\Exception $e) {
            return $this->unauthorizedResponse($request, $e->getMessage());
        }
    }

    /**
     * Handle API authentication
     */
    private function handleApiAuth(Request $request, Closure $next)
    {
        try {
            // ตรวจสอบ JWT token
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                throw new \Exception('User not found');
            }

            if (!$user->is_active) {
                throw new \Exception('User account is disabled');
            }

            Auth::setUser($user);
            return $next($request);

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired'
            ], 401);
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalid'
            ], 401);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token absent'
            ], 401);
        }
    }

    /**
     * Handle web authentication
     */
    private function handleWebAuth(Request $request, Closure $next)
    {
        // ตรวจสอบ Session Authentication
        if (!Auth::check()) {
            return redirect()->route('login')
                           ->with('error', 'กรุณาเข้าสู่ระบบก่อนใช้งาน');
        }

        $user = Auth::user();

        // ตรวจสอบว่า user ยัง active อยู่
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                           ->with('error', 'บัญชีผู้ใช้ของคุณถูกปิดใช้งาน กรุณาติดต่อผู้ดูแลระบบ');
        }

        // ตรวจสอบว่า session ยังไม่หมดอายุ
        if (session('last_activity') && (time() - session('last_activity')) > config('session.lifetime') * 60) {
            Auth::logout();
            return redirect()->route('login')
                           ->with('error', 'Session หมดอายุ กรุณาเข้าสู่ระบบใหม่');
        }

        // อัปเดต last activity
        session(['last_activity' => time()]);

        return $next($request);
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(Request $request, string $message = 'Unauthorized')
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 401);
        }

        return redirect()->route('login')->with('error', $message);
    }
