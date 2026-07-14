<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeReportReg extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'grade_report_reg';

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'COURSECODE',
        'COURSENAMEENG',
        'SECTION',
        'ACADYEAR',
        'SEMESTER',
        'LEVELID',
        'FACULTYID',
        'OFFICERNAME',
        'OFFICERSURNAME',
        'KKUMAIL',
        'OFFICERID',
    ];
}
