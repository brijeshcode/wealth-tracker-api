<?php

namespace App\Http\Controllers\Stocks;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Stocks\StockHolding;
use App\Services\Stocks\TaxCalculator;
use Illuminate\Http\JsonResponse;

class StockTaxController extends Controller
{
    public function __construct(private TaxCalculator $calculator) {}

    public function show(StockHolding $stockHolding): JsonResponse
    {
        $data = $this->calculator->compute($stockHolding);

        return ApiResponse::show('Tax breakdown', $data);
    }
}
