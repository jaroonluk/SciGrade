<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblProgramQa extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'tblprogram_qa';

    public $timestamps = false;

    protected $primaryKey = 'programid';

    protected $keyType = 'string';

    public $incrementing = false;

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
}
