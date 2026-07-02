<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeTerm extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'grade_term';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'term',
        'year',
    ];
}
