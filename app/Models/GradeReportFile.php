<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GradeReportFile extends Model
{
    protected $connection = 'scigrad';

    protected $table = 'grade_report_file';

    protected $primaryKey = 'file_id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'grade_id',
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
        static::deleting(function (GradeReportFile $file): void {
            Storage::disk('local')->delete($file->stored_path);
        });
    }

    public function gradeReport(): BelongsTo
    {
        return $this->belongsTo(GradeReport::class, 'grade_id', 'grade_id');
    }
}
