function formatDecimal2(value) {
    if (value === '' || value == null) return '';
    const num = parseFloat(String(value).replace(/,/g, ''));
    if (Number.isNaN(num)) return '';
    return num.toFixed(2);
}

function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    if (!t) {
        console[type === 'error' ? 'error' : 'log'](msg);
        return;
    }
    t.textContent = msg;
    t.className = `fixed bottom-6 right-6 px-5 py-3 rounded-lg shadow-lg text-sm font-medium no-print z-50 ${type === 'error' ? 'bg-red-600 text-white' : 'bg-green-700 text-white'}`;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 3000);
}
window.showToast = showToast;

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
        refreshCourseContext();
        applyGraduateFacultyDefault();
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
    // เก็บรหัสเป็นหลัก ใส่ชื่อสั้นๆ เพื่อไม่ให้เกินคอลัมน์ reason
    const parts = subjects.map((s) => {
        const code = String(s.code || '').trim();
        const name = String(s.name || '').trim();
        if (!name) return code;
        const shortName = name.length > 40 ? `${name.slice(0, 37)}...` : name;
        return `${code}|${shortName}`;
    });
    let reason = `ตัดเกรดร่วมกับ :${parts.join(',')}`;
    if (reason.length > 480) {
        reason = `ตัดเกรดร่วมกับ :${subjects.map((s) => s.code).filter(Boolean).join(',')}`;
    }
    return reason;
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

    const canRemove = !window.courseGroupLocked && !window.sharedFieldsLocked;
    tagsEl.innerHTML = jointGradeSubjects.map((s) => `
        <span class="joint-subject-tag inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs max-w-full">
            <span class="font-semibold shrink-0">${s.code}</span>
            ${s.name ? `<span class="text-gray-600 truncate">— ${s.name}</span>` : ''}
            ${canRemove ? `<button type="button" class="joint-subject-remove text-gray-400 hover:text-red-600 shrink-0" data-code="${s.code}" title="ลบ">×</button>` : ''}
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
    const remarksLocked = Boolean(window.courseGroupLocked || window.sharedFieldsLocked);
    const search = document.getElementById('joint-subject-search');
    const panel = document.getElementById('joint-grade-panel');
    const help = document.getElementById('remark-help-text');
    const searchHint = document.getElementById('joint-search-hint');
    if (search) {
        search.disabled = !isJoint || remarksLocked;
        search.classList.toggle('field-locked', remarksLocked);
        search.classList.toggle('hidden', Boolean(window.courseGroupLocked));
    }
    if (searchHint) searchHint.classList.toggle('hidden', Boolean(window.courseGroupLocked));
    if (panel) panel.classList.toggle('opacity-50', !isJoint && !window.courseGroupLocked);
    document.querySelectorAll('input[name="reasonid"]').forEach((el) => {
        el.disabled = remarksLocked;
    });
    ['std-i2', 'std-i3'].forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.readOnly = Boolean(window.sharedFieldsLocked);
        el.classList.toggle('field-locked', Boolean(window.sharedFieldsLocked));
    });
    if (help) {
        help.textContent = window.courseGroupLocked
            ? 'รายวิชานี้มีกลุ่มตัดเกรดร่วมอยู่แล้ว — ไม่ต้องกรอกรหัสซ้ำ'
            : (window.sharedFieldsLocked
                ? 'หมายเหตุถูกดึงจากผู้กรอกก่อน และไม่สามารถแก้ไขได้'
                : 'ข้ามขั้นตอนนี้ได้หากไม่มีหมายเหตุ');
    }
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
        radio.addEventListener('change', () => {
            updateReasonFieldsState();
        });
    });
    updateReasonFieldsState();
}

let evaHintHideTimer = null;
const EVA_HINT_DISMISS_KEY = 'scigrade.evaHintPopoverDismissed';

function isEvaHintDismissed() {
    try {
        return sessionStorage.getItem(EVA_HINT_DISMISS_KEY) === '1';
    } catch {
        return false;
    }
}

function markEvaHintDismissed() {
    try {
        sessionStorage.setItem(EVA_HINT_DISMISS_KEY, '1');
    } catch {
        // ignore quota / private mode
    }
}

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

    const hidePopover = () => {
        clearTimeout(evaHintHideTimer);
        popover.classList.remove('is-visible');
        popover.setAttribute('aria-hidden', 'true');
    };

    const dismissPopover = () => {
        markEvaHintDismissed();
        hidePopover();
    };

    const showNear = (el) => {
        if (isEvaHintDismissed()) return;
        if (el.closest('.hidden')) return;

        clearTimeout(evaHintHideTimer);
        const rect = el.getBoundingClientRect();
        const popW = 440;
        let left = rect.left;
        if (left + popW > window.innerWidth - 16) {
            left = window.innerWidth - popW - 16;
        }
        let top = rect.bottom + 8;
        const popH = popover.offsetHeight || img?.offsetHeight || 280;
        if (top + popH > window.innerHeight - 16) {
            top = Math.max(16, rect.top - popH - 8);
        }
        popover.style.left = `${Math.max(16, left)}px`;
        popover.style.top = `${top}px`;
        popover.classList.add('is-visible');
        popover.setAttribute('aria-hidden', 'false');
    };

    const scheduleHide = () => {
        clearTimeout(evaHintHideTimer);
        evaHintHideTimer = setTimeout(() => {
            if (popover.contains(document.activeElement)) return;
            const active = document.activeElement;
            if (active?.classList.contains('eva-hint-field')) return;
            hidePopover();
        }, 200);
    };

    if (popover.dataset.evaHintBound === '1') return;
    popover.dataset.evaHintBound = '1';

    document.getElementById('eva-hint-popover-close')?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        dismissPopover();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (!popover.classList.contains('is-visible')) return;
        e.preventDefault();
        dismissPopover();
    });

    document.addEventListener('focusin', (e) => {
        if (e.target.matches?.('.eva-hint-field')) showNear(e.target);
    });
    document.addEventListener('focusout', (e) => {
        if (e.target.matches?.('.eva-hint-field') || popover.contains(e.target)) scheduleHide();
    });
    document.addEventListener('mousedown', (e) => {
        if (e.target.matches?.('.eva-hint-field')) {
            showNear(e.target);
            return;
        }
        if (popover.classList.contains('is-visible') && !popover.contains(e.target)) {
            dismissPopover();
        }
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

const GRADUATE_FACULTY = 'GS';

function inferDegreeFromSubjectCode(raw) {
    const code = String(raw || '').replace(/\s+/g, '').toUpperCase();
    if (!code) return 3;
    const letters = (code.match(/^[A-Z]+/) || [''])[0];
    const digits = code.slice(letters.length).replace(/\D/g, '');
    if (!digits) return 3;
    const level = parseInt(digits.charAt(letters.length >= 2 && digits.length >= 3 ? 2 : 0), 10);
    if (Number.isNaN(level)) return 3;
    if (level >= 7) return 7;
    if (level >= 5) return 5;
    return 3;
}

function currentCourseDegree() {
    const fromCode = inferDegreeFromSubjectCode(document.getElementById('subject-code')?.value);
    const known = [window.parsedCourseDegree, window.reportDegree]
        .map((n) => Number(n))
        .find((n) => n === 5 || n === 7);
    if (fromCode === 5 || fromCode === 7) return fromCode;
    return known || 3;
}

function isGraduateCourse() {
    return currentCourseDegree() !== 3;
}

function applyGraduateFacultyDefault(options = {}) {
    const hint = document.getElementById('fac-graduate-hint');
    const graduate = isGraduateCourse();
    if (hint) hint.classList.toggle('hidden', !graduate);
    if (!graduate || editingSectionIndex !== null) return;

    const gs = Array.from(document.querySelectorAll('.fac-checkbox'))
        .find((cb) => String(cb.value).toUpperCase() === GRADUATE_FACULTY);
    if (!gs) return;

    if (!options.resetForm && getCurrentFacString()) return;

    setFacultiesSelected(gs.value);
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

    const statuseva = parseInt(document.querySelector('input[name="statuseva"]:checked')?.value || '2', 10);
    if (statuseva === 1) {
        const scoreRaw = document.getElementById('evaluationscore')?.value?.trim();
        if (scoreRaw !== '') {
            const score = Number(scoreRaw);
            if (Number.isNaN(score) || score < 0 || score > 5) {
                return 'ผลการประเมินรายวิชาโดยนักศึกษาต้องอยู่ระหว่าง 0–5 คะแนน (ช่องนี้ไม่ใช่จำนวนนักศึกษาที่เข้าประเมิน)';
            }
        }
    }

    return null;
}

function getCurrentFacString() {
    return Array.from(document.querySelectorAll('.fac-checkbox:checked')).map((c) => c.value).join(',');
}

function isPriorReportedSection(sec) {
    const n = Number(sec);
    if (!Array.isArray(window.priorReportedSections) || !window.priorReportedSections.includes(n)) {
        return false;
    }

    const currentId = currentWizardReportId();
    const detail = window.priorSectionDetails?.[n];
    const inCurrentForm = sectionStdRows.some((row) => Number(row.sec) === n);

    // Section ของรายงานที่กำลังเปิด/แนบอยู่ และอยู่ในฟอร์มแล้ว
    // (รวมกรณีบันทึกขั้น 5 แล้ว refresh แล้วบันทึกขั้น 6 ซ้ำ) — ไม่บล็อก
    if (inCurrentForm && (window.wizardConfig?.openedAsEdit || window.appendingToPriorReport)) {
        return false;
    }

    if (currentId && detail?.grade_id && String(detail.grade_id) === String(currentId)) {
        if (window.wizardConfig?.openedAsEdit || inCurrentForm) {
            return false;
        }
    }

    return true;
}

function priorSectionContactName(sec) {
    const detail = window.priorSectionDetails?.[Number(sec)];
    if (detail?.filled_by) return String(detail.filled_by).trim();
    const prior = window.courseContext?.prior;
    return (prior?.filled_by || prior?.teacher || 'ผู้กรอกก่อนหน้า').trim();
}

function priorSectionConflictMessage(sec) {
    const detail = window.priorSectionDetails?.[Number(sec)];
    const code = (detail?.subject_code
        || document.getElementById('subject-code')?.value
        || '').trim().replace(/\s+/g, '') || '-';
    const name = (detail?.subject
        || document.getElementById('subject-name')?.value
        || '').trim() || '-';
    const filledBy = priorSectionContactName(sec);
    const priorId = detail?.grade_id;
    if (priorId) {
        return `รหัสวิชา ${code} ชื่อวิชา ${name} Section ${sec} ได้มีการบันทึกผลการส่งเกรดแล้วโดย ${filledBy} — กรุณาเปิดแก้ไขรายงานเดิม (เลขที่ ${priorId}) หรือเลือก Section อื่น`;
    }
    return `รหัสวิชา ${code} ชื่อวิชา ${name} Section ${sec} ได้มีการบันทึกผลการส่งเกรดแล้ว กรุณาติดต่อ ${filledBy}`;
}

function isSectionOptionUsed(sec, excludeIndex = null) {
    if (isPriorReportedSection(sec)) return true;
    const fac = getCurrentFacString();
    return sectionStdRows.some((row, idx) => {
        if (excludeIndex !== null && idx === excludeIndex) return false;
        if (Number(row.sec) !== Number(sec)) return false;
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
    if (editingSectionIndex !== null) {
        hint.textContent = `กำลังแก้ไข Section ${sectionStdRows[editingSectionIndex]?.sec ?? ''} — กด «บันทึก Section นี้» เพื่อยืนยัน`;
        return;
    }
    hint.textContent = window.priorReportedSections?.length
        ? 'รายวิชานี้มีผู้กรอก Section บางส่วนแล้ว — กรอกเฉพาะ Section ที่ยังไม่มี ระบบจะเพิ่มเข้าในรายงานเดิมอัตโนมัติ'
        : 'กรอกข้อมูล Section แล้วกด «บันทึก Section นี้» — Section ที่บันทึกแล้วจะไม่แสดงในรายการ';
}

function cancelSectionEdit() {
    editingSectionIndex = null;
    clearGradeStdFormCounts();
    document.getElementById('section-input').value = '1';
    document.querySelectorAll('.fac-checkbox').forEach((cb) => { cb.checked = false; });
    renderFacTags();
    setRadio('type_course', 1);
    applyGraduateFacultyDefault({ resetForm: true });
    updateSectionFormHint();
    const cancelBtn = document.getElementById('btn-cancel-section-edit');
    if (cancelBtn) cancelBtn.classList.add('hidden');
    refreshSectionSelectOptions();
}

function applyParsedSectionFromPdf(parsed) {
    if (!parsed) return;

    if (!window.sharedFieldsLocked) {
        if (parsed.intflag != null) setRadio('intflag', parsed.intflag);
        parseScoreRange(parsed.score_a, 'range-a-max', 'range-a-min');
        parseScoreRange(parsed.score_bb, 'range-bp-max', 'range-bp-min');
        parseScoreRange(parsed.score_b, 'range-b-max', 'range-b-min');
        parseScoreRange(parsed.score_cc, 'range-cp-max', 'range-cp-min');
        parseScoreRange(parsed.score_c, 'range-c-max', 'range-c-min');
        parseScoreRange(parsed.score_dd, 'range-dp-max', 'range-dp-min');
        parseScoreRange(parsed.score_d, 'range-d-max', 'range-d-min');
        parseScoreRange(parsed.score_f, 'range-f-max', 'range-f-min');
        recalcAllGradeChains();
    }
    if (parsed.degree != null) window.parsedCourseDegree = Number(parsed.degree);
    if (parsed.type_course != null) setRadio('type_course', parsed.type_course);

    const std = Array.isArray(parsed.grade_stds) && parsed.grade_stds.length
        ? parsed.grade_stds[0]
        : null;
    if (!std) return;

    const sec = std.sec ?? 1;
    const existingIndex = sectionStdRows.findIndex((row) => Number(row.sec) === Number(sec));
    editingSectionIndex = existingIndex >= 0 ? existingIndex : null;

    const existing = existingIndex >= 0 ? sectionStdRows[existingIndex] : null;
    const fac = existing?.fac
        ? existing.fac
        : (isGraduateCourse() ? GRADUATE_FACULTY : (std.fac || ''));
    document.getElementById('section-input').value = String(sec);
    setFacultiesSelected(fac);
    setRadio('type_course', std.type_course ?? parsed.type_course ?? 1);
    loadGradeStdToForm({
        ...std,
        sec,
        fac,
        type_course: std.type_course ?? parsed.type_course ?? 1,
    });

    const saved = addOrUpdateSectionFromForm();
    if (!saved.ok) {
        showToast(saved.error || 'อ่านไฟล์แล้ว แต่บันทึก Section ไม่สำเร็จ — กรุณาตรวจสอบคณะ/ข้อมูล', 'error');
    }
}

async function uploadSectionRegistrarPdf(file, options = {}) {
    const attachOnly = Boolean(options.attachOnly);
    const expectedSection = options.expectedSection != null ? Number(options.expectedSection) : null;
    const silentToast = Boolean(options.silentToast);
    const statusEl = attachOnly && expectedSection
        ? document.getElementById(`wizard-reg-status-${expectedSection}`)
        : (attachOnly ? null : document.getElementById('section-pdf-upload-status'));
    const errorEl = attachOnly && expectedSection
        ? document.getElementById(`wizard-reg-error-${expectedSection}`)
        : (attachOnly ? null : document.getElementById('section-pdf-upload-error'));
    const input = attachOnly && expectedSection
        ? document.getElementById(`wizard-reg-input-${expectedSection}`)
        : (attachOnly ? document.getElementById('wizard-reg-bulk-upload') : document.getElementById('section-pdf-upload'));

    const showError = (msg) => {
        if (errorEl) {
            errorEl.textContent = msg;
            errorEl.classList.remove('hidden');
        }
        if (statusEl && !attachOnly) statusEl.textContent = '';
        if (!silentToast) showToast(msg, 'error');
    };

    if (errorEl) {
        errorEl.textContent = '';
        errorEl.classList.add('hidden');
    }

    const subjectCode = document.getElementById('subject-code')?.value?.trim();
    const term = document.querySelector('input[name="term"]:checked')?.value
        || document.getElementById('term-input')?.value;
    const year = document.getElementById('year-input')?.value;

    if (!subjectCode) {
        const msg = 'กรุณากรอกรหัสวิชาก่อนอัปโหลดไฟล์ หรือกรอกจำนวนนักศึกษาเอง';
        showError(msg);
        if (input && !options.keepInput) input.value = '';
        return { ok: false, error: msg };
    }
    if (!term || !year) {
        const msg = 'กรุณาเลือกภาคการศึกษาและปีการศึกษาก่อนอัปโหลดไฟล์ หรือกรอกจำนวนนักศึกษาเอง';
        showError(msg);
        if (input && !options.keepInput) input.value = '';
        return { ok: false, error: msg };
    }

    if (statusEl) statusEl.textContent = attachOnly ? 'กำลังอัปโหลด...' : 'กำลังอ่านไฟล์...';

    const body = new FormData();
    body.append('grade_file', file);
    body.append('subject_code', subjectCode);
    body.append('term', term);
    body.append('year', year);
    if (attachOnly) body.append('attach_only', '1');
    if (expectedSection != null && expectedSection > 0) {
        body.append('expected_section', String(expectedSection));
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    try {
        const res = await fetch('/grade-reports/parse-section-pdf', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const details = Array.isArray(data.mismatch) && data.mismatch.length
                ? data.mismatch.join(' ')
                : (data.errors?.grade_file
                    ? [].concat(data.errors.grade_file).join(' ')
                    : null);
            throw new Error(details || data.message || 'ไม่สามารถอัปโหลดไฟล์ได้ กรุณาอัปโหลดไฟล์ใหม่ หรือกรอกข้อมูลเอง');
        }

        const sec = Number(data.section || data.parsed?.grade_stds?.[0]?.sec || expectedSection || 0);
        if (attachOnly && sec > 0) {
            const required = requiredRegSections();
            if (required.length && !required.includes(sec)) {
                throw new Error(`ไฟล์นี้เป็น Section ${sec} ซึ่งไม่อยู่ในรายการที่ต้องแนบ (${required.join(', ')})`);
            }
            // ถ้า Section นี้มีไฟล์บันทึกในระบบแล้ว ให้ลบก่อนแล้วค่อยแทนด้วยไฟล์ใหม่
            const existing = window.regUploadBySection[sec];
            if (existing?.source === 'saved') {
                const fileId = resolveSavedRegFileId(sec, existing, window.wizardConfig);
                if (window.wizardConfig?.currentReportId && fileId) {
                    const delRes = await fetch(`/api/grade-reports/${window.wizardConfig.currentReportId}/files/${fileId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    });
                    if (!delRes.ok) {
                        const delData = await delRes.json().catch(() => ({}));
                        throw new Error(delData.message || `ไม่สามารถแทนที่ไฟล์ Section ${sec} ได้`);
                    }
                }
            }
        }

        markRegUploadForSection(sec, data.file_name || file.name || 'ไฟล์ มข.11', 'pending', {
            viewUrl: data.view_url || (sec > 0 ? `/grade-reports/pending-registrar/${sec}` : null),
        });

        if (!attachOnly && data.parsed) {
            applyParsedSectionFromPdf(data.parsed);
        }

        window.wizardHasPendingReg = true;
        window.pendingRegFileName = data.file_name || file.name || 'ไฟล์ มข.11';
        if (window.wizardConfig) {
            window.wizardConfig.hasPendingRegistrar = true;
            window.wizardConfig.regFilledFromPdf = true;
            if (!options.skipRender) {
                persistWizardState(window.wizardConfig, attachOnly ? 6 : 5);
                updateAttachmentChecklist(window.wizardConfig);
                renderRegUploadSlots(window.wizardConfig);
                syncWizardRegStatus(window.wizardConfig);
            }
        }
        if (statusEl && !attachOnly) {
            statusEl.textContent = data.file_name
                ? `อ่านสำเร็จ: ${data.file_name}`
                : 'อ่านไฟล์สำเร็จ';
        }
        if (!silentToast) {
            showToast(data.message || 'แนบแบบฟอร์ม มข.11 สำเร็จ', 'success');
        }
        return { ok: true, section: sec, fileName: data.file_name || file.name };
    } catch (err) {
        const msg = err?.message || 'ไม่สามารถอัปโหลดไฟล์ได้ กรุณาอัปโหลดไฟล์ใหม่ หรือกรอกข้อมูลเอง';
        showError(msg);
        return { ok: false, error: msg };
    } finally {
        if (input && !options.keepInput) input.value = '';
    }
}

