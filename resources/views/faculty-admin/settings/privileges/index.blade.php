@extends('layouts.scigrad')

@section('title', 'ผู้มีสิทธิใช้งาน — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">ผู้มีสิทธิใช้งาน</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">ผู้มีสิทธิใช้งานระบบรายงานผลการสอบ</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            ระบบรายงานผลการสอบ (system_id = 11) —
            <span class="font-medium">0</span> = เจ้าหน้าที่งานบริการ,
            <span class="font-medium">1</span> = เจ้าหน้าที่สาขาวิชา
        </p>
    </div>

    <div class="form-section rounded-xl p-5">
        <h3 class="font-semibold text-[#5C2E1F] mb-3">เพิ่มผู้ใช้งาน</h3>
        <form method="POST" action="{{ route('faculty-admin.settings.privileges.store') }}" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1">รหัสผู้ใช้ (username)</label>
                <input type="text" name="username" value="{{ old('username') }}" maxlength="10"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">ระดับสิทธิ์</label>
                <select name="level" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" required>
                    <option value="0" @selected(old('level') === '0')>เจ้าหน้าที่งานบริการ</option>
                    <option value="1" @selected(old('level', '1') === '1')>เจ้าหน้าที่สาขาวิชา</option>
                </select>
            </div>
            <div class="flex flex-col gap-2">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="can_print_report" value="1" class="accent-amber-700" @checked(old('can_print_report'))>
                    พิมพ์ใบส่งเกรดได้
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="can_view_all_instructors" value="1" class="accent-amber-700" @checked(old('can_view_all_instructors'))>
                    เห็นรายงานอาจารย์ทุกคนในสาขา
                </label>
            </div>
            <div>
                <button type="submit" class="px-5 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-semibold">เพิ่ม</button>
            </div>
        </form>
        @if ($errors->any())
            <div class="mt-3 text-sm text-red-600">{{ $errors->first() }}</div>
        @endif
    </div>

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <table class="w-full text-sm min-w-[900px]">
            <thead class="bg-amber-50">
                <tr>
                    <th class="px-3 py-2 text-left">username</th>
                    <th class="px-3 py-2 text-left">ชื่อ-สกุล</th>
                    <th class="px-3 py-2 text-center">ระดับ</th>
                    <th class="px-3 py-2 text-center">พิมพ์ใบส่งเกรด</th>
                    <th class="px-3 py-2 text-center">เห็นรายงานทุกคน</th>
                    <th class="px-3 py-2 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($privileges as $privilege)
                    <tr class="border-t border-amber-100 align-top">
                        <td class="px-3 py-2 font-medium">{{ $privilege->username }}</td>
                        <td class="px-3 py-2">{{ $privilege->user?->displayName() ?? '-' }}</td>
                        <td class="px-3 py-2 text-center text-xs">{{ $privilege->levelLabel() }}</td>
                        <td class="px-3 py-2 text-center">
                            <form method="POST" action="{{ route('faculty-admin.settings.privileges.update', $privilege) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="username" value="{{ $privilege->username }}">
                                <input type="hidden" name="level" value="{{ $privilege->level }}">
                                <input type="hidden" name="can_view_all_instructors" value="{{ $privilege->can_view_all_instructors ? '1' : '0' }}">
                                <input type="checkbox" name="can_print_report" value="1" class="accent-amber-700"
                                    @checked($privilege->can_print_report) onchange="this.form.submit()">
                            </form>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <form method="POST" action="{{ route('faculty-admin.settings.privileges.update', $privilege) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="username" value="{{ $privilege->username }}">
                                <input type="hidden" name="level" value="{{ $privilege->level }}">
                                <input type="hidden" name="can_print_report" value="{{ $privilege->can_print_report ? '1' : '0' }}">
                                <input type="checkbox" name="can_view_all_instructors" value="1" class="accent-amber-700"
                                    @checked($privilege->can_view_all_instructors) onchange="this.form.submit()">
                            </form>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <form method="POST" action="{{ route('faculty-admin.settings.privileges.update', $privilege) }}" class="mb-2">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="username" value="{{ $privilege->username }}">
                                <input type="hidden" name="can_print_report" value="{{ $privilege->can_print_report ? '1' : '0' }}">
                                <input type="hidden" name="can_view_all_instructors" value="{{ $privilege->can_view_all_instructors ? '1' : '0' }}">
                                <input type="hidden" name="can_view_all_instructors" value="{{ $privilege->can_view_all_instructors ? '1' : '0' }}">
                                <select name="level" class="border border-amber-300 rounded text-xs px-2 py-1" onchange="this.form.submit()">
                                    <option value="0" @selected($privilege->level === 0)>งานบริการ</option>
                                    <option value="1" @selected($privilege->level === 1)>สาขาวิชา</option>
                                </select>
                            </form>
                            <form method="POST" action="{{ route('faculty-admin.settings.privileges.destroy', $privilege) }}"
                                onsubmit="return confirm('ลบสิทธิ์นี้?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-xs">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-8 text-center text-gray-500">ยังไม่มีผู้ใช้งาน</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $privileges->links() }}</div>
</div>
@endsection
