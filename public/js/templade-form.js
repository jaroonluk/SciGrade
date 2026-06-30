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
    if (record.programid) document.getElementById('programid').value = record.programid;
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
        const facSelect = document.getElementById('fac-select');
        const facVal = std.fac || record.fac;
        if (facSelect) {
            Array.from(facSelect.options).forEach((o) => {
                o.selected = facVal.split(',').includes(o.value);
            });
        }
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

    if (record.mean != null) document.getElementById('mean-score').value = record.mean;
    if (record.sd != null) document.getElementById('sd-score').value = record.sd;
    if (record.totalnumstdevz != null) document.getElementById('totalnumstdevz').value = record.totalnumstdevz;
    if (record.totalevaluationscore != null) document.getElementById('totalevaluationscore').value = record.totalevaluationscore;
    if (std.numstdevz != null) document.getElementById('numstdevz').value = std.numstdevz;
    if (std.evaluationscore != null) document.getElementById('evaluationscore').value = std.evaluationscore;
    if (record.remark) document.getElementById('remark-input').value = record.remark;

    toggleSelecttypeFields();
    toggleEvaFields();
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