function setupSectionPdfUpload() {
    const input = document.getElementById('section-pdf-upload');
    if (!input || input.dataset.bound === '1') return;
    input.dataset.bound = '1';
    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (file) uploadSectionRegistrarPdf(file);
    });
}

function addOrUpdateSectionFromForm() {
    const error = validateSectionStdForm();
    if (error) return { ok: false, error };

    const row = normalizeSectionRow(collectGradeStd());
    if (isPriorReportedSection(row.sec)) {
        return { ok: false, error: priorSectionConflictMessage(row.sec) };
    }
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
    applyGraduateFacultyDefault({ resetForm: true });
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
        const facMissing = !String(row.fac || '').trim();
        const facLabel = facMissing
            ? '<span class="text-red-700 font-semibold">ยังไม่เลือกคณะ</span>'
            : `${String(row.fac || '').toUpperCase()}${TYPE_COURSE_SUFFIX[row.type_course] || ''}`;
        const evaCell = showEva
            ? `<td class="px-2 py-2 text-center border-t border-amber-100">${row.evaluationscore ?? '—'}</td>`
            : '';
        const rowClass = [
            editingSectionIndex === index ? 'bg-amber-50' : '',
            facMissing ? 'bg-red-50' : '',
        ].filter(Boolean).join(' ');
        return `
            <tr class="${rowClass}">
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
    const statuseva = parseInt(document.querySelector('input[name="statuseva"]:checked')?.value || '2', 10);

    document.querySelectorAll('.grade-range-input').forEach((input) => applyGradeRangeFormat(input));

    const gradeStds = sectionStdRows.map((row) => {
        const payload = { ...row };
        delete payload.total_std;

        // โหมดรวม (2): ผลประเมินอยู่ที่ระดับรายงาน ไม่ส่งค่าในแต่ละ Section
        // โหมดตาม Section (1): ใช้เฉพาะค่าใน Section
        if (statuseva === 2) {
            payload.evaluationscore = null;
            payload.numstdevz = null;
        }

        return payload;
    });

    return {
        report_date: document.getElementById('report-date')?.value || new Date().toISOString().slice(0, 10),
        term: getTermFromForm(),
        year: parseInt(document.getElementById('year-input')?.value || '2568', 10),
        subject_code: document.getElementById('subject-code')?.value?.trim().replace(/\s+/g, ''),
        subject: document.getElementById('subject-name')?.value?.trim(),
        teacher: document.getElementById('teacher-input')?.value?.trim(),
        selecttype: 1,
        degree: currentCourseDegree(),
        programid: null,
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
        score_s: buildScoreRange('range-s-max', 'range-s-min'),
        score_u: buildScoreRange('range-u-max', 'range-u-min'),
        grade_scheme: currentGradeScheme(),
        remark: null,
        grade_stds: gradeStds,
        append_sections: Boolean(window.appendingToPriorReport),
    };
}

function validateEvaluationScores(payload) {
    const statuseva = parseInt(payload.statuseva || 2, 10);

    if (statuseva === 2) {
        const total = payload.totalevaluationscore;
        if (total !== null && total !== '' && Number(total) > 5) {
            return 'ขั้นตอนที่ 4: ผลการประเมินรายวิชาโดยนักศึกษาต้องไม่เกิน 5 คะแนน (ช่องนี้ไม่ใช่จำนวนนักศึกษาที่เข้าประเมิน)';
        }
        return null;
    }

    for (let i = 0; i < (payload.grade_stds || []).length; i += 1) {
        const score = payload.grade_stds[i]?.evaluationscore;
        if (score !== null && score !== '' && Number(score) > 5) {
            return `ขั้นตอนที่ 5 — Section ${payload.grade_stds[i]?.sec ?? (i + 1)}: ผลการประเมินรายวิชาต้องไม่เกิน 5 คะแนน (ช่องนี้ไม่ใช่จำนวนนักศึกษาที่เข้าประเมิน)`;
        }
    }

    return null;
}

function validateGradeReportBeforeSave(payload) {
    if (!payload.subject_code) {
        return 'ขั้นตอนที่ 1: กรุณากรอกรหัสวิชา';
    }
    if (!payload.subject) {
        return 'ขั้นตอนที่ 1: กรุณากรอกชื่อวิชา';
    }
    if (!payload.teacher) {
        return 'ขั้นตอนที่ 1: กรุณากรอกชื่ออาจารย์ผู้สอน';
    }
    if (!payload.term || ![1, 2, 3].includes(Number(payload.term))) {
        return 'ขั้นตอนที่ 1: กรุณาเลือกภาคการศึกษา';
    }
    if (!payload.year || Number(payload.year) < 2500) {
        return 'ขั้นตอนที่ 1: กรุณาระบุปีการศึกษา (พ.ศ.)';
    }
    if (Number(payload.reasonid) === 1 && !(payload.joint_subject_codes || []).length && !window.courseGroupLocked) {
        return 'ขั้นตอนที่ 2: กรุณาเลือกวิชาที่ตัดเกรดร่วมกับอย่างน้อย 1 วิชา';
    }
    if (payload.reason && String(payload.reason).length > 500) {
        return 'ขั้นตอนที่ 2: ข้อความหมายเหตุ/วิชาตัดเกรดร่วมยาวเกินไป — ลดจำนวนวิชาหรือชื่อวิชา';
    }

    const rangeError = validateGradeRanges();
    if (rangeError) return `ขั้นตอนที่ 3: ${rangeError}`;

    const evaError = validateEvaluationScores(payload);
    if (evaError) return evaError;

    if (!payload.grade_stds?.length) {
        return 'ขั้นตอนที่ 5: กรุณาเพิ่มข้อมูลจำนวนนักศึกษาอย่างน้อย 1 Section (กด «บันทึก Section นี้» ก่อน)';
    }

    for (let i = 0; i < payload.grade_stds.length; i += 1) {
        const row = payload.grade_stds[i];
        const sec = row?.sec ?? (i + 1);
        if (isPriorReportedSection(sec)) {
            return `ขั้นตอนที่ 5: ${priorSectionConflictMessage(sec)}`;
        }
        if (!String(row?.fac || '').trim()) {
            return `ขั้นตอนที่ 5: Section ${sec} ยังไม่ได้เลือกคณะ — เปิดแก้ไข Section แล้วเลือกคณะก่อนบันทึก`;
        }
        if (String(row.fac).length > 255) {
            return `ขั้นตอนที่ 5: Section ${sec} เลือกคณะมากเกินไป — แบ่งเป็นหลาย Section หรือลดจำนวนคณะ`;
        }
    }

    return null;
}

const GRADE_RANGE_KEYS = ['a', 'bp', 'b', 'cp', 'c', 'dp', 'd', 'f'];
const SU_RANGE_KEYS = ['s', 'u'];
const GRADE_RANGE_LABELS = { a: 'A', bp: 'B+', b: 'B', cp: 'C+', c: 'C', dp: 'D+', d: 'D', f: 'F', s: 'S', u: 'U' };

function defaultAMaxValue() {
    return isDecimalMode() ? '100.00' : '100';
}

function currentGradeScheme() {
    const credit = document.getElementById('scheme-credit')?.checked;
    const audit = document.getElementById('scheme-audit')?.checked;
    if (credit && audit) return 'both';
    if (audit) return 'audit';
    return 'credit';
}

function visibleRangeKeys() {
    const scheme = currentGradeScheme();
    if (scheme === 'audit') return SU_RANGE_KEYS;
    if (scheme === 'both') return [...GRADE_RANGE_KEYS, ...SU_RANGE_KEYS];
    return GRADE_RANGE_KEYS;
}

function applyGradeSchemeUi() {
    const scheme = currentGradeScheme();
    const showCredit = scheme === 'credit' || scheme === 'both';
    const showAudit = scheme === 'audit' || scheme === 'both';
    document.getElementById('credit-range-table')?.classList.toggle('hidden', !showCredit);
    document.getElementById('audit-range-table')?.classList.toggle('hidden', !showAudit);
    document.querySelectorAll('#student-grade-table th, #student-grade-table td').forEach((cell, idx) => {
        const col = idx % 12;
        const isCreditCol = col <= 7;
        const isSuCol = col === 9 || col === 10;
        if (scheme === 'audit' && isCreditCol) cell.classList.add('opacity-40');
        else if (scheme === 'credit' && isSuCol) cell.classList.add('opacity-40');
        else cell.classList.remove('opacity-40');
    });
}

function setGradeScheme(scheme) {
    const credit = document.getElementById('scheme-credit');
    const audit = document.getElementById('scheme-audit');
    const both = document.getElementById('scheme-both');
    if (!credit || !audit || !both) return;
    if (scheme === 'both') {
        credit.checked = true;
        audit.checked = true;
        both.checked = true;
    } else if (scheme === 'audit') {
        credit.checked = false;
        audit.checked = true;
        both.checked = false;
    } else {
        credit.checked = true;
        audit.checked = false;
        both.checked = false;
    }
    applyGradeSchemeUi();
}

function setupGradeScheme() {
    const credit = document.getElementById('scheme-credit');
    const audit = document.getElementById('scheme-audit');
    const both = document.getElementById('scheme-both');
    if (!credit || !audit || !both) return;

    const sync = (source) => {
        if (source === 'both') {
            credit.checked = both.checked;
            audit.checked = both.checked;
            if (!both.checked) credit.checked = true;
        } else {
            both.checked = credit.checked && audit.checked;
            if (!credit.checked && !audit.checked) credit.checked = true;
        }
        applyGradeSchemeUi();
    };

    credit.addEventListener('change', () => sync('credit'));
    audit.addEventListener('change', () => sync('audit'));
    both.addEventListener('change', () => sync('both'));
    applyGradeSchemeUi();
}

function chainGradeFromMin(gradeKey) {
    const keys = GRADE_RANGE_KEYS.includes(gradeKey) ? GRADE_RANGE_KEYS : SU_RANGE_KEYS;
    const idx = keys.indexOf(gradeKey);
    if (idx < 0) return;

    const minEl = document.getElementById(`range-${gradeKey}-min`);
    const minRaw = minEl?.value?.trim() ?? '';
    const minVal = parseFloat(minRaw);
    const decimal = isDecimalMode();

    if ((gradeKey === 'a' || gradeKey === 's') && minRaw !== '') {
        const maxEl = document.getElementById(`range-${gradeKey}-max`);
        if (maxEl) maxEl.value = defaultAMaxValue();
    }

    const nextKey = keys[idx + 1];
    if (!nextKey) return;

    const nextMax = document.getElementById(`range-${nextKey}-max`);
    if (!nextMax) return;

    if (minRaw === '' || Number.isNaN(minVal)) {
        if (minRaw.length > 0 && idx > 0) {
            const prevMin = parseFloat(document.getElementById(`range-${keys[idx - 1]}-min`)?.value);
            if (!Number.isNaN(prevMin)) {
                nextMax.value = decimal ? (prevMin - 0.01).toFixed(2) : String(Math.round(prevMin - 1));
            }
        } else {
            nextMax.value = '';
        }
        return;
    }

    if (minVal > 0 || gradeKey === 'f' || gradeKey === 'u') {
        nextMax.value = decimal ? (minVal - 0.01).toFixed(2) : String(Math.round(minVal - 1));
    }
    updateGradeRangeColumnHeaders();
}

function recalcAllGradeChains() {
    [...GRADE_RANGE_KEYS, ...SU_RANGE_KEYS].forEach((key) => chainGradeFromMin(key));
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
    const keys = visibleRangeKeys();
    for (const key of keys) {
        const max = parseRangeInput(`range-${key}-max`);
        const min = parseRangeInput(`range-${key}-min`);
        if (max !== null && min !== null && max < min) {
            return `ช่วงคะแนนเกรด ${GRADE_RANGE_LABELS[key]}: ค่าสูงสุดต้องมากกว่าหรือเท่ากับค่าต่ำสุด`;
        }
    }

    const groups = currentGradeScheme() === 'both'
        ? [GRADE_RANGE_KEYS, SU_RANGE_KEYS]
        : [keys];

    for (const group of groups) {
        for (let i = group.length - 1; i >= 0; i--) {
            const key = group[i];
            if (isGradeRangeFilled(key)) {
                for (let j = 0; j < i; j++) {
                    if (!isGradeRangeFilled(group[j])) {
                        return `กรุณากรอกช่วงคะแนนให้ครบถึงเกรด ${GRADE_RANGE_LABELS[group[j]]} ด้วย`;
                    }
                }
                break;
            }
        }
    }

    return null;
}

function chainGradeRanges() {
    [...GRADE_RANGE_KEYS, ...SU_RANGE_KEYS].forEach((key) => {
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
            if (maxEl) maxEl.value = (key === 'a' || key === 's') ? defaultAMaxValue() : '';
            if (minEl) minEl.value = '';
            updateGradeRangeColumnHeaders();
        });
    });
}

function toggleEvaFields() {
    const statuseva = document.querySelector('input[name="statuseva"]:checked')?.value;
    const reportEva = document.getElementById('report-eva-fields');
    const sectionEva = document.getElementById('section-eva-fields');

    if (reportEva) reportEva.classList.toggle('hidden', statuseva === '1');
    if (sectionEva) sectionEva.classList.toggle('hidden', statuseva === '2');

    // เคลียร์ค่าโหมดที่ไม่ได้ใช้ เพื่อกันค่าค้างจาก autofill / สลับโหมด
    if (statuseva === '1') {
        const totalNum = document.getElementById('totalnumstdevz');
        const totalScore = document.getElementById('totalevaluationscore');
        if (totalNum) totalNum.value = '';
        if (totalScore) totalScore.value = '';
        sectionStdRows = sectionStdRows.map((row) => ({
            ...row,
            // คงค่าเดิมของ section ที่บันทึกไว้แล้วในโหมด 1
        }));
    } else if (statuseva === '2') {
        const num = document.getElementById('numstdevz');
        const score = document.getElementById('evaluationscore');
        if (num) num.value = '';
        if (score) score.value = '';
        sectionStdRows = sectionStdRows.map((row) => ({
            ...row,
            evaluationscore: null,
            numstdevz: null,
        }));
    }

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

    window.reportDegree = record.degree != null ? Number(record.degree) : null;
    if (record.degree != null && (Number(record.degree) === 5 || Number(record.degree) === 7)) {
        window.parsedCourseDegree = Number(record.degree);
    }

    document.getElementById('subject-code').value = record.subject_code || '';
    document.getElementById('subject-name').value = record.subject || '';
    setRadio('term', record.term ?? 1);
    if (record.year) document.getElementById('year-input').value = record.year;
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
    parseScoreRange(record.score_s, 'range-s-max', 'range-s-min');
    parseScoreRange(record.score_u, 'range-u-max', 'range-u-min');
    setGradeScheme(record.grade_scheme || (record.score_s || record.score_u ? 'both' : 'credit'));
    recalcAllGradeChains();

    const std = record.grade_std || {};
    const keepSavedFac = Boolean(record.__backendId || record.grade_id);
    const withDefaultFac = (rows) => rows.map((row) => (
        keepSavedFac || !isGraduateCourse()
            ? row
            : { ...row, fac: GRADUATE_FACULTY }
    ));
    if (Array.isArray(record.grade_stds) && record.grade_stds.length) {
        setSectionStdRows(withDefaultFac(record.grade_stds));
    } else if (std.sec || std.fac) {
        setSectionStdRows(withDefaultFac([std]));
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

    toggleEvaFields();
    updateGradeBoundaryHint();
    updateGradeRangeColumnHeaders();
    applyGraduateFacultyDefault();
    refreshCourseContext();
    if (window.wizardConfig) {
        renderRegUploadSlots(window.wizardConfig);
        syncWizardRegStatus(window.wizardConfig);
    }
}

let templadeFormBootstrapped = false;

function initTempladeForm(options = {}) {
    if (!templadeFormBootstrapped) {
        chainGradeRanges();
        setupGradeRangeInputs();
        setupGradeScheme();
        setupIntflagMode();
        setupScoreDecimalInputs();
        setupSubjectAutocomplete();
        setupJointGradeSubjectSearch();
        setupReasonIdFields();
        setupSectionStdManager();
        setupFacMultiSelect();
        setupSectionPdfUpload();
        setupWizardRegUpload();
        setupCourseContextWatchers();
        document.querySelectorAll('input[name="statuseva"]').forEach((el) => {
            el.addEventListener('change', toggleEvaFields);
        });
        templadeFormBootstrapped = true;
    }

    setupEvaHintPopover(options.teacherHelpImageUrl);
    toggleEvaFields();
    updateGradeBoundaryHint();
    updateGradeRangeColumnHeaders();
    renderFacTags();
    updateReasonFieldsState();
    renderSectionStdList();
    updateSectionFormHint();
    refreshSectionSelectOptions();
    applyGraduateFacultyDefault();
}

window.courseContext = null;
window.priorReportedSections = [];
window.priorSectionDetails = {};
window.sharedFieldsLocked = false;
window.courseGroupLocked = false;
window.priorSectionEvaEditable = false;

let courseContextSeq = 0;
let priorCriteriaApplied = false;
let groupMembersApplied = false;

function currentWizardReportId() {
    return window.wizardConfig?.currentReportId || null;
}

function setupCourseContextWatchers() {
    let timer = null;
    const schedule = () => {
        clearTimeout(timer);
        timer = setTimeout(() => refreshCourseContext(), 280);
    };
    document.getElementById('subject-code')?.addEventListener('blur', () => {
        schedule();
        applyGraduateFacultyDefault();
    });
    document.getElementById('year-input')?.addEventListener('change', schedule);
    document.querySelectorAll('input[name="term"]').forEach((el) => {
        el.addEventListener('change', schedule);
    });
}

async function refreshCourseContext() {
    const code = document.getElementById('subject-code')?.value?.trim().replace(/\s+/g, '') || '';
    const term = document.querySelector('input[name="term"]:checked')?.value || '';
    const year = document.getElementById('year-input')?.value || '';
    const exclude = window.appendingToPriorReport ? '' : (currentWizardReportId() || '');
    const seq = ++courseContextSeq;

    if (!code) {
        applyCourseContext({
            grouped: false,
            members: [],
            prior: null,
            reported_sections: [],
            reported_section_details: [],
        });
        return;
    }

    const params = new URLSearchParams({ subject_code: code });
    if (term) params.set('term', term);
    if (year) params.set('year', year);
    if (exclude) params.set('exclude', String(exclude));

    try {
        const res = await fetch(`/api/grade-reports/course-context?${params}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await res.json().catch(() => ({}));
        if (seq !== courseContextSeq) return;
        applyCourseContext(res.ok ? data : {
            grouped: false,
            members: [],
            prior: null,
            reported_sections: [],
            reported_section_details: [],
        });
    } catch {
        if (seq !== courseContextSeq) return;
        applyCourseContext({
            grouped: false,
            members: [],
            prior: null,
            reported_sections: [],
            reported_section_details: [],
        });
    }
}

