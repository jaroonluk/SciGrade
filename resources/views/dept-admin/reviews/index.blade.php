@extends('layouts.scigrad')

@section('title', 'ตรวจสอบรายวิชา — Admin สาขา')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">ตรวจสอบรายวิชา</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-[#5C2E1F]">ตรวจสอบรายการรายวิชา</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">รายวิชาที่อาจารย์ส่งเกรดมาในสาขาที่คุณมีสิทธิ์ตรวจสอบ</p>
        </div>
        <a href="{{ route('dept-admin.reg-grade-status.index') }}" class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">
            ตรวจสอบสถานะการส่ง
        </a>
        <a href="{{ route('dept-admin.reports.form') }}" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
            พิมพ์รายงานสาขา
        </a>
    </div>

    <div class="form-section rounded-xl p-5">
        <form method="GET" class="grid md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สาขาวิชา</label>
                <select name="department_id" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">ทุกสาขาที่มีสิทธิ์</option>
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
                    <option value="0" @selected(($filters['status'] ?? '') === '0' || ($filters['status'] ?? null) === 0)>บันทึกแล้ว</option>
                    <option value="1" @selected(($filters['status'] ?? '') === '1' || ($filters['status'] ?? null) === 1)>สาขาอนุมัติ</option>
                    <option value="3" @selected(($filters['status'] ?? '') === '3' || ($filters['status'] ?? null) === 3)>ตรวจแล้ว</option>
                    <option value="2" @selected(($filters['status'] ?? '') === '2' || ($filters['status'] ?? null) === 2)>คณะอนุมัติ</option>
                    <option value="-1" @selected(($filters['status'] ?? '') === '-1' || ($filters['status'] ?? null) === -1)>ส่งกลับแก้ไข</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">รหัสวิชา</label>
                <input type="text" name="subject_code" value="{{ $filters['subject_code'] ?? '' }}"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ชื่อวิชา</label>
                <input type="text" name="subject" value="{{ $filters['subject'] ?? '' }}"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
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
                <a href="{{ route('dept-admin.reviews.index') }}" class="px-5 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ล้างตัวกรอง</a>
            </div>
        </form>
    </div>

    @php
        $uploadTermLabel = match ((int) ($filters['term'] ?? 1)) {
            1 => 'ภาคต้น',
            2 => 'ภาคปลาย',
            default => 'ภาคการศึกษาพิเศษ',
        };
    @endphp
    <div id="registrar-bulk-upload" class="form-section rounded-xl p-5 space-y-4"
        data-preview-url="{{ route('dept-admin.reviews.registrar-files.preview') }}"
        data-upload-url="{{ route('dept-admin.reviews.registrar-files.store') }}">
        <div>
            <p class="text-sm font-semibold text-[#5C2E1F]">อัปโหลดใบส่งผลการศึกษา (REG) หลายไฟล์</p>
            <p class="text-xs text-[#7A4A3A]/80 mt-1">
                จับคู่ตามภาค/ปีที่กำลังกรอง:
                <span class="font-medium">{{ $uploadTermLabel }} ปีการศึกษา {{ $filters['year'] ?? '' }}</span>
                — รูปแบบชื่อไฟล์ <code class="text-[11px] bg-amber-50 px-1 rounded">รหัสวิชา-กลุ่ม.pdf</code>
                เช่น <code class="text-[11px] bg-amber-50 px-1 rounded">SC101011-01.pdf</code>
            </p>
            <p class="text-xs text-[#7A4A3A]/70 mt-0.5">อัปโหลดได้เมื่อรายวิชายังเป็นบันทึกแล้วหรือสาขาอนุมัติ (คณะยังไม่ตรวจ) แนะนำไม่เกิน 20 ไฟล์ต่อครั้ง</p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-[#7A4A3A] mb-1">เลือกไฟล์ PDF</label>
                <input type="file" id="registrar-files-input" accept="application/pdf,.pdf" multiple
                    class="block text-sm text-[#5C2E1F] file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-amber-100 file:text-[#5C2E1F] file:text-sm">
            </div>
            <button type="button" id="registrar-upload-btn" disabled
                class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410] disabled:opacity-50 disabled:cursor-not-allowed">
                อัปโหลด
            </button>
            <button type="button" id="registrar-clear-btn"
                class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">
                ล้างรายการ
            </button>
        </div>
        <div id="registrar-preview-wrap" class="hidden overflow-x-auto">
            <p class="text-xs font-semibold text-[#5C2E1F] mb-2">พรีวิวก่อนอัปโหลด</p>
            <table class="w-full text-xs min-w-[640px]">
                <thead class="bg-amber-50">
                    <tr>
                        <th class="px-2 py-1.5 text-left">ชื่อไฟล์ต้นฉบับ</th>
                        <th class="px-2 py-1.5 text-left">รหัส / กลุ่ม</th>
                        <th class="px-2 py-1.5 text-left">วิชาที่จับคู่</th>
                        <th class="px-2 py-1.5 text-right">ขนาด</th>
                        <th class="px-2 py-1.5 text-left">สถานะจับคู่</th>
                    </tr>
                </thead>
                <tbody id="registrar-preview-body"></tbody>
            </table>
        </div>
        <div id="registrar-result-wrap" class="hidden overflow-x-auto">
            <p class="text-xs font-semibold text-[#5C2E1F] mb-2">ผลการอัปโหลด</p>
            <p id="registrar-result-summary" class="text-xs text-[#7A4A3A] mb-2"></p>
            <table class="w-full text-xs min-w-[720px]">
                <thead class="bg-amber-50">
                    <tr>
                        <th class="px-2 py-1.5 text-left">ชื่อไฟล์</th>
                        <th class="px-2 py-1.5 text-left">ผล</th>
                        <th class="px-2 py-1.5 text-left">เหตุผล / ไฟล์ในระบบ</th>
                    </tr>
                </thead>
                <tbody id="registrar-result-body"></tbody>
            </table>
        </div>
        <p id="registrar-upload-error" class="hidden text-sm text-red-700"></p>
    </div>

    @error('approval')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror
    @error('download')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

    <form id="download-files-form" method="POST" action="{{ route('dept-admin.reviews.files.download') }}" class="form-section rounded-xl p-4 space-y-3">
        @csrf
        <input type="hidden" name="scope" id="download-scope" value="selected">
        @foreach ($filters as $key => $value)
            @if ($key !== 'department_ids' && $value !== null && $value !== '')
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <div class="flex flex-wrap items-end gap-3 justify-between">
            <div>
                <p class="text-sm font-semibold text-[#5C2E1F]">ดาวน์โหลดไฟล์แนบ</p>
                <p class="text-xs text-[#7A4A3A]/80 mt-0.5">
                    แยกเลือกไฟล์ของอาจารย์ หรือ REG ที่ Admin สาขาอัปโหลดได้ —
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
        <table class="w-full text-sm min-w-[1040px]">
            <thead class="bg-amber-50">
                <tr>
                    <th class="px-3 py-2 text-center w-10">
                        <input type="checkbox" id="select-all-download" class="rounded border-amber-400" title="เลือกทั้งหมดในหน้านี้">
                    </th>
                    <th class="px-3 py-2 text-left">รหัสวิชา</th>
                    <th class="px-3 py-2 text-left">ชื่อวิชา</th>
                    <th class="px-3 py-2 text-center">วันที่กรอก</th>
                    <th class="px-3 py-2 text-left">ไฟล์แนบ</th>
                    <th class="px-3 py-2 text-center">สถานะ</th>
                    <th class="px-3 py-2 text-center">ทำรายการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    @php
                        $canAct = (int) $report->approv === 0;
                        $isDeptResubmit = $canAct && $report->awaitingDeptResubmit();
                        $canSendBack = $canAct && ! $isDeptResubmit;
                        $badge = match ((int) $report->approv) {
                            1 => 'status-dept',
                            3 => 'status-checked',
                            2 => 'status-approved',
                            -1 => 'status-rejected',
                            default => 'status-pending',
                        };
                    @endphp
                    <tr class="border-t border-amber-100 hover:bg-amber-50/40">
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox" name="grade_ids[]" value="{{ $report->grade_id }}"
                                form="download-files-form" class="row-download-select rounded border-amber-400">
                        </td>
                        <td class="px-3 py-2 font-medium text-[#5C2E1F]">{{ $report->subject_code }}</td>
                        <td class="px-3 py-2">
                            <div>{{ $report->subject }}</div>
                            <div class="text-xs text-gray-500">{{ $report->teacher }}</div>
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
                                <div class="text-[10px] text-gray-500 mt-1">
                                    {{ $report->latestDeptApprovalLog->approver?->displayName() }}
                                    {{ $report->latestDeptApprovalLog->created_at ? \App\Support\ThaiDateTime::formatDateTime($report->latestDeptApprovalLog->created_at) : '' }}
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            <div class="flex flex-wrap justify-center gap-2">
                                @if ($canAct)
                                    <form method="POST" action="{{ route('dept-admin.reviews.approve', $report) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700">
                                            {{ $isDeptResubmit ? 'ส่งรายงานผลการสอบไล่อีกครั้ง' : 'ผ่านการรับรอง' }}
                                        </button>
                                    </form>
                                    @if ($canSendBack)
                                    <button type="button" class="px-3 py-1.5 bg-amber-600 text-white rounded text-xs font-medium hover:bg-amber-700 btn-send-back"
                                        data-action="{{ route('dept-admin.reviews.send-back', $report) }}"
                                        data-subject="{{ $report->subject_code }}">
                                        ส่งกลับให้แก้ไข
                                    </button>
                                    @endif
                                @else
                                    <a href="{{ route('grade-reports.print', $report) }}" target="_blank"
                                       class="px-3 py-1.5 border border-amber-300 rounded text-xs hover:bg-amber-50">ดูรายงาน</a>
                                    @if ($report->canDeptRevertToSaved())
                                        <form method="POST" action="{{ route('dept-admin.reviews.revert', $report) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="px-3 py-1.5 border border-amber-400 text-amber-900 rounded text-xs font-medium hover:bg-amber-50">
                                                กลับเป็นบันทึกแล้ว
                                            </button>
                                        </form>
                                    @endif
                                    @if ((int) $report->approv === -1)
                                        <span class="text-xs text-red-700 w-full text-center">{{ $report->reason ?: 'ส่งกลับแก้ไข' }}</span>
                                    @elseif (in_array((int) $report->approv, [1, 2, 3], true))
                                        <span class="text-xs text-gray-500 w-full text-center">{{ $report->approvalResultLabel() }}</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-10 text-center text-gray-500">ไม่พบรายการตามเงื่อนไข</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $reports->links() }}</div>
