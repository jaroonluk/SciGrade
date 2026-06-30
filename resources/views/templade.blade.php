@extends('layouts.scigrad')

@section('title', isset($reportId) ? 'แก้ไขรายงานผลสอบ — SciGrad' : 'กรอกผลสอบ — SciGrad')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">{{ isset($reportId) ? 'แก้ไขรายงาน' : 'กรอกผลสอบ' }}</span>
@endsection

@push('styles')
<style>
    input:focus, select:focus, textarea:focus { outline: none; border-color: #c4856c; box-shadow: 0 0 0 2px rgba(196,133,108,0.2); }
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
                <h2 class="text-lg font-bold mb-4 text-[#5C2E1F]">{{ isset($reportId) ? 'แก้ไขแบบรายงานผลการสอบไล่' : 'สร้างแบบรายงานผลการสอบไล่' }}</h2>
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

        initTempladeForm();

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
                    document.getElementById('grade-form').reset();
                    document.getElementById('report-date').value = new Date().toISOString().slice(0, 10);
                    document.getElementById('range-a-max').value = '100';
                    initTempladeForm();
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
