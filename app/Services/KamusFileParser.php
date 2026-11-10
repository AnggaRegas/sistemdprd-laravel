<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

/**
 * Pembaca berkas Kamus Acuan.
 *
 * Mendukung CSV/TXT, XLSX, dan PDF berbasis teks. Berkas XLS biner
 * (Excel 97-2003) hanya dapat dibaca bila pustaka PhpSpreadsheet tersedia;
 * bila tidak, pengguna diberi pesan konversi yang jelas alih-alih berkas
 * diterima lalu gagal secara diam-diam.
 */
class KamusFileParser
{
    public const FORMAT_LABELS = [
        'csv' => 'CSV / TXT',
        'xlsx' => 'Excel (XLSX)',
        'xls' => 'Excel (XLS)',
        'pdf' => 'PDF',
    ];

    /**
     * @return array{format: string, label: string, sheet: ?string, rows: array<int, array<int, string>>, warnings: array<int, string>}
     */
    public function parse(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => 'Berkas tidak dapat dibaca dari penyimpanan sementara.',
            ]);
        }

        return match ($extension) {
            'csv', 'txt' => $this->parseCsv($path),
            'xlsx' => $this->parseXlsx($path),
            'xls' => $this->parseXls($path),
            'pdf' => $this->parsePdf($path),
            default => throw ValidationException::withMessages([
                'file' => 'Format berkas tidak didukung. Gunakan CSV, XLSX, XLS, atau PDF.',
            ]),
        };
    }

    /**
     * @return array{format: string, label: string, sheet: ?string, rows: array<int, array<int, string>>, warnings: array<int, string>}
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'Berkas CSV tidak dapat dibuka.']);
        }

        $rows = [];
        $maxRows = $this->maxRows();

        try {
            $first = fgets($handle);
            if ($first === false) {
                throw ValidationException::withMessages(['file' => 'Berkas CSV kosong.']);
            }

            $first = preg_replace('/^\xEF\xBB\xBF/', '', $first) ?? $first;
            $delimiter = $this->detectDelimiter($first);
            rewind($handle);
            $rowNumber = 0;

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;

                if ($rowNumber === 1) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? '')) ?? (string) ($row[0] ?? '');
                }

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $rows[] = array_map(fn ($value) => trim((string) $value), $row);

                if (count($rows) > $maxRows) {
                    throw ValidationException::withMessages([
                        'file' => 'Jumlah baris melebihi batas '.$maxRows.' baris per unggahan.',
                    ]);
                }
            }
        } finally {
            fclose($handle);
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'Berkas CSV tidak berisi baris data.']);
        }

        return [
            'format' => 'csv',
            'label' => self::FORMAT_LABELS['csv'],
            'sheet' => null,
            'rows' => $rows,
            'warnings' => [],
        ];
    }

    /**
     * @return array{format: string, label: string, sheet: ?string, rows: array<int, array<int, string>>, warnings: array<int, string>}
     */
    private function parseXlsx(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                'file' => 'Berkas XLSX rusak atau bukan arsip Excel yang valid.',
            ]);
        }

        try {
            $shared = $this->xlsxSharedStrings($zip);
            $sheetPath = $this->xlsxFirstSheetPath($zip);
            $xml = $zip->getFromName($sheetPath);

            if ($xml === false || trim($xml) === '') {
                throw ValidationException::withMessages([
                    'file' => 'Lembar kerja pertama tidak ditemukan di dalam berkas XLSX.',
                ]);
            }

            $rows = $this->xlsxRows($xml, $shared);
        } finally {
            $zip->close();
        }

        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'Berkas XLSX tidak berisi baris data.']);
        }

        return [
            'format' => 'xlsx',
            'label' => self::FORMAT_LABELS['xlsx'],
            'sheet' => basename($sheetPath),
            'rows' => $rows,
            'warnings' => [],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function xlsxSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false || trim($xml) === '') {
            return [];
        }

        $document = new \DOMDocument();
        $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        $strings = [];

        foreach ($document->getElementsByTagName('si') as $item) {
            $text = '';

            foreach ($item->getElementsByTagName('t') as $node) {
                $text .= $node->textContent;
            }

            $strings[] = $text;
        }

        return $strings;
    }

    private function xlsxFirstSheetPath(ZipArchive $zip): string
    {
        $workbook = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbook !== false && $rels !== false) {
            $workbookDoc = new \DOMDocument();
            $workbookDoc->loadXML($workbook, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

            $first = $workbookDoc->getElementsByTagName('sheet')->item(0);
            $relId = $first?->getAttribute('r:id');

            if ($relId) {
                $relsDoc = new \DOMDocument();
                $relsDoc->loadXML($rels, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

                foreach ($relsDoc->getElementsByTagName('Relationship') as $relationship) {
                    if ($relationship->getAttribute('Id') === $relId) {
                        $target = ltrim((string) $relationship->getAttribute('Target'), '/');
                        $target = str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;

                        if ($zip->locateName($target) !== false) {
                            return $target;
                        }
                    }
                }
            }
        }

        for ($index = 1; $index <= 20; $index++) {
            $candidate = 'xl/worksheets/sheet'.$index.'.xml';

            if ($zip->locateName($candidate) !== false) {
                return $candidate;
            }
        }

        throw ValidationException::withMessages([
            'file' => 'Lembar kerja XLSX tidak ditemukan.',
        ]);
    }

    /**
     * @param  array<int, string>  $shared
     * @return array<int, array<int, string>>
     */
    private function xlsxRows(string $xml, array $shared): array
    {
        $document = new \DOMDocument();
        $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        $rows = [];
        $maxRows = $this->maxRows();

        foreach ($document->getElementsByTagName('row') as $rowNode) {
            $cells = [];

            foreach ($rowNode->getElementsByTagName('c') as $cell) {
                $reference = (string) $cell->getAttribute('r');
                $column = $this->columnIndex($reference);
                $type = (string) $cell->getAttribute('t');
                $value = '';

                if ($type === 'inlineStr') {
                    foreach ($cell->getElementsByTagName('t') as $textNode) {
                        $value .= $textNode->textContent;
                    }
                } else {
                    $valueNode = $cell->getElementsByTagName('v')->item(0);
                    $raw = $valueNode?->textContent ?? '';

                    $value = $type === 's'
                        ? ($shared[(int) $raw] ?? '')
                        : $raw;
                }

                $cells[$column] = trim((string) $value);
            }

            if ($cells === []) {
                continue;
            }

            $width = max(array_keys($cells)) + 1;
            $normalized = [];

            for ($index = 0; $index < $width; $index++) {
                $normalized[] = $cells[$index] ?? '';
            }

            if ($this->isEmptyRow($normalized)) {
                continue;
            }

            $rows[] = $normalized;

            if (count($rows) > $maxRows) {
                throw ValidationException::withMessages([
                    'file' => 'Jumlah baris melebihi batas '.$maxRows.' baris per unggahan.',
                ]);
            }
        }

        return $rows;
    }

    private function columnIndex(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/i', '', $reference) ?? '';
        $index = 0;

        foreach (str_split(strtoupper($letters)) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * @return array{format: string, label: string, sheet: ?string, rows: array<int, array<int, string>>, warnings: array<int, string>}
     */
    private function parseXls(string $path): array
    {
        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            try {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);
                $sheet = $spreadsheet->getSheet(0);
                $rows = [];

                foreach ($sheet->toArray(null, true, false, false) as $row) {
                    $normalized = array_map(fn ($value) => trim((string) $value), $row);

                    if (! $this->isEmptyRow($normalized)) {
                        $rows[] = $normalized;
                    }
                }

                if ($rows !== []) {
                    return [
                        'format' => 'xls',
                        'label' => self::FORMAT_LABELS['xls'],
                        'sheet' => $sheet->getTitle(),
                        'rows' => $rows,
                        'warnings' => [],
                    ];
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        throw ValidationException::withMessages([
            'file' => 'Berkas .xls biner belum dapat dibaca pada server ini. Simpan ulang berkas sebagai .xlsx atau .csv, lalu unggah kembali. Tidak ada data Kamus Acuan yang diubah.',
        ]);
    }

    /**
     * @return array{format: string, label: string, sheet: ?string, rows: array<int, array<int, string>>, warnings: array<int, string>}
     */
    private function parsePdf(string $path): array
    {
        $contents = (string) file_get_contents($path);
        $text = $this->extractPdfText($contents);
        $warnings = [];

        if (trim($text) === '') {
            throw ValidationException::withMessages([
                'file' => 'Teks PDF tidak dapat diekstrak. PDF hasil pindai (gambar) harus diubah menjadi CSV/XLSX terlebih dahulu.',
            ]);
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $rows = [];
        $maxRows = $this->maxRows();

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $row = preg_split('/\t+|\s*\|\s*|\s*;\s*/', $line) ?: [];

            if (count($row) < 2) {
                $row = preg_split('/\s{2,}/', $line) ?: [];
            }

            $row = array_values(array_filter(array_map('trim', $row), fn ($value) => $value !== ''));

            if (count($row) < 2) {
                continue;
            }

            $rows[] = $row;

            if (count($rows) > $maxRows) {
                throw ValidationException::withMessages([
                    'file' => 'Jumlah baris melebihi batas '.$maxRows.' baris per unggahan.',
                ]);
            }
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'file' => 'PDF tidak memiliki tabel teks yang dapat dikenali. Gunakan CSV atau XLSX agar pemetaan kolom jelas.',
            ]);
        }

        $warnings[] = 'PDF hanya menyimpan teks tanpa struktur kolom. Periksa kembali hasil pemetaan pada pratinjau sebelum menerapkan.';

        return [
            'format' => 'pdf',
            'label' => self::FORMAT_LABELS['pdf'],
            'sheet' => null,
            'rows' => $rows,
            'warnings' => $warnings,
        ];
    }

    private function extractPdfText(string $contents): string
    {
        $texts = [];
        $offset = 0;

        while (($start = strpos($contents, 'stream', $offset)) !== false) {
            $start += 6;

            if (isset($contents[$start]) && $contents[$start] === "\r") {
                $start++;
            }

            if (isset($contents[$start]) && $contents[$start] === "\n") {
                $start++;
            }

            $end = strpos($contents, 'endstream', $start);

            if ($end === false) {
                break;
            }

            $stream = substr($contents, $start, $end - $start);
            $offset = $end + 9;
            $decoded = @gzuncompress($stream);

            if ($decoded === false) {
                $decoded = $stream;
            }

            if (! str_contains($decoded, 'Tj') && ! str_contains($decoded, 'TJ')) {
                continue;
            }

            preg_match_all('/\((?:\\\\.|[^\\\\()])*\)\s*Tj|\[(?:[^\[\]]|\\\\.)*\]\s*TJ/s', $decoded, $matches);

            foreach ($matches[0] as $chunk) {
                preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $chunk, $groups);

                foreach ($groups[0] as $group) {
                    $texts[] = $this->decodePdfString(substr($group, 1, -1));
                }

                if (str_ends_with(trim($chunk), '] TJ')) {
                    $texts[] = "\n";
                }
            }

            if ($texts !== []) {
                $texts[] = "\n";
            }
        }

        return implode('', $texts);
    }

    private function decodePdfString(string $value): string
    {
        $value = preg_replace_callback('/\\\\([nrtbf()\\\\])/', function (array $match) {
            return match ($match[1]) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'b', 'f' => '',
                default => $match[1],
            };
        }, $value) ?? $value;

        $value = preg_replace_callback('/\\\\([0-7]{1,3})/', function (array $match) {
            return chr(octdec($match[1]) & 0xFF);
        }, $value) ?? $value;

        return $value;
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];

        foreach ($candidates as $delimiter => $_) {
            $candidates[$delimiter] = substr_count($line, $delimiter);
        }

        arsort($candidates);
        $best = (string) array_key_first($candidates);

        return $candidates[$best] > 0 ? $best : ',';
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function maxRows(): int
    {
        return max(1, (int) config('kamus_acuan.upload_max_rows', 10000));
    }
}
