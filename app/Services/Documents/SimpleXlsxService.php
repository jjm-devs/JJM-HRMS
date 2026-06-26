<?php

namespace App\Services\Documents;

use RuntimeException;
use ZipArchive;

class SimpleXlsxService
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array{
     *     headerRows?: array<int>,
     *     titleRows?: array<int>,
     *     freezeRow?: int,
     *     autoFilterRow?: int,
     *     columnWidths?: array<int, float|int>
     * }  $options
     */
    public function make(string $sheetName, array $rows, array $options = []): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx_');

        if ($path === false) {
            throw new RuntimeException('Unable to create temporary XLSX file.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            @unlink($path);

            throw new RuntimeException('Unable to open XLSX archive for writing.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
        $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
        $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($rows, $options));
        $zip->close();

        $content = file_get_contents($path);
        @unlink($path);

        if ($content === false) {
            throw new RuntimeException('Unable to read generated XLSX file.');
        }

        return $content;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, mixed>  $options
     */
    private function worksheetXml(array $rows, array $options): string
    {
        $headerRows = $options['headerRows'] ?? [1];
        $titleRows = $options['titleRows'] ?? [];
        $freezeRow = $options['freezeRow'] ?? null;
        $autoFilterRow = $options['autoFilterRow'] ?? null;
        $columnWidths = $options['columnWidths'] ?? [];
        $maxColumns = $this->maxColumns($rows);
        $lastColumn = $this->columnName(max($maxColumns, 1));
        $lastRow = max(count($rows), 1);

        $cols = $this->columnsXml($maxColumns, $columnWidths);
        $sheetViews = $freezeRow
            ? '<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.($freezeRow - 1).'" topLeftCell="A'.$freezeRow.'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            : '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';

        $sheetData = '';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $cells = '';

            for ($columnIndex = 1; $columnIndex <= $maxColumns; $columnIndex++) {
                $value = $row[$columnIndex - 1] ?? null;
                $style = 0;

                if (in_array($excelRow, $headerRows, true)) {
                    $style = 1;
                } elseif (in_array($excelRow, $titleRows, true)) {
                    $style = 3;
                } elseif (is_int($value) || is_float($value)) {
                    $style = 2;
                }

                $cells .= $this->cellXml($columnIndex, $excelRow, $value, $style);
            }

            $sheetData .= '<row r="'.$excelRow.'">'.$cells.'</row>';
        }

        $autoFilter = $autoFilterRow && $autoFilterRow <= $lastRow
            ? '<autoFilter ref="A'.$autoFilterRow.':'.$lastColumn.$lastRow.'"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .$sheetViews
            .$cols
            .'<sheetData>'.$sheetData.'</sheetData>'
            .$autoFilter
            .'<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            .'</worksheet>';
    }

    private function cellXml(int $columnIndex, int $rowIndex, mixed $value, int $style): string
    {
        $ref = $this->columnName($columnIndex).$rowIndex;
        $styleAttribute = $style > 0 ? ' s="'.$style.'"' : '';

        if ($value === null || $value === '') {
            return '<c r="'.$ref.'"'.$styleAttribute.'/>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="'.$ref.'"'.$styleAttribute.'><v>'.$value.'</v></c>';
        }

        return '<c r="'.$ref.'" t="inlineStr"'.$styleAttribute.'><is><t xml:space="preserve">'
            .$this->escape((string) $value)
            .'</t></is></c>';
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function maxColumns(array $rows): int
    {
        return collect($rows)
            ->map(fn (array $row): int => count($row))
            ->max() ?? 0;
    }

    /**
     * @param  array<int, float|int>  $columnWidths
     */
    private function columnsXml(int $maxColumns, array $columnWidths): string
    {
        if ($maxColumns <= 0) {
            return '';
        }

        $xml = '<cols>';

        for ($index = 1; $index <= $maxColumns; $index++) {
            $width = $columnWidths[$index] ?? 16;
            $xml .= '<col min="'.$index.'" max="'.$index.'" width="'.$width.'" customWidth="1"/>';
        }

        return $xml.'</cols>';
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function workbookXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$this->escape($this->sheetName($sheetName)).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
            .'<fonts count="3">'
            .'<font><sz val="11"/><color theme="1"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="14"/><color rgb="FF1F2937"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF1F4E79"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color rgb="FFD9E2EC"/></left><right style="thin"><color rgb="FFD9E2EC"/></right><top style="thin"><color rgb="FFD9E2EC"/></top><bottom style="thin"><color rgb="FFD9E2EC"/></bottom><diagonal/></border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="4">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private function corePropertiesXml(): string
    {
        $createdAt = now()->toAtomString();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:creator>JJM Brain HRMS</dc:creator>'
            .'<cp:lastModifiedBy>JJM Brain HRMS</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$createdAt.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function appPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>JJM Brain HRMS</Application>'
            .'</Properties>';
    }

    private function sheetName(string $sheetName): string
    {
        return mb_substr(preg_replace('/[\[\]\:*?\/\\\\]/', ' ', $sheetName) ?: 'Sheet1', 0, 31);
    }

    private function escape(string $value): string
    {
        $clean = preg_replace('/[^\P{C}\t\r\n]/u', '', $value) ?? $value;

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
