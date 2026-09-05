<?php

namespace Tests\Unit;

use App\Support\SubjectDegree;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubjectDegreeTest extends TestCase
{
    #[Test]
    public function it_treats_undergraduate_codes_as_bachelor(): void
    {
        $this->assertSame(SubjectDegree::BACHELOR, SubjectDegree::fromSubjectCode('SC101011'));
        $this->assertSame(SubjectDegree::BACHELOR, SubjectDegree::fromSubjectCode('SC213501'));
        $this->assertFalse(SubjectDegree::isGraduate('SC101011'));
    }

    #[Test]
    public function it_detects_graduate_codes_from_the_level_digit(): void
    {
        $this->assertSame(SubjectDegree::MASTER, SubjectDegree::fromSubjectCode('SC215501'));
        $this->assertSame(SubjectDegree::DOCTORAL, SubjectDegree::fromSubjectCode('SC217701'));
        $this->assertTrue(SubjectDegree::isGraduate('SC215501'));
        $this->assertTrue(SubjectDegree::isGraduate('SC101011', 5));
    }
}
