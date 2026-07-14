<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\PrivilegeRequest;
use App\Models\TblPrivilege;
use App\Models\TblUser;
use App\Support\SciGradeRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrivilegeController extends Controller
{
    public function index(): View
    {
        $privileges = TblPrivilege::query()
            ->with(['user.titleRelation'])
            ->where('system_id', TblPrivilege::SYSTEM_GRADE_REPORT)
            ->orderBy('level')
            ->orderBy('username')
            ->paginate(30);

        return view('faculty-admin.settings.privileges.index', [
            'privileges' => $privileges,
            'canAssignSuper' => SciGradeRole::canAssignSuperPrivilege(),
        ]);
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
            ]);

        return response()->json($users);
    }

    public function store(PrivilegeRequest $request): RedirectResponse
    {
        $level = (int) $request->input('level');

        TblPrivilege::query()->create([
            'username' => $request->input('username'),
            'level' => $level,
            'system_id' => TblPrivilege::SYSTEM_GRADE_REPORT,
        ]);

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

        $privilege->update([
            'level' => $level,
        ]);

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

        $privilege->delete();

        return redirect()
            ->route('faculty-admin.settings.privileges.index')
            ->with('status', 'ลบสิทธิ์เรียบร้อย');
    }
}
