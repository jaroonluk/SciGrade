@extends('layouts.scigrad')

@section('title', 'รับผลการเรียนวิทยานิพนธ์ — Admin สาขา')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">รับผลการเรียนวิทยานิพนธ์</span>
@endsection

@push('styles')
<style>
    .thesis-dept-card { background: #fff; border: 1px solid #fde68a; border-radius: 1rem; }
    .thesis-dept-card:hover { border-color: #eab308; box-shadow: 0 6px 16px rgba(161, 98, 7, .07); }
    .thesis-student-table { width: 100%; border-collapse: collapse; }
    .thesis-student-table th {
        text-align: left; font-size: .7rem; font-weight: 700; letter-spacing: .02em;
        text-transform: uppercase; color: #854d0e; padding: 0 .5rem .45rem 0; white-space: nowrap;
        border-bottom: 1px solid #fde68a;
    }
    .thesis-student-table td {
        padding: .55rem .5rem .55rem 0; vertical-align: top;
        border-bottom: 1px solid #fef3c7; font-size: .8125rem; color: #5C2E1F;
    }
    .thesis-student-table tr:last-child td { border-bottom: 0; }
    .thesis-student-table .stu-name {
        font-size: 1rem; font-weight: 700; color: #3f2a1d; line-height: 1.25;
    }
    .thesis-student-table .stu-code {
        display: block; font-size: .72rem; font-weight: 600; color: #a16207; margin-top: .1rem;
    }
    .thesis-docs {
        display: flex; flex-wrap: wrap; gap: .4rem .75rem; align-items: center;
        padding: .55rem .7rem; border-radius: .65rem; background: #fffbeb; border: 1px solid #fde68a;
    }
    .thesis-docs-group {
        display: inline-flex; flex-wrap: wrap; align-items: center; gap: .3rem .45rem;
        min-width: 0;
    }
    .thesis-docs-label {
        font-size: .65rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        color: #a16207; white-space: nowrap;
    }
    .thesis-docs-dept .thesis-docs-label { color: #0f766e; }
    .thesis-doc-link {
        display: inline-flex; align-items: center; gap: .25rem; max-width: 14rem;
        font-size: .7rem; font-weight: 600; color: #854d0e; text-decoration: none;
        background: #fff; border: 1px solid #fde68a; border-radius: .4rem;
        padding: .15rem .4rem; line-height: 1.2;
    }
    .thesis-docs-dept .thesis-doc-link { color: #0f766e; border-color: #99f6e4; }
    .thesis-doc-link:hover { background: #fef9c3; }
    .thesis-docs-dept .thesis-doc-link:hover { background: #ccfbf1; }
    .thesis-doc-link span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .thesis-doc-muted { font-size: .7rem; color: #7A4A3A99; }
    .thesis-upload-mini {
        display: inline-flex; align-items: center; font-size: .68rem; font-weight: 700;
        color: #0f766e; background: #fff; border: 1px dashed #5eead4; border-radius: .4rem;
        padding: .18rem .45rem; cursor: pointer;
    }
    .thesis-upload-mini:hover { background: #f0fdfa; }
    .thesis-doc-del { font-size: .65rem; color: #b91c1c; font-weight: 600; background: none; border: 0; cursor: pointer; padding: 0; }
</style>
@endpush

@section('content')
<div>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-[#a16207]">THESIS · DISSERTATION · INDEPENDENT STUDY</p>
            <h2 class="text-xl font-bold text-[#5C2E1F] mt-1">รับผลการเรียนวิทยานิพนธ์ / การศึกษาอิสระ</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">ตรวจรายชื่อและเอกสารในหน้ารายการนี้ได้เลย — อัปโหลดเอกสารสาขาได้เฉพาะก่อนกดผ่านที่ประชุมสาขาวิชา</p>
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
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="วิชา / อาจารย์ / นักศึกษา" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
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
                $tsFiles = $report->tsFiles();
                $s0Files = $report->s0Files();
                $chairFiles = $report->chairFiles();
                $canReceive = $report->canDeptReceive();
                $canUploadChair = $report->canDeptUploadChairFiles();
            @endphp
            <article class="thesis-dept-card p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
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
                            </p>
                            @if ($report->overdueStudentCount() || $report->missingS0Count())
                                <p class="text-xs text-red-700 mt-1">
                                    @if ($report->overdueStudentCount()) เลยกำหนดเค้าโครง {{ $report->overdueStudentCount() }} คน @endif
                                    @if ($report->missingS0Count()) · ขาดบันทึกข้อความชี้แจง S=0 {{ $report->missingS0Count() }} คน @endif
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
                        @if ($report->files->isNotEmpty())
                            <a href="{{ route('dept-admin.thesis-grades.files.zip', $report) }}"
                               class="px-2.5 py-1.5 border border-amber-200 rounded-lg text-xs font-semibold text-[#7A4A3A] hover:bg-amber-50">ZIP</a>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto mb-3 rounded-lg border border-amber-100 bg-amber-50/30 px-3 py-2">
                    <p class="text-[11px] font-bold tracking-wide text-[#854d0e] mb-1.5">รายชื่อนักศึกษา</p>
                    <table class="thesis-student-table">
                        <thead>
                            <tr>
                                <th>นักศึกษา</th>
                                <th>ระดับ</th>
                                <th>ภาคสะสม</th>
                                <th>เค้าโครง</th>
                                <th>เกรด / นก.</th>
                                <th>สอบวิทยานิพนธ์</th>
                                <th>S=0</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($report->students as $student)
                                <tr class="{{ $student->isProposalOverdue() ? 'bg-red-50/80' : '' }}">
                                    <td>
                                        <span class="stu-name">{{ $student->displayName() }}</span>
                                        <span class="stu-code">{{ $student->student_code }}</span>
                                    </td>
                                    <td>{{ $student->degreeLabel() }}</td>
                                    <td>{{ $student->thesis_terms_count }}</td>
                                    <td>
                                        @if ($student->proposal_approved)
                                            อนุมัติแล้ว
                                        @elseif ($student->isProposalOverdue())
                                            <span class="text-red-700 font-semibold">เลยกำหนด</span>
                                        @else
                                            อยู่ในกำหนด
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-semibold">{{ $student->grade ?: '—' }}</span>
                                        <span class="text-[#7A4A3A]/70">/ {{ $student->credits_passed ?? $student->progress_credits ?? '—' }}</span>
                                    </td>
                                    <td>
                                        @if ($student->completed)
                                            {{ $student->defense_date?->format('d/m/Y') ?: 'ยังไม่ระบุวันที่' }}
                                        @else
                                            <span class="text-[#7A4A3A]/70">ยังไม่ครบหลักสูตร</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">
                                        @if ($student->isS0())
                                            <span class="{{ $student->hasS0Letter($report) ? 'text-emerald-800' : 'text-amber-800' }} font-semibold">
                                                {{ $student->hasS0Letter($report) ? 'มีบันทึก' : 'ขาดบันทึก' }}
                                            </span>
                                            <span class="block mt-0.5">
                                                <a href="{{ route('dept-admin.thesis-grades.s0-letter', [$report, $student]) }}" target="_blank" rel="noopener"
                                                   class="text-[11px] font-semibold text-[#a16207] underline">พิมพ์</a>
                                                <a href="{{ route('dept-admin.thesis-grades.s0.docx', [$report, $student]) }}"
                                                   class="text-[11px] font-semibold text-[#a16207] underline ml-1.5">.docx</a>
                                            </span>
                                        @else
                                            <span class="text-[#7A4A3A]/50">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-[#7A4A3A]/70 py-3">ยังไม่มีรายชื่อนักศึกษา</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-2">
                    <section class="thesis-docs" aria-label="เอกสารที่อาจารย์แนบ">
                        <div class="w-full mb-0.5">
                            <p class="text-xs font-bold text-[#854d0e]">เอกสารที่อาจารย์แนบ</p>
                            <p class="text-[11px] text-[#7A4A3A]/75 leading-snug">ใบส่งเกรด (TS) และบันทึกข้อความชี้แจง S=0 จากอาจารย์ — Admin สาขาเปิดดูได้อย่างเดียว</p>
                        </div>
                        <div class="thesis-docs-group">
                            <span class="thesis-docs-label">ใบ TS</span>
                            @forelse ($tsFiles as $file)
                                <a href="{{ route('dept-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener"
                                   class="thesis-doc-link" title="{{ $file->original_name }}">
                                    <span>{{ $file->original_name }}</span>
                                </a>
                            @empty
                                <span class="thesis-doc-muted">ยังไม่มีไฟล์จากอาจารย์</span>
                            @endforelse
                        </div>

                        <div class="thesis-docs-group">
                            <span class="thesis-docs-label">S=0</span>
                            @forelse ($s0Files as $file)
                                <a href="{{ route('dept-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener"
                                   class="thesis-doc-link" title="{{ $file->original_name }}">
                                    <span>{{ $file->original_name }}</span>
                                </a>
                            @empty
                                <span class="thesis-doc-muted">ไม่มีบันทึก S=0</span>
                            @endforelse
                        </div>
                    </section>

                    <section class="thesis-docs thesis-docs-dept" style="background:#f0fdfa;border-color:#99f6e4;" aria-label="เอกสารที่ Admin สาขาแนบ">
                        <div class="w-full mb-0.5">
                            <p class="text-xs font-bold text-teal-800">เอกสารที่ Admin สาขาแนบ</p>
                            @if ($canUploadChair)
                                <p class="text-[11px] text-teal-800/80 leading-snug">อัปโหลดเพิ่มได้เฉพาะก่อนกด «ผ่านที่ประชุมสาขาวิชา» — ไม่บังคับ</p>
                            @else
                                <p class="text-[11px] text-teal-800/80 leading-snug">ผ่านที่ประชุมสาขาฯ แล้ว — แก้ไขหรืออัปโหลดเอกสารสาขาไม่ได้ เปิดดูได้อย่างเดียว</p>
                            @endif
                        </div>
                        <div class="thesis-docs-group flex-1">
                            @forelse ($chairFiles as $file)
                                <span class="inline-flex items-center gap-1">
                                    <a href="{{ route('dept-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener"
                                       class="thesis-doc-link" title="{{ $file->original_name }}">
                                        <span>{{ $file->original_name }}</span>
                                    </a>
                                    @if ($canUploadChair)
                                        <form method="POST" action="{{ route('dept-admin.thesis-grades.chair-files.destroy', [$report, $file]) }}" class="inline"
                                              onsubmit="return confirm('ลบไฟล์นี้หรือไม่?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="thesis-doc-del">ลบ</button>
                                        </form>
                                    @endif
                                </span>
                            @empty
                                <span class="thesis-doc-muted">
                                    @if ($canUploadChair)
                                        ยังไม่มีไฟล์จากสาขา — อัปโหลดได้ถ้ามีเอกสารเพิ่ม
                                    @else
                                        ไม่มีไฟล์จากสาขา
                                    @endif
                                </span>
                            @endforelse
                            @if ($canUploadChair)
                                <form method="POST" action="{{ route('dept-admin.thesis-grades.chair-files.store', $report) }}" enctype="multipart/form-data" class="inline">
                                    @csrf
                                    <label class="thesis-upload-mini">
                                        <span>+ อัปโหลด PDF</span>
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
