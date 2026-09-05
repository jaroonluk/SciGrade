@extends('layouts.scigrad')

@section('title', 'รับผลการเรียนวิทยานิพนธ์ — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">รับผลการเรียนวิทยานิพนธ์</span>
@endsection

@section('content')
<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-[#a16207]">งานบริการ ป.บัณฑิต</p>
            <h2 class="text-xl font-bold text-[#5C2E1F] mt-1">รับผลการเรียนวิทยานิพนธ์ / การศึกษาอิสระ</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">ดูและดาวน์โหลดไฟล์ทุกสาขา แล้วรับเรื่องเป็นขั้นที่สองหลังสาขารับแล้ว</p>
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
                    <option value="submitted" @selected(($filters['status'] ?? '') === 'submitted')>รอสาขา</option>
                    <option value="returned" @selected(($filters['status'] ?? '') === 'returned')>ส่งกลับแก้ไข</option>
                    <option value="received" @selected(($filters['status'] ?? '') === 'received')>สาขารับแล้ว — รอคณะ</option>
                    <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>คณะรับแล้ว</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">รหัสวิชา</label>
                <input type="text" name="subject_code" value="{{ $filters['subject_code'] ?? '' }}" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ค้นหา</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="วิชา / อาจารย์" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <button type="submit" class="px-4 py-2 bg-[#a16207] text-white rounded-lg text-sm font-semibold hover:bg-[#854d0e]">แสดงรายการ</button>
        </form>
    </div>

    <form method="POST" action="{{ route('faculty-admin.thesis-grades.download') }}" id="bulk-zip">
        @csrf
        <div class="flex justify-end mb-3">
            <button type="submit" class="px-3 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ดาวน์โหลดไฟล์ที่เลือก</button>
        </div>

        <div class="bg-white rounded-xl border border-amber-200 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-[#fdf6f0] text-[#5C2E1F]">
                        <th class="p-3 text-left"><input type="checkbox" id="check-all"></th>
                        <th class="p-3 text-left">รายวิชา</th>
                        <th class="p-3 text-left">อาจารย์</th>
                        <th class="p-3 text-left">นักศึกษา</th>
                        <th class="p-3 text-left">เอกสาร</th>
                        <th class="p-3 text-left">สถานะ</th>
                        <th class="p-3 text-left"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr class="border-t border-amber-100 hover:bg-amber-50/40">
                            <td class="p-3"><input type="checkbox" name="ids[]" value="{{ $report->thesis_grade_id }}" class="row-check"></td>
                            <td class="p-3">
                                <p class="font-semibold text-[#5C2E1F]">{{ $report->displayCode() }} · กลุ่ม {{ $report->paddedSection() }}</p>
                                <p class="text-xs text-[#7A4A3A]">{{ $report->subject }}</p>
                                <p class="text-xs text-[#7A4A3A]/70">{{ $report->tsFilename() }}</p>
                            </td>
                            <td class="p-3">{{ $report->teacher ?: $report->username }}</td>
                            <td class="p-3">
                                {{ $report->students->count() }} คน
                                @if ($report->overdueStudentCount())
                                    <p class="text-xs text-red-700">เลยกำหนด {{ $report->overdueStudentCount() }}</p>
                                @endif
                            </td>
                            <td class="p-3 text-xs">
                                TS {{ $report->tsFiles()->count() }}
                                · S=0 {{ $report->s0Files()->count() }}
                            </td>
                            <td class="p-3"><span class="text-xs px-2 py-0.5 rounded-full {{ $report->statusChipClass() }}">{{ $report->statusLabel() }}</span></td>
                            <td class="p-3"><a href="{{ route('faculty-admin.thesis-grades.show', $report) }}" class="text-[#a16207] font-semibold hover:underline">เปิดดู</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-[#7A4A3A]/70">ไม่มีรายการตามตัวกรอง</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    <div class="mt-4">{{ $reports->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('check-all')?.addEventListener('change', (e) => {
        document.querySelectorAll('.row-check').forEach((c) => { c.checked = e.target.checked; });
    });
</script>
@endpush
