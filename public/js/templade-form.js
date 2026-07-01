function formatDecimal2(value) {
    if (value === '' || value == null) return '';
    const num = parseFloat(String(value).replace(/,/g, ''));
    if (Number.isNaN(num)) return '';
    return num.toFixed(2);
}

function setupScoreDecimalInputs() {
    document.querySelectorAll('.score-decimal-input').forEach((input) => {
        input.addEventListener('input', () => {
            const parts = input.value.replace(/[^\d.]/g, '').split('.');
            if (parts.length > 2) {
                input.value = parts[0] + '.' + parts.slice(1).join('');
            }
            if (parts[1]?.length > 2) {
                input.value = parts[0] + '.' + parts[1].slice(0, 2);
            }
        });
        input.addEventListener('blur', () => {
            if (input.value.trim() !== '') {
                input.value = formatDecimal2(input.value);
            }
        });
    });
}

async function fetchSubjectSuggestions(q) {
    const res = await fetch(`/api/subjects/search?q=${encodeURIComponent(q)}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    return res.json();
}

function setupSubjectAutocomplete() {
    const input = document.getElementById('subject-code');
    const list = document.getElementById('subject-suggestions');
    const nameInput = document.getElementById('subject-name');
    if (!input || !list || !nameInput) return;

    let timer = null;

    const hideList = () => {
        list.classList.add('hidden');
        list.innerHTML = '';
    };

    const selectSubject = (code, name) => {
        input.value = code;
        nameInput.value = name;
        hideList();
    };

    const render = (items) => {
        if (!items.length) {
            hideList();
            return;
        }
        list.innerHTML = items.map((item) => `
            <button type="button" class="subject-suggestion-btn"
                data-code="${item.subject_code.replace(/"/g, '&quot;')}"
                data-name="${item.subject.replace(/"/g, '&quot;')}">
                <span class="subject-suggestion-code">${item.subject_code}</span>
                <span class="subject-suggestion-name">${item.subject}</span>
            </button>
        `).join('');
        list.classList.remove('hidden');
        list.querySelectorAll('button').forEach((btn) => {
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectSubject(btn.dataset.code, btn.dataset.name);
            });
        });
    };

    const search = async (q) => {
        if (q.length < 1) {
            hideList();
            return;
        }
        try {
            render(await fetchSubjectSuggestions(q));
        } catch {
            hideList();
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => search(input.value.trim()), 250);
    });

    input.addEventListener('focus', () => {
        if (input.value.trim()) search(input.value.trim());
    });

    input.addEventListener('blur', () => setTimeout(hideList, 150));

    document.addEventListener('click', (e) => {
        if (!list.contains(e.target) && e.target !== input) hideList();
    });
}

let jointGradeSubjects = [];

function parseJointGradeReason(reason) {
    if (!reason) return [];

    const prefixes = ['ตัดเกรดร่วมกับ :', 'ตัดเกรดร่วมกับ:', 'ซ้อนวิชากับ :', 'ซ้อนวิชากับ:'];
    let rest = String(reason).trim();
    for (const prefix of prefixes) {
        if (rest.startsWith(prefix)) {
            rest = rest.slice(prefix.length).trim();
            break;
        }
    }

    return rest.split(',').map((pair) => {
        const trimmed = pair.trim();
        if (!trimmed) return null;
        const pipeIdx = trimmed.indexOf('|');
        if (pipeIdx === -1) {
            return { code: trimmed, name: '' };
        }
        return {
            code: trimmed.slice(0, pipeIdx).trim(),
            name: trimmed.slice(pipeIdx + 1).trim(),
        };
    }).filter((item) => item && item.code);
}

function serializeJointGradeReason(subjects) {
    if (!subjects.length) return null;
    return `ตัดเกรดร่วมกับ :${subjects.map((s) => `${s.code}|${s.name}`).join(',')}`;
}

function setJointGradeSubjects(subjects) {
    jointGradeSubjects = subjects.filter((s) => s.code);
    renderJointGradeTags();
}

async function enrichJointGradeSubjectNames(subjects) {
    const enriched = await Promise.all(subjects.map(async (s) => {
        if (s.name) return s;
        try {
            const items = await fetchSubjectSuggestions(s.code);
            const match = items.find((item) => item.subject_code === s.code);
            return match ? { code: s.code, name: match.subject } : s;
        } catch {
            return s;
        }
    }));
    return enriched;
}

function resetJointGradeSubjects() {
    jointGradeSubjects = [];
    renderJointGradeTags();
    const search = document.getElementById('joint-subject-search');
    if (search) search.value = '';
}

function addJointGradeSubject(code, name) {
    const normalized = String(code).trim().replace(/\s+/g, '');
    if (!normalized) return false;
    if (jointGradeSubjects.some((s) => s.code === normalized)) return false;

    jointGradeSubjects.push({ code: normalized, name: String(name || '').trim() });
    renderJointGradeTags();
    return true;
}

async function commitJointGradeSubjectInput(rawCode) {
    const code = String(rawCode).trim().replace(/\s+/g, '');
    if (!code) return false;

    const radio = document.querySelector('input[name="reasonid"][value="1"]');
    if (radio) radio.checked = true;
    updateReasonFieldsState();

    if (jointGradeSubjects.some((s) => s.code === code)) return false;

    let name = '';
    try {
        const items = await fetchSubjectSuggestions(code);
        const exact = items.find((item) => item.subject_code === code);
        if (exact) name = exact.subject;
    } catch {
        /* allow manual code without name */
    }

    return addJointGradeSubject(code, name);
}

function removeJointGradeSubject(code) {
    jointGradeSubjects = jointGradeSubjects.filter((s) => s.code !== code);
    renderJointGradeTags();
}

function renderJointGradeTags() {
    const tagsEl = document.getElementById('joint-subject-tags');
    if (!tagsEl) return;

    if (!jointGradeSubjects.length) {
        tagsEl.innerHTML = '';
        return;
    }

    tagsEl.innerHTML = jointGradeSubjects.map((s) => `
        <span class="joint-subject-tag inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs max-w-full">
            <span class="font-semibold shrink-0">${s.code}</span>
            ${s.name ? `<span class="text-gray-600 truncate">— ${s.name}</span>` : ''}
            <button type="button" class="joint-subject-remove text-gray-400 hover:text-red-600 shrink-0" data-code="${s.code}" title="ลบ">×</button>
        </span>
    `).join('');

    tagsEl.querySelectorAll('.joint-subject-remove').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            removeJointGradeSubject(btn.dataset.code);
        });
    });
}

function updateReasonFieldsState() {
    const isJoint = document.querySelector('input[name="reasonid"]:checked')?.value === '1';
    const search = document.getElementById('joint-subject-search');
    const panel = document.getElementById('joint-grade-panel');
    if (search) search.disabled = !isJoint;
    if (panel) panel.classList.toggle('opacity-50', !isJoint);
}

function setupJointGradeSubjectSearch() {
    const input = document.getElementById('joint-subject-search');
    const list = document.getElementById('joint-subject-suggestions');
    if (!input || !list) return;

    let timer = null;

    const hideList = () => {
        list.classList.add('hidden');
        list.innerHTML = '';
    };

    const selectFromList = async (code, name) => {
        const radio = document.querySelector('input[name="reasonid"][value="1"]');
        if (radio) radio.checked = true;
        updateReasonFieldsState();
        if (addJointGradeSubject(code, name)) {
            input.value = '';
        }
        hideList();
    };

    const bindListButtons = () => {
        list.querySelectorAll('[data-joint-code]').forEach((btn) => {
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectFromList(btn.dataset.jointCode, btn.dataset.jointName || '');
            });
        });
        list.querySelectorAll('.joint-subject-manual').forEach((btn) => {
            btn.addEventListener('mousedown', async (e) => {
                e.preventDefault();
                if (await commitJointGradeSubjectInput(btn.dataset.jointCode || input.value)) {
                    input.value = '';
                }
                hideList();
            });
        });
    };

    const render = (items, q) => {
        const query = String(q || '').trim().replace(/\s+/g, '');
        if (!query) {
            hideList();
            return;
        }

        const hasExact = items.some((item) => item.subject_code === query);
        let html = items.map((item) => `
            <button type="button" class="w-full text-left px-3 py-2 border-b border-amber-100 hover:bg-amber-50"
                data-joint-code="${item.subject_code.replace(/"/g, '&quot;')}"
                data-joint-name="${item.subject.replace(/"/g, '&quot;')}">
                <span class="font-semibold text-[#5C2E1F]">${item.subject_code}</span>
                <span class="text-gray-600"> — ${item.subject}</span>
            </button>
        `).join('');

        if (!items.length || !hasExact) {
            const label = items.length
                ? `ใช้รหัส <span class="font-semibold">${query}</span> ที่กรอกเอง`
                : `เพิ่มรหัสวิชา <span class="font-semibold">${query}</span> (ไม่มีในฐานข้อมูล)`;
            html += `<button type="button" class="joint-subject-manual w-full text-left px-3 py-2 border-t border-amber-200 text-[#5C2E1F] hover:bg-amber-50"
                data-joint-code="${query.replace(/"/g, '&quot;')}">${label}</button>`;
        }

        list.innerHTML = html;
        list.classList.remove('hidden');
        bindListButtons();
    };

    const search = async (q) => {
        const query = q.trim();
        if (query.length < 1) {
            hideList();
            return;
        }
        try {
            render(await fetchSubjectSuggestions(query), query);
        } catch {
            render([], query);
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => search(input.value.trim()), 250);
    });

    input.addEventListener('focus', () => {
        if (!input.disabled && input.value.trim()) search(input.value.trim());
    });

    input.addEventListener('keydown', async (e) => {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        if (await commitJointGradeSubjectInput(input.value)) {
            input.value = '';
        }
        hideList();
    });

    input.addEventListener('blur', () => setTimeout(hideList, 150));

    document.addEventListener('click', (e) => {
        if (!list.contains(e.target) && e.target !== input) hideList();
    });
}

