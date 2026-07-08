<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::send('Authenticated as admin', 200, [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    }
}
