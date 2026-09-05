<?php

namespace Tests\Unit;

use App\Support\ThesisCourse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ThesisCourseTest extends TestCase
{
    #[Test]
    public function it_detects_thesis_dissertation_and_independent_study(): void
    {
        $this->assertTrue(ThesisCourse::isThesisTitle('THESIS'));
        $this->assertTrue(ThesisCourse::isThesisTitle("MASTER'S THESIS"));
        $this->assertTrue(ThesisCourse::isThesisTitle('DOCTORAL DISSERTATION'));
        $this->assertTrue(ThesisCourse::isThesisTitle('INDEPENDENT STUDY'));
        $this->assertTrue(ThesisCourse::isThesisTitle('independent study'));
    }

    #[Test]
    public function it_does_not_treat_regular_courses_or_synthesis_as_thesis(): void
    {
        $this->assertFalse(ThesisCourse::isThesisTitle('PHYSICAL SCIENCE'));
        $this->assertFalse(ThesisCourse::isThesisTitle('ORGANIC SYNTHESIS'));
        $this->assertFalse(ThesisCourse::isThesisTitle('SEMINAR'));
        $this->assertFalse(ThesisCourse::isThesisTitle(''));
        $this->assertFalse(ThesisCourse::isThesisTitle(null));
    }

    #[Test]
    public function it_classifies_course_kind(): void
    {
        $this->assertSame('thesis', ThesisCourse::courseKind("MASTER'S THESIS"));
        $this->assertSame('dissertation', ThesisCourse::courseKind('DOCTORAL DISSERTATION'));
        $this->assertSame('independent_study', ThesisCourse::courseKind('INDEPENDENT STUDY'));
        $this->assertNull(ThesisCourse::courseKind('ORGANIC SYNTHESIS'));
        $this->assertNull(ThesisCourse::courseKind('SEMINAR'));
    }
}
