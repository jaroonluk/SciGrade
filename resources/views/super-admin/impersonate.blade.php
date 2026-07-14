@extends('layouts.scigrad')

@section('title', 'เข้าใช้งานแทน — Super Admin')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">เข้าใช้งานแทนบุคลากร</span>
@endsection

@push('styles')
<style>
    #impersonate-suggestions {
        max-height: 16rem;
        overflow-y: auto;
        box-shadow: 0 10px 28px rgba(92, 46, 31, 0.14);
    }
    #impersonate-suggestions button {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.65rem 0.9rem;
        border-bottom: 1px solid #f5e6d8;
    }
    #impersonate-suggestions button:last-child { border-bottom: 0; }
    #impersonate-suggestions button:hover { background: #fdf6f0; }
    .role-card {
        border: 2px solid #E8C4B8;
        border-radius: 0.75rem;
        padding: 1rem;
        background: #fff;
        cursor: pointer;
        transition: all .15s;
    }
    .role-card:hover { border-color: #C4725C; }
    .role-card.is-selected {
        border-color: #8B4513;
        background: #fdf6f0;
        box-shadow: 0 0 0 1px #8B4513;
    }
</style>
@endpush

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">เข้าใช้งานระบบแทนบุคลากร</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            ค้นหาบุคลากรในคณะ แล้วเลือกบทบาทที่ต้องการเข้าแทนได้ทุกระดับ:
            อาจารย์, Admin สาขา, หรือ Admin กลาง
        </p>
    </div>

    <form method="POST" action="{{ route('super-admin.impersonate.start') }}" id="impersonate-form" class="form-section rounded-xl p-6 space-y-5">
        @csrf

        <div class="relative">
            <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ค้นหาบุคลากร</label>
            <input type="text" id="impersonate-search" autocomplete="off"
                placeholder="พิมพ์ชื่อ สกุล อีเมล รหัสประจำตัว หรือ username"
                class="w-full border border-amber-300 rounded-lg px-3 py-2.5 text-sm bg-white">
            <input type="hidden" name="username" id="impersonate-username" value="{{ old('username') }}" required>
            <div id="impersonate-suggestions"
                class="hidden absolute left-0 right-0 top-full mt-1 z-30 bg-white border border-amber-200 rounded-lg"></div>
            <p id="impersonate-selected" class="text-xs text-gray-600 mt-2 {{ old('username') ? '' : 'hidden' }}"></p>
        </div>

        <div>
            <label class="block text-sm font-medium text-[#5C2E1F] mb-2">เข้าแทนในบทบาท</label>
            <input type="hidden" name="as_role" id="impersonate-as-role" value="{{ old('as_role', 'instructor') }}" required>
            <div class="grid sm:grid-cols-3 gap-3">
                @foreach ($roles as $value => $label)
                    <button type="button" class="role-card text-left {{ old('as_role', 'instructor') === $value ? 'is-selected' : '' }}"
                        data-role="{{ $value }}">
                        <p class="font-semibold text-[#5C2E1F]">{{ $label }}</p>
                        <p class="text-xs text-[#7A4A3A]/80 mt-1">
                            @if ($value === 'instructor')
                                กรอก/แก้ไขรายงานผลสอบ
                            @elseif ($value === 'dept_admin')
                                ตรวจและอนุมัติระดับสาขา
                            @else
                                ตรวจและอนุมัติระดับคณะ
                            @endif
                        </p>
                    </button>
                @endforeach
            </div>
        </div>

        @if ($errors->any())
            <div class="text-sm text-red-600">{{ $errors->first() }}</div>
        @endif

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-semibold hover:bg-[#6B3410]">
                เข้าใช้งานแทน
            </button>
            <a href="{{ route('dashboard') }}" class="px-5 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-white">
                กลับหน้าหลัก
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const searchInput = document.getElementById('impersonate-search');
    const usernameInput = document.getElementById('impersonate-username');
    const suggestions = document.getElementById('impersonate-suggestions');
    const selectedLabel = document.getElementById('impersonate-selected');
    const form = document.getElementById('impersonate-form');
    const roleInput = document.getElementById('impersonate-as-role');
    let timer = null;

    function hideSuggestions() {
        suggestions.classList.add('hidden');
        suggestions.innerHTML = '';
    }

    function selectUser(user) {
        usernameInput.value = user.username;
        selectedLabel.textContent = user.display_name
            + (user.userid ? ' · รหัส ' + user.userid : '')
            + (user.email ? ' · ' + user.email : '');
        selectedLabel.classList.remove('hidden');
        searchInput.value = user.display_name;
        hideSuggestions();
    }

    searchInput.addEventListener('input', function () {
        usernameInput.value = '';
        selectedLabel.classList.add('hidden');
        const q = searchInput.value.trim();
        clearTimeout(timer);
        if (q.length < 2) {
            hideSuggestions();
            return;
        }
        timer = setTimeout(async () => {
            const res = await fetch(`{{ route('super-admin.impersonate.users.search') }}?q=` + encodeURIComponent(q), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const users = await res.json();
            if (!users.length) {
                suggestions.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">ไม่พบบุคลากร</div>';
                suggestions.classList.remove('hidden');
                return;
            }
            suggestions.innerHTML = users.map(u =>
                `<button type="button" data-username="${u.username}" data-name="${u.display_name}" data-email="${u.email || ''}" data-userid="${u.userid || ''}">
                    <span class="font-medium text-[#5C2E1F]">${u.display_name}</span>
                    <span class="block text-xs text-gray-500">${u.username}${u.userid ? ' · ' + u.userid : ''}${u.email ? ' · ' + u.email : ''}</span>
                </button>`
            ).join('');
            suggestions.classList.remove('hidden');
            suggestions.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', () => selectUser({
                    username: btn.dataset.username,
                    display_name: btn.dataset.name,
                    email: btn.dataset.email,
                    userid: btn.dataset.userid,
                }));
            });
        }, 250);
    });

    document.querySelectorAll('.role-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.role-card').forEach(c => c.classList.remove('is-selected'));
            card.classList.add('is-selected');
            roleInput.value = card.dataset.role;
        });
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#impersonate-form')) hideSuggestions();
    });

    form.addEventListener('submit', (e) => {
        if (!usernameInput.value) {
            e.preventDefault();
            alert('กรุณาเลือกบุคลากรจากรายการค้นหา');
        }
    });
})();
</script>
@endpush
