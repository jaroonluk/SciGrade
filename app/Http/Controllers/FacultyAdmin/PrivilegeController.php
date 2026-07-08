<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\PrivilegeRequest;
use App\Models\TblDepartment;
use App\Models\TblPrivilege;
use App\Models\TblUser;
use Illuminate\Http\RedirectResponse;
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
            'departments' => TblDepartment::query()->orderBy('department_name')->get(),
        ]);
    }

    public function store(PrivilegeRequest $request): RedirectResponse
    {
        TblPrivilege::query()->create([
            ...$request->validated(),
            'system_id' => TblPrivilege::SYSTEM_GRADE_REPORT,
        ]);

        return redirect()
            ->route('faculty-admin.settings.privileges.index')
            ->with('status', 'เพิ่มผู้ใช้งานเรียบร้อย');
    }

    public function update(PrivilegeRequest $request, TblPrivilege $privilege): RedirectResponse
    {
        abort_unless((int) $privilege->system_id === TblPrivilege::SYSTEM_GRADE_REPORT, 404);

        $privilege->update($request->validated());

        return redirect()
            ->route('faculty-admin.settings.privileges.index')
            ->with('status', 'บันทึกสิทธิ์เรียบร้อย');
    }

    public function destroy(TblPrivilege $privilege): RedirectResponse
    {
        abort_unless((int) $privilege->system_id === TblPrivilege::SYSTEM_GRADE_REPORT, 404);

        $privilege->delete();

        return redirect()
            ->route('faculty-admin.settings.privileges.index')
            ->with('status', 'ลบสิทธิ์เรียบร้อย');
    }

    public function lookupUser(string $username): \Illuminate\Http\JsonResponse
    {
        $user = TblUser::query()->with('titleRelation')->find($username);

        if (! $user) {
            return response()->json(['message' => 'ไม่พบผู้ใช้'], 404);
        }

        return response()->json([
            'username' => $user->username,
            'display_name' => $user->displayName(),
            'department_id' => $user->department_id,
        ]);
    }
}
