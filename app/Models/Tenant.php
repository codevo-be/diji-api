<?php

namespace App\Models;

use Diji\Module\Models\Module;
use Diji\Module\Models\TenantModule;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

    protected $fillable = ['id', 'name', 'data'];

    public static function getCustomColumns(): array
    {
        return ['id', 'name'];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_tenants');
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'tenant_modules');
    }
}
