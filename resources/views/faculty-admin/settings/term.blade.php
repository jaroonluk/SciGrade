@extends('layouts.scigrad')

@section('title', 'กำหนดภาคการศึกษา — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">อนุมัติระดับคณะ</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">กำหนดภาคการศึกษา</span>
@endsection

@section('content')
<div class="max-w-xl mx-auto">
    <h2 class="text-xl font-bold text-[#5C2E1F] mb-2">กำหนดภาคการศึกษาปัจจุบัน</h2>
    <p class="text-sm text-[#7A4A3A]/80 mb-6">
        ค่านี้ใช้เป็นค่าเริ่มต้นในหน้า dashboard และหน้าอื่นๆ ของระบบรายงานผลการสอบ
    </p>

    <div class="form-section rounded-xl p-5">
        <form method="POST" action="{{ route('faculty-admin.settings.term.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                <select name="term" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    @php $currentTerm = old('term', $termSetting?->term ?? $defaults['term']); @endphp
                    <option value="1" @selected((int) $currentTerm === 1)>ภาคต้น</option>
                    <option value="2" @selected((int) $currentTerm === 2)>ภาคปลาย</option>
                    <option value="3" @selected((int) $currentTerm === 3)>ภาคการศึกษาพิเศษ</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                <select name="year" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    @php $currentYear = (int) old('year', $termSetting?->year ?? $defaults['year']); @endphp
                    @for ($y = 2565; $y <= 2575; $y++)
                        <option value="{{ $y }}" @selected($currentYear === $y)>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <button type="submit" class="px-5 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-semibold hover:bg-[#6B3410]">บันทึก</button>
        </form>
    </div>
</div>
@endsection
