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
        'student_name',
        'degree',
        'thesis_terms_count',
        'proposal_approved',
        'grade',
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
        return ThesisGradeComplianceService::isS0($this->grade, $this->progress_credits);
    }

    public function requiresS0Letter(): bool
    {
        return ThesisGradeComplianceService::requiresS0Letter(
            (string) $this->degree,
            (int) $this->thesis_terms_count,
            (bool) $this->proposal_approved,
            $this->grade,
            $this->progress_credits,
        );
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
