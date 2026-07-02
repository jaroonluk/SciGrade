<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TblDepartment extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'tbldepartment';

    protected $primaryKey = 'department_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'department_name',
        'department_name_en',
    ];
}
