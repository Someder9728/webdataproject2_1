<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'กรุณาเปลี่ยนรหัสผ่านก่อนใช้งาน',
                    'errors' => (object) [],
                    'code' => 'PASSWORD_CHANGE_REQUIRED',
                ], 403);
            }

            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}