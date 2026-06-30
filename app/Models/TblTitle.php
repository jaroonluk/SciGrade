<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TblTitle extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'tbltitle';

    protected $primaryKey = 'title_id';

    public $timestamps = false;

    public function users(): HasMany
    {
        return $this->hasMany(TblUser::class, 'title', 'title_id');
    }
}
