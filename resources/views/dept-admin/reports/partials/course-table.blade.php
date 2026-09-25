@php
    /** @var \App\Services\DeptAdmin\DepartmentReportExportPresenter $presenter */
    /** @var object $course */
    $sections = collect($course->sections ?? [])->values();
    $summary = $presenter->summaryTotals($sections);
    $totalAll = $summary['total_std'];
    $totalColumns = 19;
    $reporter = trim((string) ($course->reporter ?? '')) ?: '-';
    $sectionCount = $sections->count();
    $subjectHtml = e($course->subject_code).' '.e(strtoupper((string) $course->subject)).'<br>'.e($course->teacher);
    $empty = '&nbsp;';
    $noteText = 'หมายเหตุ : '.($course->reason ?: '-');
    $reporterText = 'ผู้รายงาน : '.$reporter;
    /*
     | คอลัมน์ tbody ต้องครบ 19 ช่องทุกแถว
     | 1 ลำดับที่ | 2 ชื่อวิชา | 3 กลุ่ม | 4 เกรด | 5-16 A..W | 17 รวม | 18 ค่าเฉลี่ย | 19 SD
     */
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
                @php
                    $sectionLabel = $presenter->formatSectionLabel($std);
                    $studentCount = (int) $std->total_std;
                    // แถวในบล็อก meta: 0 = แรก, สุดท้าย = รวม/%
                    $metaPos = $index === 0 ? 'meta-span-start' : 'meta-span-mid';
                @endphp
                <tr class="section-row">
                    <td class="course-meta course-meta-order {{ $metaPos }}">{{ $index === 0 ? $number : '' }}{!! $index === 0 ? '' : $empty !!}</td>
                    <td class="left course-meta course-meta-subject {{ $metaPos }}">{!! ($index === 0 ? $subjectHtml : $empty) !!}</td>
                    <td class="col-section">{{ $sectionLabel !== '' ? $sectionLabel : '-' }}</td>
                    <td class="col-grade-count">{{ $studentCount }}</td>
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
                    <td>{{ $studentCount }}</td>
                    @if ($index === 0)
                        <td class="course-meta {{ $metaPos }}">{{ $presenter->formatMean($course->mean) }}</td>
                        <td class="course-meta {{ $metaPos }}">{{ $presenter->formatSd($course->sd) }}</td>
                    @else
                        <td class="course-meta {{ $metaPos }}">{!! $empty !!}</td>
                        <td class="course-meta {{ $metaPos }}">{!! $empty !!}</td>
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
                <tr class="summary-row">
                    <td class="course-meta course-meta-filler meta-span-mid">{!! $empty !!}</td>
                    <td class="course-meta course-meta-filler meta-span-mid">{!! $empty !!}</td>
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
                    <td class="course-meta meta-span-mid">{!! $empty !!}</td>
                    <td class="course-meta meta-span-mid">{!! $empty !!}</td>
                </tr>
                <tr class="summary-row">
                    <td class="course-meta course-meta-filler meta-span-end">{!! $empty !!}</td>
                    <td class="course-meta course-meta-filler meta-span-end">{!! $empty !!}</td>
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
                    <td class="course-meta meta-span-end">{!! $empty !!}</td>
                    <td class="course-meta meta-span-end">{!! $empty !!}</td>
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
