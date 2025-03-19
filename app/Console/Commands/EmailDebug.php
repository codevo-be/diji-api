<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
            Mail::raw('Test email body', function ($message) {
                $message->to('maxime@codevo.be')
                    ->subject('SMTP Test');
            });

            $this->info('SMTP test email sent successfully!');
        } catch (\Exception $e) {
            Log::error('SMTP test failed: ' . $e->getMessage());

            $this->error('SMTP Test Failed:');
            $this->error('Error Message: ' . $e->getMessage());

            $this->error('Stack Trace: ' . $e->getTraceAsString());
        }
    }
}
