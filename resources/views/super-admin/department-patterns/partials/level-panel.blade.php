@php
    /** @var string $level bachelor|graduate */
    /** @var \Illuminate\Support\Collection $patterns */
    /** @var list<array{pattern: string, label: string, kind: string}> $details */
    /** @var object $dept */
    /** @var string $q */
    $isGraduate = $level === \App\Models\DepartmentSubjectPattern::EDUCATION_GRADUATE;
    $levelLabel = \App\Models\DepartmentSubjectPattern::label($level);
    $tone = $isGraduate ? 'grad' : 'bach';
@endphp
<div class="level-panel is-{{ $tone }}">
    <div class="level-panel-head">
        <div class="min-w-0">
            <p class="level-panel-title">
                <i data-lucide="{{ $isGraduate ? 'graduation-cap' : 'book-open' }}" class="w-4 h-4"></i>
                {{ $isGraduate ? 'รหัสวิชา บัณฑิตศึกษา / ป.บัณฑิต' : 'รหัสวิชา ปริญญาตรี' }}
            </p>
            <p class="level-panel-hint">
                @if ($isGraduate)
                    เงื่อนไขเพิ่มเติมสำหรับกรองระดับบัณฑิตศึกษา
                @else
                    เงื่อนไขจากฐานข้อมูลเดิมของสาขา (ค่าเริ่มต้นเมื่อไม่ได้ระบุบัณฑิตศึกษา)
                @endif
                · {{ $patterns->count() }} เงื่อนไข
            </p>
        </div>
        <form method="POST" action="{{ route('faculty-admin.department-patterns.restore') }}"
              onsubmit="return confirm('กู้คืนค่าเริ่มต้นระดับ{{ $levelLabel }} ของสาขา {{ $dept->department_name }}?\nเงื่อนไขระดับนี้จะถูกแทนที่ (อีกระดับไม่ถูกเปลี่ยน)')">
            @csrf
            <input type="hidden" name="department_id" value="{{ $dept->department_id }}">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="education_level" value="{{ $level }}">
            <input type="hidden" name="view" value="{{ $viewFilter ?? 'all' }}">
            <button type="submit" class="px-2.5 py-1 border rounded-lg text-[0.7rem] hover:bg-white/70
                {{ $isGraduate ? 'border-violet-300 text-violet-900' : 'border-amber-300 text-[#5C2E1F]' }}">
                กู้คืนค่าเริ่มต้น
            </button>
        </form>
    </div>

    <div class="px-3 py-2.5 border-b {{ $isGraduate ? 'border-violet-100 bg-violet-50/40' : 'border-amber-100 bg-amber-50/40' }}">
        <div class="flex flex-wrap gap-2">
            @forelse ($details as $item)
                <div class="pattern-chip is-{{ $item['kind'] }}" title="{{ $item['label'] }}">
                    <code>{{ $item['pattern'] }}</code>
                    <span>{{ $item['label'] }}</span>
                </div>
            @empty
                <p class="text-xs {{ $isGraduate ? 'text-violet-800' : 'text-amber-800' }}">
                    ยังไม่มีเงื่อนไขระดับ{{ $levelLabel }}
                    @if ($isGraduate)
                        — เมื่อกรองบัณฑิตศึกษา สาขานี้จะไม่พบรายวิชาจนกว่าจะเพิ่มรหัส
                    @else
                        — เมื่อกรองปริญญาตรี สาขานี้จะไม่พบรายวิชาจนกว่าจะเพิ่มรหัส
                    @endif
                </p>
            @endforelse
        </div>
    </div>

    <div>
        @foreach ($patterns as $row)
            <div class="pattern-row" data-pattern-row>
                <div class="pattern-view flex flex-wrap items-center gap-2 w-full justify-between">
                    <div class="flex items-center gap-2 min-w-0">
                        <code class="text-sm font-bold {{ $isGraduate ? 'text-violet-800' : 'text-[#8B4513]' }}">{{ $row->pattern }}</code>
                        <span class="text-xs text-[#7A4A3A]/70">{{ app(\App\Services\DeptAdmin\DepartmentSubjectFilter::class)->describePattern($row->pattern) }}</span>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" class="btn-edit-pattern px-2.5 py-1 border rounded text-xs hover:bg-white
                            {{ $isGraduate ? 'border-violet-300' : 'border-amber-300' }}">แก้ไข</button>
                        <form method="POST" action="{{ route('faculty-admin.department-patterns.destroy', $row->id) }}"
                              onsubmit="return confirm('ลบเงื่อนไข {{ $row->pattern }} ({{ $levelLabel }}) หรือไม่?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="q" value="{{ $q }}">
                            <input type="hidden" name="education_level" value="{{ $level }}">
                            <input type="hidden" name="view" value="{{ $viewFilter ?? 'all' }}">
                            <button type="submit" class="px-2.5 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">ลบ</button>
                        </form>
                    </div>
                </div>
                <form method="POST" action="{{ route('faculty-admin.department-patterns.update', $row->id) }}" class="pattern-edit-form flex-wrap items-center gap-2">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="q" value="{{ $q }}">
                    <input type="hidden" name="education_level" value="{{ $level }}">
                    <input type="hidden" name="view" value="{{ $viewFilter ?? 'all' }}">
                    <input type="text" name="pattern" value="{{ $row->pattern }}" required maxlength="100"
                        class="flex-1 min-w-[12rem] border rounded-lg px-3 py-1.5 text-sm bg-white uppercase font-mono
                            {{ $isGraduate ? 'border-violet-300' : 'border-amber-300' }}">
                    <button type="submit" class="px-3 py-1.5 text-white rounded text-xs
                        {{ $isGraduate ? 'bg-violet-700 hover:bg-violet-800' : 'bg-[#8B4513] hover:bg-[#6B3410]' }}">บันทึก</button>
                    <button type="button" class="btn-cancel-edit px-3 py-1.5 border rounded text-xs hover:bg-white
                        {{ $isGraduate ? 'border-violet-300' : 'border-amber-300' }}">ยกเลิก</button>
                </form>
            </div>
        @endforeach
    </div>

    <div class="px-3 py-3 {{ $isGraduate ? 'bg-violet-50/50' : 'bg-[#FAF0E6]/35' }}">
        <form method="POST" action="{{ route('faculty-admin.department-patterns.store') }}" class="flex flex-wrap items-end gap-2">
            @csrf
            <input type="hidden" name="department_id" value="{{ $dept->department_id }}">
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="education_level" value="{{ $level }}">
            <input type="hidden" name="view" value="{{ $viewFilter ?? 'all' }}">
            <div class="flex-1 min-w-[12rem]">
                <label class="block text-xs font-medium mb-1 {{ $isGraduate ? 'text-violet-950' : 'text-[#5C2E1F]' }}">
                    เพิ่มรหัสเงื่อนไข ({{ $levelLabel }})
                </label>
                <input type="text" name="pattern" required maxlength="100" placeholder="เช่น 319% หรือ %SC9% หรือ SC904491"
                    class="w-full border rounded-lg px-3 py-2 text-sm bg-white uppercase font-mono
                        {{ $isGraduate ? 'border-violet-300' : 'border-amber-300' }}"
                    value="{{ (int) old('department_id') === (int) $dept->department_id && old('education_level') === $level ? old('pattern') : '' }}">
            </div>
            <button type="submit" class="px-4 py-2 text-white rounded-lg text-sm font-medium
                {{ $isGraduate ? 'bg-violet-700 hover:bg-violet-800' : 'bg-green-700 hover:bg-green-800' }}">
                เพิ่ม
            </button>
        </form>
    </div>
</div>
