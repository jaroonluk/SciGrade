@extends('layouts.scigrad')

@section('title', 'หน้าหลัก — SciGrad')

@push('styles')
<style>
    .entry-card { transition: all .2s; border: 2px solid #E8C4B8; }
    .entry-card:hover { border-color: #C4725C; box-shadow: 0 6px 20px rgba(139,69,19,.12); transform: translateY(-2px); }
    .progress-step { flex: 1; text-align: center; position: relative; }
    .progress-step .dot {
        width: 2rem; height: 2rem; border-radius: 9999px; margin: 0 auto .35rem;
        display: flex; align-items: center; justify-content: center;
        font-size: .75rem; font-weight: 700; border: 2px solid #E8C4B8; background: #fff; color: #9ca3af;
    }
    .progress-step.done .dot { background: #166534; border-color: #166534; color: #fff; }
    .progress-step.current .dot { background: #8B4513; border-color: #8B4513; color: #fff; }
    .progress-step.rejected .dot { background: #b91c1c; border-color: #b91c1c; color: #fff; }
    .progress-step .label { font-size: .7rem; line-height: 1.2; color: #7A4A3A; }
    @media (min-width: 640px) { .progress-step .label { font-size: .8rem; } }
    .report-table th { background: #fdf6f0; color: #5C2E1F; font-weight: 600; }
    .report-table td, .report-table th { padding: .65rem .75rem; border-bottom: 1px solid #f0e0d0; vertical-align: top; }
    .report-table tr:hover td { background: #fffaf5; }
    .action-btn { display: inline-flex; align-items: center; gap: .25rem; padding: .4rem .75rem; border-radius: .5rem; font-size: .8rem; font-weight: 600; }
    .file-chip {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .25rem .5rem; border-radius: .375rem;
        background: #fffaf5; border: 1px solid #f0e0d0; font-size: .75rem; color: #5C2E1F;
    }
    .file-upload-zone {
        border: 1px dashed #E8C4B8; border-radius: .5rem; padding: .5rem;
        background: #fffaf5;
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-[#5C2E1F]">ยินดีต้อนรับ, {{ $staffDisplayName }}</h2>
        <p class="text-[#7A4A3A]/80 mt-1 text-base">เลือกบทบาทและเมนูงานที่ต้องการดำเนินการ</p>
    </div>

    <div class="form-section rounded-xl p-5 mb-8 no-print">
        <p class="text-sm font-semibold text-[#5C2E1F] mb-3">เลือกบทบาทการใช้งาน</p>
        <form method="POST" action="{{ route('role.set') }}" class="flex flex-wrap gap-3">
            @csrf
            @foreach ([
                'instructor' => 'อาจารย์',
                'dept_admin' => 'Admin สาขา',
                'faculty_admin' => 'Admin กลาง',
            ] as $value => $label)
                <button type="submit" name="role" value="{{ $value }}"
                    class="px-5 py-2.5 rounded-lg text-sm font-medium border transition
                    {{ $role === $value
                        ? 'bg-[#8B4513] text-white border-[#8B4513]'
                        : 'bg-white text-[#5C2E1F] border-[#E8C4B8] hover:border-[#C4725C]' }}">
                    {{ $label }}
                </button>
            @endforeach
        </form>
    </div>

    @if ($role === 'instructor')
        <h3 class="text-lg font-bold text-[#5C2E1F] mb-4 flex items-center gap-2">
            <i data-lucide="user" class="w-5 h-5"></i> เมนูอาจารย์ — กรอกผลสอบ
        </h3>

        <div class="grid md:grid-cols-2 gap-5 mb-8">
            <a href="{{ route('grade-reports.create', ['term' => $term, 'year' => $year, 'return' => 'dashboard']) }}" class="entry-card rounded-xl p-6 bg-white block">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-xl bg-[#FAF0E6] flex items-center justify-center shrink-0 p-2">
                        <img src="{{ asset('images/icons/grade-manual-entry.svg') }}" alt="" class="w-10 h-10" width="40" height="40">
                    </div>
                    <div>
                        <p class="text-lg font-bold text-[#5C2E1F]">กรอกข้อมูลเอง</p>
                        <p class="text-sm text-[#7A4A3A]/80 mt-2 leading-relaxed">
                            สร้างแบบรายงานผลการสอบไล่และกรอกจำนวนนักศึกษาทีละ Section ผ่านฟอร์มในระบบ
                        </p>
                        <span class="inline-block mt-3 text-sm font-semibold text-[#8B4513]">คลิกเพื่อเริ่มกรอก →</span>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.upload') }}" class="entry-card rounded-xl p-6 bg-white block">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-xl bg-[#FAF0E6] flex items-center justify-center text-[#8B4513] shrink-0">
                        <i data-lucide="upload-cloud" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-[#5C2E1F]">อัปโหลดไฟล์จากสำนักทะเบียน</p>
                        <p class="text-sm text-[#7A4A3A]/80 mt-2 leading-relaxed">
                            นำเข้าไฟล์รายงานผลสอบจากสำนักทะเบียน แล้วตรวจสอบ/แก้ไขก่อนบันทึก
                        </p>
                        <span class="inline-block mt-3 text-sm font-semibold text-[#8B4513]">คลิกเพื่ออัปโหลด →</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="form-section rounded-xl p-5 mb-5">
            <h4 class="text-base font-bold text-[#5C2E1F] mb-1 flex items-center gap-2">
                <i data-lucide="list" class="w-5 h-5"></i>
                รายวิชาที่กรอกแล้ว — แบบรายงานผลการสอบไล่
            </h4>
            <p class="text-sm text-[#7A4A3A]/80 mb-4">เลือกภาคการศึกษาเพื่อดู แก้ไข ลบ หรือพิมพ์รายงาน</p>

            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                    <select name="term" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                        <option value="1" @selected($term === 1)>ภาคต้น</option>
                        <option value="2" @selected($term === 2)>ภาคปลาย</option>
                        <option value="3" @selected($term === 3)>ภาคการศึกษาพิเศษ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                    <select name="year" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[8rem]">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-5 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-semibold hover:bg-[#6B3410]">
                    แสดงรายการ
                </button>
            </form>

            <div class="mb-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-sm text-[#5C2E1F]">
                <strong>สถานะการอนุมัติ:</strong>
                <span class="inline-block mx-1 px-2 py-0.5 rounded status-pending text-xs">รออนุมัติ (0)</span>
                <span class="inline-block mx-1 px-2 py-0.5 rounded status-dept text-xs">สาขาอนุมัติ (1)</span>
                <span class="inline-block mx-1 px-2 py-0.5 rounded status-approved text-xs">คณะอนุมัติ (2)</span>
            </div>

            @if ($reports->isEmpty())
                <div class="text-center py-12 bg-white rounded-xl border border-dashed border-amber-300">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto text-amber-400 mb-3"></i>
                    <p class="text-[#5C2E1F] font-medium">ยังไม่มีรายวิชาในภาคการศึกษานี้</p>
                    <p class="text-sm text-gray-500 mt-1">กด «กรอกข้อมูลเอง» หรือ «อัปโหลดไฟล์» เพื่อเริ่มสร้างรายงาน</p>
                </div>
            @else
                <div class="overflow-x-auto bg-white rounded-xl border border-amber-200">
                    <table class="report-table w-full text-sm min-w-[800px]">
                        <thead>
                            <tr>
                                <th class="text-left">รายวิชา</th>
                                <th class="text-left" style="min-width:14rem">ความคืบหน้า / สถานะ</th>
                                <th class="text-left" style="min-width:12rem">ไฟล์แนบ (PDF)</th>
                                <th class="text-center">ทำรายการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                                @php
                                    $step = $report->approvalStep();
                                    $rejected = (int) $report->approv === -1;
                                    $canEdit = $report->canEdit();
                                    $canPrint = $report->canPrint();
                                @endphp
                                <tr>
                                    <td>
                                        <p class="font-semibold text-[#5C2E1F]">{{ $report->subject_code }}</p>
                                        <p class="text-gray-600 mt-0.5">{{ $report->subject }}</p>
                                    </td>
                                    <td>
                                        <div class="flex gap-1 mb-2 max-w-xs mx-auto sm:mx-0">
                                            <div class="progress-step done {{ $rejected ? 'rejected' : '' }}">
                                                <div class="dot">1</div>
                                                <div class="label">บันทึกแล้ว</div>
                                            </div>
                                            <div class="progress-step {{ $step >= 1 ? 'done' : '' }} {{ $step === 0 && ! $rejected ? 'current' : '' }}">
                                                <div class="dot">2</div>
                                                <div class="label">สาขาอนุมัติ</div>
                                            </div>
                                            <div class="progress-step {{ $step >= 2 ? 'done' : '' }} {{ $step === 1 ? 'current' : '' }}">
                                                <div class="dot">3</div>
                                                <div class="label">คณะอนุมัติ</div>
                                            </div>
                                        </div>
                                        @php
                                            $badge = match ((int) $report->approv) {
                                                1 => 'status-dept',
                                                2 => 'status-approved',
                                                -1 => 'status-rejected',
                                                default => 'status-pending',
                                            };
                                        @endphp
                                        <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $badge }}">
                                            {{ $report->statusShortLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="space-y-2" data-report-files="{{ $report->grade_id }}">
                                            @if ($report->files->isEmpty())
                                                <p class="text-xs text-gray-500 file-empty-msg">ยังไม่มีไฟล์แนบ</p>
                                            @else
                                                <div class="flex flex-col gap-1.5 file-list">
                                                    @foreach ($report->files as $file)
                                                        <div class="file-chip" data-file-id="{{ $file->file_id }}">
                                                            <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-[#8B4513]"></i>
                                                            <a href="{{ route('grade-reports.files.show', ['gradeReport' => $report->grade_id, 'file' => $file->file_id]) }}"
                                                               target="_blank" rel="noopener noreferrer"
                                                               class="hover:underline truncate max-w-[10rem]" title="{{ $file->original_name }}">
                                                                {{ $file->original_name }}
                                                            </a>
                                                            @if ($canEdit)
                                                                <button type="button"
                                                                    class="btn-delete-file text-red-600 hover:text-red-800 ml-1"
                                                                    data-report-id="{{ $report->grade_id }}"
                                                                    data-file-id="{{ $file->file_id }}"
                                                                    title="ลบไฟล์">
                                                                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if ($canEdit)
                                                <label class="file-upload-zone block cursor-pointer">
                                                    <input type="file" accept=".pdf,application/pdf" class="hidden file-upload-input"
                                                        data-report-id="{{ $report->grade_id }}">
                                                    <span class="text-xs text-[#8B4513] font-medium flex items-center gap-1">
                                                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                                        อัปโหลด PDF
                                                    </span>
                                                </label>
                                            @else
                                                <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded px-2 py-1.5 leading-relaxed">
                                                    รายงานผ่านการอนุมัติแล้ว — ไม่สามารถอัปโหลดหรือแก้ไขไฟล์ได้จนกว่าเจ้าหน้าที่จะคืนสถานะเป็นรออนุมัติ
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap justify-center gap-2">
                                            @if ($canEdit)
                                                @if ($canPrint)
                                                    <a href="{{ route('grade-reports.print', $report->grade_id) }}" target="_blank"
                                                       class="action-btn bg-amber-700 text-white hover:bg-amber-800">
                                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i> พิมพ์
                                                    </a>
                                                @else
                                                    <span class="action-btn bg-gray-100 text-gray-400 cursor-not-allowed" title="กรอกจำนวนนักศึกษาก่อน">
                                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i> พิมพ์
                                                    </span>
                                                @endif
                                                <a href="{{ route('grade-reports.edit', ['gradeReport' => $report->grade_id, 'term' => $term, 'year' => $year, 'return' => 'dashboard']) }}"
                                                   class="action-btn border border-amber-300 text-[#5C2E1F] hover:bg-amber-50">
                                                    <i data-lucide="pencil" class="w-3.5 h-3.5"></i> แก้ไข
                                                </a>
                                                <button type="button" class="action-btn bg-red-600 text-white hover:bg-red-700 btn-delete-report"
                                                    data-id="{{ $report->grade_id }}"
                                                    data-subject="{{ $report->subject_code }}">
                                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> ลบ
                                                </button>
                                            @else
                                                @if ($canPrint)
                                                    <a href="{{ route('grade-reports.print', $report->grade_id) }}" target="_blank"
                                                       class="action-btn bg-amber-700 text-white hover:bg-amber-800">
                                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i> พิมพ์
                                                    </a>
                                                @endif
                                                <span class="text-xs text-gray-500 text-center block w-full mt-1">{{ $report->statusLabel() }}</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-red-700 mt-3 leading-relaxed">
                    ** เมื่อสร้างแบบรายงานแล้ว ต้องกรอกจำนวนนักศึกษาก่อนจึงจะพิมพ์แบบฟอร์มได้<br>
                    ** วิชาที่ส่งเกรดช้าและมี I ต้องแนบบันทึกมาพร้อมกับใบส่งเกรด (อัปโหลดไฟล์ PDF ในคอลัมน์ «ไฟล์แนบ»)
                </p>
            @endif
        </div>
    @endif

    @if ($role === 'dept_admin')
        <h3 class="text-lg font-bold text-[#5C2E1F] mb-4 flex items-center gap-2">
            <i data-lucide="shield-check" class="w-5 h-5"></i> เมนู Admin สาขา
        </h3>

        <div class="form-section rounded-xl p-5 mb-6">
            <h4 class="text-base font-bold text-[#5C2E1F] mb-1 flex items-center gap-2">
                <i data-lucide="folder-up" class="w-5 h-5"></i>
                อัปโหลดเอกสารสาขา
            </h4>
            <p class="text-sm text-[#7A4A3A]/80 mb-4">
                ส่งเอกสารรายงานสาขาตามภาคการศึกษา อัปโหลดได้ไม่จำกัดจำนวนไฟล์
                แก้ไข/ลบได้จนกว่า Admin กลางจะกดรับเอกสารในแต่ละรอบ
            </p>

            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-4 mb-5">
                @if ($departments->count() > 1)
                    <div>
                        <label class="block text-sm font-medium text-[#5C2E1F] mb-1">สาขาวิชา</label>
                        <select name="dept_department_id" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[14rem]">
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->department_id }}" @selected($deptDepartmentId == $dept->department_id)>
                                    {{ $dept->department_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                    <select name="term" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                        <option value="1" @selected($term === 1)>ภาคต้น</option>
                        <option value="2" @selected($term === 2)>ภาคปลาย</option>
                        <option value="3" @selected($term === 3)>ภาคการศึกษาพิเศษ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                    <select name="year" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[8rem]">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">แสดงรายการ</button>
            </form>

            @php
                $deptCanModify = (bool) ($deptSubmission?->isOpen());
                $deptFiles = $deptSubmission?->files ?? collect();
            @endphp

            <div class="rounded-lg border border-amber-200 bg-white p-4"
                 data-dept-submission
                 data-department-id="{{ $deptDepartmentId }}"
                 data-term="{{ $term }}"
                 data-year="{{ $year }}"
                 data-can-modify="{{ $deptCanModify ? '1' : '0' }}">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <p class="text-sm font-semibold text-[#5C2E1F]">
                        รอบการส่งปัจจุบัน:
                        @if ($deptSubmission)
                            <span class="inline-block px-2 py-0.5 rounded text-xs {{ $deptSubmission->isOpen() ? 'bg-amber-100 text-amber-900' : 'bg-green-100 text-green-800' }}">
                                {{ $deptSubmission->statusLabel() }}
                            </span>
                        @else
                            <span class="inline-block px-2 py-0.5 rounded text-xs bg-slate-100 text-slate-700">พร้อมเริ่มรอบส่งใหม่</span>
                        @endif
                    </p>
                    @if ($deptSubmission?->isOpen())
                        <p class="text-xs text-[#7A4A3A]/70">รอบที่ {{ $deptSubmission->submission_id }} — รอ Admin กลางรับเอกสาร</p>
                    @endif
                </div>

                @if ($deptFiles->isEmpty())
                    <p class="text-xs text-gray-500 dept-file-empty-msg mb-3">ยังไม่มีไฟล์ในรอบนี้</p>
                @else
                    <div class="flex flex-col gap-2 mb-3 dept-file-list">
                        @foreach ($deptFiles as $file)
                            <div class="file-chip dept-file-row items-start sm:items-center flex-wrap" data-file-id="{{ $file->file_id }}">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-[#8B4513]"></i>
                                <a href="{{ route('dept-submissions.files.show', $file->file_id) }}{{ $file->uploaded_at ? '?v='.$file->uploaded_at->timestamp : '' }}"
                                   target="_blank" rel="noopener noreferrer"
                                   class="dept-file-name hover:underline truncate max-w-[14rem]" title="{{ $file->original_name }}">
                                    {{ $file->original_name }}
                                </a>
                                <span class="text-xs text-gray-500 dept-file-uploaded-at">{{ $file->uploaded_at?->format('d/m/Y H:i') }}</span>
                                @if ($deptCanModify)
                                    <button type="button" class="btn-edit-dept-file text-[#8B4513] hover:text-[#6B3410] text-xs font-medium"
                                        data-file-id="{{ $file->file_id }}"
                                        data-file-name="{{ $file->original_name }}">แก้ไขชื่อ</button>
                                    <label class="btn-replace-dept-file text-[#8B4513] hover:text-[#6B3410] text-xs font-medium cursor-pointer">
                                        เปลี่ยนไฟล์
                                        <input type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="hidden dept-file-replace-input" data-file-id="{{ $file->file_id }}">
                                    </label>
                                    <button type="button" class="btn-delete-dept-file text-red-600 hover:text-red-800"
                                        data-file-id="{{ $file->file_id }}" title="ลบไฟล์">
                                        <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($deptDepartmentId)
                    <label class="file-upload-zone block cursor-pointer">
                        <input type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="hidden" id="dept-file-upload-input">
                        <span class="text-sm text-[#8B4513] font-medium flex items-center gap-1">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            อัปโหลดไฟล์ (PDF / Word) — ไม่จำกัดจำนวน
                        </span>
                    </label>
                @endif
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4 mb-6">
            <a href="{{ route('dept-admin.reviews.index') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="list-checks" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">ตรวจสอบรายวิชา</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">อนุมัติ/ไม่อนุมัติรายการที่อาจารย์ส่งมา พร้อมดูไฟล์แนบ</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('dept-admin.reports.form') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="printer" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">พิมพ์ใบรายงานสาขา</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">Export PDF/Word ตามสาขา ระดับการศึกษา และสถานะ</p>
                    </div>
                </div>
            </a>
        </div>
    @endif

    @if ($role === 'faculty_admin')
        <h3 class="text-lg font-bold text-[#5C2E1F] mb-4 flex items-center gap-2">
            <i data-lucide="building-2" class="w-5 h-5"></i> เมนู Admin กลาง
        </h3>

        <div class="form-section rounded-xl p-5 mb-6">
            <h4 class="text-base font-bold text-[#5C2E1F] mb-1 flex items-center gap-2">
                <i data-lucide="inbox" class="w-5 h-5"></i>
                รับเอกสารจากสาขาวิชา
            </h4>
            <p class="text-sm text-[#7A4A3A]/80 mb-4">
                เลือกภาคการศึกษาเพื่อดูเอกสารที่สาขาส่งเข้ามา — กดรับเอกสารทีละสาขาเมื่อตรวจสอบครบแล้ว
                (หลังรับแล้วสาขาจะไม่สามารถแก้ไขชื่อหรือไฟล์ในรอบนั้นได้)
            </p>

            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ภาคการศึกษา</label>
                    <select name="term" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[10rem]">
                        <option value="1" @selected($term === 1)>ภาคต้น</option>
                        <option value="2" @selected($term === 2)>ภาคปลาย</option>
                        <option value="3" @selected($term === 3)>ภาคการศึกษาพิเศษ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#5C2E1F] mb-1">ปีการศึกษา</label>
                    <select name="year" class="border border-amber-300 rounded-lg px-3 py-2 text-sm bg-white min-w-[8rem]">
                        @foreach ($years as $y)
                            <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-[#8B4513] text-white rounded-lg text-sm font-medium hover:bg-[#6B3410]">แสดงรายการ</button>
            </form>

            @php
                $facultyTermLabel = match ($term) {
                    1 => 'ภาคต้น',
                    2 => 'ภาคปลาย',
                    default => 'ภาคการศึกษาพิเศษ',
                };
            @endphp

            @if ($openDeptSubmissions->isEmpty())
                <p class="text-sm text-gray-500">
                    ไม่มีเอกสารรอรับจากสาขาใน{{ $facultyTermLabel }} ปีการศึกษา {{ $year }}
                </p>
            @else
                <p class="text-xs text-[#7A4A3A]/70 mb-3">
                    {{ $facultyTermLabel }} ปีการศึกษา {{ $year }} — รอรับ {{ $openDeptSubmissions->count() }} สาขา
                </p>
                <div class="space-y-3">
                    @foreach ($openDeptSubmissions as $submission)
                        @php
                            $deptName = $submission->department?->department_name ?? 'สาขา #'.$submission->department_id;
                            $submittedAt = $submission->latestSubmittedAt();
                        @endphp
                        <div class="rounded-lg border border-amber-200 bg-white p-4" data-faculty-submission="{{ $submission->submission_id }}">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-[#5C2E1F]">สาขาวิชา: {{ $deptName }}</p>
                                    <p class="text-sm text-[#7A4A3A]/80 mt-1">
                                        ส่งเมื่อ {{ \App\Support\ThaiDateTime::formatDateTime($submittedAt) }}
                                        — {{ $submission->files->count() }} ไฟล์
                                    </p>
                                    <div class="flex flex-col gap-1.5 mt-3">
                                        @foreach ($submission->files as $file)
                                            <a href="{{ route('dept-submissions.files.show', $file->file_id) }}{{ $file->uploaded_at ? '?v='.$file->uploaded_at->timestamp : '' }}" target="_blank" rel="noopener noreferrer"
                                               class="text-sm text-[#8B4513] hover:underline flex items-center gap-1.5">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                                                <span class="truncate" title="{{ $file->original_name }}">{{ $file->original_name }}</span>
                                                <span class="text-xs text-gray-500 shrink-0">{{ $file->uploaded_at?->format('d/m/Y H:i') }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                                <button type="button"
                                    class="btn-receive-dept-submission px-4 py-2 bg-green-700 text-white rounded-lg text-sm font-medium hover:bg-green-800 shrink-0"
                                    data-submission-id="{{ $submission->submission_id }}"
                                    data-department-name="{{ $deptName }}">
                                    รับเอกสาร
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="form-section rounded-xl p-5 mb-6">
            <h4 class="text-base font-bold text-[#5C2E1F] mb-1 flex items-center gap-2">
                <i data-lucide="history" class="w-5 h-5"></i>
                ประวัติการรับเอกสารจากสาขาวิชา
            </h4>
            <p class="text-sm text-[#7A4A3A]/80 mb-4">
                รายการเอกสารที่รับแล้ว แยกตามหน่วยงาน/สาขาวิชาที่ส่ง — ใช้ตัวกรองภาคการศึกษาด้านบน
            </p>

            @if ($receivedDeptSubmissionsGrouped->isEmpty())
                <p class="text-sm text-gray-500">
                    ยังไม่มีประวัติการรับเอกสารใน{{ $facultyTermLabel }} ปีการศึกษา {{ $year }}
                </p>
            @else
                <p class="text-xs text-[#7A4A3A]/70 mb-3">
                    {{ $facultyTermLabel }} ปีการศึกษา {{ $year }} — รับแล้ว {{ $receivedDeptSubmissionsGrouped->count() }} หน่วยงาน
                </p>
                <div class="space-y-4">
                    @foreach ($receivedDeptSubmissionsGrouped as $departmentId => $submissions)
                        @php
                            $first = $submissions->first();
                            $deptName = $first->department?->department_name ?? 'สาขา #'.$departmentId;
                        @endphp
                        <div class="rounded-lg border border-green-200 bg-white overflow-hidden">
                            <div class="px-4 py-3 bg-green-50 border-b border-green-200">
                                <p class="font-semibold text-[#5C2E1F] flex items-center gap-2">
                                    <i data-lucide="building" class="w-4 h-4 text-green-800"></i>
                                    {{ $deptName }}
                                </p>
                                <p class="text-xs text-[#7A4A3A]/70 mt-0.5">รับเอกสารแล้ว {{ $submissions->count() }} รอบ</p>
                            </div>
                            <div class="divide-y divide-green-100">
                                @foreach ($submissions as $submission)
                                    @php $submittedAt = $submission->latestSubmittedAt(); @endphp
                                    <div class="px-4 py-3">
                                        <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                                            <div class="text-sm text-[#7A4A3A]/90">
                                                <p>
                                                    <span class="font-medium text-[#5C2E1F]">ส่งเมื่อ</span>
                                                    {{ \App\Support\ThaiDateTime::formatDateTime($submittedAt) }}
                                                </p>
                                                <p class="mt-0.5">
                                                    <span class="font-medium text-[#5C2E1F]">รับเมื่อ</span>
                                                    {{ \App\Support\ThaiDateTime::formatDateTime($submission->received_at) }}
                                                    <span class="text-gray-500">โดย {{ $submission->receiverDisplayName() }}</span>
                                                </p>
                                            </div>
                                            <span class="inline-block px-2 py-0.5 rounded text-xs bg-green-100 text-green-800 shrink-0">
                                                รับแล้ว — {{ $submission->files->count() }} ไฟล์
                                            </span>
                                        </div>
                                        <div class="flex flex-col gap-1.5">
                                            @foreach ($submission->files as $file)
                                                <a href="{{ route('dept-submissions.files.show', $file->file_id) }}{{ $file->uploaded_at ? '?v='.$file->uploaded_at->timestamp : '' }}"
                                                   target="_blank" rel="noopener noreferrer"
                                                   class="text-sm text-[#8B4513] hover:underline flex items-center gap-1.5">
                                                    <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0"></i>
                                                    <span class="truncate" title="{{ $file->original_name }}">{{ $file->original_name }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="{{ route('faculty-admin.reviews.index', ['term' => $term, 'year' => $year]) }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="badge-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">อนุมัติระดับคณะ</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">ตรวจสอบทุกสถานะ อนุมัติ/ส่งกลับเมื่อสาขาอนุมัติแล้ว</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('faculty-admin.settings.term') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="calendar" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">กำหนดภาคการศึกษา</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">ตั้งค่าภาค/ปีการศึกษาเริ่มต้นของระบบ</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('faculty-admin.settings.programs.index') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">จัดการหลักสูตร</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">เพิ่ม/แก้ไขหลักสูตรใน tblprogram_qa</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('faculty-admin.settings.privileges.index') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="users" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">ผู้มีสิทธิใช้งาน</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">กำหนดเจ้าหน้าที่สาขา/งานบริการ และสิทธิพิเศษ</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.reports') }}" class="menu-card rounded-xl p-5 block">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="layers" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">ดูรายงานทุกสาขา</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">ดูรายงานจากทุกสาขา กรองตามสถานะ</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('grade-reports.print.summary') }}" class="menu-card rounded-xl p-5 block sm:col-span-2">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg bg-[#FAF0E6] flex items-center justify-center text-[#8B4513]">
                        <i data-lucide="file-text" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-[#5C2E1F]">พิมพ์รายงานรวม</p>
                        <p class="text-sm text-[#7A4A3A]/70 mt-1">พิมพ์รายงานรวมทุกสาขา หรือเลือกทีละสาขา</p>
                    </div>
                </div>
            </a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function() {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    function refreshLucideIcons(root) {
        if (typeof lucide === 'undefined' || !lucide.createIcons) return;
        lucide.createIcons(root ? { root } : undefined);
    }

    document.querySelectorAll('.btn-delete-report').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            const subject = btn.dataset.subject;
            if (!confirm(`ต้องการลบรายงานวิชา ${subject} หรือไม่?`)) return;

            const res = await fetch(`/api/grade-reports/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (res.ok) {
                window.location.reload();
            } else {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'ลบไม่สำเร็จ');
            }
        });
    });

    document.querySelectorAll('.file-upload-input').forEach((input) => {
        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;

            if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                alert('รองรับเฉพาะไฟล์ PDF');
                input.value = '';
                return;
            }

            const reportId = input.dataset.reportId;
            const formData = new FormData();
            formData.append('attachment', file);

            const res = await fetch(`/api/grade-reports/${reportId}/files`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            input.value = '';

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'อัปโหลดไม่สำเร็จ');
                return;
            }

            const uploaded = await res.json();
            const container = document.querySelector(`[data-report-files="${reportId}"]`);
            if (!container) return;

            container.querySelector('.file-empty-msg')?.remove();

            let list = container.querySelector('.file-list');
            if (!list) {
                list = document.createElement('div');
                list.className = 'flex flex-col gap-1.5 file-list';
                const uploadZone = container.querySelector('.file-upload-zone');
                container.insertBefore(list, uploadZone);
            }

            const chip = document.createElement('div');
            chip.className = 'file-chip';
            chip.dataset.fileId = uploaded.file_id;
            chip.innerHTML = `
                <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-[#8B4513]"></i>
                <a href="${uploaded.view_url}" target="_blank" rel="noopener noreferrer"
                   class="hover:underline truncate max-w-[10rem]" title="${uploaded.original_name}">
                    ${uploaded.original_name}
                </a>
                <button type="button" class="btn-delete-file text-red-600 hover:text-red-800 ml-1"
                    data-report-id="${reportId}" data-file-id="${uploaded.file_id}" title="ลบไฟล์">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            `;
            list.appendChild(chip);
            bindDeleteFile(chip.querySelector('.btn-delete-file'));
            refreshLucideIcons(chip);
        });
    });

    function bindDeleteFile(btn) {
        if (!btn || btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            if (!confirm('ต้องการลบไฟล์นี้หรือไม่?')) return;

            const reportId = btn.dataset.reportId;
            const fileId = btn.dataset.fileId;
            const res = await fetch(`/api/grade-reports/${reportId}/files/${fileId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'ลบไฟล์ไม่สำเร็จ');
                return;
            }

            btn.closest('.file-chip')?.remove();
            const container = document.querySelector(`[data-report-files="${reportId}"]`);
            const list = container?.querySelector('.file-list');
            if (list && !list.children.length) {
                list.remove();
                const empty = document.createElement('p');
                empty.className = 'text-xs text-gray-500 file-empty-msg';
                empty.textContent = 'ยังไม่มีไฟล์แนบ';
                const uploadZone = container.querySelector('.file-upload-zone');
                container.insertBefore(empty, uploadZone);
            }
        });
    }

    document.querySelectorAll('.btn-delete-file').forEach(bindDeleteFile);

    const deptBox = document.querySelector('[data-dept-submission]');
    const deptUploadInput = document.getElementById('dept-file-upload-input');

    function deptApiMessage(data, fallback) {
        if (data?.message) return data.message;
        const errors = data?.errors;
        if (errors && typeof errors === 'object') {
            const first = Object.values(errors).flat()[0];
            if (first) return first;
        }
        return fallback;
    }

    function findDeptFileRow(el) {
        return el?.closest('.dept-file-row') ?? null;
    }

    function ensureDeptFileList() {
        if (!deptBox) return null;
        deptBox.querySelector('.dept-file-empty-msg')?.remove();
        let list = deptBox.querySelector('.dept-file-list');
        if (!list) {
            list = document.createElement('div');
            list.className = 'flex flex-col gap-2 mb-3 dept-file-list';
            const uploadZone = deptBox.querySelector('.file-upload-zone');
            if (uploadZone) {
                deptBox.insertBefore(list, uploadZone);
            } else {
                deptBox.appendChild(list);
            }
        }
        return list;
    }

    function renderDeptFileRow(file) {
        const canModify = deptBox?.dataset.canModify === '1';
        const row = document.createElement('div');
        row.className = 'file-chip dept-file-row items-start sm:items-center flex-wrap';
        row.dataset.fileId = file.file_id;
        const actions = canModify ? `
            <button type="button" class="btn-edit-dept-file text-[#8B4513] hover:text-[#6B3410] text-xs font-medium"
                data-file-id="${file.file_id}" data-file-name="${file.original_name}">แก้ไขชื่อ</button>
            <label class="btn-replace-dept-file text-[#8B4513] hover:text-[#6B3410] text-xs font-medium cursor-pointer">
                เปลี่ยนไฟล์
                <input type="file" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                    class="hidden dept-file-replace-input" data-file-id="${file.file_id}">
            </label>
            <button type="button" class="btn-delete-dept-file text-red-600 hover:text-red-800"
                data-file-id="${file.file_id}" title="ลบไฟล์">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
            </button>
        ` : '';
        row.innerHTML = `
            <i data-lucide="file-text" class="w-3.5 h-3.5 shrink-0 text-[#8B4513]"></i>
            <a href="${file.view_url}" target="_blank" rel="noopener noreferrer"
               class="dept-file-name hover:underline truncate max-w-[14rem]" title="${file.original_name}">
                ${file.original_name}
            </a>
            <span class="text-xs text-gray-500 dept-file-uploaded-at">${file.uploaded_at || ''}</span>
            ${actions}
        `;
        if (canModify) {
            bindDeptDelete(row.querySelector('.btn-delete-dept-file'));
            bindDeptEdit(row.querySelector('.btn-edit-dept-file'));
        }
        return row;
    }

    function syncDeptEmptyState() {
        if (!deptBox) return;
        const list = deptBox.querySelector('.dept-file-list');
        if (list && list.children.length) return;
        list?.remove();
        if (!deptBox.querySelector('.dept-file-empty-msg')) {
            const empty = document.createElement('p');
            empty.className = 'text-xs text-gray-500 dept-file-empty-msg mb-3';
            empty.textContent = 'ยังไม่มีไฟล์ในรอบนี้';
            const uploadZone = deptBox.querySelector('.file-upload-zone');
            if (uploadZone) {
                deptBox.insertBefore(empty, uploadZone);
            }
        }
    }

    if (deptUploadInput && deptBox) {
        deptUploadInput.addEventListener('change', async () => {
            const file = deptUploadInput.files?.[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('attachment', file);
            formData.append('department_id', deptBox.dataset.departmentId);
            formData.append('term', deptBox.dataset.term);
            formData.append('year', deptBox.dataset.year);

            const res = await fetch('/api/dept-submissions/files', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            deptUploadInput.value = '';

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(deptApiMessage(data, 'อัปโหลดไม่สำเร็จ'));
                return;
            }

            const uploaded = await res.json();
            deptBox.dataset.canModify = '1';
            const row = renderDeptFileRow(uploaded);
            const list = ensureDeptFileList();
            if (list) {
                list.appendChild(row);
                refreshLucideIcons(row);
            }
        });
    }

    function bindDeptDelete(btn) {
        if (!btn || btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            if (!confirm('ต้องการลบไฟล์นี้หรือไม่?')) return;
            const row = findDeptFileRow(btn);
            const fileId = btn.dataset.fileId;
            const res = await fetch(`/api/dept-submissions/files/${fileId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(deptApiMessage(data, 'ลบไฟล์ไม่สำเร็จ'));
                if (res.status === 422) window.location.reload();
                return;
            }
            row?.remove();
            syncDeptEmptyState();
        });
    }

    function bindDeptEdit(btn) {
        if (!btn || btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', async () => {
            const row = findDeptFileRow(btn);
            const newName = prompt('ชื่อไฟล์', btn.dataset.fileName || '');
            if (newName === null || newName.trim() === '') return;

            const res = await fetch(`/api/dept-submissions/files/${btn.dataset.fileId}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ original_name: newName.trim() }),
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(deptApiMessage(data, 'แก้ไขชื่อไม่สำเร็จ'));
                if (res.status === 422) window.location.reload();
                return;
            }

            const data = await res.json();
            const link = row?.querySelector('.dept-file-name');
            if (link) {
                link.textContent = data.original_name;
                link.title = data.original_name;
            }
            btn.dataset.fileName = data.original_name;
        });
    }

    function updateDeptFileRow(row, data) {
        if (!row || !data) return;
        const link = row.querySelector('.dept-file-name');
        if (link) {
            link.textContent = data.original_name;
            link.title = data.original_name;
            link.href = data.view_url;
        }
        const uploadedAt = row.querySelector('.dept-file-uploaded-at');
        if (uploadedAt) {
            uploadedAt.textContent = data.uploaded_at || '';
            uploadedAt.classList.add('text-green-700', 'font-medium');
            setTimeout(() => uploadedAt.classList.remove('text-green-700', 'font-medium'), 2500);
        }
        const editBtn = row.querySelector('.btn-edit-dept-file');
        if (editBtn) editBtn.dataset.fileName = data.original_name;
    }

    async function handleDeptFileReplace(input) {
        const file = input.files?.[0];
        if (!file) return;

        const row = findDeptFileRow(input);
        const formData = new FormData();
        formData.append('attachment', file);

        const res = await fetch(`/api/dept-submissions/files/${input.dataset.fileId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        });

        input.value = '';

        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            alert(deptApiMessage(data, 'เปลี่ยนไฟล์ไม่สำเร็จ'));
            if (res.status === 422) window.location.reload();
            return;
        }

        const data = await res.json();
        updateDeptFileRow(row, data);
    }

    if (deptBox) {
        deptBox.addEventListener('change', (event) => {
            const input = event.target;
            if (!(input instanceof HTMLInputElement)) return;
            if (!input.classList.contains('dept-file-replace-input')) return;
            handleDeptFileReplace(input);
        });
    }

    document.querySelectorAll('.btn-delete-dept-file').forEach(bindDeptDelete);
    document.querySelectorAll('.btn-edit-dept-file').forEach(bindDeptEdit);

    document.querySelectorAll('.btn-receive-dept-submission').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const deptName = btn.dataset.departmentName || 'สาขานี้';
            if (!confirm(`ยืนยันรับเอกสารจากสาขา "${deptName}" หรือไม่?\n\nหลังรับแล้วสาขาจะไม่สามารถแก้ไขชื่อหรือไฟล์ในรอบนี้ได้`)) return;

            btn.disabled = true;

            const res = await fetch(`/api/faculty-admin/dept-submissions/${btn.dataset.submissionId}/receive`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                alert(data.message || data.errors?.submission?.[0] || 'รับเอกสารไม่สำเร็จ');
                btn.disabled = false;
                return;
            }

            document.querySelector(`[data-faculty-submission="${btn.dataset.submissionId}"]`)?.remove();
        });
    });

    refreshLucideIcons();
})();
</script>
@endpush
