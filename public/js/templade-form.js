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
            <button type="button" class="w-full text-left px-3 py-2 border-b border-amber-100 last:border-0 hover:bg-amber-50"
                data-code="${item.subject_code.replace(/"/g, '&quot;')}"
                data-name="${item.subject.replace(/"/g, '&quot;')}">
                <span class="font-semibold text-[#5C2E1F]">${item.subject_code}</span>
                <span class="text-gray-600"> — ${item.subject}</span>
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
            const res = await fetch(`/api/subjects/search?q=${encodeURIComponent(q)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            render(await res.json());
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
        const code = document.getElementById('std-i1')?.value?.trim();
        return { reasonid, reason: code ? `ซ้อนวิชากับ :${code}` : null };
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
        programid: selecttype === 2
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
        grade_std: collectGradeStd(),
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

    if (selecttype === '2') {
        programField?.classList.remove('hidden');
    } else {
        programField?.classList.add('hidden');
        const programSelect = document.getElementById('programid');
        if (programSelect) programSelect.value = '';
        if (degree) degree.value = '3';
    }
}

function toggleEvaFields() {
    const statuseva = document.querySelector('input[name="statuseva"]:checked')?.value;
    const reportEva = document.getElementById('report-eva-fields');
    const sectionEva = document.getElementById('section-eva-fields');

    if (reportEva) reportEva.classList.toggle('hidden', statuseva === '1');
    if (sectionEva) sectionEva.classList.toggle('hidden', statuseva === '2');
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

    const std = record.grade_std || {};
    document.getElementById('section-input').value = std.sec ?? record.section ?? 1;
    if (std.fac || record.fac) {
        setFacultiesSelected(std.fac || record.fac);
    }

    const counts = {
        'count-a': std.num_a ?? record.count_a,
        'count-bp': std.num_bb ?? record.count_bp,
        'count-b': std.num_b ?? record.count_b,
        'count-cp': std.num_cc ?? record.count_cp,
        'count-c': std.num_c ?? record.count_c,
        'count-dp': std.num_dd ?? record.count_dp,
        'count-d': std.num_d ?? record.count_d,
        'count-f': std.num_f ?? record.count_f,
        'count-i': std.num_i ?? record.count_i,
        'count-s': std.num_s ?? record.count_s,
        'count-u': std.num_v ?? record.count_u,
        'count-w': std.num_w ?? record.count_w,
    };
    Object.entries(counts).forEach(([id, val]) => {
        const el = document.getElementById(id);
        if (el && val != null) el.value = val;
    });

    if (record.mean != null) document.getElementById('mean-score').value = formatDecimal2(record.mean);
    if (record.sd != null) document.getElementById('sd-score').value = formatDecimal2(record.sd);
    if (record.totalnumstdevz != null) document.getElementById('totalnumstdevz').value = record.totalnumstdevz;
    if (record.totalevaluationscore != null) document.getElementById('totalevaluationscore').value = record.totalevaluationscore;
    if (std.numstdevz != null) document.getElementById('numstdevz').value = std.numstdevz;
    if (std.evaluationscore != null) document.getElementById('evaluationscore').value = std.evaluationscore;

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
}
