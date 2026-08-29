@extends('layouts.scigrad')

@section('title', 'ประวัติการรับเอกสารจากหน่วยงาน — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('dashboard') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">ประวัติการรับเอกสารจากหน่วยงาน</span>
@endsection

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-[#5C2E1F]">ประวัติการรับเอกสารจากหน่วยงาน</h1>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            ดูรายการรับเอกสารใหม่ที่รอรับ และประวัติเอกสารที่รับแล้วจากสาขาวิชา
        </p>
    </div>

    <div class="form-section rounded-xl p-5 mb-6">
        <form method="GET" action="{{ route('faculty-admin.dept-submission-history.index') }}" class="flex flex-wrap items-end gap-4">
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
            <button type="submit" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">แสดงรายการ</button>
        </form>
    </div>

    <div class="form-section rounded-xl p-5 mb-6">
        <h2 class="text-base font-bold text-[#5C2E1F] mb-1 flex items-center gap-2">
            <i data-lucide="inbox" class="w-5 h-5"></i>
            รายการรับเอกสารใหม่
        </h2>
        <p class="text-sm text-[#7A4A3A]/80 mb-4">
            เอกสารที่สาขาส่งเข้ามาและยังรอรับ — กดรับเอกสารเมื่อตรวจสอบครบแล้ว
        </p>
        @include('faculty-admin.dept-submission-history.partials.open-list')
    </div>

    <div class="form-section rounded-xl p-5 mb-6">
        <h2 class="text-base font-bold text-[#5C2E1F] mb-1 flex items-center gap-2">
            <i data-lucide="history" class="w-5 h-5"></i>
            ประวัติการรับเอกสารจากสาขาวิชา
        </h2>
        <p class="text-sm text-[#7A4A3A]/80 mb-4">
            รายการเอกสารที่รับแล้ว แยกตามหน่วยงาน/สาขาวิชาที่ส่ง
        </p>
        @include('faculty-admin.dept-submission-history.partials.received-history')
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
    const refreshLucideIcons = () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    };

    document.querySelectorAll('.btn-receive-dept-submission').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const deptName = btn.dataset.departmentName || 'สาขานี้';
            const eduLabel = btn.dataset.educationLabel ? ` (${btn.dataset.educationLabel})` : '';
            if (!confirm(`ยืนยันรับเอกสารจากสาขา "${deptName}"${eduLabel} หรือไม่?\n\nหลังรับแล้วสาขาจะไม่สามารถแก้ไขชื่อหรือไฟล์ในรอบนี้ได้`)) return;

            btn.disabled = true;

            const res = await fetch(`/api/faculty-admin/dept-submissions/${btn.dataset.submissionId}/receive`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(data.message || data.errors?.submission?.[0] || 'รับเอกสารไม่สำเร็จ');
                btn.disabled = false;
                return;
            }

            window.location.reload();
        });
    });

    refreshLucideIcons();
})();
</script>
@endpush
