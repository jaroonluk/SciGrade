@extends('layouts.scigrad')

@section('title', 'ตรวจสอบสถานะการส่งผลการสอบ — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">ตรวจสอบสถานะการส่งผลการสอบ</span>
@endsection

@section('mainClass', 'max-w-none w-full')

@push('styles')
<style>
    tr.course-group-start td { border-top: 2px solid #d6b896 !important; }
    tr.course-group-cont td.col-course {
        padding-left: 1.75rem;
        color: #7A4A3A;
    }
    #status-table td.col-course {
        word-break: break-word;
        overflow-wrap: anywhere;
        white-space: normal;
    }
    #status-table th,
    #status-table td { vertical-align: middle; }
    .sec-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 2rem; padding: 0.15rem 0.5rem; border-radius: 9999px;
        background: #FAF0E6; color: #5C2E1F; font-weight: 700; font-size: 0.75rem;
    }
    .multi-sec-tag {
        display: inline-block; margin-left: 0.35rem; padding: 0.1rem 0.45rem;
        border-radius: 9999px; background: #e8f4ff; color: #075985;
        font-size: 0.65rem; font-weight: 600; vertical-align: middle;
    }
    .dup-tag {
        display: inline-block; margin-left: 0.35rem; padding: 0.1rem 0.45rem;
        border-radius: 9999px; background: #fef2f2; color: #b91c1c;
        border: 1px solid #fecaca; font-size: 0.65rem; font-weight: 700; vertical-align: middle;
    }
    tr.course-dup td { background: #FFF5F5 !important; }
    .status-radio {
        appearance: none; -webkit-appearance: none;
        width: 1.35rem; height: 1.35rem; border-radius: 9999px;
        border: 2px solid #cbd5e1; background: #fff;
        display: inline-grid; place-content: center; cursor: default; vertical-align: middle;
        transition: box-shadow .15s ease, border-color .15s ease, background .15s ease, transform .12s ease;
    }
    .status-radio::before {
        content: ""; width: 0.65rem; height: 0.65rem; border-radius: 9999px;
        transform: scale(0); transition: transform .12s ease; box-shadow: inset 1em 1em currentColor;
    }
    .status-radio:checked::before { transform: scale(1); }
    .status-radio.status-0 { border-color: #94a3b8; color: #64748b; }
    .status-radio.status-0:checked { background: #f1f5f9; box-shadow: 0 0 0 3px rgba(148,163,184,.25); }
    .status-radio.status-1 { border-color: #f59e0b; color: #d97706; }
    .status-radio.status-1:checked { background: #fffbeb; box-shadow: 0 0 0 3px rgba(245,158,11,.22); }
    .status-radio.status-2 { border-color: #6366f1; color: #4f46e5; }
    .status-radio.status-2:checked { background: #eef2ff; box-shadow: 0 0 0 3px rgba(99,102,241,.22); }
    .status-radio.status-3 { border-color: #0ea5e9; color: #0284c7; }
    .status-radio.status-3:checked { background: #f0f9ff; box-shadow: 0 0 0 3px rgba(14,165,233,.22); }
    .status-radio.status-4 { border-color: #f97316; color: #ea580c; }
    .status-radio.status-4:checked { background: #fff7ed; box-shadow: 0 0 0 3px rgba(249,115,22,.22); }
    .status-radio.status-5 { border-color: #16a34a; color: #15803d; }
    .status-radio.status-5:checked { background: #f0fdf4; box-shadow: 0 0 0 3px rgba(22,163,74,.22); }
    .status-radio.status-6 { border-color: #dc2626; color: #b91c1c; }
    .status-radio.status-6:checked { background: #fef2f2; box-shadow: 0 0 0 3px rgba(220,38,38,.22); }
    .status-radio.is-clickable {
        cursor: pointer; border-width: 3px; box-shadow: 0 0 0 2px rgba(22,163,74,.14);
    }
    .status-radio.is-clickable:hover { transform: scale(1.08); box-shadow: 0 0 0 4px rgba(22,163,74,.22); }
    .status-radio:disabled { opacity: .95; }
    .status-cell-active-0 { background: #f8fafc; }
    .status-cell-active-1 { background: #fffbeb; }
    .status-cell-active-2 { background: #eef2ff; }
    .status-cell-active-3 { background: #f0f9ff; }
    .status-cell-active-4 { background: #fff7ed; }
    .status-cell-active-5 { background: #f0fdf4; }
    .status-cell-active-6 { background: #fef2f2; }
    .status-cell-wrap {
        position: relative; display: inline-flex; flex-direction: column;
        align-items: center; gap: 0.2rem; min-height: 2.4rem; justify-content: center;
    }
    .status-toast {
        font-size: 0.65rem; line-height: 1; color: #15803d; font-weight: 600;
        white-space: nowrap; opacity: 0; transition: opacity .2s ease; pointer-events: none;
    }
    .status-toast.is-visible { opacity: 1; }
    .status-toast.is-error { color: #b91c1c; }
</style>
@endpush

@section('content')
<div class="w-full space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">ตรวจสอบสถานะการส่งผลการสอบไล่</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            Admin กลางติกได้เฉพาะ
            <span class="font-medium text-orange-700">ตรวจแล้ว</span> /
            <span class="font-medium text-green-700">คณะอนุมัติ</span> /
            <span class="font-medium text-red-700">ส่งกลับแก้ไข</span>
            (กดครั้งเดียวมีผลทุก Section ของวิชานั้น)
        </p>
    </div>

    <div class="form-section rounded-xl p-6">
        <form method="GET" action="{{ route('faculty-admin.settings.reg-grade-status.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สาขาวิชา</label>
                <select name="department_id" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[14rem]">
                    <option value="">ทั้งหมด</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->department_id }}" @selected($departmentId === (int) $dept->department_id)>
                            {{ $dept->department_name }}
                        </option>
                    @endforeach
                </select>
            </div>
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
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สถานะ</label>
                <select name="status" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[14rem]">
                    <option value="all" @selected(($statusFilter ?? 'all') === 'all')>ทั้งหมด</option>
                    <option value="0" @selected(($statusFilter ?? 'all') === '0')>ยังไม่ส่ง</option>
                    <option value="1" @selected(($statusFilter ?? 'all') === '1')>ส่งแล้ว</option>
                    <option value="2" @selected(($statusFilter ?? 'all') === '2')>นำเข้าที่ประชุมสาขา</option>
                    <option value="3" @selected(($statusFilter ?? 'all') === '3')>ผ่านที่ประชุมสาขา</option>
                    <option value="4" @selected(($statusFilter ?? 'all') === '4')>ตรวจแล้ว</option>
                    <option value="5" @selected(($statusFilter ?? 'all') === '5')>คณะอนุมัติ</option>
                    <option value="6" @selected(($statusFilter ?? 'all') === '6')>ส่งกลับแก้ไข</option>
                </select>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
                แสดงข้อมูล
            </button>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center">
                <i data-lucide="circle" class="w-5 h-5"></i>
            </div>
            <p class="text-xs text-slate-600">ยังไม่ส่ง</p>
            <p class="text-lg font-bold text-slate-700 summary-0">{{ $summary[0] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center">
                <i data-lucide="send" class="w-5 h-5"></i>
            </div>
            <p class="text-xs text-amber-800">ส่งแล้ว</p>
            <p class="text-lg font-bold text-amber-800 summary-1">{{ $summary[1] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center">
                <i data-lucide="calendar-plus" class="w-5 h-5"></i>
            </div>
            <p class="text-xs text-indigo-800">นำเข้าที่ประชุมสาขา</p>
            <p class="text-lg font-bold text-indigo-800 summary-2">{{ $summary[2] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
            </div>
            <p class="text-xs text-sky-800">ผ่านที่ประชุมสาขา</p>
            <p class="text-lg font-bold text-sky-800 summary-3">{{ $summary[3] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-orange-100 text-orange-700 flex items-center justify-center">
                <i data-lucide="file-check" class="w-5 h-5"></i>
            </div>
            <p class="text-xs text-orange-800">ตรวจแล้ว</p>
            <p class="text-lg font-bold text-orange-800 summary-4">{{ $summary[4] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-green-100 text-green-700 flex items-center justify-center">
                <i data-lucide="badge-check" class="w-5 h-5"></i>
            </div>
            <p class="text-xs text-green-800">คณะอนุมัติ</p>
            <p class="text-lg font-bold text-green-800 summary-5">{{ $summary[5] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-red-100 text-red-700 flex items-center justify-center">
                <i data-lucide="undo-2" class="w-5 h-5"></i>
            </div>
            <p class="text-xs text-red-800">ส่งกลับแก้ไข</p>
            <p class="text-lg font-bold text-red-800 summary-6">{{ $summary[6] ?? 0 }}</p>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200 w-full">
        <div class="px-4 py-3 bg-amber-50 border-b border-amber-200 text-sm text-[#5C2E1F]">
            พบ {{ number_format($courses->count()) }} รายวิชา
            <span class="text-xs text-gray-500 ml-2">ติกที่แถวแรกของวิชา: ตรวจแล้ว / คณะอนุมัติ / ส่งกลับแก้ไข — มีผลทุก Sec.</span>
        </div>
        <table class="w-full text-sm table-fixed min-w-[1280px]" id="status-table">
            <colgroup>
                <col class="w-[3.5%]">
                <col class="w-[24%]">
                <col class="w-[4.5%]">
                <col class="w-[8%]">
                <col class="w-[8%]">
                <col class="w-[9%]">
                <col class="w-[9%]">
                <col class="w-[8.5%]">
                <col class="w-[8.5%]">
                <col class="w-[9%]">
            </colgroup>
            <thead class="bg-amber-50/60">
                <tr>
                    <th class="px-2 py-2 text-left">ลำดับ</th>
                    <th class="px-2 py-2 text-left">รายวิชา</th>
                    <th class="px-2 py-2 text-center">Sec.</th>
                    <th class="px-1.5 py-2 text-center text-slate-600 leading-tight whitespace-normal">ยังไม่ส่ง</th>
                    <th class="px-1.5 py-2 text-center text-amber-700 leading-tight whitespace-normal">ส่งแล้ว</th>
                    <th class="px-1.5 py-2 text-center text-indigo-700 leading-tight whitespace-normal">นำเข้าที่<br>ประชุมสาขา</th>
                    <th class="px-1.5 py-2 text-center text-sky-700 leading-tight whitespace-normal">ผ่านที่<br>ประชุมสาขา</th>
                    <th class="px-1.5 py-2 text-center text-orange-700 leading-tight whitespace-normal">ตรวจแล้ว</th>
                    <th class="px-1.5 py-2 text-center text-green-700 leading-tight whitespace-normal">คณะอนุมัติ</th>
                    <th class="px-1.5 py-2 text-center text-red-700 leading-tight whitespace-normal">ส่งกลับ<br>แก้ไข</th>
                </tr>
            </thead>
            <tbody>
                @php $prevCode = null; $prevSection = null; @endphp
                @forelse ($courses as $index => $row)
                    @php
                        $isSameSectionDuplicate = $prevCode !== null
                            && $prevCode === $row->COURSECODE
                            && $prevSection !== null
                            && (int) $prevSection === (int) $row->SECTION;
                        $isContinuation = $prevCode !== null && $prevCode === $row->COURSECODE && ! $isSameSectionDuplicate;
                        $isGroupStart = ! $isContinuation && ! $isSameSectionDuplicate && ($row->has_multi_section || ! empty($row->is_duplicate_entry));
                        $rowClass = $isSameSectionDuplicate
                            ? 'bg-[#FFF5F5] course-dup'
                            : ($isContinuation
                                ? 'bg-[#F8FBFF] course-group-cont'
                                : ($isGroupStart ? 'bg-[#FFF8F0] course-group-start' : ($index % 2 === 0 ? 'bg-white' : 'bg-[#F0FFFF]/40')));
                        $isStatusControlRow = (bool) ($row->is_course_start ?? ! $isContinuation);
                        $controlGradeId = $row->course_grade_id ?: $row->grade_id;
                        $canMarkChecked = $isStatusControlRow && (bool) ($row->course_can_mark_checked ?? false) && $controlGradeId;
                        $canApproveFaculty = $isStatusControlRow && (bool) ($row->course_can_approve_faculty ?? false) && $controlGradeId;
                        $canSendBack = $isStatusControlRow && (bool) ($row->course_can_send_back_faculty ?? false) && $controlGradeId;
                        $radioName = 'status-'.$index.'-'.($row->grade_id ?: $row->COURSECODE.'-'.$row->SECTION);
                    @endphp
                    <tr class="border-t border-amber-100 {{ $rowClass }}"
                        data-course-code="{{ strtoupper(trim((string) $row->COURSECODE)) }}"
                        data-grade-id="{{ $row->grade_id }}"
                        data-status="{{ $row->status }}"
                        data-status-control="{{ $isStatusControlRow ? '1' : '0' }}"
                        @if ($controlGradeId)
                            data-set-status-url="{{ route('faculty-admin.settings.reg-grade-status.set-status', $controlGradeId) }}"
                        @endif>
                        <td class="px-2 py-2 text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-2 py-2 col-course">
                            @if ($isSameSectionDuplicate)
                                <span class="text-xs text-red-700 font-medium">↳ กรอกซ้ำ · ชื่อวิชาและ Sec. เดียวกัน</span>
                            @elseif ($isContinuation)
                                <span class="text-xs text-sky-700 font-medium">↳ Sec. ต่อเนื่อง · วิชาเดียวกัน</span>
                                <div class="text-xs text-gray-500 mb-0.5">{{ $row->COURSECODE }}</div>
                            @endif
                            <span class="font-medium text-[#5C2E1F]">
                                @unless($isContinuation){{ $row->COURSECODE }} @endunless
                                {{ $row->COURSENAMEENG }}
                            </span>
                            @if (! empty($row->is_duplicate_entry))
                                <span class="dup-tag">กรอกซ้ำ {{ $row->duplicate_count }} รายการ</span>
                            @endif
                            @if ($row->grade_id && (int) $row->status >= 1)
                                @php $attachedFiles = collect($row->attached_files ?? []); @endphp
                                @if ($attachedFiles->isNotEmpty())
                                    <div class="mt-1 flex flex-col gap-0.5">
                                        @foreach ($attachedFiles as $file)
                                            <a href="{{ route('grade-reports.files.show', ['gradeReport' => $file->grade_id ?? $row->grade_id, 'file' => $file->file_id]) }}"
                                                target="_blank" rel="noopener noreferrer"
                                                class="text-xs text-[#8B4513] hover:underline inline-flex items-center gap-1 w-fit"
                                                title="{{ $file->file_name ?: $file->type_label }}">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                                                {{ $file->type_label }}
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-amber-700 block mt-0.5">ส่งแล้ว แต่ยังไม่มีไฟล์ PDF</span>
                                @endif
                            @endif
                            @if (! $isContinuation && $row->has_multi_section)
                                <span class="multi-sec-tag">{{ $row->section_count }} Sec.</span>
                            @endif
                            @if ((int) $row->status === 6 || (int) $row->approv === -1)
                                <span class="ml-1 text-xs text-red-600">ส่งกลับแก้ไข</span>
                            @endif
                            @if ($row->officers)
                                <div class="text-xs text-gray-500 mt-0.5">{{ $row->officers }}</div>
                            @endif
                        </td>
                        <td class="px-2 py-2 text-center"><span class="sec-badge">{{ $row->SECTION }}</span></td>
                        @foreach ([0, 1, 2, 3, 4, 5, 6] as $statusValue)
                            @php
                                $isActive = (int) $row->status === $statusValue;
                                $action = null;
                                if ($statusValue === 4 && $canMarkChecked) $action = 'check';
                                elseif ($statusValue === 5 && $canApproveFaculty) $action = 'approve';
                                elseif ($statusValue === 6 && $canSendBack) $action = 'send_back';
                                $title = match ($action) {
                                    'check' => 'คลิกเพื่อตั้งเป็นตรวจแล้ว (ทุก Section)',
                                    'approve' => 'คลิกเพื่อคณะอนุมัติ (ทุก Section)',
                                    'send_back' => 'คลิกเพื่อส่งกลับแก้ไข (ทุก Section)',
                                    default => '',
                                };
                            @endphp
                            <td class="px-2 py-2 text-center status-cell {{ $isActive ? 'status-cell-active-'.$statusValue : '' }}">
                                <div class="status-cell-wrap">
                                    <input type="radio"
                                        class="status-radio status-{{ $statusValue }} {{ $action ? 'is-clickable btn-faculty-status' : '' }}"
                                        name="{{ $radioName }}"
                                        value="{{ $statusValue }}"
                                        @checked($isActive)
                                        @if ($action)
                                            data-action="{{ $action }}"
                                        @else
                                            disabled
                                        @endif
                                        title="{{ $title }}">
                                    <span class="status-toast" aria-live="polite"></span>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                    @php $prevCode = $row->COURSECODE; $prevSection = $row->SECTION; @endphp
                @empty
                    <tr>
                        <td colspan="10" class="px-3 py-8 text-center text-gray-500">ไม่พบข้อมูลตามเงื่อนไขที่เลือก</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const actionStatus = { check: 4, approve: 5, send_back: 6 };

    const bumpSummary = (fromStatus, toStatus) => {
        const fromEl = document.querySelector('.summary-' + fromStatus);
        const toEl = document.querySelector('.summary-' + toStatus);
        if (fromEl) fromEl.textContent = Math.max(0, (parseInt(fromEl.textContent, 10) || 0) - 1);
        if (toEl) toEl.textContent = (parseInt(toEl.textContent, 10) || 0) + 1;
    };

    const showToast = (radio, message, isError = false) => {
        const toast = radio.closest('.status-cell-wrap')?.querySelector('.status-toast');
        if (!toast) return;
        toast.textContent = message;
        toast.classList.toggle('is-error', !!isError);
        toast.classList.add('is-visible');
        clearTimeout(toast._hideTimer);
        toast._hideTimer = setTimeout(() => toast.classList.remove('is-visible'), 2500);
    };

    const enableRadio = (r, action) => {
        r.disabled = false;
        r.classList.add('is-clickable', 'btn-faculty-status');
        r.dataset.action = action;
        r.title = ({
            check: 'คลิกเพื่อตั้งเป็นตรวจแล้ว (ทุก Section)',
            approve: 'คลิกเพื่อคณะอนุมัติ (ทุก Section)',
            send_back: 'คลิกเพื่อส่งกลับแก้ไข (ทุก Section)',
        })[action] || '';
        r.style.cursor = 'pointer';
        bindFacultyRadio(r);
    };

    const paintRow = (row, targetStatus) => {
        const radios = row.querySelectorAll('.status-radio');
        const cells = row.querySelectorAll('.status-cell');
        const setUrl = row.dataset.setStatusUrl || '';
        const isControl = row.dataset.statusControl === '1';

        radios.forEach((r) => {
            const value = Number(r.value);
            r.checked = value === targetStatus;
            r.classList.remove('is-clickable', 'btn-faculty-status');
            r.removeAttribute('data-action');
            r.removeAttribute('title');
            r.disabled = true;
            r.style.cursor = 'default';
            r.dataset.busy = '0';

            if (!isControl || !setUrl) return;
            if (targetStatus === 3 && value === 4) enableRadio(r, 'check');
            if ((targetStatus === 3 || targetStatus === 4) && value === 5) enableRadio(r, 'approve');
            if ([3, 4, 5].includes(targetStatus) && value === 6) enableRadio(r, 'send_back');
        });

        cells.forEach((cell, idx) => {
            cell.classList.remove(
                'status-cell-active-0','status-cell-active-1','status-cell-active-2','status-cell-active-3',
                'status-cell-active-4','status-cell-active-5','status-cell-active-6'
            );
            if (idx === targetStatus) cell.classList.add('status-cell-active-' + targetStatus);
        });
        row.dataset.status = String(targetStatus);
    };

    const courseRows = (row) => {
        const code = row?.dataset.courseCode;
        if (!code) return [row].filter(Boolean);
        return Array.from(document.querySelectorAll('#status-table tr[data-course-code="' + CSS.escape(code) + '"]'));
    };

    const updatedGradeIds = (data, fallbackId) => {
        const ids = Array.isArray(data.grade_ids) ? data.grade_ids : [];
        if (ids.length) return ids.map(String);
        return fallbackId ? [String(fallbackId)] : [];
    };

    const bindFacultyRadio = (radio) => {
        if (radio.dataset.bound === '1') return;
        radio.dataset.bound = '1';
        radio.addEventListener('click', async (e) => {
            e.preventDefault();
            const row = radio.closest('tr');
            const action = radio.dataset.action;
            const url = row?.dataset.setStatusUrl || '';
            if (!url || !action || radio.dataset.busy === '1') return;

            radio.dataset.busy = '1';
            radio.disabled = true;
            showToast(radio, 'กำลังบันทึกทุก Section...');

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf(),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ action }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    showToast(radio, data.message || 'ไม่สำเร็จ', true);
                    radio.disabled = false;
                    radio.dataset.busy = '0';
                    return;
                }

                const targetStatus = typeof data.status === 'number' ? data.status : (actionStatus[action] || 5);
                const gradeIds = new Set(updatedGradeIds(data, row.dataset.gradeId));

                courseRows(row).forEach((courseRow) => {
                    const rowGradeId = String(courseRow.dataset.gradeId || '');
                    if (!rowGradeId || (gradeIds.size && !gradeIds.has(rowGradeId))) return;
                    const rowFrom = Number(courseRow.dataset.status || 0);
                    paintRow(courseRow, targetStatus);
                    if (rowFrom !== targetStatus) bumpSummary(rowFrom, targetStatus);
                });

                const activeRadio = row.querySelector('.status-radio[value="' + targetStatus + '"]');
                if (activeRadio) showToast(activeRadio, 'บันทึกสำเร็จ');
            } catch {
                showToast(radio, 'เชื่อมต่อไม่สำเร็จ', true);
                radio.disabled = false;
                radio.dataset.busy = '0';
            }
        });
    };

    document.querySelectorAll('.btn-faculty-status').forEach(bindFacultyRadio);
})();
</script>
@endpush
