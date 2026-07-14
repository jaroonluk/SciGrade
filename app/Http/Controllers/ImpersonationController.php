<?php

namespace App\Http\Controllers;

use App\Models\TblUser;
use App\Services\ImpersonationService;
use App\Support\SciGradeRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ImpersonationController extends Controller
{
    public function __construct(
        private readonly ImpersonationService $impersonation,
    ) {}

    public function index(): View
    {
        return view('super-admin.impersonate', [
            'roles' => [
                SciGradeRole::INSTRUCTOR => SciGradeRole::label(SciGradeRole::INSTRUCTOR),
                SciGradeRole::DEPT_ADMIN => SciGradeRole::label(SciGradeRole::DEPT_ADMIN),
                SciGradeRole::FACULTY_ADMIN => SciGradeRole::label(SciGradeRole::FACULTY_ADMIN),
            ],
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        abort_unless(SciGradeRole::canImpersonate(), 403);

        $q = trim((string) $request->get('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $like = '%'.$q.'%';
        $users = TblUser::query()
            ->with('titleRelation')
            ->where(function ($query) use ($like) {
                $query->where('fname', 'like', $like)
                    ->orWhere('lname', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('userid', 'like', $like)
                    ->orWhere('email', 'like', $like);
            })
            ->orderBy('fname')
            ->orderBy('lname')
            ->limit(15)
            ->get()
            ->map(fn (TblUser $user) => [
                'username' => $user->username,
                'userid' => $user->userid,
                'display_name' => $user->displayName(),
                'email' => $user->email,
                'department_id' => $user->department_id,
            ]);

        return response()->json($users);
    }

    public function start(Request $request): RedirectResponse
    {
        abort_unless(SciGradeRole::canImpersonate(), 403);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'as_role' => ['required', 'in:instructor,dept_admin,faculty_admin'],
        ], [
            'username.required' => 'กรุณาเลือกผู้ใช้งาน',
            'as_role.required' => 'กรุณาเลือกบทบาท',
        ]);

        $staff = TblUser::query()->find($validated['username']);
        abort_unless($staff, 404, 'ไม่พบผู้ใช้งาน');

        try {
            $this->impersonation->start($staff, $validated['as_role']);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('super-admin.impersonate')
                ->withErrors(['impersonate' => $e->getMessage()]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'เข้าใช้งานแทน '.$staff->displayName().' ในบทบาท '.SciGradeRole::label($validated['as_role']).' เรียบร้อย');
    }

    public function stop(): RedirectResponse
    {
        try {
            $this->impersonation->stop();
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('dashboard')
                ->withErrors(['impersonate' => $e->getMessage()]);
        }

        return redirect()
            ->route('super-admin.impersonate')
            ->with('status', 'กลับสู่บัญชี Super Admin เรียบร้อย');
    }
}