function applyCourseContext(data) {
    window.courseContext = data || {};
    const thesisBlocked = data?.exam_reportable === false;
    renderCourseThesisBanner(thesisBlocked, data?.message);
    if (thesisBlocked) {
        data = {
            grouped: false,
            members: [],
            prior: null,
            reported_sections: [],
            reported_section_details: [],
        };
    }
    const members = Array.isArray(data?.members) ? data.members : [];
    const grouped = Boolean(data?.grouped && members.length);
    const prior = data?.prior && data.prior.exists ? data.prior : null;

    window.priorSectionDetails = {};
    (Array.isArray(data?.reported_section_details) ? data.reported_section_details : []).forEach((row) => {
        const sec = Number(row?.sec);
        if (sec > 0) window.priorSectionDetails[sec] = row;
    });

    const currentId = currentWizardReportId();
    let reported = Array.isArray(data?.reported_sections)
        ? data.reported_sections.map((n) => Number(n)).filter((n) => n > 0)
        : [];
    // กรอง Section ของรายงานที่กำลังเปิดแก้ไขออกจากรายการ "ถูกบันทึกแล้ว"
    if (currentId && !window.appendingToPriorReport) {
        reported = reported.filter((sec) => {
            const detail = window.priorSectionDetails[sec];
            return !(detail?.grade_id && String(detail.grade_id) === String(currentId));
        });
        Object.keys(window.priorSectionDetails).forEach((sec) => {
            const detail = window.priorSectionDetails[sec];
            if (detail?.grade_id && String(detail.grade_id) === String(currentId)) {
                delete window.priorSectionDetails[sec];
            }
        });
    }
    window.priorReportedSections = reported;
    window.courseGroupLocked = grouped;
    window.sharedFieldsLocked = Boolean(prior);
    window.priorSectionEvaEditable = Boolean(prior && Number(prior.statuseva) === 1);
    attachWizardToPriorReport(prior);

    renderCourseGroupBanner(members, grouped);
    renderCoursePriorBanner(prior);
    renderPriorSectionsBox(window.priorReportedSections);

    if (grouped) {
        const radio = document.querySelector('input[name="reasonid"][value="1"]');
        if (radio) radio.checked = true;
        setJointGradeSubjects(members
            .filter((m) => !m.is_current)
            .map((m) => ({ code: m.subject_code, name: m.subject || '' })));
        groupMembersApplied = true;
    } else if (groupMembersApplied && !window.sharedFieldsLocked) {
        resetJointGradeSubjects();
        groupMembersApplied = false;
    }

    if (prior?.payload) {
        applyPriorSharedFields(prior.payload);
        priorCriteriaApplied = true;
    } else if (priorCriteriaApplied) {
        clearInheritedPriorCriteria();
        priorCriteriaApplied = false;
    }
    applyPriorTeacherNames(prior);

    setSharedFieldsLocked(window.sharedFieldsLocked, {
        sectionEvaEditable: window.priorSectionEvaEditable,
        groupLocked: window.courseGroupLocked,
    });
    updateReasonFieldsState();
    updateSectionFormHint();
    refreshSectionSelectOptions();
}

