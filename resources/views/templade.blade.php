@extends('layouts.scigrad')

@section('title', isset($reportId) ? 'แก้ไขรายงานผลสอบ — SciGrad' : 'กรอกผลสอบ — SciGrad')

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
</style>
@endpush

@section('content')
        @php
            $defaultYear = (int) date('Y') + 543;
            if ((int) date('m') <= 9) { $defaultYear--; }
            $faculties = [
                ['SC', 'คณะวิทยาศาสตร์'], ['EN', 'คณะวิศวกรรมศาสตร์'], ['AG', 'คณะเกษตรศาสตร์'],
                ['ED', 'คณะศึกษาศาสตร์'], ['NU', 'คณะพยาบาลศาสตร์'], ['MD', 'คณะแพทยศาสตร์'],
                ['PH', 'คณะเภสัชศาสตร์'], ['DN', 'คณะทันตแพทยศาสตร์'], ['HS', 'คณะสาธารณสุขศาสตร์'],
                ['LA', 'คณะนิติศาสตร์'], ['EC', 'คณะเศรษฐศาสตร์'], ['BA', 'คณะบริหารธุรกิจ'],
                ['GS', 'บัณฑิตวิทยาลัย'], ['HU', 'คณะมนุษยศาสตร์และสังคมศาสตร์'],
            ];
        @endphp

        <section>
            <div class="form-section rounded-lg p-5 mb-4">
                <h2 class="text-lg font-bold mb-4 text-[#5C2E1F] flex items-center gap-2">
                    <i data-lucide="file-text" class="w-5 h-5 text-[#8B4513]"></i>
                    {{ isset($reportId) ? 'แก้ไขแบบรายงานผลการสอบไล่' : 'สร้างแบบรายงานผลการสอบไล่' }}
                </h2>
                <form id="grade-form" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">ประเภทรายวิชา *</label>
                            <select id="selecttype" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                                <option value="1">วิชาในหลักสูตร</option>
                                <option value="2">รายวิชาบริการ</option>
                            </select>
                        </div>
                        <div class="relative md:col-span-2">
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">รหัสวิชา *</label>
                            <input id="subject-code" type="text" maxlength="20" autocomplete="off"
                                class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" placeholder="รหัสวิชา">
                            <div id="subject-suggestions"
                                class="absolute left-0 top-full mt-1.5 z-40 hidden bg-white border border-amber-300 rounded-lg"></div>
                        </div>
                        <div class="md:col-span-7">
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">ชื่อวิชา *</label>
                            <input id="subject-name" type="text"
                                class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" placeholder="PHYSICAL SCIENCE">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2 text-[#5C2E1F]">ภาคการศึกษา *</label>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="term" value="1" checked class="accent-amber-700"> ภาคต้น</label>
                                <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="term" value="2" class="accent-amber-700"> ภาคปลาย</label>
                                <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="term" value="3" class="accent-amber-700"> ภาคการศึกษาพิเศษ</label>
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

                    <div id="curriculum-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">ระดับการศึกษา</label>
                            <select id="degree" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                                <option value="3">ปริญญาตรี</option>
                                <option value="5" id="degree-opt-master">ปริญญาโท</option>
                                <option value="7" id="degree-opt-phd">ปริญญาเอก</option>
                            </select>
                        </div>
                        <div id="program-field" class="hidden">
                            <label for="programid" class="block text-sm font-medium mb-1 text-[#5C2E1F]">หลักสูตร <span class="text-red-600">*</span></label>
                            <select id="programid" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                                <option value="">— เลือกหลักสูตร —</option>
                                @foreach ($programs as $program)
                                    <option value="{{ $program->programid }}">{{ $program->programname }}</option>
                                @endforeach
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

                    <div class="bg-white border border-amber-200 rounded-lg p-4 space-y-3">
                        <p class="text-sm font-semibold text-[#5C2E1F]">หมายเหตุ</p>
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
                                    <p class="text-xs text-[#7A4A3A]/70 mt-1">เลือกจากรายการเพื่อแสดงชื่อวิชา — หากไม่มีในฐานข้อมูล กด Enter หรือเลือก «กรอกเอง» เพื่อเพิ่มรหัสวิชา</p>
                                </div>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="reasonid" value="2" class="accent-amber-700"> ได้ I เนื่องจาก <input id="std-i2" type="text" class="border border-amber-200 rounded px-2 py-1 text-sm flex-1"></label>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="reasonid" value="3" class="accent-amber-700"> อื่นๆ <input id="std-i3" type="text" class="border border-amber-200 rounded px-2 py-1 text-sm flex-1"></label>
                    </div>

                    <div class="bg-white border border-amber-200 rounded-lg p-4">
                        <p class="text-sm font-semibold text-[#5C2E1F] mb-2">รูปแบบการกรอกจำนวนนักศึกษา</p>
                        <label class="flex items-center gap-2 text-sm mb-1"><input type="radio" name="statuseva" value="1" class="accent-amber-700"> ต้องกรอกช่วงคะแนนตาม Section</label>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="statuseva" value="2" checked class="accent-amber-700"> กรอกช่วงคะแนนรวม</label>
                    </div>

                    <div id="report-eva-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="relative">
                            <label for="totalnumstdevz" class="block text-sm font-medium mb-1 text-[#5C2E1F]">จำนวนนักศึกษาที่เข้าประเมิน</label>
                            <input id="totalnumstdevz" type="number" min="0"
                                class="eva-hint-field w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                        </div>
                        <div class="relative">
                            <label for="totalevaluationscore" class="block text-sm font-medium mb-1 text-[#5C2E1F]">ผลการประเมินรายวิชาโดยนักศึกษา</label>
                            <input id="totalevaluationscore" type="number" min="1" max="5" step="0.01"
                                class="eva-hint-field w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                            <p class="text-xs text-[#7A4A3A]/80 mt-1">ไม่เกิน 5 คะแนน — ดูผลประเมินได้ที่ <a href="https://reg.kku.ac.th" target="_blank" rel="noopener noreferrer" class="text-[#8B4513] underline hover:text-[#5C2E1F]">reg.kku.ac.th</a></p>
                        </div>
                    </div>

                    <div id="eva-hint-popover" class="fixed z-[9999] no-print pointer-events-none">
                        <img src="{{ $teacherHelpImageUrl }}" alt="ตัวอย่างการกรอกผลประเมินรายวิชา"
                            onerror="this.onerror=null;this.src='https://e.sc.kku.ac.th/sci-eoffice/teacher/images2/teacher2.png';">
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-[#5C2E1F] mb-2">ช่วงคะแนนของแต่ละเกรด</p>
                        <div class="flex flex-wrap gap-4 mb-2">
                            <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="intflag" value="0" checked class="accent-amber-700"> มีทศนิยม</label>
                            <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="intflag" value="1" class="accent-amber-700"> เป็นจำนวนเต็ม</label>
                        </div>
                        <p id="grade-boundary-hint" class="text-xs mb-3">กรุณากรอกเฉพาะขอบเขตล่างของช่วงคะแนน เป็นจำนวนทศนิยม เท่านั้น!!</p>
                        <p class="text-xs text-[#7A4A3A]/70 mb-3">ช่องซ้าย (สูงสุด) คำนวณอัตโนมัติ — กรอกเฉพาะช่องขวา (ขอบเขตล่าง) ของแต่ละเกรด</p>
                        <div class="bg-white border border-amber-200 rounded-lg p-4 max-w-lg">
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
                    </div>

                    <div id="section-std-form" class="rounded-xl border border-amber-200 bg-[#FFFBF7] p-5 space-y-5 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-200 pb-3">
                            <h3 class="font-bold text-[#5C2E1F] flex items-center gap-2 text-base">
                                <i data-lucide="users" class="w-5 h-5 text-[#8B4513]"></i>
                                กรอกจำนวนนักศึกษา
                            </h3>
                            <p id="section-form-hint" class="text-xs text-[#7A4A3A]/80"></p>
                        </div>

                        <div id="section-std-list-empty" class="rounded-lg border border-dashed border-amber-300 bg-white px-4 py-6 text-center text-sm text-[#7A4A3A]/80">
                            ยังไม่มีข้อมูล Section — กรอกด้านล่างแล้วกด «บันทึก Section นี้»
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
                                    @foreach ($faculties as [$code, $name])
                                        <label class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-amber-50 cursor-pointer text-sm">
                                            <input type="checkbox" class="fac-checkbox accent-amber-700" value="{{ $code }}">
                                            <span><span class="font-semibold text-[#5C2E1F]">{{ $code }}</span> : {{ $name }}</span>
                                        </label>
                                    @endforeach
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
                                    class="eva-hint-field w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <p class="text-xs text-[#7A4A3A]/70 mt-1">กรอกจำนวนนักศึกษาที่เข้าประเมินรายวิชาใน Section นี้</p>
                            </div>
                            <div class="relative">
                                <label for="evaluationscore" class="block text-sm font-medium mb-1 text-[#5C2E1F]">ผลการประเมินรายวิชาโดยนักศึกษา</label>
                                <input id="evaluationscore" type="number" min="1" max="5" step="0.01"
                                    class="eva-hint-field w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <p class="text-xs text-[#7A4A3A]/80 mt-1">ไม่เกิน 5 คะแนน — ดูผลประเมินได้ที่ <a href="https://reg.kku.ac.th" target="_blank" rel="noopener noreferrer" class="text-[#8B4513] underline hover:text-[#5C2E1F]">reg.kku.ac.th</a></p>
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

                    <div class="flex gap-3 pt-2">
                        <button type="submit" id="btn-submit" class="px-6 py-2.5 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800">
                            <i data-lucide="send" class="w-4 h-4 inline mr-1"></i>{{ isset($reportId) ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล' }}
                        </button>
                        <a id="btn-cancel" href="{{ route('grade-reports.my') }}" class="px-6 py-2.5 border border-amber-400 text-amber-800 rounded-lg text-sm font-medium hover:bg-amber-50 inline-flex items-center">ยกเลิก</a>
                    </div>
                </form>
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
    <script src="{{ asset('js/templade-form.js') }}"></script>
    <script>
    (function() {
        const reportId = @json($reportId ?? null);
        const teacherHelpImageUrl = @json($teacherHelpImageUrl);

        initTempladeForm({ teacherHelpImageUrl });

        if (reportId) {
            fetch(`/api/grade-reports/${reportId}?role=instructor`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.json())
                .then(populateFormFromRecord)
                .catch(() => showToast('โหลดข้อมูลไม่สำเร็จ', 'error'));
        } else {
            const reportDate = document.getElementById('report-date');
            if (reportDate && !reportDate.value) reportDate.value = new Date().toISOString().slice(0, 10);
            const rangeA = document.getElementById('range-a-max');
            if (rangeA && !rangeA.value) rangeA.value = '100';
        }

        document.getElementById('grade-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = collectGradeReportPayload();
            if (!payload.subject_code || !payload.subject) {
                showToast('กรุณากรอกรหัสวิชาและชื่อวิชา', 'error');
                return;
            }
            if (payload.selecttype === 1 && !payload.programid) {
                showToast('กรุณาเลือกหลักสูตร (วิชาในหลักสูตร)', 'error');
                return;
            }
            if (payload.reasonid === 1 && !payload.reason) {
                showToast('กรุณาเลือกวิชาที่ตัดเกรดร่วมกับอย่างน้อย 1 วิชา', 'error');
                return;
            }
            const rangeError = validateGradeRanges();
            if (rangeError) {
                showToast(rangeError, 'error');
                return;
            }
            if (!payload.grade_stds?.length) {
                showToast('กรุณาเพิ่มข้อมูลจำนวนนักศึกษาอย่างน้อย 1 Section', 'error');
                return;
            }

            const btn = document.getElementById('btn-submit');
            const cancelLink = document.getElementById('btn-cancel');
            const form = document.getElementById('grade-form');
            btn.disabled = true;
            if (cancelLink) cancelLink.classList.add('pointer-events-none', 'opacity-40');
            form.querySelectorAll('input, select, textarea, button').forEach((el) => {
                if (el !== btn) el.disabled = true;
            });

            showSaveProcessing(reportId ? 'update' : 'create');

            let result;
            try {
                if (reportId) {
                    payload.__backendId = String(reportId);
                    result = await window.dataSdk.update(payload);
                } else {
                    result = await window.dataSdk.create(payload);
                }
            } catch (err) {
                result = { isOk: false, error: err?.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ' };
            }

            if (result.isOk) {
                const successMsg = reportId
                    ? 'แก้ไขแบบรายงานผลการสอบไล่ของท่านเรียบร้อยแล้ว'
                    : 'บันทึกแบบรายงานผลการสอบไล่ของท่านเรียบร้อยแล้ว';
                showSaveSuccess(successMsg);
                await new Promise((r) => setTimeout(r, reportId ? 2200 : 2800));

                hideSaveOverlay();
                restoreFormAfterSave(btn, form, cancelLink);

                if (!reportId) {
                    const teacherDefault = document.getElementById('teacher-input')?.dataset.defaultTeacher || '';
                    form.reset();
                    document.getElementById('report-date').value = new Date().toISOString().slice(0, 10);
                    document.getElementById('range-a-max').value = '100';
                    document.getElementById('teacher-input').value = teacherDefault;
                    initTempladeForm({ teacherHelpImageUrl });
                    resetJointGradeSubjects();
                    resetSectionStdRows();
                    if (typeof renderFacTags === 'function') renderFacTags();
                    if (typeof updateGradeRangeColumnHeaders === 'function') updateGradeRangeColumnHeaders();
                } else {
                    window.location.href = '{{ route('grade-reports.my') }}';
                }
            } else {
                showSaveError(result.error || 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
                restoreFormAfterSave(btn, form, cancelLink);
            }
        });

        function restoreFormAfterSave(btn, form, cancelLink) {
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="send" class="w-4 h-4 inline mr-1"></i>${reportId ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล'}`;
            if (cancelLink) cancelLink.classList.remove('pointer-events-none', 'opacity-40');
            form.querySelectorAll('input, select, textarea, button').forEach((el) => {
                el.disabled = false;
            });
            lucide.createIcons();
        }

        function showSaveProcessing(mode) {
            const overlay = document.getElementById('save-overlay');
            document.getElementById('save-overlay-loading').classList.remove('hidden');
            document.getElementById('save-overlay-success').classList.add('hidden');
            document.getElementById('save-overlay-error').classList.add('hidden');
            document.getElementById('save-overlay-title').textContent =
                mode === 'update' ? 'กำลังบันทึกการแก้ไข...' : 'กำลังบันทึกข้อมูล...';
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function showSaveSuccess(message) {
            document.getElementById('save-overlay-loading').classList.add('hidden');
            document.getElementById('save-overlay-error').classList.add('hidden');
            document.getElementById('save-overlay-success-msg').textContent = message;
            document.getElementById('save-overlay-success').classList.remove('hidden');
            lucide.createIcons();
        }

        function showSaveError(message) {
            document.getElementById('save-overlay-loading').classList.add('hidden');
            document.getElementById('save-overlay-success').classList.add('hidden');
            document.getElementById('save-overlay-error-msg').textContent = message;
            document.getElementById('save-overlay-error').classList.remove('hidden');
            lucide.createIcons();
        }

        function hideSaveOverlay() {
            document.getElementById('save-overlay').classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.getElementById('save-overlay-error-close')?.addEventListener('click', hideSaveOverlay);

        function showToast(msg, type='success') {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = `fixed bottom-6 right-6 px-5 py-3 rounded-lg shadow-lg text-sm font-medium no-print z-50 ${type==='error'?'bg-red-600 text-white':'bg-green-700 text-white'}`;
            t.classList.remove('hidden');
            setTimeout(() => t.classList.add('hidden'), 3000);
        }

        lucide.createIcons();
    })();
    </script>
@endpush
