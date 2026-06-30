<!doctype html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แบบรายงานผลการสอบไล่ — {{ $gradeReport->subject_code }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Thai', sans-serif; font-size: 12px; margin: 24px; color: #111; }
        h1, p { text-align: center; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: center; }
        th { background: #f3f3f3; }
        .left { text-align: left; }
        .no-print { margin-bottom: 16px; }
        @media print { .no-print { display: none; } body { margin: 12px; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 16px;cursor:pointer">พิมพ์</button>
        <button onclick="window.close()" style="padding:8px 16px;cursor:pointer">ปิด</button>
    </div>

    <h1>แบบรายงานผลการสอบไล่</h1>
    <p>
        ประจำภาค ( {{ $gradeReport->term === 1 ? '/' : '&nbsp;' }} ) ต้น
        ( {{ $gradeReport->term === 2 ? '/' : '&nbsp;' }} ) ปลาย
        ( {{ $gradeReport->term === 3 ? '/' : '&nbsp;' }} ) ภาคการศึกษาพิเศษ
        ปีการศึกษา {{ $gradeReport->year }}
    </p>
    <p>รหัส/ชื่อวิชา (ภาษาอังกฤษ) {{ $gradeReport->subject_code }} {{ strtoupper($gradeReport->subject) }}</p>

    @php
        $std = $gradeReport->gradeStds->first();
        $typeCourse = match ($std?->type_course) {
            2 => '(โครงการพิเศษ)',
            3 => '(ปริญญาตรี ก้าวหน้า)',
            4 => '(นานาชาติ)',
            5 => '(นานาชาติ โครงการพิเศษ)',
            default => '',
        };
    @endphp

    <table>
        <thead>
            <tr>
                <th rowspan="2">ลำดับที่</th>
                <th rowspan="2" class="left">ชื่อวิชา<br>(อาจารย์ผู้สอน)</th>
                <th rowspan="2">กลุ่ม<br>(คณะ)</th>
                <th>เกรด</th>
                @foreach (['A','B+','B','C+','C','D+','D','F','I','S','U','W'] as $g)
                    <th>{{ $g }}</th>
                @endforeach
                <th>รวม</th>
                <th>ค่าเฉลี่ย</th>
                <th>SD</th>
            </tr>
            <tr>
                <th>ช่วงคะแนน</th>
                <td>{{ $gradeReport->score_a }}</td>
                <td>{{ $gradeReport->score_bb }}</td>
                <td>{{ $gradeReport->score_b }}</td>
                <td>{{ $gradeReport->score_cc }}</td>
                <td>{{ $gradeReport->score_c }}</td>
                <td>{{ $gradeReport->score_dd }}</td>
                <td>{{ $gradeReport->score_d }}</td>
                <td>{{ $gradeReport->score_f }}</td>
                <td colspan="4"></td>
                <td colspan="3"></td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td class="left">
                    {{ $gradeReport->subject_code }} {{ strtoupper($gradeReport->subject) }}<br>
                    {{ $gradeReport->teacher }}
                </td>
                <td>{{ $std?->sec }} {{ strtoupper($std?->fac ?? '') }} {{ $typeCourse }}</td>
                <td>{{ $std?->total_std }}</td>
                <td>{{ $std?->num_a }}</td>
                <td>{{ $std?->num_bb }}</td>
                <td>{{ $std?->num_b }}</td>
                <td>{{ $std?->num_cc }}</td>
                <td>{{ $std?->num_c }}</td>
                <td>{{ $std?->num_dd }}</td>
                <td>{{ $std?->num_d }}</td>
                <td>{{ $std?->num_f }}</td>
                <td>{{ $std?->num_i }}</td>
                <td>{{ $std?->num_s }}</td>
                <td>{{ $std?->num_v }}</td>
                <td>{{ $std?->num_w }}</td>
                <td>{{ $std?->total_std }}</td>
                <td>{{ $gradeReport->mean }}</td>
                <td>{{ $gradeReport->sd }}</td>
            </tr>
        </tbody>
    </table>

    @if ($gradeReport->remark)
        <p style="margin-top:12px"><strong>หมายเหตุ:</strong> {{ $gradeReport->remark }}</p>
    @endif

    <p style="margin-top:24px">สถานะ: {{ $gradeReport->statusLabel() }}</p>
</body>
</html>
