<?php

namespace App\Http\Controllers\Docs;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/stock-holdings/{id}/transactions',
    tags: ['Stock Transactions'],
    summary: 'List all transactions for a holding, newest first',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Transaction list',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'type', type: 'string', enum: ['buy', 'sell', 'dividend', 'bonus', 'split']),
                            new OA\Property(property: 'quantity', type: 'number', nullable: true, example: 10.0),
                            new OA\Property(property: 'price_per_unit', type: 'number', nullable: true, example: 1500.0),
                            new OA\Property(property: 'amount', type: 'number', example: 15000.0),
                            new OA\Property(property: 'transaction_date', type: 'string', format: 'date', example: '2024-01-10'),
                            new OA\Property(property: 'source', type: 'string', enum: ['manual', 'csv_import', 'api_sync']),
                            new OA\Property(property: 'reference', type: 'string', nullable: true),
                        ]
                    )),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Holding not found'),
    ]
)]
#[OA\Post(
    path: '/api/stock-transactions',
    tags: ['Stock Transactions'],
    summary: 'Add a transaction — auto-creates the holding on first transaction for a stock + platform + exchange combination',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['stock_id', 'platform_id', 'exchange', 'type', 'transaction_date'],
            properties: [
                new OA\Property(property: 'stock_id', type: 'integer', example: 42, description: 'ID from stocks master'),
                new OA\Property(property: 'platform_id', type: 'integer', example: 1, description: 'Broker/platform ID'),
                new OA\Property(property: 'exchange', type: 'string', enum: ['NSE', 'BSE'], example: 'NSE'),
                new OA\Property(property: 'type', type: 'string', enum: ['buy', 'sell', 'dividend', 'bonus', 'split'], example: 'buy'),
                new OA\Property(property: 'quantity', type: 'number', example: 10, description: 'Required for buy/sell'),
                new OA\Property(property: 'price_per_unit', type: 'number', example: 1500.0, description: 'Required for buy/sell; amount auto-computed when both provided'),
                new OA\Property(property: 'transaction_date', type: 'string', format: 'date', example: '2024-01-10'),
                new OA\Property(property: 'source', type: 'string', enum: ['manual', 'csv_import', 'api_sync'], example: 'manual'),
                new OA\Property(property: 'reference', type: 'string', nullable: true, example: 'ORD123456', description: 'Broker order ID'),
                new OA\Property(property: 'nickname', type: 'string', nullable: true, example: 'My INFY position', description: 'Only applied when a new holding is created'),
                new OA\Property(property: 'notes', type: 'string', nullable: true, description: 'Only applied when a new holding is created'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Transaction created; holding + lot auto-managed'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation error or locked lot'),
    ]
)]
#[OA\Put(
    path: '/api/stock-transactions/{id}',
    tags: ['Stock Transactions'],
    summary: 'Edit a transaction — re-runs FIFO and recalculates holding on quantity change',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'quantity', type: 'number', nullable: true, example: 10.0),
                new OA\Property(property: 'price_per_unit', type: 'number', nullable: true, example: 1600.0),
                new OA\Property(property: 'transaction_date', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'reference', type: 'string', nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Transaction updated; FIFO and holding resynced'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Transaction belongs to another user'),
        new OA\Response(response: 404, description: 'Transaction not found'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Delete(
    path: '/api/stock-transactions/{id}',
    tags: ['Stock Transactions'],
    summary: 'Delete a transaction — restores FIFO lots if sell, soft-deletes lot if buy',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Transaction deleted; lots and holding resynced'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Transaction belongs to another user'),
        new OA\Response(response: 404, description: 'Transaction not found'),
    ]
)]
class StockTransactionDoc {}
