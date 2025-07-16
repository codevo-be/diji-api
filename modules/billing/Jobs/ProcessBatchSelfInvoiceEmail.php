<?php

namespace Diji\Billing\Jobs;

use App\Services\Brevo;
use App\Services\ZipService;
use Diji\Billing\Models\SelfInvoice;
use Diji\Billing\Services\PdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class ProcessBatchSelfInvoiceEmail implements ShouldQueue
{
    use Queueable, Dispatchable;

    protected array $validIds;
    protected string $email;

    public function __construct(array $validIds, string $email)
    {
        $this->validIds = $validIds;
        $this->email = $email;
    }

    public function handle(): void
    {
        $pdfFiles = array();

        foreach ($this->validIds as $id) {
            $selfInvoice = SelfInvoice::findOrFail($id)->load('items');

            $fileName = 'auto-facturation-' . str_replace("/", "-", $selfInvoice->identifier) . '.pdf';
            $pdfString = PdfService::generateSelfInvoice($selfInvoice);
            $pdfFiles[$fileName] = $pdfString;
        }

        $zipPath = ZipService::createTempZip($pdfFiles);
        $zipContent = file_get_contents($zipPath);

        $mailService = new Brevo();
        $mailService->to($this->email, "Diji")
            ->subject('Auto-facturations')
            ->content('Voici vos auto-facturations')
            ->attachments([
                [
                    'output' => $zipContent,
                    'filename' => 'auto-facturations.zip',
                ],
            ])
            ->send();

        ZipService::deleteTempZip($zipPath);
    }
}
