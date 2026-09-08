@extends('layouts.scigrad')

@section('title', 'สรุปผลการเรียนวิทยานิพนธ์ — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.thesis-grades.index') }}" class="text-[#8B4513] hover:underline">รับผลการเรียนวิทยานิพนธ์</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">สรุปผล</span>
@endsection

@section('content')
<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-[#a16207]">แบบฟอร์ม 3.1</p>
            <h2 class="text-xl font-bold text-[#5C2E1F] mt-1">สรุปผลการเรียนวิทยานิพนธ์ / การศึกษาอิสระ</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">ตารางสรุปตามภาค/ปี — ส่งออก .docx เพื่อกรอกเพิ่มนอกระบบได้</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('faculty-admin.thesis-grades.summary.docx', request()->query()) }}"
               class="px-4 py-2 bg-[#a16207] text-white rounded-lg text-sm font-semibold hover:bg-[#854d0e]">ดาวน์โหลด .docx</a>
            <a href="{{ route('faculty-admin.thesis-grades.index', request()->query()) }}"
               class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">กลับรายการ</a>
        </div>
    </div>

    <div class="form-section rounded-xl p-5 mb-5">
        <form method="GET" class="grid md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สาขาวิชา</label>
                <select name="department_id" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">ทุกสาขา</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->department_id }}" @selected(($filters['department_id'] ?? null) == $dept->department_id)>
                            {{ $dept->department_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                <select name="term" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="1" @selected(($filters['term'] ?? 1) === 1)>ภาคต้น</option>
                    <option value="2" @selected(($filters['term'] ?? 2) === 2)>ภาคปลาย</option>
                    <option value="3" @selected(($filters['term'] ?? 3) === 3)>ภาคการศึกษาพิเศษ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                <select name="year" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected(($filters['year'] ?? null) == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สถานะ</label>
                <select name="status" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">ทุกสถานะ (ยกเว้นร่าง)</option>
                    <option value="submitted" @selected(($filters['status'] ?? '') === 'submitted')>อาจารย์ส่ง</option>
                    <option value="returned" @selected(($filters['status'] ?? '') === 'returned')>ส่งกลับแก้ไข</option>
                    <option value="received" @selected(($filters['status'] ?? '') === 'received')>ผ่านที่ประชุมสาขาฯ</option>
                    <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>ผ่านที่ประชุมกรรมการคณะฯ</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-[#a16207] text-white rounded-lg text-sm font-semibold hover:bg-[#854d0e]">แสดงสรุป</button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-amber-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-[#fdf6f0] text-[#5C2E1F]">
                    <th class="p-3 text-left w-14">ที่</th>
                    <th class="p-3 text-left">รหัส-ชื่อวิชา</th>
                    <th class="p-3 text-left">ภาค/ปีการศึกษา</th>
                    <th class="p-3 text-left">กลุ่มที่</th>
                    <th class="p-3 text-left">จำนวน (คน)</th>
                    <th class="p-3 text-left">สถานะ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $i => $report)
                    <tr class="border-t border-amber-100 hover:bg-amber-50/40">
                        <td class="p-3">{{ $i + 1 }}</td>
                        <td class="p-3">
                            <p class="font-semibold text-[#5C2E1F]">{{ $report->displayCode() }} : {{ $report->courseKindLabel() ?: $report->subject }}</p>
                            @if ($report->subject)
                                <p class="text-xs text-[#7A4A3A]">{{ $report->subject }}</p>
                            @endif
                        </td>
                        <td class="p-3">{{ $report->term }}/{{ $report->year }}</td>
                        <td class="p-3">{{ $report->paddedSection() }}</td>
                        <td class="p-3 font-semibold">{{ $report->students->count() }}</td>
                        <td class="p-3">
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $report->statusChipClass() }}">{{ $report->statusLabel() }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-[#7A4A3A]">ไม่พบรายการตามเงื่อนไข</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="text-xs text-[#7A4A3A]/70 mt-3">รวม {{ $reports->count() }} รายวิชา · นักศึกษาทั้งหมด {{ $reports->sum(fn ($r) => $r->students->count()) }} คน</p>
</div>
@endsection
