<?php

namespace App\Console\Commands;

use Diji\Billing\Models\Transaction;
use Diji\Billing\Services\NordigenService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class FetchDailyTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:fetch-daily';

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

        foreach ($tenants as $tenant){

            tenancy()->initialize($tenant->id);

            try {
                $nordigen = new NordigenService();
                $transaction = $nordigen->getTransactions();

                foreach ($transaction["transactions"]["booked"] as $item){
                    Transaction::create([
                        "transaction_id" => $item["transactionId"],
                        "structured_communication" => isset($item["remittanceInformationStructured"]) ? $item["remittanceInformationStructured"] : null,
                        "creditor_name" => isset($item["creditorName"]) ? strtolower($item["creditorName"]) : null,
                        "creditor_account" => str_replace("BE", '', $item["creditorAccount"]["iban"]),
                        "debtor_name" => strtolower($item["debtorName"]),
                        "debtor_account" => str_replace("BE", '', $item["debtorAccount"]["iban"]),
                        "amount" => floatval($item["transactionAmount"]["amount"]),
                        "response" => $item,
                        "date" => Carbon::now()->subDay()
                    ]);
                }
            }catch (\Exception $e){
                Log::channel('transaction')->info("Tenant : $tenant->name");
                Log::channel('transaction')->info($e->getMessage());
            }
        }
    }
}
