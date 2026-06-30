<!doctype html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานสรุปผลการสอบ — SciGrad</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Thai', sans-serif; font-size: 11px; margin: 20px; }
        h1, h2 { text-align: center; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 3px 5px; text-align: center; }
        th { background: #f5f5f5; }
        .left { text-align: left; }
        .no-print { margin-bottom: 12px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print">
        @if ($role === 'faculty_admin')
            <form method="GET" style="margin-bottom:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <select name="approv" style="padding:6px">
                    <option value="">ทุกสถานะ (คณะอนุมัติเป็นค่าเริ่มต้น)</option>
                    <option value="0" @selected(request('approv') === '0')>ยังไม่ผ่านกรรมการ</option>
                    <option value="1" @selected(request('approv') === '1')>สาขาอนุมัติ</option>
                    <option value="2" @selected(request('approv') === '2' || !request()->has('approv'))>คณะอนุมัติ</option>
                    <option value="-1" @selected(request('approv') === '-1')>ส่งกลับแก้ไข</option>
                </select>
                <input type="text" name="fac" value="{{ $fac }}" placeholder="กรองคณะ เช่น SC" style="padding:6px;width:140px">
                <button type="submit" style="padding:6px 12px;cursor:pointer">กรอง</button>
            </form>
        @endif
        <button onclick="window.print()" style="padding:8px 16px;cursor:pointer">พิมพ์</button>
        <button onclick="window.close()" style="padding:8px 16px;cursor:pointer">ปิด</button>
    </div>

    <h1>รายงานสรุปผลการสอบไล่</h1>
    <h2>คณะวิทยาศาสตร์ มหาวิทยาลัยขอนแก่น</h2>
    <p style="text-align:center">
        {{ $role === 'faculty_admin' ? 'รายงานระดับคณะ' : 'รายงานภาพรวมสาขา' }}
        @if ($fac) — กรองคณะ: {{ $fac }} @endif
        ({{ $reports->count() }} รายการ)
    </p>

    <table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th class="left">รหัสวิชา</th>
                <th class="left">ชื่อวิชา</th>
                <th>ภาค/ปี</th>
                <th>Sec</th>
                <th>คณะ</th>
                <th>จำนวน</th>
                <th>A</th><th>B+</th><th>B</th><th>C+</th><th>C</th>
                <th>D+</th><th>D</th><th>F</th><th>I</th><th>S</th><th>U</th><th>W</th>
                <th>เฉลี่ย</th><th>SD</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reports as $i => $report)
                @php $std = $report->gradeStds->first(); @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="left">{{ $report->subject_code }}</td>
                    <td class="left">{{ $report->subject }}</td>
                    <td>{{ match($report->term){1=>'ต้น',2=>'ปลาย',default=>'พิเศษ'} }} {{ $report->year }}</td>
                    <td>{{ $std?->sec }}</td>
                    <td>{{ $std?->fac }}</td>
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
                    <td>{{ $report->mean }}</td>
                    <td>{{ $report->sd }}</td>
                    <td>{{ $report->statusLabel() }}</td>
                </tr>
            @empty
                <tr><td colspan="22">ไม่มีข้อมูล</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
