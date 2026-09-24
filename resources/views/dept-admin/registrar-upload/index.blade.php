@extends('layouts.scigrad')

@section('title', 'อัปโหลด มข.11 (สาขาวิชา) — Admin สาขา')

@section('subnav')
<span class="text-gray-400">/</span>
<a href="{{ route('dept-admin.reviews.index') }}" class="text-[#8B4513] hover:underline">รายวิชาที่อาจารย์ส่งเกรด</a>
<span class="text-gray-400">/</span>
<span class="text-[#5C2E1F] font-medium">อัปโหลด มข.11</span>
@endsection

@push('styles')
<style>
    .upload-scope-card {
        border: 1px solid #e8cdb5;
        background: linear-gradient(180deg, #fffaf5 0%, #fff 100%);
        border-radius: 1rem;
        padding: 1.1rem 1.25rem;
    }
    .upload-hint-code {
        font-size: 0.7rem;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 0.35rem;
        padding: 0.1rem 0.35rem;
        color: #9a3412;
    }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="rounded-2xl overflow-hidden border border-amber-200 bg-white shadow-sm">
        <div class="px-6 py-5 bg-gradient-to-r from-[#8B4513] via-[#A0522D] to-[#C4725C] text-white">
            <div class="flex items-start gap-3">
                <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                    <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold leading-tight">อัปโหลด มข.11 (สาขาวิชา)</h2>
                    <p class="text-sm text-white/85 mt-1">
                        อัปโหลดหลายไฟล์ในครั้งเดียว — เลือกภาค/ปีการศึกษาให้ตรงกับรายวิชาที่ต้องการแนบ
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <div class="upload-scope-card space-y-4">
                <div class="flex items-center gap-2 text-sm font-semibold text-[#5C2E1F]">
                    <i data-lucide="calendar-range" class="w-4 h-4 text-[#8B4513]"></i>
                    ภาค / ปีการศึกษา (ค่าเริ่มต้นจากระบบ — แก้ไขได้)
                </div>
                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-[#7A4A3A] mb-1" for="upload-term">ภาคการศึกษา *</label>
                        <select id="upload-term" class="w-full border border-amber-300 rounded-xl px-3 py-2.5 text-sm bg-white">
                            <option value="1" @selected((int) $term === 1)>ภาคต้น</option>
                            <option value="2" @selected((int) $term === 2)>ภาคปลาย</option>
                            <option value="3" @selected((int) $term === 3)>ภาคการศึกษาพิเศษ</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#7A4A3A] mb-1" for="upload-year">ปีการศึกษา *</label>
                        <select id="upload-year" class="w-full border border-amber-300 rounded-xl px-3 py-2.5 text-sm bg-white">
                            @foreach ($years as $y)
                                <option value="{{ $y }}" @selected((int) $year === (int) $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-[#7A4A3A] mb-1" for="upload-department">สาขาวิชา</label>
                        <select id="upload-department" class="w-full border border-amber-300 rounded-xl px-3 py-2.5 text-sm bg-white">
                            @if ($departments->count() > 1)
                                <option value="">ทุกสาขาที่มีสิทธิ์</option>
                            @endif
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->department_id }}" @selected(($departmentId ?? null) == $dept->department_id)>
                                    {{ $dept->department_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <p class="text-xs text-[#7A4A3A]/80 leading-relaxed">
                    ระบบจับคู่ไฟล์กับรายวิชาตามภาค/ปีที่เลือกด้านบน
                    หากต้องการอัปโหลดไปภาคอื่น ให้เปลี่ยนค่าก่อนเลือกไฟล์
                </p>
            </div>

            <div id="registrar-bulk-upload" class="space-y-4"
                data-preview-url="{{ route('dept-admin.reviews.registrar-files.preview') }}"
                data-upload-url="{{ route('dept-admin.reviews.registrar-files.store') }}">
                <div class="rounded-xl border border-amber-100 bg-amber-50/50 px-4 py-3 text-xs text-[#7A4A3A] space-y-1">
                    <p class="font-semibold text-[#5C2E1F] inline-flex items-center gap-1.5">
                        <i data-lucide="info" class="w-3.5 h-3.5"></i>
                        รูปแบบชื่อไฟล์
                    </p>
                    <p>
                        ใช้ชื่อไฟล์แบบ
                        <code class="upload-hint-code">รหัสวิชา-กลุ่ม.pdf</code>
                        เช่น
                        <code class="upload-hint-code">SC101011-01.pdf</code>
                    </p>
                    <p>อัปโหลดได้เมื่อรายวิชายังเป็นบันทึกแล้ว นำเข้าที่ประชุม หรือผ่านที่ประชุมสาขา (คณะยังไม่ตรวจ) · แนะนำไม่เกิน 20 ไฟล์ต่อครั้ง</p>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[16rem] flex-1">
                        <label class="block text-xs font-medium text-[#7A4A3A] mb-1" for="registrar-files-input">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="file-plus-2" class="w-3.5 h-3.5 text-[#8B4513]"></i>
                                เลือกไฟล์ PDF
                            </span>
                        </label>
                        <input type="file" id="registrar-files-input" accept="application/pdf,.pdf" multiple
                            class="block w-full text-sm text-[#5C2E1F] file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-amber-100 file:text-[#5C2E1F] file:text-sm file:font-medium">
                    </div>
                    <button type="button" id="registrar-upload-btn" disabled
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#8B4513] text-white rounded-xl text-sm font-semibold hover:bg-[#6B3410] disabled:opacity-50 disabled:cursor-not-allowed shadow-sm">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        อัปโหลด
                    </button>
                    <button type="button" id="registrar-clear-btn"
                        class="inline-flex items-center gap-2 px-4 py-2.5 border border-amber-300 rounded-xl text-sm text-[#5C2E1F] hover:bg-amber-50">
                        <i data-lucide="eraser" class="w-4 h-4"></i>
                        ล้างรายการ
                    </button>
                </div>

                <div id="registrar-preview-wrap" class="hidden overflow-x-auto rounded-xl border border-amber-100">
                    <div class="px-3 py-2 bg-amber-50 border-b border-amber-100 text-xs font-semibold text-[#5C2E1F]">
                        พรีวิวก่อนอัปโหลด
                    </div>
                    <table class="w-full text-xs min-w-[640px]">
                        <thead class="bg-white">
                            <tr class="text-[#7A4A3A]">
                                <th class="px-3 py-2 text-left font-medium">ชื่อไฟล์ต้นฉบับ</th>
                                <th class="px-3 py-2 text-left font-medium">รหัส / กลุ่ม</th>
                                <th class="px-3 py-2 text-left font-medium">วิชาที่จับคู่</th>
                                <th class="px-3 py-2 text-right font-medium">ขนาด</th>
                                <th class="px-3 py-2 text-left font-medium">สถานะจับคู่</th>
                            </tr>
                        </thead>
                        <tbody id="registrar-preview-body"></tbody>
                    </table>
                </div>

                <div id="registrar-result-wrap" class="hidden overflow-x-auto rounded-xl border border-amber-100">
                    <div class="px-3 py-2 bg-amber-50 border-b border-amber-100 text-xs font-semibold text-[#5C2E1F]">
                        ผลการอัปโหลด
                    </div>
                    <p id="registrar-result-summary" class="px-3 pt-2 text-xs text-[#7A4A3A]"></p>
                    <table class="w-full text-xs min-w-[720px]">
                        <thead class="bg-white">
                            <tr class="text-[#7A4A3A]">
                                <th class="px-3 py-2 text-left font-medium">ชื่อไฟล์</th>
                                <th class="px-3 py-2 text-left font-medium">ผล</th>
                                <th class="px-3 py-2 text-left font-medium">เหตุผล / ไฟล์ในระบบ</th>
                            </tr>
                        </thead>
                        <tbody id="registrar-result-body"></tbody>
                    </table>
                </div>

                <p id="registrar-upload-error" class="hidden text-sm text-red-700 flex items-start gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 shrink-0"></i>
                    <span id="registrar-upload-error-text"></span>
                </p>
            </div>

            <div class="flex flex-wrap gap-3 pt-1 border-t border-amber-100">
                <a href="{{ route('dept-admin.reviews.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 border border-amber-300 rounded-xl text-sm text-[#5C2E1F] hover:bg-amber-50">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    กลับหน้ารายวิชา
                </a>
                <a href="{{ route('dept-admin.reg-grade-status.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 border border-amber-300 rounded-xl text-sm text-[#5C2E1F] hover:bg-amber-50">
                    <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                    รายงานสถานะการส่ง
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const uploadBox = document.getElementById('registrar-bulk-upload');
    const fileInput = document.getElementById('registrar-files-input');
    const uploadBtn = document.getElementById('registrar-upload-btn');
    const clearBtn = document.getElementById('registrar-clear-btn');
    const previewWrap = document.getElementById('registrar-preview-wrap');
    const previewBody = document.getElementById('registrar-preview-body');
    const resultWrap = document.getElementById('registrar-result-wrap');
    const resultBody = document.getElementById('registrar-result-body');
    const resultSummary = document.getElementById('registrar-result-summary');
    const uploadError = document.getElementById('registrar-upload-error');
    const uploadErrorText = document.getElementById('registrar-upload-error-text');
    const termEl = document.getElementById('upload-term');
    const yearEl = document.getElementById('upload-year');
    const deptEl = document.getElementById('upload-department');
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    if (!uploadBox) return;

    const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[ch]));

    const firstError = (data) => {
        const errors = data?.errors;
        if (errors && typeof errors === 'object') {
            const first = Object.values(errors)[0];
            if (Array.isArray(first) && first[0]) return first[0];
        }
        return data?.message || null;
    };

    const formatSize = (bytes) => {
        const n = Number(bytes) || 0;
        if (n < 1024) return n + ' B';
        if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
        return (n / 1048576).toFixed(1) + ' MB';
    };

    const setError = (message) => {
        if (!uploadError) return;
        if (!message) {
            uploadError.classList.add('hidden');
            if (uploadErrorText) uploadErrorText.textContent = '';
            return;
        }
        if (uploadErrorText) uploadErrorText.textContent = message;
        else uploadError.textContent = message;
        uploadError.classList.remove('hidden');
        if (window.lucide?.createIcons) window.lucide.createIcons();
    };

    let selectedFiles = [];

    const renderPreview = (rows) => {
        previewBody.innerHTML = '';
        (rows || []).forEach((row, index) => {
            const file = selectedFiles[index];
            const tr = document.createElement('tr');
            tr.className = 'border-t border-amber-100 ' + (row.ok ? 'bg-green-50/60' : 'bg-red-50/70');
            const matched = row.matched
                ? `${row.subject_code || ''} ${row.subject || ''}`.trim()
                : 'ไม่พบ';
            const codeSec = (row.course_code || '—') + (row.section ? '-' + row.section : '');
            tr.innerHTML = `
                <td class="px-3 py-2">${esc(row.original_name || '')}</td>
                <td class="px-3 py-2 font-medium">${esc(codeSec)}</td>
                <td class="px-3 py-2">${esc(matched)}</td>
                <td class="px-3 py-2 text-right">${esc(formatSize(file?.size))}</td>
                <td class="px-3 py-2">${esc(row.ok ? 'จับคู่ได้' : (row.reason || 'จับคู่ไม่ได้'))}</td>
            `;
            previewBody.appendChild(tr);
        });
        previewWrap.classList.toggle('hidden', previewBody.children.length === 0);
        uploadBtn.disabled = selectedFiles.length === 0;
    };

    const previewFiles = async () => {
        setError('');
        resultWrap.classList.add('hidden');
        if (selectedFiles.length === 0) {
            previewWrap.classList.add('hidden');
            previewBody.innerHTML = '';
            uploadBtn.disabled = true;
            return;
        }
        try {
            const body = {
                term: Number(termEl.value),
                year: Number(yearEl.value),
                filenames: selectedFiles.map((f) => f.name),
            };
            if (deptEl.value) body.department_id = Number(deptEl.value);
            const res = await fetch(uploadBox.dataset.previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            if (!res.ok) {
                setError(firstError(data) || 'ไม่สามารถตรวจสอบชื่อไฟล์ได้');
                return;
            }
            renderPreview(data.results || []);
        } catch (e) {
            setError('ไม่สามารถตรวจสอบชื่อไฟล์ได้');
        }
    };

    const syncUrlParams = () => {
        const url = new URL(window.location.href);
        url.searchParams.set('term', termEl.value);
        url.searchParams.set('year', yearEl.value);
        if (deptEl.value) url.searchParams.set('department_id', deptEl.value);
        else url.searchParams.delete('department_id');
        window.history.replaceState({}, '', url);
    };

    [termEl, yearEl, deptEl].forEach((el) => {
        el?.addEventListener('change', () => {
            syncUrlParams();
            if (selectedFiles.length) previewFiles();
        });
    });

    fileInput?.addEventListener('change', () => {
        selectedFiles = Array.from(fileInput.files || []);
        previewFiles();
    });

    clearBtn?.addEventListener('click', () => {
        selectedFiles = [];
        if (fileInput) fileInput.value = '';
        previewBody.innerHTML = '';
        previewWrap.classList.add('hidden');
        resultBody.innerHTML = '';
        resultWrap.classList.add('hidden');
        uploadBtn.disabled = true;
        setError('');
    });

    uploadBtn?.addEventListener('click', async () => {
        if (selectedFiles.length === 0) return;
        uploadBtn.disabled = true;
        setError('');
        const form = new FormData();
        form.append('term', termEl.value);
        form.append('year', yearEl.value);
        if (deptEl.value) form.append('department_id', deptEl.value);
        selectedFiles.forEach((file) => form.append('attachments[]', file));
        try {
            const res = await fetch(uploadBox.dataset.uploadUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: form,
            });
            const data = await res.json();
            if (!res.ok) {
                setError(firstError(data) || 'อัปโหลดไม่สำเร็จ');
                uploadBtn.disabled = false;
                return;
            }
            resultBody.innerHTML = '';
            (data.results || []).forEach((row) => {
                const tr = document.createElement('tr');
                tr.className = 'border-t border-amber-100 ' + (row.ok ? 'bg-green-50/70' : 'bg-red-50/80');
                const statusClass = row.ok ? 'text-green-800' : 'text-red-800';
                const nameTd = document.createElement('td');
                nameTd.className = 'px-3 py-2';
                nameTd.textContent = row.original_name || '';
                const statusTd = document.createElement('td');
                statusTd.className = 'px-3 py-2 font-semibold ' + statusClass;
                statusTd.textContent = row.ok ? 'สำเร็จ' : 'ไม่สำเร็จ';
                const detailTd = document.createElement('td');
                detailTd.className = 'px-3 py-2';
                if (row.ok) {
                    detailTd.append(row.stored_name ? `เก็บเป็น ${row.stored_name}` : 'อัปโหลดสำเร็จ');
                    if (row.view_url) {
                        const link = document.createElement('a');
                        link.href = row.view_url;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.className = 'font-medium underline ml-1 text-[#8B4513]';
                        link.textContent = 'เปิดดู PDF';
                        detailTd.append(' ', link);
                    }
                } else {
                    detailTd.textContent = row.reason || '';
                }
                tr.append(nameTd, statusTd, detailTd);
                resultBody.appendChild(tr);
            });
            resultSummary.textContent = `สำเร็จ ${data.ok_count ?? 0} ไฟล์ · ไม่สำเร็จ ${data.fail_count ?? 0} ไฟล์`;
            resultWrap.classList.remove('hidden');
        } catch (e) {
            setError('อัปโหลดไม่สำเร็จ');
        }
        uploadBtn.disabled = selectedFiles.length === 0;
    });
})();
</script>
@endpush