function renderCourseThesisBanner(blocked, message) {
    const banner = document.getElementById('course-thesis-banner');
    const body = document.getElementById('course-thesis-body');
    if (!banner) return;
    banner.classList.toggle('hidden', !blocked);
    if (body && message && !body.querySelector('a')) body.textContent = message;
}

function renderCourseGroupBanner(members, grouped) {
    const banner = document.getElementById('course-group-banner');
    const list = document.getElementById('course-group-list');
    const peerBox = document.getElementById('joint-peer-box');
    if (!banner || !list) return;

    if (!grouped) {
        banner.classList.add('hidden');
        peerBox?.classList.add('hidden');
        return;
    }

    banner.classList.remove('hidden');
    list.innerHTML = members.map((m) => `
        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-white border ${m.is_current ? 'border-sky-500 text-sky-950' : 'border-sky-200 text-[#0c4a6e]'}">
            <span class="font-semibold">${m.subject_code}</span>
            ${m.subject ? `<span class="truncate max-w-[12rem]">— ${m.subject}</span>` : ''}
            ${m.is_current ? '<span class="text-[10px] font-medium">(รายวิชาที่กำลังกรอก)</span>' : ''}
        </span>
    `).join('');

    if (peerBox) {
        peerBox.classList.remove('hidden');
        const peerList = document.getElementById('joint-peer-list');
        const empty = document.getElementById('joint-peer-empty');
        if (peerList) peerList.innerHTML = list.innerHTML;
        empty?.classList.toggle('hidden', members.length > 0);
    }
}

function renderCoursePriorBanner(prior) {
    const banner = document.getElementById('course-prior-banner');
    const body = document.getElementById('course-prior-body');
    if (!banner || !body) return;

    if (!prior) {
        banner.classList.add('hidden');
        body.textContent = '';
        return;
    }

    const name = prior.filled_by || prior.teacher || 'ผู้กรอกก่อน';
    const termLabel = prior.term_label || 'ภาคการศึกษานี้';
    const year = prior.year || '';
    const sectionNote = Number(prior.statuseva) === 1
        ? ' ผู้กรอกก่อนเลือกให้กรอกคะแนนประเมินตาม Section — ท่านกรอกคะแนนประเมินของ Section ตนเองได้'
        : ' คะแนนประเมินรายวิชาแบบรวมถูกดึงมาให้แล้ว';

    banner.classList.remove('hidden');
    body.textContent = `${termLabel} ปีการศึกษา ${year} มีผู้กรอกก่อนหน้าแล้ว โดย ${name} `
        + 'หากเพิ่ม Section อื่นที่ยังไม่ถูกบันทึก ระบบจะเพิ่มเข้าในรายงานรายวิชาเดิมให้อัตโนมัติ '
        + 'ช่วงคะแนนและเกณฑ์ถูกดึงมาให้แล้วและไม่สามารถแก้ไขได้ '
        + `หากต้องการเปลี่ยนแปลงเกณฑ์หรือแก้ไข Section เดิม กรุณาติดต่อ ${name}${sectionNote}`;
}

let priorTeacherSourceId = null;

function splitTeacherNames(value) {
    return String(value || '')
        .split(/[,;\/]+/)
        .map((name) => name.trim())
        .filter(Boolean);
}

function mergeTeacherNames(existing, extra) {
    const names = splitTeacherNames(existing);
    splitTeacherNames(extra).forEach((name) => {
        const key = name.replace(/\s+/g, '');
        if (!names.some((item) => item.replace(/\s+/g, '') === key)) {
            names.push(name);
        }
    });
    return names.join(', ');
}

function applyPriorTeacherNames(prior) {
    const input = document.getElementById('teacher-input');
    const hint = document.getElementById('teacher-help-text');
    if (!input) return;

    if (window.wizardConfig?.openedAsEdit) {
        return;
    }

    if (!prior) {
        if (priorTeacherSourceId) {
            input.value = input.dataset.defaultTeacher || '';
            priorTeacherSourceId = null;
        }
        if (hint) {
            hint.textContent = 'ดึงชื่อจากข้อมูลบุคลากรเป็นค่าเริ่มต้น — สามารถแก้ไขหรือเพิ่มชื่ออาจารย์ผู้สอนได้';
        }
        return;
    }

    const sourceId = String(prior.grade_id || '');
    const priorNames = prior.teacher
        || (Array.isArray(prior.teachers) ? prior.teachers.join(', ') : '');
    if (priorTeacherSourceId === sourceId) {
        if (hint) {
            hint.textContent = 'แสดงชื่ออาจารย์จากรายการเดิมในภาคนี้ — สามารถเพิ่มชื่ออาจารย์ผู้สอนได้';
        }
        return;
    }

    input.value = mergeTeacherNames(priorNames, input.dataset.defaultTeacher || '');
    priorTeacherSourceId = sourceId;
    if (hint) {
        hint.textContent = 'แสดงชื่ออาจารย์จากรายการเดิมในภาคนี้ — สามารถเพิ่มชื่ออาจารย์ผู้สอนได้';
    }
}

function attachWizardToPriorReport(prior) {
    const config = window.wizardConfig;
    if (!config) {
        window.appendingToPriorReport = Boolean(prior);
        return;
    }

    if (config.openedAsEdit) {
        window.appendingToPriorReport = false;
        return;
    }

    if (!prior?.grade_id) {
        if (window.appendingToPriorReport) {
            config.currentReportId = null;
        }
        window.appendingToPriorReport = false;
        return;
    }

    window.appendingToPriorReport = true;
    config.currentReportId = String(prior.grade_id);
}

function renderPriorSectionsBox(sections) {
    const box = document.getElementById('prior-sections-box');
    const list = document.getElementById('prior-sections-list');
    if (!box || !list) return;

    if (!sections.length) {
        box.classList.add('hidden');
        list.innerHTML = '';
        return;
    }

    box.classList.remove('hidden');
    list.innerHTML = sections.map((sec) => {
        const filledBy = priorSectionContactName(sec);
        return `
            <span class="prior-sec-chip inline-flex flex-col items-start gap-0.5 px-2.5 py-1 rounded-lg text-xs">
                <span class="font-semibold">Section ${sec}</span>
                <span class="font-normal opacity-90">กรอกโดย ${filledBy}</span>
            </span>
        `;
    }).join('');
}

