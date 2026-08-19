<?php

namespace App\Services;

use Carbon\Carbon;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;
use ZipArchive;

/**
 * Parses a J&T export (csv/xlsx, or a zip containing them) into deduped, normalized
 * rows keyed by waybill_number. Throws "Wrong File Uploaded" if the required J&T
 * columns are missing.
 */
class RtsFileParser
{
    /** Last date format that parsed successfully — tried first on the next row (big speedup). */
    private ?string $dateFormatHint = null;

    /**
     * EXACT J&T export header (normalized: lowercased, whitespace-collapsed) → from_jnts column.
     * Exact matching avoids the mis-map traps of fuzzy matching (e.g. "Sender Address" /
     * "Sender City" grabbing `sender`, or "Receipt Waybill No" grabbing `waybill_number`).
     */
    private const HEADER_MAP = [
        'waybill number'      => 'waybill_number',
        'order status'        => 'status',
        'item name'           => 'item_name',
        'sender name'         => 'sender',
        'receiver'            => 'receiver',
        'receiver cellphone'  => 'receiver_cellphone',
        'cod'                 => 'cod',
        'submission time'     => 'submission_time',
        'signingtime'         => 'signingtime',
        'total shipping cost' => 'total_shipping_cost',
        'province'            => 'province',
        'city'                => 'city',
        'barangay'            => 'barangay',
        'rts reason'          => 'rts_reason',
        'remarks'             => 'remarks',
    ];

    /** Columns that MUST be present for RTS + remittance to compute correctly. */
    private const REQUIRED = [
        'waybill_number', 'status', 'item_name', 'sender',
        'cod', 'submission_time', 'signingtime', 'total_shipping_cost',
    ];

    /** Friendly J&T header names, for the "missing columns" error message. */
    private const REQUIRED_LABELS = [
        'waybill_number'      => 'Waybill Number',
        'status'              => 'Order Status',
        'item_name'           => 'Item Name',
        'sender'              => 'Sender Name',
        'cod'                 => 'Cod',
        'submission_time'     => 'Submission Time',
        'signingtime'         => 'SigningTime',
        'total_shipping_cost' => 'Total Shipping Cost',
    ];

    /** Parse a stored upload. Returns ['rows' => [waybill => row], 'total' => int]. */
    /**
     * @param  callable|null  $cancelCheck  Optional progress/cancel hook: called every ~1000
     *                                       rows as fn(int $rowsRead): bool. Return true to
     *                                       abort (throws App\Exceptions\RtsUploadCanceled).
     */
    public function parse(string $absPath, string $ext, ?callable $cancelCheck = null): array
    {
        $ext = strtolower($ext);

        if ($ext === 'zip') {
            return $this->parseZip($absPath, $cancelCheck);
        }

        if (! in_array($ext, ['csv', 'xlsx'], true)) {
            throw new RuntimeException('Unsupported file type: '.$ext.'. Please upload a CSV, XLSX, or ZIP.');
        }

        $rows  = [];
        $total = 0;
        $this->readFile($absPath, $ext, $rows, $total, $cancelCheck);

        return ['rows' => $rows, 'total' => $total];
    }

