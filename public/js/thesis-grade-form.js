(() => {
    const root = document.getElementById('thesis-form-root');
    if (!root) return;

    const editable = root.dataset.editable === '1';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const initial = window.THESIS_FORM || { students: [], files: [], oldStudents: [], uncertainCourse: {} };
    let students = (initial.oldStudents && initial.oldStudents.length)
        ? initial.oldStudents.map(normalizeStudent)
        : (initial.students || []).map(normalizeStudent);
    let files = initial.files || [];
    let courseUncertain = { ...(initial.uncertainCourse || {}) };
    let step = Number(root.dataset.initialStep || 1);

    const listEl = document.getElementById('student-list');
    const summaryEl = document.getElementById('student-summary');
    const tsFilesEl = document.getElementById('ts-files');
    const s0SlotsEl = document.getElementById('s0-slots');
    const form = document.getElementById('thesis-form');

    function normalizeStudent(row) {
        const prefix = row.name_prefix || '';
        const first = row.first_name || '';
        const last = row.last_name || '';
        const composed = [prefix, first, last].filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
        const uncertain = (row.uncertain_fields && typeof row.uncertain_fields === 'object')
            ? { ...row.uncertain_fields }
            : {};
        return {
            id: row.id || row.student_id || '',
            student_code: row.student_code || '',
            name_prefix: prefix,
            first_name: first,
            last_name: last,
            student_name: composed || row.student_name || '',
            degree: row.degree === 'doctoral' ? 'doctoral' : 'master',
            thesis_terms_count: Number(row.thesis_terms_count || 1),
            proposal_approved: !!row.proposal_approved && row.proposal_approved !== '0',
            grade: (row.grade || 'S').toString().toUpperCase(),
            credits_registered: row.credits_registered === null || row.credits_registered === undefined ? '' : row.credits_registered,
            credits_passed: row.credits_passed === null || row.credits_passed === undefined ? '' : row.credits_passed,
            progress_credits: row.progress_credits === null || row.progress_credits === undefined ? '' : row.progress_credits,
            completed: !!row.completed && row.completed !== '0',
            defense_date: row.defense_date || '',
            note: row.note || '',
            uncertain_fields: uncertain,
        };
    }

    function reviewClass(uncertain, key) {
        return uncertain && uncertain[key] ? 'field-needs-review' : '';
    }

    function reviewHintHtml(uncertain, key) {
        if (!uncertain || !uncertain[key]) return '';
        return `<span class="field-hint-review">${escapeHtml(uncertain[key])}</span>`;
    }

    function clearStudentUncertain(index, key) {
        if (!students[index] || !students[index].uncertain_fields) return;
        delete students[index].uncertain_fields[key];
    }

    function clearCourseUncertain(key) {
        if (!courseUncertain || !courseUncertain[key]) return;
        delete courseUncertain[key];
        applyCourseUncertainMarks();
        updateUncertainBanner();
    }

    function applyCourseUncertainMarks() {
        document.querySelectorAll('[data-review-field]').forEach((el) => {
            const key = el.dataset.reviewField;
            const msg = courseUncertain[key] || '';
            el.classList.toggle('field-needs-review', !!msg);
            const hint = document.querySelector(`[data-review-hint="${key}"]`);
            if (hint) {
                hint.textContent = msg;
                hint.classList.toggle('hidden', !msg);
            }
        });
    }

    function updateCourseContext() {
        const code = (document.getElementById('subject_code')?.value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        const subject = document.getElementById('subject')?.value || '';
        const sectionRaw = document.querySelector('[name="section"]')?.value || '01';
        const section = String(sectionRaw).replace(/\D/g, '') || '1';
        const sectionPad = section.padStart(2, '0');
        const label = [
            code || 'ยังไม่มีรหัสวิชา',
            subject || 'ยังไม่เลือกชื่อวิชา',
            `กลุ่ม ${sectionPad}`,
        ].join(' · ');

        const main = document.getElementById('course-context-text');
        if (main) main.textContent = label;
        document.querySelectorAll('.course-context-text').forEach((el) => {
            el.textContent = label;
        });
        const inline = document.getElementById('subject-inline-label');
        if (inline) inline.textContent = label;
    }

    function updateUncertainBanner() {
        const banner = document.getElementById('uncertain-review-banner');
        if (!banner) return;
        const hasCourse = Object.keys(courseUncertain || {}).length > 0;
        const hasStudent = students.some((s) => s.uncertain_fields && Object.keys(s.uncertain_fields).length > 0);
        banner.classList.toggle('hidden', !(hasCourse || hasStudent));
    }

    function isOverdue(s) {
        if (s.proposal_approved) return false;
        return Number(s.thesis_terms_count || 0) >= (s.degree === 'doctoral' ? 4 : 2);
    }

    function isS0(s) {
        const credits = s.credits_passed === '' || s.credits_passed === null || s.credits_passed === undefined
            ? s.progress_credits
            : s.credits_passed;
        return String(s.grade || '').toUpperCase() === 'S' && (credits === '' || credits === null || credits === undefined || Number(credits) === 0);
    }

    function needsS0(s) {
        return isOverdue(s) && isS0(s);
    }

    function hasS0(s) {
        return files.some((f) => f.file_type === 's0_letter' && String(f.student_id) === String(s.id));
    }

    function tsFiles() {
        return files.filter((f) => f.file_type === 'ts_report');
    }

    function goStep(n) {
        step = Math.max(1, Math.min(3, n));
        document.getElementById('form-step').value = String(step);
        document.querySelectorAll('.thesis-panel').forEach((p) => {
            p.classList.toggle('active', Number(p.dataset.step) === step);
        });
        document.querySelectorAll('.thesis-step').forEach((el) => {
            const s = Number(el.dataset.goStep);
            el.classList.toggle('active', s === step);
            el.classList.toggle('done', s < step);
        });
        document.getElementById('prev-step').style.visibility = step === 1 ? 'hidden' : 'visible';
        const next = document.getElementById('next-step');
        if (next) next.style.display = step === 3 ? 'none' : '';
        renderTsName();
        renderFiles();
        updateCourseContext();
    }

    function collectFromDom() {
        if (!listEl) return;
        listEl.querySelectorAll('[data-student-index]').forEach((card) => {
            const i = Number(card.dataset.studentIndex);
            if (!students[i]) return;
            students[i].student_code = card.querySelector('[data-f="student_code"]')?.value || '';
            students[i].name_prefix = card.querySelector('[data-f="name_prefix"]')?.value || '';
            students[i].first_name = card.querySelector('[data-f="first_name"]')?.value || '';
            students[i].last_name = card.querySelector('[data-f="last_name"]')?.value || '';
            students[i].student_name = [students[i].name_prefix, students[i].first_name, students[i].last_name]
                .filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();
            students[i].degree = card.querySelector('[data-f="degree"]')?.value || 'master';
            students[i].thesis_terms_count = Number(card.querySelector('[data-f="thesis_terms_count"]')?.value || students[i].thesis_terms_count || 1);
            students[i].proposal_approved = !!card.querySelector('[data-f="proposal_approved"]')?.checked;
            students[i].grade = (card.querySelector('[data-f="grade"]')?.value || 'S').toUpperCase();
            students[i].credits_registered = card.querySelector('[data-f="credits_registered"]')?.value ?? '';
            students[i].credits_passed = card.querySelector('[data-f="credits_passed"]')?.value ?? '';
            students[i].progress_credits = students[i].credits_passed;
            students[i].completed = !!card.querySelector('[data-f="completed"]')?.checked;
            students[i].defense_date = card.querySelector('[data-f="defense_date"]')?.value || '';
            students[i].note = card.querySelector('[data-f="note"]')?.value || '';
        });
    }

    function renderStudents() {
        if (!listEl) return;
        if (students.length === 0) {
            listEl.innerHTML = '<p class="text-sm text-[#7A4A3A]/70">ยังไม่มีรายชื่อ — เพิ่มทีละคน หรือวางจาก Excel</p>';
            renderSummary();
            renderFiles();
            return;
        }

        listEl.innerHTML = students.map((s, i) => {
            const overdue = isOverdue(s);
            const s0 = needsS0(s);
            const cls = s.completed && s.defense_date ? 'is-ready' : (overdue ? 'is-overdue' : '');
            const badge = overdue
                ? `<span class="text-xs font-semibold text-red-700">เลยกำหนดเค้าโครง${s0 ? ' · ควรพิจารณา S=0' : ''}</span>`
                : (s.proposal_approved ? '<span class="text-xs font-semibold text-green-700">อนุมัติเค้าโครงแล้ว</span>' : '<span class="text-xs text-amber-800">อยู่ในกำหนด</span>');
            const ro = editable ? '' : 'disabled';
            const u = s.uncertain_fields || {};
            return `
            <div class="student-card ${cls} p-4" data-student-index="${i}">
                <input type="hidden" name="students[${i}][id]" value="${escapeHtml(s.id)}">
                <input type="hidden" name="students[${i}][thesis_terms_count]" data-f="thesis_terms_count" value="${escapeHtml(s.thesis_terms_count || 1)}">
                <input type="hidden" name="students[${i}][student_name]" value="${escapeHtml(s.student_name)}">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <p class="text-sm font-semibold text-[#5C2E1F]">นักศึกษาคนที่ ${i + 1}</p>
                    <div class="flex items-center gap-2">${badge}
                        ${editable ? `<button type="button" class="text-xs text-red-700 hover:underline" data-remove="${i}">ลบ</button>` : ''}
                    </div>
                </div>
                <div class="grid md:grid-cols-4 gap-3">
                    <label class="text-xs text-[#7A4A3A]">รหัสนักศึกษา
                        <input ${ro} data-f="student_code" name="students[${i}][student_code]" value="${escapeHtml(s.student_code)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white ${reviewClass(u, 'student_code')}" placeholder="677020018-0">
                        ${reviewHintHtml(u, 'student_code')}
                    </label>
                    <label class="text-xs text-[#7A4A3A]">คำนำหน้าชื่อ
                        <input ${ro} data-f="name_prefix" name="students[${i}][name_prefix]" value="${escapeHtml(s.name_prefix)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white ${reviewClass(u, 'name_prefix')}" placeholder="นาย / นางสาว / Mr.">
                        ${reviewHintHtml(u, 'name_prefix')}
                    </label>
                    <label class="text-xs text-[#7A4A3A]">ชื่อ
                        <input ${ro} data-f="first_name" name="students[${i}][first_name]" value="${escapeHtml(s.first_name)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white ${reviewClass(u, 'first_name')}">
                        ${reviewHintHtml(u, 'first_name')}
                    </label>
                    <label class="text-xs text-[#7A4A3A]">สกุล
                        <input ${ro} data-f="last_name" name="students[${i}][last_name]" value="${escapeHtml(s.last_name)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white ${reviewClass(u, 'last_name')}">
                        ${reviewHintHtml(u, 'last_name')}
                    </label>
                    <label class="text-xs text-[#7A4A3A]">ระดับ
                        <select ${ro} data-f="degree" name="students[${i}][degree]" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white">
                            <option value="master" ${s.degree === 'master' ? 'selected' : ''}>ปริญญาโท</option>
                            <option value="doctoral" ${s.degree === 'doctoral' ? 'selected' : ''}>ปริญญาเอก</option>
                        </select>
                    </label>
                    <label class="text-xs text-[#7A4A3A]">หน่วยกิตที่ลง
                        <input ${ro} type="number" min="0" step="0.5" data-f="credits_registered" name="students[${i}][credits_registered]" value="${escapeHtml(s.credits_registered)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white ${reviewClass(u, 'credits_registered')}">
                        ${reviewHintHtml(u, 'credits_registered')}
                    </label>
                    <label class="text-xs text-[#7A4A3A]">หน่วยกิตที่ผ่าน
                        <input ${ro} type="number" min="0" step="0.5" data-f="credits_passed" name="students[${i}][credits_passed]" value="${escapeHtml(s.credits_passed)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white ${reviewClass(u, 'credits_passed')}">
                        ${reviewHintHtml(u, 'credits_passed')}
                    </label>
                    <label class="text-xs text-[#7A4A3A]">เกรด
                        <input ${ro} data-f="grade" name="students[${i}][grade]" value="${escapeHtml(s.grade)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white ${reviewClass(u, 'grade')}" placeholder="S / U / I">
                        ${reviewHintHtml(u, 'grade')}
                    </label>
                    <label class="text-xs text-[#7A4A3A] md:col-span-2">หมายเหตุ
                        <input ${ro} data-f="note" name="students[${i}][note]" value="${escapeHtml(s.note)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white">
                    </label>
                    <label class="text-xs text-[#7A4A3A] flex items-center gap-2 mt-6">
                        <input ${ro} type="checkbox" data-f="proposal_approved" name="students[${i}][proposal_approved]" value="1" ${s.proposal_approved ? 'checked' : ''}>
                        อนุมัติเค้าโครงแล้ว
                    </label>
                    <label class="text-xs text-[#7A4A3A] flex items-center gap-2 mt-6">
                        <input ${ro} type="checkbox" data-f="completed" name="students[${i}][completed]" value="1" ${s.completed ? 'checked' : ''}>
                        ครบตามหลักสูตร
                    </label>
                    <label class="text-xs text-[#7A4A3A]">วันที่สอบวิทยานิพนธ์
                        <input ${ro} type="date" data-f="defense_date" name="students[${i}][defense_date]" value="${escapeHtml(s.defense_date)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white">
                    </label>
                </div>
            </div>`;
        }).join('');

        listEl.querySelectorAll('[data-remove]').forEach((btn) => {
            btn.addEventListener('click', () => {
                collectFromDom();
                students.splice(Number(btn.dataset.remove), 1);
                renderStudents();
            });
        });
        listEl.querySelectorAll('input, select').forEach((el) => {
            const onEdit = () => {
                const card = el.closest('[data-student-index]');
                const idx = card ? Number(card.dataset.studentIndex) : -1;
                const key = el.getAttribute('data-f');
                if (idx >= 0 && key) clearStudentUncertain(idx, key);
                collectFromDom();
                renderStudents();
            };
            el.addEventListener('change', onEdit);
            el.addEventListener('input', () => {
                const card = el.closest('[data-student-index]');
                const idx = card ? Number(card.dataset.studentIndex) : -1;
                const key = el.getAttribute('data-f');
                if (idx >= 0 && key) {
                    clearStudentUncertain(idx, key);
                    el.classList.remove('field-needs-review');
                    const hint = el.parentElement?.querySelector('.field-hint-review');
                    if (hint) hint.remove();
                    updateUncertainBanner();
                }
            });
        });
        renderSummary();
        renderFiles();
        updateUncertainBanner();
    }

    function renderSummary() {
        if (!summaryEl) return;
        const overdue = students.filter(isOverdue).length;
        const s0 = students.filter((s) => needsS0(s) && !hasS0(s)).length;
        const defense = students.filter((s) => s.completed && !s.defense_date).length;
        summaryEl.innerHTML = `
            <div class="rounded-lg bg-white border border-amber-200 px-3 py-2"><p class="text-xs text-[#7A4A3A]">นักศึกษา</p><p class="font-semibold text-[#5C2E1F]">${students.length} คน</p></div>
            <div class="rounded-lg ${overdue ? 'bg-red-50 border-red-200' : 'bg-white border-amber-200'} border px-3 py-2"><p class="text-xs text-[#7A4A3A]">เลยกำหนดเค้าโครง</p><p class="font-semibold ${overdue ? 'text-red-700' : 'text-[#5C2E1F]'}">${overdue} คน</p></div>
            <div class="rounded-lg ${s0 || defense ? 'bg-amber-50 border-amber-200' : 'bg-white border-amber-200'} border px-3 py-2"><p class="text-xs text-[#7A4A3A]">เอกสารที่ยังขาด</p><p class="font-semibold text-[#854d0e]">S=0 ${s0} · วันที่สอบ ${defense}</p></div>
        `;
    }

    function renderTsName() {
        const code = (document.getElementById('subject_code')?.value || 'รหัสวิชา').replace(/[^A-Za-z0-9]/g, '').toUpperCase() || 'รหัสวิชา';
        const section = String(document.querySelector('[name="section"]')?.value || '01').replace(/\D/g, '') || '1';
        const term = document.querySelector('[name="term"]')?.value || '1';
        const year = document.querySelector('[name="year"]')?.value || '';
        const preview = document.getElementById('ts-name-preview');
        if (preview) {
            preview.textContent = `TS-${code}-${String(section).padStart(2, '0')}-${term}-${year}.pdf`;
        }
    }

    function renderFiles() {
        if (tsFilesEl) {
            const items = tsFiles();
            tsFilesEl.innerHTML = items.length
                ? items.map((f) => fileRow(f)).join('')
                : '<p class="text-xs text-[#7A4A3A]/70">ยังไม่มีไฟล์ TS</p>';
        }
        if (s0SlotsEl) {
            const needed = students.filter((s) => needsS0(s) && s.id);
            const unsaved = students.filter((s) => needsS0(s) && !s.id);
            if (needed.length === 0 && unsaved.length === 0) {
                s0SlotsEl.innerHTML = '<p class="text-xs text-[#7A4A3A]/70">ยังไม่มีนักศึกษาที่ต้องแนบหนังสือ S=0</p>';
            } else {
                s0SlotsEl.innerHTML = [
                    ...needed.map((s) => {
                        const attached = files.filter((f) => f.file_type === 's0_letter' && String(f.student_id) === String(s.id));
                        return `<div class="rounded-lg border border-red-200 bg-white px-3 py-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-medium text-red-800">${escapeHtml(s.student_code)} ${escapeHtml(s.student_name)}</p>
                                ${editable && root.dataset.uploadUrl ? `<label class="text-xs font-semibold text-[#a16207] cursor-pointer">แนบ PDF
                                    <input type="file" accept="application/pdf" class="hidden" data-s0="${escapeHtml(s.id)}">
                                </label>` : ''}
                            </div>
                            <div class="mt-1 space-y-1">${attached.map(fileRow).join('') || '<p class="text-xs text-red-700">ยังไม่มีหนังสือชี้แจง</p>'}</div>
                        </div>`;
                    }),
                    unsaved.length ? '<p class="text-xs text-amber-800">บันทึกร่างก่อน จึงแนบหนังสือ S=0 รายคนได้</p>' : '',
                ].join('');
            }
        }
        bindFileActions();
    }

    function fileRow(f) {
        return `<div class="flex items-center justify-between gap-2 text-sm bg-white border border-amber-200 rounded-lg px-3 py-1.5">
            <a href="${f.url}" target="_blank" class="text-[#a16207] underline truncate">${escapeHtml(f.original_name)}</a>
            ${editable ? `<button type="button" class="text-xs text-red-700" data-del-file="${f.file_id}">ลบ</button>` : ''}
        </div>`;
    }

    function bindFileActions() {
        document.querySelectorAll('[data-del-file]').forEach((btn) => {
            btn.addEventListener('click', () => deleteFile(btn.dataset.delFile));
        });
        document.querySelectorAll('[data-s0]').forEach((input) => {
            input.addEventListener('change', () => {
                if (input.files[0]) uploadFile(input.files[0], 's0_letter', input.dataset.s0);
                input.value = '';
            });
        });
    }

    async function uploadFile(file, fileType, studentId) {
        if (!root.dataset.uploadUrl) return;
        const body = new FormData();
        body.append('file', file);
        body.append('file_type', fileType);
        if (studentId) body.append('student_id', studentId);
        const res = await fetch(root.dataset.uploadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            body,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            alert(data.message || 'อัปโหลดไม่สำเร็จ');
            return;
        }
        files.unshift(data.file);
        renderFiles();
        renderSummary();

        if (fileType === 'ts_report') {
            showSignatureBanner(data);
            if (data.show_next_actions) {
                showPostUploadModal(data);
            }
        }
    }

    function showSignatureBanner(data) {
        const box = document.getElementById('ts-signature-banner');
        if (!box) return;
        const signed = !!data.signature_signed;
        box.classList.remove('hidden', 'border-amber-300', 'bg-amber-50', 'text-amber-950', 'border-emerald-300', 'bg-emerald-50', 'text-emerald-900');
        if (signed) {
            box.classList.add('border-emerald-300', 'bg-emerald-50', 'text-emerald-900');
            box.textContent = data.signature_message || 'พบลายเซ็นดิจิทัลในไฟล์';
        } else {
            box.classList.add('border-amber-300', 'bg-amber-50', 'text-amber-950');
            box.textContent = data.signature_message || 'ยังไม่ลงนามดิจิทัล — อัปโหลดแล้ว แต่แนะนำให้ลงนามก่อนส่งเข้าสาขา';
        }
    }

    function showPostUploadModal(data) {
        const modal = document.getElementById('ts-upload-modal');
        if (!modal) return;
        const createBtn = document.getElementById('ts-modal-create');
        const indexBtn = document.getElementById('ts-modal-index');
        if (createBtn && data.create_url) createBtn.href = data.create_url;
        if (indexBtn && data.index_url) indexBtn.href = data.index_url;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    document.getElementById('ts-modal-close')?.addEventListener('click', () => {
        const modal = document.getElementById('ts-upload-modal');
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    });
    document.getElementById('ts-upload-modal')?.addEventListener('click', (e) => {
        if (e.target === e.currentTarget) {
            e.currentTarget.classList.add('hidden');
            e.currentTarget.classList.remove('flex');
        }
    });

    async function deleteFile(id) {
        if (!root.dataset.fileBase) return;
        const res = await fetch(`${root.dataset.fileBase}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
        });
        if (!res.ok) {
            alert('ลบไฟล์ไม่สำเร็จ');
            return;
        }
        files = files.filter((f) => String(f.file_id) !== String(id));
        renderFiles();
        renderSummary();
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[ch]));
    }

    function parsePaste(text) {
        return text.split(/\r?\n/).map((line) => line.trim()).filter(Boolean).flatMap((line) => {
            const cells = line.split(/[\t,;|]+/).map((c) => c.trim());
            const first = (cells[0] || '').toLowerCase();
            if (first.includes('รหัส') || first.includes('code')) return [];
            if (!cells[0]) return [];
            return [normalizeStudent({
                student_code: cells[0],
                name_prefix: cells[1] || '',
                first_name: cells[2] || '',
                last_name: cells[3] || '',
                student_name: [cells[1], cells[2], cells[3]].filter(Boolean).join(' '),
                degree: /เอก|doctoral|phd|^d$/i.test(cells[4] || '') ? 'doctoral' : 'master',
                grade: cells[5] || 'S',
                credits_registered: cells[6] || '',
                credits_passed: cells[7] || '',
                note: cells[8] || '',
            })];
        });
    }

    document.querySelectorAll('[data-go-step]').forEach((el) => {
        el.addEventListener('click', () => {
            collectFromDom();
            goStep(Number(el.dataset.goStep));
        });
    });
    document.getElementById('prev-step')?.addEventListener('click', () => {
        collectFromDom();
        goStep(step - 1);
    });
    document.getElementById('next-step')?.addEventListener('click', () => {
        collectFromDom();
        if (editable && !root.dataset.reportId) {
            document.getElementById('form-intent').value = 'draft';
            document.getElementById('form-step').value = String(Math.min(3, step + 1));
            form.submit();
            return;
        }
        goStep(step + 1);
    });
    document.getElementById('add-student')?.addEventListener('click', () => {
        collectFromDom();
        students.push(normalizeStudent({}));
        renderStudents();
    });
    document.getElementById('toggle-paste')?.addEventListener('click', () => {
        document.getElementById('paste-box')?.classList.toggle('hidden');
    });
    document.getElementById('apply-paste')?.addEventListener('click', () => {
        const text = document.getElementById('paste-input')?.value || '';
        const rows = parsePaste(text);
        if (!rows.length) {
            alert('ไม่พบแถวที่นำเข้าได้');
            return;
        }
        collectFromDom();
        students = students.concat(rows);
        renderStudents();
        document.getElementById('paste-input').value = '';
    });
    document.getElementById('delete-draft')?.addEventListener('click', () => {
        if (confirm('ลบร่างนี้หรือไม่')) document.getElementById('delete-form')?.submit();
    });
    form?.querySelectorAll('[data-intent]').forEach((btn) => {
        btn.addEventListener('click', () => {
            collectFromDom();
            document.getElementById('form-intent').value = btn.dataset.intent;
        });
    });
    form?.addEventListener('submit', () => {
        collectFromDom();
    });

    const codeInput = document.getElementById('subject_code');
    const subjectSelect = document.getElementById('subject');
    const suggest = document.getElementById('subject-suggest');
    const catalogHint = document.getElementById('subject-catalog-hint');
    let timer = null;

    function setCatalogHint(text, isWarn) {
        if (!catalogHint) return;
        catalogHint.textContent = text;
        catalogHint.classList.toggle('text-amber-800', !!isWarn);
        catalogHint.classList.toggle('text-[#7A4A3A]/70', !isWarn);
    }

    function applySubjectChoice(choice) {
        if (!subjectSelect || !choice) return;
        subjectSelect.value = choice;
    }

    function applyPrefill(prefill, studentsFromPdf, uncertain) {
        if (!prefill) return;
        if (prefill.term != null) {
            const termEl = document.querySelector('[name="term"]');
            if (termEl) termEl.value = String(prefill.term);
        }
        if (prefill.year != null) {
            const yearEl = document.querySelector('[name="year"]');
            if (yearEl) yearEl.value = String(prefill.year);
        }
        if (prefill.section != null) {
            const sectionEl = document.querySelector('[name="section"]');
            if (sectionEl) sectionEl.value = String(prefill.section);
        }
        if (prefill.subject_code != null && codeInput) {
            codeInput.value = prefill.subject_code;
        }
        if (prefill.subject) {
            applySubjectChoice(prefill.subject);
        }
        if (uncertain && typeof uncertain === 'object') {
            courseUncertain = { ...(uncertain.course || uncertain) };
            if (Array.isArray(uncertain.students) && Array.isArray(studentsFromPdf)) {
                studentsFromPdf = studentsFromPdf.map((s, i) => ({
                    ...s,
                    uncertain_fields: uncertain.students[i] || s.uncertain_fields || {},
                }));
            }
        } else if (prefill.uncertain_fields && typeof prefill.uncertain_fields === 'object') {
            courseUncertain = { ...prefill.uncertain_fields };
        }
        if (Array.isArray(studentsFromPdf) && studentsFromPdf.length) {
            students = studentsFromPdf.map(normalizeStudent);
            renderStudents();
        }
        applyCourseUncertainMarks();
        updateCourseContext();
        updateUncertainBanner();
        renderTsName();
        codeInput?.focus();
    }

    async function searchSubjects(q) {
        const res = await fetch(`${root.dataset.searchUrl}?q=${encodeURIComponent(q)}`);
        return res.json();
    }

    codeInput?.addEventListener('input', () => {
        const q = codeInput.value.trim();
        clearTimeout(timer);
        clearCourseUncertain('subject_code');
        renderTsName();
        updateCourseContext();
        if (q.length < 1) {
            suggest?.classList.add('hidden');
            setCatalogHint('มีในฐานข้อมูล: พิมพ์แล้วเลือกรายการ · ไม่มี: กรอกเองได้ (ชื่อวิชาเลือก THESIS / INDEPENDENT STUDY / DISSERTATION)', false);
            return;
        }
        timer = setTimeout(async () => {
            const rows = await searchSubjects(q);
            if (!suggest) return;
            if (!Array.isArray(rows) || !rows.length) {
                suggest.innerHTML = '<div class="suggest-item text-[#7A4A3A]">ไม่พบรหัสในฐานข้อมูล — กรอกรหัสเองได้ และเลือกชื่อวิชาทางขวา</div>';
                suggest.classList.remove('hidden');
                setCatalogHint('ไม่พบในฐานข้อมูล — ใช้รหัสที่พิมพ์และเลือกชื่อวิชาเองได้', true);
                return;
            }

            const exact = rows.find((r) => String(r.subject_code || '').toUpperCase() === q.toUpperCase());
            if (exact && exact.subject_choice) {
                codeInput.value = exact.subject_code;
                applySubjectChoice(exact.subject_choice);
                clearCourseUncertain('subject_code');
                clearCourseUncertain('subject');
                setCatalogHint(`พบในฐานข้อมูล: ${exact.subject_code} · ${exact.subject || exact.subject_choice}`, false);
                updateCourseContext();
            } else {
                setCatalogHint('พบรายการใกล้เคียง — คลิกเพื่อเลือก หรือกรอกเองได้', false);
            }

            suggest.innerHTML = rows.map((r) => {
                const choice = r.subject_choice || '';
                const name = r.subject || choice || '—';
                return `<div class="suggest-item" data-code="${escapeHtml(r.subject_code)}" data-choice="${escapeHtml(choice)}" data-name="${escapeHtml(name)}"><span class="font-semibold">${escapeHtml(r.subject_code)}</span> · ${escapeHtml(name)}</div>`;
            }).join('');
            suggest.classList.remove('hidden');
            suggest.querySelectorAll('.suggest-item[data-code]').forEach((item) => {
                item.addEventListener('click', () => {
                    codeInput.value = item.dataset.code || '';
                    clearCourseUncertain('subject_code');
                    if (item.dataset.choice) {
                        applySubjectChoice(item.dataset.choice);
                        clearCourseUncertain('subject');
                        setCatalogHint(`เลือกจากฐานข้อมูล: ${item.dataset.code} · ${item.dataset.name || item.dataset.choice}`, false);
                    } else {
                        setCatalogHint('พบรหัสแล้ว — กรุณาเลือกชื่อวิชาทางขวาเอง', true);
                    }
                    suggest.classList.add('hidden');
                    renderTsName();
                    updateCourseContext();
                });
            });
        }, 200);
    });

    subjectSelect?.addEventListener('change', () => {
        clearCourseUncertain('subject');
        renderTsName();
        updateCourseContext();
    });

    document.addEventListener('click', (e) => {
        if (!suggest?.contains(e.target) && e.target !== codeInput) suggest?.classList.add('hidden');
    });

    async function quickUpload(file) {
        const url = root.dataset.quickUploadUrl;
        if (!url || !file) return;

        const status = document.getElementById('quick-upload-status');
        const label = document.getElementById('quick-drop-label');
        const showStatus = (kind, title, hint) => {
            if (!status) return;
            status.classList.remove('hidden', 'border-amber-300', 'bg-amber-50', 'text-amber-950', 'border-red-200', 'bg-red-50', 'text-red-800', 'border-emerald-300', 'bg-emerald-50', 'text-emerald-900');
            if (kind === 'error') {
                status.classList.add('border-red-200', 'bg-red-50', 'text-red-800');
            } else if (kind === 'ok') {
                status.classList.add('border-emerald-300', 'bg-emerald-50', 'text-emerald-900');
            } else {
                status.classList.add('border-amber-300', 'bg-amber-50', 'text-amber-950');
            }
            const warnHtml = Array.isArray(hint) 
                ? hint.map((w) => `<p class="mt-1">${escapeHtml(w)}</p>`).join('')
                : (hint ? `<p class="mt-1">${escapeHtml(hint)}</p>` : '');
            status.innerHTML = `<p class="font-semibold">${escapeHtml(title)}</p>${warnHtml}`;
        };

        showStatus('info', 'กำลังอัปโหลดและอ่านข้อความจาก PDF...', 'กรุณารอสักครู่');
        if (label) label.textContent = 'กำลังประมวลผล...';

        const body = new FormData();
        body.append('file', file);
        body.append('term', document.querySelector('[name="term"]')?.value || '');
        body.append('year', document.querySelector('[name="year"]')?.value || '');

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body,
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                const title = data.message || 'อัปโหลดหรืออ่านไฟล์ไม่สำเร็จ';
                const hint = data.hint || 'กรุณากรอกรหัสวิชา ชื่อวิชา ภาคการศึกษา ปีการศึกษา กลุ่มเรียน และรายชื่อนักศึกษาด้วยตนเองในแบบฟอร์มด้านล่างแทน';
                showStatus('error', title, hint);
                if (data.prefill) {
                    applyPrefill(data.prefill, data.prefill.students, {
                        course: data.uncertain_fields || data.prefill.uncertain_fields || {},
                        students: (data.prefill.students || []).map((s) => s.uncertain_fields || {}),
                    });
                }
                if (label) label.textContent = 'ลากวางหรือคลิกเพื่อเลือก PDF';
                return;
            }

            // อ่านชนิดวิชาได้แล้ว แต่ยังต้องกรอกรหัสเอง
            if (data.draft_created === false && data.prefill) {
                applyPrefill(data.prefill, data.prefill.students, {
                    course: data.uncertain_fields || data.prefill.uncertain_fields || {},
                    students: (data.prefill.students || []).map((s) => s.uncertain_fields || {}),
                });
                const hints = [
                    data.message || 'อ่านข้อมูลจาก PDF แล้ว',
                    ...(Array.isArray(data.warnings) ? data.warnings : []),
                ];
                showStatus('info', 'อ่านจาก PDF แล้ว — กรุณากรอกรหัสวิชาแล้วบันทึกร่าง', hints.slice(1).join(' ') || hints[0]);
                if (label) label.textContent = 'ลากวางหรือคลิกเพื่อเลือก PDF';
                return;
            }

            showStatus('ok', data.message || 'อ่านข้อมูลจากไฟล์สำเร็จ', 'กำลังเปิดร่างเพื่อให้ตรวจสอบ...');
            window.location.href = data.edit_url;
        } catch (err) {
            showStatus(
                'error',
                'อัปโหลดไม่สำเร็จ เพราะเชื่อมต่อกับเซิร์ฟเวอร์ไม่ได้',
                'กรุณาลองใหม่อีกครั้ง หรือกรอกข้อมูลด้วยตนเองในแบบฟอร์มด้านล่างแทน',
            );
            if (label) label.textContent = 'ลากวางหรือคลิกเพื่อเลือก PDF';
        }
    }

    const quickInput = document.getElementById('quick-input');
    const quickDrop = document.getElementById('quick-drop');
    quickInput?.addEventListener('change', () => {
        if (quickInput.files[0]) quickUpload(quickInput.files[0]);
        quickInput.value = '';
    });
    ['dragenter', 'dragover'].forEach((ev) => {
        quickDrop?.addEventListener(ev, (e) => {
            e.preventDefault();
            quickDrop.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach((ev) => {
        quickDrop?.addEventListener(ev, (e) => {
            e.preventDefault();
            quickDrop.classList.remove('dragover');
        });
    });
    quickDrop?.addEventListener('drop', (e) => {
        const file = e.dataTransfer?.files?.[0];
        if (file) quickUpload(file);
    });

    const tsInput = document.getElementById('ts-input');
    const tsDrop = document.getElementById('ts-drop');
    tsInput?.addEventListener('change', () => {
        if (tsInput.files[0]) uploadFile(tsInput.files[0], 'ts_report');
        tsInput.value = '';
    });
    ['dragenter', 'dragover'].forEach((ev) => {
        tsDrop?.addEventListener(ev, (e) => {
            e.preventDefault();
            tsDrop.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach((ev) => {
        tsDrop?.addEventListener(ev, (e) => {
            e.preventDefault();
            tsDrop.classList.remove('dragover');
        });
    });
    tsDrop?.addEventListener('drop', (e) => {
        const file = e.dataTransfer?.files?.[0];
        if (file) uploadFile(file, 'ts_report');
    });

    ['term', 'year', 'section'].forEach((name) => {
        const el = document.querySelector(`[name="${name}"]`);
        el?.addEventListener('change', () => {
            clearCourseUncertain(name);
            renderTsName();
            updateCourseContext();
        });
        el?.addEventListener('input', () => {
            clearCourseUncertain(name);
            renderTsName();
            updateCourseContext();
        });
    });

    if (!students.length && editable) {
        students.push(normalizeStudent({}));
    }

    applyCourseUncertainMarks();
    updateCourseContext();
    updateUncertainBanner();
    renderStudents();
    goStep(step);
})();
