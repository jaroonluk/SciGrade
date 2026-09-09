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
            $sqlState = (string) ($e->errorInfo[0] ?? '');
            $driverCode = (int) ($e->errorInfo[1] ?? 0);
            $message = (string) ($e->errorInfo[2] ?? $e->getMessage());

            $tooMany = $sqlState === '08004' || $driverCode === 1040
                || str_contains(strtolower($message), 'too many connections');

            if (! $tooMany) {
                return null;
            }

            $title = 'ฐานข้อมูลเชื่อมต่อไม่สำเร็จ';
            $body = 'เซิร์ฟเวอร์ฐานข้อมูลเต็มจำนวนการเชื่อมต่อชั่วคราว (Too many connections) '
                .'กรุณารอสักครู่แล้วลองใหม่ หากยังไม่หาย ให้ผู้ดูแล MySQL ตรวจ Threads_connected / max_connections';

            // ห้าม redirect()->back() — จะเกิด ERR_TOO_MANY_REDIRECTS เมื่อหน้าเดิมยัง error อยู่
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $title,
                    'hint' => $body,
                ], 503);
            }

            return response(
                '<!DOCTYPE html><html lang="th"><head><meta charset="utf-8">'
                .'<meta name="viewport" content="width=device-width, initial-scale=1">'
                .'<title>503 — '.$title.'</title>'
                .'<style>body{font-family:Tahoma,sans-serif;max-width:40rem;margin:3rem auto;padding:0 1rem;color:#3f2a1d;line-height:1.5}'
                .'h1{font-size:1.25rem;color:#991b1b}p{color:#5c4033}.btn{display:inline-block;margin-top:1rem;padding:.6rem 1rem;'
                .'background:#a16207;color:#fff;text-decoration:none;border-radius:.5rem}</style></head><body>'
                .'<h1>'.$title.'</h1><p>'.e($body).'</p>'
                .'<a class="btn" href="'.e(url('/')).'">ลองใหม่อีกครั้ง</a>'
                .'</body></html>',
                503,
                ['Content-Type' => 'text/html; charset=UTF-8', 'Retry-After' => '30']
            );
        });
    })->create();
