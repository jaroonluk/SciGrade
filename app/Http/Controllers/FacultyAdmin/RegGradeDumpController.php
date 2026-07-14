<?php

namespace App\Http\Controllers\FacultyAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyAdmin\RegGradeDumpRequest;
use App\Models\GradeReportReg;
use App\Services\FacultyAdmin\RegGradeDumpService;
use App\Support\AcademicTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class RegGradeDumpController extends Controller
{
    public function __construct(
        private readonly RegGradeDumpService $dumpService,
    ) {}

    public function index(Request $request): View
    {
        $currentBuddhistYear = (int) date('Y') + 543;
        $years = range(2566, max(2566, $currentBuddhistYear));

        $term = (int) $request->input('term', AcademicTerm::defaultTerm());
        $year = (int) $request->input('year', AcademicTerm::defaultYear());
        $q = trim((string) $request->input('q', ''));

        if (! in_array($term, [1, 2, 3], true)) {
            $term = AcademicTerm::defaultTerm();
        }
        if ($year < 2566 || $year > $currentBuddhistYear + 5) {
            $year = AcademicTerm::defaultYear();
        }

        $coursesQuery = GradeReportReg::query()
            ->where('SEMESTER', (string) $term)
            ->where('ACADYEAR', (string) $year)
            ->orderBy('COURSECODE')
            ->orderBy('SECTION')
            ->orderBy('OFFICERNAME');

        if ($q !== '') {
            $like = '%'.$q.'%';
            $coursesQuery->where(function ($query) use ($like) {
                $query->where('COURSECODE', 'like', $like)
                    ->orWhere('COURSENAMEENG', 'like', $like)
                    ->orWhere('OFFICERNAME', 'like', $like)
                    ->orWhere('OFFICERSURNAME', 'like', $like)
                    ->orWhere('KKUMAIL', 'like', $like)
                    ->orWhere('OFFICERID', 'like', $like);
            });
        }

        $courses = $coursesQuery->paginate(50)->withQueryString();

        return view('faculty-admin.settings.reg-grade-dump.index', [
            'years' => $years,
            'term' => $term,
            'year' => $year,
            'q' => $q,
            'courses' => $courses,
            'canConnect' => $this->dumpService->canConnect(),
        ]);
    }

    public function dump(RegGradeDumpRequest $request): RedirectResponse
    {
        $year = $request->integer('year');
        $term = $request->integer('term');

        try {
            $result = $this->dumpService->dump($year, $term);
        } catch (Throwable $e) {
            return redirect()
                ->route('faculty-admin.settings.reg-grade-dump.index', [
                    'term' => $term,
                    'year' => $year,
                ])
                ->withInput()
                ->withErrors(['dump' => 'ไม่สามารถดึงข้อมูลจาก REG ได้: '.$e->getMessage()]);
        }

        $status = sprintf(
            'Download รายวิชา REG ภาค %d/%d เรียบร้อย — พบ %d รายการ เพิ่มใหม่ %d อัปเดต %d ข้าม %d',
            $term,
            $year,
            $result['fetched'],
            $result['inserted'],
            $result['updated'],
            $result['skipped'],
        );

        return redirect()
            ->route('faculty-admin.settings.reg-grade-dump.index', [
                'term' => $term,
                'year' => $year,
            ])
            ->with('status', $status);
    }
}
