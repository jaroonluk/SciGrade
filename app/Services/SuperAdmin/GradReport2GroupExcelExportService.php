<?php

namespace App\Services\SuperAdmin;

use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class GradReport2GroupExcelExportService
{
    public function __construct(
        private readonly GradReport2GroupService $groups,
    ) {}

    public function download(?string $q = null): StreamedResponse
    {
        $rows = $this->groups->exportPasteRows($q);
        $binary = $this->buildXlsxBinary($rows);
        $filename = 'grad-report2-groups-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  list<array{group_code: string, subject: string, member_code: string}>  $rows
     */
    public function buildXlsxBinary(array $rows): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'gr2xlsx');
        if ($tmp === false) {
            throw new RuntimeException('Cannot create temp file for Excel export.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            throw new RuntimeException('Cannot open ZipArchive for Excel export.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml($rows));
        $zip->close();

        $binary = file_get_contents($tmp);
        @unlink($tmp);

        if ($binary === false || $binary === '') {
            throw new RuntimeException('Failed to build Excel binary.');
        }

        return $binary;
    }

    /**
     * @param  list<array{group_code: string, subject: string, member_code: string}>  $rows
     */
    private function sheetXml(array $rows): string
    {
        $headers = ['รหัสกลุ่ม', 'ชื่อวิชา (ENG)', 'รหัสวิชาในกลุ่ม'];
        $sheetData = [];
        $sheetData[] = $this->rowXml(1, $headers, 1);

        $rowIndex = 2;
        foreach ($rows as $row) {
            $sheetData[] = $this->rowXml($rowIndex, [
                $row['group_code'],
                $row['subject'],
                $row['member_code'],
            ], 2);
            $rowIndex++;
        }

        $sheetRows = implode('', $sheetData);

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
           xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheetViews>
    <sheetView workbookViewId="0">
      <pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>
    </sheetView>
  </sheetViews>
  <sheetFormatPr defaultRowHeight="18"/>
  <cols>
    <col min="1" max="1" width="16" customWidth="1"/>
    <col min="2" max="2" width="36" customWidth="1"/>
    <col min="3" max="3" width="20" customWidth="1"/>
  </cols>
  <sheetData>
{$sheetRows}
  </sheetData>
</worksheet>
XML;
    }

    /**
     * @param  list<string>  $values
     */
    private function rowXml(int $rowIndex, array $values, int $styleIndex): string
    {
        $cells = '';
        foreach ($values as $i => $value) {
            $col = chr(ord('A') + $i);
            $ref = $col.$rowIndex;
            $cells .= '<c r="'.$ref.'" t="inlineStr" s="'.$styleIndex.'"><is><t>'
                .$this->xml($value)
                .'</t></is></c>';
        }

        return '<row r="'.$rowIndex.'">'.$cells.'</row>';
    }

    private function stylesXml(): string
    {
        // style 0 = default, 1 = header, 2 = body — both use TH Sarabun New 14
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="3">
    <font>
      <sz val="14"/>
      <name val="TH Sarabun New"/>
    </font>
    <font>
      <b/>
      <sz val="14"/>
      <name val="TH Sarabun New"/>
    </font>
    <font>
      <sz val="14"/>
      <name val="TH Sarabun New"/>
    </font>
  </fonts>
  <fills count="3">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill>
      <patternFill patternType="solid">
        <fgColor rgb="FFFAF0E6"/>
        <bgColor indexed="64"/>
      </patternFill>
    </fill>
  </fills>
  <borders count="2">
    <border>
      <left/><right/><top/><bottom/><diagonal/>
    </border>
    <border>
      <left style="thin"><color rgb="FFE8C4B8"/></left>
      <right style="thin"><color rgb="FFE8C4B8"/></right>
      <top style="thin"><color rgb="FFE8C4B8"/></top>
      <bottom style="thin"><color rgb="FFE8C4B8"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="3">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center"/>
    </xf>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="left" vertical="center"/>
    </xf>
  </cellXfs>
  <cellStyles count="1">
    <cellStyle name="Normal" xfId="0" builtinId="0"/>
  </cellStyles>
</styleSheet>
XML;
    }

    private function workbookXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="จัดกลุ่มรายวิชา" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML;
    }

    private function workbookRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private function rootRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    private function contentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
