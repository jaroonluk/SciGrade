<!doctype html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบรายงานผลการสอบไล่ — {{ $gradeReport->subject_code }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 10mm 12mm; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Noto Sans Thai', sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 12px 16px 28px;
            color: #111;
        }
        h1 { text-align: center; font-size: 18px; margin: 0 0 6px; }
        .subtitle { text-align: center; font-size: 13px; margin: 4px 0; }
        table.report {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            table-layout: fixed;
        }
        table.report th, table.report td {
            border: 1px solid #333;
            padding: 3px 4px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }
        table.report th { background: #f0f0f0; font-weight: 600; }
        table.report th.th-center,
        table.report td.th-center { text-align: center; }
        table.report .left { text-align: left; }
        table.report .strong { font-weight: 700; }
        table.report th.th-order {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
            word-break: keep-all;
            line-height: 1.35;
            min-width: 2.75rem;
            padding: 4px 6px;
        }
        table.report th.th-subject-header {
            text-align: center;
            vertical-align: middle;
            line-height: 1.35;
        }
        .no-print { margin-bottom: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        .no-print button, .no-print a {
            padding: 8px 16px;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #ccc;
            background: #fff;
        }
        .no-print .primary { background: #8B4513; color: #fff; border-color: #8B4513; }
        .sign-area {
            margin-top: 56px;
            padding-top: 8px;
            font-size: 14px;
            max-width: 28rem;
        }
        .sign-area .sign-space {
            min-height: 72px;
            margin-bottom: 8px;
        }
        .sign-area .sign-line {
            margin: 0 0 6px;
            line-height: 1.6;
        }
        .print-footer {
            margin-top: 16px;
            text-align: right;
            font-size: 10px;
            color: #444;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0 0 20px; }
            table.report th.th-order {
                white-space: nowrap;
                font-size: 10px;
            }
            .sign-area {
                margin-top: 64px;
                page-break-inside: avoid;
            }
            .sign-area .sign-space {
                min-height: 80px;
            }
            .print-footer {
                position: fixed;
                bottom: 5mm;
                right: 12mm;
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" class="primary" onclick="window.print()">พิมพ์ / บันทึกเป็น PDF</button>
        <a href="{{ route('dashboard') }}">กลับหน้าหลัก</a>
        <button type="button" onclick="window.close()">ปิดหน้าต่าง</button>
    </div>

    @php
        $stds = $gradeReport->gradeStds->sortBy(fn ($r) => (int) $r->sec)->values();
        $rowCount = max($stds->count(), 1);
        $typeSuffix = function ($type) {
            return match ((int) $type) {
                2 => '(โครงการพิเศษ)',
                3 => '(ปริญญาตรี ก้าวหน้า)',
                4 => '(นานาชาติ)',
                5 => '(นานาชาติ โครงการพิเศษ)',
                default => '',
            };
        };
        $scoreDisp = fn (?string $value) => ($value !== null && trim($value) !== '') ? $value : '-';
        $sum = fn ($key) => (int) $stds->sum($key);
        $totalAll = (int) $stds->sum(fn ($r) => (int) $r->total_std);
        $pct = fn ($n) => $totalAll > 0 ? number_format(($n * 100) / $totalAll, 2, '.', ',') : '-';
        $fmtMean = $gradeReport->mean !== '' && (float) $gradeReport->mean != 0
            ? number_format((float) $gradeReport->mean, 2, '.', ',') : '-';
        $fmtSd = $gradeReport->sd !== '' && (float) $gradeReport->sd != 0
            ? number_format((float) $gradeReport->sd, 2, '.', ',') : '-';
        $term = (int) $gradeReport->term;
        $totalColumns = 19;
    @endphp

    <h1>แบบรายงานผลการสอบไล่</h1>
    <p class="subtitle">
        ประจำภาค ( {{ $term === 1 ? '/' : ' ' }} ) ต้น
        ( {{ $term === 2 ? '/' : ' ' }} ) ปลาย
        ( {{ $term === 3 ? '/' : ' ' }} ) ภาคการศึกษาพิเศษ
        ปีการศึกษา {{ $gradeReport->year }}
    </p>
    <p class="subtitle">
        รหัส/ชื่อวิชา (ภาษาอังกฤษ) {{ $gradeReport->subject_code }} {{ strtoupper($gradeReport->subject) }}
    </p>

    <table class="report">
        <thead>
            <tr>
                <th rowspan="2" class="th-order" style="width:4%">ลำดับที่</th>
                <th rowspan="2" class="th-subject-header" style="width:14%">ชื่อวิชา<br>(อาจารย์ผู้สอน)</th>
                <th rowspan="2" style="width:9%">กลุ่ม<br>(คณะ)</th>
                <th style="width:5%">เกรด</th>
                @foreach (['A','B+','B','C+','C','D+','D','F','I','S','U','W'] as $g)
                    <th style="width:4%">{{ $g }}</th>
                @endforeach
                <th style="width:4%">รวม</th>
                <th rowspan="2" style="width:5%">ค่าเฉลี่ย</th>
                <th rowspan="2" style="width:4%">SD</th>
            </tr>
            <tr>
                <th>ช่วงคะแนน</th>
                <td>{{ $scoreDisp($gradeReport->score_a) }}</td>
                <td>{{ $scoreDisp($gradeReport->score_bb) }}</td>
                <td>{{ $scoreDisp($gradeReport->score_b) }}</td>
                <td>{{ $scoreDisp($gradeReport->score_cc) }}</td>
                <td>{{ $scoreDisp($gradeReport->score_c) }}</td>
                <td>{{ $scoreDisp($gradeReport->score_dd) }}</td>
                <td>{{ $scoreDisp($gradeReport->score_d) }}</td>
                <td>{{ $scoreDisp($gradeReport->score_f) }}</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
            </tr>
        </thead>
        <tbody>
            @forelse ($stds as $index => $std)
                <tr>
                    @if ($index === 0)
                        <td rowspan="{{ $rowCount + 2 }}">1</td>
                        <td rowspan="{{ $rowCount + 2 }}" class="left">
                            {{ $gradeReport->subject_code }} {{ strtoupper($gradeReport->subject) }}<br>
                            {{ $gradeReport->teacher }}
                        </td>
                    @endif
                    <td>{{ $std->sec }} {{ strtoupper($std->fac ?? '') }} {{ $typeSuffix($std->type_course) }}</td>
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
                        <td rowspan="{{ $rowCount + 2 }}">{{ $fmtMean }}</td>
                        <td rowspan="{{ $rowCount + 2 }}">{{ $fmtSd }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td>1</td>
                    <td class="left" colspan="{{ $totalColumns - 1 }}">ยังไม่มีข้อมูลจำนวนนักศึกษา</td>
                </tr>
            @endforelse

            @if ($stds->isNotEmpty())
                <tr>
                    <td class="strong">รวม</td>
                    <td>{{ $totalAll }}</td>
                    <td>{{ $sum('num_a') }}</td>
                    <td>{{ $sum('num_bb') }}</td>
                    <td>{{ $sum('num_b') }}</td>
                    <td>{{ $sum('num_cc') }}</td>
                    <td>{{ $sum('num_c') }}</td>
                    <td>{{ $sum('num_dd') }}</td>
                    <td>{{ $sum('num_d') }}</td>
                    <td>{{ $sum('num_f') }}</td>
                    <td>{{ $sum('num_i') }}</td>
                    <td>{{ $sum('num_s') }}</td>
                    <td>{{ $sum('num_v') }}</td>
                    <td>{{ $sum('num_w') }}</td>
                    <td>{{ $totalAll }}</td>
                </tr>
                <tr>
                    <td class="strong">%</td>
                    <td>-</td>
                    <td>{{ $pct($sum('num_a')) }}</td>
                    <td>{{ $pct($sum('num_bb')) }}</td>
                    <td>{{ $pct($sum('num_b')) }}</td>
                    <td>{{ $pct($sum('num_cc')) }}</td>
                    <td>{{ $pct($sum('num_c')) }}</td>
                    <td>{{ $pct($sum('num_dd')) }}</td>
                    <td>{{ $pct($sum('num_d')) }}</td>
                    <td>{{ $pct($sum('num_f')) }}</td>
                    <td>{{ $pct($sum('num_i')) }}</td>
                    <td>{{ $pct($sum('num_s')) }}</td>
                    <td>{{ $pct($sum('num_v')) }}</td>
                    <td>{{ $pct($sum('num_w')) }}</td>
                    <td>100.00</td>
                </tr>
            @endif

            <tr>
                <td colspan="{{ $totalColumns }}" class="left">หมายเหตุ : {{ $gradeReport->reason ?: '-' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="sign-area">
        <div class="sign-space" aria-hidden="true"></div>
        <p class="sign-line">ลงชื่อ....................................................</p>
        <p class="sign-line">&nbsp;&nbsp;&nbsp;&nbsp;( {{ $teacherSignName }} )</p>
        <p class="sign-line">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;อาจารย์ประจำวิชา</p>
    </div>

    <div class="print-footer">พิมพ์เมื่อ {{ $printedAt }}</div>
</body>
</html>
