<!doctype html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกข้อความ — {{ $fields['subject_line'] }}</title>
    <style>
        body { font-family: "TH Sarabun New", "Noto Sans Thai", sans-serif; font-size: 18px; color: #1c1917; margin: 0; background: #faf6f1; }
        .toolbar { max-width: 210mm; margin: 16px auto 0; display: flex; flex-wrap: wrap; gap: 8px; }
        .toolbar a, .toolbar button {
            display: inline-flex; align-items: center; padding: .45rem .9rem; border-radius: .5rem;
            font-size: .9rem; font-weight: 600; text-decoration: none; border: 1px solid #e8cdb5; background: #fff; color: #5C2E1F; cursor: pointer;
        }
        .toolbar .primary { background: #a16207; border-color: #a16207; color: #fff; }
        .sheet {
            width: 210mm; min-height: 297mm; margin: 16px auto 32px; padding: 22mm 20mm;
            background: #fff; box-shadow: 0 8px 24px rgba(28, 25, 23, .08);
        }
        h1 { text-align: center; font-size: 26px; margin: 0 0 18px; }
        .line { margin: 6px 0; }
        .indent { text-indent: 2.5em; text-align: justify; line-height: 1.7; margin: 14px 0; }
        .filled { font-weight: 700; border-bottom: 1px dotted #78716c; }
        .meta { margin-top: 28px; padding-top: 12px; border-top: 1px solid #e7e5e4; font-size: 15px; color: #44403c; }
        .meta p { margin: 3px 0; }
        .sign { margin-top: 36px; }
        .sign p { margin: 4px 0; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet { box-shadow: none; margin: 0; width: auto; min-height: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">พิมพ์ / บันทึกเป็น PDF</button>
        @if ($docxUrl)
            <a href="{{ $docxUrl }}">ดาวน์โหลด Word</a>
        @endif
        <a href="{{ $backUrl }}">กลับหน้ารายงาน</a>
        @if ($officialFormUrl)
            <a href="{{ $officialFormUrl }}" target="_blank" rel="noopener">เปิดแบบฟอร์มต้นฉบับ</a>
        @endif
    </div>

    <article class="sheet">
        <h1>บันทึกข้อความ</h1>

        <p class="line">ส่วนราชการ&nbsp;&nbsp;คณะวิทยาศาสตร์</p>
        <p class="line">ที่ ศบ &nbsp;&nbsp;................ / ........ &nbsp;&nbsp;&nbsp;วันที่ ......................</p>
        <p class="line">เรื่อง&nbsp;&nbsp;<span class="filled">{{ $fields['subject_line'] }}</span></p>
        <p class="line">เรียน&nbsp;&nbsp;คณบดีคณะวิทยาศาสตร์ (ผ่านหัวหน้าสาขาวิชา)</p>

        <p class="indent">
            ด้วยข้าพเจ้า
            @if ($fields['student_name'] !== '')
                <span class="filled">{{ $fields['student_name'] }}</span>
            @else
                (นาย/นาง/นางสาว)......................................
            @endif
            รหัสประจำตัวนักศึกษา
            @if ($fields['student_code'] !== '')
                <span class="filled">{{ $fields['student_code'] }}</span>
            @else
                ....................
            @endif
            นักศึกษาหลักสูตร <span class="filled">{{ $fields['course_kind_th'] ?: 'วิทยานิพนธ์/ดุษฎีนิพนธ์/การศึกษาอิสระ' }}</span>
            @if ($fields['degree'] !== '')
                ระดับ <span class="filled">{{ $fields['degree'] }}</span>
            @endif
            @if ($fields['proposal_status'] !== '')
                <span class="filled">{{ $fields['proposal_status'] }}</span>
            @else
                ได้รับอนุมัติเค้าโครงแล้ว / ยังไม่ได้รับอนุมัติเค้าโครง
            @endif
            และได้ลงทะเบียนรายวิชา
            <span class="filled">{{ $fields['subject_code'] }} {{ $fields['subject'] }}</span>
            กลุ่มที่ <span class="filled">{{ $fields['section'] }}</span>
            {{ $fields['term_label'] }} ปีการศึกษา <span class="filled">{{ $fields['year'] }}</span>
            ได้เกรด <span class="filled">{{ $fields['grade'] }}</span>
            หน่วยกิตที่ผ่าน <span class="filled">{{ $fields['credits_passed'] }}</span>
            @if ($fields['note'] !== '')
                หมายเหตุ <span class="filled">{{ $fields['note'] }}</span>
            @endif
        </p>

        <p class="indent">จึงเรียนมาเพื่อโปรดพิจารณา</p>

        <div class="sign">
            <p>({{ $fields['teacher'] !== '' ? $fields['teacher'] : '..........................................................' }})</p>
            <p>อาจารย์ที่ปรึกษาวิทยานิพนธ์</p>
            <p style="margin-top:28px">(..........................................................)</p>
            <p>หัวหน้าสาขาวิชา</p>
        </div>

        <div class="meta">
            <p><strong>ข้อมูลรายวิชาที่กำลังรายงานใน SciGrade</strong></p>
            <p>รหัส/ชื่อวิชา: {{ $fields['subject_code'] }} {{ $fields['subject'] }}</p>
            <p>กลุ่มที่ {{ $fields['section'] }} · {{ $fields['term_label'] }} ปีการศึกษา {{ $fields['year'] }}</p>
            <p>อาจารย์ผู้ส่ง: {{ $fields['teacher'] !== '' ? $fields['teacher'] : '—' }}</p>
            @if ($fields['student_code'] !== '' || $fields['student_name'] !== '')
                <p>นักศึกษา: {{ trim($fields['student_code'].' '.$fields['student_name']) }}</p>
            @endif
            @if ($fields['degree'] !== '')
                <p>ระดับ: {{ $fields['degree'] }}@if ($fields['thesis_terms'] !== '') · ภาคสะสม {{ $fields['thesis_terms'] }}@endif</p>
            @endif
        </div>
    </article>
</body>
</html>
