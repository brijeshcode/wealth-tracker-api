<?php

namespace App\Http\Controllers\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Wealth Tracker API',
    version: '1.0.0',
    description: 'Self-hosted personal finance platform — stocks, FDs, mutual funds, bonds, gold, PPF/EPF, NPS.',
)]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: 'API Server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Enter the Sanctum token returned from /api/auth/login',
)]
#[OA\Tag(name: 'Auth', description: 'Registration, login and logout')]
#[OA\Tag(name: 'Mutual Funds', description: 'User MF investment records')]
#[OA\Tag(name: 'Mutual Funds Master', description: 'AMFI fund master — search schemes')]
#[OA\Tag(name: 'Mutual Funds NAV', description: 'NAV fetch and portfolio-wide refresh')]
#[OA\Tag(name: 'Mutual Funds Calculator', description: 'XIRR and return analytics')]
class ApiDocController {}
