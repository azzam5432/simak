<?php
// ============================================
// includes/ExcelExport.php
// Helper export Excel (.xlsx) tanpa library eksternal.
// Menggunakan ZipArchive + XMLWriter (bawaan PHP).
// ============================================

class ExcelExport
{
    private $sheets = [];   // [ [ 'name' => ..., 'headers' => [...], 'rows' => [...] ], ... ]
    private $title = '';

    public function __construct($title = 'Laporan') {
        $this->title = $title;
    }

    /**
     * Tambah sheet baru.
     * $rows harus array-of-array, nilai numerik otomatis dianggap number cell.
     */
    public function addSheet($name, array $headers, array $rows) {
        // Nama sheet Excel max 31 char & tidak boleh mengandung : \ / ? * [ ]
        $name = substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $name), 0, 31);
        $this->sheets[] = [
            'name'    => $name ?: 'Sheet',
            'headers' => array_values($headers),
            'rows'    => $rows,
        ];
        return $this;
    }

    /**
     * Bangun binary .xlsx (zip) dan kembalikan sebagai string.
     */
    public function build() {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi zip PHP tidak tersedia');
        }

        $zip = new ZipArchive();
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $nSheets = max(count($this->sheets), 1);
        $sheetXmlIds = [];

        // ---------- [Content_Types].xml ----------
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
        for ($i = 1; $i <= $nSheets; $i++) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $ct .= '</Types>';
        $zip->addFromString('[Content_Types].xml', $ct);

        // ---------- _rels/.rels ----------
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>'
        );

        // ---------- docProps ----------
        $appTitle = htmlspecialchars($this->title, ENT_XML1, 'UTF-8');
        $zip->addFromString('docProps/core.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/">'
            . '<dc:title>' . $appTitle . '</dc:title>'
            . '<dc:creator>SIMAK</dc:creator>'
            . '</cp:coreProperties>'
        );
        $zip->addFromString('docProps/app.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Microsoft Excel</Application>'
            . '</Properties>'
        );

        // ---------- xl/workbook.xml ----------
        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        if (empty($this->sheets)) {
            $this->addSheet('Sheet1', ['Data'], []);
        }
        // WAJIB: elemen <sheet> harus dibungkus <sheets> sesuai skema OOXML.
        // Tanpa wrapper ini Excel menganggap file korup dan menolak membukanya.
        $wb .= '<sheets>';
        foreach ($this->sheets as $i => $sheet) {
            $id = 'rId' . ($i + 1);
            $sheetXmlIds[] = $id;
            $wb .= '<sheet name="' . htmlspecialchars($sheet['name'], ENT_XML1, 'UTF-8') . '" sheetId="' . ($i + 1) . '" r:id="' . $id . '"/>';
        }
        $wb .= '</sheets>';
        $wb .= '</workbook>';
        $zip->addFromString('xl/workbook.xml', $wb);

        // ---------- xl/_rels/workbook.xml.rels ----------
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($sheetXmlIds as $i => $id) {
            $rels .= '<Relationship Id="' . $id . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }
        // style s:0=normal, s:1=header bold putih, s:2=number 0.00
        $rels .= '<Relationship Id="rIdStyle" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $rels .= '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);

        // ---------- xl/styles.xml (header hijau bold + format angka) ----------
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        // ---------- xl/worksheets/sheetN.xml ----------
        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $this->sheetXml($sheet));
        }

        $zip->close();

        $binary = file_get_contents($tmp);
        unlink($tmp);
        return $binary;
    }

    /**
     * Kirim file .xlsx ke browser (download) lalu keluar.
     */
    public function download($filename) {
        try {
            $binary = $this->build();
        } catch (RuntimeException $e) {
            // Fallback darurat: CSV dengan BOM agar tetap bisa dibuka Excel
            $this->fallbackCsv($filename);
            return;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Length: ' . strlen($binary));

        echo $binary;
        exit();
    }

    // ============================================
    // INTERNAL
    // ============================================

    private function colName($index) {
        $name = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $name = chr(65 + $mod) . $name;
            $index = (int)(($index - $mod) / 26);
        }
        return $name;
    }

    private function esc($value) {
        return htmlspecialchars((string)$value, ENT_XML1, 'UTF-8');
    }

    private function stylesXml() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            // fonts: 0 normal, 1 bold putih (header)
            . '<fonts count="2">'
            .   '<font><sz val="11"/><name val="Calibri"/></font>'
            .   '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            // fills: 0 none, 1 gray125 (wajib), 2 hijau header
            . '<fills count="3">'
            .   '<fill><patternFill patternType="none"/></fill>'
            .   '<fill><patternFill patternType="gray125"/></fill>'
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FF27AE60"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            // cellXfs: 0 normal, 1 header, 2 angka 2 desimal
            . '<cellXfs count="3">'
            .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .   '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .   '<xf numFmtId="2" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function sheetXml($sheet) {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        // Auto-fit lebar kolom sederhana (estimasi dari panjang teks)
        $widths = [];
        $measure = function ($val) {
            $len = mb_strlen((string)$val);
            return min(max($len + 2, 8), 50);
        };
        foreach ($sheet['headers'] as $ci => $h) {
            $widths[$ci] = $measure($h);
        }
        foreach ($sheet['rows'] as $row) {
            $row = array_values($row);
            foreach ($row as $ci => $v) {
                $w = $measure(is_int($v) || is_float($v) ? number_format((float)$v, 2) : $v);
                if (!isset($widths[$ci]) || $w > $widths[$ci]) {
                    $widths[$ci] = $w;
                }
            }
        }

        // Urutan elemen worksheet WAJIB sesuai skema OOXML:
        // dimension -> sheetViews -> sheetFormatPr -> cols -> sheetData.
        $lastCol = $this->colName(max(count($sheet['headers']) - 1, 0));
        $lastRow = max(count($sheet['rows']) + 1, 1);
        $xml .= '<dimension ref="A1:' . $lastCol . $lastRow . '"/>';

        // Freeze baris header
        $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';

        $xml .= '<sheetFormatPr defaultRowHeight="15"/>';

        $xml .= '<cols>';
        foreach ($widths as $ci => $w) {
            $xml .= '<col min="' . ($ci + 1) . '" max="' . ($ci + 1) . '" width="' . $w . '" customWidth="1"/>';
        }
        $xml .= '</cols>';

        $xml .= '<sheetData>';

        // ---- Header row (style 1) ----
        $xml .= '<row r="1">';
        foreach ($sheet['headers'] as $ci => $h) {
            $ref = $this->colName($ci) . '1';
            $xml .= '<c r="' . $ref . '" t="inlineStr" s="1"><is><t>' . $this->esc($h) . '</t></is></c>';
        }
        $xml .= '</row>';

        // ---- Data rows ----
        $rn = 1; // nomor baris excel
        foreach ($sheet['rows'] as $row) {
            $rn++;
            $xml .= '<row r="' . $rn . '">';
            $row = array_values($row);
            foreach ($row as $ci => $v) {
                $ref = $this->colName($ci) . $rn;
                if ($v === null || $v === '') {
                    continue; // cell kosong boleh dilewati
                }
                if (is_int($v) || is_float($v)) {
                    // Hanya tipe numerik asli yang ditulis sebagai number cell.
                    // String seperti NIM "08123..." tetap string agar leading zero tidak hilang.
                    $num = $v;
                    // Angka desimal -> style 2 (2 desimal), integer -> style 0
                    $style = (is_float($v) && fmod($num, 1) != 0.0) ? ' s="2"' : '';
                    $xml .= '<c r="' . $ref . '"' . $style . '><v>' . $num . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . $this->esc($v) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    /**
     * Fallback darurat bila ekstensi zip tidak tersedia:
     * export CSV UTF-8 BOM (bisa dibuka Excel).
     */
    private function fallbackCsv($filename) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/\.xlsx$/i', '.csv', $filename) . '"');
        echo chr(0xEF) . chr(0xBB) . chr(0xBF);
        $out = fopen('php://output', 'w');
        foreach ($this->sheets as $sheet) {
            fputcsv($out, $sheet['headers'], ';');
            foreach ($sheet['rows'] as $row) {
                fputcsv($out, array_map(function ($v) {
                    return is_array($v) ? implode(', ', $v) : $v;
                }, $row), ';');
            }
        }
        fclose($out);
        exit();
    }
}
