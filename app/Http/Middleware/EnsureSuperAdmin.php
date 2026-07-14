<?php

namespace App\Http\Middleware;

use App\Support\SciGradeRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SciGradeRole::isSuperAdmin()
            || ! SciGradeRole::staffHasSuperPrivilege()
            || SciGradeRole::isImpersonating()) {
            abort(403, 'เฉพาะ Super Admin เท่านั้น');
        }

        return $next($request);
    }
}
