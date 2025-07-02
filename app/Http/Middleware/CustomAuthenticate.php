<?php

namespace App\Http\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class CustomAuthenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        return route('404');
    }

    protected function unauthenticated($request, array $guards)
    {
        $bearer = $request->bearerToken();

        try {
            $publicKey = file_get_contents(storage_path('oauth-public.key'));
            $decoded = JWT::decode($bearer, new Key($publicKey, 'RS256'));

            if (isset($decoded->aud)) {
                return;
            }
        } catch (\Exception $e) {
            // Nothing
        }

        throw new AuthenticationException(
            "Échec de l'authentification : vous devez être connecté pour accéder à cette ressource.",
            $guards
        );
    }
}
