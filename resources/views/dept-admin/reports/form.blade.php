@extends('layouts.scigrad')

@section('title', 'พิมพ์รายงานสาขา — Admin สาขา')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('dept-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">ตรวจสอบรายวิชา</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">พิมพ์รายงาน</span>
@endsection

@push('styles')
<style>
    .report-choice {
        position: relative;
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
        padding: 1rem 1.1rem;
        border-radius: 0.9rem;
        border: 2px solid #e8cdb5;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease, transform .12s ease;
    }
    .report-choice:hover {
        border-color: #c4a484;
        box-shadow: 0 4px 14px rgba(139, 69, 19, .08);
        transform: translateY(-1px);
    }
    .report-choice:has(input:checked) {
        border-color: #8B4513;
        background: linear-gradient(180deg, #fffaf5 0%, #fff 100%);
        box-shadow: 0 0 0 3px rgba(139, 69, 19, .12);
    }
    .report-choice input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .report-choice-icon {
        width: 2.6rem;
        height: 2.6rem;
        border-radius: 0.75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .report-choice-icon.is-queued { background: #eef2ff; color: #4338ca; }
    .report-choice-icon.is-passed { background: #e0f2fe; color: #0369a1; }
    .report-choice-icon.is-pending { background: #fff7ed; color: #c2410c; }
    .report-choice-icon.is-pdf { background: #fef2f2; color: #b91c1c; }
    .report-choice-icon.is-word { background: #eff6ff; color: #1d4ed8; }
    .report-choice:has(input:checked) .report-choice-check {
        opacity: 1;
        transform: scale(1);
    }
    .report-choice-check {
        margin-left: auto;
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 9999px;
        background: #8B4513;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(.7);
        transition: opacity .15s ease, transform .15s ease;
        flex-shrink: 0;
    }
    .report-step {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: .02em;
        text-transform: uppercase;
        color: #9a6b4f;
        margin-bottom: 0.65rem;
    }
    .report-step-dot {
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 9999px;
        background: #8B4513;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
    }
</style>
@endpush

@section('content')
@php
    $selectedStatus = (string) old('report_status', '4');
    $selectedFormat = (string) old('format', 'pdf');
@endphp
<div class="max-w-4xl mx-auto space-y-6">
    <div class="rounded-2xl overflow-hidden border border-amber-200 bg-white shadow-sm">
        <div class="px-6 py-5 bg-gradient-to-r from-[#8B4513] via-[#A0522D] to-[#C4725C] text-white">
            <div class="flex items-start gap-3">
                <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                    <i data-lucide="printer" class="w-6 h-6"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold leading-tight">พิมพ์ใบรายงานสาขา</h2>
                    <p class="text-sm text-white/85 mt-1">
                        เลือกสาขา ภาค/ปี และสถานะ — ระบบคิวรีตามสถานะที่เลือก (ไม่จำกัดช่วงวันที่)
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            @error('export')
                <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm flex items-start gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                    <span>{{ $message }}</span>
                </div>
            @enderror
            @if ($errors->any() && ! $errors->has('export'))
                <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('dept-admin.reports.export') }}" id="dept-report-export-form" class="space-y-7">
                @csrf

                <section>
                    <div class="report-step"><span class="report-step-dot">1</span> ขอบเขตข้อมูล</div>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-[#5C2E1F] mb-1.5">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="building-2" class="w-4 h-4 text-[#8B4513]"></i>
                                    สาขาวิชา *
                                </span>
                            </label>
                            <select name="department_id" id="report-department-id" required class="w-full border border-amber-300 rounded-xl px-3 py-2.5 text-sm bg-white">
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->department_id }}" @selected(old('department_id', $initialDepartmentId ?? null) == $dept->department_id)>
                                        {{ $dept->department_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @include('partials.department-code-patterns', [
                            'patternsByDepartment' => $patternsByDepartment ?? [],
                            'initialDepartmentId' => $initialDepartmentId ?? $departments->first()?->department_id,
                            'selectName' => 'department_id',
                            'panelId' => 'dept-report-dept-patterns',
                            'helpText' => 'รายงานจะรวมเฉพาะรายวิชาที่รหัสตรงตามเงื่อนไขของสาขานี้ และกรอกโดยอาจารย์ในสาขา/หน่วยงานที่รับผิดชอบ',
                        ])

                        <div>
                            <label class="block text-sm font-medium text-[#5C2E1F] mb-1.5">
                                <span class="inline-flex items-center gap-1.5">
                                    <i data-lucide="graduation-cap" class="w-4 h-4 text-[#8B4513]"></i>
                                    ระดับการศึกษา *
                                </span>
                            </label>
                            <select name="education_level" id="report-education-level" required class="w-full border border-amber-300 rounded-xl px-3 py-2.5 text-sm bg-white">
                                <option value="bachelor" @selected(old('education_level', $educationLevel ?? '') === 'bachelor')>ปริญญาตรี</option>
                                <option value="master" @selected(old('education_level', $educationLevel ?? '') === 'master')>ปริญญาโท</option>
                                <option value="doctoral" @selected(old('education_level', $educationLevel ?? '') === 'doctoral')>ปริญญาเอก</option>
                                <option value="graduate" @selected(old('education_level', $educationLevel ?? 'graduate') === 'graduate')>บัณฑิตศึกษา (โท+เอก)</option>
                                <option value="all" @selected(old('education_level', $educationLevel ?? '') === 'all')>รวมทั้งหมด</option>
                            </select>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-[#5C2E1F] mb-1.5">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="calendar-range" class="w-4 h-4 text-[#8B4513]"></i>
                                        ภาคการศึกษา
                                    </span>
                                </label>
                                <select name="term" id="report-term" class="w-full border border-amber-300 rounded-xl px-3 py-2.5 text-sm bg-white">
                                    <option value="">ทุกภาค</option>
                                    <option value="1" @selected(old('term', $term) == 1)>ภาคต้น</option>
                                    <option value="2" @selected(old('term', $term) == 2)>ภาคปลาย</option>
                                    <option value="3" @selected(old('term', $term) == 3)>ภาคการศึกษาพิเศษ</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[#5C2E1F] mb-1.5">
                                    <span class="inline-flex items-center gap-1.5">
                                        <i data-lucide="calendar" class="w-4 h-4 text-[#8B4513]"></i>
                                        ปีการศึกษา
                                    </span>
                                </label>
                                <select name="year" id="report-year" class="w-full border border-amber-300 rounded-xl px-3 py-2.5 text-sm bg-white">
                                    <option value="">ทุกปี</option>
                                    @foreach ($years as $y)
                                        <option value="{{ $y }}" @selected(old('year', $year) == $y)>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="report-step"><span class="report-step-dot">2</span> สถานะที่ต้องการพิมพ์</div>
                    <p class="text-xs text-[#7A4A3A]/80 mb-3">เลือก 1 สถานะ — ระบบดึงรายวิชาตามสถานะนี้ทั้งหมดของสาขา/ภาค/ปีที่เลือก</p>
                    <div class="grid gap-3">
                        <label class="report-choice">
                            <input type="radio" name="report_status" value="4" @checked($selectedStatus === '4') required>
                            <span class="report-choice-icon is-queued">
                                <i data-lucide="calendar-plus" class="w-5 h-5"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-[#5C2E1F]">นำเข้าที่ประชุมสาขา</span>
                                <span class="block text-xs text-[#7A4A3A]/80 mt-0.5">รายวิชาที่นำเข้าวาระแล้ว รอผลมติที่ประชุมสาขา</span>
                            </span>
                            <span class="report-choice-check" aria-hidden="true">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </span>
                        </label>

                        <label class="report-choice">
                            <input type="radio" name="report_status" value="1" @checked($selectedStatus === '1')>
                            <span class="report-choice-icon is-passed">
                                <i data-lucide="badge-check" class="w-5 h-5"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-[#5C2E1F]">ผ่านที่ประชุมสาขา</span>
                                <span class="block text-xs text-[#7A4A3A]/80 mt-0.5">รายวิชาที่ที่ประชุมสาขาเห็นชอบแล้ว</span>
                            </span>
                            <span class="report-choice-check" aria-hidden="true">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </span>
                        </label>

                        <label class="report-choice">
                            <input type="radio" name="report_status" value="0" @checked($selectedStatus === '0')>
                            <span class="report-choice-icon is-pending">
                                <i data-lucide="clock-3" class="w-5 h-5"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-[#5C2E1F]">ยังไม่ผ่านที่ประชุมสาขา</span>
                                <span class="block text-xs text-[#7A4A3A]/80 mt-0.5">ส่งแล้ว / บันทึกแล้ว แต่ยังไม่ได้นำเข้าหรือผ่านที่ประชุม</span>
                            </span>
                            <span class="report-choice-check" aria-hidden="true">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </span>
                        </label>
                    </div>
                </section>

                <section>
                    <div class="report-step"><span class="report-step-dot">3</span> รูปแบบไฟล์</div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <label class="report-choice">
                            <input type="radio" name="format" value="pdf" @checked($selectedFormat === 'pdf') required>
                            <span class="report-choice-icon is-pdf">
                                <i data-lucide="file-text" class="w-5 h-5"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-[#5C2E1F]">PDF</span>
                                <span class="block text-xs text-[#7A4A3A]/80 mt-0.5">เหมาะสำหรับพิมพ์และเก็บเอกสาร</span>
                            </span>
                            <span class="report-choice-check" aria-hidden="true">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </span>
                        </label>
                        <label class="report-choice">
                            <input type="radio" name="format" value="word" @checked($selectedFormat === 'word')>
                            <span class="report-choice-icon is-word">
                                <i data-lucide="file-type" class="w-5 h-5"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-[#5C2E1F]">Word (.docx)</span>
                                <span class="block text-xs text-[#7A4A3A]/80 mt-0.5">เหมาะสำหรับแก้ไขต่อใน Microsoft Word</span>
                            </span>
                            <span class="report-choice-check" aria-hidden="true">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </span>
                        </label>
                    </div>
                </section>

                <div class="flex flex-wrap items-center gap-3 pt-1 border-t border-amber-100">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#8B4513] text-white rounded-xl text-sm font-semibold hover:bg-[#6B3410] shadow-sm">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        ส่งออกรายงาน
                    </button>
                    <a href="{{ route('dept-admin.reviews.index') }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 border border-amber-300 rounded-xl text-sm text-[#5C2E1F] hover:bg-amber-50">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        กลับ
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
