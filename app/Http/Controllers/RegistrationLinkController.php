<?php

namespace App\Http\Controllers;

use App\Models\RegistrationLink;
use Illuminate\Http\JsonResponse;

class RegistrationLinkController extends Controller
{
    public function check(string $token): JsonResponse
    {
        $link = RegistrationLink::where('token', $token)->first();

        if (!$link) {
            return response()->json([
                'message' => 'Lien d’inscription introuvable.'
            ], 404);
        }

        if ($link->used_at !== null) {
            return response()->json([
                'message' => 'Ce lien a déjà été utilisé.'
            ], 410);
        }

        if ($link->expires_at->isPast()) {
            return response()->json([
                'message' => 'Ce lien a expiré.'
            ], 403);
        }

        return response()->json([
            'email' => $link->email,
        ], 200);
    }
}
