@extends('layouts.scigrad')

@section('title', 'ตรวจสอบสถานะการส่งผลการสอบ — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">ตรวจสอบสถานะการส่งผลการสอบ</span>
@endsection

@push('styles')
<style>
    tr.course-group-start td { border-top: 2px solid #d6b896 !important; }
    tr.course-group-cont td.col-course {
        padding-left: 1.75rem;
        color: #7A4A3A;
    }
    .sec-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        background: #FAF0E6;
        color: #5C2E1F;
        font-weight: 700;
        font-size: 0.75rem;
    }
    .multi-sec-tag {
        display: inline-block;
        margin-left: 0.35rem;
        padding: 0.1rem 0.45rem;
        border-radius: 9999px;
        background: #e8f4ff;
        color: #075985;
        font-size: 0.65rem;
        font-weight: 600;
        vertical-align: middle;
    }
    .status-radio {
        appearance: none;
        -webkit-appearance: none;
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 9999px;
        border: 2px solid #cbd5e1;
        background: #fff;
        display: inline-grid;
        place-content: center;
        cursor: default;
        vertical-align: middle;
        transition: box-shadow .15s ease, border-color .15s ease, background .15s ease;
    }
    .status-radio::before {
        content: "";
        width: 0.65rem;
        height: 0.65rem;
        border-radius: 9999px;
        transform: scale(0);
        transition: transform .12s ease;
        box-shadow: inset 1em 1em currentColor;
    }
    .status-radio:checked::before { transform: scale(1); }
    .status-radio.status-0 { border-color: #94a3b8; color: #64748b; }
    .status-radio.status-0:checked { background: #f1f5f9; box-shadow: 0 0 0 3px rgba(148,163,184,.25); }
    .status-radio.status-1 { border-color: #f59e0b; color: #d97706; }
    .status-radio.status-1:checked { background: #fffbeb; box-shadow: 0 0 0 3px rgba(245,158,11,.22); }
    .status-radio.status-2 { border-color: #0ea5e9; color: #0284c7; }
    .status-radio.status-2:checked { background: #f0f9ff; box-shadow: 0 0 0 3px rgba(14,165,233,.22); }
    .status-radio.status-3 { border-color: #16a34a; color: #15803d; }
    .status-radio.status-3:checked { background: #f0fdf4; box-shadow: 0 0 0 3px rgba(22,163,74,.22); }
    .status-radio.is-clickable {
        cursor: pointer;
        border-width: 3px;
        box-shadow: 0 0 0 2px rgba(22,163,74,.12);
    }
    .status-radio.is-clickable:hover {
        transform: scale(1.08);
        box-shadow: 0 0 0 4px rgba(22,163,74,.2);
    }
    .status-radio:disabled { opacity: .95; }
    .status-cell-active-0 { background: #f8fafc; }
    .status-cell-active-1 { background: #fffbeb; }
    .status-cell-active-2 { background: #f0f9ff; }
    .status-cell-active-3 { background: #f0fdf4; }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">ตรวจสอบสถานะการส่งผลการสอบไล่</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            เทียบรายวิชาจาก REG กับสถานะในระบบ — คลิกชื่อวิชาเพื่อเปิดไฟล์ PDF ที่อาจารย์อัปโหลด
            และติก “ผ่านคณะฯ” ได้ทันทีเมื่อรายวิชาผ่านสาขาแล้ว
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
            <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
                แสดงข้อมูล
            </button>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center">
                <i data-lucide="circle" class="w-5 h-5"></i>
            </div>
            <p class="text-xs text-slate-600">ยังไม่ส่ง</p>
            <p class="text-lg font-bold text-slate-700 summary-0">{{ $summary[0] }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center">
                <i data-lucide="send" class="w-5 h-5"></i>
            </div>
            <p class="text-xs text-amber-800">ส่งรายงานผลสอบแล้ว</p>
            <p class="text-lg font-bold text-amber-800 summary-1">{{ $summary[1] }}</p>
        </div>
        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-sky-100 text-sky-700 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
            </div>
            <p class="text-xs text-sky-800">ผ่านที่ประชุมสาขาฯ</p>
            <p class="text-lg font-bold text-sky-800 summary-2">{{ $summary[2] }}</p>
        </div>
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-center">
            <div class="w-10 h-10 mx-auto mb-2 rounded-full bg-green-100 text-green-700 flex items-center justify-center">
                <i data-lucide="badge-check" class="w-5 h-5"></i>
            </div>
            <p class="text-xs text-green-800">ผ่านที่ประชุมกรรมการคณะฯ</p>
            <p class="text-lg font-bold text-green-800 summary-3">{{ $summary[3] }}</p>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <div class="px-4 py-3 bg-amber-50 border-b border-amber-200 text-sm text-[#5C2E1F]">
            พบ {{ number_format($courses->count()) }} รายวิชา
            <span class="text-xs text-gray-500 ml-2">สีเขียว = ติกได้เมื่อผ่านสาขาแล้ว</span>
        </div>
        <table class="w-full text-sm min-w-[900px]" id="status-table">
            <thead class="bg-amber-50/60">
                <tr>
                    <th class="px-3 py-2 text-left w-14">ลำดับ</th>
                    <th class="px-3 py-2 text-left">รายวิชา</th>
                    <th class="px-3 py-2 text-center">Sec.</th>
                    <th class="px-3 py-2 text-center text-slate-600">ยังไม่ส่ง</th>
                    <th class="px-3 py-2 text-center text-amber-700">ส่งแล้ว</th>
                    <th class="px-3 py-2 text-center text-sky-700">ผ่านสาขาฯ</th>
                    <th class="px-3 py-2 text-center text-green-700">ผ่านคณะฯ</th>
                </tr>
            </thead>
            <tbody>
                @php $prevCode = null; @endphp
                @forelse ($courses as $index => $row)
                    @php
                        $isContinuation = $prevCode !== null && $prevCode === $row->COURSECODE;
                        $isGroupStart = ! $isContinuation && $row->has_multi_section;
                        $rowClass = $isContinuation
                            ? 'bg-[#F8FBFF] course-group-cont'
                            : ($isGroupStart ? 'bg-[#FFF8F0] course-group-start' : ($index % 2 === 0 ? 'bg-white' : 'bg-[#F0FFFF]/40'));
                        $canApproveFaculty = (int) $row->status === 2 && $row->grade_id;
                        $radioName = 'status-'.$index.'-'.($row->grade_id ?: $row->COURSECODE.'-'.$row->SECTION);
                    @endphp
                    <tr class="border-t border-amber-100 {{ $rowClass }}"
                        data-grade-id="{{ $row->grade_id }}"
                        data-status="{{ $row->status }}">
                        <td class="px-3 py-2 text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-3 py-2 col-course">
                            @if ($isContinuation)
                                <span class="text-xs text-sky-700 font-medium">↳ Sec. ต่อเนื่อง · วิชาเดียวกัน</span>
                                <div class="text-xs text-gray-500 mb-0.5">{{ $row->COURSECODE }}</div>
                            @endif

                            @if ($row->grade_id && $row->file_id && (int) $row->status >= 1)
                                <a href="{{ route('grade-reports.files.show', ['gradeReport' => $row->grade_id, 'file' => $row->file_id]) }}"
                                    target="_blank" rel="noopener noreferrer"
                                    class="text-[#8B4513] hover:underline font-medium inline-flex items-center gap-1"
                                    title="{{ $row->file_name ?: 'เปิดไฟล์ PDF' }}">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                                    @unless($isContinuation)
                                        {{ $row->COURSECODE }}
                                    @endunless
                                    {{ $row->COURSENAMEENG }}
                                </a>
                            @elseif ($row->grade_id && (int) $row->status >= 1)
                                <span class="font-medium text-[#5C2E1F]">
                                    @unless($isContinuation){{ $row->COURSECODE }} @endunless
                                    {{ $row->COURSENAMEENG }}
                                </span>
                                <span class="text-xs text-amber-700 block">ส่งแล้ว แต่ยังไม่มีไฟล์ PDF</span>
                            @else
                                <span class="font-medium text-[#5C2E1F]">
                                    @unless($isContinuation){{ $row->COURSECODE }} @endunless
                                    {{ $row->COURSENAMEENG }}
                                </span>
                            @endif

                            @if (! $isContinuation && $row->has_multi_section)
                                <span class="multi-sec-tag">{{ $row->section_count }} Sec.</span>
                            @endif
                            @if ((int) $row->approv === -1)
                                <span class="ml-1 text-xs text-red-600">ส่งกลับแก้ไข</span>
                            @endif
                            @if ($row->officers)
                                <div class="text-xs text-gray-500 mt-0.5">{{ $row->officers }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center"><span class="sec-badge">{{ $row->SECTION }}</span></td>
                        @foreach ([0, 1, 2, 3] as $statusValue)
                            @php
                                $isActive = (int) $row->status === $statusValue;
                                $isFacultyClickable = $statusValue === 3 && $canApproveFaculty;
                            @endphp
                            <td class="px-3 py-2 text-center status-cell {{ $isActive ? 'status-cell-active-'.$statusValue : '' }}">
                                <input type="radio"
                                    class="status-radio status-{{ $statusValue }} {{ $isFacultyClickable ? 'is-clickable btn-approve-faculty' : '' }}"
                                    name="{{ $radioName }}"
                                    value="{{ $statusValue }}"
                                    @checked($isActive)
                                    @if ($isFacultyClickable)
                                        data-approve-url="{{ route('faculty-admin.settings.reg-grade-status.approve-faculty', $row->grade_id) }}"
                                    @else
                                        disabled
                                    @endif
                                    title="{{ $isFacultyClickable ? 'คลิกเพื่อผ่านคณะฯ' : '' }}">
                            </td>
                        @endforeach
                    </tr>
                    @php $prevCode = $row->COURSECODE; @endphp
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-8 text-center text-gray-500">ไม่พบข้อมูลตามเงื่อนไขที่เลือก</td>
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

    const bumpSummary = (fromStatus, toStatus) => {
        const fromEl = document.querySelector('.summary-' + fromStatus);
        const toEl = document.querySelector('.summary-' + toStatus);
        if (fromEl) fromEl.textContent = Math.max(0, (parseInt(fromEl.textContent, 10) || 0) - 1);
        if (toEl) toEl.textContent = (parseInt(toEl.textContent, 10) || 0) + 1;
    };

    document.querySelectorAll('.btn-approve-faculty').forEach((radio) => {
        radio.addEventListener('click', async (e) => {
            e.preventDefault();
            const url = radio.dataset.approveUrl;
            if (!url) return;
            if (!confirm('ยืนยันผ่านที่ประชุมกรรมการคณะฯ สำหรับรายวิชานี้?')) return;

            radio.disabled = true;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf(),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    alert(data.message || 'อนุมัติไม่สำเร็จ');
                    radio.disabled = false;
                    return;
                }

                const row = radio.closest('tr');
                const radios = row.querySelectorAll('.status-radio');
                const cells = row.querySelectorAll('.status-cell');
                radios.forEach((r) => {
                    r.checked = Number(r.value) === 3;
                    r.classList.remove('is-clickable', 'btn-approve-faculty');
                    r.removeAttribute('data-approve-url');
                    r.disabled = true;
                    r.style.cursor = 'default';
                });
                cells.forEach((cell, idx) => {
                    cell.classList.remove('status-cell-active-0', 'status-cell-active-1', 'status-cell-active-2', 'status-cell-active-3');
                    if (idx === 3) cell.classList.add('status-cell-active-3');
                });
                bumpSummary(2, 3);
                row.dataset.status = '3';
            } catch {
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                radio.disabled = false;
            }
        });
    });
})();
</script>
@endpush
