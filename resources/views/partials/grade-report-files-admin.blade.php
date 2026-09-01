@php
    use App\Models\GradeReportFile;
    $examFiles = $report->files->filter(fn ($f) => $f->resolvedType() === GradeReportFile::TYPE_EXAM_REPORT);
    $regInstructorFiles = $report->files->filter(
        fn ($f) => $f->resolvedType() === GradeReportFile::TYPE_REGISTRAR && $f->isInstructorUpload($report)
    );
    $regDeptFiles = $report->files->filter(
        fn ($f) => $f->isDeptAdminUpload($report)
    );
@endphp
<div class="space-y-3 min-w-[14rem]">
    <div class="rounded-lg border border-sky-200 bg-sky-50/60 p-2 space-y-2">
        <p class="text-[10px] font-bold uppercase tracking-wide text-sky-900">ไฟล์แนบของอาจารย์</p>
        <div>
            <p class="text-[10px] font-semibold text-sky-800/90 mb-0.5">แบบรายงานผลการสอบไล่</p>
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
            <p class="text-[10px] font-semibold text-sky-800/90 mb-0.5">ใบส่งผลการศึกษา (REG)</p>
            <div class="js-registrar-instructor-list flex flex-col gap-1" data-grade-id="{{ $report->grade_id }}">
                @if ($regInstructorFiles->isEmpty())
                    <span class="js-registrar-instructor-empty text-xs text-gray-400">ไม่มีไฟล์</span>
                @else
                    @foreach ($regInstructorFiles as $file)
                        <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                           target="_blank" class="text-xs text-[#8B4513] hover:underline truncate" title="{{ $file->original_name }}">
                            {{ $file->original_name }}
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-2 space-y-2">
        <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-900">ไฟล์แนบของ Admin สาขา</p>
        <div>
            <p class="text-[10px] font-semibold text-emerald-800/90 mb-0.5">ใบส่งผลการศึกษา (REG)</p>
            <div class="js-registrar-dept-list js-registrar-list flex flex-col gap-1" data-grade-id="{{ $report->grade_id }}">
                @if ($regDeptFiles->isEmpty())
                    <span class="js-registrar-empty js-registrar-dept-empty text-xs text-gray-400">ไม่มีไฟล์</span>
                @else
                    @foreach ($regDeptFiles as $file)
                        <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                           target="_blank"
                           class="text-xs text-emerald-800 hover:underline truncate"
                           title="{{ $report->deptRegistrarDownloadName() }} (เก็บเป็น {{ $file->original_name }})">
                            {{ $report->deptRegistrarDownloadName() }}
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    @if ($report->files->isNotEmpty())
        <a href="{{ route('grade-reports.files.zip', ['gradeReport' => $report->grade_id, 'type' => 'all']) }}"
           class="inline-flex items-center gap-1 text-[11px] font-medium text-[#5C2E1F] hover:underline">
            <i data-lucide="download" class="w-3 h-3"></i> ดาวน์โหลดไฟล์วิชานี้ทั้งหมด
        </a>
    @endif
</div>
