@extends('layouts.scigrad')

@section('title', 'พิมพ์รายงานสาขา — Admin สาขา')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('dept-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">ตรวจสอบรายวิชา</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">พิมพ์รายงาน</span>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <h2 class="text-xl font-bold text-[#5C2E1F] mb-2">แบบรายงานผลการสอบไล่สำหรับเจ้าหน้าที่</h2>
    <p class="text-sm text-[#7A4A3A]/80 mb-6">เลือกสาขาวิชาเพื่อดูเงื่อนไขรหัสที่ใช้กรองรายงาน แล้วกำหนดรูปแบบการส่งออก</p>

    @error('export')
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('dept-admin.reports.export') }}" class="form-section rounded-xl p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สาขาวิชา *</label>
            <select name="department_id" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                @foreach ($departments as $dept)
                    <option value="{{ $dept->department_id }}" @selected(old('department_id', $initialDepartmentId ?? null) == $dept->department_id)>
                        {{ $dept->department_name }}
                    </option>
                @endforeach
            </select>
        </div>

        @include('partials.department-code-patterns', [
            'patternsByDepartment' => $patternsByDepartment ?? [],
            'initialDepartmentId' => $initialDepartmentId ?? $departments->first()?->department_id,
            'selectName' => 'department_id',
            'panelId' => 'dept-report-dept-patterns',
            'helpText' => 'รายงานจะรวมเฉพาะรายวิชาที่รหัสตรงตามเงื่อนไขของสาขานี้ — ใช้ตรวจสอบก่อนเพิ่ม/ลบข้อมูลในอนาคต',
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

        <div>
            <p class="text-sm font-medium text-[#5C2E1F] mb-2">รูปแบบรายงาน *</p>
            <label class="flex items-center gap-2 text-sm mb-2">
                <input type="radio" name="report_status" value="0" @checked(old('report_status') === '0') class="accent-amber-700">
                ยังไม่ผ่านการรับรองผลสอบ (ที่ประชุมสาขาวิชา)
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="radio" name="report_status" value="1" @checked(old('report_status', '1') === '1') class="accent-amber-700">
                ผ่านการรับรองผลสอบ (ที่ประชุมสาขาวิชา)
            </label>
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
            <a href="{{ route('dept-admin.reviews.index') }}" class="px-5 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">กลับ</a>
        </div>
    </form>
</div>
@endsection
