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
    .dept-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        background: #ecfdf8;
        border: 1px solid #99f6e4;
        color: #134e4a;
        font-size: 0.7rem;
        font-weight: 600;
        margin: 0.1rem;
    }
    .dept-check-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr));
        gap: 0.35rem 0.75rem;
        max-height: 14rem;
        overflow-y: auto;
        border: 1px solid #fcd34d;
        border-radius: 0.75rem;
        padding: 0.75rem;
        background: #fffdfb;
    }
    .dept-check-grid label {
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
        font-size: 0.8rem;
        color: #5C2E1F;
        line-height: 1.3;
    }
</style>
@endpush

@section('content')
@php
    $oldDeptIds = collect(old('department_ids', []))->map(fn ($id) => (int) $id)->all();
@endphp
<div class="max-w-6xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">ผู้มีสิทธิใช้งานระบบรายงานผลการสอบ</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            ระบบรายงานผลการสอบ (system_id = 11) —
            <span class="font-medium">0</span> = เจ้าหน้าที่งานบริการ,
            <span class="font-medium">1</span> = เจ้าหน้าที่สาขาวิชา (เลือกสาขาที่ดูแลได้หลายสาขา)
            @if ($canAssignSuper ?? false)
                , <span class="font-medium">2</span> = Super Admin
            @else
                <span class="block mt-1 text-xs text-amber-800">ระดับ Super Admin กำหนดได้เฉพาะ Super Admin เท่านั้น</span>
            @endif
        </p>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <a href="{{ route('faculty-admin.settings.privileges.index', array_filter(['q' => $search ?: null])) }}"
            class="rounded-xl border p-4 text-center transition {{ ($levelFilter ?? 'all') === 'all' ? 'border-[#8B4513] bg-[#fdf6f0] ring-1 ring-[#8B4513]/30' : 'border-amber-200 bg-white hover:border-[#C4725C]' }}">
            <p class="text-xs text-[#7A4A3A]/80">ทั้งหมด</p>
            <p class="text-2xl font-bold text-[#5C2E1F] mt-1">{{ $summary['total'] ?? 0 }}</p>
        </a>
        <a href="{{ route('faculty-admin.settings.privileges.index', array_filter(['level' => '0', 'q' => $search ?: null])) }}"
            class="rounded-xl border p-4 text-center transition {{ ($levelFilter ?? 'all') === '0' ? 'border-slate-400 bg-slate-50 ring-1 ring-slate-300' : 'border-slate-200 bg-slate-50/70 hover:border-slate-400' }}">
            <p class="text-xs text-slate-600">เจ้าหน้าที่งานบริการ</p>
            <p class="text-2xl font-bold text-slate-700 mt-1">{{ $summary[0] ?? 0 }}</p>
        </a>
        <a href="{{ route('faculty-admin.settings.privileges.index', array_filter(['level' => '1', 'q' => $search ?: null])) }}"
            class="rounded-xl border p-4 text-center transition {{ ($levelFilter ?? 'all') === '1' ? 'border-sky-400 bg-sky-50 ring-1 ring-sky-300' : 'border-sky-200 bg-sky-50/70 hover:border-sky-400' }}">
            <p class="text-xs text-sky-800">เจ้าหน้าที่สาขาวิชา</p>
            <p class="text-2xl font-bold text-sky-800 mt-1">{{ $summary[1] ?? 0 }}</p>
        </a>
        <a href="{{ route('faculty-admin.settings.privileges.index', array_filter(['level' => '2', 'q' => $search ?: null])) }}"
            class="rounded-xl border p-4 text-center transition {{ ($levelFilter ?? 'all') === '2' ? 'border-violet-400 bg-violet-50 ring-1 ring-violet-300' : 'border-violet-200 bg-violet-50/70 hover:border-violet-400' }}">
            <p class="text-xs text-violet-800">Super Admin</p>
            <p class="text-2xl font-bold text-violet-800 mt-1">{{ $summary[2] ?? 0 }}</p>
        </a>
    </div>

    <div class="form-section rounded-xl p-5">
        <form method="GET" action="{{ route('faculty-admin.settings.privileges.index') }}" class="flex flex-wrap items-end gap-3" id="privilege-filter-form">
            <div class="flex-1 min-w-[16rem]">
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ค้นหารายชื่อที่มีสิทธิ์แล้ว</label>
                <input type="text" name="q" id="privilege-list-search" value="{{ $search ?? '' }}"
                    placeholder="ชื่อ สกุล รหัสประจำตัว หรือ username"
                    autocomplete="off"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ระดับสิทธิ์</label>
                <select name="level" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[12rem]"
                    onchange="this.form.submit()">
                    <option value="all" @selected(($levelFilter ?? 'all') === 'all')>ทั้งหมด</option>
                    <option value="0" @selected(($levelFilter ?? 'all') === '0')>เจ้าหน้าที่งานบริการ</option>
                    <option value="1" @selected(($levelFilter ?? 'all') === '1')>เจ้าหน้าที่สาขาวิชา</option>
                    <option value="2" @selected(($levelFilter ?? 'all') === '2')>Super Admin</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">ค้นหา</button>
            @if (($search ?? '') !== '' || ($levelFilter ?? 'all') !== 'all')
                <a href="{{ route('faculty-admin.settings.privileges.index') }}"
                    class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ล้าง</a>
            @endif
        </form>
        <p class="text-xs text-gray-500 mt-2">
            คลิกการ์ดสรุปด้านบน หรือเลือกระดับสิทธิ์ เพื่อดูรายชื่อแล้วกด “ลบสิทธิ” ได้ทันที
            @if (($search ?? '') !== '' || ($levelFilter ?? 'all') !== 'all')
                — แสดง {{ number_format($privileges->total()) }} รายการตามเงื่อนไข
            @endif
        </p>
    </div>

    <div class="form-section rounded-xl p-5">
        <h3 class="font-semibold text-[#5C2E1F] mb-3">เพิ่มผู้ใช้งาน</h3>
        <form method="POST" action="{{ route('faculty-admin.settings.privileges.store') }}" id="privilege-create-form" class="space-y-4">
            @csrf
            <div class="grid md:grid-cols-2 gap-4">
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
                    <select name="level" id="create-level" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" required>
                        <option value="0" @selected(old('level') === '0')>เจ้าหน้าที่งานบริการ</option>
                        <option value="1" @selected(old('level', '1') === '1')>เจ้าหน้าที่สาขาวิชา</option>
                        @if ($canAssignSuper ?? false)
                            <option value="2" @selected(old('level') === '2')>Super Admin</option>
                        @endif
                    </select>
                </div>
            </div>

            <div id="create-dept-box" class="{{ (string) old('level', '1') === '1' ? '' : 'hidden' }}">
                <label class="block text-sm font-medium mb-1">สาขาวิชาที่ดูแลได้ *</label>
                <p class="text-xs text-[#7A4A3A]/75 mb-2">
                    เลือกได้มากกว่า 1 สาขา — เมื่อเข้าใช้งาน Admin สาขา จะเลือกดู/พิมพ์/ตรวจสอบได้เฉพาะสาขาที่กำหนด
                </p>
                <div class="dept-check-grid">
                    @foreach ($departments as $dept)
                        <label>
                            <input type="checkbox" name="department_ids[]" value="{{ $dept->department_id }}"
                                class="create-dept-check mt-0.5 rounded border-amber-300 text-[#8B4513] focus:ring-[#8B4513]"
                                @checked(in_array((int) $dept->department_id, $oldDeptIds, true))>
                            <span>{{ $dept->department_name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="px-5 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-semibold">เพิ่ม</button>
        </form>
        @if ($errors->any())
            <div class="mt-3 text-sm text-red-600">{{ $errors->first() }}</div>
        @endif
    </div>

    <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
        <div class="px-4 py-3 bg-amber-50 border-b border-amber-200 text-sm text-[#5C2E1F] flex flex-wrap items-center justify-between gap-2">
            <span>
                รายชื่อผู้มีสิทธิ์
                @if (($levelFilter ?? 'all') === '0') — เจ้าหน้าที่งานบริการ
                @elseif (($levelFilter ?? 'all') === '1') — เจ้าหน้าที่สาขาวิชา
                @elseif (($levelFilter ?? 'all') === '2') — Super Admin
                @endif
            </span>
            <span class="text-xs text-gray-600">{{ number_format($privileges->total()) }} คน</span>
        </div>
        <table class="w-full text-sm min-w-[720px]">
            <thead class="bg-amber-50">
                <tr>
                    <th class="px-3 py-2 text-left">ชื่อ-สกุล</th>
                    <th class="px-3 py-2 text-center">ระดับ</th>
                    <th class="px-3 py-2 text-left">สาขาที่ดูแล</th>
                    <th class="px-3 py-2 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($privileges as $privilege)
                    @php
                        $assigned = $privilege->assignedDepartments ?? collect();
                        $assignedIds = $assigned->pluck('department_id')->map(fn ($id) => (int) $id)->values()->all();
                    @endphp
                    <tr class="border-t border-amber-100 align-top">
                        <td class="px-3 py-2">
                            <div class="font-medium text-[#5C2E1F]">{{ $privilege->user?->displayName() ?? '-' }}</div>
                            @if ($privilege->user?->userid)
                                <div class="text-xs text-gray-500">รหัส {{ $privilege->user->userid }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center text-xs">{{ $privilege->levelLabel() }}</td>
                        <td class="px-3 py-2">
                            @if ((int) $privilege->level === \App\Models\TblPrivilege::LEVEL_DEPT)
                                @forelse ($assigned as $dept)
                                    <span class="dept-chip">{{ $dept->department_name }}</span>
                                @empty
                                    <span class="text-xs text-amber-800">ยังไม่กำหนด — ใช้สาขาตามบัญชีผู้ใช้</span>
                                @endforelse
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            @php
                                $isSuperRow = (int) $privilege->level === \App\Models\TblPrivilege::LEVEL_SUPER;
                                $canManageRow = ! $isSuperRow || ($canAssignSuper ?? false);
                            @endphp
                            @if ($canManageRow)
                                <div class="flex flex-wrap justify-center gap-2">
                                    <button type="button"
                                        class="px-3 py-1.5 border border-amber-300 rounded text-xs hover:bg-amber-50 btn-edit-privilege"
                                        data-action="{{ route('faculty-admin.settings.privileges.update', $privilege) }}"
                                        data-name="{{ $privilege->user?->displayName() ?? $privilege->username }}"
                                        data-level="{{ $privilege->level }}"
                                        data-departments="{{ implode(',', $assignedIds) }}">
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
                            @else
                                <span class="text-xs text-gray-400">เฉพาะ Super Admin</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-8 text-center text-gray-500">
                        @if (($search ?? '') !== '' || ($levelFilter ?? 'all') !== 'all')
                            ไม่พบผู้มีสิทธิ์ตามเงื่อนไขที่ค้นหา
                        @else
                            ยังไม่มีผู้ใช้งาน
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $privileges->links() }}</div>
</div>

<div id="edit-privilege-modal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden no-print">
    <div class="bg-white rounded-xl p-6 w-full max-w-2xl shadow-xl mx-4 max-h-[90vh] overflow-y-auto">
        <h3 class="font-bold text-lg mb-2 text-[#5C2E1F]">แก้ไขสิทธิ</h3>
        <p id="edit-privilege-name" class="text-sm text-gray-600 mb-4"></p>
        <form id="edit-privilege-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium mb-1">ระดับสิทธิ์</label>
                <select name="level" id="edit-privilege-level" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white" required>
                    <option value="0">เจ้าหน้าที่งานบริการ</option>
                    <option value="1">เจ้าหน้าที่สาขาวิชา</option>
                    @if ($canAssignSuper ?? false)
                        <option value="2">Super Admin</option>
                    @endif
                </select>
            </div>
            <div id="edit-dept-box" class="hidden">
                <label class="block text-sm font-medium mb-1">สาขาวิชาที่ดูแลได้ *</label>
                <p class="text-xs text-[#7A4A3A]/75 mb-2">เลือกได้มากกว่า 1 สาขา</p>
                <div class="dept-check-grid">
                    @foreach ($departments as $dept)
                        <label>
                            <input type="checkbox" name="department_ids[]" value="{{ $dept->department_id }}"
                                class="edit-dept-check mt-0.5 rounded border-amber-300 text-[#8B4513] focus:ring-[#8B4513]">
                            <span>{{ $dept->department_name }}</span>
                        </label>
                    @endforeach
                </div>
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
    const filterForm = document.getElementById('privilege-filter-form');
    const listSearch = document.getElementById('privilege-list-search');
    if (filterForm && listSearch) {
        let filterTimer = null;
        let lastSubmitted = listSearch.value;
        listSearch.addEventListener('input', () => {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => {
                if (listSearch.value === lastSubmitted) return;
                lastSubmitted = listSearch.value;
                filterForm.requestSubmit();
            }, 350);
        });
    }

    const searchInput = document.getElementById('user-search');
    const usernameInput = document.getElementById('user-username');
    const suggestions = document.getElementById('user-suggestions');
    const selectedLabel = document.getElementById('user-selected');
    const createForm = document.getElementById('privilege-create-form');
    const createLevel = document.getElementById('create-level');
    const createDeptBox = document.getElementById('create-dept-box');
    let timer = null;

    const toggleCreateDepts = () => {
        const show = String(createLevel?.value) === '1';
        createDeptBox?.classList.toggle('hidden', !show);
        if (!show) {
            createDeptBox?.querySelectorAll('.create-dept-check').forEach((el) => { el.checked = false; });
        }
    };
    createLevel?.addEventListener('change', toggleCreateDepts);
    toggleCreateDepts();

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

        // ติ๊กสาขาตามบัญชีผู้ใช้เป็นค่าเริ่มต้น (ถ้ายังไม่เลือก)
        if (String(createLevel?.value) === '1' && user.department_id) {
            const anyChecked = [...(createDeptBox?.querySelectorAll('.create-dept-check') || [])].some((el) => el.checked);
            if (!anyChecked) {
                const box = createDeptBox?.querySelector(`.create-dept-check[value="${user.department_id}"]`);
                if (box) box.checked = true;
            }
        }
    };

    const renderSuggestions = (users) => {
        if (!users.length) {
            hideSuggestions();
            return;
        }
        suggestions.innerHTML = users.map((user) => `
            <button type="button" data-username="${user.username.replace(/"/g, '&quot;')}"
                data-userid="${String(user.userid || '').replace(/"/g, '&quot;')}"
                data-name="${user.display_name.replace(/"/g, '&quot;')}"
                data-department-id="${String(user.department_id || 0)}">
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
                    department_id: Number(btn.dataset.departmentId || 0),
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
            return;
        }
        if (String(createLevel?.value) === '1') {
            const checked = createDeptBox?.querySelectorAll('.create-dept-check:checked') || [];
            if (!checked.length) {
                e.preventDefault();
                alert('กรุณาเลือกสาขาวิชาอย่างน้อย 1 สาขา');
            }
        }
    });

    const editModal = document.getElementById('edit-privilege-modal');
    const editForm = document.getElementById('edit-privilege-form');
    const editName = document.getElementById('edit-privilege-name');
    const editLevel = document.getElementById('edit-privilege-level');
    const editDeptBox = document.getElementById('edit-dept-box');

    const toggleEditDepts = () => {
        const show = String(editLevel?.value) === '1';
        editDeptBox?.classList.toggle('hidden', !show);
    };
    editLevel?.addEventListener('change', toggleEditDepts);

    document.querySelectorAll('.btn-edit-privilege').forEach((btn) => {
        btn.addEventListener('click', () => {
            editForm.action = btn.dataset.action;
            editName.textContent = btn.dataset.name;
            editLevel.value = btn.dataset.level;
            const ids = String(btn.dataset.departments || '')
                .split(',')
                .map((v) => v.trim())
                .filter(Boolean);
            editDeptBox?.querySelectorAll('.edit-dept-check').forEach((el) => {
                el.checked = ids.includes(String(el.value));
            });
            toggleEditDepts();
            editModal.classList.remove('hidden');
        });
    });

    editForm?.addEventListener('submit', (e) => {
        if (String(editLevel?.value) === '1') {
            const checked = editDeptBox?.querySelectorAll('.edit-dept-check:checked') || [];
            if (!checked.length) {
                e.preventDefault();
                alert('กรุณาเลือกสาขาวิชาอย่างน้อย 1 สาขา');
            }
        } else {
            editDeptBox?.querySelectorAll('.edit-dept-check').forEach((el) => { el.checked = false; });
        }
    });

    document.getElementById('btn-cancel-edit-privilege')?.addEventListener('click', () => {
        editModal.classList.add('hidden');
    });
})();
</script>
@endpush
