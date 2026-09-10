(function () {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    function flattenErrors(errors) {
        if (!errors || typeof errors !== 'object') return [];
        const out = [];
        const walk = (value) => {
            if (value == null) return;
            if (typeof value === 'string') {
                const t = value.trim();
                if (t) out.push(t);
                return;
            }
            if (Array.isArray(value)) {
                value.forEach(walk);
                return;
            }
            if (typeof value === 'object') {
                Object.values(value).forEach(walk);
            }
        };
        walk(errors);
        return out;
    }

    function statusHint(status) {
        if (status === 401 || status === 419) {
            return 'เซสชันหมดอายุหรือ CSRF ไม่ถูกต้อง — กรุณารีเฟรชหน้าแล้วเข้าสู่ระบบใหม่ แล้วลองบันทึกอีกครั้ง';
        }
        if (status === 403) {
            return 'ไม่มีสิทธิ์บันทึกรายการนี้ — ตรวจสอบว่าเข้าสู่ระบบด้วยบัญชีบุคลากร มข. และเป็นเจ้าของรายงานหรือร่วมตัดเกรดได้';
        }
        if (status === 413) {
            return 'ข้อมูลที่ส่งมีขนาดใหญ่เกินไป — ลดจำนวน Section หรือข้อความหมายเหตุแล้วลองใหม่';
        }
        if (status === 422) {
            return 'ข้อมูลยังไม่ครบหรือไม่ถูกต้อง — ตรวจขั้นตอนที่ระบบระบุแล้วแก้ก่อนบันทึกอีกครั้ง';
        }
        if (status === 503) {
            return 'เซิร์ฟเวอร์ฐานข้อมูลหนาแน่นชั่วคราว — รอสักครู่แล้วลองใหม่';
        }
        if (status >= 500) {
            return 'เซิร์ฟเวอร์เกิดข้อผิดพลาดขณะบันทึก — ลองใหม่ หรือแจ้งผู้ดูแลระบบหากยังไม่หาย';
        }
        return null;
    }

    function extractErrorMessage(data, response) {
        const fromErrors = flattenErrors(data?.errors);
        if (fromErrors.length) {
            return fromErrors.join(' ');
        }

        const message = typeof data?.message === 'string' ? data.message.trim() : '';
        const hint = typeof data?.hint === 'string' ? data.hint.trim() : '';
        if (message && hint && hint !== message) {
            return `${message} — ${hint}`;
        }
        if (message) return message;
        if (hint) return hint;

        const fallback = statusHint(response.status);
        if (fallback) return fallback;

        if (response.status) {
            return `บันทึกไม่สำเร็จ (รหัส ${response.status})`;
        }
        return 'เกิดข้อผิดพลาดขณะบันทึก กรุณาลองใหม่';
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            ...options,
        });

        const raw = await response.text();
        let data = {};
        if (raw) {
            try {
                data = JSON.parse(raw);
            } catch {
                data = {};
            }
        }

        if (!response.ok) {
            throw new Error(extractErrorMessage(data, response));
        }

        return data;
    }

    let handler = null;
    let currentRole = 'instructor';

    async function reload() {
        const data = await request(`/api/grade-reports?role=${currentRole}`);
        if (handler && typeof handler.onDataChanged === 'function') {
            handler.onDataChanged(data);
        }
    }

    window.dataSdk = {
        init(h) {
            handler = h;
            reload();
        },
        setRole(role) {
            currentRole = role;
            reload();
        },
        async create(record) {
            try {
                const data = await request('/api/grade-reports', {
                    method: 'POST',
                    body: JSON.stringify(record),
                });
                try { await reload(); } catch { /* wizard continues without list refresh */ }
                return { isOk: true, data };
            } catch (e) {
                return { isOk: false, error: e.message };
            }
        },
        async update(record) {
            try {
                let data = null;
                if (record.approv !== undefined) {
                    data = await request(`/api/grade-reports/${record.__backendId}`, {
                        method: 'PUT',
                        body: JSON.stringify({
                            approv: record.approv,
                            rejection_reason: record.rejection_reason,
                            role: record.role,
                        }),
                    });
                } else {
                    data = await request(`/api/grade-reports/${record.__backendId}`, {
                        method: 'PUT',
                        body: JSON.stringify(record),
                    });
                }
                try { await reload(); } catch { /* wizard continues without list refresh */ }
                return { isOk: true, data };
            } catch (e) {
                return { isOk: false, error: e.message };
            }
        },
        async remove(id) {
            try {
                await request(`/api/grade-reports/${id}`, { method: 'DELETE' });
                await reload();
                return { isOk: true };
            } catch (e) {
                return { isOk: false, error: e.message };
            }
        },
    };
})();
