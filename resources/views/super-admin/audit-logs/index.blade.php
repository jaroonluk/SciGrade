@extends('layouts.scigrad')

@section('title', 'บันทึกการใช้งานระบบ — Super Admin')

@section('subnav')
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">บันทึกการใช้งานระบบ</span>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-bold text-[#5C2E1F]">บันทึกการใช้งานระบบ</h2>
        <p class="text-sm text-[#7A4A3A]/80 mt-1">
            ประวัติการเข้าใช้งานและการเปลี่ยนแปลงสำคัญในระบบ — เฉพาะ Super Admin
        </p>
    </div>

    @if ($error)
        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ $error }}
        </div>
    @endif

    <form method="GET" action="{{ route('super-admin.audit-logs.index') }}" class="form-section rounded-xl p-4 space-y-4">
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-medium text-[#5C2E1F] mb-1">ประเภทเหตุการณ์</label>
                <select name="event" class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">ทั้งหมด</option>
                    @foreach ($eventOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['event'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-[#5C2E1F] mb-1">ผู้ใช้ / Impersonator</label>
                <input type="text" name="actor" value="{{ $filters['actor'] ?? '' }}"
                    placeholder="username"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#5C2E1F] mb-1">ตั้งแต่</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#5C2E1F] mb-1">ถึง</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#5C2E1F] mb-1">ค้นหาเพิ่มเติม</label>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                    placeholder="subject, path, IP"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="submit" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
                ค้นหา
            </button>
            <a href="{{ route('super-admin.audit-logs.index') }}"
                class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">
                ล้างตัวกรอง
            </a>
        </div>
    </form>

    @if (! $error)
        <div class="bg-white border border-amber-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#fdf6f0] text-[#5C2E1F]">
                        <tr>
                            <th class="text-left px-3 py-2.5 font-semibold whitespace-nowrap">เวลา</th>
                            <th class="text-left px-3 py-2.5 font-semibold whitespace-nowrap">เหตุการณ์</th>
                            <th class="text-left px-3 py-2.5 font-semibold whitespace-nowrap">ผู้กระทำ</th>
                            <th class="text-left px-3 py-2.5 font-semibold whitespace-nowrap">เป้าหมาย</th>
                            <th class="text-left px-3 py-2.5 font-semibold whitespace-nowrap">รายละเอียด</th>
                            <th class="text-left px-3 py-2.5 font-semibold whitespace-nowrap">IP / Path</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-100">
                        @forelse ($logs as $log)
                            <tr class="align-top hover:bg-[#fffbf7]">
                                <td class="px-3 py-2.5 whitespace-nowrap text-xs text-gray-600">
                                    {{ $log->created_at?->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <p class="font-medium text-[#5C2E1F]">{{ \App\Support\AuditLogEvent::label($log->event) }}</p>
                                    <p class="text-[11px] text-gray-500 font-mono">{{ $log->event }}</p>
                                </td>
                                <td class="px-3 py-2.5">
                                    <p class="font-medium text-[#5C2E1F]">
                                        {{ $log->actor?->displayName() ?: ($log->actor_username ?: '—') }}
                                    </p>
                                    @if ($log->actor_username)
                                        <p class="text-[11px] text-gray-500">{{ $log->actor_username }}</p>
                                    @endif
                                    @if ($log->actor_role)
                                        <p class="text-[11px] text-amber-800 mt-0.5">
                                            {{ \App\Support\SciGradeRole::label($log->actor_role) }}
                                        </p>
                                    @endif
                                    @if ($log->impersonator_username)
                                        <p class="text-[11px] text-sky-800 mt-1">
                                            โดย Super Admin:
                                            {{ $log->impersonator?->displayName() ?: $log->impersonator_username }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-xs">
                                    @if ($log->subject_type || $log->subject_id)
                                        <p class="font-mono text-gray-700">{{ $log->subject_type }}</p>
                                        <p class="font-mono text-gray-500">{{ $log->subject_id }}</p>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-xs max-w-xs">
                                    @if (is_array($log->metadata) && $log->metadata !== [])
                                        <pre class="whitespace-pre-wrap break-all text-[11px] text-gray-600 bg-gray-50 rounded-lg px-2 py-1.5 border border-gray-100">{{ json_encode($log->metadata, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-xs text-gray-600">
                                    <p>{{ $log->ip_address ?: '—' }}</p>
                                    @if ($log->request_method || $log->request_path)
                                        <p class="font-mono text-[11px] text-gray-500 mt-1">
                                            {{ $log->request_method }} {{ $log->request_path }}
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-10 text-center text-sm text-gray-500">
                                    ไม่พบบันทึกตามเงื่อนไขที่เลือก
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (method_exists($logs, 'links'))
            <div>{{ $logs->links() }}</div>
        @endif
    @endif
</div>
@endsection
