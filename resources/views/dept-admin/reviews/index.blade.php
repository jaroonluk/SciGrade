@extends('layouts.scigrad')

@php
    $reviewMode = $reviewMode ?? 'intake';
    $isMeetingMode = $reviewMode === 'meeting';
    $pageTitle = $isMeetingMode
        ? 'อนุมัติรายวิชาที่ผ่านการเห็นชอบที่ประชุมสาขาฯ'
        : 'รายวิชาที่อาจารย์สาขาวิชาส่งเกรด';
    $pageSubtitle = $isMeetingMode
        ? 'แสดงเฉพาะรายการที่นำเข้าที่ประชุมสาขาแล้ว — กดผ่านที่ประชุมสาขา ส่งกลับ หรือดูรายงาน'
        : 'รายวิชาที่อาจารย์ส่งเกรดมาในสาขาที่คุณมีสิทธิ์ — นำเข้าที่ประชุมสาขา ส่งกลับ หรือกลับเป็นบันทึกแล้ว';
    $formAction = $isMeetingMode
        ? route('dept-admin.reviews.meeting-approval')
        : route('dept-admin.reviews.index');
@endphp

@section('title', $pageTitle.' — Admin สาขา')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">{{ $pageTitle }}</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-[#5C2E1F]">{{ $pageTitle }}</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">{{ $pageSubtitle }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($isMeetingMode)
                <a href="{{ route('dept-admin.reviews.index') }}" class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">
                    รายวิชาที่อาจารย์ส่งเกรด
                </a>
            @else
                <a href="{{ route('dept-admin.reviews.meeting-approval') }}" class="px-4 py-2 border border-sky-300 rounded-lg text-sm text-sky-900 hover:bg-sky-50">
                    อนุมัติที่ประชุมสาขาฯ
                </a>
            @endif
            <a href="{{ route('dept-admin.registrar-upload.index') }}" class="px-4 py-2 border border-emerald-300 rounded-lg text-sm text-emerald-900 hover:bg-emerald-50">
                อัปโหลด มข.11
            </a>
            <a href="{{ route('dept-admin.reg-grade-status.index') }}" class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">
                รายงานสถานะการส่ง
            </a>
            <a href="{{ route('dept-admin.reports.form') }}" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
                พิมพ์รายงานสาขา
            </a>
        </div>
    </div>

    <div class="form-section rounded-xl p-5">
        <form method="GET" action="{{ $formAction }}" class="grid md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
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
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ระดับการศึกษา</label>
                <select name="education_level" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="all" @selected(($filters['education_level'] ?? 'all') === 'all')>รวมทั้งหมด</option>
                    <option value="bachelor" @selected(($filters['education_level'] ?? '') === 'bachelor')>ปริญญาตรี</option>
                    <option value="master" @selected(($filters['education_level'] ?? '') === 'master')>ปริญญาโท</option>
                    <option value="doctoral" @selected(($filters['education_level'] ?? '') === 'doctoral')>ปริญญาเอก</option>
                    <option value="graduate" @selected(($filters['education_level'] ?? '') === 'graduate')>บัณฑิตศึกษา (โท+เอก)</option>
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
                @if ($isMeetingMode)
                    <input type="hidden" name="status" value="4">
                    <div class="w-full border border-sky-200 rounded-lg px-3 py-2 text-sm bg-sky-50 text-sky-900">
                        นำเข้าที่ประชุมสาขา
                    </div>
                @else
                    <select name="status" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">ทุกสถานะ</option>
                        <option value="0" @selected(($filters['status'] ?? '') === '0' || ($filters['status'] ?? null) === 0)>บันทึกแล้ว</option>
                        <option value="4" @selected(($filters['status'] ?? '') === '4' || ($filters['status'] ?? null) === 4)>นำเข้าที่ประชุมสาขา</option>
                        <option value="1" @selected(($filters['status'] ?? '') === '1' || ($filters['status'] ?? null) === 1)>ผ่านที่ประชุมสาขา</option>
                        <option value="3" @selected(($filters['status'] ?? '') === '3' || ($filters['status'] ?? null) === 3)>ตรวจแล้ว</option>
                        <option value="2" @selected(($filters['status'] ?? '') === '2' || ($filters['status'] ?? null) === 2)>คณะอนุมัติ</option>
                        <option value="-1" @selected(($filters['status'] ?? '') === '-1' || ($filters['status'] ?? null) === -1)>ส่งกลับแก้ไข</option>
                    </select>
                @endif
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
                <a href="{{ $formAction }}" class="px-5 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ล้างตัวกรอง</a>
            </div>
        </form>
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
                    แยกเลือกไฟล์ของอาจารย์ หรือ มข.11 ที่สาขาอัปโหลดได้ —
                    มข.11 ของอาจารย์: <code class="text-[11px] bg-amber-50 px-1 rounded">รหัสวิชา-กลุ่ม.pdf</code>
                    · มข.11 ของสาขา: <code class="text-[11px] bg-amber-50 px-1 rounded">รหัสวิชา-กลุ่ม-จำนวนนักศึกษา.pdf</code>
                </p>
            </div>
            <div class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="block text-xs text-[#7A4A3A] mb-1">ประเภทไฟล์</label>
                    <select name="type" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[14rem]">
                        <option value="all">ทั้งหมด</option>
                        <option value="exam_report">แบบรายงานผลการสอบไล่ (อาจารย์)</option>
                        <option value="registrar_instructor">มข.11 ของอาจารย์</option>
                        <option value="registrar_dept">มข.11 ของสาขา</option>
                        <option value="registrar">มข.11 ทั้งหมด (อาจารย์+สาขา)</option>
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
        <table class="w-full text-sm min-w-[1120px]">
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
                        $approv = (int) $report->approv;
                        $isSaved = $approv === 0;
                        $isMeetingQueued = $approv === \App\Enums\GradeApprovalStatus::DepartmentMeetingQueued->value;
                        $isDeptResubmit = $isSaved && $report->awaitingDeptResubmit();
                        // หน้า intake: นำเข้า / ส่งกลับ / กลับเป็นบันทึกแล้ว
                        // หน้า meeting: ผ่านที่ประชุม / ส่งกลับ / ดูรายงาน
                        $canQueueMeeting = ! $isMeetingMode && $isSaved;
                        $canPassMeeting = $isMeetingMode && $isMeetingQueued;
                        $canSendBack = (($isMeetingMode && $isMeetingQueued) || (! $isMeetingMode && ($isSaved || $isMeetingQueued)))
                            && ! $isDeptResubmit;
                        $canRevert = ! $isMeetingMode && $report->canDeptRevertToSaved();
                        $showViewReport = $isMeetingMode || $canQueueMeeting || $canPassMeeting || $canRevert || $canSendBack || $approv !== 0;
                        $badge = match ($approv) {
                            4 => 'status-checked',
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
                            @include('partials.grade-report-files-admin', [
                                'report' => $report,
                                'allowDeptRegDelete' => true,
                            ])
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
                                @if ($canQueueMeeting)
                                    <form method="POST" action="{{ route('dept-admin.reviews.queue-meeting', $report) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-sky-600 text-white rounded text-xs font-medium hover:bg-sky-700">
                                            {{ $isDeptResubmit ? 'ส่งรายงานอีกครั้ง · นำเข้าที่ประชุมสาขา' : 'นำเข้าที่ประชุมสาขา' }}
                                        </button>
                                    </form>
                                @endif
                                @if ($canPassMeeting)
                                    <form method="POST" action="{{ route('dept-admin.reviews.approve', $report) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700">
                                            ผ่านที่ประชุมสาขา
                                        </button>
                                    </form>
                                @endif
                                @if ($canSendBack)
                                    <button type="button" class="px-3 py-1.5 bg-amber-600 text-white rounded text-xs font-medium hover:bg-amber-700 btn-send-back"
                                        data-action="{{ route('dept-admin.reviews.send-back', $report) }}"
                                        data-subject="{{ $report->subject_code }}">
                                        ส่งกลับให้แก้ไข
                                    </button>
                                @endif
                                @if ($showViewReport)
                                    <a href="{{ route('grade-reports.print', $report) }}" target="_blank"
                                       class="px-3 py-1.5 border border-amber-300 rounded text-xs hover:bg-amber-50">ดูรายงาน</a>
                                @endif
                                @if ($canRevert)
                                    <form method="POST" action="{{ route('dept-admin.reviews.revert', $report) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 border border-amber-400 text-amber-900 rounded text-xs font-medium hover:bg-amber-50">
                                            กลับเป็นบันทึกแล้ว
                                        </button>
                                    </form>
                                @endif
                                @if ($approv === -1)
                                    <span class="text-xs text-red-700 w-full text-center">{{ $report->reason ?: 'ส่งกลับแก้ไข' }}</span>
                                @elseif (! $canQueueMeeting && ! $canPassMeeting && ! $canSendBack && in_array($approv, [1, 2, 3], true))
                                    <span class="text-xs text-gray-500 w-full text-center">{{ $report->approvalResultLabel() }}</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-10 text-center text-gray-500">
                            {{ $isMeetingMode ? 'ยังไม่มีรายวิชาที่นำเข้าที่ประชุมสาขา' : 'ไม่พบรายการตามเงื่อนไข' }}
                        </td>
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
            sendBackSubject.textContent = `รายวิชา ${btn.dataset.subject} จะถูกส่งกลับให้อาจารย์แก้ไข (ก่อนผ่านที่ประชุมสาขา)`;
            sendBackModal.classList.remove('hidden');
        });
    });
    document.getElementById('btn-cancel-send-back').onclick = () => sendBackModal.classList.add('hidden');

    const selectAll = document.getElementById('select-all-download');
    const rowChecks = () => document.querySelectorAll('.row-download-select');
    selectAll?.addEventListener('change', () => {
        rowChecks().forEach((cb) => { cb.checked = selectAll.checked; });
    });

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const regAdminDeleteUrl = (gradeId, fileId) =>
        @json(rtrim(url('/dept-admin/reviews'), '/')) + `/${gradeId}/registrar-files/${fileId}`;

    function bindDeleteRegAdminFile(btn) {
        if (!btn || btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            if (!confirm('ต้องการลบไฟล์ REG-Admin นี้หรือไม่?')) return;

            const deleteUrl = btn.dataset.deleteUrl
                || regAdminDeleteUrl(btn.dataset.gradeId, btn.dataset.fileId);

            const res = await fetch(deleteUrl, {
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

            const row = btn.closest('.js-reg-admin-file-row');
            const box = row?.closest('.js-registrar-dept-list');
            row?.remove();

            if (box && !box.querySelector('.js-reg-admin-file-row')) {
                const empty = document.createElement('span');
                empty.className = 'js-registrar-empty js-registrar-dept-empty text-[11px] text-emerald-800/45';
                empty.textContent = 'ไม่มีไฟล์';
                box.appendChild(empty);
            }
        });
    }

    document.querySelectorAll('.btn-delete-reg-admin-file').forEach(bindDeleteRegAdminFile);
})();
</script>
@endpush
