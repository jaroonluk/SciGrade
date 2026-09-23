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
</style>
@endpush

@section('content')
@php
    $maxFiles = \App\Services\Instructor\InstructorRegistrarUploadBatchService::MAX_FILES;
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
    <div class="form-section rounded-xl p-6">
        <div class="flex items-start gap-4 mb-6">
            <div class="upload-icon-wrap bg-teal-100 text-teal-800">
                <i data-lucide="files" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-[#5C2E1F]">อัปโหลดไฟล์จากสำนักทะเบียน</h2>
                <p class="text-sm text-[#7A4A3A]/80 mt-1 leading-relaxed">
                    เลือกภาค/ปีการศึกษา แล้วเพิ่มไฟล์ PDF ได้เรื่อยๆ (สูงสุด {{ $maxFiles }} ไฟล์)
                    ระบบตรวจว่าเป็น<strong class="text-[#5C2E1F]">วิชาเดียวกัน</strong> ก่อนอ่านตามโฟลว์เดิม
                    หากไฟล์ซ้ำจะอ่านเพียงไฟล์เดียวและแจ้งชื่อไฟล์ที่ซ้ำให้ทราบ
                </p>
            </div>
        </div>

        <div class="grid sm:grid-cols-3 gap-3 mb-6">
            <div class="rounded-xl bg-white border border-teal-100 px-3 py-3 flex gap-3 items-start">
                <div class="upload-icon-wrap bg-teal-50 text-teal-700">
                    <i data-lucide="plus-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-teal-900">เพิ่มได้เรื่อยๆ</p>
                    <p class="text-xs text-[#7A4A3A]/80 mt-0.5">กดเพิ่มไฟล์ทีละใบ หรือเลือกหลายไฟล์พร้อมกัน</p>
                </div>
            </div>
            <div class="rounded-xl bg-white border border-sky-100 px-3 py-3 flex gap-3 items-start">
                <div class="upload-icon-wrap bg-sky-50 text-sky-700">
                    <i data-lucide="book-open" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-sky-900">ต้องเป็นวิชาเดียวกัน</p>
                    <p class="text-xs text-[#7A4A3A]/80 mt-0.5">เช่น Sec 01–03 ของรหัสวิชาเดียวกัน</p>
                </div>
            </div>
            <div class="rounded-xl bg-white border border-amber-100 px-3 py-3 flex gap-3 items-start">
                <div class="upload-icon-wrap bg-amber-50 text-amber-700">
                    <i data-lucide="copy" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-amber-900">ข้ามไฟล์ซ้ำ</p>
                    <p class="text-xs text-[#7A4A3A]/80 mt-0.5">เนื้อหาหรือกลุ่มเรียนซ้ำ — อ่านไฟล์แรกเท่านั้น</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('grade-reports.upload.store') }}" enctype="multipart/form-data"
              id="multi-upload-form" class="space-y-5" data-max-files="{{ $maxFiles }}">
            @csrf

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา *</label>
                    <select name="term" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="1" @selected(old('term', $term) == 1)>ภาคต้น</option>
                        <option value="2" @selected(old('term', $term) == 2)>ภาคปลาย</option>
                        <option value="3" @selected(old('term', $term) == 3)>ภาคการศึกษาพิเศษ</option>
                    </select>
                    @error('term')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา *</label>
                    <select name="year" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected(old('year', $year) == $y)>{{ $y }}</option>
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
                    <p class="text-sm text-[#7A4A3A]/80 mt-1">รองรับ PDF จากสำนักทะเบียน · เพิ่มไฟล์ได้เรื่อยๆ</p>
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
                    (รหัสวิชา, กลุ่มเรียน, คณะนักศึกษา, ตารางสรุปเกรด) โดยไม่ผูกกับชื่อไฟล์
                </p>
                @error('grade_files')
                    {{-- shown in layout banner --}}
                @enderror
                @error('grade_files.*')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 flex-wrap items-center">
                <button type="submit" id="btn-submit-upload" disabled
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-semibold hover:bg-[#6B3410] disabled:opacity-45 disabled:cursor-not-allowed">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    อัปโหลดและดำเนินการต่อ
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
@if (session('image_pdf_guide') || collect($errors->get('grade_files'))->merge($errors->get('grade_file'))->contains(fn ($m) => \App\Support\ImageOnlyPdfMessage::matches((string) $m)))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.SciGradeImagePdfGuide?.show();
    });
</script>
@endif
@endpush
