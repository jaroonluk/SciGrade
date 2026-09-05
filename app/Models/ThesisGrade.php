<?php

namespace App\Models;

use App\Support\ThesisCourse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ThesisGrade extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_RECEIVED = 'received';

    protected $connection = 'scigrad';

    protected $table = 'thesis_grade';

    protected $primaryKey = 'thesis_grade_id';

    public $incrementing = true;

    protected $fillable = [
        'term',
        'year',
        'subject_code',
        'subject',
        'section',
        'course_kind',
        'username',
        'teacher',
        'status',
        'checked_proposal',
        'checked_signed',
        'submitted_at',
        'received_at',
        'received_by',
        'return_reason',
        'returned_at',
        'returned_by',
    ];

    protected function casts(): array
    {
        return [
            'term' => 'integer',
            'year' => 'integer',
            'checked_proposal' => 'boolean',
            'checked_signed' => 'boolean',
            'submitted_at' => 'datetime',
            'received_at' => 'datetime',
            'returned_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (ThesisGrade $report): void {
            $report->loadMissing('files', 'students');
            $report->files->each->delete();
            $report->students()->delete();
        });
    }

    public function students(): HasMany
    {
        return $this->hasMany(ThesisGradeStudent::class, 'thesis_grade_id', 'thesis_grade_id')
            ->orderBy('sort_order')
            ->orderBy('student_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ThesisGradeFile::class, 'thesis_grade_id', 'thesis_grade_id')
            ->orderByDesc('uploaded_at')
            ->orderByDesc('file_id');
    }

    public function tsFiles(): Collection
    {
        return $this->files->filter(fn (ThesisGradeFile $file) => $file->isTsReport())->values();
    }

    public function s0Files(): Collection
    {
        return $this->files->filter(fn (ThesisGradeFile $file) => $file->isS0Letter())->values();
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_RETURNED], true);
    }

    public function isDeletable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED => 'รอสาขา',
            self::STATUS_RETURNED => 'ส่งกลับแก้ไข',
            self::STATUS_RECEIVED => 'สาขารับแล้ว',
            default => 'ร่าง',
        };
    }

    public function statusChipClass(): string
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED => 'status-pending',
            self::STATUS_RETURNED => 'status-rejected',
            self::STATUS_RECEIVED => 'status-approved',
            default => 'status-checked',
        };
    }

    public function termLabel(): string
    {
        return match ((int) $this->term) {
            1 => 'ภาคต้น',
            2 => 'ภาคปลาย',
            default => 'ภาคการศึกษาพิเศษ',
        };
    }

    public function courseKindLabel(): string
    {
        return ThesisCourse::courseKindLabel($this->course_kind);
    }

    public function paddedSection(): string
    {
        return str_pad((string) ((int) preg_replace('/\D/', '', (string) $this->section) ?: 0), 2, '0', STR_PAD_LEFT);
    }

    public function displayCode(): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->subject_code) ?: (string) $this->subject_code);
    }

    public function tsFilename(): string
    {
        return sprintf(
            'TS-%s-%s-%d-%d.pdf',
            $this->displayCode(),
            $this->paddedSection(),
            (int) $this->term,
            (int) $this->year,
        );
    }

    public function overdueStudentCount(): int
    {
        return $this->students->filter(fn (ThesisGradeStudent $student) => $student->isProposalOverdue())->count();
    }

    public function missingS0Count(): int
    {
        $this->loadMissing('files');

        return $this->students
            ->filter(fn (ThesisGradeStudent $student) => $student->requiresS0Letter() && ! $student->hasS0Letter($this))
            ->count();
    }

    public function missingDefenseCount(): int
    {
        return $this->students->filter(fn (ThesisGradeStudent $student) => $student->requiresDefenseDate())->count();
    }

    public function scopeOwnedBy(Builder $query, string $username): Builder
    {
        return $query->where('username', $username);
    }
}
