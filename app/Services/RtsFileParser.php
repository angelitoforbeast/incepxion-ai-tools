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

    /** Parse a stored upload. Returns ['rows' => [waybill => row], 'total' => int]. */
    /**
     * @param  callable|null  $cancelCheck  Optional; called periodically during the row
     *                                       loop. If it returns true, parsing aborts by
     *                                       throwing App\Exceptions\RtsUploadCanceled.
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
                // Cooperative cancellation: check every ~2000 rows so a user can stop
                // a long parse without waiting for it to finish.
                if ($cancelCheck !== null && (++$seen % 2000 === 0) && $cancelCheck()) {
                    $reader->close();
                    throw new \App\Exceptions\RtsUploadCanceled('Upload canceled by user.');
                }

                $cells = $row->toArray();

                if ($headerMap === null) {
                    $headerMap = $this->buildHeaderMap($cells);
                    if (! $this->hasRequiredHeaders($headerMap)) {
                        $reader->close();
                        throw new RuntimeException('Wrong File Uploaded — the J&T columns (waybill number, status) were not found.');
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

    private function buildHeaderMap(array $headers): array
    {
        $norm = fn ($s) => trim(mb_strtolower((string) $s));

        $aliases = [
            'waybill_number'     => ['waybill', 'waybill number', 'awb', 'tracking no', 'tracking number'],
            'status'             => ['status', 'order status', 'order_status', 'orderstatus'],
            'item_name'          => ['item name', 'item', 'product', 'product name'],
            'sender'             => ['sender', 'shipper', 'from'],
            'receiver'           => ['receiver', 'consignee', 'to'],
            'receiver_cellphone' => ['receiver cellphone', 'receiver phone', 'consignee phone', 'phone', 'mobile'],
            'cod'                => ['cod', 'c.o.d', 'cod amt', 'cod amount', 'collect on delivery'],
            'submission_time'    => ['submission time', 'pu time', 'pickup time', 'created time'],
            'signingtime'        => ['signingtime', 'signing time', 'delivered time'],
            'remarks'            => ['remarks', 'remark', 'note', 'notes'],
            'province'           => ['province', 'prov'],
            'city'               => ['city', 'municipality', 'city/municipality'],
            'barangay'           => ['barangay', 'brgy', 'barangay name'],
            'total_shipping_cost'=> ['total shipping cost', 'shipping cost', 'total freight'],
            'rts_reason'         => ['rts reason', 'rts_reason', 'return reason', 'reason for rts'],
        ];

        $map = [];
        foreach ($headers as $idx => $label) {
            $h = $norm($label);
            $tokens = preg_split('/[^a-z0-9]+/u', $h, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($aliases as $canon => $cands) {
                if (isset($map[$canon])) {
                    continue;
                }
                foreach ($cands as $cand) {
                    $c = $norm($cand);
                    $matched = false;

                    if ($h === $c) {
                        $matched = true;
                    } elseif (mb_strpos($c, ' ') !== false) {
                        if (preg_match('/\b'.preg_quote($c, '/').'\b/u', $h)) {
                            $matched = true;
                        }
                    } elseif (in_array($c, $tokens, true)) {
                        $matched = true;
                    }

                    if ($matched) {
                        if ($canon === 'receiver' && $c === 'to' && $h !== 'to') {
                            $matched = false;
                        }
                        if ($canon === 'cod' && in_array('code', $tokens, true)) {
                            $matched = false;
                        }
                        if ($canon === 'receiver_cellphone' && in_array('sender', $tokens, true)) {
                            $matched = false;
                        }
                    }

                    if ($matched) {
                        $map[$canon] = $idx;
                        break;
                    }
                }
            }
        }

        return $map;
    }

    private function hasRequiredHeaders(array $map): bool
    {
        return isset($map['waybill_number'], $map['status']);
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