function setupReasonIdFields() {
    document.querySelectorAll('input[name="reasonid"]').forEach((radio) => {
        radio.addEventListener('change', updateReasonFieldsState);
    });
    updateReasonFieldsState();
}

let evaHintHideTimer = null;

function setupEvaHintPopover(imageUrl) {
    const popover = document.getElementById('eva-hint-popover');
    if (!popover || !imageUrl) return;

    const img = popover.querySelector('img');
    if (img && img.src !== imageUrl) {
        img.src = imageUrl;
    }

    if (popover.parentElement !== document.body) {
        document.body.appendChild(popover);
    }

    if (popover.dataset.evaHintBound === '1') return;
    popover.dataset.evaHintBound = '1';

    const showNear = (el) => {
        if (el.closest('.hidden')) return;

        clearTimeout(evaHintHideTimer);
        const rect = el.getBoundingClientRect();
        const popW = 440;
        let left = rect.left;
        if (left + popW > window.innerWidth - 16) {
            left = window.innerWidth - popW - 16;
        }
        let top = rect.bottom + 8;
        const popH = img?.offsetHeight || 280;
        if (top + popH > window.innerHeight - 16) {
            top = Math.max(16, rect.top - popH - 8);
        }
        popover.style.left = `${Math.max(16, left)}px`;
        popover.style.top = `${top}px`;
        popover.classList.add('is-visible');
    };

    const scheduleHide = () => {
        clearTimeout(evaHintHideTimer);
        evaHintHideTimer = setTimeout(() => {
            const active = document.activeElement;
            if (active?.classList.contains('eva-hint-field')) return;
            popover.classList.remove('is-visible');
        }, 200);
    };

    document.addEventListener('focusin', (e) => {
        if (e.target.matches?.('.eva-hint-field')) showNear(e.target);
    });
    document.addEventListener('focusout', (e) => {
        if (e.target.matches?.('.eva-hint-field')) scheduleHide();
    });
    document.addEventListener('mousedown', (e) => {
        if (e.target.matches?.('.eva-hint-field')) showNear(e.target);
    });
}

