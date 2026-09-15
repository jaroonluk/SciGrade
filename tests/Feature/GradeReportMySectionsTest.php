<?php

namespace Tests\Feature;

use App\Models\GradeReport;
use App\Models\GradeStd;
use App\Models\User;
use App\Support\ThaiDateTime;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GradeReportMySectionsTest extends TestCase
{
    #[Test]
    public function my_page_lists_sections_with_edit_and_delete_when_editable(): void
    {
        $this->actingAs(new User(['name' => 'อาจารย์ ทดสอบ', 'email' => 'teacher@kku.ac.th']));

        $report = new GradeReport([
            'subject_code' => 'SC700001',
            'subject' => 'SEMINAR',
            'term' => '2',
            'year' => '2568',
            'approv' => 0,
            'username' => 'teacher01',
        ]);
        $report->grade_id = 305955;
        $empty = new GradeStd(['sec' => '1', 'fac' => 'SC', 'total_std' => 0]);
        $empty->grade_std_id = 11;
        $filled = new GradeStd(['sec' => '5', 'fac' => 'SC', 'total_std' => 8]);
        $filled->grade_std_id = 12;
        $report->setRelation('gradeStds', collect([$empty, $filled]));
        $report->setRelation('files', collect());
        $report->setRelation('approvalLogs', collect());

        $html = view('grade-reports.my', [
            'reports' => collect([$report]),
            'term' => 2,
            'year' => 2568,
            'years' => [2568],
            'staffUsername' => 'teacher01',
        ])->render();

        $this->assertStringContainsString('กลุ่ม 1', $html);
        $this->assertStringContainsString('ยังไม่มีจำนวนนักศึกษา', $html);
        $this->assertStringContainsString('กลุ่ม 5', $html);
        $this->assertStringContainsString('8 คน', $html);
        $this->assertStringContainsString('btn-delete-section', $html);
        $this->assertStringContainsString('wizard_step=5', $html);
    }

    #[Test]
    public function print_preview_uses_landscape_sheet(): void
    {
        $report = new GradeReport([
            'subject_code' => 'SC700001',
            'subject' => 'SEMINAR',
            'teacher' => 'อาจารย์ ทดสอบ',
            'term' => '2',
            'year' => '2568',
            'reason' => '',
            'mean' => 0,
            'sd' => 0,
        ]);
        $report->setRelation('gradeStds', collect([
            new GradeStd(['sec' => '5', 'fac' => 'SC', 'total_std' => 8]),
        ]));

        $html = view('grade-reports.print', [
            'gradeReport' => $report,
            'printStds' => $report->gradeStds,
            'teacherSignName' => 'อาจารย์ ทดสอบ',
            'printedAt' => ThaiDateTime::formatPrintFooter(),
        ])->render();

        $this->assertStringContainsString('size: A4 landscape', $html);
        $this->assertStringContainsString('class="sheet"', $html);
        $this->assertStringContainsString('width: 297mm', $html);
        $this->assertStringContainsString('แสดงแบบแนวนอน', $html);
    }
}
