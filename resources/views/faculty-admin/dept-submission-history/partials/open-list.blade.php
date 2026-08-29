{{-- รายการเอกสารรอรับจากสาขา --}}
@php
    $facultyTermLabel = match ((int) $term) {
        1 => 'ภาคต้น',
        2 => 'ภาคปลาย',
        default => 'ภาคการศึกษาพิเศษ',
    };
@endphp

@if ($openDeptSubmissions->isEmpty())
    <p class="text-sm text-gray-500">
        ไม่มีเอกสารรอรับจากสาขาใน{{ $facultyTermLabel }} ปีการศึกษา {{ $year }}
    </p>
@else
    <p class="text-xs text-[#7A4A3A]/70 mb-3">
        {{ $facultyTermLabel }} ปีการศึกษา {{ $year }} — รอรับ {{ $openDeptSubmissions->count() }} รายการ
    </p>
    <div class="space-y-3">
        @foreach ($openDeptSubmissions as $submission)
            @php
                $deptName = $submission->department?->department_name ?? 'สาขา #'.$submission->department_id;
                $submittedAt = $submission->latestSubmittedAt();
            @endphp
            <div class="rounded-lg border border-amber-200 bg-white p-4" data-faculty-submission="{{ $submission->submission_id }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-[#5C2E1F] flex flex-wrap items-center gap-2">
                            สาขาวิชา: {{ $deptName }}
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.65rem] font-semibold {{ $submission->isGraduate() ? 'bg-violet-100 text-violet-800 border border-violet-200' : 'bg-sky-100 text-sky-800 border border-sky-200' }}">
                                {{ $submission->educationLevelLabel() }}
                            </span>
                        </p>
                        <p class="text-sm text-[#7A4A3A]/80 mt-1">
                            ส่งเมื่อ {{ \App\Support\ThaiDateTime::formatDateTime($submittedAt) }}
                            — {{ $submission->files->count() }} ไฟล์
                        </p>
                        <div class="flex flex-col gap-1.5 mt-3">
                            @foreach ($submission->files as $file)
                                <a href="{{ route('dept-submissions.files.show', $file->file_id) }}{{ $file->uploaded_at ? '?v='.$file->uploaded_at->timestamp : '' }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="text-sm text-[#8B4513] hover:underline flex items-center gap-1.5">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                                    <span class="truncate" title="{{ $file->original_name }}">{{ $file->original_name }}</span>
                                    <span class="text-xs text-gray-500 shrink-0">{{ $file->uploaded_at?->format('d/m/Y H:i') }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <button type="button"
                        class="btn-receive-dept-submission px-4 py-2 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800 shrink-0"
                        data-submission-id="{{ $submission->submission_id }}"
                        data-department-name="{{ $deptName }}"
                        data-education-label="{{ $submission->educationLevelLabel() }}">
                        รับเอกสาร
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
