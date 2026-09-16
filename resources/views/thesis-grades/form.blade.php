@extends('layouts.scigrad')

@php
    $editable = $report === null || $report->isEditable();
    $title = $report ? $report->displayCode().' กลุ่ม '.$report->paddedSection() : 'ส่งผลวิชาใหม่';
    $subjectChoices = $subjectChoices ?? ['THESIS', 'INDEPENDENT STUDY', 'DISSERTATION'];
    $rawSubject = (string) old('subject', $report?->subject ?? '');
    $selectedSubject = app(\App\Services\ThesisGrade\ThesisGradePdfParser::class)->normalizeSubjectChoice($rawSubject)
        ?? (in_array($rawSubject, $subjectChoices, true) ? $rawSubject : '');
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
    .wizard-trail {
        display: flex; flex-wrap: nowrap; align-items: stretch;
        list-style: none; padding: 0; margin: 0;
    }
    .wizard-step-item {
        flex: 1 1 0; min-width: 0;
        display: flex; flex-direction: column; align-items: center;
        position: relative; margin-left: -10px;
        cursor: pointer; background: transparent; border: 0; padding: 0;
        --arrow: #d1d5db; --ink: #6b7280; --label: #9ca3af;
    }
    .wizard-step-item:nth-child(1) { z-index: 3; margin-left: 0; }
    .wizard-step-item:nth-child(2) { z-index: 2; }
    .wizard-step-item:nth-child(3) { z-index: 1; }
    .wizard-chevron {
        width: 100%; min-height: 2.35rem;
        display: flex; align-items: center; justify-content: center;
        background: var(--arrow); color: var(--ink);
        font-size: 0.8rem; font-weight: 800;
        clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%, 14px 50%);
        transition: background .25s ease, color .25s ease, filter .25s ease, transform .25s ease;
    }
    .wizard-step-item:first-child .wizard-chevron {
        clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%);
    }
    .wizard-step-item:last-child .wizard-chevron {
        clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%, 14px 50%);
    }
    .wizard-arrow-num { position: relative; z-index: 1; }
    .wizard-label {
        font-size: .62rem; line-height: 1.2; color: var(--label);
        max-width: 7.2rem; text-align: center; margin-top: 0.28rem;
    }
    .wizard-step-item.is-current[data-tone="1"],
    .wizard-step-item.is-done[data-tone="1"] { --arrow: #dc2626; --ink: #fff; --label: #b91c1c; }
    .wizard-step-item.is-current[data-tone="2"],
    .wizard-step-item.is-done[data-tone="2"] { --arrow: #ea580c; --ink: #fff; --label: #c2410c; }
    .wizard-step-item.is-current[data-tone="3"],
    .wizard-step-item.is-done[data-tone="3"] { --arrow: #f97316; --ink: #fff; --label: #c2410c; }
    .wizard-step-item.is-current .wizard-chevron { transform: scale(1.04); filter: drop-shadow(0 2px 5px rgba(0,0,0,.18)); }
    .wizard-step-item.is-current .wizard-label { font-weight: 700; }
    .wizard-step-item:hover:not(.is-current) .wizard-chevron { filter: brightness(0.96); }
    @@media (min-width: 640px) {
        .wizard-label { font-size: .7rem; }
        .wizard-chevron { min-height: 2.5rem; font-size: .9rem; }
    }
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
    .field-needs-review {
        border-color: #ef4444 !important;
        background: #fef2f2 !important;
        box-shadow: 0 0 0 1px rgba(239, 68, 68, .25);
    }
    .field-hint-review {
        display: block;
        margin-top: .25rem;
        font-size: .7rem;
        line-height: 1.35;
        color: #b91c1c;
    }
    label.confirm-missing {
        background: #fef2f2;
        outline: 1px solid #fca5a5;
    }
</style>
@endpush

@section('content')
<div
    id="thesis-form-root"
    data-editable="{{ $editable ? '1' : '0' }}"
    data-report-id="{{ $report?->thesis_grade_id }}"
    data-search-url="{{ url('/api/subjects/search-thesis') }}"
    data-quick-upload-url="{{ route('thesis-grades.quick-upload') }}"
    data-upload-url="{{ $report ? route('thesis-grades.files.store', $report) : '' }}"
    data-file-base="{{ $report ? url('/thesis-grades/'.$report->thesis_grade_id.'/files') : '' }}"
    data-initial-step="{{ $step }}"
    data-s0-form-url="{{ $s0FormUrl }}"
    data-s0-letter-url="{{ $report ? route('thesis-grades.s0-letter', $report) : '' }}"
    data-s0-letter-student-tpl="{{ $report ? url('/thesis-grades/'.$report->thesis_grade_id.'/students/__SID__/s0-letter') : '' }}"
    data-s0-docx-student-tpl="{{ $report ? url('/thesis-grades/'.$report->thesis_grade_id.'/students/__SID__/s0.docx') : '' }}"
>
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            @php
                $headingCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) old('subject_code', $report?->subject_code ?? '')) ?: '') ?: 'ยังไม่มีรหัสวิชา';
                $headingSubject = $selectedSubject !== '' ? $selectedSubject : 'ยังไม่เลือกชื่อวิชา';
                $headingSection = str_pad((string) ((int) preg_replace('/\D/', '', (string) old('section', $report?->paddedSection() ?? '01')) ?: 1), 2, '0', STR_PAD_LEFT);
            @endphp
            <p class="text-xs text-[#7A4A3A]">รหัสวิชา · ชื่อวิชา · กลุ่ม</p>
            <h2 id="course-context-text" class="text-xl font-bold text-[#5C2E1F] mt-0.5">{{ $headingCode }} · {{ $headingSubject }} · กลุ่ม {{ $headingSection }}</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">
                ให้เกรดที่
                <a href="{{ $regUrl }}" target="_blank" rel="noopener" class="underline text-[#a16207]">REG</a>
                ก่อน แล้วอัปโหลดใบ มข.11 — ระบบอ่านข้อมูลและเก็บไฟล์บน S3 ให้เอง
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

    @if ($report?->normalizedStatus() === 'returned' && $report->return_reason)
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-semibold">สาขาส่งกลับให้แก้ไข — สามารถแก้ไขแล้วส่งเข้าสาขาใหม่ หรือลบรายการนี้ได้</p>
            <p class="mt-1">{{ $report->return_reason }}</p>
        </div>
    @endif

    @if (session('pdf_warnings'))
        @php
            $pdfWarnings = array_values(array_filter((array) session('pdf_warnings')));
            $isImagePdf = collect($pdfWarnings)->contains(
                fn ($w) => \App\Support\ImageOnlyPdfMessage::matches((string) $w)
            );
        @endphp
        <div class="mb-4 rounded-xl border px-4 py-3 text-sm {{ $isImagePdf ? 'border-red-200 bg-red-50 text-red-900' : 'border-amber-200 bg-amber-50 text-amber-950' }}">
            <p class="font-semibold">{{ $isImagePdf ? 'ไฟล์นี้เป็น PDF แบบภาพ — อ่านเนื้อหาไม่ได้' : 'อ่านจาก PDF แล้ว — โปรดตรวจข้อมูล' }}</p>
            <ul class="list-disc pl-5 mt-1 space-y-0.5">
                @foreach ($pdfWarnings as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('signature_message'))
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            {{ session('signature_message') }}
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

    <div class="form-section rounded-lg p-5 mb-5">
        <ol id="wizard-stepper" class="wizard-trail" aria-label="ขั้นตอนการส่งผลการเรียนวิทยานิพนธ์">
            <li class="wizard-step-item {{ (int) $step === 1 ? 'is-current' : ((int) $step > 1 ? 'is-done' : '') }}" data-wizard-dot="1" data-tone="1" data-go-step="1" role="button" tabindex="0">
                <span class="wizard-chevron" aria-hidden="true"><span class="wizard-arrow-num">1</span></span>
                <span class="wizard-label">รายวิชา</span>
            </li>
            <li class="wizard-step-item {{ (int) $step === 2 ? 'is-current' : ((int) $step > 2 ? 'is-done' : '') }}" data-wizard-dot="2" data-tone="2" data-go-step="2" role="button" tabindex="0">
                <span class="wizard-chevron" aria-hidden="true"><span class="wizard-arrow-num">2</span></span>
                <span class="wizard-label">ตรวจความครบถ้วน</span>
            </li>
            <li class="wizard-step-item {{ (int) $step === 3 ? 'is-current' : '' }}" data-wizard-dot="3" data-tone="3" data-go-step="3" role="button" tabindex="0">
                <span class="wizard-chevron" aria-hidden="true"><span class="wizard-arrow-num">3</span></span>
                <span class="wizard-label">ไฟล์และส่ง</span>
            </li>
        </ol>
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
                @if ($editable && ! $report)
                    <div>
                        <label class="block text-sm font-medium text-[#5C2E1F] mb-1">อัปโหลดใบ มข.11 / TS (แนะนำ)</label>
                        <p class="text-xs text-[#7A4A3A]/80 mb-2">ตั้งชื่อไฟล์อย่างไรก็ได้ — ระบบอ่านข้อความจาก PDF (THESIS / INDEPENDENT STUDY / DISSERTATION) แล้วกรอกให้อัตโนมัติ หากรหัสวิชาไม่พบในฐานข้อมูล ยังใช้ค่าจากไฟล์ได้หรือแก้เอง</p>
                        <label class="file-drop block" id="quick-drop">
                            <input type="file" accept="application/pdf" class="hidden" id="quick-input">
                            <p class="font-medium text-[#854d0e]" id="quick-drop-label">ลากวางหรือคลิกเพื่อเลือก PDF</p>
                            <p class="text-xs text-[#7A4A3A]/70 mt-1">เฉพาะ .pdf ไม่เกิน 15 MB</p>
                        </label>
                        <div id="quick-upload-status" class="hidden mt-2 rounded-lg border px-3 py-2 text-sm leading-relaxed"></div>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-[#7A4A3A]/70">
                        <span class="flex-1 border-t border-amber-200"></span>
                        <span>หรือกรอกเอง</span>
                        <span class="flex-1 border-t border-amber-200"></span>
                    </div>
                @endif

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                        <select name="term" @disabled(! $editable) class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" data-review-field="term">
                            <option value="1" @selected((int) old('term', $report?->term ?? $term) === 1)>ภาคต้น</option>
                            <option value="2" @selected((int) old('term', $report?->term ?? $term) === 2)>ภาคปลาย</option>
                            <option value="3" @selected((int) old('term', $report?->term ?? $term) === 3)>ภาคการศึกษาพิเศษ</option>
                        </select>
                        <span class="field-hint-review hidden" data-review-hint="term"></span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                        <select name="year" @disabled(! $editable) class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" data-review-field="year">
                            @foreach ($years as $y)
                                <option value="{{ $y }}" @selected((int) old('year', $report?->year ?? $year) === (int) $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                        <span class="field-hint-review hidden" data-review-hint="year"></span>
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-white p-4 space-y-3">
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="relative flex-1 min-w-[12rem]">
                            <label class="block text-sm font-medium text-[#5C2E1F] mb-1">รหัสวิชา</label>
                            <input type="text" name="subject_code" id="subject_code"
                                   value="{{ old('subject_code', $report?->subject_code ?? '') }}"
                                   autocomplete="off" @disabled(! $editable)
                                   placeholder="พิมพ์บางส่วน เช่น SC05"
                                   class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white"
                                   data-review-field="subject_code">
                            <div id="subject-suggest" class="suggest-list hidden"></div>
                            <span class="field-hint-review hidden" data-review-hint="subject_code"></span>
                        </div>
                        <div class="flex-1 min-w-[12rem]">
                            <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ชื่อวิชา <span class="font-normal text-[#7A4A3A]/70">(แสดงข้างรหัส)</span></label>
                            <select name="subject" id="subject" @disabled(! $editable)
                                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white"
                                    data-review-field="subject">
                                <option value="">— เลือกชนิดวิชา —</option>
                                @foreach ($subjectChoices as $choice)
                                    <option value="{{ $choice }}" @selected($selectedSubject === $choice)>{{ $choice }}</option>
                                @endforeach
                            </select>
                            <span class="field-hint-review hidden" data-review-hint="subject"></span>
                        </div>
                        <div class="w-28">
                            <label class="block text-sm font-medium text-[#5C2E1F] mb-1">กลุ่ม</label>
                            <input type="text" name="section" id="section" value="{{ old('section', $report?->paddedSection() ?? '01') }}" @disabled(! $editable)
                                   class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" placeholder="01"
                                   data-review-field="section">
                            <span class="field-hint-review hidden" data-review-hint="section"></span>
                        </div>
                    </div>
                    <p id="subject-catalog-hint" class="text-xs text-[#7A4A3A]/70">มีในฐานข้อมูล: พิมพ์แล้วเลือกรายการ · ไม่มี: กรอกเองได้ (ชื่อวิชาเลือก THESIS / INDEPENDENT STUDY / DISSERTATION)</p>
                </div>
            </div>
        </div>

        <div class="thesis-panel" data-step="2">
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 mb-4">
                <p class="font-semibold">ตัวช่วยตรวจเค้าโครง</p>
                <p class="mt-1 leading-relaxed">ปริญญาโทต้องได้รับอนุมัติเค้าโครงภายใน 2 ภาคที่มีการลงวิทยานิพนธ์ · ปริญญาเอกภายใน 4 ภาค หากให้ S=0 ต้องแนบบันทึกข้อความชี้แจง (PDF) — ระเบียบ พ.ศ. 2566 ยกเลิกการตกออกจาก S=0 สองภาคติดแล้ว</p>
                @if ($report)
                    <p class="mt-2">กด «พิมพ์บันทึก S=0» ที่นักศึกษาที่ได้ S=0 เพื่อพิมพ์หรือดาวน์โหลด Word ตามแบบฟอร์มชี้แจง โดยระบบเติมรหัสวิชา ชื่อวิชา กลุ่ม และภาค/ปีให้อัตโนมัติ</p>
                @endif
            </div>
            <div id="uncertain-review-banner" class="hidden mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">มีช่องที่ระบบอ่านจาก PDF ได้ไม่แน่ใจ</p>
                <p class="mt-1">ช่องที่มีกรอบสีแดง — กรุณาตรวจสอบหรือกรอกเองให้ถูกต้อง</p>
            </div>

            <div id="student-summary" class="grid sm:grid-cols-2 gap-3 mb-4 text-sm"></div>
            <div id="student-list" class="space-y-3"></div>

            @if ($editable)
                <div class="flex flex-wrap gap-2 mt-4">
                    <button type="button" id="add-student" class="px-3 py-2 bg-[#a16207] text-white rounded-lg text-sm font-semibold hover:bg-[#854d0e]">+ เพิ่มนักศึกษา</button>
                    @if ($report && $report->files->contains(fn ($f) => $f->resolvedType() === \App\Models\ThesisGradeFile::TYPE_TS_REPORT))
                        <button type="submit" form="reparse-ts-form" class="px-3 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">
                            อ่านหน่วยกิต/หมายเหตุจากใบส่งเกรดอีกครั้ง
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <div class="thesis-panel" data-step="3">
            <div class="form-section rounded-xl p-5 mb-4">
                <h3 class="font-semibold text-[#5C2E1F] mb-1">ใบส่งเกรดวิทยานิพนธ์ (มข.11 / TS)</h3>
                <p class="text-sm text-[#7A4A3A]/80 mb-3">อัปโหลด PDF ที่พิมพ์จาก REG มี barcode และลงนามดิจิทัลแล้ว ระบบจะตั้งชื่อให้เอง</p>
                <p class="text-xs font-semibold text-[#854d0e] mb-3" id="ts-name-preview">TS-รหัสวิชา-กลุ่ม-ภาค-ปี.pdf</p>
                <div id="ts-signature-banner" class="hidden mb-3 rounded-lg border px-3 py-2 text-sm leading-relaxed"></div>

                @if ($editable && $report)
                    <label class="file-drop block" id="ts-drop">
                        <input type="file" accept="application/pdf" class="hidden" id="ts-input">
                        <p class="font-medium text-[#854d0e]">ลากวางหรือคลิกเพื่อเลือก PDF</p>
                        <p class="text-xs text-[#7A4A3A]/70 mt-1">เฉพาะ .pdf ไม่เกิน 15 MB</p>
                    </label>
                @elseif ($editable)
                    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">ใช้ช่องอัปโหลดในขั้นที่ 1 หรือบันทึกร่างก่อน จึงอัปโหลดไฟล์เพิ่มได้</p>
                @endif
                <div id="ts-files" class="mt-3 space-y-2"></div>
            </div>

            <div class="form-section rounded-xl p-5 mb-4">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <h3 class="font-semibold text-[#5C2E1F]">บันทึกข้อความชี้แจง S=0</h3>
                    @if ($report)
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('thesis-grades.s0-letter', $report) }}" target="_blank" rel="noopener" class="text-sm text-[#a16207] underline">พิมพ์บันทึกข้อความ</a>
                            <a href="{{ route('thesis-grades.s0.docx', $report) }}" class="text-sm text-[#a16207] underline">ดาวน์โหลด Word</a>
                        </div>
                    @else
                        <a href="{{ $s0FormUrl }}" target="_blank" rel="noopener" class="text-sm text-[#a16207] underline">เปิดแบบฟอร์มบันทึกชี้แจง</a>
                    @endif
                </div>
                <p class="text-sm text-[#7A4A3A]/80 mb-3">แนบรายคนเป็นไฟล์ PDF สำหรับนักศึกษาที่ได้ S=0</p>
                <div id="s0-slots" class="space-y-2"></div>
            </div>

            @if ($report && $report->chairFiles()->isNotEmpty())
                <div class="form-section rounded-xl p-5 mb-4 border border-teal-200 bg-teal-50/50">
                    <h3 class="font-semibold text-teal-900 mb-1">เอกสารจาก Admin สาขา</h3>
                    <p class="text-sm text-teal-900/80 mb-3">สาขาอัปโหลดไฟล์นี้เพิ่มจากหน้ารับเรื่อง — คุณเปิดดูได้จากที่นี่</p>
                    <div class="space-y-2">
                        @foreach ($report->chairFiles() as $file)
                            <a href="{{ route('thesis-grades.files.show', [$report, $file]) }}" target="_blank" rel="noopener"
                               class="flex items-center justify-between gap-2 rounded-lg border border-teal-200 bg-white px-3 py-2 text-sm text-teal-800 hover:bg-teal-50">
                                <span class="truncate">{{ $file->original_name }}</span>
                                <span class="font-semibold shrink-0">เปิด</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @elseif ($report && $report->normalizedStatus() === 'submitted')
                <div class="rounded-xl border border-teal-200 bg-teal-50/60 px-4 py-3 text-sm text-teal-900 mb-4">
                    สาขาไม่จำเป็นต้องอัปโหลดไฟล์เพิ่ม — ทุกรายการต้องรอสาขากดผ่านที่ประชุมสาขาวิชา
                </div>
            @endif

            <div class="form-section rounded-xl p-5 space-y-3" id="submit-confirmations">
                <p class="font-semibold text-[#5C2E1F]">ก่อนส่งเข้าสาขา กรุณาติ๊กยืนยัน</p>
                <label data-confirm-check="checked_signed" class="flex items-start gap-2 text-sm text-[#5C2E1F] rounded-lg p-2 -mx-2">
                    <input type="checkbox" name="checked_signed" value="1" class="mt-1" @checked(old('checked_signed', $report?->checked_signed ?? false)) @disabled(! $editable)>
                    <span data-confirm-label>ไฟล์ใบส่งเกรดได้ลงนามด้วยลายมือชื่อดิจิทัลแล้ว</span>
                </label>
                <div id="submit-checklist-hint" class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-800">
                    <p class="font-semibold">ยังส่งเข้าสาขาไม่ได้ — กรุณาติ๊กยืนยันข้อที่ยังขาด:</p>
                    <ul id="submit-checklist-items" class="list-disc pl-5 mt-1 space-y-0.5"></ul>
                </div>
                <p id="submit-ready-hint" class="hidden text-sm font-medium text-green-800">ติ๊กยืนยันแล้ว กด «ส่งเข้าสาขา» ได้</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mt-5">
            <button type="button" id="prev-step" class="px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">ย้อนกลับ</button>
            <div class="flex flex-wrap gap-2">
                @if ($editable)
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700" data-intent="draft">บันทึกร่าง</button>
                    <button type="button" id="next-step" class="px-4 py-2 bg-sky-600 text-white rounded-lg text-sm font-semibold hover:bg-sky-700">ถัดไป</button>
                    <div class="flex flex-col items-end gap-1">
                        <button type="submit" id="submit-to-dept" class="px-4 py-2 bg-[#a16207] text-white rounded-lg text-sm font-semibold hover:bg-[#854d0e] {{ (int) $step === 3 ? '' : 'hidden' }}" data-intent="submit">ส่งเข้าสาขา</button>
                        <p id="submit-btn-hint" class="hidden max-w-sm text-right text-xs text-red-700 leading-snug"></p>
                    </div>
                @endif
                @if ($report?->isDeletable())
                    <button type="button" id="delete-draft" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700">
                        {{ $report->normalizedStatus() === 'draft' ? 'ลบร่าง' : 'ลบรายการ' }}
                    </button>
                @endif
            </div>
        </div>
    </form>

    @if ($report && $report->files->contains(fn ($f) => $f->resolvedType() === \App\Models\ThesisGradeFile::TYPE_TS_REPORT))
        <form id="reparse-ts-form" method="POST" action="{{ route('thesis-grades.reparse-ts', $report) }}" class="hidden">
            @csrf
        </form>
    @endif
    @if ($report?->isDeletable())
        <form method="POST" action="{{ route('thesis-grades.destroy', $report) }}" id="delete-form" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>

<div id="ts-upload-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white border border-amber-200 shadow-xl p-5">
        <h3 class="text-lg font-bold text-[#854d0e]">อัปโหลดใบส่งเกรดเรียบร้อย</h3>
        <p class="text-sm text-[#7A4A3A] mt-2 leading-relaxed">ต้องการส่งเกรดวิชาต่อไป หรือไปหน้าติดตามผลการส่งผลการเรียนวิทยานิพนธ์/การศึกษาอิสระ?</p>
        <div class="mt-5 flex flex-col gap-2">
            <a id="ts-modal-create" href="{{ route('thesis-grades.create', ['term' => $term ?? request('term'), 'year' => $year ?? request('year')]) }}"
               class="px-4 py-2.5 bg-[#a16207] text-white rounded-lg text-sm font-semibold text-center hover:bg-[#854d0e]">
                ส่งเกรดวิชาต่อไป
            </a>
            <a id="ts-modal-index" href="{{ route('thesis-grades.index', ['term' => $term ?? request('term'), 'year' => $year ?? request('year')]) }}"
               class="px-4 py-2.5 border border-amber-300 text-[#5C2E1F] rounded-lg text-sm font-semibold text-center hover:bg-amber-50">
                ไปหน้าติดตามผลการส่ง
            </a>
            <button type="button" id="ts-modal-close" class="px-4 py-2 text-sm text-[#7A4A3A] hover:underline">อยู่ในหน้านี้อีกสักครู่</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $pdfUncertain = session('pdf_uncertain', ['course' => [], 'students' => []]);
    $uncertainCourse = is_array($pdfUncertain['course'] ?? null) ? $pdfUncertain['course'] : [];
    $uncertainStudents = is_array($pdfUncertain['students'] ?? null) ? $pdfUncertain['students'] : [];

    $thesisFormStudents = ($report?->students ?? collect())->values()->map(function ($s, $i) use ($uncertainStudents) {
        return [
            'id' => $s->student_id,
            'student_code' => $s->student_code,
            'name_prefix' => $s->name_prefix,
            'first_name' => $s->first_name,
            'last_name' => $s->last_name,
            'student_name' => $s->displayName(),
            'degree' => $s->degree,
            'thesis_terms_count' => $s->thesis_terms_count,
            'proposal_approved' => (bool) $s->proposal_approved,
            'grade' => $s->grade ?: 'S',
            'credits_registered' => $s->credits_registered,
            'credits_passed' => $s->credits_passed,
            'progress_credits' => $s->progress_credits,
            'completed' => (bool) $s->completed,
            'defense_date' => $s->defense_date?->toDateString(),
            'note' => \App\Models\ThesisGradeStudent::sanitizeNote($s->note),
            'uncertain_fields' => is_array($uncertainStudents[$i] ?? null) ? $uncertainStudents[$i] : [],
        ];
    })->values();

    $thesisFormFiles = ($report?->files ?? collect())->map(function ($f) use ($report) {
        return [
            'file_id' => $f->file_id,
            'file_type' => $f->resolvedType(),
            'original_name' => $f->original_name,
            'student_id' => $f->student_id,
            'url' => route('thesis-grades.files.show', [$report, $f]),
        ];
    })->values();

    $oldStudents = collect(old('students', []))->values()->map(function ($row, $i) use ($uncertainStudents) {
        if (! is_array($row)) {
            return $row;
        }
        if (array_key_exists('note', $row)) {
            $row['note'] = \App\Models\ThesisGradeStudent::sanitizeNote($row['note']);
        }
        if (! isset($row['uncertain_fields']) && is_array($uncertainStudents[$i] ?? null)) {
            $row['uncertain_fields'] = $uncertainStudents[$i];
        }

        return $row;
    })->all();
@endphp
<script>
    window.THESIS_FORM = {
        students: @json($thesisFormStudents),
        files: @json($thesisFormFiles),
        oldStudents: @json($oldStudents),
        uncertainCourse: @json($uncertainCourse),
    };
</script>
<script src="{{ asset('js/image-only-pdf-guide.js') }}?v={{ filemtime(public_path('js/image-only-pdf-guide.js')) }}"></script>
<script src="{{ asset('js/thesis-grade-form.js') }}?v=16"></script>
@if (collect((array) session('pdf_warnings'))->contains(fn ($w) => \App\Support\ImageOnlyPdfMessage::matches((string) $w))
    || \App\Support\ImageOnlyPdfMessage::matches((string) session('error', '')))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.SciGradeImagePdfGuide?.show();
    });
</script>
@endif
@endpush
