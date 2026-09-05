(() => {
    const root = document.getElementById('thesis-form-root');
    if (!root) return;

    const editable = root.dataset.editable === '1';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const initial = window.THESIS_FORM || { students: [], files: [], oldStudents: [] };
    let students = (initial.oldStudents && initial.oldStudents.length)
        ? initial.oldStudents.map(normalizeStudent)
        : (initial.students || []).map(normalizeStudent);
    let files = initial.files || [];
    let step = Number(root.dataset.initialStep || 1);

    const listEl = document.getElementById('student-list');
    const summaryEl = document.getElementById('student-summary');
    const tsFilesEl = document.getElementById('ts-files');
    const s0SlotsEl = document.getElementById('s0-slots');
    const form = document.getElementById('thesis-form');

    function normalizeStudent(row) {
        return {
            id: row.id || row.student_id || '',
            student_code: row.student_code || '',
            student_name: row.student_name || '',
            degree: row.degree === 'doctoral' ? 'doctoral' : 'master',
            thesis_terms_count: Number(row.thesis_terms_count || 1),
            proposal_approved: !!row.proposal_approved && row.proposal_approved !== '0',
            grade: (row.grade || 'S').toString().toUpperCase(),
            progress_credits: row.progress_credits === null || row.progress_credits === undefined ? '' : row.progress_credits,
            completed: !!row.completed && row.completed !== '0',
            defense_date: row.defense_date || '',
            note: row.note || '',
        };
    }

    function isOverdue(s) {
        if (s.proposal_approved) return false;
        return Number(s.thesis_terms_count || 0) >= (s.degree === 'doctoral' ? 4 : 2);
    }

    function isS0(s) {
        return String(s.grade || '').toUpperCase() === 'S' && (s.progress_credits === '' || Number(s.progress_credits) === 0);
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
    }

    function collectFromDom() {
        if (!listEl) return;
        listEl.querySelectorAll('[data-student-index]').forEach((card) => {
            const i = Number(card.dataset.studentIndex);
            if (!students[i]) return;
            students[i].student_code = card.querySelector('[data-f="student_code"]')?.value || '';
            students[i].student_name = card.querySelector('[data-f="student_name"]')?.value || '';
            students[i].degree = card.querySelector('[data-f="degree"]')?.value || 'master';
            students[i].thesis_terms_count = Number(card.querySelector('[data-f="thesis_terms_count"]')?.value || 1);
            students[i].proposal_approved = !!card.querySelector('[data-f="proposal_approved"]')?.checked;
            students[i].grade = (card.querySelector('[data-f="grade"]')?.value || 'S').toUpperCase();
            students[i].progress_credits = card.querySelector('[data-f="progress_credits"]')?.value ?? '';
            students[i].completed = !!card.querySelector('[data-f="completed"]')?.checked;
            students[i].defense_date = card.querySelector('[data-f="defense_date"]')?.value || '';
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
            return `
            <div class="student-card ${cls} p-4" data-student-index="${i}">
                <input type="hidden" name="students[${i}][id]" value="${escapeHtml(s.id)}">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <p class="text-sm font-semibold text-[#5C2E1F]">นักศึกษาคนที่ ${i + 1}</p>
                    <div class="flex items-center gap-2">${badge}
                        ${editable ? `<button type="button" class="text-xs text-red-700 hover:underline" data-remove="${i}">ลบ</button>` : ''}
                    </div>
                </div>
                <div class="grid md:grid-cols-4 gap-3">
                    <label class="text-xs text-[#7A4A3A]">รหัสนักศึกษา
                        <input ${ro} data-f="student_code" name="students[${i}][student_code]" value="${escapeHtml(s.student_code)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white">
                    </label>
                    <label class="text-xs text-[#7A4A3A] md:col-span-2">ชื่อ-สกุล
                        <input ${ro} data-f="student_name" name="students[${i}][student_name]" value="${escapeHtml(s.student_name)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white">
                    </label>
                    <label class="text-xs text-[#7A4A3A]">ระดับ
                        <select ${ro} data-f="degree" name="students[${i}][degree]" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white">
                            <option value="master" ${s.degree === 'master' ? 'selected' : ''}>ปริญญาโท</option>
                            <option value="doctoral" ${s.degree === 'doctoral' ? 'selected' : ''}>ปริญญาเอก</option>
                        </select>
                    </label>
                    <label class="text-xs text-[#7A4A3A]">ภาคที่ลงวิทยานิพนธ์สะสม
                        <input ${ro} type="number" min="1" max="20" data-f="thesis_terms_count" name="students[${i}][thesis_terms_count]" value="${escapeHtml(s.thesis_terms_count)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white">
                    </label>
                    <label class="text-xs text-[#7A4A3A] flex items-center gap-2 mt-6">
                        <input ${ro} type="checkbox" data-f="proposal_approved" name="students[${i}][proposal_approved]" value="1" ${s.proposal_approved ? 'checked' : ''}>
                        อนุมัติเค้าโครงแล้ว
                    </label>
                    <label class="text-xs text-[#7A4A3A]">เกรด
                        <input ${ro} data-f="grade" name="students[${i}][grade]" value="${escapeHtml(s.grade)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white">
                    </label>
                    <label class="text-xs text-[#7A4A3A]">หน่วยกิตความก้าวหน้า
                        <input ${ro} type="number" min="0" step="0.5" data-f="progress_credits" name="students[${i}][progress_credits]" value="${escapeHtml(s.progress_credits)}" class="mt-1 w-full border border-amber-300 rounded-lg px-2 py-1.5 text-sm bg-white">
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
            el.addEventListener('change', () => {
                collectFromDom();
                renderStudents();
            });
        });
        renderSummary();
        renderFiles();
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
    }

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
            const degreeRaw = cells[2] || '';
            const yes = (v) => ['1', 'y', 'yes', 'true', 'อนุมัติ', 'ผ่าน', 'ครบ', 'x'].includes((v || '').toLowerCase());
            return [normalizeStudent({
                student_code: cells[0],
                student_name: cells[1] || '',
                degree: /เอก|doctoral|phd|^d$/i.test(degreeRaw) ? 'doctoral' : 'master',
                thesis_terms_count: Number(cells[3] || 1),
                proposal_approved: yes(cells[4]),
                grade: cells[5] || 'S',
                progress_credits: cells[6] || '',
                completed: yes(cells[7]),
                defense_date: cells[8] || '',
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
        syncSubjectFromSearch();
    });

    function syncSubjectFromSearch() {
        const codeEl = document.getElementById('subject_code');
        const nameEl = document.getElementById('subject');
        const manualCode = document.getElementById('manual_subject_code')?.value.trim();
        const manualName = document.getElementById('manual_subject_name')?.value.trim();
        if (manualCode && manualName) {
            codeEl.value = manualCode;
            nameEl.value = manualName;
            return;
        }
        if (!codeEl || (codeEl.value && nameEl.value)) return;
        const raw = (searchInput?.value || '').trim();
        const parts = raw.split(/\s+[—\-]\s+/);
        if (parts.length >= 2) {
            codeEl.value = parts[0].trim();
            nameEl.value = parts.slice(1).join(' - ').trim();
        }
    }

    document.getElementById('toggle-manual-subject')?.addEventListener('click', () => {
        document.getElementById('manual-subject')?.classList.toggle('hidden');
    });
    ['manual_subject_code', 'manual_subject_name'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', () => {
            const code = document.getElementById('manual_subject_code')?.value.trim() || '';
            const name = document.getElementById('manual_subject_name')?.value.trim() || '';
            document.getElementById('subject_code').value = code;
            document.getElementById('subject').value = name;
            if (searchInput) searchInput.value = code && name ? `${code} — ${name}` : code;
            renderTsName();
        });
    });

    const searchInput = document.getElementById('subject-search');
    const suggest = document.getElementById('subject-suggest');
    let timer = null;
    searchInput?.addEventListener('input', () => {
        const q = searchInput.value.trim();
        clearTimeout(timer);
        if (q.length < 1) {
            suggest.classList.add('hidden');
            return;
        }
        timer = setTimeout(async () => {
            const res = await fetch(`${root.dataset.searchUrl}?q=${encodeURIComponent(q)}`);
            const rows = await res.json();
            if (!Array.isArray(rows) || !rows.length) {
                suggest.innerHTML = '<div class="suggest-item text-[#7A4A3A]">ไม่พบวิชาวิทยานิพนธ์ที่ตรงกัน</div>';
                suggest.classList.remove('hidden');
                return;
            }
            suggest.innerHTML = rows.map((r) => `<div class="suggest-item" data-code="${escapeHtml(r.subject_code)}" data-name="${escapeHtml(r.subject)}"><span class="font-semibold">${escapeHtml(r.subject_code)}</span> · ${escapeHtml(r.subject)}</div>`).join('');
            suggest.classList.remove('hidden');
            suggest.querySelectorAll('.suggest-item').forEach((item) => {
                item.addEventListener('click', () => {
                    document.getElementById('subject_code').value = item.dataset.code;
                    document.getElementById('subject').value = item.dataset.name;
                    searchInput.value = `${item.dataset.code} — ${item.dataset.name}`;
                    suggest.classList.add('hidden');
                    renderTsName();
                });
            });
        }, 200);
    });
    document.addEventListener('click', (e) => {
        if (!suggest?.contains(e.target) && e.target !== searchInput) suggest?.classList.add('hidden');
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
        document.querySelector(`[name="${name}"]`)?.addEventListener('change', renderTsName);
        document.querySelector(`[name="${name}"]`)?.addEventListener('input', renderTsName);
    });

    if (!students.length && editable) {
        students.push(normalizeStudent({}));
    }

    renderStudents();
    goStep(step);
})();
