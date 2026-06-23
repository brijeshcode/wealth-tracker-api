<?php

namespace App\Http\Controllers\Stocks;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Stocks\StockHolding;
use App\Services\Stocks\HoldingsCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockHoldingController extends Controller
{
    public function __construct(private HoldingsCalculator $calculator) {}

    public function index(): JsonResponse
    {
        $holdings = StockHolding::with(['holding.platform', 'stock.latestPrice'])
            ->get();

        return ApiResponse::index('Stock holdings retrieved', $holdings);
    }

    public function show(StockHolding $stockHolding): JsonResponse
    {
        $stockHolding->load(['holding.platform', 'stock.latestPrice']);

        return ApiResponse::show('Stock holding detail', $stockHolding);
    }

    public function update(Request $request, StockHolding $stockHolding): JsonResponse
    {
        $validated = $request->validate([
            'nickname' => ['nullable', 'string', 'max:255'],
            'notes'    => ['nullable', 'string'],
        ]);

        $stockHolding->holding->update($validated);

        return ApiResponse::update('Stock holding updated', $stockHolding->fresh(['holding.platform', 'stock']));
    }

    public function destroy(StockHolding $stockHolding): JsonResponse
    {
        DB::transaction(function () use ($stockHolding) {
            $stockHolding->holding->delete();
            $stockHolding->delete();
        });

        return ApiResponse::successMessage('Stock holding deleted');
    }

    public function computed(StockHolding $stockHolding): JsonResponse
    {
        $data = $this->calculator->compute($stockHolding);

        return ApiResponse::show('Computed holding metrics', $data);
    }
}
