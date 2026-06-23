<?php

namespace App\Http\Controllers\Docs;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/stocks/search',
    tags: ['Stocks Master'],
    summary: 'Search NSE/BSE stock master by company name, symbol, or ISIN',
    parameters: [
        new OA\Parameter(name: 'q', in: 'query', required: true,
            schema: new OA\Schema(type: 'string', minLength: 1, maxLength: 100),
            description: 'Search term — company name, NSE/BSE symbol, or ISIN'
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Matching stocks (max 20)',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'isin', type: 'string', example: 'INE009A01021'),
                            new OA\Property(property: 'company_name', type: 'string', example: 'Infosys Ltd'),
                            new OA\Property(property: 'nse_symbol', type: 'string', example: 'INFY'),
                            new OA\Property(property: 'bse_symbol', type: 'string', example: 'INFY'),
                            new OA\Property(property: 'bse_code', type: 'string', example: '500209'),
                            new OA\Property(property: 'sector', type: 'string', example: 'IT'),
                        ]
                    )),
                ]
            )
        ),
        new OA\Response(response: 422, description: 'Validation error — q is required'),
    ]
)]
#[OA\Get(
    path: '/api/stocks/{id}',
    tags: ['Stocks Master'],
    summary: 'Get single stock detail with meta and latest price',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true,
            schema: new OA\Schema(type: 'integer'), description: 'Stock ID'
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Stock detail'),
        new OA\Response(response: 404, description: 'Stock not found'),
    ]
)]
#[OA\Get(
    path: '/api/stocks/{id}/events',
    tags: ['Stocks Master'],
    summary: 'List corporate events (splits, bonuses, mergers) for a stock',
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true,
            schema: new OA\Schema(type: 'integer'), description: 'Stock ID'
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Corporate events ordered by event_date desc'),
        new OA\Response(response: 404, description: 'Stock not found'),
    ]
)]
class StockMasterDoc {}
