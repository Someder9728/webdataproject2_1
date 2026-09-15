<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                abort(response()->json([
                    'message' => 'กรุณาเข้าสู่ระบบ',
                    'errors' => (object) [],
                    'code' => 'UNAUTHENTICATED',
                ], 401));
            }

            return redirect()->route('login');
        }

        if (! in_array($user->u_role, $roles, true)) {
            if ($request->expectsJson()) {
                abort(response()->json([
                    'message' => 'คุณไม่มีสิทธิ์ดำเนินการนี้',
                    'errors' => (object) [],
                    'code' => 'FORBIDDEN',
                ], 403));
            }

            abort(403, 'คุณไม่มีสิทธิ์ดำเนินการนี้');
        }

        return $next($request);
    }
}