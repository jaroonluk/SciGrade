<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeReportApprovalLog extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'grade_report_approval_log';

    protected $primaryKey = 'log_id';

    public $incrementing = true;

    public const UPDATED_AT = null;

    protected $fillable = [
        'grade_id',
        'action',
        'from_status',
        'to_status',
        'approver_username',
        'approver_role',
        'remark',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => 'integer',
            'to_status' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function gradeReport(): BelongsTo
    {
        return $this->belongsTo(GradeReport::class, 'grade_id', 'grade_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(TblUser::class, 'approver_username', 'username');
    }
}
