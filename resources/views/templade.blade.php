@extends('layouts.scigrad')

@section('title', isset($reportId) ? 'แก้ไขรายงานผลสอบ — SciGrade' : 'กรอกผลสอบ — SciGrade')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium flex items-center gap-1.5">
    <i data-lucide="file-text" class="w-4 h-4"></i>
    {{ isset($reportId) ? 'แก้ไขรายงาน' : 'กรอกผลสอบ' }}
</span>
@endsection

@push('styles')
<style>
    input:focus, select:focus, textarea:focus { outline: none; border-color: #c4856c; box-shadow: 0 0 0 2px rgba(196,133,108,0.2); }
    #subject-suggestions {
        min-width: min(36rem, calc(100vw - 2.5rem));
        width: max(100%, 28rem);
        max-height: 18rem;
        overflow-y: auto;
        box-shadow: 0 10px 28px rgba(92, 46, 31, 0.14);
    }
    #subject-suggestions .subject-suggestion-btn {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        width: 100%;
        text-align: left;
        padding: 0.65rem 0.9rem;
        border-bottom: 1px solid #f5e6d8;
        transition: background-color 0.15s ease;
    }
    #subject-suggestions .subject-suggestion-btn:last-child { border-bottom: 0; }
    #subject-suggestions .subject-suggestion-btn:hover,
    #subject-suggestions .subject-suggestion-btn:focus {
        background: #fdf6f0;
        outline: none;
    }
    #subject-suggestions .subject-suggestion-code {
        flex: 0 0 auto;
        min-width: 5.5rem;
        font-weight: 700;
        color: #5C2E1F;
        font-size: 0.875rem;
        line-height: 1.35;
    }
    #subject-suggestions .subject-suggestion-name {
        flex: 1 1 auto;
        color: #4b5563;
        font-size: 0.875rem;
        line-height: 1.45;
        word-break: break-word;
    }
    #eva-hint-popover { display: none; z-index: 9999; }
    #eva-hint-popover.is-visible { display: block; }
    #eva-hint-popover .eva-hint-popover-card {
        position: relative;
        max-width: min(100vw - 2rem, 420px);
        border-radius: 0.65rem;
        border: 1px solid #e8cdb5;
        box-shadow: 0 8px 24px rgba(92,46,31,.18);
        background: #fff;
        overflow: hidden;
    }
    #eva-hint-popover .eva-hint-popover-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.45rem 0.55rem 0.45rem 0.75rem;
        background: #fff8f1;
        border-bottom: 1px solid #f3e0d0;
    }
    #eva-hint-popover .eva-hint-popover-title {
        font-size: 0.75rem;
        font-weight: 600;
        color: #5C2E1F;
    }
    #eva-hint-popover .eva-hint-popover-close {
        flex-shrink: 0;
        min-width: 2.25rem;
        min-height: 2.25rem;
        padding: 0 0.65rem;
        border-radius: 0.45rem;
        border: 1px solid #e8cdb5;
        background: #fff;
        color: #5C2E1F;
        font-size: 0.8rem;
        font-weight: 700;
        line-height: 1;
        cursor: pointer;
    }
    #eva-hint-popover .eva-hint-popover-close:hover,
    #eva-hint-popover .eva-hint-popover-close:focus {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #b91c1c;
        outline: none;
    }
    #eva-hint-popover img { display: block; width: 100%; height: auto; background: #fff; }
    #grade-boundary-hint { color: #92400e; font-weight: 500; }
    .fac-dropdown-panel { max-height: 16rem; overflow-y: auto; }
    .fac-tag { background: #FAF0E6; border: 1px solid #E8C4B8; color: #5C2E1F; }
    .joint-subject-tag { background: #FAF0E6; border: 1px solid #E8C4B8; color: #5C2E1F; }
    #joint-subject-suggestions { max-height: 14rem; overflow-y: auto; }
    #joint-subject-suggestions button:hover { background: #fdf6f0; }
    #student-grade-table th { background: linear-gradient(180deg, #fdf6f0 0%, #f5e6d8 100%); }
    #student-grade-table .grade-range-col { font-size: 0.65rem; line-height: 1.2; color: #8B4513; font-weight: 500; }
    #student-grade-table input[type=number] { min-width: 3rem; }

    /* หน้าจอประมวลผลขณะบันทึก — ตัวอักษรใหญ่ อ่านง่าย */
    #save-overlay { font-family: 'Noto Sans Thai', sans-serif; }
    #save-overlay .save-overlay-card {
        animation: saveOverlayIn 0.25s ease-out;
    }
    @keyframes saveOverlayIn {
        from { opacity: 0; transform: scale(0.96) translateY(8px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    @keyframes saveSpinner {
        to { transform: rotate(360deg); }
    }
    .save-spinner {
        width: 4.5rem;
        height: 4.5rem;
        border: 5px solid #f5e6d8;
        border-top-color: #8B4513;
        border-radius: 50%;
        animation: saveSpinner 0.85s linear infinite;
        margin: 0 auto;
    }
    @keyframes saveSuccessPop {
        0% { transform: scale(0.5); opacity: 0; }
        60% { transform: scale(1.08); }
        100% { transform: scale(1); opacity: 1; }
    }
    .save-success-icon {
        animation: saveSuccessPop 0.45s ease-out;
    }
    .wizard-step { display: none; }
    .wizard-step.is-active { display: block; }
    .wizard-trail {
        display: flex; flex-wrap: wrap; align-items: stretch;
        list-style: none; padding: 0; margin: 0 0 1.25rem;
        gap: 0.35rem 0;
    }
    .wizard-step-item {
        flex: 1 1 calc(25% - 0.15rem);
        display: flex; flex-direction: column; align-items: center;
        min-width: 4.6rem; position: relative;
        --arrow: #d1d5db;
        --ink: #6b7280;
        --label: #9ca3af;
    }
    .wizard-chevron {
        width: 100%;
        min-height: 2.35rem;
        display: flex; align-items: center; justify-content: center;
        background: var(--arrow); color: var(--ink);
        font-size: 0.8rem; font-weight: 800;
        clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 50%, calc(100% - 12px) 100%, 0 100%, 12px 50%);
        transition: background .25s ease, color .25s ease, filter .25s ease, transform .25s ease;
    }
    .wizard-step-item:nth-child(4n+1) .wizard-chevron {
        clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 50%, calc(100% - 12px) 100%, 0 100%);
        padding-left: 0.15rem;
    }
    .wizard-arrow-num { position: relative; z-index: 1; }
    .wizard-label {
        font-size: .62rem; line-height: 1.2; color: var(--label);
        max-width: 6rem; text-align: center; margin-top: 0.28rem;
    }
    .wizard-step-item.is-current[data-tone="1"],
    .wizard-step-item.is-done[data-tone="1"] { --arrow: #dc2626; --ink: #fff; --label: #b91c1c; }
    .wizard-step-item.is-current[data-tone="2"],
    .wizard-step-item.is-done[data-tone="2"] { --arrow: #ea580c; --ink: #fff; --label: #c2410c; }
    .wizard-step-item.is-current[data-tone="3"],
    .wizard-step-item.is-done[data-tone="3"] { --arrow: #f97316; --ink: #fff; --label: #c2410c; }
    .wizard-step-item.is-current[data-tone="4"],
    .wizard-step-item.is-done[data-tone="4"] { --arrow: #f59e0b; --ink: #fff; --label: #b45309; }
    .wizard-step-item.is-current[data-tone="5"],
    .wizard-step-item.is-done[data-tone="5"] { --arrow: #ca8a04; --ink: #fff; --label: #854d0e; }
    .wizard-step-item.is-current[data-tone="6"],
    .wizard-step-item.is-done[data-tone="6"] { --arrow: #65a30d; --ink: #fff; --label: #3f6212; }
    .wizard-step-item.is-current[data-tone="7"],
    .wizard-step-item.is-done[data-tone="7"] { --arrow: #22c55e; --ink: #fff; --label: #15803d; }
    .wizard-step-item.is-current[data-tone="8"],
    .wizard-step-item.is-done[data-tone="8"] { --arrow: #16a34a; --ink: #fff; --label: #166534; }
    .wizard-step-item.is-current .wizard-chevron { transform: scale(1.04); filter: drop-shadow(0 2px 5px rgba(0,0,0,.18)); }
    .wizard-step-item.is-current .wizard-label { font-weight: 700; }
    .wizard-trail.is-complete .wizard-step-item { --arrow: #16a34a; --ink: #fff; --label: #166534; }
    .wizard-trail.is-complete .wizard-label { font-weight: 600; }
    .field-locked, .field-locked:disabled { background: #f3f4f6 !important; color: #4b5563; cursor: not-allowed; }
    .lock-note { border: 1px solid #bbf7d0; background: #f0fdf4; }
    .group-note { border: 1px solid #93c5fd; background: #eff6ff; }
    .prior-sec-chip { background: #fff; border: 1px solid #fdba74; color: #9a3412; }
    @@media (min-width: 768px) {
        .wizard-trail { flex-wrap: nowrap; }
        .wizard-step-item { flex: 1 1 0; min-width: 0; margin-left: -10px; }
        .wizard-step-item:nth-child(1) { z-index: 8; }
        .wizard-step-item:nth-child(2) { z-index: 7; }
        .wizard-step-item:nth-child(3) { z-index: 6; }
        .wizard-step-item:nth-child(4) { z-index: 5; }
        .wizard-step-item:nth-child(5) { z-index: 4; }
        .wizard-step-item:nth-child(6) { z-index: 3; }
        .wizard-step-item:nth-child(7) { z-index: 2; }
        .wizard-step-item:nth-child(8) { z-index: 1; }
        .wizard-step-item:first-child { margin-left: 0; }
        .wizard-step-item:nth-child(4n+1) .wizard-chevron,
        .wizard-chevron {
            clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%, 14px 50%);
        }
        .wizard-step-item:first-child .wizard-chevron {
            clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%);
        }
        .wizard-step-item:last-child .wizard-chevron {
            clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%, 14px 50%);
        }
        .wizard-label { font-size: .7rem; max-width: 7.2rem; }
    }
    .scheme-box { border: 1px solid #f0e0d0; background: #fff; }
    .scheme-box input:checked + span { color: #5C2E1F; font-weight: 600; }
</style>
@endpush

@section('content')
        @php
            use App\Support\AcademicTerm;
            $defaultYear = $prefillYear ?? AcademicTerm::defaultYear();
            $defaultTerm = $prefillTerm ?? AcademicTerm::defaultTerm();
        @endphp

        <section>
            <div class="form-section rounded-lg p-5 mb-4">
                <h2 class="text-lg font-bold mb-4 text-[#5C2E1F] flex items-center gap-2">
                    <i data-lucide="file-text" class="w-5 h-5 text-[#8B4513]"></i>
                    {{ isset($reportId) ? 'แก้ไขแบบรายงานผลการสอบไล่' : 'สร้างแบบรายงานผลการสอบไล่' }}
                </h2>
                <ol id="wizard-stepper" class="wizard-trail" aria-label="ขั้นตอนการกรอกรายงาน">
                    @foreach ([
                        1 => 'ข้อมูลรายวิชา',
                        2 => 'หมายเหตุ',
                        3 => 'ช่วงคะแนน',
                        4 => 'จำนวนนักศึกษา',
                        5 => 'ประเมินรายวิชา',
                        6 => 'แนบ มข.11',
                        7 => 'พิมพ์ใบขวาง',
                        8 => 'อัปโหลดใบขวาง',
                    ] as $n => $label)
                        <li class="wizard-step-item {{ $n === 1 ? 'is-current' : '' }}" data-wizard-dot="{{ $n }}" data-tone="{{ $n }}">
                            <span class="wizard-chevron" aria-hidden="true"><span class="wizard-arrow-num">{{ $n }}</span></span>
                            <span class="wizard-label">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>
                <div id="course-context-banners" class="space-y-2 mb-5">
                    <div id="course-group-banner" class="group-note hidden rounded-lg px-3 py-2.5">
                        <p class="text-sm font-semibold text-sky-900">รายวิชานี้จัดกลุ่มตัดเกรดร่วมไว้แล้ว — ไม่ต้องกรอกรหัสซ้ำ</p>
                        <p class="text-xs text-sky-800/90 mt-0.5">ระบบแสดงรหัสวิชาที่อยู่ในกลุ่มเดียวกันให้แล้ว หากต้องการเปลี่ยนกลุ่ม กรุณาติดต่อผู้ดูแล</p>
                        <div id="course-group-list" class="flex flex-wrap gap-1.5 mt-2"></div>
                    </div>
                    <div id="course-prior-banner" class="lock-note hidden rounded-lg px-3 py-2.5">
                        <p id="course-prior-title" class="text-sm font-semibold text-green-900">มีข้อมูลเกณฑ์รายวิชานี้ในระบบแล้ว</p>
                        <p id="course-prior-body" class="text-xs text-green-800/90 mt-0.5 leading-relaxed"></p>
                    </div>
                    <div id="course-thesis-banner" class="hidden rounded-lg px-3 py-2.5 border border-amber-300 bg-amber-50">
                        <p class="text-sm font-semibold text-amber-950">รายวิชานี้ไม่ใช้เมนูรายงานสอบไล่</p>
                        <p id="course-thesis-body" class="text-xs text-amber-900/80 mt-0.5 leading-relaxed">
                            ใช้เมนู
                            <a href="{{ route('thesis-grades.index') }}" class="underline font-semibold">ส่งผลการเรียนวิทยานิพนธ์/การศึกษาอิสระ</a>
                            เท่านั้น
                        </p>
                    </div>
                </div>
                <form id="grade-form" class="space-y-5">
                    <div class="wizard-step is-active space-y-5" data-wizard-step="1">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="relative md:col-span-3">
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">รหัสวิชา *</label>
                            <input id="subject-code" type="text" maxlength="20" autocomplete="off"
                                class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" placeholder="รหัสวิชา">
                            <div id="subject-suggestions"
                                class="absolute left-0 top-full mt-1.5 z-40 hidden bg-white border border-amber-300 rounded-lg"></div>
                        </div>
                        <div class="md:col-span-9">
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">ชื่อวิชา *</label>
                            <input id="subject-name" type="text"
                                class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" placeholder="PHYSICAL SCIENCE">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2 text-[#5C2E1F]">ภาคการศึกษา *</label>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="term" value="1" @checked($defaultTerm === 1) class="accent-amber-700"> ภาคต้น</label>
                                <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="term" value="2" @checked($defaultTerm === 2) class="accent-amber-700"> ภาคปลาย</label>
                                <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="term" value="3" @checked($defaultTerm === 3) class="accent-amber-700"> ภาคการศึกษาพิเศษ</label>
                            </div>
                        </div>
                        <div>
                            <label for="year-input" class="block text-sm font-medium mb-1 text-[#5C2E1F]">ปีการศึกษา *</label>
                            <select id="year-input" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                                @for ($y = 2565; $y <= 2575; $y++)
                                    <option value="{{ $y }}" @selected($y === $defaultYear)>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="teacher-input" class="block text-sm font-medium mb-1 text-[#5C2E1F]">อาจารย์ผู้สอน</label>
                            <input id="teacher-input" type="text"
                                data-default-teacher="{{ $staffTeacherName }}"
                                class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white"
                                value="{{ $staffTeacherName }}" placeholder="ชื่ออาจารย์ผู้สอน">
                            <p id="teacher-help-text" class="text-xs text-[#7A4A3A]/80 mt-1">ดึงชื่อจากข้อมูลบุคลากรเป็นค่าเริ่มต้น — สามารถแก้ไขหรือเพิ่มชื่ออาจารย์ผู้สอนได้</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">วันที่บันทึก</label>
                            <input id="report-date" type="date" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="mean-score" class="block text-sm font-medium mb-1 text-[#5C2E1F]">ค่าเฉลี่ยคะแนน</label>
                            <input id="mean-score" type="text" inputmode="decimal"
                                class="score-decimal-input w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" placeholder="0.00">
                            <p class="text-xs text-[#7A4A3A]/80 mt-1">กรอกทศนิยม 2 ตำแหน่ง — หากมีนักเรียนน้อยกว่า 5 คน ไม่ต้องกรอกช่องนี้</p>
                        </div>
                        <div>
                            <label for="sd-score" class="block text-sm font-medium mb-1 text-[#5C2E1F]">ค่าส่วนเบี่ยงเบนมาตรฐานคะแนน</label>
                            <input id="sd-score" type="text" inputmode="decimal"
                                class="score-decimal-input w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" placeholder="0.00">
                            <p class="text-xs text-[#7A4A3A]/80 mt-1">กรอกทศนิยม 2 ตำแหน่ง — หากมีนักเรียนน้อยกว่า 5 คน ไม่ต้องกรอกช่องนี้</p>
                        </div>
                    </div>
                    </div>

                    <div class="wizard-step space-y-5" data-wizard-step="2">
                    <div class="bg-white border border-amber-200 rounded-lg p-4 space-y-4">
                        <div>
                            <p class="text-sm font-semibold text-[#5C2E1F]">หมายเหตุ</p>
                            <p id="remark-help-text" class="text-xs text-[#7A4A3A]/80 mt-0.5">เลือกได้หลายข้อ — ข้ามได้หากไม่มีหมายเหตุ</p>
                        </div>

                        <label class="flex items-start gap-2 text-sm">
                            <input type="checkbox" id="remark-joint" name="remark_joint" value="1" class="accent-amber-700 mt-1 shrink-0">
                            <div class="flex-1 min-w-0">
                                <span class="text-[#5C2E1F] font-medium">ตัดเกรดร่วมกับ</span>
                                <div id="joint-grade-panel" class="mt-2 relative">
                                    <input id="joint-subject-search" type="text" maxlength="20" autocomplete="off"
                                        class="w-full border border-amber-200 rounded px-2 py-1.5 text-sm bg-white"
                                        placeholder="พิมพ์รหัสวิชา — เลือกจากรายการ หรือกด Enter เพื่อเพิ่มเอง">
                                    <div id="joint-subject-suggestions"
                                        class="absolute left-0 right-0 top-full mt-1 z-30 hidden bg-white border border-amber-300 rounded-lg shadow-lg text-sm min-w-[16rem]"></div>
                                    <div id="joint-subject-tags" class="flex flex-wrap gap-1.5 mt-2"></div>
                                    <div id="joint-peer-box" class="hidden mt-3 rounded-lg border border-sky-200 bg-sky-50/70 px-3 py-2">
                                        <p class="text-xs font-semibold text-[#0c4a6e] mb-1">รายวิชาในกลุ่มเดียวกัน (จากฐานข้อมูลตัดเกรดร่วม)</p>
                                        <div id="joint-peer-list" class="flex flex-wrap gap-1.5"></div>
                                        <p id="joint-peer-empty" class="hidden text-xs text-[#0c4a6e]/70">ยังไม่พบรายวิชาอื่นในกลุ่มเดียวกับรหัสที่กำลังกรอก</p>
                                    </div>
                                    <p id="joint-search-hint" class="text-xs text-[#7A4A3A]/70 mt-1">เลือกจากรายการเพื่อแสดงชื่อวิชา — หากไม่มีในฐานข้อมูล กด Enter หรือเลือก «กรอกเอง» เพื่อเพิ่มรหัสวิชา</p>
                                </div>
                            </div>
                        </label>

                        <div class="space-y-2">
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" id="remark-i" name="remark_i" value="1" class="accent-amber-700 mt-1 shrink-0">
                                <span class="text-[#5C2E1F] font-medium pt-0.5">ได้ I เนื่องจาก</span>
                            </label>
                            <div id="prior-i-box" class="hidden ml-6 rounded-lg border border-amber-200 bg-amber-50/60 px-3 py-2">
                                <p class="text-[0.7rem] font-semibold text-[#854d0e] mb-1">ข้อความจากผู้กรอก Sec ก่อนหน้า (รหัสวิชาเดียวกัน)</p>
                                <ul id="prior-i-list" class="list-disc pl-4 space-y-0.5 text-xs text-[#5C2E1F]"></ul>
                            </div>
                            <div class="ml-6">
                                <input id="std-i2" type="text" maxlength="400"
                                    class="w-full border border-amber-200 rounded px-2 py-1.5 text-sm"
                                    placeholder="กรอกเหตุผลเพิ่มเติม (บังคับเมื่อติ๊กข้อนี้)" disabled>
                                <p class="text-[0.65rem] text-[#7A4A3A]/75 mt-1">ข้อความใหม่จะถูกเพิ่มต่อท้าย โดยไม่ทับข้อมูลเดิม</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" id="remark-other" name="remark_other" value="1" class="accent-amber-700 mt-1 shrink-0">
                                <span class="text-[#5C2E1F] font-medium pt-0.5">อื่นๆ</span>
                            </label>
                            <div id="prior-other-box" class="hidden ml-6 rounded-lg border border-stone-200 bg-stone-50 px-3 py-2">
                                <p class="text-[0.7rem] font-semibold text-[#57534e] mb-1">ข้อความจากผู้กรอก Sec ก่อนหน้า (รหัสวิชาเดียวกัน)</p>
                                <ul id="prior-other-list" class="list-disc pl-4 space-y-0.5 text-xs text-[#5C2E1F]"></ul>
                            </div>
                            <div class="ml-6">
                                <input id="std-i3" type="text" maxlength="400"
                                    class="w-full border border-amber-200 rounded px-2 py-1.5 text-sm"
                                    placeholder="กรอกข้อความเพิ่มเติม (บังคับเมื่อติ๊กข้อนี้)" disabled>
                                <p class="text-[0.65rem] text-[#7A4A3A]/75 mt-1">ข้อความใหม่จะถูกเพิ่มต่อท้าย โดยไม่ทับข้อมูลเดิม</p>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="wizard-step space-y-5" data-wizard-step="5">
                    <div class="bg-white border border-amber-200 rounded-lg p-4">
                        <p class="text-sm font-semibold text-[#5C2E1F] mb-2">เลือกรูปแบบการกรอกผลการประเมินรายวิชา</p>
                        <label class="flex items-center gap-2 text-sm mb-1"><input type="radio" name="statuseva" value="1" class="accent-amber-700"> กรอกคะแนนประเมินรายวิชาตาม Section</label>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="statuseva" value="2" checked class="accent-amber-700"> กรอกคะแนนประเมินรายวิชาแบบรวม</label>
                    </div>

                    <div id="report-eva-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="relative">
                            <label for="totalnumstdevz" class="block text-sm font-medium mb-1 text-[#5C2E1F]">จำนวนนักศึกษาที่เข้าประเมิน</label>
                            <input id="totalnumstdevz" type="number" min="0"
                                autocomplete="off"
                                class="eva-hint-field w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                        </div>
                        <div class="relative">
                            <label for="totalevaluationscore" class="block text-sm font-medium mb-1 text-[#5C2E1F]">ผลการประเมินรายวิชาโดยนักศึกษา</label>
                            <input id="totalevaluationscore" type="number" min="0" max="5" step="0.01"
                                autocomplete="off" inputmode="decimal"
                                class="eva-hint-field w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                            <p class="text-xs text-[#7A4A3A]/80 mt-1">คะแนนเฉลี่ย 0–5 เท่านั้น — ไม่ใช่จำนวนนักศึกษา — ดูผลประเมินได้ที่ <a href="https://reg.kku.ac.th" target="_blank" rel="noopener noreferrer" class="text-[#8B4513] underline hover:text-[#5C2E1F]">reg.kku.ac.th</a></p>
                        </div>
                    </div>

                    <div id="section-eva-panel" class="hidden space-y-3">
                        <div class="rounded-lg border border-amber-200 bg-amber-50/50 px-3 py-2">
                            <p class="text-sm font-semibold text-[#5C2E1F]">กรอกผลประเมินแยกตาม Section</p>
                            <p class="text-xs text-[#7A4A3A]/80 mt-0.5">แสดงตาม Section ที่บันทึกในขั้นตอนจำนวนนักศึกษา — กรอกในหน้านี้ ไม่ต้องกรอกตอนบันทึก Section</p>
                        </div>
                        <div id="section-eva-empty" class="hidden rounded-lg border border-dashed border-amber-300 bg-white px-4 py-5 text-center text-sm text-[#7A4A3A]/80">
                            ยังไม่มี Section — กรุณาย้อนกลับไปขั้นตอนที่ 4 กรอกจำนวนนักศึกษาก่อน
                        </div>
                        <div id="section-eva-list" class="space-y-3"></div>
                    </div>
                    </div>

                    <div id="eva-hint-popover" class="fixed z-[9999] no-print" role="dialog" aria-label="ตัวอย่างการกรอกผลประเมินรายวิชา" aria-hidden="true">
                        <div class="eva-hint-popover-card">
                            <div class="eva-hint-popover-bar">
                                <p class="eva-hint-popover-title">ตัวอย่างการกรอก — กดปิดได้</p>
                                <button type="button" id="eva-hint-popover-close" class="eva-hint-popover-close" aria-label="ปิดรูปตัวอย่าง">ปิด</button>
                            </div>
                            <img src="{{ $teacherHelpImageUrl }}" alt="ตัวอย่างการกรอกผลประเมินรายวิชา"
                                onerror="this.onerror=null;this.src='https://e.sc.kku.ac.th/sci-eoffice/teacher/images2/teacher2.png';">
                        </div>
                    </div>

                    <div class="wizard-step space-y-5" data-wizard-step="3">
                    <div>
                        <p class="text-sm font-semibold text-[#5C2E1F] mb-2">ช่วงคะแนนของแต่ละเกรด</p>
                        <p class="text-xs text-[#7A4A3A]/80 mb-3">เลือกรูปแบบที่ต้องการกรอกอย่างน้อย 1 รายการ</p>
                        <div class="grid sm:grid-cols-3 gap-2 mb-4">
                            <label class="scheme-box flex items-start gap-2 rounded-lg px-3 py-2 text-sm cursor-pointer">
                                <input id="scheme-credit" type="checkbox" class="accent-amber-700 mt-0.5" checked>
                                <span>Credit (A–F)</span>
                            </label>
                            <label class="scheme-box flex items-start gap-2 rounded-lg px-3 py-2 text-sm cursor-pointer">
                                <input id="scheme-audit" type="checkbox" class="accent-amber-700 mt-0.5">
                                <span>Audit/SU เช่น รายวิชาสัมมนา</span>
                            </label>
                            <label class="scheme-box flex items-start gap-2 rounded-lg px-3 py-2 text-sm cursor-pointer">
                                <input id="scheme-both" type="checkbox" class="accent-amber-700 mt-0.5">
                                <span>มีทั้งสองแบบ</span>
                            </label>
                        </div>
                        <div class="flex flex-wrap gap-4 mb-2">
                            <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="intflag" value="0" checked class="accent-amber-700"> มีทศนิยม</label>
                            <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="intflag" value="1" class="accent-amber-700"> เป็นจำนวนเต็ม</label>
                        </div>
                        <p id="grade-boundary-hint" class="text-xs mb-3">กรุณากรอกเฉพาะขอบเขตล่างของช่วงคะแนน เป็นจำนวนทศนิยม เท่านั้น!!</p>
                        <p class="text-xs text-[#7A4A3A]/70 mb-3">ช่องซ้าย (สูงสุด) คำนวณอัตโนมัติ — กรอกเฉพาะช่องขวา (ขอบเขตล่าง) ของแต่ละเกรด</p>
                        <div id="credit-range-table" class="bg-white border border-amber-200 rounded-lg p-4 max-w-lg">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-amber-200">
                                        <th class="py-2 text-left font-semibold text-[#5C2E1F] w-16">เกรด</th>
                                        <th class="py-2 text-left font-semibold text-[#5C2E1F]">ช่วงคะแนน</th>
                                        <th class="py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ([['a','A'],['bp','B+'],['b','B'],['cp','C+'],['c','C'],['dp','D+'],['d','D'],['f','F']] as [$key, $label])
                                        <tr class="border-b border-amber-100 last:border-0 {{ in_array($key, ['bp','cp','dp']) ? 'bg-rose-50/40' : '' }}">
                                            <td class="py-2 font-medium">{{ $label }}</td>
                                            <td class="py-2">
                                                <div class="flex items-center gap-2">
                                                    <input id="range-{{ $key }}-max" type="text" class="grade-range-input w-24 border border-amber-200 rounded px-2 py-1.5 text-sm text-center {{ $key === 'a' ? 'bg-gray-100' : 'bg-gray-100' }}" value="{{ $key === 'a' ? '100' : '' }}" readonly>
                                                    <span class="text-xs text-gray-500">-</span>
                                                    <input id="range-{{ $key }}-min" type="text" data-grade="{{ $key }}" class="grade-range-min grade-range-input w-24 border border-amber-200 rounded px-2 py-1.5 text-sm text-center" placeholder="">
                                                </div>
                                            </td>
                                            <td class="py-2 text-center">
                                                <button type="button" class="grade-range-clear text-gray-400 hover:text-red-600 text-xs" data-grade="{{ $key }}" title="ล้างช่วงคะแนน">✕</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div id="audit-range-table" class="hidden bg-white border border-amber-200 rounded-lg p-4 max-w-lg mt-4">
                            <p class="text-sm font-semibold text-[#5C2E1F] mb-2">ช่วงคะแนน S และ U</p>
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-amber-200">
                                        <th class="py-2 text-left font-semibold text-[#5C2E1F] w-16">เกรด</th>
                                        <th class="py-2 text-left font-semibold text-[#5C2E1F]">ช่วงคะแนน</th>
                                        <th class="py-2 w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ([['s','S'],['u','U']] as [$key, $label])
                                        <tr class="border-b border-amber-100 last:border-0">
                                            <td class="py-2 font-medium">{{ $label }}</td>
                                            <td class="py-2">
                                                <div class="flex items-center gap-2">
                                                    <input id="range-{{ $key }}-max" type="text" class="grade-range-input w-24 border border-amber-200 rounded px-2 py-1.5 text-sm text-center bg-gray-100" value="{{ $key === 's' ? '100' : '' }}" readonly>
                                                    <span class="text-xs text-gray-500">-</span>
                                                    <input id="range-{{ $key }}-min" type="text" data-grade="{{ $key }}" class="grade-range-min grade-range-input w-24 border border-amber-200 rounded px-2 py-1.5 text-sm text-center" placeholder="">
                                                </div>
                                            </td>
                                            <td class="py-2 text-center">
                                                <button type="button" class="grade-range-clear text-gray-400 hover:text-red-600 text-xs" data-grade="{{ $key }}" title="ล้างช่วงคะแนน">✕</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    </div>

                    <div class="wizard-step space-y-5" data-wizard-step="4">
                    <div id="section-std-form" class="rounded-xl border border-amber-200 bg-[#FFFBF7] p-5 space-y-5 shadow-sm">
                        <div id="section-std-header" class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-200 pb-3">
                            <h3 class="font-bold text-[#5C2E1F] flex items-center gap-2 text-base">
                                <i data-lucide="users" class="w-5 h-5 text-[#8B4513]"></i>
                                กรอกจำนวนนักศึกษา
                            </h3>
                            <p id="section-form-hint" class="text-xs text-[#7A4A3A]/80"></p>
                        </div>
                        <div id="prior-sections-box" class="hidden rounded-lg border border-orange-200 bg-orange-50 px-3 py-2.5">
                            <p class="text-sm font-semibold text-orange-950">Section ที่รายงานไปแล้วในภาคนี้</p>
                            <p class="text-xs text-orange-900/80 mt-0.5">
                                Section เหล่านี้ถูกบันทึกแล้ว จึงไม่ให้เลือกซ้ำ — กรอกเฉพาะ Section ที่ยังไม่มี
                                ระบบจะเพิ่มเข้าในรายงานรายวิชาเดิมอัตโนมัติ หากต้องการแก้ Section เดิม กรุณาติดต่อผู้กรอกที่แสดงด้านล่าง
                            </p>
                            <div id="prior-sections-list" class="flex flex-wrap gap-1.5 mt-2"></div>
                        </div>

                        <div id="section-all-complete-box" class="hidden rounded-lg border border-green-200 bg-green-50 px-4 py-3">
                            <p class="text-sm font-semibold text-green-900">กรอกครบทุก Section แล้ว</p>
                            <p class="text-xs text-green-800/80 mt-0.5">
                                ไม่ต้องเพิ่ม Section อีก — หากต้องการแก้ไข ให้กด «แก้ไข» ที่รายการด้านล่าง ระบบจะเปิดเฉพาะ Section ที่กำลังแก้
                            </p>
                        </div>

                        <div id="section-std-entry-fields" class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="section-input" class="block text-sm font-medium mb-1 text-[#5C2E1F]">กลุ่ม (Section)</label>
                                <select id="section-input" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                                    @for ($i = 1; $i <= 20; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                                <p id="section-available-hint" class="text-xs text-[#7A4A3A]/80 mt-1">
                                    แสดง Section ตามที่เปิดสอนจริงในภาคนี้ — หากไม่พบในรายการระบบจะแสดง Section 1–20
                                </p>
                            </div>
                            <div class="relative" id="fac-multi-select">
                                <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">คณะ (เลือกได้หลายคณะ)</label>
                                <button type="button" id="fac-dropdown-btn"
                                    class="w-full flex items-center justify-between border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white text-left hover:border-[#C4725C]">
                                    <span id="fac-dropdown-label" class="text-gray-500 truncate">— เลือกคณะ —</span>
                                    <i data-lucide="chevron-down" class="w-4 h-4 shrink-0 text-[#8B4513]"></i>
                                </button>
                                <div id="fac-dropdown-panel" class="fac-dropdown-panel hidden absolute left-0 right-0 top-full mt-1 z-30 bg-white border border-amber-300 rounded-lg shadow-lg p-2">
                                    @forelse ($faculties as $faculty)
                                        <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-amber-50 cursor-pointer text-sm">
                                            <input type="checkbox" class="fac-checkbox accent-amber-700" value="{{ $faculty->nameng }}">
                                            <span><span class="font-semibold text-[#5C2E1F]">{{ $faculty->nameng }}</span> : {{ $faculty->namethai }}</span>
                                        </label>
                                    @empty
                                        <p class="px-2 py-1.5 text-sm text-gray-500">ไม่พบข้อมูลคณะจากตาราง grade_type</p>
                                    @endforelse
                                </div>
                                <div id="fac-selected-tags" class="flex flex-wrap gap-1.5 mt-2"></div>
                                <p id="fac-graduate-hint" class="hidden text-xs text-[#7A4A3A]/80 mt-1">
                                    รายวิชานี้อยู่นอกระดับปริญญาตรี ระบบเลือกคณะ GS บัณฑิตศึกษาให้แล้ว — สามารถเปลี่ยนได้
                                </p>
                            </div>
                        </div>

                        <div class="bg-[#fdf6f0] border border-amber-200 rounded-lg px-4 py-3">
                            <p class="text-sm font-medium text-[#5C2E1F] mb-2">ประเภทรายวิชา (กลุ่มเรียน)</p>
                            <div class="flex flex-wrap gap-3 text-sm">
                                @foreach ([1=>'ภาคปกติ',2=>'โครงการพิเศษ',3=>'ก้าวหน้า',4=>'ปกติ นานาชาติ',5=>'โครงการพิเศษ นานาชาติ'] as $v => $l)
                                    <label class="flex items-center gap-1.5 px-3 py-1 rounded-full border border-amber-200 bg-white hover:border-[#C4725C] cursor-pointer">
                                        <input type="radio" name="type_course" value="{{ $v }}" @checked($v===1) class="accent-amber-700"> {{ $l }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <div class="mb-3 rounded-lg border border-dashed border-amber-300 bg-white px-4 py-3 space-y-2">
                                <label for="section-pdf-upload" class="block text-sm font-medium text-[#5C2E1F]">
                                    อัปโหลดใบส่งผลการศึกษา (PDF) เพื่อกรอกจำนวนนักศึกษา
                                </label>
                                <p class="text-xs text-[#7A4A3A]/80">
                                    ตั้งชื่อไฟล์อย่างไรก็ได้ — ระบบอ่านกลุ่มเรียนจากเนื้อหา PDF แล้วตั้งชื่อเป็น
                                    <span class="font-semibold text-[#854d0e]">รหัสวิชา-กลุ่ม.pdf</span>
                                    ให้อัตโนมัติ (เช่น SC101011-01.pdf)
                                    และตรวจรหัสวิชา ภาคการศึกษา ปีการศึกษาให้ตรงกับที่กรอกด้านบน
                                </p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <input id="section-pdf-upload" type="file" accept=".pdf,application/pdf"
                                        class="block w-full max-w-md text-sm text-[#5C2E1F] file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-[#8B4513] file:text-white file:text-sm file:font-medium hover:file:bg-[#6B3410]">
                                    <span id="section-pdf-upload-status" class="text-xs text-[#7A4A3A]"></span>
                                </div>
                                <p id="section-pdf-upload-error" class="hidden text-xs text-red-600"></p>
                            </div>

                            <p class="text-sm font-medium text-[#5C2E1F] mb-2">จำนวนนักศึกษาแยกตามเกรด</p>
                            <p class="text-xs text-[#7A4A3A]/70 mb-2">ช่วงคะแนนด้านล่างอัปเดตตามที่กำหนดในตารางช่วงคะแนน</p>

                            <div class="overflow-x-auto rounded-lg border border-amber-200 bg-white">
                                <table id="student-grade-table" class="w-full text-sm min-w-[640px]">
                                    <thead>
                                        <tr>
                                            @foreach (['a'=>'A','bp'=>'B+','b'=>'B','cp'=>'C+','c'=>'C','dp'=>'D+','d'=>'D','f'=>'F'] as $key => $label)
                                                <th class="px-2 py-2 text-center border-b border-amber-200">
                                                    <div class="font-bold text-[#5C2E1F]">{{ $label }}</div>
                                                    <div class="grade-range-col mt-0.5" data-grade="{{ $key }}">—</div>
                                                </th>
                                            @endforeach
                                            @foreach (['i'=>'I','s'=>'S','u'=>'U','w'=>'W'] as $key => $label)
                                                <th class="px-2 py-2 text-center border-b border-amber-200 bg-amber-50/50">
                                                    <div class="font-bold text-[#5C2E1F]">{{ $label }}</div>
                                                    <div class="text-xs text-gray-400 mt-0.5">—</div>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            @foreach (['a','bp','b','cp','c','dp','d','f','i','s','u','w'] as $key)
                                                <td class="px-2 py-2 text-center border-t border-amber-100">
                                                    <input id="count-{{ $key }}" type="number" min="0"
                                                        class="w-full max-w-[4.5rem] mx-auto border border-amber-200 rounded-lg px-1 py-2 text-sm text-center bg-[#FFFBF7] focus:bg-white focus:border-[#C4725C]"
                                                        value="0">
                                                </td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 pt-2 border-t border-amber-200">
                            <button type="button" id="btn-save-section"
                                class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
                                บันทึก Section นี้
                            </button>
                            <button type="button" id="btn-cancel-section-edit"
                                class="hidden px-4 py-2 border border-amber-400 text-amber-800 rounded-lg text-sm font-medium hover:bg-amber-50">
                                ยกเลิกแก้ไข
                            </button>
                        </div>
                        </div>

                        <div id="section-std-results" class="space-y-3">
                            <p id="section-std-results-title" class="hidden text-sm font-semibold text-[#5C2E1F]">รายการจำนวนนักศึกษาที่กรอกแล้ว</p>
                            <div id="section-std-list-empty" class="rounded-lg border border-dashed border-amber-300 bg-white px-4 py-6 text-center text-sm text-[#7A4A3A]/80">
                                ยังไม่มีข้อมูล Section — กรอกด้านบนแล้วกด «บันทึก Section นี้»
                            </div>
                            <div id="section-std-list-wrap" class="hidden overflow-x-auto rounded-lg border border-amber-200 bg-white">
                                <table class="w-full text-xs min-w-[900px]">
                                    <thead>
                                        <tr class="bg-gradient-to-b from-[#fdf6f0] to-[#f5e6d8]">
                                            <th class="px-2 py-2 text-center border-b border-amber-200">ดำเนินการ</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">กลุ่ม</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">คณะ</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">รวม</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">A</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">B+</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">B</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">C+</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">C</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">D+</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">D</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">F</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">I</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">S</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">U</th>
                                            <th class="px-2 py-2 text-center border-b border-amber-200">W</th>
                                        </tr>
                                    </thead>
                                    <tbody id="section-std-list-body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="wizard-step space-y-4" data-wizard-step="6">
                        <div class="rounded-xl border border-amber-200 bg-white p-5 space-y-4">
                            <h3 class="font-bold text-[#5C2E1F]">แนบแบบฟอร์ม มข.11 ตามจำนวน Section ที่กรอก</h3>
                            <p id="wizard-reg-help" class="text-sm text-[#7A4A3A]/80 leading-relaxed">
                                ระบบจะนับจำนวน Section ที่คุณกรอกในขั้นตอนที่ 4 แล้วแสดงช่องอัปโหลด มข.11 เท่าจำนวนนั้น (Section ละ 1 ไฟล์)
                                จากสำนักทะเบียน
                                (<a href="https://reg.kku.ac.th" target="_blank" rel="noopener noreferrer" class="text-[#8B4513] underline">https://reg.kku.ac.th</a>)
                                <strong class="font-semibold text-[#5C2E1F]">ตั้งชื่อไฟล์อย่างไรก็ได้</strong>
                                — ระบบจะตั้งชื่อเป็น <span class="font-semibold text-[#854d0e]">รหัสวิชา-กลุ่ม.pdf</span> ให้อัตโนมัติ
                                หากยังอัปโหลดไม่ครบ ระบบจะไม่อนุญาตให้ไปขั้นตอนถัดไป
                            </p>

                            <p id="wizard-reg-section-count" class="text-sm font-semibold text-[#5C2E1F]"></p>

                            <div class="rounded-lg border border-dashed border-amber-400 bg-[#FFFBF7] p-4 space-y-2">
                                <p class="text-sm font-semibold text-[#5C2E1F]">อัปโหลดหลายไฟล์พร้อมกัน</p>
                                <p class="text-xs text-[#7A4A3A]/80">เลือกไฟล์ PDF มข.11 ได้หลายไฟล์ในครั้งเดียว (ตั้งชื่ออย่างไรก็ได้) ระบบจะจับคู่ Section และตั้งชื่อเป็นรหัสวิชา-กลุ่ม.pdf ให้เอง</p>
                                <input id="wizard-reg-bulk-upload" type="file" accept=".pdf,application/pdf" multiple
                                    class="block w-full max-w-xl text-sm text-[#5C2E1F] file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-[#8B4513] file:text-white file:text-sm file:font-medium hover:file:bg-[#6B3410]">
                                <p id="wizard-reg-bulk-status" class="text-xs text-[#7A4A3A]"></p>
                            </div>

                            <p id="wizard-reg-summary" class="text-sm font-semibold text-[#5C2E1F]"></p>
                            <ul id="wizard-reg-completeness" class="grid gap-1.5 sm:grid-cols-2 text-sm"></ul>
                            <div id="wizard-reg-uploads-list" class="space-y-3"></div>
                            <p id="wizard-reg-status" class="text-sm text-[#5C2E1F] font-medium"></p>
                            <input id="wizard-reg-upload" type="file" accept=".pdf,application/pdf" class="hidden">
                        </div>
                    </div>

                    <div class="wizard-step space-y-4" data-wizard-step="7">
                        <div class="rounded-xl border border-amber-200 bg-white p-5 space-y-3">
                            <h3 class="font-bold text-[#5C2E1F]">พิมพ์รายงานผลการสอบไล่ (ใบขวาง)</h3>
                            <p class="text-sm text-[#7A4A3A]/80">
                                กดปุ่มด้านล่างเพื่อเปิดแบบพิมพ์ใบขวาง หรือกด «ถัดไป» ระบบจะดาวน์โหลดใบขวางให้อัตโนมัติอีกครั้งก่อนเข้าขั้นตอนอัปโหลด
                            </p>
                            <button type="button" id="wizard-print-link"
                               class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-700 text-white rounded-lg text-sm font-semibold hover:bg-amber-800">
                                <i data-lucide="printer" class="w-4 h-4"></i> พิมพ์ใบขวาง
                            </button>
                        </div>
                    </div>

                    <div class="wizard-step space-y-4" data-wizard-step="8">
                        <div id="wizard-section-overview" class="rounded-xl border border-amber-200 bg-white p-5 space-y-4 shadow-sm">
                            <div>
                                <h3 class="font-bold text-[#5C2E1F] text-base">สรุป Section ของรายวิชานี้</h3>
                                <p id="wizard-section-overview-sub" class="text-sm text-[#7A4A3A]/80 mt-0.5">
                                    ดูจำนวน Section ทั้งหมด ส่งแล้ว / ยังไม่ส่ง และกำลังส่งกลุ่มไหนอยู่
                                </p>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                                <div class="rounded-xl border border-amber-200 bg-[#FFFBF7] px-3 py-3 text-center">
                                    <p id="wizard-sec-stat-total" class="text-2xl sm:text-3xl font-bold text-[#5C2E1F] tabular-nums">—</p>
                                    <p class="text-xs sm:text-sm text-[#7A4A3A] mt-1">ทั้งหมด</p>
                                </div>
                                <div class="rounded-xl border border-green-200 bg-green-50 px-3 py-3 text-center">
                                    <p id="wizard-sec-stat-filled" class="text-2xl sm:text-3xl font-bold text-green-800 tabular-nums">—</p>
                                    <p class="text-xs sm:text-sm text-green-900/80 mt-1">ส่งแล้ว</p>
                                </div>
                                <div class="rounded-xl border border-amber-300 bg-amber-50 px-3 py-3 text-center">
                                    <p id="wizard-sec-stat-remain" class="text-2xl sm:text-3xl font-bold text-amber-900 tabular-nums">—</p>
                                    <p class="text-xs sm:text-sm text-amber-900/80 mt-1">ยังไม่ส่ง</p>
                                </div>
                                <div class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-3 text-center">
                                    <p id="wizard-sec-stat-current" class="text-2xl sm:text-3xl font-bold text-sky-900 tabular-nums">—</p>
                                    <p class="text-xs sm:text-sm text-sky-900/80 mt-1">กำลังส่ง</p>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-2 text-xs text-[#7A4A3A] mb-1.5">
                                    <span>ความคืบหน้าการส่งเอกสาร</span>
                                    <span id="wizard-sec-progress-label" class="font-semibold text-[#5C2E1F]">—</span>
                                </div>
                                <div class="h-2.5 rounded-full bg-amber-100 overflow-hidden">
                                    <div id="wizard-sec-progress-bar" class="h-full rounded-full bg-[#8B4513] transition-all duration-300" style="width:0%"></div>
                                </div>
                            </div>

                            <div id="wizard-sec-current-box" class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3">
                                <p class="text-xs font-semibold text-sky-900 uppercase tracking-wide">กำลังส่ง Section</p>
                                <p id="wizard-sec-current-label" class="text-base font-bold text-sky-950 mt-1">—</p>
                                <p id="wizard-sec-current-help" class="text-xs text-sky-900/75 mt-1"></p>
                            </div>

                            <div>
                                <p class="text-xs font-semibold text-[#5C2E1F] mb-2">รายการกลุ่มเรียน</p>
                                <div id="wizard-sec-chip-list" class="flex flex-wrap gap-1.5"></div>
                            </div>
                        </div>

                        <div id="wizard-attachment-checklist" class="rounded-xl border border-amber-200 bg-[#FFFBF7] p-4 space-y-2">
                            <p class="text-sm font-semibold text-[#5C2E1F]">ต้องมีไฟล์ครบก่อนเสร็จสิ้น</p>
                            <p class="text-xs text-[#7A4A3A]/80">เมื่อกดเสร็จสิ้น ระบบจะอัปโหลดแบบฟอร์ม มข.11 และใบขวางของ Section ที่คุณกรอกเข้าสู่ระบบ — ใบขวาง 1 ใบสามารถครอบคลุม มข.11 ได้หลาย Section</p>
                            <p id="wizard-reg-check" class="text-sm text-[#7A4A3A]">แบบฟอร์ม มข.11 ครบทุก Section — ขั้นตอนที่ 6</p>
                            <p id="wizard-exam-check" class="text-sm text-[#7A4A3A]">ใบรายงานผลการสอบไล่ / ใบขวาง — ขั้นตอนที่ 8</p>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-white p-5 space-y-4">
                            <div>
                                <h3 class="font-bold text-[#5C2E1F]">ประวัติแบบรายงานผลการสอบไล่ (ใบขวาง)</h3>
                                <p class="text-sm text-[#7A4A3A]/80 mt-1">
                                    ระบบบันทึกทุกใบที่อัปโหลดไว้ตรวจสอบได้ — ใบขวาง 1 ใบอาจผูกกับใบ มข.11 ได้หลาย Section
                                    · แก้ไข/ลบได้เฉพาะไฟล์ที่คุณอัปโหลด และเฉพาะเมื่อ Admin ยังไม่เปลี่ยนสถานะรายงาน
                                </p>
                            </div>
                            <div id="wizard-exam-packets" class="space-y-3">
                                <p class="text-sm text-[#7A4A3A]/70">ยังไม่มีใบขวางในรายงานนี้</p>
                            </div>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-white p-5 space-y-4">
                            <div>
                                <h3 class="font-bold text-[#5C2E1F]">สถานะเอกสารทุก Section</h3>
                                <p class="text-sm text-[#7A4A3A]/80 mt-1">
                                    ดูได้ว่าแต่ละ Section กรอกโดยใคร และมีเอกสารอะไรแล้วบ้าง
                                    — จัดการได้เฉพาะ Section/ไฟล์ของตนเอง · ของอาจารย์อื่นดูได้อย่างเดียว
                                </p>
                            </div>
                            <div id="wizard-section-board" class="space-y-3">
                                <p class="text-sm text-[#7A4A3A]/70">กำลังโหลดรายการ Section…</p>
                            </div>
                        </div>

                        <div id="wizard-exam-own-panel" class="rounded-xl border border-sky-200 bg-sky-50/60 p-5 space-y-3">
                            <h3 class="font-bold text-[#0c4a6e]">อัปโหลดใบขวาง — Section ของคุณ</h3>
                            <p id="wizard-exam-own-help" class="text-sm text-[#0c4a6e]/80">
                                หากคุณกรอกหลาย Section ในรอบนี้ สามารถอัปโหลดใบขวางไฟล์เดียว ครอบคลุม มข.11 ของหลาย Section ได้
                            </p>
                            <p id="wizard-exam-own-secs" class="text-sm font-semibold text-[#0c4a6e]"></p>
                            <div id="wizard-exam-file-row" class="hidden"></div>
                            <input id="wizard-exam-upload" type="file" accept=".pdf,application/pdf"
                                class="block w-full max-w-md text-sm text-[#5C2E1F] file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-[#8B4513] file:text-white file:text-sm file:font-medium hover:file:bg-[#6B3410]">
                            <div id="wizard-exam-actions" class="hidden flex flex-wrap gap-2"></div>
                            <p id="wizard-exam-status" class="text-xs text-[#7A4A3A]"></p>
                        </div>
                    </div>

                    <div id="wizard-nav" class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-amber-200">
                        <a id="btn-cancel" href="{{ $returnUrl }}" class="px-4 py-2 border border-amber-400 text-amber-800 rounded-lg text-sm font-medium hover:bg-amber-50 inline-flex items-center">ยกเลิก</a>
                        <div class="flex gap-2">
                            <button type="button" id="wizard-back" class="hidden px-5 py-2 border border-amber-400 text-[#5C2E1F] rounded-lg text-sm font-medium hover:bg-amber-50">ย้อนกลับ</button>
                            <button type="button" id="wizard-next" class="px-5 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-semibold hover:bg-[#6B3410]">ถัดไป</button>
                            <button type="submit" id="btn-submit" class="hidden">บันทึก</button>
                        </div>
                    </div>
                </form>
                <div id="wizard-done" class="hidden rounded-xl border border-green-200 bg-green-50 p-8 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-100 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-10 h-10 text-green-700"></i>
                    </div>
                    <h3 class="text-xl font-bold text-green-900">ดำเนินการรายงานผลการสอบไล่เรียบร้อยแล้ว</h3>
                    <p class="mt-2 text-sm text-[#5C2E1F] leading-relaxed max-w-xl mx-auto">
                        ระบบบันทึกข้อมูลรายวิชาและอัปโหลดไฟล์แนบครบแล้ว
                        (แบบฟอร์ม มข.11 + ใบรายงานผลการสอบไล่ / ใบขวาง)
                        ท่านสามารถกลับหน้าหลักเพื่อกรอกรายวิชาถัดไป หรือไปติดตามสถานะที่ส่งแล้ว
                    </p>
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <a href="{{ $dashboardUrl }}" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-semibold hover:bg-[#6B3410]">
                            กลับหน้าหลัก — กรอกรายวิชาถัดไป
                        </a>
                        <a href="{{ $trackUrl ?? route('grade-reports.my') }}" class="px-5 py-2.5 border border-amber-300 text-[#5C2E1F] rounded-lg text-sm font-medium hover:bg-amber-50">
                            ติดตามรายงานผลการสอบ
                        </a>
                    </div>
                </div>
            </div>
        </section>

    <div id="save-overlay" class="hidden fixed inset-0 z-[100] no-print" role="dialog" aria-modal="true" aria-labelledby="save-overlay-title">
        <div class="absolute inset-0 bg-[#3d2418]/70 backdrop-blur-[2px]"></div>
        <div class="relative z-10 min-h-full flex items-center justify-center p-4 sm:p-6">
            <div class="save-overlay-card bg-white rounded-2xl shadow-2xl max-w-xl w-full p-8 sm:p-10 text-center border-4 border-amber-300">

                <div id="save-overlay-loading">
                    <div class="save-spinner mb-6" aria-hidden="true"></div>
                    <h2 id="save-overlay-title" class="text-2xl sm:text-3xl font-bold text-[#5C2E1F] mb-3">
                        กำลังบันทึกข้อมูล...
                    </h2>
                    <p class="text-lg sm:text-xl text-[#7A4A3A] leading-relaxed">
                        ระบบกำลังบันทึกแบบรายงานผลการสอบไล่<br>
                        <strong class="text-[#8B4513]">กรุณารอสักครู่</strong> และ<strong class="text-red-700">อย่าปิดหน้านี้</strong>
                    </p>
                    <p class="mt-4 text-base text-gray-500">อาจใช้เวลา 5–15 วินาที</p>
                </div>

                <div id="save-overlay-success" class="hidden">
                    <div class="save-success-icon w-20 h-20 mx-auto mb-5 rounded-full bg-green-100 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-14 h-14 text-green-700"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-green-800 mb-3">บันทึกข้อมูลเรียบร้อยแล้ว</h2>
                    <p id="save-overlay-success-msg" class="text-lg sm:text-xl text-[#5C2E1F] leading-relaxed"></p>
                    <p class="mt-4 text-base text-gray-500">ระบบจะดำเนินการต่อให้อัตโนมัติ...</p>
                </div>

                <div id="save-overlay-error" class="hidden">
                    <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-red-100 flex items-center justify-center">
                        <i data-lucide="alert-circle" class="w-14 h-14 text-red-700"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-red-800 mb-3">บันทึกไม่สำเร็จ</h2>
                    <p id="save-overlay-error-msg" class="text-lg sm:text-xl text-[#5C2E1F] leading-relaxed mb-6"></p>
                    <button type="button" id="save-overlay-error-close"
                        class="px-8 py-3 bg-[#8B4513] text-white text-lg font-semibold rounded-xl hover:bg-[#6d3610] min-w-[10rem]">
                        ตกลง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="wizard-print-download-overlay" class="hidden fixed inset-0 z-[100] no-print" role="dialog" aria-modal="true" aria-labelledby="wizard-print-download-title">
        <div class="absolute inset-0 bg-[#3d2418]/70 backdrop-blur-[2px]"></div>
        <div class="relative z-10 min-h-full flex items-center justify-center p-4 sm:p-6">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-8 sm:p-10 text-center border-4 border-sky-300">
                <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-sky-100 flex items-center justify-center">
                    <i data-lucide="download" class="w-12 h-12 text-sky-800"></i>
                </div>
                <h2 id="wizard-print-download-title" class="text-2xl sm:text-3xl font-bold text-sky-950 mb-3">
                    ระบบได้ทำการ download เอกสารให้เรียบร้อยแล้ว
                </h2>
                <p class="text-lg text-[#5C2E1F] leading-relaxed mb-6">
                    กรุณากดเปิดไฟล์ เพื่อตรวจสอบแบบรายงานผลการสอบไล่ (ใบขวาง)<br>
                    หรือกดยกเลิกเพื่อไปขั้นตอนที่ 8 ต่อโดยไม่เปิดไฟล์
                </p>
                <div class="flex flex-wrap justify-center gap-3">
                    <button type="button" id="wizard-print-download-overlay-open"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-sky-800 text-white text-base font-semibold hover:bg-sky-900">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                        เปิดไฟล์
                    </button>
                    <button type="button" id="wizard-print-download-overlay-close"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl border border-amber-300 text-[#5C2E1F] text-base font-semibold hover:bg-amber-50">
                        ยกเลิก
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" class="fixed bottom-6 right-6 px-5 py-3 rounded-lg shadow-lg text-sm font-medium hidden no-print z-50"></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/templade-data-sdk.js') }}?v={{ filemtime(public_path('js/templade-data-sdk.js')) }}"></script>
    <script src="{{ asset('js/image-only-pdf-guide.js') }}?v={{ filemtime(public_path('js/image-only-pdf-guide.js')) }}"></script>
    <script src="{{ asset('js/templade-form.js') }}?v={{ filemtime(public_path('js/templade-form.js')) }}"></script>
    <script>
    (function() {
        const reportId = @json($reportId ?? null);
        const teacherHelpImageUrl = @json($teacherHelpImageUrl);
        const returnUrl = @json($returnUrl);
        const dashboardUrl = @json($dashboardUrl);
        const uploadParsed = @json($uploadParsed ?? null);
        const prefillReport = @json($prefillReport ?? null);
        const cameFromUpload = @json($cameFromUpload ?? false);
        const hasPendingRegistrar = @json($hasPendingRegistrar ?? false);
        const hasRegistrarFile = @json($hasRegistrarFile ?? false);
        const hasExamReportFile = @json($hasExamReportFile ?? false);
        const registrarFileSections = @json($registrarFileSections ?? []);
        const registrarFileDetails = @json($registrarFileDetails ?? []);
        const examFileDetail = @json($examFileDetail ?? null);
        const pendingRegistrarSections = @json($pendingRegistrarSections ?? []);
        const staffUsername = @json($staffUsername ?? null);

        window.wizardConfig = {
            currentReportId: reportId,
            openedAsEdit: Boolean(reportId),
            createdInSession: false,
            boundSubjectCode: null,
            staffUsername,
        };
        initTempladeForm({ teacherHelpImageUrl });

        if (prefillReport) {
            populateFormFromRecord(prefillReport);
        } else if (reportId) {
            fetch(`/api/grade-reports/${reportId}?role=instructor`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(async (r) => {
                    const data = await r.json().catch(() => ({}));
                    if (!r.ok) {
                        throw new Error(data.message || 'โหลดข้อมูลไม่สำเร็จ');
                    }
                    return data;
                })
                .then(populateFormFromRecord)
                .catch((err) => showToast(err?.message || 'โหลดข้อมูลไม่สำเร็จ', 'error'));
        } else {
            const reportDate = document.getElementById('report-date');
            if (reportDate && !reportDate.value) reportDate.value = new Date().toISOString().slice(0, 10);
            const rangeA = document.getElementById('range-a-max');
            if (rangeA && !rangeA.value) rangeA.value = '100';
            if (uploadParsed) {
                window.wizardConfig.cameFromUpload = true;
                window.sectionEntryViaUpload = true;
                uploadParsed.__fromUpload = true;
                populateFormFromRecord(uploadParsed);
            }
        }

        initGradeReportWizard(Object.assign(window.wizardConfig, {
            cameFromUpload,
            hasPendingRegistrar,
            hasRegistrarFile,
            hasExamReportFile,
            registrarFileSections,
            registrarFileDetails,
            examFileDetail,
            pendingRegistrarSections,
            staffUsername,
            returnUrl,
            dashboardUrl,
        }));

        document.getElementById('save-overlay-error-close')?.addEventListener('click', () => {
            document.getElementById('save-overlay')?.classList.add('hidden');
            document.body.style.overflow = '';
        });

        lucide.createIcons();
    })();
    </script>
@endpush
