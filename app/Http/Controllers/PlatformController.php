<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\Platform;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Platforms', description: 'Investment platforms (brokers, banks, apps)')]
class PlatformController extends Controller
{
    #[OA\Get(
        path: '/api/platforms',
        tags: ['Platforms'],
        summary: 'List platforms that support a given asset type',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'type', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filter by asset type, e.g. stock, mf, fd'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of matching platforms'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        $type = request()->query('type');

        $platforms = Platform::when(
            $type,
            fn ($q) => $q->whereJsonContains('supported_asset_types', $type)
        )
            ->orderBy('display_name')
            ->get(['id', 'name', 'display_name', 'type']);

        return ApiResponse::index('Platforms retrieved', $platforms);
    }
}
