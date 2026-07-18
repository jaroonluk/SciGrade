<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDepartmentAccess extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'staff_department_access';

    protected $fillable = [
        'username',
        'department_id',
    ];

    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TblUser::class, 'username', 'username');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(TblDepartment::class, 'department_id', 'department_id');
    }
}