function buildScoreRange(maxId, minId) {
    const max = document.getElementById(maxId)?.value?.trim();
    const min = document.getElementById(minId)?.value?.trim();
    if (!max && !min) return null;
    return `${max || ''}-${min || ''}`;
}

function getTermFromForm() {
    const v = document.querySelector('input[name="term"]:checked')?.value;
    return parseInt(v || '1', 10);
}

function isDecimalMode() {
    return document.querySelector('input[name="intflag"]:checked')?.value === '0';
}

function formatGradeRangeValue(value) {
    if (value === '' || value == null) return '';
    const num = parseFloat(String(value).replace(/,/g, ''));
    if (Number.isNaN(num)) return value;
    return isDecimalMode() ? num.toFixed(2) : String(Math.round(num));
}

function applyGradeRangeFormat(input) {
    if (!input || input.readOnly) return;
    input.value = formatGradeRangeValue(input.value);
}

function setupGradeRangeInputs() {
    document.querySelectorAll('.grade-range-input').forEach((input) => {
        input.addEventListener('blur', () => applyGradeRangeFormat(input));
        input.addEventListener('input', () => {
            if (!isDecimalMode()) {
                input.value = input.value.replace(/[^\d]/g, '');
                return;
            }
            const parts = input.value.split('.');
            if (parts.length > 2) {
                input.value = parts[0] + '.' + parts.slice(1).join('');
            }
            if (parts[1]?.length > 2) {
                input.value = parts[0] + '.' + parts[1].slice(0, 2);
            }
        });
    });
}

function updateGradeBoundaryHint() {
    const el = document.getElementById('grade-boundary-hint');
    if (!el) return;
    el.textContent = isDecimalMode()
        ? 'กรุณากรอกเฉพาะขอบเขตล่างของช่วงคะแนน เป็นจำนวนทศนิยม เท่านั้น!!'
        : 'กรุณากรอกเฉพาะขอบเขตล่างของช่วงคะแนน เป็นเลขจำนวนเต็ม เท่านั้น!!';
}

function getDisplayedRange(key) {
    const max = document.getElementById(`range-${key}-max`)?.value?.trim();
    const min = document.getElementById(`range-${key}-min`)?.value?.trim();
    if (max && min) return `${max} – ${min}`;
    if (max || min) return `${max || '?'} – ${min || '?'}`;
    return '—';
}

function updateGradeRangeColumnHeaders() {
    GRADE_RANGE_KEYS.forEach((key) => {
        const cell = document.querySelector(`.grade-range-col[data-grade="${key}"]`);
        if (cell) cell.textContent = getDisplayedRange(key);
    });
}

function renderFacTags() {
    const tagsEl = document.getElementById('fac-selected-tags');
    const labelEl = document.getElementById('fac-dropdown-label');
    const checked = Array.from(document.querySelectorAll('.fac-checkbox:checked'));

    if (tagsEl) {
        tagsEl.innerHTML = checked.map((cb) => `
            <span class="fac-tag inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs">
                ${cb.value}
                <button type="button" class="fac-tag-remove text-gray-400 hover:text-red-600" data-code="${cb.value}">×</button>
            </span>
        `).join('');
        tagsEl.querySelectorAll('.fac-tag-remove').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const box = document.querySelector(`.fac-checkbox[value="${btn.dataset.code}"]`);
                if (box) box.checked = false;
                renderFacTags();
            });
        });
    }

    if (labelEl) {
        if (!checked.length) {
            labelEl.textContent = '— เลือกคณะ —';
            labelEl.classList.add('text-gray-500');
        } else {
            labelEl.textContent = `เลือกแล้ว ${checked.length} คณะ`;
            labelEl.classList.remove('text-gray-500');
            labelEl.classList.add('text-[#5C2E1F]');
        }
    }

    refreshSectionSelectOptions();
}

function setFacultiesSelected(codes) {
    const set = new Set((codes || '').split(',').map((c) => c.trim()).filter(Boolean));
    document.querySelectorAll('.fac-checkbox').forEach((cb) => {
        cb.checked = set.has(cb.value);
    });
    renderFacTags();
}

