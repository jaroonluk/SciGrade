@extends('layouts.scigrad')

@section('title', 'อัปโหลดไฟล์ — SciGrad')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">อัปโหลดไฟล์</span>
@endsection

@section('content')
<div class="max-w-xl mx-auto">
    <div class="form-section rounded-xl p-6">
        <h2 class="text-lg font-bold text-[#5C2E1F] mb-2">อัปโหลดไฟล์รายงานผลสอบ</h2>
        <p class="text-sm text-[#7A4A3A]/80 mb-6">รองรับไฟล์ CSV, Excel หรือ PDF หลังอัปโหลดระบบจะนำไปหน้ากรอกข้อมูลเพื่อตรวจสอบและแก้ไขก่อนบันทึก</p>

        <form method="POST" action="{{ route('grade-reports.upload.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">เลือกไฟล์</label>
                <input type="file" name="grade_file" accept=".csv,.xlsx,.xls,.pdf" required
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                @error('grade_file')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">อัปโหลดและดำเนินการต่อ</button>
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
@endsection
