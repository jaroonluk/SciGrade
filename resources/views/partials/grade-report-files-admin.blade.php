@php
    use App\Models\GradeReportFile;
    $sortBySection = function ($files) use ($report) {
        return $files->sortBy(function ($file) use ($report) {
            $section = $file->resolvedSection($report);

            return sprintf('%05d-%010d', $section !== null ? (int) $section : 999, (int) $file->file_id);
        })->values();
    };
    $examFiles = $report->files
        ->filter(fn ($f) => $f->resolvedType() === GradeReportFile::TYPE_EXAM_REPORT)
        ->sortBy(fn ($f) => (int) $f->file_id)
        ->values();
    $registrarLatest = GradeReportFile::latestRegistrarPerSection($report->files, $report);
    $regInstructorFiles = $sortBySection($registrarLatest->filter(
        fn ($f) => $f->isInstructorUpload($report)
    ));
    $regDeptFiles = $sortBySection($registrarLatest->filter(
        fn ($f) => $f->isDeptAdminUpload($report)
    ));
    $hasInstructorFiles = $examFiles->isNotEmpty() || $regInstructorFiles->isNotEmpty();
    $hasDeptFiles = $regDeptFiles->isNotEmpty();
    $hasAnyFile = $hasInstructorFiles || $hasDeptFiles;
    $canDeleteRegAdmin = ($allowDeptRegDelete ?? false) && $report->canDeptDeleteRegistrar();
@endphp
<div class="grade-files-by-uploader min-w-[14rem] max-w-[18rem] space-y-2">
    {{-- ส่วนอัปโหลดโดยอาจารย์ --}}
    <section class="rounded-md border border-amber-200/90 bg-gradient-to-b from-amber-50/80 to-white px-2 py-1.5">
        <header class="flex items-center gap-1.5 mb-1.5 pb-1 border-b border-amber-100">
            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[#8B4513]/10 text-[#8B4513] shrink-0">
                <i data-lucide="user" class="w-2.5 h-2.5"></i>
            </span>
            <span class="text-[10px] font-semibold tracking-wide text-[#8B4513]">อาจารย์อัปโหลด</span>
            <span class="ml-auto text-[10px] tabular-nums text-amber-800/60">{{ $examFiles->count() + $regInstructorFiles->count() }}</span>
        </header>
        <div class="flex flex-col gap-0.5 min-h-[1.25rem]">
            @forelse ($examFiles as $file)
                <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                   target="_blank" rel="noopener noreferrer"
                   class="text-xs text-[#8B4513] hover:underline inline-flex items-center gap-1 w-fit"
                   title="{{ \App\Models\GradeReportFile::examReportLabel($loop->iteration) }}">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                    {{ \App\Models\GradeReportFile::examReportLabel($loop->iteration) }}
                </a>
            @empty
            @endforelse

            @foreach ($regInstructorFiles as $file)
                <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                   target="_blank" rel="noopener noreferrer"
                   class="text-xs text-[#8B4513] hover:underline inline-flex items-center gap-1 w-fit js-registrar-instructor-item"
                   title="{{ $file->original_name }}">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                    {{ $file->attachmentLinkLabel('มข.11', $report) }}
                </a>
            @endforeach

            @unless ($hasInstructorFiles)
                <span class="text-[11px] text-amber-800/45">ไม่มีไฟล์</span>
            @endunless
        </div>
    </section>

    {{-- ส่วนอัปโหลดโดย Admin สาขา --}}
    <section class="rounded-md border border-emerald-200/90 bg-gradient-to-b from-emerald-50/70 to-white px-2 py-1.5">
        <header class="flex items-center gap-1.5 mb-1.5 pb-1 border-b border-emerald-100">
            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-emerald-600/10 text-emerald-700 shrink-0">
                <i data-lucide="building-2" class="w-2.5 h-2.5"></i>
            </span>
            <span class="text-[10px] font-semibold tracking-wide text-emerald-800">Admin สาขาอัปโหลด</span>
            <span class="ml-auto text-[10px] tabular-nums text-emerald-800/60">{{ $regDeptFiles->count() }}</span>
        </header>
        <div class="js-registrar-dept-list js-registrar-list flex flex-col gap-0.5 min-h-[1.25rem]" data-grade-id="{{ $report->grade_id }}">
            @forelse ($regDeptFiles as $file)
                <div class="js-reg-admin-file-row inline-flex items-center gap-1 w-fit" data-file-id="{{ $file->file_id }}">
                    <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="text-xs text-emerald-700 hover:underline inline-flex items-center gap-1 font-medium js-reg-admin-file-link"
                       title="{{ $file->deptRegistrarDownloadName($report) }} (เก็บเป็น {{ $file->original_name }})">
                        <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                        {{ $file->attachmentLinkLabel('มข.11', $report) }}
                    </a>
                    @if ($canDeleteRegAdmin)
                        <button type="button"
                            class="btn-delete-reg-admin-file text-red-600 hover:text-red-800 shrink-0"
                            data-grade-id="{{ $report->grade_id }}"
                            data-file-id="{{ $file->file_id }}"
                            data-delete-url="{{ route('dept-admin.reviews.registrar-files.destroy', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                            title="ลบไฟล์ มข.11 (Admin สาขา)">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    @endif
                </div>
            @empty
                <span class="js-registrar-empty js-registrar-dept-empty text-[11px] text-emerald-800/45">ไม่มีไฟล์</span>
            @endforelse
        </div>
    </section>

    @if ($hasAnyFile)
        <a href="{{ route('grade-reports.files.zip', ['gradeReport' => $report->grade_id, 'type' => 'all']) }}"
           class="inline-flex items-center gap-1 text-[11px] font-medium text-[#5C2E1F] hover:underline pt-0.5">
            <i data-lucide="download" class="w-3 h-3"></i> ดาวน์โหลดไฟล์วิชานี้ทั้งหมด
        </a>
    @endif
</div>
