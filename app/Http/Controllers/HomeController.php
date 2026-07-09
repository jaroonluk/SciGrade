<?php

namespace App\Http\Controllers;

use App\Models\GradeReport;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DeptSubmissionService;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly DeptSubmissionService $deptSubmissionService,
    ) {}

    public function index(Request $request): View
    {
        $role = session('scigrade_role', 'instructor');
        $defaultYear = AcademicTerm::defaultYear();
        $defaultTerm = AcademicTerm::defaultTerm();
        $term = (int) $request->input('term', $defaultTerm);
        $year = (int) $request->input('year', $defaultYear);

        $reports = collect();
        $departments = collect();
        $deptDepartmentId = null;
        $deptSubmission = null;
        $openDeptSubmissions = collect();
        $receivedDeptSubmissionsGrouped = collect();

        if ($role === 'instructor') {
            $username = $this->resolveStaffUsername();
            if ($username) {
                $reports = GradeReport::query()
                    ->with(['gradeStds', 'files', 'approvalLogs.approver'])
                    ->where('username', $username)
                    ->where('term', (string) $term)
                    ->where('year', (string) $year)
                    ->orderByDesc('created_stamp')
                    ->orderByDesc('grade_id')
                    ->get();
            }
        }

        if ($role === 'dept_admin') {
            $staff = $this->staffAuth->findByEmail(auth()->user()->email);
            if ($staff) {
                $this->staffAuth->storeInSession($staff);
                $departments = $this->departmentAccess->allowedDepartments($staff);
                $deptDepartmentId = (int) $request->input('dept_department_id', $departments->first()?->department_id);
                if ($departments->contains('department_id', $deptDepartmentId)) {
                    $deptSubmission = $this->deptSubmissionService->openSubmission($deptDepartmentId, $term, $year);
                }
            }
        }

        if ($role === 'faculty_admin') {
            $openDeptSubmissions = $this->deptSubmissionService->openSubmissionsForFaculty($term, $year);
            $receivedDeptSubmissionsGrouped = $this->deptSubmissionService->receivedSubmissionsGroupedForFaculty($term, $year);
        }

        return view('home', [
            'role' => $role,
            'staffDisplayName' => $this->staffAuth->displayNameFor(
                auth()->user()->email,
                auth()->user()->name,
            ),
            'reports' => $reports,
            'term' => $term,
            'year' => $year,
            'years' => AcademicTerm::yearOptions(),
            'departments' => $departments,
            'deptDepartmentId' => $deptDepartmentId,
            'deptSubmission' => $deptSubmission,
            'openDeptSubmissions' => $openDeptSubmissions,
            'receivedDeptSubmissionsGrouped' => $receivedDeptSubmissionsGrouped,
        ]);
    }

    public function setRole(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'in:instructor,dept_admin,faculty_admin'],
        ]);

        session(['scigrade_role' => $request->role]);

        return redirect()->route('dashboard');
    }

    private function resolveStaffUsername(): ?string
    {
        $username = session('staff_username');

        if (empty($username) && auth()->user()) {
            $staff = $this->staffAuth->findByEmail(auth()->user()->email);
            if ($staff) {
                $this->staffAuth->storeInSession($staff);
                $username = $staff->username;
            }
        }

        return $username ? (string) $username : null;
    }
}
