@extends('layouts.scigrad')

@section('title', ($program->exists ? 'แก้ไข' : 'เพิ่ม').'หลักสูตร — Admin กลาง')

@section('content')
<div class="max-w-xl mx-auto">
    <h2 class="text-xl font-bold text-[#5C2E1F] mb-6">{{ $program->exists ? 'แก้ไขหลักสูตร' : 'เพิ่มหลักสูตร' }}</h2>

    <div class="form-section rounded-xl p-5">
        <form method="POST"
            action="{{ $program->exists ? route('faculty-admin.settings.programs.update', $program) : route('faculty-admin.settings.programs.store') }}"
            class="space-y-4">
            @csrf
            @if ($program->exists) @method('PUT') @endif

            <div>
                <label class="block text-sm font-medium mb-1">รหัสหลักสูตร (programid)</label>
                <input type="text" name="programid" value="{{ old('programid', $program->programid) }}"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white"
                    {{ $program->exists ? 'readonly' : '' }} required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">ชื่อหลักสูตร</label>
                <input type="text" name="programname" value="{{ old('programname', $program->programname) }}"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">สาขา (department_id)</label>
                <select name="department_id" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" required>
                    <option value="">— เลือก —</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->department_id }}" @selected(old('department_id', $program->department_id) == $dept->department_id)>
                            {{ $dept->department_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">ระดับการศึกษา (typestudy)</label>
                <select name="typestudy" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" required>
                    @foreach (['3' => 'ปริญญาตรี', '5' => 'ปริญญาโท', '7' => 'ปริญญาเอก'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('typestudy', $program->typestudy) == $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <input type="hidden" name="departmentid" value="{{ old('departmentid', $program->departmentid ?: '0') }}">
            <input type="hidden" name="depart_id" value="{{ old('depart_id', $program->depart_id ?: '0') }}">

            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-semibold">บันทึก</button>
                <a href="{{ route('faculty-admin.settings.programs.index') }}" class="px-5 py-2 border border-amber-300 rounded-lg text-sm">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
@endsection
