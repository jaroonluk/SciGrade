@extends('layouts.scigrad')

@section('title', 'Download ข้อมูลรายวิชา — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">Download ข้อมูลรายวิชา</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">Download รายวิชา REG</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            ดึงรายวิชาที่เปิดสอนจากระบบ Reg เข้าตาราง grade_report_reg แล้วดูรายวิชาทั้งหมดแบบแบ่งหน้า
        </p>
    </div>

    @unless ($canConnect)
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            เชื่อมต่อฐานข้อมูล REG ไม่ได้ กรุณาตรวจสอบว่า MySQL มี database ชื่อ
            <span class="font-semibold">reg</span> บน localhost
        </div>
    @endunless

    @error('dump')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

    <div class="form-section rounded-xl p-6">
        <h3 class="font-semibold text-[#5C2E1F] mb-3">ดึงข้อมูลจาก REG</h3>
        <form method="POST" action="{{ route('faculty-admin.settings.reg-grade-dump.dump') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                <select name="term" required class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                    <option value="1" @selected((int) old('term', $term) === 1)>ภาคต้น</option>
                    <option value="2" @selected((int) old('term', $term) === 2)>ภาคปลาย</option>
                    <option value="3" @selected((int) old('term', $term) === 3)>ภาคการศึกษาพิเศษ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                <select name="year" required class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected((int) old('year', $year) === $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]"
                @disabled(! $canConnect)
                onclick="return confirm('ดึงรายวิชาจาก REG ตามภาค/ปีที่เลือก และบันทึกลง grade_report_reg?')">
                Download จาก REG
            </button>
        </form>
    </div>

    <div class="form-section rounded-xl p-6">
        <h3 class="font-semibold text-[#5C2E1F] mb-3">ดูรายวิชาในระบบ</h3>
        <form method="GET" action="{{ route('faculty-admin.settings.reg-grade-dump.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                <select name="term" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                    <option value="1" @selected($term === 1)>ภาคต้น</option>
                    <option value="2" @selected($term === 2)>ภาคปลาย</option>
                    <option value="3" @selected($term === 3)>ภาคการศึกษาพิเศษ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                <select name="year" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[14rem]">
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ค้นหา</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="รหัสวิชา / ชื่อวิชา / ชื่ออาจารย์"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <button type="submit" class="px-5 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">
                แสดงรายการ
            </button>
        </form>
    </div>

    @php
        $termLabel = match ($term) {
            1 => 'ภาคต้น',
            2 => 'ภาคปลาย',
            default => 'ภาคการศึกษาพิเศษ',
        };
    @endphp

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <div class="px-4 py-3 bg-amber-50 border-b border-amber-200 text-sm text-[#5C2E1F] flex flex-wrap items-center justify-between gap-2">
            <span>
                รายวิชาใน {{ $termLabel }} ปีการศึกษา {{ $year }}
                — ทั้งหมด {{ number_format($courses->total()) }} รายการ
            </span>
            <span class="text-xs text-gray-500">
                หน้า {{ $courses->currentPage() }} / {{ max($courses->lastPage(), 1) }}
                (หน้าละ {{ $courses->perPage() }})
            </span>
        </div>
        <table class="w-full text-sm min-w-[800px]">
            <thead class="bg-amber-50/60">
                <tr>
                    <th class="px-3 py-2 text-left w-14">#</th>
                    <th class="px-3 py-2 text-left">รหัสวิชา</th>
                    <th class="px-3 py-2 text-left">ชื่อวิชา (ENG)</th>
                    <th class="px-3 py-2 text-center">กลุ่ม</th>
                    <th class="px-3 py-2 text-left">อาจารย์</th>
                    <th class="px-3 py-2 text-left">อีเมล</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($courses as $index => $row)
                    <tr class="border-t border-amber-100">
                        <td class="px-3 py-2 text-gray-500">{{ $courses->firstItem() + $index }}</td>
                        <td class="px-3 py-2 font-medium">{{ $row->COURSECODE }}</td>
                        <td class="px-3 py-2">{{ $row->COURSENAMEENG }}</td>
                        <td class="px-3 py-2 text-center">{{ $row->SECTION }}</td>
                        <td class="px-3 py-2">{{ trim(($row->OFFICERNAME ?? '').' '.($row->OFFICERSURNAME ?? '')) ?: '-' }}</td>
                        <td class="px-3 py-2 text-xs text-gray-600">{{ $row->KKUMAIL ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                            ไม่พบรายวิชาในภาค/ปีที่เลือก — กด Download จาก REG หรือเปลี่ยนตัวกรอง
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($courses->hasPages())
        <div class="flex justify-center">{{ $courses->links() }}</div>
    @endif
</div>
@endsection
