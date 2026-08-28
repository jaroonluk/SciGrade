<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GradeType extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'grade_type';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'nameng',
        'namethai',
    ];

    /**
     * คณะสำหรับฟอร์มรายงานผลสอบ — เรียงแบบระบบเดิม (id 3 ขึ้นก่อน แล้วตาม nameng)
     *
     * @return Collection<int, self>
     */
    public static function forForm(): Collection
    {
        return static::query()
            ->whereNotNull('nameng')
            ->where('nameng', '!=', '')
            ->orderByRaw('CASE WHEN id IN (3) THEN 0 ELSE 1 END')
            ->orderBy('nameng')
            ->get(['id', 'nameng', 'namethai']);
    }
}
