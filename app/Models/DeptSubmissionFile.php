<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeptSubmissionFile extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'dept_submission_file';

    protected $primaryKey = 'file_id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'submission_id',
        'original_name',
        'stored_path',
        'uploaded_at',
        'username',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (DeptSubmissionFile $file): void {
            \App\Support\UploadStorage::disk()->delete($file->stored_path);
            if (\App\Support\UploadStorage::diskName() !== 'local') {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($file->stored_path);
            }
        });
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(DeptSubmission::class, 'submission_id', 'submission_id');
    }
}
