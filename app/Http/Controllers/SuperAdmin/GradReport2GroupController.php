<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\GradReport2GroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GradReport2GroupController extends Controller
{
    public function __construct(
        private readonly GradReport2GroupService $service,
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));
        $focus = strtoupper(trim((string) $request->input('group', '')));

        return view('super-admin.grad-report2-groups.index', [
            'groups' => $this->service->paginateGroups($q !== '' ? $q : null),
            'stats' => $this->service->stats($q !== '' ? $q : null),
            'q' => $q,
            'focusGroup' => $focus,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_code' => ['required', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:255'],
            'member_codes' => ['nullable', 'string', 'max:2000'],
        ]);

        $memberCodes = $this->parseCodes((string) ($validated['member_codes'] ?? ''));

        try {
            $result = $this->service->createGroup(
                $validated['group_code'],
                $validated['subject'],
                $memberCodes,
                $this->username(),
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        $msg = ($result['was_existing'] ? 'เพิ่มรหัสเข้ากลุ่ม ' : 'สร้างกลุ่ม ')
            .$result['group_code']
            .' เรียบร้อย — เพิ่ม '.implode(', ', $result['inserted']);

        return redirect()
            ->route('super-admin.grad-report2-groups.index', [
                'q' => $request->input('q'),
                'group' => $result['group_code'],
            ])
            ->with('status', $msg);
    }

    public function updateGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_code' => ['required', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->service->updateGroupSubject($validated['group_code'], $validated['subject']);
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('super-admin.grad-report2-groups.index', [
                'q' => $request->input('q'),
                'group' => $validated['group_code'],
            ])
            ->with('status', 'อัปเดตชื่อวิชากลุ่ม '.$validated['group_code'].' เรียบร้อย');
    }

    public function destroyGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_code' => ['required', 'string', 'max:20'],
        ]);

        try {
            $count = $this->service->deleteGroup($validated['group_code']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('super-admin.grad-report2-groups.index', ['q' => $request->input('q')])
            ->with('status', 'ลบกลุ่ม '.$validated['group_code'].' ('.$count.' รายการ) เรียบร้อย');
    }

    public function storeMember(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_code' => ['required', 'string', 'max:20'],
            'subject_code' => ['required', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->service->addMember(
                $validated['group_code'],
                $validated['subject_code'],
                $validated['subject'] ?? null,
                $this->username(),
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()
            ->route('super-admin.grad-report2-groups.index', [
                'q' => $request->input('q'),
                'group' => $validated['group_code'],
            ])
            ->with('status', 'เพิ่มรหัส '.$validated['subject_code'].' เข้ากลุ่ม '.$validated['group_code'].' เรียบร้อย');
    }

    public function updateMember(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_code' => ['required', 'string', 'max:20'],
            'subject_code' => ['required', 'string', 'max:20'],
            'new_subject_code' => ['required', 'string', 'max:20'],
            'subject' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->service->updateMember(
                $validated['group_code'],
                $validated['subject_code'],
                $validated['new_subject_code'],
                $validated['subject'],
            );
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        $focus = $validated['group_code'];
        if (strcasecmp(trim($validated['subject_code']), trim($validated['group_code'])) === 0
            && strcasecmp(trim($validated['new_subject_code']), trim($validated['group_code'])) !== 0) {
            $focus = $validated['new_subject_code'];
        }

        return redirect()
            ->route('super-admin.grad-report2-groups.index', [
                'q' => $request->input('q'),
                'group' => $focus,
            ])
            ->with('status', 'แก้ไขรหัสวิชาเรียบร้อย');
    }

    public function destroyMember(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group_code' => ['required', 'string', 'max:20'],
            'subject_code' => ['required', 'string', 'max:20'],
        ]);

        try {
            $this->service->removeMember($validated['group_code'], $validated['subject_code']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('super-admin.grad-report2-groups.index', [
                'q' => $request->input('q'),
                'group' => $validated['group_code'],
            ])
            ->with('status', 'ลบรหัส '.$validated['subject_code'].' ออกจากกลุ่มเรียบร้อย');
    }

    /**
     * @return list<string>
     */
    private function parseCodes(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r", ';', '|'], [',', ',', ',', ','], $raw);
        $parts = preg_split('/[\s,]+/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $parts)));
    }

    private function username(): string
    {
        return (string) session('staff_username', '');
    }
}
