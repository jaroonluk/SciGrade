<?php

namespace App\Http\Middleware;

use App\Support\SciGradeRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureThesisGradeFacultyReview
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SciGradeRole::canReviewThesisGrades()) {
            abort(403, 'เฉพาะเจ้าหน้าที่งานบริการ (บัณฑิตศึกษา) และ Super Admin เท่านั้น');
        }

        return $next($request);
    }
}
