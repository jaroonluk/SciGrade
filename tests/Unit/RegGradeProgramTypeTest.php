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

    #[Test]
    public function it_classifies_reg_class_level_ids(): void
    {
        $this->assertSame(RegGradeDumpService::PROGRAM_TYPE_REGULAR, RegGradeDumpService::classifyLevelId(31));
        $this->assertSame(RegGradeDumpService::PROGRAM_TYPE_REGULAR, RegGradeDumpService::classifyLevelId('51'));
        $this->assertSame(RegGradeDumpService::PROGRAM_TYPE_REGULAR, RegGradeDumpService::classifyLevelId(71));
        $this->assertSame(RegGradeDumpService::PROGRAM_TYPE_SPECIAL, RegGradeDumpService::classifyLevelId(34));

        foreach ([33, 35, 53, 73] as $levelId) {
            $this->assertSame(
                RegGradeDumpService::PROGRAM_TYPE_INTERNATIONAL,
                RegGradeDumpService::classifyLevelId($levelId),
            );
        }

        $this->assertNull(RegGradeDumpService::classifyLevelId(99));
        $this->assertNull(RegGradeDumpService::classifyLevelId(null));
    }
}
