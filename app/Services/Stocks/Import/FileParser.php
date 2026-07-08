<?php

namespace App\Services\Stocks\Import;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use League\Csv\Reader;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FileParser
{
    public function parse(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return match ($ext) {
            'csv'         => $this->parseCsv($file),
            'xlsx', 'xls' => $this->parseExcel($file),
            default       => throw new InvalidArgumentException("Unsupported file type: {$ext}"),
        };
    }

    private function parseCsv(UploadedFile $file): array
    {
        $csv = Reader::createFromPath($file->getRealPath(), 'r');
        $csv->setHeaderOffset(0);

        $rows = [];
        foreach ($csv->getRecords() as $record) {
            if (array_filter($record, fn ($v) => trim((string) $v) !== '') === []) {
                continue;
            }
            $rows[] = $record;
        }

        return $rows;
    }

    private function parseExcel(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet       = $spreadsheet->getActiveSheet();
        $data        = $sheet->toArray(null, true, true, false);

        if (empty($data)) {
            return [];
        }

        // Find the header row: first row with at least 4 non-empty string cells.
        // This skips broker logo/metadata rows that precede the actual trade table.
        $headerIndex = null;
        foreach ($data as $i => $row) {
            $nonEmpty = array_filter($row, fn ($v) => is_string($v) && trim($v) !== '');
            if (count($nonEmpty) >= 4) {
                $headerIndex = $i;
                break;
            }
        }

        if ($headerIndex === null) {
            return [];
        }

        $headers = $data[$headerIndex];
        $rows    = [];

        foreach (array_slice($data, $headerIndex + 1) as $row) {
            if (array_filter($row, fn ($v) => $v !== null && $v !== '') === []) {
                continue;
            }
            $rows[] = array_combine($headers, $row);
        }

        return $rows;
    }
}
