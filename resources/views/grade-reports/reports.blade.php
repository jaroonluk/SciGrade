@extends('layouts.scigrad')

@section('title', 'รายงาน — SciGrad')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">รายงาน</span>
@endsection

@section('content')
<div>
    <h2 class="text-xl font-bold text-[#5C2E1F] mb-6">
        {{ $role === 'faculty_admin' ? 'รายงานทุกสาขา' : ($role === 'dept_admin' ? 'รายงานตามสถานะ (สาขา)' : 'รายงานของฉัน') }}
    </h2>

    <div class="flex flex-wrap gap-3 mb-6 no-print">
        <select id="filter-approv" class="border border-amber-300 rounded px-3 py-2 text-sm bg-white">
            <option value="">ทุกสถานะ</option>
            <option value="0">ยังไม่ผ่านกรรมการ</option>
            <option value="1">สาขาอนุมัติ</option>
            <option value="2">คณะอนุมัติ</option>
            <option value="-1">ส่งกลับแก้ไข</option>
        </select>
        @if ($role === 'faculty_admin')
            <input id="filter-fac" type="text" placeholder="กรองคณะ (เช่น SC)" class="border border-amber-300 rounded px-3 py-2 text-sm w-40">
        @endif
        <button type="button" id="btn-print" class="px-4 py-2 bg-amber-700 text-white rounded text-sm hover:bg-amber-800">พิมพ์รายงาน</button>
    </div>

    <div id="report-table" class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <p class="text-gray-500 text-center py-8">กำลังโหลด...</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const role = @json($role);
    let allData = [];

    async function load() {
        const res = await fetch(`/api/grade-reports?role=${role}`, {
            headers: { 'Accept': 'application/json' }
        });
        allData = await res.json();
        render();
    }

    function render() {
        const approv = document.getElementById('filter-approv').value;
        const fac = document.getElementById('filter-fac')?.value?.trim().toUpperCase() || '';
        let rows = allData;
        if (approv !== '') rows = rows.filter(r => String(r.approv) === approv);
        if (fac) rows = rows.filter(r => (r.fac || '').toUpperCase().includes(fac));

        const el = document.getElementById('report-table');
        if (!rows.length) { el.innerHTML = '<p class="text-gray-500 text-center py-8">ไม่มีข้อมูล</p>'; return; }

        el.innerHTML = `<table class="w-full text-xs">
            <thead class="bg-amber-50"><tr>
                <th class="px-2 py-2 text-left">รหัสวิชา</th><th class="px-2 py-2 text-left">ชื่อวิชา</th>
                <th class="px-2 py-2">ภาค/ปี</th><th class="px-2 py-2">คณะ</th><th class="px-2 py-2">จำนวน</th>
                <th class="px-2 py-2">สถานะ</th><th class="px-2 py-2">ผู้สอน</th>
            </tr></thead>
            <tbody>${rows.map(r => `<tr class="border-t border-amber-100 hover:bg-amber-50/40">
                <td class="px-2 py-2">${r.subject_code}</td>
                <td class="px-2 py-2">${r.subject}</td>
                <td class="px-2 py-2 text-center">${r.semester_type} ${r.academic_year}</td>
                <td class="px-2 py-2 text-center">${r.fac||'-'}</td>
                <td class="px-2 py-2 text-center">${r.student_count}</td>
                <td class="px-2 py-2 text-center">${r.status}</td>
                <td class="px-2 py-2">${r.teacher||'-'}</td>
            </tr>`).join('')}</tbody></table>`;
    }

    document.getElementById('filter-approv').addEventListener('change', render);
    document.getElementById('filter-fac')?.addEventListener('input', render);
    document.getElementById('btn-print').addEventListener('click', () => {
        const approv = document.getElementById('filter-approv').value;
        const fac = document.getElementById('filter-fac')?.value || '';
        let url = '{{ route('grade-reports.print.summary') }}?';
        if (approv) url += `approv=${approv}&`;
        if (fac) url += `fac=${encodeURIComponent(fac)}`;
        window.open(url, '_blank');
    });
    load();
})();
</script>
@endpush
