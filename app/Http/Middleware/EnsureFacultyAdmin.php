<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFacultyAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('scigrade_role', 'instructor') !== 'faculty_admin') {
            abort(403, 'เฉพาะ Admin กลางเท่านั้น');
        }

        return $next($request);
    }
}
