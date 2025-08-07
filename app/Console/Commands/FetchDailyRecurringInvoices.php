<?php

namespace App\Console\Commands;

use Diji\Billing\Models\Invoice;
use Diji\Billing\Models\RecurringInvoice;
use Diji\Billing\Models\Transaction;
use Diji\Billing\Services\NordigenService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FetchDailyRecurringInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring-invoice:fetch-daily';

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
        $tenants = \App\Models\Tenant::all();

        foreach ($tenants as $tenant) {

            tenancy()->initialize($tenant->id);

            try {
                $recurring_invoices = RecurringInvoice::where('status', RecurringInvoice::STATUS_ACTIVE)->get();

                foreach ($recurring_invoices as $recurring_invoice) {
                    if (Carbon::parse($recurring_invoice->next_run_at)->startOfDay()->lessThanOrEqualTo(Carbon::today())) {

                        DB::beginTransaction();

                        try {
                            $invoice = new Invoice();
                            $invoice->issuer = $recurring_invoice->issuer;
                            $invoice->recipient = $recurring_invoice->recipient;
                            $invoice->contact_id = $recurring_invoice->contact_id;
                            $invoice->subtotal = $recurring_invoice->subtotal;
                            $invoice->taxes = $recurring_invoice->taxes;
                            $invoice->total = $recurring_invoice->total;
                            $invoice->save();

                            foreach ($recurring_invoice->items as $item) {
                                $newItem = $item->replicate();
                                $newItem->model_type = Invoice::class;
                                $newItem->model_id = $invoice->id;
                                $newItem->save();
                            }

                            $recurring_invoice->next_date_at = RecurringInvoice::generateNexRunDate($recurring_invoice);
                            $recurring_invoice->save();
                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Log::channel("recurring-invoice")->info("Erreur facture récurrente ($recurring_invoice->id)");
                            Log::channel("recurring-invoice")->info($e->getMessage());
                            continue;
                        }
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}
