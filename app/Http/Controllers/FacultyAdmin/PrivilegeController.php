<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\PrivilegeRequest;
use App\Models\StaffDepartmentAccess;
use App\Models\TblDepartment;
use App\Models\TblPrivilege;
use App\Models\TblUser;
use App\Services\AuditLogService;
use App\Services\FacultyAdmin\StaffDepartmentAccessService;
use App\Support\SciGradeRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrivilegeController extends Controller
{
    public function __construct(
        private readonly StaffDepartmentAccessService $departmentAccess,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $levelFilter = $request->input('level', 'all');
        if ($levelFilter !== 'all' && ! in_array((string) $levelFilter, TblPrivilege::filterableLevelValues(), true)) {
            $levelFilter = 'all';
        }

        $baseQuery = TblPrivilege::query()
            ->where('system_id', TblPrivilege::SYSTEM_GRADE_REPORT);

        $levelCounts = $baseQuery->clone()
            ->selectRaw('level, COUNT(*) as total')
            ->groupBy('level')
            ->pluck('total', 'level');

        $summary = [
            TblPrivilege::LEVEL_SERVICE => (int) ($levelCounts[TblPrivilege::LEVEL_SERVICE] ?? 0),
            TblPrivilege::LEVEL_SERVICE_BACHELOR => (int) ($levelCounts[TblPrivilege::LEVEL_SERVICE_BACHELOR] ?? 0),
            TblPrivilege::LEVEL_SERVICE_GRADUATE => (int) ($levelCounts[TblPrivilege::LEVEL_SERVICE_GRADUATE] ?? 0),
            TblPrivilege::LEVEL_DEPT => (int) ($levelCounts[TblPrivilege::LEVEL_DEPT] ?? 0),
            TblPrivilege::LEVEL_SUPER => (int) ($levelCounts[TblPrivilege::LEVEL_SUPER] ?? 0),
        ];
        $summary['total'] = array_sum($summary);

        $privileges = $baseQuery->clone()
            ->with(['user.titleRelation'])
            ->when($levelFilter !== 'all', fn ($q) => $q->where('level', (int) $levelFilter))
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('username', 'like', $like)
                        ->orWhereHas('user', function ($userQuery) use ($like) {
                            $userQuery->where('fname', 'like', $like)
                                ->orWhere('lname', 'like', $like)
                                ->orWhere('userid', 'like', $like)
                                ->orWhere('username', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                });
            })
            ->orderBy('level')
            ->orderBy('username')
            ->paginate(30)
            ->withQueryString();

        $usernames = $privileges->getCollection()->pluck('username')->filter()->all();
        $accessByUser = StaffDepartmentAccess::query()
            ->with('department')
            ->whereIn('username', $usernames)
            ->get()
            ->groupBy('username');

        $privileges->getCollection()->transform(function (TblPrivilege $privilege) use ($accessByUser) {
            $rows = $accessByUser->get($privilege->username, collect());
            $departments = $rows
                ->map(fn (StaffDepartmentAccess $row) => $row->department)
                ->filter()
                ->sortBy('department_name')
                ->values();

            $privilege->setRelation('assignedDepartments', $departments);

            return $privilege;
        });

        return view('faculty-admin.settings.privileges.index', [
            'privileges' => $privileges,
            'departments' => $this->selectableDepartments(),
            'canAssignSuper' => SciGradeRole::canAssignSuperPrivilege(),
            'summary' => $summary,
            'search' => $search,
            'levelFilter' => (string) $levelFilter,
        ]);
    }

    /**
     * สาขาที่ให้เลือกตอนกำหนดสิทธิ์ (ไม่รวมหน่วยงานสนับสนุน / กลุ่มภาระงาน)
     *
     * @return \Illuminate\Support\Collection<int, TblDepartment>
     */
    private function selectableDepartments()
    {
        $excludedIds = [
            4,  // สาขาวิชาวิทยาการคอมพิวเตอร์
            23, // ศูนย์วิจัยนาโนฯ
            24, // หน่วยส่งเสริมและพัฒนาทางวิชาการ
            30, // กลุ่มผู้พัฒนาระบบ
        ];

        return TblDepartment::query()
            ->orderBy('department_name')
            ->get(['department_id', 'department_name'])
            ->reject(function (TblDepartment $dept) use ($excludedIds) {
                $name = (string) $dept->department_name;

                if (str_starts_with($name, 'กลุ่มภาระงาน') || str_starts_with($name, 'งาน')) {
                    return true;
                }

                return in_array((int) $dept->department_id, $excludedIds, true);
            })
            ->values();
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $like = '%'.$q.'%';
        $existingUsernames = TblPrivilege::query()
            ->where('system_id', TblPrivilege::SYSTEM_GRADE_REPORT)
            ->pluck('username');

        $users = TblUser::query()
            ->with('titleRelation')
            ->whereNotIn('username', $existingUsernames)
            ->where(function ($query) use ($like) {
                $query->where('fname', 'like', $like)
                    ->orWhere('lname', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('userid', 'like', $like);
            })
            ->orderBy('fname')
            ->orderBy('lname')
            ->limit(15)
            ->get()
            ->map(fn (TblUser $user) => [
                'username' => $user->username,
                'userid' => $user->userid,
                'display_name' => $user->displayName(),
                'department_id' => (int) ($user->department_id ?? 0),
            ]);

        return response()->json($users);
    }

    public function store(PrivilegeRequest $request): RedirectResponse
    {
        $level = (int) $request->input('level');
        $username = (string) $request->input('username');

        TblPrivilege::query()->create([
            'username' => $username,
            'level' => $level,
            'system_id' => TblPrivilege::SYSTEM_GRADE_REPORT,
        ]);

        $departmentIds = (array) $request->input('department_ids', []);
        $this->departmentAccess->syncForUsername(
            $username,
            $level,
            $departmentIds,
        );

        $this->auditLog->record(
            'privilege.create',
            subjectType: 'privilege',
            subjectId: $username,
            metadata: [
                'level' => $level,
                'department_ids' => $departmentIds,
            ],
        );

        return redirect()
            ->route('faculty-admin.settings.privileges.index')
            ->with('status', 'เพิ่มผู้ใช้งานเรียบร้อย');
    }

    public function update(PrivilegeRequest $request, TblPrivilege $privilege): RedirectResponse
    {
        abort_unless((int) $privilege->system_id === TblPrivilege::SYSTEM_GRADE_REPORT, 404);
        abort_if(
            (int) $privilege->level === TblPrivilege::LEVEL_SUPER && ! SciGradeRole::canAssignSuperPrivilege(),
            403,
            'เฉพาะ Super Admin เท่านั้นที่แก้ไขสิทธิ์ Super Admin ได้',
        );

        $level = (int) $request->input('level');
        $fromLevel = (int) $privilege->level;

        $privilege->update([
            'level' => $level,
        ]);

        $departmentIds = (array) $request->input('department_ids', []);
        $this->departmentAccess->syncForUsername(
            (string) $privilege->username,
            $level,
            $departmentIds,
        );

        $this->auditLog->record(
            'privilege.update',
            subjectType: 'privilege',
            subjectId: $privilege->username,
            metadata: [
                'from_level' => $fromLevel,
                'to_level' => $level,
                'department_ids' => $departmentIds,
            ],
        );

        return redirect()
            ->route('faculty-admin.settings.privileges.index')
            ->with('status', 'บันทึกสิทธิ์เรียบร้อย');
    }

    public function destroy(TblPrivilege $privilege): RedirectResponse
    {
        abort_unless((int) $privilege->system_id === TblPrivilege::SYSTEM_GRADE_REPORT, 404);
        abort_if(
            (int) $privilege->level === TblPrivilege::LEVEL_SUPER && ! SciGradeRole::canAssignSuperPrivilege(),
            403,
            'เฉพาะ Super Admin เท่านั้นที่ลบสิทธิ์ Super Admin ได้',
        );

        $username = (string) $privilege->username;
        $level = (int) $privilege->level;
        $privilege->delete();
        $this->departmentAccess->clearForUsername($username);

        $this->auditLog->record(
            'privilege.delete',
            subjectType: 'privilege',
            subjectId: $username,
            metadata: [
                'level' => $level,
            ],
        );

        return redirect()
            ->route('faculty-admin.settings.privileges.index')
            ->with('status', 'ลบสิทธิ์เรียบร้อย');
    }
}
