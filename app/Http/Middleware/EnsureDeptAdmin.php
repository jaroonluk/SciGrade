<?php

namespace App\Http\Middleware;

use App\Support\SciGradeRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeptAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SciGradeRole::isDeptAdmin()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'เฉพาะ Admin สาขาเท่านั้น');
        }

        if (auth()->check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login');
    }
}
