@extends('layouts.scigrad')

@section('title', 'ดึงข้อมูลรายวิชาจาก REG — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">ดึงข้อมูลรายวิชาจาก REG</span>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">ดึงข้อมูลรายวิชาจาก REG</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            ดึงชื่อวิชาเข้า pdcourse เพื่อเป็นชื่อวิชาให้อาจารย์เลือกตอนรายงานผลการสอบ
        </p>
    </div>

    @unless ($canConnect)
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            เชื่อมต่อฐานข้อมูล REG ไม่ได้ กรุณาตรวจสอบว่า MySQL มี database ชื่อ
            <span class="font-semibold">reg</span> บน localhost
        </div>
    @endunless

    @error('sync')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

    <div class="form-section rounded-xl p-6">
        <form method="POST" action="{{ route('faculty-admin.settings.reg-courses.sync') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                <select name="year" required class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                    <option value="">-เลือกปี-</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected((int) old('year', session('sync_year', $defaultYear)) === $y)>{{ $y }}</option>
                    @endforeach
                </select>
                @error('year')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]"
                @disabled(! $canConnect)
                onclick="return confirm('ดึงรายวิชาจาก REG ตามปีที่เลือก และเพิ่มเฉพาะรหัสที่ยังไม่มีในระบบ?')">
                ดึงข้อมูล
            </button>
        </form>
        <p class="text-xs text-[#7A4A3A]/70 mt-3">
            ระบบจะอ่านจากฐาน <span class="font-medium">reg.course</span>
            (รายวิชา SC ที่สร้างในปีปฏิทินที่ตรงกับปีการศึกษา − 543 รวมวิชา SEMINAR)
            แล้วเพิ่มเฉพาะรหัสที่ยังไม่มีใน <span class="font-medium">eoffice.pdcourse</span>
            เพื่อให้อาจารย์เลือกชื่อวิชาตอนรายงานผลการสอบ — ยังไม่ดึง THESIS / Independent Study / Dissertation
        </p>
    </div>

    @if (session('sync_result'))
        @php $result = session('sync_result'); @endphp
        <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
            <div class="px-4 py-3 bg-amber-50 border-b border-amber-200 text-sm text-[#5C2E1F]">
                ผลลัพธ์ — พบ {{ $result['fetched'] }} รายการ ·
                เพิ่มใหม่ {{ $result['inserted'] }} ·
                มีอยู่แล้ว {{ $result['skipped'] }}
            </div>
            <table class="w-full text-sm min-w-[640px]">
                <thead class="bg-amber-50/60">
                    <tr>
                        <th class="px-3 py-2 text-left w-12">#</th>
                        <th class="px-3 py-2 text-left">รหัสวิชา</th>
                        <th class="px-3 py-2 text-left">ชื่อวิชา (ENG)</th>
                        <th class="px-3 py-2 text-left">หน่วยกิต</th>
                        <th class="px-3 py-2 text-center">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($result['rows'] as $index => $row)
                        <tr class="border-t border-amber-100">
                            <td class="px-3 py-2 text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-3 py-2 font-medium">{{ $row['subjcode'] }}</td>
                            <td class="px-3 py-2">{{ $row['subjname'] }}</td>
                            <td class="px-3 py-2">{{ $row['courseint'] ?: '-' }}</td>
                            <td class="px-3 py-2 text-center">
                                @if ($row['status'] === 'inserted')
                                    <span class="inline-block px-2 py-0.5 rounded text-xs bg-green-100 text-green-800">เพิ่มใหม่</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">มีอยู่แล้ว</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-gray-500">ไม่พบรายวิชาตามเงื่อนไข</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
