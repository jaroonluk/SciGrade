@extends('layouts.scigrad')

@section('title', 'รับผลการเรียนวิทยานิพนธ์ — Admin สาขา')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">รับผลการเรียนวิทยานิพนธ์</span>
@endsection

@push('styles')
<style>
    .thesis-dept-card { background: #fff; border: 1px solid #fde68a; border-radius: 1rem; }
    .thesis-dept-card:hover { border-color: #eab308; box-shadow: 0 8px 22px rgba(161, 98, 7, .08); }
    .thesis-file-panel { border-radius: .75rem; padding: .75rem .85rem; min-height: 7.5rem; }
    .thesis-file-instructor { background: #fffbeb; border: 1px solid #fde68a; }
    .thesis-file-dept { background: #f0fdfa; border: 1px solid #99f6e4; }
    .thesis-file-chip {
        display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        background: #fff; border-radius: .5rem; padding: .35rem .55rem;
        font-size: .75rem; line-height: 1.3;
    }
    .thesis-file-chip a { color: #854d0e; font-weight: 600; min-width: 0; }
    .thesis-file-dept .thesis-file-chip a { color: #0f766e; }
</style>
@endpush

@section('content')
<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-[#a16207]">THESIS · DISSERTATION · INDEPENDENT STUDY</p>
            <h2 class="text-xl font-bold text-[#5C2E1F] mt-1">รับผลการเรียนวิทยานิพนธ์ / การศึกษาอิสระ</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">ต้องกดผ่านที่ประชุมสาขาวิชาทุกรายการ ไฟล์จากสาขาเป็นทางเลือก ไม่บังคับก่อนกดผ่าน</p>
        </div>
    </div>

    <div class="form-section rounded-xl p-5 mb-5">
        <form method="GET" class="grid md:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
            @if ($departments->count() > 1)
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สาขาวิชา</label>
                    <select name="department_id" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="">ทุกสาขาที่มีสิทธิ์</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->department_id }}" @selected(($filters['department_id'] ?? null) == $dept->department_id)>
                                {{ $dept->department_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
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

    <form method="POST" action="{{ route('dept-admin.thesis-grades.download') }}" id="bulk-zip" class="flex flex-wrap justify-end gap-2 mb-3">
        @csrf
        <input type="hidden" name="term" value="{{ $filters['term'] ?? '' }}">
        <input type="hidden" name="year" value="{{ $filters['year'] ?? '' }}">
        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
        <input type="hidden" name="department_id" value="{{ $filters['department_id'] ?? '' }}">
        <input type="hidden" name="subject_code" value="{{ $filters['subject_code'] ?? '' }}">
        <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
        <label class="inline-flex items-center gap-2 text-sm text-[#5C2E1F] mr-auto">
            <input type="checkbox" id="check-all">
            เลือกทั้งหมด
        </label>
        <button type="submit" name="all_filtered" value="1" class="px-3 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ดาวน์โหลดทั้งหมดตามเงื่อนไข</button>
        <button type="submit" class="px-3 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ดาวน์โหลดไฟล์ที่เลือก</button>
    </form>

    <div class="space-y-4">
        @forelse ($reports as $report)
            @php
                $instructorFiles = $report->instructorFiles();
                $chairFiles = $report->chairFiles();
                $canReceive = $report->canDeptReceive();
                $canUploadChair = $report->canDeptUploadChairFiles();
            @endphp
            <article class="thesis-dept-card p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div class="flex items-start gap-3 min-w-0">
                        <input type="checkbox" form="bulk-zip" name="ids[]" value="{{ $report->thesis_grade_id }}" class="row-check mt-1.5">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-[#5C2E1F]">{{ $report->displayCode() }} · กลุ่ม {{ $report->paddedSection() }}</h3>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">{{ $report->courseKindLabel() }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $report->statusChipClass() }}">{{ $report->statusLabel() }}</span>
                            </div>
                            <p class="text-sm text-[#7A4A3A] mt-0.5">{{ $report->subject }}</p>
                            <p class="text-xs text-[#7A4A3A]/70 mt-1">
                                {{ $report->teacher ?: $report->username }}
                                · นักศึกษา {{ $report->students->count() }} คน
                                · {{ $report->tsFilename() }}
                            </p>
                            @if ($report->overdueStudentCount() || $report->missingS0Count())
                                <p class="text-xs text-red-700 mt-1">
                                    @if ($report->overdueStudentCount()) เลยกำหนดเค้าโครง {{ $report->overdueStudentCount() }} คน @endif
                                    @if ($report->missingS0Count()) · ขาดหนังสือ S=0 {{ $report->missingS0Count() }} คน @endif
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($canReceive)
                            <form method="POST" action="{{ route('dept-admin.thesis-grades.receive', $report) }}"
                                  onsubmit="return confirm('ยืนยันผ่านที่ประชุมสาขาวิชาสำหรับ {{ $report->displayCode() }} กลุ่ม {{ $report->paddedSection() }} ?')">
                                @csrf
                                <button type="submit" class="px-3.5 py-2 bg-emerald-700 text-white rounded-lg text-sm font-semibold hover:bg-emerald-800">
                                    ผ่านที่ประชุมสาขาวิชา
                                </button>
                            </form>
                        @elseif ($report->normalizedStatus() === 'received')
                            <span class="px-3 py-2 rounded-lg text-sm font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">ผ่านที่ประชุมสาขาฯ แล้ว</span>
                        @endif
                        <a href="{{ route('dept-admin.thesis-grades.show', $report) }}" class="px-3 py-2 border border-amber-300 rounded-lg text-sm font-semibold text-[#5C2E1F] hover:bg-amber-50">รายละเอียด</a>
                    </div>
                </div>
                @include('thesis-grades.partials.s0-print-buttons', ['report' => $report, 'role' => 'dept'])

                <div class="grid md:grid-cols-2 gap-3">
                    <section class="thesis-file-panel thesis-file-instructor">
                        <p class="text-xs font-bold tracking-wide text-[#854d0e] mb-2">ไฟล์อาจารย์</p>
                        <div class="space-y-1.5">
                            @forelse ($instructorFiles as $file)
                                <div class="thesis-file-chip">
                                    <a href="{{ route('dept-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener" class="truncate" title="{{ $file->original_name }}">
                                        {{ $file->typeLabel() }} · {{ $file->original_name }}
                                    </a>
                                    <a href="{{ route('dept-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener" class="shrink-0">เปิด</a>
                                </div>
                            @empty
                                <p class="text-xs text-[#7A4A3A]/70">ยังไม่มีใบ TS หรือหนังสือ S=0</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="thesis-file-panel thesis-file-dept">
                        <p class="text-xs font-bold tracking-wide text-teal-800 mb-2">เอกสารสาขาวิชา · Admin สาขาอัปโหลด</p>
                        <div class="space-y-1.5">
                            @forelse ($chairFiles as $file)
                                <div class="thesis-file-chip">
                                    <a href="{{ route('dept-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener" class="truncate" title="{{ $file->original_name }}">
                                        {{ $file->original_name }}
                                    </a>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <a href="{{ route('dept-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener">เปิด</a>
                                        @if ($canUploadChair)
                                            <form method="POST" action="{{ route('dept-admin.thesis-grades.chair-files.destroy', [$report, $file]) }}" onsubmit="return confirm('ลบไฟล์นี้หรือไม่?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-700">ลบ</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-teal-800/70">ยังไม่มีไฟล์จากสาขา — อัปโหลดได้ถ้ามีเอกสารเพิ่ม ไม่บังคับก่อนกดผ่านที่ประชุม</p>
                            @endforelse
                            @if ($canUploadChair)
                                <form method="POST" action="{{ route('dept-admin.thesis-grades.chair-files.store', $report) }}" enctype="multipart/form-data" class="pt-1">
                                    @csrf
                                    <label class="flex items-center justify-center gap-2 rounded-lg border border-dashed border-teal-300 bg-white px-3 py-2 text-xs font-semibold text-teal-800 cursor-pointer hover:bg-teal-50">
                                        <span>อัปโหลด PDF จากสาขา</span>
                                        <input type="file" name="files[]" accept="application/pdf" multiple required class="sr-only" onchange="this.form.submit()">
                                    </label>
                                </form>
                            @endif
                        </div>
                    </section>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-amber-300 bg-white px-6 py-14 text-center">
                <p class="text-base font-semibold text-[#854d0e]">ไม่มีรายการตามตัวกรอง</p>
            </div>
        @endforelse
    </div>

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
