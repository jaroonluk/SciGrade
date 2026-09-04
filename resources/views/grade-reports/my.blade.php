@extends('layouts.scigrad')

@section('title', 'ติดตามรายงานผลการสอบ — SciGrade')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">ติดตามรายงานผลการสอบ</span>
@endsection

@push('styles')
<style>
    .progress-step { flex: 1; text-align: center; position: relative; }
    .progress-step .dot {
        width: 2rem; height: 2rem; border-radius: 9999px; margin: 0 auto .35rem;
        display: flex; align-items: center; justify-content: center;
        font-size: .75rem; font-weight: 700; border: 2px solid #E8C4B8; background: #fff; color: #9ca3af;
    }
    .progress-step.done .dot { background: #166534; border-color: #166534; color: #fff; }
    .progress-step.current .dot { background: #8B4513; border-color: #8B4513; color: #fff; }
    .progress-step.rejected .dot { background: #b91c1c; border-color: #b91c1c; color: #fff; }
    .progress-step .label { font-size: .7rem; line-height: 1.2; color: #7A4A3A; }
    @@media (min-width: 640px) { .progress-step .label { font-size: .8rem; } }
    .report-table th { background: #fdf6f0; color: #5C2E1F; font-weight: 600; }
    .report-table td, .report-table th { padding: .65rem .75rem; border-bottom: 1px solid #f0e0d0; vertical-align: top; }
    .report-table tr:hover td { background: #fffaf5; }
    .action-btn { display: inline-flex; align-items: center; gap: .25rem; padding: .4rem .75rem; border-radius: .5rem; font-size: .8rem; font-weight: 600; }
    .file-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .5rem; border-radius: .375rem;
        background: #fffaf5; border: 1px solid #f0e0d0; font-size: .75rem; color: #5C2E1F;
    }
    .file-upload-zone {
        border: 1px dashed #E8C4B8; border-radius: .5rem; padding: .5rem;
        background: #fffaf5;
    }
    .reg-source-block {
        border-radius: .5rem;
        padding: .45rem .5rem;
        border: 1px solid #f0e0d0;
    }
    .reg-source-block + .reg-source-block { margin-top: .5rem; }
    .reg-source-instructor { background: #fffaf5; border-color: #f0e0d0; }
    .reg-source-dept { background: #f0fdfa; border-color: #ccfbf1; }
    .reg-source-label {
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .02em;
        margin-bottom: .35rem;
        line-height: 1.2;
    }
    .reg-source-instructor .reg-source-label { color: #8B4513; }
    .reg-source-dept .reg-source-label { color: #0f766e; }
</style>
@endpush

@section('content')
<div>
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h2 class="text-xl font-bold text-[#5C2E1F]">ติดตามรายงานผลการสอบ</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">รายการล่าสุดอยู่บนสุด — ดูวันที่กรอก สถานะการอนุมัติ และไฟล์ที่แนบ</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard') }}" class="px-4 py-2 border border-amber-300 text-[#5C2E1F] rounded-lg text-sm font-medium hover:bg-amber-50">
                กลับหน้าหลัก
            </a>
            <a href="{{ route('grade-reports.create', ['term' => $term, 'year' => $year, 'return' => 'my']) }}" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
                + สร้างรายงานใหม่
            </a>
        </div>
    </div>

    <div class="form-section rounded-xl p-5 mb-5">
        <p class="text-sm text-[#7A4A3A]/80 mb-4">เลือกภาคการศึกษาและปีการศึกษาเพื่อแสดงรายการที่บันทึกไว้</p>
        <form method="GET" action="{{ route('grade-reports.my') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                <select name="term" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                    <option value="1" @selected($term === 1)>ภาคต้น</option>
                    <option value="2" @selected($term === 2)>ภาคปลาย</option>
                    <option value="3" @selected($term === 3)>ภาคการศึกษาพิเศษ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                <select name="year" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[8rem]">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-semibold hover:bg-[#6B3410]">
                แสดงรายการ
            </button>
        </form>
    </div>

    <div class="mb-4 p-3 rounded-lg bg-sky-50/80 border border-sky-200 text-sm text-[#0c4a6e]">
        <strong>ขั้นตอนการอนุมัติ:</strong>
        ส่งแล้ว → สาขาอนุมัติ → คณะอนุมัติ
    </div>

    <p class="text-sm text-[#7A4A3A]/80 mb-4">
        ภาค{{ $term === 1 ? 'ต้น' : ($term === 2 ? 'ปลาย' : 'การศึกษาพิเศษ') }} ปีการศึกษา {{ $year }}
        — พบ {{ $reports->count() }} รายการ
    </p>

    @if ($reports->isEmpty())
        <div class="text-center py-12 bg-[#FFFBF7] rounded-xl border border-dashed border-amber-300">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto text-amber-400 mb-3"></i>
            <p class="text-[#5C2E1F] font-medium">ยังไม่มีรายวิชาในภาคการศึกษานี้</p>
            <p class="text-sm text-gray-500 mt-1">กด «กรอกข้อมูลเอง» หรือ «อัปโหลดไฟล์» จากหน้าหลักเพื่อเริ่มสร้างรายงาน</p>
        </div>
    @else
        <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
            <table class="report-table w-full text-sm min-w-[960px]">
                <thead>
                    <tr>
                        <th class="text-left">รายวิชา</th>
                        <th class="text-left" style="min-width:7.5rem">วันที่กรอก</th>
                        <th class="text-left" style="min-width:14rem">ความคืบหน้า / สถานะ</th>
                        <th class="text-left" style="min-width:11rem">แบบรายงานผลการสอบไล่</th>
                        <th class="text-left" style="min-width:11rem">ใบส่งผลการศึกษา (REG)</th>
                        <th class="text-center">ทำรายการ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $report)
                        @php
                            $step = $report->approvalStep();
                            $rejected = (int) $report->approv === -1;
                            $canSubmitCorrections = $report->canSubmitCorrections();
                            $awaitingDept = $report->awaitingDeptResubmit();
                            $canEdit = $report->canEdit();
                            $canPrint = $report->canPrint();
                            $enteredAt = $report->created_stamp ?: $report->created;
                        @endphp
                        <tr>
                            <td>
                                <p class="font-semibold text-[#5C2E1F]">{{ $report->subject_code }}</p>
                                <p class="text-gray-600 mt-0.5">{{ $report->subject }}</p>
                            </td>
                            <td class="whitespace-nowrap text-[#5C2E1F]">
                                {{ \App\Support\ThaiDateTime::formatDate($enteredAt) }}
                            </td>
                            <td>
                                <div class="flex gap-1 mb-2 max-w-xs mx-auto sm:mx-0">
                                    <div class="progress-step done {{ $rejected ? 'rejected' : '' }}">
                                        <div class="dot">1</div>
                                        <div class="label">ส่งแล้ว</div>
                                    </div>
                                    <div class="progress-step {{ $step >= 1 ? 'done' : '' }} {{ $step === 0 && ! $rejected ? 'current' : '' }}">
                                        <div class="dot">2</div>
                                        <div class="label">สาขา</div>
                                    </div>
                                    <div class="progress-step {{ $step >= 2 ? 'done' : '' }} {{ $step === 1 ? 'current' : '' }}">
                                        <div class="dot">3</div>
                                        <div class="label">คณะ</div>
                                    </div>
                                </div>
                                <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $report->instructorTrackStatusClass() }}">
                                    {{ $report->instructorTrackStatusLabel() }}
                                </span>
                                @php $adminComments = $report->instructorAdminComments(); @endphp
                                @if ($adminComments->isNotEmpty())
                                    <div class="mt-2 space-y-1.5 max-w-sm">
                                        @foreach ($adminComments as $comment)
                                            <div @class([
                                                'rounded px-2 py-1.5 text-left border text-xs leading-relaxed',
                                                'bg-red-50 border-red-200 text-red-800' => $comment['tone'] === 'warning',
                                                'bg-amber-50 border-amber-200 text-amber-900' => $comment['tone'] === 'info',
                                            ])>
                                                <p class="font-semibold">
                                                    {{ $comment['role_label'] }}
                                                    <span class="font-normal opacity-80">· {{ $comment['action_label'] }}</span>
                                                </p>
                                                <p class="mt-0.5 whitespace-pre-line">{{ $comment['text'] }}</p>
                                                @if ($comment['at'])
                                                    <p class="mt-1 text-[10px] opacity-70">
                                                        {{ \App\Support\ThaiDateTime::formatDateTime($comment['at']) }}
                                                        @if ($comment['approver'])
                                                            — {{ $comment['approver'] }}
                                                        @endif
                                                    </p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                @php
                                    $examFiles = $report->files->filter(fn ($f) => $f->resolvedType() === \App\Models\GradeReportFile::TYPE_EXAM_REPORT);
                                    $regInstructorFiles = $report->files->filter(
                                        fn ($f) => $f->resolvedType() === \App\Models\GradeReportFile::TYPE_REGISTRAR
                                            && $f->isInstructorUpload($report)
                                    );
                                    $regDeptFiles = $report->files->filter(
                                        fn ($f) => $f->isDeptAdminUpload($report)
                                    );
                                @endphp
                                <div class="space-y-2" data-report-files="{{ $report->grade_id }}" data-file-type="exam_report">
                                    @if ($examFiles->isEmpty())
                                        <p class="text-xs text-gray-500 file-empty-msg">ยังไม่มีไฟล์</p>
                                    @else
                                        <div class="flex flex-col gap-1.5 file-list">
                                            @foreach ($examFiles as $file)
                                                <div class="file-chip" data-file-id="{{ $file->file_id }}">
                                                    <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-[#8B4513]"></i>
                                                    <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                                                       target="_blank" rel="noopener noreferrer"
                                                       class="hover:underline truncate max-w-[9rem]" title="{{ $file->original_name }}">
                                                        {{ $file->original_name }}
                                                    </a>
                                                    @if ($canEdit)
                                                        <button type="button"
                                                            class="btn-delete-file text-red-600 hover:text-red-800 ml-1"
                                                            data-report-id="{{ $report->grade_id }}"
                                                            data-file-id="{{ $file->file_id }}"
                                                            title="ลบไฟล์">
                                                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($canEdit)
                                        <label class="file-upload-zone block cursor-pointer">
                                            <input type="file" accept=".pdf,application/pdf" class="hidden file-upload-input"
                                                data-report-id="{{ $report->grade_id }}"
                                                data-file-type="exam_report">
                                            <span class="text-xs text-[#8B4513] font-medium flex items-center gap-1">
                                                <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                                อัปโหลด PDF
                                            </span>
                                        </label>
                                    @elseif ($awaitingDept)
                                        <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded px-2 py-1.5 leading-relaxed">
                                            ส่งการแก้ไขแล้ว — รอสาขา
                                        </p>
                                    @else
                                        <p class="text-xs text-gray-500">ล็อกแล้ว</p>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="space-y-2 min-w-[11rem]">
                                    <div class="reg-source-block reg-source-instructor space-y-2"
                                        data-report-files="{{ $report->grade_id }}"
                                        data-file-type="registrar"
                                        data-reg-source="instructor">
                                        <p class="reg-source-label">อาจารย์อัปโหลด</p>
                                        @if ($regInstructorFiles->isEmpty())
                                            <p class="text-xs text-gray-500 file-empty-msg">ยังไม่มีไฟล์</p>
                                        @else
                                            <div class="flex flex-col gap-1.5 file-list">
                                                @foreach ($regInstructorFiles as $file)
                                                    @php
                                                        $regDisplayName = $file->registrarDisplayName($report);
                                                    @endphp
                                                    <div class="file-chip" data-file-id="{{ $file->file_id }}">
                                                        <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-[#8B4513]"></i>
                                                        <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                                                           target="_blank" rel="noopener noreferrer"
                                                           class="hover:underline truncate max-w-[9rem]" title="{{ $regDisplayName }}">
                                                            {{ $regDisplayName }}
                                                        </a>
                                                        @if ($canEdit)
                                                            <button type="button"
                                                                class="btn-delete-file text-red-600 hover:text-red-800 ml-1"
                                                                data-report-id="{{ $report->grade_id }}"
                                                                data-file-id="{{ $file->file_id }}"
                                                                title="ลบไฟล์">
                                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($canEdit)
                                            <label class="file-upload-zone block cursor-pointer">
                                                <input type="file" accept=".pdf,application/pdf" class="hidden file-upload-input"
                                                    data-report-id="{{ $report->grade_id }}"
                                                    data-file-type="registrar"
                                                    data-reg-source="instructor">
                                                <span class="text-xs text-[#8B4513] font-medium flex items-center gap-1">
                                                    <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                                    อัปโหลดจาก REG
                                                </span>
                                            </label>
                                            @if ($canSubmitCorrections)
                                                <p class="text-xs text-red-700 bg-red-50 border border-red-200 rounded px-2 py-1.5 leading-relaxed">
                                                    แก้ไขครบแล้ว กด «ส่งการแก้ไข» เพื่อส่งให้สาขาวิชาดำเนินการ
                                                </p>
                                            @endif
                                        @elseif ($awaitingDept)
                                            <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded px-2 py-1.5 leading-relaxed">
                                                ส่งการแก้ไขแล้ว — รอสาขาวิชาส่งรายงานผลการสอบไล่อีกครั้ง
                                            </p>
                                        @else
                                            <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded px-2 py-1.5 leading-relaxed">
                                                รายงานผ่านการอนุมัติแล้ว — ไม่สามารถอัปโหลดหรือแก้ไขไฟล์ได้จนกว่าเจ้าหน้าที่จะคืนสถานะเป็นรออนุมัติ
                                            </p>
                                        @endif
                                    </div>

                                    <div class="reg-source-block reg-source-dept space-y-2"
                                        data-report-files="{{ $report->grade_id }}"
                                        data-file-type="registrar"
                                        data-reg-source="dept">
                                        <p class="reg-source-label">Admin สาขาอัปโหลด</p>
                                        @if ($regDeptFiles->isEmpty())
                                            <p class="text-xs text-gray-500 file-empty-msg">ยังไม่มีไฟล์จากสาขา</p>
                                        @else
                                            <div class="flex flex-col gap-1.5 file-list">
                                                @foreach ($regDeptFiles as $file)
                                                    @php
                                                        $regDisplayName = $file->registrarDisplayName($report);
                                                    @endphp
                                                    <div class="file-chip" data-file-id="{{ $file->file_id }}">
                                                        <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-teal-700"></i>
                                                        <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                                                           target="_blank" rel="noopener noreferrer"
                                                           class="hover:underline truncate max-w-[9rem]" title="{{ $regDisplayName }}">
                                                            {{ $regDisplayName }}
                                                        </a>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex flex-wrap justify-center gap-2">
                                    @if ($canEdit)
                                        @if ($canPrint)
                                            <a href="{{ route('grade-reports.print', $report->grade_id) }}" target="_blank"
                                               class="action-btn bg-amber-700 text-white hover:bg-amber-800">
                                                <i data-lucide="printer" class="w-3.5 h-3.5"></i> พิมพ์
                                            </a>
                                        @else
                                            <span class="action-btn bg-gray-100 text-gray-400 cursor-not-allowed" title="กรอกจำนวนนักศึกษาก่อน">
                                                <i data-lucide="printer" class="w-3.5 h-3.5"></i> พิมพ์
                                            </span>
                                        @endif
                                        <a href="{{ route('grade-reports.edit', ['gradeReport' => $report->grade_id, 'term' => $term, 'year' => $year, 'return' => 'my']) }}"
                                           class="action-btn border border-amber-300 text-[#5C2E1F] hover:bg-amber-50">
                                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i> แก้ไข
                                        </a>
                                        <button type="button" class="action-btn bg-red-600 text-white hover:bg-red-700 btn-delete-report"
                                            data-id="{{ $report->grade_id }}"
                                            data-subject="{{ $report->subject_code }}">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> ลบ
                                        </button>
                                        @if ($canSubmitCorrections)
                                            <form method="POST" action="{{ route('grade-reports.submit-corrections', $report) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="action-btn bg-[#8B4513] text-white hover:bg-[#6B3410]"
                                                    onclick="return confirm('ยืนยันส่งการแก้ไขให้สาขาวิชาดำเนินการ?')">
                                                    <i data-lucide="send" class="w-3.5 h-3.5"></i> ส่งการแก้ไข
                                                </button>
                                            </form>
                                        @endif
                                    @elseif ($awaitingDept)
                                        @if ($canPrint)
                                            <a href="{{ route('grade-reports.print', $report->grade_id) }}" target="_blank"
                                               class="action-btn bg-amber-700 text-white hover:bg-amber-800">
                                                <i data-lucide="printer" class="w-3.5 h-3.5"></i> พิมพ์
                                            </a>
                                        @endif
                                        <span class="text-xs text-amber-800 text-center block w-full mt-1">{{ $report->instructorTrackStatusLabel() }}</span>
                                    @else
                                        @if ($canPrint)
                                            <a href="{{ route('grade-reports.print', $report->grade_id) }}" target="_blank"
                                               class="action-btn bg-amber-700 text-white hover:bg-amber-800">
                                                <i data-lucide="printer" class="w-3.5 h-3.5"></i> พิมพ์
                                            </a>
                                        @endif
                                        <span class="text-xs text-gray-500 text-center block w-full mt-1">{{ $report->instructorTrackStatusLabel() }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-red-700 mt-3 leading-relaxed">
            ** เมื่อสร้างแบบรายงานแล้ว ต้องกรอกจำนวนนักศึกษาก่อนจึงจะพิมพ์แบบฟอร์มได้<br>
            ** วิชาที่ส่งเกรดช้าและมี I ต้องแนบบันทึกมาพร้อมกับใบส่งเกรด (อัปโหลด PDF ในคอลัมน์ «แบบรายงานผลการสอบไล่» และ «ใบส่งผลการศึกษา (REG)»)
        </p>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function() {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    function refreshLucideIcons(root) {
        if (typeof lucide === 'undefined' || !lucide.createIcons) return;
        lucide.createIcons(root ? { root } : undefined);
    }

    document.querySelectorAll('.btn-delete-report').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            const subject = btn.dataset.subject;
            if (!confirm(`ต้องการลบรายงานวิชา ${subject} หรือไม่?`)) return;

            const res = await fetch(`/api/grade-reports/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (res.ok) {
                window.location.reload();
            } else {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'ลบไม่สำเร็จ');
            }
        });
    });

    document.querySelectorAll('.file-upload-input').forEach((input) => {
        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;

            if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                alert('รองรับเฉพาะไฟล์ PDF');
                input.value = '';
                return;
            }

            const reportId = input.dataset.reportId;
            const fileType = input.dataset.fileType || 'exam_report';
            const formData = new FormData();
            formData.append('attachment', file);
            formData.append('file_type', fileType);

            const res = await fetch(`/api/grade-reports/${reportId}/files`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            input.value = '';

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'อัปโหลดไม่สำเร็จ');
                return;
            }

            const uploaded = await res.json();
            const regSource = input.dataset.regSource;
            const container = regSource
                ? document.querySelector(`[data-report-files="${reportId}"][data-file-type="${fileType}"][data-reg-source="${regSource}"]`)
                : document.querySelector(`[data-report-files="${reportId}"][data-file-type="${fileType}"]`);
            if (!container) return;

            container.querySelector('.file-empty-msg')?.remove();

            let list = container.querySelector('.file-list');
            if (!list) {
                list = document.createElement('div');
                list.className = 'flex flex-col gap-1.5 file-list';
                const uploadZone = container.querySelector('.file-upload-zone');
                if (uploadZone) {
                    container.insertBefore(list, uploadZone);
                } else {
                    container.appendChild(list);
                }
            }

            const chip = document.createElement('div');
            chip.className = 'file-chip';
            chip.dataset.fileId = uploaded.file_id;
            const shownName = uploaded.display_name || uploaded.original_name;
            chip.innerHTML = `
                <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-[#8B4513]"></i>
                <a href="${uploaded.view_url}" target="_blank" rel="noopener noreferrer"
                   class="hover:underline truncate max-w-[9rem]" title="${shownName}">
                    ${shownName}
                </a>
                <button type="button" class="btn-delete-file text-red-600 hover:text-red-800 ml-1"
                    data-report-id="${reportId}" data-file-id="${uploaded.file_id}" title="ลบไฟล์">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            `;
            list.appendChild(chip);
            bindDeleteFile(chip.querySelector('.btn-delete-file'));
            refreshLucideIcons(chip);
        });
    });

    function bindDeleteFile(btn) {
        if (!btn || btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            if (!confirm('ต้องการลบไฟล์นี้หรือไม่?')) return;

            const reportId = btn.dataset.reportId;
            const fileId = btn.dataset.fileId;
            const res = await fetch(`/api/grade-reports/${reportId}/files/${fileId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'ลบไฟล์ไม่สำเร็จ');
                return;
            }

            const chip = btn.closest('.file-chip');
            const container = chip?.closest('[data-report-files]');
            chip?.remove();
            const list = container?.querySelector('.file-list');
            if (list && !list.children.length) {
                list.remove();
                const empty = document.createElement('p');
                empty.className = 'text-xs text-gray-500 file-empty-msg';
                empty.textContent = container.dataset.regSource === 'dept'
                    ? 'ยังไม่มีไฟล์จากสาขา'
                    : 'ยังไม่มีไฟล์';
                const uploadZone = container.querySelector('.file-upload-zone');
                const label = container.querySelector('.reg-source-label');
                if (uploadZone) {
                    container.insertBefore(empty, uploadZone);
                } else if (label) {
                    label.after(empty);
                } else {
                    container.appendChild(empty);
                }
            }
        });
    }

    document.querySelectorAll('.btn-delete-file').forEach(bindDeleteFile);
})();
</script>
@endpush
