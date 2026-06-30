@extends('layouts.scigrad')

@section('title', 'อนุมัติรายงาน — SciGrad')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">อนุมัติรายงาน</span>
@endsection

@section('content')
<div>
    <h2 class="text-xl font-bold text-[#5C2E1F] mb-2">
        {{ $role === 'faculty_admin' ? 'อนุมัติระดับคณะ' : 'ตรวจสอบและอนุมัติ (สาขา)' }}
    </h2>
    <p class="text-sm text-[#7A4A3A]/80 mb-6">
        {{ $role === 'faculty_admin' ? 'อนุมัติรายการที่สาขาอนุมัติแล้ว' : 'อนุมัติหรือส่งกลับรายการที่ยังไม่ผ่านกรรมการ' }}
    </p>
    <div id="approve-list" class="space-y-3">
        <p class="text-gray-500 text-center py-8">กำลังโหลด...</p>
    </div>
</div>

<div id="reject-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden no-print">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl mx-4">
        <h3 class="font-bold text-lg mb-3 text-[#5C2E1F]">ระบุเหตุผลที่ส่งกลับ</h3>
        <textarea id="reject-reason" rows="3" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm mb-4"></textarea>
        <div class="flex gap-3 justify-end">
            <button type="button" id="btn-cancel-reject" class="px-4 py-2 border rounded-lg text-sm">ยกเลิก</button>
            <button type="button" id="btn-confirm-reject" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium">ยืนยันส่งกลับ</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/templade-data-sdk.js') }}"></script>
<script>
(function() {
    const role = @json($role);
    let pendingId = null;
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

    async function load() {
        const res = await fetch(`/api/grade-reports?role=${role}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const filtered = data.filter(r => role === 'dept_admin' ? r.approv === 0 : r.approv === 1);
        const el = document.getElementById('approve-list');
        if (!filtered.length) {
            el.innerHTML = '<p class="text-gray-500 text-center py-8">ไม่มีรายการรออนุมัติ</p>';
            return;
        }
        el.innerHTML = filtered.map(r => `<div class="menu-card rounded-xl p-4">
            <p class="font-semibold text-[#5C2E1F]">${r.subject_code} ${r.subject}</p>
            <p class="text-sm text-gray-500 mt-1">${r.semester_type} ${r.academic_year} | ${r.teacher} | จำนวน ${r.student_count} คน</p>
            <div class="flex gap-2 mt-3">
                <button type="button" data-approve="${r.__backendId}" class="px-3 py-1.5 bg-green-600 text-white rounded text-xs font-medium">อนุมัติ</button>
                <button type="button" data-reject="${r.__backendId}" class="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-medium">ส่งกลับ</button>
                <a href="/grade-reports/${r.__backendId}/print" target="_blank" class="px-3 py-1.5 border border-amber-300 rounded text-xs">ดู/PDF</a>
            </div>
        </div>`).join('');
        el.querySelectorAll('[data-approve]').forEach(b => b.addEventListener('click', () => approve(b.dataset.approve)));
        el.querySelectorAll('[data-reject]').forEach(b => b.addEventListener('click', () => { pendingId = b.dataset.reject; document.getElementById('reject-modal').classList.remove('hidden'); }));
    }

    async function approve(id) {
        const approv = role === 'dept_admin' ? 1 : 2;
        await fetch(`/api/grade-reports/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: JSON.stringify({ approv, role })
        });
        load();
    }

    document.getElementById('btn-cancel-reject').onclick = () => { document.getElementById('reject-modal').classList.add('hidden'); pendingId = null; };
    document.getElementById('btn-confirm-reject').onclick = async () => {
        const reason = document.getElementById('reject-reason').value.trim();
        if (!reason || !pendingId) return;
        await fetch(`/api/grade-reports/${pendingId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
            body: JSON.stringify({ approv: -1, rejection_reason: reason, role })
        });
        document.getElementById('reject-modal').classList.add('hidden');
        document.getElementById('reject-reason').value = '';
        pendingId = null;
        load();
    };
    load();
})();
</script>
@endpush
