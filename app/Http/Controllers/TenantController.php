<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Models\RegistrationLink;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function register(StoreRegistrationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $slug = Str::slug($data['company']);

        if (Tenant::on('mysql')->find($slug)) {
            return response()->json([
                'message' => "Le tenant '{$data['company']}' existe déjà."
            ], 409);
        }

        if (User::on('mysql')->where('email', $data['email'])->exists()) {
            return response()->json([
                'message' => "L'adresse e-mail '{$data['email']}' est déjà utilisée."
            ], 409);
        }

        $tenant = Tenant::on('mysql')->create([
            'id' => $slug,
            'name' => $data['company'],
        ]);

        $user = User::on('mysql')->create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        UserTenant::on('mysql')->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);

        // Marquer le lien comme utilisé
        RegistrationLink::on('mysql')
            ->where('token', $data['token'])
            ->update(['used_at' => now()]);

        return response()->json([
            'message' => 'Tenant et utilisateur admin créés avec succès.',
            'tenant' => $tenant,
            'user' => $user,
        ], 201);
    }
}
