<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeReport extends Model
{
    protected $fillable = [
        'user_id',
        'report_date',
        'term',
        'year',
        'subject_code',
        'subject_code2',
        'subject',
        'teacher',
        'selecttype',
        'degree',
        'programid',
        'type_course',
        'mean',
        'sd',
        'reasonid',
        'reason',
        'statuseva',
        'totalnumstdevz',
        'totalevaluationscore',
        'intflag',
        'score_a',
        'score_bb',
        'score_b',
        'score_cc',
        'score_c',
        'score_dd',
        'score_d',
        'score_f',
        'approv',
        'rejection_reason',
        'dateapprove2',
        'dept_approved_at',
        'faculty_approved_at',
        'remark',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'mean' => 'decimal:2',
            'sd' => 'decimal:2',
            'totalevaluationscore' => 'decimal:2',
            'dateapprove2' => 'datetime',
            'dept_approved_at' => 'datetime',
            'faculty_approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gradeStds(): HasMany
    {
        return $this->hasMany(GradeStd::class);
    }

    public function statusLabel(): string
    {
        return match ($this->approv) {
            1 => 'สาขาอนุมัติ',
            2 => 'คณะอนุมัติ',
            -1 => 'ส่งกลับแก้ไข',
            default => 'ยังไม่ผ่านกรรมการ',
        };
    }
}
