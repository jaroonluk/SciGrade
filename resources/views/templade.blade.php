<!doctype html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ระบบรายงานผลการสอบ SciGrad</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Thai', sans-serif; }
        .canva-header { background: linear-gradient(135deg, #8B4513 0%, #A0522D 50%, #C4725C 100%); }
        header .canva-text { color: #FFFBF7; }
        .tab-active { border-bottom: 3px solid #a0522d; color: #a0522d; font-weight: 600; }
        .form-section { background: #fdf6f0; border: 1px solid #e8cdb5; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #c4856c; box-shadow: 0 0 0 2px rgba(196,133,108,0.2); }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-dept { background: #e0d4c8; color: #6b3a1f; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="min-h-screen bg-[#FAF0E6]">
    <header class="canva-header shadow-md no-print">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/90 rounded-full flex items-center justify-center">
                    <i data-lucide="graduation-cap" class="w-6 h-6 text-amber-900"></i>
                </div>
                <div>
                    <h1 class="canva-text text-lg font-bold">ระบบรายงานผลการสอบ SciGrad</h1>
                    <p class="canva-text text-sm opacity-90">คณะวิทยาศาสตร์ มหาวิทยาลัยขอนแก่น</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @auth
                    <span class="text-sm text-white/90 hidden sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded px-3 py-1.5 text-sm bg-white/90 text-amber-900 font-medium hover:bg-white">ออกจากระบบ</button>
                    </form>
                @endauth
                <label class="text-sm text-white font-medium">Role:</label>
                <select id="role-select" class="rounded px-3 py-1.5 text-sm bg-white text-gray-800 font-medium border-0">
                    <option value="instructor">อาจารย์</option>
                    <option value="dept_admin">Admin สาขา</option>
                    <option value="faculty_admin">Admin กลาง</option>
                </select>
            </div>
        </div>
    </header>

    <nav class="no-print bg-white border-b border-amber-200">
        <div class="max-w-7xl mx-auto px-4 flex gap-6">
            <button class="nav-tab tab-active py-3 px-2 text-sm" data-tab="entry"><i data-lucide="edit-3" class="w-4 h-4 inline mr-1"></i>กรอกผลสอบ</button>
            <button class="nav-tab py-3 px-2 text-sm text-gray-500" data-tab="queue"><i data-lucide="list-checks" class="w-4 h-4 inline mr-1"></i>รายการรอดำเนินการ</button>
            <button class="nav-tab py-3 px-2 text-sm text-gray-500" data-tab="reports"><i data-lucide="bar-chart-3" class="w-4 h-4 inline mr-1"></i>รายงาน</button>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-6">
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

        <section id="tab-entry">
            <div class="form-section rounded-lg p-5 mb-4">
                <h2 class="text-lg font-bold mb-4 text-[#5C2E1F]">สร้างแบบรายงานผลการสอบไล่</h2>
                <form id="grade-form" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">ประเภทรายวิชา *</label>
                            <select id="selecttype" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                                <option value="1">วิชาในหลักสูตร</option>
                                <option value="2">รายวิชาบริการ</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">รหัสวิชา *</label>
                            <input id="subject-code" type="text" maxlength="10" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" placeholder="เช่น SC101">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">ชื่อวิชา *</label>
                            <input id="subject-name" type="text" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" placeholder="PHYSICAL SCIENCE">
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
                                <option value="5">ปริญญาโท</option>
                                <option value="7">ปริญญาเอก</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">หลักสูตร</label>
                            <input id="programid" type="text" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" placeholder="เลือกหรือระบุหลักสูตร">
                        </div>
                    </div>

                    <div id="service-degree-field" class="hidden">
                        <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">ระดับการศึกษา</label>
                        <input type="text" value="ปริญญาตรี" readonly class="w-full md:w-1/2 border border-amber-200 rounded px-3 py-2 text-sm bg-gray-100 text-gray-700">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">อาจารย์ผู้สอน</label>
                            <input id="teacher-input" type="text" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" value="{{ auth()->user()->name ?? '' }}">
                            <p class="text-xs text-red-600 mt-1">* หากมีผู้สอนหลายคน คั่นด้วยเครื่องหมายจุลภาค (,)</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">วันที่บันทึก</label>
                            <input id="report-date" type="date" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">ค่าเฉลี่ย</label>
                            <input id="mean-score" type="text" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">ส่วนเบี่ยงเบนมาตรฐาน (SD)</label>
                            <input id="sd-score" type="text" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" placeholder="0.00">
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
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">จำนวนนักศึกษาที่เข้าประเมิน</label>
                            <input id="totalnumstdevz" type="number" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1 text-[#5C2E1F]">ผลการประเมินรายวิชา (ไม่เกิน 5)</label>
                            <input id="totalevaluationscore" type="number" min="1" max="5" step="0.01" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-[#5C2E1F] mb-2">ช่วงคะแนนของแต่ละเกรด</p>
                        <div class="flex flex-wrap gap-4 mb-3">
                            <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="intflag" value="0" checked class="accent-amber-700"> มีทศนิยม</label>
                            <label class="flex items-center gap-1.5 text-sm"><input type="radio" name="intflag" value="1" class="accent-amber-700"> เป็นจำนวนเต็ม</label>
                        </div>
                        <div class="bg-white border border-amber-200 rounded-lg p-4 max-w-md">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-amber-200">
                                        <th class="py-2 text-left font-semibold text-[#5C2E1F] w-16">เกรด</th>
                                        <th class="py-2 text-left font-semibold text-[#5C2E1F]">ช่วงคะแนน</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ([['a','A'],['bp','B+'],['b','B'],['cp','C+'],['c','C'],['dp','D+'],['d','D'],['f','F']] as [$key, $label])
                                        <tr class="border-b border-amber-100 last:border-0">
                                            <td class="py-2 font-medium">{{ $label }}</td>
                                            <td class="py-2">
                                                <div class="flex items-center gap-2">
                                                    <input id="range-{{ $key }}-max" type="text" class="grade-range-input w-20 border border-amber-200 rounded px-2 py-1.5 text-sm text-center {{ $key === 'a' ? '' : 'bg-gray-100' }}" value="{{ $key === 'a' ? '100' : '' }}" {{ $key === 'a' ? 'readonly' : '' }}>
                                                    <span class="text-xs text-gray-500">-</span>
                                                    <input id="range-{{ $key }}-min" type="text" class="grade-range-input w-20 border border-amber-200 rounded px-2 py-1.5 text-sm text-center" placeholder="">
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white border border-amber-200 rounded-lg p-4 space-y-4">
                        <h3 class="font-semibold text-sm text-[#5C2E1F]">กรอกจำนวนนักศึกษา (grade_std)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">กลุ่ม (Section)</label>
                                <select id="section-input" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                                    @for ($i = 1; $i <= 50; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">คณะ (เลือกได้หลายคณะ)</label>
                                <select id="fac-select" multiple class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white h-28">
                                    @foreach ($faculties as [$code, $name])
                                        <option value="{{ $code }}">{{ $code }} : {{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium mb-2">ประเภทรายวิชา (กลุ่มเรียน)</p>
                            <div class="flex flex-wrap gap-3 text-sm">
                                @foreach ([1=>'ภาคปกติ',2=>'โครงการพิเศษ',3=>'ก้าวหน้า',4=>'ปกติ นานาชาติ',5=>'โครงการพิเศษ นานาชาติ'] as $v => $l)
                                    <label class="flex items-center gap-1"><input type="radio" name="type_course" value="{{ $v }}" @checked($v===1) class="accent-amber-700"> {{ $l }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">
                            @foreach (['a'=>'A','bp'=>'B+','b'=>'B','cp'=>'C+','c'=>'C','dp'=>'D+','d'=>'D','f'=>'F','i'=>'I','s'=>'S','u'=>'U','w'=>'W'] as $key => $label)
                                <div>
                                    <label class="text-xs text-gray-600">{{ $label }}</label>
                                    <input id="count-{{ $key }}" type="number" min="0" class="w-full border border-amber-200 rounded px-2 py-1.5 text-sm text-center" value="0">
                                </div>
                            @endforeach
                        </div>
                        <div id="section-eva-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">จำนวนนักศึกษาที่เข้าประเมิน (Section)</label>
                                <input id="numstdevz" type="number" min="0" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">ผลการประเมินรายวิชา (Section)</label>
                                <input id="evaluationscore" type="number" min="1" max="5" step="0.01" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="remark-input" class="block text-sm font-medium mb-1 text-[#5C2E1F]">หมายเหตุเพิ่มเติม</label>
                        <textarea id="remark-input" rows="2" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white"></textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" id="btn-submit" class="px-6 py-2.5 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800">
                            <i data-lucide="send" class="w-4 h-4 inline mr-1"></i>บันทึกข้อมูล
                        </button>
                        <button type="reset" class="px-6 py-2.5 border border-amber-400 text-amber-800 rounded-lg text-sm font-medium hover:bg-amber-50">ล้างข้อมูล</button>
                    </div>
                </form>
            </div>
        </section>

        <section id="tab-queue" class="hidden">
            <div class="bg-white rounded-lg shadow-sm border border-amber-200 p-6">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                    <h2 class="font-bold text-[#5C2E1F]">รายการรอดำเนินการ</h2>
                    <div class="flex gap-3">
                        <select id="filter-status" class="border border-amber-300 rounded px-3 py-2 text-sm">
                            <option value="">ทุกสถานะ</option>
                            <option value="ยังไม่ผ่านกรรมการ">ยังไม่ผ่านกรรมการ</option>
                            <option value="สาขาอนุมัติ">สาขาอนุมัติ</option>
                            <option value="คณะอนุมัติ">คณะอนุมัติ</option>
                            <option value="ส่งกลับแก้ไข">ส่งกลับแก้ไข</option>
                        </select>
                        <input id="filter-subject" type="text" placeholder="ค้นหารหัสวิชา" class="border border-amber-300 rounded px-3 py-2 text-sm">
                    </div>
                </div>
                <div id="queue-list" class="space-y-3">
                    <p class="text-gray-500 text-center py-8">ไม่มีรายการ</p>
                </div>
            </div>
        </section>

        <section id="tab-reports" class="hidden">
            <div class="bg-white rounded-lg shadow-sm border border-amber-200 p-6">
                <h2 class="font-bold text-[#5C2E1F] mb-4">รายงานผลการสอบ</h2>
                <div class="flex gap-3 mb-6 flex-wrap">
                    <select id="report-term" class="border border-amber-300 rounded px-3 py-2 text-sm">
                        <option value="">ทุกภาค</option>
                        <option value="1">ภาคต้น</option>
                        <option value="2">ภาคปลาย</option>
                        <option value="3">ภาคพิเศษ</option>
                    </select>
                    <input id="report-year" type="number" placeholder="ปีการศึกษา" class="border border-amber-300 rounded px-3 py-2 text-sm w-32">
                    <button type="button" id="btn-print-report" class="px-4 py-2 bg-amber-700 text-white rounded text-sm font-medium hover:bg-amber-800">
                        <i data-lucide="printer" class="w-4 h-4 inline mr-1"></i>พิมพ์รายงาน
                    </button>
                </div>
                <div id="report-content" class="overflow-x-auto">
                    <p class="text-gray-500 text-center py-8">เลือกสาขาเพื่อดูรายงาน</p>
                </div>
            </div>
        </section>
    </main>

    <div id="toast" class="fixed bottom-6 right-6 px-5 py-3 rounded-lg shadow-lg text-sm font-medium hidden no-print z-50"></div>

    <div id="reject-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden no-print">
        <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
            <h3 class="font-bold text-lg mb-3">ระบุเหตุผลที่ส่งกลับ</h3>
            <textarea id="reject-reason" rows="3" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm mb-4" placeholder="เหตุผล..."></textarea>
            <div class="flex gap-3 justify-end">
                <button type="button" id="btn-cancel-reject" class="px-4 py-2 border rounded-lg text-sm">ยกเลิก</button>
                <button type="button" id="btn-confirm-reject" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium">ยืนยันส่งกลับ</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/templade-data-sdk.js') }}"></script>
    <script src="{{ asset('js/templade-form.js') }}"></script>
    <script>
    (function() {
        let currentRole = 'instructor';
        let allRecords = [];
        let pendingRejectKey = null;

        const handler = { onDataChanged(data) { allRecords = data; renderQueue(); renderReport(); } };
        window.dataSdk.init(handler);

        initTempladeForm();

        document.getElementById('role-select').addEventListener('change', e => {
            currentRole = e.target.value;
            window.dataSdk.setRole(currentRole);
        });

        document.querySelectorAll('.nav-tab').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.nav-tab').forEach(b => { b.classList.remove('tab-active'); b.classList.add('text-gray-500'); });
                btn.classList.add('tab-active');
                btn.classList.remove('text-gray-500');
                const tab = btn.dataset.tab;
                document.getElementById('tab-entry').classList.toggle('hidden', tab !== 'entry');
                document.getElementById('tab-queue').classList.toggle('hidden', tab !== 'queue');
                document.getElementById('tab-reports').classList.toggle('hidden', tab !== 'reports');
            });
        });

        document.getElementById('grade-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = collectGradeReportPayload();
            if (!payload.subject_code || !payload.subject) {
                showToast('กรุณากรอกรหัสวิชาและชื่อวิชา', 'error');
                return;
            }

            const btn = document.getElementById('btn-submit');
            btn.disabled = true;
            btn.textContent = 'กำลังบันทึก...';

            const result = await window.dataSdk.create(payload);

            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="send" class="w-4 h-4 inline mr-1"></i>บันทึกข้อมูล';
            lucide.createIcons();

            if (result.isOk) {
                showToast('บันทึกแบบรายงานผลการสอบเรียบร้อย');
                document.getElementById('grade-form').reset();
                document.getElementById('report-date').value = new Date().toISOString().slice(0, 10);
                document.getElementById('range-a-max').value = '100';
            } else {
                showToast(result.error || 'เกิดข้อผิดพลาด', 'error');
            }
        });

        function renderQueue() {
            const sf = document.getElementById('filter-status').value;
            const subjectQ = document.getElementById('filter-subject').value.trim().toLowerCase();
            const filtered = allRecords.filter(r => {
                if (sf && r.status !== sf) return false;
                if (subjectQ && !(`${r.subject_code}`.toLowerCase().includes(subjectQ))) return false;
                return true;
            });
            const container = document.getElementById('queue-list');
            if (!filtered.length) { container.innerHTML = '<p class="text-gray-500 text-center py-8">ไม่มีรายการ</p>'; return; }

            container.innerHTML = filtered.map((g) => {
                const sc = g.status==='ยังไม่ผ่านกรรมการ'?'status-pending':g.status==='สาขาอนุมัติ'?'status-dept':g.status==='คณะอนุมัติ'?'status-approved':'status-rejected';
                const canApprove = (currentRole==='dept_admin'&&g.approv===0)||(currentRole==='faculty_admin'&&g.approv===1);
                return `<div class="border border-amber-200 rounded-lg p-4 bg-white hover:shadow-sm transition">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div><span class="font-semibold">${g.subject_code} ${g.subject}</span><span class="text-gray-500 text-sm ml-2">Sec ${g.section||'-'} | ${g.semester_type} ${g.academic_year}</span></div>
                        <span class="px-2 py-0.5 rounded text-xs font-medium ${sc}">${g.status}</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">ผู้สอน: ${g.teacher||'-'} | จำนวน ${g.student_count} คน | คณะ: ${g.fac||'-'} | ส่ง ${g.submitted_at?new Date(g.submitted_at).toLocaleDateString('th-TH'):'-'}</div>
                    ${g.rejection_reason?`<div class="text-xs text-red-600 mt-0.5">เหตุผล: ${g.rejection_reason}</div>`:''}
                    ${canApprove?`<div class="flex gap-2 mt-3"><button type="button" class="btn-approve px-3 py-1.5 bg-green-600 text-white rounded text-xs font-medium" data-id="${g.__backendId}"><i data-lucide="check" class="w-3 h-3 inline mr-0.5"></i>อนุมัติ</button><button type="button" class="btn-reject px-3 py-1.5 bg-red-600 text-white rounded text-xs font-medium" data-id="${g.__backendId}"><i data-lucide="x" class="w-3 h-3 inline mr-0.5"></i>ส่งกลับ</button></div>`:''}
                </div>`;
            }).join('');
            lucide.createIcons();
            container.querySelectorAll('.btn-approve').forEach(b => b.addEventListener('click', () => approveRecord(b.dataset.id)));
            container.querySelectorAll('.btn-reject').forEach(b => b.addEventListener('click', () => { pendingRejectKey = b.dataset.id; document.getElementById('reject-modal').classList.remove('hidden'); }));
        }

        async function approveRecord(id) {
            const approv = currentRole === 'dept_admin' ? 1 : 2;
            await window.dataSdk.update({ __backendId: id, approv, role: currentRole });
            showToast('อนุมัติเรียบร้อย');
        }

        document.getElementById('btn-cancel-reject').addEventListener('click', () => { document.getElementById('reject-modal').classList.add('hidden'); pendingRejectKey=null; });
        document.getElementById('btn-confirm-reject').addEventListener('click', async () => {
            const reason = document.getElementById('reject-reason').value.trim();
            if (!reason) { showToast('กรุณาระบุเหตุผล','error'); return; }
            if (pendingRejectKey) {
                await window.dataSdk.update({ __backendId: pendingRejectKey, approv: -1, rejection_reason: reason, role: currentRole });
            }
            document.getElementById('reject-modal').classList.add('hidden');
            document.getElementById('reject-reason').value = '';
            pendingRejectKey = null;
            showToast('ส่งกลับแก้ไขเรียบร้อย');
        });

        document.getElementById('filter-status').addEventListener('change', renderQueue);
        document.getElementById('filter-subject').addEventListener('input', renderQueue);

        function renderReport() {
            const term = document.getElementById('report-term').value;
            const year = document.getElementById('report-year').value;
            const approved = allRecords.filter(r => {
                if (r.approv !== 2) return false;
                if (term && String(r.term) !== term) return false;
                if (year && String(r.year) !== year) return false;
                return true;
            });
            const container = document.getElementById('report-content');
            if (!approved.length) { container.innerHTML='<p class="text-gray-500 text-center py-8">ไม่มีรายการที่คณะอนุมัติแล้ว</p>'; return; }
            container.innerHTML = `<table class="w-full text-xs border border-amber-200"><thead class="bg-amber-50"><tr>
                <th class="px-2 py-2 text-left border-b">รหัสวิชา</th><th class="px-2 py-2 text-left border-b">ชื่อวิชา</th>
                <th class="px-2 py-2 border-b">Sec</th><th class="px-2 py-2 border-b">ภาค/ปี</th><th class="px-2 py-2 border-b">คณะ</th>
                <th class="px-2 py-2 border-b">จำนวน</th><th class="px-2 py-2 border-b">A</th><th class="px-2 py-2 border-b">B+</th>
                <th class="px-2 py-2 border-b">B</th><th class="px-2 py-2 border-b">C+</th><th class="px-2 py-2 border-b">C</th>
                <th class="px-2 py-2 border-b">D+</th><th class="px-2 py-2 border-b">D</th><th class="px-2 py-2 border-b">F</th>
                <th class="px-2 py-2 border-b">I</th><th class="px-2 py-2 border-b">S</th><th class="px-2 py-2 border-b">U</th><th class="px-2 py-2 border-b">W</th>
                <th class="px-2 py-2 border-b">ค่าเฉลี่ย</th><th class="px-2 py-2 border-b">SD</th>
            </tr></thead><tbody>${approved.map(r=>`<tr class="border-t border-amber-100 hover:bg-amber-50/50">
                <td class="px-2 py-1.5">${r.subject_code}</td><td class="px-2 py-1.5">${r.subject}</td>
                <td class="px-2 py-1.5 text-center">${r.section||''}</td>
                <td class="px-2 py-1.5 text-center">${r.semester_type} ${r.academic_year}</td>
                <td class="px-2 py-1.5 text-center">${r.fac||''}</td>
                <td class="px-2 py-1.5 text-center">${r.student_count}</td>
                <td class="px-2 py-1.5 text-center">${r.count_a}</td><td class="px-2 py-1.5 text-center">${r.count_bp}</td>
                <td class="px-2 py-1.5 text-center">${r.count_b}</td><td class="px-2 py-1.5 text-center">${r.count_cp}</td>
                <td class="px-2 py-1.5 text-center">${r.count_c}</td><td class="px-2 py-1.5 text-center">${r.count_dp}</td>
                <td class="px-2 py-1.5 text-center">${r.count_d}</td><td class="px-2 py-1.5 text-center">${r.count_f}</td>
                <td class="px-2 py-1.5 text-center">${r.count_i}</td><td class="px-2 py-1.5 text-center">${r.count_s}</td>
                <td class="px-2 py-1.5 text-center">${r.count_u}</td><td class="px-2 py-1.5 text-center">${r.count_w}</td>
                <td class="px-2 py-1.5 text-center">${r.mean_score??''}</td><td class="px-2 py-1.5 text-center">${r.sd_score??''}</td>
            </tr>`).join('')}</tbody></table>`;
        }
        document.getElementById('report-term').addEventListener('change', renderReport);
        document.getElementById('report-year').addEventListener('input', renderReport);
        document.getElementById('btn-print-report').addEventListener('click', () => { renderReport(); window.print(); });

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
</body>
</html>
