<?php

namespace Diji\Billing\Jobs;

use App\Services\Brevo;
use App\Services\ZipService;
use Diji\Billing\Models\CreditNote;
use Diji\Billing\Services\PdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBatchCreditNotesEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(){}

    /**
     * Execute the job.
     */
    public function handle($validIds, string $email): void
    {

    }
}
