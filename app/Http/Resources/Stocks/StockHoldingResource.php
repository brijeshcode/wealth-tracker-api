<?php

namespace App\Http\Resources\Stocks;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockHoldingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestPrice = $this->stock?->latestPrice;

        return [
            'id'            => $this->id,
            'stock_id'      => $this->stock_id,
            'exchange'      => $this->exchange,
            'quantity'      => (float) $this->quantity,
            'avg_buy_price' => (float) $this->avg_buy_price,

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
                'latest_price' => $latestPrice ? [
                    'price'          => (float) $latestPrice->price,
                    'price_date'     => $latestPrice->price_date,
                    'change_percent' => $latestPrice->change_percent !== null
                        ? (float) $latestPrice->change_percent
                        : null,
                ] : null,
            ],
        ];
    }
}
