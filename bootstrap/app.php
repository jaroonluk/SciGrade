<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\UseRequestRootUrl::class);

        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
        );

        $middleware->alias([
            'dept.admin' => \App\Http\Middleware\EnsureDeptAdmin::class,
            'faculty.admin' => \App\Http\Middleware\EnsureFacultyAdmin::class,
            'super.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Database\QueryException $e, Request $request) {
            $sqlState = $e->errorInfo[0] ?? '';
            $driverCode = (int) ($e->errorInfo[1] ?? 0);
            $message = (string) ($e->errorInfo[2] ?? $e->getMessage());

            $tooMany = $sqlState === '08004' || $driverCode === 1040
                || str_contains(strtolower($message), 'too many connections');

            if (! $tooMany) {
                return null;
            }

            $hint = 'เซิร์ฟเวอร์ฐานข้อมูลเต็มจำนวนการเชื่อมต่อชั่วคราว กรุณารอสักครู่แล้วลองใหม่ '
                .'หากยังไม่หาย ให้ผู้ดูแล MySQL ตรวจ Threads_connected / max_connections และปิด connection ที่ค้าง';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'ฐานข้อมูลเชื่อมต่อไม่สำเร็จ (Too many connections)',
                    'hint' => $hint,
                ], 503);
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'ฐานข้อมูลเชื่อมต่อไม่สำเร็จ (Too many connections)')
                ->with('error_hint', $hint);
        });
    })->create();
