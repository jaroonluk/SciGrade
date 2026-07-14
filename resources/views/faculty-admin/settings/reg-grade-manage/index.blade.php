@extends('layouts.scigrad')

@section('title', 'จัดการข้อมูลรายวิชา REG — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">จัดการข้อมูลรายวิชา REG</span>
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

    tr.course-group-start td { border-top: 2px solid #d6b896 !important; }
    tr.course-group-cont td.col-course {
        padding-left: 1.75rem;
        color: #7A4A3A;
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
    .multi-sec-tag {
        display: inline-block;
        margin-left: 0.35rem;
        padding: 0.1rem 0.45rem;
        border-radius: 9999px;
        background: #e8f4ff;
        color: #075985;
        font-size: 0.65rem;
        font-weight: 600;
        vertical-align: middle;
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">จัดการข้อมูลรายวิชา REG</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            เพิ่ม / แก้ไข / ลบรายวิชา และกำหนด Sec. พร้อมอาจารย์ผู้สอน ตามสาขาวิชา ภาค และปีการศึกษา
        </p>
    </div>

    @error('manage')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

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
            </div>
            <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
                แสดงข้อมูล
            </button>
        </form>
    </div>

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <div class="px-4 py-3 bg-amber-50 border-b border-amber-200 text-sm text-[#5C2E1F] flex flex-wrap items-center justify-between gap-2">
            <span>
                พบ {{ number_format($courses->total()) }} รายการ
                @if ($q !== '')
                    <span class="text-xs text-gray-500">สำหรับคำค้น “{{ $q }}”</span>
                @endif
            </span>
            <span class="text-xs text-gray-500">
                หน้า {{ $courses->currentPage() }} / {{ max($courses->lastPage(), 1) }}
                (หน้าละ {{ $courses->perPage() }})
            </span>
        </div>
        <table class="w-full text-sm min-w-[860px]">
            <thead class="bg-amber-50/60">
                <tr>
                    <th class="px-3 py-2 text-left w-14">ลำดับ</th>
                    <th class="px-3 py-2 text-left">รายวิชา</th>
                    <th class="px-3 py-2 text-center">Sec.</th>
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
                        $rowClass = $isContinuation
                            ? 'bg-[#F8FBFF]'
                            : ($isGroupStart ? 'bg-[#FFF8F0] course-group-start' : ($index % 2 === 0 ? 'bg-white' : 'bg-[#F0FFFF]/40'));
                    @endphp
                    <tr class="border-t border-amber-100 {{ $rowClass }} {{ $isContinuation ? 'course-group-cont' : '' }}">
                        <td class="px-3 py-2 text-gray-500">{{ $courses->firstItem() + $index }}</td>
                        <td class="px-3 py-2 col-course">
                            @if ($isContinuation)
                                <span class="text-xs text-sky-700 font-medium">↳ Sec. ต่อเนื่อง · วิชาเดียวกัน</span>
                                <div class="text-xs text-gray-500">{{ $row->COURSECODE }}</div>
                            @else
                                <span class="font-medium text-[#5C2E1F]">{{ $row->COURSECODE }}</span>
                                {{ $row->COURSENAMEENG }}
                                @if ($row->has_multi_section)
                                    <span class="multi-sec-tag">{{ $row->section_count }} Sec.</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="sec-badge">{{ $row->SECTION }}</span>
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
                        <td colspan="5" class="px-3 py-8 text-center text-gray-500">ไม่พบข้อมูลตามเงื่อนไขที่เลือก</td>
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
})();
</script>
@endpush
