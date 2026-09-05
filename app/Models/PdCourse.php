<?php

namespace App\Models;

use App\Support\ThesisCourse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PdCourse extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'pdcourse';

    public $timestamps = false;

    protected $fillable = [
        'subjcode',
        'subjname',
    ];

    /**
     * รายวิชาที่ใช้ในรายงานสอบไล่ — ไม่รวมวิทยานิพนธ์/การศึกษาอิสระ
     */
    public function scopeExamReportable(Builder $query): Builder
    {
        return ThesisCourse::constrainExamReportable($query, $query->getModel()->qualifyColumn('subjname'));
    }

    /**
     * รายวิชาวิทยานิพนธ์ / ดุษฎีนิพนธ์ / การศึกษาอิสระ
     */
    public function scopeThesisOnly(Builder $query): Builder
    {
        return ThesisCourse::constrainThesisOnly($query, $query->getModel()->qualifyColumn('subjname'));
    }
}