function setupFacMultiSelect() {
    const btn = document.getElementById('fac-dropdown-btn');
    const panel = document.getElementById('fac-dropdown-panel');
    if (!btn || !panel) return;

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        panel.classList.toggle('hidden');
    });

    panel.addEventListener('click', (e) => e.stopPropagation());

    document.querySelectorAll('.fac-checkbox').forEach((cb) => {
        cb.addEventListener('change', renderFacTags);
    });

    document.addEventListener('click', () => panel.classList.add('hidden'));

    renderFacTags();
}

function setupIntflagMode() {
    document.querySelectorAll('input[name="intflag"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const maxA = document.getElementById('range-a-max');
            if (maxA) maxA.value = defaultAMaxValue();
            document.querySelectorAll('.grade-range-input').forEach((input) => {
                if (!input.readOnly && input.value !== '') {
                    applyGradeRangeFormat(input);
                }
            });
            recalcAllGradeChains();
            updateGradeBoundaryHint();
            updateGradeRangeColumnHeaders();
        });
    });
    updateGradeBoundaryHint();
}

function buildReason() {
    const reasonid = parseInt(document.querySelector('input[name="reasonid"]:checked')?.value || '0', 10);
    if (reasonid === 1) {
        return { reasonid, reason: serializeJointGradeReason(jointGradeSubjects) };
    }
    if (reasonid === 2) {
        const text = document.getElementById('std-i2')?.value?.trim();
        return { reasonid, reason: text ? `ได้ I เนื่องจาก :${text}` : null };
    }
    if (reasonid === 3) {
        return { reasonid, reason: document.getElementById('std-i3')?.value?.trim() || null };
    }
    return { reasonid: null, reason: null };
}

function collectGradeStd() {
    const statuseva = parseInt(document.querySelector('input[name="statuseva"]:checked')?.value || '2', 10);
    const fac = Array.from(document.querySelectorAll('.fac-checkbox:checked')).map((c) => c.value).join(',');

    return {
        sec: parseInt(document.getElementById('section-input')?.value || '1', 10),
        fac,
        type_course: parseInt(document.querySelector('input[name="type_course"]:checked')?.value || '1', 10),
        num_a: parseInt(document.getElementById('count-a')?.value || '0', 10),
        num_bb: parseInt(document.getElementById('count-bp')?.value || '0', 10),
        num_b: parseInt(document.getElementById('count-b')?.value || '0', 10),
        num_cc: parseInt(document.getElementById('count-cp')?.value || '0', 10),
        num_c: parseInt(document.getElementById('count-c')?.value || '0', 10),
        num_dd: parseInt(document.getElementById('count-dp')?.value || '0', 10),
        num_d: parseInt(document.getElementById('count-d')?.value || '0', 10),
        num_f: parseInt(document.getElementById('count-f')?.value || '0', 10),
        num_i: parseInt(document.getElementById('count-i')?.value || '0', 10),
        num_s: parseInt(document.getElementById('count-s')?.value || '0', 10),
        num_v: parseInt(document.getElementById('count-u')?.value || '0', 10),
        num_w: parseInt(document.getElementById('count-w')?.value || '0', 10),
        num_out: 0,
        numstdevz: statuseva === 1 && document.getElementById('numstdevz')?.value
            ? parseInt(document.getElementById('numstdevz').value, 10)
            : null,
        evaluationscore: statuseva === 1 && document.getElementById('evaluationscore')?.value
            ? document.getElementById('evaluationscore').value
            : null,
    };
}

let sectionStdRows = [];
let editingSectionIndex = null;

const TYPE_COURSE_SUFFIX = {
    1: '',
    2: '(โครงการพิเศษ)',
    3: '(ก้าวหน้า)',
    4: '(ปกติ นานาชาติ)',
    5: '(โครงการพิเศษ นานาชาติ)',
};

function calcSectionTotalStd(row) {
    const keys = ['num_a', 'num_bb', 'num_b', 'num_cc', 'num_c', 'num_dd', 'num_d', 'num_f', 'num_i', 'num_s', 'num_v', 'num_w', 'num_out'];
    return keys.reduce((sum, key) => sum + (parseInt(row[key] || 0, 10) || 0), 0);
}

function normalizeSectionRow(row) {
    const normalized = { ...row };
    normalized.total_std = calcSectionTotalStd(normalized);
    return normalized;
}

function validateSectionStdForm() {
    const fac = Array.from(document.querySelectorAll('.fac-checkbox:checked'));
    if (!fac.length) {
        return 'กรุณาเลือกคณะก่อนบันทึก Section';
    }
    return null;
}

function getCurrentFacString() {
    return Array.from(document.querySelectorAll('.fac-checkbox:checked')).map((c) => c.value).join(',');
}

function isSectionOptionUsed(sec, excludeIndex = null) {
    const fac = getCurrentFacString();
    return sectionStdRows.some((row, idx) => {
        if (excludeIndex !== null && idx === excludeIndex) return false;
        if (row.sec !== sec) return false;
        if (!fac) return true;
        return row.fac === fac;
    });
}

function refreshSectionSelectOptions() {
    const select = document.getElementById('section-input');
    if (!select) return;

    const excludeIndex = editingSectionIndex;
    let firstAvailable = null;

    Array.from(select.options).forEach((opt) => {
        const sec = parseInt(opt.value, 10);
        const used = isSectionOptionUsed(sec, excludeIndex);
        opt.hidden = used;
        opt.disabled = used;
        if (!used && firstAvailable === null) {
            firstAvailable = opt.value;
        }
    });

    const selected = select.options[select.selectedIndex];
    if (selected?.disabled && firstAvailable !== null) {
        select.value = firstAvailable;
    }
}

