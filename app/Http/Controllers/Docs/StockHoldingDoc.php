<?php

namespace App\Http\Controllers\Docs;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/stock-holdings',
    tags: ['Stock Holdings'],
    summary: 'List all stock holdings for the authenticated user',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'List of stock holdings with parent holding, platform, and latest price',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'stock_id', type: 'integer', example: 42),
                            new OA\Property(property: 'exchange', type: 'string', enum: ['NSE', 'BSE'], example: 'NSE'),
                            new OA\Property(property: 'quantity', type: 'number', format: 'float', example: 10.0),
                            new OA\Property(property: 'avg_buy_price', type: 'number', format: 'float', example: 1500.0),
                            new OA\Property(property: 'holding', type: 'object', properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'nickname', type: 'string', nullable: true),
                                new OA\Property(property: 'notes', type: 'string', nullable: true),
                                new OA\Property(property: 'status', type: 'string', example: 'active'),
                                new OA\Property(property: 'principal_amount', type: 'number', example: 15000.00),
                                new OA\Property(property: 'platform', type: 'object'),
                            ]),
                            new OA\Property(property: 'stock', type: 'object', properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'company_name', type: 'string'),
                                new OA\Property(property: 'nse_symbol', type: 'string'),
                                new OA\Property(property: 'latest_price', type: 'object', nullable: true),
                            ]),
                        ]
                    )),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/stock-holdings/{id}',
    tags: ['Stock Holdings'],
    summary: 'Get a single stock holding',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Stock holding detail'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Put(
    path: '/api/stock-holdings/{id}',
    tags: ['Stock Holdings'],
    summary: 'Update nickname or notes on a holding',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'nickname', type: 'string', nullable: true, maxLength: 255, example: 'My INFY position'),
                new OA\Property(property: 'notes', type: 'string', nullable: true, example: 'Long term hold'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Holding updated'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Delete(
    path: '/api/stock-holdings/{id}',
    tags: ['Stock Holdings'],
    summary: 'Soft-delete a stock holding and its parent holding record',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Holding deleted'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/stock-holdings/{id}/computed',
    tags: ['Stock Holdings'],
    summary: 'Computed metrics — quantity, avg buy price, cost basis, unrealized P&L',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Computed metrics. current_price and unrealized_pnl are null when no daily price row exists.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'quantity', type: 'number', example: 10.0),
                        new OA\Property(property: 'avg_buy_price', type: 'number', example: 1500.0),
                        new OA\Property(property: 'cost_basis', type: 'number', example: 15000.0),
                        new OA\Property(property: 'current_price', type: 'number', nullable: true, example: 1750.0),
                        new OA\Property(property: 'price_date', type: 'string', format: 'date', nullable: true, example: '2024-06-20'),
                        new OA\Property(property: 'current_value', type: 'number', nullable: true, example: 17500.0),
                        new OA\Property(property: 'unrealized_pnl', type: 'number', nullable: true, example: 2500.0),
                        new OA\Property(property: 'unrealized_pnl_pct', type: 'number', nullable: true, example: 16.67),
                    ]),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/stock-holdings/{id}/lots',
    tags: ['Stock Lots'],
    summary: 'List FIFO lots for a holding — ordered oldest buy first',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Lot list with lock-in status',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'buy_date', type: 'string', format: 'date', example: '2024-01-10'),
                            new OA\Property(property: 'buy_price', type: 'number', example: 1500.0),
                            new OA\Property(property: 'original_quantity', type: 'number', example: 10.0),
                            new OA\Property(property: 'quantity_remaining', type: 'number', example: 6.0),
                            new OA\Property(property: 'is_exhausted', type: 'boolean', example: false),
                            new OA\Property(property: 'is_locked', type: 'boolean', example: false),
                            new OA\Property(property: 'locked_until', type: 'string', format: 'date', nullable: true),
                        ]
                    )),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/stock-holdings/{id}/tax',
    tags: ['Stock Tax'],
    summary: 'STCG / LTCG tax breakdown computed from exhausted FIFO lots',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Tax breakdown. LTCG exemption of ₹1 lakh applied at holding level.',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'stcg_gain', type: 'number', example: 5000.0),
                        new OA\Property(property: 'stcg_tax', type: 'number', example: 750.0),
                        new OA\Property(property: 'ltcg_gain', type: 'number', example: 120000.0),
                        new OA\Property(property: 'ltcg_exemption', type: 'integer', example: 100000),
                        new OA\Property(property: 'ltcg_taxable_gain', type: 'number', example: 20000.0),
                        new OA\Property(property: 'ltcg_tax', type: 'number', example: 2000.0),
                        new OA\Property(property: 'total_tax', type: 'number', example: 2750.0),
                        new OA\Property(property: 'breakdown', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'lot_id', type: 'integer'),
                                new OA\Property(property: 'buy_date', type: 'string', format: 'date'),
                                new OA\Property(property: 'sell_date', type: 'string', format: 'date'),
                                new OA\Property(property: 'holding_months', type: 'integer'),
                                new OA\Property(property: 'quantity', type: 'number'),
                                new OA\Property(property: 'buy_price', type: 'number'),
                                new OA\Property(property: 'sell_price', type: 'number'),
                                new OA\Property(property: 'gain', type: 'number'),
                                new OA\Property(property: 'type', type: 'string', enum: ['STCG', 'LTCG']),
                            ]
                        )),
                    ]),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
class StockHoldingDoc {}
