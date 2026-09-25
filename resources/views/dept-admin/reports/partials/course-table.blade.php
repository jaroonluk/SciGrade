@php
    /** @var \App\Services\DeptAdmin\DepartmentReportExportPresenter $presenter */
    /** @var object $course */
    $sections = collect($course->sections ?? [])->values();
    $summary = $presenter->summaryTotals($sections);
    $totalAll = $summary['total_std'];
    $totalColumns = 19;
    $reporter = trim((string) ($course->reporter ?? '')) ?: '-';
    $sectionCount = $sections->count();
    // ลำดับที่/ชื่อวิชา: rowspan เฉพาะแถว Section — แถวรวม/% ใส่เซลล์เองเสมอ
    // เพื่อไม่ให้ข้อความ "รวม" / "%" ไปโผล่ที่คอลัมน์ลำดับที่ เมื่อ DomPDF ทำลาย rowspan
    $orderSubjectRowspan = max($sectionCount, 1);
    // ค่าเฉลี่ย/SD: rowspan ครอบ Section + รวม + % ตามแบบเดิม
    $meanSdRowspan = $sectionCount > 0 ? $sectionCount + 2 : 1;
    $subjectHtml = e($course->subject_code).' '.e(strtoupper((string) $course->subject)).'<br>'.e($course->teacher);
    $empty = '&nbsp;';
    $noteText = 'หมายเหตุ : '.($course->reason ?: '-');
    $reporterText = 'ผู้รายงาน : '.$reporter;
@endphp

