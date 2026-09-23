<!doctype html>
<html lang="th"
    @if (($format ?? 'pdf') === 'word')
        xmlns:v="urn:schemas-microsoft-com:vml"
        xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:w="urn:schemas-microsoft-com:office:word"
        xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
        xmlns="http://www.w3.org/TR/REC-html40"
    @endif
>
<head>
    <meta charset="UTF-8">
    <title>รายงานผลการสอบไล่ — {{ $department->department_name }}</title>
    @if (($format ?? 'pdf') === 'word')
        <meta name="ProgId" content="Word.Document">
        <meta name="Generator" content="Microsoft Word 15">
        <meta name="Originator" content="Microsoft Word 15">
        <!--[if gte mso 9]>
        <xml>
            <w:WordDocument>
                <w:View>Print</w:View>
                <w:Zoom>100</w:Zoom>
                <w:DoNotOptimizeForBrowser/>
                <w:LidThemeComplexScript>TH</w:LidThemeComplexScript>
            </w:WordDocument>
        </xml>
        <![endif]-->
    @endif
    <style>
        @if (($format ?? 'pdf') === 'word')
        @page WordSection1 {
            size: 297mm 210mm;
            mso-page-orientation: landscape;
            margin: 10mm 12mm;
            mso-header-margin: 8mm;
            mso-footer-margin: 8mm;
        }
        div.WordSection1 { page: WordSection1; }
        body {
            font-family: "Sarabun", "TH Sarabun New", "Cordia New", sans-serif;
            font-size: 9pt;
            color: #111;
            margin: 0;
            padding: 0;
            mso-bidi-font-family: "Cordia New";
        }
        @else
        @page { size: A4 landscape; margin: 10mm 12mm; }
        body {
            font-family: sarabun, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 12px 16px 28px;
            color: #111;
        }
        @endif

        * { box-sizing: border-box; }
        h1 { text-align: center; font-size: {{ ($format ?? 'pdf') === 'word' ? '14pt' : '18px' }}; margin: 0 0 6px; }
        .subtitle, .meta { text-align: center; font-size: {{ ($format ?? 'pdf') === 'word' ? '10pt' : '13px' }}; margin: 4px 0; line-height: 1.35; }
        .report-block { margin-bottom: {{ ($format ?? 'pdf') === 'word' ? '14px' : '24px' }}; page-break-inside: avoid; }
        table.report {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            table-layout: fixed;
            mso-table-layout-alt: fixed;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        table.report th,
        table.report td {
            border: 1px solid #333;
            padding: {{ ($format ?? 'pdf') === 'word' ? '1px 2px' : '3px 4px' }};
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
            font-size: {{ ($format ?? 'pdf') === 'word' ? '8pt' : 'inherit' }};
            line-height: 1.2;
        }
        table.report th { background: #f0f0f0; font-weight: 600; }
        table.report .left { text-align: left; }
        table.report .strong { font-weight: 700; }
        table.report th.th-order {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
            word-break: keep-all;
            line-height: 1.25;
            padding: {{ ($format ?? 'pdf') === 'word' ? '2px 3px' : '4px 6px' }};
        }
        table.report th.th-subject-header {
            text-align: center;
            vertical-align: middle;
            line-height: 1.25;
        }
        .print-footer {
            margin-top: 16px;
            text-align: right;
            font-size: {{ ($format ?? 'pdf') === 'word' ? '8pt' : '10px' }};
            color: #444;
        }
        .dept-sign-wrap {
            width: 100%;
            text-align: right;
            margin-top: {{ ($format ?? 'pdf') === 'word' ? '20pt' : '36px' }};
            page-break-inside: avoid;
        }
        .dept-sign-block {
            display: inline-block;
            text-align: center;
            width: {{ ($format ?? 'pdf') === 'word' ? '210pt' : '260px' }};
            font-size: {{ ($format ?? 'pdf') === 'word' ? '10pt' : '13px' }};
            line-height: 1.6;
        }
        .dept-sign-block .sign-space {
            min-height: {{ ($format ?? 'pdf') === 'word' ? '48pt' : '64px' }};
            margin-bottom: 6px;
        }
        .dept-sign-block .sign-line {
            margin: 0 0 4px;
        }
    </style>
</head>
<body>
@if (($format ?? 'pdf') === 'word')
<div class="WordSection1">
@endif

    <h1>แบบรายงานผลการสอบไล่</h1>
    <p class="meta"><strong>{{ $department->department_name }}</strong></p>

    @if ($reports->isNotEmpty())
        @php $term = (int) $exportTerm; @endphp
        <p class="subtitle">
            ประจำภาค ( {{ $term === 1 ? '/' : ' ' }} ) ต้น
            ( {{ $term === 2 ? '/' : ' ' }} ) ปลาย
            ( {{ $term === 3 ? '/' : ' ' }} ) ภาคการศึกษาพิเศษ
            ปีการศึกษา {{ $exportYear }}
        </p>
    @endif

    @forelse ($courseGroups as $index => $course)
        @include('dept-admin.reports.partials.course-table', [
            'course' => $course,
            'number' => $index + 1,
            'presenter' => $presenter,
            'format' => $format ?? 'pdf',
        ])
    @empty
        <p class="meta">ไม่มีข้อมูล</p>
    @endforelse

    @if ($courseGroups->isNotEmpty())
        <div class="dept-sign-wrap">
            <div class="dept-sign-block">
                <div class="sign-space" aria-hidden="true"></div>
                <p class="sign-line">ลงชื่อ....................................................</p>
                <p class="sign-line">(....................................................)</p>
                <p class="sign-line">หัวหน้าสาขาวิชา</p>
            </div>
        </div>
    @endif

    <div class="print-footer">พิมพ์เมื่อ {{ $printedAt }}</div>

@if (($format ?? 'pdf') === 'word')
</div>
@endif
</body>
</html>
