@extends('layouts.scigrad')

@section('title', 'หน้าหลัก — SciGrad')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-[#5C2E1F]">ยินดีต้อนรับ, {{ $staffDisplayName }}</h2>
        <p class="text-[#7A4A3A]/80 mt-1">เลือกบทบาทและเมนูงานที่ต้องการดำเนินการ</p>
    </div>

    <div class="form-section rounded-xl p-5 mb-8 no-print">
        <p class="text-sm font-semibold text-[#5C2E1F] mb-3">เลือกบทบาทการใช้งาน</p>
        <form method="POST" action="{{ route('role.set') }}" class="flex flex-wrap gap-3">
            @csrf
            @foreach ([
                'instructor' => 'อาจารย์',
                'dept_admin' => 'Admin สาขา',
                'faculty_admin' => 'Admin กลาง',
            ] as $value => $label)
                <button type="submit" name="role" value="{{ $value }}"
                    class="px-5 py-2.5 rounded-lg text-sm font-medium border transition
                    {{ $role === $value
                        ? 'bg-[#8B4513] text-white border-[#8B4513]'
                        : 'bg-white text-[#5C2E1F] border-[#E8C4B8] hover:border-[#C4725C]' }}">
                    {{ $label }}
                </button>
            @endforeach
        </form>
    </div>

    @if ($role === 'instructor')
        <h3 class="text-lg font-bold text-[#5C2E1F] mb-4 flex items-center gap-2">
            <i data-lucide="user" class="w-5 h-5"></i> เมนูอาจารย์
        </h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <a href="{{ route('grade-reports.create') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513] shrink-0">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">กรอกผลสอบ</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">กรอกข้อมูลรายงานผลการสอบด้วยตนเอง</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.upload') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="upload" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">อัปโหลดไฟล์</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">อัปโหลดไฟล์แล้วเข้าไปตรวจสอบ/แก้ไขก่อนบันทึก</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.my') }}" class="menu-card rounded-xl p-5 block sm:col-span-2">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="folder-open" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">จัดการรายงานของฉัน</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">แก้ไข / ลบ / พิมพ์ PDF รายงานผลสอบของตนเอง</p>
                    </div>
                </div>
            </a>
        </div>
    @endif

    @if ($role === 'dept_admin')
        <h3 class="text-lg font-bold text-[#5C2E1F] mb-4 flex items-center gap-2">
            <i data-lucide="shield-check" class="w-5 h-5"></i> เมนู Admin สาขา
        </h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <a href="{{ route('grade-reports.approve') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="list-checks" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">ตรวจสอบและอนุมัติ (สาขา)</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">อนุมัติหรือส่งกลับแก้ไขในระดับสาขา</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.print.summary') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="printer" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">พิมพ์ใบส่งเกรดภาพรวมสาขา</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">พิมพ์รายงานสรุปทุกสถานะในสาขา</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.reports') }}" class="menu-card rounded-xl p-5 block sm:col-span-2">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">ดูรายงานตามสถานะ</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">ดูรายงานทุกสถานะ (รออนุมัติ / สาขาอนุมัติ / คณะอนุมัติ / ส่งกลับ)</p>
                    </div>
                </div>
            </a>
        </div>
    @endif

    @if ($role === 'faculty_admin')
        <h3 class="text-lg font-bold text-[#5C2E1F] mb-4 flex items-center gap-2">
            <i data-lucide="building-2" class="w-5 h-5"></i> เมนู Admin กลาง
        </h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <a href="{{ route('grade-reports.approve') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="badge-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">อนุมัติระดับคณะ</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">ตรวจสอบรายการที่สาขาอนุมัติแล้ว</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.reports') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="layers" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">ดูรายงานทุกสาขา</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">ดูรายงานจากทุกสาขา กรองตามสถานะ</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.print.summary') }}" class="menu-card rounded-xl p-5 block sm:col-span-2">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">พิมพ์รายงานรวม</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">พิมพ์รายงานรวมทุกสาขา หรือเลือกทีละสาขา</p>
                    </div>
                </div>
            </a>
        </div>
    @endif
</div>
@endsection
