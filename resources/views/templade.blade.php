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
    #subject-suggestions { max-height: 14rem; overflow-y: auto; }
    #subject-suggestions button:hover { background: #fdf6f0; }
    #eva-hint-popover { display: none; z-index: 9999; }
    #eva-hint-popover.is-visible { display: block; }
    #eva-hint-popover img { max-width: min(100vw - 2rem, 420px); height: auto; border-radius: 0.5rem; border: 1px solid #e8cdb5; box-shadow: 0 8px 24px rgba(92,46,31,.18); background: #fff; }
    #grade-boundary-hint { color: #92400e; font-weight: 500; }
    .fac-dropdown-panel { max-height: 16rem; overflow-y: auto; }
    .fac-tag { background: #FAF0E6; border: 1px solid #E8C4B8; color: #5C2E1F; }
    #student-grade-table th { background: linear-gradient(180deg, #fdf6f0 0%, #f5e6d8 100%); }
    #student-grade-table .grade-range-col { font-size: 0.65rem; line-height: 1.2; color: #8B4513; font-weight: 500; }
    #student-grade-table input[type=number] { min-width: 3rem; }
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
                                class="absolute left-0 right-0 top-full mt-1 z-30 hidden bg-white border border-amber-300 rounded-lg shadow-lg text-sm min-w-[14rem]"></div>
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
                            <label for="programid" class="block text-sm font-medium mb-1 text-[#5C2E1F]">หลักสูตร</label>
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
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">อาจารย์ผู้สอน</label>
                            <input id="teacher-input" type="text" readonly
                                data-default-teacher="{{ $staffDisplayName }}"
                                class="w-full border border-amber-200 rounded px-3 py-2 text-sm bg-gray-50 text-gray-800"
                                value="{{ $staffDisplayName }}">
                            <p class="text-xs text-[#7A4A3A]/80 mt-1">แสดงจากข้อมูลบุคลากรในระบบ (คำนำหน้าชื่อ ชื่อ-นามสกุล)</p>
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

                    <div class="bg-white border border-amber-200 rounded-lg p-4 space-y-2">
                        <p class="text-sm font-semibold text-[#5C2E1F]">หมายเหตุ</p>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="reasonid" value="1" class="accent-amber-700"> ซ้อนวิชากับ <input id="std-i1" type="text" maxlength="8" class="border border-amber-200 rounded px-2 py-1 text-sm flex-1" placeholder="รหัสวิชา"></label>
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

                    <div class="rounded-xl border border-amber-200 bg-[#FFFBF7] p-5 space-y-5 shadow-sm">
                        <h3 class="font-bold text-[#5C2E1F] flex items-center gap-2 text-base border-b border-amber-200 pb-3">
                            <i data-lucide="users" class="w-5 h-5 text-[#8B4513]"></i>
                            กรอกจำนวนนักศึกษา
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">กลุ่ม (Section)</label>
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
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" id="btn-submit" class="px-6 py-2.5 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800">
                            <i data-lucide="send" class="w-4 h-4 inline mr-1"></i>{{ isset($reportId) ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล' }}
                        </button>
                        <a href="{{ route('grade-reports.my') }}" class="px-6 py-2.5 border border-amber-400 text-amber-800 rounded-lg text-sm font-medium hover:bg-amber-50 inline-flex items-center">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </section>

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
            const rangeError = validateGradeRanges();
            if (rangeError) {
                showToast(rangeError, 'error');
                return;
            }

            const btn = document.getElementById('btn-submit');
            btn.disabled = true;
            btn.textContent = 'กำลังบันทึก...';

            let result;
            if (reportId) {
                payload.__backendId = String(reportId);
                result = await window.dataSdk.update(payload);
            } else {
                result = await window.dataSdk.create(payload);
            }

            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="send" class="w-4 h-4 inline mr-1"></i>${reportId ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล'}`;
            lucide.createIcons();

            if (result.isOk) {
                showToast(reportId ? 'บันทึกการแก้ไขเรียบร้อย' : 'บันทึกแบบรายงานผลการสอบเรียบร้อย');
                if (!reportId) {
                    const teacherDefault = document.getElementById('teacher-input')?.dataset.defaultTeacher || '';
                    document.getElementById('grade-form').reset();
                    document.getElementById('report-date').value = new Date().toISOString().slice(0, 10);
                    document.getElementById('range-a-max').value = '100';
                    document.getElementById('teacher-input').value = teacherDefault;
                    initTempladeForm({ teacherHelpImageUrl });
                    if (typeof renderFacTags === 'function') renderFacTags();
                    if (typeof updateGradeRangeColumnHeaders === 'function') updateGradeRangeColumnHeaders();
                } else {
                    setTimeout(() => { window.location.href = '{{ route('grade-reports.my') }}'; }, 1200);
                }
            } else {
                showToast(result.error || 'เกิดข้อผิดพลาด', 'error');
            }
        });

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
