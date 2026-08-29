@php
    use App\Models\GradeReportFile;
    $examFiles = $report->files->filter(fn ($f) => $f->resolvedType() === GradeReportFile::TYPE_EXAM_REPORT);
    $regFiles = $report->files->filter(fn ($f) => $f->resolvedType() === GradeReportFile::TYPE_REGISTRAR);
@endphp
<div class="space-y-2 min-w-[12rem]">
    <div>
        <p class="text-[10px] font-semibold text-[#7A4A3A] mb-0.5">แบบรายงานผลการสอบไล่</p>
        @if ($examFiles->isEmpty())
            <span class="text-xs text-gray-400">ไม่มีไฟล์</span>
        @else
            <div class="flex flex-col gap-1">
                @foreach ($examFiles as $file)
                    <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                       target="_blank" class="text-xs text-[#8B4513] hover:underline truncate" title="{{ $file->original_name }}">
                        {{ $file->original_name }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
    <div>
        <p class="text-[10px] font-semibold text-[#7A4A3A] mb-0.5">ใบส่งผลการศึกษา (REG)</p>
        <div class="js-registrar-list flex flex-col gap-1" data-grade-id="{{ $report->grade_id }}">
            @if ($regFiles->isEmpty())
                <span class="js-registrar-empty text-xs text-gray-400">ไม่มีไฟล์</span>
            @else
                @foreach ($regFiles as $file)
                    <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                       target="_blank" class="text-xs text-[#8B4513] hover:underline truncate" title="{{ $file->original_name }}">
                        {{ $file->original_name }}
                    </a>
                @endforeach
            @endif
        </div>
    </div>
    @if ($report->files->isNotEmpty())
        <a href="{{ route('grade-reports.files.zip', ['gradeReport' => $report->grade_id, 'type' => 'all']) }}"
           class="inline-flex items-center gap-1 text-[11px] font-medium text-[#5C2E1F] hover:underline">
            <i data-lucide="download" class="w-3 h-3"></i> ดาวน์โหลดไฟล์วิชานี้
        </a>
    @endif
</div>
