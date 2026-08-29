<?php

namespace App\Http\Controllers;

use App\Models\DeptSubmission;
use App\Models\GradeReport;
use App\Services\AuditLogService;
use App\Services\DeptAdmin\DepartmentAccessService;
use App\Services\DeptAdmin\DeptSubmissionService;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use App\Support\SciGradeRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly DepartmentAccessService $departmentAccess,
        private readonly DeptSubmissionService $deptSubmissionService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $role = SciGradeRole::current();
        $defaultYear = AcademicTerm::defaultYear();
        $defaultTerm = AcademicTerm::defaultTerm();
        $term = (int) $request->input('term', $defaultTerm);
        $year = (int) $request->input('year', $defaultYear);

        $reports = collect();
        $departments = collect();
        $deptDepartmentId = null;
        $deptSubmission = null;
        $deptEducationLevel = DeptSubmission::EDUCATION_BACHELOR;
        $openDeptSubmissions = collect();

        if ($role === SciGradeRole::INSTRUCTOR) {
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

        if ($role === SciGradeRole::DEPT_ADMIN) {
            $staff = $this->staffAuth->findByEmail(auth()->user()->email);
            if ($staff) {
                $this->staffAuth->storeInSession($staff);
                $departments = $this->departmentAccess->allowedDepartments($staff);
                $deptDepartmentId = (int) $request->input('dept_department_id', $departments->first()?->department_id);
                $deptEducationLevel = DeptSubmission::normalizeEducationLevel(
                    $request->input('education_level')
                );
                if ($departments->contains('department_id', $deptDepartmentId)) {
                    $deptSubmission = $this->deptSubmissionService->openSubmission(
                        $deptDepartmentId,
                        $term,
                        $year,
                        $deptEducationLevel,
                    );
                }
            }
        }

        if (SciGradeRole::isFacultyCapable($role)) {
            $staff = $this->staffAuth->findByEmail(auth()->user()->email);
            if ($staff) {
                $this->staffAuth->storeInSession($staff);
            }
            $openDeptSubmissions = $this->deptSubmissionService->openSubmissionsForFaculty($term, $year);
        }

        $selectableRoles = SciGradeRole::selectableRolesForCurrentUser();
        $canImpersonate = SciGradeRole::canImpersonate();

        return view('home', [
            'role' => $role,
            'selectableRoles' => $selectableRoles,
            'canImpersonate' => $canImpersonate,
            'isImpersonating' => SciGradeRole::isImpersonating(),
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
            'deptEducationLevel' => $deptEducationLevel,
            'deptSubmission' => $deptSubmission,
            'openDeptSubmissions' => $openDeptSubmissions,
        ]);
    }

    public function setRole(Request $request): RedirectResponse
    {
        $allowed = SciGradeRole::selectableRolesForCurrentUser();

        $request->validate([
            'role' => ['required', Rule::in($allowed)],
        ]);

        if ($request->role === SciGradeRole::SUPER_ADMIN && ! SciGradeRole::staffHasSuperPrivilege()) {
            abort(403);
        }

        $fromRole = SciGradeRole::current();
        session(['scigrade_role' => $request->role]);

        $this->auditLog->record('role.switch', metadata: [
            'from_role' => $fromRole,
            'to_role' => $request->role,
        ]);

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
