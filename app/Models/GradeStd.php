<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeStd extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'grade_std';

    protected $primaryKey = 'grade_std_id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'grade_id',
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
            'num_a' => 'integer',
            'num_bb' => 'integer',
            'num_b' => 'integer',
            'num_cc' => 'integer',
            'num_c' => 'integer',
            'num_dd' => 'integer',
            'num_d' => 'integer',
            'num_f' => 'integer',
            'num_ff' => 'integer',
            'num_i' => 'integer',
            'num_s' => 'integer',
            'num_v' => 'integer',
            'num_w' => 'integer',
            'num_out' => 'integer',
            'numstdevz' => 'integer',
        ];
    }

    public function gradeReport(): BelongsTo
    {
        return $this->belongsTo(GradeReport::class, 'grade_id', 'grade_id');
    }
}
