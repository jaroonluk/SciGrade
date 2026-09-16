<?php

namespace App\Http\Controllers;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\GradeReportReg;
use App\Models\GradeStd;
use App\Models\GradReport2;
use App\Models\TblUser;
use App\Services\AuditLogService;
use App\Services\GradReport2Service;
use App\Services\Instructor\InstructorPendingRegistrarService;
use App\Services\StaffAuthService;
use App\Support\GradeReportRemarks;
use App\Support\SubjectDegree;
use App\Support\ThesisCourse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class GradeReportController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly GradReport2Service $gradReport2,
        private readonly AuditLogService $auditLog,
        private readonly InstructorPendingRegistrarService $pendingRegistrar,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = GradeReport::query()
            ->examReportable()
            ->with('gradeStds')
            ->when($request->filled('approv'), fn ($q) => $q->where('approv', $request->integer('approv')))
            ->when($request->filled('term'), fn ($q) => $q->where('term', (string) $request->integer('term')))
            ->when($request->filled('year'), fn ($q) => $q->where('year', (string) $request->integer('year')))
            ->orderByDesc('created_stamp')
            ->orderByDesc('grade_id');

        if ($request->input('role', 'instructor') === 'instructor') {
            $query->filledByInstructor($this->staffUsername());
        }

        return response()->json($query->get()->map(fn (GradeReport $r) => $this->formatReport($r)));
    }

    public function show(Request $request, GradeReport $gradeReport): JsonResponse
    {
        abort_if(ThesisCourse::isThesisSubject((string) $gradeReport->subject_code, (string) $gradeReport->subject), 404);

        if ($request->input('role', 'instructor') === 'instructor') {
            abort_unless($gradeReport->instructorCanManage($this->staffUsername()), 403);
        }

        return response()->json($this->formatReport($gradeReport->load('gradeStds')));
    }

    public function courseContext(Request $request): JsonResponse
    {
        $code = GradReport2::normalizeCode((string) $request->query('subject_code', ''));
        $term = (int) $request->query('term', 0);
        $year = (int) $request->query('year', 0);
        $exclude = (int) $request->query('exclude', 0);

        if ($code !== '' && ThesisCourse::isThesisSubject($code)) {
            return response()->json([
                'exam_reportable' => false,
                'message' => ThesisCourse::EXAM_BLOCK_MESSAGE,
                'grouped' => false,
                'members' => [],
                'prior' => null,
                'reported_sections' => [],
                'available_sections' => range(1, 20),
                'available_sections_from_reg' => false,
            ]);
        }

        $members = $code !== '' ? $this->gradReport2->groupMembersForSubject($code) : [];
        $availableSections = $this->resolveAvailableSections($code, $term, $year);

        $prior = null;
        $priorRemarks = null;
        $reportedSections = [];
        $sectionDetails = [];

        if ($code !== '' && $term > 0 && $year > 0) {
            $reports = GradeReport::query()
                ->examReportable()
                ->with('gradeStds')
                ->whereRaw(GradReport2::normalizedCodeSql('subject_code').' = ?', [$code])
                ->where('term', (string) $term)
                ->where('year', (string) $year)
                ->when($exclude > 0, fn ($q) => $q->where('grade_id', '!=', $exclude))
                ->orderBy('created_stamp')
                ->orderBy('grade_id')
                ->get();

            foreach ($reports as $report) {
                foreach ($report->gradeStds as $std) {
                    $sec = (int) $std->sec;
                    if ($sec <= 0) {
                        continue;
                    }
                    $reportedSections[$sec] = true;
                    if (! isset($sectionDetails[$sec])) {
                        $stdUsername = trim((string) ($std->getAttributes()['username'] ?? $std->username ?? ''));
                        if ($stdUsername === '') {
                            $stdUsername = trim((string) $report->username);
                        }
                        $sectionDetails[$sec] = [
                            'sec' => $sec,
                            'filled_by' => $this->resolveStaffDisplayName($stdUsername)
                                ?: $this->resolveReportFillerName($report),
                            'username' => $stdUsername,
                            'grade_id' => $report->grade_id,
                            'subject_code' => trim((string) $report->subject_code),
                            'subject' => trim((string) $report->subject),
                            'is_mine' => $stdUsername !== '' && $stdUsername === $this->staffUsername(),
                        ];
                    }
                }
            }

            $priorRemarks = $this->aggregatePriorRemarks($reports);

            $first = $reports->first();
            if ($first) {
                $filledBy = $this->resolveReportFillerName($first);
                $teacherNames = $this->collectTeacherNames($reports);
                $ownedByMe = $this->ownsReport($first);
                $canAppend = $ownedByMe || $this->canContribute($first);
                $prior = [
                    'exists' => true,
                    'grade_id' => $first->grade_id,
                    'teacher' => implode(', ', $teacherNames),
                    'teachers' => $teacherNames,
                    'filled_by' => $filledBy,
                    'username' => $first->username,
                    'subject_code' => trim((string) $first->subject_code),
                    'subject' => trim((string) $first->subject),
                    'term' => (int) $first->term,
                    'year' => (int) $first->year,
                    'term_label' => $first->termLabel(),
                    'statuseva' => (int) $first->statuseva,
                    'payload' => $this->formPayload($first),
                    'remarks' => $priorRemarks,
                    'owned_by_me' => $ownedByMe,
                    'can_append' => $canAppend,
                    'approv' => (int) $first->approv,
                ];
            }
        }

        ksort($sectionDetails);

        return response()->json([
            'grouped' => $members !== [],
            'members' => $members,
            'prior' => $prior,
            'prior_remarks' => $priorRemarks ?? [
                'joint_line' => null,
                'i_entries' => [],
                'other_entries' => [],
                'flags' => 0,
            ],
            'reported_sections' => array_values(array_map('intval', array_keys($reportedSections))),
            'reported_section_details' => array_values($sectionDetails),
            'available_sections' => $availableSections['sections'],
            'available_sections_from_reg' => $availableSections['from_reg'],
        ]);
    }

    /**
     * Section ที่เปิดจริงจาก grade_report_reg — ถ้าไม่มีรายวิชาในรายการให้ใช้ 1–20
     *
     * @return array{sections: list<int>, from_reg: bool}
     */
    private function resolveAvailableSections(string $code, int $term, int $year): array
    {
        $fallback = range(1, 20);

        if ($code === '' || $term <= 0 || $year <= 0) {
            return ['sections' => $fallback, 'from_reg' => false];
        }

        try {
            $sections = GradeReportReg::query()
                ->whereRaw(
                    "UPPER(REPLACE(REPLACE(TRIM(`COURSECODE`), ' ', ''), UNHEX('C2A0'), '')) = ?",
                    [$code],
                )
                ->where(function ($query) use ($year) {
                    $query->where('ACADYEAR', (string) $year)
                        ->orWhere('ACADYEAR', $year)
                        ->orWhereRaw('CAST(ACADYEAR AS UNSIGNED) = ?', [$year]);
                })
                ->where(function ($query) use ($term) {
                    $query->where('SEMESTER', (string) $term)
                        ->orWhere('SEMESTER', $term)
                        ->orWhereRaw('CAST(SEMESTER AS UNSIGNED) = ?', [$term])
                        ->orWhere('SEMESTER', str_pad((string) $term, 2, '0', STR_PAD_LEFT));
                })
                ->pluck('SECTION')
                ->map(function ($raw) {
                    $digits = preg_replace('/\D+/', '', (string) $raw) ?? '';

                    return (int) $digits;
                })
                ->filter(fn (int $sec) => $sec > 0)
                ->unique()
                ->sort()
                ->values()
                ->all();
        } catch (Throwable $e) {
            Log::warning('resolveAvailableSections failed', [
                'code' => $code,
                'term' => $term,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);

            return ['sections' => $fallback, 'from_reg' => false];
        }

        if ($sections === []) {
            return ['sections' => $fallback, 'from_reg' => false];
        }

        return ['sections' => $sections, 'from_reg' => true];
    }

    /**
     * @param  Collection<int, GradeReport>  $reports
     * @return array{joint_line: ?string, i_entries: list<string>, other_entries: list<string>, flags: int}
     */
    private function aggregatePriorRemarks(Collection $reports): array
    {
        $jointLine = null;
        $iEntries = [];
        $otherEntries = [];
        $flags = 0;

        foreach ($reports as $report) {
            $parsed = GradeReportRemarks::parse($report->reason, $report->reasonid);
            if ($jointLine === null && $parsed['joint_line']) {
                $jointLine = $parsed['joint_line'];
            }
            foreach ($parsed['i_entries'] as $entry) {
                $iEntries[] = $entry;
            }
            foreach ($parsed['other_entries'] as $entry) {
                $otherEntries[] = $entry;
            }
            $flags |= $parsed['flags'];
        }

        $iEntries = array_values(array_unique($iEntries));
        $otherEntries = array_values(array_unique($otherEntries));
        if ($jointLine) {
            $flags |= GradeReportRemarks::FLAG_JOINT;
        }
        if ($iEntries !== []) {
            $flags |= GradeReportRemarks::FLAG_I;
        }
        if ($otherEntries !== []) {
            $flags |= GradeReportRemarks::FLAG_OTHER;
        }

        return [
            'joint_line' => $jointLine,
            'i_entries' => $iEntries,
            'other_entries' => $otherEntries,
            'flags' => $flags,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formPayload(GradeReport $gradeReport, ?string $forUsername = null): array
    {
        $payload = $this->formatReport($gradeReport->loadMissing('gradeStds'));
        $staff = trim((string) $forUsername);
        if ($staff === '' || $gradeReport->instructorOwns($staff)) {
            return $payload;
        }

        $payload['grade_stds'] = array_values(array_filter(
            $payload['grade_stds'] ?? [],
            function ($row) use ($gradeReport, $staff) {
                $id = (int) ($row['id'] ?? $row['grade_std_id'] ?? 0);
                $std = $gradeReport->gradeStds->firstWhere('grade_std_id', $id);

                return $std instanceof GradeStd && $std->filledBy($staff, $gradeReport);
            },
        ));

        return $payload;
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $data = $this->validateReport($request);
            unset($data['append_sections']);
            $this->assertExamReportable($data);

            $existing = $this->findExistingCourseReport($data);
            if ($existing) {
                abort_unless(
                    $this->ownsReport($existing) || $this->canContribute($existing),
                    403,
                    'ไม่มีสิทธิ์เพิ่ม Section ในรายงานวิชานี้ (อาจอนุมัติแล้วหรือรอสาขาดำเนินการ)'
                );

                return $this->appendSectionsToReport($request, $existing, $data);
            }

            $report = DB::connection('scigrad')->transaction(function () use ($data, $request) {
                $stds = $data['grade_stds'];
                unset($data['grade_stds'], $data['grade_std']);

                $data = $this->applyGradReport2Rules($data);

                $report = GradeReport::query()->create(
                    $this->prepareReportAttributes($data, $request, isCreate: true)
                );

                $this->syncGradeStds($report, $stds);

                return $report->load('gradeStds');
            });

            // ไฟล์ REG ใน session จะถูกแนบเมื่อทำ wizard ครบ (finalize-wizard) เท่านั้น

            $this->auditLog->record(
                'grade_report.create',
                subjectType: 'grade_report',
                subjectId: $report->grade_id,
                metadata: [
                    'subject_code' => $report->subject_code,
                    'term' => $report->term,
                    'year' => $report->year,
                ],
            );

            return response()->json($this->formatReport($report), 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->saveFailureResponse($e, 'create');
        }
    }

    public function update(Request $request, GradeReport $gradeReport): JsonResponse
    {
        try {
            if ($request->has('approv')) {
                return $this->updateApproval($request, $gradeReport);
            }

            if ($request->boolean('append_sections') || ! $this->ownsReport($gradeReport)) {
                abort_unless(
                    $this->ownsReport($gradeReport) || $this->canContribute($gradeReport),
                    403,
                    'ไม่มีสิทธิ์เพิ่ม Section ในรายงานวิชานี้ (อาจอนุมัติแล้วหรือรอสาขาดำเนินการ)'
                );
                $data = $this->validateReport($request, updating: true);
                unset($data['append_sections']);
                $this->assertExamReportable($data, $gradeReport);

                if (! $this->ownsReport($gradeReport) && ! $request->boolean('append_sections')) {
                    return $this->syncContributorSections($request, $gradeReport, $data);
                }

                return $this->appendSectionsToReport($request, $gradeReport, $data);
            }

            abort_unless($this->ownsReport($gradeReport), 403, 'ไม่มีสิทธิ์แก้ไขรายงานของผู้อื่น');

            if ((int) $gradeReport->approv > 0) {
                return response()->json(['message' => 'ไม่สามารถแก้ไขรายการที่อนุมัติแล้ว'], 422);
            }

            if ($gradeReport->awaitingDeptResubmit()) {
                return response()->json(['message' => 'รายการส่งการแก้ไขแล้ว รอสาขาวิชาดำเนินการ'], 422);
            }

            $data = $this->validateReport($request, updating: true);
            $this->assertExamReportable($data, $gradeReport);
            $stds = $data['grade_stds'] ?? null;
            unset($data['grade_stds'], $data['grade_std']);

            DB::connection('scigrad')->transaction(function () use ($gradeReport, $data, $stds, $request) {
                $data = $this->applyGradReport2Rules($data);
                $mergedRemarks = GradeReportRemarks::merge(
                    $gradeReport->reason,
                    $gradeReport->reasonid !== null ? (int) $gradeReport->reasonid : null,
                    $data['reason'] ?? null,
                    isset($data['reasonid']) ? (int) $data['reasonid'] : null,
                );
                $data['reason'] = $mergedRemarks['reason'];
                $data['reasonid'] = $mergedRemarks['reasonid'];

                $gradeReport->update($this->prepareReportAttributes($data, $request, isCreate: false));

                if ($stds !== null) {
                    $this->syncGradeStds($gradeReport, $stds);
                }
            });

            // ไฟล์ REG ใน session จะถูกแนบเมื่อทำ wizard ครบ (finalize-wizard) เท่านั้น

            $this->auditLog->record(
                'grade_report.update',
                subjectType: 'grade_report',
                subjectId: $gradeReport->grade_id,
                metadata: [
                    'subject_code' => $gradeReport->subject_code,
                    'term' => $gradeReport->term,
                    'year' => $gradeReport->year,
                ],
            );

            return response()->json($this->formatReport($gradeReport->fresh('gradeStds')));
        } catch (ValidationException $e) {
            throw $e;
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->saveFailureResponse($e, 'update');
        }
    }

    public function destroy(Request $request, GradeReport $gradeReport): JsonResponse
    {
        abort_unless($this->ownsReport($gradeReport), 403);

        if ((int) $gradeReport->approv > 0) {
            return response()->json(['message' => 'ไม่สามารถลบรายการที่อนุมัติแล้ว'], 422);
        }

        $meta = [
            'subject_code' => $gradeReport->subject_code,
            'term' => $gradeReport->term,
            'year' => $gradeReport->year,
        ];
        $gradeId = $gradeReport->grade_id;
        $subjectCode = (string) $gradeReport->subject_code;
        $term = $gradeReport->term;
        $year = $gradeReport->year;

        DB::connection('scigrad')->transaction(function () use ($gradeReport) {
            $gradeReport->loadMissing('files');
            foreach ($gradeReport->files as $file) {
                $file->delete();
            }
            $gradeReport->gradeStds()->delete();
            $gradeReport->delete();
        });

        // ลบไฟล์ มข.11 ที่ค้างใน session ของวิชานี้ด้วย กันแสดง/แนบไฟล์เก่าตอนกรอกใหม่
        $this->pendingRegistrar->forgetMatchingCourse($subjectCode, $term, $year);

        $this->auditLog->record(
            'grade_report.delete',
            subjectType: 'grade_report',
            subjectId: $gradeId,
            metadata: $meta,
        );

        return response()->json([
            'ok' => true,
            'cleared_subject' => $subjectCode,
            'cleared_term' => $term,
            'cleared_year' => $year,
        ]);
    }

    public function destroySection(Request $request, GradeReport $gradeReport, GradeStd $gradeStd): JsonResponse
    {
        abort_unless((int) $gradeStd->grade_id === (int) $gradeReport->grade_id, 404);
        abort_unless($gradeReport->canEdit(), 403, 'ไม่สามารถแก้ไขรายการนี้ได้');

        $username = $this->staffUsername();
        abort_unless(
            $this->ownsReport($gradeReport) || $gradeStd->filledBy($username, $gradeReport),
            403,
            'ไม่มีสิทธิ์ลบ Section นี้'
        );

        $sec = (int) $gradeStd->sec;
        $stdId = (int) $gradeStd->grade_std_id;

        DB::connection('scigrad')->transaction(function () use ($gradeReport, $gradeStd, $sec) {
            $gradeStd->delete();
            $this->pendingRegistrar->deleteInstructorRegistrarForSection($gradeReport, $sec);
        });

        $this->auditLog->record(
            'grade_report.delete_section',
            subjectType: 'grade_std',
            subjectId: $stdId,
            metadata: [
                'grade_id' => $gradeReport->grade_id,
                'subject_code' => $gradeReport->subject_code,
                'sec' => $sec,
            ],
        );

        return response()->json([
            'ok' => true,
            'grade_id' => $gradeReport->grade_id,
            'deleted_section' => $sec,
        ]);
    }

    private function updateApproval(Request $request, GradeReport $gradeReport): JsonResponse
    {
        $validated = $request->validate([
            'approv' => ['required', 'integer', 'in:-1,0,1,2'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'role' => ['required', 'in:dept_admin,faculty_admin,super_admin'],
        ]);

        $today = now()->toDateString();

        if ($validated['approv'] === -1) {
            $gradeReport->update([
                'approv' => -1,
                'dateapprove2' => $today,
                'reason' => $validated['rejection_reason'] ?? $gradeReport->reason,
            ]);
        } elseif ($validated['approv'] === 0) {
            $gradeReport->update([
                'approv' => 0,
                'dateapprove1' => null,
                'dateapprove2' => null,
            ]);
        } elseif ($validated['role'] === 'dept_admin' && $validated['approv'] === 1) {
            $gradeReport->update([
                'approv' => 1,
                'dateapprove1' => $today,
            ]);
        } elseif (in_array($validated['role'], ['faculty_admin', 'super_admin'], true) && $validated['approv'] === 2) {
            $gradeReport->update([
                'approv' => 2,
                'dateapprove2' => $today,
            ]);
        } else {
            return response()->json(['message' => 'ไม่สามารถอนุมัติรายการนี้ได้'], 422);
        }

        return response()->json($this->formatReport($gradeReport->fresh('gradeStds')));
    }

    private function staffUsername(): string
    {
        $username = session('staff_username');

        if (empty($username) && auth()->user()) {
            $staff = $this->staffAuth->findByEmail(auth()->user()->email);
            if ($staff) {
                $this->staffAuth->storeInSession($staff);
                $username = $staff->username;
            }
        }

        abort_if(empty($username), 403, 'ไม่พบข้อมูลบุคลากรในระบบ');

        return (string) $username;
    }

    private function ownsReport(GradeReport $report): bool
    {
        return trim((string) $report->username) === trim($this->staffUsername());
    }

    private function saveFailureResponse(Throwable $e, string $action): JsonResponse
    {
        Log::error('grade_report.'.$action.' failed', [
            'message' => $e->getMessage(),
            'exception' => $e::class,
        ]);

        if ($e instanceof QueryException) {
            $sqlMessage = (string) ($e->errorInfo[2] ?? $e->getMessage());
            $lower = strtolower($sqlMessage);

            if (str_contains($lower, 'too many connections')) {
                return response()->json([
                    'message' => 'ฐานข้อมูลเชื่อมต่อไม่สำเร็จ',
                    'hint' => 'เซิร์ฟเวอร์ฐานข้อมูลเต็มจำนวนการเชื่อมต่อชั่วคราว กรุณารอสักครู่แล้วลองใหม่',
                ], 503);
            }

            if (str_contains($lower, 'unknown column')) {
                return response()->json([
                    'message' => 'โครงสร้างฐานข้อมูลยังไม่พร้อมบันทึกรายงานผลสอบ',
                    'hint' => 'คอลัมน์บางตัวยังไม่มีในตาราง grade_report / grade_std / grad_report2 — แจ้งผู้ดูแลระบบให้รัน migration หรือ ALTER TABLE',
                ], 500);
            }

            if (str_contains($lower, 'data too long') || str_contains($lower, 'data truncated')) {
                return response()->json([
                    'message' => 'ข้อมูลบางช่องยาวเกินที่ฐานข้อมูลรองรับ',
                    'hint' => 'ตรวจชื่อวิชา / หมายเหตุตัดเกรดร่วม / คณะในขั้นตอนที่ 4 ให้สั้นลง หรือลดจำนวนคณะที่เลือกใน Section เดียว',
                ], 422);
            }

            return response()->json([
                'message' => 'บันทึกลงฐานข้อมูลไม่สำเร็จ',
                'hint' => 'ตรวจข้อมูลขั้นตอนที่ 1–5 ให้ครบ (วิชา ช่วงคะแนน จำนวนนักศึกษา/คณะ) แล้วลองใหม่ หากยังไม่หายให้แจ้งผู้ดูแลระบบ',
            ], 500);
        }

        return response()->json([
            'message' => 'บันทึกไม่สำเร็จ เกิดข้อผิดพลาดภายในระบบ',
            'hint' => 'ลองรีเฟรชหน้าแล้วบันทึกอีกครั้ง หรือแจ้งผู้ดูแลระบบพร้อมรหัสวิชา/ภาค/ปีที่กำลังกรอก',
        ], 500);
    }

    private function canContribute(GradeReport $report): bool
    {
        return (int) $report->approv <= 0 && ! $report->awaitingDeptResubmit();
    }

    private function resolveReportFillerName(GradeReport $report): string
    {
        $fromStaff = $this->resolveStaffDisplayName(trim((string) $report->username));
        if ($fromStaff !== null) {
            return $fromStaff;
        }

        $teacher = trim((string) $report->teacher);
        if ($teacher !== '') {
            $parts = preg_split('/[,;\/]+/u', $teacher) ?: [];
            $first = trim((string) ($parts[0] ?? ''));
            if ($first !== '') {
                return $first;
            }
        }

        $username = trim((string) $report->username);

        return $username !== '' ? $username : 'ผู้กรอกก่อนหน้า';
    }

    private function resolveStaffDisplayName(?string $username): ?string
    {
        $username = trim((string) $username);
        if ($username === '') {
            return null;
        }

        try {
            $staff = TblUser::query()->with('titleRelation')->find($username);
            $display = $staff?->displayName();
            if (is_string($display) && trim($display) !== '') {
                return trim($display);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * ภาพรวม Section + ไฟล์แนบ สำหรับขั้นตอนอัปโหลดใบขวาง
     */
    public function sectionBoard(GradeReport $gradeReport): JsonResponse
    {
        abort_unless($this->ownsReport($gradeReport) || $this->canContribute($gradeReport), 403);

        $gradeReport->loadMissing(['gradeStds', 'files']);
        $me = $this->staffUsername();
        $reportCanEdit = $gradeReport->canUploadFiles();
        $sections = [];

        foreach ($gradeReport->gradeStds->sortBy(fn ($row) => (int) $row->sec) as $std) {
            $sec = (int) $std->sec;
            if ($sec <= 0) {
                continue;
            }
            $stdUsername = trim((string) ($std->getAttributes()['username'] ?? $std->username ?? ''));
            if ($stdUsername === '') {
                $stdUsername = trim((string) $gradeReport->username);
            }
            $isMine = $stdUsername !== '' && $stdUsername === $me;
            $sections[$sec] = [
                'sec' => $sec,
                'fac' => trim((string) ($std->fac ?? '')),
                'filled_by' => $this->resolveStaffDisplayName($stdUsername)
                    ?: $this->resolveReportFillerName($gradeReport),
                'username' => $stdUsername,
                'is_mine' => $isMine,
                'can_manage' => $isMine && $reportCanEdit,
                'registrar' => null,
                'exam' => null,
                'exam_files' => [],
            ];
        }

        $examPacketsMap = [];

        foreach ($gradeReport->files->sortBy('file_id') as $file) {
            $type = $file->resolvedType();
            $sec = $file->resolvedSection($gradeReport);
            $uploader = trim((string) ($file->username ?? ''));
            $uploaderName = $this->resolveStaffDisplayName($uploader) ?: ($uploader !== '' ? $uploader : null);
            $canDelete = $reportCanEdit && (
                ($uploader !== '' && $uploader === $me)
                || ($uploader === '' && $this->ownsReport($gradeReport))
            );
            $uploadedAt = $file->uploaded_at
                ? $file->uploaded_at->timezone(config('app.timezone'))->format('d/m/Y H:i')
                : null;
            $payload = [
                'file_id' => (int) $file->file_id,
                'name' => $type === GradeReportFile::TYPE_REGISTRAR
                    ? (string) ($file->registrarDisplayName($gradeReport) ?: $file->original_name)
                    : (string) $file->original_name,
                'view_url' => route('grade-reports.files.show', [
                    'gradeReport' => $gradeReport->grade_id,
                    'file' => $file->file_id,
                ]),
                'username' => $uploader,
                'uploaded_by' => $uploaderName,
                'uploaded_at' => $uploadedAt,
                'section' => $sec !== null && (int) $sec > 0 ? (int) $sec : null,
                'can_delete' => $canDelete,
            ];

            if ($type === GradeReportFile::TYPE_REGISTRAR) {
                if ($sec === null || (int) $sec <= 0) {
                    continue;
                }
                $n = (int) $sec;
                if (! isset($sections[$n])) {
                    $sections[$n] = [
                        'sec' => $n,
                        'fac' => '',
                        'filled_by' => $uploaderName ?: 'ผู้กรอกก่อนหน้า',
                        'username' => $uploader,
                        'is_mine' => $uploader === $me,
                        'can_manage' => $uploader === $me && $reportCanEdit,
                        'registrar' => null,
                        'exam' => null,
                        'exam_files' => [],
                    ];
                }
                $sections[$n]['registrar'] = $payload;
            } elseif ($type === GradeReportFile::TYPE_EXAM_REPORT) {
                // ใบขวางผูกตาม Section ในชื่อไฟล์ — เก็บทุกใบเพื่อตรวจสอบได้
                $n = ($sec !== null && (int) $sec > 0) ? (int) $sec : null;
                if ($n !== null) {
                    if (! isset($sections[$n])) {
                        $sections[$n] = [
                            'sec' => $n,
                            'fac' => '',
                            'filled_by' => $uploaderName ?: 'ผู้กรอกก่อนหน้า',
                            'username' => $uploader,
                            'is_mine' => $uploader === $me,
                            'can_manage' => $uploader === $me && $reportCanEdit,
                            'registrar' => null,
                            'exam' => null,
                            'exam_files' => [],
                        ];
                    }
                    $sections[$n]['exam_files'][] = $payload;
                    $sections[$n]['exam'] = $payload; // ใบล่าสุดของกลุ่มนี้
                }

                $stamp = $file->uploaded_at
                    ? $file->uploaded_at->format('Y-m-d H:i:s')
                    : ('id-'.$file->file_id);
                $packetKey = ($uploader !== '' ? $uploader : 'unknown').'|'.$stamp;
                if (! isset($examPacketsMap[$packetKey])) {
                    $examPacketsMap[$packetKey] = [
                        'packet_key' => $packetKey,
                        'uploaded_by' => $uploaderName ?: ($uploader !== '' ? $uploader : 'ไม่ระบุ'),
                        'username' => $uploader,
                        'uploaded_at' => $uploadedAt,
                        'sections' => [],
                        'files' => [],
                        'can_delete' => $canDelete,
                        'view_url' => $payload['view_url'],
                        'name' => $payload['name'],
                    ];
                }
                if ($n !== null) {
                    $examPacketsMap[$packetKey]['sections'][$n] = $n;
                }
                $examPacketsMap[$packetKey]['files'][] = $payload;
                $examPacketsMap[$packetKey]['can_delete'] = $examPacketsMap[$packetKey]['can_delete'] && $canDelete;
                // ใช้ไฟล์แรกของชุดเป็นตัวแทนเปิดดู
                if (count($examPacketsMap[$packetKey]['files']) === 1) {
                    $examPacketsMap[$packetKey]['view_url'] = $payload['view_url'];
                    $examPacketsMap[$packetKey]['name'] = $payload['name'];
                }
            }
        }

        foreach ($sections as &$row) {
            $row['exam_files'] = array_values($row['exam_files']);
            $row['docs_complete'] = $row['registrar'] !== null && $row['exam'] !== null;
        }
        unset($row);

        ksort($sections);

        $examPackets = array_values(array_map(function (array $packet) use ($sections) {
            $secs = array_values($packet['sections']);
            sort($secs);
            $packet['sections'] = $secs;
            $packet['section_label'] = $secs === []
                ? '—'
                : implode(', ', array_map(fn (int $s) => (string) $s, $secs));
            $packet['registrar_count'] = count(array_filter(
                $secs,
                fn (int $s) => isset($sections[$s]) && $sections[$s]['registrar'] !== null,
            ));
            $packet['label'] = 'แบบรายงานผลการสอบไล่ (ใบขวาง)';
            if (count($packet['files']) > 1) {
                $packet['label'] .= ' · '.count($packet['files']).' กลุ่ม';
            }

            return $packet;
        }, $examPacketsMap));

        // ใหม่สุดอยู่บน
        usort($examPackets, function (array $a, array $b) {
            return ((int) ($b['files'][0]['file_id'] ?? 0)) <=> ((int) ($a['files'][0]['file_id'] ?? 0));
        });

        $available = $this->resolveAvailableSections(
            GradReport2::normalizeCode((string) $gradeReport->subject_code),
            (int) $gradeReport->term,
            (int) $gradeReport->year,
        );

        return response()->json([
            'grade_id' => $gradeReport->grade_id,
            'current_username' => $me,
            'report_can_edit' => $reportCanEdit,
            'approv' => (int) $gradeReport->approv,
            'sections' => array_values($sections),
            'exam_packets' => $examPackets,
            'available_sections' => $available['sections'],
            'available_sections_from_reg' => $available['from_reg'],
            'subject_code' => trim((string) $gradeReport->subject_code),
            'term' => (int) $gradeReport->term,
            'year' => (int) $gradeReport->year,
        ]);
    }

    /**
     * @param  list<int>  $sections
     */
    private function duplicateSectionMessage(GradeReport $report, array $sections): string
    {
        $code = trim((string) $report->subject_code) ?: '-';
        $name = trim((string) $report->subject) ?: '-';
        $secLabel = implode(', ', array_map(fn (int $sec) => (string) $sec, $sections));
        $filledBy = $this->resolveReportFillerName($report);

        return "รหัสวิชา {$code} ชื่อวิชา {$name} Section {$secLabel} ได้มีการบันทึกผลการส่งเกรดแล้ว กรุณาติดต่อ {$filledBy}";
    }

    /**
     * @param  list<int>  $sections
     */
    private function duplicateOwnSectionMessage(GradeReport $report, array $sections): string
    {
        $code = trim((string) $report->subject_code) ?: '-';
        $name = trim((string) $report->subject) ?: '-';
        $secLabel = implode(', ', array_map(fn (int $sec) => (string) $sec, $sections));
        $gradeId = $report->grade_id;

        return "รหัสวิชา {$code} ชื่อวิชา {$name} Section {$secLabel} มีอยู่ในรายงานของท่านแล้ว (เลขที่ {$gradeId}) — กรุณาเปิดแก้ไขรายงานเดิมแทนการสร้างใหม่";
    }

    /**
     * @param  Collection<int, GradeReport>  $reports
     * @return list<string>
     */
    private function collectTeacherNames($reports): array
    {
        $names = [];
        foreach ($reports as $report) {
            foreach (preg_split('/[,;\/]+/u', (string) $report->teacher) ?: [] as $name) {
                $name = trim($name);
                if ($name === '') {
                    continue;
                }
                $names[$name] = $name;
            }
        }

        return array_values($names);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findExistingCourseReport(array $data): ?GradeReport
    {
        $code = GradReport2::normalizeCode((string) ($data['subject_code'] ?? ''));
        $term = (int) ($data['term'] ?? 0);
        $year = (int) ($data['year'] ?? 0);
        if ($code === '' || $term < 1 || $year < 1) {
            return null;
        }

        return GradeReport::query()
            ->examReportable()
            ->whereRaw(GradReport2::normalizedCodeSql('subject_code').' = ?', [$code])
            ->where('term', (string) $term)
            ->where('year', (string) $year)
            ->orderBy('created_stamp')
            ->orderBy('grade_id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function appendSectionsToReport(Request $request, GradeReport $report, array $data): JsonResponse
    {
        if ((int) $report->approv > 0) {
            return response()->json(['message' => 'รายวิชานี้มีรายงานที่อนุมัติแล้ว ไม่สามารถเพิ่ม Section ได้'], 422);
        }
        if ($report->awaitingDeptResubmit()) {
            return response()->json(['message' => 'รายการส่งการแก้ไขแล้ว รอสาขาวิชาดำเนินการ'], 422);
        }

        $stds = $data['grade_stds'] ?? [];
        if ($stds === []) {
            return response()->json(['message' => 'กรุณาเพิ่มข้อมูลจำนวนนักศึกษาอย่างน้อย 1 Section'], 422);
        }

        $existingSecs = $report->gradeStds()
            ->pluck('sec')
            ->map(fn ($sec) => (int) $sec)
            ->all();

        $skipped = [];
        $toAdd = [];
        foreach ($stds as $std) {
            $sec = (int) ($std['sec'] ?? 0);
            if ($sec > 0 && in_array($sec, $existingSecs, true)) {
                // บันทึกซ้ำตอน wizard (เช่น ขั้น 5 แล้วขั้น 6) — ข้าม Section ที่มีแล้ว ไม่ error
                $skipped[] = $sec;
                continue;
            }
            $toAdd[] = $std;
        }

        $skipped = array_values(array_unique($skipped));

        if ($toAdd === []) {
            // ไม่มี Section ใหม่ — คืนรายงานปัจจุบันให้ wizard ไปต่อได้
            return response()->json($this->formatReport($report->loadMissing('gradeStds')));
        }

        DB::connection('scigrad')->transaction(function () use ($report, $toAdd, $data) {
            $teacher = trim((string) ($data['teacher'] ?? ''));
            $mergedRemarks = GradeReportRemarks::merge(
                $report->reason,
                $report->reasonid !== null ? (int) $report->reasonid : null,
                $data['reason'] ?? null,
                isset($data['reasonid']) ? (int) $data['reasonid'] : null,
            );
            $updates = [
                'reason' => $mergedRemarks['reason'],
                'reasonid' => $mergedRemarks['reasonid'],
            ];
            if ($teacher !== '') {
                $updates['teacher'] = mb_substr($teacher, 0, 250);
            }
            $report->update($updates);
            $this->mergeNewGradeStds($report, $toAdd);
        });

        // ไฟล์ REG ใน session จะถูกแนบเมื่อทำ wizard ครบ (finalize-wizard) เท่านั้น

        $this->auditLog->record(
            'grade_report.append_sections',
            subjectType: 'grade_report',
            subjectId: $report->grade_id,
            metadata: [
                'subject_code' => $report->subject_code,
                'term' => $report->term,
                'year' => $report->year,
                'added_sections' => array_values(array_filter(array_map(
                    fn ($std) => (int) ($std['sec'] ?? 0),
                    $toAdd,
                ))),
                'skipped_sections' => $skipped,
            ],
        );

        return response()->json($this->formatReport($report->fresh('gradeStds')));
    }

    /**
     * อาจารย์ที่กรอกเพิ่มในรายงานวิชาร่วม — อัปเดต/ลบได้เฉพาะ Section ของตนเอง
     *
     * @param  array<string, mixed>  $data
     */
    private function syncContributorSections(Request $request, GradeReport $report, array $data): JsonResponse
    {
        if ((int) $report->approv > 0) {
            return response()->json(['message' => 'ไม่สามารถแก้ไขรายการที่อนุมัติแล้ว'], 422);
        }
        if ($report->awaitingDeptResubmit()) {
            return response()->json(['message' => 'รายการส่งการแก้ไขแล้ว รอสาขาวิชาดำเนินการ'], 422);
        }

        $stds = $data['grade_stds'] ?? [];
        $username = $this->staffUsername();
        $report->loadMissing('gradeStds');

        DB::connection('scigrad')->transaction(function () use ($report, $stds, $username) {
            $ownIds = $report->gradeStds
                ->filter(fn (GradeStd $row) => $row->filledBy($username, $report))
                ->pluck('grade_std_id')
                ->all();
            $existingSecs = $report->gradeStds
                ->map(fn (GradeStd $row) => (int) $row->sec)
                ->all();
            $keptIds = [];

            foreach ($stds as $std) {
                $stdData = $this->normalizeStdData($std);
                $stdData['total_std'] = (string) $this->calcTotalStd($stdData);
                $sec = (int) ($stdData['sec'] ?? 0);

                if (! empty($std['id'])) {
                    $model = $report->gradeStds()->where('grade_std_id', $std['id'])->first();
                    if ($model && $model->filledBy($username, $report)) {
                        $model->update($stdData);
                        $keptIds[] = $model->grade_std_id;
                    }

                    continue;
                }

                if ($sec > 0 && in_array($sec, $existingSecs, true)) {
                    continue;
                }

                $created = $report->gradeStds()->create($this->stampNewStdUsername($stdData));
                $keptIds[] = $created->grade_std_id;
                $existingSecs[] = (int) $created->sec;
            }

            $toDelete = array_values(array_diff($ownIds, $keptIds));
            if ($toDelete !== []) {
                $removed = $report->gradeStds()->whereIn('grade_std_id', $toDelete)->get();
                foreach ($removed as $row) {
                    $sec = (int) $row->sec;
                    $row->delete();
                    $this->pendingRegistrar->deleteInstructorRegistrarForSection($report, $sec);
                }
            }
        });

        $this->auditLog->record(
            'grade_report.contributor_sync_sections',
            subjectType: 'grade_report',
            subjectId: $report->grade_id,
            metadata: [
                'subject_code' => $report->subject_code,
                'term' => $report->term,
                'year' => $report->year,
            ],
        );

        return response()->json($this->formatReport($report->fresh('gradeStds')));
    }

    private function mergeNewGradeStds(GradeReport $report, array $stds): void
    {
        $existingSecs = $report->gradeStds()
            ->pluck('sec')
            ->map(fn ($sec) => (int) $sec)
            ->all();

        foreach ($stds as $std) {
            $sec = (int) ($std['sec'] ?? 0);
            if ($sec > 0 && in_array($sec, $existingSecs, true)) {
                continue;
            }

            $stdData = $this->normalizeStdData($std);
            $stdData['total_std'] = (string) $this->calcTotalStd($stdData);
            $stdData = $this->stampNewStdUsername($stdData);
            $created = $report->gradeStds()->create($stdData);
            $existingSecs[] = (int) $created->sec;
        }
    }

    /**
     * เงื่อนไขจาก project_old/grade_add_new.php — checksubject() + checksubjectID()
     */
    private function applyGradReport2Rules(array $data): array
    {
        $subjectCode = $this->gradReport2->normalizeSubjectCode($data['subject_code'] ?? '');
        $data['subject_code'] = $subjectCode;

        $jointCodes = array_values(array_filter(
            array_map(
                fn ($code) => $this->gradReport2->normalizeSubjectCode((string) $code),
                $data['joint_subject_codes'] ?? [],
            ),
        ));

        if ($jointCodes === [] && GradeReportRemarks::hasJoint($data['reasonid'] ?? null, $data['reason'] ?? null) && ! empty($data['reason'])) {
            $jointCodes = $this->gradReport2->parseJointCodesFromReason($data['reason']);
        }

        $data['subject_code2'] = $this->gradReport2->resolveSubjectCode2Multi($subjectCode, $jointCodes);

        if (GradeReportRemarks::hasJoint($data['reasonid'] ?? null, $data['reason'] ?? null) && $jointCodes !== []) {
            try {
                $this->gradReport2->syncJointGradeSubjects(
                    $subjectCode,
                    (string) ($data['subject'] ?? ''),
                    $this->staffUsername(),
                    $jointCodes,
                );
            } catch (Throwable $e) {
                Log::warning('syncJointGradeSubjects failed; continuing grade report save', [
                    'subject_code' => $subjectCode,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        unset($data['joint_subject_codes']);

        return $data;
    }

    private function prepareReportAttributes(array $data, Request $request, bool $isCreate): array
    {
        $attributes = [
            'created' => $data['report_date'] ?? now()->toDateString(),
            'term' => (string) ($data['term'] ?? 1),
            'year' => (string) ($data['year'] ?? 2568),
            'subject_code' => $data['subject_code'] ?? '',
            'subject_code2' => $data['subject_code2'] ?? ($data['subject_code'] ?? ''),
            'subject' => mb_substr((string) ($data['subject'] ?? ''), 0, 150),
            'teacher' => mb_substr((string) ($data['teacher'] ?? ''), 0, 250),
            'selecttype' => (int) ($data['selecttype'] ?? 1),
            'degree' => $this->resolveDegree($data),
            'programid' => substr((string) ($data['programid'] ?? ''), 0, 4),
            'type_course' => (string) ($data['type_course'] ?? 1),
            'mean' => $data['mean'] !== null && $data['mean'] !== '' ? (string) $data['mean'] : '',
            'sd' => $data['sd'] !== null && $data['sd'] !== '' ? (string) $data['sd'] : '',
            'reasonid' => $data['reasonid'] ?? null,
            'reason' => mb_substr((string) ($data['reason'] ?? ''), 0, 2000),
            'statuseva' => (int) ($data['statuseva'] ?? 2),
            'totalnumstdevz' => $data['totalnumstdevz'] ?? null,
            'totalevaluationscore' => $data['totalevaluationscore'] ?? null,
            'intflag' => (int) ($data['intflag'] ?? 0),
            'score_a' => (string) ($data['score_a'] ?? ''),
            'score_bb' => (string) ($data['score_bb'] ?? ''),
            'score_b' => (string) ($data['score_b'] ?? ''),
            'score_cc' => (string) ($data['score_cc'] ?? ''),
            'score_c' => (string) ($data['score_c'] ?? ''),
            'score_dd' => (string) ($data['score_dd'] ?? ''),
            'score_d' => (string) ($data['score_d'] ?? ''),
            'score_f' => (string) ($data['score_f'] ?? ''),
        ];

        if ($this->gradeReportHasSchemeColumns()) {
            $attributes['score_s'] = (string) ($data['score_s'] ?? '');
            $attributes['score_u'] = (string) ($data['score_u'] ?? '');
            $attributes['grade_scheme'] = $this->normalizeGradeScheme($data['grade_scheme'] ?? null);
        }

        if ($isCreate) {
            $attributes['username'] = $this->staffUsername();
            $attributes['approv'] = 0;
        }

        if (array_key_exists('approv', $data)) {
            $attributes['approv'] = (int) $data['approv'];
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveDegree(array $data): int
    {
        $provided = (int) ($data['degree'] ?? SubjectDegree::BACHELOR);
        $inferred = SubjectDegree::fromSubjectCode((string) ($data['subject_code'] ?? ''));

        if (in_array($inferred, [SubjectDegree::MASTER, SubjectDegree::DOCTORAL], true)) {
            return $inferred;
        }

        return in_array($provided, [SubjectDegree::MASTER, SubjectDegree::DOCTORAL], true)
            ? $provided
            : SubjectDegree::BACHELOR;
    }

    private function normalizeGradeScheme(mixed $value): string
    {
        $scheme = is_string($value) ? strtolower(trim($value)) : '';

        return in_array($scheme, ['credit', 'audit', 'both'], true) ? $scheme : 'credit';
    }

    private function gradeReportHasSchemeColumns(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }

        try {
            $has = Schema::connection('scigrad')->hasColumn('grade_report', 'grade_scheme');
        } catch (\Throwable) {
            $has = false;
        }

        return $has;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertExamReportable(array $data, ?GradeReport $existing = null): void
    {
        $code = (string) ($data['subject_code'] ?? $existing?->subject_code ?? '');
        $name = (string) ($data['subject'] ?? $existing?->subject ?? '');

        if (ThesisCourse::isThesisSubject($code, $name)
            || ($existing && ThesisCourse::isThesisSubject((string) $existing->subject_code, (string) $existing->subject))) {
            throw ValidationException::withMessages([
                'subject_code' => ThesisCourse::EXAM_BLOCK_MESSAGE,
            ]);
        }

        foreach ($data['joint_subject_codes'] ?? [] as $jointCode) {
            if (ThesisCourse::isThesisSubject((string) $jointCode)) {
                throw ValidationException::withMessages([
                    'joint_subject_codes' => ThesisCourse::EXAM_BLOCK_MESSAGE,
                ]);
            }
        }
    }

    private function validateReport(Request $request, bool $updating = false): array
    {
        $payload = $request->all();
        $statuseva = (int) ($payload['statuseva'] ?? 2);

        // กันค่าผลประเมินค้างผิดโหมด (เช่น สลับ radio / autofill แล้วไปติด grade_stds)
        if ($statuseva === 2) {
            $payload['grade_stds'] = array_map(function ($std) {
                if (! is_array($std)) {
                    return $std;
                }
                $std['evaluationscore'] = null;
                $std['numstdevz'] = null;

                return $std;
            }, $payload['grade_stds'] ?? []);
        } else {
            $payload['totalevaluationscore'] = null;
            $payload['totalnumstdevz'] = null;
        }

        $request->merge($payload);

        return $request->validate([
            'report_date' => [$updating ? 'sometimes' : 'required', 'date'],
            'term' => [$updating ? 'sometimes' : 'required', 'integer', 'in:1,2,3'],
            'year' => [$updating ? 'sometimes' : 'required', 'integer', 'min:2500', 'max:2700'],
            'subject_code' => [$updating ? 'sometimes' : 'required', 'string', 'max:50'],
            'subject_code2' => ['nullable', 'string', 'max:50'],
            'subject' => [$updating ? 'sometimes' : 'required', 'string', 'max:150'],
            'teacher' => ['nullable', 'string', 'max:250'],
            'selecttype' => ['nullable', 'integer', 'in:1,2'],
            'degree' => ['nullable', 'integer', 'in:3,5,7'],
            'programid' => ['nullable', 'string', 'max:4'],
            'type_course' => ['nullable', 'integer', 'in:1,2,3,4,5'],
            'mean' => ['nullable', 'numeric'],
            'sd' => ['nullable', 'numeric'],
            'reasonid' => ['nullable', 'integer', 'min:1', 'max:15'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'joint_subject_codes' => ['nullable', 'array'],
            'joint_subject_codes.*' => ['string', 'max:50'],
            'statuseva' => ['nullable', 'integer', 'in:1,2'],
            'totalnumstdevz' => ['nullable', 'integer', 'min:0'],
            'totalevaluationscore' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'intflag' => ['nullable', 'integer', 'in:0,1'],
            'score_a' => ['nullable', 'string', 'max:20'],
            'score_bb' => ['nullable', 'string', 'max:20'],
            'score_b' => ['nullable', 'string', 'max:20'],
            'score_cc' => ['nullable', 'string', 'max:20'],
            'score_c' => ['nullable', 'string', 'max:20'],
            'score_dd' => ['nullable', 'string', 'max:20'],
            'score_d' => ['nullable', 'string', 'max:20'],
            'score_f' => ['nullable', 'string', 'max:20'],
            'score_s' => ['nullable', 'string', 'max:20'],
            'score_u' => ['nullable', 'string', 'max:20'],
            'grade_scheme' => ['nullable', 'string', 'in:credit,audit,both'],
            'append_sections' => ['nullable', 'boolean'],
            'grade_stds' => [$updating ? 'sometimes' : 'required', 'array', 'min:1'],
            ...$this->gradeStdItemRules('grade_stds.*'),
        ], [
            'subject_code.required' => 'ขั้นตอนที่ 1: กรุณากรอกรหัสวิชา',
            'subject.required' => 'ขั้นตอนที่ 1: กรุณากรอกชื่อวิชา',
            'term.required' => 'ขั้นตอนที่ 1: กรุณาเลือกภาคการศึกษา',
            'year.required' => 'ขั้นตอนที่ 1: กรุณาระบุปีการศึกษา',
            'year.min' => 'ปีการศึกษาต้องเป็น พ.ศ. (เช่น 2568)',
            'year.max' => 'ปีการศึกษาต้องเป็น พ.ศ. ที่ถูกต้อง',
            'reason.max' => 'ขั้นตอนที่ 2: ข้อความหมายเหตุ/วิชาตัดเกรดร่วมยาวเกินไป — ลดจำนวนวิชาหรือชื่อวิชาให้สั้นลง',
            'totalevaluationscore.max' => 'ขั้นตอนที่ 5: ผลการประเมินรายวิชาโดยนักศึกษาต้องไม่เกิน 5 คะแนน (ไม่ใช่จำนวนนักศึกษาที่เข้าประเมิน)',
            'totalevaluationscore.numeric' => 'ขั้นตอนที่ 5: ผลการประเมินรายวิชาโดยนักศึกษาต้องเป็นตัวเลข',
            'grade_stds.required' => 'ขั้นตอนที่ 4: กรุณาเพิ่มข้อมูลจำนวนนักศึกษาอย่างน้อย 1 Section',
            'grade_stds.min' => 'ขั้นตอนที่ 4: กรุณาเพิ่มข้อมูลจำนวนนักศึกษาอย่างน้อย 1 Section',
            'grade_stds.*.fac.required' => 'ขั้นตอนที่ 4: กรุณาเลือกคณะของนักศึกษาในแต่ละ Section ก่อนบันทึก',
            'grade_stds.*.fac.max' => 'ขั้นตอนที่ 4: เลือกคณะใน Section เดียวมากเกินไป — แบ่งเป็นหลาย Section หรือลดจำนวนคณะ',
            'grade_stds.*.evaluationscore.max' => 'ขั้นตอนที่ 5: ผลการประเมินรายวิชาโดยนักศึกษาต้องไม่เกิน 5 คะแนน (ไม่ใช่จำนวนนักศึกษาที่เข้าประเมิน)',
            'grade_stds.*.evaluationscore.numeric' => 'ขั้นตอนที่ 5: ผลการประเมินรายวิชาโดยนักศึกษาต้องเป็นตัวเลข',
            'score_a.max' => 'ขั้นตอนที่ 3: ช่วงคะแนนเกรด A ยาวเกินที่ระบบรองรับ',
            'score_bb.max' => 'ขั้นตอนที่ 3: ช่วงคะแนนเกรด B+ ยาวเกินที่ระบบรองรับ',
            'score_b.max' => 'ขั้นตอนที่ 3: ช่วงคะแนนเกรด B ยาวเกินที่ระบบรองรับ',
            'score_cc.max' => 'ขั้นตอนที่ 3: ช่วงคะแนนเกรด C+ ยาวเกินที่ระบบรองรับ',
            'score_c.max' => 'ขั้นตอนที่ 3: ช่วงคะแนนเกรด C ยาวเกินที่ระบบรองรับ',
            'score_dd.max' => 'ขั้นตอนที่ 3: ช่วงคะแนนเกรด D+ ยาวเกินที่ระบบรองรับ',
            'score_d.max' => 'ขั้นตอนที่ 3: ช่วงคะแนนเกรด D ยาวเกินที่ระบบรองรับ',
            'score_f.max' => 'ขั้นตอนที่ 3: ช่วงคะแนนเกรด F ยาวเกินที่ระบบรองรับ',
        ], [
            'subject_code' => 'รหัสวิชา',
            'subject' => 'ชื่อวิชา',
            'teacher' => 'อาจารย์ผู้สอน',
            'reason' => 'หมายเหตุ / วิชาตัดเกรดร่วม',
            'totalevaluationscore' => 'ผลการประเมินรายวิชาโดยนักศึกษา',
            'grade_stds' => 'จำนวนนักศึกษาตาม Section',
            'grade_stds.*.fac' => 'คณะใน Section',
            'grade_stds.*.evaluationscore' => 'ผลการประเมินรายวิชาโดยนักศึกษา',
            'totalnumstdevz' => 'จำนวนนักศึกษาที่เข้าประเมิน',
            'grade_stds.*.numstdevz' => 'จำนวนนักศึกษาที่เข้าประเมิน',
        ]);
    }

    private function gradeStdItemRules(string $prefix): array
    {
        return [
            "{$prefix}.id" => ['nullable', 'integer'],
            "{$prefix}.sec" => ['nullable', 'integer', 'min:1', 'max:50'],
            "{$prefix}.fac" => ['required', 'string', 'max:255'],
            "{$prefix}.num_a" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_bb" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_b" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_cc" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_c" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_dd" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_d" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_f" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_ff" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_i" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_s" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_v" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_w" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.num_out" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.evaluationscore" => ['nullable', 'numeric', 'min:0', 'max:5'],
            "{$prefix}.numstdevz" => ['nullable', 'integer', 'min:0'],
            "{$prefix}.type_course" => ['nullable', 'integer', 'in:1,2,3,4,5'],
        ];
    }

    private function syncGradeStds(GradeReport $report, array $stds): void
    {
        $keptIds = [];

        foreach ($stds as $std) {
            $stdData = $this->normalizeStdData($std);
            $stdData['total_std'] = (string) $this->calcTotalStd($stdData);

            if (! empty($std['id'])) {
                $model = $report->gradeStds()->where('grade_std_id', $std['id'])->first();
                if ($model) {
                    $model->update($stdData);
                    $keptIds[] = $model->grade_std_id;

                    continue;
                }
            }

            $created = $report->gradeStds()->create($this->stampNewStdUsername($stdData));
            $keptIds[] = $created->grade_std_id;
        }

        if ($keptIds) {
            $report->gradeStds()->whereNotIn('grade_std_id', $keptIds)->delete();
        } else {
            $report->gradeStds()->delete();
        }

        $this->pendingRegistrar->purgeOrphanInstructorRegistrarFiles($report);
    }

    private function normalizeStdData(array $std): array
    {
        $keys = [
            'sec', 'fac', 'num_a', 'num_bb', 'num_b', 'num_cc', 'num_c',
            'num_dd', 'num_d', 'num_f', 'num_ff', 'num_i', 'num_s', 'num_v',
            'num_w', 'num_out', 'evaluationscore', 'numstdevz', 'type_course',
        ];

        $data = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $std)) {
                $data[$key] = $std[$key];
            }
        }

        $data['sec'] = (string) ($data['sec'] ?? 1);
        $data['type_course'] = (string) ($data['type_course'] ?? 1);
        if (array_key_exists('fac', $data)) {
            $data['fac'] = mb_substr(trim((string) $data['fac']), 0, 255);
        }

        foreach (['num_a', 'num_bb', 'num_b', 'num_cc', 'num_c', 'num_dd', 'num_d', 'num_f', 'num_ff', 'num_i', 'num_s', 'num_v', 'num_w', 'num_out'] as $key) {
            $data[$key] = (int) ($data[$key] ?? 0);
        }

        if (array_key_exists('evaluationscore', $data) && $data['evaluationscore'] !== null && $data['evaluationscore'] !== '') {
            $data['evaluationscore'] = (string) $data['evaluationscore'];
        } else {
            unset($data['evaluationscore']);
        }

        if (array_key_exists('numstdevz', $data) && $data['numstdevz'] === null) {
            unset($data['numstdevz']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $stdData
     * @return array<string, mixed>
     */
    private function stampNewStdUsername(array $stdData): array
    {
        if (GradeStd::hasUsernameColumn()) {
            $stdData['username'] = $this->staffUsername();
        }

        return $stdData;
    }

    private function calcTotalStd(array $std): int
    {
        $keys = ['num_a', 'num_bb', 'num_b', 'num_cc', 'num_c', 'num_dd', 'num_d', 'num_f', 'num_i', 'num_s', 'num_v', 'num_w', 'num_out'];

        return array_sum(array_map(fn ($k) => (int) ($std[$k] ?? 0), $keys));
    }

    private function formatStdRow(GradeStd $row): array
    {
        return [
            'id' => $row->grade_std_id,
            'grade_std_id' => $row->grade_std_id,
            'grade_id' => $row->grade_id,
            'sec' => (int) $row->sec,
            'fac' => $row->fac,
            'total_std' => (int) $row->total_std,
            'num_a' => $row->num_a,
            'num_bb' => $row->num_bb,
            'num_b' => $row->num_b,
            'num_cc' => $row->num_cc,
            'num_c' => $row->num_c,
            'num_dd' => $row->num_dd,
            'num_d' => $row->num_d,
            'num_f' => $row->num_f,
            'num_ff' => $row->num_ff,
            'num_i' => $row->num_i,
            'num_s' => $row->num_s,
            'num_v' => $row->num_v,
            'num_w' => $row->num_w,
            'num_out' => $row->num_out,
            'evaluationscore' => $row->evaluationscore,
            'numstdevz' => $row->numstdevz,
            'type_course' => (int) $row->type_course,
        ];
    }

    private function formatReport(GradeReport $report): array
    {
        $stds = $report->gradeStds->sortBy(fn ($row) => (int) $row->sec)->values();
        $std = $stds->first();
        $term = (int) $report->term;

        return [
            '__backendId' => (string) $report->grade_id,
            'record_type' => 'grade_report',
            'report_date' => $report->created?->format('Y-m-d'),
            'term' => $term,
            'semester_type' => match ($term) {
                1 => 'ภาคต้น',
                2 => 'ภาคปลาย',
                default => 'ภาคการศึกษาพิเศษ',
            },
            'year' => (int) $report->year,
            'academic_year' => (string) $report->year,
            'subject_code' => $report->subject_code,
            'course_id' => $report->subject_code,
            'subject_code2' => $report->subject_code2,
            'subject' => $report->subject,
            'course_name' => $report->subject,
            'teacher' => $report->teacher,
            'instructor_name' => $report->teacher,
            'selecttype' => (int) $report->selecttype,
            'course_type' => (int) $report->selecttype === 1 ? 'วิชาในหลักสูตร' : 'รายวิชาบริการ',
            'degree' => (int) $report->degree,
            'programid' => $report->programid,
            'type_course' => (int) $report->type_course,
            'mean' => $report->mean !== '' ? $report->mean : null,
            'mean_score' => $report->mean !== '' ? $report->mean : null,
            'sd' => $report->sd !== '' ? $report->sd : null,
            'sd_score' => $report->sd !== '' ? $report->sd : null,
            'reasonid' => $report->reasonid,
            'reason' => $report->reason,
            'statuseva' => (int) $report->statuseva,
            'totalnumstdevz' => $report->totalnumstdevz,
            'totalevaluationscore' => $report->totalevaluationscore,
            'intflag' => (int) $report->intflag,
            'score_type' => (int) $report->intflag ? 'เป็นจำนวนเต็ม' : 'มีเกณฑ์',
            'score_a' => $report->score_a,
            'score_bb' => $report->score_bb,
            'score_b' => $report->score_b,
            'score_cc' => $report->score_cc,
            'score_c' => $report->score_c,
            'score_dd' => $report->score_dd,
            'score_d' => $report->score_d,
            'score_f' => $report->score_f,
            'score_s' => $this->gradeReportHasSchemeColumns() ? $report->score_s : null,
            'score_u' => $this->gradeReportHasSchemeColumns() ? $report->score_u : null,
            'grade_scheme' => $this->gradeReportHasSchemeColumns()
                ? $this->normalizeGradeScheme($report->grade_scheme)
                : 'credit',
            'approv' => (int) $report->approv,
            'status' => $report->statusLabel(),
            'rejection_reason' => (int) $report->approv === -1 ? $report->reason : null,
            'remark' => null,
            'submitted_by' => $report->teacher,
            'submitted_at' => null,
            'dept_approved_at' => $report->dateapprove1,
            'faculty_approved_at' => $report->dateapprove2,
            'section' => $std ? (int) $std->sec : null,
            'fac' => $std?->fac,
            'student_count' => $stds->sum(fn ($row) => (int) $row->total_std),
            'count_a' => $stds->sum('num_a'),
            'count_bp' => $stds->sum('num_bb'),
            'count_b' => $stds->sum('num_b'),
            'count_cp' => $stds->sum('num_cc'),
            'count_c' => $stds->sum('num_c'),
            'count_dp' => $stds->sum('num_dd'),
            'count_d' => $stds->sum('num_d'),
            'count_f' => $stds->sum('num_f'),
            'count_i' => $stds->sum('num_i'),
            'count_s' => $stds->sum('num_s'),
            'count_u' => $stds->sum('num_v'),
            'count_w' => $stds->sum('num_w'),
            'grade_std' => $std ? $this->formatStdRow($std) : null,
            'grade_stds' => $stds->map(fn (GradeStd $row) => $this->formatStdRow($row))->all(),
        ];
    }
}
