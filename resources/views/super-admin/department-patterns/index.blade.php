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
    .pattern-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 0.75rem;
        padding: 0.65rem 0.85rem;
        border-top: 1px solid #f3e4d8;
    }
    .pattern-row:hover { background: #fffaf5; }
    .pattern-edit-form { display: none; width: 100%; }
    .pattern-row.is-editing .pattern-view { display: none; }
    .pattern-row.is-editing .pattern-edit-form { display: flex; }
</style>
@endpush

@section('content')
@php
    $focusId = (int) (session('focus_department_id') ?: ($focusDepartmentId ?? 0));
@endphp
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-[#5C2E1F]">จัดการรหัสสาขาที่ใช้กรอง</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">
                กำหนดเงื่อนไขรหัสวิชาของแต่ละสาขา ที่ใช้กรองหน้ารายงาน / REG / ตรวจสอบสถานะ
                — รองรับรูปแบบเช่น <code class="text-[#8B4513]">319%</code>, <code class="text-[#8B4513]">%SC9%</code>, หรือรหัสตรงทั้งหมด
            </p>
        </div>
        <div class="rounded-xl border border-[#E8C4B8] bg-[#FFFBF7] px-4 py-3 text-center min-w-[8rem]">
            <p class="text-[0.65rem] text-[#A0522D]/70">สาขาทั้งหมด</p>
            <p class="text-2xl font-bold text-[#8B4513] leading-none">{{ $departments->count() }}</p>
        </div>
    </div>

    @error('pattern')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror
    @error('department_id')
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror

    <div class="form-section rounded-xl p-4">
        <form method="GET" action="{{ route('faculty-admin.department-patterns.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[16rem]">
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ค้นหา</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="ชื่อสาขา / รหัสเงื่อนไข เช่น SC9"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <button type="submit" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">ค้นหา</button>
            @if ($q !== '')
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
            @endphp
            <section id="dept-{{ $dept->department_id }}"
                     class="dept-pattern-card {{ $isFocus ? 'is-focus' : '' }}">
                <div class="px-4 py-3 bg-gradient-to-r from-[#FFFBF7] to-[#FAF0E6]/60 border-b border-[#E8C4B8]/70 flex flex-wrap items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="font-bold text-[#5C2E1F] flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-[#8B4513]"></i>
                            {{ $dept->department_name }}
                        </h3>
                        <p class="text-xs text-[#7A4A3A]/75 mt-0.5">
                            ID {{ $dept->department_id }} · {{ $dept->patterns->count() }} เงื่อนไข
                        </p>
                    </div>
                    <form method="POST" action="{{ route('faculty-admin.department-patterns.restore') }}"
                          onsubmit="return confirm('กู้คืนค่าเริ่มต้นของสาขา {{ $dept->department_name }}?\nเงื่อนไขปัจจุบันจะถูกแทนที่ทั้งหมด')">
                        @csrf
                        <input type="hidden" name="department_id" value="{{ $dept->department_id }}">
                        <input type="hidden" name="q" value="{{ $q }}">
                        <button type="submit" class="px-3 py-1.5 border border-amber-300 rounded-lg text-xs text-[#5C2E1F] hover:bg-amber-50">
                            กู้คืนค่าเริ่มต้น
                        </button>
                    </form>
                </div>

                <div class="px-4 py-3 border-b border-[#E8C4B8]/40 bg-[#FFFBF7]/40">
                    <div class="flex flex-wrap gap-2">
                        @forelse ($dept->pattern_details as $item)
                            <div class="pattern-chip is-{{ $item['kind'] }}" title="{{ $item['label'] }}">
                                <code>{{ $item['pattern'] }}</code>
                                <span>{{ $item['label'] }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-amber-800">ยังไม่มีเงื่อนไข — สาขานี้จะไม่พบรายวิชาเมื่อกรองตามสาขา</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    @foreach ($dept->patterns as $row)
                        <div class="pattern-row" data-pattern-row>
                            <div class="pattern-view flex flex-wrap items-center gap-2 w-full justify-between">
                                <div class="flex items-center gap-2 min-w-0">
                                    <code class="text-sm font-bold text-[#8B4513]">{{ $row->pattern }}</code>
                                    <span class="text-xs text-[#7A4A3A]/70">{{ app(\App\Services\DeptAdmin\DepartmentSubjectFilter::class)->describePattern($row->pattern) }}</span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" class="btn-edit-pattern px-2.5 py-1 border border-amber-300 rounded text-xs hover:bg-amber-50">แก้ไข</button>
                                    <form method="POST" action="{{ route('faculty-admin.department-patterns.destroy', $row) }}"
                                          onsubmit="return confirm('ลบเงื่อนไข {{ $row->pattern }} หรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="q" value="{{ $q }}">
                                        <button type="submit" class="px-2.5 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">ลบ</button>
                                    </form>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('faculty-admin.department-patterns.update', $row) }}" class="pattern-edit-form flex-wrap items-center gap-2">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="q" value="{{ $q }}">
                                <input type="text" name="pattern" value="{{ $row->pattern }}" required maxlength="100"
                                    class="flex-1 min-w-[12rem] border border-amber-300 rounded-lg px-3 py-1.5 text-sm bg-white uppercase font-mono">
                                <button type="submit" class="px-3 py-1.5 bg-[#8B4513] text-white rounded text-xs hover:bg-[#6B3410]">บันทึก</button>
                                <button type="button" class="btn-cancel-edit px-3 py-1.5 border border-amber-300 rounded text-xs hover:bg-amber-50">ยกเลิก</button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="px-4 py-3 bg-[#FAF0E6]/35">
                    <form method="POST" action="{{ route('faculty-admin.department-patterns.store') }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <input type="hidden" name="department_id" value="{{ $dept->department_id }}">
                        <input type="hidden" name="q" value="{{ $q }}">
                        <div class="flex-1 min-w-[14rem]">
                            <label class="block text-xs font-medium text-[#5C2E1F] mb-1">เพิ่มเงื่อนไขใหม่</label>
                            <input type="text" name="pattern" required maxlength="100" placeholder="เช่น 319% หรือ %SC9% หรือ SC904491"
                                class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white uppercase font-mono"
                                value="{{ (int) old('department_id') === (int) $dept->department_id ? old('pattern') : '' }}">
                        </div>
                        <button type="submit" class="px-4 py-2 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800">
                            เพิ่ม
                        </button>
                    </form>
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
