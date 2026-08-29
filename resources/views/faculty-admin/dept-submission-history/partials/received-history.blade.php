{{-- ประวัติเอกสารที่รับแล้ว แยกตามสาขา --}}
@php
    $facultyTermLabel = match ((int) $term) {
        1 => 'ภาคต้น',
        2 => 'ภาคปลาย',
        default => 'ภาคการศึกษาพิเศษ',
    };
@endphp

@if ($receivedDeptSubmissionsGrouped->isEmpty())
    <p class="text-sm text-gray-500">
        ยังไม่มีประวัติการรับเอกสารใน{{ $facultyTermLabel }} ปีการศึกษา {{ $year }}
    </p>
@else
    <p class="text-xs text-[#7A4A3A]/70 mb-3">
        {{ $facultyTermLabel }} ปีการศึกษา {{ $year }} — รับแล้ว {{ $receivedDeptSubmissionsGrouped->count() }} หน่วยงาน
    </p>
    <div class="space-y-4">
        @foreach ($receivedDeptSubmissionsGrouped as $departmentId => $submissions)
            @php
                $first = $submissions->first();
                $deptName = $first->department?->department_name ?? 'สาขา #'.$departmentId;
            @endphp
            <div class="rounded-lg border border-green-200 bg-white overflow-hidden">
                <div class="px-4 py-3 bg-green-50 border-b border-green-200">
                    <p class="font-semibold text-[#5C2E1F] flex items-center gap-2">
                        <i data-lucide="building" class="w-4 h-4 text-green-800"></i>
                        {{ $deptName }}
                    </p>
                    <p class="text-xs text-[#7A4A3A]/70 mt-0.5">รับเอกสารแล้ว {{ $submissions->count() }} รอบ</p>
                </div>
                <div class="divide-y divide-green-100">
                    @foreach ($submissions as $submission)
                        @php $submittedAt = $submission->latestSubmittedAt(); @endphp
                        <div class="px-4 py-3">
                            <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                                <div class="text-sm text-[#7A4A3A]/90">
                                    <p class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.65rem] font-semibold {{ $submission->isGraduate() ? 'bg-violet-100 text-violet-800 border border-violet-200' : 'bg-sky-100 text-sky-800 border border-sky-200' }}">
                                            {{ $submission->educationLevelLabel() }}
                                        </span>
                                        <span class="font-medium text-[#5C2E1F]">ส่งเมื่อ</span>
                                        {{ \App\Support\ThaiDateTime::formatDateTime($submittedAt) }}
                                    </p>
                                    <p class="mt-0.5">
                                        <span class="font-medium text-[#5C2E1F]">รับเมื่อ</span>
                                        {{ \App\Support\ThaiDateTime::formatDateTime($submission->received_at) }}
                                        <span class="text-gray-500">โดย {{ $submission->receiverDisplayName() }}</span>
                                    </p>
                                </div>
                                <span class="inline-block px-2 py-0.5 rounded text-xs bg-green-100 text-green-800 shrink-0">
                                    รับแล้ว — {{ $submission->files->count() }} ไฟล์
                                </span>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                @foreach ($submission->files as $file)
                                    <a href="{{ route('dept-submissions.files.show', $file->file_id) }}{{ $file->uploaded_at ? '?v='.$file->uploaded_at->timestamp : '' }}"
                                       target="_blank" rel="noopener noreferrer"
                                       class="text-sm text-[#8B4513] hover:underline flex items-center gap-1.5">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                                        <span class="truncate" title="{{ $file->original_name }}">{{ $file->original_name }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
