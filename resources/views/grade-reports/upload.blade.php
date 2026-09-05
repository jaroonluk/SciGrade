@extends('layouts.scigrad')

@section('title', 'อัปโหลดไฟล์ — SciGrade')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">อัปโหลดไฟล์</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="form-section rounded-xl p-6">
        <h2 class="text-lg font-bold text-[#5C2E1F] mb-2">อัปโหลดไฟล์จากสำนักทะเบียน</h2>
        <p class="text-sm text-[#7A4A3A]/80 mb-6">
            เลือกภาคการศึกษาและปีการศึกษาก่อนอัปโหลด ระบบรองรับเฉพาะไฟล์ PDF ใบส่งผลการศึกษาจากสำนักทะเบียน มข.
            เมื่อบันทึกรายงานผลสอบ ไฟล์นี้จะถูกแนบเป็น <strong class="text-[#5C2E1F]">ใบส่งผลการศึกษา (REG)</strong> ของอาจารย์โดยอัตโนมัติ
        </p>

        <form method="POST" action="{{ route('grade-reports.upload.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา *</label>
                    <select name="term" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="1" @selected(old('term', $term) == 1)>ภาคต้น</option>
                        <option value="2" @selected(old('term', $term) == 2)>ภาคปลาย</option>
                        <option value="3" @selected(old('term', $term) == 3)>ภาคการศึกษาพิเศษ</option>
                    </select>
                    @error('term')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา *</label>
                    <select name="year" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected(old('year', $year) == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                    @error('year')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">เลือกไฟล์ PDF *</label>
                <input type="file" name="grade_file" accept=".pdf,application/pdf" required
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                <p class="text-xs text-[#7A4A3A]/80 mt-2">
                    ระบบจะ<strong class="text-[#5C2E1F]">อ่านข้อมูลจากเนื้อหาในไฟล์ PDF</strong>
                    (รหัสวิชา, กลุ่มเรียน, คณะนักศึกษา, ตารางสรุปเกรด) โดยไม่ผูกกับชื่อไฟล์
                    รองรับเฉพาะใบส่งผลการศึกษาจากระบบทะเบียน มข.
                </p>
                @error('grade_file')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 flex-wrap">
                <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">อัปโหลดและดำเนินการต่อ</button>
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
@endsection
