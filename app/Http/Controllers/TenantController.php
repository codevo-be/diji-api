<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTenantRequest;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function update(UpdateTenantRequest $request)
    {
        $tenant = Tenant::findOrFail(tenant()->getTenantKey());

        $tenant->update($request->validated());

        return response()->json([
            'message' => 'Tenant mis à jour avec succès.',
            'tenant' => $tenant->only(['settings', 'peppol_identifier']),
        ]);
    }
}
