<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Brevo;
use Carbon\Carbon;
use Diji\Billing\Models\Invoice;
use Diji\Billing\Models\InvoiceEmailLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InvoiceCheckPaid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:check-paid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "A command that will send an email to the user if the invoice hasn't been paid (day 0, +7, +30)";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mailService = new Brevo();
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->info("Traitement du tenant : " . $tenant->name);

            $sql = "select *,
                        DATEDIFF(CAST(NOW() AS DATE), due_date) as days_diff
                    from {$tenant->name}.invoices
                    where CAST(NOW() AS DATE) IN (
                              due_date,
                              DATE_ADD(due_date, INTERVAL 7 DAY),
                              DATE_ADD(due_date, INTERVAL 1 MONTH)
                              )
                        AND status = 'pending'
                        AND check_paid_notification = true;";

            $invoices = DB::select($sql);

            foreach ($invoices as $invoice) {
                $recipient = json_decode($invoice->recipient, true);
                $email = $recipient['email'] ?? null;
                if ($email === null) continue;

                $this->info($invoice->id);
                $succeed = false;
                $messageError = null;

                try {

                    $mailService
                        ->to($email)
                        ->subject('Factures non payées')
                        ->content("Paie ta facture")
                        ->send();

                    $succeed = true;
                } catch (\Exception $e) {
                    $this->error($e->getMessage());
                    $messageError = $e->getMessage();
                } finally {
                    DB::insert("
                            INSERT INTO {$tenant->name}.invoice_email_logs
                                (invoice_id, recipient_email, sent_at, extended_date, success, error_message, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ", [
                        $invoice->id,
                        $email,
                        now(),
                        $invoice->days_diff,
                        $succeed,
                        $messageError,
                        now(),
                        now()
                    ]);
                }
            }
        }
    }
}
