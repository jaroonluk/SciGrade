<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TblProgramQa extends Model
{
    /** @var list<int> */
    public const ALLOWED_DEPARTMENT_IDS = [5, 6, 7, 8, 9, 10, 11, 12, 17, 21, 25, 31, 32, 35, 36, 37];

    protected $connection = 'scigrad';

    protected $table = 'tblprogram_qa';

    public $timestamps = false;

    protected $primaryKey = 'programid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'programid',
        'programname',
        'departmentid',
        'depart_id',
        'department_id',
        'typestudy',
    ];

    public static function forDepartment(?int $departmentId): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::query()->orderBy('typestudy');

        if (in_array($departmentId, [15, 17], true)) {
            return $query->get();
        }

        return $query
            ->where(function ($q) use ($departmentId) {
                $q->where('department_id', (string) $departmentId)
                    ->orWhereIn('programid', ['46', '47']);
            })
            ->get();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(TblDepartment::class, 'department_id', 'department_id');
    }

    public function typestudyLabel(): string
    {
        return match ((string) $this->typestudy) {
            '3' => 'ปริญญาตรี',
            '5' => 'ปริญญาโท',
            '7' => 'ปริญญาเอก',
            default => (string) $this->typestudy,
        };
    }
}
