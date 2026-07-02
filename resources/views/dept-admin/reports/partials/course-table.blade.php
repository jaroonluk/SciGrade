@php
    /** @var \App\Services\DeptAdmin\DepartmentReportExportPresenter $presenter */
    $sections = $presenter->sortedSections($report);
    $rowCount = max($sections->count(), 1);
    $rowspan = $sections->isNotEmpty() ? $rowCount + 2 : 1;
    $summary = $presenter->summaryTotals($sections);
    $totalAll = $summary['total_std'];
    $totalColumns = 19;
@endphp

<div class="report-block">
    <table class="report" border="1" cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; mso-table-layout-alt:fixed;">
        <thead>
            <tr>
                <th rowspan="2" class="th-order" style="width:4%">ลำดับที่</th>
                <th rowspan="2" class="th-subject-header" style="width:14%">ชื่อวิชา<br>(อาจารย์ผู้สอน)</th>
                <th rowspan="2" style="width:9%">กลุ่ม<br>(คณะ)</th>
                <th style="width:5%">เกรด</th>
                @foreach (['A', 'B+', 'B', 'C+', 'C', 'D+', 'D', 'F', 'I', 'S', 'U', 'W'] as $grade)
                    <th style="width:4%">{{ $grade }}</th>
                @endforeach
                <th style="width:4%">รวม</th>
                <th rowspan="2" style="width:5%">ค่าเฉลี่ย</th>
                <th rowspan="2" style="width:4%">SD</th>
            </tr>
            <tr>
                <th>ช่วงคะแนน</th>
                <td>{{ $presenter->scoreDisplay($report->score_a) }}</td>
                <td>{{ $presenter->scoreDisplay($report->score_bb) }}</td>
                <td>{{ $presenter->scoreDisplay($report->score_b) }}</td>
                <td>{{ $presenter->scoreDisplay($report->score_cc) }}</td>
                <td>{{ $presenter->scoreDisplay($report->score_c) }}</td>
                <td>{{ $presenter->scoreDisplay($report->score_dd) }}</td>
                <td>{{ $presenter->scoreDisplay($report->score_d) }}</td>
                <td>{{ $presenter->scoreDisplay($report->score_f) }}</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
            </tr>
        </thead>
        <tbody>
            @forelse ($sections as $index => $std)
                <tr>
                    @if ($index === 0)
                        <td rowspan="{{ $rowspan }}">{{ $number }}</td>
                        <td rowspan="{{ $rowspan }}" class="left">
                            {{ $report->subject_code }} {{ strtoupper($report->subject) }}<br>
                            {{ $report->teacher }}
                        </td>
                    @endif
                    <td>{{ $presenter->formatSectionLabel($std) }}</td>
                    <td>{{ $std->total_std }}</td>
                    <td>{{ $std->num_a }}</td>
                    <td>{{ $std->num_bb }}</td>
                    <td>{{ $std->num_b }}</td>
                    <td>{{ $std->num_cc }}</td>
                    <td>{{ $std->num_c }}</td>
                    <td>{{ $std->num_dd }}</td>
                    <td>{{ $std->num_d }}</td>
                    <td>{{ $std->num_f }}</td>
                    <td>{{ $std->num_i }}</td>
                    <td>{{ $std->num_s }}</td>
                    <td>{{ $std->num_v }}</td>
                    <td>{{ $std->num_w }}</td>
                    <td>{{ $std->total_std }}</td>
                    @if ($index === 0)
                        <td rowspan="{{ $rowspan }}">{{ $presenter->formatMean($report->mean) }}</td>
                        <td rowspan="{{ $rowspan }}">{{ $presenter->formatSd($report->sd) }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td>{{ $number }}</td>
                    <td class="left" colspan="{{ $totalColumns - 1 }}">ยังไม่มีข้อมูลจำนวนนักศึกษา</td>
                </tr>
            @endforelse

            @if ($sections->isNotEmpty())
                <tr>
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
                <tr>
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

            <tr>
                <td colspan="{{ $totalColumns }}" class="left">หมายเหตุ : {{ $report->reason ?: '-' }}</td>
            </tr>
        </tbody>
    </table>
</div>
