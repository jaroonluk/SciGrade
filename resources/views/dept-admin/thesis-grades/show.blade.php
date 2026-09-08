@extends('layouts.scigrad')

@section('title', $report->displayCode().' — รับผลการเรียนวิทยานิพนธ์')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('dept-admin.thesis-grades.index') }}" class="text-[#8B4513] hover:underline">รับผลการเรียนวิทยานิพนธ์</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">{{ $report->displayCode() }}</span>
@endsection

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-bold text-[#5C2E1F]">{{ $report->displayCode() }} · กลุ่ม {{ $report->paddedSection() }}</h2>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $report->statusChipClass() }}">{{ $report->statusLabel() }}</span>
            </div>
            <p class="text-sm text-[#7A4A3A] mt-1">{{ $report->subject }}</p>
            <p class="text-xs text-[#7A4A3A]/70 mt-1">
                {{ $report->termLabel() }} {{ $report->year }} · {{ $report->teacher ?: $report->username }}
                · ชื่อไฟล์มาตรฐาน {{ $report->tsFilename() }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($report->files->isNotEmpty())
                <a href="{{ route('dept-admin.thesis-grades.files.zip', $report) }}" class="px-3 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">ดาวน์โหลดรวม</a>
            @endif
            <a href="{{ route('dept-admin.thesis-grades.index') }}" class="px-3 py-2 border border-amber-300 rounded-lg text-sm text-[#5C2E1F] hover:bg-amber-50">กลับรายการ</a>
        </div>
    </div>

    @if ($report->return_reason)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            ส่งกลับล่าสุด: {{ $report->return_reason }}
        </div>
    @endif

    <section class="form-section rounded-xl p-5">
        <h3 class="font-semibold text-[#5C2E1F] mb-3">ไฟล์แนบ</h3>
        <div class="space-y-2">
            @forelse ($report->files as $file)
                <a href="{{ route('dept-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank"
                   class="flex items-center justify-between gap-2 rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm hover:bg-amber-50">
                    <span>{{ $file->typeLabel() }} · {{ $file->original_name }}</span>
                    <span class="text-[#a16207] font-semibold">เปิด</span>
                </a>
            @empty
                <p class="text-sm text-[#7A4A3A]/70">ไม่มีไฟล์</p>
            @endforelse
        </div>
    </section>

    <section class="form-section rounded-xl p-5">
        <h3 class="font-semibold text-[#5C2E1F] mb-3">รายชื่อนักศึกษา</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[#5C2E1F]">
                        <th class="pb-2">รหัส</th>
                        <th class="pb-2">ชื่อ</th>
                        <th class="pb-2">ระดับ</th>
                        <th class="pb-2">ภาคสะสม</th>
                        <th class="pb-2">เค้าโครง</th>
                        <th class="pb-2">เกรด / หน่วยกิต</th>
                        <th class="pb-2">สอบวิทยานิพนธ์</th>
                        <th class="pb-2">S=0</th>
                        <th class="pb-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report->students as $student)
                        <tr class="border-t border-amber-100 {{ $student->isProposalOverdue() ? 'bg-red-50/70' : '' }}">
                            <td class="py-2 pr-2 font-medium">{{ $student->student_code }}</td>
                            <td class="py-2 pr-2">{{ $student->student_name }}</td>
                            <td class="py-2 pr-2">{{ $student->degreeLabel() }}</td>
                            <td class="py-2 pr-2">{{ $student->thesis_terms_count }}</td>
                            <td class="py-2 pr-2">
                                @if ($student->proposal_approved)
                                    อนุมัติแล้ว
                                @elseif ($student->isProposalOverdue())
                                    <span class="text-red-700 font-semibold">เลยกำหนด</span>
                                @else
                                    อยู่ในกำหนด
                                @endif
                            </td>
                            <td class="py-2 pr-2">{{ $student->grade }} / {{ $student->progress_credits ?? '—' }}</td>
                            <td class="py-2 pr-2">
                                @if ($student->completed)
                                    {{ $student->defense_date?->format('d/m/Y') ?: 'ยังไม่ระบุวันที่' }}
                                @else
                                    ยังไม่ครบหลักสูตร
                                @endif
                            </td>
                            <td class="py-2">
                                @if ($student->requiresS0Letter())
                                    {{ $student->hasS0Letter($report) ? 'มีหนังสือ' : 'ขาดหนังสือ' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2 whitespace-nowrap">
                                @if ($student->requiresS0Letter() || strtoupper((string) $student->grade) === 'S')
                                    <a href="{{ route('dept-admin.thesis-grades.s0.docx', [$report, $student]) }}"
                                       class="text-xs font-semibold text-[#a16207] underline">พิมพ์ S=0</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-[#7A4A3A]/70 mt-3">แบบฟอร์มหนังสือชี้แจง: <a href="{{ $s0FormUrl }}" class="underline text-[#a16207]" target="_blank" rel="noopener">เปิดลิงก์</a></p>
    </section>

    @if (in_array($report->status, ['submitted', 'received'], true))
        <section class="form-section rounded-xl p-5">
            <h3 class="font-semibold text-[#5C2E1F] mb-1">ไฟล์ที่ประธานหลักสูตรลงนามแล้ว</h3>
            <p class="text-sm text-[#7A4A3A]/80 mb-3">อัปโหลดได้ทีละไฟล์หรือหลายไฟล์ (PDF)</p>
            <form method="POST" action="{{ route('dept-admin.thesis-grades.chair-files.store', $report) }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="file" name="files[]" accept="application/pdf" multiple required
                       class="block w-full text-sm text-[#5C2E1F] file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-amber-100 file:text-[#854d0e]">
                <button type="submit" class="px-4 py-2 bg-[#a16207] text-white rounded-lg text-sm font-semibold hover:bg-[#854d0e]">อัปโหลดไฟล์ประธานหลักสูตร</button>
            </form>
            <div class="mt-3 space-y-2">
                @forelse ($report->files->filter->isChairSigned() as $file)
                    <div class="flex items-center justify-between gap-2 rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm">
                        <a href="{{ route('dept-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank" class="text-[#a16207] underline truncate">{{ $file->original_name }}</a>
                        <form method="POST" action="{{ route('dept-admin.thesis-grades.chair-files.destroy', [$report, $file]) }}" onsubmit="return confirm('ลบไฟล์นี้หรือไม่?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-700">ลบ</button>
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-[#7A4A3A]/70">ยังไม่มีไฟล์ประธานหลักสูตร</p>
                @endforelse
            </div>
        </section>
    @else
        @php $chairFiles = $report->files->filter->isChairSigned(); @endphp
        @if ($chairFiles->isNotEmpty())
            <section class="form-section rounded-xl p-5">
                <h3 class="font-semibold text-[#5C2E1F] mb-3">ไฟล์ที่ประธานหลักสูตรลงนามแล้ว</h3>
                <div class="space-y-2">
                    @foreach ($chairFiles as $file)
                        <a href="{{ route('dept-admin.thesis-grades.files.show', [$report, $file]) }}" target="_blank"
                           class="flex items-center justify-between gap-2 rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm hover:bg-amber-50">
                            <span>{{ $file->original_name }}</span>
                            <span class="text-[#a16207] font-semibold">เปิด</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    @endif

    @if (in_array($report->status, ['submitted', 'received'], true))
        <section class="rounded-xl border border-amber-200 bg-white p-5">
            <h3 class="font-semibold text-[#5C2E1F] mb-3">การดำเนินการของสาขา</h3>
            <div class="flex flex-wrap gap-3">
                @if ($report->status === 'submitted')
                    <form method="POST" action="{{ route('dept-admin.thesis-grades.receive', $report) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-emerald-700 text-white rounded-lg text-sm font-semibold hover:bg-emerald-800">ผ่านที่ประชุมสาขาฯ</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('dept-admin.thesis-grades.send-back', $report) }}" class="flex-1 min-w-[16rem]">
                    @csrf
                    <label class="block text-sm text-[#5C2E1F] mb-1">ส่งกลับแก้ไข</label>
                    <div class="flex gap-2">
                        <input type="text" name="return_reason" required placeholder="ระบุสิ่งที่ขาด เช่น ยังไม่มีหนังสือ S=0"
                               class="flex-1 border border-amber-300 rounded-lg px-3 py-2 text-sm">
                        <button type="submit" class="px-4 py-2 bg-red-700 text-white rounded-lg text-sm font-semibold hover:bg-red-800">ส่งกลับ</button>
                    </div>
                </form>
            </div>
        </section>
    @endif
</div>
@endsection
