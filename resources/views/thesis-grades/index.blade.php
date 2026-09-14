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
    .thesis-file-panel { border-radius: .75rem; padding: .75rem .85rem; }
    .thesis-file-instructor { background: #fffbeb; border: 1px solid #fde68a; }
    .thesis-file-dept { background: #f0fdfa; border: 1px solid #99f6e4; }
    .thesis-file-chip {
        display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        background: #fff; border-radius: .5rem; padding: .35rem .55rem;
        font-size: .75rem; line-height: 1.3;
    }
    .thesis-file-chip a { color: #854d0e; font-weight: 600; min-width: 0; }
    .thesis-file-dept .thesis-file-chip a { color: #0f766e; }
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
    <div class="thesis-hero rounded-2xl p-5 mb-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-[#a16207]">THESIS · DISSERTATION · INDEPENDENT STUDY</p>
                <h2 class="text-xl font-bold text-[#854d0e] mt-1">ส่งผลการเรียนวิทยานิพนธ์ / การศึกษาอิสระ</h2>
                <p class="text-sm text-[#7A4A3A]/80 mt-1.5 max-w-2xl leading-relaxed">
                    ให้เกรดที่ REG ตาม มข.30 แล้วอัปโหลดใบส่งเกรดที่ลงนามแล้วเข้าที่นี่ — รายวิชานี้ส่งได้ตลอด ไม่ผูกปฏิทินสอบไล่
                    หลังส่งเข้าสาขา หากสาขาหรือ Admin กลางยังไม่เปลี่ยนสถานะ อาจารย์แก้ไขหรือลบรายการนั้นได้
                    เมื่อสาขากดผ่านที่ประชุมสาขาวิชาแล้ว จะแก้หรือลบไม่ได้ (สาขาไม่จำเป็นต้องอัปโหลดไฟล์เพิ่ม)
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
                    $instructorFiles = $report->instructorFiles();
                    $chairFiles = $report->chairFiles();
                    $status = $report->normalizedStatus();
                @endphp
                <article class="thesis-card rounded-xl p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-[#5C2E1F]">{{ $report->displayCode() }} <span class="text-[#7A4A3A] font-medium">กลุ่ม {{ $report->paddedSection() }}</span></p>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200">{{ $report->courseKindLabel() }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $report->statusChipClass() }}">{{ $report->statusLabel() }}</span>
                            </div>
                            <p class="text-sm text-[#7A4A3A] mt-1">{{ $report->subject }}</p>
                            <p class="text-xs text-[#7A4A3A]/70 mt-1">
                                {{ $report->termLabel() }} {{ $report->year }} · นักศึกษา {{ $report->students->count() }} คน
                            </p>
                            @if ($status === 'submitted')
                                <p class="text-xs text-amber-800 mt-1.5">รอสาขากดผ่านที่ประชุมสาขาวิชา — ยังแก้ไขหรือลบได้จนกว่าสาขาหรือ Admin กลางจะเปลี่ยนสถานะ</p>
                            @elseif ($status === 'received')
                                <p class="text-xs text-emerald-800 mt-1.5">สาขาผ่านที่ประชุมสาขาวิชาแล้ว@if ($chairFiles->isEmpty()) โดยไม่มีไฟล์เพิ่มจากสาขา@endif</p>
                            @endif
                            @if ($overdue || $missingS0 || $missingDefense)
                                <p class="text-xs text-amber-800 mt-1.5">
                                    @if ($overdue) เลยกำหนดเค้าโครง {{ $overdue }} คน @endif
                                    @if ($missingS0) · ขาดหนังสือ S=0 {{ $missingS0 }} คน @endif
                                    @if ($missingDefense) · ขาดวันที่สอบ {{ $missingDefense }} คน @endif
                                </p>
                            @endif
                            @if ($status === 'returned' && $report->return_reason)
                                <p class="text-xs text-red-700 mt-1">สาขาส่งกลับ: {{ $report->return_reason }}</p>
                            @endif
                        </div>
                        <div class="thesis-actions shrink-0">
                            @if ($report->isEditable())
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
                        </div>
                    </div>
                    @include('thesis-grades.partials.s0-print-buttons', ['report' => $report, 'role' => 'instructor'])

                    <div class="grid md:grid-cols-2 gap-3 mt-4">
                        <section class="thesis-file-panel thesis-file-instructor">
                            <p class="text-xs font-bold tracking-wide text-[#854d0e] mb-2">ไฟล์ที่คุณอัปโหลด</p>
                            <div class="space-y-1.5">
                                @forelse ($instructorFiles as $file)
                                    <div class="thesis-file-chip">
                                        <a href="{{ route('thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener" class="truncate" title="{{ $file->original_name }}">
                                            {{ $file->typeLabel() }} · {{ $file->original_name }}
                                        </a>
                                        <a href="{{ route('thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener" class="shrink-0">เปิด</a>
                                    </div>
                                @empty
                                    <p class="text-xs text-[#7A4A3A]/70">ยังไม่มีใบ TS หรือหนังสือ S=0</p>
                                @endforelse
                            </div>
                        </section>

                        <section class="thesis-file-panel thesis-file-dept">
                            <p class="text-xs font-bold tracking-wide text-teal-800 mb-2">เอกสารจาก Admin สาขา</p>
                            <div class="space-y-1.5">
                                @forelse ($chairFiles as $file)
                                    <div class="thesis-file-chip">
                                        <a href="{{ route('thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener" class="truncate" title="{{ $file->original_name }}">
                                            {{ $file->original_name }}
                                        </a>
                                        <a href="{{ route('thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener" class="shrink-0">เปิด</a>
                                    </div>
                                @empty
                                    <p class="text-xs text-teal-800/80">สาขายังไม่ได้อัปโหลดเอกสารเพิ่ม — ไม่บังคับ รอสาขากดผ่านที่ประชุมสาขาวิชา</p>
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
