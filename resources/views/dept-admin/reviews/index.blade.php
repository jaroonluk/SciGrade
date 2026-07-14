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
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">วันที่กรอก (จาก)</label>
                <input type="date" name="created_from" value="{{ $filters['created_from'] ?? '' }}"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">วันที่กรอก (ถึง)</label>
                <input type="date" name="created_to" value="{{ $filters['created_to'] ?? '' }}"
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

    @error('approval')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <table class="w-full text-sm min-w-[960px]">
            <thead class="bg-amber-50">
                <tr>
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
                            2 => 'status-approved',
                            -1 => 'status-rejected',
                            default => 'status-pending',
                        };
                    @endphp
                    <tr class="border-t border-amber-100 hover:bg-amber-50/40">
                        <td class="px-3 py-2 font-medium text-[#5C2E1F]">{{ $report->subject_code }}</td>
                        <td class="px-3 py-2">
                            <div>{{ $report->subject }}</div>
                            <div class="text-xs text-gray-500">{{ $report->teacher }}</div>
                        </td>
                        <td class="px-3 py-2 text-center whitespace-nowrap">{{ \App\Support\ThaiDateTime::formatDate($report->created) }}</td>
                        <td class="px-3 py-2">
                            @if ($report->files->isEmpty())
                                <span class="text-xs text-gray-400">ไม่มีไฟล์</span>
                            @else
                                <div class="flex flex-col gap-1">
                                    @foreach ($report->files as $file)
                                        <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                                           target="_blank" class="text-xs text-[#8B4513] hover:underline">
                                            {{ $file->original_name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
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
                                        <button type="submit" class="px-3 py-1.5 bg-green-600 text-white rounded text-xs font-medium hover:bg-green-700"
                                            onclick="return confirm('{{ $isDeptResubmit ? 'ยืนยันส่งรายงานผลการสอบไล่อีกครั้ง?' : 'ยืนยันผ่านการรับรองผลสอบ?' }}')">
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
                                    @if ((int) $report->approv === -1)
                                        <span class="text-xs text-red-700 w-full text-center">{{ $report->reason ?: 'ส่งกลับแก้ไข' }}</span>
                                    @elseif (in_array((int) $report->approv, [1, 2], true))
                                        <span class="text-xs text-gray-500 w-full text-center">{{ $report->approvalResultLabel() }}</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-10 text-center text-gray-500">ไม่พบรายการตามเงื่อนไข</td>
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
})();
</script>
@endpush