function applyRemarksFromRecord(record) {
    if (!record?.reasonid) return;
    setRadio('reasonid', record.reasonid);
    if (Number(record.reasonid) === 1 && record.reason) {
        const subjects = parseJointGradeReason(record.reason);
        setJointGradeSubjects(subjects);
        enrichJointGradeSubjectNames(subjects).then(setJointGradeSubjects);
    } else if (Number(record.reasonid) === 2 && record.reason) {
        const match = String(record.reason).match(/ได้ I เนื่องจาก\s*:?\s*(.*)/);
        const el = document.getElementById('std-i2');
        if (el) el.value = match?.[1]?.trim() || record.reason;
    } else if (Number(record.reasonid) === 3 && record.reason) {
        const el = document.getElementById('std-i3');
        if (el) el.value = record.reason;
    }
}

function applyPriorSharedFields(record) {
    if (!record) return;

    setRadio('intflag', record.intflag ?? 0);
    setRadio('statuseva', record.statuseva ?? 2);

    parseScoreRange(record.score_a, 'range-a-max', 'range-a-min');
    parseScoreRange(record.score_bb, 'range-bp-max', 'range-bp-min');
    parseScoreRange(record.score_b, 'range-b-max', 'range-b-min');
    parseScoreRange(record.score_cc, 'range-cp-max', 'range-cp-min');
    parseScoreRange(record.score_c, 'range-c-max', 'range-c-min');
    parseScoreRange(record.score_dd, 'range-dp-max', 'range-dp-min');
    parseScoreRange(record.score_d, 'range-d-max', 'range-d-min');
    parseScoreRange(record.score_f, 'range-f-max', 'range-f-min');
    parseScoreRange(record.score_s, 'range-s-max', 'range-s-min');
    parseScoreRange(record.score_u, 'range-u-max', 'range-u-min');
    setGradeScheme(record.grade_scheme || (record.score_s || record.score_u ? 'both' : 'credit'));
    recalcAllGradeChains();

    if (record.mean != null) document.getElementById('mean-score').value = formatDecimal2(record.mean);
    if (record.sd != null) document.getElementById('sd-score').value = formatDecimal2(record.sd);
    if (record.totalnumstdevz != null) document.getElementById('totalnumstdevz').value = record.totalnumstdevz;
    if (record.totalevaluationscore != null) document.getElementById('totalevaluationscore').value = record.totalevaluationscore;

    applyRemarksFromRecord(record);
    toggleEvaFields();
    updateGradeBoundaryHint();
    updateGradeRangeColumnHeaders();
}

function clearInheritedPriorCriteria() {
    ['mean-score', 'sd-score', 'totalnumstdevz', 'totalevaluationscore', 'std-i2', 'std-i3'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    ['a', 'bp', 'b', 'cp', 'c', 'dp', 'd', 'f', 's', 'u'].forEach((key) => {
        const min = document.getElementById(`range-${key}-min`);
        const max = document.getElementById(`range-${key}-max`);
        if (min) min.value = '';
        if (max) max.value = (key === 'a' || key === 's') ? '100' : '';
    });
    setGradeScheme('credit');
    setRadio('intflag', 0);
    setRadio('statuseva', 2);
    document.querySelectorAll('input[name="reasonid"]').forEach((el) => { el.checked = false; });
    recalcAllGradeChains();
    toggleEvaFields();
    updateGradeBoundaryHint();
    updateGradeRangeColumnHeaders();
}

const SHARED_LOCK_FIELD_IDS = [
    'mean-score', 'sd-score',
    'totalnumstdevz', 'totalevaluationscore',
    'range-a-min', 'range-bp-min', 'range-b-min', 'range-cp-min',
    'range-c-min', 'range-dp-min', 'range-d-min', 'range-f-min',
    'range-s-min', 'range-u-min',
];

function setSharedFieldsLocked(locked, opts = {}) {
    const sectionEvaEditable = Boolean(opts.sectionEvaEditable);
    SHARED_LOCK_FIELD_IDS.forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        const isAggregateEva = id === 'totalnumstdevz' || id === 'totalevaluationscore';
        const shouldLock = locked && !(isAggregateEva && sectionEvaEditable);
        el.readOnly = shouldLock;
        el.classList.toggle('field-locked', shouldLock);
    });

    ['scheme-credit', 'scheme-audit', 'scheme-both'].forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.disabled = locked;
        el.classList.toggle('field-locked', locked);
    });

    document.querySelectorAll('input[name="intflag"]').forEach((el) => {
        el.disabled = locked;
    });
    document.querySelectorAll('input[name="statuseva"]').forEach((el) => {
        el.disabled = locked;
    });
    document.querySelectorAll('.grade-range-clear').forEach((el) => {
        el.classList.toggle('hidden', locked);
        el.disabled = locked;
    });

    ['numstdevz', 'evaluationscore'].forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        const shouldLock = locked && !sectionEvaEditable;
        el.readOnly = shouldLock;
        el.classList.toggle('field-locked', shouldLock);
    });
}

async function loadJointPeers() {
    await refreshCourseContext();
}

function setupWizardRegUpload() {
    // ช่องอัปโหลดราย Section ถูก bind ใน renderRegUploadSlots()
    const legacy = document.getElementById('wizard-reg-upload');
    if (legacy && legacy.dataset.bound !== '1') {
        legacy.dataset.bound = '1';
    }
}

/** @type {Record<number, { name: string, source: 'pending'|'saved', fileId?: number|null, viewUrl?: string|null }>} */
window.regUploadBySection = window.regUploadBySection || {};

function requiredRegSections() {
    const secs = sectionStdRows
        .map((row) => Number(row.sec))
        .filter((sec) => sec > 0);
    return [...new Set(secs)].sort((a, b) => a - b);
}

function markRegUploadForSection(sec, name, source = 'pending', meta = {}) {
    const n = Number(sec);
    if (!n || n <= 0) return;
    window.regUploadBySection[n] = {
        name: name || `Section ${n}`,
        source: source === 'saved' ? 'saved' : 'pending',
        fileId: meta.fileId != null ? Number(meta.fileId) : null,
        viewUrl: meta.viewUrl || null,
    };
}

function clearRegUploadForSection(sec) {
    const n = Number(sec);
    if (!n || n <= 0) return;
    delete window.regUploadBySection[n];
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function missingRegSections() {
    return requiredRegSections().filter((sec) => !window.regUploadBySection[sec]);
}

function regUploadProgress() {
    const required = requiredRegSections();
    const done = required.filter((sec) => window.regUploadBySection[sec]).length;
    return { required, done, total: required.length, missing: missingRegSections() };
}

function hasRegistrarAttachment(config) {
    const progress = regUploadProgress();
    if (progress.total > 0) {
        return progress.missing.length === 0;
    }
    // ยังไม่มี Section ในฟอร์ม — fallback ตามสถานะเดิม
    return Boolean(
        config?.hasRegistrarFile
        || window.wizardHasPendingReg
        || (!config?.openedAsEdit && (config?.cameFromUpload || config?.hasPendingRegistrar))
    );
}

function pdfFileIconHtml() {
    return `<span class="inline-flex h-10 w-8 shrink-0 items-center justify-center rounded bg-[#E53935] text-[10px] font-bold leading-none text-white shadow-sm" aria-hidden="true">PDF</span>`;
}

function regViewUrlForSection(sec, info = window.regUploadBySection[sec]) {
    if (!info) return null;
    if (info.viewUrl) return info.viewUrl;
    if (info.source === 'pending') return `/grade-reports/pending-registrar/${sec}`;
    const fileId = resolveSavedRegFileId(sec, info, window.wizardConfig);
    const reportId = window.wizardConfig?.currentReportId;
    if (info.source === 'saved' && reportId && fileId) {
        return `/grade-reports/${reportId}/files/${fileId}`;
    }
    return null;
}

function resolveSavedRegFileId(sec, info, config = window.wizardConfig) {
    if (info?.fileId) return Number(info.fileId);
    const detail = (config?.registrarFileDetails || []).find((row) => Number(row.section) === Number(sec));
    return detail?.file_id != null ? Number(detail.file_id) : null;
}

function renderRegCompleteness(config) {
    const list = document.getElementById('wizard-reg-completeness');
    if (!list) return;
    const required = requiredRegSections();
    if (!required.length) {
        list.innerHTML = '';
        return;
    }
    list.innerHTML = required.map((sec) => {
        const done = Boolean(window.regUploadBySection[sec]);
        return `<li class="inline-flex items-center gap-2 rounded-md px-2.5 py-1.5 ${done ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-700'}">
            <span class="text-base leading-none">${done ? '✓' : '✗'}</span>
            <span>Section ${sec}${done ? ' ครบ' : ' ยังขาดไฟล์'}</span>
        </li>`;
    }).join('');
}

function renderRegUploadSlots(config) {
    const list = document.getElementById('wizard-reg-uploads-list');
    const summary = document.getElementById('wizard-reg-summary');
    if (!list) return;

    const required = requiredRegSections();
    if (!required.length) {
        list.innerHTML = '<p class="text-sm text-amber-800">ยังไม่มี Section จากขั้นตอนที่ 5 — กรุณาย้อนกลับไปเพิ่ม Section ก่อน</p>';
        if (summary) summary.textContent = '';
        renderRegCompleteness(config);
        return;
    }

    const progress = regUploadProgress();
    if (summary) {
        summary.textContent = progress.missing.length === 0
            ? `อัปโหลด มข.11 ครบแล้ว ${progress.done}/${progress.total} Section`
            : `อัปโหลด มข.11 แล้ว ${progress.done}/${progress.total} Section — ยังขาด Section ${progress.missing.join(', ')}`;
        summary.className = `text-sm font-semibold ${progress.missing.length === 0 ? 'text-green-800' : 'text-red-700'}`;
    }
    renderRegCompleteness(config);

    list.innerHTML = required.map((sec) => {
        const info = window.regUploadBySection[sec];
        const done = Boolean(info);
        const viewUrl = regViewUrlForSection(sec, info);
        const fileName = escapeHtml(info?.name || `มข.11-Section-${sec}.pdf`);

        if (done) {
            return `
            <div class="rounded-lg border border-green-300 bg-green-50 px-3 py-3 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-[#5C2E1F]">Section ${sec}</p>
                    <p id="wizard-reg-status-${sec}" class="text-xs text-green-800">แนบไฟล์แล้ว</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="${escapeHtml(viewUrl || '#')}" target="_blank" rel="noopener noreferrer"
                        data-reg-view="${sec}"
                        class="inline-flex items-center gap-2 rounded-lg border border-green-200 bg-white px-3 py-2 text-sm text-[#5C2E1F] hover:bg-green-50"
                        title="เปิดดูไฟล์ PDF">
                        ${pdfFileIconHtml()}
                        <span class="min-w-0">
                            <span class="block font-medium truncate max-w-[16rem]">${fileName}</span>
                            <span class="block text-xs text-[#7A4A3A]">คลิกเพื่อเปิดดู PDF</span>
                        </span>
                    </a>
                    <button type="button" data-reg-delete="${sec}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-300 text-red-700 text-xs font-medium hover:bg-red-50">
                        ลบไฟล์
                    </button>
                </div>
                <p id="wizard-reg-error-${sec}" class="hidden text-xs text-red-600"></p>
            </div>`;
        }

        return `
            <div class="rounded-lg border border-amber-300 bg-[#FFFBF7] px-3 py-3 space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-[#5C2E1F]">Section ${sec}</p>
                    <p id="wizard-reg-status-${sec}" class="text-xs text-red-700">ยังไม่ได้แนบไฟล์ — อัปโหลดด้านล่างหรือใช้ช่องหลายไฟล์ด้านบน</p>
                </div>
                <label class="block space-y-1">
                    <span class="text-xs font-medium text-[#5C2E1F]">อัปโหลดไฟล์ มข.11 ของ Section ${sec}</span>
                    <input id="wizard-reg-input-${sec}" type="file" accept=".pdf,application/pdf" data-reg-section="${sec}"
                        class="block w-full max-w-md text-sm text-[#5C2E1F] file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-[#8B4513] file:text-white file:text-sm file:font-medium hover:file:bg-[#6B3410]">
                </label>
                <p id="wizard-reg-error-${sec}" class="hidden text-xs text-red-600"></p>
            </div>
        `;
    }).join('');

    list.querySelectorAll('input[data-reg-section]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files?.[0];
            const sec = Number(input.dataset.regSection);
            if (file && sec > 0) {
                uploadSectionRegistrarPdf(file, { attachOnly: true, expectedSection: sec });
            }
        });
    });

    list.querySelectorAll('[data-reg-view]').forEach((el) => {
        el.addEventListener('click', (e) => {
            const sec = Number(el.getAttribute('data-reg-view'));
            const url = regViewUrlForSection(sec);
            if (!url) {
                e.preventDefault();
                showToast('ไม่พบลิงก์ดูไฟล์ของ Section นี้', 'error');
            }
        });
    });

    list.querySelectorAll('[data-reg-delete]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const sec = Number(btn.getAttribute('data-reg-delete'));
            deleteRegUploadForSection(sec, config);
        });
    });
}

