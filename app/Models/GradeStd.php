<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeStd extends Model
{
    protected $fillable = [
        'grade_report_id',
        'sec',
        'fac',
        'total_std',
        'num_a',
        'num_bb',
        'num_b',
        'num_cc',
        'num_c',
        'num_dd',
        'num_d',
        'num_f',
        'num_ff',
        'num_i',
        'num_s',
        'num_v',
        'num_w',
        'num_out',
        'evaluationscore',
        'numstdevz',
        'type_course',
    ];

    protected function casts(): array
    {
        return [
            'evaluationscore' => 'decimal:2',
        ];
    }

    public function gradeReport(): BelongsTo
    {
        return $this->belongsTo(GradeReport::class);
    }
}
