<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TblPrivilege extends Model
{
    public const SYSTEM_GRADE_REPORT = 11;

    public const LEVEL_SERVICE = 0;

    public const LEVEL_DEPT = 1;

    public const LEVEL_SUPER = 2;

    public const LEVEL_SERVICE_BACHELOR = 3;

    public const LEVEL_SERVICE_GRADUATE = 4;

    protected $connection = 'scigrad';

    protected $table = 'tblprivileges';

    protected $primaryKey = 'privilegs_id';

    public $timestamps = false;

    protected $fillable = [
        'system_id',
        'username',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'system_id' => 'integer',
            'level' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TblUser::class, 'username', 'username');
    }

    public function levelLabel(): string
    {
        return self::labelForLevel((int) $this->level);
    }

    public static function labelForLevel(int $level): string
    {
        return match ($level) {
            self::LEVEL_SUPER => 'Super Admin',
            self::LEVEL_DEPT => 'เจ้าหน้าที่สาขาวิชา',
            self::LEVEL_SERVICE_BACHELOR => 'เจ้าหน้าที่งานบริการ(ป.ตรี)',
            self::LEVEL_SERVICE_GRADUATE => 'เจ้าหน้าที่งานบริการ(ป.บัณฑิต)',
            default => 'เจ้าหน้าที่งานบริการ',
        };
    }

    /**
     * @return list<string>
     */
    public static function filterableLevelValues(): array
    {
        return ['0', '1', '2', '3', '4'];
    }
}
