<?php

namespace Tests\Unit;

use App\Models\GradeReport;
use App\Models\GradeReportFile;
use App\Models\GradeStd;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\GradeReportAttachmentNameService;
use App\Services\Instructor\InstructorPendingRegistrarService;
use App\Support\UploadStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstructorPendingRegistrarServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_attaches_pending_upload_as_instructor_registrar_file_on_save(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        try {
            $user = User::factory()->create();
            $report = GradeReport::query()->create([
                'created' => now()->toDateString(),
                'term' => '2',
                'year' => '2568',
                'subject_code' => 'SC101011',
                'subject_code2' => 'SC101011',
                'subject' => 'Test Subject',
                'username' => 'teacher01',
                'score_a' => '0',
                'score_bb' => '0',
                'score_b' => '0',
                'score_cc' => '0',
                'score_c' => '0',
                'score_dd' => '0',
                'score_d' => '0',
                'score_f' => '0',
                'approv' => 0,
            ]);
            GradeStd::query()->create([
                'grade_id' => $report->grade_id,
                'sec' => '1',
                'fac' => 'SC',
                'total_std' => 10,
                'num_a' => 10,
            ]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('scigrad database not available: '.$e->getMessage());
        }

        $sourcePath = 'grade-uploads/'.$user->id.'/SC101011-01.pdf';
        UploadStorage::disk()->put($sourcePath, '%PDF-1.4 sample');

        $service = new InstructorPendingRegistrarService(
            new GradeReportAttachmentNameService,
            app(AuditLogService::class),
        );

        $this->actingAs($user)->withSession([
            InstructorPendingRegistrarService::SESSION_PATH => $sourcePath,
            InstructorPendingRegistrarService::SESSION_NAME => 'SC101011-01.pdf',
            InstructorPendingRegistrarService::SESSION_TERM => 2,
            InstructorPendingRegistrarService::SESSION_YEAR => 2568,
            InstructorPendingRegistrarService::SESSION_SUBJECT => 'SC101011',
            InstructorPendingRegistrarService::SESSION_SECTION => 1,
            InstructorPendingRegistrarService::SESSION_OWNER => $user->id,
        ]);

        $file = $service->attachFromSession($report, 'teacher01', $user->id);

        $this->assertInstanceOf(GradeReportFile::class, $file);
        $this->assertSame(GradeReportFile::TYPE_REGISTRAR, $file->resolvedType());
        $this->assertSame('teacher01', $file->username);
        $this->assertTrue(UploadStorage::disk()->exists($file->stored_path));
        $this->assertFalse(UploadStorage::disk()->exists($sourcePath));
        $this->assertNull(session(InstructorPendingRegistrarService::SESSION_PATH));
    }

    #[Test]
    public function it_skips_attach_when_subject_does_not_match_session(): void
    {
        config(['filesystems.upload_disk' => 'local']);
        Storage::fake('local');

        try {
            $user = User::factory()->create();
            $report = new GradeReport([
                'term' => '2',
                'year' => '2568',
                'subject_code' => 'SC999999',
            ]);
            $report->grade_id = 1;
        } catch (\Throwable $e) {
            $this->markTestSkipped('scigrad database not available: '.$e->getMessage());
        }

        $sourcePath = 'grade-uploads/'.$user->id.'/SC101011-01.pdf';
        UploadStorage::disk()->put($sourcePath, '%PDF-1.4 sample');

        $service = new InstructorPendingRegistrarService(
            new GradeReportAttachmentNameService,
            app(AuditLogService::class),
        );

        $this->actingAs($user)->withSession([
            InstructorPendingRegistrarService::SESSION_PATH => $sourcePath,
            InstructorPendingRegistrarService::SESSION_NAME => 'SC101011-01.pdf',
            InstructorPendingRegistrarService::SESSION_TERM => 2,
            InstructorPendingRegistrarService::SESSION_YEAR => 2568,
            InstructorPendingRegistrarService::SESSION_SUBJECT => 'SC101011',
            InstructorPendingRegistrarService::SESSION_OWNER => $user->id,
        ]);

        $this->assertNull($service->attachFromSession($report, 'teacher01', $user->id));
        $this->assertTrue(UploadStorage::disk()->exists($sourcePath));
    }
}
