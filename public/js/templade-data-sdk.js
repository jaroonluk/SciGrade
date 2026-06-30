(function () {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

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

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationMsg = data.errors
                ? Object.values(data.errors).flat().join(' ')
                : null;
            throw new Error(validationMsg || data.message || 'เกิดข้อผิดพลาด');
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
                await request('/api/grade-reports', {
                    method: 'POST',
                    body: JSON.stringify(record),
                });
                await reload();
                return { isOk: true };
            } catch (e) {
                return { isOk: false, error: e.message };
            }
        },
        async update(record) {
            try {
                if (record.approv !== undefined) {
                    await request(`/api/grade-reports/${record.__backendId}`, {
                        method: 'PUT',
                        body: JSON.stringify({
                            approv: record.approv,
                            rejection_reason: record.rejection_reason,
                            role: record.role,
                        }),
                    });
                } else {
                    await request(`/api/grade-reports/${record.__backendId}`, {
                        method: 'PUT',
                        body: JSON.stringify(record),
                    });
                }
                await reload();
                return { isOk: true };
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
