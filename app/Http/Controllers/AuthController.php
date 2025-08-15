<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserTenant;
use App\Services\Brevo;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Http\Controllers\AccessTokenController;

class AuthController extends Controller
{
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $clientId = config('services.passport.password_grant_client.id');
        $clientSecret = config('services.passport.password_grant_client.secret');

        if (!$clientId || !$clientSecret) {
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

            return response()->json([
                "data" => [
                    'token_type' => 'Bearer',
                    'access_token' => $content['access_token'],
                    'expires_in' => $content['expires_in'] ?? null,
                    'user' => $user,
                    'tenant' => $user->tenants->first()
                ]
            ]);
        } catch (OAuthServerException $e) {
            return response()->json([
                'message' => "Les informations d'identification sont incorrectes."
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
        $tenant = tenant();
        $tenants = Tenant::whereIn('id', UserTenant::on('mysql')->where('user_id', $user->id)->pluck('tenant_id'))->get();

        return response()->json([
            "data" => [
                "user" => $user,
                "tenant" => $tenant,
                "tenants" => $tenants ?? [],
                "modules" => $tenant->modules
            ]
        ]);
    }

    public function logout(Request $request): \Illuminate\Http\Response
    {
        $user = Auth::user();

        if ($user) {
            $request->user()->token()->revoke();
        }

        return response()->noContent();
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where("email", $request->email)->first();

        if (!$user) {
            return response(['message' => "L'adresse email n'existe pas !"], 403);
        }

        $token = Password::createToken($user);

        $resetLink = env('FRONTEND_URLS') . "/reset-password?token={$token}&email=" . urlencode($user->email);

        $brevo = new Brevo();

        $brevo->to($user->email, "Diji");
        $brevo->subject("Réinitialisation de mot de passe");
        $brevo->content("
            <h1>Diji</h1>
            <h2>Réinitialisation de mot de passe</h2>
            <p>Veuillez cliquer sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
            <a href='{$resetLink}'>Réinitialiser le mot de passe</a>");

        $brevo->send();

        return response()->noContent();
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->noContent();
        }

        return response()->json(['message' => 'Erreur lors de la réinitialisation.'], 400);
    }
}
