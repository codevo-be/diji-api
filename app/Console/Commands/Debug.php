<?php

namespace App\Console\Commands;

use Diji\Billing\Models\Invoice;
use Illuminate\Console\Command;

class Debug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug';

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

        tenancy()->initialize('codevo');

        $invoices = Invoice::all();

        foreach ($invoices as $invoice) {
            $transaction_amount = $invoice->transactions()->sum('amount') ?? 0;

            if (isset($invoice->total) && $invoice->total && $transaction_amount >= $invoice->total) {
                $invoice->update([
                    "status" => Invoice::STATUS_PAYED
                ]);
            }
        }
    }
}
