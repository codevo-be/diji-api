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
        $mailService = new Brevo();
        $pdfFiles = [];

        foreach($this->validIds as $id) { //TODO Faire une gestion d'erreur
            $credit_note = CreditNote::findOrFail($id)->load('items');

            $fileName = 'facture-' .str_replace("/", "-", $credit_note->identifier);
            $pdfString = PdfService::generateCreditNote($credit_note);

            $pdfFiles[$fileName] = $pdfString;
        }

        $zipPath = ZipService::createTempZip($pdfFiles);
        $zipContent = file_get_contents($zipPath);

        $mailService->to($this->email)
            ->subject('Factures')
            ->content('Voici vos factures')
            ->attachments([
                [
                    'output' => $zipContent,
                    'filename' => 'factures.zip',
                ],
            ])
            ->send();

        // Suppression du fichier zip temporaire
        ZipService::deleteTempZip($zipPath);
    }
}
