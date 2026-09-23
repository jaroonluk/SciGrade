<!doctype html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกข้อความ — {{ $fields['subject_line'] }}</title>
    @include('partials.favicon')
    <style>
        @font-face {
            font-family: "TH Sarabun Local";
            src: local("TH SarabunPSK"), local("TH Sarabun New"), local("THSarabunNew"), local("Sarabun");
        }
        body { font-family: "TH SarabunPSK", "TH Sarabun New", "TH Sarabun Local", "Sarabun", "Noto Sans Thai", sans-serif; font-size: 16pt; color: #111; margin: 0; background: #faf6f1; }
        .toolbar { max-width: 210mm; margin: 16px auto 0; display: flex; flex-wrap: wrap; gap: 8px; }
        .toolbar a, .toolbar button {
            display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .9rem; border-radius: .5rem;
            font-size: .9rem; font-weight: 600; text-decoration: none; border: 1px solid #e8cdb5; background: #fff; color: #5C2E1F; cursor: pointer;
        }
        .toolbar .primary { background: #a16207; border-color: #a16207; color: #fff; }
        .sheet {
            width: 210mm; min-height: 297mm; margin: 16px auto 32px; padding: 18mm 25.4mm 18mm;
            background: #fff; box-shadow: 0 8px 24px rgba(28, 25, 23, .08); box-sizing: border-box;
            display: flex; flex-direction: column;
        }
        .sheet-body { flex: 1 1 auto; }
        .title-row { display: flex; align-items: center; gap: 14px; margin-bottom: 10px; }
        .title-row img { width: 41px; height: 71px; object-fit: contain; }
        .title-row h1 { font-size: 22pt; font-weight: 400; margin: 0; letter-spacing: .02em; }
        .line { margin: 2px 0; }
        .label { font-weight: 700; }
        .indent { text-indent: 2.5em; text-align: justify; line-height: 1.55; margin: 16px 0 0; }
        .filled { font-weight: 600; }
        .sign-wrap { display: grid; grid-template-columns: 1fr 1fr; margin-top: 36px; }
        .sign { text-align: center; line-height: 1.5; }
        .sign .gap { height: 36px; }
        .page-footer {
            margin-top: 28px; padding-top: 8px;
            flex: 0 0 auto;
        }
        .page-footer img {
            display: block; width: 100%; max-width: 160mm; height: auto; margin: 0 auto;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet { box-shadow: none; margin: 0; width: auto; min-height: 277mm; padding: 0; }
            .page-footer { margin-top: auto; padding-top: 12px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="primary" onclick="window.print()">พิมพ์ / บันทึกเป็น PDF</button>
        @if ($docxUrl)
            <a href="{{ $docxUrl }}">ดาวน์โหลด Word (.docx)</a>
        @endif
        <a href="{{ $backUrl }}">กลับหน้ารายงาน</a>
        @if ($officialFormUrl)
            <a href="{{ $officialFormUrl }}" target="_blank" rel="noopener">เปิดแบบฟอร์มต้นฉบับ</a>
        @endif
    </div>

    <article class="sheet">
        <div class="sheet-body">
            <div class="title-row">
                @if (is_file($fields['emblem_path']))
                    <img src="{{ $fields['emblem_url'] }}" alt="ตราสัญลักษณ์คณะวิทยาศาสตร์">
                @endif
                <h1>บันทึกข้อความ</h1>
            </div>

            <p class="line"><span class="label">ส่วนงาน</span>&nbsp;&nbsp;&nbsp;&nbsp;คณะวิทยาศาสตร์&nbsp;&nbsp;&nbsp;สาขาวิชา{{ $fields['department'] !== '' ? $fields['department'] : '..........' }}&nbsp;&nbsp;&nbsp;&nbsp;หมายเลขโทรศัพท์&nbsp;&nbsp;.....................</p>
            <p class="line"><span class="label">ที่</span>&nbsp;&nbsp;{{ $fields['memo_no'] }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;วันที่&nbsp;&nbsp;{{ $fields['letter_date'] }}</p>
            <p class="line"><span class="label">เรื่อง</span>&nbsp;&nbsp;<span class="filled">{{ $fields['subject_line'] }}</span></p>
            <p class="line" style="margin-top:12px"><span class="label">เรียน</span>&nbsp;&nbsp;{{ $fields['to_line'] }}</p>

            <p class="indent">{{ $fields['body'] }}</p>
            <p class="indent">จึงเรียนมาเพื่อโปรดพิจารณา</p>

            <div class="sign-wrap">
                <div></div>
                <div class="sign">
                    <p>({{ $fields['teacher'] !== '' ? $fields['teacher'] : '..........................................................' }})</p>
                    <p>{{ $fields['advisor_title'] }}</p>
                    <div class="gap"></div>
                    <p>(..........................................................)</p>
                    <p>{{ $fields['chair_title'] }}</p>
                </div>
            </div>
        </div>

        @if (is_file($fields['footer_path']))
            <footer class="page-footer">
                <img src="{{ $fields['footer_url'] }}" alt="วิสัยทัศน์คณะวิทยาศาสตร์ มหาวิทยาลัยขอนแก่น">
            </footer>
        @endif
    </article>
</body>
</html>
