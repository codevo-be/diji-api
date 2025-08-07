<?php

namespace App\Console\Commands;

use App\Models\Meta;
use App\Models\Tenant;
use App\Services\Brevo;
use Barryvdh\DomPDF\Facade\Pdf;
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
                    if ($invoice->days_diff === 0) {
                        $subject = "Rappel : Votre facture est arrivée à échéance";
                        $body = "Bonjour,\n\nNous vous informons que votre facture n°{$invoice->identifier} € est arrivée à échéance aujourd’hui ({$invoice->due_date}).\n\nMerci de procéder au règlement dans les plus brefs délais.";
                    } else if ($invoice->days_diff === 7) {
                        $subject = 'Relance : Facture impayée depuis 7 jours';
                        $body = "Bonjour,\n\nNous constatons que la facture n°{$invoice->identifier}, n’a pas encore été réglée alors qu’elle était due le {$invoice->due_date}.\n\nMerci de bien vouloir régulariser votre situation dans les meilleurs délais.";
                    } else if ($invoice->days_diff === 30) {
                        $subject = 'Dernier rappel : Facture impayée depuis plus de 30 jours';
                        $body = "Bonjour,\n\nMalgré nos précédentes relances, la facture n°{$invoice->identifier} reste impayée depuis plus de 30 jours.\n\nNous vous invitons à régler cette facture sans délai afin d’éviter d’éventuelles pénalités.";
                    } else {
                        continue;
                    }

                    $qrcode = \Diji\Billing\Helpers\Invoice::generateQrCode($invoice->issuer["name"], $invoice->issuer["iban"], $invoice->total, $invoice->structured_communication);
                    $logo = Meta::getValue('tenant_billing_details')['logo'];

                    $pdf = PDF::loadView('billing::invoice', [
                        ...$invoice->toArray(), // TODO Vérifier si ceci fonctionne avec le select qui prend invoice + days_diff de sql
                        "logo" => $logo,
                        "qrcode" => $qrcode
                    ]);

                    $mailService->attachments([
                        [
                            "filename" => "facture-" . str_replace("/", "-", $invoice->identifier) . ".pdf",
                            "output" => $pdf->output()
                        ]
                    ]);

                    $mailService
                        ->to($email, $recipient['name'])
                        ->subject($subject)
                        ->view("billing::email-invoice", ["invoice" => $invoice, "logo" => $logo,  "qrcode" => $qrcode,  "body" => $body])
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
