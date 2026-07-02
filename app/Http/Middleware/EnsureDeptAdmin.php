<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeptAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('scigrade_role', 'instructor') !== 'dept_admin') {
            abort(403, 'เฉพาะ Admin สาขาเท่านั้น');
        }

        return $next($request);
    }
}
