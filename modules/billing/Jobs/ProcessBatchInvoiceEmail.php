<?php

namespace Diji\Billing\Jobs;

use App\Services\Brevo;
use App\Services\ZipService;
use Diji\Billing\Models\Invoice;
use Diji\Billing\Services\PdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBatchInvoiceEmail implements ShouldQueue
{
    use Queueable, Dispatchable;

    protected array $validIds;
    protected string $email;

    /**
     * Create a new job instance.
     */
    public function __construct(array $validIds, string $email)
    {
        $this->validIds = $validIds;
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pdfFiles = array();

        foreach($this->validIds as $id) {
            $invoice = Invoice::findOrFail($id)->load('items');

            $fileName = 'facture-' . str_replace("/", "-", $invoice->identifier) . '.pdf';
            $pdfString = PdfService::generateInvoice($invoice);

            $pdfFiles[$fileName] = $pdfString;
        }
        $zipPath = ZipService::createTempZip($pdfFiles);
        $zipContent = file_get_contents($zipPath);

        $mailService = new Brevo();
        $mailService->to($this->email, "Diji")
            ->subject('Factures')
            ->content('Voici vos factures')
            ->attachments([
                [
                    'output' => $zipContent,
                    'filename' => 'factures.zip',
                ],
            ])
            ->send();

        ZipService::deleteTempZip($zipPath);
    }
}
