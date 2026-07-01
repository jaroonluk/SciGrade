<?php

namespace App\Http\Controllers;

use App\Models\GradeReport;
use App\Models\GradeStd;
use App\Services\GradReport2Service;
use App\Services\StaffAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradeReportController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly GradReport2Service $gradReport2,
    ) {}
    public function index(Request $request): JsonResponse
    {
        $query = GradeReport::query()
            ->with('gradeStds')
            ->when($request->filled('approv'), fn ($q) => $q->where('approv', $request->integer('approv')))
            ->when($request->filled('term'), fn ($q) => $q->where('term', (string) $request->integer('term')))
            ->when($request->filled('year'), fn ($q) => $q->where('year', (string) $request->integer('year')))
            ->orderByDesc('created_stamp')
            ->orderByDesc('grade_id');

        if ($request->input('role', 'instructor') === 'instructor') {
            $query->where('username', $this->staffUsername());
        }

        return response()->json($query->get()->map(fn (GradeReport $r) => $this->formatReport($r)));
    }

    public function show(Request $request, GradeReport $gradeReport): JsonResponse
    {
        if ($request->input('role', 'instructor') === 'instructor') {
            abort_unless($this->ownsReport($gradeReport), 403);
        }

        return response()->json($this->formatReport($gradeReport->load('gradeStds')));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateReport($request);

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

        return response()->json($this->formatReport($report), 201);
    }

    public function update(Request $request, GradeReport $gradeReport): JsonResponse
    {
        if ($request->has('approv')) {
            return $this->updateApproval($request, $gradeReport);
        }

        abort_unless($this->ownsReport($gradeReport), 403);

        if ((int) $gradeReport->approv > 0) {
            return response()->json(['message' => 'ไม่สามารถแก้ไขรายการที่อนุมัติแล้ว'], 422);
        }

        $data = $this->validateReport($request, updating: true);
        $stds = $data['grade_stds'] ?? null;
        unset($data['grade_stds'], $data['grade_std']);

        DB::connection('scigrad')->transaction(function () use ($gradeReport, $data, $stds, $request) {
            if ((int) $gradeReport->approv === -1) {
                $data['approv'] = 0;
            }

            $data = $this->applyGradReport2Rules($data);

            $gradeReport->update($this->prepareReportAttributes($data, $request, isCreate: false));

            if ($stds !== null) {
                $this->syncGradeStds($gradeReport, $stds);
            }
        });

        return response()->json($this->formatReport($gradeReport->fresh('gradeStds')));
    }

    public function destroy(Request $request, GradeReport $gradeReport): JsonResponse
    {
        abort_unless($this->ownsReport($gradeReport), 403);

        if ((int) $gradeReport->approv > 0) {
            return response()->json(['message' => 'ไม่สามารถลบรายการที่อนุมัติแล้ว'], 422);
        }

        DB::connection('scigrad')->transaction(function () use ($gradeReport) {
            $gradeReport->gradeStds()->delete();
            $gradeReport->delete();
        });

        return response()->json(['ok' => true]);
    }

    private function updateApproval(Request $request, GradeReport $gradeReport): JsonResponse
    {
        $validated = $request->validate([
            'approv' => ['required', 'integer', 'in:-1,0,1,2'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'role' => ['required', 'in:dept_admin,faculty_admin'],
        ]);

        $today = now()->toDateString();

        if ($validated['approv'] === -1) {
            $gradeReport->update([
                'approv' => -1,
                'dateapprove2' => $today,
                'reason' => $validated['rejection_reason'] ?? $gradeReport->reason,
            ]);
        } elseif ($validated['role'] === 'dept_admin' && $validated['approv'] === 1) {
            $gradeReport->update([
                'approv' => 1,
                'dateapprove1' => $today,
            ]);
        } elseif ($validated['role'] === 'faculty_admin' && $validated['approv'] === 2) {
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
        return $report->username === $this->staffUsername();
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

        if ($jointCodes === [] && (int) ($data['reasonid'] ?? 0) === 1 && ! empty($data['reason'])) {
            $jointCodes = $this->gradReport2->parseJointCodesFromReason($data['reason']);
        }

        $data['subject_code2'] = $this->gradReport2->resolveSubjectCode2Multi($subjectCode, $jointCodes);

        if ((int) ($data['reasonid'] ?? 0) === 1 && $jointCodes !== []) {
            $this->gradReport2->syncJointGradeSubjects(
                $subjectCode,
                (string) ($data['subject'] ?? ''),
                $this->staffUsername(),
                $jointCodes,
            );
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
            'subject' => $data['subject'] ?? '',
            'teacher' => $data['teacher'] ?? '',
            'selecttype' => (int) ($data['selecttype'] ?? 1),
            'degree' => (int) ($data['degree'] ?? 3),
            'programid' => substr((string) ($data['programid'] ?? ''), 0, 4),
            'type_course' => (string) ($data['type_course'] ?? 1),
            'mean' => $data['mean'] !== null && $data['mean'] !== '' ? (string) $data['mean'] : '',
            'sd' => $data['sd'] !== null && $data['sd'] !== '' ? (string) $data['sd'] : '',
            'reasonid' => $data['reasonid'] ?? null,
            'reason' => (string) ($data['reason'] ?? ''),
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

        if ($isCreate) {
            $attributes['username'] = $this->staffUsername();
            $attributes['approv'] = 0;
        }

        if (array_key_exists('approv', $data)) {
            $attributes['approv'] = (int) $data['approv'];
        }

        return $attributes;
    }

    private function validateReport(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'report_date' => [$updating ? 'sometimes' : 'required', 'date'],
            'term' => [$updating ? 'sometimes' : 'required', 'integer', 'in:1,2,3'],
            'year' => [$updating ? 'sometimes' : 'required', 'integer', 'min:2500', 'max:2600'],
            'subject_code' => [$updating ? 'sometimes' : 'required', 'string', 'max:50'],
            'subject_code2' => ['nullable', 'string', 'max:50'],
            'subject' => [$updating ? 'sometimes' : 'required', 'string', 'max:150'],
            'teacher' => ['nullable', 'string', 'max:250'],
            'selecttype' => [$updating ? 'sometimes' : 'required', 'integer', 'in:1,2'],
            'degree' => ['nullable', 'integer', 'in:3,5,7'],
            'programid' => ['nullable', 'required_if:selecttype,1', 'string', 'max:4'],
            'type_course' => ['nullable', 'integer', 'in:1,2,3,4,5'],
            'mean' => ['nullable', 'numeric'],
            'sd' => ['nullable', 'numeric'],
            'reasonid' => ['nullable', 'integer', 'in:1,2,3'],
            'reason' => ['nullable', 'string', 'max:150'],
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
            'grade_stds' => [$updating ? 'sometimes' : 'required', 'array', 'min:1'],
            ...$this->gradeStdItemRules('grade_stds.*'),
        ]);
    }

    private function gradeStdItemRules(string $prefix): array
    {
        return [
            "{$prefix}.id" => ['nullable', 'integer'],
            "{$prefix}.sec" => ['nullable', 'integer', 'min:1', 'max:50'],
            "{$prefix}.fac" => ['required', 'string', 'max:100'],
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

            $created = $report->gradeStds()->create($stdData);
            $keptIds[] = $created->grade_std_id;
        }

        if ($keptIds) {
            $report->gradeStds()->whereNotIn('grade_std_id', $keptIds)->delete();
        } else {
            $report->gradeStds()->delete();
        }
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
