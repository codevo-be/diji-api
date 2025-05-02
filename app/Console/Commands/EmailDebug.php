<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Brevo;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\SendSmtpEmail;

class EmailDebug extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test';

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
        try {
            tenancy()->initialize("gvt");
            $brevo = new Brevo();

            $brevo->to("maxime@codevo.be", "Maxime");
            $brevo->subject("test API");
            $brevo->content("test html");

            $brevo->send();
        } catch (\Exception $e) {
            dump($e->getMessage());
        }
    }
}
