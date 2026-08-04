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
}
