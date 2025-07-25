<?php

namespace App\Http\Middleware;

use App\Models\UserTenant;
use Closure;
use Laravel\Passport\Token;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Client;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedById;

class AuthTenantRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $tenant_id = $request->header('X-Tenant');

        if (!$tenant_id) {
            return response()->json([
                'message' => 'The headers need X-Tenant !'
            ], 400);
        }

        $user = Auth::user();

        if ($user) {
            $relation_exist = UserTenant::where('user_id', $user->id)
                ->where('tenant_id', $tenant_id)
                ->exists();

            if (!$relation_exist) {
                return response()->json(['message' => 'Not authorized to Tenant'], 403);
            }
        } else {
            $bearer = $request->bearerToken();

            if (!$bearer) {
                return response()->json(['message' => 'No access token'], 401);
            }

            $publicKey = file_get_contents(storage_path('oauth-public.key'));
            $decoded = JWT::decode($bearer, new Key($publicKey, 'RS256'));

            $authorized = Client::where('name', $tenant_id)
                ->where('id', $decoded->aud)
                ->exists();

            if (!$authorized) {
                return response()->json(['message' => 'Client not authorized for this tenant'], 403);
            }
        }

        try {
            tenancy()->initialize($tenant_id);
        } catch (TenantCouldNotBeIdentifiedById $e) {
            return response()->json(['message' => 'Tenant not found'], 403);
        }

        return $next($request);
    }
}
