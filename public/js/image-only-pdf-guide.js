/**
 * หน้าจอแนะนำเมื่ออัปโหลด PDF แบบภาพ (ไม่มีข้อความฝัง)
 * ใช้งาน: await window.SciGradeImagePdfGuide.show()
 */
(function (global) {
    const REG_URL = 'https://reg.kku.ac.th/';
    const TITLE = 'ไฟล์นี้เป็น PDF แบบภาพ — อ่านเนื้อหาไม่ได้';
    const BODY =
        'ไฟล์นี้เป็น PDF แบบภาพ ระบบไม่สามารถอ่านเนื้อหาเพื่อมาแสดงข้อมูลได้ ' +
        'กรุณาใช้ใบ มข.11 ที่ส่งออกจากระบบ REG โดยตรง (มีข้อความเลือกได้) ' +
        'ไม่ใช่ไฟล์สแกนหรือพิมพ์เป็นรูปภาพ แล้วค่อยอัปโหลดใหม่';

    function matches(message) {
        return typeof message === 'string' && message.indexOf('ไฟล์นี้เป็น PDF แบบภาพ') !== -1;
    }

    function ensureModal() {
        let root = document.getElementById('image-only-pdf-guide');
        if (root) {
            return root;
        }

        root = document.createElement('div');
        root.id = 'image-only-pdf-guide';
        root.className = 'hidden fixed inset-0 z-[200] no-print';
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'true');
        root.setAttribute('aria-labelledby', 'image-only-pdf-guide-title');
        root.innerHTML = `
            <div class="absolute inset-0 bg-[#3d2418]/70 backdrop-blur-[2px]" data-image-pdf-backdrop></div>
            <div class="relative z-10 min-h-full flex items-center justify-center p-4 sm:p-6">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 sm:p-8 border-4 border-red-200 text-left">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h2 id="image-only-pdf-guide-title" class="text-xl sm:text-2xl font-bold text-red-800 text-center mb-3"></h2>
                    <p data-image-pdf-body class="text-base sm:text-lg text-[#5C2E1F] leading-relaxed mb-4"></p>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm sm:text-base text-[#5C2E1F] mb-5 space-y-2">
                        <p class="font-semibold text-[#8B4513]">วิธีใช้งานที่ถูกต้อง</p>
                        <ol class="list-decimal pl-5 space-y-1.5">
                            <li>เข้าสู่ระบบทะเบียน มข. (REG)</li>
                            <li>ส่งออกใบ มข.11 เป็น PDF โดยตรงจากระบบ (ต้องมีข้อความเลือกได้)</li>
                            <li>อย่าใช้ไฟล์สแกน / พิมพ์เป็นรูปภาพ / บันทึกจาก Photoshop</li>
                            <li>แล้วนำไฟล์ PDF นั้นมาอัปโหลดใหม่ใน SciGrade</li>
                        </ol>
                        <p class="pt-1">
                            เปิดระบบ REG ได้ที่
                            <a data-image-pdf-reg-link href="${REG_URL}" target="_blank" rel="noopener noreferrer"
                               class="font-semibold text-[#8B4513] underline underline-offset-2 hover:text-[#6d3610]">
                                ${REG_URL}
                            </a>
                        </p>
                    </div>
                    <div class="flex justify-center">
                        <button type="button" data-image-pdf-ok
                            class="px-10 py-3 bg-[#8B4513] text-white text-lg font-semibold rounded-xl hover:bg-[#6d3610] min-w-[10rem] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#8B4513]">
                            ตกลง
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(root);
        return root;
    }

    function show(options) {
        const opts = options || {};
        const title = opts.title || TITLE;
        const body = opts.body || opts.message || BODY;
        const regUrl = opts.regUrl || opts.reg_url || REG_URL;

        const root = ensureModal();
        const titleEl = root.querySelector('#image-only-pdf-guide-title');
        const bodyEl = root.querySelector('[data-image-pdf-body]');
        const linkEl = root.querySelector('[data-image-pdf-reg-link]');
        const okBtn = root.querySelector('[data-image-pdf-ok]');

        if (titleEl) titleEl.textContent = title;
        if (bodyEl) bodyEl.textContent = body;
        if (linkEl) {
            linkEl.href = regUrl;
            linkEl.textContent = regUrl;
        }

        root.classList.remove('hidden');
        root.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');

        return new Promise((resolve) => {
            const finish = () => {
                root.classList.add('hidden');
                root.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
                okBtn?.removeEventListener('click', onOk);
                root.removeEventListener('keydown', onKey);
                resolve();
            };
            const onOk = () => finish();
            const onKey = (e) => {
                if (e.key === 'Escape' || e.key === 'Enter') {
                    e.preventDefault();
                    finish();
                }
            };

            okBtn?.addEventListener('click', onOk);
            root.addEventListener('keydown', onKey);
            setTimeout(() => okBtn?.focus(), 50);
        });
    }

    global.SciGradeImagePdfGuide = {
        REG_URL,
        TITLE,
        BODY,
        matches,
        show,
    };
})(window);
