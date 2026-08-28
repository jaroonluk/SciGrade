@extends('layouts.scigrad')

@section('title', 'จัดการหลักสูตร — Super Admin')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('dashboard') }}" class="text-[#8B4513] hover:underline">Super Admin</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">จัดการหลักสูตร</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-[#5C2E1F]">จัดการหลักสูตร</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">หลักสูตรที่ใช้ในฟอร์มกรอกผลสอบ</p>
        </div>
        <a href="{{ route('faculty-admin.settings.programs.create') }}" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">เพิ่มหลักสูตร</a>
    </div>

    <div class="form-section rounded-xl p-5">
        <form method="GET" id="program-search-form" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[16rem]">
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ค้นหา</label>
                <input type="text" id="program-search-input" name="search" value="{{ $search }}"
                    placeholder="ชื่อหลักสูตร หรือชื่อสาขาวิชา" autocomplete="off"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            @if ($search !== '')
                <a href="{{ route('faculty-admin.settings.programs.index') }}"
                   class="px-5 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ล้าง</a>
            @endif
        </form>
        <p class="text-xs text-gray-500 mt-2">พิมพ์ชื่อหลักสูตรหรือชื่อสาขาวิชา ระบบกรองรายการให้ทันที</p>
    </div>

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <table class="w-full text-sm min-w-[800px]">
            <thead class="bg-amber-50">
                <tr>
                    <th class="px-3 py-2 text-left">รหัส</th>
                    <th class="px-3 py-2 text-left">ชื่อหลักสูตร</th>
                    <th class="px-3 py-2 text-center">สาขา</th>
                    <th class="px-3 py-2 text-center">ระดับ</th>
                    <th class="px-3 py-2 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($programs as $program)
                    <tr class="border-t border-amber-100">
                        <td class="px-3 py-2 font-medium">{{ $program->programid }}</td>
                        <td class="px-3 py-2">{{ $program->programname }}</td>
                        <td class="px-3 py-2">{{ $program->department?->department_name ?? '-' }}</td>
                        <td class="px-3 py-2 text-center">{{ $program->typestudyLabel() }}</td>
                        <td class="px-3 py-2 text-center">
                            @if (filled($program->programid))
                                <a href="{{ route('faculty-admin.settings.programs.edit', ['program' => $program->programid]) }}" class="text-[#8B4513] hover:underline text-xs mr-2">แก้ไข</a>
                                <form method="POST" action="{{ route('faculty-admin.settings.programs.destroy', ['program' => $program->programid]) }}" class="inline"
                                    onsubmit="return confirm('ลบหลักสูตรนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-xs">ลบ</button>
                                </form>
                            @else
                                <span class="text-xs text-red-600">ไม่มีรหัสหลักสูตร</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-8 text-center text-gray-500">
                        {{ $search !== '' ? 'ไม่พบหลักสูตรตามคำค้นหา' : 'ยังไม่มีหลักสูตร' }}
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $programs->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const form = document.getElementById('program-search-form');
    const input = document.getElementById('program-search-input');
    if (!form || !input) return;

    let timer = null;
    let lastSubmitted = input.value;

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (input.value === lastSubmitted) return;
            lastSubmitted = input.value;
            form.requestSubmit();
        }, 300);
    });
})();
</script>
@endpush
