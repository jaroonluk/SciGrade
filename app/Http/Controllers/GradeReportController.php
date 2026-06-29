<?php

namespace App\Http\Controllers;

use App\Models\GradeReport;
use App\Models\GradeStd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradeReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = GradeReport::query()
            ->with('gradeStds')
            ->when($request->filled('approv'), fn ($q) => $q->where('approv', $request->integer('approv')))
            ->when($request->filled('term'), fn ($q) => $q->where('term', $request->integer('term')))
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->integer('year')))
            ->orderByDesc('created_at');

        if ($request->input('role', 'instructor') === 'instructor') {
            $query->where('user_id', $request->user()->id);
        }

        return response()->json($query->get()->map(fn (GradeReport $r) => $this->formatReport($r)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateReport($request);

        $report = DB::transaction(function () use ($data, $request) {
            $stdData = $data['grade_std'];
            unset($data['grade_std']);

            $report = GradeReport::query()->create([
                ...$data,
                'user_id' => $request->user()->id,
                'approv' => 0,
            ]);

            GradeStd::query()->create([
                ...$stdData,
                'grade_report_id' => $report->id,
                'total_std' => $this->calcTotalStd($stdData),
            ]);

            return $report->load('gradeStds');
        });

        return response()->json($this->formatReport($report), 201);
    }

    public function update(Request $request, GradeReport $gradeReport): JsonResponse
    {
        if ($request->has('approv')) {
            return $this->updateApproval($request, $gradeReport);
        }

        if ($gradeReport->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($gradeReport->approv > 0) {
            return response()->json(['message' => 'ไม่สามารถแก้ไขรายการที่อนุมัติแล้ว'], 422);
        }

        $data = $this->validateReport($request, updating: true);
        $stdData = $data['grade_std'] ?? null;
        unset($data['grade_std']);

        DB::transaction(function () use ($gradeReport, $data, $stdData) {
            $gradeReport->update($data);

            if ($stdData) {
                $std = $gradeReport->gradeStds()->first();
                $stdData['total_std'] = $this->calcTotalStd($stdData);

                if ($std) {
                    $std->update($stdData);
                } else {
                    GradeStd::query()->create([
                        ...$stdData,
                        'grade_report_id' => $gradeReport->id,
                    ]);
                }
            }
        });

        return response()->json($this->formatReport($gradeReport->fresh('gradeStds')));
    }

    public function destroy(Request $request, GradeReport $gradeReport): JsonResponse
    {
        if ($gradeReport->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($gradeReport->approv > 0) {
            return response()->json(['message' => 'ไม่สามารถลบรายการที่อนุมัติแล้ว'], 422);
        }

        $gradeReport->delete();

        return response()->json(['ok' => true]);
    }

    private function updateApproval(Request $request, GradeReport $gradeReport): JsonResponse
    {
        $validated = $request->validate([
            'approv' => ['required', 'integer', 'in:-1,0,1,2'],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
            'role' => ['required', 'in:dept_admin,faculty_admin'],
        ]);

        if ($validated['approv'] === -1) {
            $gradeReport->update([
                'approv' => -1,
                'rejection_reason' => $validated['rejection_reason'] ?? '',
                'dateapprove2' => now(),
            ]);
        } elseif ($validated['role'] === 'dept_admin' && $validated['approv'] === 1) {
            $gradeReport->update([
                'approv' => 1,
                'dept_approved_at' => now(),
                'rejection_reason' => null,
            ]);
        } elseif ($validated['role'] === 'faculty_admin' && $validated['approv'] === 2) {
            $gradeReport->update([
                'approv' => 2,
                'faculty_approved_at' => now(),
                'rejection_reason' => null,
            ]);
        } else {
            return response()->json(['message' => 'ไม่สามารถอนุมัติรายการนี้ได้'], 422);
        }

        return response()->json($this->formatReport($gradeReport->fresh('gradeStds')));
    }

    private function validateReport(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'report_date' => [$updating ? 'sometimes' : 'required', 'date'],
            'term' => [$updating ? 'sometimes' : 'required', 'integer', 'in:1,2,3'],
            'year' => [$updating ? 'sometimes' : 'required', 'integer', 'min:2500', 'max:2600'],
            'subject_code' => [$updating ? 'sometimes' : 'required', 'string', 'max:20'],
            'subject_code2' => ['nullable', 'string', 'max:20'],
            'subject' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'teacher' => ['nullable', 'string', 'max:255'],
            'selecttype' => [$updating ? 'sometimes' : 'required', 'integer', 'in:1,2'],
            'degree' => ['nullable', 'integer', 'in:3,5,7'],
            'programid' => ['nullable', 'string', 'max:50'],
            'type_course' => ['nullable', 'integer', 'in:1,2,3,4,5'],
            'mean' => ['nullable', 'numeric'],
            'sd' => ['nullable', 'numeric'],
            'reasonid' => ['nullable', 'integer', 'in:1,2,3'],
            'reason' => ['nullable', 'string', 'max:500'],
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
            'remark' => ['nullable', 'string', 'max:1000'],
            'grade_std' => [$updating ? 'sometimes' : 'required', 'array'],
            'grade_std.sec' => ['nullable', 'integer', 'min:1', 'max:50'],
            'grade_std.fac' => ['nullable', 'string', 'max:255'],
            'grade_std.num_a' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_bb' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_b' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_cc' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_c' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_dd' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_d' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_f' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_ff' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_i' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_s' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_v' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_w' => ['nullable', 'integer', 'min:0'],
            'grade_std.num_out' => ['nullable', 'integer', 'min:0'],
            'grade_std.evaluationscore' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'grade_std.numstdevz' => ['nullable', 'integer', 'min:0'],
            'grade_std.type_course' => ['nullable', 'integer', 'in:1,2,3,4,5'],
        ]);
    }

    private function calcTotalStd(array $std): int
    {
        $keys = ['num_a', 'num_bb', 'num_b', 'num_cc', 'num_c', 'num_dd', 'num_d', 'num_f', 'num_i', 'num_s', 'num_v', 'num_w', 'num_out'];

        return array_sum(array_map(fn ($k) => (int) ($std[$k] ?? 0), $keys));
    }

    private function formatReport(GradeReport $report): array
    {
        $std = $report->gradeStds->first();

        return [
            '__backendId' => (string) $report->id,
            'record_type' => 'grade_report',
            'report_date' => $report->report_date?->format('Y-m-d'),
            'term' => $report->term,
            'semester_type' => match ($report->term) {
                1 => 'ภาคต้น',
                2 => 'ภาคปลาย',
                default => 'ภาคการศึกษาพิเศษ',
            },
            'year' => $report->year,
            'academic_year' => (string) $report->year,
            'subject_code' => $report->subject_code,
            'course_id' => $report->subject_code,
            'subject_code2' => $report->subject_code2,
            'subject' => $report->subject,
            'course_name' => $report->subject,
            'teacher' => $report->teacher,
            'instructor_name' => $report->teacher,
            'selecttype' => $report->selecttype,
            'course_type' => $report->selecttype === 1 ? 'วิชาในหลักสูตร' : 'รายวิชาบริการ',
            'degree' => $report->degree,
            'programid' => $report->programid,
            'type_course' => $report->type_course,
            'mean' => $report->mean,
            'mean_score' => $report->mean,
            'sd' => $report->sd,
            'sd_score' => $report->sd,
            'reasonid' => $report->reasonid,
            'reason' => $report->reason,
            'statuseva' => $report->statuseva,
            'totalnumstdevz' => $report->totalnumstdevz,
            'totalevaluationscore' => $report->totalevaluationscore,
            'intflag' => $report->intflag,
            'score_type' => $report->intflag ? 'เป็นจำนวนเต็ม' : 'มีเกณฑ์',
            'score_a' => $report->score_a,
            'score_bb' => $report->score_bb,
            'score_b' => $report->score_b,
            'score_cc' => $report->score_cc,
            'score_c' => $report->score_c,
            'score_dd' => $report->score_dd,
            'score_d' => $report->score_d,
            'score_f' => $report->score_f,
            'approv' => $report->approv,
            'status' => $report->statusLabel(),
            'rejection_reason' => $report->rejection_reason,
            'remark' => $report->remark,
            'submitted_by' => $report->teacher,
            'submitted_at' => $report->created_at?->toIso8601String(),
            'dept_approved_at' => $report->dept_approved_at?->toIso8601String(),
            'faculty_approved_at' => $report->faculty_approved_at?->toIso8601String(),
            'section' => $std?->sec,
            'fac' => $std?->fac,
            'student_count' => $std?->total_std ?? 0,
            'count_a' => $std?->num_a ?? 0,
            'count_bp' => $std?->num_bb ?? 0,
            'count_b' => $std?->num_b ?? 0,
            'count_cp' => $std?->num_cc ?? 0,
            'count_c' => $std?->num_c ?? 0,
            'count_dp' => $std?->num_dd ?? 0,
            'count_d' => $std?->num_d ?? 0,
            'count_f' => $std?->num_f ?? 0,
            'count_i' => $std?->num_i ?? 0,
            'count_s' => $std?->num_s ?? 0,
            'count_u' => $std?->num_v ?? 0,
            'count_w' => $std?->num_w ?? 0,
            'grade_std' => $std?->toArray(),
        ];
    }
}
