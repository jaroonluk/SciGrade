@extends('layouts.scigrad')

@section('title', 'อัปโหลดไฟล์ — SciGrad')

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
                    ชื่อไฟล์ต้องเป็นรูปแบบ <strong class="text-[#5C2E1F]">รหัสวิชา-เลขกลุ่ม.pdf</strong>
                    เช่น <code class="bg-amber-50 px-1 rounded">SC101011-01.pdf</code>
                    (รหัสวิชา + เครื่องหมายขีด + เลขกลุ่ม 2 หลัก)
                </p>
                @error('grade_file')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 flex-wrap">
                <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">อัปโหลดและดำเนินการต่อ</button>
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ยกเลิก</a>
            </div>
        </form>
    </div>

    <div class="form-section rounded-xl p-6">
        <h3 class="text-base font-bold text-[#5C2E1F] mb-3 flex items-center gap-2">
            <i data-lucide="image" class="w-5 h-5"></i>
            ตัวอย่างข้อมูลในไฟล์ที่ระบบรองรับ
        </h3>
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-[#5C2E1F] space-y-2 mb-4">
            <p><strong>ข้อมูลที่ต้องมีในไฟล์:</strong> รหัสรายวิชา, ชื่อวิชา, ชื่อผู้สอน, รหัสนักศึกษา, เกรด, ชื่อนักศึกษา</p>
            <p><strong>หมายเหตุ:</strong> ไฟล์ 1 ไฟล์ต่อ 1 กลุ่มเรียน (Section) ตามเลขท้ายชื่อไฟล์ เช่น <code class="bg-white px-1 rounded">-01</code>, <code class="bg-white px-1 rounded">-02</code></p>
            <p><strong>ดาวน์โหลดไฟล์:</strong> <a href="https://reg.kku.ac.th/" target="_blank" rel="noopener noreferrer" class="text-[#8B4513] underline hover:text-[#5C2E1F]">ระบบทะเบียน มข. (reg.kku.ac.th)</a></p>
        </div>

        <div class="rounded-lg border border-amber-200 overflow-hidden bg-white shadow-sm">
            @include('partials.registrar-grade-sample')
        </div>
        <p class="text-xs text-gray-500 mt-3 text-center">ภาพตัวอย่างเพื่ออธิบายรูปแบบข้อมูล — ไม่ใช่ไฟล์จริงจากสำนักทะเบียน</p>
    </div>
</div>
@endsection
