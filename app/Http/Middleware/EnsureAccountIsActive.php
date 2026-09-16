<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'บัญชีถูกระงับ กรุณาติดต่อผู้ดูแล',
                    'errors' => (object) [],
                    'code' => 'ACCOUNT_INACTIVE',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'u_username' => 'บัญชีถูกระงับ กรุณาติดต่อผู้ดูแล',
            ]);
        }

        return $next($request);
    }
}