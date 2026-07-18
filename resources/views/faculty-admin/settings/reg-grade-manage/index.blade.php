@extends('layouts.scigrad')

@section('title', 'จัดการรายวิชา REG — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">จัดการรายวิชา REG</span>
@endsection

@push('styles')
<style>
    #subject-suggestions, #instructor-suggestions {
        max-height: 14rem;
        overflow-y: auto;
        box-shadow: 0 10px 28px rgba(92, 46, 31, 0.14);
    }
    #subject-suggestions button,
    #instructor-suggestions button {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.65rem 0.9rem;
        border-bottom: 1px solid #f5e6d8;
    }
    #subject-suggestions button:last-child,
    #instructor-suggestions button:last-child { border-bottom: 0; }
    #subject-suggestions button:hover,
    #instructor-suggestions button:hover { background: #fdf6f0; }

    tr.course-group-start td { border-top: 2px solid #7dd3fc !important; }
    tr.course-group-start td:first-child { box-shadow: inset 4px 0 0 #0284c7; }
    tr.course-group-cont td:first-child { box-shadow: inset 4px 0 0 #38bdf8; }
    tr.course-group-cont td.col-course {
        padding-left: 1.75rem;
        color: #7A4A3A;
    }
    tr.row-no-enroll {
        background: #fff7ed !important;
    }
    tr.row-no-enroll td:first-child {
        box-shadow: inset 4px 0 0 #dc2626;
    }
    tr.row-no-enroll.course-group-start td:first-child,
    tr.row-no-enroll.course-group-cont td:first-child {
        box-shadow: inset 4px 0 0 #dc2626, inset 8px 0 0 #38bdf8;
    }
    .sec-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        background: #FAF0E6;
        color: #5C2E1F;
        font-weight: 700;
        font-size: 0.75rem;
    }
    .sec-badge.is-multi {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #7dd3fc;
    }
    .multi-sec-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        margin-left: 0.35rem;
        padding: 0.15rem 0.55rem;
        border-radius: 9999px;
        background: #0369a1;
        color: #fff;
        font-size: 0.68rem;
        font-weight: 700;
        vertical-align: middle;
        letter-spacing: 0.01em;
    }
    .pattern-chip {
        display: inline-flex;
        flex-direction: column;
        gap: 0.15rem;
        min-width: 7.5rem;
        padding: 0.55rem 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid #e8c4b8;
        background: linear-gradient(180deg, #fffdfb 0%, #faf0e6 100%);
        box-shadow: 0 1px 2px rgba(139, 69, 19, 0.06);
    }
    .pattern-chip code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.85rem;
        font-weight: 700;
        color: #8B4513;
        letter-spacing: 0.02em;
    }
    .pattern-chip span {
        font-size: 0.68rem;
        color: #7A4A3A;
        line-height: 1.25;
    }
    .pattern-chip.is-exact {
        border-color: #c4d4e8;
        background: linear-gradient(180deg, #ffffff 0%, #eef5ff 100%);
    }
    .pattern-chip.is-exact code { color: #1e4b7b; }
    .pattern-chip.is-contains {
        border-color: #c9dfc8;
        background: linear-gradient(180deg, #ffffff 0%, #f1f8f0 100%);
    }
    .pattern-chip.is-contains code { color: #2f6b3a; }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">จัดการรายวิชา REG</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            ดึงรายวิชาจาก REG แล้วเพิ่ม / แก้ไข / ลบ ตามสาขาวิชา ภาค และปีการศึกษา
        </p>
    </div>

    @unless ($canConnectReg)
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            เชื่อมต่อฐานข้อมูล REG ไม่ได้ กรุณาตรวจสอบการตั้งค่าฐานข้อมูล
            <span class="font-semibold">reg</span>
        </div>
    @endunless

    @error('dump')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

    @error('manage')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

    <div class="form-section rounded-xl p-6">
        <h3 class="font-semibold text-[#5C2E1F] mb-3">ดึงข้อมูลจาก REG</h3>
        <form method="POST" action="{{ route('faculty-admin.settings.reg-grade-dump.dump') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                <select name="term" required class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                    <option value="1" @selected((int) old('term', $term) === 1)>ภาคต้น</option>
                    <option value="2" @selected((int) old('term', $term) === 2)>ภาคปลาย</option>
                    <option value="3" @selected((int) old('term', $term) === 3)>ภาคการศึกษาพิเศษ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                <select name="year" required class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected((int) old('year', $year) === $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410] disabled:opacity-50"
                @disabled(! $canConnectReg)
                onclick="return confirm('ดึงรายวิชาจาก REG (รวม SEMINAR) และทับข้อมูลเดิมใน grade_report_reg ตามภาค/ปีที่เลือก?')">
                ดึงข้อมูลจาก REG
            </button>
        </form>
        <p class="text-xs text-[#7A4A3A]/75 mt-3">
            รวมวิชา SEMINAR · ทับข้อมูลเดิมทั้งกลุ่มวิชา/Sec. · ยังไม่ดึง THESIS / Independent Study / Dissertation
        </p>
    </div>

    <div class="form-section rounded-xl p-6">
        <h3 class="font-semibold text-[#5C2E1F] mb-3">เพิ่มรายวิชา</h3>
        <form method="POST" action="{{ route('faculty-admin.settings.reg-grade-manage.store') }}" id="form-add-course" class="space-y-4">
            @csrf
            <input type="hidden" name="department_id" value="{{ $departmentId }}">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="OFFICERID" id="officer-id" value="{{ old('OFFICERID') }}">
            <input type="hidden" name="OFFICERNAME" id="officer-fname" value="{{ old('OFFICERNAME') }}">
            <input type="hidden" name="OFFICERSURNAME" id="officer-lname" value="{{ old('OFFICERSURNAME') }}">
            <input type="hidden" name="KKUMAIL" id="officer-email" value="{{ old('KKUMAIL') }}">

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา *</label>
                    <select name="SEMESTER" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="1" @selected((int) old('SEMESTER', $term) === 1)>ภาคต้น</option>
                        <option value="2" @selected((int) old('SEMESTER', $term) === 2)>ภาคปลาย</option>
                        <option value="3" @selected((int) old('SEMESTER', $term) === 3)>ภาคการศึกษาพิเศษ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา *</label>
                    <select name="ACADYEAR" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected((int) old('ACADYEAR', $year) === $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="relative">
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">รหัสวิชา *</label>
                    <input type="text" name="COURSECODE" id="course-code" value="{{ old('COURSECODE') }}" required autocomplete="off"
                        placeholder="พิมพ์รหัสหรือชื่อวิชา แล้วเลือกรายการ"
                        class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white uppercase">
                    <div id="subject-suggestions" class="hidden absolute left-0 right-0 top-full mt-1 z-30 bg-white border border-amber-200 rounded-lg"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ชื่อวิชา (ENG) *</label>
                    <input type="text" name="COURSENAMEENG" id="course-name" value="{{ old('COURSENAMEENG') }}" required
                        class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">Sec. *</label>
                    <input type="text" name="SECTION" value="{{ old('SECTION', '1') }}" required maxlength="5"
                        class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                </div>
                <div class="relative">
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">อาจารย์ผู้สอน *</label>
                    <input type="text" id="instructor-search" autocomplete="off"
                        placeholder="พิมพ์ชื่อ สกุล หรือรหัสอาจารย์"
                        value="{{ old('OFFICERNAME') ? trim(old('OFFICERNAME').' '.old('OFFICERSURNAME')) : '' }}"
                        class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <div id="instructor-suggestions" class="hidden absolute left-0 right-0 top-full mt-1 z-30 bg-white border border-amber-200 rounded-lg"></div>
                    <p id="instructor-selected" class="text-xs text-gray-600 mt-1 {{ old('OFFICERID') ? '' : 'hidden' }}">
                        @if(old('OFFICERID'))
                            รหัส {{ old('OFFICERID') }} · {{ old('KKUMAIL') }}
                        @endif
                    </p>
                </div>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
                เพิ่มรายวิชา
            </button>
        </form>
    </div>

    <div class="form-section rounded-xl p-6">
        <h3 class="font-semibold text-[#5C2E1F] mb-3">ค้นหา / กรองรายการ</h3>
        <form method="GET" action="{{ route('faculty-admin.settings.reg-grade-manage.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สาขาวิชา</label>
                <select name="department_id" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[14rem]">
                    <option value="">ทั้งหมด</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->department_id }}" @selected($departmentId === (int) $dept->department_id)>
                            {{ $dept->department_name }}
                        </option>
                    @endforeach
                </select>
            </div>
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
            <div class="flex-1 min-w-[16rem]">
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ค้นหา</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="รหัสวิชา / ชื่อวิชา / ชื่ออาจารย์"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                <p class="text-xs text-gray-500 mt-1">เมื่อพิมพ์คำค้น ระบบจะค้นทั้งภาค/ปี (รวมรหัสที่อยู่นอกกลุ่มสาขา)</p>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
                แสดงข้อมูล
            </button>
        </form>

        @if ($departmentId && $selectedDepartment)
            <div class="mt-5 rounded-xl border border-[#E8C4B8]/80 bg-gradient-to-br from-[#FFFBF7] via-[#FAF0E6]/70 to-[#F5E6D8]/40 overflow-hidden">
                <div class="px-4 py-3 border-b border-[#E8C4B8]/60 flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#A0522D]/80">เงื่อนไขรหัสวิชาของสาขา</p>
                        <h4 class="text-base font-bold text-[#5C2E1F] mt-0.5 flex items-center gap-2">
                            <i data-lucide="filter" class="w-4 h-4 text-[#8B4513]"></i>
                            {{ $selectedDepartment->department_name }}
                        </h4>
                        <p class="text-xs text-[#7A4A3A]/80 mt-1">
                            เมื่อเลือกสาขานี้โดยไม่พิมพ์คำค้น ระบบจะแสดงเฉพาะรายวิชาที่รหัสตรงตามเงื่อนไขด้านล่าง
                            — ใช้ตรวจสอบก่อนเพิ่ม/ลบข้อมูล
                        </p>
                    </div>
                    <div class="shrink-0 rounded-lg bg-white/80 border border-[#E8C4B8]/70 px-3 py-2 text-center">
                        <p class="text-[0.65rem] text-[#A0522D]/70">จำนวนเงื่อนไข</p>
                        <p class="text-lg font-bold text-[#8B4513] leading-none">{{ count($departmentPatterns) }}</p>
                    </div>
                </div>

                @if ($departmentPatterns !== [])
                    <div class="px-4 py-4">
                        <div class="flex flex-wrap gap-2.5">
                            @foreach ($departmentPatterns as $item)
                                <div class="pattern-chip is-{{ $item['kind'] }}" title="{{ $item['label'] }}">
                                    <code>{{ $item['pattern'] }}</code>
                                    <span>{{ $item['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 flex flex-wrap gap-4 text-[0.7rem] text-[#7A4A3A]/75">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#C4725C]"></span> ขึ้นต้น / ลงท้าย
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#5a9a63]"></span> มีข้อความในรหัส
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#4a7fb0]"></span> รหัสตรงทั้งหมด
                            </span>
                        </div>
                    </div>
                @else
                    <div class="px-4 py-4 text-sm text-amber-800 bg-amber-50/80">
                        ยังไม่ได้กำหนดเงื่อนไขรหัสวิชาสำหรับสาขานี้
                    </div>
                @endif

                @if ($q !== '' && $outsidePatternCount > 0)
                    <div class="px-4 py-3 border-t border-amber-200/80 bg-amber-50/90 text-sm text-amber-900 flex items-start gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 mt-0.5 shrink-0 text-amber-700"></i>
                        <div>
                            พบ <strong>{{ $outsidePatternCount }}</strong> รายการจากการค้นหา “{{ $q }}”
                            ที่<strong>อยู่นอกเงื่อนไขรหัสสาขานี้</strong>
                            (เช่น รหัสทดสอบหรือรหัสที่เพิ่มเอง) — เมื่อล้างคำค้น รายการเหล่านี้อาจไม่แสดงในสาขานี้
                        </div>
                    </div>
                @elseif ($q !== '')
                    <div class="px-4 py-3 border-t border-[#E8C4B8]/50 bg-white/50 text-xs text-[#7A4A3A]/80">
                        กำลังค้นหา “{{ $q }}” ทั้งภาค/ปี — ผลลัพธ์อาจรวมรหัสนอกเงื่อนไขสาขาด้านบน
                    </div>
                @endif
            </div>
        @elseif (! $departmentId)
            <div class="mt-4 rounded-lg border border-dashed border-[#E8C4B8] bg-[#FFFBF7]/70 px-4 py-3 text-xs text-[#7A4A3A]/85 flex items-start gap-2">
                <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0 text-[#8B4513]"></i>
                <span>
                    เลือกสาขาวิชาเพื่อดูเงื่อนไขรหัสที่ใช้กรองรายการของสาขานั้น
                    หรือพิมพ์คำค้นเพื่อหารหัสที่อยู่นอกกลุ่มสาขา (เช่น รหัสทดสอบ)
                </span>
            </div>
        @endif
    </div>

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <div class="px-4 py-3 bg-amber-50 border-b border-amber-200 text-sm text-[#5C2E1F] flex flex-wrap items-center justify-between gap-2">
            <span>
                พบ {{ number_format($courses->total()) }} รายการ
                @if ($q !== '')
                    <span class="text-xs text-gray-500">สำหรับคำค้น “{{ $q }}”</span>
                @endif
                @if (($zeroEnrollmentCount ?? 0) > 0)
                    <span class="text-red-700 font-medium">· ไม่มีผู้ลงทะเบียนในหน้านี้ {{ $zeroEnrollmentCount }} กลุ่ม</span>
                @endif
                @if (($multiSectionCourseCount ?? 0) > 0)
                    <span class="text-sky-800 font-medium">· มีหลาย Sec. {{ $multiSectionCourseCount }} วิชา</span>
                @endif
            </span>
            <span class="text-xs text-gray-500">
                หน้า {{ $courses->currentPage() }} / {{ max($courses->lastPage(), 1) }}
                (หน้าละ {{ $courses->perPage() }})
            </span>
        </div>

        @if ($canConnectReg ?? false)
            <div class="px-4 py-2 border-b border-amber-100 bg-white text-xs text-[#7A4A3A]/85 flex flex-wrap items-center gap-x-4 gap-y-1">
                <span>
                    สถานะลงทะเบียนดึงจาก REG — แถวสีส้มและป้าย
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-100 text-red-700 font-semibold">ไม่มีผู้ลงทะเบียน</span>
                    คือ ENROLLSEAT = 0 หรือไม่พบใน REG
                </span>
                <span>
                    วิชามากกว่า 1 Sec. แสดงแถบฟ้าด้านซ้ายและป้าย
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-sky-700 text-white font-semibold">N Sec.</span>
                </span>
            </div>
        @elseif ($courses->total() > 0)
            <div class="px-4 py-2 border-b border-amber-100 bg-amber-50/80 text-xs text-amber-900">
                เชื่อมต่อฐานข้อมูล REG ไม่ได้ — ยังไม่สามารถแสดงจำนวนผู้ลงทะเบียนได้
            </div>
        @endif

        @if ($courses->total() > 0)
            <div class="px-4 py-3 border-b border-amber-100 bg-white flex flex-wrap items-center gap-3">
                <span class="text-sm text-[#5C2E1F] font-medium">ลบหลายรายการ:</span>
                <span id="bulk-selected-count" class="text-xs text-gray-500">เลือกแล้ว 0 รายการ</span>
                <button type="button" id="btn-bulk-delete-selected"
                    class="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    disabled>
                    ลบที่เลือก
                </button>
                <button type="button" id="btn-bulk-delete-all"
                    class="px-3 py-1.5 border border-red-300 text-red-700 rounded text-xs font-medium hover:bg-red-50">
                    ลบทั้งหมดตามเงื่อนไขที่กรอง ({{ number_format($courses->total()) }} กลุ่ม)
                </button>
            </div>
        @endif

        <form id="form-bulk-delete" method="POST" action="{{ route('faculty-admin.settings.reg-grade-manage.bulk-destroy') }}" class="hidden">
            @csrf
            @method('DELETE')
            <input type="hidden" name="scope" id="bulk-scope" value="selected">
            <input type="hidden" name="ACADYEAR" value="{{ $year }}">
            <input type="hidden" name="SEMESTER" value="{{ $term }}">
            <input type="hidden" name="department_id" value="{{ $departmentId }}">
            <input type="hidden" name="q" value="{{ $q }}">
            <div id="bulk-items-container"></div>
        </form>

        <table class="w-full text-sm min-w-[1020px]">
            <thead class="bg-amber-50/60">
                <tr>
                    <th class="px-3 py-2 text-center w-12">
                        @if ($courses->total() > 0)
                            <input type="checkbox" id="check-all-courses" class="rounded border-amber-300 text-[#8B4513] focus:ring-[#8B4513]" title="เลือกทั้งหมดในหน้านี้">
                        @endif
                    </th>
                    <th class="px-3 py-2 text-left w-14">ลำดับ</th>
                    <th class="px-3 py-2 text-left">รายวิชา</th>
                    <th class="px-3 py-2 text-center">Sec.</th>
                    <th class="px-3 py-2 text-center">สถานะลงทะเบียน</th>
                    <th class="px-3 py-2 text-left">ผู้สอน</th>
                    <th class="px-3 py-2 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @php $prevCode = null; @endphp
                @forelse ($courses as $index => $row)
                    @php
                        $isContinuation = $prevCode !== null && $prevCode === $row->COURSECODE;
                        $isGroupStart = ! $isContinuation && $row->has_multi_section;
                        $isZero = (bool) ($row->has_no_enrollment ?? false);
                        $enrollSeat = $row->enrollseat ?? null;
                        $rowClass = $isContinuation
                            ? 'bg-[#F0F9FF] course-group-cont'
                            : ($isGroupStart ? 'bg-[#E0F2FE]/70 course-group-start' : ($index % 2 === 0 ? 'bg-white' : 'bg-[#F0FFFF]/40'));
                        if ($isZero) {
                            $rowClass .= ' row-no-enroll';
                        }
                    @endphp
                    <tr class="border-t border-amber-100 {{ $rowClass }}">
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox"
                                class="course-row-check rounded border-amber-300 text-[#8B4513] focus:ring-[#8B4513]"
                                value="{{ $row->COURSECODE }}|{{ $row->SECTION }}"
                                data-code="{{ $row->COURSECODE }}"
                                data-section="{{ $row->SECTION }}">
                        </td>
                        <td class="px-3 py-2 text-gray-500">{{ $courses->firstItem() + $index }}</td>
                        <td class="px-3 py-2 col-course">
                            @if ($isContinuation)
                                <span class="text-xs text-sky-800 font-semibold">↳ Sec. ต่อเนื่อง · วิชาเดียวกัน</span>
                                <div class="text-xs text-gray-500">
                                    {{ $row->COURSECODE }}
                                    @if ($departmentId && in_array($row->COURSECODE, $outsidePatternCodes ?? [], true))
                                        <span class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded-full text-[0.6rem] font-semibold bg-amber-100 text-amber-800 border border-amber-200">นอกเงื่อนไขสาขา</span>
                                    @endif
                                </div>
                            @else
                                <div class="flex items-start gap-1.5 flex-wrap">
                                    @if ($isZero)
                                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-600 text-white text-[0.65rem] font-bold shrink-0 mt-0.5" title="ไม่มีผู้ลงทะเบียน">!</span>
                                    @endif
                                    <div>
                                        <span class="font-medium text-[#5C2E1F]">{{ $row->COURSECODE }}</span>
                                        {{ $row->COURSENAMEENG }}
                                        @if ($row->has_multi_section)
                                            <span class="multi-sec-tag" title="วิชานี้มีหลายกลุ่มเรียน">{{ $row->section_count }} Sec.</span>
                                        @endif
                                        @if ($departmentId && in_array($row->COURSECODE, $outsidePatternCodes ?? [], true))
                                            <span class="inline-flex items-center ml-1.5 px-2 py-0.5 rounded-full text-[0.65rem] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                                นอกเงื่อนไขสาขา
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="sec-badge {{ $row->has_multi_section ? 'is-multi' : '' }}">{{ $row->SECTION }}</span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if ($isZero)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[0.7rem] font-semibold bg-red-100 text-red-800 border border-red-200">
                                    <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                                    ไม่มีผู้ลงทะเบียน
                                </span>
                                <div class="text-[0.65rem] text-red-700/80 mt-0.5 font-medium">ENROLLSEAT = {{ $enrollSeat }}</div>
                            @elseif ($enrollSeat !== null)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[0.7rem] font-semibold bg-green-100 text-green-800 border border-green-200">
                                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                                    {{ number_format($enrollSeat) }} คน
                                </span>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600">{{ $row->officers ?: '-' }}</td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex flex-wrap justify-center gap-2">
                                <a href="{{ route('faculty-admin.settings.reg-grade-manage.edit', [
                                        'COURSECODE' => $row->COURSECODE,
                                        'SECTION' => $row->SECTION,
                                        'ACADYEAR' => $row->ACADYEAR,
                                        'SEMESTER' => $row->SEMESTER,
                                        'department_id' => $departmentId,
                                    ]) }}"
                                    class="px-3 py-1.5 border border-amber-300 rounded text-xs hover:bg-amber-50">
                                    แก้ไข
                                </a>
                                <form method="POST" action="{{ route('faculty-admin.settings.reg-grade-manage.destroy') }}"
                                    onsubmit="return confirm('ต้องการลบรายวิชา {{ $row->COURSECODE }} Sec. {{ $row->SECTION }} หรือไม่?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="COURSECODE" value="{{ $row->COURSECODE }}">
                                    <input type="hidden" name="SECTION" value="{{ $row->SECTION }}">
                                    <input type="hidden" name="ACADYEAR" value="{{ $row->ACADYEAR }}">
                                    <input type="hidden" name="SEMESTER" value="{{ $row->SEMESTER }}">
                                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
                                    <input type="hidden" name="q" value="{{ $q }}">
                                    <button type="submit" class="px-3 py-1.5 bg-red-600 text-white rounded text-xs hover:bg-red-700">ลบ</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @php $prevCode = $row->COURSECODE; @endphp
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-8 text-center text-gray-500">ไม่พบข้อมูลตามเงื่อนไขที่เลือก</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($courses->hasPages())
        <div class="flex justify-center">{{ $courses->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const subjectInput = document.getElementById('course-code');
    const courseNameInput = document.getElementById('course-name');
    const subjectBox = document.getElementById('subject-suggestions');
    const instructorInput = document.getElementById('instructor-search');
    const instructorBox = document.getElementById('instructor-suggestions');
    const officerId = document.getElementById('officer-id');
    const officerFname = document.getElementById('officer-fname');
    const officerLname = document.getElementById('officer-lname');
    const officerEmail = document.getElementById('officer-email');
    const instructorSelected = document.getElementById('instructor-selected');
    const form = document.getElementById('form-add-course');
    let subjectTimer = null;
    let instructorTimer = null;

    const hide = (el) => { el.classList.add('hidden'); el.innerHTML = ''; };

    const searchSubjects = async (q) => {
        if (q.length < 1) { hide(subjectBox); return; }
        try {
            const res = await fetch(`/api/subjects/search?q=${encodeURIComponent(q)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const rows = await res.json();
            if (!rows.length) { hide(subjectBox); return; }
            subjectBox.innerHTML = rows.map((row) => `
                <button type="button" data-code="${row.subject_code.replace(/"/g, '&quot;')}"
                    data-name="${(row.subject || '').replace(/"/g, '&quot;')}">
                    <span class="font-medium text-[#5C2E1F]">${row.subject_code}</span>
                    <span class="text-xs text-gray-500 block">${row.subject || ''}</span>
                </button>
            `).join('');
            subjectBox.classList.remove('hidden');
            subjectBox.querySelectorAll('button').forEach((btn) => {
                btn.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    subjectInput.value = btn.dataset.code;
                    courseNameInput.value = btn.dataset.name || courseNameInput.value;
                    hide(subjectBox);
                });
            });
        } catch {
            hide(subjectBox);
        }
    };

    const searchInstructors = async (q) => {
        if (q.length < 1) { hide(instructorBox); return; }
        try {
            const res = await fetch(`{{ route('faculty-admin.settings.reg-grade-manage.instructors.search') }}?q=${encodeURIComponent(q)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const rows = await res.json();
            if (!rows.length) { hide(instructorBox); return; }
            instructorBox.innerHTML = rows.map((row) => `
                <button type="button"
                    data-id="${String(row.officer_id).replace(/"/g, '&quot;')}"
                    data-fname="${String(row.fname || '').replace(/"/g, '&quot;')}"
                    data-lname="${String(row.lname || '').replace(/"/g, '&quot;')}"
                    data-email="${String(row.email || '').replace(/"/g, '&quot;')}"
                    data-name="${String(row.display_name || '').replace(/"/g, '&quot;')}">
                    <span class="font-medium text-[#5C2E1F]">${row.display_name}</span>
                    <span class="text-xs text-gray-500 block">${row.email || row.officer_id}</span>
                </button>
            `).join('');
            instructorBox.classList.remove('hidden');
            instructorBox.querySelectorAll('button').forEach((btn) => {
                btn.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    officerId.value = btn.dataset.id;
                    officerFname.value = btn.dataset.fname;
                    officerLname.value = btn.dataset.lname;
                    officerEmail.value = btn.dataset.email;
                    instructorInput.value = btn.dataset.name;
                    instructorSelected.textContent = `รหัส ${btn.dataset.id}${btn.dataset.email ? ' · ' + btn.dataset.email : ''}`;
                    instructorSelected.classList.remove('hidden');
                    hide(instructorBox);
                });
            });
        } catch {
            hide(instructorBox);
        }
    };

    subjectInput?.addEventListener('input', () => {
        clearTimeout(subjectTimer);
        subjectTimer = setTimeout(() => searchSubjects(subjectInput.value.trim()), 250);
    });
    subjectInput?.addEventListener('blur', () => setTimeout(() => hide(subjectBox), 150));

    instructorInput?.addEventListener('input', () => {
        clearTimeout(instructorTimer);
        if (!instructorInput.value.trim()) {
            officerId.value = '';
            officerFname.value = '';
            officerLname.value = '';
            officerEmail.value = '';
            instructorSelected.classList.add('hidden');
        }
        instructorTimer = setTimeout(() => searchInstructors(instructorInput.value.trim()), 250);
    });
    instructorInput?.addEventListener('blur', () => setTimeout(() => hide(instructorBox), 150));

    form?.addEventListener('submit', (e) => {
        if (!officerId.value) {
            e.preventDefault();
            alert('กรุณาเลือกอาจารย์ผู้สอนจากรายการค้นหา');
        }
    });

    // Bulk delete
    const checkAll = document.getElementById('check-all-courses');
    const rowChecks = Array.from(document.querySelectorAll('.course-row-check'));
    const selectedCountEl = document.getElementById('bulk-selected-count');
    const btnDeleteSelected = document.getElementById('btn-bulk-delete-selected');
    const btnDeleteAll = document.getElementById('btn-bulk-delete-all');
    const bulkForm = document.getElementById('form-bulk-delete');
    const bulkScope = document.getElementById('bulk-scope');
    const bulkItemsContainer = document.getElementById('bulk-items-container');
    const filteredTotal = {{ (int) $courses->total() }};

    const syncBulkUi = () => {
        const selected = rowChecks.filter((el) => el.checked);
        if (selectedCountEl) {
            selectedCountEl.textContent = `เลือกแล้ว ${selected.length} รายการ`;
        }
        if (btnDeleteSelected) {
            btnDeleteSelected.disabled = selected.length === 0;
        }
        if (checkAll) {
            checkAll.checked = rowChecks.length > 0 && selected.length === rowChecks.length;
            checkAll.indeterminate = selected.length > 0 && selected.length < rowChecks.length;
        }
    };

    checkAll?.addEventListener('change', () => {
        rowChecks.forEach((el) => { el.checked = checkAll.checked; });
        syncBulkUi();
    });

    rowChecks.forEach((el) => el.addEventListener('change', syncBulkUi));
    syncBulkUi();

    const submitBulk = (scope) => {
        if (!bulkForm || !bulkScope || !bulkItemsContainer) return;

        bulkScope.value = scope;
        bulkItemsContainer.innerHTML = '';

        if (scope === 'selected') {
            const selected = rowChecks.filter((el) => el.checked);
            if (!selected.length) {
                alert('กรุณาเลือกรายวิชาที่ต้องการลบ');
                return;
            }
            if (!confirm(`ต้องการลบรายวิชาที่เลือก ${selected.length} กลุ่ม หรือไม่?\n\nการลบจะลบทุกอาจารย์ในกลุ่มวิชา/Sec. นั้น`)) {
                return;
            }
            selected.forEach((el, index) => {
                const codeInput = document.createElement('input');
                codeInput.type = 'hidden';
                codeInput.name = `items[${index}][COURSECODE]`;
                codeInput.value = el.dataset.code || '';
                bulkItemsContainer.appendChild(codeInput);

                const secInput = document.createElement('input');
                secInput.type = 'hidden';
                secInput.name = `items[${index}][SECTION]`;
                secInput.value = el.dataset.section || '';
                bulkItemsContainer.appendChild(secInput);
            });
        } else {
            if (filteredTotal <= 0) {
                alert('ไม่มีรายวิชาตามเงื่อนไขที่กรอง');
                return;
            }
            if (!confirm(`ต้องการลบรายวิชาทั้งหมดตามเงื่อนไขที่กรอง (${filteredTotal} กลุ่ม) หรือไม่?\n\nรวมทุกหน้า ไม่ใช่เฉพาะหน้านี้`)) {
                return;
            }
            if (!confirm('ยืนยันอีกครั้ง: ลบทั้งหมดตามตัวกรอง สาขา/ภาค/ปี/คำค้น ปัจจุบัน?')) {
                return;
            }
        }

        bulkForm.submit();
    };

    btnDeleteSelected?.addEventListener('click', () => submitBulk('selected'));
    btnDeleteAll?.addEventListener('click', () => submitBulk('filtered'));
})();
</script>
@endpush
