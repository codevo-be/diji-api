<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;

class TenantController extends Controller
{
    public function register(StoreRegistrationRequest $request)
    {
        $data = $request->validated();

        return response()->json([
            'message' => 'Données validées',
            'data' => $data,
        ]);
    }
}
