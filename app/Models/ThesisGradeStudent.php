<?php

namespace App\Models;

use App\Services\ThesisGrade\ThesisGradeComplianceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ThesisGradeStudent extends Model
{
    public const DEGREE_MASTER = 'master';

    public const DEGREE_DOCTORAL = 'doctoral';

    public $timestamps = false;

    protected $connection = 'scigrad';

    protected $table = 'thesis_grade_student';

    protected $primaryKey = 'student_id';

    public $incrementing = true;

    protected $fillable = [
        'thesis_grade_id',
        'student_code',
        'name_prefix',
        'first_name',
        'last_name',
        'student_name',
        'degree',
        'thesis_terms_count',
        'proposal_approved',
        'grade',
        'credits_registered',
        'credits_passed',
        'progress_credits',
        'completed',
        'defense_date',
        'note',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'thesis_terms_count' => 'integer',
            'proposal_approved' => 'boolean',
            'credits_registered' => 'float',
            'credits_passed' => 'float',
            'progress_credits' => 'float',
            'completed' => 'boolean',
            'defense_date' => 'date',
            'sort_order' => 'integer',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ThesisGrade::class, 'thesis_grade_id', 'thesis_grade_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ThesisGradeFile::class, 'student_id', 'student_id');
    }

    public function degreeLabel(): string
    {
        return $this->degree === self::DEGREE_DOCTORAL ? 'ปริญญาเอก' : 'ปริญญาโท';
    }

    public function isProposalOverdue(): bool
    {
        return ThesisGradeComplianceService::isProposalOverdue(
            (string) $this->degree,
            (int) $this->thesis_terms_count,
            (bool) $this->proposal_approved,
        );
    }

    public function isS0(): bool
    {
        $credits = $this->credits_passed ?? $this->progress_credits;

        return ThesisGradeComplianceService::isS0($this->grade, $credits);
    }

    public function requiresS0Letter(): bool
    {
        $credits = $this->credits_passed ?? $this->progress_credits;

        return ThesisGradeComplianceService::requiresS0Letter(
            (string) $this->degree,
            (int) $this->thesis_terms_count,
            (bool) $this->proposal_approved,
            $this->grade,
            $credits,
        );
    }

    public function displayName(): string
    {
        $composed = trim(preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $this->name_prefix).' '.trim((string) $this->first_name).' '.trim((string) $this->last_name)
        ) ?? '');

        return $composed !== '' ? $composed : (string) $this->student_name;
    }

    /**
     * ค่าว่างหรือเครื่องหมาย <> จากใบ REG ไม่ใช่หมายเหตุที่ผู้ใช้ควรเห็น
     */
    public static function sanitizeNote(mixed $note): ?string
    {
        $note = trim((string) ($note ?? ''));
        if ($note === '' || preg_match('/^(<>|&lt;&gt;|< >)$/u', $note) === 1) {
            return null;
        }

        return $note;
    }

    public function requiresDefenseDate(): bool
    {
        return ThesisGradeComplianceService::requiresDefenseDate((bool) $this->completed, $this->defense_date);
    }

    public function hasS0Letter(?ThesisGrade $report = null): bool
    {
        $files = $report?->relationLoaded('files')
            ? $report->files
            : $this->files;

        return $files->contains(
            fn (ThesisGradeFile $file) => $file->isS0Letter() && (int) $file->student_id === (int) $this->student_id
        );
    }
}