</div>

<div id="reject-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden no-print">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl mx-4">
        <h3 class="font-bold text-lg mb-3 text-[#5C2E1F]">เหตุผล (ถ้ามี)</h3>
        <form id="reject-form" method="POST">
            @csrf
            <textarea name="remark" rows="3" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm mb-4" placeholder="ระบุหมายเหตุการไม่อนุมัติ"></textarea>
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
            <textarea name="remark" rows="3" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm mb-4" placeholder="ระบุเหตุผลหรือข้อแนะนำ (ถ้ามี)"></textarea>
            <div class="flex gap-3 justify-end">
                <button type="button" id="btn-cancel-send-back" class="px-4 py-2 border rounded-lg text-sm">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm font-medium">ยืนยันส่งกลับ</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
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
            sendBackSubject.textContent = `รายวิชา ${btn.dataset.subject} จะถูกส่งกลับให้อาจารย์แก้ไข (ก่อนผ่านการรับรองจากสาขา)`;
            sendBackModal.classList.remove('hidden');
        });
    });
    document.getElementById('btn-cancel-send-back').onclick = () => sendBackModal.classList.add('hidden');

    const selectAll = document.getElementById('select-all-download');
    const rowChecks = () => document.querySelectorAll('.row-download-select');
    selectAll?.addEventListener('change', () => {
        rowChecks().forEach((cb) => { cb.checked = selectAll.checked; });
    });

    const uploadBox = document.getElementById('registrar-bulk-upload');
    const fileInput = document.getElementById('registrar-files-input');
    const uploadBtn = document.getElementById('registrar-upload-btn');
    const clearBtn = document.getElementById('registrar-clear-btn');
    const previewWrap = document.getElementById('registrar-preview-wrap');
    const previewBody = document.getElementById('registrar-preview-body');
    const resultWrap = document.getElementById('registrar-result-wrap');
    const resultBody = document.getElementById('registrar-result-body');
    const resultSummary = document.getElementById('registrar-result-summary');
    const uploadError = document.getElementById('registrar-upload-error');
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    const filterTerm = () => document.querySelector('select[name="term"]')?.value || '';
    const filterYear = () => document.querySelector('select[name="year"]')?.value || '';
    const filterDepartment = () => document.querySelector('select[name="department_id"]')?.value || '';

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[ch]));

    const firstError = (data) => {
        const errors = data?.errors;
        if (errors && typeof errors === 'object') {
            const first = Object.values(errors)[0];
            if (Array.isArray(first) && first[0]) return first[0];
        }
        return data?.message || null;
    };

    const formatSize = (bytes) => {
        const n = Number(bytes) || 0;
        if (n < 1024) return n + ' B';
        if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
        return (n / 1048576).toFixed(1) + ' MB';
    };

    const setError = (message) => {
        if (!uploadError) return;
        if (!message) {
            uploadError.classList.add('hidden');
            uploadError.textContent = '';
            return;
        }
        uploadError.textContent = message;
        uploadError.classList.remove('hidden');
    };

    const appendRegistrarLink = (gradeId, name, url) => {
        const box = document.querySelector(`.js-registrar-dept-list[data-grade-id="${gradeId}"]`);
        if (!box || !url) return;
        box.querySelector('.js-registrar-empty')?.remove();
        box.querySelector('.js-registrar-dept-empty')?.remove();
        const a = document.createElement('a');
        a.href = url;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        a.className = 'text-xs text-emerald-700 hover:underline inline-flex items-center gap-1 w-fit font-medium';
        a.title = name || 'ใบส่งผลการศึกษา (REG-Admin)';
        a.innerHTML = '<i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i> ใบส่งผลการศึกษา (REG-Admin)';
        box.appendChild(a);
        if (window.lucide?.createIcons) {
            window.lucide.createIcons();
        }
    };

    let selectedFiles = [];

    const renderPreview = (rows) => {
        previewBody.innerHTML = '';
        (rows || []).forEach((row, index) => {
            const file = selectedFiles[index];
            const tr = document.createElement('tr');
            tr.className = 'border-t border-amber-100 ' + (row.ok ? 'bg-green-50/60' : 'bg-red-50/70');
            const matched = row.matched
                ? `${row.subject_code || ''} ${row.subject || ''}`.trim()
                : 'ไม่พบ';
            const codeSec = (row.course_code || '—') + (row.section ? '-' + row.section : '');
            tr.innerHTML = `
                <td class="px-2 py-1.5">${esc(row.original_name || '')}</td>
                <td class="px-2 py-1.5 font-medium">${esc(codeSec)}</td>
                <td class="px-2 py-1.5">${esc(matched)}</td>
                <td class="px-2 py-1.5 text-right">${esc(formatSize(file?.size))}</td>
                <td class="px-2 py-1.5">${esc(row.ok ? 'จับคู่ได้' : (row.reason || 'จับคู่ไม่ได้'))}</td>
            `;
            previewBody.appendChild(tr);
        });
        previewWrap.classList.toggle('hidden', previewBody.children.length === 0);
        uploadBtn.disabled = selectedFiles.length === 0;
    };

    const previewFiles = async () => {
        setError('');
        resultWrap.classList.add('hidden');
        if (selectedFiles.length === 0) {
            previewWrap.classList.add('hidden');
            previewBody.innerHTML = '';
            uploadBtn.disabled = true;
            return;
        }
        try {
            const body = {
                term: Number(filterTerm()),
                year: Number(filterYear()),
                filenames: selectedFiles.map((f) => f.name),
            };
            if (filterDepartment()) body.department_id = Number(filterDepartment());
            const res = await fetch(uploadBox.dataset.previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (!res.ok) {
                setError(firstError(data) || 'ไม่สามารถตรวจสอบชื่อไฟล์ได้');
                return;
            }
            renderPreview(data.results || []);
        } catch (e) {
            setError('ไม่สามารถตรวจสอบชื่อไฟล์ได้');
        }
    };

    fileInput?.addEventListener('change', () => {
        selectedFiles = Array.from(fileInput.files || []);
        previewFiles();
    });

    clearBtn?.addEventListener('click', () => {
        selectedFiles = [];
        if (fileInput) fileInput.value = '';
        previewBody.innerHTML = '';
        previewWrap.classList.add('hidden');
        resultBody.innerHTML = '';
        resultWrap.classList.add('hidden');
        uploadBtn.disabled = true;
        setError('');
    });

    uploadBtn?.addEventListener('click', async () => {
        if (selectedFiles.length === 0) return;
        uploadBtn.disabled = true;
        setError('');
        const form = new FormData();
        form.append('term', filterTerm());
        form.append('year', filterYear());
        if (filterDepartment()) form.append('department_id', filterDepartment());
        selectedFiles.forEach((file) => form.append('attachments[]', file));
        try {
            const res = await fetch(uploadBox.dataset.uploadUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: form,
            });
            const data = await res.json();
            if (!res.ok) {
                setError(firstError(data) || 'อัปโหลดไม่สำเร็จ');
                uploadBtn.disabled = false;
                return;
            }
            resultBody.innerHTML = '';
            (data.results || []).forEach((row) => {
                const tr = document.createElement('tr');
                tr.className = 'border-t border-amber-100 ' + (row.ok ? 'bg-green-50/70' : 'bg-red-50/80');
                const statusClass = row.ok ? 'text-green-800' : 'text-red-800';
                const nameTd = document.createElement('td');
                nameTd.className = 'px-2 py-1.5';
                nameTd.textContent = row.original_name || '';
                const statusTd = document.createElement('td');
                statusTd.className = 'px-2 py-1.5 font-semibold ' + statusClass;
                statusTd.textContent = row.ok ? 'สำเร็จ' : 'ไม่สำเร็จ';
                const detailTd = document.createElement('td');
                detailTd.className = 'px-2 py-1.5';
                if (row.ok) {
                    detailTd.append(row.stored_name ? `เก็บเป็น ${row.stored_name}` : 'อัปโหลดสำเร็จ');
                    if (row.view_url) {
                        const link = document.createElement('a');
                        link.href = row.view_url;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.className = 'font-medium underline ml-1';
                        link.textContent = 'เปิดดู PDF';
                        detailTd.append(' ', link);
                    }
                    appendRegistrarLink(
                        row.grade_id,
                        row.download_name || row.stored_name || row.original_name,
                        row.view_url
                    );
                } else {
                    detailTd.textContent = row.reason || '';
                }
                tr.append(nameTd, statusTd, detailTd);
                resultBody.appendChild(tr);
            });
            resultSummary.textContent = `สำเร็จ ${data.ok_count ?? 0} ไฟล์ · ไม่สำเร็จ ${data.fail_count ?? 0} ไฟล์`;
            resultWrap.classList.remove('hidden');
        } catch (e) {
            setError('อัปโหลดไม่สำเร็จ');
        }
        uploadBtn.disabled = selectedFiles.length === 0;
    });
})();
</script>
@endpush
