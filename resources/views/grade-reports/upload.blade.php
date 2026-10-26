@extends('layouts.scigrad')

@section('title', 'อัปโหลดไฟล์ — SciGrade')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium flex items-center gap-1.5">
    <i data-lucide="upload-cloud" class="w-4 h-4 text-teal-700"></i>
    อัปโหลดไฟล์
</span>
@endsection

@push('styles')
<style>
    .upload-dropzone {
        background:
            radial-gradient(circle at 12% 20%, rgba(45, 212, 191, 0.16), transparent 42%),
            radial-gradient(circle at 88% 18%, rgba(251, 191, 36, 0.18), transparent 40%),
            linear-gradient(180deg, #fffefb 0%, #f8f1e8 100%);
        border: 2px dashed #d4a373;
        transition: border-color .2s, box-shadow .2s, transform .15s;
    }
    .upload-dropzone.is-dragover {
        border-color: #0f766e;
        box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.18);
        transform: translateY(-1px);
    }
    .upload-dropzone.is-focus-missing {
        border-color: #d97706;
        box-shadow: 0 0 0 4px rgba(251, 191, 36, 0.28);
        animation: upload-pulse 1.4s ease-in-out 2;
    }
    @keyframes upload-pulse {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-2px); }
    }
    .upload-file-row {
        background: #fff;
        border: 1px solid #ecd7c4;
        transition: border-color .15s, box-shadow .15s;
    }
    .upload-file-row:hover {
        border-color: #c4725c;
        box-shadow: 0 4px 14px rgba(139, 69, 19, 0.08);
    }
    .upload-icon-wrap {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .section-progress-card {
        background:
            radial-gradient(circle at 100% 0%, rgba(20, 184, 166, 0.12), transparent 45%),
            radial-gradient(circle at 0% 100%, rgba(245, 158, 11, 0.14), transparent 42%),
            linear-gradient(165deg, #fffdf9 0%, #f7efe6 100%);
        border: 1px solid #e4c9b0;
    }
    .section-progress-card.is-complete {
        background:
            radial-gradient(circle at 90% 10%, rgba(16, 185, 129, 0.16), transparent 48%),
            linear-gradient(165deg, #f4fdf8 0%, #eef8f2 100%);
        border-color: #86efac;
    }
    .sec-chip {
        min-width: 3.25rem;
        transition: transform .15s, box-shadow .15s;
    }
    .sec-chip:hover { transform: translateY(-1px); }
    .sec-chip.is-done {
        background: #ecfdf5;
        border: 1px solid #6ee7b7;
        color: #065f46;
    }
    .sec-chip.is-missing {
        background: #fffbeb;
        border: 1px dashed #f59e0b;
        color: #92400e;
        cursor: pointer;
    }
    .sec-chip.is-existing {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #475569;
    }
    .progress-bar-track {
        height: 0.65rem;
        background: rgba(92, 46, 31, 0.08);
        border-radius: 999px;
        overflow: hidden;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #0f766e, #14b8a6);
        transition: width .45s ease;
    }
    .progress-bar-fill.is-complete {
        background: linear-gradient(90deg, #047857, #34d399);
    }
</style>
@endpush

@section('content')
@php
    $maxFiles = \App\Services\Instructor\InstructorRegistrarUploadBatchService::MAX_FILES;
    $progress = $sectionProgress ?? null;
    $hasProgress = is_array($progress) && (($progress['attached_count'] ?? 0) > 0 || ($progress['required_count'] ?? 0) > 0);
    $isComplete = is_array($progress) && !empty($progress['complete']);
    $isGated = is_array($progress) && !empty($progress['gated']);
    $pct = 0;
    if ($hasProgress && (int) ($progress['required_count'] ?? 0) > 0) {
        $pct = (int) round(((int) $progress['attached_count'] / (int) $progress['required_count']) * 100);
    } elseif ($isComplete) {
        $pct = 100;
    }
    $formatSec = fn (int $sec) => str_pad((string) $sec, 2, '0', STR_PAD_LEFT);
@endphp
<div class="max-w-3xl mx-auto space-y-6">
    @if ($errors->has('grade_files'))
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm whitespace-pre-line flex gap-3 items-start">
            <span class="shrink-0 mt-0.5 inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-700">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
            </span>
            <div>{{ $errors->first('grade_files') }}</div>
        </div>
    @endif

    @if ($hasProgress)
        <div id="section-progress-panel"
             class="section-progress-card rounded-2xl p-5 sm:p-6 {{ $isComplete ? 'is-complete' : '' }}"
             data-complete="{{ $isComplete ? '1' : '0' }}"
             data-destroy-url-template="{{ url('/grade-reports/pending-registrar/__SEC__') }}">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="upload-icon-wrap {{ $isComplete ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900' }}">
                        <i data-lucide="{{ $isComplete ? 'check-circle-2' : 'layers' }}" class="w-5 h-5"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-wide uppercase text-[#7A4A3A]/70">
                            {{ $isComplete ? 'แนบ มข.11 ครบแล้ว' : 'กำลังแนบ มข.11' }}
                        </p>
                        <h2 class="text-xl font-bold text-[#5C2E1F] mt-0.5 truncate">
                            {{ $progress['subject_code'] }}
                            @if (!empty($progress['subject_name']))
                                <span class="font-medium text-[#7A4A3A] text-base">· {{ $progress['subject_name'] }}</span>
                            @endif
                        </h2>
                        <p class="text-sm text-[#7A4A3A] mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/80 border border-amber-200 text-[#5C2E1F] font-medium">
                                <i data-lucide="calendar-days" class="w-3.5 h-3.5 text-teal-700"></i>
                                {{ $progress['term_label'] }}
                            </span>
                            @if ($isGated)
                                <span class="text-[#7A4A3A]/80">ตามรายการเปิดสอนจากสำนักทะเบียน</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="text-right shrink-0">
                    <p class="text-3xl font-bold tabular-nums leading-none {{ $isComplete ? 'text-emerald-700' : 'text-[#5C2E1F]' }}">
                        {{ (int) $progress['attached_count'] }}<span class="text-lg font-semibold text-[#7A4A3A]/70">/{{ (int) $progress['required_count'] }}</span>
                    </p>
                    <p class="text-xs font-medium text-[#7A4A3A] mt-1">Section ที่แนบแล้ว</p>
                </div>
            </div>

            @if ($isGated)
                <div class="mb-5">
                    <div class="flex items-center justify-between text-xs font-medium text-[#7A4A3A] mb-1.5">
                        <span>ความคืบหน้าการแนบ มข.11</span>
                        <span>{{ $pct }}%</span>
                    </div>
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill {{ $isComplete ? 'is-complete' : '' }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>

                <div class="rounded-xl bg-white/70 border border-amber-100 px-4 py-3 mb-5 text-sm text-[#5C2E1F] leading-relaxed">
                    @if ($isComplete)
                        <p>
                            วิชา <strong>{{ $progress['subject_code'] }}</strong>
                            ภาคการศึกษา <strong>{{ $progress['term_label'] }}</strong>
                            ต้องแนบ มข.11 ทั้งหมด <strong>{{ (int) $progress['required_count'] }}</strong> section —
                            แนบครบแล้วทุก section พร้อมไปขั้นตอนถัดไป
                        </p>
                    @else
                        <p>
                            วิชา <strong>{{ $progress['subject_code'] }}</strong>
                            ภาคการศึกษา <strong>{{ $progress['term_label'] }}</strong>
                            ต้องแนบ มข.11 ทั้งหมด <strong>{{ (int) $progress['required_count'] }}</strong> section
                            ตอนนี้แนบแล้ว <strong>{{ (int) $progress['attached_count'] }}</strong> section
                            เหลืออีก <strong class="text-amber-800">{{ (int) $progress['missing_count'] }}</strong> section
                        </p>
                        @if (!empty($progress['missing_sections']))
                            <p class="mt-1.5 text-[#7A4A3A]">
                                Section ที่ยังขาด:
                                <strong class="text-amber-900">
                                    {{ collect($progress['missing_sections'])->map($formatSec)->implode(', ') }}
                                </strong>
                            </p>
                        @endif
                    @endif
                </div>
            @endif

            <div class="mb-4">
                <p class="text-sm font-semibold text-[#5C2E1F] mb-2 flex items-center gap-2">
                    <i data-lucide="layout-grid" class="w-4 h-4 text-teal-700"></i>
                    รายการ Section
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($progress['required_sections'] as $sec)
                        @php $done = in_array((int) $sec, $progress['attached_sections'] ?? [], true); @endphp
                        <button type="button"
                            class="sec-chip rounded-xl px-3 py-2 text-center {{ $done ? 'is-done' : 'is-missing' }}"
                            data-section="{{ (int) $sec }}"
                            @if (!$done) data-focus-upload="1" title="คลิกเพื่อไปแนบไฟล์ Section นี้" @endif
                            @if ($done) disabled @endif>
                            <span class="block text-[10px] font-semibold uppercase tracking-wide opacity-70">Sec</span>
                            <span class="block text-base font-bold tabular-nums leading-tight">{{ $formatSec((int) $sec) }}</span>
                            <span class="block text-[10px] mt-0.5 font-medium">
                                {{ $done ? 'แนบแล้ว' : 'ยังขาด' }}
                            </span>
                        </button>
                    @endforeach
                    @foreach ($progress['existing_sections'] ?? [] as $sec)
                        <div class="sec-chip is-existing rounded-xl px-3 py-2 text-center" title="มีข้อมูลในระบบแล้ว">
                            <span class="block text-[10px] font-semibold uppercase tracking-wide opacity-70">Sec</span>
                            <span class="block text-base font-bold tabular-nums leading-tight">{{ $formatSec((int) $sec) }}</span>
                            <span class="block text-[10px] mt-0.5 font-medium">ในระบบแล้ว</span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if (!empty($progress['pending_details']))
                <div class="mb-5">
                    <p class="text-sm font-semibold text-[#5C2E1F] mb-2 flex items-center gap-2">
                        <i data-lucide="paperclip" class="w-4 h-4 text-teal-700"></i>
                        ไฟล์ มข.11 ที่แนบไว้แล้ว
                    </p>
                    <ul class="space-y-2" id="pending-file-list">
                        @foreach ($progress['pending_details'] as $file)
                            <li class="upload-file-row rounded-xl px-3 py-2.5 flex items-center gap-3"
                                data-pending-section="{{ (int) $file['section'] }}">
                                <div class="upload-icon-wrap bg-emerald-100 text-emerald-700" style="width:2rem;height:2rem;border-radius:0.6rem;">
                                    <i data-lucide="file-check-2" class="w-4 h-4"></i>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-[#5C2E1F] truncate">
                                        Section {{ $formatSec((int) $file['section']) }}
                                        <span class="font-normal text-[#7A4A3A]">· {{ $file['name'] }}</span>
                                    </p>
                                </div>
                                <a href="{{ $file['view_url'] }}" target="_blank" rel="noopener"
                                   class="text-xs font-medium text-teal-800 hover:text-teal-950 inline-flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-teal-50">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    ดู
                                </a>
                                <button type="button"
                                    class="btn-remove-pending text-xs font-medium text-rose-700 hover:text-rose-900 inline-flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-rose-50"
                                    data-section="{{ (int) $file['section'] }}">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    ลบ
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-wrap gap-2 items-center">
                @if ($isComplete || (!$isGated && ($progress['attached_count'] ?? 0) > 0))
                    <form method="POST" action="{{ route('grade-reports.upload.continue') }}" class="inline">
                        @csrf
                        <input type="hidden" name="term" value="{{ $progress['term'] }}">
                        <input type="hidden" name="year" value="{{ $progress['year'] }}">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-700 text-white rounded-lg text-sm font-semibold hover:bg-emerald-800 shadow-sm">
                            <i data-lucide="arrow-right-circle" class="w-4 h-4"></i>
                            ไปขั้นตอนถัดไป
                        </button>
                    </form>
                @else
                    <button type="button" id="btn-scroll-to-upload"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber-600 text-white rounded-lg text-sm font-semibold hover:bg-amber-700 shadow-sm">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        แนบ มข.11 ที่เหลือ ({{ (int) ($progress['missing_count'] ?? 0) }} section)
                    </button>
                @endif
                <form method="POST" action="{{ route('grade-reports.upload.clear') }}" class="inline"
                      onsubmit="return confirm('ล้างไฟล์ มข.11 ที่แนบไว้ทั้งหมดของวิชานี้ แล้วเริ่มใหม่หรือไม่?');">
                    @csrf
                    <input type="hidden" name="term" value="{{ $progress['term'] }}">
                    <input type="hidden" name="year" value="{{ $progress['year'] }}">
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2.5 border border-rose-200 text-rose-800 bg-rose-50 rounded-lg text-sm font-medium hover:bg-rose-100">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        เริ่มใหม่
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div id="upload-form-section" class="form-section rounded-xl p-6">
        <div class="flex items-start gap-4 mb-6">
            <div class="upload-icon-wrap bg-teal-100 text-teal-800">
                <i data-lucide="files" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-[#5C2E1F]">
                    @if ($hasProgress && $isGated && !$isComplete)
                        แนบ มข.11 Section ที่เหลือ
                    @else
                        อัปโหลดไฟล์จากสำนักทะเบียน (มข.11)
                    @endif
                </h2>
                <p class="text-sm text-[#7A4A3A]/80 mt-1 leading-relaxed">
                    @if ($hasProgress && $isGated && !$isComplete)
                        เลือกไฟล์ PDF ของ Section
                        <strong class="text-amber-900">{{ collect($progress['missing_sections'] ?? [])->map($formatSec)->implode(', ') }}</strong>
                        แล้วอัปโหลดต่อได้ทันที (สูงสุด {{ $maxFiles }} ไฟล์ต่อครั้ง)
                    @else
                        เลือกภาค/ปีการศึกษา แล้วเพิ่มไฟล์ มข.11 (สูงสุด {{ $maxFiles }} ไฟล์)
                        — ระบบจะตรวจว่าต้องแนบครบทุก Section ตามรายการเปิดสอนก่อนไปขั้นตอนถัดไป
                    @endif
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('grade-reports.upload.store') }}" enctype="multipart/form-data"
              id="multi-upload-form" class="space-y-5" data-max-files="{{ $maxFiles }}">
            @csrf

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา *</label>
                    <select name="term" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="1" @selected(old('term', $hasProgress ? $progress['term'] : $term) == 1)>ภาคต้น</option>
                        <option value="2" @selected(old('term', $hasProgress ? $progress['term'] : $term) == 2)>ภาคปลาย</option>
                        <option value="3" @selected(old('term', $hasProgress ? $progress['term'] : $term) == 3)>ภาคการศึกษาพิเศษ</option>
                    </select>
                    @error('term')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา *</label>
                    <select name="year" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected(old('year', $hasProgress ? $progress['year'] : $year) == $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                    @error('year')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-2">ไฟล์ PDF ใบส่งผลการศึกษา *</label>

                <div id="upload-dropzone" class="upload-dropzone rounded-2xl px-5 py-8 text-center cursor-pointer">
                    <div class="mx-auto mb-3 upload-icon-wrap bg-teal-600 text-white shadow-sm" style="width:3.25rem;height:3.25rem;border-radius:1rem;">
                        <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                    </div>
                    <p class="text-base font-semibold text-[#5C2E1F]">ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือก</p>
                    <p class="text-sm text-[#7A4A3A]/80 mt-1">
                        @if ($hasProgress && $isGated && !$isComplete)
                            แนบ มข.11 ของ Section ที่ยังขาด · เพิ่มได้หลายไฟล์ในครั้งเดียว
                        @else
                            รองรับ PDF จากสำนักทะเบียน · เพิ่มไฟล์ได้เรื่อยๆ
                        @endif
                    </p>
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <button type="button" id="btn-pick-files"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-teal-700 text-white text-sm font-semibold hover:bg-teal-800">
                            <i data-lucide="folder-open" class="w-4 h-4"></i>
                            เลือกไฟล์
                        </button>
                        <button type="button" id="btn-add-more" disabled
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-sky-300 bg-sky-50 text-sky-900 text-sm font-semibold hover:bg-sky-100 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            เพิ่มไฟล์อีก
                        </button>
                    </div>
                </div>

                <input type="file" id="grade-files-input" accept=".pdf,application/pdf" multiple class="hidden">
                <div id="grade-files-hidden"></div>

                <div id="upload-file-list-wrap" class="mt-4 hidden space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-[#5C2E1F] flex items-center gap-2">
                            <span class="upload-icon-wrap bg-emerald-100 text-emerald-700" style="width:1.75rem;height:1.75rem;border-radius:0.5rem;">
                                <i data-lucide="list" class="w-3.5 h-3.5"></i>
                            </span>
                            รายการไฟล์ที่เลือก
                            <span id="upload-file-count" class="text-xs font-medium px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">0</span>
                        </p>
                        <button type="button" id="btn-clear-files"
                            class="text-xs font-medium text-rose-700 hover:text-rose-900 inline-flex items-center gap-1">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            ล้างทั้งหมด
                        </button>
                    </div>
                    <ul id="upload-file-list" class="space-y-2"></ul>
                </div>

                <p id="upload-client-error" class="hidden text-sm text-red-700 mt-2 whitespace-pre-line"></p>
                <p class="text-xs text-[#7A4A3A]/80 mt-3">
                    ระบบจะ<strong class="text-[#5C2E1F]">อ่านข้อมูลจากเนื้อหาในไฟล์ PDF</strong>
                    (รหัสวิชา, กลุ่มเรียน, คณะนักศึกษา, ตารางสรุปเกรด)
                    แล้วตรวจว่าต้องแนบ มข.11 ครบทุก Section ของรายวิชานั้นก่อนไปขั้นตอนถัดไป
                </p>
                @error('grade_files')
                    {{-- shown in layout banner --}}
                @enderror
                @error('grade_files.*')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 flex-wrap items-center">
                <button type="submit" id="btn-submit-upload" disabled
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-semibold hover:bg-[#6B3410] disabled:opacity-45 disabled:cursor-not-allowed">
                    <i data-lucide="{{ $hasProgress && !$isComplete ? 'upload' : 'sparkles' }}" class="w-4 h-4"></i>
                    @if ($hasProgress && $isGated && !$isComplete)
                        อัปโหลด Section ที่เหลือ
                    @else
                        อัปโหลดและตรวจสอบ
                    @endif
                </button>
                <a href="{{ route('dashboard') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">
                    <i data-lucide="x" class="w-4 h-4"></i>
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/image-only-pdf-guide.js') }}?v={{ filemtime(public_path('js/image-only-pdf-guide.js')) }}"></script>
<script src="{{ asset('js/grade-reports-multi-upload.js') }}?v={{ filemtime(public_path('js/grade-reports-multi-upload.js')) }}"></script>
<script>
(() => {
    const panel = document.getElementById('section-progress-panel');
    const dropzone = document.getElementById('upload-dropzone');
    const formSection = document.getElementById('upload-form-section');
    const scrollBtn = document.getElementById('btn-scroll-to-upload');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const focusUpload = () => {
        formSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (!dropzone) return;
        dropzone.classList.add('is-focus-missing');
        window.setTimeout(() => dropzone.classList.remove('is-focus-missing'), 2800);
    };

    scrollBtn?.addEventListener('click', focusUpload);

    document.querySelectorAll('[data-focus-upload]').forEach((btn) => {
        btn.addEventListener('click', focusUpload);
    });

    @if (session('upload_section_incomplete') && $hasProgress && !$isComplete)
    document.addEventListener('DOMContentLoaded', () => {
        panel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    @endif

    document.querySelectorAll('.btn-remove-pending').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const sec = Number(btn.dataset.section || 0);
            if (!sec || !panel) return;
            if (!window.confirm(`ลบไฟล์ มข.11 ของ Section ${String(sec).padStart(2, '0')} หรือไม่?`)) {
                return;
            }
            const template = panel.dataset.destroyUrlTemplate || '';
            const url = template.replace('__SEC__', String(sec));
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) throw new Error('remove failed');
                window.location.reload();
            } catch (e) {
                window.alert('ลบไฟล์ไม่สำเร็จ กรุณาลองใหม่');
            }
        });
    });
})();
</script>
@if (session('image_pdf_guide') || collect($errors->get('grade_files'))->merge($errors->get('grade_file'))->contains(fn ($m) => \App\Support\ImageOnlyPdfMessage::matches((string) $m)))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.SciGradeImagePdfGuide?.show();
    });
</script>
@endif
@endpush
