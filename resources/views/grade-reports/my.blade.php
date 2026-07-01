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
        <a href="{{ route('grade-reports.create', ['term' => $term, 'year' => $year, 'return' => 'dashboard']) }}" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
            + สร้างรายงานใหม่
        </a>
    </div>

    <div class="form-section rounded-xl p-5 mb-5">
        <p class="text-sm text-[#7A4A3A]/80 mb-4">เลือกภาคการศึกษาและปีการศึกษาเพื่อแสดงรายการที่บันทึกไว้</p>
        <form method="GET" action="{{ route('grade-reports.my') }}" class="flex flex-wrap items-end gap-4">
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
            <button type="submit" class="px-5 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-semibold hover:bg-[#6B3410]">
                แสดงรายการ
            </button>
        </form>
    </div>

    <p class="text-sm text-[#7A4A3A]/80 mb-4">
        ภาค{{ $term === 1 ? 'ต้น' : ($term === 2 ? 'ปลาย' : 'การศึกษาพิเศษ') }} ปีการศึกษา {{ $year }}
        — พบ {{ $reports->count() }} รายการ
    </p>

    @if ($reports->isEmpty())
        <div class="text-center py-12 bg-white rounded-xl border border-dashed border-amber-300">
            <p class="text-[#5C2E1F] font-medium">ยังไม่มีรายการในภาคการศึกษานี้</p>
            <p class="text-sm text-gray-500 mt-1">ลองเปลี่ยนภาค/ปี หรือสร้างรายงานใหม่</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($reports as $report)
                @php
                    $statusClass = match ((int) $report->approv) {
                        1 => 'status-dept',
                        2 => 'status-approved',
                        -1 => 'status-rejected',
                        default => 'status-pending',
                    };
                @endphp
                <div class="menu-card rounded-xl p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-[#5C2E1F]">{{ $report->subject_code }} — {{ $report->subject }}</p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $report->termLabel() }} {{ $report->year }}
                                @if ($report->gradeStds->isNotEmpty())
                                    | Sec {{ $report->gradeStds->first()->sec ?? '-' }}
                                @endif
                                | {{ $report->teacher ?: '-' }}
                            </p>
                            <span class="inline-block mt-2 px-2 py-0.5 rounded text-xs font-medium {{ $statusClass }}">
                                {{ $report->statusShortLabel() }}
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if ($report->canEdit())
                                <a href="{{ route('grade-reports.edit', ['gradeReport' => $report, 'term' => $term, 'year' => $year, 'return' => 'my']) }}" class="px-3 py-1.5 text-xs border border-amber-300 rounded-lg hover:bg-amber-50">แก้ไข</a>
                            @endif
                            @if ($report->canPrint())
                                <a href="{{ route('grade-reports.print', $report) }}" target="_blank" class="px-3 py-1.5 text-xs bg-amber-700 text-white rounded-lg hover:bg-amber-800">พิมพ์ PDF</a>
                            @endif
                            @if ($report->canEdit())
                                <button type="button" data-delete="{{ $report->grade_id }}" class="px-3 py-1.5 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700">ลบ</button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function() {
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

    document.querySelectorAll('[data-delete]').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('ต้องการลบรายการนี้?')) return;
            const res = await fetch(`/api/grade-reports/${btn.dataset.delete}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            if (res.ok) {
                window.location.reload();
            } else {
                alert('ไม่สามารถลบรายการได้');
            }
        });
    });
})();
</script>
@endpush
