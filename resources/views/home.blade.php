@extends('layouts.scigrad')

@section('title', 'หน้าหลัก — SciGrad')

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

    /* Admin กลาง / Super Admin menu groups */
    .admin-workflow {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }
    @@media (max-width: 900px) {
        .admin-workflow { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @@media (max-width: 520px) {
        .admin-workflow { grid-template-columns: 1fr; }
    }
    .admin-workflow-step {
        border-radius: 0.85rem;
        padding: 0.7rem 0.75rem;
        border: 1px solid transparent;
        background: #fff;
        text-align: left;
        min-height: 4.25rem;
    }
    .admin-workflow-step .step-no {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        opacity: 0.85;
    }
    .admin-workflow-step .step-title {
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1.25;
        margin-top: 0.2rem;
        color: #3d2a22;
    }
    .admin-section {
        border-radius: 1.1rem;
        border: 1px solid #e8d5c8;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 1px 0 rgba(92, 46, 31, 0.04);
    }
    .admin-section-head {
        padding: 0.95rem 1.15rem;
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        border-bottom: 1px solid rgba(92, 46, 31, 0.08);
    }
    .admin-section-body { padding: 1rem 1.15rem 1.15rem; }
    .admin-section .menu-card {
        background: rgba(255, 255, 255, 0.72);
        border-color: rgba(92, 46, 31, 0.12);
    }
    .admin-section .menu-card:hover {
        background: #fff;
    }
    .menu-card .menu-icon {
        width: 2.6rem;
        height: 2.6rem;
        border-radius: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .menu-card .menu-step {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        margin-bottom: 0.15rem;
        opacity: 0.9;
    }

    .admin-tone-docs {
        --tone: #0f766e;
        --tone-soft: #ecfdf8;
        --tone-border: #99f6e4;
        --tone-icon: #0f766e;
        border-color: var(--tone-border);
    }
    .admin-tone-docs .admin-section-head,
    .admin-workflow-step.tone-docs {
        background: linear-gradient(135deg, #ecfdf8 0%, #f0fdfa 55%, #fff 100%);
        border-color: #99f6e4;
    }
    .admin-tone-docs .menu-icon { background: #ccfbf1; color: #0f766e; }
    .admin-tone-docs .menu-step, .admin-tone-docs .step-no { color: #0f766e; }

    .admin-tone-approve {
        --tone: #166534;
        --tone-soft: #f0fdf4;
        --tone-border: #bbf7d0;
        border-color: var(--tone-border);
    }
    .admin-tone-approve .admin-section-head,
    .admin-workflow-step.tone-approve {
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 55%, #fff 100%);
        border-color: #bbf7d0;
    }
    .admin-tone-approve .menu-icon { background: #dcfce7; color: #166534; }
    .admin-tone-approve .menu-step, .admin-tone-approve .step-no { color: #166534; }

    .admin-tone-courses {
        --tone: #9a3412;
        --tone-soft: #fff7ed;
        --tone-border: #fed7aa;
        border-color: var(--tone-border);
    }
    .admin-tone-courses .admin-section-head,
    .admin-workflow-step.tone-courses {
        background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 55%, #fff 100%);
        border-color: #fed7aa;
    }
    .admin-tone-courses .menu-icon { background: #ffedd5; color: #9a3412; }
    .admin-tone-courses .menu-step, .admin-tone-courses .step-no { color: #c2410c; }

    .admin-tone-settings {
        --tone: #1e4b7b;
        --tone-soft: #eff6ff;
        --tone-border: #bfdbfe;
        border-color: var(--tone-border);
    }
    .admin-tone-settings .admin-section-head,
    .admin-workflow-step.tone-settings {
        background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 55%, #fff 100%);
        border-color: #bfdbfe;
    }
    .admin-tone-settings .menu-icon { background: #dbeafe; color: #1e4b7b; }
    .admin-tone-settings .menu-step, .admin-tone-settings .step-no { color: #1d4ed8; }

    .admin-tone-access {
        --tone: #7c2d12;
        --tone-soft: #fdf4f0;
        --tone-border: #e8c4b8;
        border-color: var(--tone-border);
    }
    .admin-tone-access .admin-section-head,
    .admin-workflow-step.tone-access {
        background: linear-gradient(135deg, #fdf4f0 0%, #faf0e6 55%, #fff 100%);
        border-color: #e8c4b8;
    }
    .admin-tone-access .menu-icon { background: #f5e6d8; color: #7c2d12; }
    .admin-tone-access .menu-step, .admin-tone-access .step-no { color: #9a3412; }

    .admin-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.55rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        background: #0f766e;
        color: #fff;
    }

    /* อาจารย์ / Admin สาขา — เรียบ ใช้งานง่าย */
    .role-panel {
        border-radius: 1.1rem;
        border: 1px solid #e8d5c8;
        background: #fff;
        overflow: hidden;
        margin-bottom: 1.25rem;
    }
    .role-panel-head {
        padding: 0.9rem 1.1rem;
        border-bottom: 1px solid rgba(92, 46, 31, 0.08);
    }
    .role-panel-body { padding: 1rem 1.1rem 1.15rem; }
    .role-panel .menu-card {
        background: rgba(255, 255, 255, 0.8);
        border-color: rgba(92, 46, 31, 0.1);
    }
    .entry-card {
        transition: all .2s;
        border: 1px solid #e8d5c8;
        background: #fff;
        position: relative;
        overflow: hidden;
    }
    .entry-card::before {
        content: '';
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 4px;
        background: #c4725c;
    }
    .entry-card:hover {
        border-color: #d4a090;
        box-shadow: 0 6px 18px rgba(92, 46, 31, 0.08);
        transform: translateY(-2px);
    }
    .entry-card .entry-icon {
        width: 3.25rem;
        height: 3.25rem;
        border-radius: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .entry-card.tone-create::before { background: #c2410c; }
    .entry-card.tone-create .entry-icon { background: #ffedd5; color: #9a3412; }
    .entry-card.tone-create .entry-cta { color: #c2410c; }
    .entry-card.tone-upload::before { background: #0f766e; }
    .entry-card.tone-upload .entry-icon { background: #ccfbf1; color: #0f766e; }
    .entry-card.tone-upload .entry-cta { color: #0f766e; }

    .role-tone-instructor {
        border-color: #fed7aa;
    }
    .role-tone-instructor .role-panel-head {
        background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 60%, #fff 100%);
    }
    .role-tone-instructor .role-kicker { color: #c2410c; }
    .role-tone-instructor .role-title { color: #7c2d12; }

    .role-tone-list {
        border-color: #e8c4b8;
    }
    .role-tone-list .role-panel-head {
        background: linear-gradient(135deg, #fdf4f0 0%, #faf0e6 55%, #fff 100%);
    }
    .role-tone-list .role-kicker { color: #9a3412; }
    .role-tone-list .role-title { color: #5C2E1F; }

    .role-tone-dept-docs {
        border-color: #99f6e4;
    }
    .role-tone-dept-docs .role-panel-head {
        background: linear-gradient(135deg, #ecfdf8 0%, #f0fdfa 55%, #fff 100%);
    }
    .role-tone-dept-docs .role-kicker { color: #0f766e; }
    .role-tone-dept-docs .role-title { color: #134e4a; }
    .role-tone-dept-docs .menu-icon { background: #ccfbf1; color: #0f766e; }

    .role-tone-dept-work .menu-card.tone-review .menu-icon { background: #dcfce7; color: #166534; }
    .role-tone-dept-work .menu-card.tone-review .menu-step { color: #166534; }
    .role-tone-dept-work .menu-card.tone-status .menu-icon { background: #ffedd5; color: #9a3412; }
    .role-tone-dept-work .menu-card.tone-status .menu-step { color: #c2410c; }
    .role-tone-dept-work .menu-card.tone-print .menu-icon { background: #dbeafe; color: #1e4b7b; }
    .role-tone-dept-work .menu-card.tone-print .menu-step { color: #1d4ed8; }

    .role-kicker {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        margin-bottom: 0.15rem;
    }
    .role-title {
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.3;
    }
    .role-desc {
        font-size: 0.8rem;
        color: rgba(92, 46, 31, 0.72);
        margin-top: 0.2rem;
    }
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
            @foreach ($selectableRoles as $value)
                @php
                    $label = match ($value) {
                        'dept_admin' => 'Admin สาขา',
                        'faculty_admin' => 'Admin กลาง',
                        'super_admin' => 'Super Admin',
                        default => 'อาจารย์',
                    };
                @endphp
                <button type="submit" name="role" value="{{ $value }}"
                    class="px-5 py-2.5 rounded-lg text-sm font-medium border transition
                    {{ $role === $value
                        ? 'bg-[#8B4513] text-white border-[#8B4513]'
                        : 'bg-white text-[#5C2E1F] border-[#E8C4B8] hover:border-[#C4725C]' }}">
                    {{ $label }}
                </button>
            @endforeach
        </form>
        @if ($errors->has('role'))
            <p class="text-sm text-red-600 mt-2">{{ $errors->first('role') }}</p>
        @endif
    </div>

    @if ($role === 'instructor')
        <div class="mb-5">
            <h3 class="text-lg font-bold text-[#5C2E1F] flex items-center gap-2">
                <i data-lucide="user" class="w-5 h-5"></i> เมนูอาจารย์
            </h3>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">เริ่มจากกรอกหรืออัปโหลดผลสอบ แล้วติดตามสถานะรายวิชาด้านล่าง</p>
        </div>

        <section class="role-panel role-tone-instructor">
            <div class="role-panel-head">
                <p class="role-kicker">เริ่มงาน</p>
                <h4 class="role-title">สร้างรายงานผลการสอบไล่</h4>
                <p class="role-desc">เลือกวิธีที่สะดวก — กรอกเอง หรือนำเข้าไฟล์จากสำนักทะเบียน</p>
            </div>
            <div class="role-panel-body">
                <div class="grid md:grid-cols-2 gap-4">
                    <a href="{{ route('grade-reports.create', ['term' => $term, 'year' => $year, 'return' => 'dashboard']) }}"
                       class="entry-card tone-create rounded-xl p-5 block">
                        <div class="flex items-start gap-4">
                            <div class="entry-icon p-2">
                                <img src="{{ asset('images/icons/grade-manual-entry.svg') }}" alt="" class="w-9 h-9" width="36" height="36">
                            </div>
                            <div>
                                <p class="text-base font-bold text-[#7c2d12]">กรอกข้อมูลเอง</p>
                                <p class="text-sm text-[#7A4A3A]/80 mt-1.5 leading-relaxed">
                                    สร้างแบบรายงานและกรอกจำนวนนักศึกษาทีละ Section ผ่านฟอร์มในระบบ
                                </p>
                                <span class="entry-cta inline-block mt-3 text-sm font-semibold">เริ่มกรอก →</span>
                            </div>
                        </div>
                    </a>
                    <a href="{{ route('grade-reports.upload') }}" class="entry-card tone-upload rounded-xl p-5 block">
                        <div class="flex items-start gap-4">
                            <div class="entry-icon">
                                <i data-lucide="upload-cloud" class="w-7 h-7"></i>
                            </div>
                            <div>
                                <p class="text-base font-bold text-[#134e4a]">อัปโหลดไฟล์จากสำนักทะเบียน</p>
                                <p class="text-sm text-[#7A4A3A]/80 mt-1.5 leading-relaxed">
                                    นำเข้าไฟล์รายงานผลสอบ แล้วตรวจสอบ/แก้ไขก่อนบันทึก
                                </p>
                                <span class="entry-cta inline-block mt-3 text-sm font-semibold">ไปอัปโหลด →</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        <section class="role-panel role-tone-list">
            <div class="role-panel-head">
                <p class="role-kicker">ติดตามงาน</p>
                <h4 class="role-title">รายวิชาที่กรอกแล้ว</h4>
                <p class="role-desc">เลือกภาคการศึกษาเพื่อดู แก้ไข ลบ หรือพิมพ์รายงาน</p>
            </div>
            <div class="role-panel-body">
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

            <div class="mb-4 p-3 rounded-lg bg-amber-50/80 border border-amber-200 text-sm text-[#5C2E1F]">
                <strong>สถานะการอนุมัติ:</strong>
                <span class="inline-block mx-1 px-2 py-0.5 rounded status-pending text-xs">รออนุมัติ (0)</span>
                <span class="inline-block mx-1 px-2 py-0.5 rounded status-dept text-xs">สาขาอนุมัติ (1)</span>
                <span class="inline-block mx-1 px-2 py-0.5 rounded status-checked text-xs">ตรวจแล้ว (3)</span>
                <span class="inline-block mx-1 px-2 py-0.5 rounded status-approved text-xs">คณะอนุมัติ (2)</span>
            </div>

            @if ($reports->isEmpty())
                <div class="text-center py-12 bg-[#FFFBF7] rounded-xl border border-dashed border-amber-300">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto text-amber-400 mb-3"></i>
                    <p class="text-[#5C2E1F] font-medium">ยังไม่มีรายวิชาในภาคการศึกษานี้</p>
                    <p class="text-sm text-gray-500 mt-1">กด «กรอกข้อมูลเอง» หรือ «อัปโหลดไฟล์» เพื่อเริ่มสร้างรายงาน</p>
                </div>
            @else
                <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
                    <table class="report-table w-full text-sm min-w-[800px]">
                        <thead>
                            <tr>
                                <th class="text-left">รายวิชา</th>
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
                                                3 => 'status-checked',
                                                2 => 'status-approved',
                                                -1 => 'status-rejected',
                                                default => 'status-pending',
                                            };
                                        @endphp
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $badge }}">
                                            {{ $report->statusShortLabel() }}
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
                                            $regFiles = $report->files->filter(fn ($f) => $f->resolvedType() === \App\Models\GradeReportFile::TYPE_REGISTRAR);
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
                                        <div class="space-y-2" data-report-files="{{ $report->grade_id }}" data-file-type="registrar">
                                            @if ($regFiles->isEmpty())
                                                <p class="text-xs text-gray-500 file-empty-msg">ยังไม่มีไฟล์</p>
                                            @else
                                                <div class="flex flex-col gap-1.5 file-list">
                                                    @foreach ($regFiles as $file)
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
                                                        data-file-type="registrar">
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
                                                <span class="text-xs text-amber-800 text-center block w-full mt-1">{{ $report->workflowStatusLabel() }}</span>
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
                    ** วิชาที่ส่งเกรดช้าและมี I ต้องแนบบันทึกมาพร้อมกับใบส่งเกรด (อัปโหลด PDF ในคอลัมน์ «แบบรายงานผลการสอบไล่» และ «ใบส่งผลการศึกษา (REG)»)
                </p>
            @endif
            </div>
        </section>
    @endif

    @if ($role === 'dept_admin')
        <div class="mb-5">
            <h3 class="text-lg font-bold text-[#5C2E1F] flex items-center gap-2">
                <i data-lucide="shield-check" class="w-5 h-5"></i> เมนู Admin สาขา
            </h3>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">ส่งเอกสารสาขา ตรวจสอบรายวิชา และพิมพ์รายงาน ตามลำดับงาน</p>
        </div>

        <section class="role-panel role-tone-dept-docs">
            <div class="role-panel-head">
                <p class="role-kicker">เอกสารสาขา</p>
                <h4 class="role-title">อัปโหลดเอกสารสาขา</h4>
                <p class="role-desc">
                    ส่งเอกสารรายงานตามภาคการศึกษา — เลือกช่องทางปริญญาตรีหรือบัณฑิตศึกษาแล้วอัปโหลดแยกกัน
                    แก้ไข/ลบได้จนกว่า Admin กลางจะกดรับเอกสาร
                </p>
            </div>
            <div class="role-panel-body">
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-4 mb-5">
                @if ($departments->count() > 1)
                    <div>
                        <label class="block text-sm font-medium text-[#134e4a] mb-1">สาขาวิชา</label>
                        <select name="dept_department_id" class="border border-teal-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[14rem]">
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->department_id }}" @selected($deptDepartmentId == $dept->department_id)>
                                    {{ $dept->department_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-[#134e4a] mb-1">ภาคการศึกษา</label>
                    <select name="term" class="border border-teal-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                        <option value="1" @selected($term === 1)>ภาคต้น</option>
                        <option value="2" @selected($term === 2)>ภาคปลาย</option>
                        <option value="3" @selected($term === 3)>ภาคการศึกษาพิเศษ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#134e4a] mb-1">ปีการศึกษา</label>
                    <select name="year" class="border border-teal-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[8rem]">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#134e4a] mb-1">ช่องทางการส่ง</label>
                    <select name="education_level" class="border border-teal-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[12rem]">
                        <option value="bachelor" @selected(($deptEducationLevel ?? 'bachelor') === 'bachelor')>ระดับปริญญาตรี</option>
                        <option value="graduate" @selected(($deptEducationLevel ?? 'bachelor') === 'graduate')>ระดับบัณฑิตศึกษา</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-teal-700 text-white rounded-lg text-sm font-medium hover:bg-teal-800">แสดงรายการ</button>
            </form>

            @php
                $deptCanModify = (bool) ($deptSubmission?->isOpen());
                $deptFiles = $deptSubmission?->files ?? collect();
            @endphp

            <div class="rounded-xl border border-teal-100 bg-white p-4"
                 data-dept-submission
                 data-department-id="{{ $deptDepartmentId }}"
                 data-term="{{ $term }}"
                 data-year="{{ $year }}"
                 data-education-level="{{ $deptEducationLevel ?? 'bachelor' }}"
                 data-can-modify="{{ $deptCanModify ? '1' : '0' }}">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <p class="text-sm font-semibold text-[#134e4a]">
                        รอบการส่งปัจจุบัน
                        <span class="inline-block px-2 py-0.5 rounded text-xs {{ ($deptEducationLevel ?? 'bachelor') === 'graduate' ? 'bg-violet-100 text-violet-800' : 'bg-sky-100 text-sky-800' }}">
                            {{ ($deptEducationLevel ?? 'bachelor') === 'graduate' ? 'บัณฑิตศึกษา' : 'ปริญญาตรี' }}
                        </span>:
                        @if ($deptSubmission)
                            <span class="inline-block px-2 py-0.5 rounded text-xs {{ $deptSubmission->isOpen() ? 'bg-amber-100 text-amber-900' : 'bg-green-100 text-green-800' }}">
                                {{ $deptSubmission->statusLabel() }}
                            </span>
                        @else
                            <span class="inline-block px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-700">พร้อมเริ่มรอบส่งใหม่</span>
                        @endif
                    </p>
                    @if ($deptSubmission?->isOpen())
                        <p class="text-xs text-teal-900/70">รอบที่ {{ $deptSubmission->submission_id }} — รอ Admin กลางรับเอกสาร</p>
                    @endif
                </div>

                @if ($deptFiles->isEmpty())
                    <p class="text-xs text-gray-500 dept-file-empty-msg mb-3">ยังไม่มีไฟล์ในรอบนี้</p>
                @else
                    <div class="flex flex-col gap-2 mb-3 dept-file-list">
                        @foreach ($deptFiles as $file)
                            <div class="file-chip dept-file-row items-start sm:items-center flex-wrap" data-file-id="{{ $file->file_id }}">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-teal-700"></i>
                                <a href="{{ route('dept-submissions.files.show', $file->file_id) }}{{ $file->uploaded_at ? '?v='.$file->uploaded_at->timestamp : '' }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="dept-file-name hover:underline truncate max-w-[14rem]" title="{{ $file->original_name }}">
                                    {{ $file->original_name }}
                                </a>
                                <span class="text-xs text-gray-500 dept-file-uploaded-at">{{ $file->uploaded_at?->format('d/m/Y H:i') }}</span>
                                @if ($deptCanModify)
                                    <button type="button" class="btn-edit-dept-file text-teal-700 hover:text-teal-900 text-xs font-medium"
                                        data-file-id="{{ $file->file_id }}"
                                        data-file-name="{{ $file->original_name }}">แก้ไขชื่อ</button>
                                    <label class="btn-replace-dept-file text-teal-700 hover:text-teal-900 text-xs font-medium cursor-pointer">
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

                @if ($deptDepartmentId)
                    <label class="file-upload-zone block cursor-pointer border-teal-200 bg-teal-50/40">
                        <input type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="hidden" id="dept-file-upload-input">
                        <span class="text-sm text-teal-800 font-medium flex items-center gap-1">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            อัปโหลดไฟล์ (PDF / Word) — ไม่จำกัดจำนวน
                        </span>
                    </label>
                @endif
            </div>
            </div>
        </section>

        <section class="role-panel role-tone-list role-tone-dept-work mb-6">
            <div class="role-panel-head">
                <p class="role-kicker">งานประจำ</p>
                <h4 class="role-title">ตรวจสอบและรายงาน</h4>
                <p class="role-desc">อนุมัติรายวิชา ดูสถานะการส่ง และพิมพ์ใบรายงานสาขา</p>
            </div>
            <div class="role-panel-body">
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="{{ route('dept-admin.reviews.index') }}" class="menu-card tone-review rounded-xl p-5 block">
                        <div class="flex items-start gap-3">
                            <div class="menu-icon"><i data-lucide="list-checks" class="w-5 h-5"></i></div>
                            <div>
                                <p class="menu-step">1 · อนุมัติ</p>
                                <p class="font-semibold text-green-950">ตรวจสอบรายวิชา</p>
                                <p class="text-sm text-green-900/65 mt-1">อนุมัติ/ไม่อนุมัติรายการที่อาจารย์ส่งมา พร้อมดูไฟล์แนบ</p>
                            </div>
                        </div>
                    </a>
                    <a href="{{ route('dept-admin.reg-grade-status.index') }}" class="menu-card tone-status rounded-xl p-5 block">
                        <div class="flex items-start gap-3">
                            <div class="menu-icon"><i data-lucide="clipboard-check" class="w-5 h-5"></i></div>
                            <div>
                                <p class="menu-step">2 · สถานะ</p>
                                <p class="font-semibold text-orange-950">ตรวจสอบสถานะการส่งผลการสอบ</p>
                                <p class="text-sm text-orange-900/65 mt-1">ดูสถานะตามรายวิชา REG และติกผ่านสาขาฯ ได้ทันที</p>
                            </div>
                        </div>
                    </a>
                    <a href="{{ route('dept-admin.reports.form') }}" class="menu-card tone-print rounded-xl p-5 block">
                        <div class="flex items-start gap-3">
                            <div class="menu-icon"><i data-lucide="printer" class="w-5 h-5"></i></div>
                            <div>
                                <p class="menu-step">3 · รายงาน</p>
                                <p class="font-semibold text-sky-950">พิมพ์ใบรายงานสาขา</p>
                                <p class="text-sm text-sky-900/65 mt-1">Export PDF/Word ตามสาขา ระดับการศึกษา และสถานะ</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </section>
    @endif

    @if (in_array($role, ['faculty_admin', 'super_admin'], true))
        @php
            $openCount = $openDeptSubmissions->count();
            $isSuper = ($role ?? '') === 'super_admin';
        @endphp

        <div class="mb-5">
            <h3 class="text-lg font-bold text-[#5C2E1F] flex items-center gap-2">
                <i data-lucide="building-2" class="w-5 h-5"></i>
                เมนู {{ $isSuper ? 'Super Admin' : 'Admin กลาง' }}
            </h3>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">ลำดับงานแนะนำ — เริ่มจากรับเอกสาร แล้วอนุมัติ/รายงาน ตามด้วยจัดการรายวิชาและตั้งค่า</p>
        </div>

        <div class="admin-workflow no-print">
            <a href="#admin-section-docs" class="admin-workflow-step tone-docs">
                <div class="step-no">งานหลัก</div>
                <div class="step-title">รับเอกสารจากหน่วยงาน</div>
            </a>
            <a href="#admin-section-approve" class="admin-workflow-step tone-approve">
                <div class="step-no">ลำดับ 1</div>
                <div class="step-title">อนุมัติและรายงาน</div>
            </a>
            <a href="#admin-section-courses" class="admin-workflow-step tone-courses">
                <div class="step-no">ลำดับ 2</div>
                <div class="step-title">จัดการข้อมูลรายวิชา</div>
            </a>
            <a href="#admin-section-settings" class="admin-workflow-step tone-settings">
                <div class="step-no">ลำดับ 3</div>
                <div class="step-title">ตั้งค่าระบบ</div>
            </a>
            <a href="#admin-section-access" class="admin-workflow-step tone-access">
                <div class="step-no">ลำดับ 4</div>
                <div class="step-title">สิทธิผู้ใช้งาน</div>
            </a>
        </div>

        <div class="space-y-6 mb-8">
            {{-- งานหลัก: รับเอกสาร + ประวัติ --}}
            <section id="admin-section-docs" class="admin-section admin-tone-docs">
                <div class="admin-section-head">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-teal-700 text-white text-xs font-bold">0</span>
                            <h4 class="text-base font-bold text-[#134e4a]">รับเอกสารจากหน่วยงาน</h4>
                            @if ($openCount > 0)
                                <span class="admin-badge">รอรับ {{ $openCount }} รายการ</span>
                            @endif
                        </div>
                        <p class="text-xs text-teal-900/70 ml-9">
                            เลือกภาคการศึกษา กดรับเอกสารทีละสาขา — เอกสารแยกช่องทางปริญญาตรีและบัณฑิตศึกษา
                            หลังรับแล้วสาขาจะไม่สามารถแก้ไขชื่อหรือไฟล์ในรอบนั้นได้
                        </p>
                    </div>
                    <a href="{{ route('faculty-admin.dept-submission-history.index', ['term' => $term, 'year' => $year]) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-teal-700 text-white hover:bg-teal-800">
                        <i data-lucide="history" class="w-3.5 h-3.5"></i>
                        ประวัติการรับเอกสาร
                    </a>
                </div>
                <div class="admin-section-body space-y-4">
                    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-4">
                        <div>
                            <label class="block text-sm font-medium text-[#134e4a] mb-1">ภาคการศึกษา</label>
                            <select name="term" class="border border-teal-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                                <option value="1" @selected($term === 1)>ภาคต้น</option>
                                <option value="2" @selected($term === 2)>ภาคปลาย</option>
                                <option value="3" @selected($term === 3)>ภาคการศึกษาพิเศษ</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#134e4a] mb-1">ปีการศึกษา</label>
                            <select name="year" class="border border-teal-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[8rem]">
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-teal-700 text-white rounded-lg text-sm font-medium hover:bg-teal-800">แสดงรายการ</button>
                    </form>

                    @include('faculty-admin.dept-submission-history.partials.open-list')

                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-1">
                        <a href="{{ route('faculty-admin.dept-submission-history.index', ['term' => $term, 'year' => $year]) }}" class="menu-card rounded-xl p-5 block">
                            <div class="flex items-start gap-3">
                                <div class="menu-icon"><i data-lucide="history" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="menu-step">ประวัติ</p>
                                    <p class="font-semibold text-[#134e4a]">ประวัติการรับเอกสารจากหน่วยงาน</p>
                                    <p class="text-sm text-teal-900/65 mt-1">ดูรายการรอรับ และประวัติเอกสารที่รับแล้วจากสาขาวิชา</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            {{-- 1. อนุมัติและรายงาน --}}
            <section id="admin-section-approve" class="admin-section admin-tone-approve">
                <div class="admin-section-head">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-700 text-white text-xs font-bold">1</span>
                            <h4 class="text-base font-bold text-green-900">อนุมัติและรายงาน</h4>
                        </div>
                        <p class="text-xs text-green-900/70 ml-9">อนุมัติระดับคณะ พิมพ์และดูรายงานทุกสาขา</p>
                    </div>
                </div>
                <div class="admin-section-body">
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('faculty-admin.reviews.index', ['term' => $term, 'year' => $year]) }}" class="menu-card rounded-xl p-5 block">
                            <div class="flex items-start gap-3">
                                <div class="menu-icon"><i data-lucide="badge-check" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="menu-step">1.1</p>
                                    <p class="font-semibold text-green-950">อนุมัติระดับคณะ</p>
                                    <p class="text-sm text-green-900/65 mt-1">ตรวจสอบทุกสถานะ อนุมัติ/ส่งกลับเมื่อสาขาอนุมัติแล้ว</p>
                                </div>
                            </div>
                        </a>
                        <a href="{{ route('grade-reports.reports') }}" class="menu-card rounded-xl p-5 block">
                            <div class="flex items-start gap-3">
                                <div class="menu-icon"><i data-lucide="layers" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="menu-step">1.2</p>
                                    <p class="font-semibold text-green-950">ดูรายงานทุกสาขา</p>
                                    <p class="text-sm text-green-900/65 mt-1">พิมพ์รายงานตามสาขาและสถานะรับรองผลสอบ</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            {{-- 2. จัดการข้อมูลรายวิชาที่ส่งผลสอบ --}}
            <section id="admin-section-courses" class="admin-section admin-tone-courses">
                <div class="admin-section-head">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-orange-700 text-white text-xs font-bold">2</span>
                            <h4 class="text-base font-bold text-orange-950">จัดการข้อมูลรายวิชาที่ส่งผลสอบ</h4>
                        </div>
                        <p class="text-xs text-orange-900/70 ml-9">ดึงรายวิชาจาก REG → แก้ไขตามสาขา → ตรวจสอบสถานะการส่ง</p>
                    </div>
                </div>
                <div class="admin-section-body">
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('faculty-admin.settings.reg-grade-manage.index', ['term' => $term, 'year' => $year]) }}" class="menu-card rounded-xl p-5 block">
                            <div class="flex items-start gap-3">
                                <div class="menu-icon"><i data-lucide="list-checks" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="menu-step">2.1</p>
                                    <p class="font-semibold text-orange-950">จัดการรายวิชา REG</p>
                                    <p class="text-sm text-orange-900/65 mt-1">ดึงจาก REG แล้วเพิ่ม/แก้ไข/ลบรายวิชาตามสาขา</p>
                                </div>
                            </div>
                        </a>
                        <a href="{{ route('faculty-admin.settings.reg-grade-status.index', ['term' => $term, 'year' => $year]) }}" class="menu-card rounded-xl p-5 block">
                            <div class="flex items-start gap-3">
                                <div class="menu-icon"><i data-lucide="clipboard-check" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="menu-step">2.2</p>
                                    <p class="font-semibold text-orange-950">ตรวจสอบสถานะการส่งผลการสอบ</p>
                                    <p class="text-sm text-orange-900/65 mt-1">ดูว่าแต่ละรายวิชาส่ง/ผ่านสาขา/ผ่านคณะแล้วหรือยัง</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            {{-- 3. ตั้งค่าระบบและข้อมูลพื้นฐาน --}}
            <section id="admin-section-settings" class="admin-section admin-tone-settings">
                <div class="admin-section-head">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-sky-700 text-white text-xs font-bold">3</span>
                            <h4 class="text-base font-bold text-sky-950">ตั้งค่าระบบและข้อมูลพื้นฐาน</h4>
                        </div>
                        <p class="text-xs text-sky-900/70 ml-9">
                            กำหนดภาคการศึกษา ชื่อวิชา และรหัสสาขาที่ใช้กรอง
                            @if ($isSuper)
                                รวมถึงหลักสูตร
                            @endif
                        </p>
                    </div>
                </div>
                <div class="admin-section-body">
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('faculty-admin.settings.term') }}" class="menu-card rounded-xl p-5 block">
                            <div class="flex items-start gap-3">
                                <div class="menu-icon"><i data-lucide="calendar" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="menu-step">3.1</p>
                                    <p class="font-semibold text-sky-950">กำหนดภาคการศึกษา</p>
                                    <p class="text-sm text-sky-900/65 mt-1">ตั้งค่าภาค/ปีการศึกษาเริ่มต้นของระบบ</p>
                                </div>
                            </div>
                        </a>
                        @if ($isSuper)
                            <a href="{{ route('faculty-admin.settings.programs.index') }}" class="menu-card rounded-xl p-5 block ring-1 ring-sky-200">
                                <div class="flex items-start gap-3">
                                    <div class="menu-icon"><i data-lucide="book-open" class="w-5 h-5"></i></div>
                                    <div>
                                        <p class="menu-step">3.2 · Super Admin</p>
                                        <p class="font-semibold text-sky-950">จัดการหลักสูตร</p>
                                        <p class="text-sm text-sky-900/65 mt-1">เพิ่ม/แก้ไขหลักสูตรใน tblprogram_qa</p>
                                    </div>
                                </div>
                            </a>
                        @endif
                        <a href="{{ route('faculty-admin.settings.reg-courses.index') }}" class="menu-card rounded-xl p-5 block">
                            <div class="flex items-start gap-3">
                                <div class="menu-icon"><i data-lucide="book-marked" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="menu-step">3.3</p>
                                    <p class="font-semibold text-sky-950">Download ข้อมูลรายวิชาจาก REG</p>
                                    <p class="text-sm text-sky-900/65 mt-1">ดึงชื่อวิชาเข้า pdcourse เพื่อให้อาจารย์เลือกตอนรายงานผลการสอบ</p>
                                </div>
                            </div>
                        </a>
                        <a href="{{ route('faculty-admin.grad-report2-groups.index') }}" class="menu-card rounded-xl p-5 block">
                            <div class="flex items-start gap-3">
                                <div class="menu-icon"><i data-lucide="git-merge" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="menu-step">3.4</p>
                                    <p class="font-semibold text-sky-950">จัดกลุ่มรายวิชา</p>
                                    <p class="text-sm text-sky-900/65 mt-1">กำหนดรหัสที่ตัดเกรดร่วมกัน (grad_report2)</p>
                                </div>
                            </div>
                        </a>
                        <a href="{{ route('faculty-admin.department-patterns.index') }}" class="menu-card rounded-xl p-5 block">
                            <div class="flex items-start gap-3">
                                <div class="menu-icon"><i data-lucide="filter" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="menu-step">3.5</p>
                                    <p class="font-semibold text-sky-950">จัดการรหัสสาขาที่ใช้กรอง</p>
                                    <p class="text-sm text-sky-900/65 mt-1">เพิ่ม / แก้ไข / ลบเงื่อนไขรหัสวิชาของแต่ละสาขา</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            {{-- 4. สิทธิผู้ใช้งาน --}}
            <section id="admin-section-access" class="admin-section admin-tone-access">
                <div class="admin-section-head">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-[#8B4513] text-white text-xs font-bold">4</span>
                            <h4 class="text-base font-bold text-[#5C2E1F]">สิทธิผู้ใช้งาน</h4>
                        </div>
                        <p class="text-xs text-[#7A4A3A]/75 ml-9">กำหนดสิทธิเจ้าหน้าที่ เข้าใช้งานแทนบุคลากร และดูบันทึกการใช้งาน</p>
                    </div>
                </div>
                <div class="admin-section-body">
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('faculty-admin.settings.privileges.index') }}" class="menu-card rounded-xl p-5 block">
                            <div class="flex items-start gap-3">
                                <div class="menu-icon"><i data-lucide="users" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="menu-step">4.1</p>
                                    <p class="font-semibold text-[#5C2E1F]">ผู้มีสิทธิใช้งาน</p>
                                    <p class="text-sm text-[#7A4A3A]/70 mt-1">กำหนดเจ้าหน้าที่สาขา/งานบริการ</p>
                                </div>
                            </div>
                        </a>
                        @if ($canImpersonate ?? false)
                            <a href="{{ route('super-admin.impersonate') }}" class="menu-card rounded-xl p-5 block ring-1 ring-[#E8C4B8]">
                                <div class="flex items-start gap-3">
                                    <div class="menu-icon"><i data-lucide="user-cog" class="w-5 h-5"></i></div>
                                    <div>
                                        <p class="menu-step">4.2 · Super Admin</p>
                                        <p class="font-semibold text-[#5C2E1F]">เข้าใช้งานแทนบุคลากร</p>
                                        <p class="text-sm text-[#7A4A3A]/70 mt-1">เข้าแทนอาจารย์ / Admin สาขา / Admin กลาง</p>
                                    </div>
                                </div>
                            </a>
                            <a href="{{ route('super-admin.audit-logs.index') }}" class="menu-card rounded-xl p-5 block ring-1 ring-[#E8C4B8]">
                                <div class="flex items-start gap-3">
                                    <div class="menu-icon"><i data-lucide="scroll-text" class="w-5 h-5"></i></div>
                                    <div>
                                        <p class="menu-step">4.3 · Super Admin</p>
                                        <p class="font-semibold text-[#5C2E1F]">บันทึกการใช้งานระบบ</p>
                                        <p class="text-sm text-[#7A4A3A]/70 mt-1">ดูประวัติการเข้าใช้และการเปลี่ยนแปลงสำคัญ</p>
                                    </div>
                                </div>
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        </div>
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
            const container = document.querySelector(`[data-report-files="${reportId}"][data-file-type="${fileType}"]`);
            if (!container) return;

            container.querySelector('.file-empty-msg')?.remove();

            let list = container.querySelector('.file-list');
            if (!list) {
                list = document.createElement('div');
                list.className = 'flex flex-col gap-1.5 file-list';
                const uploadZone = container.querySelector('.file-upload-zone');
                container.insertBefore(list, uploadZone);
            }

            const chip = document.createElement('div');
            chip.className = 'file-chip';
            chip.dataset.fileId = uploaded.file_id;
            chip.innerHTML = `
                <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-[#8B4513]"></i>
                <a href="${uploaded.view_url}" target="_blank" rel="noopener noreferrer"
                   class="hover:underline truncate max-w-[9rem]" title="${uploaded.original_name}">
                    ${uploaded.original_name}
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
                empty.textContent = 'ยังไม่มีไฟล์';
                const uploadZone = container.querySelector('.file-upload-zone');
                container.insertBefore(empty, uploadZone);
            }
        });
    }

    document.querySelectorAll('.btn-delete-file').forEach(bindDeleteFile);

    const deptBox = document.querySelector('[data-dept-submission]');
    const deptUploadInput = document.getElementById('dept-file-upload-input');

    function deptApiMessage(data, fallback) {
        if (data?.message) return data.message;
        const errors = data?.errors;
        if (errors && typeof errors === 'object') {
            const first = Object.values(errors).flat()[0];
            if (first) return first;
        }
        return fallback;
    }

    function findDeptFileRow(el) {
        return el?.closest('.dept-file-row') ?? null;
    }

    function ensureDeptFileList() {
        if (!deptBox) return null;
        deptBox.querySelector('.dept-file-empty-msg')?.remove();
        let list = deptBox.querySelector('.dept-file-list');
        if (!list) {
            list = document.createElement('div');
            list.className = 'flex flex-col gap-2 mb-3 dept-file-list';
            const uploadZone = deptBox.querySelector('.file-upload-zone');
            if (uploadZone) {
                deptBox.insertBefore(list, uploadZone);
            } else {
                deptBox.appendChild(list);
            }
        }
        return list;
    }

    function renderDeptFileRow(file) {
        const canModify = deptBox?.dataset.canModify === '1';
        const row = document.createElement('div');
        row.className = 'file-chip dept-file-row items-start sm:items-center flex-wrap';
        row.dataset.fileId = file.file_id;
        const actions = canModify ? `
            <button type="button" class="btn-edit-dept-file text-[#8B4513] hover:text-[#6B3410] text-xs font-medium"
                data-file-id="${file.file_id}" data-file-name="${file.original_name}">แก้ไขชื่อ</button>
            <label class="btn-replace-dept-file text-[#8B4513] hover:text-[#6B3410] text-xs font-medium cursor-pointer">
                เปลี่ยนไฟล์
                <input type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                    class="hidden dept-file-replace-input" data-file-id="${file.file_id}">
            </label>
            <button type="button" class="btn-delete-dept-file text-red-600 hover:text-red-800"
                data-file-id="${file.file_id}" title="ลบไฟล์">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        ` : '';
        row.innerHTML = `
            <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-[#8B4513]"></i>
            <a href="${file.view_url}" target="_blank" rel="noopener noreferrer"
               class="dept-file-name hover:underline truncate max-w-[14rem]" title="${file.original_name}">
                ${file.original_name}
            </a>
            <span class="text-xs text-gray-500 dept-file-uploaded-at">${file.uploaded_at || ''}</span>
            ${actions}
        `;
        if (canModify) {
            bindDeptDelete(row.querySelector('.btn-delete-dept-file'));
            bindDeptEdit(row.querySelector('.btn-edit-dept-file'));
        }
        return row;
    }

    function syncDeptEmptyState() {
        if (!deptBox) return;
        const list = deptBox.querySelector('.dept-file-list');
        if (list && list.children.length) return;
        list?.remove();
        if (!deptBox.querySelector('.dept-file-empty-msg')) {
            const empty = document.createElement('p');
            empty.className = 'text-xs text-gray-500 dept-file-empty-msg mb-3';
            empty.textContent = 'ยังไม่มีไฟล์ในรอบนี้';
            const uploadZone = deptBox.querySelector('.file-upload-zone');
            if (uploadZone) {
                deptBox.insertBefore(empty, uploadZone);
            }
        }
    }

    if (deptUploadInput && deptBox) {
        deptUploadInput.addEventListener('change', async () => {
            const file = deptUploadInput.files?.[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('attachment', file);
            formData.append('department_id', deptBox.dataset.departmentId);
            formData.append('term', deptBox.dataset.term);
            formData.append('year', deptBox.dataset.year);
            formData.append('education_level', deptBox.dataset.educationLevel || 'bachelor');

            const res = await fetch('/api/dept-submissions/files', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            deptUploadInput.value = '';

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(deptApiMessage(data, 'อัปโหลดไม่สำเร็จ'));
                return;
            }

            const uploaded = await res.json();
            deptBox.dataset.canModify = '1';
            const row = renderDeptFileRow(uploaded);
            const list = ensureDeptFileList();
            if (list) {
                list.appendChild(row);
                refreshLucideIcons(row);
            }
        });
    }

    function bindDeptDelete(btn) {
        if (!btn || btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            if (!confirm('ต้องการลบไฟล์นี้หรือไม่?')) return;
            const row = findDeptFileRow(btn);
            const fileId = btn.dataset.fileId;
            const res = await fetch(`/api/dept-submissions/files/${fileId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(deptApiMessage(data, 'ลบไฟล์ไม่สำเร็จ'));
                if (res.status === 422) window.location.reload();
                return;
            }
            row?.remove();
            syncDeptEmptyState();
        });
    }

    function bindDeptEdit(btn) {
        if (!btn || btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            const row = findDeptFileRow(btn);
            const newName = prompt('ชื่อไฟล์', btn.dataset.fileName || '');
            if (newName === null || newName.trim() === '') return;

            const res = await fetch(`/api/dept-submissions/files/${btn.dataset.fileId}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ original_name: newName.trim() }),
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(deptApiMessage(data, 'แก้ไขชื่อไม่สำเร็จ'));
                if (res.status === 422) window.location.reload();
                return;
            }

            const data = await res.json();
            const link = row?.querySelector('.dept-file-name');
            if (link) {
                link.textContent = data.original_name;
                link.title = data.original_name;
            }
            btn.dataset.fileName = data.original_name;
        });
    }

    function updateDeptFileRow(row, data) {
        if (!row || !data) return;
        const link = row.querySelector('.dept-file-name');
        if (link) {
            link.textContent = data.original_name;
            link.title = data.original_name;
            link.href = data.view_url;
        }
        const uploadedAt = row.querySelector('.dept-file-uploaded-at');
        if (uploadedAt) {
            uploadedAt.textContent = data.uploaded_at || '';
            uploadedAt.classList.add('text-green-700', 'font-medium');
            setTimeout(() => uploadedAt.classList.remove('text-green-700', 'font-medium'), 2500);
        }
        const editBtn = row.querySelector('.btn-edit-dept-file');
        if (editBtn) editBtn.dataset.fileName = data.original_name;
    }

    async function handleDeptFileReplace(input) {
        const file = input.files?.[0];
        if (!file) return;

        const row = findDeptFileRow(input);
        const formData = new FormData();
        formData.append('attachment', file);

        const res = await fetch(`/api/dept-submissions/files/${input.dataset.fileId}`, {
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
            alert(deptApiMessage(data, 'เปลี่ยนไฟล์ไม่สำเร็จ'));
            if (res.status === 422) window.location.reload();
            return;
        }

        const data = await res.json();
        updateDeptFileRow(row, data);
    }

    if (deptBox) {
        deptBox.addEventListener('change', (event) => {
            const input = event.target;
            if (!(input instanceof HTMLInputElement)) return;
            if (!input.classList.contains('dept-file-replace-input')) return;
            handleDeptFileReplace(input);
        });
    }

    document.querySelectorAll('.btn-delete-dept-file').forEach(bindDeptDelete);
    document.querySelectorAll('.btn-edit-dept-file').forEach(bindDeptEdit);

    document.querySelectorAll('.btn-receive-dept-submission').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const deptName = btn.dataset.departmentName || 'สาขานี้';
            const eduLabel = btn.dataset.educationLabel ? ` (${btn.dataset.educationLabel})` : '';
            if (!confirm(`ยืนยันรับเอกสารจากสาขา "${deptName}"${eduLabel} หรือไม่?\n\nหลังรับแล้วสาขาจะไม่สามารถแก้ไขชื่อหรือไฟล์ในรอบนี้ได้`)) return;

            btn.disabled = true;

            const res = await fetch(`/api/faculty-admin/dept-submissions/${btn.dataset.submissionId}/receive`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(data.message || data.errors?.submission?.[0] || 'รับเอกสารไม่สำเร็จ');
                btn.disabled = false;
                return;
            }

            document.querySelector(`[data-faculty-submission="${btn.dataset.submissionId}"]`)?.remove();
        });
    });

    refreshLucideIcons();
})();
</script>
@endpush
