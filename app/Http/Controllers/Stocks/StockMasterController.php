<?php

namespace App\Http\Controllers\Stocks;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Stocks\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMasterController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:1', 'max:100']]);

        $q = $request->query('q');

        $stocks = Stock::where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('company_name', 'like', "%{$q}%")
                    ->orWhere('nse_symbol', 'like', "%{$q}%")
                    ->orWhere('bse_symbol', 'like', "%{$q}%")
                    ->orWhere('isin', 'like', "%{$q}%");
            })
            ->select('id', 'isin', 'company_name', 'nse_symbol', 'bse_symbol', 'bse_code', 'sector')
            ->limit(20)
            ->get();

        return ApiResponse::index('Stocks found', $stocks);
    }

    public function show(Stock $stock): JsonResponse
    {
        $stock->load(['meta', 'latestPrice']);

        return ApiResponse::show('Stock detail', $stock);
    }

    public function events(Stock $stock): JsonResponse
    {
        $events = $stock->events()->orderByDesc('event_date')->get();

        return ApiResponse::index('Stock events', $events);
    }
}
