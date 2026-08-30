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
    public function it_classifies_program_types_from_science_faculty_level_ids(): void
    {
        foreach ([31, 51, 71, '31', '51'] as $levelId) {
            $this->assertSame(
                RegGradeDumpService::PROGRAM_TYPE_REGULAR,
                RegGradeDumpService::classifyProgramLevelId($levelId),
                'LEVELID '.$levelId.' should be regular',
            );
        }

        $this->assertSame(
            RegGradeDumpService::PROGRAM_TYPE_SPECIAL,
            RegGradeDumpService::classifyProgramLevelId(34),
        );

        foreach ([33, 35, 53, 73, '33'] as $levelId) {
            $this->assertSame(
                RegGradeDumpService::PROGRAM_TYPE_INTERNATIONAL,
                RegGradeDumpService::classifyProgramLevelId($levelId),
                'LEVELID '.$levelId.' should be international',
            );
        }

        $this->assertNull(RegGradeDumpService::classifyProgramLevelId(99));
        $this->assertNull(RegGradeDumpService::classifyProgramLevelId(null));
        $this->assertNull(RegGradeDumpService::classifyProgramLevelId(''));
    }
}
