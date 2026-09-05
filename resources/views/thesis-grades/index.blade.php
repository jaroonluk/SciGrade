@extends('layouts.scigrad')

@section('title', 'ส่งผลการเรียนวิทยานิพนธ์ / การศึกษาอิสระ — SciGrade')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">วิทยานิพนธ์ / การศึกษาอิสระ</span>
@endsection

@push('styles')
<style>
    .thesis-hero {
        background: linear-gradient(135deg, #fefce8 0%, #fef9c3 40%, #fff 100%);
        border: 1px solid #facc15;
    }
    .thesis-card { background: #fff; border: 1px solid #fde68a; transition: all .15s; }
    .thesis-card:hover { border-color: #eab308; box-shadow: 0 6px 18px rgba(161, 98, 7, .08); }
</style>
@endpush

@section('content')
<div>
    <div class="thesis-hero rounded-2xl p-5 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#a16207]">THESIS · DISSERTATION · INDEPENDENT STUDY</p>
                <h2 class="text-xl font-bold text-[#854d0e] mt-1">ส่งผลการเรียนวิทยานิพนธ์ / การศึกษาอิสระ</h2>
                <p class="text-sm text-[#7A4A3A]/80 mt-1.5 max-w-2xl leading-relaxed">
                    ให้เกรดที่ REG ตาม มข.30 แล้วอัปโหลดใบส่งเกรดที่ลงนามแล้วเข้าที่นี่ — รายวิชานี้ส่งได้ตลอด ไม่ผูกปฏิทินสอบไล่
                </p>
            </div>
            <a href="{{ route('thesis-grades.create', ['term' => $term, 'year' => $year]) }}"
               class="px-4 py-2.5 bg-[#a16207] text-white rounded-lg text-sm font-semibold hover:bg-[#854d0e]">
                + ส่งผลวิชาใหม่
            </a>
        </div>
    </div>

    <div class="form-section rounded-xl p-5 mb-5">
        <form method="GET" action="{{ route('thesis-grades.index') }}" class="flex flex-wrap items-end gap-4">
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
                <select name="year" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[8rem]">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-white border border-amber-300 rounded-lg text-sm font-medium text-[#5C2E1F] hover:bg-amber-50">แสดงรายการ</button>
        </form>
    </div>

    @if ($reports->isEmpty())
        <div class="rounded-2xl border border-dashed border-amber-300 bg-white px-6 py-14 text-center">
            <p class="text-base font-semibold text-[#854d0e]">ยังไม่มีรายการในภาคนี้</p>
            <p class="text-sm text-[#7A4A3A]/75 mt-1">เริ่มจากเลือกวิชา ตรวจรายชื่อนักศึกษา แล้วอัปโหลดไฟล์ TS ที่เซ็นแล้ว</p>
            <a href="{{ route('thesis-grades.create', ['term' => $term, 'year' => $year]) }}"
               class="inline-block mt-4 px-4 py-2 bg-[#a16207] text-white rounded-lg text-sm font-semibold hover:bg-[#854d0e]">
                ส่งผลวิชาแรก
            </a>
        </div>
    @else
        <div class="grid gap-3">
            @foreach ($reports as $report)
                @php
                    $overdue = $report->overdueStudentCount();
                    $missingS0 = $report->missingS0Count();
                    $missingDefense = $report->missingDefenseCount();
                @endphp
                <a href="{{ route('thesis-grades.edit', $report) }}" class="thesis-card rounded-xl p-4 block">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-[#5C2E1F]">{{ $report->displayCode() }} <span class="text-[#7A4A3A] font-medium">กลุ่ม {{ $report->paddedSection() }}</span></p>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">{{ $report->courseKindLabel() }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $report->statusChipClass() }}">{{ $report->statusLabel() }}</span>
                            </div>
                            <p class="text-sm text-[#7A4A3A] mt-1">{{ $report->subject }}</p>
                            <p class="text-xs text-[#7A4A3A]/70 mt-1">
                                {{ $report->termLabel() }} {{ $report->year }} · นักศึกษา {{ $report->students->count() }} คน
                                @if ($report->tsFiles()->isNotEmpty())
                                    · มีไฟล์ TS
                                @else
                                    · ยังไม่มีไฟล์ TS
                                @endif
                            </p>
                            @if ($overdue || $missingS0 || $missingDefense)
                                <p class="text-xs text-amber-800 mt-1.5">
                                    @if ($overdue) เลยกำหนดเค้าโครง {{ $overdue }} คน @endif
                                    @if ($missingS0) · ขาดหนังสือ S=0 {{ $missingS0 }} คน @endif
                                    @if ($missingDefense) · ขาดวันที่สอบ {{ $missingDefense }} คน @endif
                                </p>
                            @endif
                            @if ($report->status === 'returned' && $report->return_reason)
                                <p class="text-xs text-red-700 mt-1">สาขาส่งกลับ: {{ $report->return_reason }}</p>
                            @endif
                        </div>
                        <span class="text-sm font-semibold text-[#a16207]">เปิดรายการ →</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