    private function parseZip(string $zipPath, ?callable $cancelCheck = null): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Cannot open the ZIP file.');
        }

        $tmpDir = storage_path('app/tmp/rts_zip_'.uniqid());
        @mkdir($tmpDir, 0777, true);

        $rows  = [];
        $total = 0;
        $found = false;

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->statIndex($i)['name'] ?? '';
                $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (! in_array($ext, ['csv', 'xlsx'], true)) {
                    continue;
                }

                $target = $tmpDir.DIRECTORY_SEPARATOR.basename($name);
                $in  = $zip->getStream($name);
                if (! $in) {
                    continue;
                }
                $out = fopen($target, 'wb');
                stream_copy_to_stream($in, $out);
                fclose($in);
                fclose($out);

                $found = true;
                $this->readFile($target, $ext, $rows, $total, $cancelCheck);
                @unlink($target);
            }
        } finally {
            $zip->close();
            @rmdir($tmpDir);
        }

        if (! $found) {
            throw new RuntimeException('The ZIP has no CSV or XLSX files inside.');
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /** Stream one CSV/XLSX file, appending normalized rows into $rows (deduped by waybill). */
    private function readFile(string $absPath, string $ext, array &$rows, int &$total, ?callable $cancelCheck = null): void
    {
        if ($ext === 'xlsx') {
            $reader = new XlsxReader();
        } else {
            $options = new CsvOptions();
            $options->FIELD_DELIMITER = ',';
            $options->FIELD_ENCLOSURE = '"';
            $reader = new CsvReader($options);
        }

        $reader->open($absPath);

        $headerMap = null;
        $seen = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                // Every ~1000 rows: report progress + allow cancellation. The callback
                // receives the running row count and returns true to abort the parse.
                if ($cancelCheck !== null && (++$seen % 1000 === 0) && $cancelCheck($seen)) {
                    $reader->close();
                    throw new \App\Exceptions\RtsUploadCanceled('Upload canceled by user.');
                }

                $cells = $row->toArray();

                if ($headerMap === null) {
                    $headerMap = $this->buildHeaderMap($cells);
                    $missing = $this->missingRequired($headerMap);
                    if (! empty($missing)) {
                        $reader->close();
                        $labels = array_map(fn ($c) => self::REQUIRED_LABELS[$c] ?? $c, $missing);
                        throw new RuntimeException('Wrong file — these required J&T columns are missing: '.implode(', ', $labels).'. Please upload the complete J&T export.');
                    }
                    continue;
                }

                $norm = $this->normalizeRow($cells, $headerMap);
                if ($norm['waybill_number'] === '' || $norm['status'] === '') {
                    continue;
                }

                // Dedupe within the file — last occurrence of a waybill wins.
                $rows[$norm['waybill_number']] = $norm;
                $total++;
            }
        }

        $reader->close();
    }

    /** Map each column index to a from_jnts field by EXACT header name (first match wins). */
    private function buildHeaderMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $idx => $label) {
            $h = $this->normHeader((string) $label);
            if ($h === '' || ! isset(self::HEADER_MAP[$h])) {
                continue;
            }
            $canon = self::HEADER_MAP[$h];
            if (! isset($map[$canon])) {
                $map[$canon] = $idx;
            }
        }

        return $map;
    }

    /** Normalize a header for exact comparison: lowercase + collapse whitespace + trim. */
    private function normHeader(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($s)));
    }

    /** Required columns not found in the header map (canonical names). */
    private function missingRequired(array $map): array
    {
        return array_values(array_diff(self::REQUIRED, array_keys($map)));
    }

    private function normalizeRow(array $cells, array $map): array
    {
        $get = function ($key) use ($cells, $map) {
            if (! isset($map[$key])) {
                return '';
            }
            $val = $cells[$map[$key]] ?? '';
            if ($val instanceof \DateTimeInterface) {
                return $val->format('Y-m-d H:i:s');
            }
            $val = is_scalar($val) ? (string) $val : '';

            return trim(preg_replace('/\s+/u', ' ', $val));
        };

        return [
            'waybill_number'      => $get('waybill_number'),
            'status'              => $get('status'),
            'item_name'           => $get('item_name'),
            'sender'              => $get('sender'),
            'receiver'            => $get('receiver'),
            'receiver_cellphone'  => $get('receiver_cellphone'),
            'cod'                 => $get('cod'),
            'submission_time'     => $this->parseDate($get('submission_time')),
            'signingtime'         => $this->parseDate($get('signingtime')),
            'remarks'             => $get('remarks'),
            'province'            => $get('province'),
            'city'                => $get('city'),
            'barangay'            => $get('barangay'),
            'total_shipping_cost' => $this->parseMoney($get('total_shipping_cost')),
            'rts_reason'          => $get('rts_reason'),
        ];
    }

    private function parseDate(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }

        // Excel serial date (numeric) → real date.
        if (is_numeric($v)) {
            try {
                return Carbon::create(1899, 12, 30, 0, 0, 0, 'Asia/Manila')
                    ->addDays((int) $v)->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // fall through
            }
        }

        $formats = [
            'Y-m-d H:i:s', 'Y-m-d H:i', 'm/d/Y H:i', 'd/m/Y H:i', 'm/d/Y', 'd/m/Y',
            'Y-m-d', 'd-m-Y H:i', 'd-m-Y H:i:s', 'd-m-Y', 'H:i d-m-Y', 'H:i d/m/Y',
        ];

        // Fast path: rows in one file share a date format, so the format that matched
        // the previous row almost always matches again — try it first to avoid throwing
        // (and catching) an exception for every non-matching format on every row.
        if ($this->dateFormatHint !== null) {
            try {
                return Carbon::createFromFormat($this->dateFormatHint, $v, 'Asia/Manila')->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // format changed — fall back to the full scan
            }
        }

        foreach ($formats as $fmt) {
            try {
                $out = Carbon::createFromFormat($fmt, $v, 'Asia/Manila')->format('Y-m-d H:i:s');
                $this->dateFormatHint = $fmt;

                return $out;
            } catch (\Throwable $e) {
                // try next
            }
        }

        try {
            return Carbon::parse($v, 'Asia/Manila')->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseMoney(string $v): ?string
    {
        $clean = trim(preg_replace('/[^\d\.\-]/', '', $v));

        return $clean === '' ? null : $clean;
    }
}
