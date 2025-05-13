<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Upload;
use App\Models\User;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Http\Controllers\AccessTokenController;

class AuthController extends Controller
{
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $clientId = config('services.passport.password_grant_client.id');
        $clientSecret = config('services.passport.password_grant_client.secret');

        if(!$clientId || !$clientSecret){
            return response()->json([
                'message' => "Erreur de configuration serveur. Veuillez contacter l'administrateur."
            ], 500);
        }

        $data = [
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $request->email,
            'password' => $request->password,
            'scope' => ''
        ];

        try {
            $serverRequest = new ServerRequest('POST', '/oauth/token', [], null, '1.1', []);
            $serverRequest = $serverRequest->withParsedBody($data);

            $tokenResponse = app(AccessTokenController::class)->issueToken($serverRequest);
            $content = json_decode($tokenResponse->getContent(), true);

            if (isset($content['error'])) {
                return response()->json([
                    'message' => "Les informations d'identification sont incorrectes.",
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            $rawTenants = $user->tenants;
            $tenants = [];

            foreach ($rawTenants as $rawTenant) {
                tenancy()->initialize($rawTenant->id);
                $relativePath = Upload::where('filename', 'tenantLogo')->value('path');
                tenancy()->end();

                $logoPath = $relativePath ?? '';
                $tenant = array(
                    'id' => $rawTenant->id,
                    'name' => $rawTenant->name,
                    'logo' => $logoPath
                );
                $tenants[] = $tenant;
            }

            return response()->json([
                "data" => [
                    'token_type' => 'Bearer',
                    'access_token' => $content['access_token'],
                    'expires_in' => $content['expires_in'] ?? null,
                    'user' => $user,
                    'tenants' => $tenants
                ]
            ]);

        } catch (OAuthServerException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAuthenticatedUser(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $userId = $user->getAuthIdentifier();
        $tenants = User::on('mysql')->findOrFail($userId)->tenants;

        $tenant = tenant();

        return response()->json([
            "data" => [
                "user" => $user,
                "tenant" => $tenant,
                "tenants" => $tenants,
                "modules" => $tenant->modules
            ]
        ]);
    }

    public function logout(Request $request): \Illuminate\Http\Response
    {
        $user = Auth::user();

        if($user){
            $request->user()->token()->revoke();
        }

        return response()->noContent();
    }
}
