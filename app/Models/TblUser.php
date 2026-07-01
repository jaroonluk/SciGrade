<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TblUser extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'tbluser';

    protected $primaryKey = 'username';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public function titleRelation(): BelongsTo
    {
        return $this->belongsTo(TblTitle::class, 'title', 'title_id');
    }

    public static function findByEmail(string $email): ?self
    {
        return static::query()
            ->with('titleRelation')
            ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($email))])
            ->first();
    }

    public function displayName(): string
    {
        $prefix = $this->titleRelation?->title_name_s ?? '';

        return trim(trim($prefix).' '.$this->fname.' '.$this->lname);
    }

    public function teacherName(): string
    {
        return trim($this->fname.' '.$this->lname);
    }
}
