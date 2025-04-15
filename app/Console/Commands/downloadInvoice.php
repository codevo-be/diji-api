<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Diji\Billing\Models\CreditNote;
use Diji\Billing\Models\Invoice;
use Diji\Billing\Models\SelfInvoice;
use Diji\Billing\Services\PdfService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class downloadInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice:download {tenant}';

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
        $tenant = Tenant::find($tenant_slug);

        tenancy()->initialize($tenant);

        $invoices = Invoice::query()->whereBetween('date', [
        "2025-01-01",
        "2025-03-31"
    ])->get();

        foreach ($invoices as $invoice){
            try {
                $pdfString = PdfService::generateInvoice($invoice);

                $path = storage_path('app/invoices/' . str_replace("/", "-", $invoice->identifier) . '.pdf');

                if (!file_exists(dirname($path))) {
                    mkdir(dirname($path), 0755, true);
                }

                file_put_contents($path, $pdfString);
            }catch (\Exception $e){
                dump('error');
                dump($e->getMessage());
            }
        }

        $credit_notes = CreditNote::query()->whereBetween('date', [
            "2025-01-01",
            "2025-03-31"
        ])->get();

        foreach ($credit_notes as $credit_note){
            try {
                $pdfString = PdfService::generateCreditNote($credit_note);

                $path = storage_path('app/credit-note/' . str_replace("/", "-", $credit_note->identifier) . '.pdf');

                if (!file_exists(dirname($path))) {
                    mkdir(dirname($path), 0755, true);
                }

                file_put_contents($path, $pdfString);
            }catch (\Exception $e){
                Log::info(json_encode($e));
                dump('error');
                dump($e->getMessage());
            }
        }

        $self_invoices = SelfInvoice::query()->whereBetween('date', [
            "2025-01-01",
            "2025-03-31"
        ])->get();

        foreach ($self_invoices as $self_invoice){
            try {
                $pdfString = PdfService::generateSelfInvoice($self_invoice);

                $path = storage_path('app/self-invoices/' . str_replace("/", "-", $self_invoice->identifier) . '.pdf');

                if (!file_exists(dirname($path))) {
                    mkdir(dirname($path), 0755, true);
                }

                file_put_contents($path, $pdfString);
            }catch (\Exception $e){
                dump('error');
                dump($e->getMessage());
            }
        }
    }
}
