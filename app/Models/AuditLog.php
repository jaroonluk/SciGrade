<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'audit_log_scigrad';

    protected $primaryKey = 'log_id';

    public $incrementing = true;

    public const UPDATED_AT = null;

    protected $fillable = [
        'event',
        'actor_username',
        'actor_role',
        'impersonator_username',
        'subject_type',
        'subject_id',
        'ip_address',
        'user_agent',
        'request_method',
        'request_path',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(TblUser::class, 'actor_username', 'username');
    }

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(TblUser::class, 'impersonator_username', 'username');
    }
}
