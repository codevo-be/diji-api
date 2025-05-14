<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create {tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenant_name = $this->argument('tenant');
        $tenant_slug = Str::slug($tenant_name);

        if (Tenant::find($tenant_slug)) {
            $this->error("Le tenant '$tenant_name' existe déjà.");
            return 0;
        }

        DB::beginTransaction();

        try {
            $tenant = \App\Models\Tenant::create([
                "id" => $tenant_slug,
                "name" => $tenant_name
            ]);

            $this->info("✅ Tenant '$tenant_name' créé avec succès.");

            tenancy()->initialize($tenant->id);

            DB::commit();

            return 0;
        }catch (\Exception $e){
            DB::rollBack();

            $this->error($e->getMessage());

            return 0;
        }
    }
}
