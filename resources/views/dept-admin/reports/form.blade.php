@extends('layouts.scigrad')

@section('title', 'พิมพ์รายงานสาขา — Admin สาขา')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('dept-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">ตรวจสอบรายวิชา</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">พิมพ์รายงาน</span>
@endsection

@section('content')
@php
    $summary = $dateSummary ?? ['count' => 0, 'min_date' => null, 'max_date' => null, 'min_date_display' => null, 'max_date_display' => null, 'term_label' => '', 'year' => null];
    $defaultFrom = old('created_from', $summary['min_date'] ?? '');
    $defaultTo = old('created_to', $summary['max_date'] ?? '');
@endphp
<div class="max-w-4xl mx-auto">
    <h2 class="text-xl font-bold text-[#5C2E1F] mb-2">แบบรายงานผลการสอบไล่สำหรับเจ้าหน้าที่</h2>
    <p class="text-sm text-[#7A4A3A]/80 mb-6">เลือกสาขาวิชาเพื่อดูเงื่อนไขรหัสที่ใช้กรองรายงาน แล้วกำหนดช่วงวันที่และรูปแบบการส่งออก</p>

    @error('export')
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror
    @if ($errors->any() && ! $errors->has('export'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('dept-admin.reports.export') }}" id="dept-report-export-form" class="form-section rounded-xl p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สาขาวิชา *</label>
            <select name="department_id" id="report-department-id" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
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
            'helpText' => 'รายงานจะรวมเฉพาะรายวิชาที่รหัสตรงตามเงื่อนไขของสาขานี้ — ใช้ตรวจสอบก่อนเพิ่ม/ลบข้อมูลในอนาคต',
        ])

        <div>
            <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ระดับการศึกษา *</label>
            <select name="education_level" id="report-education-level" required class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                <option value="bachelor" @selected(old('education_level') === 'bachelor')>ปริญญาตรี</option>
                <option value="master" @selected(old('education_level') === 'master')>ปริญญาโท</option>
                <option value="doctoral" @selected(old('education_level') === 'doctoral')>ปริญญาเอก</option>
                <option value="graduate" @selected(old('education_level', 'graduate') === 'graduate')>บัณฑิตศึกษา (โท+เอก)</option>
                <option value="all" @selected(old('education_level') === 'all')>รวมทั้งหมด</option>
            </select>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                <select name="term" id="report-term" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">ทุกภาค</option>
                    <option value="1" @selected(old('term', $term) == 1)>ภาคต้น</option>
                    <option value="2" @selected(old('term', $term) == 2)>ภาคปลาย</option>
                    <option value="3" @selected(old('term', $term) == 3)>ภาคการศึกษาพิเศษ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                <select name="year" id="report-year" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">ทุกปี</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected(old('year', $year) == $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="submission-date-summary" class="rounded-lg border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-[#5C2E1F] space-y-1"
             data-url="{{ $dateSummaryUrl }}">
            <p class="font-semibold">สรุปช่วงวันที่อาจารย์รายงานผลสอบ</p>
            <p id="summary-scope-text">
                @if (($summary['count'] ?? 0) > 0)
                    {{ $summary['term_label'] }}
                    @if (! empty($summary['year'])) ปีการศึกษา {{ $summary['year'] }} @endif
                    — พบ {{ number_format($summary['count']) }} รายวิชา
                    ระหว่างวันที่ <strong id="summary-min-text">{{ $summary['min_date_display'] }}</strong>
                    ถึง <strong id="summary-max-text">{{ $summary['max_date_display'] }}</strong>
                @else
                    <span id="summary-empty-text">ยังไม่พบรายวิชาตามเงื่อนไขสาขา / ภาค / ปี ที่เลือก</span>
                @endif
            </p>
            <p class="text-xs text-[#7A4A3A]/80">ใช้ช่วงนี้เป็นแนวทางเลือกวันที่พิมพ์รายงาน (อิงวันที่กรอก/บันทึกจริง แสดงเป็น พ.ศ.)</p>
            <button type="button" id="btn-apply-summary-dates"
                    class="mt-1 text-xs font-medium text-[#8B4513] hover:underline disabled:opacity-40 disabled:no-underline"
                    @disabled(($summary['count'] ?? 0) === 0)>
                ใช้ช่วงวันที่จากสรุป
            </button>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">วันที่เริ่มต้น *</label>
                <input type="date" name="created_from" id="report-created-from" required
                       value="{{ $defaultFrom }}"
                       class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">วันที่สิ้นสุด *</label>
                <input type="date" name="created_to" id="report-created-to" required
                       value="{{ $defaultTo }}"
                       class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
        </div>

        <div>
            <p class="text-sm font-medium text-[#5C2E1F] mb-2">รูปแบบรายงาน *</p>
            <label class="flex items-center gap-2 text-sm mb-2">
                <input type="radio" name="report_status" value="0" @checked(old('report_status') === '0') class="accent-amber-700">
                ยังไม่ผ่านการรับรองผลสอบ (ที่ประชุมสาขาวิชา)
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="radio" name="report_status" value="1" @checked(old('report_status', '1') === '1') class="accent-amber-700">
                ผ่านการรับรองผลสอบ (ที่ประชุมสาขาวิชา)
            </label>
        </div>

        <div>
            <p class="text-sm font-medium text-[#5C2E1F] mb-2">รูปแบบไฟล์ *</p>
            <label class="inline-flex items-center gap-2 text-sm mr-6">
                <input type="radio" name="format" value="pdf" @checked(old('format', 'pdf') === 'pdf') class="accent-amber-700"> PDF
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="radio" name="format" value="word" @checked(old('format') === 'word') class="accent-amber-700"> Word (.docx)
            </label>
        </div>

        <div class="flex gap-3 flex-wrap">
            <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">ส่งออกรายงาน</button>
            <a href="{{ route('dept-admin.reviews.index') }}" class="px-5 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">กลับ</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const summaryBox = document.getElementById('submission-date-summary');
    if (!summaryBox) return;

    const url = summaryBox.dataset.url;
    const deptEl = document.getElementById('report-department-id');
    const levelEl = document.getElementById('report-education-level');
    const termEl = document.getElementById('report-term');
    const yearEl = document.getElementById('report-year');
    const fromEl = document.getElementById('report-created-from');
    const toEl = document.getElementById('report-created-to');
    const scopeEl = document.getElementById('summary-scope-text');
    const applyBtn = document.getElementById('btn-apply-summary-dates');

    let latestSummary = @json($summary);
    let fetchTimer = null;

    function applySummaryDates() {
        if (!latestSummary || !latestSummary.min_date || !latestSummary.max_date) return;
        fromEl.value = latestSummary.min_date;
        toEl.value = latestSummary.max_date;
    }

    function renderSummary(data) {
        latestSummary = data;
        if (data.count > 0) {
            const yearPart = data.year ? ` ปีการศึกษา ${data.year}` : '';
            scopeEl.innerHTML =
                `${data.term_label}${yearPart} — พบ ${Number(data.count).toLocaleString('th-TH')} รายวิชา ` +
                `ระหว่างวันที่ <strong>${data.min_date_display}</strong> ถึง <strong>${data.max_date_display}</strong>`;
            applyBtn.disabled = false;
        } else {
            scopeEl.innerHTML = 'ยังไม่พบรายวิชาตามเงื่อนไขสาขา / ภาค / ปี / ระดับการศึกษา ที่เลือก';
            applyBtn.disabled = true;
        }
    }

    async function refreshSummary() {
        const params = new URLSearchParams({
            department_id: deptEl.value || '',
            education_level: levelEl.value || 'all',
            term: termEl.value || '',
            year: yearEl.value || '',
        });

        scopeEl.textContent = 'กำลังโหลดสรุปช่วงวันที่…';
        applyBtn.disabled = true;

        try {
            const res = await fetch(`${url}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('summary failed');
            renderSummary(await res.json());
        } catch (e) {
            scopeEl.textContent = 'ไม่สามารถโหลดสรุปช่วงวันที่ได้ กรุณาลองใหม่';
            applyBtn.disabled = true;
        }
    }

    function scheduleRefresh() {
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(refreshSummary, 200);
    }

    [deptEl, levelEl, termEl, yearEl].forEach((el) => {
        el?.addEventListener('change', scheduleRefresh);
    });

    applyBtn?.addEventListener('click', applySummaryDates);
})();
</script>
@endpush