function clearGradeStdFormCounts() {
    ['a', 'bp', 'b', 'cp', 'c', 'dp', 'd', 'f', 'i', 's', 'u', 'w'].forEach((key) => {
        const el = document.getElementById(`count-${key}`);
        if (el) el.value = '0';
    });
    const numstdevz = document.getElementById('numstdevz');
    const evaluationscore = document.getElementById('evaluationscore');
    if (numstdevz) numstdevz.value = '';
    if (evaluationscore) evaluationscore.value = '';
}

function loadGradeStdToForm(row) {
    if (!row) return;

    document.getElementById('section-input').value = row.sec ?? 1;
    setFacultiesSelected(row.fac || '');
    setRadio('type_course', row.type_course ?? 1);

    const map = {
        'count-a': row.num_a,
        'count-bp': row.num_bb,
        'count-b': row.num_b,
        'count-cp': row.num_cc,
        'count-c': row.num_c,
        'count-dp': row.num_dd,
        'count-d': row.num_d,
        'count-f': row.num_f,
        'count-i': row.num_i,
        'count-s': row.num_s,
        'count-u': row.num_v,
        'count-w': row.num_w,
    };
    Object.entries(map).forEach(([id, val]) => {
        const el = document.getElementById(id);
        if (el) el.value = val ?? 0;
    });

    const numstdevz = document.getElementById('numstdevz');
    const evaluationscore = document.getElementById('evaluationscore');
    if (numstdevz) numstdevz.value = row.numstdevz ?? '';
    if (evaluationscore) evaluationscore.value = row.evaluationscore ?? '';
    refreshSectionSelectOptions();
}

function updateSectionFormHint() {
    const hint = document.getElementById('section-form-hint');
    if (!hint) return;
    hint.textContent = editingSectionIndex !== null
        ? `กำลังแก้ไข Section ${sectionStdRows[editingSectionIndex]?.sec ?? ''} — กด «บันทึก Section นี้» เพื่อยืนยัน`
        : 'กรอกข้อมูล Section แล้วกด «บันทึก Section นี้» — Section ที่บันทึกแล้วจะไม่แสดงในรายการ';
}

function cancelSectionEdit() {
    editingSectionIndex = null;
    clearGradeStdFormCounts();
    document.getElementById('section-input').value = '1';
    document.querySelectorAll('.fac-checkbox').forEach((cb) => { cb.checked = false; });
    renderFacTags();
    setRadio('type_course', 1);
    updateSectionFormHint();
    const cancelBtn = document.getElementById('btn-cancel-section-edit');
    if (cancelBtn) cancelBtn.classList.add('hidden');
    refreshSectionSelectOptions();
}

function addOrUpdateSectionFromForm() {
    const error = validateSectionStdForm();
    if (error) return { ok: false, error };

    const row = normalizeSectionRow(collectGradeStd());
    const duplicateIndex = sectionStdRows.findIndex((item, idx) => (
        idx !== editingSectionIndex
        && item.sec === row.sec
        && item.fac === row.fac
    ));
    if (duplicateIndex !== -1) {
        return { ok: false, error: `Section ${row.sec} คณะ ${row.fac} มีอยู่แล้ว` };
    }

    if (editingSectionIndex !== null) {
        row.id = sectionStdRows[editingSectionIndex].id ?? null;
        sectionStdRows[editingSectionIndex] = row;
        editingSectionIndex = null;
    } else {
        sectionStdRows.push(row);
    }

    renderSectionStdList();
    clearGradeStdFormCounts();
    document.getElementById('section-input').value = '1';
    document.querySelectorAll('.fac-checkbox').forEach((cb) => { cb.checked = false; });
    renderFacTags();
    setRadio('type_course', 1);
    updateSectionFormHint();
    const cancelBtn = document.getElementById('btn-cancel-section-edit');
    if (cancelBtn) cancelBtn.classList.add('hidden');

    return { ok: true };
}

