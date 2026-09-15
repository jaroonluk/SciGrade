<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DepartmentSubjectPattern extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'department_subject_pattern';

    public const EDUCATION_BACHELOR = 'bachelor';

    public const EDUCATION_GRADUATE = 'graduate';

    protected $fillable = [
        'department_id',
        'education_level',
        'pattern',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(TblDepartment::class, 'department_id', 'department_id');
    }

    public static function hasEducationLevelColumn(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        try {
            $cached = Schema::connection('scigrad')->hasColumn('department_subject_pattern', 'education_level');
        } catch (Throwable) {
            $cached = false;
        }

        return $cached;
    }

    public static function normalizeEducationLevel(?string $value): string
    {
        return $value === self::EDUCATION_GRADUATE
            ? self::EDUCATION_GRADUATE
            : self::EDUCATION_BACHELOR;
    }

    public static function label(?string $value): string
    {
        return self::normalizeEducationLevel($value) === self::EDUCATION_GRADUATE
            ? 'บัณฑิตศึกษา'
            : 'ปริญญาตรี';
    }

    /**
     * แปลงค่าจากฟอร์มรายงาน (bachelor/master/doctoral/graduate/all) เป็นระดับของเงื่อนไขรหัส
     */
    public static function fromReportFilter(?string $value): ?string
    {
        return match (strtolower(trim((string) $value))) {
            self::EDUCATION_BACHELOR => self::EDUCATION_BACHELOR,
            'master', 'doctoral', self::EDUCATION_GRADUATE => self::EDUCATION_GRADUATE,
            default => null,
        };
    }
}
