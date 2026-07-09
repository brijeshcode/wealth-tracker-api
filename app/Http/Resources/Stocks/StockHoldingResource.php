<?php

namespace App\Http\Resources\Stocks;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockHoldingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quantity    = (float) $this->quantity;
        $avgBuy      = (float) $this->avg_buy_price;
        $costBasis   = $quantity * $avgBuy;
        $latestPrice = $this->stock?->latestPrice;

        $currentPrice = $latestPrice ? (float) $latestPrice->close_price : null;
        $priceDate    = $latestPrice?->price_date?->toDateString();
        $currentValue = $currentPrice !== null ? round($quantity * $currentPrice, 2) : null;
        $pnl          = $currentValue !== null ? round($currentValue - $costBasis, 2) : null;
        $pnlPct       = ($costBasis > 0 && $pnl !== null)
            ? round(($pnl / $costBasis) * 100, 2)
            : null;

        return [
            'id'            => $this->id,
            'stock_id'      => $this->stock_id,
            'exchange'      => $this->exchange,
            'quantity'      => $quantity,
            'avg_buy_price' => round($avgBuy, 4),
            'cost_basis'    => round($costBasis, 2),

            'valuation' => [
                'current_price'      => $currentPrice,
                'price_date'         => $priceDate,
                'current_value'      => $currentValue,
                'unrealized_pnl'     => $pnl,
                'unrealized_pnl_pct' => $pnlPct,
            ],

            'holding' => [
                'id'               => $this->holding->id,
                'nickname'         => $this->holding->nickname,
                'notes'            => $this->holding->notes,
                'status'           => $this->holding->status,
                'principal_amount' => (float) $this->holding->principal_amount,
                'platform'         => [
                    'id'           => $this->holding->platform->id,
                    'name'         => $this->holding->platform->name,
                    'display_name' => $this->holding->platform->display_name,
                ],
            ],

            'stock' => [
                'id'           => $this->stock->id,
                'company_name' => $this->stock->company_name,
                'nse_symbol'   => $this->stock->nse_symbol,
                'isin'         => $this->stock->isin,
            ],
        ];
    }
}