function viewRegUploadForSection(sec) {
    const url = regViewUrlForSection(sec);
    if (!url) {
        showToast('ไม่พบลิงก์ดูไฟล์ของ Section นี้', 'error');
        return;
    }
    const opened = window.open(url, '_blank', 'noopener,noreferrer');
    if (!opened) {
        showToast('เบราว์เซอร์บล็อกหน้าต่างใหม่ — อนุญาตป๊อปอัปแล้วกดดูไฟล์อีกครั้ง', 'error');
    }
}

async function clearRegSlotLocal(sec, config = window.wizardConfig) {
    clearRegUploadForSection(sec);
    window.wizardHasPendingReg = Object.values(window.regUploadBySection).some((x) => x.source === 'pending');
    if (config) {
        config.hasPendingRegistrar = window.wizardHasPendingReg;
        config.registrarFileDetails = (config.registrarFileDetails || [])
            .filter((row) => Number(row.section) !== Number(sec));
        config.registrarFileSections = (config.registrarFileSections || [])
            .filter((s) => Number(s) !== Number(sec));
        config.pendingRegistrarSections = (config.pendingRegistrarSections || [])
            .filter((row) => Number(row?.section ?? row) !== Number(sec));
        config.hasRegistrarFile = (config.registrarFileSections || []).length > 0
            || Object.values(window.regUploadBySection).some((x) => x.source === 'saved');
    }
    renderRegUploadSlots(config);
    syncWizardRegStatus(config);
    updateAttachmentChecklist(config);
    persistWizardState(config, currentWizardStep());
}

async function deleteRegUploadForSection(sec, config = window.wizardConfig) {
    const info = window.regUploadBySection[sec];
    if (!info) {
        await clearRegSlotLocal(sec, config);
        return;
    }
    if (!window.confirm(`ลบไฟล์ มข.11 ของ Section ${sec} แล้วอัปโหลดใหม่หรือไม่?`)) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    try {
        if (info.source === 'saved') {
            const fileId = resolveSavedRegFileId(sec, info, config);
            if (config?.currentReportId && fileId) {
                const res = await fetch(`/api/grade-reports/${config.currentReportId}/files/${fileId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'ลบไฟล์ไม่สำเร็จ');
                }
            }
        } else {
            const res = await fetch(`/grade-reports/pending-registrar/${sec}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || 'ลบไฟล์ชั่วคราวไม่สำเร็จ');
            }
        }

        await clearRegSlotLocal(sec, config);
        showToast(`ลบไฟล์ Section ${sec} แล้ว — กรุณาอัปโหลดใหม่ด้านล่างหรือใช้ช่องหลายไฟล์ด้านบน`, 'success');
    } catch (err) {
        // แม้ลบฝั่งเซิร์ฟเวอร์ไม่สำเร็จ ให้เปิดช่องอัปโหลดใหม่ถ้าผู้ใช้ยืนยันแล้ว
        await clearRegSlotLocal(sec, config);
        showToast(err?.message || 'ลบไฟล์ไม่สำเร็จ — เปิดช่องอัปโหลดใหม่ให้แล้ว', 'error');
    }
}

async function uploadMultipleRegistrarPdfs(fileList, config = window.wizardConfig) {
    const files = Array.from(fileList || []).filter(Boolean);
    const status = document.getElementById('wizard-reg-bulk-status');
    if (!files.length) return;

    const required = requiredRegSections();
    if (!required.length) {
        showToast('ยังไม่มี Section จากขั้นตอนที่ 5 — กรุณาย้อนกลับไปเพิ่ม Section ก่อน', 'error');
        return;
    }

    let ok = 0;
    let fail = 0;
    const notes = [];

    if (status) {
        status.textContent = `กำลังอัปโหลด 0/${files.length} ไฟล์...`;
        status.className = 'text-xs text-[#7A4A3A]';
    }

    for (let i = 0; i < files.length; i += 1) {
        const file = files[i];
        if (status) status.textContent = `กำลังอัปโหลด ${i + 1}/${files.length}: ${file.name}`;
        try {
            if (file.type && file.type !== 'application/pdf' && !/\.pdf$/i.test(file.name)) {
                throw new Error(`${file.name}: รองรับเฉพาะ PDF`);
            }
            const result = await uploadSectionRegistrarPdf(file, {
                attachOnly: true,
                expectedSection: null,
                silentToast: true,
                keepInput: true,
                skipRender: true,
            });
            if (!result?.ok) {
                throw new Error(result?.error || `${file.name}: อัปโหลดไม่สำเร็จ`);
            }
            const sec = Number(result.section || 0);
            if (sec > 0 && !required.includes(sec)) {
                notes.push(`${file.name}: เป็น Section ${sec} ซึ่งไม่อยู่ในรายการที่ต้องแนบ`);
            }
            ok += 1;
        } catch (err) {
            fail += 1;
            notes.push(err?.message || `${file.name}: ไม่สำเร็จ`);
        }
    }

    renderRegUploadSlots(config);
    syncWizardRegStatus(config);
    updateAttachmentChecklist(config);

    const progress = regUploadProgress();
    const summaryMsg = fail === 0
        ? `อัปโหลดสำเร็จ ${ok} ไฟล์ — ครบ ${progress.done}/${progress.total} Section`
        : `สำเร็จ ${ok} ไฟล์, ไม่สำเร็จ ${fail} ไฟล์ — ครบ ${progress.done}/${progress.total} Section`;
    if (status) {
        status.textContent = notes.length ? `${summaryMsg} | ${notes.join(' · ')}` : summaryMsg;
        status.className = `text-xs ${progress.missing.length === 0 && fail === 0 ? 'text-green-800' : 'text-amber-800'}`;
    }
    showToast(summaryMsg, fail === 0 && progress.missing.length === 0 ? 'success' : (fail ? 'error' : 'success'));
}

function currentWizardStep() {
    const active = document.querySelector('[data-wizard-step].is-active');
    const n = Number(active?.dataset?.wizardStep);
    return n >= 1 && n <= 8 ? n : 1;
}

function syncWizardRegStatus(config) {
    const status = document.getElementById('wizard-reg-status');
    const help = document.getElementById('wizard-reg-help');
    const progress = regUploadProgress();

    if (help) {
        help.innerHTML = progress.total === 0
            ? 'กรุณาย้อนกลับไปขั้นตอนที่ 5 เพิ่ม Section ก่อน แล้วจึงแนบแบบฟอร์ม มข.11 ให้ครบทุก Section'
            : 'ต้องอัปโหลดแบบฟอร์ม มข.11 ให้ครบทุก Section ที่กรอกในขั้นตอนที่ 5 <strong>ตั้งชื่อไฟล์อย่างไรก็ได้</strong> — ระบบตั้งชื่อเป็น <span class="font-semibold text-[#854d0e]">รหัสวิชา-กลุ่ม.pdf</span> ให้อัตโนมัติ สามารถเลือกหลายไฟล์พร้อมกันได้ หากยังไม่ครบจะไปขั้นตอนถัดไปไม่ได้';
    }

    if (!status) return;
    if (progress.total === 0) {
        status.textContent = 'สถานะ: ยังไม่มี Section ที่ต้องแนบไฟล์';
        status.className = 'text-sm text-amber-800 font-medium';
        return;
    }
    if (progress.missing.length === 0) {
        status.textContent = `สถานะ: แนบ มข.11 ครบ ${progress.done}/${progress.total} Section แล้ว — กดไปต่อได้`;
        status.className = 'text-sm text-green-800 font-medium';
        return;
    }
    status.textContent = `สถานะ: ยังแนบไม่ครบ (ขาด Section ${progress.missing.join(', ')}) — กรุณาอัปโหลดให้ครบ ${progress.total} ไฟล์`;
    status.className = 'text-sm text-red-700 font-medium';
}

function hasExamReportAttachment(config) {
    return Boolean(config?.hasExamReportFile || config?.hasPendingExam || window.pendingExamFile);
}

function shouldSkipRegStep() {
    return false;
}

function requiredAttachmentError(config) {
    const hasReg = hasRegistrarAttachment(config);
    const hasExam = hasExamReportAttachment(config);
    if (hasReg && hasExam) return null;
    if (!hasReg && !hasExam) {
        const missing = missingRegSections();
        const regMsg = missing.length
            ? `แบบฟอร์ม มข.11 ยังไม่ครบ (ขาด Section ${missing.join(', ')})`
            : 'แบบฟอร์ม มข.11 ในขั้นตอนที่ 6';
        return `กรุณาแนบไฟล์ให้ครบก่อนเสร็จสิ้น: ${regMsg} และใบรายงานผลการสอบไล่ (ใบขวาง) ในขั้นตอนที่ 8`;
    }
    if (!hasReg) {
        const missing = missingRegSections();
        return missing.length
            ? `ยังแนบแบบฟอร์ม มข.11 ไม่ครบ — ขาด Section ${missing.join(', ')} กรุณาย้อนกลับไปขั้นตอนที่ 6`
            : 'ยังไม่ได้แนบแบบฟอร์ม มข.11 กรุณาย้อนกลับไปขั้นตอนที่ 6';
    }
    return 'ยังไม่ได้แนบใบรายงานผลการสอบไล่ (ใบขวาง) กรุณาเลือกไฟล์ PDF ที่พิมพ์และลงนามแล้วในขั้นตอนที่ 8 ก่อนเสร็จสิ้น';
}

function updateAttachmentChecklist(config) {
    const hasReg = hasRegistrarAttachment(config);
    const hasExam = hasExamReportAttachment(config);
    const regCheck = document.getElementById('wizard-reg-check');
    const examCheck = document.getElementById('wizard-exam-check');
    const box = document.getElementById('wizard-attachment-checklist');
    const progress = regUploadProgress();

    if (regCheck) {
        regCheck.textContent = hasReg
            ? (config?.hasRegistrarFile && progress.missing.length === 0
                ? `แนบแบบฟอร์ม มข.11 ครบ ${progress.done}/${progress.total || progress.done} Section แล้ว`
                : `เลือก มข.11 ครบ ${progress.done}/${progress.total} Section — จะอัปโหลดเมื่อกดเสร็จสิ้น`)
            : (progress.missing.length
                ? `ยังแนบ มข.11 ไม่ครบ — ขาด Section ${progress.missing.join(', ')}`
                : 'ยังไม่ได้แนบแบบฟอร์ม มข.11 — ย้อนกลับไปขั้นตอนที่ 6');
        regCheck.className = `text-sm ${hasReg ? 'text-green-800 font-medium' : 'text-red-700'}`;
    }
    if (examCheck) {
        examCheck.textContent = hasExam
            ? (config?.hasExamReportFile
                ? 'แนบใบรายงานผลการสอบไล่ (ใบขวาง) เข้าสู่ระบบแล้ว'
                : 'เลือกใบขวางแล้ว — จะอัปโหลดเข้าสู่ระบบเมื่อกดเสร็จสิ้น')
            : 'ยังไม่ได้แนบใบรายงานผลการสอบไล่ (ใบขวาง) — อัปโหลดในขั้นตอนนี้';
        examCheck.className = `text-sm ${hasExam ? 'text-green-800 font-medium' : 'text-red-700'}`;
    }
    if (box) {
        box.classList.toggle('border-green-200', hasReg && hasExam);
        box.classList.toggle('bg-green-50', hasReg && hasExam);
        box.classList.toggle('border-amber-200', !(hasReg && hasExam));
        box.classList.toggle('bg-[#FFFBF7]', !(hasReg && hasExam));
    }

    syncWizardRegStatus(config);
    syncExamUploadUi(config);
}

