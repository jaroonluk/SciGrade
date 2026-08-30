<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class UseRequestRootUrl
{
    /**
     * สร้างลิงก์และ Google callback ตามโฮสต์ที่ผู้ใช้เปิดอยู่
     * (เครื่อง: 127.0.0.1:8000 / เซิร์ฟเวอร์: gms.sc.kku.ac.th)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $root = $request->getSchemeAndHttpHost();
        URL::forceRootUrl($root);
        URL::forceScheme($request->getScheme());

        config(['services.google.redirect' => $root.'/auth/google/callback']);

        if ($this->isLoopbackHost($request)) {
            config([
                'session.secure' => false,
                'session.domain' => null,
            ]);
        }

        return $next($request);
    }

    private function isLoopbackHost(Request $request): bool
    {
        return in_array($request->getHost(), ['127.0.0.1', 'localhost', '::1'], true);
    }
}
