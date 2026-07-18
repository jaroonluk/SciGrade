@extends('layouts.scigrad')

@section('title', 'จัดกลุ่มรายวิชา (grad_report2) — Super Admin')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('dashboard') }}" class="text-[#8B4513] hover:underline">Super Admin</a>
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
        <div class="flex gap-2">
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
    @foreach (['group_code', 'subject', 'member_codes', 'subject_code', 'new_subject_code'] as $errKey)
        @error($errKey)
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">{{ $message }}</div>
        @enderror
    @endforeach

    {{-- ฟอร์มสร้างกลุ่ม --}}
    <div class="form-section rounded-xl p-6 space-y-4">
        <div>
            <h3 class="font-semibold text-[#5C2E1F]">สร้างกลุ่มใหม่ / เพิ่มหลายรหัสเข้ากลุ่ม</h3>
            <p class="text-xs text-[#7A4A3A]/75 mt-1">
                พิมพ์ค้นหาจาก <code>pdcourse</code> แล้วเลือก — ถ้ามีรหัสกลุ่มนี้อยู่แล้ว ระบบจะเพิ่มเฉพาะรหัสที่ยังไม่มี
            </p>
        </div>
        <form method="POST" action="{{ route('super-admin.grad-report2-groups.store') }}" id="form-create-group" class="space-y-4">
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

    {{-- ค้นหา --}}
    <div class="form-section rounded-xl p-4">
        <form method="GET" action="{{ route('super-admin.grad-report2-groups.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[16rem]">
                <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ค้นหากลุ่ม / รหัสวิชา / ชื่อวิชา</label>
                <input type="text" name="q" value="{{ $q }}" placeholder="เช่น 300109 หรือ PHYSICAL"
                    class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
            </div>
            <button type="submit" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">ค้นหา</button>
            @if ($q !== '')
                <a href="{{ route('super-admin.grad-report2-groups.index') }}" class="px-4 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ล้าง</a>
            @endif
        </form>
    </div>

    {{-- รายการกลุ่ม --}}
    <div class="space-y-4">
        @forelse ($groups as $group)
            @php $isFocus = $focusGroup !== '' && $focusGroup === strtoupper($group->group_code); @endphp
            <section id="group-{{ $group->group_code }}" class="group-card {{ $isFocus ? 'is-focus' : '' }}">
                <div class="px-4 py-3 bg-gradient-to-r from-[#FFFBF7] to-[#FAF0E6]/60 border-b border-[#E8C4B8]/70 flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#8B4513] text-white text-sm font-bold tracking-wide">
                                {{ $group->group_code }}
                            </span>
                            <span class="text-xs text-[#7A4A3A]/70">{{ $group->member_count }} รหัส</span>
                        </div>
                        <p class="text-sm text-[#5C2E1F] mt-1.5 font-medium">{{ $group->subject ?: '—' }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="px-3 py-1.5 border border-amber-300 rounded-lg text-xs hover:bg-amber-50"
                            onclick="togglePanel('edit-name-{{ $group->group_code }}')">แก้ไขชื่อ</button>
                        <button type="button" class="px-3 py-1.5 border border-amber-300 rounded-lg text-xs hover:bg-amber-50"
                            onclick="togglePanel('add-member-{{ $group->group_code }}')">เพิ่มรหัส</button>
                        <form method="POST" action="{{ route('super-admin.grad-report2-groups.destroy') }}"
                            onsubmit="return confirm('ลบกลุ่ม {{ $group->group_code }} ทั้งกลุ่ม ({{ $group->member_count }} รหัส)?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="group_code" value="{{ $group->group_code }}">
                            <input type="hidden" name="q" value="{{ $q }}">
                            <button type="submit" class="px-3 py-1.5 border border-red-300 text-red-700 rounded-lg text-xs hover:bg-red-50">ลบกลุ่ม</button>
                        </form>
                    </div>
                </div>

                <div id="edit-name-{{ $group->group_code }}" class="hidden px-4 py-3 border-b border-amber-100 bg-white">
                    <form method="POST" action="{{ route('super-admin.grad-report2-groups.update') }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="group_code" value="{{ $group->group_code }}">
                        <input type="hidden" name="q" value="{{ $q }}">
                        <div class="flex-1 min-w-[14rem]">
                            <label class="block text-xs font-medium text-[#5C2E1F] mb-1">ชื่อวิชาทั้งกลุ่ม</label>
                            <input type="text" name="subject" value="{{ $group->subject }}" required
                                class="w-full border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white">
                        </div>
                        <button type="submit" class="px-3 py-2 bg-[#8B4513] text-white rounded-lg text-xs">บันทึกชื่อ</button>
                    </form>
                </div>

                <div id="add-member-{{ $group->group_code }}" class="hidden px-4 py-3 border-b border-amber-100 bg-white">
                    <form method="POST" action="{{ route('super-admin.grad-report2-groups.members.store') }}" class="flex flex-wrap items-end gap-2">
                        @csrf
                        <input type="hidden" name="group_code" value="{{ $group->group_code }}">
                        <input type="hidden" name="q" value="{{ $q }}">
                        <div class="relative">
                            <label class="block text-xs font-medium text-[#5C2E1F] mb-1">รหัสวิชาที่จะเพิ่ม</label>
                            <input type="text" name="subject_code" required maxlength="20" placeholder="พิมพ์ค้นหาจาก pdcourse"
                                autocomplete="off"
                                data-subject-autocomplete
                                data-suggest-box="add-suggest-{{ $group->group_code }}"
                                class="w-56 border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white uppercase">
                            <div id="add-suggest-{{ $group->group_code }}" class="subject-suggest hidden absolute left-0 right-0 top-full mt-1 bg-white border border-amber-200 rounded-lg"></div>
                        </div>
                        <button type="submit" class="px-3 py-2 bg-[#8B4513] text-white rounded-lg text-xs">เพิ่ม</button>
                    </form>
                    <p class="field-hint">ค้นหาจาก pdcourse — รหัสต้องยังไม่มีในกลุ่มอื่น</p>
                </div>

                <div class="px-4 py-3 space-y-2">
                    <p class="text-[0.7rem] text-[#7A4A3A]/70 font-medium">รหัสในกลุ่ม</p>
                    @foreach ($group->members as $member)
                        <div class="member-row flex flex-wrap items-center gap-2 py-1.5 border-t border-amber-50 first:border-0">
                            <div class="member-view flex flex-wrap items-center gap-2 w-full">
                                <span class="member-chip {{ $member->is_group_key ? 'is-key' : '' }}">
                                    @if ($member->is_group_key)
                                        <span class="text-[0.6rem] uppercase tracking-wide text-[#8B4513]">กลุ่ม</span>
                                    @endif
                                    <code>{{ $member->subject_code }}</code>
                                </span>
                                <span class="text-xs text-gray-500 truncate max-w-[18rem]">{{ $member->subject }}</span>
                                <div class="ml-auto flex gap-1">
                                    <button type="button" class="px-2 py-1 text-xs border border-amber-200 rounded hover:bg-amber-50"
                                        onclick="this.closest('.member-row').classList.add('is-editing')">แก้ไข</button>
                                    <form method="POST" action="{{ route('super-admin.grad-report2-groups.members.destroy') }}"
                                        onsubmit="return confirm('ลบรหัส {{ $member->subject_code }} ออกจากกลุ่ม?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="group_code" value="{{ $group->group_code }}">
                                        <input type="hidden" name="subject_code" value="{{ $member->subject_code }}">
                                        <input type="hidden" name="q" value="{{ $q }}">
                                        <button type="submit" class="px-2 py-1 text-xs border border-red-200 text-red-700 rounded hover:bg-red-50">ลบ</button>
                                    </form>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('super-admin.grad-report2-groups.members.update') }}"
                                class="member-edit hidden flex-wrap items-end gap-2 w-full bg-[#FFFBF7] rounded-lg p-2 border border-amber-100">
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
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-amber-300 bg-white px-6 py-10 text-center text-sm text-[#7A4A3A]">
                @if ($q !== '')
                    ไม่พบกลุ่มที่ตรงกับ «{{ $q }}»
                @else
                    ยังไม่มีข้อมูลจัดกลุ่มรายวิชา — เริ่มจากแบบฟอร์มด้านบน
                @endif
            </div>
        @endforelse
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
        const focus = document.querySelector('.group-card.is-focus');
        if (focus) focus.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
})();
</script>
@endpush
