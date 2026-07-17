<?php

namespace App\Services\DeptAdmin;

use App\Models\GradeReport;
use App\Models\TblDepartment;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\AcademicTerm;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentReportExportService
{
    public function __construct(
        private readonly DepartmentReportExportPresenter $presenter,
    ) {}

    /**
     * @param  Collection<int, GradeReport>  $reports
     */
    public function exportPdf(
        Collection $reports,
        TblDepartment $department,
        string $startDate,
        string $endDate,
        int $reportStatus,
        ?int $term = null,
        ?int $year = null,
    ): Response {
        $pdf = Pdf::loadView('dept-admin.reports.export', $this->viewData(
            $reports,
            $department,
            $startDate,
            $endDate,
            $reportStatus,
            'pdf',
            $term,
            $year,
        ))
            ->setPaper('a4', 'landscape')
            ->setOption('defaultFont', 'sarabun')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('dpi', 96);

        $filename = sprintf('grade-report-dept-%s-%s.pdf', $department->department_id, date('Ymd'));

        return $pdf->download($filename);
    }

    /**
     * @param  Collection<int, GradeReport>  $reports
     */
    public function exportWord(
        Collection $reports,
        TblDepartment $department,
        string $startDate,
        string $endDate,
        int $reportStatus,
        ?int $term = null,
        ?int $year = null,
    ): StreamedResponse {
        $html = view('dept-admin.reports.export', $this->viewData(
            $reports,
            $department,
            $startDate,
            $endDate,
            $reportStatus,
            'word',
            $term,
            $year,
        ))->render();

        $filename = sprintf('grade-report-dept-%s-%s.doc', $department->department_id, date('Ymd'));

        return response()->streamDownload(function () use ($html): void {
            echo "\xEF\xBB\xBF".$html;
        }, $filename, [
            'Content-Type' => 'application/msword; charset=UTF-8',
        ]);
    }

    /**
     * @param  Collection<int, GradeReport>  $reports
     * @return array<string, mixed>
     */
    private function viewData(
        Collection $reports,
        TblDepartment $department,
        string $startDate,
        string $endDate,
        int $reportStatus,
        string $format,
        ?int $term = null,
        ?int $year = null,
    ): array {
        $firstReport = $reports->first();
        $courseGroups = $this->presenter->groupBySubjectCode($reports);

        return [
            'reports' => $reports,
            'courseGroups' => $courseGroups,
            'department' => $department,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'reportStatus' => $reportStatus,
            'presenter' => $this->presenter,
            'format' => $format,
            'printedAt' => \App\Support\ThaiDateTime::formatPrintFooter(),
            'exportTerm' => $term ?? (int) ($firstReport?->term ?? AcademicTerm::defaultTerm()),
            'exportYear' => $year ?? (int) ($firstReport?->year ?? AcademicTerm::defaultYear()),
        ];
    }
}
