<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradReport2 extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'grad_report2';

    public $incrementing = false;

    protected $primaryKey = null;

    public $timestamps = true;

    protected $fillable = [
        'subject_code2',
        'subject_code',
        'subject',
        'username',
    ];
}