function editSectionStd(index) {
    editingSectionIndex = index;
    loadGradeStdToForm(sectionStdRows[index]);
    updateSectionFormHint();
    const cancelBtn = document.getElementById('btn-cancel-section-edit');
    if (cancelBtn) cancelBtn.classList.remove('hidden');
    document.getElementById('section-std-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function removeSectionStd(index) {
    sectionStdRows.splice(index, 1);
    if (editingSectionIndex === index) {
        cancelSectionEdit();
    } else if (editingSectionIndex !== null && editingSectionIndex > index) {
        editingSectionIndex -= 1;
    }
    renderSectionStdList();
    updateSectionFormHint();
}

function resetSectionStdRows() {
    sectionStdRows = [];
    editingSectionIndex = null;
    renderSectionStdList();
    cancelSectionEdit();
}

function setSectionStdRows(rows) {
    sectionStdRows = (rows || []).map((row) => normalizeSectionRow({
        id: row.id ?? null,
        sec: row.sec ?? 1,
        fac: row.fac ?? '',
        type_course: row.type_course ?? 1,
        num_a: row.num_a ?? 0,
        num_bb: row.num_bb ?? 0,
        num_b: row.num_b ?? 0,
        num_cc: row.num_cc ?? 0,
        num_c: row.num_c ?? 0,
        num_dd: row.num_dd ?? 0,
        num_d: row.num_d ?? 0,
        num_f: row.num_f ?? 0,
        num_i: row.num_i ?? 0,
        num_s: row.num_s ?? 0,
        num_v: row.num_v ?? 0,
        num_w: row.num_w ?? 0,
        num_out: row.num_out ?? 0,
        numstdevz: row.numstdevz ?? null,
        evaluationscore: row.evaluationscore ?? null,
    }));
    editingSectionIndex = null;
    renderSectionStdList();
    updateSectionFormHint();
}

function renderSectionStdList() {
    const tbody = document.getElementById('section-std-list-body');
    const empty = document.getElementById('section-std-list-empty');
    const wrap = document.getElementById('section-std-list-wrap');
    if (!tbody) return;

    if (!sectionStdRows.length) {
        tbody.innerHTML = '';
        empty?.classList.remove('hidden');
        wrap?.classList.add('hidden');
        return;
    }

    empty?.classList.add('hidden');
    wrap?.classList.remove('hidden');

    const statuseva = document.querySelector('input[name="statuseva"]:checked')?.value || '2';
    const showEva = statuseva === '1';

    tbody.innerHTML = sectionStdRows.map((row, index) => {
        const facLabel = `${String(row.fac || '').toUpperCase()}${TYPE_COURSE_SUFFIX[row.type_course] || ''}`;
        const evaCell = showEva
            ? `<td class="px-2 py-2 text-center border-t border-amber-100">${row.evaluationscore ?? '—'}</td>`
            : '';
        return `
            <tr class="${editingSectionIndex === index ? 'bg-amber-50' : ''}">
                <td class="px-2 py-2 text-center border-t border-amber-100 whitespace-nowrap">
                    <button type="button" class="text-[#8B4513] hover:underline text-xs section-edit-btn" data-index="${index}">แก้ไข</button>
                    <button type="button" class="text-red-600 hover:underline text-xs ml-1 section-delete-btn" data-index="${index}">ลบ</button>
                </td>
                <td class="px-2 py-2 text-center border-t border-amber-100 font-semibold">${row.sec}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100 text-xs">${facLabel}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100 font-medium">${row.total_std}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_a}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_bb}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_b}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_cc}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_c}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_dd}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_d}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_f}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_i}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_s}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_v}</td>
                <td class="px-2 py-2 text-center border-t border-amber-100">${row.num_w}</td>
                ${evaCell}
            </tr>
        `;
    }).join('');

    tbody.querySelectorAll('.section-edit-btn').forEach((btn) => {
        btn.addEventListener('click', () => editSectionStd(parseInt(btn.dataset.index, 10)));
    });
    tbody.querySelectorAll('.section-delete-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (confirm('คุณต้องการลบ Section นี้หรือไม่?')) {
                removeSectionStd(parseInt(btn.dataset.index, 10));
            }
        });
    });

    const evaHeader = document.getElementById('section-list-eva-col');
    if (evaHeader) evaHeader.classList.toggle('hidden', !showEva);

    refreshSectionSelectOptions();
}

function setupSectionStdManager() {
    document.getElementById('btn-save-section')?.addEventListener('click', () => {
        const result = addOrUpdateSectionFromForm();
        if (!result.ok) {
            alert(result.error);
            return;
        }
    });
    document.getElementById('btn-cancel-section-edit')?.addEventListener('click', cancelSectionEdit);
    document.querySelectorAll('input[name="statuseva"]').forEach((el) => {
        el.addEventListener('change', renderSectionStdList);
    });
    updateSectionFormHint();
}

function collectGradeReportPayload() {
    const { reasonid, reason } = buildReason();
    const selecttype = parseInt(document.getElementById('selecttype')?.value || '1', 10);
    const statuseva = parseInt(document.querySelector('input[name="statuseva"]:checked')?.value || '2', 10);

    document.querySelectorAll('.grade-range-input').forEach((input) => applyGradeRangeFormat(input));

    return {
        report_date: document.getElementById('report-date')?.value || new Date().toISOString().slice(0, 10),
        term: getTermFromForm(),
        year: parseInt(document.getElementById('year-input')?.value || '2568', 10),
        subject_code: document.getElementById('subject-code')?.value?.trim().replace(/\s+/g, ''),
        subject: document.getElementById('subject-name')?.value?.trim(),
        teacher: document.getElementById('teacher-input')?.value?.trim(),
        selecttype,
        degree: selecttype === 1
            ? parseInt(document.getElementById('degree')?.value || '3', 10)
            : 3,
        programid: selecttype === 1
            ? document.getElementById('programid')?.value?.trim() || null
            : null,
        type_course: parseInt(document.querySelector('input[name="type_course"]:checked')?.value || '1', 10),
        mean: (() => {
            const v = document.getElementById('mean-score')?.value?.trim();
            return v ? formatDecimal2(v) : null;
        })(),
        sd: (() => {
            const v = document.getElementById('sd-score')?.value?.trim();
            return v ? formatDecimal2(v) : null;
        })(),
        reasonid,
        reason,
        joint_subject_codes: jointGradeSubjects.map((row) => row.code),
        statuseva,
        totalnumstdevz: statuseva === 2 && document.getElementById('totalnumstdevz')?.value
            ? parseInt(document.getElementById('totalnumstdevz').value, 10)
            : null,
        totalevaluationscore: statuseva === 2 && document.getElementById('totalevaluationscore')?.value
            ? document.getElementById('totalevaluationscore').value
            : null,
        intflag: parseInt(document.querySelector('input[name="intflag"]:checked')?.value || '0', 10),
        score_a: buildScoreRange('range-a-max', 'range-a-min'),
        score_bb: buildScoreRange('range-bp-max', 'range-bp-min'),
        score_b: buildScoreRange('range-b-max', 'range-b-min'),
        score_cc: buildScoreRange('range-cp-max', 'range-cp-min'),
        score_c: buildScoreRange('range-c-max', 'range-c-min'),
        score_dd: buildScoreRange('range-dp-max', 'range-dp-min'),
        score_d: buildScoreRange('range-d-max', 'range-d-min'),
        score_f: buildScoreRange('range-f-max', 'range-f-min'),
        remark: null,
        grade_stds: sectionStdRows.map((row) => {
            const payload = { ...row };
            delete payload.total_std;
            return payload;
        }),
    };
}

