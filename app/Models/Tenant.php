<?php

namespace App\Models;

use Diji\Module\Models\Module;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

    protected $fillable = ['id', 'name', 'data', 'settings', 'peppol_identifier'];

    protected $casts = [
        'data' => 'array',
        'settings' => 'array',
    ];

    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'settings', 'peppol_identifier'];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_tenants');
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'tenant_modules');
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
    }

    protected static function booted(): void
    {
        static::saving(function (Tenant $tenant) {
            $vat = $tenant->settings['vat_number'] ?? null;

            if ($vat && str_starts_with(strtoupper($vat), 'BE')) {
                $normalizedVat = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $vat)); // BE1016227032
                $tenant->peppol_identifier = '9925:' . $normalizedVat;
            }
        });
    }
}
