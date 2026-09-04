@extends('layouts.scigrad')

@section('title', 'หน้าหลัก — SciGrade')

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
    .entry-card.tone-thesis {
        background: linear-gradient(135deg, #fefce8 0%, #fef9c3 45%, #fff 100%);
        border-color: #facc15;
    }
    .entry-card.tone-thesis::before { background: #ca8a04; }
    .entry-card.tone-thesis .entry-icon { background: #fef08a; color: #a16207; }
    .entry-card.tone-thesis .entry-cta { color: #a16207; }
    .entry-card.tone-thesis:hover {
        border-color: #eab308;
        box-shadow: 0 6px 18px rgba(161, 98, 7, 0.12);
    }
    .entry-card.tone-thesis.entry-card-compact {
        padding: 0.9rem 1rem !important;
    }
    .entry-card.tone-thesis.entry-card-compact .entry-icon {
        width: 2.4rem;
        height: 2.4rem;
        border-radius: 0.7rem;
    }
    .entry-card.tone-thesis.entry-card-compact .entry-icon img {
        width: 1.6rem;
        height: 1.6rem;
    }
    .entry-card.tone-track::before { background: #0369a1; }
    .entry-card.tone-track .entry-icon { background: #e0f2fe; color: #0369a1; }
    .entry-card.tone-track .entry-cta { color: #0369a1; }

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

    .dept-submit-lane-bachelor {
        border-color: #7dd3fc;
        background: linear-gradient(180deg, #f0f9ff 0%, #ffffff 42%);
    }
    .dept-submit-lane-graduate {
        border-color: #c4b5fd;
        background: linear-gradient(180deg, #f5f3ff 0%, #ffffff 42%);
    }
    .dept-submit-kicker {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        margin: 0;
    }
    .dept-submit-lane-bachelor .dept-submit-kicker { color: #0369a1; }
    .dept-submit-lane-graduate .dept-submit-kicker { color: #6d28d9; }
    .dept-submit-title {
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1.2;
        margin: 0.1rem 0 0;
    }
    .dept-submit-lane-bachelor .dept-submit-title { color: #0c4a6e; }
    .dept-submit-lane-graduate .dept-submit-title { color: #4c1d95; }
    .dept-submit-sub {
        font-size: 0.8rem;
        margin: 0.15rem 0 0;
        color: #64748b;
    }
    .dept-submit-lane-bachelor .dept-submit-drop {
        border-color: #7dd3fc;
        background: #e0f2fe;
    }
    .dept-submit-lane-graduate .dept-submit-drop {
        border-color: #c4b5fd;
        background: #ede9fe;
    }

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
            <p class="text-sm text-[#7A4A3A]/80 mt-1">เลือกเมนูด้านล่างเพื่อสร้างรายงาน หรือติดตามสถานะรายวิชาที่ส่งแล้ว</p>
        </div>

        <section class="role-panel role-tone-instructor">
            <div class="role-panel-head">
                <p class="role-kicker">เมนูการทำงาน</p>
                <h4 class="role-title">งานของอาจารย์</h4>
                <p class="role-desc">สร้างรายงานผลการสอบไล่ ส่งผลวิทยานิพนธ์ หรือติดตามสถานะที่ส่งไปแล้ว</p>
            </div>
            <div class="role-panel-body">
                <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 items-stretch">
                    <a href="{{ route('grade-reports.create', ['term' => $term, 'year' => $year, 'return' => 'dashboard']) }}"
                       class="entry-card tone-create rounded-xl p-5 block h-full">
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
                    <a href="{{ route('grade-reports.upload') }}" class="entry-card tone-upload rounded-xl p-5 block h-full">
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
                    @php $thesisGradeUrl = trim((string) config('scigrade.thesis_grade_url', '')); @endphp
                    <a href="{{ $thesisGradeUrl !== '' ? $thesisGradeUrl : '#' }}"
                       @if ($thesisGradeUrl !== '' && str_starts_with($thesisGradeUrl, 'http'))
                           target="_blank" rel="noopener noreferrer"
                       @endif
                       class="entry-card tone-thesis entry-card-compact rounded-xl block h-full">
                        <div class="flex items-start gap-3">
                            <div class="entry-icon p-1.5">
                                <img src="{{ asset('images/icons/thesis-independent-study.svg') }}" alt="" class="w-6 h-6" width="24" height="24">
                            </div>
                            <div>
                                <p class="text-sm font-bold text-[#854d0e] leading-snug">ส่งผลการเรียนวิทยานิพนธ์/การศึกษาอิสระ</p>
                                <p class="text-xs text-[#7A4A3A]/80 mt-1 leading-relaxed">
                                    รายวิชาวิทยานิพนธ์หรือการศึกษาอิสระ (แยกจากรายงานสอบไล่)
                                </p>
                                <span class="entry-cta inline-block mt-2 text-xs font-semibold">ไปส่งผล →</span>
                            </div>
                        </div>
                    </a>
                    <a href="{{ route('grade-reports.my', ['term' => $term, 'year' => $year]) }}"
                       class="entry-card tone-track rounded-xl p-5 block h-full">
                        <div class="flex items-start gap-4">
                            <div class="entry-icon">
                                <i data-lucide="clipboard-list" class="w-7 h-7"></i>
                            </div>
                            <div>
                                <p class="text-base font-bold text-[#0c4a6e]">ติดตามผลการสอบ</p>
                                <p class="text-sm text-[#7A4A3A]/80 mt-1.5 leading-relaxed">
                                    ดูรายวิชาที่กรอกแล้ว สถานะการอนุมัติ และวันที่กรอก
                                </p>
                                <span class="entry-cta inline-block mt-3 text-sm font-semibold">ติดตามรายงานผลการสอบ →</span>
                            </div>
                        </div>
                    </a>
                </div>
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
                    ส่งเอกสารรายงานตามภาคการศึกษา — แยกช่องทางปริญญาตรี (ป.ตรี) และบัณฑิตศึกษา (ป.บัณฑิต)
                    อัปโหลดได้ทันทีในช่องที่ต้องการ แก้ไข/ลบได้จนกว่า Admin กลางจะกดรับเอกสาร
                </p>
            </div>
            <div class="role-panel-body">
            @php
                $deptTermLabel = match ((int) $term) {
                    1 => 'ภาคต้น',
                    2 => 'ภาคปลาย',
                    default => 'ภาคการศึกษาพิเศษ',
                };
                $deptName = optional($departments->firstWhere('department_id', $deptDepartmentId))->department_name;
            @endphp
            <form method="GET" action="{{ route('dashboard') }}" id="dept-docs-filter" class="flex flex-wrap items-end gap-4 mb-4">
                @if ($departments->count() > 1)
                    <div>
                        <label class="block text-sm font-medium text-[#134e4a] mb-1">สาขาวิชา</label>
                        <select name="dept_department_id" class="border border-teal-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[14rem]" onchange="this.form.submit()">
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
                    <select name="term" class="border border-teal-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]" onchange="this.form.submit()">
                        <option value="1" @selected($term === 1)>ภาคต้น</option>
                        <option value="2" @selected($term === 2)>ภาคปลาย</option>
                        <option value="3" @selected($term === 3)>ภาคการศึกษาพิเศษ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#134e4a] mb-1">ปีการศึกษา</label>
                    <select name="year" class="border border-teal-200 rounded-lg px-3 py-2 text-sm bg-white min-w-[8rem]" onchange="this.form.submit()">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div class="rounded-lg border border-teal-100 bg-teal-50/70 px-4 py-3 mb-4">
                <p class="text-sm font-semibold text-[#134e4a]">
                    กำลังส่งเอกสาร{{ $deptName ? ' สาขา'.$deptName : '' }} — {{ $deptTermLabel }} ปีการศึกษา {{ $year }}
                </p>
                <p class="text-xs text-teal-900/75 mt-0.5">เลือกช่องทางด้านล่างแล้วอัปโหลดได้เลย ไม่ต้องกดแสดงรายการก่อนส่ง</p>
            </div>

            <div class="grid md:grid-cols-2 gap-4" data-dept-submission-board>
                @include('dept-admin.partials.dept-submission-lane', [
                    'educationLevel' => \App\Models\DeptSubmission::EDUCATION_BACHELOR,
                    'submission' => $deptSubmissions[\App\Models\DeptSubmission::EDUCATION_BACHELOR] ?? null,
                    'departmentId' => $deptDepartmentId,
                    'term' => $term,
                    'year' => $year,
                ])
                @include('dept-admin.partials.dept-submission-lane', [
                    'educationLevel' => \App\Models\DeptSubmission::EDUCATION_GRADUATE,
                    'submission' => $deptSubmissions[\App\Models\DeptSubmission::EDUCATION_GRADUATE] ?? null,
                    'departmentId' => $deptDepartmentId,
                    'term' => $term,
                    'year' => $year,
                ])
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

    const deptBoard = document.querySelector('[data-dept-submission-board]');

    function deptApiMessage(data, fallback) {
        if (data?.message) return data.message;
        const errors = data?.errors;
        if (errors && typeof errors === 'object') {
            const first = Object.values(errors).flat()[0];
            if (first) return first;
        }
        return fallback;
    }

    function findDeptBox(el) {
        return el?.closest('[data-dept-submission]') ?? null;
    }

    function findDeptFileRow(el) {
        return el?.closest('.dept-file-row') ?? null;
    }

    function deptToneClass(deptBox, graduateClass, bachelorClass) {
        return deptBox?.dataset.educationLevel === 'graduate' ? graduateClass : bachelorClass;
    }

    function ensureDeptFileList(deptBox) {
        if (!deptBox) return null;
        deptBox.querySelector('.dept-file-empty-msg')?.remove();
        let list = deptBox.querySelector('.dept-file-list');
        if (!list) {
            list = document.createElement('div');
            list.className = 'flex flex-col gap-2 dept-file-list';
            const uploadZone = deptBox.querySelector('.file-upload-zone');
            if (uploadZone) {
                deptBox.insertBefore(list, uploadZone);
            } else {
                deptBox.appendChild(list);
            }
        }
        return list;
    }

    function renderDeptFileRow(file, deptBox) {
        const canModify = deptBox?.dataset.canModify === '1';
        const actionClass = deptToneClass(deptBox, 'text-violet-800 hover:text-violet-950', 'text-sky-800 hover:text-sky-950');
        const iconClass = deptToneClass(deptBox, 'text-violet-700', 'text-sky-700');
        const row = document.createElement('div');
        row.className = 'file-chip dept-file-row items-start sm:items-center flex-wrap';
        row.dataset.fileId = file.file_id;
        const actions = canModify ? `
            <button type="button" class="btn-edit-dept-file text-xs font-medium ${actionClass}"
                data-file-id="${file.file_id}" data-file-name="${file.original_name}">แก้ไขชื่อ</button>
            <label class="btn-replace-dept-file text-xs font-medium cursor-pointer ${actionClass}">
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
            <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 ${iconClass}"></i>
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

    function syncDeptEmptyState(deptBox) {
        if (!deptBox) return;
        const list = deptBox.querySelector('.dept-file-list');
        if (list && list.children.length) return;
        list?.remove();
        if (!deptBox.querySelector('.dept-file-empty-msg')) {
            const empty = document.createElement('p');
            empty.className = 'text-xs text-gray-500 dept-file-empty-msg';
            empty.textContent = 'ยังไม่มีไฟล์ในช่องทางนี้';
            const uploadZone = deptBox.querySelector('.file-upload-zone');
            if (uploadZone) {
                deptBox.insertBefore(empty, uploadZone);
            }
        }
    }

    async function handleDeptFileUpload(input) {
        const deptBox = findDeptBox(input);
        const file = input.files?.[0];
        if (!file || !deptBox) return;

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

        input.value = '';

        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            alert(deptApiMessage(data, 'อัปโหลดไม่สำเร็จ'));
            return;
        }

        const uploaded = await res.json();
        deptBox.dataset.canModify = '1';
        const row = renderDeptFileRow(uploaded, deptBox);
        const list = ensureDeptFileList(deptBox);
        if (list) {
            list.appendChild(row);
            refreshLucideIcons(row);
        }
    }

    function bindDeptDelete(btn) {
        if (!btn || btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            if (!confirm('ต้องการลบไฟล์นี้หรือไม่?')) return;
            const row = findDeptFileRow(btn);
            const deptBox = findDeptBox(btn);
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
            syncDeptEmptyState(deptBox);
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

    document.querySelectorAll('.dept-file-upload-input').forEach((input) => {
        input.addEventListener('change', () => handleDeptFileUpload(input));
    });

    deptBoard?.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement)) return;
        if (!input.classList.contains('dept-file-replace-input')) return;
        handleDeptFileReplace(input);
    });

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
