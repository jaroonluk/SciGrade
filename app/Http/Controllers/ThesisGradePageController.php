<?php

namespace App\Http\Controllers;

use App\Http\Requests\ThesisGrade\SaveThesisGradeRequest;
use App\Models\ThesisGrade;
use App\Services\StaffAuthService;
use App\Services\ThesisGrade\ThesisGradeService;
use App\Services\ThesisGrade\ThesisGradeZipService;
use App\Support\AcademicTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ThesisGradePageController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
        private readonly ThesisGradeService $thesisGrades,
        private readonly ThesisGradeZipService $zipService,
    ) {}

    public function index(Request $request): View
    {
        $username = $this->staffUsername();
        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());

        $reports = ThesisGrade::query()
            ->with(['students', 'files'])
            ->ownedBy($username)
            ->where('term', $term)
            ->where('year', $year)
            ->orderByDesc('updated_at')
            ->orderByDesc('thesis_grade_id')
            ->get();

        return view('thesis-grades.index', [
            'reports' => $reports,
            'term' => $term,
            'year' => $year,
            'years' => AcademicTerm::yearOptions(),
            'staffDisplayName' => $this->staffAuth->displayNameFor(
                auth()->user()->email,
                auth()->user()->name,
            ),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formView(null, [
            'term' => (int) $request->input('term', AcademicTerm::defaultTerm()),
            'year' => (int) $request->input('year', AcademicTerm::defaultYear()),
        ]);
    }

    public function store(SaveThesisGradeRequest $request): RedirectResponse
    {
        try {
            $report = $this->thesisGrades->save(
                $request->validated(),
                $this->staffUsername(),
                $this->staffAuth->teacherNameFor(auth()->user()->email, auth()->user()->name),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return $this->afterSave($request, $report, created: true);
    }

    public function edit(ThesisGrade $thesisGrade): View
    {
        $this->authorize('view', $thesisGrade);
        $thesisGrade->load(['students', 'files']);

        return $this->formView($thesisGrade);
    }

    public function update(SaveThesisGradeRequest $request, ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->authorize('update', $thesisGrade);

        try {
            $report = $this->thesisGrades->save(
                $request->validated(),
                $this->staffUsername(),
                $this->staffAuth->teacherNameFor(auth()->user()->email, auth()->user()->name),
                $thesisGrade,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return $this->afterSave($request, $report, created: false);
    }

    public function submit(Request $request, ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->authorize('submit', $thesisGrade);

        $errors = $this->thesisGrades->submit($thesisGrade);
        if ($errors !== []) {
            return redirect()
                ->route('thesis-grades.edit', $thesisGrade)
                ->with('error', implode("\n", $errors))
                ->with('submit_errors', $errors);
        }

        return redirect()
            ->route('thesis-grades.index', ['term' => $thesisGrade->term, 'year' => $thesisGrade->year])
            ->with('status', 'ส่งผลการเรียนเข้าสาขาแล้ว');
    }

    public function destroy(ThesisGrade $thesisGrade): RedirectResponse
    {
        $this->authorize('delete', $thesisGrade);

        $term = $thesisGrade->term;
        $year = $thesisGrade->year;
        $thesisGrade->delete();

        return redirect()
            ->route('thesis-grades.index', ['term' => $term, 'year' => $year])
            ->with('status', 'ลบร่างเรียบร้อย');
    }

    public function downloadZip(ThesisGrade $thesisGrade): BinaryFileResponse
    {
        $this->authorize('view', $thesisGrade);
        $thesisGrade->load('files');

        try {
            return $this->zipService->downloadReports(
                collect([$thesisGrade]),
                $thesisGrade->displayCode().'-'.$thesisGrade->paddedSection().'-files.zip',
            );
        } catch (RuntimeException $e) {
            abort(404, $e->getMessage());
        }
    }

    private function afterSave(SaveThesisGradeRequest $request, ThesisGrade $report, bool $created): RedirectResponse
    {
        if ($request->input('intent') === 'submit') {
            $errors = $this->thesisGrades->submit($report);
            if ($errors !== []) {
                return redirect()
                    ->route('thesis-grades.edit', ['thesisGrade' => $report, 'step' => 3])
                    ->with('error', implode("\n", $errors))
                    ->with('submit_errors', $errors);
            }

            return redirect()
                ->route('thesis-grades.index', ['term' => $report->term, 'year' => $report->year])
                ->with('status', 'ส่งผลการเรียนเข้าสาขาแล้ว');
        }

        $step = (int) $request->input('step', $created ? 2 : 1);

        return redirect()
            ->route('thesis-grades.edit', ['thesisGrade' => $report, 'step' => max(1, min(3, $step))])
            ->with('status', $created ? 'บันทึกร่างแล้ว — อัปโหลดไฟล์ได้ที่ขั้นที่ 3' : 'บันทึกแล้ว');
    }

    private function formView(?ThesisGrade $report, array $defaults = []): View
    {
        $term = (int) ($report?->term ?? $defaults['term'] ?? AcademicTerm::defaultTerm());
        $year = (int) ($report?->year ?? $defaults['year'] ?? AcademicTerm::defaultYear());

        return view('thesis-grades.form', [
            'report' => $report,
            'term' => $term,
            'year' => $year,
            'years' => AcademicTerm::yearOptions(),
            'staffDisplayName' => $this->staffAuth->displayNameFor(
                auth()->user()->email,
                auth()->user()->name,
            ),
            'regUrl' => (string) config('scigrade.reg_url'),
            's0FormUrl' => (string) config('scigrade.s0_letter_form_url'),
            'step' => max(1, min(3, (int) request('step', 1))),
        ]);
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
}
