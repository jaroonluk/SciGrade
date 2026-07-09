@extends('layouts.scigrad')

@section('title', 'ผู้มีสิทธิใช้งาน — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('faculty-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">ผู้มีสิทธิใช้งาน</span>
@endsection

@push('styles')
<style>
    #user-suggestions {
        max-height: 14rem;
        overflow-y: auto;
        box-shadow: 0 10px 28px rgba(92, 46, 31, 0.14);
    }
    #user-suggestions button {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.65rem 0.9rem;
        border-bottom: 1px solid #f5e6d8;
    }
    #user-suggestions button:last-child { border-bottom: 0; }
    #user-suggestions button:hover { background: #fdf6f0; }
</style>
@endpush

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
        <form method="POST" action="{{ route('faculty-admin.settings.privileges.store') }}" id="privilege-create-form" class="grid md:grid-cols-2 gap-4 items-end">
            @csrf
            <div class="relative">
                <label class="block text-sm font-medium mb-1">ผู้ใช้งาน</label>
                <input type="text" id="user-search" autocomplete="off"
                    placeholder="พิมพ์ชื่อ สกุล หรือรหัสประจำตัว"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                <input type="hidden" name="username" id="user-username" value="{{ old('username') }}" required>
                <div id="user-suggestions"
                    class="hidden absolute left-0 right-0 top-full mt-1 z-30 bg-white border border-amber-200 rounded-lg"></div>
                <p id="user-selected" class="text-xs text-gray-600 mt-1 {{ old('username') ? '' : 'hidden' }}"></p>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">ระดับสิทธิ์</label>
                <select name="level" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" required>
                    <option value="0" @selected(old('level') === '0')>เจ้าหน้าที่งานบริการ</option>
                    <option value="1" @selected(old('level', '1') === '1')>เจ้าหน้าที่สาขาวิชา</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="px-5 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-semibold">เพิ่ม</button>
            </div>
        </form>
        @if ($errors->any())
            <div class="mt-3 text-sm text-red-600">{{ $errors->first() }}</div>
        @endif
    </div>

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <table class="w-full text-sm min-w-[520px]">
            <thead class="bg-amber-50">
                <tr>
                    <th class="px-3 py-2 text-left">ชื่อ-สกุล</th>
                    <th class="px-3 py-2 text-center">ระดับ</th>
                    <th class="px-3 py-2 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($privileges as $privilege)
                    <tr class="border-t border-amber-100 align-top">
                        <td class="px-3 py-2">
                            <div class="font-medium text-[#5C2E1F]">{{ $privilege->user?->displayName() ?? '-' }}</div>
                            @if ($privilege->user?->userid)
                                <div class="text-xs text-gray-500">รหัส {{ $privilege->user->userid }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center text-xs">{{ $privilege->levelLabel() }}</td>
                        <td class="px-3 py-2 text-center">
                            <div class="flex flex-wrap justify-center gap-2">
                                <button type="button"
                                    class="px-3 py-1.5 border border-amber-300 rounded text-xs hover:bg-amber-50 btn-edit-privilege"
                                    data-action="{{ route('faculty-admin.settings.privileges.update', $privilege) }}"
                                    data-name="{{ $privilege->user?->displayName() ?? $privilege->username }}"
                                    data-level="{{ $privilege->level }}">
                                    แก้ไขสิทธิ
                                </button>
                                <form method="POST" action="{{ route('faculty-admin.settings.privileges.destroy', $privilege) }}"
                                    class="inline" onsubmit="return confirm('ลบสิทธิ์นี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-red-600 text-white rounded text-xs font-medium hover:bg-red-700">
                                        ลบสิทธิ
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-3 py-8 text-center text-gray-500">ยังไม่มีผู้ใช้งาน</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $privileges->links() }}</div>
</div>

<div id="edit-privilege-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden no-print">
    <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-xl mx-4">
        <h3 class="font-bold text-lg mb-2 text-[#5C2E1F]">แก้ไขสิทธิ</h3>
        <p id="edit-privilege-name" class="text-sm text-gray-600 mb-4"></p>
        <form id="edit-privilege-form" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">ระดับสิทธิ์</label>
                <select name="level" id="edit-privilege-level" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" required>
                    <option value="0">เจ้าหน้าที่งานบริการ</option>
                    <option value="1">เจ้าหน้าที่สาขาวิชา</option>
                </select>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" id="btn-cancel-edit-privilege" class="px-4 py-2 border rounded-lg text-sm">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium">บันทึก</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const searchInput = document.getElementById('user-search');
    const usernameInput = document.getElementById('user-username');
    const suggestions = document.getElementById('user-suggestions');
    const selectedLabel = document.getElementById('user-selected');
    const createForm = document.getElementById('privilege-create-form');
    let timer = null;

    const hideSuggestions = () => {
        suggestions.classList.add('hidden');
        suggestions.innerHTML = '';
    };

    const selectUser = (user) => {
        usernameInput.value = user.username;
        searchInput.value = user.display_name;
        selectedLabel.textContent = `รหัสประจำตัว: ${user.userid || user.username} (username: ${user.username})`;
        selectedLabel.classList.remove('hidden');
        hideSuggestions();
    };

    const renderSuggestions = (users) => {
        if (!users.length) {
            hideSuggestions();
            return;
        }
        suggestions.innerHTML = users.map((user) => `
            <button type="button" data-username="${user.username.replace(/"/g, '&quot;')}"
                data-userid="${String(user.userid || '').replace(/"/g, '&quot;')}"
                data-name="${user.display_name.replace(/"/g, '&quot;')}">
                <span class="font-medium text-[#5C2E1F]">${user.display_name}</span>
                <span class="text-xs text-gray-500 block">${user.userid ? 'รหัส ' + user.userid + ' · ' : ''}username: ${user.username}</span>
            </button>
        `).join('');
        suggestions.classList.remove('hidden');
        suggestions.querySelectorAll('button').forEach((btn) => {
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectUser({
                    username: btn.dataset.username,
                    userid: btn.dataset.userid,
                    display_name: btn.dataset.name,
                });
            });
        });
    };

    const searchUsers = async (q) => {
        if (q.length < 1) {
            hideSuggestions();
            return;
        }
        try {
            const res = await fetch(`{{ route('faculty-admin.settings.privileges.users.search') }}?q=${encodeURIComponent(q)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            renderSuggestions(await res.json());
        } catch {
            hideSuggestions();
        }
    };

    searchInput?.addEventListener('input', () => {
        clearTimeout(timer);
        if (searchInput.value.trim() === '') {
            usernameInput.value = '';
            selectedLabel.classList.add('hidden');
        }
        timer = setTimeout(() => searchUsers(searchInput.value.trim()), 250);
    });

    searchInput?.addEventListener('focus', () => {
        if (searchInput.value.trim()) searchUsers(searchInput.value.trim());
    });

    searchInput?.addEventListener('blur', () => setTimeout(hideSuggestions, 150));

    document.addEventListener('click', (e) => {
        if (!suggestions.contains(e.target) && e.target !== searchInput) hideSuggestions();
    });

    createForm?.addEventListener('submit', (e) => {
        if (!usernameInput.value) {
            e.preventDefault();
            alert('กรุณาเลือกผู้ใช้งานจากรายการค้นหา');
        }
    });

    const editModal = document.getElementById('edit-privilege-modal');
    const editForm = document.getElementById('edit-privilege-form');
    const editName = document.getElementById('edit-privilege-name');
    const editLevel = document.getElementById('edit-privilege-level');

    document.querySelectorAll('.btn-edit-privilege').forEach((btn) => {
        btn.addEventListener('click', () => {
            editForm.action = btn.dataset.action;
            editName.textContent = btn.dataset.name;
            editLevel.value = btn.dataset.level;
            editModal.classList.remove('hidden');
        });
    });

    document.getElementById('btn-cancel-edit-privilege')?.addEventListener('click', () => {
        editModal.classList.add('hidden');
    });
})();
</script>
@endpush
