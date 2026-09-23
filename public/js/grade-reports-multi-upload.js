(() => {
    const form = document.getElementById('multi-upload-form');
    if (!form) return;

    const maxFiles = Number(form.dataset.maxFiles || 20);
    const dropzone = document.getElementById('upload-dropzone');
    const input = document.getElementById('grade-files-input');
    const hiddenWrap = document.getElementById('grade-files-hidden');
    const listEl = document.getElementById('upload-file-list');
    const listWrap = document.getElementById('upload-file-list-wrap');
    const countEl = document.getElementById('upload-file-count');
    const errorEl = document.getElementById('upload-client-error');
    const btnPick = document.getElementById('btn-pick-files');
    const btnAdd = document.getElementById('btn-add-more');
    const btnClear = document.getElementById('btn-clear-files');
    const btnSubmit = document.getElementById('btn-submit-upload');

    /** @type {File[]} */
    let files = [];

    const formatSize = (bytes) => {
        if (!Number.isFinite(bytes) || bytes < 0) return '';
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    };

    const showError = (msg) => {
        if (!errorEl) return;
        if (!msg) {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
            return;
        }
        errorEl.textContent = msg;
        errorEl.classList.remove('hidden');
    };

    const fileKey = (file) => `${file.name}::${file.size}::${file.lastModified}`;

    const refreshIcons = () => {
        if (window.lucide?.createIcons) window.lucide.createIcons();
    };

    const syncHiddenInputs = () => {
        if (!hiddenWrap) return;
        hiddenWrap.innerHTML = '';
        const dt = new DataTransfer();
        files.forEach((file) => dt.items.add(file));
        const live = document.createElement('input');
        live.type = 'file';
        live.name = 'grade_files[]';
        live.id = 'grade-files-submit';
        live.multiple = true;
        live.accept = '.pdf,application/pdf';
        live.className = 'sr-only';
        live.setAttribute('aria-hidden', 'true');
        live.tabIndex = -1;
        // Assign files before append — required for reliable multipart submit
        live.files = dt.files;
        hiddenWrap.appendChild(live);
    };

    const render = () => {
        if (!listEl || !listWrap || !countEl || !btnSubmit || !btnAdd) return;

        listEl.innerHTML = '';
        countEl.textContent = String(files.length);
        listWrap.classList.toggle('hidden', files.length === 0);
        btnSubmit.disabled = files.length === 0;
        btnAdd.disabled = files.length === 0 || files.length >= maxFiles;

        files.forEach((file, index) => {
            const li = document.createElement('li');
            li.className = 'upload-file-row rounded-xl px-3 py-2.5 flex items-center gap-3';
            li.innerHTML = `
                <div class="upload-icon-wrap bg-rose-100 text-rose-700">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-[#5C2E1F] truncate" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</p>
                    <p class="text-xs text-[#7A4A3A]/75 mt-0.5">${formatSize(file.size)} · PDF พร้อมอัปโหลด</p>
                </div>
                <span class="shrink-0 inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-1 rounded-full bg-emerald-100 text-emerald-800">
                    <i data-lucide="check-circle" class="w-3 h-3"></i>
                    พร้อม
                </span>
                <button type="button" class="shrink-0 p-1.5 rounded-lg text-rose-700 hover:bg-rose-50" data-remove="${index}" title="ลบไฟล์นี้" aria-label="ลบไฟล์นี้">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            `;
            listEl.appendChild(li);
        });

        listEl.querySelectorAll('[data-remove]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = Number(btn.getAttribute('data-remove'));
                if (!Number.isInteger(idx)) return;
                files.splice(idx, 1);
                showError('');
                render();
            });
        });

        syncHiddenInputs();
        refreshIcons();
    };

    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const addFiles = (fileList) => {
        const incoming = Array.from(fileList || []);
        if (incoming.length === 0) return;

        const existing = new Set(files.map(fileKey));
        const next = [...files];
        const skipped = [];

        for (const file of incoming) {
            const isPdf = file.type === 'application/pdf'
                || /\.pdf$/i.test(file.name)
                || file.type === ''
                || file.type === 'application/octet-stream';

            if (!isPdf) {
                skipped.push(`«${file.name}» ไม่ใช่ PDF`);
                continue;
            }
            if (existing.has(fileKey(file))) {
                skipped.push(`«${file.name}» เลือกไว้แล้ว`);
                continue;
            }
            if (next.length >= maxFiles) {
                skipped.push(`เกินจำนวนสูงสุด ${maxFiles} ไฟล์`);
                break;
            }
            existing.add(fileKey(file));
            next.push(file);
        }

        files = next;
        showError(skipped.length ? skipped.join('\n') : '');
        render();
    };

    const openPicker = () => {
        if (!input) return;
        input.value = '';
        input.click();
    };

    btnPick?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        openPicker();
    });
    btnAdd?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        openPicker();
    });
    dropzone?.addEventListener('click', (e) => {
        if (e.target.closest('button')) return;
        openPicker();
    });

    input?.addEventListener('change', () => addFiles(input.files));

    btnClear?.addEventListener('click', () => {
        files = [];
        showError('');
        render();
    });

    ['dragenter', 'dragover'].forEach((evt) => {
        dropzone?.addEventListener(evt, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('is-dragover');
        });
    });
    ['dragleave', 'drop'].forEach((evt) => {
        dropzone?.addEventListener(evt, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('is-dragover');
        });
    });
    dropzone?.addEventListener('drop', (e) => {
        addFiles(e.dataTransfer?.files);
    });

    form.addEventListener('submit', (e) => {
        if (files.length === 0) {
            e.preventDefault();
            showError('กรุณาเลือกไฟล์ PDF อย่างน้อย 1 ไฟล์');
            return;
        }
        syncHiddenInputs();
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> กำลังอ่านไฟล์...';
            refreshIcons();
        }
    });

    render();
})();
