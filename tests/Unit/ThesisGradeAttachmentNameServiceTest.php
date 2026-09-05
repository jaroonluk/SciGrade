<?php

namespace Tests\Unit;

use App\Models\ThesisGrade;
use App\Models\ThesisGradeStudent;
use App\Services\ThesisGrade\ThesisGradeAttachmentNameService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisGradeAttachmentNameServiceTest extends TestCase
{
    #[Test]
    public function it_builds_ts_filename_from_course_identity(): void
    {
        $report = new ThesisGrade([
            'subject_code' => 'SC123999',
            'section' => '1',
            'term' => 2,
            'year' => 2568,
        ]);

        $names = new ThesisGradeAttachmentNameService;

        $this->assertSame('TS-SC123999-01-2-2568', $names->tsBase($report));
        $this->assertSame('TS-SC123999-01-2-2568.pdf', $names->tsReportName($report));
        $this->assertSame('TS-SC123999-01-2-2568_02.pdf', $names->tsReportName($report, 2));
    }

    #[Test]
    public function it_builds_s0_filename_with_student_code(): void
    {
        $report = new ThesisGrade([
            'subject_code' => 'SC123999',
            'section' => '01',
            'term' => 2,
            'year' => 2568,
        ]);
        $student = new ThesisGradeStudent([
            'student_code' => '653020001-1',
        ]);

        $name = (new ThesisGradeAttachmentNameService)->s0LetterName($report, $student);

        $this->assertSame('TS-SC123999-01-2-2568-S0-6530200011.pdf', $name);
    }
}
