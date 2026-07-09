<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Stocks\Stock;
use App\Services\Stocks\NseStockImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMasterController extends Controller
{
    public function __construct(private NseStockImportService $importService) {}

    public function importNse(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $result = $this->importService->importFromPath($request->file('file')->getRealPath());

        return ApiResponse::send('NSE stock master updated', 200, $result);
    }

    public function importNseEtf(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $result = $this->importService->importEtfFromPath($request->file('file')->getRealPath());

        return ApiResponse::send('NSE ETF master updated', 200, $result);
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status'      => ['sometimes', 'in:active,inactive'],
            'has_holders' => ['sometimes', 'boolean'],
            'page_size'   => ['sometimes', 'integer', 'min:1', 'max:200'],
        ]);

        $holderCountSql  = '(SELECT COUNT(DISTINCT user_id) FROM stock_holdings WHERE stock_id = stocks.id AND quantity > 0 AND deleted_at IS NULL)';
        $totalSharesSql  = '(SELECT COALESCE(SUM(quantity), 0) FROM stock_holdings WHERE stock_id = stocks.id AND quantity > 0 AND deleted_at IS NULL)';

        $query = Stock::withTrashed()->whereNull('deleted_at')
            ->select([
                'id', 'isin', 'company_name', 'nse_symbol', 'bse_symbol', 'bse_code', 'sector', 'industry', 'is_active',
                DB::raw("{$holderCountSql} as holder_count"),
                DB::raw("{$totalSharesSql} as total_shares"),
            ]);

        if ($request->filled('q')) {
            $q = $request->query('q');
            $query->where(function ($qb) use ($q) {
                $qb->where('company_name', 'like', "%{$q}%")
                    ->orWhere('nse_symbol', 'like', "%{$q}%")
                    ->orWhere('isin', 'like', "%{$q}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->query('status') === 'active');
        }

        if ($request->boolean('has_holders')) {
            $query->whereRaw("{$holderCountSql} > 0");
        }

        $pageSize = (int) $request->query('page_size', 50);
        $stocks   = $query->orderBy('nse_symbol')->paginate($pageSize);

        return ApiResponse::paginated('Stocks', $stocks);
    }

    public function update(Request $request, Stock $stock): JsonResponse
    {
        $data = $request->validate([
            'company_name' => ['sometimes', 'string', 'max:255'],
            'nse_symbol'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'bse_symbol'   => ['sometimes', 'nullable', 'string', 'max:50'],
            'bse_code'     => ['sometimes', 'nullable', 'string', 'max:20'],
            'sector'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'industry'     => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $stock->update($data);

        return ApiResponse::update('Stock updated', $stock->fresh());
    }

    public function toggleActive(Stock $stock): JsonResponse
    {
        $stock->update(['is_active' => !$stock->is_active]);

        $msg = $stock->is_active ? 'Stock enabled' : 'Stock disabled';

        return ApiResponse::update($msg, $stock->fresh());
    }

    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'isin'         => ['required', 'string', 'max:20'],
            'company_name' => ['required', 'string', 'max:255'],
            'nse_symbol'   => ['nullable', 'string', 'max:50'],
            'bse_symbol'   => ['nullable', 'string', 'max:50'],
            'bse_code'     => ['nullable', 'string', 'max:20'],
            'sector'       => ['nullable', 'string', 'max:100'],
            'industry'     => ['nullable', 'string', 'max:100'],
            'is_active'    => ['sometimes', 'boolean'],
        ]);

        $stock = Stock::withTrashed()->where('isin', $data['isin'])->first();

        if ($stock) {
            $stock->update(array_merge($data, ['deleted_at' => null]));
            return ApiResponse::update('Stock updated', $stock->fresh());
        }

        $stock = Stock::create(array_merge($data, ['is_active' => $data['is_active'] ?? true]));
        return ApiResponse::store('Stock created', $stock);
    }
}
