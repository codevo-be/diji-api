<?php

namespace App\Console\Commands;

use App\Models\Meta;
use App\Services\Brevo;
use Diji\Billing\Models\NordigenAccount;
use Diji\Billing\Notifications\RequisitionExpirationNotification;
use Diji\Billing\Services\NordigenService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
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

        foreach ($tenants as $tenant) {
            tenancy()->initialize($tenant->id);

            $account = NordigenAccount::latest()->first();
            $daysLeft = $account ? Carbon::now()->diffInDays(Carbon::parse($account->account_expires_at)) : 0;

            if (in_array($daysLeft, $alertDays) || $daysLeft <= 0) {
                try {
                    $nordigenService = new NordigenService();
                    $nordigen_institution_id = \App\Models\Meta::getValue('nordigen_institution_id');
                    $response = $nordigenService->createRequisition($nordigen_institution_id);

                    $link = $response["link"];
                    $subject = $daysLeft <= 0
                        ? "🔴 Votre accès bancaire a expiré !"
                        : "⏳ Votre connexion bancaire expire dans {$daysLeft} jours";

                    $brevo = new Brevo();

                    $brevo->to(Meta::getValue("nordigen_admin_email"), $tenant->name)->subject($subject)->content(utf8_encode("<p>Bonjour,</p><p>Votre connexion bancaire avec Gocardless expire dans <strong>" . $daysLeft . " jours**</strong>.</p><p>Pour continuer à utiliser ce service, veuillez renouveler votre connexion bancaire.</p><a href='" . $link . "'>Renouveler ma connexion</a>"))->send();

                    Log::channel('transaction')->info("verify-bank-account - Tenant : $tenant->name");
                    Log::channel('transaction')->info("Account disabled : " . $daysLeft . "Email send !");
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }
}
