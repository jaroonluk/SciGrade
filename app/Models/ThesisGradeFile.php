<?php

namespace App\Models;

use App\Support\UploadStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ThesisGradeFile extends Model
{
    public const TYPE_TS_REPORT = 'ts_report';

    public const TYPE_S0_LETTER = 's0_letter';

    public $timestamps = false;

    protected $connection = 'scigrad';

    protected $table = 'thesis_grade_file';

    protected $primaryKey = 'file_id';

    public $incrementing = true;

    protected $fillable = [
        'thesis_grade_id',
        'student_id',
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
        static::deleting(function (ThesisGradeFile $file): void {
            UploadStorage::disk()->delete($file->stored_path);
            if (UploadStorage::diskName() !== 'local') {
                Storage::disk('local')->delete($file->stored_path);
            }
        });
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ThesisGrade::class, 'thesis_grade_id', 'thesis_grade_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(ThesisGradeStudent::class, 'student_id', 'student_id');
    }

    public function isTsReport(): bool
    {
        return $this->resolvedType() === self::TYPE_TS_REPORT;
    }

    public function isS0Letter(): bool
    {
        return $this->resolvedType() === self::TYPE_S0_LETTER;
    }

    public function resolvedType(): string
    {
        $type = (string) ($this->file_type ?? '');

        return $type !== '' ? $type : self::TYPE_TS_REPORT;
    }

    public function typeLabel(): string
    {
        return match ($this->resolvedType()) {
            self::TYPE_S0_LETTER => 'หนังสือชี้แจง S=0',
            default => 'ใบส่งเกรดวิทยานิพนธ์ (TS)',
        };
    }

    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return [self::TYPE_TS_REPORT, self::TYPE_S0_LETTER];
    }
}
