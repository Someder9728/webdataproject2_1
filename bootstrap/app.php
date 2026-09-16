<?php


use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsurePasswordIsChanged;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureRole;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        EnsureAccountIsActive::class,
    ]);

    $middleware->alias([
        'password.changed' => EnsurePasswordIsChanged::class,
        'role' => EnsureRole::class,
        ]);
    })
     
        ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (
            AuthenticationException $exception,
            Request $request
        ) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'กรุณาเข้าสู่ระบบ',
                    'errors' => (object) [],
                    'code' => 'UNAUTHENTICATED',
                ], 401);
            }

            return null;
        });

        $exceptions->respond(function (
            \Symfony\Component\HttpFoundation\Response $response,
            \Throwable $exception,
            Request $request
        ) {
            $status = $response->getStatusCode();

            if (! $request->is('api/*') || $status < 400) {
                return $response;
            }

            [$code, $message] = match ($status) {
                401 => ['UNAUTHENTICATED', 'กรุณาเข้าสู่ระบบ'],
                403 => ['FORBIDDEN', 'ไม่มีสิทธิ์ดำเนินการ'],
                404 => ['NOT_FOUND', 'ไม่พบข้อมูลที่ร้องขอ'],
                405 => ['METHOD_NOT_ALLOWED', 'ไม่รองรับวิธีเรียกใช้งานนี้'],
                409 => ['CONFLICT', 'ข้อมูลมีการเปลี่ยนแปลงหรือขัดแย้ง กรุณาตรวจสอบอีกครั้ง'],
                419 => ['CSRF_TOKEN_MISMATCH', 'กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง'],
                422 => ['VALIDATION_FAILED', 'ข้อมูลที่ส่งมาไม่ถูกต้อง'],
                429 => ['TOO_MANY_REQUESTS', 'เรียกใช้งานถี่เกินไป กรุณาลองใหม่ภายหลัง'],
                default => $status >= 500
                    ? ['SERVER_ERROR', 'ระบบขัดข้อง กรุณาลองใหม่ภายหลัง']
                    : ['REQUEST_FAILED', 'ไม่สามารถดำเนินการได้'],
            };

            $original = json_decode($response->getContent() ?: '{}', true);

            if (! is_array($original)) {
                $original = [];
            }
            $accountMessages = [
                'ACCOUNT_INACTIVE' => 'บัญชีถูกระงับ กรุณาติดต่อผู้ดูแล',
                'PASSWORD_CHANGE_REQUIRED' => 'กรุณาเปลี่ยนรหัสผ่านก่อนใช้งาน',
            ];

            $originalCode = $original['code'] ?? null;

            if (
                $status === 403 &&
                is_string($originalCode) &&
                isset($accountMessages[$originalCode])
            ) {
                $code = $originalCode;
                $message = $accountMessages[$originalCode];
            }

            $errors = $status === 422 && is_array($original['errors'] ?? null)
                ? $original['errors']
                : [];

            $payload = [
                'message' => $message,
                'errors' => (object) $errors,
                'code' => $code,
            ];

            if ($response instanceof \Symfony\Component\HttpFoundation\JsonResponse) {
                $response->setData($payload);
            } else {
                $response->setContent(json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                ));

                $response->headers->set('Content-Type', 'application/json');
            }

            $response->headers->remove('Content-Length');

            return $response;
        });
    })->create();
