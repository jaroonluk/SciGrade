<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeptSubmission extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'dept_submission';

    protected $primaryKey = 'submission_id';

    public $incrementing = true;

    public const STATUS_OPEN = 'open';

    public const STATUS_RECEIVED = 'received';

    protected $fillable = [
        'department_id',
        'term',
        'year',
        'status',
        'received_at',
        'received_by',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'term' => 'integer',
            'year' => 'integer',
            'received_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(TblDepartment::class, 'department_id', 'department_id');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(TblUser::class, 'received_by', 'username');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DeptSubmissionFile::class, 'submission_id', 'submission_id')
            ->orderByDesc('uploaded_at')
            ->orderByDesc('file_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_RECEIVED => 'รับเอกสารแล้ว',
            default => 'รอส่ง / กำลังส่ง',
        };
    }

    public function termLabel(): string
    {
        return match ((int) $this->term) {
            1 => 'ภาคต้น',
            2 => 'ภาคปลาย',
            default => 'ภาคการศึกษาพิเศษ',
        };
    }

    public function latestSubmittedAt(): ?\Illuminate\Support\Carbon
    {
        $latestFile = $this->relationLoaded('files')
            ? $this->files->first()
            : $this->files()->orderByDesc('uploaded_at')->first();

        return $latestFile?->uploaded_at ?? $this->created_at;
    }

    public function receiverDisplayName(): string
    {
        return $this->receivedByUser?->displayName() ?? ($this->received_by ?: '-');
    }
}
