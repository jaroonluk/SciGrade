@extends('layouts.scigrad')

@section('title', 'พิมพ์รายงาน — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">ตรวจสอบรายวิชา</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">พิมพ์รายงาน</span>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <h2 class="text-xl font-bold text-[#5C2E1F] mb-2">แบบรายงานผลการสอบไล่สำหรับเจ้าหน้าที่</h2>
    <p class="text-sm text-[#7A4A3A]/80 mb-6">
        เลือกสาขาวิชา ภาค/ปีการศึกษา และสถานะ —
        ระบบพิมพ์ตาม <strong class="text-[#5C2E1F]">รหัสวิชาในสังกัดสาขา</strong> ที่ตั้งไว้
        โดยไม่จำกัดว่าใครเป็นผู้กรอก
    </p>

    @error('export')
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('grade-reports.reports.export') }}" class="form-section rounded-xl p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-[#5C2E1F] mb-1" for="report-department-id">สาขาวิชา *</label>
            <select name="department_id" id="report-department-id" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                @foreach ($departments as $dept)
                    <option value="{{ $dept->department_id }}" @selected(old('department_id', $initialDepartmentId ?? null) == $dept->department_id)>
                        {{ $dept->department_name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1.5 text-xs text-[#7A4A3A] leading-relaxed">
                เลือกสาขาที่ต้องการพิมพ์ — ระบบจะดึงเฉพาะ
                <strong class="text-[#5C2E1F]">รหัสวิชาในสังกัดสาขานั้น</strong>
                ตามเงื่อนไขที่ตั้งไว้ (ไม่ขึ้นกับว่าใครเป็นผู้กรอก)
            </p>
        </div>

        @include('partials.department-code-patterns', [
            'patternsByDepartment' => $patternsByDepartment,
            'initialDepartmentId' => $initialDepartmentId ?? $departments->first()?->department_id,
            'selectName' => 'department_id',
            'panelId' => 'faculty-report-dept-patterns',
            'panelTitle' => 'รหัสวิชาในสังกัดสาขาวิชา',
            'countLabel' => 'จำนวนเงื่อนไขรหัส',
            'helpText' => 'รายการด้านล่างคือรหัสวิชา/รูปแบบรหัสที่อยู่ในสังกัดสาขาที่เลือก — รายงานจะพิมพ์เฉพาะวิชาที่รหัสตรงเงื่อนไขเหล่านี้',
        ])

        <div>
            <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ระดับการศึกษา *</label>
            <select name="education_level" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                <option value="bachelor" @selected(old('education_level') === 'bachelor')>ปริญญาตรี</option>
                <option value="master" @selected(old('education_level') === 'master')>ปริญญาโท</option>
                <option value="doctoral" @selected(old('education_level') === 'doctoral')>ปริญญาเอก</option>
                <option value="graduate" @selected(old('education_level', 'graduate') === 'graduate')>บัณฑิตศึกษา (โท+เอก)</option>
                <option value="all" @selected(old('education_level') === 'all')>รวมทั้งหมด</option>
            </select>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                <select name="term" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">ทุกภาค</option>
                    <option value="1" @selected(old('term', $term) == 1)>ภาคต้น</option>
                    <option value="2" @selected(old('term', $term) == 2)>ภาคปลาย</option>
                    <option value="3" @selected(old('term', $term) == 3)>ภาคการศึกษาพิเศษ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                <select name="year" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">ทุกปี</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected(old('year', $year) == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @php
            $selectedReportStatus = (string) old('report_status', '1');
            $reportStatusOptions = [
                ['value' => '0', 'label' => 'ส่งแล้ว', 'hint' => 'อาจารย์ส่งรายงานแล้ว รอสาขานำเข้าที่ประชุม', 'color' => 'text-amber-800'],
                ['value' => '4', 'label' => 'นำเข้าที่ประชุมสาขา', 'hint' => 'นำเข้าวาระแล้ว รอผลมติที่ประชุมสาขา', 'color' => 'text-indigo-800'],
                ['value' => '1', 'label' => 'ผ่านการรับรองผลสอบ (ที่ประชุมสาขาวิชา)', 'hint' => 'ผ่านที่ประชุมสาขาแล้ว — รอ Admin กลางตรวจ', 'color' => 'text-sky-800'],
                ['value' => '3', 'label' => 'ตรวจแล้ว', 'hint' => 'Admin กลางตรวจแล้ว — รอผ่านที่ประชุมกรรมการคณะฯ', 'color' => 'text-orange-800'],
                ['value' => '2', 'label' => 'ผ่านที่ประชุมกรรมการคณะฯ', 'hint' => 'รับรองผลสอบระดับคณะเรียบร้อย', 'color' => 'text-green-800'],
                ['value' => '-1', 'label' => 'ส่งกลับแก้ไข', 'hint' => 'ส่งกลับให้อาจารย์แก้ไขและส่งใหม่', 'color' => 'text-red-800'],
            ];
        @endphp
        <div>
            <p class="text-sm font-medium text-[#5C2E1F] mb-1">รูปแบบรายงาน *</p>
            <p class="text-xs text-[#7A4A3A]/80 mb-3">เลือกสถานะเดียวกับหน้าสถานะการส่งเกรด — ระบบพิมพ์เฉพาะรายวิชาในสถานะนั้น</p>
            <div class="grid gap-2">
                @foreach ($reportStatusOptions as $option)
                    <label class="flex items-start gap-3 rounded-xl border border-amber-200 bg-white px-3 py-2.5 text-sm cursor-pointer hover:border-amber-400 hover:bg-amber-50/40 has-[:checked]:border-[#8B4513] has-[:checked]:bg-amber-50">
                        <input type="radio"
                               name="report_status"
                               value="{{ $option['value'] }}"
                               @checked($selectedReportStatus === $option['value'])
                               required
                               class="mt-1 accent-amber-700">
                        <span class="min-w-0">
                            <span class="block font-semibold {{ $option['color'] }}">{{ $option['label'] }}</span>
                            <span class="block text-xs text-[#7A4A3A]/80 mt-0.5">{{ $option['hint'] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <p class="text-sm font-medium text-[#5C2E1F] mb-2">รูปแบบไฟล์ *</p>
            <label class="inline-flex items-center gap-2 text-sm mr-6">
                <input type="radio" name="format" value="pdf" @checked(old('format', 'pdf') === 'pdf') class="accent-amber-700"> PDF
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="radio" name="format" value="word" @checked(old('format') === 'word') class="accent-amber-700"> Word (.docx)
            </label>
        </div>

        <div class="flex gap-3 flex-wrap">
            <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">ส่งออกรายงาน</button>
            <a href="{{ route('faculty-admin.reviews.index') }}" class="px-5 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">กลับ</a>
        </div>
    </form>
</div>
@endsection
