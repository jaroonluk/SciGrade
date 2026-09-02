<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeReportFile extends Model
{
    public const TYPE_EXAM_REPORT = 'exam_report';

    public const TYPE_REGISTRAR = 'registrar';

    protected $connection = 'scigrad';

    protected $table = 'grade_report_file';

    protected $primaryKey = 'file_id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'grade_id',
        'file_type',
        'original_name',
        'stored_path',
        'uploaded_at',
        'username',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (GradeReportFile $file): void {
            \App\Support\UploadStorage::disk()->delete($file->stored_path);
            if (\App\Support\UploadStorage::diskName() !== 'local') {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($file->stored_path);
            }
        });
    }

    public function gradeReport(): BelongsTo
    {
        return $this->belongsTo(GradeReport::class, 'grade_id', 'grade_id');
    }

    public function typeLabel(): string
    {
        return match ($this->resolvedType()) {
            self::TYPE_REGISTRAR => 'ใบส่งผลการศึกษา (REG)',
            default => 'แบบรายงานผลการสอบไล่',
        };
    }

    public function resolvedType(): string
    {
        $type = (string) ($this->file_type ?? '');

        return $type !== '' ? $type : self::TYPE_EXAM_REPORT;
    }

    public function isRegistrar(): bool
    {
        return $this->resolvedType() === self::TYPE_REGISTRAR;
    }

    /**
     * ไฟล์ที่อาจารย์เจ้าของรายวิชาอัปโหลด (username ตรงกับ grade_report.username)
     * แถวเก่าที่ไม่มี username ถือเป็นของอาจารย์
     */
    public function isInstructorUpload(?GradeReport $report = null): bool
    {
        $report ??= $this->relationLoaded('gradeReport')
            ? $this->gradeReport
            : $this->gradeReport()->first();

        if ($report === null) {
            return true;
        }

        $uploader = trim((string) ($this->username ?? ''));
        if ($uploader === '') {
            return true;
        }

        return $uploader === trim((string) $report->username);
    }

    /**
     * ไฟล์ REG ที่ Admin สาขา (หรือผู้ใช้อื่นที่ไม่ใช่เจ้าของรายวิชา) อัปโหลด
     */
    public function isDeptAdminUpload(?GradeReport $report = null): bool
    {
        return $this->isRegistrar() && ! $this->isInstructorUpload($report);
    }

    /**
     * ป้ายกลุ่มเรียนสำหรับแสดงต่อท้ายชื่อไฟล์ เช่น Sec1
     */
    public function attachmentSectionSuffix(?GradeReport $report = null): ?string
    {
        $section = $this->parseSectionFromStoredName((string) $this->original_name);

        if ($section === null) {
            $report ??= $this->relationLoaded('gradeReport')
                ? $this->gradeReport
                : $this->gradeReport()->with('gradeStds')->first();

            if ($report instanceof GradeReport) {
                $sections = $report->enrollmentSections()
                    ->pluck('sec')
                    ->filter(fn ($sec) => $sec !== '' && $sec !== '-')
                    ->map(fn ($sec) => (string) (int) preg_replace('/\D/', '', (string) $sec))
                    ->unique()
                    ->values();

                if ($sections->count() === 1) {
                    $section = $sections->first();
                }
            }
        }

        if ($section === null || $section === '') {
            return null;
        }

        return 'Sec'.((int) $section);
    }

    /** @deprecated Use attachmentSectionSuffix() */
    public function registrarSectionLabel(?GradeReport $report = null): ?string
    {
        return $this->attachmentSectionSuffix($report);
    }

    /**
     * ข้อความลิงก์ที่แสดงให้ผู้ใช้ เช่น ใบส่งผลการศึกษา (REG-Admin)-Sec1
     */
    public function attachmentLinkLabel(string $baseLabel, ?GradeReport $report = null): string
    {
        $suffix = $this->attachmentSectionSuffix($report);

        return $suffix !== null ? $baseLabel.'-'.$suffix : $baseLabel;
    }

    public function registrarLinkLabel(string $baseLabel, ?GradeReport $report = null): string
    {
        return $this->attachmentLinkLabel($baseLabel, $report);
    }

    /**
     * กลุ่มเรียนของไฟล์นี้ (จากชื่อที่เก็บ หรือกลุ่มเดียวในรายงาน)
     */
    public function resolvedSection(?GradeReport $report = null): ?int
    {
        $section = $this->parseSectionFromStoredName((string) $this->original_name);

        if ($section !== null && $section !== '') {
            return (int) $section;
        }

        $report ??= $this->relationLoaded('gradeReport')
            ? $this->gradeReport
            : $this->gradeReport()->with('gradeStds')->first();

        if ($report instanceof GradeReport) {
            $sections = $report->enrollmentSections()
                ->pluck('sec')
                ->filter(fn ($sec) => $sec !== '' && $sec !== '-')
                ->map(fn ($sec) => (int) preg_replace('/\D/', '', (string) $sec))
                ->unique()
                ->values();

            if ($sections->count() === 1) {
                return (int) $sections->first();
            }
        }

        return null;
    }

    /**
     * ชื่อไฟล์ตอนดาวน์โหลด REG-Admin ตามกลุ่มเรียนของไฟล์นี้
     */
    public function deptRegistrarDownloadName(?GradeReport $report = null): string
    {
        $report ??= $this->relationLoaded('gradeReport')
            ? $this->gradeReport
            : $this->gradeReport()->with('gradeStds')->first();

        if (! $report instanceof GradeReport) {
            return $this->safeDownloadBasename();
        }

        if (! $report->relationLoaded('gradeStds')) {
            $report->load('gradeStds');
        }

        return $report->deptRegistrarDownloadName($this->resolvedSection($report));
    }

    private function safeDownloadBasename(): string
    {
        $base = basename(str_replace('\\', '/', (string) $this->original_name));

        return $base !== '' ? $base : 'file.pdf';
    }

    private function parseSectionFromStoredName(string $name): ?string
    {
        $base = basename(str_replace('\\', '/', $name));

        if (preg_match('/^REG_\d+_\d+_[A-Z0-9]+_(\d{1,2})(?:_\d{2})?\.pdf$/i', $base, $match)) {
            return (string) (int) $match[1];
        }

        if (preg_match('/^\d+_\d+_[A-Z0-9]+_(\d{1,2})(?:_\d{2})?\.pdf$/i', $base, $match)) {
            return (string) (int) $match[1];
        }

        if (preg_match('/^[A-Za-z0-9]+-(\d{1,2})\.pdf$/i', $base, $match)) {
            return (string) (int) $match[1];
        }

        if (preg_match('/^[A-Za-z0-9]+-(\d{1,2})-\d+\.pdf$/i', $base, $match)) {
            return (string) (int) $match[1];
        }

        return null;
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        if ($type === self::TYPE_EXAM_REPORT) {
            return $query->where(function (Builder $inner) use ($type): void {
                $inner->where('file_type', $type)
                    ->orWhereNull('file_type')
                    ->orWhere('file_type', '');
            });
        }

        return $query->where('file_type', $type);
    }

    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return [self::TYPE_EXAM_REPORT, self::TYPE_REGISTRAR];
    }

    /**
     * ประเภทที่ใช้กรองตอนดาวน์โหลด (รวมแยก REG ตามผู้ upload)
     *
     * @return list<string>
     */
    public static function allowedDownloadTypes(): array
    {
        return [
            'all',
            self::TYPE_EXAM_REPORT,
            self::TYPE_REGISTRAR,
            'registrar_instructor',
            'registrar_dept',
        ];
    }
}
