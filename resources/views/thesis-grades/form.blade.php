@extends('layouts.scigrad')

@php
    $editable = $report === null || $report->isEditable();
    $title = $report ? $report->displayCode().' กลุ่ม '.$report->paddedSection() : 'ส่งผลวิชาใหม่';
@endphp

@section('title', $title.' — วิทยานิพนธ์ / การศึกษาอิสระ')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('thesis-grades.index', ['term' => $term, 'year' => $year]) }}" class="text-[#8B4513] hover:underline">วิทยานิพนธ์ / การศึกษาอิสระ</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">{{ $report ? 'รายการส่งผล' : 'ส่งผลวิชาใหม่' }}</span>
@endsection

@push('styles')
<style>
    .thesis-stepper { display: flex; gap: .5rem; }
    .thesis-step { flex: 1; text-align: center; }
    .thesis-step .dot {
        width: 2rem; height: 2rem; border-radius: 9999px; margin: 0 auto .35rem;
        display: flex; align-items: center; justify-content: center;
        font-size: .8rem; font-weight: 700; border: 2px solid #fde68a; background: #fff; color: #a16207;
    }
    .thesis-step.active .dot { background: #a16207; border-color: #a16207; color: #fff; }
    .thesis-step.done .dot { background: #166534; border-color: #166534; color: #fff; }
    .thesis-panel { display: none; }
    .thesis-panel.active { display: block; }
    .student-card { border: 1px solid #fde68a; background: #fffbeb; border-radius: .9rem; }
    .student-card.is-overdue { border-color: #fca5a5; background: #fef2f2; }
    .student-card.is-ready { border-color: #86efac; background: #f0fdf4; }
    .file-drop {
        border: 1.5px dashed #eab308; border-radius: .85rem; background: #fffbeb;
        padding: 1.25rem; text-align: center; cursor: pointer;
    }
    .file-drop.dragover { background: #fef9c3; border-color: #a16207; }
    .suggest-list {
        position: absolute; z-index: 20; left: 0; right: 0; top: 100%;
        background: #fff; border: 1px solid #fde68a; border-radius: .6rem;
        margin-top: .25rem; max-height: 16rem; overflow: auto;
        box-shadow: 0 10px 24px rgba(120, 53, 15, .12);
    }
    .suggest-item { padding: .55rem .75rem; cursor: pointer; font-size: .85rem; }
    .suggest-item:hover, .suggest-item.active { background: #fef9c3; }
</style>
@endpush

@section('content')
<div
    id="thesis-form-root"
    data-editable="{{ $editable ? '1' : '0' }}"
    data-report-id="{{ $report?->thesis_grade_id }}"
    data-search-url="{{ url('/api/subjects/search-thesis') }}"
    data-upload-url="{{ $report ? route('thesis-grades.files.store', $report) : '' }}"
    data-file-base="{{ $report ? url('/thesis-grades/'.$report->thesis_grade_id.'/files') : '' }}"
    data-initial-step="{{ $step }}"
    data-s0-form-url="{{ $s0FormUrl }}"
>
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <h2 class="text-xl font-bold text-[#5C2E1F]">{{ $report ? $report->displayCode().' · กลุ่ม '.$report->paddedSection() : 'ส่งผลการเรียนวิชาใหม่' }}</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">
                ให้เกรดที่
                <a href="{{ $regUrl }}" target="_blank" rel="noopener" class="underline text-[#a16207]">REG</a>
                ก่อน แล้วส่งเอกสารในระบบนี้
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('thesis-grades.index', ['term' => $term, 'year' => $year]) }}" class="px-3 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">กลับรายการของฉัน</a>
            @if ($report && $report->files->isNotEmpty())
                <a href="{{ route('thesis-grades.files.zip', $report) }}" class="px-3 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ดาวน์โหลดไฟล์</a>
            @endif
        </div>
    </div>

    @if ($report)
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs px-2 py-0.5 rounded-full {{ $report->statusChipClass() }}">{{ $report->statusLabel() }}</span>
            <span class="text-xs text-[#7A4A3A]/70">จะถูกตั้งชื่อเป็น <span class="font-semibold text-[#854d0e]">{{ $report->tsFilename() }}</span></span>
        </div>
    @endif

    @if ($report?->status === 'returned' && $report->return_reason)
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">สาขาส่งกลับให้แก้ไข</p>
            <p class="mt-1">{{ $report->return_reason }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('submit_errors'))
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <p class="font-semibold">ยังส่งเข้าสาขาไม่ได้</p>
            <ul class="list-disc pl-5 mt-1 space-y-0.5">
                @foreach (session('submit_errors') as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="thesis-stepper mb-5">
        <button type="button" class="thesis-step" data-go-step="1">
            <div class="dot">1</div>
            <div class="text-xs text-[#7A4A3A]">รายวิชา</div>
        </button>
        <button type="button" class="thesis-step" data-go-step="2">
            <div class="dot">2</div>
            <div class="text-xs text-[#7A4A3A]">ตรวจความครบถ้วน</div>
        </button>
        <button type="button" class="thesis-step" data-go-step="3">
            <div class="dot">3</div>
            <div class="text-xs text-[#7A4A3A]">ไฟล์และส่ง</div>
        </button>
    </div>

    <form method="POST" action="{{ $report ? route('thesis-grades.update', $report) : route('thesis-grades.store') }}" id="thesis-form">
        @csrf
        @if ($report)
            @method('PUT')
        @endif
        <input type="hidden" name="intent" id="form-intent" value="draft">
        <input type="hidden" name="step" id="form-step" value="{{ $step }}">

        <div class="thesis-panel" data-step="1">
            <div class="form-section rounded-xl p-5 space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                        <select name="term" @disabled(! $editable) class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                            <option value="1" @selected((int) old('term', $report?->term ?? $term) === 1)>ภาคต้น</option>
                            <option value="2" @selected((int) old('term', $report?->term ?? $term) === 2)>ภาคปลาย</option>
                            <option value="3" @selected((int) old('term', $report?->term ?? $term) === 3)>ภาคการศึกษาพิเศษ</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                        <select name="year" @disabled(! $editable) class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                            @foreach ($years as $y)
                                <option value="{{ $y }}" @selected((int) old('year', $report?->year ?? $year) === (int) $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="relative">
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">รหัส / ชื่อวิชา</label>
                    <input type="text" id="subject-search" value="{{ old('subject_code', $report?->subject_code ?? '') }}{{ old('subject', $report?->subject ?? '') ? ' — '.old('subject', $report?->subject ?? '') : '' }}"
                           autocomplete="off" @disabled(! $editable)
                           placeholder="พิมพ์ THESIS, DISSERTATION, INDEPENDENT STUDY หรือรหัสวิชา"
                           class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <input type="hidden" name="subject_code" id="subject_code" value="{{ old('subject_code', $report?->subject_code ?? '') }}">
                    <input type="hidden" name="subject" id="subject" value="{{ old('subject', $report?->subject ?? '') }}">
                    <div id="subject-suggest" class="suggest-list hidden"></div>
                    <p class="text-xs text-[#7A4A3A]/70 mt-1">ค้นได้เฉพาะวิชาวิทยานิพนธ์ / ดุษฎีนิพนธ์ / การศึกษาอิสระ</p>
                    <button type="button" id="toggle-manual-subject" class="mt-2 text-xs text-[#a16207] underline">กรอกรหัสและชื่อวิชาเอง</button>
                    <div id="manual-subject" class="hidden grid sm:grid-cols-2 gap-3 mt-2">
                        <label class="text-xs text-[#7A4A3A]">รหัสวิชา
                            <input type="text" id="manual_subject_code" @disabled(! $editable) value="{{ old('subject_code', $report?->subject_code ?? '') }}" class="mt-1 w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" placeholder="SC123999">
                        </label>
                        <label class="text-xs text-[#7A4A3A]">ชื่อวิชา (ต้องมีคำว่า THESIS / DISSERTATION / INDEPENDENT STUDY)
                            <input type="text" id="manual_subject_name" @disabled(! $editable) value="{{ old('subject', $report?->subject ?? '') }}" class="mt-1 w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" placeholder="THESIS">
                        </label>
                    </div>
                </div>

                <div class="max-w-xs">
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">กลุ่มเรียน</label>
                    <input type="text" name="section" value="{{ old('section', $report?->paddedSection() ?? '01') }}" @disabled(! $editable)
                           class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" placeholder="01">
                </div>
            </div>
        </div>

        <div class="thesis-panel" data-step="2">
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 mb-4">
                <p class="font-semibold">ตัวช่วยตรวจเค้าโครง</p>
                <p class="mt-1 leading-relaxed">ปริญญาโทต้องได้รับอนุมัติเค้าโครงภายใน 2 ภาคที่มีการลงวิทยานิพนธ์ · ปริญญาเอกภายใน 4 ภาค หากเลยกำหนดและให้ S=0 ต้องแนบหนังสือชี้แจง — ระเบียบ พ.ศ. 2566 ยกเลิกการตกออกจาก S=0 สองภาคติดแล้ว</p>
            </div>

            <div id="student-summary" class="grid sm:grid-cols-3 gap-3 mb-4 text-sm"></div>
            <div id="student-list" class="space-y-3"></div>

            @if ($editable)
                <div class="flex flex-wrap gap-2 mt-4">
                    <button type="button" id="add-student" class="px-3 py-2 bg-[#a16207] text-white rounded-lg text-sm font-semibold hover:bg-[#854d0e]">+ เพิ่มนักศึกษา</button>
                    <button type="button" id="toggle-paste" class="px-3 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">วางจาก Excel / CSV</button>
                </div>
                <div id="paste-box" class="hidden mt-3">
                    <textarea id="paste-input" rows="4" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm" placeholder="รหัสนักศึกษา, ชื่อ, ระดับ(โท/เอก), จำนวนภาค, อนุมัติเค้าโครง(1/0), เกรด, หน่วยกิต, ครบหลักสูตร(1/0), วันที่สอบ"></textarea>
                    <button type="button" id="apply-paste" class="mt-2 px-3 py-1.5 bg-white border border-amber-300 rounded-lg text-sm">นำเข้า</button>
                </div>
            @endif
        </div>

        <div class="thesis-panel" data-step="3">
            <div class="form-section rounded-xl p-5 mb-4">
                <h3 class="font-semibold text-[#5C2E1F] mb-1">ใบส่งเกรดวิทยานิพนธ์ (TS)</h3>
                <p class="text-sm text-[#7A4A3A]/80 mb-3">อัปโหลด PDF ที่พิมพ์จาก REG มี barcode และลงนามดิจิทัลแล้ว ระบบจะตั้งชื่อให้เอง</p>
                <p class="text-xs font-semibold text-[#854d0e] mb-3" id="ts-name-preview">TS-รหัสวิชา-กลุ่ม-ภาค-ปี.pdf</p>

                @if ($editable && $report)
                    <label class="file-drop block" id="ts-drop">
                        <input type="file" accept="application/pdf" class="hidden" id="ts-input">
                        <p class="font-medium text-[#854d0e]">ลากวางหรือคลิกเพื่อเลือก PDF</p>
                        <p class="text-xs text-[#7A4A3A]/70 mt-1">เฉพาะ .pdf ไม่เกิน 15 MB</p>
                    </label>
                @elseif ($editable)
                    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">บันทึกร่างก่อน จึงอัปโหลดไฟล์ได้</p>
                @endif
                <div id="ts-files" class="mt-3 space-y-2"></div>
            </div>

            <div class="form-section rounded-xl p-5 mb-4">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <h3 class="font-semibold text-[#5C2E1F]">หนังสือชี้แจง S=0</h3>
                    <a href="{{ $s0FormUrl }}" target="_blank" rel="noopener" class="text-sm text-[#a16207] underline">เปิดแบบฟอร์มบันทึกชี้แจง</a>
                </div>
                <p class="text-sm text-[#7A4A3A]/80 mb-3">แนบรายคนเฉพาะนักศึกษาที่เลยกำหนดเค้าโครงและให้ S=0</p>
                <div id="s0-slots" class="space-y-2"></div>
            </div>

            <div class="form-section rounded-xl p-5 space-y-3">
                <label class="flex items-start gap-2 text-sm text-[#5C2E1F]">
                    <input type="checkbox" name="checked_proposal" value="1" class="mt-1" @checked(old('checked_proposal', $report?->checked_proposal ?? false)) @disabled(! $editable)>
                    <span>ตรวจสอบข้อมูลนักศึกษาที่ครบกำหนดอนุมัติเค้าโครงแล้ว (ป.โท ภายใน 2 ภาค / ป.เอก ภายใน 4 ภาค)</span>
                </label>
                <label class="flex items-start gap-2 text-sm text-[#5C2E1F]">
                    <input type="checkbox" name="checked_signed" value="1" class="mt-1" @checked(old('checked_signed', $report?->checked_signed ?? false)) @disabled(! $editable)>
                    <span>ไฟล์ใบส่งเกรดได้ลงนามด้วยลายมือชื่อดิจิทัลแล้ว</span>
                </label>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mt-5">
            <button type="button" id="prev-step" class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ย้อนกลับ</button>
            <div class="flex flex-wrap gap-2">
                @if ($editable)
                    <button type="submit" class="px-4 py-2 border border-amber-300 rounded-lg text-sm font-medium text-[#5C2E1F] hover:bg-amber-50" data-intent="draft">บันทึกร่าง</button>
                    <button type="button" id="next-step" class="px-4 py-2 bg-white border border-amber-300 rounded-lg text-sm font-semibold text-[#854d0e] hover:bg-amber-50">ถัดไป</button>
                    <button type="submit" class="px-4 py-2 bg-[#a16207] text-white rounded-lg text-sm font-semibold hover:bg-[#854d0e]" data-intent="submit">ส่งเข้าสาขา</button>
                @endif
                @if ($report?->isDeletable())
                    <button type="button" id="delete-draft" class="px-4 py-2 text-sm text-red-700 hover:underline">ลบร่าง</button>
                @endif
            </div>
        </div>
    </form>

    @if ($report?->isDeletable())
        <form method="POST" action="{{ route('thesis-grades.destroy', $report) }}" id="delete-form" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
    window.THESIS_FORM = {
        students: @json($report?->students->map(fn ($s) => [
            'id' => $s->student_id,
            'student_code' => $s->student_code,
            'student_name' => $s->student_name,
            'degree' => $s->degree,
            'thesis_terms_count' => $s->thesis_terms_count,
            'proposal_approved' => (bool) $s->proposal_approved,
            'grade' => $s->grade ?: 'S',
            'progress_credits' => $s->progress_credits,
            'completed' => (bool) $s->completed,
            'defense_date' => $s->defense_date?->toDateString(),
            'note' => $s->note,
        ]) ?? []),
        files: @json($report?->files->map(fn ($f) => [
            'file_id' => $f->file_id,
            'file_type' => $f->resolvedType(),
            'original_name' => $f->original_name,
            'student_id' => $f->student_id,
            'url' => route('thesis-grades.files.show', [$report, $f]),
        ]) ?? []),
        oldStudents: @json(old('students', [])),
    };
</script>
<script src="{{ asset('js/thesis-grade-form.js') }}"></script>
@endpush
