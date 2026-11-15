<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Penulis file Excel (.xlsx) sederhana tanpa library tambahan (FR-15).
 * Membuat satu sheet: judul, keterangan, baris header tebal, lalu data.
 * Membutuhkan ekstensi PHP zip; jika tidak ada, controller memakai CSV.
 */
class XlsxSederhana
{
    public static function tersedia(): bool
    {
        return class_exists(ZipArchive::class);
    }

    /**
     * @param  array<int, string>  $judul  baris-baris judul di atas tabel
     * @param  array<int, string>  $header
     * @param  array<int, array<int, string|int|float|null>>  $baris
     */
    public static function buat(string $namaSheet, array $judul, array $header, array $baris): string
    {
        $file = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;
        if ($zip->open($file, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file Excel.');
        }

        $sheetRows = [];
        $r = 1;
        foreach ($judul as $i => $teks) {
            $sheetRows[] = self::row($r++, [$teks], $i === 0 ? 2 : 0);
        }
        $r++;
        $sheetRows[] = self::row($r++, $header, 1);
        foreach ($baris as $isi) {
            $sheetRows[] = self::row($r++, $isi, 0);
        }

        $lebar = array_map(function ($i) use ($header, $baris) {
            $maks = mb_strlen((string) $header[$i]);
            foreach ($baris as $b) {
                $maks = max($maks, mb_strlen((string) ($b[$i] ?? '')));
            }

            return min(60, max(10, $maks + 2));
        }, array_keys($header));
        $cols = '';
        foreach ($lebar as $i => $w) {
            $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::esc(mb_substr($namaSheet, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFDCE6F7"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<cols>' . $cols . '</cols><sheetData>' . implode('', $sheetRows) . '</sheetData></worksheet>');
        $zip->close();

        $isi = file_get_contents($file);
        @unlink($file);

        return $isi;
    }

    private static function row(int $r, array $nilai, int $gaya): string
    {
        $sel = '';
        foreach (array_values($nilai) as $i => $v) {
            $ref = self::kolom($i) . $r;
            $s = $gaya ? ' s="' . $gaya . '"' : '';
            if (is_int($v) || is_float($v)) {
                $sel .= '<c r="' . $ref . '"' . $s . '><v>' . $v . '</v></c>';
            } elseif ($v !== null && $v !== '') {
                $sel .= '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">' . self::esc((string) $v) . '</t></is></c>';
            }
        }

        return '<row r="' . $r . '">' . $sel . '</row>';
    }

    private static function kolom(int $i): string
    {
        $nama = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $nama = chr(65 + (($i - 1) % 26)) . $nama;
        }

        return $nama;
    }

    private static function esc(string $s): string
    {
        // buang karakter kontrol yang tidak valid di XML
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);

        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