function syncExamUploadUi(config = window.wizardConfig) {
    const input = document.getElementById('wizard-exam-upload');
    const fileRow = document.getElementById('wizard-exam-file-row');
    const actions = document.getElementById('wizard-exam-actions');
    const status = document.getElementById('wizard-exam-status');
    if (!status) return;

    const saved = Boolean(config?.hasExamReportFile && config?.examFileDetail?.view_url);
    const pending = Boolean(config?.hasPendingExam || window.pendingExamFile);
    const pendingName = window.pendingExamFile?.name || 'ใบขวาง.pdf';

    if (fileRow) {
        if (saved) {
            fileRow.classList.remove('hidden');
            fileRow.innerHTML = `
                <div class="flex flex-wrap items-center gap-3">
                    <a href="${escapeHtml(config.examFileDetail.view_url)}" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-[#5C2E1F] hover:bg-green-100"
                        title="เปิดดูไฟล์ PDF">
                        ${pdfFileIconHtml()}
                        <span class="min-w-0">
                            <span class="block font-medium truncate max-w-[16rem]">${escapeHtml(config.examFileDetail.name || 'ใบขวาง.pdf')}</span>
                            <span class="block text-xs text-[#7A4A3A]">คลิกเพื่อเปิดดู PDF</span>
                        </span>
                    </a>
                    <button type="button" id="wizard-exam-delete"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-300 text-red-700 text-xs font-medium hover:bg-red-50">
                        ลบไฟล์
                    </button>
                </div>`;
            document.getElementById('wizard-exam-delete')?.addEventListener('click', () => {
                deleteExamReportFile(config);
            });
        } else if (pending) {
            fileRow.classList.remove('hidden');
            fileRow.innerHTML = `
                <div class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-[#FFFBF7] px-3 py-2 text-sm text-[#5C2E1F]">
                    ${pdfFileIconHtml()}
                    <span class="min-w-0">
                        <span class="block font-medium truncate max-w-[16rem]">${escapeHtml(pendingName)}</span>
                        <span class="block text-xs text-[#7A4A3A]">เลือกแล้ว — จะอัปโหลดเมื่อกดเสร็จสิ้น</span>
                    </span>
                </div>`;
        } else {
            fileRow.classList.add('hidden');
            fileRow.innerHTML = '';
        }
    }

    if (actions) {
        actions.classList.add('hidden');
        actions.innerHTML = '';
    }

    if (input) {
        input.classList.toggle('hidden', saved);
        if (!saved) {
            input.classList.remove('hidden');
        }
    }

    if (saved) {
        status.textContent = 'มีไฟล์ใบขวางในระบบแล้ว — คลิกไอคอน PDF เพื่อดู หรือลบแล้วอัปโหลดใหม่';
        status.className = 'text-xs text-green-800 font-medium';
    } else if (pending) {
        status.textContent = `เลือกไฟล์แล้ว: ${pendingName} — จะอัปโหลดเข้าสู่ระบบเมื่อกดเสร็จสิ้น`;
        status.className = 'text-xs text-green-800';
    } else {
        status.textContent = 'ยังไม่ได้เลือกไฟล์ใบขวาง — กรุณาเลือกไฟล์ด้านบน';
        status.className = 'text-xs text-[#7A4A3A]';
    }
}

