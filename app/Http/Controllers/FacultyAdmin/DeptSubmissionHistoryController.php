<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Services\DeptAdmin\DeptSubmissionService;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use App\Support\SciGradeRole;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeptSubmissionHistoryController extends Controller
{
    public function __construct(
        private readonly DeptSubmissionService $submissionService,
        private readonly StaffAuthService $staffAuth,
    ) {}

    public function index(Request $request): View
    {
        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());

        if (! in_array($term, [1, 2, 3], true)) {
            $term = AcademicTerm::defaultTerm();
        }

        if (SciGradeRole::isFacultyCapable() && auth()->user()) {
            $staff = $this->staffAuth->findByEmail(auth()->user()->email);
            if ($staff) {
                $this->staffAuth->storeInSession($staff);
            }
        }

        return view('faculty-admin.dept-submission-history.index', [
            'term' => $term,
            'year' => $year,
            'years' => AcademicTerm::yearOptions(),
            'openDeptSubmissions' => $this->submissionService->openSubmissionsForFaculty($term, $year),
            'receivedDeptSubmissionsGrouped' => $this->submissionService->receivedSubmissionsGroupedForFaculty($term, $year),
        ]);
    }
}