const GRADE_RANGE_KEYS = ['a', 'bp', 'b', 'cp', 'c', 'dp', 'd', 'f'];
const GRADE_RANGE_LABELS = { a: 'A', bp: 'B+', b: 'B', cp: 'C+', c: 'C', dp: 'D+', d: 'D', f: 'F' };

function defaultAMaxValue() {
    return isDecimalMode() ? '100.00' : '100';
}

function chainGradeFromMin(gradeKey) {
    const idx = GRADE_RANGE_KEYS.indexOf(gradeKey);
    if (idx < 0) return;

    const minEl = document.getElementById(`range-${gradeKey}-min`);
    const minRaw = minEl?.value?.trim() ?? '';
    const minVal = parseFloat(minRaw);
    const decimal = isDecimalMode();

    if (gradeKey === 'a' && minRaw !== '') {
        const maxA = document.getElementById('range-a-max');
        if (maxA) maxA.value = defaultAMaxValue();
    }

    const nextKey = GRADE_RANGE_KEYS[idx + 1];
    if (!nextKey) return;

    const nextMax = document.getElementById(`range-${nextKey}-max`);
    if (!nextMax) return;

    if (minRaw === '' || Number.isNaN(minVal)) {
        if (minRaw.length > 0 && idx > 0) {
            const prevMin = parseFloat(document.getElementById(`range-${GRADE_RANGE_KEYS[idx - 1]}-min`)?.value);
            if (!Number.isNaN(prevMin)) {
                nextMax.value = decimal ? (prevMin - 0.01).toFixed(2) : String(Math.round(prevMin - 1));
            }
        } else {
            nextMax.value = '';
        }
        return;
    }

    if (minVal > 0 || gradeKey === 'f') {
        nextMax.value = decimal ? (minVal - 0.01).toFixed(2) : String(Math.round(minVal - 1));
    }
    updateGradeRangeColumnHeaders();
}

function recalcAllGradeChains() {
    GRADE_RANGE_KEYS.forEach((key) => chainGradeFromMin(key));
}

function parseRangeInput(id) {
    const raw = document.getElementById(id)?.value?.trim();
    if (raw === '') return null;
    const num = parseFloat(String(raw).replace(/,/g, ''));
    return Number.isNaN(num) ? null : num;
}

function isGradeRangeFilled(key) {
    const max = document.getElementById(`range-${key}-max`)?.value?.trim();
    const min = document.getElementById(`range-${key}-min`)?.value?.trim();
    return Boolean(max && min);
}

function validateGradeRanges() {
    for (const key of GRADE_RANGE_KEYS) {
        const max = parseRangeInput(`range-${key}-max`);
        const min = parseRangeInput(`range-${key}-min`);
        if (max !== null && min !== null && max < min) {
            return `ช่วงคะแนนเกรด ${GRADE_RANGE_LABELS[key]}: ค่าสูงสุดต้องมากกว่าหรือเท่ากับค่าต่ำสุด`;
        }
    }

    for (let i = GRADE_RANGE_KEYS.length - 1; i >= 0; i--) {
        const key = GRADE_RANGE_KEYS[i];
        if (isGradeRangeFilled(key)) {
            for (let j = 0; j < i; j++) {
                if (!isGradeRangeFilled(GRADE_RANGE_KEYS[j])) {
                    return `กรุณากรอกช่วงคะแนนให้ครบถึงเกรด ${GRADE_RANGE_LABELS[GRADE_RANGE_KEYS[j]]} ด้วย`;
                }
            }
            break;
        }
    }

    return null;
}

function chainGradeRanges() {
    GRADE_RANGE_KEYS.forEach((key) => {
        const minEl = document.getElementById(`range-${key}-min`);
        if (!minEl) return;
        minEl.addEventListener('input', () => chainGradeFromMin(key));
        minEl.addEventListener('change', () => {
            applyGradeRangeFormat(minEl);
            chainGradeFromMin(key);
        });
    });

    document.querySelectorAll('.grade-range-clear').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.grade;
            const maxEl = document.getElementById(`range-${key}-max`);
            const minEl = document.getElementById(`range-${key}-min`);
            if (maxEl) maxEl.value = key === 'a' ? defaultAMaxValue() : '';
            if (minEl) minEl.value = '';
            updateGradeRangeColumnHeaders();
        });
    });
}

function toggleSelecttypeFields() {
    const selecttype = document.getElementById('selecttype')?.value;
    const programField = document.getElementById('program-field');
    const degree = document.getElementById('degree');
    const masterOpt = document.getElementById('degree-opt-master');
    const phdOpt = document.getElementById('degree-opt-phd');

    if (selecttype === '1') {
        programField?.classList.remove('hidden');
        if (masterOpt) masterOpt.disabled = false;
        if (phdOpt) phdOpt.disabled = false;
    } else {
        programField?.classList.add('hidden');
        const programSelect = document.getElementById('programid');
        if (programSelect) programSelect.value = '';
        if (degree) degree.value = '3';
        if (masterOpt) masterOpt.disabled = true;
        if (phdOpt) phdOpt.disabled = true;
    }
}

