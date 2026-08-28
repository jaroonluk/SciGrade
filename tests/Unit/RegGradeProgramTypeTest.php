<?php

namespace Tests\Unit;

use App\Services\FacultyAdmin\RegGradeDumpService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegGradeProgramTypeTest extends TestCase
{
    #[Test]
    public function it_classifies_international_special_and_regular_programs(): void
    {
        $this->assertSame(
            [RegGradeDumpService::PROGRAM_TYPE_INTERNATIONAL],
            RegGradeDumpService::classifyProgramName('วิทยาศาสตรบัณฑิต (นานาชาติ)', 'Bachelor of Science (International)'),
        );

        $this->assertSame(
            [RegGradeDumpService::PROGRAM_TYPE_SPECIAL],
            RegGradeDumpService::classifyProgramName('วิทยาศาสตรบัณฑิต (โครงการพิเศษ)', 'Bachelor of Science (Special)'),
        );

        $this->assertSame(
            [
                RegGradeDumpService::PROGRAM_TYPE_INTERNATIONAL,
                RegGradeDumpService::PROGRAM_TYPE_SPECIAL,
            ],
            RegGradeDumpService::classifyProgramName('วิทยาศาสตรบัณฑิต (นานาชาติ) โครงการพิเศษ', 'B.Sc. International Special'),
        );

        $this->assertSame(
            [RegGradeDumpService::PROGRAM_TYPE_REGULAR],
            RegGradeDumpService::classifyProgramName('วิทยาศาสตรบัณฑิต', 'Bachelor of Science'),
        );
    }
}
