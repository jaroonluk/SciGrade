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
    $canDeleteDeptReg = ($allowDeptRegDelete ?? false) && $report->canDeptDeleteRegAdminFile();
@endphp
<div class="space-y-1.5 min-w-[12rem]">
    <div class="flex flex-col gap-0.5">
        @forelse ($examFiles as $file)
            <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
               target="_blank" rel="noopener noreferrer"
               class="text-xs text-[#8B4513] hover:underline inline-flex items-center gap-1 w-fit"
               title="{{ $file->original_name }}">
                <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                แบบรายงานผลการสอบไล่
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
                ใบส่งผลการศึกษา (REG)
            </a>
        @endforeach

        <div class="js-registrar-dept-list js-registrar-list flex flex-col gap-0.5" data-grade-id="{{ $report->grade_id }}">
            @forelse ($regDeptFiles as $file)
                <div class="js-reg-admin-file-row inline-flex items-center gap-1.5 flex-wrap" data-file-id="{{ $file->file_id }}">
                    <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="text-xs text-emerald-700 hover:underline inline-flex items-center gap-1 w-fit font-medium"
                       title="{{ $report->deptRegistrarDownloadName() }} (เก็บเป็น {{ $file->original_name }})">
                        <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                        ใบส่งผลการศึกษา (REG-Admin)
                    </a>
                    @if ($canDeleteDeptReg)
                        <form method="POST"
                              action="{{ route('dept-admin.reviews.reg-admin-files.destroy', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                              class="inline js-delete-reg-admin-form"
                              onsubmit="return confirm('ลบไฟล์ REG-Admin นี้?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="text-[10px] text-red-600 hover:text-red-800 font-medium px-1 py-0.5 rounded hover:bg-red-50"
                                title="ลบไฟล์ REG-Admin">
                                ลบ
                            </button>
                        </form>
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
