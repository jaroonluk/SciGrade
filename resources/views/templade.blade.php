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
    #eva-hint-popover img { max-width: min(100vw - 2rem, 420px); height: auto; border-radius: 0.5rem; border: 1px solid #e8cdb5; box-shadow: 0 8px 24px rgba(92,46,31,.18); background: #fff; }
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
    .wizard-dot {
        width: 1.75rem; height: 1.75rem; border-radius: 9999px;
        display: flex; align-items: center; justify-content: center;
        font-size: .7rem; font-weight: 700; border: 2px solid #E8C4B8;
        background: #fff; color: #9ca3af;
    }
    .wizard-dot.is-done { background: #166534; border-color: #166534; color: #fff; }
    .wizard-dot.is-current { background: #8B4513; border-color: #8B4513; color: #fff; }
    .wizard-label { font-size: .65rem; line-height: 1.2; color: #7A4A3A; max-width: 5.5rem; }
    @@media (min-width: 768px) { .wizard-label { font-size: .75rem; max-width: 7rem; } }
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
                <ol id="wizard-stepper" class="grid grid-cols-4 md:grid-cols-8 gap-2 mb-6">
                    @foreach ([
                        1 => 'ข้อมูลรายวิชา',
                        2 => 'หมายเหตุ',
                        3 => 'ช่วงคะแนน',
                        4 => 'ประเมินรายวิชา',
                        5 => 'จำนวนนักศึกษา',
                        6 => 'แนบ REG',
                        7 => 'พิมพ์ใบขวาง',
                        8 => 'อัปโหลดใบขวาง',
                    ] as $n => $label)
                        <li class="flex flex-col items-center text-center gap-1" data-wizard-dot="{{ $n }}">
                            <span class="wizard-dot {{ $n === 1 ? 'is-current' : '' }}">{{ $n }}</span>
                            <span class="wizard-label">{{ $label }}</span>
                        </li>
                    @endforeach
                </ol>
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
                            <p class="text-xs text-[#7A4A3A]/80 mt-1">ดึงชื่อจากข้อมูลบุคลากรเป็นค่าเริ่มต้น — สามารถแก้ไขหรือเพิ่มชื่ออาจารย์ผู้สอนได้</p>
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
                    <div class="bg-white border border-amber-200 rounded-lg p-4 space-y-3">
                        <p class="text-sm font-semibold text-[#5C2E1F]">หมายเหตุ</p>
                        <p class="text-xs text-[#7A4A3A]/80">ข้ามขั้นตอนนี้ได้หากไม่มีหมายเหตุ</p>
                        <label class="flex items-start gap-2 text-sm">
                            <input type="radio" name="reasonid" value="1" class="accent-amber-700 mt-1 shrink-0">
                            <div class="flex-1 min-w-0">
                                <span class="text-[#5C2E1F]">ตัดเกรดร่วมกับ</span>
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
                                    <p class="text-xs text-[#7A4A3A]/70 mt-1">เลือกจากรายการเพื่อแสดงชื่อวิชา — หากไม่มีในฐานข้อมูล กด Enter หรือเลือก «กรอกเอง» เพื่อเพิ่มรหัสวิชา</p>
                                </div>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="reasonid" value="2" class="accent-amber-700"> ได้ I เนื่องจาก <input id="std-i2" type="text" class="border border-amber-200 rounded px-2 py-1 text-sm flex-1"></label>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="reasonid" value="3" class="accent-amber-700"> อื่นๆ <input id="std-i3" type="text" class="border border-amber-200 rounded px-2 py-1 text-sm flex-1"></label>
                    </div>
                    </div>

                    <div class="wizard-step space-y-5" data-wizard-step="4">
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
                    </div>

                    <div id="eva-hint-popover" class="fixed z-[9999] no-print pointer-events-none">
                        <img src="{{ $teacherHelpImageUrl }}" alt="ตัวอย่างการกรอกผลประเมินรายวิชา"
                            onerror="this.onerror=null;this.src='https://e.sc.kku.ac.th/sci-eoffice/teacher/images2/teacher2.png';">
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

                    <div class="wizard-step space-y-5" data-wizard-step="5">
                    <div id="section-std-form" class="rounded-xl border border-amber-200 bg-[#FFFBF7] p-5 space-y-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-200 pb-3">
                            <h3 class="font-bold text-[#5C2E1F] flex items-center gap-2 text-base">
                                <i data-lucide="users" class="w-5 h-5 text-[#8B4513]"></i>
                                กรอกจำนวนนักศึกษา
                            </h3>
                            <p id="section-form-hint" class="text-xs text-[#7A4A3A]/80"></p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="section-input" class="block text-sm font-medium mb-1 text-[#5C2E1F]">กลุ่ม (Section)</label>
                                <select id="section-input" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                                    @for ($i = 1; $i <= 50; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
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
                                    ระบบจะตรวจรหัสวิชา ภาคการศึกษา และปีการศึกษาให้ตรงกับที่กรอกด้านบน
                                    เมื่อบันทึกรายงาน ไฟล์นี้จะถูกแนบเป็นใบส่งผลการศึกษา (REG) อัตโนมัติ
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

                        <div id="section-eva-fields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 mt-2 border-t border-amber-200">
                            <div class="relative">
                                <label for="numstdevz" class="block text-sm font-medium mb-1 text-[#5C2E1F]">จำนวนนักศึกษาที่เข้าประเมิน</label>
                                <input id="numstdevz" type="number" min="0"
                                    autocomplete="off"
                                    class="eva-hint-field w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <p class="text-xs text-[#7A4A3A]/70 mt-1">กรอกจำนวนนักศึกษาที่เข้าประเมินรายวิชาใน Section นี้</p>
                            </div>
                            <div class="relative">
                                <label for="evaluationscore" class="block text-sm font-medium mb-1 text-[#5C2E1F]">ผลการประเมินรายวิชาโดยนักศึกษา</label>
                                <input id="evaluationscore" type="number" min="0" max="5" step="0.01"
                                    autocomplete="off" inputmode="decimal"
                                    class="eva-hint-field w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <p class="text-xs text-[#7A4A3A]/80 mt-1">คะแนนเฉลี่ย 0–5 เท่านั้น — ไม่ใช่จำนวนนักศึกษา — ดูผลประเมินได้ที่ <a href="https://reg.kku.ac.th" target="_blank" rel="noopener noreferrer" class="text-[#8B4513] underline hover:text-[#5C2E1F]">reg.kku.ac.th</a></p>
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
                                        <th id="section-list-eva-col" class="px-2 py-2 text-center border-b border-amber-200 hidden">คะแนนประเมิน</th>
                                    </tr>
                                </thead>
                                <tbody id="section-std-list-body"></tbody>
                            </table>
                        </div>
                    </div>
                    </div>

                    <div class="wizard-step space-y-4" data-wizard-step="6">
                        <div class="rounded-xl border border-amber-200 bg-white p-5 space-y-3">
                            <h3 class="font-bold text-[#5C2E1F]">แนบใบส่งผลการศึกษา (REG)</h3>
                            <p class="text-sm text-[#7A4A3A]/80 leading-relaxed">
                                กรณีกรอกข้อมูลเอง กรุณาดาวน์โหลดใบส่งผลการศึกษาจากสำนักทะเบียน
                                <a href="https://reg.kku.ac.th" target="_blank" rel="noopener noreferrer" class="text-[#8B4513] underline">https://reg.kku.ac.th</a>
                                แล้วอัปโหลดไฟล์ PDF ที่นี่ หากอัปโหลดไว้แล้วในขั้นตอนก่อนหน้า สามารถข้ามได้
                            </p>
                            <input id="wizard-reg-upload" type="file" accept=".pdf,application/pdf"
                                class="block w-full max-w-md text-sm text-[#5C2E1F] file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-[#8B4513] file:text-white file:text-sm file:font-medium hover:file:bg-[#6B3410]">
                            <p id="wizard-reg-status" class="text-xs text-[#7A4A3A]"></p>
                        </div>
                    </div>

                    <div class="wizard-step space-y-4" data-wizard-step="7">
                        <div class="rounded-xl border border-amber-200 bg-white p-5 space-y-3">
                            <h3 class="font-bold text-[#5C2E1F]">พิมพ์รายงานผลการสอบไล่ (ใบขวาง)</h3>
                            <p class="text-sm text-[#7A4A3A]/80">ระบบบันทึกรายงานแล้ว — กดปุ่มด้านล่างเพื่อเปิดแบบพิมพ์ใบขวางในแท็บใหม่</p>
                            <a id="wizard-print-link" href="#" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-700 text-white rounded-lg text-sm font-semibold hover:bg-amber-800">
                                <i data-lucide="printer" class="w-4 h-4"></i> พิมพ์ใบขวาง
                            </a>
                        </div>
                    </div>

                    <div class="wizard-step space-y-4" data-wizard-step="8">
                        <div class="rounded-xl border border-amber-200 bg-white p-5 space-y-3">
                            <h3 class="font-bold text-[#5C2E1F]">อัปโหลดรายงานผลการสอบไล่ (ใบขวาง)</h3>
                            <p class="text-sm text-[#7A4A3A]/80">แนะนำให้อัปโหลดไฟล์ PDF ที่พิมพ์และลงนามแล้ว เพื่อให้สาขาตรวจสอบได้</p>
                            <input id="wizard-exam-upload" type="file" accept=".pdf,application/pdf"
                                class="block w-full max-w-md text-sm text-[#5C2E1F] file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-[#8B4513] file:text-white file:text-sm file:font-medium hover:file:bg-[#6B3410]">
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
                        ระบบได้รับข้อมูลรายวิชานี้แล้ว ท่านสามารถกลับหน้าหลักเพื่อกรอกผลการสอบรายวิชาถัดไป หรือไปติดตามสถานะที่ส่งแล้ว
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

    <div id="toast" class="fixed bottom-6 right-6 px-5 py-3 rounded-lg shadow-lg text-sm font-medium hidden no-print z-50"></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/templade-data-sdk.js') }}"></script>
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
                populateFormFromRecord(uploadParsed);
            }
        }

        initGradeReportWizard({
            currentReportId: reportId,
            cameFromUpload,
            hasPendingRegistrar,
            hasRegistrarFile,
            hasExamReportFile,
            returnUrl,
            dashboardUrl,
        });

        document.getElementById('save-overlay-error-close')?.addEventListener('click', () => {
            document.getElementById('save-overlay')?.classList.add('hidden');
            document.body.style.overflow = '';
        });

        lucide.createIcons();
    })();
    </script>
@endpush
