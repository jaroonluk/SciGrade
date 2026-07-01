<?php

namespace App\Http\Controllers;

use App\Models\GradeReport;
use App\Models\TblProgramQa;
use App\Services\RegistrarGradePdfParser;
use App\Services\RegistrarPdfParseException;
use App\Services\StaffAuthService;
use App\Support\AcademicTerm;
use App\Support\ThaiDateTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GradeReportPageController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly RegistrarGradePdfParser $pdfParser,
    ) {}

    private function formView(?int $reportId, array $nav = [], ?array $uploadParsed = null): View
    {
        $deptId = session('staff_department_id');
        if ($deptId === null && auth()->user()) {
            $staff = $this->staffAuth->findByEmail(auth()->user()->email);
            if ($staff) {
                $this->staffAuth->storeInSession($staff);
                $deptId = (int) $staff->department_id;
            }
        }

        $teacherHelpImageUrl = file_exists(public_path('images/teacher2.png'))
            ? asset('images/teacher2.png')
            : (Storage::disk('public')->exists('teacher2.png')
                ? asset('storage/teacher2.png')
                : 'https://e.sc.kku.ac.th/sci-eoffice/teacher/images2/teacher2.png');

        $prefillTerm = $reportId === null
            ? ($nav['returnTerm'] ?? session('grade_upload_term'))
            : null;
        $prefillYear = $reportId === null
            ? ($nav['returnYear'] ?? session('grade_upload_year'))
            : null;

        return view('templade', [
            'reportId' => $reportId,
            'staffDisplayName' => $this->staffAuth->displayNameFor(
                auth()->user()->email,
                auth()->user()->name,
            ),
            'staffTeacherName' => $this->staffAuth->teacherNameFor(
                auth()->user()->email,
                auth()->user()->name,
            ),
            'teacherHelpImageUrl' => $teacherHelpImageUrl,
            'programs' => TblProgramQa::forDepartment($deptId),
            'prefillTerm' => $prefillTerm,
            'prefillYear' => $prefillYear,
            'returnUrl' => $nav['returnUrl'] ?? route('grade-reports.my'),
            'dashboardUrl' => route('dashboard'),
            'uploadParsed' => $uploadParsed,
        ]);
    }

    /**
     * @return array{returnUrl: string, returnTo: string, returnTerm: int, returnYear: int}
     */
    private function buildReturnContext(Request $request, ?GradeReport $report = null, bool $isCreate = false): array
    {
        $returnTo = $request->input('return', $isCreate ? 'dashboard' : 'my');

        $term = $request->has('term')
            ? $request->integer('term')
            : ($report ? (int) $report->term : null);
        $year = $request->has('year')
            ? $request->integer('year')
            : ($report ? (int) $report->year : null);

        if ($term === null) {
            $term = AcademicTerm::defaultTerm();
        }
        if ($year === null) {
            $year = AcademicTerm::defaultYear();
        }

        $params = ['term' => $term, 'year' => $year];
        $returnUrl = match ($returnTo) {
            'dashboard' => route('dashboard', $params),
            default => route('grade-reports.my', $params),
        };

        return [
            'returnUrl' => $returnUrl,
            'returnTo' => $returnTo,
            'returnTerm' => $term,
            'returnYear' => $year,
        ];
    }

    public function create(Request $request): View
    {
        $uploadParsed = session()->pull('grade_upload_parsed');

        return $this->formView(
            null,
            $this->buildReturnContext($request, isCreate: true),
            is_array($uploadParsed) ? $uploadParsed : null,
        );
    }

    public function edit(Request $request, GradeReport $gradeReport): View
    {
        abort_unless($gradeReport->username === session('staff_username'), 403);
        abort_if((int) $gradeReport->approv > 0, 403, 'ไม่สามารถแก้ไขรายการที่อนุมัติแล้ว');

        return $this->formView($gradeReport->grade_id, $this->buildReturnContext($request, $gradeReport));
    }

    public function upload(): View
    {
        return view('grade-reports.upload', [
            'term' => AcademicTerm::defaultTerm(),
            'year' => AcademicTerm::defaultYear(),
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function storeUpload(Request $request): RedirectResponse
    {
        $request->validate([
            'term' => ['required', 'integer', 'in:1,2,3'],
            'year' => ['required', 'integer', 'min:2500', 'max:2600'],
            'grade_file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:20480',
                function (string $attribute, $value, \Closure $fail): void {
                    $name = $value->getClientOriginalName();
                    if (! preg_match('/^[A-Z0-9]+-\d{2}\.pdf$/i', $name)) {
                        $fail('ชื่อไฟล์ต้องเป็นรูปแบบ รหัสวิชา-เลขกลุ่ม เช่น SC101011-01.pdf');
                    }
                },
            ],
        ], [
            'grade_file.mimes' => 'รองรับเฉพาะไฟล์ PDF จากสำนักทะเบียน',
            'grade_file.required' => 'กรุณาเลือกไฟล์ PDF',
        ]);

        $path = $request->file('grade_file')->store('grade-uploads/'.auth()->id(), 'local');
        $fullPath = Storage::disk('local')->path($path);

        try {
            $parsed = $this->pdfParser->parse(
                $fullPath,
                $request->file('grade_file')->getClientOriginalName(),
                $request->integer('term'),
                $request->integer('year'),
            );
        } catch (RegistrarPdfParseException $e) {
            Storage::disk('local')->delete($path);

            return redirect()
                ->route('grade-reports.upload')
                ->withInput()
                ->withErrors(['grade_file' => $e->getMessage()]);
        }

        session([
            'grade_upload_path' => $path,
            'grade_upload_name' => $request->file('grade_file')->getClientOriginalName(),
            'grade_upload_term' => $request->integer('term'),
            'grade_upload_year' => $request->integer('year'),
            'grade_upload_parsed' => $parsed,
        ]);

        return redirect()
            ->route('grade-reports.create', [
                'term' => $request->integer('term'),
                'year' => $request->integer('year'),
                'return' => 'dashboard',
            ])
            ->with('status', 'อ่านไฟล์ PDF สำเร็จ กรุณาตรวจสอบข้อมูลและเลือกประเภทรายวิชาก่อนบันทึก');
    }

    public function my(Request $request): View
    {
        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());

        $reports = collect();
        $username = $this->resolveStaffUsername();
        if ($username) {
            $reports = GradeReport::query()
                ->with('gradeStds')
                ->where('username', $username)
                ->where('term', (string) $term)
                ->where('year', (string) $year)
                ->orderByDesc('created_stamp')
                ->orderByDesc('grade_id')
                ->get();
        }

        return view('grade-reports.my', [
            'reports' => $reports,
            'term' => $term,
            'year' => $year,
            'years' => AcademicTerm::yearOptions(),
        ]);
    }

    public function approve(): View
    {
        $role = session('scigrade_role', 'dept_admin');
        abort_if(! in_array($role, ['dept_admin', 'faculty_admin'], true), 403);

        return view('grade-reports.approve', compact('role'));
    }

    public function reports(): View
    {
        $role = session('scigrade_role', 'instructor');

        return view('grade-reports.reports', compact('role'));
    }

    public function printSummary(Request $request): View
    {
        $role = session('scigrade_role', 'dept_admin');
        abort_if(! in_array($role, ['dept_admin', 'faculty_admin'], true), 403);

        $query = GradeReport::query()->with('gradeStds')->orderBy('subject_code');

        if ($role === 'dept_admin') {
            $query->whereIn('approv', [0, 1, 2, -1]);
        } else {
            if ($request->filled('fac')) {
                $query->whereHas('gradeStds', fn ($q) => $q->where('fac', 'like', '%'.$request->fac.'%'));
            }
            if ($request->filled('approv')) {
                $query->where('approv', $request->integer('approv'));
            } else {
                $query->where('approv', 2);
            }
        }

        $reports = $query->get();

        return view('grade-reports.print-summary', [
            'reports' => $reports,
            'role' => $role,
            'fac' => $request->get('fac'),
        ]);
    }

    public function print(GradeReport $gradeReport): View
    {
        if (session('scigrade_role', 'instructor') === 'instructor') {
            abort_unless($gradeReport->username === session('staff_username'), 403);
        }

        $gradeReport->load('gradeStds');

        $staff = \App\Models\TblUser::query()
            ->with('titleRelation')
            ->find($gradeReport->username);

        return view('grade-reports.print', [
            'gradeReport' => $gradeReport,
            'teacherSignName' => $staff?->displayName() ?? $gradeReport->teacher,
            'printedAt' => ThaiDateTime::formatPrintFooter(),
        ]);
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
