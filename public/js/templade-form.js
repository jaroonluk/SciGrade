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

function setupIntflagMode() {
    document.querySelectorAll('input[name="intflag"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('.grade-range-input').forEach((input) => {
                if (!input.readOnly && input.value !== '') {
                    applyGradeRangeFormat(input);
                }
            });
        });
    });
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
    const facSelect = document.getElementById('fac-select');
    const fac = facSelect
        ? Array.from(facSelect.selectedOptions).map(o => o.value).join(',')
        : '';

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
        programid: selecttype === 1 ? document.getElementById('programid')?.value?.trim() || null : null,
        type_course: parseInt(document.querySelector('input[name="type_course"]:checked')?.value || '1', 10),
        mean: document.getElementById('mean-score')?.value || null,
        sd: document.getElementById('sd-score')?.value || null,
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
        remark: document.getElementById('remark-input')?.value?.trim() || null,
        grade_std: collectGradeStd(),
    };
}

function chainGradeRanges() {
    const grades = ['a', 'bp', 'b', 'cp', 'c', 'dp', 'd', 'f'];
    grades.forEach((g, i) => {
        const minEl = document.getElementById(`range-${g}-min`);
        if (!minEl) return;
        minEl.addEventListener('change', () => {
            applyGradeRangeFormat(minEl);
            const next = grades[i + 1];
            if (!next) return;
            const nextMax = document.getElementById(`range-${next}-max`);
            if (nextMax && !nextMax.readOnly) {
                nextMax.value = minEl.value;
            }
        });
    });
}

function toggleSelecttypeFields() {
    const selecttype = document.getElementById('selecttype')?.value;
    const curriculum = document.getElementById('curriculum-fields');
    const service = document.getElementById('service-degree-field');

    if (curriculum) curriculum.classList.toggle('hidden', selecttype !== '1');
    if (service) service.classList.toggle('hidden', selecttype !== '2');
}

function toggleEvaFields() {
    const statuseva = document.querySelector('input[name="statuseva"]:checked')?.value;
    const reportEva = document.getElementById('report-eva-fields');
    const sectionEva = document.getElementById('section-eva-fields');

    if (reportEva) reportEva.classList.toggle('hidden', statuseva === '1');
    if (sectionEva) sectionEva.classList.toggle('hidden', statuseva === '2');
}

function initTempladeForm() {
    chainGradeRanges();
    setupGradeRangeInputs();
    setupIntflagMode();
    toggleSelecttypeFields();
    toggleEvaFields();

    document.getElementById('selecttype')?.addEventListener('change', toggleSelecttypeFields);
    document.querySelectorAll('input[name="statuseva"]').forEach((el) => {
        el.addEventListener('change', toggleEvaFields);
    });
}
