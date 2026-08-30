@php
    $isGraduate = $educationLevel === \App\Models\DeptSubmission::EDUCATION_GRADUATE;
    $shortLabel = $isGraduate ? 'ป.บัณฑิต' : 'ป.ตรี';
    $fullLabel = $isGraduate ? 'ระดับบัณฑิตศึกษา' : 'ระดับปริญญาตรี';
    $tone = $isGraduate ? 'graduate' : 'bachelor';
    $canModify = (bool) ($submission?->isOpen());
    $files = $submission?->files ?? collect();
@endphp
<div class="dept-submit-lane dept-submit-lane-{{ $tone }} rounded-xl border p-4 flex flex-col gap-3"
     data-dept-submission
     data-department-id="{{ $departmentId }}"
     data-term="{{ $term }}"
     data-year="{{ $year }}"
     data-education-level="{{ $educationLevel }}"
     data-can-modify="{{ $canModify ? '1' : '0' }}">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="dept-submit-kicker">กำลังส่งเอกสาร</p>
            <h5 class="dept-submit-title">{{ $shortLabel }}</h5>
            <p class="dept-submit-sub">{{ $fullLabel }}</p>
        </div>
        <div class="text-right shrink-0">
            @if ($submission)
                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $submission->isOpen() ? 'bg-amber-100 text-amber-900' : 'bg-green-100 text-green-800' }}">
                    {{ $submission->statusLabel() }}
                </span>
                @if ($submission->isOpen())
                    <p class="text-[11px] text-gray-500 mt-1">รอบที่ {{ $submission->submission_id }}</p>
                @endif
            @else
                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">พร้อมส่ง</span>
            @endif
        </div>
    </div>

    @if ($files->isEmpty())
        <p class="text-xs text-gray-500 dept-file-empty-msg">ยังไม่มีไฟล์ในช่องทางนี้</p>
    @else
        <div class="flex flex-col gap-2 dept-file-list">
            @foreach ($files as $file)
                <div class="file-chip dept-file-row items-start sm:items-center flex-wrap" data-file-id="{{ $file->file_id }}">
                    <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 {{ $isGraduate ? 'text-violet-700' : 'text-sky-700' }}"></i>
                    <a href="{{ route('dept-submissions.files.show', $file->file_id) }}{{ $file->uploaded_at ? '?v='.$file->uploaded_at->timestamp : '' }}"
                       target="_blank" rel="noopener noreferrer"
                       class="dept-file-name hover:underline truncate max-w-[14rem]" title="{{ $file->original_name }}">
                        {{ $file->original_name }}
                    </a>
                    <span class="text-xs text-gray-500 dept-file-uploaded-at">{{ $file->uploaded_at?->format('d/m/Y H:i') }}</span>
                    @if ($canModify)
                        <button type="button" class="btn-edit-dept-file text-xs font-medium {{ $isGraduate ? 'text-violet-800 hover:text-violet-950' : 'text-sky-800 hover:text-sky-950' }}"
                            data-file-id="{{ $file->file_id }}"
                            data-file-name="{{ $file->original_name }}">แก้ไขชื่อ</button>
                        <label class="btn-replace-dept-file text-xs font-medium cursor-pointer {{ $isGraduate ? 'text-violet-800 hover:text-violet-950' : 'text-sky-800 hover:text-sky-950' }}">
                            เปลี่ยนไฟล์
                            <input type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="hidden dept-file-replace-input" data-file-id="{{ $file->file_id }}">
                        </label>
                        <button type="button" class="btn-delete-dept-file text-red-600 hover:text-red-800"
                            data-file-id="{{ $file->file_id }}" title="ลบไฟล์">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($departmentId)
        <label class="file-upload-zone dept-submit-drop block cursor-pointer mt-auto">
            <input type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="hidden dept-file-upload-input">
            <span class="text-sm font-medium flex items-center gap-1 {{ $isGraduate ? 'text-violet-900' : 'text-sky-900' }}">
                <i data-lucide="upload" class="w-4 h-4"></i>
                อัปโหลดไฟล์{{ $shortLabel }} (PDF / Word)
            </span>
            <span class="block text-[11px] text-gray-500 mt-0.5">ไฟล์ที่เลือกจะเข้าช่องทาง {{ $fullLabel }} ทันที</span>
        </label>
    @endif
</div>
