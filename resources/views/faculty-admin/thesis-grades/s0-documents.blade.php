@extends('layouts.scigrad')

@section('title', 'รับเอกสารบันทึกข้อความชี้แจง S=0 — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.thesis-grades.index') }}" class="text-[#8B4513] hover:underline">รับผลการเรียนวิทยานิพนธ์</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">รับเอกสาร S=0</span>
@endsection

@push('styles')
<style>
    .s0-doc-card { background: #fff; border: 1px solid #fde68a; border-radius: 1rem; }
    .s0-doc-card:hover { border-color: #eab308; box-shadow: 0 8px 22px rgba(161, 98, 7, .08); }
    .s0-file-chip {
        display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        background: #fff; border: 1px solid #fde68a; border-radius: .5rem; padding: .4rem .65rem;
        font-size: .8rem; line-height: 1.35;
    }
    .s0-file-chip a { color: #854d0e; font-weight: 600; min-width: 0; }
</style>
@endpush

@section('content')
<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-[#a16207]">งานบริการ บัณฑิตศึกษา · Admin กลาง</p>
            <h2 class="text-xl font-bold text-[#5C2E1F] mt-1">รับเอกสารบันทึกข้อความชี้แจง S=0</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1 max-w-3xl leading-relaxed">
                ช่องทางเปิดรับบันทึกข้อความชี้แจง (PDF) จากอาจารย์เมื่อมีนักศึกษาได้ S=0 —
                เห็นเฉพาะเจ้าหน้าที่งานบริการ (บัณฑิตศึกษา) และ Super Admin
            </p>
        </div>
        <a href="{{ route('faculty-admin.thesis-grades.index', ['term' => $filters['term'] ?? null, 'year' => $filters['year'] ?? null]) }}"
           class="px-4 py-2 border border-amber-300 rounded-lg text-sm font-semibold text-[#5C2E1F] hover:bg-amber-50">กลับรับผลการเรียน</a>
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

    <div class="space-y-4">
        @forelse ($reports as $report)
            @php
                $s0Students = $report->s0Students();
                $s0Files = $report->s0Files();
            @endphp
            <article class="s0-doc-card p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-[#5C2E1F]">{{ $report->displayCode() }} · กลุ่ม {{ $report->paddedSection() }}</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $report->statusChipClass() }}">{{ $report->statusLabel() }}</span>
                        </div>
                        <p class="text-sm text-[#7A4A3A] mt-0.5">{{ $report->subject }}</p>
                        <p class="text-xs text-[#7A4A3A]/70 mt-1">
                            {{ $report->teacher ?: $report->username }}
                            · S=0 {{ $s0Students->count() }} คน
                            · มีไฟล์ชี้แจง {{ $s0Files->count() }} ไฟล์
                        </p>
                    </div>
                    <a href="{{ route('faculty-admin.thesis-grades.show', $report) }}"
                       class="px-3 py-2 border border-amber-300 rounded-lg text-sm font-semibold text-[#5C2E1F] hover:bg-amber-50">รายละเอียด</a>
                </div>

                <div class="grid md:grid-cols-2 gap-3">
                    <section class="rounded-lg border border-amber-200 bg-amber-50/50 p-3">
                        <p class="text-xs font-bold tracking-wide text-[#854d0e] mb-2">นักศึกษา S=0</p>
                        <ul class="space-y-1.5">
                            @foreach ($s0Students as $student)
                                @php $hasLetter = $student->hasS0Letter($report); @endphp
                                <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                    <span class="text-[#5C2E1F]">
                                        <span class="font-semibold">{{ $student->student_code }}</span>
                                        {{ $student->displayName() }}
                                    </span>
                                    <span class="text-xs font-semibold {{ $hasLetter ? 'text-emerald-800' : 'text-amber-800' }}">
                                        {{ $hasLetter ? 'มีบันทึกแล้ว' : 'รออัปโหลด' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    <section class="rounded-lg border border-amber-200 bg-white p-3">
                        <p class="text-xs font-bold tracking-wide text-[#854d0e] mb-2">ไฟล์บันทึกข้อความชี้แจง (PDF)</p>
                        <div class="space-y-1.5">
                            @forelse ($s0Files as $file)
                                <div class="s0-file-chip">
                                    <a href="{{ route('faculty-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener" class="truncate" title="{{ $file->original_name }}">
                                        {{ $file->original_name }}
                                    </a>
                                    <a href="{{ route('faculty-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener" class="shrink-0">เปิด</a>
                                </div>
                            @empty
                                <p class="text-xs text-amber-800">ยังไม่มีไฟล์ PDF จากอาจารย์</p>
                            @endforelse
                        </div>
                    </section>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-amber-300 bg-white px-6 py-14 text-center">
                <p class="text-base font-semibold text-[#854d0e]">ไม่มีรายการที่มีนักศึกษา S=0 ตามตัวกรอง</p>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $reports->links() }}</div>
</div>
@endsection
