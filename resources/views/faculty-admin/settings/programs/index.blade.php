@extends('layouts.scigrad')

@section('title', 'จัดการหลักสูตร — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">จัดการหลักสูตร</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-[#5C2E1F]">จัดการหลักสูตร (tblprogram_qa)</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">หลักสูตรที่ใช้ในฟอร์มกรอกผลสอบ</p>
        </div>
        <a href="{{ route('faculty-admin.settings.programs.create') }}" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">เพิ่มหลักสูตร</a>
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
                        <td class="px-3 py-2 text-center">{{ $program->department_id }}</td>
                        <td class="px-3 py-2 text-center">{{ $program->typestudy }}</td>
                        <td class="px-3 py-2 text-center">
                            <a href="{{ route('faculty-admin.settings.programs.edit', $program) }}" class="text-[#8B4513] hover:underline text-xs mr-2">แก้ไข</a>
                            <form method="POST" action="{{ route('faculty-admin.settings.programs.destroy', $program) }}" class="inline"
                                onsubmit="return confirm('ลบหลักสูตรนี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-xs">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-8 text-center text-gray-500">ยังไม่มีหลักสูตร</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $programs->links() }}</div>
</div>
@endsection
