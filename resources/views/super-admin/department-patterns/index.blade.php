@extends('layouts.scigrad')

@section('title', 'จัดการรหัสสาขาที่ใช้กรอง — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('dashboard') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">จัดการรหัสสาขาที่ใช้กรอง</span>
@endsection

@push('styles')
<style>
    .pattern-chip {
        display: inline-flex;
        flex-direction: column;
        gap: 0.15rem;
        min-width: 7rem;
        padding: 0.5rem 0.7rem;
        border-radius: 0.75rem;
        border: 1px solid #e8c4b8;
        background: linear-gradient(180deg, #fffdfb 0%, #faf0e6 100%);
    }
    .pattern-chip code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.82rem;
        font-weight: 700;
        color: #8B4513;
    }
    .pattern-chip span { font-size: 0.65rem; color: #7A4A3A; line-height: 1.2; }
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

    .dept-pattern-card {
        border: 1px solid #e8c4b8;
        border-radius: 1rem;
        background: #fff;
        overflow: hidden;
        transition: box-shadow .15s ease, border-color .15s ease;
    }
    .dept-pattern-card.is-focus {
        border-color: #8B4513;
        box-shadow: 0 0 0 2px rgba(139, 69, 19, 0.18);
    }
    .level-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.75rem;
        padding: 0.85rem;
    }
    @media (min-width: 960px) {
        .level-grid { grid-template-columns: 1fr 1fr; }
    }
    .level-panel {
        border-radius: 0.85rem;
        overflow: hidden;
        border: 1px solid #e8c4b8;
        background: #fff;
    }
    .level-panel.is-bach { border-color: #e8c4b8; }
    .level-panel.is-grad { border-color: #ddd6fe; }
    .level-panel-head {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.7rem 0.85rem;
    }
    .level-panel.is-bach .level-panel-head {
        background: linear-gradient(180deg, #fffbf7 0%, #faf0e6 100%);
        border-bottom: 1px solid #e8c4b8;
    }
    .level-panel.is-grad .level-panel-head {
        background: linear-gradient(180deg, #faf5ff 0%, #ede9fe 100%);
        border-bottom: 1px solid #ddd6fe;
    }
    .level-panel-title {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.88rem;
        font-weight: 700;
    }
    .level-panel.is-bach .level-panel-title { color: #5C2E1F; }
    .level-panel.is-grad .level-panel-title { color: #4c1d95; }
    .level-panel-hint {
        margin-top: 0.2rem;
        font-size: 0.68rem;
        color: #7A4A3A;
        opacity: 0.85;
    }
    .pattern-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 0.75rem;
        padding: 0.55rem 0.75rem;
        border-top: 1px solid #f3e4d8;
    }
    .level-panel.is-grad .pattern-row { border-top-color: #ede9fe; }
    .pattern-row:hover { background: #fffaf5; }
    .level-panel.is-grad .pattern-row:hover { background: #faf5ff; }
    .pattern-edit-form { display: none; width: 100%; }
    .pattern-row.is-editing .pattern-view { display: none; }
    .pattern-row.is-editing .pattern-edit-form { display: flex; }
    .guide-layer {
        border: 1px solid #e8c4b8;
        border-radius: 1rem;
        background: linear-gradient(180deg, #fffdfb 0%, #faf0e6 100%);
        padding: 1rem 1.15rem;
    }
</style>
@endpush

@section('content')
@php
    $focusId = (int) (session('focus_department_id') ?: ($focusDepartmentId ?? 0));
    $viewFilter = $viewFilter ?? 'all';
@endphp
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-[#5C2E1F]">จัดการรหัสสาขาที่ใช้กรอง</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">
                แต่ละสาขาแยกชัดว่าใช้รหัสใดกรอง <strong>ปริญญาตรี</strong> และรหัสใดกรอง <strong>บัณฑิตศึกษา / ป.บัณฑิต</strong>
            </p>
        </div>
        <div class="rounded-xl border border-[#E8C4B8] bg-[#FFFBF7] px-4 py-3 text-center min-w-[8rem]">
            <p class="text-[0.65rem] text-[#A0522D]/70">สาขาทั้งหมด</p>
            <p class="text-2xl font-bold text-[#8B4513] leading-none">{{ $departments->count() }}</p>
        </div>
    </div>

    <div class="guide-layer space-y-2 text-sm text-[#5C2E1F]">
        <p class="font-semibold flex items-center gap-2">
            <i data-lucide="layers" class="w-4 h-4 text-[#8B4513]"></i>
            ชั้นตัวกรองรหัสวิชา
        </p>
        <ul class="list-disc pl-5 space-y-1 text-[#7A4A3A] text-xs leading-relaxed">
            <li><strong class="text-[#5C2E1F]">รหัสวิชาปริญญาตรี</strong> — เงื่อนไขจากฐานข้อมูลเดิมของสาขา ใช้เมื่อกรองระดับปริญญาตรี (และเป็นค่าเริ่มต้นเมื่อไม่ได้ระบุบัณฑิตศึกษา)</li>
            <li><strong class="text-violet-900">รหัสวิชาบัณฑิตศึกษา / ป.บัณฑิต</strong> — เงื่อนไขเพิ่มเติมเฉพาะระดับบัณฑิตศึกษา</li>
            <li>ถ้ารหัสวิชาไม่อยู่ในเงื่อนไขบัณฑิตศึกษาที่กำหนด จะจัดอยู่ในกลุ่มกรองด้วยเงื่อนไขปริญญาตรี</li>
        </ul>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('status') }}</div>
    @endif
    @error('pattern')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror
    @error('department_id')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

    <div class="form-section rounded-xl p-4">
        <form method="GET" action="{{ route('faculty-admin.department-patterns.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">แสดงชั้นกรอง</label>
                <select name="education_level" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[14rem]">
                    <option value="all" @selected($viewFilter === 'all')>ทั้งหมด (ปริญญาตรี + บัณฑิตศึกษา)</option>
                    <option value="bachelor" @selected($viewFilter === 'bachelor')>เฉพาะรหัสปริญญาตรี</option>
                    <option value="graduate" @selected($viewFilter === 'graduate')>เฉพาะรหัสบัณฑิตศึกษา</option>
                </select>
            </div>
            <div class="flex-1 min-w-[16rem]">
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ค้นหา</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="ชื่อสาขา / รหัสเงื่อนไข เช่น สถิติ หรือ SC9"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <button type="submit" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">แสดง</button>
            @if ($q !== '' || $viewFilter !== 'all')
                <a href="{{ route('faculty-admin.department-patterns.index') }}" class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ล้าง</a>
            @endif
        </form>
        <div class="mt-3 flex flex-wrap gap-4 text-[0.7rem] text-[#7A4A3A]/75">
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#C4725C]"></span> ขึ้นต้น / ลงท้าย</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#5a9a63]"></span> มีข้อความในรหัส</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#4a7fb0]"></span> รหัสตรงทั้งหมด</span>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($departments as $dept)
            @php
                $isFocus = $focusId === (int) $dept->department_id;
                $showBachelor = $viewFilter === 'all' || $viewFilter === 'bachelor';
                $showGraduate = $viewFilter === 'all' || $viewFilter === 'graduate';
            @endphp
            <section id="dept-{{ $dept->department_id }}"
                     class="dept-pattern-card {{ $isFocus ? 'is-focus' : '' }}">
                <div class="px-4 py-3 bg-gradient-to-r from-[#FFFBF7] to-[#FAF0E6]/60 border-b border-[#E8C4B8]/70">
                    <h3 class="font-bold text-[#5C2E1F] flex items-center gap-2">
                        <i data-lucide="building-2" class="w-4 h-4 text-[#8B4513]"></i>
                        {{ $dept->department_name }}
                    </h3>
                    <p class="text-xs text-[#7A4A3A]/75 mt-0.5">
                        ID {{ $dept->department_id }}
                        · ปริญญาตรี <strong>{{ $dept->bachelor_count ?? 0 }}</strong> รหัส
                        · บัณฑิตศึกษา <strong>{{ $dept->graduate_count ?? 0 }}</strong> รหัส
                    </p>
                </div>

                <div class="level-grid {{ $viewFilter !== 'all' ? '!grid-cols-1' : '' }}">
                    @if ($showBachelor)
                        @include('super-admin.department-patterns.partials.level-panel', [
                            'dept' => $dept,
                            'q' => $q,
                            'viewFilter' => $viewFilter,
                            'level' => \App\Models\DepartmentSubjectPattern::EDUCATION_BACHELOR,
                            'patterns' => $dept->bachelor_patterns ?? collect(),
                            'details' => $dept->bachelor_details ?? [],
                        ])
                    @endif

                    @if ($showGraduate)
                        @include('super-admin.department-patterns.partials.level-panel', [
                            'dept' => $dept,
                            'q' => $q,
                            'viewFilter' => $viewFilter,
                            'level' => \App\Models\DepartmentSubjectPattern::EDUCATION_GRADUATE,
                            'patterns' => $dept->graduate_patterns ?? collect(),
                            'details' => $dept->graduate_details ?? [],
                        ])
                    @endif
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-amber-300 bg-white px-6 py-10 text-center text-sm text-gray-500">
                ไม่พบสาขาตามคำค้น
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    document.querySelectorAll('[data-pattern-row]').forEach((row) => {
        row.querySelector('.btn-edit-pattern')?.addEventListener('click', () => {
            document.querySelectorAll('[data-pattern-row].is-editing').forEach((el) => el.classList.remove('is-editing'));
            row.classList.add('is-editing');
            row.querySelector('input[name="pattern"]')?.focus();
        });
        row.querySelector('.btn-cancel-edit')?.addEventListener('click', () => {
            row.classList.remove('is-editing');
        });
    });

    const focus = document.querySelector('.dept-pattern-card.is-focus');
    if (focus) {
        focus.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    if (window.lucide?.createIcons) window.lucide.createIcons();
})();
</script>
@endpush
