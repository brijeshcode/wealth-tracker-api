<?php

namespace App\Http\Controllers\Stocks;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Stocks\Import\ImportPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockImportController extends Controller
{
    public function __construct(private ImportPipeline $pipeline) {}

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file'   => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:10240'],
            'broker' => ['required', 'string', 'in:standard,zerodha,groww,upstox'],
        ]);

        $result = $this->pipeline->preview(
            $request->file('file'),
            $request->input('broker'),
            $request->user()->id,
        );

        if (!empty($result['errors'])) {
            ApiResponse::failValidation($result['errors'], 'Import validation failed');
        }

        return ApiResponse::send('Import preview ready', 200, [
            'summary'  => $result['summary'],
            'warnings' => $result['warnings'],
            'rows'     => $result['rows'],
        ]);
    }

    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'rows'                    => ['required', 'array', 'min:1'],
            'rows.*.transaction_date' => ['required', 'string'],
            'rows.*.symbol'           => ['required', 'string'],
            'rows.*.exchange'         => ['required', 'string'],
            'rows.*.type'             => ['required', 'string'],
            'rows.*.platform'         => ['required', 'string'],
            'ignore_warnings'         => ['sometimes', 'boolean'],
        ]);

        $result = $this->pipeline->confirm(
            $request->input('rows'),
            (bool) $request->input('ignore_warnings', false),
            $request->user()->id,
        );

        if ($result['_type'] === 'validation_error') {
            ApiResponse::failValidation($result['errors'], 'Import validation failed');
        }

        if ($result['_type'] === 'duplicate_warning') {
            return ApiResponse::custom(
                'Duplicate transactions detected — set ignore_warnings to true to proceed',
                409,
                ['warnings' => $result['warnings']],
            );
        }

        return ApiResponse::send('Import complete', 200, [
            'imported'        => $result['imported'],
            'holdings_synced' => $result['holdings_synced'],
        ]);
    }
}
