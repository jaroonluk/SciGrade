@extends('layouts.scigrad')

@section('title', 'แก้ไขรายวิชา REG')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.settings.reg-grade-manage.index', ['term' => $term, 'year' => $year, 'department_id' => $departmentId]) }}" class="text-[#8B4513] hover:underline">จัดการรายวิชา REG</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">แก้ไข</span>
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">แก้ไขรายวิชา REG</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">{{ $courseCode }} · กลุ่ม {{ $section }} · ภาค {{ $term }}/{{ $year }}</p>
    </div>

    <div class="form-section rounded-xl p-6 space-y-4">
        <form method="POST" action="{{ route('faculty-admin.settings.reg-grade-manage.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="COURSECODE" value="{{ $courseCode }}">
            <input type="hidden" name="SECTION" value="{{ $section }}">
            <input type="hidden" name="ACADYEAR" value="{{ $year }}">
            <input type="hidden" name="SEMESTER" value="{{ $term }}">
            <input type="hidden" name="department_id" value="{{ $departmentId }}">

            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ชื่อวิชา (ENG)</label>
                <input type="text" name="COURSENAMEENG" value="{{ old('COURSENAMEENG', $courseNameEng) }}" required
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">บันทึก</button>
                <a href="{{ route('faculty-admin.settings.reg-grade-manage.index', ['term' => $term, 'year' => $year, 'department_id' => $departmentId]) }}"
                    class="px-5 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">กลับ</a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <div class="px-4 py-3 bg-amber-50 border-b border-amber-200 text-sm text-[#5C2E1F]">ผู้สอนในรายวิชานี้</div>
        <table class="w-full text-sm">
            <thead class="bg-amber-50/60">
                <tr>
                    <th class="px-3 py-2 text-left">ชื่อ-สกุล</th>
                    <th class="px-3 py-2 text-left">อีเมล</th>
                    <th class="px-3 py-2 text-center">ลบผู้สอน</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="border-t border-amber-100">
                        <td class="px-3 py-2">{{ trim(($row->OFFICERNAME ?? '').' '.($row->OFFICERSURNAME ?? '')) ?: '-' }}</td>
                        <td class="px-3 py-2 text-xs text-gray-600">{{ $row->KKUMAIL ?: '-' }}</td>
                        <td class="px-3 py-2 text-center">
                            <form method="POST" action="{{ route('faculty-admin.settings.reg-grade-manage.destroy') }}"
                                onsubmit="return confirm('ลบผู้สอนคนนี้ออกจากรายวิชา?')">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="COURSECODE" value="{{ $courseCode }}">
                                <input type="hidden" name="SECTION" value="{{ $section }}">
                                <input type="hidden" name="ACADYEAR" value="{{ $year }}">
                                <input type="hidden" name="SEMESTER" value="{{ $term }}">
                                <input type="hidden" name="OFFICERID" value="{{ $row->OFFICERID }}">
                                <input type="hidden" name="department_id" value="{{ $departmentId }}">
                                <button type="submit" class="px-3 py-1.5 bg-red-600 text-white rounded text-xs hover:bg-red-700">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