function toggleEvaFields() {
    const statuseva = document.querySelector('input[name="statuseva"]:checked')?.value;
    const reportEva = document.getElementById('report-eva-fields');
    const sectionEva = document.getElementById('section-eva-fields');

    if (reportEva) reportEva.classList.toggle('hidden', statuseva === '1');
    if (sectionEva) sectionEva.classList.toggle('hidden', statuseva === '2');
    renderSectionStdList();
}

function parseScoreRange(value, maxId, minId) {
    if (!value) return;
    const parts = String(value).split('-');
    const maxEl = document.getElementById(maxId);
    const minEl = document.getElementById(minId);
    if (maxEl && parts[0]) maxEl.value = parts[0];
    if (minEl && parts[1]) minEl.value = parts[1];
}

function setRadio(name, value) {
    const el = document.querySelector(`input[name="${name}"][value="${value}"]`);
    if (el) el.checked = true;
}

function populateFormFromRecord(record) {
    if (!record) return;

    document.getElementById('selecttype').value = String(record.selecttype ?? 1);
    document.getElementById('subject-code').value = record.subject_code || '';
    document.getElementById('subject-name').value = record.subject || '';
    setRadio('term', record.term ?? 1);
    if (record.year) document.getElementById('year-input').value = record.year;
    if (record.degree) document.getElementById('degree').value = record.degree;
    if (record.programid) {
        const programSelect = document.getElementById('programid');
        if (programSelect) programSelect.value = String(record.programid);
    }
    document.getElementById('teacher-input').value = record.teacher || '';
    if (record.report_date) document.getElementById('report-date').value = record.report_date;

    setRadio('intflag', record.intflag ?? 0);
    setRadio('statuseva', record.statuseva ?? 2);
    setRadio('type_course', record.type_course ?? record.grade_std?.type_course ?? 1);

    parseScoreRange(record.score_a, 'range-a-max', 'range-a-min');
    parseScoreRange(record.score_bb, 'range-bp-max', 'range-bp-min');
    parseScoreRange(record.score_b, 'range-b-max', 'range-b-min');
    parseScoreRange(record.score_cc, 'range-cp-max', 'range-cp-min');
    parseScoreRange(record.score_c, 'range-c-max', 'range-c-min');
    parseScoreRange(record.score_dd, 'range-dp-max', 'range-dp-min');
    parseScoreRange(record.score_d, 'range-d-max', 'range-d-min');
    parseScoreRange(record.score_f, 'range-f-max', 'range-f-min');
    recalcAllGradeChains();

    const std = record.grade_std || {};
    if (Array.isArray(record.grade_stds) && record.grade_stds.length) {
        setSectionStdRows(record.grade_stds);
        setFacultiesSelected(record.grade_stds[0].fac || '');
    } else if (std.sec || std.fac) {
        setSectionStdRows([std]);
        setFacultiesSelected(std.fac || '');
    } else {
        resetSectionStdRows();
    }

    if (record.mean != null) document.getElementById('mean-score').value = formatDecimal2(record.mean);
    if (record.sd != null) document.getElementById('sd-score').value = formatDecimal2(record.sd);
    if (record.totalnumstdevz != null) document.getElementById('totalnumstdevz').value = record.totalnumstdevz;
    if (record.totalevaluationscore != null) document.getElementById('totalevaluationscore').value = record.totalevaluationscore;

    if (record.reasonid) {
        setRadio('reasonid', record.reasonid);
        if (record.reasonid === 1 && record.reason) {
            const subjects = parseJointGradeReason(record.reason);
            setJointGradeSubjects(subjects);
            enrichJointGradeSubjectNames(subjects).then(setJointGradeSubjects);
        } else if (record.reasonid === 2 && record.reason) {
            const match = String(record.reason).match(/ได้ I เนื่องจาก\s*:?\s*(.*)/);
            const el = document.getElementById('std-i2');
            if (el) el.value = match?.[1]?.trim() || record.reason;
        } else if (record.reasonid === 3 && record.reason) {
            const el = document.getElementById('std-i3');
            if (el) el.value = record.reason;
        }
    }
    updateReasonFieldsState();

    toggleSelecttypeFields();
    toggleEvaFields();
    updateGradeBoundaryHint();
    updateGradeRangeColumnHeaders();
}

let templadeFormBootstrapped = false;

function initTempladeForm(options = {}) {
    if (!templadeFormBootstrapped) {
        chainGradeRanges();
        setupGradeRangeInputs();
        setupIntflagMode();
        setupScoreDecimalInputs();
        setupSubjectAutocomplete();
        setupJointGradeSubjectSearch();
        setupReasonIdFields();
        setupSectionStdManager();
        setupFacMultiSelect();
        document.getElementById('selecttype')?.addEventListener('change', toggleSelecttypeFields);
        document.querySelectorAll('input[name="statuseva"]').forEach((el) => {
            el.addEventListener('change', toggleEvaFields);
        });
        templadeFormBootstrapped = true;
    }

    setupEvaHintPopover(options.teacherHelpImageUrl);
    toggleSelecttypeFields();
    toggleEvaFields();
    updateGradeBoundaryHint();
    updateGradeRangeColumnHeaders();
    renderFacTags();
    updateReasonFieldsState();
    renderSectionStdList();
    updateSectionFormHint();
    refreshSectionSelectOptions();
}
