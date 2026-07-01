<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeReport extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'grade_report';

    protected $primaryKey = 'grade_id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'created',
        'term',
        'year',
        'subject_code',
        'subject_code2',
        'subject',
        'username',
        'score_a',
        'score_bb',
        'score_b',
        'score_cc',
        'score_c',
        'score_dd',
        'score_d',
        'score_f',
        'mean',
        'sd',
        'reasonid',
        'reason',
        'teacher',
        'approv',
        'dateapprove1',
        'dateapprove2',
        'type_course',
        'programid',
        'degree',
        'selecttype',
        'totalnumstdevz',
        'totalevaluationscore',
        'statuseva',
        'intflag',
    ];

    protected function casts(): array
    {
        return [
            'created' => 'date',
            'approv' => 'integer',
            'reasonid' => 'integer',
            'degree' => 'integer',
            'selecttype' => 'integer',
            'statuseva' => 'integer',
            'intflag' => 'integer',
            'totalnumstdevz' => 'integer',
            'totalevaluationscore' => 'float',
        ];
    }

    public function gradeStds(): HasMany
    {
        return $this->hasMany(GradeStd::class, 'grade_id', 'grade_id');
    }

    public function statusLabel(): string
    {
        return match ((int) $this->approv) {
            1 => 'ผ่านที่ประชุมกรรมการสาขาวิชา',
            2 => 'ผ่านที่ประชุมกรรมการคณะ',
            -1 => 'ส่งกลับแก้ไข',
            default => 'รอดำเนินการ / ยังไม่อนุมัติ',
        };
    }

    public function statusShortLabel(): string
    {
        return match ((int) $this->approv) {
            1 => 'สาขาอนุมัติแล้ว',
            2 => 'คณะอนุมัติแล้ว',
            -1 => 'ส่งกลับแก้ไข',
            default => 'รออนุมัติ',
        };
    }

    public function approvalStep(): int
    {
        return match ((int) $this->approv) {
            1 => 1,
            2 => 2,
            default => 0,
        };
    }

    public function canEdit(): bool
    {
        return in_array((int) $this->approv, [0, -1], true);
    }

    public function canPrint(): bool
    {
        return $this->gradeStds->isNotEmpty();
    }

    public function totalStudents(): int
    {
        return (int) $this->gradeStds->sum(fn ($row) => (int) $row->total_std);
    }

    public function termLabel(): string
    {
        return match ((int) $this->term) {
            1 => 'ภาคต้น',
            2 => 'ภาคปลาย',
            default => 'ภาคการศึกษาพิเศษ',
        };
    }
}
