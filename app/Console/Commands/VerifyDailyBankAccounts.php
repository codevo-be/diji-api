<?php

namespace App\Console\Commands;

use App\Models\Meta;
use Diji\Billing\Models\NordigenAccount;
use Diji\Billing\Notifications\RequisitionExpirationNotification;
use Diji\Billing\Services\NordigenService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class VerifyDailyBankAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nordigen:verify-bank-account';

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
        $alertDays = [30, 15, 7, 1];
        $tenants = \App\Models\Tenant::all();

        foreach ($tenants as $tenant){
            tenancy()->initialize($tenant->id);

            $account = NordigenAccount::latest()->first();
            $daysLeft = $account ? Carbon::now()->diffInDays(Carbon::parse($account->account_expires_at)) : 0;

            if (in_array($daysLeft, $alertDays) || $daysLeft <= 0) {
                $nordigenService = new NordigenService();
                $nordigen_institution_id = \App\Models\Meta::getValue('nordigen_institution_id');
                $response = $nordigenService->createRequisition($nordigen_institution_id);

                Notification::route('mail', Meta::getValue("nordigen_admin_email"))
                    ->notify(new RequisitionExpirationNotification($daysLeft, $response["link"]));
            }
        }
    }
}
