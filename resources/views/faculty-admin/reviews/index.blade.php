@extends('layouts.scigrad')

@section('title', 'อนุมัติระดับคณะ — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">อนุมัติระดับคณะ</span>
@endsection

@push('styles')
<style>
    .filter-suggestions {
        min-width: min(36rem, calc(100vw - 2.5rem));
        width: max(100%, 20rem);
        max-height: 16rem;
        overflow-y: auto;
        box-shadow: 0 10px 28px rgba(92, 46, 31, 0.14);
    }
    .filter-suggestions .suggestion-btn {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        width: 100%;
        text-align: left;
        padding: 0.65rem 0.9rem;
        border-bottom: 1px solid #f5e6d8;
        transition: background-color 0.15s ease;
    }
    .filter-suggestions .suggestion-btn:last-child { border-bottom: 0; }
    .filter-suggestions .suggestion-btn:hover,
    .filter-suggestions .suggestion-btn:focus {
        background: #fdf6f0;
        outline: none;
    }
    .filter-suggestions .suggestion-code {
        flex: 0 0 auto;
        min-width: 5.5rem;
        font-weight: 700;
        color: #5C2E1F;
        font-size: 0.875rem;
        line-height: 1.35;
    }
    .filter-suggestions .suggestion-name {
        flex: 1 1 auto;
        color: #4b5563;
        font-size: 0.875rem;
        line-height: 1.45;
        word-break: break-word;
    }
    .sortable-th {
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }
    .sortable-th:hover { color: #8B4513; }
    .sortable-th .sort-icon {
        display: inline-block;
        margin-left: 0.25rem;
        font-size: 0.75rem;
        opacity: 0.45;
    }
    .sortable-th.is-active .sort-icon { opacity: 1; color: #8B4513; }
</style>
@endpush

@section('content')
@php
    $sortBy = $filters['sort_by'] ?? 'subject_code';
    $sortDir = $filters['sort_dir'] ?? 'asc';
    $sortLink = function (string $column) use ($sortBy, $sortDir) {
        $dir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

        return route('faculty-admin.reviews.index', array_merge(
            request()->except(['page', 'sort_by', 'sort_dir']),
            ['sort_by' => $column, 'sort_dir' => $dir],
        ));
    };
    $sortIcon = function (string $column) use ($sortBy, $sortDir) {
        if ($sortBy !== $column) {
            return '↕';
        }

        return $sortDir === 'asc' ? '↑' : '↓';
    };
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-[#5C2E1F]">อนุมัติรายวิชาที่ผ่านกรรมการคณะฯ</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">
                แสดงทุกสถานะ — กด «ตรวจแล้ว» หลังตรวจสอบเอกสารก่อนส่งกรรมการคณะฯ
                อนุมัติระดับคณะได้เมื่อสาขาวิชาอนุมัติแล้ว
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('faculty-admin.settings.term') }}" class="px-3 py-2 border border-amber-300 rounded-lg text-sm hover:bg-amber-50">กำหนดภาคการศึกษา</a>
            @if (\App\Support\SciGradeRole::isSuperAdmin())
                <a href="{{ route('faculty-admin.settings.programs.index') }}" class="px-3 py-2 border border-amber-300 rounded-lg text-sm hover:bg-amber-50">จัดการหลักสูตร</a>
            @endif
            <a href="{{ route('faculty-admin.settings.privileges.index') }}" class="px-3 py-2 border border-amber-300 rounded-lg text-sm hover:bg-amber-50">ผู้มีสิทธิใช้งาน</a>
        </div>
    </div>

    <div class="form-section rounded-xl p-5">
        <form method="GET" id="review-filter-form" class="grid md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_dir" value="{{ $sortDir }}">

            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สาขาวิชา</label>
                <select name="department_id" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">ทุกสาขาวิชา</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->department_id }}" @selected(($filters['department_id'] ?? null) == $dept->department_id)>
                            {{ $dept->department_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                <select name="term" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="1" @selected(($filters['term'] ?? 1) === 1)>ภาคต้น</option>
                    <option value="2" @selected(($filters['term'] ?? 2) === 2)>ภาคปลาย</option>
                    <option value="3" @selected(($filters['term'] ?? 3) === 3)>ภาคการศึกษาพิเศษ</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                <select name="year" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected(($filters['year'] ?? null) == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สถานะ</label>
                <select name="status" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">ทุกสถานะ</option>
                    <option value="0" @selected(($filters['status'] ?? '') === '0' || ($filters['status'] ?? null) === 0)>อาจารย์บันทึกแล้ว</option>
                    <option value="1" @selected(($filters['status'] ?? '') === '1' || ($filters['status'] ?? null) === 1)>สาขาอนุมัติ</option>
                    <option value="3" @selected(($filters['status'] ?? '') === '3' || ($filters['status'] ?? null) === 3)>ตรวจแล้ว</option>
                    <option value="2" @selected(($filters['status'] ?? '') === '2' || ($filters['status'] ?? null) === 2)>คณะอนุมัติ</option>
                    <option value="-1" @selected(($filters['status'] ?? '') === '-1' || ($filters['status'] ?? null) === -1)>ส่งกลับแก้ไข</option>
                </select>
            </div>

            <div class="relative md:col-span-2">
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">รหัสวิชา / ชื่อวิชา</label>
                <div class="grid sm:grid-cols-2 gap-2">
                    <div class="relative">
                        <input type="text" id="subject-code" name="subject_code" autocomplete="off"
                            value="{{ $filters['subject_code'] ?? '' }}" placeholder="รหัสวิชา"
                            class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <div id="subject-code-suggestions"
                            class="filter-suggestions hidden absolute left-0 right-0 top-full mt-1 z-30 bg-white border border-amber-200 rounded-lg"></div>
                    </div>
                    <div class="relative">
                        <input type="text" id="subject-name" name="subject" autocomplete="off"
                            value="{{ $filters['subject'] ?? '' }}" placeholder="ชื่อวิชา"
                            class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <div id="subject-name-suggestions"
                            class="filter-suggestions hidden absolute left-0 right-0 top-full mt-1 z-30 bg-white border border-amber-200 rounded-lg"></div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">พิมพ์รหัสหรือชื่อวิชาเพื่อเลือกจากรายการ หรือกรอกเองได้</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">แสดงต่อหน้า</label>
                <select name="per_page" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    @foreach ([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" @selected(request('per_page', 20) == $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3 lg:col-span-4 flex gap-3">
                <button type="submit" class="px-5 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-semibold hover:bg-[#6B3410]">ค้นหา</button>
                <a href="{{ route('faculty-admin.reviews.index', ['term' => $filters['term'], 'year' => $filters['year'], 'status' => 1]) }}"
                   class="px-5 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ล้างตัวกรอง</a>
            </div>
        </form>
    </div>

    @error('approval')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm whitespace-pre-line">{{ $message }}</div>
    @enderror
    @error('download')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

    @php
        $approvableCount = $reports->filter(fn ($report) => $report->canFacultyApprove())->count();
    @endphp

    <form method="POST" action="{{ route('faculty-admin.reviews.bulk-approve') }}" id="bulk-approve-form">
        @csrf
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3 no-print">
            <div class="text-sm text-gray-600">
                @if ($approvableCount > 0)
                    มี {{ $approvableCount }} รายการในหน้านี้ที่พร้อมอนุมัติระดับคณะ
                @else
                    ไม่มีรายการที่พร้อมอนุมัติในหน้านี้
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" id="btn-select-all-page"
                    class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50 {{ $approvableCount === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ $approvableCount === 0 ? 'disabled' : '' }}>
                    เลือกทั้งหมดที่อนุมัติได้
                </button>
                <button type="submit" id="btn-bulk-approve"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                    คณะอนุมัติที่เลือก (<span id="selected-count">0</span>)
                </button>
            </div>
        </div>
    </form>

    <form id="download-files-form" method="POST" action="{{ route('faculty-admin.reviews.files.download') }}" class="form-section rounded-xl p-4 space-y-3 mb-3">
        @csrf
        <input type="hidden" name="scope" id="download-scope" value="selected">
        @foreach ($filters as $key => $value)
            @if ($value !== null && $value !== '')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <div class="flex flex-wrap items-end gap-3 justify-between">
            <div>
                <p class="text-sm font-semibold text-[#5C2E1F]">ดาวน์โหลดไฟล์แนบ</p>
                <p class="text-xs text-[#7A4A3A]/80 mt-0.5">
                    เลือกดาวน์โหลดไฟล์ของอาจารย์ หรือ REG ที่ Admin สาขาอัปโหลด —
                    ไฟล์ REG ของ Admin สาขาจะตั้งชื่อเป็น <code class="text-[11px] bg-amber-50 px-1 rounded">รหัสวิชา-กลุ่ม-จำนวนนักศึกษา.pdf</code>
                </p>
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="block text-xs text-[#7A4A3A] mb-1">ประเภทไฟล์</label>
                    <select name="type" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[14rem]">
                        <option value="all">ทั้งหมด</option>
                        <option value="exam_report">แบบรายงานผลการสอบไล่ (อาจารย์)</option>
                        <option value="registrar_instructor">REG ของอาจารย์</option>
                        <option value="registrar_dept">REG ของ Admin สาขา</option>
                        <option value="registrar">REG ทั้งหมด (อาจารย์ + สาขา)</option>
                    </select>
                </div>
                <button type="button" id="btn-select-all-download"
                    class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">
                    เลือกทั้งหมดในหน้านี้
                </button>
                <button type="submit" class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50"
                    onclick="document.getElementById('download-scope').value='selected'">
                    ดาวน์โหลดที่เลือก
                </button>
                <button type="submit" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]"
                    onclick="document.getElementById('download-scope').value='all'">
                    ดาวน์โหลดทั้งหมด (ตามตัวกรอง)
                </button>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <table class="w-full text-sm min-w-[1240px]">
            <thead class="bg-amber-50">
                <tr>
                    <th class="px-3 py-2 text-center w-10">
                        <input type="checkbox" id="select-all-checkbox" class="rounded border-amber-400"
                            title="เลือกทั้งหมดในหน้านี้">
                    </th>
                    <th class="px-3 py-2 text-left">สาขาวิชา</th>
                    <th class="px-3 py-2 text-left sortable-th {{ $sortBy === 'subject_code' ? 'is-active' : '' }}">
                        <a href="{{ $sortLink('subject_code') }}" class="inline-flex items-center text-inherit hover:text-[#8B4513]">
                            รหัสวิชา <span class="sort-icon">{{ $sortIcon('subject_code') }}</span>
                        </a>
                    </th>
                    <th class="px-3 py-2 text-left sortable-th {{ $sortBy === 'subject' ? 'is-active' : '' }}">
                        <a href="{{ $sortLink('subject') }}" class="inline-flex items-center text-inherit hover:text-[#8B4513]">
                            ชื่อวิชา / อาจารย์ <span class="sort-icon">{{ $sortIcon('subject') }}</span>
                        </a>
                    </th>
                    <th class="px-3 py-2 text-center">Sec. / กลุ่ม</th>
                    <th class="px-3 py-2 text-center sortable-th {{ $sortBy === 'created' ? 'is-active' : '' }}">
                        <a href="{{ $sortLink('created') }}" class="inline-flex items-center justify-center text-inherit hover:text-[#8B4513]">
                            วันที่กรอก <span class="sort-icon">{{ $sortIcon('created') }}</span>
                        </a>
                    </th>
                    <th class="px-3 py-2 text-left">ไฟล์แนบ</th>
                    <th class="px-3 py-2 text-center sortable-th {{ $sortBy === 'status' ? 'is-active' : '' }}">
                        <a href="{{ $sortLink('status') }}" class="inline-flex items-center justify-center text-inherit hover:text-[#8B4513]">
                            สถานะ <span class="sort-icon">{{ $sortIcon('status') }}</span>
                        </a>
                    </th>
                    <th class="px-3 py-2 text-center">ทำรายการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    @php
                        $canAct = $report->canFacultyApprove();
                        $canMarkChecked = $report->canMarkFacultyChecked();
                        $canSendBack = (int) $report->approv === 2;
                        $badge = match ((int) $report->approv) {
                            1 => 'status-dept',
                            3 => 'status-checked',
                            2 => 'status-approved',
                            -1 => 'status-rejected',
                            default => 'status-pending',
                        };
                        $deptName = $queryService->resolveDepartmentName((string) $report->subject_code);
                        $sections = $report->enrollmentSections();
                        $sectionCount = $sections->count();
                    @endphp
                    <tr class="border-t border-amber-100 hover:bg-amber-50/40 {{ $canAct ? 'row-approvable' : '' }}">
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox" name="grade_ids[]" value="{{ $report->grade_id }}"
                                form="download-files-form"
                                class="row-select rounded border-amber-400 {{ $canAct ? 'row-approvable-select' : '' }}"
                                @if ($canAct) data-approvable="1" @endif>
                            @if ($canAct)
                                <input type="hidden" name="grade_ids[]" value="{{ $report->grade_id }}"
                                    form="bulk-approve-form" class="bulk-approve-mirror" disabled>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs {{ $deptName ? 'text-gray-600' : 'text-amber-700' }}">
                            @if ($deptName)
                                {{ $deptName }}
                            @else
                                <span title="รหัสวิชานี้ไม่ตรงกับเงื่อนไขรหัสของสาขาใดในระบบ">{{ \App\Services\FacultyAdmin\FacultyReportQueryService::UNMATCHED_DEPARTMENT_LABEL }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 font-medium text-[#5C2E1F]">{{ $report->subject_code }}</td>
                        <td class="px-3 py-2">
                            <div>{{ $report->subject }}</div>
                            <div class="text-xs text-gray-500">{{ $report->teacher }}</div>
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if ($sectionCount === 0)
                                <span class="text-xs text-gray-400">-</span>
                            @else
                                <div class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.7rem] font-bold {{ $sectionCount > 1 ? 'bg-sky-100 text-sky-800 border border-sky-200' : 'bg-amber-50 text-[#5C2E1F] border border-amber-200' }}">
                                    {{ $sectionCount }} Sec.
                                </div>
                                <div class="mt-1.5 flex flex-col gap-0.5 items-center">
                                    @foreach ($sections as $section)
                                        <span class="text-[0.7rem] text-[#5C2E1F] whitespace-nowrap" title="กลุ่ม {{ $section['sec'] }} — ลงทะเบียน {{ number_format($section['total']) }} คน">
                                            กลุ่ม {{ $section['sec'] }}
                                            <span class="text-gray-500">({{ number_format($section['total']) }} คน)</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center whitespace-nowrap">{{ \App\Support\ThaiDateTime::formatDate($report->created) }}</td>
                        <td class="px-3 py-2">
                            @include('partials.grade-report-files-admin', ['report' => $report])
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $badge }}">
                                {{ $report->workflowStatusLabel() }}
                            </span>
                            @if ($report->latestDeptApprovalLog)
                                <div class="text-[10px] text-gray-500 mt-1">สาขา: {{ $report->latestDeptApprovalLog->approver?->displayName() }}</div>
                            @endif
                            @if ($report->latestCentralApprovalLog)
                                <div class="text-[10px] text-gray-500 mt-0.5">
                                    {{ $report->latestCentralApprovalLog->action === 'central_checked' ? 'ตรวจโดย' : 'คณะ' }}:
                                    {{ $report->latestCentralApprovalLog->approver?->displayName() }}
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex flex-wrap justify-center gap-2">
                                @if ($canMarkChecked)
                                    <form method="POST" action="{{ route('faculty-admin.reviews.mark-checked', $report) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-orange-500 text-white rounded text-xs font-medium hover:bg-orange-600"
                                            onclick="return confirm('ยืนยันว่าตรวจเอกสารแล้ว พร้อมส่งกรรมการคณะฯ?')">
                                            ตรวจแล้ว
                                        </button>
                                    </form>
                                @endif
                                @if ($canAct)
                                    <form method="POST" action="{{ route('faculty-admin.reviews.approve', $report) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700">
                                            คณะอนุมัติ
                                        </button>
                                    </form>
                                    <button type="button" class="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700 btn-reject"
                                        data-action="{{ route('faculty-admin.reviews.reject', $report) }}">
                                        ส่งกลับแก้ไข
                                    </button>
                                @elseif ($canSendBack)
                                    <button type="button"
                                        class="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700 btn-send-back"
                                        data-action="{{ route('faculty-admin.reviews.send-back', $report) }}"
                                        data-subject="{{ $report->subject_code }}">
                                        ส่งกลับแก้ไข
                                    </button>
                                @elseif ((int) $report->approv === -1)
                                    <span class="text-xs text-red-700 w-full text-center">{{ $report->reason ?: 'ส่งกลับแก้ไข' }}</span>
                                @else
                                    <span class="text-xs text-gray-500">รอสาขาอนุมัติ</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-3 py-10 text-center text-gray-500">ไม่พบรายการตามเงื่อนไข</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $reports->links() }}</div>
</div>

<div id="reject-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden no-print">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl mx-4">
        <h3 class="font-bold text-lg mb-3 text-[#5C2E1F]">เหตุผลการส่งกลับแก้ไข</h3>
        <form id="reject-form" method="POST">
            @csrf
            <textarea name="remark" rows="3" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm mb-4" placeholder="ระบุเหตุผล (ถ้ามี)"></textarea>
            <div class="flex gap-3 justify-end">
                <button type="button" id="btn-cancel-reject" class="px-4 py-2 border rounded-lg text-sm">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium">ยืนยัน</button>
            </div>
        </form>
    </div>
</div>

<div id="send-back-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden no-print">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl mx-4">
        <h3 class="font-bold text-lg mb-2 text-[#5C2E1F]">ส่งกลับให้อาจารย์แก้ไข</h3>
        <p id="send-back-subject" class="text-sm text-gray-600 mb-3"></p>
        <form id="send-back-form" method="POST">
            @csrf
            <textarea name="remark" rows="3" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm mb-4" placeholder="ระบุเหตุผล (ถ้ามี)"></textarea>
            <div class="flex gap-3 justify-end">
                <button type="button" id="btn-cancel-send-back" class="px-4 py-2 border rounded-lg text-sm">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium">ยืนยันส่งกลับ</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    async function fetchSubjectSuggestions(q) {
        const res = await fetch(`/api/subjects/search?q=${encodeURIComponent(q)}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        return res.json();
    }

    function setupSubjectAutocomplete(inputId, listId) {
        const input = document.getElementById(inputId);
        const list = document.getElementById(listId);
        const codeInput = document.getElementById('subject-code');
        const nameInput = document.getElementById('subject-name');
        if (!input || !list || !codeInput || !nameInput) return;

        let timer = null;

        const hideList = () => {
            list.classList.add('hidden');
            list.innerHTML = '';
        };

        const selectSubject = (code, name) => {
            codeInput.value = code;
            nameInput.value = name;
            hideList();
        };

        const render = (items) => {
            if (!items.length) {
                hideList();
                return;
            }
            list.innerHTML = items.map((item) => `
                <button type="button" class="suggestion-btn"
                    data-code="${item.subject_code.replace(/"/g, '&quot;')}"
                    data-name="${item.subject.replace(/"/g, '&quot;')}">
                    <span class="suggestion-code">${item.subject_code}</span>
                    <span class="suggestion-name">${item.subject}</span>
                </button>
            `).join('');
            list.classList.remove('hidden');
            list.querySelectorAll('button').forEach((btn) => {
                btn.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    selectSubject(btn.dataset.code, btn.dataset.name);
                });
            });
        };

        const search = async (q) => {
            if (q.length < 1) {
                hideList();
                return;
            }
            try {
                render(await fetchSubjectSuggestions(q));
            } catch {
                hideList();
            }
        };

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => search(input.value.trim()), 250);
        });

        input.addEventListener('focus', () => {
            if (input.value.trim()) search(input.value.trim());
        });

        input.addEventListener('blur', () => setTimeout(hideList, 150));

        document.addEventListener('click', (e) => {
            if (!list.contains(e.target) && e.target !== input) hideList();
        });
    }

    setupSubjectAutocomplete('subject-code', 'subject-code-suggestions');
    setupSubjectAutocomplete('subject-name', 'subject-name-suggestions');

    const bulkForm = document.getElementById('bulk-approve-form');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const selectAllButton = document.getElementById('btn-select-all-page');
    const selectAllDownload = document.getElementById('btn-select-all-download');
    const bulkApproveButton = document.getElementById('btn-bulk-approve');
    const selectedCountEl = document.getElementById('selected-count');

    function rowCheckboxes() {
        return Array.from(document.querySelectorAll('.row-select'));
    }

    function approvableBoxes() {
        return rowCheckboxes().filter((box) => box.dataset.approvable === '1');
    }

    function syncBulkMirrors() {
        approvableBoxes().forEach((box) => {
            const mirror = box.parentElement?.querySelector('.bulk-approve-mirror');
            if (mirror) {
                mirror.disabled = !box.checked;
            }
        });
    }

    function updateBulkSelection() {
        const approvable = approvableBoxes();
        const checked = approvable.filter((box) => box.checked);
        const count = checked.length;
        const all = rowCheckboxes();
        const allChecked = all.filter((box) => box.checked);

        if (selectedCountEl) selectedCountEl.textContent = String(count);
        if (bulkApproveButton) bulkApproveButton.disabled = count === 0;
        syncBulkMirrors();

        if (selectAllCheckbox) {
            selectAllCheckbox.indeterminate = allChecked.length > 0 && allChecked.length < all.length;
            selectAllCheckbox.checked = all.length > 0 && allChecked.length === all.length;
        }
    }

    function setRows(boxes, checked) {
        boxes.forEach((box) => { box.checked = checked; });
        updateBulkSelection();
    }

    rowCheckboxes().forEach((box) => {
        box.addEventListener('change', updateBulkSelection);
    });

    selectAllCheckbox?.addEventListener('change', () => {
        setRows(rowCheckboxes(), selectAllCheckbox.checked);
    });

    selectAllDownload?.addEventListener('click', () => {
        setRows(rowCheckboxes(), true);
    });

    selectAllButton?.addEventListener('click', () => {
        setRows(approvableBoxes(), true);
    });

    bulkForm?.addEventListener('submit', (e) => {
        syncBulkMirrors();
        const count = approvableBoxes().filter((box) => box.checked).length;
        if (count === 0) {
            e.preventDefault();
        }
    });

    updateBulkSelection();

    const modal = document.getElementById('reject-modal');
    const form = document.getElementById('reject-form');
    document.querySelectorAll('.btn-reject').forEach((btn) => {
        btn.addEventListener('click', () => {
            form.action = btn.dataset.action;
            modal.classList.remove('hidden');
        });
    });
    document.getElementById('btn-cancel-reject').onclick = () => modal.classList.add('hidden');

    const sendBackModal = document.getElementById('send-back-modal');
    const sendBackForm = document.getElementById('send-back-form');
    const sendBackSubject = document.getElementById('send-back-subject');
    document.querySelectorAll('.btn-send-back').forEach((btn) => {
        btn.addEventListener('click', () => {
            sendBackForm.action = btn.dataset.action;
            sendBackSubject.textContent = `รายวิชา ${btn.dataset.subject} (คณะอนุมัติแล้ว) จะถูกส่งกลับให้อาจารย์แก้ไข`;
            sendBackModal.classList.remove('hidden');
        });
    });
    document.getElementById('btn-cancel-send-back').onclick = () => sendBackModal.classList.add('hidden');
})();
</script>
@endpush