async function deleteExamReportFile(config = window.wizardConfig) {
    if (!window.confirm('ลบไฟล์ใบขวางแล้วอัปโหลดใหม่หรือไม่?')) {
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const fileId = config?.examFileDetail?.file_id;
    try {
        if (config?.currentReportId && fileId) {
            const res = await fetch(`/api/grade-reports/${config.currentReportId}/files/${fileId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || 'ลบไฟล์ไม่สำเร็จ');
            }
        }

        config.hasExamReportFile = false;
        config.examFileDetail = null;
        config.hasPendingExam = false;
        window.pendingExamFile = null;
        const input = document.getElementById('wizard-exam-upload');
        if (input) {
            input.value = '';
            input.classList.remove('hidden');
        }
        updateAttachmentChecklist(config);
        persistWizardState(config, currentWizardStep());
        showToast('ลบไฟล์ใบขวางแล้ว — กรุณาเลือกไฟล์ใหม่ด้านล่าง', 'success');
    } catch (err) {
        config.hasExamReportFile = false;
        config.examFileDetail = null;
        config.hasPendingExam = false;
        window.pendingExamFile = null;
        const input = document.getElementById('wizard-exam-upload');
        if (input) input.classList.remove('hidden');
        updateAttachmentChecklist(config);
        showToast(err?.message || 'ลบไฟล์ไม่สำเร็จ — เปิดช่องอัปโหลดใหม่ให้แล้ว', 'error');
    }
}

function nextWizardStep(current) {
    return current + 1;
}

function prevWizardStep(current) {
    return current - 1;
}

function validateWizardStep(step, config) {
    if (step === 1) {
        const code = document.getElementById('subject-code')?.value?.trim();
        const name = document.getElementById('subject-name')?.value?.trim();
        if (!code || !name) return 'กรุณากรอกรหัสวิชาและชื่อวิชา';
        return null;
    }
    if (step === 2) {
        const reasonid = document.querySelector('input[name="reasonid"]:checked')?.value;
        if (reasonid === '1' && !jointGradeSubjects.length && !window.courseGroupLocked) {
            return 'กรุณาเลือกวิชาที่ตัดเกรดร่วมกับอย่างน้อย 1 วิชา';
        }
        return null;
    }
    if (step === 3) {
        if (!document.getElementById('scheme-credit')?.checked && !document.getElementById('scheme-audit')?.checked) {
            return 'กรุณาเลือกรูปแบบช่วงคะแนนอย่างน้อย 1 รายการ';
        }
        return validateGradeRanges();
    }
    if (step === 4) {
        return validateEvaluationScores(collectGradeReportPayload());
    }
    if (step === 5) {
        if (!sectionStdRows.length) {
            return 'กรุณาเพิ่มข้อมูลจำนวนนักศึกษาอย่างน้อย 1 Section (กด «บันทึก Section นี้» ก่อนไปต่อ)';
        }
        for (let i = 0; i < sectionStdRows.length; i += 1) {
            const row = sectionStdRows[i];
            if (!String(row?.fac || '').trim()) {
                return `Section ${row?.sec ?? (i + 1)} ยังไม่ได้เลือกคณะ — แก้ไข Section แล้วเลือกคณะก่อน`;
            }
        }
        return validateEvaluationScores(collectGradeReportPayload());
    }
    if (step === 6) {
        const missing = missingRegSections();
        if (!requiredRegSections().length) {
            return 'กรุณาย้อนกลับไปขั้นตอนที่ 5 เพิ่ม Section ก่อนแนบแบบฟอร์ม มข.11';
        }
        if (missing.length) {
            return `กรุณาแนบแบบฟอร์ม มข.11 ให้ครบทุก Section — ยังขาด Section ${missing.join(', ')} (ต้องอัปโหลด ${requiredRegSections().length} ไฟล์ ตามจำนวน Section ที่กรอก)`;
        }
        return null;
    }
    if (step === 8) {
        return requiredAttachmentError(config);
    }
    return null;
}

function showWizardStep(step, config) {
    const evaHint = document.getElementById('eva-hint-popover');
    if (evaHint) {
        evaHint.classList.remove('is-visible');
        evaHint.setAttribute('aria-hidden', 'true');
    }
    document.querySelectorAll('[data-wizard-step]').forEach((el) => {
        el.classList.toggle('is-active', Number(el.dataset.wizardStep) === step);
    });
    document.querySelectorAll('[data-wizard-dot]').forEach((el) => {
        const n = Number(el.dataset.wizardDot);
        el.classList.toggle('is-current', n === step);
        el.classList.toggle('is-done', n < step);
    });
    document.getElementById('wizard-stepper')?.classList.remove('is-complete');

    const back = document.getElementById('wizard-back');
    const next = document.getElementById('wizard-next');
    if (back) back.classList.toggle('hidden', step <= 1);
    if (next) next.textContent = step === 8 ? 'เสร็จสิ้น' : (step === 5 || step === 6 ? 'บันทึกแล้วไปต่อ' : 'ถัดไป');

    if (step === 2) refreshCourseContext();
    if (step === 5) {
        applyGraduateFacultyDefault();
        refreshCourseContext();
    }
    if (step === 6) {
        renderRegUploadSlots(config);
        syncWizardRegStatus(config);
    }
    updateAttachmentChecklist(config);
}

function showWizardDone() {
    clearWizardState();
    document.getElementById('grade-form')?.classList.add('hidden');
    document.getElementById('wizard-nav')?.classList.add('hidden');
    const stepper = document.getElementById('wizard-stepper');
    stepper?.classList.remove('hidden');
    stepper?.classList.add('is-complete');
    document.querySelectorAll('[data-wizard-dot]').forEach((el) => {
        el.classList.remove('is-current');
        el.classList.add('is-done');
    });
    document.getElementById('course-context-banners')?.classList.add('hidden');
    document.getElementById('wizard-done')?.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

async function saveWizardReport(config) {
    const payload = collectGradeReportPayload();
    const precheckError = validateGradeReportBeforeSave(payload);

    const overlay = document.getElementById('save-overlay');
    const loading = document.getElementById('save-overlay-loading');
    const success = document.getElementById('save-overlay-success');
    const errorBox = document.getElementById('save-overlay-error');
    const showSaveError = (message) => {
        loading?.classList.add('hidden');
        success?.classList.add('hidden');
        const errorMsg = document.getElementById('save-overlay-error-msg');
        if (errorMsg) errorMsg.textContent = message || 'บันทึกไม่สำเร็จ';
        errorBox?.classList.remove('hidden');
        overlay?.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        showToast(message || 'บันทึกไม่สำเร็จ', 'error');
    };

    if (precheckError) {
        showSaveError(precheckError);
        return { ok: false, error: precheckError };
    }

    loading?.classList.remove('hidden');
    success?.classList.add('hidden');
    errorBox?.classList.add('hidden');
    overlay?.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    let result;
    try {
        if (config.currentReportId) {
            payload.__backendId = String(config.currentReportId);
            result = await window.dataSdk.update(payload);
        } else {
            result = await window.dataSdk.create(payload);
        }
    } catch (err) {
        result = { isOk: false, error: err?.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ' };
    }

    if (!result.isOk) {
        showSaveError(result.error || 'บันทึกไม่สำเร็จ');
        return { ok: false, error: result.error };
    }

    const savedId = result.data?.__backendId || result.data?.grade_id || config.currentReportId;
    config.currentReportId = savedId ? String(savedId) : config.currentReportId;
    loading?.classList.add('hidden');
    overlay?.classList.add('hidden');
    document.body.style.overflow = '';
    // ไฟล์ REG ยังเป็น pending จนกว่าจะกดเสร็จสิ้น — ไม่ทำเครื่องหมายว่าอัปโหลดเข้าฐานแล้ว
    persistWizardState(config, 7);
    refreshCourseContext();
    return { ok: true };
}

/** ไฟล์ใบขวางที่เลือกไว้ รออัปโหลดจริงตอนกดเสร็จสิ้น */
window.pendingExamFile = null;

async function stageExamReport(config, file) {
    if (!file) return;
    if (file.type && file.type !== 'application/pdf' && !/\.pdf$/i.test(file.name)) {
        showToast('รองรับเฉพาะไฟล์ PDF', 'error');
        return;
    }
    window.pendingExamFile = file;
    config.hasPendingExam = true;
    config.hasExamReportFile = false;
    config.examFileDetail = null;
    syncExamUploadUi(config);
    persistWizardState(config, 8);
    updateAttachmentChecklist(config);
    showToast('เลือกใบขวางแล้ว — กดเสร็จสิ้นเพื่ออัปโหลด มข.11 และใบขวางเข้าสู่ระบบ', 'success');
}

async function finalizeWizardAttachments(config) {
    if (!config.currentReportId) {
        return { ok: false, error: 'ยังไม่มีเลขรายงาน — กรุณาย้อนกลับไปกด «บันทึกแล้วไปต่อ» ที่ขั้นตอนที่ 5' };
    }

    if (!hasRegistrarAttachment(config)) {
        const missing = missingRegSections();
        return {
            ok: false,
            error: missing.length
                ? `ยังแนบแบบฟอร์ม มข.11 ไม่ครบ — ขาด Section ${missing.join(', ')} กรุณาย้อนกลับไปขั้นตอนที่ 6`
                : 'ยังไม่ได้แนบแบบฟอร์ม มข.11 — กรุณาย้อนกลับไปขั้นตอนที่ 6',
        };
    }
    if (!hasExamReportAttachment(config)) {
        return { ok: false, error: 'ยังไม่ได้เลือกใบรายงานผลการสอบไล่ (ใบขวาง) — กรุณาเลือกไฟล์ในขั้นตอนนี้ก่อนกดเสร็จสิ้น' };
    }

    const overlay = document.getElementById('save-overlay');
    const loading = document.getElementById('save-overlay-loading');
    const success = document.getElementById('save-overlay-success');
    const errorBox = document.getElementById('save-overlay-error');
    loading?.classList.remove('hidden');
    success?.classList.add('hidden');
    errorBox?.classList.add('hidden');
    overlay?.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    const formData = new FormData();
    if (window.pendingExamFile) {
        formData.append('attachment', window.pendingExamFile);
        formData.append('file_type', 'exam_report');
    }
    requiredRegSections().forEach((sec) => {
        formData.append('required_sections[]', String(sec));
    });
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    try {
        const res = await fetch(`/api/grade-reports/${config.currentReportId}/finalize-wizard`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
            body: formData,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const msg = [data.message, data.hint].filter(Boolean).join(' — ')
                || 'อัปโหลดไฟล์เข้าสู่ระบบไม่สำเร็จ';
            throw new Error(msg);
        }

        window.pendingExamFile = null;
        window.pendingRegFileName = null;
        config.hasPendingExam = false;
        config.hasExamReportFile = true;
        config.hasRegistrarFile = true;
        window.wizardHasPendingReg = false;
        config.hasPendingRegistrar = false;
        config.regFilledFromPdf = false;

        if (data.exam_file?.view_url) {
            config.examFileDetail = {
                file_id: data.exam_file.file_id,
                name: data.exam_file.display_name || data.exam_file.original_name || 'ใบขวาง',
                view_url: data.exam_file.view_url,
            };
        }

        Object.keys(window.regUploadBySection).forEach((sec) => {
            const info = window.regUploadBySection[sec];
            if (!info) return;
            const matched = Array.isArray(data.registrar_files)
                ? data.registrar_files.find((file) => {
                    const name = String(file.display_name || file.original_name || '');
                    const m = name.match(/(?:^|-)(\d{2})(?:\.pdf)?$/i);
                    return m && Number(m[1]) === Number(sec);
                })
                : null;
            markRegUploadForSection(sec, matched?.display_name || matched?.original_name || info.name, 'saved', {
                fileId: matched?.file_id ?? info.fileId,
                viewUrl: matched?.view_url || null,
            });
        });
        config.registrarFileSections = Object.keys(window.regUploadBySection).map(Number).filter((n) => n > 0);
        config.registrarFileDetails = Object.entries(window.regUploadBySection).map(([sec, info]) => ({
            section: Number(sec),
            file_id: info.fileId,
            name: info.name,
            view_url: info.viewUrl,
        }));

        loading?.classList.add('hidden');
        const successMsg = document.getElementById('save-overlay-success-msg');
        if (successMsg) {
            successMsg.textContent = data.message
                || 'อัปโหลดแบบฟอร์ม มข.11 และใบรายงานผลการสอบไล่ (ใบขวาง) เข้าสู่ระบบเรียบร้อยแล้ว';
        }
        success?.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        await new Promise((resolve) => setTimeout(resolve, 900));
        overlay?.classList.add('hidden');
        success?.classList.add('hidden');
        document.body.style.overflow = '';
        updateAttachmentChecklist(config);
        showToast('อัปโหลด มข.11 และใบขวางเข้าสู่ระบบเรียบร้อย', 'success');
        return { ok: true, data };
    } catch (err) {
        loading?.classList.add('hidden');
        const errorMsg = document.getElementById('save-overlay-error-msg');
        const message = err?.message || 'อัปโหลดไฟล์เข้าสู่ระบบไม่สำเร็จ';
        if (errorMsg) errorMsg.textContent = message;
        errorBox?.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
        showToast(message, 'error');
        return { ok: false, error: message };
    }
}

function wizardStorageKey(config = window.wizardConfig) {
    if (config?.openedAsEdit && config?.currentReportId) {
        return `scigrade.wizard.edit.${config.currentReportId}`;
    }
    return 'scigrade.wizard.create';
}

function persistWizardState(config, step) {
    try {
        sessionStorage.setItem(wizardStorageKey(config), JSON.stringify({
            reportId: config.currentReportId || null,
            step,
            hasRegistrarFile: Boolean(config.hasRegistrarFile),
            hasPendingRegistrar: hasRegistrarAttachment(config) && !config.hasRegistrarFile,
            regUploadBySection: window.regUploadBySection || {},
            hasExamReportFile: Boolean(config.hasExamReportFile),
            hasPendingExam: Boolean(config.hasPendingExam || window.pendingExamFile),
            at: Date.now(),
        }));
    } catch {
        /* ignore quota / private mode */
    }
}

function restoreWizardState(config) {
    try {
        const saved = JSON.parse(sessionStorage.getItem(wizardStorageKey(config)) || 'null');
        if (!saved || typeof saved !== 'object') return 1;

        if (config.currentReportId && saved.reportId && String(saved.reportId) !== String(config.currentReportId)) {
            return 1;
        }
        if (!config.currentReportId && saved.reportId) {
            config.currentReportId = String(saved.reportId);
        }
        if (saved.hasRegistrarFile) config.hasRegistrarFile = true;
        if (saved.hasPendingRegistrar && !config.openedAsEdit) {
            config.hasPendingRegistrar = true;
            window.wizardHasPendingReg = true;
        }
        if (saved.regUploadBySection && typeof saved.regUploadBySection === 'object') {
            Object.entries(saved.regUploadBySection).forEach(([sec, info]) => {
                const n = Number(sec);
                if (n > 0 && info) {
                    window.regUploadBySection[n] = {
                        name: info.name || `Section ${n}`,
                        source: info.source === 'saved' ? 'saved' : 'pending',
                        fileId: info.fileId != null ? Number(info.fileId) : null,
                        viewUrl: info.viewUrl || null,
                    };
                }
            });
        }
        if (saved.hasExamReportFile) config.hasExamReportFile = true;
        // ใบขวางที่เลือกค้างไว้หายหลังรีเฟรช — ต้องเลือกใหม่ (ยังไม่อัปโหลดเข้าฐาน)
        if (saved.hasPendingExam && !saved.hasExamReportFile) {
            config.hasPendingExam = false;
        }

        const step = Number(saved.step);
        return step >= 1 && step <= 8 ? step : 1;
    } catch {
        return 1;
    }
}

function clearWizardState(config = window.wizardConfig) {
    try {
        sessionStorage.removeItem(wizardStorageKey(config));
    } catch {
        /* ignore */
    }
}

function printReportUrl(reportId, sections = null) {
    const path = `/grade-reports/${encodeURIComponent(reportId)}/print`;
    const secs = [...new Set(
        (Array.isArray(sections) ? sections : requiredRegSections())
            .map((n) => Number(n))
            .filter((n) => n > 0)
    )];
    if (!secs.length) {
        return `${path}?sections=`;
    }

    return `${path}?${secs.map((sec) => `sections[]=${encodeURIComponent(sec)}`).join('&')}`;
}

function initGradeReportWizard(config) {
    config.openedAsEdit = Boolean(config.openedAsEdit);
    window.wizardConfig = config;
    window.pendingExamFile = window.pendingExamFile || null;
    window.pendingRegFileName = window.pendingRegFileName || null;
    window.regUploadBySection = window.regUploadBySection || {};

    // ไฟล์ มข.11 ที่บันทึกในระบบแล้ว (โหมดแก้ไข)
    (config.registrarFileDetails || []).forEach((row) => {
        const n = Number(row?.section);
        if (n > 0) {
            markRegUploadForSection(n, row.name || `Section ${n}`, 'saved', {
                fileId: row.file_id,
                viewUrl: row.view_url,
            });
        }
    });
    (config.registrarFileSections || []).forEach((sec) => {
        const n = Number(sec);
        if (n > 0 && !window.regUploadBySection[n]) {
            markRegUploadForSection(n, `Section ${n}`, 'saved');
        }
    });
    // ไฟล์ที่ค้างใน session
    (config.pendingRegistrarSections || []).forEach((row) => {
        const n = Number(row?.section ?? row);
        if (n > 0) {
            markRegUploadForSection(n, row?.name || `Section ${n}`, 'pending', {
                viewUrl: row?.view_url || `/grade-reports/pending-registrar/${n}`,
            });
        }
    });

    if (config.examFileDetail?.view_url) {
        config.hasExamReportFile = true;
    }

    // โหมดแก้ไขไม่ใช้ REG pending จาก session ของรายวิชาอื่นแบบรวม — ใช้ราย Section ด้านบน
    if (config.openedAsEdit) {
        window.wizardHasPendingReg = Object.values(window.regUploadBySection).some((x) => x.source === 'pending');
        config.hasPendingRegistrar = window.wizardHasPendingReg;
        config.cameFromUpload = false;
    } else {
        window.wizardHasPendingReg = Boolean(
            config.hasPendingRegistrar
            || config.cameFromUpload
            || Object.keys(window.regUploadBySection).length > 0
        );
        if (window.wizardHasPendingReg) {
            config.regFilledFromPdf = true;
            if (!window.pendingRegFileName) {
                window.pendingRegFileName = 'ไฟล์ มข.11 ที่อัปโหลดไว้';
            }
        }
    }
    let step = restoreWizardState(config);

    renderRegUploadSlots(config);
    syncWizardRegStatus(config);

    const go = (next) => {
        step = next;
        persistWizardState(config, step);
        showWizardStep(step, config);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    document.getElementById('wizard-next')?.addEventListener('click', async () => {
        const error = validateWizardStep(step, config);
        if (error) {
            showToast(error, 'error');
            updateAttachmentChecklist(config);
            return;
        }

        if (step === 5 || step === 6) {
            const saved = await saveWizardReport(config);
            if (!saved.ok) return;
        }

        if (step === 8) {
            const finalized = await finalizeWizardAttachments(config);
            if (!finalized.ok) return;
            showWizardDone();
            return;
        }

        go(nextWizardStep(step, config));
    });

    document.getElementById('wizard-back')?.addEventListener('click', () => {
        go(prevWizardStep(step, config));
    });

    document.getElementById('wizard-exam-upload')?.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        if (file) stageExamReport(config, file);
        e.target.value = '';
    });

    const bulkInput = document.getElementById('wizard-reg-bulk-upload');
    if (bulkInput && bulkInput.dataset.bound !== '1') {
        bulkInput.dataset.bound = '1';
        bulkInput.addEventListener('change', async () => {
            const files = bulkInput.files;
            if (files?.length) {
                await uploadMultipleRegistrarPdfs(files, config);
            }
            bulkInput.value = '';
        });
    }

    document.getElementById('wizard-print-link')?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (!config.currentReportId) {
            showToast('ยังไม่มีเลขรายงานสำหรับพิมพ์ กรุณากด «บันทึกแล้วไปต่อ» อีกครั้ง', 'error');
            return;
        }
        persistWizardState(config, step);
        const opened = window.open(printReportUrl(config.currentReportId), '_blank', 'noopener,noreferrer');
        if (!opened) {
            showToast('เบราว์เซอร์บล็อกหน้าต่างใหม่ — อนุญาตป๊อปอัปแล้วกดพิมพ์อีกครั้ง', 'error');
        }
    });

    document.getElementById('btn-cancel')?.addEventListener('click', () => {
        clearWizardState();
    });

    document.getElementById('grade-form')?.addEventListener('submit', (e) => e.preventDefault());
    showWizardStep(step, config);
}
