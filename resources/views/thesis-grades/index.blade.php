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
    .thesis-card { background: #fff; border: 1px solid #fde68a; border-radius: 1rem; transition: all .15s; }
    .thesis-card:hover { border-color: #eab308; box-shadow: 0 6px 16px rgba(161, 98, 7, .07); }
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
        color: #854d0e; background: #fff; border: 1px dashed #f59e0b; border-radius: .4rem;
        padding: .18rem .45rem; cursor: pointer;
    }
    .thesis-upload-mini:hover { background: #fffbeb; }
    .thesis-actions {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .28rem;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: .9rem;
        box-shadow: 0 1px 2px rgba(120, 53, 15, .06);
    }
    .thesis-icon-btn {
        position: relative;
        width: 2.4rem;
        height: 2.4rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: .65rem;
        border: 1px solid transparent;
        background: #fff;
        cursor: pointer;
        transition: background .15s ease, color .15s ease, border-color .15s ease, transform .15s ease, box-shadow .15s ease;
    }
    .thesis-icon-btn svg { width: 1.05rem; height: 1.05rem; }
    .thesis-icon-edit { color: #854d0e; border-color: #fde68a; }
    .thesis-icon-edit:hover { background: #a16207; color: #fff; border-color: #a16207; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(161, 98, 7, .28); }
    .thesis-icon-view { color: #7A4A3A; border-color: #e8cdb5; }
    .thesis-icon-view:hover { background: #5C2E1F; color: #fff; border-color: #5C2E1F; transform: translateY(-1px); }
    .thesis-icon-delete { color: #b91c1c; border-color: #fecaca; }
    .thesis-icon-delete:hover { background: #dc2626; color: #fff; border-color: #dc2626; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(220, 38, 38, .22); }
    .thesis-icon-btn::after {
        content: attr(data-tip);
        position: absolute;
        top: calc(100% + .4rem);
        left: 50%;
        transform: translateX(-50%) translateY(.15rem);
        white-space: nowrap;
        background: #3f2a1d;
        color: #fff;
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .01em;
        padding: .28rem .55rem;
        border-radius: .4rem;
        opacity: 0;
        pointer-events: none;
        transition: opacity .12s ease, transform .12s ease;
        z-index: 20;
        box-shadow: 0 6px 16px rgba(63, 42, 29, .2);
    }
    .thesis-icon-btn:hover::after,
    .thesis-icon-btn:focus-visible::after {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
</style>
@endpush

@section('content')
<div>
    <div class="thesis-hero rounded-2xl p-5 mb-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#a16207]">THESIS · DISSERTATION · INDEPENDENT STUDY</p>
                <h2 class="text-xl font-bold text-[#854d0e] mt-1">ส่งผลการเรียนวิทยานิพนธ์ / การศึกษาอิสระ</h2>
                <p class="text-sm text-[#7A4A3A]/80 mt-1.5 max-w-2xl leading-relaxed">
                    ตรวจรายชื่อนักศึกษาและเอกสารในหน้ารายการนี้ได้เลย — หลังส่งเข้าสาขา หากสาขาหรือ Admin กลางยังไม่เปลี่ยนสถานะ ยังแก้ไขหรือลบได้
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
        <div class="space-y-4">
            @foreach ($reports as $report)
                @php
                    $tsFiles = $report->tsFiles();
                    $s0Files = $report->s0Files();
                    $chairFiles = $report->chairFiles();
                    $status = $report->normalizedStatus();
                    $canEdit = $report->isEditable();
                    $overdue = $report->overdueStudentCount();
                    $missingS0 = $report->missingS0Count();
                    $missingDefense = $report->missingDefenseCount();
                @endphp
                <article class="thesis-card p-4 sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-[#5C2E1F]">{{ $report->displayCode() }} · กลุ่ม {{ $report->paddedSection() }}</h3>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">{{ $report->courseKindLabel() }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $report->statusChipClass() }}">{{ $report->statusLabel() }}</span>
                            </div>
                            <p class="text-sm text-[#7A4A3A] mt-0.5">{{ $report->subject }}</p>
                            <p class="text-xs text-[#7A4A3A]/70 mt-1">
                                {{ $report->termLabel() }} {{ $report->year }}
                                · นักศึกษา {{ $report->students->count() }} คน
                            </p>
                            @if ($status === 'submitted')
                                <p class="text-xs text-amber-800 mt-1">รอสาขากดผ่านที่ประชุมสาขาวิชา — ยังแก้ไขหรือลบได้จนกว่าสาขาหรือ Admin กลางจะเปลี่ยนสถานะ</p>
                            @elseif ($status === 'received')
                                <p class="text-xs text-emerald-800 mt-1">สาขาผ่านที่ประชุมสาขาวิชาแล้ว@if ($chairFiles->isEmpty()) โดยไม่มีไฟล์เพิ่มจากสาขา@endif</p>
                            @endif
                            @if ($overdue || $missingS0 || $missingDefense)
                                <p class="text-xs text-red-700 mt-1">
                                    @if ($overdue) เลยกำหนดเค้าโครง {{ $overdue }} คน @endif
                                    @if ($missingS0) · ขาดบันทึกข้อความชี้แจง S=0 {{ $missingS0 }} คน @endif
                                    @if ($missingDefense) · ขาดวันที่สอบ {{ $missingDefense }} คน @endif
                                </p>
                            @endif
                            @if ($status === 'returned' && $report->return_reason)
                                <p class="text-xs text-red-700 mt-1">สาขาส่งกลับ: {{ $report->return_reason }}</p>
                            @endif
                        </div>
                        <div class="thesis-actions shrink-0">
                            @if ($canEdit)
                                <a href="{{ route('thesis-grades.edit', $report) }}"
                                   class="thesis-icon-btn thesis-icon-edit"
                                   data-tip="แก้ไขรายการ"
                                   aria-label="แก้ไขรายการ">
                                    <i data-lucide="pencil"></i>
                                </a>
                            @else
                                <a href="{{ route('thesis-grades.edit', $report) }}"
                                   class="thesis-icon-btn thesis-icon-view"
                                   data-tip="เปิดรายการ"
                                   aria-label="เปิดรายการ">
                                    <i data-lucide="eye"></i>
                                </a>
                            @endif
                            @if ($report->isDeletable())
                                <form method="POST" action="{{ route('thesis-grades.destroy', $report) }}" class="inline-flex"
                                      onsubmit="return confirm('ต้องการลบรายการนี้หรือไม่? การลบจะลบไฟล์แนบด้วย')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="thesis-icon-btn thesis-icon-delete"
                                            data-tip="ลบรายการ"
                                            aria-label="ลบรายการ">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </form>
                            @endif
                            @if ($report->files->isNotEmpty())
                                <a href="{{ route('thesis-grades.files.zip', $report) }}"
                                   class="px-2.5 py-1.5 border border-amber-200 rounded-lg text-xs font-semibold text-[#7A4A3A] hover:bg-amber-50 self-center">ZIP</a>
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
                                    @php
                                        $attached = $s0Files->first(
                                            fn ($file) => (int) $file->student_id === (int) $student->student_id
                                        );
                                    @endphp
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
                                        <td class="whitespace-nowrap"
                                            @if ($canEdit && $student->isS0())
                                                data-s0-student="{{ $student->student_id }}"
                                                data-s0-upload-url="{{ route('thesis-grades.files.store', $report) }}"
                                            @endif>
                                            @if ($student->isS0())
                                                <span class="{{ $attached ? 'text-emerald-800' : 'text-amber-800' }} font-semibold"
                                                      data-s0-status-text>
                                                    {{ $attached ? 'มีบันทึก' : 'ขาดบันทึก' }}
                                                </span>
                                                <span class="block mt-0.5" data-s0-file-row>
                                                    <a href="{{ route('thesis-grades.s0-letter.student', [$report, $student]) }}" target="_blank" rel="noopener"
                                                       class="text-[11px] font-semibold text-[#a16207] underline">พิมพ์</a>
                                                    <a href="{{ route('thesis-grades.s0.docx.student', [$report, $student]) }}"
                                                       class="text-[11px] font-semibold text-[#a16207] underline ml-1.5">.docx</a>
                                                    @if ($attached)
                                                        <a href="{{ route('thesis-grades.files.show', [$report, $attached]) }}" target="_blank" rel="noopener"
                                                           class="text-[11px] font-semibold text-[#854d0e] underline ml-1.5 truncate max-w-[10rem] inline-block align-bottom"
                                                           data-s0-file-link
                                                           title="{{ $attached->original_name }}">PDF</a>
                                                    @elseif ($canEdit)
                                                        <span class="text-[11px] text-amber-800 ml-1.5" data-s0-file-missing>ยังไม่อัปโหลด</span>
                                                    @endif
                                                    @if ($canEdit)
                                                        <label class="thesis-upload-mini ml-1.5 align-middle">
                                                            <input type="file" accept="application/pdf,.pdf" class="sr-only" data-s0-file-input>
                                                            <span data-s0-upload-label>{{ $attached ? 'เปลี่ยน PDF' : 'อัปโหลด PDF' }}</span>
                                                        </label>
                                                        <span class="text-[11px] text-[#7A4A3A]/70 hidden ml-1" data-s0-upload-status></span>
                                                    @endif
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
                                <p class="text-[11px] text-[#7A4A3A]/75 leading-snug">ใบส่งเกรด (TS) และบันทึกข้อความชี้แจง S=0 ที่คุณอัปโหลด</p>
                            </div>
                            <div class="thesis-docs-group">
                                <span class="thesis-docs-label">ใบ TS</span>
                                @forelse ($tsFiles as $file)
                                    <a href="{{ route('thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener"
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
                                    <a href="{{ route('thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener"
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
                                <p class="text-[11px] text-teal-800/80 leading-snug">เอกสารเพิ่มจากสาขา (ถ้ามี) — ไม่บังคับ เปิดดูได้อย่างเดียว</p>
                            </div>
                            <div class="thesis-docs-group flex-1">
                                @forelse ($chairFiles as $file)
                                    <a href="{{ route('thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener"
                                       class="thesis-doc-link" title="{{ $file->original_name }}">
                                        <span>{{ $file->original_name }}</span>
                                    </a>
                                @empty
                                    <span class="thesis-doc-muted">สาขายังไม่ได้อัปโหลดเอกสารเพิ่ม — ไม่บังคับ รอสาขากดผ่านที่ประชุมสาขาวิชา</span>
                                @endforelse
                            </div>
                        </section>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

@if (session('thesis_submitted'))
    @php $submitted = session('thesis_submitted'); @endphp
    <div id="thesis-submit-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white border border-amber-200 shadow-xl p-5">
            <h3 class="text-lg font-bold text-[#854d0e]">ส่งเข้าสาขาเรียบร้อย</h3>
            <p class="text-sm text-[#7A4A3A] mt-2 leading-relaxed">
                ส่งผลการเรียน
                <span class="font-semibold text-[#5C2E1F]">{{ $submitted['code'] ?? '' }} {{ $submitted['subject'] ?? '' }}</span>
                @if (! empty($submitted['section']))
                    กลุ่ม {{ $submitted['section'] }}
                @endif
                เข้าสาขาแล้ว — ติดตามสถานะจากรายการนี้ หรือส่งผลวิชาต่อไป
            </p>
            <div class="mt-5 flex flex-col gap-2">
                <a href="{{ route('thesis-grades.create', ['term' => $term, 'year' => $year]) }}"
                   class="px-4 py-2.5 bg-[#a16207] text-white rounded-lg text-sm font-semibold text-center hover:bg-[#854d0e]">
                    ส่งเกรดวิชาต่อไป
                </a>
                <a href="{{ route('dashboard') }}"
                   class="px-4 py-2.5 border border-amber-300 text-[#5C2E1F] rounded-lg text-sm font-semibold text-center hover:bg-amber-50">
                    กลับหน้าหลัก
                </a>
                <button type="button" id="thesis-submit-modal-close" class="px-4 py-2 text-sm text-[#7A4A3A] hover:underline">
                    อยู่ในหน้ารายการนี้
                </button>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    (() => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

        document.querySelectorAll('[data-s0-file-input]').forEach((input) => {
            input.addEventListener('change', async () => {
                const file = input.files?.[0];
                const wrap = input.closest('[data-s0-student]');
                const url = wrap?.dataset.s0UploadUrl;
                const studentId = wrap?.dataset.s0Student;
                const status = wrap?.querySelector('[data-s0-upload-status]');
                if (!file || !url || !studentId) return;

                if (file.type !== 'application/pdf' && !/\.pdf$/i.test(file.name)) {
                    if (status) {
                        status.textContent = 'รับเฉพาะไฟล์ PDF';
                        status.classList.remove('hidden', 'text-emerald-700');
                        status.classList.add('text-red-700');
                    }
                    input.value = '';
                    return;
                }

                const fd = new FormData();
                fd.append('file', file);
                fd.append('file_type', 's0_letter');
                fd.append('student_id', studentId);

                if (status) {
                    status.textContent = 'กำลังอัปโหลด...';
                    status.classList.remove('hidden', 'text-red-700', 'text-emerald-700');
                    status.classList.add('text-[#7A4A3A]');
                }

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                        body: fd,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        throw new Error(data.message || data.errors?.file?.[0] || 'อัปโหลดไม่สำเร็จ');
                    }

                    const row = wrap.querySelector('[data-s0-file-row]');
                    row?.querySelector('[data-s0-file-missing]')?.remove();
                    let link = row?.querySelector('[data-s0-file-link]');
                    if (!link && row) {
                        link = document.createElement('a');
                        link.className = 'text-[11px] font-semibold text-[#854d0e] underline ml-1.5 truncate max-w-[10rem] inline-block align-bottom';
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.dataset.s0FileLink = '';
                        link.textContent = 'PDF';
                        const uploadLabel = row.querySelector('label');
                        row.insertBefore(link, uploadLabel);
                    }
                    if (link) {
                        link.href = data.file?.url || link.href;
                        link.title = data.file?.original_name || file.name;
                    }
                    const statusText = wrap.querySelector('[data-s0-status-text]');
                    if (statusText) {
                        statusText.textContent = 'มีบันทึก';
                        statusText.classList.remove('text-amber-800');
                        statusText.classList.add('text-emerald-800');
                    }
                    const uploadLabel = wrap.querySelector('[data-s0-upload-label]');
                    if (uploadLabel) uploadLabel.textContent = 'เปลี่ยน PDF';
                    if (status) {
                        status.textContent = 'อัปโหลดแล้ว';
                        status.classList.remove('text-[#7A4A3A]', 'text-red-700');
                        status.classList.add('text-emerald-700');
                    }
                } catch (err) {
                    if (status) {
                        status.textContent = err.message || 'อัปโหลดไม่สำเร็จ';
                        status.classList.remove('text-[#7A4A3A]', 'text-emerald-700');
                        status.classList.add('text-red-700');
                    }
                } finally {
                    input.value = '';
                }
            });
        });
    })();
</script>
@if (session('thesis_submitted'))
<script>
    document.getElementById('thesis-submit-modal-close')?.addEventListener('click', () => {
        document.getElementById('thesis-submit-modal')?.remove();
    });
    document.getElementById('thesis-submit-modal')?.addEventListener('click', (e) => {
        if (e.target === e.currentTarget) e.currentTarget.remove();
    });
</script>
@endif
@endpush
