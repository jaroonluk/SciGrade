<?php

namespace Tests\Unit;

use App\Services\Instructor\RegistrarSectionRequirement;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RegistrarSectionRequirementTest extends TestCase
{
    #[Test]
    public function appending_one_section_does_not_require_other_report_sections(): void
    {
        $needed = RegistrarSectionRequirement::needed(
            reportSections: [1, 5, 7],
            requestedSections: [5],
            pendingSections: [5],
        );

        $this->assertSame([5], $needed);
    }

    #[Test]
    public function pending_only_still_scopes_to_this_session_when_form_omits_sections(): void
    {
        $needed = RegistrarSectionRequirement::needed(
            reportSections: [1, 5, 7],
            requestedSections: [],
            pendingSections: [5],
        );

        $this->assertSame([5], $needed);
    }

    #[Test]
    public function new_report_with_multiple_form_sections_requires_all_of_them(): void
    {
        $needed = RegistrarSectionRequirement::needed(
            reportSections: [1, 2],
            requestedSections: [1, 2],
            pendingSections: [1],
        );

        $this->assertSame([1, 2], $needed);
    }

    #[Test]
    public function without_form_or_pending_falls_back_to_every_section_on_the_report(): void
    {
        $needed = RegistrarSectionRequirement::needed(
            reportSections: [1, 7],
            requestedSections: [],
            pendingSections: [],
        );

        $this->assertSame([1, 7], $needed);
    }
}
