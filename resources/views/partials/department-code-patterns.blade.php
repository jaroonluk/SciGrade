{{--
  แสดงเงื่อนไขรหัสวิชาที่ใช้กรองตามสาขา
  Required: $patternsByDepartment = [id => ['name' => string, 'patterns' => list<array{pattern,label,kind}>]]
  Optional: $panelId, $selectName, $initialDepartmentId, $helpText
--}}
@php
    $panelId = $panelId ?? 'department-code-patterns';
    $selectName = $selectName ?? 'department_id';
    $initialDepartmentId = (string) ($initialDepartmentId ?? '');
    $helpText = $helpText ?? 'รายงานจะรวมเฉพาะรายวิชาที่รหัสตรงตามเงื่อนไขของสาขานี้ — ใช้ตรวจสอบก่อนเพิ่ม/ลบข้อมูล';
    $initial = $initialDepartmentId !== '' && isset($patternsByDepartment[(int) $initialDepartmentId])
        ? $patternsByDepartment[(int) $initialDepartmentId]
        : null;
@endphp

@once
    @push('styles')
    <style>
        .pattern-chip {
            display: inline-flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 7.5rem;
            padding: 0.55rem 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid #e8c4b8;
            background: linear-gradient(180deg, #fffdfb 0%, #faf0e6 100%);
            box-shadow: 0 1px 2px rgba(139, 69, 19, 0.06);
        }
        .pattern-chip code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.85rem;
            font-weight: 700;
            color: #8B4513;
            letter-spacing: 0.02em;
        }
        .pattern-chip span {
            font-size: 0.68rem;
            color: #7A4A3A;
            line-height: 1.25;
        }
        .pattern-chip.is-exact {
            border-color: #c4d4e8;
            background: linear-gradient(180deg, #ffffff 0%, #eef5ff 100%);
        }
        .pattern-chip.is-exact code { color: #1e4b7b; }
        .pattern-chip.is-contains {
            border-color: #c9dfc8;
            background: linear-gradient(180deg, #ffffff 0%, #f1f8f0 100%);
        }
        .pattern-chip.is-contains code { color: #2f6b3a; }
    </style>
    @endpush
@endonce

<div id="{{ $panelId }}"
     class="rounded-xl border border-[#E8C4B8]/80 bg-gradient-to-br from-[#FFFBF7] via-[#FAF0E6]/70 to-[#F5E6D8]/40 overflow-hidden {{ $initial ? '' : 'hidden' }}"
     data-patterns='@json($patternsByDepartment)'
     data-select-name="{{ $selectName }}"
     data-help="{{ $helpText }}">
    <div class="px-4 py-3 border-b border-[#E8C4B8]/60 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-[#A0522D]/80">เงื่อนไขรหัสวิชาของสาขา</p>
            <h4 class="js-dept-pattern-name text-base font-bold text-[#5C2E1F] mt-0.5 flex items-center gap-2">
                <i data-lucide="filter" class="w-4 h-4 text-[#8B4513]"></i>
                <span>{{ $initial['name'] ?? '—' }}</span>
            </h4>
            <p class="js-dept-pattern-help text-xs text-[#7A4A3A]/80 mt-1">{{ $helpText }}</p>
        </div>
        <div class="shrink-0 rounded-lg bg-white/80 border border-[#E8C4B8]/70 px-3 py-2 text-center">
            <p class="text-[0.65rem] text-[#A0522D]/70">จำนวนเงื่อนไข</p>
            <p class="js-dept-pattern-count text-lg font-bold text-[#8B4513] leading-none">{{ $initial ? count($initial['patterns']) : 0 }}</p>
        </div>
    </div>

    <div class="js-dept-pattern-body px-4 py-4">
        @if ($initial && ($initial['patterns'] ?? []) !== [])
            <div class="flex flex-wrap gap-2.5">
                @foreach ($initial['patterns'] as $item)
                    <div class="pattern-chip is-{{ $item['kind'] }}" title="{{ $item['label'] }}">
                        <code>{{ $item['pattern'] }}</code>
                        <span>{{ $item['label'] }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex flex-wrap gap-4 text-[0.7rem] text-[#7A4A3A]/75">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#C4725C]"></span> ขึ้นต้น / ลงท้าย
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#5a9a63]"></span> มีข้อความในรหัส
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#4a7fb0]"></span> รหัสตรงทั้งหมด
                </span>
            </div>
        @elseif ($initial)
            <div class="text-sm text-amber-800 bg-amber-50/80 rounded-lg px-3 py-2">
                ยังไม่ได้กำหนดเงื่อนไขรหัสวิชาสำหรับสาขานี้
            </div>
        @endif
    </div>
</div>

@once
    @push('scripts')
    <script>
    (function () {
        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        const legendHtml = `
            <div class="mt-4 flex flex-wrap gap-4 text-[0.7rem] text-[#7A4A3A]/75">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#C4725C]"></span> ขึ้นต้น / ลงท้าย
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#5a9a63]"></span> มีข้อความในรหัส
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#4a7fb0]"></span> รหัสตรงทั้งหมด
                </span>
            </div>
        `;

        const renderPanel = (panel, departmentId) => {
            const map = JSON.parse(panel.dataset.patterns || '{}');
            const data = map[String(departmentId)] || map[departmentId] || null;
            const nameEl = panel.querySelector('.js-dept-pattern-name span');
            const countEl = panel.querySelector('.js-dept-pattern-count');
            const bodyEl = panel.querySelector('.js-dept-pattern-body');

            if (!data) {
                panel.classList.add('hidden');
                return;
            }

            panel.classList.remove('hidden');
            if (nameEl) nameEl.textContent = data.name || '—';
            if (countEl) countEl.textContent = String((data.patterns || []).length);

            if (!bodyEl) return;

            const patterns = data.patterns || [];
            if (!patterns.length) {
                bodyEl.innerHTML = `
                    <div class="text-sm text-amber-800 bg-amber-50/80 rounded-lg px-3 py-2">
                        ยังไม่ได้กำหนดเงื่อนไขรหัสวิชาสำหรับสาขานี้
                    </div>
                `;
                return;
            }

            bodyEl.innerHTML = `
                <div class="flex flex-wrap gap-2.5">
                    ${patterns.map((item) => `
                        <div class="pattern-chip is-${escapeHtml(item.kind)}" title="${escapeHtml(item.label)}">
                            <code>${escapeHtml(item.pattern)}</code>
                            <span>${escapeHtml(item.label)}</span>
                        </div>
                    `).join('')}
                </div>
                ${legendHtml}
            `;

            if (window.lucide?.createIcons) {
                window.lucide.createIcons();
            }
        };

        document.querySelectorAll('[data-patterns]').forEach((panel) => {
            const selectName = panel.dataset.selectName || 'department_id';
            const select = document.querySelector(`select[name="${selectName}"]`);
            if (!select) return;

            const sync = () => renderPanel(panel, select.value);
            select.addEventListener('change', sync);
            sync();
        });
    })();
    </script>
    @endpush
@endonce
