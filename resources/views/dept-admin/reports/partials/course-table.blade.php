@php
    /** @var \App\Services\DeptAdmin\DepartmentReportExportPresenter $presenter */
    /** @var object $course */
    $sections = collect($course->sections ?? [])->values();
    $summary = $presenter->summaryTotals($sections);
    $totalAll = $summary['total_std'];
    $totalColumns = 19;
    $reporter = trim((string) ($course->reporter ?? '')) ?: '-';
    // DomPDF ตัดหน้ากลางตารางที่มี rowspan แล้วเซลล์พัง — แบ่งกลุ่ม Section ต่อตาราง
    $maxSectionsPerChunk = 7;
    $chunks = $sections->isEmpty()
        ? collect([collect()])
        : $sections->chunk($maxSectionsPerChunk)->values();
    $chunkCount = $chunks->count();
@endphp

@foreach ($chunks as $chunkIndex => $chunkSections)
    @php
        $isFirstChunk = $chunkIndex === 0;
        $isLastChunk = $chunkIndex === $chunkCount - 1;
        $chunkLabel = $isFirstChunk ? (string) $number : $number.' (ต่อ)';
        $subjectLabel = $isFirstChunk
            ? ($course->subject_code.' '.strtoupper((string) $course->subject).'<br>'.$course->teacher)
            : ($course->subject_code.' '.strtoupper((string) $course->subject).' <span class="cont-label">(ต่อ)</span><br>'.$course->teacher);
    @endphp
    <div class="report-block {{ $chunkSections->count() <= 4 ? 'report-block-compact' : '' }}">
        <table class="report" border="1" cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; mso-table-layout-alt:fixed;">
            <thead>
                <tr>
                    <th class="th-order" style="width:4%">ลำดับที่</th>
                    <th class="th-subject-header" style="width:16%">ชื่อวิชา<br>(อาจารย์ผู้สอน)</th>
                    <th style="width:8%">กลุ่ม<br>(คณะ)</th>
                    <th style="width:4.5%">เกรด</th>
                    @foreach (['A', 'B+', 'B', 'C+', 'C', 'D+', 'D', 'F', 'I', 'S', 'U', 'W'] as $grade)
                        <th style="width:3.9%">{{ $grade }}</th>
                    @endforeach
                    <th style="width:3.9%">รวม</th>
                    <th style="width:5%">ค่าเฉลี่ย</th>
                    <th style="width:4%">SD</th>
                </tr>
                <tr>
                    <th colspan="3" class="score-range-label">&nbsp;</th>
                    <th>ช่วงคะแนน</th>
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
                    <td></td>
                    <td></td>
                </tr>
            </thead>
            <tbody>
                @forelse ($chunkSections as $index => $std)
                    <tr class="section-row">
                        @if ($index === 0)
                            <td class="course-meta">{{ $chunkLabel }}</td>
                            <td class="left course-meta">{!! $subjectLabel !!}</td>
                        @else
                            <td class="course-meta-empty"></td>
                            <td class="course-meta-empty"></td>
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
                        @if ($index === 0 && $isFirstChunk)
                            <td class="course-meta">{{ $presenter->formatMean($course->mean) }}</td>
                            <td class="course-meta">{{ $presenter->formatSd($course->sd) }}</td>
                        @else
                            <td class="course-meta-empty"></td>
                            <td class="course-meta-empty"></td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td>{{ $number }}</td>
                        <td class="left">{{ $course->subject_code }} {{ strtoupper($course->subject) }}</td>
                        <td class="left" colspan="{{ $totalColumns - 2 }}">ยังไม่มีข้อมูลจำนวนนักศึกษา</td>
                    </tr>
                @endforelse

                @if ($sections->isNotEmpty() && $isLastChunk)
                    <tr class="summary-row">
                        <td class="course-meta-empty"></td>
                        <td class="course-meta-empty"></td>
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
                        <td class="course-meta-empty"></td>
                        <td class="course-meta-empty"></td>
                    </tr>
                    <tr class="summary-row">
                        <td class="course-meta-empty"></td>
                        <td class="course-meta-empty"></td>
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
                        <td class="course-meta-empty"></td>
                        <td class="course-meta-empty"></td>
                    </tr>
                    <tr class="note-row">
                        <td colspan="{{ $totalColumns }}" class="left">หมายเหตุ : {{ $course->reason ?: '-' }}</td>
                    </tr>
                    <tr class="reporter-row">
                        <td colspan="{{ $totalColumns }}" class="left">ผู้รายงาน : {{ $reporter }}</td>
                    </tr>
                @elseif ($sections->isEmpty() && $isLastChunk)
                    <tr class="note-row">
                        <td colspan="{{ $totalColumns }}" class="left">หมายเหตุ : {{ $course->reason ?: '-' }}</td>
                    </tr>
                    <tr class="reporter-row">
                        <td colspan="{{ $totalColumns }}" class="left">ผู้รายงาน : {{ $reporter }}</td>
                    </tr>
                @elseif ($sections->isNotEmpty() && ! $isLastChunk)
                    <tr class="cont-hint-row">
                        <td colspan="{{ $totalColumns }}" class="left cont-hint">… มี Section ถัดไปในตารางหน้าถัดไป …</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
@endforeach
