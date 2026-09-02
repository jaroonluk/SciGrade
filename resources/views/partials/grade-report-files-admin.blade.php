@php
    use App\Models\GradeReportFile;
    $examFiles = $report->files->filter(fn ($f) => $f->resolvedType() === GradeReportFile::TYPE_EXAM_REPORT);
    $regInstructorFiles = $report->files->filter(
        fn ($f) => $f->resolvedType() === GradeReportFile::TYPE_REGISTRAR && $f->isInstructorUpload($report)
    );
    $regDeptFiles = $report->files->filter(
        fn ($f) => $f->isDeptAdminUpload($report)
    );
    $hasAnyFile = $examFiles->isNotEmpty() || $regInstructorFiles->isNotEmpty() || $regDeptFiles->isNotEmpty();
    $canDeleteRegAdmin = ($allowDeptRegDelete ?? false) && $report->canDeptDeleteRegistrar();
@endphp
<div class="space-y-1.5 min-w-[12rem]">
    <div class="flex flex-col gap-0.5">
        @forelse ($examFiles as $file)
            <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
               target="_blank" rel="noopener noreferrer"
               class="text-xs text-[#8B4513] hover:underline inline-flex items-center gap-1 w-fit"
               title="{{ $file->original_name }}">
                <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                {{ $file->attachmentLinkLabel('แบบรายงานผลการสอบไล่', $report) }}
            </a>
        @empty
            {{-- ไม่แสดงถ้าไม่มี --}}
        @endforelse

        @foreach ($regInstructorFiles as $file)
            <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
               target="_blank" rel="noopener noreferrer"
               class="text-xs text-[#8B4513] hover:underline inline-flex items-center gap-1 w-fit js-registrar-instructor-item"
               title="{{ $file->original_name }}">
                <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                {{ $file->attachmentLinkLabel('ใบส่งผลการศึกษา (REG)', $report) }}
            </a>
        @endforeach

        <div class="js-registrar-dept-list js-registrar-list flex flex-col gap-0.5" data-grade-id="{{ $report->grade_id }}">
            @forelse ($regDeptFiles as $file)
                <div class="js-reg-admin-file-row inline-flex items-center gap-1 w-fit" data-file-id="{{ $file->file_id }}">
                    <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="text-xs text-emerald-700 hover:underline inline-flex items-center gap-1 font-medium js-reg-admin-file-link"
                       title="{{ $file->deptRegistrarDownloadName($report) }} (เก็บเป็น {{ $file->original_name }})">
                        <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                        {{ $file->attachmentLinkLabel('ใบส่งผลการศึกษา (REG-Admin)', $report) }}
                    </a>
                    @if ($canDeleteRegAdmin)
                        <button type="button"
                            class="btn-delete-reg-admin-file text-red-600 hover:text-red-800 shrink-0"
                            data-grade-id="{{ $report->grade_id }}"
                            data-file-id="{{ $file->file_id }}"
                            data-delete-url="{{ route('dept-admin.reviews.registrar-files.destroy', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                            title="ลบไฟล์ REG-Admin">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    @endif
                </div>
            @empty
                <span class="js-registrar-empty js-registrar-dept-empty hidden"></span>
            @endforelse
        </div>

        @unless ($hasAnyFile)
            <span class="text-xs text-gray-400">ไม่มีไฟล์</span>
        @endunless
    </div>

    @if ($hasAnyFile)
        <a href="{{ route('grade-reports.files.zip', ['gradeReport' => $report->grade_id, 'type' => 'all']) }}"
           class="inline-flex items-center gap-1 text-[11px] font-medium text-[#5C2E1F] hover:underline">
            <i data-lucide="download" class="w-3 h-3"></i> ดาวน์โหลดไฟล์วิชานี้ทั้งหมด
        </a>
    @endif
</div>
