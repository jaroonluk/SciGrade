@extends('layouts.scigrad')

@section('title', 'หน้าหลัก — SciGrad')

@push('styles')
<style>
    .entry-card { transition: all .2s; border: 2px solid #E8C4B8; }
    .entry-card:hover { border-color: #C4725C; box-shadow: 0 6px 20px rgba(139,69,19,.12); transform: translateY(-2px); }
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
    @media (min-width: 640px) { .progress-step .label { font-size: .8rem; } }
    .report-table th { background: #fdf6f0; color: #5C2E1F; font-weight: 600; }
    .report-table td, .report-table th { padding: .65rem .75rem; border-bottom: 1px solid #f0e0d0; vertical-align: top; }
    .report-table tr:hover td { background: #fffaf5; }
    .action-btn { display: inline-flex; align-items: center; gap: .25rem; padding: .4rem .75rem; border-radius: .5rem; font-size: .8rem; font-weight: 600; }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-[#5C2E1F]">ยินดีต้อนรับ, {{ $staffDisplayName }}</h2>
        <p class="text-[#7A4A3A]/80 mt-1 text-base">เลือกบทบาทและเมนูงานที่ต้องการดำเนินการ</p>
    </div>

    <div class="form-section rounded-xl p-5 mb-8 no-print">
        <p class="text-sm font-semibold text-[#5C2E1F] mb-3">เลือกบทบาทการใช้งาน</p>
        <form method="POST" action="{{ route('role.set') }}" class="flex flex-wrap gap-3">
            @csrf
            @foreach ([
                'instructor' => 'อาจารย์',
                'dept_admin' => 'Admin สาขา',
                'faculty_admin' => 'Admin กลาง',
            ] as $value => $label)
                <button type="submit" name="role" value="{{ $value }}"
                    class="px-5 py-2.5 rounded-lg text-sm font-medium border transition
                    {{ $role === $value
                        ? 'bg-[#8B4513] text-white border-[#8B4513]'
                        : 'bg-white text-[#5C2E1F] border-[#E8C4B8] hover:border-[#C4725C]' }}">
                    {{ $label }}
                </button>
            @endforeach
        </form>
    </div>

    @if ($role === 'instructor')
        <h3 class="text-lg font-bold text-[#5C2E1F] mb-4 flex items-center gap-2">
            <i data-lucide="user" class="w-5 h-5"></i> เมนูอาจารย์ — กรอกผลสอบ
        </h3>

        <div class="grid md:grid-cols-2 gap-5 mb-8">
            <a href="{{ route('grade-reports.create', ['term' => $term, 'year' => $year, 'return' => 'dashboard']) }}" class="entry-card rounded-xl p-6 bg-white block">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-xl bg-[#FAF0E6] flex items-center justify-center shrink-0 p-2">
                        <img src="{{ asset('images/icons/grade-manual-entry.svg') }}" alt="" class="w-10 h-10" width="40" height="40">
                    </div>
                    <div>
                        <p class="text-lg font-bold text-[#5C2E1F]">กรอกข้อมูลเอง</p>
                        <p class="text-sm text-[#7A4A3A]/80 mt-2 leading-relaxed">
                            สร้างแบบรายงานผลการสอบไล่และกรอกจำนวนนักศึกษาทีละ Section ผ่านฟอร์มในระบบ
                        </p>
                        <span class="inline-block mt-3 text-sm font-semibold text-[#8B4513]">คลิกเพื่อเริ่มกรอก →</span>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.upload') }}" class="entry-card rounded-xl p-6 bg-white block">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-xl bg-[#FAF0E6] flex items-center justify-center text-[#8B4513] shrink-0">
                        <i data-lucide="upload-cloud" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-[#5C2E1F]">อัปโหลดไฟล์จากสำนักทะเบียน</p>
                        <p class="text-sm text-[#7A4A3A]/80 mt-2 leading-relaxed">
                            นำเข้าไฟล์รายงานผลสอบจากสำนักทะเบียน แล้วตรวจสอบ/แก้ไขก่อนบันทึก
                        </p>
                        <span class="inline-block mt-3 text-sm font-semibold text-[#8B4513]">คลิกเพื่ออัปโหลด →</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="form-section rounded-xl p-5 mb-5">
            <h4 class="text-base font-bold text-[#5C2E1F] mb-1 flex items-center gap-2">
                <i data-lucide="list" class="w-5 h-5"></i>
                รายวิชาที่กรอกแล้ว — แบบรายงานผลการสอบไล่
            </h4>
            <p class="text-sm text-[#7A4A3A]/80 mb-4">เลือกภาคการศึกษาเพื่อดู แก้ไข ลบ หรือพิมพ์รายงาน</p>

            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-4 mb-5">
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

            <div class="mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-sm text-[#5C2E1F]">
                <strong>สถานะการอนุมัติ:</strong>
                <span class="inline-block mx-1 px-2 py-0.5 rounded status-pending text-xs">รออนุมัติ (0)</span>
                <span class="inline-block mx-1 px-2 py-0.5 rounded status-dept text-xs">สาขาอนุมัติ (1)</span>
                <span class="inline-block mx-1 px-2 py-0.5 rounded status-approved text-xs">คณะอนุมัติ (2)</span>
            </div>

            @if ($reports->isEmpty())
                <div class="text-center py-12 bg-white rounded-xl border border-dashed border-amber-300">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto text-amber-400 mb-3"></i>
                    <p class="text-[#5C2E1F] font-medium">ยังไม่มีรายวิชาในภาคการศึกษานี้</p>
                    <p class="text-sm text-gray-500 mt-1">กด «กรอกข้อมูลเอง» หรือ «อัปโหลดไฟล์» เพื่อเริ่มสร้างรายงาน</p>
                </div>
            @else
                <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
                    <table class="report-table w-full text-sm min-w-[640px]">
                        <thead>
                            <tr>
                                <th class="text-left">รายวิชา</th>
                                <th class="text-left" style="min-width:14rem">ความคืบหน้า / สถานะ</th>
                                <th class="text-center">ทำรายการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                @php
                                    $step = $report->approvalStep();
                                    $rejected = (int) $report->approv === -1;
                                    $canEdit = $report->canEdit();
                                    $canPrint = $report->canPrint();
                                @endphp
                                <tr>
                                    <td>
                                        <p class="font-semibold text-[#5C2E1F]">{{ $report->subject_code }}</p>
                                        <p class="text-gray-600 mt-0.5">{{ $report->subject }}</p>
                                    </td>
                                    <td>
                                        <div class="flex gap-1 mb-2 max-w-xs mx-auto sm:mx-0">
                                            <div class="progress-step done {{ $rejected ? 'rejected' : '' }}">
                                                <div class="dot">1</div>
                                                <div class="label">บันทึกแล้ว</div>
                                            </div>
                                            <div class="progress-step {{ $step >= 1 ? 'done' : '' }} {{ $step === 0 && ! $rejected ? 'current' : '' }}">
                                                <div class="dot">2</div>
                                                <div class="label">สาขาอนุมัติ</div>
                                            </div>
                                            <div class="progress-step {{ $step >= 2 ? 'done' : '' }} {{ $step === 1 ? 'current' : '' }}">
                                                <div class="dot">3</div>
                                                <div class="label">คณะอนุมัติ</div>
                                            </div>
                                        </div>
                                        @php
                                            $badge = match ((int) $report->approv) {
                                                1 => 'status-dept',
                                                2 => 'status-approved',
                                                -1 => 'status-rejected',
                                                default => 'status-pending',
                                            };
                                        @endphp
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $badge }}">
                                            {{ $report->statusShortLabel() }}
                                        </span>
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
                                                <a href="{{ route('grade-reports.edit', ['gradeReport' => $report->grade_id, 'term' => $term, 'year' => $year, 'return' => 'dashboard']) }}"
                                                   class="action-btn border border-amber-300 text-[#5C2E1F] hover:bg-amber-50">
                                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i> แก้ไข
                                                </a>
                                                <button type="button" class="action-btn bg-red-600 text-white hover:bg-red-700 btn-delete-report"
                                                    data-id="{{ $report->grade_id }}"
                                                    data-subject="{{ $report->subject_code }}">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> ลบ
                                                </button>
                                            @else
                                                @if ($canPrint)
                                                    <a href="{{ route('grade-reports.print', $report->grade_id) }}" target="_blank"
                                                       class="action-btn bg-amber-700 text-white hover:bg-amber-800">
                                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i> พิมพ์
                                                    </a>
                                                @endif
                                                <span class="text-xs text-gray-500 text-center block w-full mt-1">{{ $report->statusLabel() }}</span>
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
                    ** วิชาที่ส่งเกรดช้าและมี I ต้องแนบบันทึกมาพร้อมกับใบส่งเกรด
                </p>
            @endif
        </div>
    @endif

    @if ($role === 'dept_admin')
        <h3 class="text-lg font-bold text-[#5C2E1F] mb-4 flex items-center gap-2">
            <i data-lucide="shield-check" class="w-5 h-5"></i> เมนู Admin สาขา
        </h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <a href="{{ route('grade-reports.approve') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="list-checks" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">ตรวจสอบและอนุมัติ (สาขา)</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">อนุมัติหรือส่งกลับแก้ไขในระดับสาขา</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.print.summary') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="printer" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">พิมพ์ใบส่งเกรดภาพรวมสาขา</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">พิมพ์รายงานสรุปทุกสถานะในสาขา</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.reports') }}" class="menu-card rounded-xl p-5 block sm:col-span-2">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">ดูรายงานตามสถานะ</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">ดูรายงานทุกสถานะ (รออนุมัติ / สาขาอนุมัติ / คณะอนุมัติ / ส่งกลับ)</p>
                    </div>
                </div>
            </a>
        </div>
    @endif

    @if ($role === 'faculty_admin')
        <h3 class="text-lg font-bold text-[#5C2E1F] mb-4 flex items-center gap-2">
            <i data-lucide="building-2" class="w-5 h-5"></i> เมนู Admin กลาง
        </h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <a href="{{ route('grade-reports.approve') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="badge-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">อนุมัติระดับคณะ</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">ตรวจสอบรายการที่สาขาอนุมัติแล้ว</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.reports') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="layers" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">ดูรายงานทุกสาขา</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">ดูรายงานจากทุกสาขา กรองตามสถานะ</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.print.summary') }}" class="menu-card rounded-xl p-5 block sm:col-span-2">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">พิมพ์รายงานรวม</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">พิมพ์รายงานรวมทุกสาขา หรือเลือกทีละสาขา</p>
                    </div>
                </div>
            </a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function() {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

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

    if (typeof lucide !== 'undefined') lucide.createIcons();
})();
</script>
@endpush
