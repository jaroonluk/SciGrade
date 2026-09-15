@extends('layouts.scigrad')

@section('title', 'จัดกลุ่มรายวิชา (grad_report2) — Admin กลาง')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('dashboard') }}" class="text-[#8B4513] hover:underline">Admin กลาง</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">จัดกลุ่มรายวิชา</span>
@endsection

@push('styles')
<style>
    .guide-card {
        border: 1px solid #e8c4b8;
        border-radius: 1rem;
        background: linear-gradient(180deg, #fffdfb 0%, #faf0e6 100%);
    }
    .guide-step {
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
    }
    .guide-num {
        flex-shrink: 0;
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 9999px;
        background: #8B4513;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .group-card {
        border: 1px solid #e8c4b8;
        border-radius: 1rem;
        background: #fff;
        overflow: hidden;
        transition: box-shadow .15s ease, border-color .15s ease;
    }
    .group-card.is-focus {
        border-color: #8B4513;
        box-shadow: 0 0 0 2px rgba(139, 69, 19, 0.18);
    }
    .sheet-wrap {
        border: 1px solid #d4a090;
        border-radius: 0.9rem;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(92, 46, 31, 0.06);
    }
    .sheet-wrap.is-focus {
        border-color: #8B4513;
        box-shadow: 0 0 0 2px rgba(139, 69, 19, 0.16);
    }
    .sheet-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .sheet-table thead th {
        background: #FAF0E6;
        color: #5C2E1F;
        font-weight: 700;
        text-align: left;
        padding: 0.7rem 0.85rem;
        border-bottom: 1px solid #d4a090;
        border-right: 1px solid #e8c4b8;
        white-space: nowrap;
        font-size: 0.78rem;
        letter-spacing: 0.01em;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .sheet-table thead th:last-child { border-right: 0; }
    .sheet-table td {
        padding: 0.55rem 0.85rem;
        border-bottom: 1px solid #f0e0d0;
        border-right: 1px solid #f5e6d8;
        vertical-align: middle;
        color: #5C2E1F;
        background: #fff;
    }
    .sheet-table td:last-child { border-right: 0; }
    .sheet-table tbody tr.group-start td {
        border-top: 3px solid #8B4513;
        background: #fffdf9;
    }
    .sheet-table tbody tr.group-start:first-child td { border-top: 0; }
    .sheet-table tbody tr:hover td { background: #fffaf5; }
    .sheet-table tbody tr.group-start:hover td { background: #fdf6f0; }
    .sheet-table tbody tr.is-focus td {
        background: #fff4e8;
        box-shadow: inset 0 0 0 2px rgba(139, 69, 19, 0.22);
    }
    .sheet-inline-panel td {
        background: #fffbf7 !important;
        border-top: 1px dashed #e8c4b8;
        border-bottom: 1px solid #f0e0d0;
        padding: 0.85rem 1rem;
        vertical-align: top;
    }
    .sheet-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-weight: 700;
        color: #8B4513;
    }
    .sheet-key-badge {
        display: inline-flex;
        align-items: center;
        margin-left: 0.35rem;
        padding: 0.05rem 0.4rem;
        border-radius: 9999px;
        background: #8B4513;
        color: #fff;
        font-size: 0.62rem;
        font-weight: 700;
        vertical-align: middle;
    }
    .sheet-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    .sheet-icon-btn {
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        border: 1px solid transparent;
        background: #fff;
        cursor: pointer;
        transition: background .15s ease, color .15s ease, border-color .15s ease, transform .12s ease;
    }
    .sheet-icon-btn svg { width: 0.95rem; height: 0.95rem; }
    .sheet-icon-edit {
        color: #854d0e;
        border-color: #fde68a;
    }
    .sheet-icon-edit:hover {
        background: #a16207;
        border-color: #a16207;
        color: #fff;
        transform: translateY(-1px);
    }
    .sheet-icon-add {
        color: #0369a1;
        border-color: #bae6fd;
    }
    .sheet-icon-add:hover {
        background: #0284c7;
        border-color: #0284c7;
        color: #fff;
        transform: translateY(-1px);
    }
    .sheet-icon-delete {
        color: #b91c1c;
        border-color: #fecaca;
    }
    .sheet-icon-delete:hover {
        background: #dc2626;
        border-color: #dc2626;
        color: #fff;
        transform: translateY(-1px);
    }
    .sheet-icon-btn[data-tip] { position: relative; }
    .sheet-icon-btn[data-tip]::after {
        content: attr(data-tip);
        position: absolute;
        top: calc(100% + 0.35rem);
        left: 50%;
        transform: translateX(-50%) translateY(0.1rem);
        white-space: nowrap;
        background: #3f2a1d;
        color: #fff;
        font-size: 0.68rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 0.35rem;
        opacity: 0;
        pointer-events: none;
        transition: opacity .12s ease, transform .12s ease;
        z-index: 30;
    }
    .sheet-icon-btn[data-tip]:hover::after,
    .sheet-icon-btn[data-tip]:focus-visible::after {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    .sheet-panel {
        border-top: 1px dashed #e8c4b8;
        background: #fffbf7;
        padding: 0.85rem 1rem;
    }
    .sheet-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1.25rem;
        font-size: 0.75rem;
        color: #7A4A3A;
    }
    .sheet-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .sheet-source {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.15rem;
        max-width: 9.5rem;
    }
    .sheet-source-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.68rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }
    .sheet-source-badge svg {
        width: 0.75rem;
        height: 0.75rem;
    }
    .sheet-source-admin {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .sheet-source-instructor {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .sheet-source-user {
        font-size: 0.65rem;
        color: #7A4A3A;
        word-break: break-word;
        line-height: 1.25;
    }
    .sheet-source-date {
        font-size: 0.62rem;
        color: #9a6a58;
        line-height: 1.2;
    }
    .member-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.55rem;
        border-radius: 0.65rem;
        border: 1px solid #e8c4b8;
        background: #fffaf5;
        font-size: 0.8rem;
    }
    .member-chip.is-key {
        border-color: #8B4513;
        background: #FAF0E6;
        font-weight: 700;
        color: #5C2E1F;
    }
    .member-row.is-editing .member-view { display: none; }
    .member-row.is-editing .member-edit { display: flex !important; }
    .field-hint { font-size: 0.7rem; color: #7A4A3A; opacity: .85; margin-top: 0.25rem; }
    .example-box {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.75rem;
        background: #fff;
        border: 1px dashed #d4a090;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
    }
    .subject-suggest {
        max-height: 14rem;
        overflow-y: auto;
        box-shadow: 0 10px 28px rgba(92, 46, 31, 0.14);
        z-index: 40;
    }
    .subject-suggest button {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.65rem 0.9rem;
        border-bottom: 1px solid #f5e6d8;
    }
    .subject-suggest button:last-child { border-bottom: 0; }
    .subject-suggest button:hover { background: #fdf6f0; }
    .member-pick-box {
        min-height: 2.75rem;
        border: 1px solid #fcd34d;
        border-radius: 0.5rem;
        background: #fff;
        padding: 0.4rem 0.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        align-items: center;
    }
    .member-pick-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.2rem 0.45rem;
        border-radius: 9999px;
        background: #FAF0E6;
        border: 1px solid #e8c4b8;
        font-size: 0.75rem;
        font-weight: 600;
        color: #5C2E1F;
    }
    .member-pick-chip button {
        line-height: 1;
        color: #A0522D;
        font-size: 0.85rem;
    }
    .member-pick-input {
        flex: 1;
        min-width: 10rem;
        border: 0;
        outline: none;
        font-size: 0.875rem;
        text-transform: uppercase;
        background: transparent;
        padding: 0.25rem 0.15rem;
    }
    .paste-grid-head {
        display: grid;
        grid-template-columns: 8rem minmax(10rem, 1.2fr) minmax(12rem, 1.4fr);
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    .paste-grid-head span {
        font-size: 0.72rem;
        font-weight: 700;
        color: #5C2E1F;
        background: #FAF0E6;
        border: 1px solid #e8c4b8;
        border-radius: 0.5rem;
        padding: 0.45rem 0.6rem;
        text-align: center;
    }
    .paste-area {
        width: 100%;
        min-height: 9.5rem;
        border: 1.5px dashed #d4a090;
        border-radius: 0.75rem;
        background: #fff;
        padding: 0.75rem 0.9rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.8rem;
        line-height: 1.45;
        color: #5C2E1F;
        resize: vertical;
    }
    .paste-area:focus {
        outline: none;
        border-color: #8B4513;
        box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.12);
        background: #fffdfb;
    }
    .paste-preview {
        overflow-x: auto;
        border: 1px solid #e8c4b8;
        border-radius: 0.75rem;
        background: #fff;
    }
    .paste-preview table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.8rem;
    }
    .paste-preview th {
        background: #fdf6f0;
        color: #5C2E1F;
        text-align: left;
        padding: 0.55rem 0.7rem;
        border-bottom: 1px solid #e8c4b8;
        white-space: nowrap;
    }
    .paste-preview td {
        padding: 0.5rem 0.7rem;
        border-bottom: 1px solid #f5e6d8;
        vertical-align: top;
    }
    .paste-preview tr:last-child td { border-bottom: 0; }
    .paste-code-chip {
        display: inline-block;
        margin: 0.1rem 0.2rem 0.1rem 0;
        padding: 0.12rem 0.4rem;
        border-radius: 9999px;
        background: #FAF0E6;
        border: 1px solid #e8c4b8;
        font-size: 0.72rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
@php
    $focusGroup = strtoupper(trim((string) ($focusGroup ?? '')));
@endphp
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-[#5C2E1F]">จัดกลุ่มรายวิชา (grad_report2)</h2>
            <p class="text-sm text-[#7A4A3A]/80 mt-1">
                กำหนดว่ารหัสวิชาใดตัดเกรดร่วมกัน — ค้นหารหัส/ชื่อจากตาราง
                <code class="text-[#8B4513]">pdcourse</code> แล้วเลือกใส่กลุ่มได้ทันที
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('faculty-admin.grad-report2-groups.export', array_filter(['q' => $q !== '' ? $q : null])) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">
                <i data-lucide="download" class="w-4 h-4"></i>
                Export Excel
            </a>
            <div class="rounded-xl border border-[#E8C4B8] bg-[#FFFBF7] px-4 py-3 text-center min-w-[7rem]">
                <p class="text-[0.65rem] text-[#A0522D]/70">กลุ่ม</p>
                <p class="text-2xl font-bold text-[#8B4513] leading-none">{{ number_format($stats['groups'] ?? 0) }}</p>
            </div>
            <div class="rounded-xl border border-[#E8C4B8] bg-[#FFFBF7] px-4 py-3 text-center min-w-[7rem]">
                <p class="text-[0.65rem] text-[#A0522D]/70">รหัสวิชา</p>
                <p class="text-2xl font-bold text-[#8B4513] leading-none">{{ number_format($stats['members'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- คำแนะนำการกรอก --}}
    <details class="guide-card" open>
        <summary class="px-4 py-3 cursor-pointer select-none font-semibold text-[#5C2E1F] flex items-center gap-2">
            <i data-lucide="book-open" class="w-4 h-4"></i>
            วิธีจัดกลุ่มรายวิชาให้ถูกต้อง (อ่านก่อนกรอก)
        </summary>
        <div class="px-4 pb-4 space-y-4 text-sm text-[#5C2E1F]">
            <div class="grid md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div class="guide-step">
                        <span class="guide-num">1</span>
                        <div>
                            <p class="font-medium">พิมพ์ค้นหาจาก <code class="text-[#8B4513]">pdcourse</code></p>
                            <p class="text-[#7A4A3A]/80 mt-0.5">พิมพ์รหัสหรือชื่อวิชา แล้วเลือกจากรายการ — ระบบเติมชื่อวิชาให้อัตโนมัติ</p>
                        </div>
                    </div>
                    <div class="guide-step">
                        <span class="guide-num">2</span>
                        <div>
                            <p class="font-medium">เลือก <span class="text-[#8B4513]">รหัสกลุ่ม</span> 1 ตัว</p>
                            <p class="text-[#7A4A3A]/80 mt-0.5">ใช้รหัสหลักหรือรหัสเดิมเป็นตัวแทนกลุ่ม (เก็บเป็น <code>subject_code2</code>)</p>
                        </div>
                    </div>
                    <div class="guide-step">
                        <span class="guide-num">3</span>
                        <div>
                            <p class="font-medium">เพิ่มรหัสวิชาที่ตัดเกรดร่วมกัน</p>
                            <p class="text-[#7A4A3A]/80 mt-0.5">
                                พิมพ์แล้ว<strong>เลือกจากรายการ หรือกด Enter</strong> จนเห็นเป็นชิป
                                (ถ้าพิมพ์แล้วกดบันทึกทันทีโดยยังไม่เป็นชิป ระบบจะไม่ได้รับรหัสนั้น)
                                — แต่ละรหัสอยู่ได้<strong>กลุ่มเดียวเท่านั้น</strong>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="example-box space-y-2">
                    <p class="font-sans font-semibold text-[#5C2E1F] text-xs mb-1">ตัวอย่างที่ถูกต้อง</p>
                    <p>รหัสกลุ่ม: <strong>300109</strong></p>
                    <p>ชื่อวิชา: PHYSICAL SCIENCE</p>
                    <p>รหัสในกลุ่ม:</p>
                    <p class="pl-2">300109 <span class="text-[#A0522D]">← ตัวแทนกลุ่ม</span></p>
                    <p class="pl-2">SC002104 <span class="text-[#A0522D]">← รหัสร่วม</span></p>
                    <p class="font-sans text-[#7A4A3A] text-xs mt-2 pt-2 border-t border-dashed border-[#e8c4b8]">
                        เมื่ออาจารย์ส่งเกรดรหัสใดในกลุ่ม รายงานจะอยู่กลุ่มเดียวกัน
                    </p>
                </div>
            </div>
            <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-950 flex gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5"></i>
                <div>
                    <strong>อย่าสับสน:</strong>
                    «รหัสกลุ่ม» ต้องเป็นรหัสวิชาจริงจาก <code>pdcourse</code> ไม่ใช่ชื่อกลุ่มอิสระ
                    และ<strong>ห้ามใส่รหัสที่อยู่กลุ่มอื่นแล้ว</strong>
                </div>
            </div>
        </div>
    </details>

    @if (session('status'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('status') }}</div>
    @endif
    @if (session('duplicate_notice'))
        @php $dup = session('duplicate_notice'); @endphp
        <div id="duplicate-notice" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 space-y-2"
            data-notice='@json($dup)'>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="font-semibold flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-700"></i>
                        พบรหัสวิชาซ้ำ — ต้องตรวจสอบก่อนบันทึก
                    </p>
                    <p class="mt-1 whitespace-pre-line text-amber-900/90">{{ $dup['message'] ?? '' }}</p>
                </div>
                @if (!empty($dup['focus_url']))
                    <a href="{{ $dup['focus_url'] }}"
                        class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-[#8B4513] text-white text-xs font-semibold hover:bg-[#6B3410]">
                        <i data-lucide="corner-down-right" class="w-3.5 h-3.5"></i>
                        ไปที่กลุ่ม {{ $dup['focus_group'] ?? '' }}
                    </a>
                @endif
            </div>
            @if (!empty($dup['conflicts']) && is_array($dup['conflicts']))
                <ul class="list-disc pl-5 space-y-1 text-xs">
                    @foreach ($dup['conflicts'] as $c)
                        <li>
                            รหัส <span class="font-mono font-semibold">{{ $c['code'] ?? '' }}</span>
                            อยู่ในกลุ่ม
                            <a class="font-semibold text-[#8B4513] underline"
                                href="{{ route('faculty-admin.grad-report2-groups.index', ['q' => $c['group_code'] ?? '', 'group' => $c['group_code'] ?? '']) }}">
                                {{ $c['group_code'] ?? '' }}
                            </a>
                            @if (!empty($c['subject']))
                                <span class="text-amber-800/80">({{ $c['subject'] }})</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
    @foreach (['group_code', 'subject', 'member_codes', 'subject_code', 'new_subject_code', 'paste_text'] as $errKey)
        @error($errKey)
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm whitespace-pre-line">{{ $message }}</div>
        @enderror
    @endforeach
    @if (session('paste_partial_errors'))
        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-950 px-4 py-3 text-sm">
            <p class="font-semibold">บางกลุ่มนำเข้าไม่ได้</p>
            <ul class="list-disc pl-5 mt-1 space-y-0.5">
                @foreach ((array) session('paste_partial_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ฟอร์มสร้างกลุ่ม --}}
    <div class="form-section rounded-xl p-6 space-y-4">
        <div>
            <h3 class="font-semibold text-[#5C2E1F]">สร้างกลุ่มใหม่ / เพิ่มหลายรหัสเข้ากลุ่ม</h3>
            <p class="text-xs text-[#7A4A3A]/75 mt-1">
                พิมพ์ค้นหาจาก <code>pdcourse</code> แล้วเลือก — ถ้ามีรหัสกลุ่มนี้อยู่แล้ว ระบบจะเพิ่มเฉพาะรหัสที่ยังไม่มี
            </p>
        </div>
        <form method="POST" action="{{ route('faculty-admin.grad-report2-groups.store') }}" id="form-create-group" class="space-y-4">
            @csrf
            <input type="hidden" name="q" value="{{ $q }}">
            <input type="hidden" name="member_codes" id="member-codes-hidden" value="{{ old('member_codes') }}">
            <div class="grid md:grid-cols-2 gap-4">
                <div class="relative">
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">รหัสกลุ่ม *</label>
                    <input type="text" name="group_code" id="group-code-input" value="{{ old('group_code') }}" required maxlength="20"
                        placeholder="พิมพ์รหัสหรือชื่อวิชา เช่น 300109"
                        autocomplete="off"
                        class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white uppercase">
                    <div id="group-code-suggest" class="subject-suggest hidden absolute left-0 right-0 top-full mt-1 bg-white border border-amber-200 rounded-lg"></div>
                    <p class="field-hint">ตัวแทนกลุ่ม — เลือกจากรายการแนะนำได้</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ชื่อวิชา (ENG) *</label>
                    <input type="text" name="subject" id="group-subject-input" value="{{ old('subject') }}" required maxlength="255"
                        placeholder="เช่น PHYSICAL SCIENCE"
                        class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <p class="field-hint">เติมอัตโนมัติเมื่อเลือกจากรายการ</p>
                </div>
            </div>
            <div class="relative">
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">รหัสวิชาในกลุ่ม</label>
                <div class="member-pick-box" id="member-pick-box">
                    <input type="text" id="member-pick-input" class="member-pick-input" autocomplete="off"
                        placeholder="พิมพ์ค้นหาแล้วเลือก หรือกด Enter เพื่อเพิ่ม">
                </div>
                <div id="member-pick-suggest" class="subject-suggest hidden absolute left-0 right-0 top-full mt-1 bg-white border border-amber-200 rounded-lg"></div>
                <p class="field-hint">
                    พิมพ์ค้นหาแล้ว<strong>เลือกจากรายการ</strong> หรือกด <kbd class="px-1 border border-amber-200 rounded bg-amber-50">Enter</kbd>
                    ให้รหัสกลายเป็นชิปก่อนกดบันทึก — ไม่ต้องใส่รหัสกลุ่มซ้ำ ระบบเติมให้อัตโนมัติ
                </p>
            </div>
            <button type="submit" class="px-5 py-2.5 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">
                บันทึกกลุ่ม
            </button>
        </form>
    </div>

    {{-- วางจาก Excel (เงื่อนไขเดียวกับฟอร์มด้านบน) --}}
    <details class="form-section rounded-xl p-6" @if (session('paste_open') || old('paste_text') || $errors->has('paste_text')) open @endif>
        <summary class="cursor-pointer select-none list-none flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-semibold text-[#5C2E1F] flex items-center gap-2">
                    <i data-lucide="clipboard-paste" class="w-4 h-4"></i>
                    เพิ่มแบบวางจาก Excel
                </h3>
                <p class="text-xs text-[#7A4A3A]/75 mt-1">
                    คัดลอกเซลจาก Excel มาวาง — คอลัมน์ตรงกับฟอร์มด้านบนและไฟล์จากปุ่ม Export Excel · การเพิ่มทีละกลุ่มแบบเดิมยังใช้ได้ตามปกติ
                </p>
            </div>
            <span class="text-xs font-semibold text-[#8B4513] px-2.5 py-1 rounded-lg bg-[#FAF0E6] border border-[#e8c4b8]">คลิกเพื่อเปิด/ปิด</span>
        </summary>

        <div class="mt-4 space-y-4">
            <div class="paste-grid-head" aria-hidden="true">
                <span>1. รหัสกลุ่ม *</span>
                <span>2. ชื่อวิชา (ENG) *</span>
                <span>3. รหัสวิชาในกลุ่ม *</span>
            </div>

            <div class="grid md:grid-cols-2 gap-3 text-xs text-[#5C2E1F]">
                <div class="example-box space-y-1.5">
                    <p class="font-sans font-semibold text-xs">รูปแบบ A — หนึ่งแถวต่อสมาชิก</p>
                    <p>300109<span class="text-[#A0522D]">[Tab]</span>PHYSICAL SCIENCE<span class="text-[#A0522D]">[Tab]</span>SC002104</p>
                    <p>300109<span class="text-[#A0522D]">[Tab]</span>PHYSICAL SCIENCE<span class="text-[#A0522D]">[Tab]</span>SC002105</p>
                    <p class="font-sans text-[#7A4A3A] pt-1">รหัสกลุ่มซ้ำได้ — ระบบรวมเข้ากลุ่มเดียวกัน</p>
                </div>
                <div class="example-box space-y-1.5">
                    <p class="font-sans font-semibold text-xs">รูปแบบ B — หลายรหัสในคอลัมน์เดียว</p>
                    <p>300109<span class="text-[#A0522D]">[Tab]</span>PHYSICAL SCIENCE<span class="text-[#A0522D]">[Tab]</span>SC002104, SC002105</p>
                    <p class="font-sans text-[#7A4A3A] pt-1">แยกด้วยจุลภาค ช่องว่าง หรือขึ้นบรรทัดในเซลได้</p>
                </div>
            </div>

            <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-950 flex gap-2">
                <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
                <div>
                    เงื่อนไขเดียวกับฟอร์มด้านบน: รหัสกลุ่มต้องเป็นรหัสจริง · แต่ละรหัสอยู่ได้กลุ่มเดียว ·
                    ห้ามวิทยานิพนธ์/การศึกษาอิสระ · มีหัวตารางก็ได้ (ระบบข้ามให้อัตโนมัติ)
                </div>
            </div>

            <form method="POST" action="{{ route('faculty-admin.grad-report2-groups.paste') }}" id="form-paste-groups" class="space-y-3">
                @csrf
                <input type="hidden" name="q" value="{{ $q }}">
                <label class="block text-sm font-medium text-[#5C2E1F]" for="paste-text">พื้นที่วางข้อมูล</label>
                <textarea id="paste-text" name="paste_text" class="paste-area"
                    placeholder="วางที่นี่ เช่น&#10;รหัสกลุ่ม&#9;ชื่อวิชา (ENG)&#9;รหัสวิชาในกลุ่ม&#10;300109&#9;PHYSICAL SCIENCE&#9;SC002104&#10;300109&#9;PHYSICAL SCIENCE&#9;SC002105"
                    spellcheck="false">{{ old('paste_text') }}</textarea>

                <div id="paste-preview-wrap" class="hidden space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-[#5C2E1F]">ตัวอย่างก่อนบันทึก</p>
                        <p id="paste-preview-meta" class="text-xs text-[#7A4A3A]"></p>
                    </div>
                    <div class="paste-preview">
                        <table>
                            <thead>
                                <tr>
                                    <th>รหัสกลุ่ม</th>
                                    <th>ชื่อวิชา (ENG)</th>
                                    <th>รหัสวิชาในกลุ่ม</th>
                                </tr>
                            </thead>
                            <tbody id="paste-preview-body"></tbody>
                        </table>
                    </div>
                    <p id="paste-preview-errors" class="hidden text-xs text-red-700 whitespace-pre-line"></p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" id="paste-submit" class="px-5 py-2.5 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700">
                        บันทึกข้อมูลที่วาง
                    </button>
                    <button type="button" id="paste-clear" class="px-4 py-2.5 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">
                        ล้างช่องวาง
                    </button>
                </div>
            </form>
        </div>
    </details>

    {{-- ค้นหา --}}
    <div class="form-section rounded-xl p-4">
        <form method="GET" action="{{ route('faculty-admin.grad-report2-groups.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[16rem]">
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ค้นหากลุ่ม / รหัสวิชา / ชื่อวิชา</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="เช่น 300109 หรือ PHYSICAL"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <button type="submit" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">ค้นหา</button>
            @if ($q !== '')
                <a href="{{ route('faculty-admin.grad-report2-groups.index') }}" class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ล้าง</a>
            @endif
        </form>
    </div>

    {{-- รายการกลุ่มแบบตาราง Excel --}}
    <div class="space-y-3">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h3 class="font-semibold text-[#5C2E1F]">รายการจัดกลุ่มรายวิชา</h3>
                <p class="text-xs text-[#7A4A3A]/80 mt-0.5">แสดงแบบตารางเหมือน Excel — มีเส้นแบ่งระหว่างกลุ่มชัดเจน</p>
            </div>
            <div class="sheet-legend">
                <span class="sheet-legend-item"><i data-lucide="pencil" class="w-3.5 h-3.5 text-[#854d0e]"></i> แก้ไขชื่อ</span>
                <span class="sheet-legend-item"><i data-lucide="plus" class="w-3.5 h-3.5 text-sky-700"></i> เพิ่มรหัส</span>
                <span class="sheet-legend-item"><i data-lucide="trash-2" class="w-3.5 h-3.5 text-red-700"></i> ลบกลุ่ม / ลบรหัส</span>
                <span class="sheet-legend-item"><span class="sheet-key-badge">กลุ่ม</span> รหัสตัวแทนกลุ่ม</span>
                <span class="sheet-legend-item"><span class="sheet-source-badge sheet-source-admin"><i data-lucide="shield"></i> Admin</span> เจ้าหน้าที่ / ข้อมูลเดิมไม่มีรหัสผู้กรอก</span>
                <span class="sheet-legend-item"><span class="sheet-source-badge sheet-source-instructor"><i data-lucide="user"></i> อาจารย์</span> ผู้กรอกที่เป็นอาจารย์</span>
            </div>
        </div>

        @if ($groups->count())
            <div class="sheet-wrap">
                <div class="overflow-x-auto">
                    <table class="sheet-table">
                        <thead>
                            <tr>
                                <th style="width:8.5rem">รหัสกลุ่ม</th>
                                <th>ชื่อวิชา (ENG)</th>
                                <th style="min-width:11rem">รหัสวิชาในกลุ่ม</th>
                                <th style="width:8.5rem">สถานะ</th>
                                <th style="width:9.5rem">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groups as $group)
                                @php $isFocus = $focusGroup !== '' && $focusGroup === strtoupper($group->group_code); @endphp
                                @foreach ($group->members as $index => $member)
                                    <tr
                                        @if ($index === 0) id="group-{{ $group->group_code }}" @endif
                                        class="member-row {{ $index === 0 ? 'group-start' : '' }} {{ $isFocus && $index === 0 ? 'is-focus' : '' }}"
                                    >
                                        <td>
                                            @if ($index === 0)
                                                <span class="sheet-code">{{ $group->group_code }}</span>
                                                <span class="ml-1 text-[0.68rem] text-[#7A4A3A]/70">{{ $group->member_count }} รหัส</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($index === 0)
                                                <span class="font-medium">{{ $group->subject ?: '—' }}</span>
                                            @else
                                                <span class="text-[#7A4A3A]/55">{{ $member->subject ?: $group->subject }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="member-view flex flex-wrap items-center gap-2">
                                                <span class="sheet-code">{{ $member->subject_code }}</span>
                                                @if ($member->is_group_key)
                                                    <span class="sheet-key-badge">กลุ่ม</span>
                                                @endif
                                                <div class="sheet-actions ml-auto sm:ml-2">
                                                    <button type="button"
                                                        class="sheet-icon-btn sheet-icon-edit"
                                                        data-tip="แก้ไขรหัสนี้"
                                                        aria-label="แก้ไขรหัส {{ $member->subject_code }}"
                                                        onclick="this.closest('.member-row').classList.add('is-editing'); if (window.lucide) lucide.createIcons();">
                                                        <i data-lucide="pencil"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('faculty-admin.grad-report2-groups.members.destroy') }}"
                                                        class="inline-flex"
                                                        onsubmit="return confirm('ลบรหัส {{ $member->subject_code }} ออกจากกลุ่ม?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="group_code" value="{{ $group->group_code }}">
                                                        <input type="hidden" name="subject_code" value="{{ $member->subject_code }}">
                                                        <input type="hidden" name="q" value="{{ $q }}">
                                                        <button type="submit"
                                                            class="sheet-icon-btn sheet-icon-delete"
                                                            data-tip="ลบรหัสนี้"
                                                            aria-label="ลบรหัส {{ $member->subject_code }}">
                                                            <i data-lucide="trash-2"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <form method="POST" action="{{ route('faculty-admin.grad-report2-groups.members.update') }}"
                                                class="member-edit hidden flex-wrap items-end gap-2 w-full bg-[#FFFBF7] rounded-lg p-2 border border-amber-100 mt-2">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="group_code" value="{{ $group->group_code }}">
                                                <input type="hidden" name="subject_code" value="{{ $member->subject_code }}">
                                                <input type="hidden" name="q" value="{{ $q }}">
                                                <div class="relative">
                                                    <label class="block text-[0.65rem] text-[#5C2E1F] mb-0.5">รหัสวิชา</label>
                                                    <input type="text" name="new_subject_code" value="{{ $member->subject_code }}" required maxlength="20"
                                                        autocomplete="off"
                                                        data-subject-autocomplete
                                                        data-fill-name="edit-name-{{ $group->group_code }}-{{ $member->subject_code }}"
                                                        data-suggest-box="edit-suggest-{{ $group->group_code }}-{{ $member->subject_code }}"
                                                        class="w-40 border border-amber-300 rounded px-2 py-1.5 text-sm uppercase">
                                                    <div id="edit-suggest-{{ $group->group_code }}-{{ $member->subject_code }}" class="subject-suggest hidden absolute left-0 right-0 top-full mt-1 bg-white border border-amber-200 rounded-lg"></div>
                                                </div>
                                                <div class="flex-1 min-w-[12rem]">
                                                    <label class="block text-[0.65rem] text-[#5C2E1F] mb-0.5">ชื่อวิชา</label>
                                                    <input type="text" name="subject" id="edit-name-{{ $group->group_code }}-{{ $member->subject_code }}" value="{{ $member->subject }}" required
                                                        class="w-full border border-amber-300 rounded px-2 py-1.5 text-sm">
                                                </div>
                                                <button type="submit" class="px-3 py-1.5 bg-[#8B4513] text-white rounded text-xs">บันทึก</button>
                                                <button type="button" class="px-3 py-1.5 border border-amber-300 rounded text-xs"
                                                    onclick="this.closest('.member-row').classList.remove('is-editing')">ยกเลิก</button>
                                                @if ($member->is_group_key)
                                                    <p class="w-full text-[0.65rem] text-amber-800">ถ้าแก้รหัสตัวแทนกลุ่ม ระบบจะเปลี่ยนรหัสกลุ่มของสมาชิกทั้งหมดตามด้วย</p>
                                                @endif
                                            </form>
                                        </td>
                                        <td>
                                            @if ($index === 0)
                                                <div class="sheet-source">
                                                    @if ($group->source === 'instructor')
                                                        <span class="sheet-source-badge sheet-source-instructor" title="อาจารย์เป็นคนกรอก">
                                                            <i data-lucide="user"></i> อาจารย์
                                                        </span>
                                                        @if (($group->entered_by_names ?? '') !== '')
                                                            <span class="sheet-source-user" title="ชื่อผู้กรอก">{{ $group->entered_by_names }}</span>
                                                        @endif
                                                        @if (!empty($group->entered_at))
                                                            <span class="sheet-source-date" title="วันที่กรอก">{{ $group->entered_at }}</span>
                                                        @endif
                                                    @else
                                                        <span class="sheet-source-badge sheet-source-admin" title="Admin / เจ้าหน้าที่เป็นผู้นำเข้า หรือข้อมูลเดิม">
                                                            <i data-lucide="shield"></i> Admin
                                                        </span>
                                                        @if (($group->entered_by_names ?? '') !== '')
                                                            <span class="sheet-source-user" title="ชื่อผู้กรอก">{{ $group->entered_by_names }}</span>
                                                        @elseif (($group->entered_by ?? '') !== '')
                                                            <span class="sheet-source-user" title="รหัสผู้กรอก">{{ $group->entered_by }}</span>
                                                        @endif
                                                        @if (!empty($group->entered_at))
                                                            <span class="sheet-source-date" title="วันที่กรอก">{{ $group->entered_at }}</span>
                                                        @endif
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($index === 0)
                                                <div class="sheet-actions">
                                                    <button type="button"
                                                        class="sheet-icon-btn sheet-icon-edit"
                                                        data-tip="แก้ไขชื่อ"
                                                        aria-label="แก้ไขชื่อกลุ่ม {{ $group->group_code }}"
                                                        onclick="togglePanel('edit-name-{{ $group->group_code }}')">
                                                        <i data-lucide="pencil"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="sheet-icon-btn sheet-icon-add"
                                                        data-tip="เพิ่มรหัส"
                                                        aria-label="เพิ่มรหัสเข้ากลุ่ม {{ $group->group_code }}"
                                                        onclick="togglePanel('add-member-{{ $group->group_code }}')">
                                                        <i data-lucide="plus"></i>
                                                    </button>
                                                    <form method="POST" action="{{ route('faculty-admin.grad-report2-groups.destroy') }}"
                                                        class="inline-flex"
                                                        onsubmit="return confirm('ลบกลุ่ม {{ $group->group_code }} ทั้งกลุ่ม ({{ $group->member_count }} รหัส)?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="group_code" value="{{ $group->group_code }}">
                                                        <input type="hidden" name="q" value="{{ $q }}">
                                                        <button type="submit"
                                                            class="sheet-icon-btn sheet-icon-delete"
                                                            data-tip="ลบกลุ่ม"
                                                            aria-label="ลบกลุ่ม {{ $group->group_code }}">
                                                            <i data-lucide="trash-2"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach

                                <tr id="edit-name-{{ $group->group_code }}" class="sheet-inline-panel hidden">
                                    <td colspan="5">
                                        <form method="POST" action="{{ route('faculty-admin.grad-report2-groups.update') }}" class="flex flex-wrap items-end gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="group_code" value="{{ $group->group_code }}">
                                            <input type="hidden" name="q" value="{{ $q }}">
                                            <div class="flex-1 min-w-[14rem]">
                                                <label class="block text-xs font-medium text-[#5C2E1F] mb-1">ชื่อวิชาทั้งกลุ่ม — {{ $group->group_code }}</label>
                                                <input type="text" name="subject" value="{{ $group->subject }}" required
                                                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                                            </div>
                                            <button type="submit" class="px-3 py-2 bg-[#8B4513] text-white rounded-lg text-xs font-semibold">บันทึกชื่อ</button>
                                            <button type="button" class="px-3 py-2 border border-amber-300 rounded-lg text-xs"
                                                onclick="togglePanel('edit-name-{{ $group->group_code }}')">ปิด</button>
                                        </form>
                                    </td>
                                </tr>

                                <tr id="add-member-{{ $group->group_code }}" class="sheet-inline-panel hidden">
                                    <td colspan="5">
                                        <form method="POST" action="{{ route('faculty-admin.grad-report2-groups.members.store') }}" class="flex flex-wrap items-end gap-2">
                                            @csrf
                                            <input type="hidden" name="group_code" value="{{ $group->group_code }}">
                                            <input type="hidden" name="q" value="{{ $q }}">
                                            <div class="relative">
                                                <label class="block text-xs font-medium text-[#5C2E1F] mb-1">เพิ่มรหัสเข้ากลุ่ม {{ $group->group_code }}</label>
                                                <input type="text" name="subject_code" required maxlength="20" placeholder="พิมพ์ค้นหาจาก pdcourse"
                                                    autocomplete="off"
                                                    data-subject-autocomplete
                                                    data-suggest-box="add-suggest-{{ $group->group_code }}"
                                                    class="w-56 border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white uppercase">
                                                <div id="add-suggest-{{ $group->group_code }}" class="subject-suggest hidden absolute left-0 right-0 top-full mt-1 bg-white border border-amber-200 rounded-lg"></div>
                                            </div>
                                            <button type="submit" class="px-3 py-2 bg-sky-600 text-white rounded-lg text-xs font-semibold hover:bg-sky-700">เพิ่ม</button>
                                            <button type="button" class="px-3 py-2 border border-amber-300 rounded-lg text-xs"
                                                onclick="togglePanel('add-member-{{ $group->group_code }}')">ปิด</button>
                                        </form>
                                        <p class="field-hint mt-1">ค้นหาจาก pdcourse — รหัสต้องยังไม่มีในกลุ่มอื่น</p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-dashed border-amber-300 bg-white px-6 py-10 text-center text-sm text-[#7A4A3A]">
                @if ($q !== '')
                    ไม่พบกลุ่มที่ตรงกับ «{{ $q }}»
                @else
                    ยังไม่มีข้อมูลจัดกลุ่มรายวิชา — เริ่มจากแบบฟอร์มด้านบน
                @endif
            </div>
        @endif
    </div>

    @if ($groups->hasPages())
        <div class="flex justify-center">{{ $groups->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function togglePanel(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('hidden');
}

(function () {
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const hideSuggest = (box) => {
        if (!box) return;
        box.classList.add('hidden');
        box.innerHTML = '';
    };

    const searchSubjects = async (q) => {
        if (!q || q.length < 1) return [];
        try {
            const res = await fetch(`/api/subjects/search?q=${encodeURIComponent(q)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            return await res.json();
        } catch {
            return [];
        }
    };

    const bindAutocomplete = (input, box, onPick) => {
        if (!input || !box) return;
        let timer = null;

        const render = async () => {
            const q = input.value.trim();
            if (q.length < 1) { hideSuggest(box); return; }
            const rows = await searchSubjects(q);
            if (!rows.length) { hideSuggest(box); return; }
            box.innerHTML = rows.map((row) => `
                <button type="button"
                    data-code="${escapeHtml(row.subject_code)}"
                    data-name="${escapeHtml(row.subject || '')}">
                    <span class="font-medium text-[#5C2E1F]">${escapeHtml(row.subject_code)}</span>
                    <span class="text-xs text-gray-500 block">${escapeHtml(row.subject || '')}</span>
                </button>
            `).join('');
            box.classList.remove('hidden');
            box.querySelectorAll('button').forEach((btn) => {
                btn.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    onPick(btn.dataset.code || '', btn.dataset.name || '');
                    hideSuggest(box);
                });
            });
        };

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(render, 220);
        });
        input.addEventListener('focus', () => {
            if (input.value.trim().length >= 1) render();
        });
        input.addEventListener('blur', () => setTimeout(() => hideSuggest(box), 150));
    };

    // --- สร้างกลุ่ม: รหัสกลุ่ม + เติมชื่อวิชา ---
    const groupCodeInput = document.getElementById('group-code-input');
    const groupSubjectInput = document.getElementById('group-subject-input');
    const groupSuggest = document.getElementById('group-code-suggest');
    bindAutocomplete(groupCodeInput, groupSuggest, (code, name) => {
        groupCodeInput.value = code;
        if (name) groupSubjectInput.value = name;
    });

    // --- สร้างกลุ่ม: เลือกหลายรหัสเป็นชิป ---
    const memberHidden = document.getElementById('member-codes-hidden');
    const memberPickInput = document.getElementById('member-pick-input');
    const memberPickSuggest = document.getElementById('member-pick-suggest');
    const memberPickBox = document.getElementById('member-pick-box');
    let selectedMembers = [];

    const syncMemberHidden = () => {
        if (memberHidden) memberHidden.value = selectedMembers.join(',');
    };

    const renderMemberChips = () => {
        if (!memberPickBox || !memberPickInput) return;
        memberPickBox.querySelectorAll('.member-pick-chip').forEach((el) => el.remove());
        selectedMembers.forEach((code) => {
            const chip = document.createElement('span');
            chip.className = 'member-pick-chip';
            chip.dataset.code = code;
            chip.innerHTML = `${escapeHtml(code)} <button type="button" aria-label="ลบ ${escapeHtml(code)}">&times;</button>`;
            chip.querySelector('button').addEventListener('click', () => {
                selectedMembers = selectedMembers.filter((c) => c !== code);
                renderMemberChips();
            });
            memberPickBox.insertBefore(chip, memberPickInput);
        });
        syncMemberHidden();
    };

    const addMemberCode = (code) => {
        const normalized = String(code || '').trim().toUpperCase().replace(/\s+/g, '');
        if (!normalized) return;
        if (!selectedMembers.includes(normalized)) {
            selectedMembers.push(normalized);
            renderMemberChips();
        }
        if (memberPickInput) memberPickInput.value = '';
    };

    if (memberHidden?.value) {
        selectedMembers = memberHidden.value
            .split(/[\s,;|]+/)
            .map((c) => c.trim().toUpperCase())
            .filter(Boolean);
        renderMemberChips();
    }

    bindAutocomplete(memberPickInput, memberPickSuggest, (code) => {
        addMemberCode(code);
        memberPickInput?.focus();
    });

    memberPickInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ',') {
            const raw = memberPickInput.value.trim();
            if (!raw) return;
            e.preventDefault();
            addMemberCode(raw);
            hideSuggest(memberPickSuggest);
        } else if (e.key === 'Backspace' && !memberPickInput.value && selectedMembers.length) {
            selectedMembers.pop();
            renderMemberChips();
        }
    });

    memberPickBox?.addEventListener('click', () => memberPickInput?.focus());

    const formCreateGroup = document.getElementById('form-create-group');
    formCreateGroup?.addEventListener('submit', () => {
        // ถ้าพิมพ์รหัสไว้แล้วยังไม่กด Enter — ดึงเข้าชิปก่อนส่งฟอร์ม
        const pending = memberPickInput?.value.trim();
        if (pending) {
            addMemberCode(pending);
        }
        syncMemberHidden();
    });

    // --- autocomplete ทั่วไป (เพิ่มรหัส / แก้ไข) ---
    document.querySelectorAll('[data-subject-autocomplete]').forEach((input) => {
        const boxId = input.getAttribute('data-suggest-box');
        const nameId = input.getAttribute('data-fill-name');
        const box = boxId ? document.getElementById(boxId) : null;
        const nameInput = nameId ? document.getElementById(nameId) : null;
        bindAutocomplete(input, box, (code, name) => {
            input.value = code;
            if (name && nameInput) nameInput.value = name;
        });
    });

    document.addEventListener('DOMContentLoaded', () => {
        const notice = document.getElementById('duplicate-notice');
        if (notice) {
            let payload = {};
            try {
                payload = JSON.parse(notice.getAttribute('data-notice') || '{}');
            } catch (e) {
                payload = {};
            }
            const message = payload.message || 'พบรหัสวิชาซ้ำในระบบ';
            const focusUrl = payload.focus_url || '';
            if (focusUrl && window.confirm(message)) {
                window.location.href = focusUrl;
                return;
            }
            notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        const focus = document.querySelector('.sheet-table tr.is-focus, .sheet-wrap.is-focus');
        if (focus) focus.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    // --- วางจาก Excel: พรีวิว ---
    const pasteText = document.getElementById('paste-text');
    const pastePreviewWrap = document.getElementById('paste-preview-wrap');
    const pastePreviewBody = document.getElementById('paste-preview-body');
    const pastePreviewMeta = document.getElementById('paste-preview-meta');
    const pastePreviewErrors = document.getElementById('paste-preview-errors');
    const pasteClear = document.getElementById('paste-clear');

    const splitPasteCells = (line) => {
        const trimmed = line.replace(/\s+$/g, '');
        if (trimmed.includes('\t')) {
            return trimmed.split('\t').map((c) => c.trim());
        }
        if ((trimmed.match(/;/g) || []).length >= 2) {
            return trimmed.split(';').map((c) => c.trim());
        }
        if ((trimmed.match(/,/g) || []).length >= 2) {
            const parts = trimmed.split(',').map((c) => c.trim());
            return [parts[0], parts[1], parts.slice(2).join(',')];
        }
        return trimmed.split(/\s{2,}/).map((c) => c.trim()).filter((c, i, arr) => c || i < arr.length - 1);
    };

    const looksLikeHeader = (cells) => {
        const joined = cells.join(' ').toLowerCase();
        return /group|รหัสกลุ่ม|subject|ชื่อวิชา|member|รหัสวิชา/.test(joined);
    };

    const normalizeCode = (value) => String(value || '').trim().toUpperCase().replace(/\s+/g, '');
    const parseMemberCodes = (raw) => String(raw || '')
        .replace(/[;|]/g, ',')
        .split(/[\s,]+/)
        .map(normalizeCode)
        .filter(Boolean);

    const renderPastePreview = () => {
        if (!pasteText || !pastePreviewWrap || !pastePreviewBody) return;
        const raw = pasteText.value.replace(/\r\n/g, '\n').replace(/\r/g, '\n').trim();
        if (!raw) {
            pastePreviewWrap.classList.add('hidden');
            return;
        }

        const lines = raw.split('\n').filter((line) => line.trim() !== '');
        let start = 0;
        if (lines.length && looksLikeHeader(splitPasteCells(lines[0]))) start = 1;

        const groups = new Map();
        const errors = [];

        for (let i = start; i < lines.length; i++) {
            const lineNo = i + 1;
            const cells = splitPasteCells(lines[i]);
            const groupCode = normalizeCode(cells[0] || '');
            const subject = String(cells[1] || '').trim().toUpperCase();
            const members = parseMemberCodes(cells.slice(2).join(' '));

            if (!groupCode && !subject && members.length === 0) continue;
            if (!groupCode) { errors.push(`แถว ${lineNo}: ขาดรหัสกลุ่ม`); continue; }
            if (!subject) { errors.push(`แถว ${lineNo}: ขาดชื่อวิชา (ENG)`); continue; }
            if (!members.length) { errors.push(`แถว ${lineNo}: ขาดรหัสวิชาในกลุ่ม`); continue; }

            if (!groups.has(groupCode)) {
                groups.set(groupCode, { subject, members: [] });
            } else if (groups.get(groupCode).subject !== subject) {
                errors.push(`แถว ${lineNo}: ชื่อวิชาของกลุ่ม ${groupCode} ไม่ตรงกับแถวก่อนหน้า`);
                continue;
            }
            const bucket = groups.get(groupCode);
            members.forEach((code) => {
                if (!bucket.members.includes(code)) bucket.members.push(code);
            });
        }

        pastePreviewWrap.classList.remove('hidden');
        if (pastePreviewMeta) {
            pastePreviewMeta.textContent = groups.size
                ? `${groups.size} กลุ่ม · ${[...groups.values()].reduce((n, g) => n + g.members.length, 0)} รหัส`
                : 'ยังไม่มีแถวที่ใช้ได้';
        }

        pastePreviewBody.innerHTML = [...groups.entries()].map(([code, row]) => `
            <tr>
                <td><code class="font-semibold text-[#8B4513]">${escapeHtml(code)}</code></td>
                <td>${escapeHtml(row.subject)}</td>
                <td>${row.members.map((m) => `<span class="paste-code-chip">${escapeHtml(m)}</span>`).join('')}</td>
            </tr>
        `).join('') || `<tr><td colspan="3" class="text-[#7A4A3A]">ยังอ่านแถวข้อมูลไม่ได้ — ตรวจคอลัมน์ 3 ช่อง</td></tr>`;

        if (pastePreviewErrors) {
            if (errors.length) {
                pastePreviewErrors.textContent = errors.join('\n');
                pastePreviewErrors.classList.remove('hidden');
            } else {
                pastePreviewErrors.textContent = '';
                pastePreviewErrors.classList.add('hidden');
            }
        }
    };

    pasteText?.addEventListener('input', renderPastePreview);
    pasteText?.addEventListener('paste', () => setTimeout(renderPastePreview, 0));
    pasteClear?.addEventListener('click', () => {
        if (pasteText) pasteText.value = '';
        renderPastePreview();
        pasteText?.focus();
    });
    if (pasteText?.value.trim()) renderPastePreview();
})();
</script>
@endpush
