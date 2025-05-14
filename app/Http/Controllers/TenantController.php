<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->only(['email', 'password', 'company']);

        return response()->json([
            'message' => 'Données reçues avec succès',
            'data' => $data,
        ]);
    }
}