<div class="report-block {{ $sectionCount <= 6 ? 'report-block-compact' : '' }}">
    <table class="report" border="1" cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; mso-table-layout-alt:fixed;">
        <thead>
            <tr>
                <th rowspan="2" class="th-order th-thai" style="width:4%">ลำดับที่</th>
                <th rowspan="2" class="th-subject-header th-thai" style="width:14%">ชื่อวิชา<br>(อาจารย์ผู้สอน)</th>
                <th rowspan="2" class="th-thai" style="width:9%">กลุ่ม<br>(คณะ)</th>
                <th style="width:5%">เกรด</th>
                @foreach (['A', 'B+', 'B', 'C+', 'C', 'D+', 'D', 'F', 'I', 'S', 'U', 'W'] as $grade)
                    <th style="width:4%">{{ $grade }}</th>
                @endforeach
                <th style="width:4%">รวม</th>
                <th rowspan="2" class="th-mean th-thai" style="width:5%">ค่าเฉลี่ย</th>
                <th rowspan="2" style="width:4%">SD</th>
            </tr>
            <tr>
                <th class="th-thai">ช่วงคะแนน</th>
                <td>{{ $presenter->scoreDisplay($course->score_a) }}</td>
                <td>{{ $presenter->scoreDisplay($course->score_bb) }}</td>
                <td>{{ $presenter->scoreDisplay($course->score_b) }}</td>
                <td>{{ $presenter->scoreDisplay($course->score_cc) }}</td>
                <td>{{ $presenter->scoreDisplay($course->score_c) }}</td>
                <td>{{ $presenter->scoreDisplay($course->score_dd) }}</td>
                <td>{{ $presenter->scoreDisplay($course->score_d) }}</td>
                <td>{{ $presenter->scoreDisplay($course->score_f) }}</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
            </tr>
        </thead>
        <tbody>
            @forelse ($sections as $index => $std)
                <tr class="section-row">
                    @if ($index === 0)
                        <td rowspan="{{ $orderSubjectRowspan }}" class="course-meta course-meta-order">{{ $number }}</td>
                        <td rowspan="{{ $orderSubjectRowspan }}" class="left course-meta course-meta-subject">{!! $subjectHtml !!}</td>
                    @endif
                    <td>{{ $presenter->formatSectionLabel($std) }}</td>
                    <td>{{ (int) $std->total_std }}</td>
                    <td>{{ (int) $std->num_a }}</td>
                    <td>{{ (int) $std->num_bb }}</td>
                    <td>{{ (int) $std->num_b }}</td>
                    <td>{{ (int) $std->num_cc }}</td>
                    <td>{{ (int) $std->num_c }}</td>
                    <td>{{ (int) $std->num_dd }}</td>
                    <td>{{ (int) $std->num_d }}</td>
                    <td>{{ (int) $std->num_f }}</td>
                    <td>{{ (int) $std->num_i }}</td>
                    <td>{{ (int) $std->num_s }}</td>
                    <td>{{ (int) $std->num_v }}</td>
                    <td>{{ (int) $std->num_w }}</td>
                    <td>{{ (int) $std->total_std }}</td>
                    @if ($index === 0)
                        <td rowspan="{{ $meanSdRowspan }}" class="course-meta">{{ $presenter->formatMean($course->mean) }}</td>
                        <td rowspan="{{ $meanSdRowspan }}" class="course-meta">{{ $presenter->formatSd($course->sd) }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td>{{ $number }}</td>
                    <td class="left">{{ $course->subject_code }} {{ strtoupper($course->subject) }}</td>
                    <td class="left" colspan="{{ $totalColumns - 2 }}">ยังไม่มีข้อมูลจำนวนนักศึกษา</td>
                </tr>
            @endforelse

            @if ($sections->isNotEmpty())
                {{-- ใส่เซลล์ลำดับที่/ชื่อวิชาเองเสมอ → "รวม"/"%" อยู่คอลัมน์กลุ่มแน่นอน --}}
                <tr class="summary-row">
                    <td class="course-meta-filler">{!! $empty !!}</td>
                    <td class="course-meta-filler">{!! $empty !!}</td>
                    <td class="strong">รวม</td>
                    <td>{{ $totalAll }}</td>
                    <td>{{ $summary['num_a'] }}</td>
                    <td>{{ $summary['num_bb'] }}</td>
                    <td>{{ $summary['num_b'] }}</td>
                    <td>{{ $summary['num_cc'] }}</td>
                    <td>{{ $summary['num_c'] }}</td>
                    <td>{{ $summary['num_dd'] }}</td>
                    <td>{{ $summary['num_d'] }}</td>
                    <td>{{ $summary['num_f'] }}</td>
                    <td>{{ $summary['num_i'] }}</td>
                    <td>{{ $summary['num_s'] }}</td>
                    <td>{{ $summary['num_v'] }}</td>
                    <td>{{ $summary['num_w'] }}</td>
                    <td>{{ $totalAll }}</td>
                </tr>
                <tr class="summary-row">
                    <td class="course-meta-filler">{!! $empty !!}</td>
                    <td class="course-meta-filler">{!! $empty !!}</td>
                    <td class="strong">%</td>
                    <td>-</td>
                    <td>{{ $presenter->formatPercent($summary['num_a'], $totalAll) }}</td>
                    <td>{{ $presenter->formatPercent($summary['num_bb'], $totalAll) }}</td>
                    <td>{{ $presenter->formatPercent($summary['num_b'], $totalAll) }}</td>
                    <td>{{ $presenter->formatPercent($summary['num_cc'], $totalAll) }}</td>
                    <td>{{ $presenter->formatPercent($summary['num_c'], $totalAll) }}</td>
                    <td>{{ $presenter->formatPercent($summary['num_dd'], $totalAll) }}</td>
                    <td>{{ $presenter->formatPercent($summary['num_d'], $totalAll) }}</td>
                    <td>{{ $presenter->formatPercent($summary['num_f'], $totalAll) }}</td>
                    <td>{{ $presenter->formatPercent($summary['num_i'], $totalAll) }}</td>
                    <td>{{ $presenter->formatPercent($summary['num_s'], $totalAll) }}</td>
                    <td>{{ $presenter->formatPercent($summary['num_v'], $totalAll) }}</td>
                    <td>{{ $presenter->formatPercent($summary['num_w'], $totalAll) }}</td>
                    <td>100.00</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="report-foot" border="1" cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; mso-table-layout-alt:fixed;">
        <tr class="note-row">
            <td class="left">{{ $noteText }}</td>
        </tr>
        <tr class="reporter-row">
            <td class="left">{{ $reporterText }}</td>
        </tr>
    </table>
</div>
