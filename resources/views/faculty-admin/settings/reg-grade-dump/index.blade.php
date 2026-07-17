@extends('layouts.scigrad')

@section('title', 'Download ข้อมูลรายวิชา — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">Download ข้อมูลรายวิชา</span>
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
</style>
@endpush

@section('content')
@php
    $termLabel = match ($term) {
        1 => 'ภาคต้น',
        2 => 'ภาคปลาย',
        default => 'ภาคการศึกษาพิเศษ',
    };
    $zeroEnrollmentCount = (int) ($zeroEnrollmentCount ?? 0);
@endphp
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">Download รายวิชา REG</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            ดึงรายวิชาจาก REG (รวม SEMINAR) เข้า grade_report_reg — วิชาที่มีอยู่แล้วจะถูกทับด้วยข้อมูลใหม่
            และระบบจะแจ้งกลุ่มที่ไม่มีผู้ลงทะเบียน
        </p>
    </div>

    @unless ($canConnect)
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            เชื่อมต่อฐานข้อมูล REG ไม่ได้ กรุณาตรวจสอบการตั้งค่าฐานข้อมูล
            <span class="font-semibold">reg</span>
        </div>
    @endunless

    @error('dump')
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
                class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]"
                @disabled(! $canConnect)
                onclick="return confirm('ดึงรายวิชาจาก REG (รวม SEMINAR) และทับข้อมูลเดิมใน grade_report_reg ตามภาค/ปีที่เลือก?')">
                Download จาก REG
            </button>
        </form>
        <p class="text-xs text-[#7A4A3A]/75 mt-3">
            รวมวิชา SEMINAR · ทับข้อมูลเดิมทั้งกลุ่มวิชา/Sec. · ยังไม่ดึง THESIS / Independent Study / Dissertation
        </p>
    </div>

    <div class="form-section rounded-xl p-6">
        <h3 class="font-semibold text-[#5C2E1F] mb-3">เพิ่มรายวิชา</h3>
        <form method="POST" action="{{ route('faculty-admin.settings.reg-grade-dump.store') }}" id="form-add-course" class="space-y-4">
            @csrf
            <input type="hidden" name="department_id" value="{{ $departmentId }}">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="SEMESTER" value="{{ $term }}">
            <input type="hidden" name="ACADYEAR" value="{{ $year }}">
            <input type="hidden" name="OFFICERID" id="officer-id" value="{{ old('OFFICERID') }}">
            <input type="hidden" name="OFFICERNAME" id="officer-fname" value="{{ old('OFFICERNAME') }}">
            <input type="hidden" name="OFFICERSURNAME" id="officer-lname" value="{{ old('OFFICERSURNAME') }}">
            <input type="hidden" name="KKUMAIL" id="officer-email" value="{{ old('KKUMAIL') }}">

            <div class="grid md:grid-cols-2 gap-4">
                <div class="relative">
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">รหัสวิชา *</label>
                    <input type="text" name="COURSECODE" id="course-code" value="{{ old('COURSECODE') }}" required autocomplete="off"
                        placeholder="พิมพ์รหัสหรือชื่อวิชา"
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
            <button type="submit" class="px-5 py-2.5 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800">
                เพิ่มข้อมูล
            </button>
        </form>
    </div>

    <div class="form-section rounded-xl p-6">
        <h3 class="font-semibold text-[#5C2E1F] mb-3">ดูรายวิชาในระบบ</h3>
        <form method="GET" action="{{ route('faculty-admin.settings.reg-grade-dump.index') }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">หน่วยงาน / สาขาวิชา</label>
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
                <select name="year" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[14rem]">
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ค้นหา</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="รหัสวิชา / ชื่อวิชา / ชื่ออาจารย์"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <button type="submit" class="px-5 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">
                แสดงรายการ
            </button>
        </form>
        @if ($canConnect)
            <p class="text-xs text-[#7A4A3A]/75 mt-3">
                สถานะผู้ลงทะเบียนดึงจาก REG อัตโนมัติ — แถวสีส้มและป้าย
                <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-100 text-red-700 font-semibold">ไม่มีผู้ลงทะเบียน</span>
                หมายถึง ENROLLSEAT = 0 หรือไม่พบข้อมูลลงทะเบียน
            </p>
        @endif
    </div>

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <div class="px-4 py-3 bg-amber-50 border-b border-amber-200 text-sm text-[#5C2E1F] flex flex-wrap items-center justify-between gap-2">
            <span>
                รายวิชาใน {{ $termLabel }} ปีการศึกษา {{ $year }}
                — ทั้งหมด {{ number_format($courses->total()) }} กลุ่ม
                @if (($zeroEnrollmentCount ?? 0) > 0)
                    <span class="text-red-700 font-medium">· ไม่มีผู้ลงทะเบียนในหน้านี้ {{ $zeroEnrollmentCount }} กลุ่ม</span>
                @endif
            </span>
            <span class="text-xs text-gray-500">
                หน้า {{ $courses->currentPage() }} / {{ max($courses->lastPage(), 1) }}
                (หน้าละ {{ $courses->perPage() }})
            </span>
        </div>

        @if ($courses->total() > 0)
            <div class="px-4 py-3 border-b border-amber-100 bg-white flex flex-wrap items-center gap-3">
                <span class="text-sm text-[#5C2E1F] font-medium">ลบหลายรายการ:</span>
                <span id="bulk-selected-count" class="text-xs text-gray-500">เลือกแล้ว 0 รายการ</span>
                <button type="button" id="btn-bulk-delete-selected"
                    class="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700 disabled:opacity-40"
                    disabled>ลบที่เลือก</button>
                <button type="button" id="btn-bulk-delete-all"
                    class="px-3 py-1.5 border border-red-300 text-red-700 rounded text-xs font-medium hover:bg-red-50">
                    ลบทั้งหมดตามเงื่อนไขที่กรอง ({{ number_format($courses->total()) }} กลุ่ม)
                </button>
            </div>
        @endif

        <form id="form-bulk-delete" method="POST" action="{{ route('faculty-admin.settings.reg-grade-dump.bulk-destroy') }}" class="hidden">
            @csrf
            @method('DELETE')
            <input type="hidden" name="scope" id="bulk-scope" value="selected">
            <input type="hidden" name="ACADYEAR" value="{{ $year }}">
            <input type="hidden" name="SEMESTER" value="{{ $term }}">
            <input type="hidden" name="department_id" value="{{ $departmentId }}">
            <input type="hidden" name="q" value="{{ $q }}">
            <div id="bulk-items-container"></div>
        </form>

        <table class="w-full text-sm min-w-[960px]">
            <thead class="bg-amber-50/60">
                <tr>
                    <th class="px-3 py-2 text-center w-12">
                        @if ($courses->total() > 0)
                            <input type="checkbox" id="check-all-courses" class="rounded border-amber-300 text-[#8B4513] focus:ring-[#8B4513]">
                        @endif
                    </th>
                    <th class="px-3 py-2 text-left w-14">#</th>
                    <th class="px-3 py-2 text-left">รหัสวิชา</th>
                    <th class="px-3 py-2 text-left">ชื่อวิชา (ENG)</th>
                    <th class="px-3 py-2 text-center">กลุ่ม</th>
                    <th class="px-3 py-2 text-center">สถานะลงทะเบียน</th>
                    <th class="px-3 py-2 text-left">อาจารย์</th>
                    <th class="px-3 py-2 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($courses as $index => $row)
                    @php
                        $isZero = (bool) ($row->has_no_enrollment ?? false);
                        $enrollSeat = $row->enrollseat ?? null;
                    @endphp
                    <tr class="border-t border-amber-100 {{ $isZero ? 'bg-amber-50/90' : '' }}">
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox"
                                class="course-row-check rounded border-amber-300 text-[#8B4513] focus:ring-[#8B4513]"
                                data-code="{{ $row->COURSECODE }}"
                                data-section="{{ $row->SECTION }}">
                        </td>
                        <td class="px-3 py-2 text-gray-500">{{ $courses->firstItem() + $index }}</td>
                        <td class="px-3 py-2 font-medium">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                @if ($isZero)
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-600 text-white text-[0.65rem] font-bold" title="ไม่มีผู้ลงทะเบียน">!</span>
                                @endif
                                <span>{{ $row->COURSECODE }}</span>
                            </div>
                        </td>
                        <td class="px-3 py-2">{{ $row->COURSENAMEENG }}</td>
                        <td class="px-3 py-2 text-center"><span class="sec-badge">{{ $row->SECTION }}</span></td>
                        <td class="px-3 py-2 text-center">
                            @if ($isZero)
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[0.7rem] font-semibold bg-red-100 text-red-800 border border-red-200">
                                    <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                                    ไม่มีผู้ลงทะเบียน
                                </span>
                                <div class="text-[0.65rem] text-red-700/80 mt-0.5">ENROLLSEAT = {{ $enrollSeat }}</div>
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
                                <form method="POST" action="{{ route('faculty-admin.settings.reg-grade-dump.destroy') }}"
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
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-8 text-center text-gray-500">
                            ไม่พบรายวิชาในเงื่อนไขที่เลือก — กด Download จาก REG หรือเปลี่ยนตัวกรอง
                        </td>
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
        } catch { hide(subjectBox); }
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
        } catch { hide(instructorBox); }
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
        if (selectedCountEl) selectedCountEl.textContent = `เลือกแล้ว ${selected.length} รายการ`;
        if (btnDeleteSelected) btnDeleteSelected.disabled = selected.length === 0;
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
            if (!selected.length) { alert('กรุณาเลือกรายวิชาที่ต้องการลบ'); return; }
            if (!confirm(`ต้องการลบรายวิชาที่เลือก ${selected.length} กลุ่ม หรือไม่?`)) return;
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
            if (filteredTotal <= 0) { alert('ไม่มีรายวิชาตามเงื่อนไขที่กรอง'); return; }
            if (!confirm(`ต้องการลบรายวิชาทั้งหมดตามเงื่อนไขที่กรอง (${filteredTotal} กลุ่ม) หรือไม่?\nรวมทุกหน้า`)) return;
            if (!confirm('ยืนยันอีกครั้ง: ลบทั้งหมดตามตัวกรองปัจจุบัน?')) return;
        }
        bulkForm.submit();
    };

    btnDeleteSelected?.addEventListener('click', () => submitBulk('selected'));
    btnDeleteAll?.addEventListener('click', () => submitBulk('filtered'));

    if (window.lucide?.createIcons) window.lucide.createIcons();
})();
</script>
@endpush
