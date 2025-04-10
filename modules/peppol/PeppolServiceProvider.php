<?php

namespace Diji\Peppol;

use Illuminate\Support\ServiceProvider;

class PeppolServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerTenantMigrations();

        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');
    }

    public function boot() {}

    protected function registerTenantMigrations(): void
    {
        if (!file_exists(config_path('tenancy.php'))) {
            return;
        }

        $tenantMigrationsPath = __DIR__ . '/Database/Migrations/tenant';
        $existingPaths = config('tenancy.migration_parameters.--path', []);

        config([
            'tenancy.migration_parameters.--path' => array_unique([...$existingPaths, $tenantMigrationsPath]),
        ]);
    }
}
