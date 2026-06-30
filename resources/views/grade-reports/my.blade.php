@extends('layouts.scigrad')

@section('title', 'รายงานของฉัน — SciGrad')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">รายงานของฉัน</span>
@endsection

@section('content')
<div>
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h2 class="text-xl font-bold text-[#5C2E1F]">รายงานผลสอบของฉัน</h2>
        <a href="{{ route('grade-reports.create') }}" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
            + สร้างรายงานใหม่
        </a>
    </div>
    <div id="my-list" class="space-y-3">
        <p class="text-gray-500 text-center py-8">กำลังโหลด...</p>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/templade-data-sdk.js') }}"></script>
<script>
(function() {
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

    async function load() {
        const res = await fetch('/api/grade-reports?role=instructor', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        const el = document.getElementById('my-list');
        if (!data.length) {
            el.innerHTML = '<p class="text-gray-500 text-center py-8">ยังไม่มีรายการ</p>';
            return;
        }
        el.innerHTML = data.map(r => {
            const sc = r.status==='ยังไม่ผ่านกรรมการ'?'status-pending':r.status==='สาขาอนุมัติ'?'status-dept':r.status==='คณะอนุมัติ'?'status-approved':'status-rejected';
            const canEdit = r.approv === 0 || r.approv === -1;
            return `<div class="menu-card rounded-xl p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">${r.subject_code} — ${r.subject}</p>
                        <p class="text-sm text-gray-500 mt-1">${r.semester_type} ${r.academic_year} | Sec ${r.section||'-'} | ${r.teacher||'-'}</p>
                        <span class="inline-block mt-2 px-2 py-0.5 rounded text-xs font-medium ${sc}">${r.status}</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        ${canEdit ? `<a href="/grade-reports/${r.__backendId}/edit" class="px-3 py-1.5 text-xs border border-amber-300 rounded-lg hover:bg-amber-50">แก้ไข</a>` : ''}
                        <a href="/grade-reports/${r.__backendId}/print" target="_blank" class="px-3 py-1.5 text-xs bg-amber-700 text-white rounded-lg hover:bg-amber-800">พิมพ์ PDF</a>
                        ${canEdit ? `<button type="button" data-delete="${r.__backendId}" class="px-3 py-1.5 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700">ลบ</button>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');
        el.querySelectorAll('[data-delete]').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('ต้องการลบรายการนี้?')) return;
                await fetch(`/api/grade-reports/${btn.dataset.delete}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                load();
            });
        });
    }
    load();
})();
</script>
@endpush
