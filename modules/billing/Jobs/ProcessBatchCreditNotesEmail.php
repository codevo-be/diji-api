<?php

namespace Diji\Billing\Jobs;

use App\Services\Brevo;
use App\Services\ZipService;
use Diji\Billing\Models\CreditNote;
use Diji\Billing\Services\PdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBatchCreditNotesEmail implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected string $content;
    protected string $email;

    /**
     * Create a new job instance.
     */
    public function __construct(string $content, string $email)
    {
        $this->content = $content;
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mailService = new Brevo();

        $mailService->to($this->email)
            ->subject('Factures')
            ->content('Voici vos factures')
            ->attachments([
                [
                    'output' => $this->content,
                    'filename' => 'factures.zip',
                ],
            ])
            ->send();
    }
}
